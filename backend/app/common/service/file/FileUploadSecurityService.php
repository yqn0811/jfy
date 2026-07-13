<?php

namespace app\common\service\file;

use OSS\OssClient;
use Qcloud\Cos\Client as CosClient;

class FileUploadSecurityService
{
    const PROVIDER_ALI_OSS = 'ali_oss';
    const PROVIDER_TEN_COS = 'ten_cos';

    private $riskGuard;

    public function __construct(FileRiskGuardService $riskGuard = null)
    {
        $this->riskGuard = $riskGuard ?: new FileRiskGuardService();
    }

    public function prepareUploadRequest(string $flow, int $uid, int $fileCount, int $totalBytes, array $context = []): array
    {
        $this->applyUploadTimeLimit();
        $this->assertRuntimeUploadLimits($fileCount, $totalBytes, $context);
        $this->assertTmpCapacityForBytes($totalBytes, $context);
        $this->assertDiskCapacityForBytes($totalBytes, $context);

        return [
            'flow' => $flow,
            'uid' => $uid,
            'file_count' => $fileCount,
            'total_bytes' => $totalBytes,
            'limits' => $this->runtimeLimits(),
        ];
    }

    public function assertDiskCapacityForBytes(int $incomingBytes, array $context = []): void
    {
        $path = $this->storageRoot();
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            $this->riskGuard->recordRiskEvent('storage_directory_create_failed', $context, 'error');
            throwError('文件存储目录创建失败');
        }

        $freeBytes = @disk_free_space($path);
        if ($freeBytes === false) {
            $this->riskGuard->recordRiskEvent('storage_free_space_unknown', $context, 'warning');
            if ($this->envBool('file_transfer.disk_check_fail_closed', true)) {
                throwError('存储容量检测失败，请稍后再试');
            }
            return;
        }

