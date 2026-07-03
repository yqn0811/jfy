<?php

namespace app\common\service\recharge;

use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\user\WdXcxRechargePackage;
use app\common\service\BaseService;
use app\common\service\bridge\JiafangyunBridgeClient;
use think\App;
use think\facade\Db;

class RechargeService extends BaseService
{
    private $recharge_model;
    private $bridge_client;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->recharge_model = new WdXcxRechargePackage();
        $this->bridge_client = new JiafangyunBridgeClient($app);
    }

    /**充值套餐列表
     * @return WdXcxRechargePackage[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRechargeList()
    {
        $resp = $this->bridge_client->get('/jiafangyun/bridge/points/recharge-plans');
        $plans = $resp['plans'] ?? ($resp['list'] ?? []);
        $lists = [];
        foreach ((array)$plans as $plan) {
            $lists[] = [
                'id' => (int)($plan['id'] ?? 0),
                'rid' => (int)($plan['id'] ?? 0),
                'name' => $plan['name'] ?? '',
                'money' => $this->centsToMoney($plan['current_price_cents'] ?? 0),
                'market_money' => $this->centsToMoney($plan['original_price_cents'] ?? 0),
                'getscore' => (int)($plan['total_points'] ?? (($plan['points_amount'] ?? 0) + ($plan['bonus_points'] ?? 0))),
                'points_amount' => (int)($plan['points_amount'] ?? 0),
                'bonus_points' => (int)($plan['bonus_points'] ?? 0),
                'validity_days' => (int)($plan['validity_days'] ?? 365),
                'promo_tagline' => $plan['promo_tagline'] ?? '',
                'badge_text' => $plan['badge_text'] ?? '',
                'features' => $plan['features'] ?? [],
                'coupon_show' => [],
                'recharge_send' => $this->formatRechargeSend($plan),
            ];
        }
        return $lists;
    }

    /**创建支付订单获取支付信息
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createRechargeOrder($param)
    {
        $user = $this->bridge_client->getUser(request()->userID());
        $rid = (int)($param['rid'] ?? 0);
        if (!$rid) {
            throwError('请选择充值套餐');
        }
        $payload = array_merge($this->bridge_client->userPayload($user), [
            'scene' => 'points_recharge',
            'points_plan_id' => $rid,
            'payment_method' => 'wechat_jsapi',
            'client_request_id' => 'b-recharge-' . (int)$user->id . '-' . $rid . '-' . time(),
        ]);
        $resp = $this->bridge_client->post('/jiafangyun/bridge/payment/create-order', $payload);
        $order = $resp['order'] ?? [];
        $order_id = $order['order_no'] ?? ($order['order_id'] ?? '');
        $pay_info = $this->normalizePayInfo($order['pay_info'] ?? ($order['request_payment'] ?? []));
        if (!$order_id || empty($pay_info)) {
            throwError('支付参数创建失败');
        }
        $recharge_order = [
            'uniacid' => $this->uniacid,
            'user_id' => request()->userID(),
            'order_id' => $order_id,
            'pay_price' => $this->centsToMoney($order['amount_cents'] ?? 0),
            'recharge_id' => $rid,
            'status' => 1,
            'invite_code' => empty($param['invite_code']) ? '' : $param['invite_code'],
            'pay_info' => $pay_info,
        ];
        //存入充值订单表
        WdXcxUserRechargeOrderLists::create($this->filterExistingColumns('wd_xcx_user_recharge_order_lists', $recharge_order));
        return [
            'order_id' => $order_id,
            'go_order_no' => $order_id,
            'amount_cents' => $order['amount_cents'] ?? 0,
            'pay_info' => $recharge_order['pay_info'],
        ];
    }

    /**查询订单状态
     * @param $order_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function checkPayStatus($order_id, $user_id)
    {
        if(!$order_id){
            throwError('参数不完整');
        }
        $user = $this->bridge_client->getUser($user_id);
        $query = http_build_query(array_merge($this->bridge_client->userPayload($user), [
            'order_no' => $order_id,
        ]));
        $resp = $this->bridge_client->get('/jiafangyun/bridge/payment/status?' . $query);
        $status = $resp['status'] ?? '';
        if ($status === 'paid') {
            WdXcxUserRechargeOrderLists::where('user_id', $user_id)
                ->where('order_id', $order_id)
                ->update(['status' => 2]);
            return;
        }
        if ($status === 'expired' || $status === 'payment_failed') {
            throwError('支付失败');
        }
        throwError('支付中');
    }

    private function formatRechargeSend($plan)
    {
        $items = [];
        $bonus = (int)($plan['bonus_points'] ?? 0);
        if ($bonus > 0) {
            $items[] = '赠送' . $bonus . '积分';
        }
        $validity_days = (int)($plan['validity_days'] ?? 0);
        if ($validity_days > 0) {
            $items[] = '有效期' . $validity_days . '天';
        }
        return implode('、', $items);
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
