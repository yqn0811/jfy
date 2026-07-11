<?php

namespace app\common\service\album;

use app\common\model\user\WdXcxUser;
use app\common\service\BaseService;
use app\index\model\WdXcxPic;
use think\App;
use think\facade\Cache;
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
        $this->apiBase = rtrim((string)$this->readBridgeConfig(
            'JIAFANGYUN_BRIDGE_API_BASE',
            'AI_RESOURCE_API_BASE',
            'https://ai-test.jfyuntu.com/api/v1'
        ), '/');
        $this->bridgeToken = trim((string)env(
            'JIAFANGYUN_BRIDGE_TOKEN',
            getenv('JIAFANGYUN_BRIDGE_TOKEN') ?: (getenv('AI_RESOURCE_BRIDGE_TOKEN') ?: '')
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
        $resp = $this->requestAiResource('GET', '/jiafangyun/bridge/resources?' . http_build_query($query), null);
        return $this->dedupeResourceResponse($resp);
    }

    public function findDuplicateResource($uid, $params)
    {
        $hash = strtolower(trim((string)($params['file_hash'] ?? ($params['content_hash'] ?? ($params['source_hash'] ?? '')))));
        if ($hash === '') {
            return [];
        }
        $this->assertBridgeAllowed($uid);
        $user = $this->getBridgeUser($uid);
        $query = [
            'b_user_id' => $user->id,
            'mini_openid' => $user->openid,
            'unionid' => $user->unionid ?: '',
            'page' => 1,
            'page_size' => 1,
            'status' => 'active',
            'file_hash' => $hash,
            'content_hash' => $hash,
            'source_hash' => $hash,
        ];
        if (!empty($params['file_size'])) {
            $query['file_size'] = (int)$params['file_size'];
        }
        $resp = $this->dedupeResourceResponse(
            $this->requestAiResource('GET', '/jiafangyun/bridge/resources?' . http_build_query($query), null)
        );
        $resources = [];
        if (isset($resp['resources']) && is_array($resp['resources'])) {
            $resources = $resp['resources'];
        } elseif (isset($resp['list']) && is_array($resp['list'])) {
            $resources = $resp['list'];
        }
        return $resources[0] ?? [];
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
        if (!$resource) {
            throwError('资源库图片不存在');
        }
        $fileUrl = removePicStyle($this->firstResourceImageUrl($resource, $this->resourceOriginalUrlFields()));
        if (!$fileUrl) {
            throwError('资源库图片不存在');
        }

        $exists = WdXcxPic::where('uid', $uid)
            ->where(function($query) use ($fileUrl, $resourceId) {
                $query->where('imgurl', $fileUrl)
                    ->whereOr('pic_name', '我的资源库-' . $resourceId)
                    ->whereOr('pic_name', '我的资源库--' . abs((int)$resourceId));
            })
            ->find();
        if ($exists) {
            $imageUrls = buildPictureImageUrls($exists);
            return [
                'id' => $exists->id,
                'url' => $imageUrls['thumb'] ?: $imageUrls['preview'],
                'thumbnail_url' => $imageUrls['thumb'],
                'preview_url' => $imageUrls['preview'],
                'file_url' => $imageUrls['origin'],
                'image_urls' => $imageUrls,
                'imageUrls' => $imageUrls,
                'original_url' => $fileUrl,
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
        $imageUrls = buildPictureImageUrls($pic);

        return [
            'id' => $pic->id,
            'url' => $imageUrls['thumb'] ?: $imageUrls['preview'],
            'thumbnail_url' => $imageUrls['thumb'],
            'preview_url' => $imageUrls['preview'],
            'file_url' => $imageUrls['origin'],
            'image_urls' => $imageUrls,
            'imageUrls' => $imageUrls,
            'original_url' => $fileUrl,
            'resource_id' => $resourceId,
            'role' => $role,
        ];
    }

    public function syncPicture($uid, $pic, $options = [])
    {
        if (!$pic || !$this->isBridgeEnabledForUser($uid)) {
            return null;
        }
        if ($this->isImportedResourcePicture($pic)) {
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
                'file_hash' => (string)($options['file_hash'] ?? ''),
                'content_hash' => (string)($options['content_hash'] ?? ($options['file_hash'] ?? '')),
            ],
        ]);
        if (!empty($options['file_hash'])) {
            $payload['file_hash'] = (string)$options['file_hash'];
            $payload['content_hash'] = (string)($options['content_hash'] ?? $options['file_hash']);
        }
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

    public function syncAlbumRelation($uid, $relation, $role = 'album', $options = [])
    {
        if (!$relation || !$relation->picture) {
            return null;
        }
        return $this->syncPicture($uid, $relation->picture, array_merge([
            'b_folder_id' => (int)$relation->folder_id,
            'b_relation_id' => (int)$relation->id,
            'external_product_id' => (string)$relation->folder_id,
            'role' => $role,
            'sort_order' => (int)$relation->sort,
        ], $options));
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

    public function safeSyncAlbumRelation($uid, $relation, $role = 'album', $options = [])
    {
        try {
            return $this->syncAlbumRelation($uid, $relation, $role, $options);
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

    public function getPictureResourceImageUrl($pic, $type = 'thumb')
    {
        if (!$pic) {
            return '';
        }
        $resourceId = $this->getResourceIdFromPicture($pic);
        if (!$resourceId) {
            return '';
        }
        $type = in_array($type, ['thumb', 'preview', 'original'], true) ? $type : 'thumb';
        $cacheKey = 'ai_resource_image_url_' . (int)$pic->id . '_' . md5($resourceId . '|' . $type);
        $cacheTtl = $this->resourceImageUrlCacheTtl($type);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        $this->assertBridgeAllowed((int)$pic->uid);
        $user = $this->getBridgeUser((int)$pic->uid);
        $query = [
            'b_user_id' => $user->id,
            'mini_openid' => $user->openid,
            'unionid' => $user->unionid ?: '',
        ];
        try {
            $resp = $this->requestAiResource('GET', '/jiafangyun/bridge/resources/' . $resourceId . '?' . http_build_query($query), null);
        } catch (\Throwable $e) {
            Log::warning('[AiResourceBridge] resource lookup failed, fallback to stored picture: ' . $e->getMessage());
            $url = $this->getSignedStoredPictureUrl($pic, $type);
            Cache::set($cacheKey, $url, $cacheTtl);
            return $url;
        }
        $resource = $resp['resource'] ?? null;
        if (!$resource) {
            $url = $this->getSignedStoredPictureUrl($pic, $type);
            Cache::set($cacheKey, $url, $cacheTtl);
            return $url;
        }
        $url = '';
        if ($type === 'original') {
            $url = $this->firstResourceImageUrl($resource, $this->resourceOriginalUrlFields());
            if (!$url) {
                $url = $this->getSignedStoredPictureUrl($pic, $type);
            }
            Cache::set($cacheKey, $url, $cacheTtl);
            return $url;
        }
        if ($type === 'preview') {
            $url = $this->firstResourceImageUrl($resource, $this->resourcePreviewUrlFields());
            if (!$url) {
                $url = $this->getSignedStoredPictureUrl($pic, $type);
            }
            Cache::set($cacheKey, $url, $cacheTtl);
            return $url;
        }
        $url = $this->firstResourceImageUrl($resource, $this->resourceThumbnailUrlFields());
        if (!$url) {
            $url = $this->getSignedStoredPictureUrl($pic, $type);
        }
        Cache::set($cacheKey, $url, $cacheTtl);
        return $url;
    }

    private function resourceImageUrlCacheTtl($type)
    {
        return $type === 'original' ? 300 : 240;
    }

    private function getSignedStoredPictureUrl($pic, $type = 'thumb')
    {
        $url = trim((string)($pic->imgurl ?? ''));
        if ($url === '') {
            return '';
        }
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        } elseif (WdXcxPic::isSchemeLessHttpUrl($url)) {
            $url = 'https://' . ltrim($url, '/');
        } elseif (!WdXcxPic::isHttpUrl($url)) {
            $url = removePicStyle(remote($pic->uniacid ?: $this->uniacid, $url, 1));
        }
        $url = removePicStyle($url);
        if ($url === '') {
            return '';
        }
        try {
            $resp = $this->requestAiResource('POST', '/jiafangyun/bridge/images/sign', [
                'url' => $url,
                'expire_minutes' => $type === 'original' ? 10 : 30,
            ]);
            return trim((string)($resp['signed_url'] ?? ($resp['url'] ?? '')));
        } catch (\Throwable $e) {
            Log::error('[AiResourceBridge] sign stored picture failed: ' . $e->getMessage());
            return '';
        }
    }

    private function getResourceIdFromPicture($pic)
    {
        if (method_exists($pic, 'getImportedResourceId')) {
            return (int)$pic->getImportedResourceId();
        }
        $name = (string)($pic->pic_name ?? '');
        if (preg_match('/^(?:我的资源库|AI资源库)-(-?\d+)$/u', $name, $match)) {
            return (int)$match[1];
        }
        return 0;
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

    private function readBridgeConfig($primary, $legacy, $default)
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

    private function dedupeResourceResponse($resp)
    {
        if (!is_array($resp)) {
            return $resp;
        }
        $field = null;
        if (isset($resp['resources']) && is_array($resp['resources'])) {
            $field = 'resources';
        } elseif (isset($resp['list']) && is_array($resp['list'])) {
            $field = 'list';
        }
        if (!$field) {
            return $resp;
        }

        $seen = [];
        $items = [];
        foreach ($resp[$field] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = $this->resourceDedupeKey($item);
            if ($key !== '' && isset($seen[$key])) {
                continue;
            }
            if ($key !== '') {
                $seen[$key] = true;
            }
            $items[] = $this->normalizeResourceImageUrls($item);
        }
        $resp[$field] = array_values($items);
        if (isset($resp['resources']) && $field !== 'resources') {
            $resp['resources'] = $resp[$field];
        }
        if (isset($resp['list']) && $field !== 'list') {
            $resp['list'] = $resp[$field];
        }
        if (isset($resp['total']) && (int)$resp['total'] < count($items)) {
            $resp['total'] = count($items);
        }
        return $resp;
    }

    private function resourceDedupeKey($item)
    {
        $url = $this->firstResourceImageUrl($item, $this->resourceDedupeUrlFields());
        $url = strtolower(removePicStyle(trim((string)$url)));
        if ($url !== '') {
            return 'url:' . $url;
        }
        if (!empty($item['id'])) {
            return 'id:' . (string)$item['id'];
        }
        return '';
    }

    private function normalizeResourceImageUrls($item)
    {
        $rawItem = $item;
        foreach ($this->resourceImageUrlFields() as $field) {
            if (!empty($item[$field])) {
                $item[$field] = proxyExternalImageUrl(
                    $item[$field],
                    in_array($field, $this->resourcePreviewImageUrlFields(), true)
                );
            }
        }
        $originalUrl = $this->firstResourceImageUrl($rawItem, $this->resourceOriginalUrlFields());
        $previewUrl = $this->firstResourceImageUrl($rawItem, $this->resourcePreviewUrlFields());
        $thumbUrl = $this->firstResourceImageUrl($rawItem, $this->resourceThumbnailUrlFields());
        if ($thumbUrl === '') {
            $thumbUrl = $previewUrl ?: $originalUrl;
        }
        if ($previewUrl === '') {
            $previewUrl = $thumbUrl ?: $originalUrl;
        }
        if ($originalUrl === '') {
            $originalUrl = $previewUrl ?: $thumbUrl;
        }
        $item['thumbnail_url'] = proxyExternalImageUrl($thumbUrl, true);
        $item['preview_url'] = proxyExternalImageUrl($previewUrl, true);
        $item['file_url'] = proxyExternalImageUrl($originalUrl, false);
        $item['image_urls'] = [
            'thumb' => (string)($item['thumbnail_url'] ?? ''),
            'preview' => (string)($item['preview_url'] ?? ''),
            'edit' => (string)($item['preview_url'] ?? ''),
            'origin' => (string)($item['file_url'] ?? ''),
            'download' => (string)($item['download_url'] ?? ($item['file_url'] ?? '')),
        ];
        $item['imageUrls'] = $item['image_urls'];
        return $item;
    }

    private function resourceDedupeUrlFields()
    {
        return array_merge($this->resourceOriginalUrlFields(), $this->resourcePreviewUrlFields(), $this->resourceThumbnailUrlFields());
    }

    private function resourceOriginalUrlFields()
    {
        return [
            'file_url',
            'fileUrl',
            'original_url',
            'originalUrl',
            'picture_url_original',
            'pictureUrlOriginal',
            'signed_url',
            'signedUrl',
            'download_url',
            'downloadUrl',
            'image_url',
            'imageUrl',
            'url',
            'preview_url',
            'previewUrl',
        ];
    }

    private function resourcePreviewUrlFields()
    {
        return [
            'preview_url',
            'previewUrl',
            'image_url',
            'imageUrl',
            'url',
            'picture_url',
            'pictureUrl',
            'file_url',
            'fileUrl',
            'original_url',
            'originalUrl',
            'signed_url',
            'signedUrl',
        ];
    }

    private function resourceThumbnailUrlFields()
    {
        return [
            'thumbnail_url',
            'thumbnailUrl',
            'thumb_url',
            'thumbUrl',
            'thumb',
            'preview_url',
            'previewUrl',
            'image_url',
            'imageUrl',
            'cover_url',
            'coverUrl',
            'picture_url',
            'pictureUrl',
            'url',
        ];
    }

    private function resourceImageUrlFields()
    {
        return [
            'thumbnail_url',
            'thumbnailUrl',
            'thumb_url',
            'thumbUrl',
            'thumb',
            'preview_url',
            'previewUrl',
            'image_url',
            'imageUrl',
            'cover_url',
            'coverUrl',
            'file_url',
            'fileUrl',
            'original_url',
            'originalUrl',
            'picture_url',
            'pictureUrl',
            'picture_url_original',
            'pictureUrlOriginal',
            'signed_url',
            'signedUrl',
            'download_url',
            'downloadUrl',
            'url',
        ];
    }

    private function resourcePreviewImageUrlFields()
    {
        return [
            'thumbnail_url',
            'thumbnailUrl',
            'thumb_url',
            'thumbUrl',
            'thumb',
            'preview_url',
            'previewUrl',
            'image_url',
            'imageUrl',
            'cover_url',
            'coverUrl',
            'picture_url',
            'pictureUrl',
            'url',
        ];
    }

    private function firstResourceImageUrl($item, $fields)
    {
        foreach ($fields as $field) {
            if (!empty($item[$field])) {
                return trim((string)$item[$field]);
            }
        }
        return '';
    }

    private function isImportedResourcePicture($pic)
    {
        $imgurl = trim((string)($pic->imgurl ?? ''));
        if ($imgurl === '') {
            return false;
        }
        if (WdXcxPic::isHttpUrl($imgurl) || WdXcxPic::isSchemeLessHttpUrl($imgurl) || strpos($imgurl, '//') === 0) {
            return true;
        }
        $name = (string)($pic->pic_name ?? '');
        return strpos($name, '我的资源库-') === 0 || strpos($name, 'AI资源库-') === 0;
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
