# 部署说明

最后核对时间：2026-07-14

这份文档是 `jfy` 子仓库的部署依据。后续部署必须按这里写明的项目、
环境、域名、目录和数据库执行。没有被标记为“已确认”的目标，不允许猜测，
必须先停下来核对。

## 适用范围

- 本地子仓库：`/Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy`
- 当前工作分支：`develop`
- 主仓库不在本文档范围内。不要在
  `/Users/mac/Documents/trae_projects/sub2api/ai_jf` 执行部署或 git 操作。
- 未经用户明确要求，不创建备份目录。
- 前端测试发布使用 Git + 服务器构建：本地提交并推送后，由测试服务器拉取
  GitHub 仓库，在服务器执行构建，再把构建产物覆盖到已确认的固定 nginx
  目录。不得再从本地直接同步 `dist/` 到测试环境。
- 所有前端站点都固定发布到一个已确认目录，直接覆盖该目录内容；不在
  `/www/wwwroot` 下新建 `releases`、日期目录、备份目录，也不使用 `current`
  软链切版本。nginx 对外目录不能放 `.git`。
- 未经用户明确说“正式环境/生产环境部署”，并确认目标域名和目录，不部署正式环境。
- `.env` 里的密码、token、密钥不能打印到聊天里，也不能提交到 git。

## 本地项目

| 项目 | 本地目录 | 类型 | 构建产物 | 说明 |
| --- | --- | --- | --- | --- |
| `backend` | `backend/` | ThinkPHP 后端 | 无 | 服务器运行环境是 PHP 7.2 |
| `album-web` | `album-web/` | Vue/Vite 静态相册前端 | `album-web/dist/` | 这是“相册”项目 |
| `file-web` | `file-web/` | Vue/Vite 文件传输前端 | `file-web/dist/` | 文件传输项目，不是相册 |
| `miniapp` | `miniapp/` | UniApp 微信小程序 | `miniapp/dist/build/mp-weixin/` | 当前配置指向测试 API |

## 已确认测试环境

服务器：`103.117.120.231`
主机名：`qlhkb-CfQ5tOF0G1Hb`

| 项目/入口 | 域名 | 服务器目录 | 状态 |
| --- | --- | --- | --- |
| 后端 API | `api-test.jfyuntu.com` | `/www/wwwroot/api-test.jfyuntu.com/current/public` | 已确认，是 active nginx root |
| 后端应用目录 | 无 | `/www/wwwroot/api-test.jfyuntu.com/current` | 已确认 |
| 相册 `album-web` | `pic-test.jfyuntu.com` | `/www/wwwroot/pic-test.jfyuntu.com/current` | 已确认，是固定测试相册入口目录 |
| 文件传输 `file-web` | `file-test.jfyuntu.com` | `/www/wwwroot/file-test.jfyuntu.com/current` | 已确认，是固定测试文件传输入口目录 |

测试后端数据库，来自 `/www/wwwroot/api-test.jfyuntu.com/current/.env`：

| 字段 | 值 |
| --- | --- |
| 类型 | `mysql` |
| 主机 | `127.0.0.1` |
| 端口 | `3306` |
| 数据库 | `yunce_jiumirw_co` |
| 用户名 | `yunce_jiumirw_co` |
| 字符集 | `utf8mb4` |

测试环境备注：

- `pic-test.jfyuntu.com` 解析到 `103.117.120.231`。
- `file-test.jfyuntu.com` 解析到 `103.117.120.231`。
- `pic-test.jfyuntu.com` 的 nginx root 是 `/www/wwwroot/pic-test.jfyuntu.com/current`。
- `file-test.jfyuntu.com` 的 nginx root 是 `/www/wwwroot/file-test.jfyuntu.com/current`，
  使用 SPA fallback 回落到 `/index.html`。
- `/www/wwwroot/pic-test.jfyuntu.com/current` 已确认是固定真实目录，不是软链。
- 前端测试发布只能把构建产物同步到对应固定目录，不能再创建
  `/www/wwwroot/*/releases/<release-name>`。
