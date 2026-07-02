<?php

return [
    // 小程序 appid
    'appid' => env('wechat.miniapp_appid', getenv('WECHAT_MINIAPP_APPID') ?: ''),
    // 小程序 appsecret
    'appsecret' => env('wechat.miniapp_appsecret', getenv('WECHAT_MINIAPP_APPSECRET') ?: ''),
    // 微信支付 v2 key
    'signkey_v2' => env('wechat.pay_signkey_v2', getenv('WECHAT_PAY_SIGNKEY_V2') ?: ''),
    // 微信支付 API v3 key
    'signkey_v3' => env('wechat.pay_signkey_v3', getenv('WECHAT_PAY_SIGNKEY_V3') ?: ''),
    // 微信支付商户号
    'mchid' => env('wechat.pay_mchid', getenv('WECHAT_PAY_MCHID') ?: ''),
    // 微信支付 API 证书序列号
    'api_serial_sn' => env('wechat.pay_api_serial_sn', getenv('WECHAT_PAY_API_SERIAL_SN') ?: ''),
    // 商户 API 私钥文件路径
    'api_private_key_path' => env('wechat.pay_api_private_key_path', getenv('WECHAT_PAY_API_PRIVATE_KEY_PATH') ?: ''),
    // 微信支付平台证书文件路径
    'platform_cert_path' => env('wechat.pay_platform_cert_path', getenv('WECHAT_PAY_PLATFORM_CERT_PATH') ?: ''),

    // 公众号 appid（PC 网页扫码登录）
    'account_appid' => env('wechat.account_appid', getenv('WECHAT_ACCOUNT_APPID') ?: ''),
    // 公众号 appsecret
    'account_appsecret' => env('wechat.account_appsecret', getenv('WECHAT_ACCOUNT_APPSECRET') ?: ''),
    // 公众号消息推送 token
    'account_token' => env('wechat.account_token', getenv('WECHAT_ACCOUNT_TOKEN') ?: ''),
    // 公众号消息推送 EncodingAESKey
    'account_aes_key' => env('wechat.account_aes_key', getenv('WECHAT_ACCOUNT_AES_KEY') ?: ''),

    // 公众号微信支付 v2 key
    'account_key_v2' => env('wechat.account_pay_key_v2', getenv('WECHAT_ACCOUNT_PAY_KEY_V2') ?: ''),
    // 公众号微信商户号
    'account_mchid' => env('wechat.account_pay_mchid', getenv('WECHAT_ACCOUNT_PAY_MCHID') ?: ''),
];
