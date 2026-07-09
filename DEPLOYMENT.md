# 部署说明

最后核对时间：2026-07-09

这份文档是 `jfy` 子仓库的部署依据。后续部署必须按这里写明的项目、
环境、域名、目录和数据库执行。没有被标记为“已确认”的目标，不允许猜测，
必须先停下来核对。

## 适用范围

- 本地子仓库：`/Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy`
- 当前工作分支：`develop`
- 主仓库不在本文档范围内。不要在
  `/Users/mac/Documents/trae_projects/sub2api/ai_jf` 执行部署或 git 操作。
- 未经用户明确要求，不创建备份目录。
- 未经用户明确说“正式环境/生产环境部署”，并确认目标域名和目录，不部署正式环境。
- `.env` 里的密码、token、密钥不能打印到聊天里，也不能提交到 git。

## 本地项目

| 项目 | 本地目录 | 类型 | 构建产物 | 说明 |
| --- | --- | --- | --- | --- |
| `backend` | `backend/` | ThinkPHP 后端 | 无 | 服务器运行环境是 PHP 7.2 |
| `album-web` | `album-web/` | Vue/Vite 静态相册前端 | `album-web/dist/` | 这是“相册”项目 |
| `file-web` | `file-web/` | Vue/Vite 文件传输前端 | `file-web/dist/` | 文件传输项目，不是相册 |
| `miniapp` | `miniapp/` | UniApp 微信小程序 | `miniapp/dist/build/mp-weixin/` | 当前配置指向正式 API |

## 已确认测试环境

服务器：`103.117.120.231`
主机名：`qlhkb-CfQ5tOF0G1Hb`

| 项目/入口 | 域名 | 服务器目录 | 状态 |
| --- | --- | --- | --- |
| 后端 API | `api-test.jfyuntu.com` | `/www/wwwroot/api-test.jfyuntu.com/current/public` | 已确认，是 active nginx root |
| 后端应用目录 | 无 | `/www/wwwroot/api-test.jfyuntu.com/current` | 已确认 |
| 相册 `album-web` | `pic-test.jfyuntu.com` | `/www/wwwroot/pic-test.jfyuntu.com/current` | 已确认，是测试相册入口 |
| 相册发布目录 | 无 | `/www/wwwroot/pic-test.jfyuntu.com/releases/<release-name>` | 已确认，`current` 是指向当前发布目录的软链 |
| 文件传输 `file-web` | `file-test.jfyuntu.com` | `/www/wwwroot/file-test.jfyuntu.com/current` | 已确认，是测试文件传输入口 |

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
- `/www/wwwroot/pic-test.jfyuntu.com/current` 是软链，当前指向
  `/www/wwwroot/pic-test.jfyuntu.com/releases/pic-album-20260709152842`。
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
| 相册 `album-web` | `pic.jfyuntu.com`、`pic.izhixu.com` | `/www/wwwroot/www.jfyuntu.com/backend/view/pic` | 已确认，是正式相册入口 |
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

## 构建命令

命令只能在对应项目目录里执行。

两个前端都是 Vue/Vite 单页应用。业务页面路由统一不带 `.html`，例如
`/share-home`、`/management-workbench`、`/workbench`。部署 nginx 必须配置 SPA
fallback，把不存在的前端路径回落到 `/index.html`；不要再发布或依赖旧的带后缀
业务入口。

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
PUBLIC_API_BASE=https://api-test.jfyuntu.com/api pnpm build
```

小程序构建：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/miniapp
npm install
npm run build:mp-weixin
```

小程序测试构建前必须先检查 `miniapp/common/config.js`。当前开发和发行配置都指向
`https://api.jfyuntu.com`，也就是正式 API。

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

静态前端部署必须先跑 `rsync --dry-run`。只有用户确认了项目、环境、域名、
服务器和目标目录后，才能执行真实同步。

## 当前阻塞点

- 小程序当前配置指向正式 API，不能在不改配置的情况下称为“测试构建”。
