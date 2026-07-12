<?php

namespace app\common\service\bridge;

use app\common\service\BaseService;
use app\common\service\user\VipgradeService;
use cores\utils\Utils;
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
        $packageType = strtolower(trim((string)($param['package_type'] ?? '')));
        return $this->createNativeOrder($user, [
            'scene' => 'membership_open',
            'membership_plan_id' => $planId,
            'plan_id' => $planId,
            'package_type' => $packageType,
            'coupon_code' => $this->normalizeCouponCode($param['coupon_code'] ?? ''),
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
            'coupon_code' => $this->normalizeCouponCode($param['coupon_code'] ?? ''),
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
        $resp = $this->bridgeClient->get('/jiafangyun/bridge/payment/orders?' . http_build_query($query));
        return $this->normalizeOrderListResponse($resp);
    }

    private function createNativeOrder($user, $payload, $prefix)
    {
        $planId = (int)($payload['membership_plan_id'] ?? ($payload['points_plan_id'] ?? 0));
        $payload = array_merge($this->bridgeClient->userPayload($user), $payload, [
            'payment_method' => 'native',
            'client_request_id' => $prefix . '-' . (int)$user->id . '-' . $planId . '-' . time(),
        ]);
        if (empty($payload['coupon_code'])) {
            unset($payload['coupon_code']);
        }
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
        $amountCents = $this->normalizeAmountCents($order);
        return [
            'order_no' => $orderNo,
            'order_id' => $orderNo,
            'status' => $this->normalizeOrderStatus($order),
            'order_type' => $order['order_type'] ?? '',
            'amount_cents' => $amountCents,
            'amount' => $this->centsToMoney($amountCents),
            'original_amount' => (int)($order['original_amount'] ?? $amountCents),
            'discount_cents' => (int)($order['discount_cents'] ?? 0),
            'payment_method' => $order['payment_method'] ?? 'wechatpay',
            'payment_url' => $codeUrl,
            'code_url' => $codeUrl,
            'qr_code_data' => $order['qr_code_data'] ?? '',
            'qr_image' => $this->makeQrImage($codeUrl, $order['qr_code_data'] ?? ''),
            'expires_at' => $order['expires_at'] ?? null,
            'raw' => $raw,
        ];
    }

    private function makeQrImage($codeUrl, $qrCodeData)
    {
        if (is_string($qrCodeData) && strpos($qrCodeData, 'data:image/') === 0) {
            return $qrCodeData;
        }
        if (!$codeUrl) {
            return '';
        }
        try {
            $dir = public_path() . 'uploads/qrcode';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            return Utils::createQrcode($codeUrl, '', true);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalizeOrderListResponse($resp)
    {
        if (!is_array($resp)) {
            return $resp;
        }
        foreach (['orders', 'list', 'data', 'lists'] as $key) {
            if (isset($resp[$key]) && is_array($resp[$key])) {
                $resp[$key] = $this->normalizeOrderRows($resp[$key]);
            }
        }
        return $resp;
    }

    private function normalizeOrderRows($rows)
    {
        $list = [];
        foreach ((array)$rows as $row) {
            $list[] = is_array($row) ? $this->normalizeOrderRow($row) : $row;
        }
        return $list;
    }

    private function normalizeOrderRow($order)
    {
        $orderNo = $order['order_no'] ?? ($order['order_id'] ?? ($order['id'] ?? ''));
        $amountCents = $this->normalizeAmountCents($order);
        $order['order_no'] = $orderNo;
        $order['order_id'] = $orderNo;
        $order['amount_cents'] = $amountCents;
        $order['amount'] = $this->centsToMoney($amountCents);
        $order['status'] = $this->normalizeOrderStatus($order);
        if (empty($order['expires_at'])) {
            $createdAt = $this->parseOrderTime($order['created_at'] ?? ($order['create_time'] ?? ''));
            if ($createdAt > 0) {
                $order['expires_at'] = date('Y-m-d H:i:s', $createdAt + 15 * 60);
            }
        }
        return $order;
    }

    private function normalizeAmountCents($order)
    {
        foreach (['amount_cents', 'pay_amount_cents', 'price_cents', 'total_fee', 'pay_fee', 'cash_fee'] as $field) {
            if (isset($order[$field]) && $order[$field] !== '') {
                return (int)round((float)$order[$field]);
            }
        }
        foreach (['amount', 'pay_price', 'price', 'total_amount', 'paid_amount', 'pay_amount'] as $field) {
            if (isset($order[$field]) && $order[$field] !== '') {
                $text = str_replace([',', '¥', '￥', ' '], '', (string)$order[$field]);
                if (is_numeric($text)) {
                    return (int)round(((float)$text) * 100);
                }
            }
        }
        return 0;
    }

    private function normalizeCouponCode($value)
    {
        return strtoupper(trim((string)$value));
    }

    private function normalizeOrderStatus($order)
    {
        $status = strtolower(trim((string)($order['status'] ?? ($order['pay_status'] ?? ($order['trade_state'] ?? '')))));
        if (in_array($status, ['paid', 'success', 'completed', 'finished', '1'], true)) {
            return 'paid';
        }
        if (in_array($status, ['closed', 'expired', 'cancelled', 'canceled', 'timeout'], true)) {
            return 'expired';
        }
        if (in_array($status, ['failed', 'fail', 'payment_failed', '-1'], true)) {
            return 'failed';
        }
        if ($this->isOrderPastFifteenMinutes($order)) {
            return 'expired';
        }
        return 'created';
    }

    private function isOrderPastFifteenMinutes($order)
    {
        $expireAt = $this->parseOrderTime($order['expires_at'] ?? ($order['expire_time'] ?? ($order['expired_at'] ?? '')));
        if ($expireAt > 0 && $expireAt <= time()) {
            return true;
        }
        $createdAt = $this->parseOrderTime($order['created_at'] ?? ($order['create_time'] ?? ''));
        return $createdAt > 0 && $createdAt + 15 * 60 <= time();
    }

    private function parseOrderTime($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $timestamp = (int)$value;
            return $timestamp > 1000000000000 ? (int)floor($timestamp / 1000) : $timestamp;
        }
        $timestamp = strtotime((string)$value);
        return $timestamp ?: 0;
    }

    private function centsToMoney($cents)
    {
        return number_format(((int)$cents) / 100, 2, '.', '');
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
