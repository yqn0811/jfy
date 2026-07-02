<?php

namespace app\common\service\bridge;

use app\common\model\user\WdXcxUser;
use app\common\service\BaseService;
use think\App;
use think\facade\Log;

class JiafangyunBridgeClient extends BaseService
{
    private $apiBase;
    private $bridgeToken;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->apiBase = rtrim($this->readConfig(
            'JIAFANGYUN_BRIDGE_API_BASE',
            'AI_RESOURCE_API_BASE',
            'https://ai-test.jfyuntu.com/api/v1'
        ), '/');
        $this->bridgeToken = trim($this->readConfig(
            'JIAFANGYUN_BRIDGE_TOKEN',
            'AI_RESOURCE_BRIDGE_TOKEN',
            ''
        ));
    }

    public function getUser($uid)
    {
        $user = WdXcxUser::where('id', $uid)->find();
        if (!$user || empty($user->openid)) {
            throwError('用户未登录');
        }
        return $user;
    }

    public function userPayload($user)
    {
        return [
            'b_user_id' => (int)$user->id,
            'mini_openid' => $user->openid ?: '',
            'unionid' => $user->unionid ?: '',
            'mobile' => $user->mobile ?: '',
            'nickname' => $user->nickname ?: '',
            'avatar_url' => $user->avatar ?: '',
        ];
    }

    public function get($path)
    {
        return $this->request('GET', $path);
    }

    public function post($path, $payload)
    {
        return $this->request('POST', $path, $payload);
    }

    private function request($method, $path, $payload = null)
    {
        if (!$this->bridgeToken) {
            throwError('套餐购买中心桥接未配置');
        }
        $url = $this->apiBase . $path;
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Jiafangyun-Bridge-Token: ' . $this->bridgeToken,
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            Log::error('[JiafangyunBridge] curl error: ' . $error);
            throwError('套餐购买中心连接失败');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Log::error('[JiafangyunBridge] invalid response: ' . $raw);
            throwError('套餐购买中心返回异常');
        }
        if ($status < 200 || $status >= 300 || (isset($data['code']) && (int)$data['code'] !== 200)) {
            $message = $data['message'] ?? '套餐购买中心请求失败';
            throwError($message);
        }
        return $data['data'] ?? [];
    }

    private function readConfig($primary, $legacy, $default)
    {
        $value = env($primary, '');
        if ($value === null || $value === '') {
            $value = env($legacy, '');
        }
        if ($value === null || $value === '') {
            $value = getenv($primary) ?: getenv($legacy);
        }
        if ($value === null || $value === false || $value === '') {
            $value = $default;
        }
        return (string)$value;
    }
}
