<?php

namespace app\common\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

class JwtService extends BaseService
{
    const PRIVATE_KEY_PATH = '-----BEGIN RSA PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJA4rVEBCzAV7Vk+
xumXQX2CsKRckXJO7f+uazfgevrzFFMyVg4RtKRq/D5ZYy8wLi4rvKxqzYfQ5c1O
mpH/rxQhVjP5+Myx+e7zbltLHjCzQPp9l3CqL7esGcLdC/Xsnp7tHqCKDKBr0Lw0
ErOcZjSywp88baa6LEAaK1QHiNwFAgMBAAECgYBgJikGFBgNWtD96qhaGwkCUBrL
uRsOhiiNiQ7aFcJng59NSAWvI4a3BsxcFOPXFdvz1BzZJesYXOCX24uZQkjJslxE
3s9H3VSVINgw1bVHSVSGXEwMde3buyPCLfFw8ib4FmJAkyzFf65DgxyPUgA2Cej0
eQz7yWjC+NTtWWMHgQJBAObFlEsaPeHLzfHMT6h/DEjCGAO2owRBjqQ3AXxArdaE
TptDBbWME1qJ9CHBQv5xAkRljNEtZv8jPKOIupGxby0CQQCf/OH9u7cS8u/H4ISg
0/yC5PokddtY20iwJDTjVtSpnUnv4NxZ2X1cuIxkuLApns8L9Vmnr5eawnFxYsqv
/Gc5AkBrwrlztIZPCQdbMOfFq8YFt7TVDxTiaOZ94j2sUtuaP2AhelORKh7jeWXp
2UA6ZnUDkVQHXacp3r9zMebFH9DlAkA38JEIShFqM715ctyM63JYRj3cb8UhXZMd
25sOfnbfU5rdoA8L74rw16pnMViPRPL6KHCPvErTFvfZgISEYkmpAkEArZb0nZAm
MPB3VjqkSn0CaZR32RNk/denN/3qrgWiWyWn6Hs/AGQXsQw8KYG3mnrByMPhefd/
TolXO5prjOSYZQ==
-----END RSA PRIVATE KEY-----
';
    const PUBLIC_KEY_PATH = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCQOK1RAQswFe1ZPsbpl0F9grCk
XJFyTu3/rms34Hr68xRTMlYOEbSkavw+WWMvMC4uK7ysas2H0OXNTpqR/68UIVYz
+fjMsfnu825bSx4ws0D6fZdwqi+3rBnC3Qv17J6e7R6gigyga9C8NBKznGY0ssKf
PG2muixAGitUB4jcBQIDAQAB
-----END PUBLIC KEY-----
';

    /**创建token
     * @param $data
     * @return string
     */
    public static function createToken($data)
    {
        $host = app()->request->host();
        $key = self::PRIVATE_KEY_PATH;
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
        $key = self::PRIVATE_KEY_PATH;
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
            throwError('请先授权登录', Config::get('status.not_logged'));
        }
        $secret = $headers['authorization-token'];
        if (!$secret) {
            throwError('请先授权登录', Config::get('status.not_logged'));
        }
        if (strpos($secret, 'Bearer') !== false) {
            $secret = str_replace('Bearer ', '', $secret);
        } else {
            throwError('请先授权登录', Config::get('status.not_logged'));
        }
        $key = self::PUBLIC_KEY_PATH;
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
        $key = self::PUBLIC_KEY_PATH;
        try {
            $info = JWT::decode($secret, new Key($key, 'RS256'));
            return $info->token_data ?? null;
        } catch (\Exception $exception) {
            return null;
        }
    }



}
