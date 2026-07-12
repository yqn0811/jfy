<?php

return [
    // 默认使用的数据库连接配置
    'default'         => env('database.driver', 'mysql'),

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,

    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',

    // 时间字段配置 配置格式：create_time,update_time
    'datetime_field'  => '',

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => env('database.type', 'mysql'),
            // 服务器地址
            'hostname'        => env('database.hostname', '127.0.0.1'),
            // 数据库名
            'database'        => env('database.database', ''),
            // 用户名
            'username'        => env('database.username', 'root'),
            // 密码
            'password'        => env('database.password', ''),
            // 端口
            'hostport'        => env('database.hostport', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('database.charset', 'utf8'),
            // 数据库表前缀
            'prefix'          => env('database.prefix', ''),

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => env('app_debug', true),
            // 开启字段缓存
            'fields_cache'    => false,
        ],

        // 文件传输模块专用 PostgreSQL 连接。默认库为 103 测试服 AI 生图库 ai_jf，
        // 只通过 ft_ 前缀表读写，不影响默认 MySQL 相册业务。
        'pgsql_file' => [
            'type'            => env('FILE_DB_TYPE', env('file_db.type', 'pgsql')),
            'hostname'        => env('FILE_DB_HOSTNAME', env('file_db.hostname', '127.0.0.1')),
            'database'        => env('FILE_DB_DATABASE', env('file_db.database', 'ai_jf')),
            'username'        => env('FILE_DB_USERNAME', env('file_db.username', 'ai_jf_user')),
            'password'        => env('FILE_DB_PASSWORD', env('file_db.password', '')),
            'hostport'        => env('FILE_DB_HOSTPORT', env('file_db.hostport', '5433')),
            'params'          => [],
            'charset'         => env('FILE_DB_CHARSET', env('file_db.charset', 'utf8')),
            'prefix'          => env('FILE_DB_PREFIX', env('file_db.prefix', 'ft_')),
            'schema'          => env('FILE_DB_SCHEMA', env('file_db.schema', 'public')),
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => false,
            'trigger_sql'     => env('app_debug', true),
            'fields_cache'    => false,
        ],

        // 更多的数据库配置信息
    ],
];
