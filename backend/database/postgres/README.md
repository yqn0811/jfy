# 文件传输 PostgreSQL 说明

文件传输模块使用独立的 ThinkPHP 连接 `pgsql_file`，默认连接 103 测试服 AI 生图 PostgreSQL 库：

```text
host: 127.0.0.1
port: 5433
database: ai_jf
schema: public
table prefix: ft_
```

相册业务仍使用默认 MySQL 连接和 `wd_xcx_*` 表，文件传输模块不写相册表。

## 初始化表

在 103 测试服上确认 `.env` 中 `FILE_DB_*` 指向 AI 生图同库后，再执行：

```bash
psql -h 127.0.0.1 -p 5433 -U ai_jf_user -d ai_jf -f database/postgres/20260709_create_file_transfer_tables.sql
```

所有文件传输表都带 `ft_` 前缀，例如 `ft_files`、`ft_file_shares`、`ft_collection_tasks`。

## 当前接口范围

- 快速发文件：上传文件、创建分享、公开查询分享、验证访问密码、下载分享文件。
- 收文件：创建收集任务、查询任务列表、查询任务详情。
- 用户体系：暂不建 `ft_users`，当前保留 `owner_user_id` 和 `sso_subject`，后续接单点登录时映射到统一用户表。

## 存储范围

当前后端 MVP 支持本地私有存储 `runtime/file_transfer`，数据库保存元数据。大文件、断点续传、对象存储直传可以后续通过 `storage_provider` 扩展，不需要改相册上传链路。

`FILE_TRANSFER_MAX_UPLOAD_MB` 是文件传输服务层的业务限制；实际单次上传大小还会受 PHP `upload_max_filesize`、`post_max_size` 和网关限制影响。为了不影响相册，本次没有修改全局 PHP 上传上限。
