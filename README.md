# 家纺云 jfy

This repository contains the Jiafangyun PHP backend and UniApp miniapp source code.

## Layout

- `backend/` - ThinkPHP backend, PC pages, API routes, upload pages.
- `miniapp/` - UniApp/WeChat miniapp source.

## Secrets

Runtime credentials are intentionally not committed. Copy `backend/.env.example` to the deployed environment and provide WeChat, payment, JWT, database, and Redis values there.

Do not commit `backend/vendor`, `miniapp/node_modules`, build artifacts, uploaded files, logs, certificates, private keys, or `.env` files.
