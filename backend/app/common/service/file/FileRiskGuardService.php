<?php

namespace app\common\service\file;

use think\cache\driver\Redis;
use think\facade\Log;

class FileRiskGuardService
{
    const DEFAULT_BLOCKED_EXTENSIONS = 'php,phtml,phar,php3,php4,php5,asp,aspx,jsp,jspx,exe,dll,bat,cmd,com,msi,sh,bash,zsh,ps1,vbs,js,mjs,jar,war,ear,htaccess,htpasswd,html,htm,svg,swf,scr,lnk,reg,apk,ipa,dmg,pkg,deb,rpm';

    public function assertUploadAllowed(int $uid, string $transferToken, int $fileCount, int $totalBytes): void
    {
        $this->assertPositiveFileBatch($fileCount, $totalBytes);

        if ($uid > 0) {
            $this->hit('upload:user:' . $uid, $this->envInt('file_transfer.user_upload_minute_limit', 60), 60, '上传过于频繁，请稍后再试');
            return;
        }

        $this->hit('upload:ip:' . $this->clientKey(), $this->envInt('file_transfer.anonymous_upload_minute_limit', 12), 60, '上传过于频繁，请稍后再试');
        $this->hit('upload:token:' . $this->hashKey($transferToken), $this->envInt('file_transfer.anonymous_token_upload_hour_limit', 80), 3600, '上传过于频繁，请稍后再试');
        $this->addBytes('upload:bytes:ip:' . $this->clientKey(), $totalBytes, $this->anonymousDailyUploadBytes(), 86400, '今日匿名上传容量已达上限，请登录后继续');
    }

    public function assertShareCreateAllowed(int $uid, string $transferToken, int $fileCount, int $totalBytes): void
    {
        $this->assertPositiveFileBatch($fileCount, $totalBytes);

        if ($uid > 0) {
            $this->hit('share:create:user:' . $uid, $this->envInt('file_transfer.user_share_create_minute_limit', 30), 60, '创建分享过于频繁，请稍后再试');
            return;
        }

        $this->hit('share:create:ip:' . $this->clientKey(), $this->envInt('file_transfer.anonymous_share_create_minute_limit', 10), 60, '创建分享过于频繁，请稍后再试');
        $this->hit('share:create:token:' . $this->hashKey($transferToken), $this->envInt('file_transfer.anonymous_token_share_hour_limit', 60), 3600, '创建分享过于频繁，请稍后再试');
    }

    public function assertPublicAccessAllowed(string $scope, string $subject = ''): void
    {
        $scope = preg_replace('/[^a-z0-9_:-]+/i', '_', $scope) ?: 'public';
        $client = $this->clientKey();
        $subjectKey = $subject !== '' ? ':' . $this->hashKey($subject) : '';

        $this->hit('public:' . $scope . ':ip:' . $client, $this->publicMinuteLimit($scope), 60, '访问过于频繁，请稍后再试');
        $this->hit('public:' . $scope . ':hour:' . $client . $subjectKey, $this->publicHourLimit($scope), 3600, '访问过于频繁，请稍后再试');
    }

    public function assertPublicSubmissionAllowed(int $taskId, int $fileCount, int $totalBytes): void
    {
        $this->assertPositiveFileBatch($fileCount, $totalBytes);
        $client = $this->clientKey();

        $this->hit('submission:ip:' . $client, $this->envInt('file_transfer.public_submission_minute_limit', 6), 60, '提交过于频繁，请稍后再试');
        $this->hit('submission:task:' . $taskId . ':' . $client, $this->envInt('file_transfer.public_submission_task_hour_limit', 20), 3600, '提交过于频繁，请稍后再试');
        $this->addBytes('submission:bytes:task:' . $taskId, $totalBytes, $this->publicTaskDailyBytes(), 86400, '今日任务提交容量已达上限，请联系发起方');
    }

    public function assertZipDownloadAllowed(int $fileCount, int $totalBytes): void
    {
        $maxFiles = $this->envInt('file_transfer.max_zip_files', 100);
        $maxBytes = $this->envInt('file_transfer.max_zip_mb', 512) * 1024 * 1024;

        if ($fileCount > $maxFiles) {
            $this->recordRiskEvent('zip_file_count_blocked', ['file_count' => $fileCount, 'max_files' => $maxFiles]);
            throwError('文件数量过多，请分批下载');
        }
        if ($totalBytes > $maxBytes) {
            $this->recordRiskEvent('zip_size_blocked', ['total_bytes' => $totalBytes, 'max_bytes' => $maxBytes]);
            throwError('打包文件过大，请分批下载');
        }
        $this->hit('zip:user:' . $this->clientKey(), $this->envInt('file_transfer.zip_minute_limit', 3), 60, '打包下载过于频繁，请稍后再试');
    }

