<?php

namespace app\common\service\user;

use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\common\service\bridge\JiafangyunBridgeClient;
use think\App;
use think\facade\Db;

class VipgradeService extends BaseService
{
    private $vipgrade_model;
    private $bridge_client;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->vipgrade_model = new WdXcxVipgrade();
        $this->bridge_client = new JiafangyunBridgeClient($app);
    }

    /**会员等级列表
     * @return WdXcxVipgrade[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVipgradeLists()
    {
        $resp = $this->bridge_client->get('/jiafangyun/bridge/subscription/plans');
        $plans = $resp['plans'] ?? ($resp['list'] ?? []);
        return $this->formatBridgeVipgradeLists($plans);
    }

    /**创建购买会员订单
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserByVipgradeOrder($param, $user_id)
    {
        $user = $this->bridge_client->getUser($user_id);
        $lists = $this->getVipgradeLists();
        $grade_info = $this->findGradeInfo($lists, $param['grade']);
        if (!$grade_info) {
            throwError('指定会员等级不存在');
        }
        $plan_id = $this->resolveMembershipPlanId($grade_info, (int)$param['buy_time']);
        if (!$plan_id) {
            throwError('当前会员套餐暂不可购买');
        }
        return $this->createBridgeVipgradeOrder($user, $grade_info, $plan_id, $param);
    }

    /**购买会员等级订单数据
     * @param $user
     * @param $grade_info
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function createBridgeVipgradeOrder($user, $grade_info, $plan_id, $param)
    {
        $payload = array_merge($this->bridge_client->userPayload($user), [
            'scene' => 'membership_open',
            'membership_plan_id' => (int)$plan_id,
            'payment_method' => 'wechat_jsapi',
            'client_request_id' => 'b-vip-' . (int)$user->id . '-' . (int)$plan_id . '-' . time(),
        ]);
        $resp = $this->bridge_client->post('/jiafangyun/bridge/payment/create-order', $payload);
        $order = $resp['order'] ?? [];
        $order_id = $order['order_no'] ?? ($order['order_id'] ?? '');
        $pay_info = $this->normalizePayInfo($order['pay_info'] ?? ($order['request_payment'] ?? []));
        if (!$order_id || empty($pay_info)) {
            throwError('支付参数创建失败');
        }

        Db::startTrans();
        try {
            $buy_grade_order = new WdXcxUserBuyGradeOrderLists();
            $buy_grade_order->save($this->filterExistingColumns('wd_xcx_user_buy_grade_order_lists', [
                'uniacid' => $this->uniacid,
                'user_id' => $user->id,
                'order_id' => $order_id,
                'grade_level' => $grade_info['grade_level'],
                'pay_price' => $this->centsToMoney($order['amount_cents'] ?? 0),
                'buy_day_limit' => $param['buy_time'],
                'grade_info' => json_encode(array_merge($grade_info, [
                    'go_membership_plan_id' => (int)$plan_id,
                    'go_order_no' => $order_id,
                ]), JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'pay_info' => $pay_info,
            ]));
        } catch (\Throwable $exception) {
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        return [
            'pay_info' => $pay_info,
            'order_id' => $order_id,
            'go_order_no' => $order_id,
            'amount_cents' => $order['amount_cents'] ?? 0,
        ];
    }

    private function formatBridgeVipgradeLists($plans)
    {
        $resourcePlans = $this->formatResourceStoragePlans($plans);
        if (!empty($resourcePlans)) {
            return $resourcePlans;
        }

        $groups = [];
        foreach ((array)$plans as $plan) {
            if (!$this->isMiniappMembershipPlan($plan)) {
                continue;
            }
            $level = $plan['level'] ?? '';
            if ($level === '') {
                continue;
            }
            if (!isset($groups[$level])) {
                $groups[$level] = $this->emptyGradeInfo($level, $plan);
            }
            $slot = $this->planSlot($plan);
            if (!$slot) {
                continue;
            }
            $current = $this->centsToMoney($plan['current_price_cents'] ?? 0);
            $original = $this->centsToMoney($plan['original_price_cents'] ?? 0);
            if ($slot === 1) {
                $groups[$level]['annual_fee'] = $current;
                $groups[$level]['market_annual_fee'] = $original;
                $groups[$level]['annual_plan_id'] = (int)$plan['id'];
                $groups[$level]['show_annual_del_str'] = $plan['plan_subtitle'] ?? '';
            } elseif ($slot === 2) {
                $groups[$level]['midd_month_fee'] = $current;
                $groups[$level]['market_midd_month_fee'] = $original;
                $groups[$level]['midd_month_plan_id'] = (int)$plan['id'];
                $groups[$level]['show_month_del_str'] = $plan['plan_subtitle'] ?? '';
            } elseif ($slot === 3) {
                $groups[$level]['month_fee'] = $current;
                $groups[$level]['market_month_fee'] = $original;
                $groups[$level]['month_plan_id'] = (int)$plan['id'];
                if (empty($groups[$level]['show_month_del_str'])) {
                    $groups[$level]['show_month_del_str'] = $plan['plan_subtitle'] ?? '';
                }
            }
        }

        $result = array_values($groups);
        usort($result, function ($a, $b) {
            return ($a['grade_level'] <=> $b['grade_level']);
        });
        return $result;
    }

    private function formatResourceStoragePlans($plans)
    {
        $result = [];
        foreach ((array)$plans as $plan) {
            if (($plan['plan_category'] ?? '') !== 'resource_storage') {
                continue;
            }
            $benefits = $plan['benefits_json'] ?? [];
            $storage_mb = (int)($benefits['storage_quota_mb'] ?? 0);
            if ($storage_mb <= 0) {
                $storage_mb = $this->storageSizeFromLevel($plan['level'] ?? '');
            }
            $grade_level = $this->levelToGrade($plan['level'] ?? '');
            $upload_size = (int)($benefits['upload_size_mb'] ?? 20);
            if ($upload_size <= 0) {
                $upload_size = 20;
            }
            $result[] = [
                'grade_level' => $grade_level,
                'grade_key' => $plan['level'] ?? '',
                'grade_name' => $plan['name'] ?? '资源包',
                'annual_fee' => $this->centsToMoney($plan['current_price_cents'] ?? 0),
                'market_annual_fee' => $this->centsToMoney($plan['original_price_cents'] ?? ($plan['current_price_cents'] ?? 0)),
                'midd_month_fee' => '0.00',
                'market_midd_month_fee' => '0.00',
                'month_fee' => '0.00',
                'market_month_fee' => '0.00',
                'new_buy_annual' => '0.00',
                'cloud_size' => $storage_mb,
                'cloud_size_str' => $this->formatStorageSize($storage_mb),
                'editor_number' => (int)($benefits['concurrency_limit'] ?? 1),
                'upload_size_type' => 1,
                'upload_size' => $upload_size,
                'upload_size_value' => $upload_size,
                'show_annual_del_str' => $plan['plan_subtitle'] ?? '资源扩容',
                'show_month_del_str' => '',
                'annual_plan_id' => (int)($plan['id'] ?? 0),
                'midd_month_plan_id' => 0,
                'month_plan_id' => 0,
                'benefits_json' => $benefits,
            ];
        }
        usort($result, function ($a, $b) {
            return ($a['cloud_size'] <=> $b['cloud_size']);
        });
        return $result;
    }

    private function emptyGradeInfo($level, $plan)
    {
        $benefits = $plan['benefits_json'] ?? [];
        $grade_level = $this->levelToGrade($level);
        $storage_mb = (int)($benefits['storage_quota_mb'] ?? 0);
        $upload_size = (int)($benefits['upload_size_mb'] ?? 20);
        if ($upload_size <= 0) {
            $upload_size = 20;
        }
        return [
            'grade_level' => $grade_level,
            'grade_key' => $level,
            'grade_name' => $this->stripPeriodSuffix($plan['name'] ?? $level),
            'annual_fee' => '0.00',
            'market_annual_fee' => '0.00',
            'midd_month_fee' => '0.00',
            'market_midd_month_fee' => '0.00',
            'month_fee' => '0.00',
            'market_month_fee' => '0.00',
            'new_buy_annual' => '0.00',
            'cloud_size' => $storage_mb,
            'cloud_size_str' => $this->formatStorageSize($storage_mb),
            'editor_number' => (int)($benefits['concurrency_limit'] ?? 1),
            'upload_size_type' => 1,
            'upload_size' => $upload_size,
            'upload_size_value' => $upload_size,
            'show_annual_del_str' => '',
            'show_month_del_str' => '',
            'annual_plan_id' => 0,
            'midd_month_plan_id' => 0,
            'month_plan_id' => 0,
            'benefits_json' => $benefits,
        ];
    }

    private function isMiniappMembershipPlan($plan)
    {
        $level = $plan['level'] ?? '';
        $category = $plan['plan_category'] ?? '';
        if (in_array($level, ['free', 'trial'], true) || $category === 'resource_storage') {
            return false;
        }
        return in_array($category, ['auto_monthly', 'auto_quarterly', 'auto_yearly'], true);
    }

    private function planSlot($plan)
    {
        $category = $plan['plan_category'] ?? '';
        $days = (int)($plan['billing_cycle_days'] ?? 0);
        if ($category === 'auto_yearly' || $days >= 360) {
            return 1;
        }
        if ($category === 'auto_quarterly' || ($days >= 80 && $days <= 100)) {
            return 2;
        }
        if ($category === 'auto_monthly' || ($days >= 28 && $days <= 31)) {
            return 3;
        }
        return 0;
    }

    private function findGradeInfo($lists, $grade)
    {
        foreach ((array)$lists as $item) {
            if ((int)$item['grade_level'] === (int)$grade) {
                return $item;
            }
        }
        return null;
    }

    private function resolveMembershipPlanId($grade_info, $buy_time)
    {
        $map = [
            1 => 'annual_plan_id',
            2 => 'midd_month_plan_id',
            3 => 'month_plan_id',
        ];
        $key = $map[$buy_time] ?? '';
        return $key ? (int)($grade_info[$key] ?? 0) : 0;
    }

    private function normalizePayInfo($pay_info)
    {
        if (!is_array($pay_info) || empty($pay_info['timeStamp']) || empty($pay_info['nonceStr']) || empty($pay_info['package']) || empty($pay_info['paySign'])) {
            return [];
        }
        return [
            'timeStamp' => (string)$pay_info['timeStamp'],
            'nonceStr' => (string)$pay_info['nonceStr'],
            'package' => (string)$pay_info['package'],
            'signType' => (string)($pay_info['signType'] ?? 'MD5'),
            'paySign' => (string)$pay_info['paySign'],
        ];
    }

    private function levelToGrade($level)
    {
        $map = [
            'trial' => 1,
            'basic' => 2,
            'standard' => 3,
            'premium' => 4,
        ];
        if (isset($map[$level])) {
            return $map[$level];
        }
        if (preg_match('/(\d+)/', (string)$level, $matches)) {
            return (int)$matches[1];
        }
        return 99;
    }

    private function storageSizeFromLevel($level)
    {
        if (preg_match('/(\d+)\s*g/i', (string)$level, $matches)) {
            return (int)$matches[1] * 1024;
        }
        if (preg_match('/(\d+)\s*m/i', (string)$level, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    private function stripPeriodSuffix($name)
    {
        return preg_replace('/(月卡|季卡|年卡|7天体验版)$/u', '', (string)$name) ?: (string)$name;
    }

    private function formatStorageSize($mb)
    {
        $mb = (int)$mb;
        if ($mb >= 1024) {
            $gb = $mb / 1024;
            return rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') . 'GB';
        }
        return $mb . 'MB';
    }

    private function centsToMoney($cents)
    {
        return number_format(((int)$cents) / 100, 2, '.', '');
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
