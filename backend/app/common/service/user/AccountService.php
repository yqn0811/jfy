<?php

namespace app\common\service\user;

use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\user\WdXcxAccountUser;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\common\service\pay\PayService;
use app\common\service\WxService;
use Exception;
use think\App;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class AccountService extends BaseService
{
    const SESSION_KEY_NAME = 'account_session_user_'; //session 中保存的用户信息

    public function __construct(App $app)
    {
        parent::__construct($app);
    }



    public function getUserAuthInfo($param)
    {
        if(!empty($param['code'])){
            $this->getAccountUserInfo($param['code']);
        }
        if(!session(self::SESSION_KEY_NAME.$this->uniacid)){
            $link = input('link');
            $result = $this->getAuthLink($link);
            return ['link'=> $result];
        }else{
            $user_info = session(self::SESSION_KEY_NAME.$this->uniacid);
            $wx_user = WdXcxUser::where('unionid', $user_info['unionid'])->find();
            if(!$wx_user){
                throwError('用户不存在');
            }
            $user_info['is_new_user'] = $wx_user->HasOrder > 0 ? 0 : 1;
        }
        return ['user_info' => $user_info];
    }

    private function getApp($type)
    {
        return (new WxService($type))->getAppData();
    }

    private function getAuthLink($link)
    {
        $response = $this->getApp(3)->oauth->scopes(['snsapi_userinfo'])->redirect($link);
        return $response->getTargetUrl();
    }


    public function getAccountUserInfo($code)
    {
        try {
            $user = $this->getApp(3)->oauth->user();
            $user_info = $user->getOriginal();
            $user_info = $this->saveUserInfo($user_info);
            session(self::SESSION_KEY_NAME.$this->uniacid, $user_info);
            return $user_info;
        }catch (\Exception $exception){
            Log::info('授权错误：'.$exception->getMessage());
            return session(self::SESSION_KEY_NAME.$this->uniacid);
        }
    }

    private function saveUserInfo($user_info)
    {
        $user = WdXcxAccountUser::where('openid',$user_info['openid'])->find();
        if(!$user){
            $user = new WdXcxAccountUser();
            $user->openid = $user_info['openid'];
            $user->uniacid = 1;
            if(isset($user_info['unionid'])){
                $user->unionid = $user_info['unionid'];
            }
            $user->save();
        }

        return $user;
    }

    public function initAccount($param)
    {
        $jssdk = $this->getApp(3)->jssdk;
        $jssdk->setUrl($param['link']);
        $config = $jssdk->buildConfig(['hideMenuItems', 'chooseWXPay']);
        return json_decode($config);
    }


    public function createUserByVipgradeOrder($param)
    {
        $account_user_info = session(self::SESSION_KEY_NAME.$this->uniacid);
        if(!$account_user_info){
            throwError('用户未授权');
        }
        $this->checkSign($param);
        $user = WdXcxUser::where('unionid', $account_user_info['unionid'])->find();
        if(!$user){
            throwError('用户不存在');
        }
        $grade_info = WdXcxVipgrade::where([
            'uniacid' => $this->uniacid,
            'grade_level' => $param['grade']
        ])->find();
        if(!$grade_info){
            throwError('指定会员等级不存在');
        }
        $this->checkByGrade($user, $grade_info, $param);
        return $this->createUserByVipgradeOrderHandler($user, $grade_info, $param, $account_user_info);
    }

    private function createUserByVipgradeOrderHandler($user, $grade_info, $param, $account_user_info)
    {
        $order_id = generateOrderId('V');
        Db::startTrans();
        try {
            $pay_info = (new WxService(4))->getPayInfo([
                'order_id' => $order_id,
                'pay_price' => $param['pay_price']*100,
                'body' => '购买会员',
                'openid' => $account_user_info['openid'],
            ]);
            $buy_grade_order = new WdXcxUserBuyGradeOrderLists();
            $buy_grade_order->save([
                'uniacid' => $this->uniacid,
                'user_id' => $user->id,
                'order_id' => $order_id,
                'grade_level' => $grade_info->grade_level,
                'pay_price' => $param['pay_price'],
                'buy_day_limit' => $param['buy_time'],
                'grade_info' => json_encode($grade_info->toArray()),
                'status' => 1,
                'pay_info' => [
                    'timeStamp' => $pay_info['timeStamp'],
                    'nonceStr' => $pay_info['nonceStr'],
                    'package' => $pay_info['package'],
                    'signType' => $pay_info['signType'],
                    'paySign' => $pay_info['paySign'],
                    'appid' => Config::get('miniprogram.account_appid')
                ],
                'xcx_pay' => 2
            ]);
        }catch (\Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        return [
            'pay_info' => $buy_grade_order['pay_info'],
            'order_id' => $order_id
        ];
    }

    private function checkByGrade($user, $grade_info, $param)
    {
        if($user->vip_grade >= $grade_info->grade_level){
            throwError('用户会员等级不能高于购买等级');
        }
        if($param['buy_time'] == 1){
            $need_pay = $grade_info->annual_fee;
            //检查是否有为新客
            $pay_order = WdXcxUserBuyGradeOrderLists::where([
                'user_id' => $user->id,
                'status' => 2
            ])->find();
            if(!$pay_order){
                $need_pay = bcsub($grade_info->annual_fee, $grade_info->new_buy_annual, 2);
            }
        }
        if($param['buy_time'] == 2){
            $need_pay = $grade_info->midd_month_fee;
        }
        if($param['buy_time'] == 3){
            $need_pay = $grade_info->month_fee;
        }
        if(bccomp($need_pay, $param['pay_price'], 2) != 0){
            throwError('购买价格有误');
        }
    }


    private function checkSign($params)
    {
        if(empty($params['timestamp']) || empty($params['sign'])){
            throwError('验签参数不完整1');
        }
        $sign = $params['sign'];
        unset($params['sign']);
        $check_sign = $this->getSign($params);
        if($check_sign != $sign){
            throwError('验签失败');
        }
    }

    /**获取签名
     * @param $request
     * @param $params
     * @return string
     */
    private function getSign($params)
    {
        $user_info = session(self::SESSION_KEY_NAME.$this->uniacid);
        $user_openid = $user_info['openid'];
        foreach ($params as $k => $v){
            $params_string[$k] = $v;
        }
        ksort($params_string);
        $string = $this->formatBizQueryParaMap($params_string);
        $sign = strtoupper(md5(md5($user_openid).$string.md5($user_openid)));
        return $sign;
    }

    /**组装参数
     * @param $paraMap
     * @return false|string
     */
    protected function formatBizQueryParaMap($paraMap)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }













}