    public function acquireLock(string $scope, int $ttl, string $message = '操作正在处理中，请稍后再试'): array
    {
        $scope = preg_replace('/[^a-z0-9_:-]+/i', '_', $scope) ?: 'lock';
        $token = bin2hex(random_bytes(12));
        $cacheKey = 'file_risk:lock:' . $scope;

        try {
            $redis = new Redis(GetRedisConf());
            $handler = $redis->handler();
            $created = $handler->set($cacheKey, $token, ['nx', 'ex' => $ttl]);
            if ($created) {
                return ['backend' => 'redis', 'key' => $cacheKey, 'token' => $token];
            }
            $this->recordRiskEvent('lock_blocked', ['scope' => $scope, 'backend' => 'redis']);
            throwError($message);
        } catch (\Throwable $e) {
            if ($this->isBusinessError($e)) {
                throw $e;
            }
            Log::warning('file risk lock redis fallback: ' . $e->getMessage());
            return $this->acquireFileLock($cacheKey, $token, $ttl, $message);
        }
    }

    public function releaseLock(array $lock): void
    {
        if (empty($lock['key']) || empty($lock['token'])) {
            return;
        }

        if (($lock['backend'] ?? '') === 'file') {
            $this->releaseFileLock($lock);
            return;
        }

        try {
            $redis = new Redis(GetRedisConf());
            $handler = $redis->handler();
            if ((string)$handler->get($lock['key']) === (string)$lock['token']) {
                $handler->del($lock['key']);
            }
        } catch (\Throwable $e) {
            Log::warning('file risk lock release failed: ' . $e->getMessage());
        }
    }

    public function assertUploadNameSafe(string $name, array $context = []): void
    {
        $lowerName = strtolower($name);
        $extension = strtolower((string)pathinfo($lowerName, PATHINFO_EXTENSION));
        $blockedExtensions = $this->blockedExtensions();

        if ($extension !== '' && in_array($extension, $blockedExtensions, true)) {
            $this->recordRiskEvent('blocked_upload_extension', array_merge($context, [
                'extension' => $extension,
                'name_hash' => $this->hashKey($name),
            ]));
            throwError('暂不支持上传该类型文件');
        }
        if (preg_match('/\.(php[0-9]?|phtml|phar|asp|aspx|jsp|jspx)(\.|$)/i', $lowerName)) {
            $this->recordRiskEvent('blocked_upload_double_extension', array_merge($context, [
                'extension' => $extension,
                'name_hash' => $this->hashKey($name),
            ]));
            throwError('暂不支持上传该类型文件');
        }
    }

