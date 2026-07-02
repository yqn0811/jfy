<?php

namespace app\common\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

class JwtService extends BaseService
{
    /**创建token
     * @param $data
     * @return string
     */
    public static function createToken($data)
    {
        $host = app()->request->host();
        $key = self::privateKey();
        $time = time();
        $exp_time = strtotime('+ 365day');
        $token = [
            'token_data' => $data,
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => $exp_time,
        ];
        return JWT::encode($token, $key, 'RS256');
    }

    /**web 上传token
     * @param $data
     * @return string
     */
    public static function createWebToken($data)
    {
        $host = app()->request->host();
        $key = self::privateKey();
        $time = time();
        $exp_time = $time + 7200*10;
        $token = [
            'token_data' => $data,
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => $exp_time,
        ];
        return JWT::encode($token, $key, 'RS256');
    }

    /**解密（必须有 token，失败抛错） */
    public static function decryptToken()
    {
        $headers = request()->header();
        if (empty($headers['authorization-token'])) {
            throwError('NEED LOGIN', Config::get('status.not_logged'));
        }
        $secret = $headers['authorization-token'];
        if (!$secret) {
            throwError('NEED TOKEN', Config::get('status.not_logged'));
        }
        if (strpos($secret, 'Bearer') !== false) {
            $secret = str_replace('Bearer ', '', $secret);
        } else {
            throwError('Missing token', Config::get('status.not_logged'));
        }
        $key = self::publicKey();
        try {
            $info = JWT::decode($secret, new Key($key, 'RS256'));
            $token_data = $info->token_data;
        } catch (\Exception $exception) {
            throwError($exception->getMessage(), Config::get('status.login_expired'));
        }
        return $token_data;
    }

    /**尝试解密（可选 token，没有或无效时返回 null，不抛错） */
    public static function tryDecryptToken()
    {
        $headers = request()->header();
        if (empty($headers['authorization-token'])) {
            return null;
        }
        $secret = $headers['authorization-token'];
        if (!$secret) {
            return null;
        }
        if (strpos($secret, 'Bearer') !== false) {
            $secret = str_replace('Bearer ', '', $secret);
        } else {
            return null;
        }
        $key = self::publicKey();
        try {
            $info = JWT::decode($secret, new Key($key, 'RS256'));
            return $info->token_data ?? null;
        } catch (\Exception $exception) {
            return null;
        }
    }

    protected static function privateKey()
    {
        return self::resolveKey('JWT_PRIVATE_KEY', 'JWT_PRIVATE_KEY_PATH');
    }

    protected static function publicKey()
    {
        return self::resolveKey('JWT_PUBLIC_KEY', 'JWT_PUBLIC_KEY_PATH');
    }

    protected static function resolveKey($valueEnv, $pathEnv)
    {
        $key = trim((string)(env($valueEnv, getenv($valueEnv) ?: '')));
        if ($key !== '') {
            return str_replace('\\n', "\n", $key);
        }

        $path = trim((string)(env($pathEnv, getenv($pathEnv) ?: '')));
        if ($path !== '' && is_readable($path)) {
            return file_get_contents($path);
        }

        throw new \RuntimeException($valueEnv . ' or ' . $pathEnv . ' is required');
    }
}
