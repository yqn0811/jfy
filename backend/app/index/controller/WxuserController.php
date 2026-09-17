<?php

namespace app\index\controller;

use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserCommissionRecord;
use app\common\model\user\WdXcxUserDiamondRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\model\user\WdXcxUserPlayRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\user\UserService;
use app\common\service\ylb\YlbApiService;
use cores\ExcelCommon;
use think\facade\Db;
use think\facade\View;

class WxuserController extends IndexBaseController
{

    /**会员管理
     * @return string
     */
    public function lists()
    {
        $this->checkMenuPath(19, 20);
        $this->checkUserRule(20);
        $id = $this->uniacid;
        $grade = WdXcxVipgrade::where('uniacid', $id)
            ->field('grade_level, grade_name')
            ->select()
            ->toArray();
        $vip = input('vip');
        $key = input('user_info');
        $user_id = input('user_id');

        $user = WdXcxUser::where('uniacid', $id)
            ->where(function ($query)use($vip, $key, $user_id){
                if($vip && $vip != 'all'){
                    $query->where('vip_grade', $vip);
                }
                if($key){
                    $query->where(function ($query)use($key){
                        $query->whereLike('nickname', '%'.$key.'%')->whereOr('nickname', 'like', '%'.$key.'%')->whereOr('mobile', 'like', '%'.$key.'%');
                    });
                }
                if($user_id){
                    $query->where('id', $user_id);
                }
            })->order('join_time desc, id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => request()->param()
            ]);

