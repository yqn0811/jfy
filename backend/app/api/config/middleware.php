<?php
// api中间件定义文件
return [
    'alias' => [
        'auth' => \app\middleware\ApiCheckToken::class,
        'sign' => \app\middleware\ApiCheckSign::class,
        'web' => \app\middleware\ApiCheckWebToken::class,
    ],
];
