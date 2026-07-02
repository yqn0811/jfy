<?php

namespace app\common\service\bridge;

use app\common\service\BaseService;
use app\common\service\user\VipgradeService;
use think\App;

class JiafangyunWebPaymentService extends BaseService
{
    private $bridgeClient;
    private $entitlementSync;
    private $appContext;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->appContext = $app;
        $this->bridgeClient = new JiafangyunBridgeClient($app);
        $this->entitlementSync = new JiafangyunEntitlementSyncService($app);
    }

    public function getSubscriptionPlans()
    {
        return $this->bridgeClient->get('/jiafangyun/bridge/subscription/plans');
    }

    public function getPointsRechargePlans()
    {
        return $this->bridgeClient->get('/jiafangyun/bridge/points/recharge-plans');
    }

    public function createMembershipOrder($uid, $param)
    {
        $user = $this->bridgeClient->getUser($uid);
        $planId = (int)($param['membership_plan_id'] ?? ($param['plan_id'] ?? 0));
        if ($planId <= 0 && isset($param['grade'])) {
            $planId = $this->resolveMembershipPlanIdByLegacyGrade($param);
        }
        if ($planId <= 0) {
            throwError('请选择会员套餐');
        }
        return $this->createNativeOrder($user, [
            'scene' => 'membership_open',
            'membership_plan_id' => $planId,
        ], 'b-web-vip');
    }

    public function createRechargeOrder($uid, $param)
    {
        $user = $this->bridgeClient->getUser($uid);
        $planId = (int)($param['points_plan_id'] ?? ($param['plan_id'] ?? ($param['rid'] ?? 0)));
        if ($planId <= 0) {
            throwError('请选择充值套餐');
        }
        return $this->createNativeOrder($user, [
            'scene' => 'points_recharge',
            'points_plan_id' => $planId,
        ], 'b-web-recharge');
    }

    public function getOrderStatus($uid, $orderNo)
    {
        if (!$orderNo) {
            throwError('参数不完整');
        }
        $user = $this->bridgeClient->getUser($uid);
        $query = http_build_query(array_merge($this->bridgeClient->userPayload($user), [
            'order_no' => $orderNo,
        ]));
        $resp = $this->bridgeClient->get('/jiafangyun/bridge/payment/status?' . $query);
        if (($resp['status'] ?? '') === 'paid') {
            $this->entitlementSync->syncUserQuietly($uid);
        }
        return $resp;
    }

    public function getOrderList($uid, $param)
    {
        $user = $this->bridgeClient->getUser($uid);
        $query = array_merge($this->bridgeClient->userPayload($user), [
            'page' => max(1, (int)($param['page'] ?? 1)),
            'page_size' => max(1, min(100, (int)($param['page_size'] ?? ($param['pageSize'] ?? 20)))),
        ]);
        if (!empty($param['status'])) {
            $query['status'] = $param['status'];
        }
        if (!empty($param['order_type'])) {
            $query['order_type'] = $param['order_type'];
        }
        if (!empty($param['type'])) {
            $query['type'] = $param['type'];
        }
        if (!empty($param['order_no'])) {
            $query['order_no'] = $param['order_no'];
        }
        return $this->bridgeClient->get('/jiafangyun/bridge/payment/orders?' . http_build_query($query));
    }

    private function createNativeOrder($user, $payload, $prefix)
    {
        $planId = (int)($payload['membership_plan_id'] ?? ($payload['points_plan_id'] ?? 0));
        $payload = array_merge($this->bridgeClient->userPayload($user), $payload, [
            'payment_method' => 'native',
            'client_request_id' => $prefix . '-' . (int)$user->id . '-' . $planId . '-' . time(),
        ]);
        $resp = $this->bridgeClient->post('/jiafangyun/bridge/payment/create-order', $payload);
        $order = $resp['order'] ?? [];
        if (empty($order['order_no']) && empty($order['order_id'])) {
            throwError('订单创建失败');
        }
        return $this->normalizeNativeOrder($order, $resp);
    }

    private function normalizeNativeOrder($order, $raw)
    {
        $orderNo = $order['order_no'] ?? ($order['order_id'] ?? '');
        $codeUrl = $order['payment_url'] ?? ($order['code_url'] ?? '');
        return [
            'order_no' => $orderNo,
            'order_id' => $orderNo,
            'status' => $order['status'] ?? 'created',
            'order_type' => $order['order_type'] ?? '',
            'amount_cents' => (int)($order['amount_cents'] ?? 0),
            'amount' => number_format(((int)($order['amount_cents'] ?? 0)) / 100, 2, '.', ''),
            'payment_method' => $order['payment_method'] ?? 'wechatpay',
            'payment_url' => $codeUrl,
            'code_url' => $codeUrl,
            'qr_code_data' => $order['qr_code_data'] ?? '',
            'expires_at' => $order['expires_at'] ?? null,
            'raw' => $raw,
        ];
    }

    private function resolveMembershipPlanIdByLegacyGrade($param)
    {
        $grade = (int)($param['grade'] ?? 0);
        $buyTime = (int)($param['buy_time'] ?? 0);
        if ($grade <= 0 || !in_array($buyTime, [1, 2, 3], true)) {
            return 0;
        }
        $lists = (new VipgradeService($this->appContext))->getVipgradeLists();
        foreach ((array)$lists as $item) {
            if ((int)($item['grade_level'] ?? 0) !== $grade) {
                continue;
            }
            $map = [
                1 => 'annual_plan_id',
                2 => 'midd_month_plan_id',
                3 => 'month_plan_id',
            ];
            return (int)($item[$map[$buyTime]] ?? 0);
        }
        return 0;
    }
}