        return View::fetch('wxuser/lists', [
            'user' => $user,
            'grade_arr' => $grade,
            'vip' => $vip,
            'user_lable_id' => ''
        ]);
    }

    /**用户详情
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userInfo()
    {
        $this->checkUserRule(20);
        $uniacid = $this->uniacid;
        $aid = input('aid');
        $item = WdXcxUser::where('uniacid', $uniacid)
            ->where('id', $aid)
            ->find();
        $item->money = 0;
        if($item->leaguer_id){
            $item->lottery = $item->UserLottery;
            $item->gamecoin = $item->UserGamecoin;
            $item->money = $item->UserBalance;

        }
        $item->birth_day = $item->birth_day ? date('Y-m-d', strtotime($item->birth_day)) : '';
        $grade = WdXcxVipgrade::where('uniacid', $uniacid)
            ->order('grade_level asc')
            ->field('grade_level, grade_name')
            ->select();
        return View::fetch('wxuser/user_info', [
            'item' => $item,
            'all_grade' => $grade,
        ]);
    }

    /**后台修改用户会员到期时间
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveUseVipgrade()
    {
        $this->checkUserRule(20);
        $uniacid = $this->uniacid;
        $suid = input('suid');
        $grade = input('grade');
        $date_type = input('date_type');
        $end_time = input('end_time');
        $user = WdXcxUser::where('uniacid', $uniacid)
            ->where('id', $suid)
            ->find();
        if(!$user){
            $this->error('指定用户不存在');
        }
        $change_info = '后台修改，从'.$user->vip_grade.'升级到'.$grade.', 到期时间为';
        if($date_type == 2){
            if(!$end_time){
                $this->error('请选择到期时间');
            }
            $change_info .= $end_time;
            $end_time = strtotime($end_time.' 23:59:59');
            if($end_time < time()){
                $this->error('到期时间不能小于当前时间');
            }
        }else{
            $end_time = 0;
            $change_info .= '永久';
        }
        $user->changeUserVipGrade($user, $grade, $end_time, $change_info);
        $this->success('操作成功');
    }

    /**修改用户财产
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserProperty()
    {
        $this->checkUserRule(20);
        $uniacid = input('uniacid');
        $suid = input('suid');
        $user = WdXcxUser::where('uniacid', $uniacid)->where('id', $suid)->find();
        if(!$user){
            $this->error('用户不存在');
        }
        $types= input("types");
        Db::startTrans();
        try {
            if($types == 1){
                $czjf_change = input("czjf_change");
                if(input("scoreNum") != $user['score']){
                    $is_end = 0;
                    $type = WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD;
                    if($czjf_change == '0'){
                        $message = '平台操作增加';
                    }else if($czjf_change == '1'){
                        $type = WdXcxUserIntegralRecord::INTEGRAL_CHANGE_DEL;
                        $message = '平台操作减少';
                    }else{
                        $is_end = 1;
                        $message = '平台操作最终';
                    }
                    $result = (new WdXcxUserIntegralRecord())->addRecord($user, [
                        'order_id' => '',
                        'change_integral' => input("scoreNum"),
                        'message' => input('scoreTip') ? input('scoreTip') : $message,
                        'is_end' => $is_end,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND,
                    ], $type);
                }
            }
            if($types == 2){
                $czye_change = input("czye_change");
                if(input("yueNum") >= 0){
                    if($czye_change == '0'){
                        $type = WdXcxUserBalanceRecord::BALANCE_CHANGE_ADD;
                        $message = '平台操作增加';
                    }else if($czye_change == '1'){
                        $type = WdXcxUserBalanceRecord::BALANCE_CHANGE_DEL;
                        $message = '平台操作减少';
                    }
                    $result = (new WdXcxUserBalanceRecord())->addRecord($user, [
                        'order_id' => generateOrderId('B'),
                        'change_price' => input("yueNum"),
                        'message' => input('change_tip') ? input('change_tip') : $message,
                        'user_id' => $user->id,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND,
                    ], $type);
                }
            }
            if($types == 3){
                $czzs_change = input("czzs_change");
                if(input("zsNum") >= 0){
                    $is_end = 0;
                    $type = WdXcxUserDiamondRecord::DIAMOND_CHANGE_ADD;
                    if($czzs_change == '0'){
                        $message = '平台操作增加';
                    }else if($czzs_change == '1'){
                        $type = WdXcxUserDiamondRecord::DIAMOND_CHANGE_DEL;
                        $message = '平台操作减少';
                    }else{
                        $is_end = 1;
                        $message = '平台操作最终';
                    }
                    $result = (new WdXcxUserDiamondRecord())->addRecord($user, [
                        'order_id' => '',
                        'change_diamond' => input('zsNum'),
                        'message' => input('change_diamond_tip') ? input('change_diamond_tip') : $message,
                        'is_end' => $is_end,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND,
                    ], $type);
                }
            }
            if($types == 4){
                $gamecoin_change = input("gamecoin_change");
                if(input("gamecoinNum") >= 0){
                    if($gamecoin_change == '0'){
                        $message = '平台操作增加';
                        $change_value = input('gamecoinNum');
                    }else if($gamecoin_change == '1'){
                        $message = '平台操作减少';
                        $change_value = bcsub(0, input('gamecoinNum'));
                    }
                    $message = input('mask_gamecoinip') ? input('mask_gamecoinip') : $message;
                    (new YlbApiService())->changeUserValues($user->leaguer_id, 402, $change_value, $message);
                    $result = $user->UserGamecoin;
                }
            }
            if($types == 5){
                $lottery_change = input("lottery_change");
                if(input("lotteryNum") >= 0){
                    if($lottery_change == '0'){
                        $message = '平台操作增加';
                        $change_value = input('lotteryNum');
                    }else if($lottery_change == '1'){
                        $message = '平台操作减少';
                        $change_value = bcsub(0, input('lotteryNum'));
                    }
                    $message = input('mask_lotteryip') ? input('mask_lotteryip') : $message;
                    (new YlbApiService())->changeUserValues($user->leaguer_id, 403, $change_value, $message);
                    $result = $user->UserLottery;
                }
            }
            if($types == 6){
                $give_money_change = input("give_money_change");
                if(input("change_give_money") >= 0){
                    $is_end = 0;
                    $type = WdXcxUserGiveBalanceRecord::BALANCE_CHANGE_ADD;
                    if($give_money_change == '0'){
                        $message = '平台操作增加';
                    }else if($give_money_change == '1'){
                        $type = WdXcxUserGiveBalanceRecord::BALANCE_CHANGE_DEL;
                        $message = '平台操作减少';
                    }else{
                        $is_end = 1;
                        $message = '平台操作最终';
                    }
                    $result = (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                        'order_id' => '',
                        'change_price' => input('change_give_money'),
                        'message' => input('mask_give_money') ? input('mask_give_money') : $message,
                        'is_end' => $is_end,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND,
                    ], $type);
                }
            }

        }catch (Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
        Db::commit();
        $this->success('操作成功', '', ['data' => $result]);
    }

    /**修改用户真实姓名
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserRealname()
    {
        $uniacid = input('uniacid');
        $suid = input('suid');
        $truename = input('truename');
        $user = WdXcxUser::where('uniacid', $uniacid)->where('id', $suid)->find();
        if(!$user){
            $this->error('用户不存在');
        }
        $user->realname = $truename ? $truename : '';
        $user->save();
        $this->success('修改成功');
    }

    public function changeUserBirthDay()
    {
        $uniacid = input('uniacid');
        $suid = input('suid');
        $birth = input('birth');
        $user = WdXcxUser::where('uniacid', $uniacid)->where('id', $suid)->find();
        if(!$user){
            $this->error('用户不存在');
        }
        if($birth){
            $date_preg = "/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/";
            if (!preg_match($date_preg, $birth)) {
                $this->error("日期格式错误！");
            }
        }else{
            $birth = '';
        }
        $user->birth_day = $birth;
        $user->save();
        $this->success('修改成功');
    }

    /**充值记录
     * @return string
     */
    public function recharge()
    {
        $this->checkUserRule(22);
        $id = $this->uniacid;
        $uid = input('uid');
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $order_id = input('order_id');
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        $lists = WdXcxUserBuyGradeOrderLists::where('uniacid', $id)
            ->where('status', 2)
            ->where(function ($query)use($start_get, $end_get, $key, $suids, $order_id){
                if($order_id){
                    $query->where('order_id', $order_id);
                }else{
                    if($start_get){
                        $query->where('create_time', '>', strtotime($start_get));
                    }
                    if($end_get){
                        $query->where('create_time', '<', strtotime($end_get));
                    }
                    if($key){
                        $query->where(function ($query)use($key, $suids){
                            $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%')->whereOr('invite_code', 'like', '%'.$key.'%');
                        });
                    }
                }

            })->order('id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => request()->param()
            ]);
        $total_money = 0;
