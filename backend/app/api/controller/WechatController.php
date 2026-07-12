<?php

namespace app\api\controller;

use app\common\model\user\WdXcxUser;
use app\common\service\JwtService;
use app\common\service\WxService;
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

class WechatController extends ApiBaseController
{
    private function writeWechatLog($title, array $context = [])
    {
        $payload = $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        Log::channel('wechat_callback')->info(trim($title . ' ' . $payload));
    }

    private function getRawRequestBody()
    {
        $raw = (string) $this->request->getInput();
        if ($raw !== '') {
            return $raw;
        }

        return (string) file_get_contents('php://input');
    }

    public function serve()
    {
        $rawBody = $this->getRawRequestBody();
        $this->writeWechatLog('[serve] incoming request', [
            'method' => $this->request->method(),
            'url' => $this->request->url(true),
            'query' => $this->request->get(),
            'content_type' => $this->request->header('content-type'),
            'user_agent' => $this->request->header('user-agent'),
            'raw_body' => $rawBody,
        ]);

        $app = (new WxService(3))->getAppData();

        $app->server->push(function ($message) use ($app) {
            $this->writeWechatLog('[serve] parsed message', $message);
            
            $msgType = $message['MsgType'] ?? '';
            $event = $message['Event'] ?? '';
            $eventKey = $message['EventKey'] ?? '';
            $openId = $message['FromUserName'] ?? '';
            $scene = $this->extractLoginScene($message);

            if ($this->isAiLoginScene($scene)) {
                return $this->forwardAiLoginCallback($message, $scene);
            }

            if ($msgType === 'event' && ($event === 'SCAN' || $event === 'subscribe')) {
                // Extract scene_str
                // For subscribe, EventKey is "qrscene_SCENE_STR"
                // For SCAN, EventKey is "SCENE_STR"
                if ($scene) {
                    try {
                        // Get User Info to get UnionID
                        $userInfo = $app->user->get($openId);
                        $unionId = $userInfo['unionid'] ?? null;
                        $this->writeWechatLog('[serve] matched scene', [
                            'scene' => $scene,
                            'open_id' => $openId,
                            'unionid' => $unionId,
                        ]);
                        
                        $user = null;
                        if ($unionId) {
                            $user = WdXcxUser::where('unionid', $unionId)->find();
                        }

                        // If user not found by UnionID, maybe create one or find by OA OpenID (if supported)
                        if (!$user) {
                            // Try to find by OA OpenID (unlikely if strictly MP based, but possible)
                            // Or create new user
                            if ($unionId) {
                                $user = WdXcxUser::create([
                                    'uniacid' => 1,
                                    'unionid' => $unionId,
                                    'nickname' => $userInfo['nickname'] ?? 'WeChat User',
                                    'avatar' => $userInfo['headimgurl'] ?? '',
                                    'create_time' => time(),
                                    'user_uuid' => (new WdXcxUser())->getUuId(),
                                    'space_size' => WdXcxUser::DEFAULT_FREE_SPACE_MB,
                                ]);
                                // Initialize grade
                                \app\common\model\user\WdXcxUserVipGradeInfo::create([
                                    'uniacid' => 1,
                                    'user_id' => $user->id,
                                    'grade_level' => 0,
                                    'end_time' => 0
                                ]);
                                $this->writeWechatLog('[serve] created user by unionid', [
                                    'scene' => $scene,
                                    'user_id' => $user->id,
                                    'unionid' => $unionId,
                                ]);
                            }
                        }

                        if ($user) {
                            // Generate Token
                            $tokenData = [
                                'user_id' => $user->id,
                                'user_uuid' => $user->user_uuid,
                                'openid' => $user->openid
                            ];
                            $token = JwtService::createToken($tokenData);
                            
                            // Cache the token for polling
                            // Key: login_scene_SCENE
                            Cache::set('login_scene_' . $scene, $token, 600); // 10 mins
                            $this->writeWechatLog('[serve] cached login token', [
                                'scene' => $scene,
                                'user_id' => $user->id,
                                'cache_key' => 'login_scene_' . $scene,
                            ]);
                            
                            return 'Welcome to Mia 233!';
                        }
                    } catch (\Exception $e) {
                        $this->writeWechatLog('[serve] login error', [
                            'scene' => $scene,
                            'open_id' => $openId,
                            'error' => $e->getMessage(),
                        ]);
                        Log::error('WeChat Login Error: ' . $e->getMessage());
                    }
                }
            }

            $this->writeWechatLog('[serve] ignored message', [
                'msg_type' => $msgType,
                'event' => $event,
                'event_key' => $eventKey,
                'open_id' => $openId,
            ]);
            return '';
        });

        $response = $app->server->serve();
        $this->writeWechatLog('[serve] response prepared', [
            'class' => is_object($response) ? get_class($response) : gettype($response),
        ]);
        return $response->send();
    }

