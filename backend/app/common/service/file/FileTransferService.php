<?php

namespace app\common\service\file;

use app\common\model\file\FtFile;
use app\common\model\file\FtFileShare;
use app\common\model\file\FtFileShareItem;
use app\common\model\file\FtShareAccessLog;
use app\common\service\BaseService;
use app\common\service\TencentCOSService;
use think\App;
use think\facade\Db;
use think\facade\Log;

class FileTransferService extends BaseService
{
    const LOCAL_PROVIDER = 'local_private';
    const ALI_OSS_PROVIDER = 'ali_oss';
    const TENCENT_COS_PROVIDER = 'ten_cos';
    const ANONYMOUS_SHARE_TTL_SECONDS = 86400;

    private $riskGuard;
    private $uploadSecurity;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->riskGuard = new FileRiskGuardService();
        $this->uploadSecurity = new FileUploadSecurityService($this->riskGuard);
    }

    public function uploadFiles(array $files, array $param, int $uid)
    {
        $uploadItems = $this->normalizeUploadItems($files, $param);
        if (empty($uploadItems)) {
            throwError('请选择上传文件');
        }
        $transferToken = $this->normalizeTransferToken($param, $uid, true);
        $ownerSubject = $uid > 0 ? ($param['sso_subject'] ?? null) : $transferToken;

        $maxSizeMb = $this->riskGuard->maxUploadSizeMb($uid);
        $totalBytes = 0;
        foreach ($uploadItems as $item) {
            $totalBytes += method_exists($item['file'], 'getSize') ? max(0, (int)$item['file']->getSize()) : 0;
        }
        $this->uploadSecurity->prepareUploadRequest('quick_send', $uid, count($uploadItems), $totalBytes, [
            'flow' => 'quick_send',
            'uid' => $uid,
        ]);
        $this->riskGuard->assertUploadAllowed($uid, $transferToken, count($uploadItems), $totalBytes);
        $saved = [];
        foreach ($uploadItems as $item) {
            $file = $item['file'];
            $originalName = $item['original_name'];
            if ($originalName === '') {
                throwError('文件名不正确');
            }
            $this->riskGuard->assertUploadNameSafe($originalName, ['flow' => 'quick_send', 'uid' => $uid]);
            $sizeBytes = method_exists($file, 'getSize') ? (int)$file->getSize() : 0;
            if ($sizeBytes < 0 || $sizeBytes > $maxSizeMb * 1024 * 1024) {
                throwError('文件大小超过限制');
            }

            $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
            $saveName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
            $relativeDir = 'file_transfer/' . $uid . '/' . date('Ymd');
            $targetDir = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
            if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throwError('文件存储目录创建失败');
            }

            $info = $file->move($targetDir, $saveName);
            if (!$info) {
                throwError(method_exists($file, 'getError') ? $file->getError() : '文件上传失败');
            }

            $objectKey = $relativeDir . '/' . $saveName;
            $storedPath = $targetDir . DIRECTORY_SEPARATOR . $saveName;
            try {
                $this->riskGuard->assertStoredFileSafe($storedPath, $originalName, ['flow' => 'quick_send', 'uid' => $uid]);
            } catch (\Throwable $e) {
                if (is_file($storedPath)) {
                    @unlink($storedPath);
                }
                throw $e;
            }

            $fileModel = FtFile::create([
                'owner_user_id' => $uid,
                'sso_subject' => $ownerSubject,
                'original_name' => $originalName,
                'object_key' => $objectKey,
                'storage_provider' => self::LOCAL_PROVIDER,
                'mime_type' => $this->detectMimeType($storedPath),
                'extension' => $extension,
                'size_bytes' => $sizeBytes,
                'sha256' => is_file($storedPath) ? hash_file('sha256', $storedPath) : null,
                'status' => 'uploaded',
                'preview_url' => '',
            ]);
            $fileModel->preview_url = '/api/file/files/download?file_id=' . $fileModel->id;
            $fileModel->save();

            $saved[] = $this->formatFile($fileModel);
        }

        return [
            'items' => $saved,
            'file_ids' => array_map(function ($file) {
                return $file['id'];
            }, $saved),
        ];
    }

    public function registerFile(array $param, int $uid)
    {
        $ownerSubject = $uid > 0 ? ($param['sso_subject'] ?? null) : $this->normalizeTransferToken($param, $uid, false);
        $originalName = trim((string)($param['original_name'] ?? ''));
        if ($originalName === '') {
            throwError('请填写文件名');
        }
        $this->riskGuard->assertUploadNameSafe($originalName, ['flow' => 'register_file', 'uid' => $uid]);

        $sizeBytes = (int)($param['size_bytes'] ?? 0);
        if ($sizeBytes < 0) {
            throwError('文件大小不正确');
        }
        if ($sizeBytes > $this->riskGuard->maxRegisteredUploadSizeMb() * 1024 * 1024) {
            throwError('文件大小超过限制');
        }

        $objectKey = trim((string)($param['object_key'] ?? ''));
        if ($objectKey === '') {
            $objectKey = 'pending/' . date('Ymd') . '/' . bin2hex(random_bytes(12));
        }
        $objectKey = $this->normalizeRegisteredObjectKey($objectKey);
        $storageProvider = trim((string)($param['storage_provider'] ?? 'pending'));
        if (!in_array($storageProvider, ['pending', self::ALI_OSS_PROVIDER, self::TENCENT_COS_PROVIDER, 'tencent_cos'], true)) {
            throwError('文件存储类型不支持');
        }
        if ($storageProvider === 'tencent_cos') {
            $storageProvider = self::TENCENT_COS_PROVIDER;
        }
        if ($uid <= 0 && $storageProvider === 'pending') {
            throwError('上传凭证无效，请重新上传');
        }
        $this->uploadSecurity->assertDirectRegisterAllowed($param, $uid);
        $status = trim((string)($param['status'] ?? 'uploaded'));
        if (!in_array($status, ['pending', 'uploaded'], true)) {
            throwError('文件状态不正确');
        }

        $file = FtFile::create([
            'owner_user_id' => $uid,
            'sso_subject' => $ownerSubject,
            'original_name' => $originalName,
            'object_key' => $objectKey,
            'storage_provider' => $storageProvider,
            'mime_type' => trim((string)($param['mime_type'] ?? '')),
            'extension' => strtolower(trim((string)($param['extension'] ?? pathinfo($originalName, PATHINFO_EXTENSION)))),
            'size_bytes' => $sizeBytes,
            'sha256' => $this->normalizeSha256($param['sha256'] ?? ''),
            'status' => $status,
            'preview_url' => $param['preview_url'] ?? null,
        ]);

        return $this->formatFile($file);
    }

    public function makeDirectUploadPolicy(array $param, int $uid)
    {
        return $this->uploadSecurity->makeDirectUploadPolicy($param, $uid);
    }

    public function uploadHealth()
    {
        return $this->uploadSecurity->runtimeHealth();
    }

    public function createShare(array $param, int $uid)
    {
        $title = trim((string)($param['title'] ?? ''));
        if ($title === '') {
            $title = '快速分享 - ' . date('Y-m-d H:i');
        }
        $transferToken = $this->normalizeTransferToken($param, $uid, false);
        $ownerSubject = $uid > 0 ? ($param['sso_subject'] ?? null) : $transferToken;
        $fileIds = $this->normalizeIdList($param['file_ids'] ?? []);
        if (empty($fileIds)) {
            throwError('请选择分享文件');
        }

        $query = FtFile::where('owner_user_id', $uid)
            ->whereIn('id', $fileIds)
            ->whereNull('deleted_at');
        if ($uid <= 0) {
            $query->where('sso_subject', $transferToken);
        }
        $files = $query->select();
        if (count($files) !== count(array_unique($fileIds))) {
            throwError('存在无权分享的文件');
        }
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += (int)$file->size_bytes;
        }
        $this->riskGuard->assertShareCreateAllowed($uid, $transferToken, count($files), $totalSize);

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            $fileCount = count($files);

            $shareData = [
                'owner_user_id' => $uid,
                'sso_subject' => $ownerSubject,
                'title' => $title,
                'share_code' => $this->makeShareCode(),
                'pickup_code' => $this->makePickupCode($param['pickup_code'] ?? ($param['pickupCode'] ?? ($param['password'] ?? ''))),
                'max_downloads' => max(0, (int)($param['max_downloads'] ?? 0)),
                'allow_preview' => !empty($param['allow_preview']) ? 1 : 0,
                'notify_on_download' => !empty($param['notify_on_download']) ? 1 : 0,
                'status' => 'active',
                'file_count' => $fileCount,
                'total_size_bytes' => $totalSize,
            ];
            $shareData['password_hash'] = $this->makePasswordHash((string)$shareData['pickup_code']);
            if ($uid > 0) {
                $shareData['expires_at'] = $this->normalizeDateTime($param['expires_at'] ?? null);
            }

            $share = FtFileShare::create($shareData);
            if ($uid <= 0) {
                $this->setAnonymousShareExpiry((int)$share->id);
            }

            $sort = 0;
            foreach ($fileIds as $fileId) {
                FtFileShareItem::create([
                    'share_id' => $share->id,
                    'file_id' => $fileId,
                    'sort_order' => $sort++,
                ]);
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return $this->getShareByCode($share->share_code, false, '', $uid);
    }

    public function verifySharePassword(string $code, string $password)
    {
        return $this->getShareByCode($code, true, $password);
    }

    public function getShareByPickupCode(string $pickupCode)
    {
        $pickupCode = $this->normalizePickupCode($pickupCode);
        if ($pickupCode === '') {
            throwError('请输入取件码');
        }
        $this->riskGuard->assertPublicAccessAllowed('pickup', $pickupCode);

        $share = FtFileShare::where('pickup_code', $pickupCode)
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('取件码不存在或已失效');
        }
        $this->assertShareUsable($share, false);
        $this->recordShareLog((int)$share->id, 'pickup');

        $items = FtFileShareItem::with(['file'])
            ->where('share_id', $share->id)
            ->order('sort_order asc, id asc')
            ->select();

        return $this->formatShare($share, $items, true, true);
    }

    public function getOwnerShareByCode(string $code, int $uid, array $param = [])
    {
        if ($uid > 0) {
            return $this->getShareByCode($code, false, '', $uid);
        }

        $transferToken = $this->normalizeTransferToken($param, $uid, false);
        $share = FtFileShare::where('share_code', trim($code))
            ->where('owner_user_id', 0)
            ->where('sso_subject', $transferToken)
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('分享不存在或已失效');
        }
        $this->assertShareUsable($share, false);

        $items = FtFileShareItem::with(['file'])
            ->where('share_id', $share->id)
            ->order('sort_order asc, id asc')
            ->select();

        return $this->formatShare($share, $items, false, true);
    }

    public function getShareQrcode(string $code, string $url)
    {
        $this->riskGuard->assertPublicAccessAllowed('qrcode', $code);
        $share = FtFileShare::where('share_code', trim($code))
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('分享不存在或已失效');
        }
        $this->assertShareUsable($share, false);

        $url = $this->normalizeShareResultUrl($url, (string)$share->share_code);
        $qrcodeDir = public_path() . 'uploads/qrcode';
        if (!is_dir($qrcodeDir) && !mkdir($qrcodeDir, 0755, true) && !is_dir($qrcodeDir)) {
            throwError('二维码目录创建失败');
        }

        try {
            $qrcode = \cores\utils\Utils::createQrcode($url, '', true);
        } catch (\Throwable $e) {
            throwError('二维码生成失败');
        }

        return [
            'share_code' => (string)$share->share_code,
            'shareCode' => (string)$share->share_code,
            'url' => $url,
            'qrcode' => $qrcode,
            'qrCode' => $qrcode,
        ];
    }

    public function getShareByCode(string $code, bool $publicView = true, string $password = '', int $ownerUid = 0)
    {
        if ($publicView) {
            $this->riskGuard->assertPublicAccessAllowed('share_view', $code);
            if ($password !== '') {
                $this->riskGuard->assertPublicAccessAllowed('share_password', $code);
            }
        }
        $share = FtFileShare::where('share_code', trim($code))
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('分享不存在或已失效');
        }
        if ($ownerUid > 0 && (int)$share->owner_user_id !== $ownerUid) {
            throwError('分享不存在或已失效');
        }
        $this->assertShareUsable($share, false);

        $hasPassword = !empty($share->password_hash);
        $passwordVerified = !$hasPassword || !$publicView || $this->verifyShareAccessCode($password, (string)$share->password_hash, $share);
        if ($publicView && $hasPassword && $password !== '' && !$passwordVerified) {
            throwError('取件码不正确');
        }

        if ($publicView) {
            $this->recordShareLog((int)$share->id, 'view');
        }

        $items = [];
        if (!$publicView || $passwordVerified) {
            $items = FtFileShareItem::with(['file'])
                ->where('share_id', $share->id)
                ->order('sort_order asc, id asc')
                ->select();
        }

        return $this->formatShare($share, $items, $publicView, $passwordVerified);
    }

    public function listShares(array $param, int $uid)
    {
        $page = max(1, (int)($param['page'] ?? 1));
        $limit = max(1, min(100, (int)($param['limit'] ?? 20)));
        $status = trim((string)($param['status'] ?? ''));
        $keyword = trim((string)($param['keyword'] ?? ''));
        $transferToken = $uid > 0 ? '' : $this->normalizeTransferToken($param, $uid, false);

        $query = FtFileShare::where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->whereLike('title', '%' . $keyword . '%');
            })
            ->order('id desc');

        if ($uid <= 0) {
            $query->where('sso_subject', $transferToken);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        return [
            'items' => array_map([$this, 'formatShareRow'], $list->items()),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
        ];
    }

    public function getSharedDownloadFile(int $fileId, string $code, string $password = '', bool $preview = false, string $pickupCode = '')
    {
        $code = trim($code);
        $pickupCode = $this->normalizePickupCode($pickupCode);
        if ($fileId <= 0 || ($code === '' && $pickupCode === '')) {
            throwError('下载参数不完整');
        }
        $this->riskGuard->assertPublicAccessAllowed('share_download', $code !== '' ? $code : $pickupCode);

        $shareQuery = FtFileShare::whereNull('deleted_at');
        if ($code !== '') {
            $shareQuery->where('share_code', $code);
        } else {
            $shareQuery->where('pickup_code', $pickupCode);
        }
        $share = $shareQuery
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('分享不存在或已失效');
        }
        $this->assertShareUsable($share, !$preview);
        $pickupVerified = $pickupCode !== '' && hash_equals((string)$share->pickup_code, $pickupCode);
        if (!$pickupVerified && !empty($share->password_hash) && !$this->verifyShareAccessCode($password, (string)$share->password_hash, $share)) {
            throwError('取件码不正确');
        }

        $item = FtFileShareItem::with(['file'])
            ->where('share_id', $share->id)
            ->where('file_id', $fileId)
            ->find();
        if (!$item || !$item->file) {
            throwError('文件不存在或已失效');
        }

        $file = $item->file;
        $path = $this->getLocalFilePath($file);
        if (!$preview) {
            $this->consumeShareDownloadQuota($share);
            $this->recordShareLog((int)$share->id, 'download', ['file_id' => $fileId]);
        }

        return [
            'path' => $path,
            'download_name' => (string)$file->original_name,
            'mime_type' => (string)$file->mime_type,
        ];
    }

    public function getOwnerDownloadFile(int $fileId, int $uid)
    {
        $file = FtFile::where('id', $fileId)
            ->where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->find();
        if (!$file) {
            throwError('文件不存在或已失效');
        }

        return [
            'path' => $this->getLocalFilePath($file),
            'download_name' => (string)$file->original_name,
            'mime_type' => (string)$file->mime_type,
        ];
    }

    public function recordShareLog(int $shareId, string $action, array $extra = [])
    {
        try {
            FtShareAccessLog::create([
                'share_id' => $shareId,
                'visitor_user_id' => 0,
                'action' => $action,
                'ip_label' => '',
                'ip_address' => request()->ip() ?: null,
                'user_agent' => substr((string)request()->header('user-agent', ''), 0, 1000),
                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            Log::error('file share access log failed: ' . $e->getMessage());
        }
    }

    public function cleanupExpiredAnonymousShares(int $limit = 200, bool $dryRun = false)
    {
        $limit = max(1, min(1000, $limit));
        $now = $this->nowUtc();
        $shares = FtFileShare::where('owner_user_id', 0)
            ->whereIn('status', ['active', 'expired'])
            ->whereNotNull('expires_at')
            ->whereRaw('expires_at <= CURRENT_TIMESTAMP')
            ->whereNull('deleted_at')
            ->order('id asc')
            ->limit($limit)
            ->select();

        $stats = [
            'shares' => 0,
            'files' => 0,
            'objects' => 0,
            'failed_objects' => 0,
            'dry_run' => $dryRun,
        ];

        foreach ($shares as $share) {
            $stats['shares']++;
            $items = FtFileShareItem::with(['file'])
                ->where('share_id', $share->id)
                ->select();

            $shareFailedObjects = 0;

            foreach ($items as $item) {
                if (!$item->file) {
                    continue;
                }
                $file = $item->file;
                if ((int)$file->owner_user_id !== 0 || !empty($file->deleted_at)) {
                    continue;
                }
                if ($this->hasActiveShareReference((int)$file->id, (int)$share->id, $now)) {
                    continue;
                }

                $stats['files']++;
                $deleted = $dryRun ? true : $this->deleteStoredObject($file);
                if ($deleted) {
                    $stats['objects']++;
                    if (!$dryRun) {
                        $file->status = 'deleted';
                        $file->deleted_at = $now;
                        $file->save();
                    }
                } else {
                    $stats['failed_objects']++;
                    $shareFailedObjects++;
                }
            }

            if (!$dryRun) {
                $share->status = 'expired';
                if ($shareFailedObjects === 0) {
                    $share->deleted_at = $now;
                }
                $share->save();
            }
        }

        return $stats;
    }

    private function assertShareUsable($share, bool $checkDownloadLimit)
    {
        if ((string)$share->status !== 'active') {
            throwError('分享不存在或已失效');
        }
        if (!empty($share->expires_at) && $this->isShareExpired($share)) {
            throwError('分享已过期');
        }
        if ($checkDownloadLimit && (int)$share->max_downloads > 0 && (int)$share->download_count >= (int)$share->max_downloads) {
            throwError('分享下载次数已用完');
        }
    }

    private function consumeShareDownloadQuota($share)
    {
        $affected = Db::connect('pgsql_file')
            ->name('file_shares')
            ->where('id', (int)$share->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereRaw('(expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)')
            ->whereRaw('(max_downloads <= 0 OR download_count < max_downloads)')
            ->update([
                'download_count' => Db::raw('download_count + 1'),
                'updated_at' => $this->nowDbExpression(),
            ]);

        if ((int)$affected > 0) {
            $share->download_count = (int)$share->download_count + 1;
            return;
        }

        $fresh = FtFileShare::where('id', (int)$share->id)->find();
        if (!$fresh || !empty($fresh->deleted_at) || (string)$fresh->status !== 'active') {
            throwError('分享不存在或已失效');
        }
        if (!empty($fresh->expires_at) && $this->isShareExpired($fresh)) {
            throwError('分享已过期');
        }
        throwError('分享下载次数已用完');
    }

    private function isShareExpired($share)
    {
        if (empty($share->id)) {
            return strtotime((string)$share->expires_at) <= time();
        }
        return FtFileShare::where('id', $share->id)
            ->whereRaw('expires_at IS NOT NULL AND expires_at <= CURRENT_TIMESTAMP')
            ->count() > 0;
    }

    private function normalizeShareResultUrl(string $url, string $shareCode)
    {
        $url = trim($url);
        if ($url === '') {
            $url = '/share-result?shareCode=' . rawurlencode($shareCode);
        }
        if (mb_strlen($url) > 2000 || !preg_match('/^(https?:\/\/|\/)/i', $url)) {
            throwError('二维码链接不合法');
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throwError('二维码链接不合法');
        }

        $path = isset($parts['path']) ? rtrim((string)$parts['path'], '/') : '';
        if (!in_array($path, ['/share-result', '/share-result.html'], true)) {
            throwError('二维码链接必须为分享结果页');
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $query);
        }
        $linkCode = trim((string)($query['shareCode'] ?? ($query['share_code'] ?? ($query['code'] ?? ''))));
        if ($linkCode !== $shareCode) {
            throwError('二维码链接与分享不匹配');
        }

        return $url;
    }

    private function getLocalFilePath($file)
    {
        if ((string)$file->storage_provider !== self::LOCAL_PROVIDER) {
            throwError('文件暂不支持本地下载');
        }
        $objectKey = str_replace('\\', '/', (string)$file->object_key);
        if (strpos($objectKey, '..') !== false || strpos($objectKey, 'file_transfer/') !== 0) {
            throwError('文件路径不合法');
        }

        $runtimeRoot = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $base = realpath($runtimeRoot . 'file_transfer');
        $path = $runtimeRoot . str_replace('/', DIRECTORY_SEPARATOR, $objectKey);
        $realPath = realpath($path);
        $baseDir = $base ? rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
        if (!$base || !$realPath || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
            throwError('文件不存在或已失效');
        }
        return $realPath;
    }

    private function hasActiveShareReference(int $fileId, int $currentShareId, string $now)
    {
        $shareIds = FtFileShareItem::where('file_id', $fileId)
            ->where('share_id', '<>', $currentShareId)
            ->column('share_id');
        if (empty($shareIds)) {
            return false;
        }

        return FtFileShare::whereIn('id', $shareIds)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->whereRaw('(expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)')
            ->count() > 0;
    }

    private function deleteStoredObject($file)
    {
        $provider = (string)$file->storage_provider;
        if ($provider === self::LOCAL_PROVIDER) {
            return $this->deleteLocalFile($file);
        }
        if ($provider === self::ALI_OSS_PROVIDER) {
            return $this->deleteAliOssObject($file);
        }
        if ($provider === self::TENCENT_COS_PROVIDER || $provider === 'tencent_cos') {
            return $this->deleteTencentCosObject($file);
        }
        Log::warning(sprintf(
            'file transfer cleanup skipped unsupported provider provider=%s object=%s file_id=%s',
            $provider,
            (string)$file->object_key,
            (string)$file->id
        ));
        return false;
    }

    private function deleteLocalFile($file)
    {
        try {
            $path = $this->getLocalFilePath($file);
        } catch (\Throwable $e) {
            return true;
        }
        if (is_file($path) && !@unlink($path)) {
            Log::error('file transfer cleanup unlink failed: ' . $path);
            return false;
        }
        return true;
    }

    private function deleteAliOssObject($file)
    {
        try {
            $config = cacheRemoteSet($this->uniacid);
            $aliInfo = $config['aliOss'] ?? ($config['oss'] ?? null);
            if (empty($aliInfo['ak']) || empty($aliInfo['sk']) || empty($aliInfo['domain']) || empty($aliInfo['bucket'])) {
                Log::warning('file transfer cleanup ali_oss skipped: config incomplete file_id=' . (string)$file->id);
                return false;
            }
            require_once root_path() . '/vendor/aliyun/autoload.php';
            $objectKey = $this->normalizeRemoteObjectKey((string)$file->object_key, (string)($aliInfo['folder_name'] ?? ''));
            $client = new \OSS\OssClient($aliInfo['ak'], $aliInfo['sk'], $aliInfo['domain']);
            $client->deleteObject($aliInfo['bucket'], $objectKey);
            return true;
        } catch (\Throwable $e) {
            Log::error('file transfer cleanup ali_oss failed file_id=' . (string)$file->id . ' error=' . $e->getMessage());
            return false;
        }
    }

    private function deleteTencentCosObject($file)
    {
        try {
            $config = cacheRemoteSet($this->uniacid);
            $cosInfo = $config['cos'] ?? ($config['ten_cos'] ?? null);
            if (empty($cosInfo['ak']) || empty($cosInfo['sk']) || empty($cosInfo['region']) || empty($cosInfo['bucket'])) {
                Log::warning('file transfer cleanup ten_cos skipped: config incomplete file_id=' . (string)$file->id);
                return false;
            }
            $objectKey = $this->normalizeRemoteObjectKey((string)$file->object_key, (string)($cosInfo['folder_name'] ?? ''));
            $cos = new TencentCOSService($cosInfo, $objectKey);
            if (!$cos->delObject()) {
                Log::error('file transfer cleanup ten_cos failed file_id=' . (string)$file->id . ' error=' . (string)$cos->getErrorMessage());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('file transfer cleanup ten_cos failed file_id=' . (string)$file->id . ' error=' . $e->getMessage());
            return false;
        }
    }

    private function normalizeRemoteObjectKey(string $objectKey, string $folderName = '')
    {
        $objectKey = ltrim(str_replace('\\', '/', $objectKey), '/');
        if (strpos($objectKey, '..') !== false || $objectKey === '') {
            throwError('文件路径不合法');
        }
        $folderName = trim(str_replace('\\', '/', $folderName), '/');
        if ($folderName !== '' && strpos($objectKey, $folderName . '/') !== 0) {
            $objectKey = $folderName . '/' . $objectKey;
        }
        return $objectKey;
    }

    private function normalizeRegisteredObjectKey(string $objectKey)
    {
        $objectKey = ltrim(str_replace('\\', '/', trim($objectKey)), '/');
        if ($objectKey === '' || mb_strlen($objectKey) > 512 || strpos($objectKey, '..') !== false || preg_match('/^[a-z]+:\/\//i', $objectKey)) {
            throwError('文件路径不合法');
        }
        return $objectKey;
    }

    private function formatFile($file, bool $includeStorage = true, string $shareCode = '')
    {
        $data = [
            'id' => (int)$file->id,
            'owner_user_id' => (int)$file->owner_user_id,
            'ownerUserId' => (int)$file->owner_user_id,
            'original_name' => (string)$file->original_name,
            'originalName' => (string)$file->original_name,
            'file_name' => (string)$file->original_name,
            'fileName' => (string)$file->original_name,
            'mime_type' => (string)$file->mime_type,
            'mimeType' => (string)$file->mime_type,
            'extension' => (string)$file->extension,
            'size_bytes' => (int)$file->size_bytes,
            'sizeBytes' => (int)$file->size_bytes,
            'size_mb' => round(((int)$file->size_bytes) / 1024 / 1024, 2),
            'sizeMb' => round(((int)$file->size_bytes) / 1024 / 1024, 2),
            'fileSizeMb' => round(((int)$file->size_bytes) / 1024 / 1024, 2),
            'status' => (string)$file->status,
            'created_at' => (string)$file->created_at,
            'createdAt' => (string)$file->created_at,
        ];
        if ($includeStorage) {
            $data['object_key'] = (string)$file->object_key;
            $data['objectKey'] = (string)$file->object_key;
            $data['storage_provider'] = (string)$file->storage_provider;
            $data['storageProvider'] = (string)$file->storage_provider;
            $data['sha256'] = (string)$file->sha256;
            $data['preview_url'] = (string)$file->preview_url;
            $data['previewUrl'] = (string)$file->preview_url;
            if ($shareCode === '') {
                $data['transfer_token'] = (string)$file->sso_subject;
                $data['transferToken'] = (string)$file->sso_subject;
            }
        }
        if ($shareCode !== '') {
            $data['download_url'] = '/api/file/shares/download?code=' . rawurlencode($shareCode) . '&file_id=' . (int)$file->id;
            $data['downloadUrl'] = $data['download_url'];
        }
        return $data;
    }

    private function formatShare($share, $items, bool $publicView = false, bool $passwordVerified = true)
    {
        $files = [];
        foreach ($items as $item) {
            if ($item->file) {
                $files[] = $this->formatFile($item->file, !$publicView, (string)$share->share_code);
            }
        }

        $recentLogs = [];
        if (!$publicView) {
            $recentLogs = FtShareAccessLog::where('share_id', $share->id)
                ->order('occurred_at desc, id desc')
                ->limit(8)
                ->select()
                ->map(function ($log) {
                    return $this->formatShareAccessLog($log);
                })
                ->toArray();
        }

        return array_merge($this->formatShareRow($share, !$publicView || $passwordVerified), [
            'has_password' => !empty($share->password_hash),
            'hasPassword' => !empty($share->password_hash),
            'password_verified' => $passwordVerified,
            'passwordVerified' => $passwordVerified,
            'files' => $files,
            'recent_logs' => $recentLogs,
            'recentLogs' => $recentLogs,
        ]);
    }

    private function formatShareAccessLog($log)
    {
        $ipLabel = (string)($log->ip_label ?: $log->ip_address ?: '');
        if ($ipLabel !== '' && strpos($ipLabel, '.') !== false) {
            $parts = explode('.', $ipLabel);
            if (count($parts) === 4) {
                $parts[3] = '*';
                $ipLabel = implode('.', $parts);
            }
        }

        return [
            'id' => (string)$log->id,
            'share_id' => (string)$log->share_id,
            'shareId' => (string)$log->share_id,
            'visitor_name' => '访客',
            'visitorName' => '访客',
            'action' => (string)$log->action,
            'occurred_at' => (string)$log->occurred_at,
            'occurredAt' => (string)$log->occurred_at,
            'ip_label' => $ipLabel,
            'ipLabel' => $ipLabel,
        ];
    }

    private function formatShareRow($share, bool $includePickupCode = false)
    {
        $data = [
            'id' => (int)$share->id,
            'task_id' => '',
            'taskId' => '',
            'owner_user_id' => (int)$share->owner_user_id,
            'ownerUserId' => (int)$share->owner_user_id,
            'title' => (string)$share->title,
            'share_code' => (string)$share->share_code,
            'shareCode' => (string)$share->share_code,
            'share_url' => '/share-result.html?shareCode=' . $share->share_code,
            'shareUrl' => '/share-result.html?shareCode=' . $share->share_code,
            'expires_at' => (string)$share->expires_at,
            'expiresAt' => (string)$share->expires_at,
            'max_downloads' => (int)$share->max_downloads,
            'maxDownloads' => (int)$share->max_downloads,
            'download_count' => (int)$share->download_count,
            'downloadCount' => (int)$share->download_count,
            'allow_preview' => (bool)$share->allow_preview,
            'allowPreview' => (bool)$share->allow_preview,
            'notify_on_download' => (bool)$share->notify_on_download,
            'notifyOnDownload' => (bool)$share->notify_on_download,
            'status' => (string)$share->status,
            'file_count' => (int)$share->file_count,
            'fileCount' => (int)$share->file_count,
            'total_size_bytes' => (int)$share->total_size_bytes,
            'totalSizeBytes' => (int)$share->total_size_bytes,
            'total_size_mb' => round(((int)$share->total_size_bytes) / 1024 / 1024, 2),
            'totalSizeMb' => round(((int)$share->total_size_bytes) / 1024 / 1024, 2),
            'created_at' => (string)$share->created_at,
            'createdAt' => (string)$share->created_at,
            'updated_at' => (string)$share->updated_at,
            'updatedAt' => (string)$share->updated_at,
        ];
        if ($includePickupCode) {
            $data['pickup_code'] = (string)$share->pickup_code;
            $data['pickupCode'] = (string)$share->pickup_code;
        }
        return $data;
    }

    private function normalizeUploadItems($files, array $param): array
    {
        $uploadFiles = $this->normalizeUploadedFiles($files);
        $items = [];
        foreach ($uploadFiles as $index => $file) {
            $originalName = $this->getUploadOriginalName($file, $index, $param);
            if ($this->isSystemMetadataFileName($originalName)) {
                $this->riskGuard->recordRiskEvent('ignored_system_metadata_upload', [
                    'flow' => 'quick_send',
                    'name_hash' => hash('sha256', $originalName),
                ], 'info');
                continue;
            }
            $items[] = [
                'file' => $file,
                'original_index' => $index,
                'original_name' => $originalName,
            ];
        }
        return $items;
    }

    private function normalizeUploadedFiles($files)
    {
        $result = [];
        foreach ($files as $file) {
            if (is_array($file)) {
                $result = array_merge($result, $this->normalizeUploadedFiles($file));
            } elseif (is_object($file) && method_exists($file, 'move')) {
                $result[] = $file;
            }
        }
        return $result;
    }

    private function getUploadOriginalName($file, int $index, array $param)
    {
        $names = $param['original_names'] ?? [];
        if (is_array($names) && isset($names[$index]) && $names[$index] !== '') {
            return $this->cleanFileName((string)$names[$index]);
        }
        foreach (['original_name', 'filename', 'file_name', 'name'] as $field) {
            if (!isset($param[$field]) || $param[$field] === '') {
                continue;
            }
            if (is_array($param[$field]) && isset($param[$field][$index])) {
                return $this->cleanFileName((string)$param[$field][$index]);
            }
            if (!is_array($param[$field])) {
                return $this->cleanFileName((string)$param[$field]);
            }
        }
        if (method_exists($file, 'getOriginalName')) {
            return $this->cleanFileName((string)$file->getOriginalName());
        }
        if (method_exists($file, 'getInfo')) {
            $info = $file->getInfo();
            if (!empty($info['name'])) {
                return $this->cleanFileName((string)$info['name']);
            }
        }
        return '';
    }

    private function isSystemMetadataFileName(string $name): bool
    {
        $normalized = str_replace('\\', '/', trim($name));
        $lower = strtolower($normalized);
        $parts = array_values(array_filter(explode('/', $lower), function ($part) {
            return $part !== '';
        }));
        $baseName = $parts ? end($parts) : $lower;

        return in_array($baseName, ['.ds_store', 'thumbs.db', 'desktop.ini', 'ehthumbs.db'], true)
            || strpos($baseName, '._') === 0
            || in_array('__macosx', $parts, true);
    }

    private function cleanFileName(string $name)
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        return $name === '' ? '' : mb_substr($name, 0, 255);
    }

    private function detectMimeType(string $path)
    {
        if (is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                return $mime ?: '';
            }
        }
        return '';
    }

    private function normalizeIdList($value)
    {
        if (is_string($value)) {
            $value = array_filter(explode(',', $value));
        }
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function normalizeSha256($value)
    {
        $value = strtolower(trim((string)$value));
        return preg_match('/^[a-f0-9]{64}$/', $value) ? $value : null;
    }

    private function normalizeTransferToken(array $param, int $uid, bool $createIfMissing)
    {
        if ($uid > 0) {
            return '';
        }
        $token = trim((string)($param['transfer_token'] ?? ($param['transferToken'] ?? '')));
        if ($token === '') {
            $token = trim((string)($param['sso_subject'] ?? ($param['ssoSubject'] ?? '')));
        }
        if ($token === '' && $createIfMissing) {
            $token = bin2hex(random_bytes(16));
        }
        if (!preg_match('/^[A-Za-z0-9_-]{16,128}$/', $token)) {
            throwError('传输凭证无效，请刷新后重试');
        }
        return $token;
    }

    private function normalizeDateTime($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp ? $this->formatUtc($timestamp) : null;
    }

    private function nowUtc()
    {
        return $this->formatUtc(time());
    }

    private function nowDbExpression()
    {
        return Db::raw('CURRENT_TIMESTAMP');
    }

    private function setAnonymousShareExpiry(int $shareId)
    {
        FtFileShare::where('id', $shareId)->update([
            'expires_at' => Db::raw("CURRENT_TIMESTAMP + INTERVAL '24 hours'"),
        ]);
    }

    private function formatUtc(int $timestamp)
    {
        return gmdate('Y-m-d H:i:sP', $timestamp);
    }

    private function makePasswordHash($password)
    {
        $password = trim((string)$password);
        return $password === '' ? null : password_hash($password, PASSWORD_DEFAULT);
    }

    private function verifyPassword(string $password, string $hash)
    {
        if ($hash === '') {
            return true;
        }
        return $password !== '' && password_verify($password, $hash);
    }

    private function verifyShareAccessCode(string $code, string $hash, $share)
    {
        if ($hash === '') {
            return true;
        }
        if ($code === '') {
            return false;
        }
        if (!empty($share->pickup_code)) {
            return $this->verifyPassword($this->normalizePickupCode($code), $hash);
        }
        return $this->verifyPassword($code, $hash);
    }

    private function makeShareCode()
    {
        do {
            $code = strtolower(bin2hex(random_bytes(5)));
            $exists = FtFileShare::where('share_code', $code)->find();
        } while ($exists);
        return $code;
    }

    private function normalizePickupCode($code)
    {
        $code = trim((string)$code);
        if ($code === '') {
            return '';
        }
        if (!preg_match('/^[A-Za-z0-9]{4}$/', $code)) {
            throwError('取件码需为 4 位大小写英文或数字');
        }
        return $code;
    }

    private function makePickupCode($preferred = '')
    {
        $preferred = $this->normalizePickupCode($preferred);
        if ($preferred !== '') {
            $exists = FtFileShare::where('pickup_code', $preferred)->find();
            if ($exists) {
                throwError('取件码已被使用，请换一个');
            }
            return $preferred;
        }

        do {
            $code = $this->makeRandomPickupCode();
            $exists = FtFileShare::where('pickup_code', $code)->find();
        } while ($exists);
        return $code;
    }

    private function makeRandomPickupCode()
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $code = '';
        $maxIndex = strlen($alphabet) - 1;
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }
        return $code;
    }
}