//        if($start_get || $end_get){
//            $total_money = WdXcxUserRechargeOrderLists::where('uniacid', $id)
//                ->where('status', 2)
//                ->where(function ($query)use($start_get, $end_get, $key, $suids, $order_id){
//                    if($order_id){
//                        $query->where('order_id', $order_id);
//                    }else{
//                        if($start_get){
//                            $query->where('create_time', '>', strtotime($start_get));
//                        }
//                        if($end_get){
//                            $query->where('create_time', '<', strtotime($end_get));
//                        }
//                        if($key){
//                            $query->where(function ($query)use($key, $suids){
//                                $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%');
//                            });
//                        }
//                    }
//
//                })->sum('pay_price');
//        }

        return View::fetch('wxuser/recharge', [
            'lists' => $lists,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'total_money' => $total_money,
        ]);
    }

    /**消费记录
     * @return string
     */
    public function consumption()
    {
        $this->checkUserRule(23);
        $id = $this->uniacid;
        $op = input("op") ? input("op") : 'display';
        $uid = input('suid') ? input('suid') : 0;
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $order_id = input('order_id');
        $order_type = input('order_type') ? input('order_type') : 0;
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        $lists = WdXcxUserBalanceRecord::where('uniacid', $id)
            ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid, $order_id, $order_type){
                if($order_id){
                    $query->where('order_id', $order_id)->where('change_type', 1);
                }else{
                    if($uid){
                        $query->where('user_id', $uid);
                    }
                    if($op == 'get'){
                        $query->where('change_type', 2);
                    }
                    if($op == 'xf'){
                        $query->where('change_type', 1);
                    }
                    if($start_get){
                        $query->where('create_time', '>', strtotime($start_get));
                    }
                    if($end_get){
                        $query->where('create_time', '<', strtotime($end_get));
                    }
                    if($key){
                        $query->where(function ($query)use($key, $suids){
                            $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%');
                        });
                    }
                    if($order_type){
                        if($order_type == 1){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING);
                        }
                        if($order_type == 2){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY);
                        }
                        if($order_type == 3){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET);
                        }
                        if($order_type == 4){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE);
                        }
                        if($order_type == 5){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN);
                        }
                    }
                }

            })->order('create_time desc')
            ->paginate([
                'list_rows' => input('size') ? input('size') : 10,
                'query' => request()->param()
            ]);
        if($uid){
            $this->success('success', '', [
                'lists' => $lists->toArray()['data'],
                'total_page' => $lists->lastPage(),
                'curr_page' => $lists->currentPage(),
            ]);
        }
        $total_money = 0;
        if($start_get || $end_get){
            $total_money = WdXcxUserBalanceRecord::where('uniacid', $id)
                ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid, $order_id, $order_type){
                    if($order_id){
                        $query->where('order_id', $order_id)->where('change_type', 1);
                    }else{
                        if($uid){
                            $query->where('user_id', $uid);
                        }
                        if($op == 'get'){
                            $query->where('change_type', 2);
                        }
                        if($op == 'xf'){
                            $query->where('change_type', 1);
                        }
                        if($start_get){
                            $query->where('create_time', '>', strtotime($start_get));
                        }
                        if($end_get){
                            $query->where('create_time', '<', strtotime($end_get));
                        }
                        if($key){
                            $query->where(function ($query)use($key, $suids){
                                $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%');
                            });
                        }
                        if($order_type){
                            if($order_type == 1){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING);
                            }
                            if($order_type == 2){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY);
                            }
                            if($order_type == 3){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET);
                            }
                            if($order_type == 4){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE);
                            }
                            if($order_type == 5){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN);
                            }
                        }
                    }

                })->sum('change_price');
        }
        return View::fetch('wxuser/consumption', [
            'op' => $op,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'lists' => $lists,
            'order_type' => $order_type,
            'total_money' => $total_money,
        ]);
    }

    /**零钱支付流水
     * @return string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function giveBalanceRecord()
    {
        $this->checkUserRule(49);
        $id = $this->uniacid;
        $op = input("op") ? input("op") : 'display';
        $uid = input('suid') ? input('suid') : 0;
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $order_id = input('order_id');
        $order_type = input('order_type') ? input('order_type') : 0;
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        $to_excel = input('excel');
        $lists_sql = WdXcxUserGiveBalanceRecord::where('uniacid', $id)
            ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid, $order_id, $order_type){
                if($order_id){
                    $query->where('order_id', $order_id)->where('change_type', 1);
                }else{
                    if($uid){
                        $query->where('user_id', $uid);
                    }
                    if($op == 'get'){
                        $query->where('change_type', 2);
                    }
                    if($op == 'xf'){
                        $query->where('change_type', 1);
                    }
                    if($start_get){
                        $query->where('create_time', '>', strtotime($start_get));
                    }
                    if($end_get){
                        $query->where('create_time', '<', strtotime($end_get));
                    }
                    if($key){
                        $query->where(function ($query)use($key, $suids){
                            $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%')->whereOr('message', 'like', '%'.$key.'%');
                        });
                    }
                    if($order_type){
                        if($order_type == 1){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING);
                        }
                        if($order_type == 2){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY);
                        }
                        if($order_type == 3){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET);
                        }
                        if($order_type == 4){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE);
                        }
                        if($order_type == 5){
                            $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN);
                        }
                    }
                }

            })->order('create_time desc');
        if($to_excel){
            $lists = $lists_sql->select();
            $this->exportExcel($lists);
        }else{
            $lists = $lists_sql->paginate([
                'list_rows' => input('size') ? input('size') : 10,
                'query' => request()->param()
            ]);
        }
        if($uid){
            $this->success('success', '', [
                'lists' => $lists->toArray()['data'],
                'total_page' => $lists->lastPage(),
                'curr_page' => $lists->currentPage(),
            ]);
        }

        $total_money = 0;
        if($start_get || $end_get){
            $total_money = WdXcxUserGiveBalanceRecord::where('uniacid', $id)
                ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid, $order_id, $order_type){
                    if($order_id){
                        $query->where('order_id', $order_id)->where('change_type', 1);
                    }else{
                        if($uid){
                            $query->where('user_id', $uid);
                        }
                        if($op == 'get'){
                            $query->where('change_type', 2);
                        }
                        if($op == 'xf'){
                            $query->where('change_type', 1);
                        }
                        if($start_get){
                            $query->where('create_time', '>', strtotime($start_get));
                        }
                        if($end_get){
                            $query->where('create_time', '<', strtotime($end_get));
                        }
                        if($key){
                            $query->where(function ($query)use($key, $suids){
                                $query->whereIn('user_id', $suids)->whereOr('order_id', 'like', '%'.$key.'%')->whereOr('message', 'like', '%'.$key.'%');
                            });
                        }
                        if($order_type){
                            if($order_type == 1){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING);
                            }
                            if($order_type == 2){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY);
                            }
                            if($order_type == 3){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET);
                            }
                            if($order_type == 4){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE);
                            }
                            if($order_type == 5){
                                $query->where('change_source', WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN);
                            }
                        }
                    }

                })->sum('change_price');
        }

        return View::fetch('wxuser/give_balance', [
            'op' => $op,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'lists' => $lists,
            'order_type' => $order_type,
            'total_money' => $total_money,
        ]);
    }

    public function wxpay()
    {
        $this->checkUserRule(50);
        $key = input('key');
        $order_type = input('order_type');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $lists = WdXcxUserWxpayRecord::where('uniacid', $this->uniacid)
            ->where(function ($query)use($key, $start_get, $end_get, $order_type){
                if($key){
                    $query->where('order_id', 'like', '%'.$key.'%');
                }
                if($start_get){
                    $query->where('create_time', '>', strtotime($start_get));
                }
                if($end_get){
                    $query->where('create_time', '<', strtotime($end_get));
                }
                if($order_type && $order_type != 'all'){
                    $query->where('order_type', $order_type);
                }
            })
            ->order('id desc')
            ->paginate([
                'list_rows' => input('size') ? input('size') : 10,
                'query' => request()->param()
            ]);
        $total_money = WdXcxUserWxpayRecord::where('uniacid', $this->uniacid)
            ->where(function ($query)use($key, $start_get, $end_get, $order_type){
                if($key){
                    $query->where('order_id', 'like', '%'.$key.'%');
                }
                if($start_get){
                    $query->where('create_time', '>', strtotime($start_get));
                }
                if($end_get){
                    $query->where('create_time', '<', strtotime($end_get));
                }
                if($order_type && $order_type != 'all'){
                    $query->where('order_type', $order_type);
                }
            })->sum('pay_price');

        return View::fetch('wxuser/wx_pay', [
            'lists' => $lists,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'order_type' => $order_type,
            'total_money' => $total_money,
        ]);
    }

    /**余额明细
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserConsumption()
    {
        $id = $this->uniacid;
        $uid = input('suid');
        if(!$uid){
            $this->error('请输入要查询的用户');
        }
        $lists = WdXcxUserBalanceRecord::where('uniacid', $id)
            ->where('user_id', $uid)
            ->order('create_time desc')
            ->paginate([
                'list_rows' => 6,
                'query' => request()->param()
            ]);
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    public function getUseGiveBalanceRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid');
        if(!$uid){
            $this->error('请输入要查询的用户');
        }
        $lists = WdXcxUserGiveBalanceRecord::where('uniacid', $id)
            ->where('user_id', $uid)
            ->order('create_time desc')
            ->paginate([
                'list_rows' => 6,
                'query' => request()->param()
            ]);
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    /**用户佣金明细
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUseCommissionRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid');
        if(!$uid){
            $this->error('请输入要查询的用户');
        }
        $lists = WdXcxUserCommissionRecord::where('uniacid', $id)
            ->where('user_id', $uid)
            ->order('create_time desc')
            ->paginate([
                'list_rows' => 6,
                'query' => request()->param()
            ]);
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    /**积分流水
     * @return string
     */
    public function integralRecord()
    {
        $this->checkUserRule(24);
        $id = $this->uniacid;
        $op = input("op") ? input("op") : 'display';
        $uid = input('suid');
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        $lists = WdXcxUserIntegralRecord::where('uniacid', $id)
            ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid){
                if($uid){
                    $query->where('user_id', $uid);
                }
                if($op == 'get'){
                    $query->where('change_type', 2);
                }
                if($op == 'xf'){
                    $query->where('change_type', 1);
                }
                if($start_get){
                    $query->where('create_time', '>', strtotime($start_get));
                }
                if($end_get){
                    $query->where('create_time', '<', strtotime($end_get));
                }
                if($key){
                    $query->whereIn('user_id', $suids);
                }
            })->order('id desc')
            ->paginate([
                'list_rows' => input('size') ? input('size') : 10,
                'query' => request()->param()
            ]);
        if($uid){
            $this->success('success', '', [
                'lists' => $lists->toArray()['data'],
                'total_page' => $lists->lastPage(),
                'curr_page' => $lists->currentPage(),
            ]);
        }
        return View::fetch('wxuser/integral_record', [
            'op' => $op,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'scorelist' => $lists,
        ]);
    }

    /**用户积分流水
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserIntegralRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid');
        $lists = WdXcxUserIntegralRecord::where('uniacid', $id)
            ->where('user_id', $uid)
            ->order('id desc')
            ->paginate(6);
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    /**钻石流水
     * @return string
     * @throws \think\db\exception\DbException
     */
    public function diamond()
    {
        $this->checkUserRule(25);
        $id = $this->uniacid;
        $op = input("op") ? input("op") : 'display';
        $uid = input('suid') ? input('suid') : 0;
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        $lists = WdXcxUserDiamondRecord::where('uniacid', $id)
            ->where(function ($query)use($op, $start_get, $end_get, $key, $suids, $uid){
                if($uid){
                    $query->where('user_id', $uid);
                }
                if($op == 'get'){
                    $query->where('change_type', 1);
                }
                if($op == 'xf'){
                    $query->where('change_type', 2);
                }
                if($start_get){
                    $query->where('create_time', '>', strtotime($start_get));
                }
                if($end_get){
                    $query->where('create_time', '<', strtotime($end_get));
                }
                if($key){
                    $query->whereIn('user_id', $suids);
                }
            })->order('id desc')
            ->paginate([
                'list_rows' => input('size') ? input('size') : 10,
                'query' => request()->param()
            ]);
        if($uid){
            $this->success('success', '', [
                'lists' => $lists->toArray()['data'],
                'total_page' => $lists->lastPage(),
                'curr_page' => $lists->currentPage(),
            ]);
        }
        return View::fetch('wxuser/diamond', [
            'op' => $op,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'lists' => $lists,
        ]);
    }

    /**获取用户钻石流水
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserDiamondRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid') ? input('suid') : 0;
        $lists = WdXcxUserDiamondRecord::where('uniacid', $id)
            ->where('user_id', $uid)
            ->order('id desc')
            ->paginate(6);
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    /**用户游玩记录
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUsePlayRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid') ? input('suid') : 0;
        $lists = WdXcxUserPlayRecord::where('user_id', $uid)
            ->order('id desc')
            ->paginate(6)->each(function ($item){
                $item->record_type_str = $item->RecordTypeStr;
                $item->code_str = $item->CodeStr;
            });
        $this->success('success', '', [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ]);
    }

    /**获取用户游戏币流水
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUseGamecoinRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid') ? input('suid') : 0;
        $lists = null;
        $page = input('page') ? input('page') : 1;
        if($uid){
            $user = WdXcxUser::where('id', $uid)->find();
            if($user){
                $lists = (new YlbApiService())->getUserValuesLog($user->leaguer_id, 402, 6, $page);
                $lists = count($lists) > 0 ? $lists : null;
            }
        }
        $this->success('success', '', [
            'lists' => $lists,
            'curr_page' => $page,
        ]);
    }

    /**获取用户彩票流水
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUseLotteryRecord()
    {
        $id = $this->uniacid;
        $uid = input('suid') ? input('suid') : 0;
        $lists = null;
        $page = input('page') ? input('page') : 1;
        if($uid){
            $user = WdXcxUser::where('id', $uid)->find();
            if($user){
                $lists = (new YlbApiService())->getUserValuesLog($user->leaguer_id, 403, 6, $page);
                $lists = count($lists) > 0 ? $lists : null;
            }
        }
        $this->success('success', '', [
            'lists' => $lists,
            'curr_page' => $page,
        ]);
    }

    /**会员等级
     * @return string
     */
    public function vipGrade()
    {
        $this->checkUserRule(21);
        $uniacid = $this->uniacid;
        $lists = WdXcxVipgrade::where('uniacid', $uniacid)
            ->order('grade_level asc')
            ->select();
        return View::fetch('wxuser/vip_grade', [
            'list' => $lists,
        ]);
    }

    public function addGrade()
    {
        $appletid = $this->uniacid;
        $grade_arr = [];
        for($i=1;$i<=50;$i++){
            array_push($grade_arr,$i);
        }
        $gid = input('gid') ? input('gid') : 0;
        $item = null;
        $upload_size_type = 1;
        if($gid){
            $item = WdXcxVipgrade::where('uniacid', $appletid)->where('id', $gid)->find();
            if(!$item){
                $this->error('会员等级不存在');
            }
            $item->other_interests = $item->other_interests ? json_decode($item->other_interests, true) : [];
            $upload_size_type = $item->upload_size_type;
        }
        $changed = WdXcxVipgrade::where('uniacid', $appletid)->column('grade_level');

        return View::fetch('wxuser/add_grade', [
            'item' => $item,
            'grade_arr' => $grade_arr,
            'changed' => $changed,
            'upload_size_type' => $upload_size_type
        ]);
    }

    /**保存会员等级
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveGrade()
    {
        $uniacid = $this->uniacid;
        $grade = intval(input('grade'));
        $info = WdXcxVipgrade::where([
            'uniacid' => $uniacid,
            'grade_level' => $grade
        ])->find();
        if(!$info){
            $info = new WdXcxVipgrade();
            $info->uniacid = $uniacid;
            $info->grade_level = $grade;
        }
        $data = [];
        $grade_name = input('grade_name');
        if(!$grade_name){
            $this->error('请填写等级名称');
        }
        $data['grade_name'] = $grade_name;
        $data['annual_fee'] = input('annual_fee');
        $data['market_annual_fee'] = input('market_annual_fee');
        $data['midd_month_fee'] = input('midd_month_fee');
        $data['market_midd_month_fee'] = input('market_midd_month_fee');
        $data['month_fee'] = input('month_fee');
        $data['market_month_fee'] = input('market_month_fee');
        $data['new_buy_annual'] = input('new_buy_annual');
        $data['cloud_size'] = input('cloud_size');
        $data['editor_number'] = input('editor_number');
        $data['upload_size_type'] = input('upload_size_type');
        $data['upload_size'] = input('upload_size');
        $info->save($data);
        $this->success('保存成功', Url('/index/user/vipgrade'));
    }

    /**删除会员等级
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteGrade()
    {
        $uniacid = $this->uniacid;
        $id = input('gid');
        $item = WdXcxVipgrade::where('uniacid', $uniacid)->where('id', $id)->find();
        if(!$item){
            $this->error('会员等级不存在');
        }
        $item->delete();
        $this->success('删除成功', Url('/index/user/vipgrade'));
    }

    /**用户选择器弹窗
     * @return mixed
     */
    public function personSelector()
    {
        return View::fetch('wxuser/person_selector',[
            'backend_style' => 1,
        ]);
    }

    


    public function userSelection()
    {
        return View::fetch('wxuser/user_selection');
    }

    public function userSelectionData()
    {
        $uniacid = $this->uniacid;
        $key = input('key');
        $get_type = input('get_type');
        $size = input('size') ? input('size') : 20;
        $users = WdXcxUser::where('uniacid', $uniacid)
            ->where(function ($query)use($key){
                if($key){
                    $query->where('mobile', 'like', '%'.$key.'%')->whereOr('nickname', 'like', '%'.$key.'%');
                }
            })
            ->order('id desc')
            ->field('id, nickname, avatar, mobile as phone')
            ->paginate($size);
        $this->success('', '', $users);
    }

    /**零钱流水导出
     * @param $lists
     * @return void
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function exportExcel($lists)
    {
        $excel_title = [
            '用户',
            '金额',
            '类型',
            '时间',
            '说明'
        ];
        $excel_data = [];
        $total_money = 0;
        foreach ($lists as $item){
            $temp[$excel_title[0]] = [
                'value' =>  filterEmoji($item->UserInfo['nickname']),
                'need_merge' => false,
                'can_show' => true,
            ];
            $temp[$excel_title[1]] = [
                'value' => $item->change_price,
                'need_merge' => false,
                'can_show' => true,
            ];
            $temp[$excel_title[2]] = [
                'value' => $item->change_type == 1 ? '消费' : '增加',
                'need_merge' => false,
                'can_show' => true,
            ];
            $temp[$excel_title[3]] = [
                'value' => $item->create_time,
                'need_merge' => false,
                'can_show' => true,
            ];
            $temp[$excel_title[4]] = [
                'value' => $item->message,
                'need_merge' => false,
                'can_show' => true,
            ];
            $temp['son'] = 0;
            $excel_data[] = $temp;
            $total_money = bcadd($total_money, $item->change_price, 2);
        }
        $total_money = '总计金额：'.$total_money;
        (new ExcelCommon())->exportExcel($excel_data, $excel_title, '零钱消费流水', $total_money);
    }

    /**改变用户会员等级时间
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserGrade()
    {
        $uniacid = $this->uniacid;
        $userId = input('user_id');
        $dateType = input('date_type');
        $endTime = input('end_time');
        $vipGrade = input('vip_grade');

        // 验证用户是否存在
        $user = WdXcxUser::where('uniacid', $uniacid)
            ->where('id', $userId)
            ->find();
        if (!$user) {
            $this->error('指定用户不存在');
        }
        // 构建更新信息说明
        $changeInfo = '后台修改，从' . $user->vip_grade . '级修改为' . $vipGrade . '级, 到期时间为';
        if ($dateType == 1) {
            // 永久有效
            $endTime = 0;
            $changeInfo .= '永久';
        } else {
            // 指定日期
            if (!$endTime) {
                $this->error('请选择到期时间');
            }
            $changeInfo .= $endTime;
            $endTime = strtotime($endTime . ' 23:59:59');

            // 验证结束时间不能小于当前时间
            if ($endTime < time()) {
                $this->error('到期时间不能小于当前时间');
            }
        }

        // 调用用户模型的会员等级变更方法
        $user->changeUserVipGrade($user, $vipGrade, $endTime, $changeInfo);
        $this->success('操作成功');
    }


}