        $reserveBytes = $this->envInt('file_transfer.min_free_disk_mb', 2048) * 1024 * 1024;
        $requiredBytes = max(0, $incomingBytes) + $reserveBytes;
        if ((int)$freeBytes < $requiredBytes) {
            $this->riskGuard->recordRiskEvent('storage_free_space_blocked', array_merge($context, [
                'free_bytes' => (int)$freeBytes,
                'incoming_bytes' => $incomingBytes,
                'reserve_bytes' => $reserveBytes,
            ]), 'error');
            throwError('服务器存储空间不足，请稍后再试');
        }
    }

    public function assertTmpCapacityForBytes(int $incomingBytes, array $context = []): void
    {
        $path = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        if (!is_dir($path)) {
            $this->riskGuard->recordRiskEvent('upload_tmp_directory_missing', array_merge($context, [
                'tmp_dir' => (string)$path,
            ]), 'error');
            if ($this->envBool('file_transfer.tmp_check_fail_closed', true)) {
                throwError('上传临时目录不可用，请稍后再试');
            }
            return;
        }

        $freeBytes = @disk_free_space($path);
        if ($freeBytes === false) {
            $this->riskGuard->recordRiskEvent('upload_tmp_free_space_unknown', $context, 'warning');
            if ($this->envBool('file_transfer.tmp_check_fail_closed', true)) {
                throwError('上传临时目录容量检测失败，请稍后再试');
            }
            return;
        }

        $reserveBytes = $this->envInt('file_transfer.min_tmp_free_disk_mb', 1024) * 1024 * 1024;
        $requiredBytes = max(0, $incomingBytes) + $reserveBytes;
        if ((int)$freeBytes < $requiredBytes) {
            $this->riskGuard->recordRiskEvent('upload_tmp_free_space_blocked', array_merge($context, [
                'free_bytes' => (int)$freeBytes,
                'incoming_bytes' => $incomingBytes,
                'reserve_bytes' => $reserveBytes,
            ]), 'error');
            throwError('上传临时目录空间不足，请稍后再试');
        }
    }

    public function makeDirectUploadPolicy(array $param, int $uid): array
    {
        if (!$this->envBool('file_transfer.enable_direct_upload', false)) {
            throwError('直传暂未开启');
        }

        $provider = $this->normalizeProvider($param['storage_provider'] ?? ($param['storageProvider'] ?? ''));
        $originalName = $this->cleanFileName((string)($param['original_name'] ?? ($param['originalName'] ?? '')));
        if ($originalName === '') {
            throwError('请填写文件名');
        }
        $this->riskGuard->assertUploadNameSafe($originalName, ['flow' => 'direct_upload_policy', 'uid' => $uid]);

        $sizeBytes = (int)($param['size_bytes'] ?? ($param['sizeBytes'] ?? 0));
        if ($sizeBytes <= 0) {
            throwError('文件大小不正确');
        }
        if ($sizeBytes > $this->riskGuard->maxUploadSizeMb($uid) * 1024 * 1024) {
            throwError('文件大小超过限制');
        }

        $this->assertDiskCapacityForBytes(0, ['flow' => 'direct_upload_policy']);
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $subject = $this->normalizeTransferSubject($param, $uid);
        $objectKey = $this->makeDirectObjectKey($uid, $subject, $extension);
        $contentType = $this->sanitizeContentType((string)($param['mime_type'] ?? ($param['mimeType'] ?? 'application/octet-stream')));
        $expiresIn = max(60, min(3600, $this->envInt('file_transfer.direct_upload_policy_ttl_seconds', 600)));
        $expiresAt = time() + $expiresIn;

        if ($provider === self::PROVIDER_ALI_OSS) {
            return $this->makeAliOssPolicy($objectKey, $contentType, $expiresIn, $subject, $expiresAt);
        }
        if ($provider === self::PROVIDER_TEN_COS) {
            return $this->makeTenCosPolicy($objectKey, $contentType, $expiresIn, $subject, $expiresAt);
        }

        throwError('文件存储类型不支持');
    }

    public function assertDirectRegisterAllowed(array $param, int $uid): void
    {
        $provider = $this->normalizeProvider($param['storage_provider'] ?? ($param['storageProvider'] ?? ''));
        if ($provider === 'pending') {
            return;
        }
        if (!$this->envBool('file_transfer.enable_direct_upload', false)) {
            throwError('直传暂未开启');
        }
        if (!in_array($provider, $this->allowedDirectProviders(), true)) {
            throwError('文件存储类型不支持');
        }

        $objectKey = $this->normalizeObjectKey((string)($param['object_key'] ?? ($param['objectKey'] ?? '')));
        $subject = $this->normalizeTransferSubject($param, $uid);
        $expectedPrefix = $this->directObjectPrefix($uid, $subject);
        if (strpos($objectKey, $expectedPrefix) !== 0) {
            $this->riskGuard->recordRiskEvent('direct_upload_object_scope_blocked', [
                'provider' => $provider,
                'uid' => $uid,
                'object_key_hash' => $this->hashKey($objectKey),
            ]);
            throwError('文件路径不合法');
        }

        $signature = (string)($param['upload_signature'] ?? ($param['uploadSignature'] ?? ''));
        $expiresAt = (int)($param['upload_expires_at'] ?? ($param['uploadExpiresAt'] ?? 0));
        if ($expiresAt <= time()) {
            throwError('上传凭证已过期，请重新上传');
        }
        if ($signature === '' || !$this->verifyDirectObjectSignature($provider, $objectKey, $subject, $expiresAt, $signature)) {
            $this->riskGuard->recordRiskEvent('direct_upload_signature_blocked', [
                'provider' => $provider,
                'uid' => $uid,
                'object_key_hash' => $this->hashKey($objectKey),
            ]);
            throwError('上传凭证无效，请重新上传');
        }
        if ($this->envBool('file_transfer.direct_upload_verify_object', true)) {
            $this->assertRemoteObjectExists($provider, $objectKey);
        }
    }

    public function runtimeHealth(): array
    {
        $limits = $this->runtimeLimits();
        $storageRoot = $this->storageRoot();
        $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $scanEnabled = $this->envBool('file_transfer.enable_antivirus_scan', false);
        $scanCommand = trim((string)env('file_transfer.antivirus_scan_command', ''));
        $clamscanPath = $this->findExecutable('clamscan');
        $clamdscanPath = $this->findExecutable('clamdscan');
        $scanProbe = $scanEnabled ? $this->probeAntivirusScanner($scanCommand) : ['ok' => true, 'exit_code' => 0];
        $checks = [];

        $expectedMaxFiles = $this->envInt('file_transfer.max_files_per_request', 50);
        $checks[] = $this->makeCheck('php_max_file_uploads', (int)$limits['php_max_file_uploads'] >= $expectedMaxFiles, [
            'actual' => (int)$limits['php_max_file_uploads'],
            'expected' => $expectedMaxFiles,
        ]);

        $expectedMaxUpload = $this->envInt('file_transfer.max_upload_mb', 500);
        $checks[] = $this->makeCheck('php_upload_max_filesize', (int)$limits['upload_max_filesize_bytes'] >= $expectedMaxUpload * 1024 * 1024, [
            'actual_bytes' => (int)$limits['upload_max_filesize_bytes'],
            'expected_mb' => $expectedMaxUpload,
        ]);
        $checks[] = $this->makeCheck('php_post_max_size', (int)$limits['post_max_size_bytes'] >= $expectedMaxUpload * 1024 * 1024, [
            'actual_bytes' => (int)$limits['post_max_size_bytes'],
            'expected_mb' => $expectedMaxUpload,
        ]);

        if (!is_dir($storageRoot)) {
            @mkdir($storageRoot, 0755, true);
        }
        $freeBytes = is_dir($storageRoot) ? @disk_free_space($storageRoot) : false;
        $checks[] = $this->makeCheck('storage_free_space', $freeBytes !== false && (int)$freeBytes >= $this->envInt('file_transfer.min_free_disk_mb', 2048) * 1024 * 1024, [
            'path' => $storageRoot,
            'free_bytes' => $freeBytes === false ? 0 : (int)$freeBytes,
            'min_free_mb' => $this->envInt('file_transfer.min_free_disk_mb', 2048),
        ]);

        $tmpFreeBytes = is_dir($tmpDir) ? @disk_free_space($tmpDir) : false;
        $checks[] = $this->makeCheck('upload_tmp_free_space', $tmpFreeBytes !== false && (int)$tmpFreeBytes >= $this->envInt('file_transfer.min_tmp_free_disk_mb', 1024) * 1024 * 1024, [
            'path' => $tmpDir,
            'free_bytes' => $tmpFreeBytes === false ? 0 : (int)$tmpFreeBytes,
            'min_free_mb' => $this->envInt('file_transfer.min_tmp_free_disk_mb', 1024),
        ]);

        $checks[] = $this->makeCheck('antivirus_ready', !$scanEnabled || (($scanCommand !== '' || $clamscanPath !== '' || $clamdscanPath !== '') && $scanProbe['ok']), [
            'enabled' => $scanEnabled,
            'command_configured' => $scanCommand !== '',
            'clamscan' => $clamscanPath !== '',
            'clamdscan' => $clamdscanPath !== '',
            'probe_ok' => (bool)$scanProbe['ok'],
            'probe_exit_code' => (int)$scanProbe['exit_code'],
        ]);

        $directEnabled = $this->envBool('file_transfer.enable_direct_upload', false);
        $checks[] = $this->makeCheck('direct_upload_ready', !$directEnabled || count($this->allowedDirectProviders()) > 0, [
            'enabled' => $directEnabled,
            'providers' => $this->allowedDirectProviders(),
        ]);

        $ok = !in_array(false, array_column($checks, 'ok'), true);
        return [
            'ok' => $ok,
            'limits' => $limits,
            'checks' => $checks,
            'storage_root' => $storageRoot,
            'upload_tmp_dir' => $tmpDir,
        ];
    }

    private function applyUploadTimeLimit(): void
    {
        $seconds = $this->envInt('file_transfer.upload_request_timeout_seconds', 600);
        if ($seconds > 0 && function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }
    }

    private function assertRuntimeUploadLimits(int $fileCount, int $totalBytes, array $context = []): void
    {
        $limits = $this->runtimeLimits();
        if ((int)$limits['php_max_file_uploads'] > 0 && $fileCount > (int)$limits['php_max_file_uploads']) {
            $this->riskGuard->recordRiskEvent('php_file_upload_count_blocked', array_merge($context, [
                'file_count' => $fileCount,
                'php_max_file_uploads' => (int)$limits['php_max_file_uploads'],
            ]));
            throwError('单次文件数量过多，请分批上传');
        }
        if ((int)$limits['post_max_size_bytes'] > 0 && $totalBytes > (int)$limits['post_max_size_bytes']) {
            $this->riskGuard->recordRiskEvent('php_post_size_blocked', array_merge($context, [
                'total_bytes' => $totalBytes,
                'post_max_size_bytes' => (int)$limits['post_max_size_bytes'],
            ]));
            throwError('单次上传总大小超过限制');
        }
    }

    private function runtimeLimits(): array
    {
        return [
            'php_max_file_uploads' => (int)ini_get('max_file_uploads'),
            'upload_max_filesize' => (string)ini_get('upload_max_filesize'),
            'upload_max_filesize_bytes' => $this->iniBytes((string)ini_get('upload_max_filesize')),
            'post_max_size' => (string)ini_get('post_max_size'),
            'post_max_size_bytes' => $this->iniBytes((string)ini_get('post_max_size')),
            'max_input_time' => (int)ini_get('max_input_time'),
            'max_execution_time' => (int)ini_get('max_execution_time'),
            'memory_limit' => (string)ini_get('memory_limit'),
            'memory_limit_bytes' => $this->iniBytes((string)ini_get('memory_limit')),
        ];
    }

    private function makeAliOssPolicy(string $objectKey, string $contentType, int $expiresIn, string $subject, int $expiresAt): array
    {
        $config = $this->remoteConfig('oss');
        if (empty($config['ak']) || empty($config['sk']) || empty($config['domain']) || empty($config['bucket'])) {
            throwError('对象存储配置不完整');
        }
        require_once root_path() . '/vendor/aliyun/autoload.php';
        $client = new OssClient($config['ak'], $config['sk'], $config['domain']);
        $headers = ['Content-Type' => $contentType];
        $uploadUrl = $client->signUrl($config['bucket'], $objectKey, $expiresIn, 'PUT', $headers);

        return $this->formatDirectPolicy(self::PROVIDER_ALI_OSS, $objectKey, $uploadUrl, $contentType, $expiresIn, $expiresAt, $subject, $headers);
    }

    private function makeTenCosPolicy(string $objectKey, string $contentType, int $expiresIn, string $subject, int $expiresAt): array
    {
        $config = $this->remoteConfig('ten_cos');
        if (empty($config['ak']) || empty($config['sk']) || empty($config['region']) || empty($config['bucket'])) {
            throwError('对象存储配置不完整');
        }
        $client = new CosClient([
            'region' => $config['region'],
            'credentials' => [
                'secretId' => $config['sk'],
                'secretKey' => $config['ak'],
            ],
        ]);
        $headers = ['Content-Type' => $contentType];
        $uploadUrl = (string)$client->getPresignedUrl('putObject', [
            'Bucket' => $config['bucket'],
            'Key' => $objectKey,
            'ContentType' => $contentType,
        ], '+' . $expiresIn . ' seconds');

        return $this->formatDirectPolicy(self::PROVIDER_TEN_COS, $objectKey, $uploadUrl, $contentType, $expiresIn, $expiresAt, $subject, $headers);
    }

    private function formatDirectPolicy(string $provider, string $objectKey, string $uploadUrl, string $contentType, int $expiresIn, int $expiresAt, string $subject, array $headers): array
    {
        $signature = $this->makeDirectObjectSignature($provider, $objectKey, $subject, $expiresAt);
        return [
            'provider' => $provider,
            'storage_provider' => $provider,
            'storageProvider' => $provider,
            'object_key' => $objectKey,
            'objectKey' => $objectKey,
            'upload_url' => $uploadUrl,
            'uploadUrl' => $uploadUrl,
            'method' => 'PUT',
            'headers' => $headers,
            'expires_in' => $expiresIn,
            'expiresIn' => $expiresIn,
            'upload_expires_at' => $expiresAt,
            'uploadExpiresAt' => $expiresAt,
            'upload_signature' => $signature,
            'uploadSignature' => $signature,
        ];
    }

    private function remoteConfig(string $provider): array
    {
        $config = cacheRemoteSet(1);
        if ($provider === 'oss') {
            return $config['oss'] ?? ($config['aliOss'] ?? []);
        }
        if ($provider === 'ten_cos') {
            return $config['ten_cos'] ?? ($config['cos'] ?? []);
        }
        return [];
    }

    private function allowedDirectProviders(): array
    {
        if (!$this->envBool('file_transfer.enable_direct_upload', false)) {
            return [];
        }
        $raw = (string)env('file_transfer.direct_upload_providers', self::PROVIDER_TEN_COS . ',' . self::PROVIDER_ALI_OSS);
        return array_values(array_intersect(array_map([$this, 'normalizeProvider'], explode(',', $raw)), [
            self::PROVIDER_ALI_OSS,
            self::PROVIDER_TEN_COS,
        ]));
    }

    private function normalizeProvider($provider): string
    {
        $provider = strtolower(trim((string)$provider));
        if ($provider === 'tencent_cos') {
            return self::PROVIDER_TEN_COS;
        }
        return $provider ?: 'pending';
    }

    private function makeDirectObjectKey(int $uid, string $subject, string $extension = ''): string
    {
        $extension = strtolower(trim($extension, '.'));
        $suffix = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        return $this->directObjectPrefix($uid, $subject) . date('Ymd') . '/' . $suffix;
    }

    private function directObjectPrefix(int $uid, string $subject): string
    {
        if ($uid > 0) {
            return 'file_transfer/direct/user/' . $uid . '/';
        }
        return 'file_transfer/direct/anonymous/' . $this->hashKey($subject) . '/';
    }

    private function makeDirectObjectSignature(string $provider, string $objectKey, string $subject, int $expiresAt): string
    {
        $payload = $provider . '|' . $objectKey . '|' . $subject . '|' . $expiresAt;
        return hash_hmac('sha256', $payload, $this->directUploadSecret());
    }

    private function verifyDirectObjectSignature(string $provider, string $objectKey, string $subject, int $expiresAt, string $signature): bool
    {
        return hash_equals($this->makeDirectObjectSignature($provider, $objectKey, $subject, $expiresAt), trim($signature));
    }

    private function normalizeTransferSubject(array $param, int $uid): string
    {
        if ($uid > 0) {
            return 'user:' . $uid;
        }
        $token = trim((string)($param['transfer_token'] ?? ($param['transferToken'] ?? ($param['sso_subject'] ?? ($param['ssoSubject'] ?? '')))));
        if (!preg_match('/^[A-Za-z0-9_-]{16,128}$/', $token)) {
            throwError('传输凭证无效，请刷新后重试');
        }
        return 'anonymous:' . $token;
    }

    private function assertRemoteObjectExists(string $provider, string $objectKey): void
    {
        try {
            if ($provider === self::PROVIDER_ALI_OSS) {
                $config = $this->remoteConfig('oss');
                require_once root_path() . '/vendor/aliyun/autoload.php';
                $client = new OssClient($config['ak'], $config['sk'], $config['domain']);
                if (!$client->doesObjectExist($config['bucket'], $objectKey)) {
                    throwError('文件上传未完成，请重新上传');
                }
                return;
            }
            if ($provider === self::PROVIDER_TEN_COS) {
                $config = $this->remoteConfig('ten_cos');
                $client = new CosClient([
                    'region' => $config['region'],
                    'credentials' => [
                        'secretId' => $config['sk'],
                        'secretKey' => $config['ak'],
                    ],
                ]);
                $client->headObject([
                    'Bucket' => $config['bucket'],
                    'Key' => $objectKey,
                ]);
            }
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), '文件上传未完成') !== false) {
                throw $e;
            }
            $this->riskGuard->recordRiskEvent('direct_upload_object_verify_failed', [
                'provider' => $provider,
                'object_key_hash' => $this->hashKey($objectKey),
            ], 'warning');
            if ($this->envBool('file_transfer.direct_upload_verify_fail_closed', true)) {
                throwError('文件上传状态确认失败，请稍后再试');
            }
        }
    }

    private function directUploadSecret(): string
    {
        $secret = (string)env('file_transfer.direct_upload_secret', '');
        if ($secret !== '') {
            return $secret;
        }
        return (string)(env('file_transfer.receipt_token_secret', '') ?: getenv('JIAFANGYUN_BRIDGE_TOKEN') ?: 'jiafangyun-file-direct-upload');
    }

    private function storageRoot(): string
    {
        return rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'file_transfer';
    }

    private function normalizeObjectKey(string $objectKey): string
    {
        $objectKey = ltrim(str_replace('\\', '/', trim($objectKey)), '/');
        if ($objectKey === '' || mb_strlen($objectKey) > 512 || strpos($objectKey, '..') !== false || preg_match('/^[a-z]+:\/\//i', $objectKey)) {
            throwError('文件路径不合法');
        }
        return $objectKey;
    }

    private function cleanFileName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        return $name === '' ? '' : mb_substr($name, 0, 255);
    }

    private function sanitizeContentType(string $contentType): string
    {
        $contentType = trim($contentType);
        if ($contentType === '' || preg_match('/[\r\n]/', $contentType)) {
            return 'application/octet-stream';
        }
        return mb_substr($contentType, 0, 120);
    }

    private function makeCheck(string $name, bool $ok, array $detail = []): array
    {
        return [
            'name' => $name,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }

    private function findExecutable(string $name): string
    {
        if (!function_exists('exec')) {
            return '';
        }
        $output = [];
        $code = 1;
        @exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null', $output, $code);
        return $code === 0 && !empty($output[0]) ? (string)$output[0] : '';
    }

    private function probeAntivirusScanner(string $commandTemplate): array
    {
        if ($commandTemplate === '' || !function_exists('exec')) {
            return ['ok' => false, 'exit_code' => 127];
        }

        $path = tempnam(sys_get_temp_dir(), 'jfy-av-probe-');
        if (!$path) {
            return ['ok' => false, 'exit_code' => 126];
        }

        try {
            file_put_contents($path, 'jfy upload antivirus health probe ' . date('c'));
            $escapedPath = escapeshellarg($path);
            $command = strpos($commandTemplate, '%s') !== false
                ? sprintf($commandTemplate, $escapedPath)
                : $commandTemplate . ' ' . $escapedPath;
            $output = [];
            $exitCode = 0;
            @exec($command . ' 2>&1', $output, $exitCode);
            return ['ok' => $exitCode === 0, 'exit_code' => $exitCode];
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        if ($number < 0) {
            return 0;
        }
        switch ($unit) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
                break;
        }
        return (int)$number;
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if ($value === '' || $value === null) {
            return $default;
        }
        return !in_array(strtolower((string)$value), ['0', 'false', 'no', 'off'], true);
    }

    private function envInt(string $key, int $default): int
    {
        $value = env($key, $default);
        if ($value === '' || $value === null) {
            return $default;
        }
        return max(0, (int)$value);
    }

    private function hashKey(string $value): string
    {
        return substr(hash('sha256', $value), 0, 32);
    }
}
