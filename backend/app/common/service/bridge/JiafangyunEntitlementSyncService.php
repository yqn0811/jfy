<?php

namespace app\common\service\bridge;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserVipGradeInfo;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use think\App;
use think\facade\Db;
use think\facade\Log;

class JiafangyunEntitlementSyncService extends BaseService
{
    const DEFAULT_FREE_SPACE_MB = 50;

    private $bridgeClient;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->bridgeClient = new JiafangyunBridgeClient($app);
    }

    public function syncUser($uid)
    {
        $user = $this->bridgeClient->getUser($uid);
        $query = http_build_query($this->bridgeClient->userPayload($user));
        $entitlements = $this->bridgeClient->get('/jiafangyun/bridge/user/entitlements?' . $query);
        return $this->applyToUser($user, $entitlements);
    }

    public function syncUserQuietly($uid)
    {
        try {
            return $this->syncUser($uid);
        } catch (\Throwable $e) {
            Log::error('[JiafangyunEntitlementSync] sync failed: ' . $e->getMessage());
            return null;
        }
    }

    private function applyToUser($user, $entitlements)
    {
        $level = trim((string)($entitlements['membership_level'] ?? 'free'));
        $gradeLevel = $this->levelToGrade($level);
        $capacityBytes = (int)($entitlements['resource_storage_capacity_bytes'] ?? 0);
        if ($capacityBytes <= 0 && isset($entitlements['resource_storage']['capacity_bytes'])) {
            $capacityBytes = (int)$entitlements['resource_storage']['capacity_bytes'];
        }
        $spaceSizeMb = $this->bytesToMb($capacityBytes);
        if ($spaceSizeMb <= 0 && $gradeLevel <= 0) {
            $spaceSizeMb = self::DEFAULT_FREE_SPACE_MB;
        }
        $expireAt = $this->parseExpireAt($entitlements['membership_expire_at'] ?? null, $gradeLevel);
        $plan = is_array($entitlements['plan'] ?? null) ? $entitlements['plan'] : [];
        $benefits = is_array($plan['benefits_json'] ?? null) ? $plan['benefits_json'] : (is_array($plan['benefits'] ?? null) ? $plan['benefits'] : []);
        $uploadSizeMb = $this->resolveUploadSizeMb($benefits, $gradeLevel);
        $gradeName = $this->resolveGradeName($gradeLevel, $level, $plan);

        Db::startTrans();
        try {
            $this->ensureVipgradeRow($gradeLevel, $gradeName, $spaceSizeMb, $uploadSizeMb);
            $this->ensureUserVipGradeInfo($user, $gradeLevel, $expireAt, $spaceSizeMb, $level);
            $updates = [
                'vip_grade' => $gradeLevel,
                'space_size' => $spaceSizeMb,
            ];
            WdXcxUser::where('id', (int)$user->id)->update($this->filterExistingColumns('wd_xcx_user', $updates));
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            'grade_level' => $gradeLevel,
            'grade_name' => $gradeName,
            'end_time' => $expireAt > 0 ? date('Y-m-d', $expireAt) : 0,
            'space_size' => $spaceSizeMb,
            'upload_size_type' => 1,
            'upload_size' => $uploadSizeMb,
            'membership_level' => $level,
            'membership_plan_id' => (int)($entitlements['membership_plan_id'] ?? 0),
            'resource_storage' => $entitlements['resource_storage'] ?? [],
            'resource_storage_capacity_bytes' => (int)($entitlements['resource_storage_capacity_bytes'] ?? ($spaceSizeMb * 1024 * 1024)),
            'resource_storage_used_bytes' => (int)($entitlements['resource_storage_used_bytes'] ?? 0),
            'resource_storage_remaining_bytes' => (int)($entitlements['resource_storage_remaining_bytes'] ?? 0),
            'used_traffic_bytes' => (int)($entitlements['used_traffic_bytes'] ?? 0),
            'used_traffic_gb' => (float)($entitlements['used_traffic_gb'] ?? 0),
            'traffic_used_gb' => (float)($entitlements['traffic_used_gb'] ?? ($entitlements['used_traffic_gb'] ?? 0)),
            'traffic_limit_bytes' => (int)($entitlements['traffic_limit_bytes'] ?? ($entitlements['monthly_traffic_limit_bytes'] ?? 0)),
            'traffic_limit_gb' => (float)($entitlements['traffic_limit_gb'] ?? ($entitlements['monthly_traffic_limit_gb'] ?? 0)),
            'monthly_traffic_limit_bytes' => (int)($entitlements['monthly_traffic_limit_bytes'] ?? ($entitlements['traffic_limit_bytes'] ?? 0)),
            'monthly_traffic_limit_gb' => (float)($entitlements['monthly_traffic_limit_gb'] ?? ($entitlements['traffic_limit_gb'] ?? 0)),
            'traffic_remaining_bytes' => (int)($entitlements['traffic_remaining_bytes'] ?? ($entitlements['monthly_traffic_remaining_bytes'] ?? 0)),
            'traffic_remaining_gb' => (float)($entitlements['traffic_remaining_gb'] ?? ($entitlements['monthly_traffic_remaining_gb'] ?? 0)),
            'monthly_traffic_remaining_bytes' => (int)($entitlements['monthly_traffic_remaining_bytes'] ?? ($entitlements['traffic_remaining_bytes'] ?? 0)),
            'monthly_traffic_remaining_gb' => (float)($entitlements['monthly_traffic_remaining_gb'] ?? ($entitlements['traffic_remaining_gb'] ?? 0)),
            'monthly_traffic_exceeded' => (bool)($entitlements['monthly_traffic_exceeded'] ?? ($entitlements['traffic_exceeded'] ?? false)),
            'concurrency_limit' => (int)($benefits['concurrency_limit'] ?? ($entitlements['concurrency_limit'] ?? 0)),
            'points_balance' => (int)($entitlements['points_balance'] ?? 0),
        ];
    }

    private function ensureVipgradeRow($gradeLevel, $gradeName, $spaceSizeMb, $uploadSizeMb)
    {
        if ($gradeLevel <= 0) {
            return;
        }
        $cloudSizeGb = max(1, (int)ceil($spaceSizeMb / 1024));
        $data = $this->filterExistingColumns('wd_xcx_vipgrade', [
            'uniacid' => $this->uniacid,
            'grade_level' => $gradeLevel,
            'grade_name' => $gradeName,
            'cloud_size' => $cloudSizeGb,
            'upload_size_type' => 1,
            'upload_size' => $uploadSizeMb,
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $row = WdXcxVipgrade::where('grade_level', $gradeLevel)->where('uniacid', $this->uniacid)->find();
        if ($row) {
            unset($data['create_time']);
            $row->save($data);
            return;
        }
        WdXcxVipgrade::create($data);
    }

    private function ensureUserVipGradeInfo($user, $gradeLevel, $expireAt, $spaceSizeMb, $level)
    {
        $info = WdXcxUserVipGradeInfo::where('user_id', (int)$user->id)->find();
        $logItem = [
            'change_time' => date('Y-m-d H:i:s'),
            'change_info' => '同步AI生图权益：' . $level . '，空间' . $spaceSizeMb . 'M',
        ];
        if (!$info) {
            WdXcxUserVipGradeInfo::create($this->filterExistingColumns('wd_xcx_user_vip_grade_info', [
                'uniacid' => $this->uniacid,
                'user_id' => (int)$user->id,
                'grade_level' => $gradeLevel,
                'end_time' => $expireAt,
                'change_log' => [$logItem],
                'create_time' => time(),
                'update_time' => time(),
            ]));
            return;
        }
        $changeLog = is_array($info->change_log) ? $info->change_log : [];
        $lastLog = end($changeLog);
        if (!is_array($lastLog) || ($lastLog['change_info'] ?? '') !== $logItem['change_info']) {
            $changeLog[] = $logItem;
            if (count($changeLog) > 20) {
                $changeLog = array_slice($changeLog, -20);
            }
        }
        $info->save($this->filterExistingColumns('wd_xcx_user_vip_grade_info', [
            'grade_level' => $gradeLevel,
            'end_time' => $expireAt,
            'change_log' => $changeLog,
            'update_time' => time(),
        ]));
    }

    private function levelToGrade($level)
    {
        $level = strtolower(trim((string)$level));
        $map = [
            'trial' => 1,
            'basic' => 2,
            'standard' => 3,
            'premium' => 4,
        ];
        if (isset($map[$level])) {
            return $map[$level];
        }
        if ($level === '' || $level === 'free' || $level === 'user') {
            return 0;
        }
        if (preg_match('/(\d+)/', $level, $matches)) {
            return (int)$matches[1];
        }
        return 99;
    }

    private function resolveGradeName($gradeLevel, $level, $plan)
    {
        if ($gradeLevel <= 0) {
            return '普通用户';
        }
        $name = trim((string)($plan['name'] ?? ''));
        if ($name !== '') {
            return preg_replace('/(月卡|季卡|年卡|7天体验版)$/u', '', $name) ?: $name;
        }
        $map = [
            'trial' => '体验版',
            'basic' => '入门版',
            'standard' => '标准版',
            'premium' => '专业版',
        ];
        return $map[$level] ?? ('会员' . $gradeLevel);
    }

    private function resolveUploadSizeMb($benefits, $gradeLevel)
    {
        $value = (int)($benefits['upload_size_mb'] ?? 0);
        if ($value > 0) {
            return $value;
        }
        if ($gradeLevel >= 4) {
            return 200;
        }
        if ($gradeLevel >= 3) {
            return 100;
        }
        if ($gradeLevel >= 2) {
            return 50;
        }
        return 20;
    }

    private function parseExpireAt($value, $gradeLevel)
    {
        if ($gradeLevel <= 0 || empty($value)) {
            return 0;
        }
        if (is_numeric($value)) {
            $int = (int)$value;
            return $int > 2000000000 ? (int)floor($int / 1000) : $int;
        }
        $timestamp = strtotime((string)$value);
        return $timestamp ?: 0;
    }

    private function bytesToMb($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return 0;
        }
        return max(1, (int)ceil($bytes / 1024 / 1024));
    }

    private function filterExistingColumns($table, $data)
    {
        $columns = [];
        try {
            $rows = Db::query("SHOW COLUMNS FROM `" . $table . "`");
            foreach ($rows as $row) {
                $field = $row['Field'] ?? ($row['field'] ?? '');
                if ($field !== '') {
                    $columns[$field] = true;
                }
            }
        } catch (\Throwable $e) {
            return $data;
        }
        if (empty($columns)) {
            return $data;
        }
        return array_intersect_key($data, $columns);
    }
}
