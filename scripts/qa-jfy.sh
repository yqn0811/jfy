#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

RUN_BACKEND="${JFY_QA_BACKEND:-1}"
RUN_ALBUM_WEB="${JFY_QA_ALBUM_WEB:-0}"
RUN_FILE_WEB="${JFY_QA_FILE_WEB:-0}"
RUN_MINIAPP="${JFY_QA_MINIAPP:-0}"

log() {
  printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    printf 'Missing required command: %s\n' "$1" >&2
    exit 1
  fi
}

check_backend() {
  require_cmd php
  log "Checking PHP syntax"
  find "$ROOT_DIR/backend/app" "$ROOT_DIR/backend/config" "$ROOT_DIR/backend/route" \
    -type f -name '*.php' -print0 | xargs -0 -n 1 php -l >/dev/null

  if command -v composer >/dev/null 2>&1; then
    log "Validating composer metadata"
    (cd "$ROOT_DIR/backend" && composer validate --no-check-publish --strict)
  else
    log "Skipping composer validate because composer is not installed"
  fi
}

build_album_web() {
  require_cmd pnpm
  log "Building album-web"
  (cd "$ROOT_DIR/album-web" && pnpm build)
}

build_file_web() {
  require_cmd pnpm
  log "Building file-web against test API"
  (cd "$ROOT_DIR/file-web" && pnpm build:test)
}

build_miniapp_test() {
  require_cmd npm
  log "Building miniapp against test API"
  (cd "$ROOT_DIR/miniapp" && npm run build:mp-weixin:test)
}

if [[ "$RUN_BACKEND" == "1" ]]; then
  check_backend
else
  log "Skipping backend checks because JFY_QA_BACKEND=$RUN_BACKEND"
fi

if [[ "$RUN_ALBUM_WEB" == "1" ]]; then
  build_album_web
else
  log "Skipping album-web build because JFY_QA_ALBUM_WEB=$RUN_ALBUM_WEB"
fi

if [[ "$RUN_FILE_WEB" == "1" ]]; then
  build_file_web
else
  log "Skipping file-web build because JFY_QA_FILE_WEB=$RUN_FILE_WEB"
fi

if [[ "$RUN_MINIAPP" == "1" ]]; then
  build_miniapp_test
else
  log "Skipping miniapp build because JFY_QA_MINIAPP=$RUN_MINIAPP"
fi

log "Jiafangyun QA completed"
