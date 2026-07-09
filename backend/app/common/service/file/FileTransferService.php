<?php

namespace app\common\service\file;

use app\common\model\file\FtFile;
use app\common\model\file\FtFileShare;
use app\common\model\file\FtFileShareItem;
use app\common\model\file\FtShareAccessLog;
use app\common\service\BaseService;
use think\App;
use think\facade\Db;
use think\facade\Log;

class FileTransferService extends BaseService
{
    const LOCAL_PROVIDER = 'local_private';

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function uploadFiles(array $files, array $param, int $uid)
    {
        $uploadFiles = $this->normalizeUploadedFiles($files);
        if (empty($uploadFiles)) {
            throwError('请选择上传文件');
        }

        $maxSizeMb = max(1, (int)env('file_transfer.max_upload_mb', 500));
        $saved = [];
        foreach ($uploadFiles as $index => $file) {
            $originalName = $this->getUploadOriginalName($file, $index, $param);
            if ($originalName === '') {
                throwError('文件名不正确');
            }
            $sizeBytes = method_exists($file, 'getSize') ? (int)$file->getSize() : 0;
            if ($sizeBytes < 0 || $sizeBytes > $maxSizeMb * 1024 * 1024) {
                throwError('文件大小超过限制');
            }

            $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
            $saveName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
            $relativeDir = 'file_transfer/' . $uid . '/' . date('Ymd');
            $targetDir = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throwError('文件存储目录创建失败');
            }

            $info = $file->move($targetDir, $saveName);
            if (!$info) {
                throwError(method_exists($file, 'getError') ? $file->getError() : '文件上传失败');
            }

            $objectKey = $relativeDir . '/' . $saveName;
            $fileModel = FtFile::create([
                'owner_user_id' => $uid,
                'sso_subject' => $param['sso_subject'] ?? null,
                'original_name' => $originalName,
                'object_key' => $objectKey,
                'storage_provider' => self::LOCAL_PROVIDER,
                'mime_type' => $this->detectMimeType($targetDir . DIRECTORY_SEPARATOR . $saveName),
                'extension' => $extension,
                'size_bytes' => $sizeBytes,
                'sha256' => is_file($targetDir . DIRECTORY_SEPARATOR . $saveName) ? hash_file('sha256', $targetDir . DIRECTORY_SEPARATOR . $saveName) : null,
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
        $originalName = trim((string)($param['original_name'] ?? ''));
        if ($originalName === '') {
            throwError('请填写文件名');
        }

        $sizeBytes = (int)($param['size_bytes'] ?? 0);
        if ($sizeBytes < 0) {
            throwError('文件大小不正确');
        }

        $objectKey = trim((string)($param['object_key'] ?? ''));
        if ($objectKey === '') {
            $objectKey = 'pending/' . date('Ymd') . '/' . bin2hex(random_bytes(12));
        }

        $file = FtFile::create([
            'owner_user_id' => $uid,
            'sso_subject' => $param['sso_subject'] ?? null,
            'original_name' => $originalName,
            'object_key' => $objectKey,
            'storage_provider' => trim((string)($param['storage_provider'] ?? 'pending')),
            'mime_type' => trim((string)($param['mime_type'] ?? '')),
            'extension' => strtolower(trim((string)($param['extension'] ?? pathinfo($originalName, PATHINFO_EXTENSION)))),
            'size_bytes' => $sizeBytes,
            'sha256' => $this->normalizeSha256($param['sha256'] ?? ''),
            'status' => trim((string)($param['status'] ?? 'uploaded')),
            'preview_url' => $param['preview_url'] ?? null,
        ]);

        return $this->formatFile($file);
    }

    public function createShare(array $param, int $uid)
    {
        $title = trim((string)($param['title'] ?? ''));
        if ($title === '') {
            $title = '快速分享 - ' . date('Y-m-d H:i');
        }
        $fileIds = $this->normalizeIdList($param['file_ids'] ?? []);
        if (empty($fileIds)) {
            throwError('请选择分享文件');
        }

        $files = FtFile::where('owner_user_id', $uid)
            ->whereIn('id', $fileIds)
            ->whereNull('deleted_at')
            ->select();
        if (count($files) !== count(array_unique($fileIds))) {
            throwError('存在无权分享的文件');
        }

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            $fileCount = count($files);
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += (int)$file->size_bytes;
            }

            $share = FtFileShare::create([
                'owner_user_id' => $uid,
                'sso_subject' => $param['sso_subject'] ?? null,
                'title' => $title,
                'share_code' => $this->makeShareCode(),
                'password_hash' => $this->makePasswordHash($param['password'] ?? ''),
                'expires_at' => $this->normalizeDateTime($param['expires_at'] ?? null),
                'max_downloads' => max(0, (int)($param['max_downloads'] ?? 0)),
                'allow_preview' => !empty($param['allow_preview']) ? 1 : 0,
                'notify_on_download' => !empty($param['notify_on_download']) ? 1 : 0,
                'status' => 'active',
                'file_count' => $fileCount,
                'total_size_bytes' => $totalSize,
            ]);

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

    public function getShareByCode(string $code, bool $publicView = true, string $password = '', int $ownerUid = 0)
    {
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
        $passwordVerified = !$hasPassword || !$publicView || $this->verifyPassword($password, (string)$share->password_hash);
        if ($publicView && $hasPassword && $password !== '' && !$passwordVerified) {
            throwError('访问密码不正确');
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

        $query = FtFileShare::where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->whereLike('title', '%' . $keyword . '%');
            })
            ->order('id desc');

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

    public function getSharedDownloadFile(int $fileId, string $code, string $password = '', bool $preview = false)
    {
        if ($fileId <= 0 || trim($code) === '') {
            throwError('下载参数不完整');
        }

        $share = FtFileShare::where('share_code', trim($code))
            ->whereNull('deleted_at')
            ->find();
        if (!$share) {
            throwError('分享不存在或已失效');
        }
        $this->assertShareUsable($share, !$preview);
        if (!empty($share->password_hash) && !$this->verifyPassword($password, (string)$share->password_hash)) {
            throwError('访问密码不正确');
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
            $share->download_count = (int)$share->download_count + 1;
            $share->save();
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

    private function assertShareUsable($share, bool $checkDownloadLimit)
    {
        if ((string)$share->status !== 'active') {
            throwError('分享不存在或已失效');
        }
        if (!empty($share->expires_at) && strtotime((string)$share->expires_at) < time()) {
            throwError('分享已过期');
        }
        if ($checkDownloadLimit && (int)$share->max_downloads > 0 && (int)$share->download_count >= (int)$share->max_downloads) {
            throwError('分享下载次数已用完');
        }
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
        if (!$base || !$realPath || strpos($realPath, $base) !== 0 || !is_file($realPath)) {
            throwError('文件不存在或已失效');
        }
        return $realPath;
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

        return array_merge($this->formatShareRow($share), [
            'has_password' => !empty($share->password_hash),
            'hasPassword' => !empty($share->password_hash),
            'password_verified' => $passwordVerified,
            'passwordVerified' => $passwordVerified,
            'files' => $files,
        ]);
    }

    private function formatShareRow($share)
    {
        return [
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

    private function normalizeDateTime($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
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

    private function makeShareCode()
    {
        do {
            $code = strtolower(bin2hex(random_bytes(5)));
            $exists = FtFileShare::where('share_code', $code)->find();
        } while ($exists);
        return $code;
    }
}
