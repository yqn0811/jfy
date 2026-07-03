<?php

namespace app\common\service\album;

use app\common\model\user\WdXcxUser;
use app\common\service\BaseService;
use app\index\model\WdXcxPic;
use think\App;
use think\facade\Log;

class AiResourceBridgeService extends BaseService
{
    private $apiBase;
    private $bridgeToken;
    private $bridgeMode;
    private $bridgeAllowedUIDs;
    private $requestRetries = 2;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->apiBase = rtrim((string)env('AI_RESOURCE_API_BASE', getenv('AI_RESOURCE_API_BASE') ?: 'https://ai.jfyuntu.com/api/v1'), '/');
        $this->bridgeToken = trim((string)env(
            'AI_RESOURCE_BRIDGE_TOKEN',
            getenv('AI_RESOURCE_BRIDGE_TOKEN') ?: (getenv('JIAFANGYUN_BRIDGE_TOKEN') ?: '')
        ));
        $this->bridgeMode = strtolower(trim((string)env('AI_RESOURCE_BRIDGE_MODE', getenv('AI_RESOURCE_BRIDGE_MODE') ?: 'all')));
        if (!in_array($this->bridgeMode, ['off', 'whitelist', 'all'], true)) {
            $this->bridgeMode = 'off';
        }
        $this->bridgeAllowedUIDs = $this->parseAllowedUIDs((string)env('AI_RESOURCE_BRIDGE_UIDS', getenv('AI_RESOURCE_BRIDGE_UIDS') ?: ''));
    }

    public function listResources($uid, $params)
    {
        $this->assertBridgeAllowed($uid);
        $user = $this->getBridgeUser($uid);
        $query = [
            'b_user_id' => $user->id,
            'mini_openid' => $user->openid,
            'unionid' => $user->unionid ?: '',
            'page' => max(1, (int)($params['page'] ?? 1)),
            'page_size' => max(1, min(60, (int)($params['page_size'] ?? 30))),
            'keyword' => trim((string)($params['keyword'] ?? '')),
            'status' => 'active',
        ];
        if (isset($params['category_id']) && $params['category_id'] !== '') {
            $query['category_id'] = $params['category_id'];
        }
        return $this->requestAiResource('GET', '/jiafangyun/bridge/resources?' . http_build_query($query), null);
    }

    public function importResource($uid, $resourceId, $role = 'cover')
    {
        $this->assertBridgeAllowed($uid);
        $user = $this->getBridgeUser($uid);
        $resourceId = (int)$resourceId;
        if ($resourceId === 0) {
            throwError('请选择资源库图片');
        }
        $query = [
            'b_user_id' => $user->id,
            'mini_openid' => $user->openid,
            'unionid' => $user->unionid ?: '',
        ];
        $resp = $this->requestAiResource('GET', '/jiafangyun/bridge/resources/' . $resourceId . '?' . http_build_query($query), null);
        $resource = $resp['resource'] ?? null;
        if (!$resource || empty($resource['file_url'])) {
            throwError('资源库图片不存在');
        }
        $fileUrl = $resource['file_url'];
        $previewUrl = $resource['thumbnail_url'] ?: ($resource['preview_url'] ?: $fileUrl);

        $exists = WdXcxPic::where('uid', $uid)
            ->where('imgurl', $fileUrl)
            ->whereIn('pic_name', ['我的资源库-' . $resourceId, 'AI资源库-' . $resourceId])
            ->find();
        if ($exists) {
            return [
                'id' => $exists->id,
                'url' => $previewUrl,
                'file_url' => $fileUrl,
                'resource_id' => $resourceId,
                'role' => $role,
            ];
        }

        $pic = WdXcxPic::savePicture([
            'uniacid' => $this->uniacid,
            'gid' => 0,
            'pic_name' => '我的资源库-' . $resourceId,
            'size' => (int)($resource['file_size'] ?? 0),
            'create_time' => time(),
            'imgurl' => $fileUrl,
            'type' => 1,
            'shop_id' => 0,
            'uid' => $uid,
            'file_type' => 1,
        ]);

        return [
            'id' => $pic->id,
            'url' => $previewUrl,
            'file_url' => $fileUrl,
            'resource_id' => $resourceId,
            'role' => $role,
        ];
    }

    public function syncPicture($uid, $pic, $options = [])
    {
        if (!$pic || !$this->isBridgeEnabledForUser($uid)) {
            return null;
        }
        $user = $this->getBridgeUser($uid);
        $fileUrl = removePicStyle(remote($pic->uniacid ?: 1, $pic->imgurl, 1));
        $previewUrl = remote($pic->uniacid ?: 1, $pic->imgurl, 1);
        if (!$fileUrl) {
            return null;
        }
        $payload = array_merge($this->bridgeUserPayload($user), [
            'b_folder_id' => (int)($options['b_folder_id'] ?? 0),
            'b_pic_id' => (int)$pic->id,
            'b_relation_id' => (int)($options['b_relation_id'] ?? 0),
            'external_product_id' => (string)($options['external_product_id'] ?? ''),
            'external_sku_id' => (string)($options['external_sku_id'] ?? ''),
            'role' => (string)($options['role'] ?? 'detail'),
            'sort_order' => (int)($options['sort_order'] ?? 0),
            'file_url' => $fileUrl,
            'preview_url' => $previewUrl,
            'thumbnail_url' => $previewUrl,
            'name' => $pic->pic_name ?: '佳方云图片',
            'mime_type' => $this->guessMimeType($fileUrl, (int)$pic->file_type),
            'file_size' => (int)$pic->getData('size'),
            'metadata' => [
                'b_pic_id' => (int)$pic->id,
                'b_folder_id' => (int)($options['b_folder_id'] ?? 0),
                'b_relation_id' => (int)($options['b_relation_id'] ?? 0),
                'external_product_id' => (string)($options['external_product_id'] ?? ''),
                'role' => (string)($options['role'] ?? 'detail'),
                'source' => 'jiafangyun',
            ],
        ]);
        return $this->requestAiResource('POST', '/jiafangyun/bridge/resources/sync', $payload);
    }

    public function syncProductPictures($uid, $product)
    {
        if (!$product || (int)$product->folder_type !== 2) {
            return;
        }
        $this->syncProductPictureIds($uid, $product, $product->pic_ids ?? '', 'cover');
        $this->syncProductPictureIds($uid, $product, $product->detail_pic_ids ?? '', 'detail');
    }

    public function syncAlbumRelation($uid, $relation, $role = 'album')
    {
        if (!$relation || !$relation->picture) {
            return null;
        }
        return $this->syncPicture($uid, $relation->picture, [
            'b_folder_id' => (int)$relation->folder_id,
            'b_relation_id' => (int)$relation->id,
            'external_product_id' => (string)$relation->folder_id,
            'role' => $role,
            'sort_order' => (int)$relation->sort,
        ]);
    }

    public function markPictureDeleted($uid, $picId, $options = [])
    {
        if (!$this->isBridgeEnabledForUser($uid)) {
            return null;
        }
        $user = $this->getBridgeUser($uid);
        $payload = array_merge($this->bridgeUserPayload($user), [
            'b_folder_id' => (int)($options['b_folder_id'] ?? 0),
            'b_pic_id' => (int)$picId,
            'b_relation_id' => (int)($options['b_relation_id'] ?? 0),
            'external_product_id' => (string)($options['external_product_id'] ?? ''),
            'role' => (string)($options['role'] ?? ''),
            'delete_resource' => !empty($options['delete_resource']),
        ]);
        return $this->requestAiResource('POST', '/jiafangyun/bridge/resources/delete', $payload);
    }

    public function safeSyncPicture($uid, $pic, $options = [])
    {
        try {
            return $this->syncPicture($uid, $pic, $options);
        } catch (\Throwable $e) {
            Log::error('[AiResourceBridge] sync picture failed: ' . $e->getMessage());
            return null;
        }
    }

    public function safeSyncProductPictures($uid, $product)
    {
        try {
            $this->syncProductPictures($uid, $product);
        } catch (\Throwable $e) {
            Log::error('[AiResourceBridge] sync product pictures failed: ' . $e->getMessage());
        }
    }

    public function safeSyncAlbumRelation($uid, $relation, $role = 'album')
    {
        try {
            return $this->syncAlbumRelation($uid, $relation, $role);
        } catch (\Throwable $e) {
            Log::error('[AiResourceBridge] sync album relation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function safeMarkPictureDeleted($uid, $picId, $options = [])
    {
        try {
            return $this->markPictureDeleted($uid, $picId, $options);
        } catch (\Throwable $e) {
            Log::error('[AiResourceBridge] mark picture deleted failed: ' . $e->getMessage());
            return null;
        }
    }

    private function getBridgeUser($uid)
    {
        $user = WdXcxUser::where('id', $uid)->find();
        if (!$user || empty($user->openid)) {
            throwError('用户未登录');
        }
        return $user;
    }

    private function bridgeUserPayload($user)
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

    private function assertBridgeAllowed($uid)
    {
        if (!$this->bridgeToken) {
            throwError('我的资源库桥接未配置');
        }
        if (!$this->isBridgeEnabledForUser($uid)) {
            throwError('我的资源库暂未开放');
        }
    }

    private function isBridgeEnabledForUser($uid)
    {
        if (!$this->bridgeToken) {
            return false;
        }
        if ($this->bridgeMode === 'all') {
            return true;
        }
        if ($this->bridgeMode === 'whitelist') {
            return in_array((int)$uid, $this->bridgeAllowedUIDs, true);
        }
        return false;
    }

    private function parseAllowedUIDs($raw)
    {
        $raw = str_replace(['，', ';', ' '], ',', (string)$raw);
        $ids = [];
        foreach (explode(',', $raw) as $item) {
            $id = (int)trim($item);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function syncProductPictureIds($uid, $product, $picIds, $role)
    {
        $ids = $this->normalizeIds($picIds);
        if (empty($ids)) {
            return;
        }
        $sort = 0;
        foreach ($ids as $picId) {
            $pic = WdXcxPic::where('id', $picId)->find();
            if (!$pic) {
                continue;
            }
            $sort++;
            $this->safeSyncPicture($uid, $pic, [
                'b_folder_id' => (int)$product->id,
                'b_relation_id' => $this->virtualProductRelationId((int)$product->id, (int)$pic->id, $role),
                'external_product_id' => (string)$product->id,
                'role' => $role,
                'sort_order' => $sort,
            ]);
        }
    }

    private function normalizeIds($raw)
    {
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $items = explode(',', (string)$raw);
        }
        $ids = [];
        foreach ($items as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function virtualProductRelationId($productId, $picId, $role)
    {
        $hash = sprintf('%u', crc32($productId . ':' . $picId . ':' . $role));
        return -1 * (int)substr($hash, 0, 9);
    }

    private function guessMimeType($url, $fileType)
    {
        if ($fileType === 2) {
            return 'video/mp4';
        }
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));
        if ($ext === 'png') return 'image/png';
        if ($ext === 'gif') return 'image/gif';
        if ($ext === 'webp') return 'image/webp';
        return 'image/jpeg';
    }

    private function requestAiResource($method, $path, $payload = null)
    {
        if (!$this->bridgeToken) {
            throwError('我的资源库桥接未配置');
        }
        $url = $this->apiBase . $path;
        $lastMessage = '';
        $maxAttempts = max(1, $this->requestRetries + 1);
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->sendAiResourceRequest($method, $url, $payload);
            if ($result['ok']) {
                return $result['data'];
            }
            $lastMessage = $result['message'];
            if (!$result['retryable'] || $attempt >= $maxAttempts) {
                break;
            }
            usleep(200000 * $attempt);
        }
        throwError($lastMessage ?: '我的资源库请求失败');
    }

    private function sendAiResourceRequest($method, $url, $payload = null)
    {
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
            Log::error('[AiResourceBridge] curl error: ' . $error);
            return [
                'ok' => false,
                'retryable' => true,
                'message' => '我的资源库连接失败',
                'data' => [],
            ];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Log::error('[AiResourceBridge] invalid response: ' . $raw);
            return [
                'ok' => false,
                'retryable' => $status >= 500 || $status === 0,
                'message' => '我的资源库返回异常',
                'data' => [],
            ];
        }
        if ($status < 200 || $status >= 300 || (isset($data['code']) && (int)$data['code'] !== 200)) {
            $message = $data['message'] ?? '我的资源库请求失败';
            return [
                'ok' => false,
                'retryable' => $status >= 500 || $status === 429,
                'message' => $message,
                'data' => [],
            ];
        }
        return [
            'ok' => true,
            'retryable' => false,
            'message' => '',
            'data' => $data['data'] ?? [],
        ];
    }
}