- 文件传输后端接口挂在 `https://api-test.jfyuntu.com/api/file/...`。
- 文件传输 PostgreSQL 使用 103 服务器 `jf-postgres` 容器里的 `ai_jf` 库，
  账号同 AI 生图服务，业务表统一使用 `ft_` 前缀。
- 旧上传页 `assets/page/product-list.html` 已确认不用，不再作为部署目标或保留入口。
- `115.190.245.200` / `www.mia-233.cn` 不属于当前相册测试部署目标。

## 已确认正式环境

服务器：`146.56.222.154`
主机名：`VM-0-4-tencentos`

| 项目/入口 | 域名 | 服务器目录 | 状态 |
| --- | --- | --- | --- |
| 后端 API | `api.jfyuntu.com`、`api.izhixu.com` | `/www/wwwroot/www.jfyuntu.com/backend/public` | 已确认，是 active nginx root |
| 后端应用目录 | 无 | `/www/wwwroot/www.jfyuntu.com/backend` | 已确认 |
| 相册 `album-web` | `pic.jfyuntu.com`、`pic.izhixu.com` | `/www/wwwroot/www.jfyuntu.com/backend/view/pic` | 已确认，是固定正式相册入口目录 |
| 旧上传页 | `pic.jfyuntu.com/assets/page/product-list.html` | `/www/wwwroot/www.jfyuntu.com/backend/view/pc/assets/page/product-list.html` | 已确认可访问 |
| 官网 | `www.jfyuntu.com`、`www.izhixu.com` | `/www/wwwroot/www_jfyuntu_site` | 独立站点，不是相册部署目标 |
| 文件传输占位站 | `file.izhixu.com` | `/www/wwwroot/file.izhixu.com` | 已确认 nginx root，但是否作为 `file-web` 部署目标未确认 |

正式后端数据库，来自 `/www/wwwroot/www.jfyuntu.com/backend/.env`：

| 字段 | 值 |
| --- | --- |
| 类型 | `mysql` |
| 主机 | `127.0.0.1` |
| 端口 | `3306` |
| 数据库 | `yunce_jiumirw_co` |
| 用户名 | `yunce_jiumirw_co` |
| 字符集 | `utf8mb4` |

正式环境备注：

- 测试和正式的数据库名一样，但都写的是 `127.0.0.1`，表示各自连接本机
  MySQL，不是同一台数据库服务。
- `file.izhixu.com` 不是相册入口，不要把相册部署到这里。
- `pic.jfyuntu.com` 是正式环境，不允许当测试环境使用。
- 正式前端发布同样只能覆盖已确认固定目录，不能创建 `releases`、日期目录、
  备份目录或软链切换目录。

## 构建命令

命令只能在对应项目目录里执行。

两个前端都是 Vue/Vite 单页应用。业务页面路由统一不带 `.html`，例如
`/share-home`、`/management-workbench`、`/workbench`。部署 nginx 必须配置 SPA
fallback，把不存在的前端路径回落到 `/index.html`；不要再发布或依赖旧的带后缀
业务入口。

部署前先运行统一 QA 入口：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
./scripts/qa-jfy.sh
```

默认只检查 PHP 语法和 Composer 元数据。需要覆盖前端构建时按项目打开开关：

```bash
JFY_QA_ALBUM_WEB=1 JFY_QA_FILE_WEB=1 ./scripts/qa-jfy.sh
JFY_QA_MINIAPP=1 ./scripts/qa-jfy.sh
```

文件传输后端还必须配置匿名过期文件清理定时任务，避免本地私有存储持续增长：

```bash
*/10 * * * * cd /www/wwwroot/api-test.jfyuntu.com/current && php think file:cleanup-expired-anonymous --limit 200 >> runtime/log/file_cleanup.log 2>&1
```

生产环境使用对应已确认后端应用目录；首次配置前先执行 `--dry-run` 确认输出。

文件传输开放上传安全基线：

```bash
# 上传环境健康检查，生产发布前必须为 ok；测试环境允许记录 failed 项后再逐项补齐。
cd /www/wwwroot/api-test.jfyuntu.com/current
php think file:upload-health
php think file:upload-health --webhook 'https://example.invalid/webhook'

