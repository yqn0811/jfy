# 项目启动指南

## 一、环境要求

- PHP >= 7.2.5
- MySQL >= 5.7
- Composer
- 扩展要求：
  - openssl
  - pdo_mysql
  - pdo_pgsql / pgsql（文件传输模块）
  - mbstring
  - curl
  - fileinfo
  - gd（图片处理）

## 二、快速启动步骤

### 1. 检查PHP环境

```bash
php -v  # 确保版本 >= 7.2.5
php -m  # 检查必需扩展是否已安装
```

### 2. 安装依赖

```bash
# 进入项目目录
cd /Users/mac/Documents/jiafangyun/newjfybackend

# 安装Composer依赖（如果vendor目录不存在）
composer install
```

### 3. 配置环境变量

创建 `.env` 文件（在项目根目录）：

```bash
# 复制示例文件（如果有）
# cp .env.example .env

# 或直接创建
touch .env
```

在 `.env` 文件中添加以下配置：

```env
# 应用配置
APP_DEBUG = true
APP_HOST = http://localhost

# 数据库配置
DATABASE_TYPE = mysql
DATABASE_HOSTNAME = 127.0.0.1
DATABASE_DATABASE = your_database_name
DATABASE_USERNAME = root
DATABASE_PASSWORD = your_password
DATABASE_HOSTPORT = 3306
DATABASE_CHARSET = utf8mb4
DATABASE_PREFIX = 

# 文件传输模块 PostgreSQL 配置
# 103 测试服使用 AI 生图同库 ai_jf，不同表前缀 ft_。
[FILE_DB]
TYPE = pgsql
HOSTNAME = 127.0.0.1
DATABASE = ai_jf
USERNAME = ai_jf_user
PASSWORD = your_file_db_password
HOSTPORT = 5433
CHARSET = utf8
PREFIX = ft_
SCHEMA = public

[FILE_TRANSFER]
MAX_UPLOAD_MB = 500

# 日志配置
LOG_CHANNEL = file

# 缓存配置
CACHE_DRIVER = file

# 文件系统配置
FILESYSTEM_DRIVER = local

# AI 资源库桥接
# 当前联调固定使用 ai-test；AI 生图正式上线后切换为 https://ai.jfyuntu.com/api/v1。
JIAFANGYUN_BRIDGE_API_BASE = https://ai-test.jfyuntu.com/api/v1
JIAFANGYUN_BRIDGE_TOKEN = 
# 兼容旧配置名；新部署优先使用 JIAFANGYUN_BRIDGE_*。
AI_RESOURCE_API_BASE = https://ai-test.jfyuntu.com/api/v1
AI_RESOURCE_BRIDGE_TOKEN = 
# 默认 all 方便联调；需要限制正式用户时改为 whitelist 并填写 UID，off 表示完全关闭。
AI_RESOURCE_BRIDGE_MODE = all
AI_RESOURCE_BRIDGE_UIDS = 
```

### 4. 设置目录权限

```bash
# 设置运行时目录权限
chmod -R 755 runtime/
chmod -R 755 log/

# 设置上传目录权限
chmod -R 755 public/uploads/
```

### 5. 启动项目

#### 方式一：使用PHP内置服务器（开发环境推荐）

```bash
# 进入public目录
cd public

# 启动服务器（默认端口8000）
php -S localhost:8000

# 或指定端口
php -S localhost:8080
```

访问地址：
- API接口：http://localhost:8000/api/
- 管理后台：http://localhost:8000/index/

#### 方式二：使用Nginx（生产环境）

创建Nginx配置文件 `/etc/nginx/sites-available/jiafangyun`：

```nginx
server {
    listen 80;
    server_name localhost;
    root /Users/mac/Documents/jiafangyun/newjfybackend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;  # 或 unix:/var/run/php-fpm.sock
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问敏感目录
    location ~ ^/(runtime|config|vendor|app)/ {
        deny all;
    }
}
```

启动Nginx：
```bash
sudo nginx -t  # 测试配置
sudo nginx -s reload  # 重载配置
```

#### 方式三：使用Apache

确保启用 `mod_rewrite` 模块，并配置虚拟主机。

### 6. 测试访问

```bash
# 测试API接口
curl http://localhost:8000/api/index

# 测试管理后台
curl http://localhost:8000/index/
```

## 三、常见问题

### 1. Composer依赖安装失败

```bash
# 清除缓存
composer clear-cache

# 使用国内镜像
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 重新安装
composer install
```

### 2. 数据库连接失败

- 检查 `.env` 文件中的数据库配置
- 确认MySQL服务已启动
- 检查数据库用户权限

### 3. 权限错误

```bash
# 确保runtime目录可写
chmod -R 777 runtime/
chmod -R 777 log/
chmod -R 777 public/uploads/
```

### 4. 路由不生效

- 检查 `config/app.php` 中 `with_route` 是否为 `true`
- 清除缓存：`rm -rf runtime/cache/*`

### 5. 500错误

- 检查 `runtime/log/` 目录下的错误日志
- 确认 `.env` 文件配置正确
- 检查PHP错误日志

## 四、开发调试

### 开启调试模式

在 `.env` 文件中设置：
```env
APP_DEBUG = true
```

### 查看日志

```bash
# 查看API日志
tail -f runtime/api/log/$(date +%Y%m%d).log

# 查看应用日志
tail -f runtime/log/$(date +%Y%m%d).log
```

### 清除缓存

```bash
# 清除应用缓存
rm -rf runtime/cache/*

# 清除模板缓存
rm -rf runtime/temp/*
```

## 五、生产环境部署

### 1. 关闭调试模式

```env
APP_DEBUG = false
```

### 2. 优化配置

- 开启OPcache
- 使用Redis缓存
- 配置CDN
- 启用HTTPS

### 3. 设置定时任务

```bash
# 编辑crontab
crontab -e

# 添加定时任务（示例）
* * * * * cd /path/to/project && php think command:name
```

## 六、API接口测试

### 获取Token示例

```bash
# 1. 获取OpenID
curl "http://localhost:8000/api/user/openid?code=YOUR_WX_CODE"

# 2. 使用Token访问需要认证的接口
curl -H "authorization-token: Bearer YOUR_TOKEN" \
     "http://localhost:8000/api/user/show_info"
```

## 七、项目结构说明

```
newjfybackend/
├── app/              # 应用目录
│   ├── api/         # API应用
│   ├── index/       # 管理后台应用
│   └── common/      # 公共模块
├── config/          # 配置文件
├── public/          # Web根目录（入口文件）
├── runtime/         # 运行时目录（日志、缓存）
├── vendor/          # Composer依赖
└── .env            # 环境配置文件（需创建）
```

## 八、下一步

1. 配置数据库并导入数据
2. 配置微信小程序/公众号信息（`config/miniprogram.php`）
3. 配置支付信息（`config/pay_config.php`）
4. 测试各个功能模块

---

**提示：** 首次启动前请确保已完成数据库配置和必要的环境变量设置。