    public function assertStoredFileSafe(string $path, string $originalName, array $context = []): void
    {
        if (!$this->envBool('file_transfer.enable_antivirus_scan', false)) {
            return;
        }
        if (!is_file($path)) {
            throwError('文件安全检测失败');
        }

        $commandTemplate = trim((string)env('file_transfer.antivirus_scan_command', ''));
        if ($commandTemplate === '') {
            $this->recordRiskEvent('antivirus_command_missing', array_merge($context, [
                'name_hash' => $this->hashKey($originalName),
            ]));
            if ($this->envBool('file_transfer.antivirus_scan_fail_closed', true)) {
                throwError('文件安全检测暂不可用，请稍后再试');
            }
            return;
        }
        if (!function_exists('exec')) {
            $this->recordRiskEvent('antivirus_exec_disabled', array_merge($context, [
                'name_hash' => $this->hashKey($originalName),
            ]));
            if ($this->envBool('file_transfer.antivirus_scan_fail_closed', true)) {
                throwError('文件安全检测暂不可用，请稍后再试');
            }
            return;
        }

        $exitCode = 0;
        $output = [];
        $escapedPath = escapeshellarg($path);
        $command = strpos($commandTemplate, '%s') !== false
            ? sprintf($commandTemplate, $escapedPath)
            : $commandTemplate . ' ' . $escapedPath;

        @exec($command . ' 2>&1', $output, $exitCode);
        if ($exitCode === 0) {
            return;
        }

        $event = $exitCode === 1 ? 'antivirus_infected_blocked' : 'antivirus_scan_error';
        $this->recordRiskEvent($event, array_merge($context, [
            'exit_code' => $exitCode,
            'extension' => strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION)),
            'name_hash' => $this->hashKey($originalName),
            'scan_output_hash' => $this->hashKey(implode("\n", array_slice($output, 0, 10))),
        ]));

        if ($exitCode === 1 || $this->envBool('file_transfer.antivirus_scan_fail_closed', true)) {
            throwError($exitCode === 1 ? '文件安全检测未通过' : '文件安全检测失败，请稍后再试');
        }
    }

    public function recordRiskEvent(string $event, array $context = [], string $level = 'warning'): void
    {
        try {
            $payload = [
                'event' => preg_replace('/[^a-z0-9_:-]+/i', '_', $event) ?: 'unknown',
                'client_key' => $this->clientKey(),
                'path' => substr((string)request()->pathinfo(), 0, 160),
                'method' => substr((string)request()->method(), 0, 12),
                'context' => $this->sanitizeContext($context),
            ];
            $line = 'file risk event: ' . json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($level === 'error') {
                Log::error($line);
            } else {
                Log::warning($line);
            }
        } catch (\Throwable $e) {
            Log::warning('file risk event log failed: ' . $e->getMessage());
        }
    }

    public function maxUploadSizeMb(int $uid): int
    {
        if ($uid > 0) {
            return $this->envInt('file_transfer.max_upload_mb', 500);
        }
        return min(
            $this->envInt('file_transfer.max_upload_mb', 500),
            $this->envInt('file_transfer.anonymous_max_upload_mb', 200)
        );
    }

    public function maxRegisteredUploadSizeMb(): int
    {
        return $this->envInt('file_transfer.max_registered_upload_mb', $this->envInt('file_transfer.max_upload_mb', 500));
    }

    private function assertPositiveFileBatch(int $fileCount, int $totalBytes): void
    {
        if ($fileCount <= 0 || $totalBytes < 0) {
            $this->recordRiskEvent('invalid_file_batch', ['file_count' => $fileCount, 'total_bytes' => $totalBytes]);
            throwError('文件参数不完整');
        }
        if ($fileCount > $this->envInt('file_transfer.max_files_per_request', 50)) {
            $this->recordRiskEvent('file_batch_count_blocked', [
                'file_count' => $fileCount,
                'max_files' => $this->envInt('file_transfer.max_files_per_request', 50),
            ]);
            throwError('单次文件数量过多，请分批上传');
        }
    }

    private function publicMinuteLimit(string $scope): int
    {
        $defaults = [
            'pickup' => 20,
            'share_password' => 10,
            'share_view' => 40,
            'share_download' => 60,
            'qrcode' => 12,
            'task_public' => 40,
            'task_access_code' => 10,
            'submission_receipt' => 20,
        ];
        return $this->envInt('file_transfer.' . $scope . '_minute_limit', $defaults[$scope] ?? 30);
    }

    private function publicHourLimit(string $scope): int
    {
        $defaults = [
            'pickup' => 80,
            'share_password' => 30,
            'share_view' => 240,
            'share_download' => 300,
            'qrcode' => 60,
            'task_public' => 240,
            'task_access_code' => 30,
            'submission_receipt' => 120,
        ];
        return $this->envInt('file_transfer.' . $scope . '_hour_limit', $defaults[$scope] ?? 120);
    }

    private function anonymousDailyUploadBytes(): int
    {
        return $this->envInt('file_transfer.anonymous_daily_upload_mb', 2048) * 1024 * 1024;
    }

    private function publicTaskDailyBytes(): int
    {
        return $this->envInt('file_transfer.public_task_daily_upload_mb', 10240) * 1024 * 1024;
    }

    private function hit(string $key, int $limit, int $ttl, string $message): void
    {
        if ($limit <= 0) {
            return;
        }
        $count = $this->incrementCounter($key, 1, $ttl);
        if ($count > $limit) {
            $this->recordRiskEvent('rate_limit_blocked', ['key_hash' => $this->hashKey($key), 'count' => $count, 'limit' => $limit, 'ttl' => $ttl]);
            throwError($message);
        }
    }

    private function addBytes(string $key, int $bytes, int $limit, int $ttl, string $message): void
    {
        if ($limit <= 0) {
            return;
        }
        $total = $this->incrementCounter($key, max(0, $bytes), $ttl);
        if ($total > $limit) {
            $this->recordRiskEvent('quota_blocked', ['key_hash' => $this->hashKey($key), 'total' => $total, 'limit' => $limit, 'ttl' => $ttl]);
            throwError($message);
        }
    }

    private function incrementCounter(string $key, int $amount, int $ttl): int
    {
        $cacheKey = 'file_risk:' . $key;
        try {
            $redis = new Redis(GetRedisConf());
            $handler = $redis->handler();
            $created = $handler->set($cacheKey, $amount, ['nx', 'ex' => $ttl]);
            $next = $created ? $amount : (int)$handler->incrBy($cacheKey, $amount);
            if (!$created && method_exists($handler, 'ttl') && (int)$handler->ttl($cacheKey) < 0 && method_exists($handler, 'expire')) {
                $handler->expire($cacheKey, $ttl);
            }
            return $next;
        } catch (\Throwable $e) {
            Log::warning('file risk redis fallback: ' . $e->getMessage());
            return $this->incrementFileCounter($cacheKey, $amount, $ttl);
        }
    }

    private function incrementFileCounter(string $key, int $amount, int $ttl): int
    {
        $dir = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'file_risk';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            return $amount;
        }
        $path = $dir . DIRECTORY_SEPARATOR . sha1($key) . '.json';
        $now = time();
        $data = ['value' => 0, 'expires_at' => $now + $ttl];
        $handle = fopen($path, 'c+');
        if (!$handle) {
            return $amount;
        }
        try {
            if (flock($handle, LOCK_EX)) {
                $raw = stream_get_contents($handle);
                $decoded = json_decode((string)$raw, true);
                if (is_array($decoded) && (int)($decoded['expires_at'] ?? 0) > $now) {
                    $data = $decoded;
                }
                $data['value'] = (int)($data['value'] ?? 0) + $amount;
                $data['expires_at'] = (int)($data['expires_at'] ?? ($now + $ttl));
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, json_encode($data));
                fflush($handle);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
        return (int)$data['value'];
    }

    private function clientKey(): string
    {
        $ip = (string)(request()->ip() ?: 'unknown');
        $ua = substr((string)request()->header('user-agent', ''), 0, 120);
        return $this->hashKey($ip . '|' . $ua);
    }

    private function hashKey(string $value): string
    {
        return substr(hash('sha256', $value), 0, 32);
    }

    private function blockedExtensions(): array
    {
        return array_values(array_unique(array_filter(array_map(function ($item) {
            return strtolower(trim((string)$item, " \t\n\r\0\x0B."));
        }, explode(',', (string)env('file_transfer.blocked_extensions', self::DEFAULT_BLOCKED_EXTENSIONS))))));
    }

    private function acquireFileLock(string $key, string $token, int $ttl, string $message): array
    {
        $dir = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'file_risk_locks';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throwError($message);
        }
        $path = $dir . DIRECTORY_SEPARATOR . sha1($key) . '.lock';
        if (is_file($path) && filemtime($path) + $ttl < time()) {
            @unlink($path);
        }
        $handle = @fopen($path, 'x');
        if (!$handle) {
            $this->recordRiskEvent('lock_blocked', ['backend' => 'file', 'key_hash' => $this->hashKey($key)]);
            throwError($message);
        }
        fwrite($handle, $token);
        fclose($handle);
        return ['backend' => 'file', 'key' => $key, 'token' => $token, 'path' => $path];
    }

    private function releaseFileLock(array $lock): void
    {
        $path = (string)($lock['path'] ?? '');
        if ($path === '' || !is_file($path)) {
            return;
        }
        if (trim((string)@file_get_contents($path)) === (string)$lock['token']) {
            @unlink($path);
        }
    }

    private function sanitizeContext(array $context): array
    {
        $result = [];
        foreach ($context as $key => $value) {
            $safeKey = preg_replace('/[^a-z0-9_:-]+/i', '_', (string)$key) ?: 'field';
            if (is_scalar($value) || $value === null) {
                $result[$safeKey] = is_string($value) ? mb_substr($value, 0, 180) : $value;
            } else {
                $result[$safeKey] = mb_substr(json_encode($value, JSON_UNESCAPED_UNICODE), 0, 180);
            }
        }
        return $result;
    }

    private function isBusinessError(\Throwable $e): bool
    {
        return stripos($e->getMessage(), '请稍后') !== false || stripos($e->getMessage(), '处理中') !== false;
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
}