    private function extractLoginScene(array $message)
    {
        $msgType = strtolower((string)($message['MsgType'] ?? ''));
        $event = strtolower((string)($message['Event'] ?? ''));
        if ($msgType !== 'event' || !in_array($event, ['scan', 'subscribe'], true)) {
            return '';
        }

        $scene = trim((string)($message['EventKey'] ?? ''));
        if ($event === 'subscribe') {
            $scene = preg_replace('/^qrscene_/', '', $scene);
        }

        return $scene;
    }

    private function isAiLoginScene($scene)
    {
        return is_string($scene) && strpos($scene, 'ai_login_') === 0;
    }

    private function forwardAiLoginCallback(array $message, $scene)
    {
        $callbackUrl = $this->getAiLoginCallbackUrl();
        if ($callbackUrl === '') {
            $this->writeWechatLog('[serve] ai login callback skipped: url not configured', [
                'scene' => $scene,
            ]);
            return 'success';
        }

        $signedUrl = $this->buildSignedWechatCallbackUrl($callbackUrl);
        if ($signedUrl === '') {
            $this->writeWechatLog('[serve] ai login callback skipped: token not configured', [
                'scene' => $scene,
            ]);
            return 'success';
        }

        $xml = $this->buildPlainWechatEventXml($message);
        if ($xml === '') {
            $this->writeWechatLog('[serve] ai login callback skipped: invalid message', [
                'scene' => $scene,
            ]);
            return 'success';
        }

        $result = $this->postXml($signedUrl, $xml);
        $this->writeWechatLog('[serve] ai login callback forwarded', [
            'scene' => $scene,
            'ok' => $result['ok'],
            'status' => $result['status'],
            'error' => $result['error'],
            'body' => $result['body'],
        ]);

        if ($result['ok'] && trim($result['body']) !== '') {
            return trim($result['body']);
        }

        return 'success';
    }

    private function getAiLoginCallbackUrl()
    {
        $url = trim((string)env('wechat.ai_callback_url', ''));
        if ($url === '') {
            $url = trim((string)env('WECHAT_AI_CALLBACK_URL', ''));
        }
        if ($url === '') {
            $url = trim((string)getenv('WECHAT_AI_CALLBACK_URL'));
        }
        return $url;
    }

    private function buildSignedWechatCallbackUrl($url)
    {
        $token = trim((string)Config::get('miniprogram.account_token'));
        if ($token === '') {
            return '';
        }

        $timestamp = (string)time();
        try {
            $nonce = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $nonce = md5(uniqid((string)mt_rand(), true));
        }
        $parts = [$token, $timestamp, $nonce];
        sort($parts, SORT_STRING);
        $signature = sha1(implode('', $parts));
        $query = http_build_query([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ]);

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    private function buildPlainWechatEventXml(array $message)
    {
        $toUser = (string)($message['ToUserName'] ?? '');
        $fromUser = (string)($message['FromUserName'] ?? '');
        $msgType = (string)($message['MsgType'] ?? 'event');
        $event = (string)($message['Event'] ?? '');
        $eventKey = (string)($message['EventKey'] ?? '');
        if ($fromUser === '' || $event === '' || $eventKey === '') {
            return '';
        }
        $createTime = (int)($message['CreateTime'] ?? time());
        $ticket = (string)($message['Ticket'] ?? '');

        return '<xml>'
            . '<ToUserName><![CDATA[' . $toUser . ']]></ToUserName>'
            . '<FromUserName><![CDATA[' . $fromUser . ']]></FromUserName>'
            . '<CreateTime>' . $createTime . '</CreateTime>'
            . '<MsgType><![CDATA[' . $msgType . ']]></MsgType>'
            . '<Event><![CDATA[' . $event . ']]></Event>'
            . '<EventKey><![CDATA[' . $eventKey . ']]></EventKey>'
            . '<Ticket><![CDATA[' . $ticket . ']]></Ticket>'
            . '</xml>';
    }

    private function postXml($url, $xml)
    {
        $result = [
            'ok' => false,
            'status' => 0,
            'body' => '',
            'error' => '',
        ];

        if (!function_exists('curl_init')) {
            $result['error'] = 'curl extension missing';
            return $result;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'Content-Length: ' . strlen($xml),
            ],
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $result['status'] = $status;
        $result['body'] = is_string($body) ? $body : '';
        $result['error'] = $error ?: '';
        $result['ok'] = $error === '' && $status >= 200 && $status < 300;
        return $result;
    }
}