# 风险事件摘要，可接企业微信/飞书等 webhook。
php think file:risk-report --minutes 60 --threshold 10
php think file:risk-report --minutes 60 --threshold 10 --webhook 'https://example.invalid/webhook'
```

测试和生产环境的大文件上传配置必须三层对齐：

1. Nginx 站点配置：

```nginx
client_max_body_size 520m;
client_body_timeout 600s;
send_timeout 600s;
proxy_read_timeout 600s;
proxy_send_timeout 600s;
```

2. PHP-FPM 配置：

```ini
max_file_uploads = 50
upload_max_filesize = 500M
post_max_size = 520M
max_input_time = 600
max_execution_time = 600
memory_limit = 512M
```

3. 后端 `.env` 的 `[FILE_TRANSFER]` 或等价环境变量：

```ini
MAX_UPLOAD_MB=500
MAX_REGISTERED_UPLOAD_MB=500
ANONYMOUS_MAX_UPLOAD_MB=200
MAX_FILES_PER_REQUEST=50
HIGH_RISK_ATTACHMENT_EXTENSIONS=
MIN_FREE_DISK_MB=2048
MIN_TMP_FREE_DISK_MB=1024
DISK_CHECK_FAIL_CLOSED=true
TMP_CHECK_FAIL_CLOSED=true
UPLOAD_REQUEST_TIMEOUT_SECONDS=600
ENABLE_DIRECT_UPLOAD=false
DIRECT_UPLOAD_PROVIDERS=ten_cos,ali_oss
DIRECT_UPLOAD_POLICY_TTL_SECONDS=600
DIRECT_UPLOAD_VERIFY_OBJECT=true
DIRECT_UPLOAD_VERIFY_FAIL_CLOSED=true
DIRECT_UPLOAD_SECRET=<32位以上随机密钥>
ENABLE_ANTIVIRUS_SCAN=true
ANTIVIRUS_SCAN_COMMAND=clamscan --no-summary --infected %s
ANTIVIRUS_SCAN_TIMEOUT_SECONDS=15
ANTIVIRUS_SCAN_FAIL_CLOSED=true
ALERT_WEBHOOK_URL=
```

测试环境默认保持 `ENABLE_DIRECT_UPLOAD=false`，前端会自动回退到 PHP 上传；对象存储
bucket CORS、签名密钥和跨域 PUT 验证完成后再打开。生产环境如需打开直传，必须先用
小文件完成 `direct_upload_policy -> PUT -> register` 全链路验证。

ClamAV 必须在开启大文件开放前安装并验证：

```bash
clamscan --version
freshclam
clamscan --no-summary --infected /tmp/known-clean-file
printf '%s' 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > /tmp/eicar.txt
clamscan --no-summary --infected /tmp/eicar.txt
rm -f /tmp/eicar.txt
```

如果 `file:upload-health` 显示 `antivirus_ready=false`，不得开启 200MB/500MB 对外上传。
CentOS 7 的 EPEL ClamAV 数据库可能较旧；`freshclam` 若被 CDN 拒绝，至少安装
`clamav-data` 作为临时测试拦截，并把病毒库升级作为上线前阻断项。

## 前端发布规范

测试环境前端发布统一使用 Git + 服务器构建：

1. 本地只做提交、推送和必要 QA，不再把本地 `dist/` 推到服务器。
2. 服务器使用独立源码目录拉取 GitHub 仓库，源码目录不得作为 nginx root。
3. 服务器在源码目录里执行对应前端构建命令。
4. 构建产物覆盖到已确认固定 nginx 目录。
5. 发布后检查目标目录不是软链，并验证域名返回 200。

禁止事项：

- 不要在 `/www/wwwroot` 下创建 `releases`、日期目录或备份目录。
- 不要把 `current` 做成软链。
- 不要把前端部署到未在本文档确认的相似目录。
- 不要绕过 Git 用本地 `dist/` 直接覆盖测试前端。

测试文件前端 Git 发布：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
./scripts/deploy-file-web-test-git.sh
```

