#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${1:-}"
REMOTE_NAME="${REMOTE_NAME:-github}"
REPO_URL="${REPO_URL:-git@github.com:yqn0811/jfy.git}"
SERVER="${SERVER:-root@103.117.120.231}"
SERVER_SOURCE_DIR="${SERVER_SOURCE_DIR:-/opt/jfy/file-web-test-source}"
SERVER_TARGET_DIR="${SERVER_TARGET_DIR:-/www/wwwroot/file-test.jfyuntu.com/current}"
PUBLIC_URL="${PUBLIC_URL:-https://file-test.jfyuntu.com/}"

log() {
  printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

require_cmd git
require_cmd ssh

cd "$ROOT_DIR"

if [[ -z "$BRANCH" ]]; then
  BRANCH="$(git branch --show-current)"
fi
[[ -n "$BRANCH" ]] || fail "Cannot resolve current branch; pass a branch name explicitly."

[[ -z "$(git status --porcelain)" ]] || fail "Working tree is not clean. Commit and push before deploying."

LOCAL_SHA="$(git rev-parse "$BRANCH")"
REMOTE_SHA="$(git ls-remote --heads "$REMOTE_NAME" "$BRANCH" | awk '{print $1}')"
[[ -n "$REMOTE_SHA" ]] || fail "Branch '$BRANCH' was not found on remote '$REMOTE_NAME'. Push it first."
[[ "$LOCAL_SHA" == "$REMOTE_SHA" ]] || fail "Local $LOCAL_SHA is not pushed to $REMOTE_NAME/$BRANCH ($REMOTE_SHA)."

log "Deploying file-web test from $REMOTE_NAME/$BRANCH@$LOCAL_SHA"

ssh "$SERVER" \
  "BRANCH='$BRANCH' REPO_URL='$REPO_URL' EXPECTED_SHA='$LOCAL_SHA' SOURCE_DIR='$SERVER_SOURCE_DIR' TARGET_DIR='$SERVER_TARGET_DIR' PUBLIC_URL='$PUBLIC_URL' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail

log() {
  printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Missing required command on server: $1"
}

require_cmd git
require_cmd rsync
require_cmd curl

if ! command -v node >/dev/null 2>&1; then
  fail "Node is not installed on server. Install Node 18.20.8, ^20.3.0, or >=22.0.0 before deploying."
fi

node -e '
const v = process.versions.node.split(".").map(Number)
const ok = (
  (v[0] === 18 && (v[1] > 20 || (v[1] === 20 && v[2] >= 8))) ||
  (v[0] === 20 && v[1] >= 3) ||
  v[0] >= 22
)
if (!ok) {
  console.error(`Node ${process.versions.node} does not satisfy file-web build requirements`)
  process.exit(1)
}
'

if ! command -v pnpm >/dev/null 2>&1; then
  if command -v corepack >/dev/null 2>&1; then
    corepack enable
    corepack prepare pnpm@10.30.3 --activate
  else
    fail "pnpm is not installed on server and corepack is unavailable."
  fi
fi

mkdir -p "$(dirname "$SOURCE_DIR")"

if [[ ! -d "$SOURCE_DIR/.git" ]]; then
  log "Cloning source repository"
  git clone --branch "$BRANCH" --single-branch "$REPO_URL" "$SOURCE_DIR"
fi

cd "$SOURCE_DIR"
git remote set-url origin "$REPO_URL"
git fetch origin "$BRANCH"
git checkout -B "$BRANCH" "origin/$BRANCH"
git reset --hard "origin/$BRANCH"
git clean -fd

CURRENT_SHA="$(git rev-parse HEAD)"
[[ "$CURRENT_SHA" == "$EXPECTED_SHA" ]] || fail "Server checked out $CURRENT_SHA, expected $EXPECTED_SHA."

log "Building file-web against test API"
cd "$SOURCE_DIR/file-web"
pnpm install --frozen-lockfile
pnpm build:test

[[ -d dist ]] || fail "Build did not create file-web/dist."
[[ -d "$TARGET_DIR" ]] || fail "Target directory does not exist: $TARGET_DIR"
[[ ! -L "$TARGET_DIR" ]] || fail "Target directory must be a fixed directory, not a symlink: $TARGET_DIR"

log "Publishing build output to fixed nginx directory"
rsync -av --delete dist/ "$TARGET_DIR/"

log "Checking public endpoint"
curl -fsSI "$PUBLIC_URL" | sed -n '1,8p'

log "file-web test deploy completed at $CURRENT_SHA"
REMOTE_SCRIPT
