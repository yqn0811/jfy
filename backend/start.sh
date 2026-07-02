#!/bin/bash

# 家方云项目启动脚本

echo "=========================================="
echo "  家方云后端项目启动脚本"
echo "=========================================="
echo ""

# 检查PHP
echo "1. 检查PHP环境..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP未安装，请先安装PHP >= 7.2.5"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✅ PHP版本: $PHP_VERSION"

# 检查Composer
echo ""
echo "2. 检查Composer..."
if ! command -v composer &> /dev/null; then
    echo "❌ Composer未安装，请先安装Composer"
    exit 1
fi
echo "✅ Composer已安装"

# 检查依赖
echo ""
echo "3. 检查项目依赖..."
if [ ! -d "vendor" ]; then
    echo "⚠️  vendor目录不存在，正在安装依赖..."
    composer install
    if [ $? -ne 0 ]; then
        echo "❌ 依赖安装失败"
        exit 1
    fi
    echo "✅ 依赖安装完成"
else
    echo "✅ 依赖已存在"
fi

# 检查.env文件
echo ""
echo "4. 检查环境配置..."
if [ ! -f ".env" ]; then
    echo "⚠️  .env文件不存在，正在创建..."
    cat > .env << 'EOF'
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

# 日志配置
LOG_CHANNEL = file

# 缓存配置
CACHE_DRIVER = file

# 文件系统配置
FILESYSTEM_DRIVER = local

# AI 资源库桥接
# 默认 all 方便联调；需要限制正式用户时改为 whitelist 并填写 UID，off 表示完全关闭。
AI_RESOURCE_API_BASE = https://ai.jfyuntu.com/api/v1
AI_RESOURCE_BRIDGE_TOKEN = 
AI_RESOURCE_BRIDGE_MODE = all
AI_RESOURCE_BRIDGE_UIDS = 
EOF
    echo "✅ .env文件已创建，请修改其中的数据库配置"
    echo "⚠️  请编辑 .env 文件配置数据库信息后再启动"
    read -p "按回车键继续..."
else
    echo "✅ .env文件已存在"
fi

# 设置权限
echo ""
echo "5. 设置目录权限..."
chmod -R 755 runtime/ 2>/dev/null
chmod -R 755 log/ 2>/dev/null
chmod -R 755 public/uploads/ 2>/dev/null
echo "✅ 权限设置完成"

# 选择启动方式
echo ""
echo "=========================================="
echo "  选择启动方式"
echo "=========================================="
echo "1. PHP内置服务器 (开发环境，推荐)"
echo "2. 查看启动说明"
echo "3. 退出"
echo ""
read -p "请选择 [1-3]: " choice

case $choice in
    1)
        echo ""
        echo "正在启动PHP内置服务器..."
        echo "访问地址："
        echo "  - API接口: http://localhost:8000/api/"
        echo "  - 管理后台: http://localhost:8000/index/"
        echo ""
        echo "按 Ctrl+C 停止服务器"
        echo ""
        cd public
        php -S localhost:8000
        ;;
    2)
        echo ""
        echo "详细启动说明请查看 README_START.md 文件"
        ;;
    3)
        echo "退出"
        exit 0
        ;;
    *)
        echo "无效选择"
        exit 1
        ;;
esac