脚本会校验本地当前分支没有未提交内容，且当前提交已经推送到 GitHub；然后登录
测试服务器拉取同一个分支，在服务器执行 `pnpm install --frozen-lockfile` 和
`pnpm build:test`，最后把服务器上的 `file-web/dist/` 覆盖到
`/www/wwwroot/file-test.jfyuntu.com/current/`。

指定分支发布：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
./scripts/deploy-file-web-test-git.sh codex/ai-image-admin-list-display
```

测试服务器一次性前置条件：

```bash
ssh root@103.117.120.231 'git ls-remote git@github.com:yqn0811/jfy.git HEAD'
ssh root@103.117.120.231 'node -v && pnpm -v'
```

`file-web` 当前依赖要求 Node `18.20.8 || ^20.3.0 || >=22.0.0`。测试服务器是
CentOS 7，安装 Node 时必须选择兼容该系统 glibc 的发行方式。`pnpm` 建议通过
Node 自带的 `corepack` 激活；如果服务器缺少 `node` 或 `pnpm`，发布脚本会停止。

测试相册 Git 发布：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
./scripts/deploy-album-web-test-git.sh
```

脚本会校验本地当前分支没有未提交内容，且当前提交已经推送到 GitHub；然后登录
测试服务器拉取同一个分支，在服务器执行相册测试构建，最后把服务器上的
`album-web/dist/` 覆盖到 `/www/wwwroot/pic-test.jfyuntu.com/current/`。

应急手工覆盖只允许在用户明确说“临时用本地 dist 覆盖测试环境”后执行：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
rsync -avzn --delete file-web/dist/ root@103.117.120.231:/www/wwwroot/file-test.jfyuntu.com/current/
rsync -avz --delete file-web/dist/ root@103.117.120.231:/www/wwwroot/file-test.jfyuntu.com/current/
```

相册正式构建：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/album-web
pnpm install --frozen-lockfile
PUBLIC_JFYUNTU_BASE_PATH=/ PUBLIC_API_BASE=https://api.jfyuntu.com/api pnpm build
```

相册测试构建。只有在测试相册域名和目录确认后才能使用：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/album-web
pnpm install --frozen-lockfile
PUBLIC_JFYUNTU_BASE_PATH=/ PUBLIC_API_BASE=https://api-test.jfyuntu.com/api pnpm build
```

文件前端构建：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/file-web
pnpm install --frozen-lockfile
pnpm build:test
```

文件前端正式构建必须显式使用生产脚本：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/file-web
pnpm install --frozen-lockfile
pnpm build:prod
```

小程序构建：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/miniapp
npm install
npm run build:mp-weixin
```

小程序正式构建默认指向 `https://api.jfyuntu.com`。如需测试包，必须显式使用
`npm run build:mp-weixin:test`，该脚本指向 `https://api-test.jfyuntu.com`。

## 部署前确认门槛

任何部署前，必须先确认并写明这四项：

1. 项目：`backend`、`album-web`、`file-web` 或 `miniapp`
2. 环境：`test` 或 `production`
3. 域名：必须匹配本文档里的已确认域名
4. 目标目录：必须匹配本文档里的已确认目录

只要有一项缺失，或者和本文档不一致，就停止。不要根据 `pic`、`file`、`www`
这类相似名字，也不要根据旧备份目录去推断部署目标。

部署前本地只读检查：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy
GIT_CEILING_DIRECTORIES=/Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp git status --short --branch
```

部署前远端只读检查：

```bash
ssh <server> 'hostname; test -d <target-directory> && pwd'
ssh <server> 'grep -nE "server_name|root |alias " /www/server/panel/vhost/nginx/<site-conf>.conf'
```

前端远端目录还必须额外确认不是软链：

```bash
ssh <server> 'test ! -L <target-directory> && echo fixed-directory'
```

静态前端部署必须先跑 `rsync --dry-run`。只有用户确认了项目、环境、域名、
服务器和固定目标目录后，才能执行真实同步。真实同步只能同步到固定目标目录，
不能创建 release 目录或切换软链。
