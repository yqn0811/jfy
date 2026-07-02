<?php

namespace app\common\service\coupon;

use app\common\model\coupon\WdXcxCoupon;
use app\common\model\coupon\WdXcxCouponBatchRecord;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\user\WdXcxUser;
use app\common\service\BaseService;
use app\common\service\ylb\YlbApiService;
use cores\utils\Utils;
use think\App;
use think\cache\driver\Redis;
use think\facade\Db;
use think\Exception;
use think\facade\Cache;

class UserCouponService extends BaseService
{
    private $model;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new WdXcxUserCoupon();
    }

    /**核销优惠券
     * @param $id
     * @param $type
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkUserCoupon($id, $type=1)
    {
        $user_coupon = $this->checkUserCouponStatus($id);
        $user_coupon->use_status = 2;
        $user_coupon->use_time = time();
        $user_coupon->use_info = $type == 1 ? '后台操作核销' : '后台扫码核销';
        $user_coupon->use_type = WdXcxUserCoupon::USE_COUPON_BACK_HX;
        $user_coupon->save();
        if($user_coupon->remote_coupon_id){ //如果是远程卡券，同步状态
            (new YlbApiService())->useCouponById($user_coupon->remote_coupon_id);
        }
    }

    private function checkUserCouponStatus($id, $user_id='')
    {
        $user_coupon = $this->model->where('id', $id)
            ->where(function ($query)use($user_id){
                if($user_id){
                    $query->where('user_id', $user_id);
                }
            })
            ->find();
        if(!$user_coupon){
            throwError('指定的卡券不存在');
        }
        if($user_coupon->use_status != 1){
            throwError('当前状态不可用');
        }
        if($user_coupon->btime != 0 && $user_coupon->btime > time()){
            throwError('当前优惠券未到使用时间');
        }
        if($user_coupon->etime != 0 && $user_coupon->etime < time()){
            $user_coupon->use_status = 3;
            $user_coupon->save();
            throwError('当前优惠券已过期');
        }
        return $user_coupon;
    }

    /**获取指定用户的卡券列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponLists($param)
    {
        $this->getUserCouponFromRemote();
        $redis = new Redis(GetRedisConf());
        $redis->set('user_new_coupon_count_'.request()->userID(), 0, 300);
        if($param['type']){
            $this->checkUserVoucherStatus($param['user_id']);
        }
        //本系统发放的券
        $system_coupon = $this->model->where([
            'uniacid' => $this->uniacid,
            'user_id' => $param['user_id'],
            'use_status' => 1
        ])->where('coupon_id', '>', 0)
            ->where(function ($query)use($param){
                if($param['type'] == 3){
                    $query->where('coupon_type', 3);
                }else{
                    $query->where('coupon_type', '<>', 3);
                }
            })
            ->group('coupon_id')
            ->order([
            'use_status' => 'asc',
            'id' => 'desc'
        ])->field('id,uniacid,user_id,coupon_id,coupon_title,coupon_type,bg_image,btime,etime,use_status,create_time,qrcode_id,remote_coupon_id,use_rule,count(*) as coupon_num')
            ->select();
        //同步的远程券
        $remote_coupon = $this->model->where([
            'uniacid' => $this->uniacid,
            'user_id' => $param['user_id'],
            'use_status' => 1
        ])->where('coupon_id', -1)
            ->where(function ($query)use($param){
                if($param['type'] == 3){
                    $query->where('coupon_type', 3);
                }else{
                    $query->where('coupon_type', '<>', 3);
                }
            })
            ->group('coupon_title')
            ->order([
                'use_status' => 'asc',
                'id' => 'desc'
            ])->field('id,uniacid,user_id,coupon_id,coupon_title,coupon_type,bg_image,btime,etime,use_status,create_time,qrcode_id,remote_coupon_id,use_rule,count(*) as coupon_num')
            ->select();
        $all_coupon = $system_coupon->merge($remote_coupon)->all();
        foreach ($all_coupon as &$item){
            if($item->coupon_id > 0){
                list($btime_str, $etime_str, $user_coupon_id) = $item->getRecentlyDataCouponData($item->user_id, $item->coupon_id);
//                $item->coupon_title = '';
                $item->bg_image = $item->bg_image ?: $this->getUserCouponDefaultBgImage($item->coupon_id);
            }else{
                list($btime_str, $etime_str, $user_coupon_id) = $item->getRecentlyDataCouponData($item->user_id, 0, $item->coupon_title);
                list($bg_image, $show_title) = $this->getCouponBg($item->coupon_title);
                $item->bg_image = $bg_image;
                $item->coupon_title = $show_title;
            }
            $item->id = $user_coupon_id;
            $item->btime_str = $btime_str;
            $item->etime_str = $etime_str;
        }
        return $all_coupon;
    }

    private function checkUserVoucherStatus($user_id)
    {
        $lists = WdXcxUserCoupon::where([
            'user_id' => $user_id,
            'use_status' => 1,
            'coupon_type' => 3
        ])
            ->select();
        foreach ($lists as $list_item){
            $list_item->use_status = $list_item->use_status;
        }
    }

    /**获取指定卡券的核销规则
     * @param $coupon_id
     * @param $user_id
     * @return WdXcxUserCoupon|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponUseRule($coupon_id, $user_id)
    {
        $user_coupon = WdXcxUserCoupon::where('id', $coupon_id)
            ->where(function ($query)use($user_id){
                if($user_id){
                    $query->where('user_id', $user_id);
                }
            })
            ->find();
        if(!$user_coupon){
            throwError('指定的卡券不存在');
        }
        $user_coupon->bg_image = $user_coupon->bg_image ?: $this->getUserCouponDefaultBgImage($user_coupon->coupon_id);
        $user_coupon->btime_str = $user_coupon->btime ? date('Y-m-d', $user_coupon->btime) : 0;
        $user_coupon->etime_str = $user_coupon->etime ? date('Y-m-d', $user_coupon->etime) : 0;
        $user_coupon->coupon_title = '';
        if(!$user_coupon->use_rule){
            $coupon_rule = WdXcxCoupon::where('id', $user_coupon->coupon_id)->value('use_rule');
            $user_coupon->use_rule = $coupon_rule ? $coupon_rule : '';
        }
        return $user_coupon;
    }

    /**获取用户代金券列表
     * @param $pay_price
     * @param $user_id
     * @return array
     */
    public function getUserCouponVoucher($pay_price, $user_id)
    {
        $this->checkUserVoucherStatus($user_id);
        $user_coupon_list = $this->getUserCouponVoucherLists($user_id);
        return $this->sortUserCouponVoucherLists($user_coupon_list, $pay_price);
    }

    /**检查代金券使用条件
     * @param $lists
     * @param $pay_price
     * @return array
     */
    private function sortUserCouponVoucherLists($lists, $pay_price)
    {
        $check_use = null;
        foreach ($lists as $k => $item){
            $item->is_check = 0;
            $item->can_use = $item->checkCouponCanUse($item, $pay_price);
            if($item->can_use){
                if(!$check_use){
                    $check_use = [
                        'key' => $k,
                        'minus_money' => $item->minus_money,
                        'etime' => $item->etime,
                        'user_coupon_id' => $item->id,
                    ];
                    $item->is_check = 1;
                }else{
                    if(bccomp($item->minus_money, $check_use['minus_money']) == 1){
                        $item->is_check = 1;
                        $lists[$check_use['key']]['is_check'] = 0;
                        $check_use = [
                            'key' => $k,
                            'minus_money' => $item->minus_money,
                            'etime' => $item->etime,
                            'user_coupon_id' => $item->id,
                        ];
                    }
                    if(bccomp($item->minus_money, $check_use['minus_money']) == 0){
                        if($item->etime != 0 && $item->etime < $check_use['etime']){
                            $item->is_check = 1;
                            $lists[$check_use['key']]['is_check'] = 0;
                            $check_use = [
                                'key' => $k,
                                'minus_money' => $item->minus_money,
                                'etime' => $item->etime,
                                'user_coupon_id' => $item->id,
                            ];
                        }
                    }
                }
            }
            $item->etime_str = $item->etime ? date('Y-m-d', $item->etime) : 0;
            $item->btime_str = $item->btime ? date('Y-m-d', $item->btime) : 0;
        }
        return [
            'user_coupom' => $lists,
            'check_coupon' => $check_use,
        ];
    }

    /**获取用户代金券列表
     * @param $user_id
     * @return mixed
     */
    private function getUserCouponVoucherLists($user_id)
    {
        $lists = $this->model->where([
            'uniacid' => $this->uniacid,
            'user_id' => $user_id,
            'use_status' => 1,
            'coupon_type' => 3,
        ])->where('coupon_id', '>', 0)
            ->group('coupon_id')
            ->order([
                'use_status' => 'asc',
                'id' => 'desc'
            ])->field('id,uniacid,user_id,coupon_id,coupon_title,coupon_type,btime,etime,use_status,pay_money,minus_money,count(*) as coupon_num')
            ->select();
        foreach ($lists as $item){
            list($btime_str, $etime_str, $user_coupon_id) = $item->getRecentlyDataCouponData($item->user_id, $item->coupon_id);
            $item->id = $user_coupon_id;
            $item->btime = $btime_str == 0 ? 0 : strtotime($btime_str.' 00:00:00');
            $item->etime = $etime_str == 0 ? 0 : strtotime($etime_str.' 23:59:59');
        }
        return $lists;
    }


    /**获取用户优惠券默认背景图
     * @param $coupon_id
     * @return array|mixed|string|string[]
     */
    private function getUserCouponDefaultBgImage($coupon_id)
    {
        $bg_image = WdXcxCoupon::where('id', $coupon_id)->value('bg_image');
        return $bg_image ? remote($this->uniacid, $bg_image, 1) : getLocalImage('/image/static/card-style1.png');
    }

    private function getCouponBg($title)
    {
        $show_title = '';
        switch ($title){
            case '咖啡抵用券':
                $bg_image = getLocalImage('/image/static/coffee_bg.jpg');
                break;
            case '果盘抵用券':
                $bg_image = getLocalImage('/image/static/guo_pan_bg.jpg');
                break;
            case '鸡尾酒抵用券':
                $bg_image = getLocalImage('/image/static/ji_wei_bg.jpg');
                break;
            case '小吃抵用券':
                $bg_image = getLocalImage('/image/static/xiao_chi_bg.jpg');
                break;
            case '台球免打券':
                $bg_image = getLocalImage('/image/static/taiqiu.png');
                break;
            case '甜品抵用券':
                $bg_image = getLocalImage('/image/static/tianping.png');
                break;
            case '台球/棋牌1小时抵用券':
                $bg_image = getLocalImage('/image/static/taiqiu_1_hour.png');
                break;
            case '全天单人畅玩-数字运动区抵用券':
                $bg_image = getLocalImage('/image/static/all_day.jpg');
                break;
            default:
                $show_title = $title;
                $bg_image = getLocalImage('/image/static/card-style1.png');
                break;
        }
        return [$bg_image, $show_title];
    }

    /**获取用户未使用卡券数量
     * @param $param
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponCount($param)
    {
        $this->getUserCouponFromRemote();
        $redis = new Redis(GetRedisConf());
        $data = $redis->get('user_new_coupon_count_'.request()->userID());
        return $data ? $data : 0;
    }

    /**获取指定用户的卡券二维码
     * @param $param
     * @return string
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponQrcode($param)
    {
        $user_coupon = $this->checkUserCouponStatus($param['cid'], $param['user_id']);
        $qrcode_id = generateOrderId('Q');
        $qrcode = Utils::createQrcode($qrcode_id, '', true);
        $user_coupon->qrcode_id = $qrcode_id;
        $user_coupon->save();
        return compact('qrcode');
    }

    /**从远程获取用户卡券并且同步到本地
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponFromRemote()
    {
        $lists = (new YlbApiService())->getUserCouponLists(request()->leaguerID());
        if(count($lists) > 0){
            $user_coupon_data = [];
            foreach ($lists as $item){
                $has = $this->model->where([
                    'user_id' => request()->userID(),
                    'coupon_id' => -1,
                    'remote_coupon_id' => $item['ID'],
                ])->find();
                if(!$has){
                    $user_coupon_data[] = [
                        'uniacid' => 1,
                        'user_id' => request()->userID(),
                        'coupon_id' => -1,
                        'coupon_title' => $item['CouponName'],
                        'bg_image' => '',
                        'coupon_type' => 1,
                        'btime' => isset($item['StartTime']) ? strtotime($item['StartTime']) : strtotime($item['StartDate'] . ' 00:00:00'),
                        'etime' => isset($item['EndTime']) ? strtotime($item['EndTime']) : strtotime($item['EndDate'] . ' 23:59:59'),
                        'use_status' => 1,
                        'message' => '同步远程优惠券',
                        'remote_coupon_id' => $item['ID'],
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                }
            }
            $new_coupon_count = count($user_coupon_data);
            if($new_coupon_count > 0){
                $this->model->insertAll($user_coupon_data);
                $redis = new Redis(GetRedisConf());
                $old_data = $redis->get('user_new_coupon_count_'.request()->userID());
                if($old_data){
                    $redis->set('user_new_coupon_count_'.request()->userID(), $old_data+$new_coupon_count);
                }else{
                    $redis->set('user_new_coupon_count_'.request()->userID(), $new_coupon_count);
                }
            }
        }
    }

    /**执行批量发券
     * @param $param
     * @return array|void
     * @throws \cores\exception\BaseException
     */
    public function doBatchCoupon($param)
    {
        if(empty($param['coupon_id']) || empty($param['coupon_num'])){
            throwError('请选择怎送券和数量');
        }
        $coupon_id = $param['coupon_id'];
        $coupon_num = $param['coupon_num'];
        if(empty($param['users'])){
            throwError('请选择送券用户');
        }
        $all_users = $param['users'];
        $page = empty($param['page']) ? 0 : $param['page'];
        $coupon_info = [];
        foreach ($coupon_id as $k => $item){
            if(!empty($coupon_num[$k])){
                $coupon_info[] = [
                    'coupon_id' => $item,
                    'coupon_num' => $coupon_num[$k],
                ];
            }
        }
        if(count($all_users) > 10){
            $suids = array_chunk($all_users, 10);
            if($page == count($suids) - 1){
                $suids = $suids[$page];
                $msg = '发放完成';
                $code = 1;
            }else{
                $total_pro = bcmul(bcdiv(($page+1), count($suids), 2), 100) . "%";
                $code = 2;
                $suids = $suids[$page];
                $page = $page + 1;
                $msg = '发放中，已完成'.$total_pro.'个';
            }
        }else{
            $suids = $all_users;
            $msg = '发放完成';
            $code = 1;
        }
        if(count($coupon_info) > 0){
            Db::startTrans();
            $batch_coupon_ids = Cache::get('batch_coupon_ids') ? Cache::get('batch_coupon_ids') : [];
            $batch_users = [];
            try {
                $coupon_model = new WdXcxUserCoupon();
                foreach ($suids as $user_id){
                    $user =  WdXcxUser::find($user_id);
                    if($user){
                        $temp_batch_coupon_ids = $coupon_model->giveUserCoupon($user, $coupon_info, '批量发券', WdXcxUserCoupon::USER_COUPON_BACK_BATCH);
                        $batch_coupon_ids = array_merge($batch_coupon_ids, $temp_batch_coupon_ids);
                        $batch_users[] = $user_id;
                    }
                }
                Cache::set('batch_coupon_ids', $batch_coupon_ids);
                if($code == 1){
                    WdXcxCouponBatchRecord::create([
                        'uniacid' => $this->uniacid,
                        'batch_coupon' => $coupon_info,
                        'batch_users' => $all_users,
                        'batch_coupon_user_ids' => $batch_coupon_ids,
                    ]);
                    Cache::delete('batch_coupon_ids');
                }
            }catch (Exception $exception){
                Db::rollback();
                throwError($exception->getMessage());
            }
            Db::commit();
            return ['code' => $code, 'msg' => $msg, 'page' => $page];
        }else{
            throwError('请选择送券数据');
        }
    }

    /**获取批量发券记录
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getBatchCouponRecord($param)
    {
        $lists = WdXcxCouponBatchRecord::where('uniacid', $this->uniacid)
            ->order('id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => request()->param()
            ]);
        return $lists;
    }

    /**获取领取用户记录
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBatchRecordUser($param)
    {
        if(empty($param['rid'])){
            throwError('请选择记录');
        }
        $info = WdXcxCouponBatchRecord::find($param['rid']);
        if(!$info){
            throwError('记录不存在');
        }
        $page = empty($param['page']) ? 0 : $param['page'];
        $batch_users = $info->batch_users;
        $size = 2;
        $users = array_splice($batch_users, $page*$size, $size);
        $result = [];
        foreach ($users as $item){
            $user = WdXcxUser::find($item);
            $result[] = [
                'avatar' => $user->avatar,
                'nickname' => $user->nickname,
                'mobile' => $user->mobile,
            ];
        }
        return [
            'users' => $result,
            'code' => ($page+1)*$size < count($info->batch_users) ? 2 : 1,
            'page' => $page+1,
        ];
    }
}