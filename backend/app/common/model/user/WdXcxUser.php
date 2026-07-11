<?php

namespace app\common\model\user;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\BaseModel;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\distribution\WdXcxDistributionBase;
use app\common\model\distribution\WdXcxDistributionOrderLists;
use app\common\model\distribution\WdXcxDistributionScanReward;
use app\common\model\distribution\WdXcxDistributionUserParent;
use app\common\model\order\WdXcxUserAppointmentOrderLists;
use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\service\distribution\DistributionService;
use app\common\service\user\UserService;
use app\common\model\WdXcxBase;
use app\common\model\WdXcxPic;
use think\facade\Db;
use think\Model;

class WdXcxUser extends BaseModel
{
    const DEFAULT_FREE_SPACE_MB = 50;

    protected $pk = 'id';
    protected $name = 'wd_xcx_user';
    protected $autoWriteTimestamp = true;

    //财产变化源
    const PROPERTY_CHANGE_SOURCE_RECHARGE = 10; //充值
    const PROPERTY_CHANGE_SOURCE_RECHARGE_SEND = 11; //充值赠送
    const PROPERTY_CHANGE_SOURCE_EXCHANGE = 20; //兑换
    const PROPERTY_CHANGE_SOURCE_TICKET = 30; //买套票
    const PROPERTY_CHANGE_SOURCE_VIPGRADE = 40; //买会员
    const PROPERTY_CHANGE_SOURCE_GRADE_SEND = 41; //会员等级回馈
    const PROPERTY_CHANGE_SOURCE_BACKEND = 50; //后台操作
    const PROPERTY_CHANGE_SOURCE_BACKEND_SCAN = 51; //后台查询用户扣减操作
    const PROPERTY_CHANGE_SOURCE_YLBXF = 60; //游乐宝消费
    const PROPERTY_CHANGE_SOURCE_CATERING = 70; //点餐消费
    const PROPERTY_CHANGE_SOURCE_CATERING_CANCEL = 71; //点餐消费取消
    const PROPERTY_CHANGE_SOURCE_RESERVE = 80; //预约消费
    const PROPERTY_CHANGE_SOURCE_RESERVE_BACK = 81; //预约消费退回积分
    const PROPERTY_CHANGE_SOURCE_RESERVE_MONEY = 82; //预约消费支付余额
    const PROPERTY_CHANGE_SOURCE_SIGN = 90; //积分签到
    const PROPERTY_CHANGE_SOURCE_REWARD = 100; //排行奖励
    const PROPERTY_CHANGE_SOURCE_GAMECOIN = 110; //购买游戏币
    const PROPERTY_CHANGE_USER_EXCHANGE_BALANCE = 120; //用户兑换余额到零钱

    /**关联会员等级
     * @return \think\model\relation\HasOne
     */
    public function grade()
    {
        return $this->hasOne(WdXcxVipgrade::class, 'grade_level', 'vip_grade');
    }

    public function gradeInfo()
    {
        return $this->hasOne(WdXcxUserVipGradeInfo::class, 'user_id', 'id');
    }

    public function appointment()
    {
        return $this->hasMany(WdXcxUserAppointmentOrderLists::class, 'user_id', 'id');
    }

    public function playRecord()
    {
        return $this->hasMany(WdXcxUserPlayRecord::class, 'user_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(WdXcxUserOrderLists::class, 'user_id', 'id');
    }

    public function album()
    {
        return $this->hasMany(WdXcxAlbumFolder::class, 'uid', 'id');
    }

    /**关联贡献的分销订单金额
     * @return \think\model\relation\HasMany
     */
    public function contributeOrder()
    {
        return $this->hasMany(WdXcxDistributionOrderLists::class, 'user_id', 'id');
    }

    /**关联下级分销用户
     * @return \think\model\relation\HasMany
     */
    public function sonDistribution()
    {
        return $this->hasMany(WdXcxDistributionUserParent::class, 'parent_id', 'id');
    }

    /**
     * 获取用户分销上级信息
     * @return array|\think\Model|null
     */
    public function getUserDistributionInfo()
    {
        return WdXcxDistributionUserParent::where('user_id', $this->id)->find();
    }

    /**
     * 绑定分销关系
     * @param $parentUser
     * @return bool
     * @throws \cores\exception\BaseException
     */
    public function bindDistribution($parentUser)
    {
        Db::startTrans();
        try {
            WdXcxDistributionUserParent::create([
                'uniacid' => $this->uniacid,
                'user_id' => $this->id,
                'parent_id' => $parentUser->id,
                'bind_log' => [
                    [
                        'time' => date('Y-m-d H:i:s'),
                        'info' => '绑定推荐人:'.$parentUser->nickname
                    ]
                ]
            ]);

            // 触发邀请奖励
            (new \app\common\service\integral_sign\IntegralSignService(app()))->handleInviteReward($this->id, 'register');

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throwError($e->getMessage());
        }
    }

    /**获取下级数量
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSonCountAttr()
    {
        return $this->sonDistribution()->where('lock_time', '>', time())->count();
    }

    /**获取用户余额
     * @return string
     */
    public function getUserBalanceAttr()
    {
        return (new UserService(app()))->getUserBalance($this->leaguer_id);
    }

    /**获取用户游戏金币
     * @return mixed
     */
    public function getUserGamecoinAttr()
    {
        return (new UserService(app()))->getUserGamecoin($this->leaguer_id);
    }

    /**获取用户彩票余额
     * @return mixed
     */
    public function getUserLotteryAttr()
    {
        return (new UserService(app()))->getUserLottery($this->leaguer_id);
    }

    public function setAvatarAttr($value)
    {
        if($value){
            if(strpos($value, 'users/user_default.png') !== false){
                return setLocalImage($value);
            }
            return remote($this->uniacid, $value, 2);
        }else{
            return '';
        }
    }

    public function getAvatarAttr($value)
    {
        if($value){
            if(strpos($value, 'users/user_default.png') !== false){
                return getLocalImage($value);
            }
            return remote($this->uniacid, $value, 1);
        }else{
            return getLocalImage('/image/users/user_default.png');
        }
    }

    public function getWxEwmAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function setWxEwmAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }



    /**获取会员等级信息
     * @return array
     */
    public function getVipGradeInfoAttr()
    {
        $grade_info = $this->gradeInfo;
        $base_size = (int)WdXcxBase::where('uniacid', 1)->value('space_size');
        if ($base_size <= 0 || $base_size == 300) {
            $base_size = self::DEFAULT_FREE_SPACE_MB;
        }
        if(!$grade_info){
            $grade_info = WdXcxUserVipGradeInfo::create([
                'uniacid' => $this->uniacid,
                'user_id' => $this->id,
                'grade_level' => 0,
                'end_time' => 0,
                'change_log' => [
                    [
                        'change_time' => date('Y-m-d H:i:s'),
                        'change_info' => '补齐会员等级默认数据'
                    ]
                ]
            ]);
        }
        if($grade_info->end_time != 0 && $grade_info->end_time < time() && $grade_info->grade_level != 0){
            $this->gradeInfo->change_log = $grade_info->change_log ? array_merge($grade_info->change_log, [
                [
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_info' => '会员到期，从'.$this->gradeInfo->grade_level.'降价为0，存储容量降为'.$base_size.'M'
                ]
            ]) : [
                [
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_info' => '会员到期，从'.$this->gradeInfo->grade_level.'降价为0，存储容量降为'.$base_size.'M'
                ]
            ];
            $this->gradeInfo->grade_level = 0;
            $this->gradeInfo->save();
            $this->vip_grade = 0;
            $this->space_size = $base_size;
            $this->save();
        }
        if(!$this->space_size){
            $this->space_size = $base_size;
            $this->save();
        }
        return [
            'grade_level' => $grade_info->grade_level,
            'end_time' => $grade_info->end_time ? date('Y-m-d', $grade_info->end_time) : 0,
            'grade_name' => $this->grade ? $this->grade->grade_name : '',
            'space_size' => $this->space_size,
            'upload_size_type' => $this->grade ? $this->grade->upload_size_type : 1,
            'upload_size' => $this->grade ? $this->grade->upload_size : 200,
        ];
    }

    public function getJoinTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function ensureUploadPasswordColumns()
    {
        $hasExpireTime = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'upload_pwd_expire_time'");
        if (!$hasExpireTime) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `upload_pwd_expire_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '批量上传密码过期时间 0永久有效' AFTER `upload_pwd`");
        }
    }

    public function ensureHomePreferenceColumns()
    {
        $this->ensureUploadPasswordColumns();
        $hasInviteCode = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'invite_code'");
        if (!$hasInviteCode) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `invite_code` varchar(32) NOT NULL DEFAULT '' AFTER `user_uuid`");
        }
        $this->backfillEmptyInviteCodes();
        $hasInviteIndex = Db::query("SHOW INDEX FROM `wd_xcx_user` WHERE Key_name = 'idx_invite_code'");
        if (!$hasInviteIndex) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD UNIQUE INDEX `idx_invite_code`(`invite_code`)");
        }
        $hasHomeShareCode = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_share_code'");
        if (!$hasHomeShareCode) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_share_code` varchar(32) NOT NULL DEFAULT '' COMMENT 'PC主页分享码' AFTER `invite_code`");
        }
        $this->backfillEmptyHomeShareCodes();
        $hasHomeShareIndex = Db::query("SHOW INDEX FROM `wd_xcx_user` WHERE Key_name = 'idx_home_share_code'");
        if (!$hasHomeShareIndex) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD UNIQUE INDEX `idx_home_share_code`(`home_share_code`)");
        }
        $hasNickname = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'visit_no_need_nickname'");
        if (!$hasNickname) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `visit_no_need_nickname` tinyint(1) NOT NULL DEFAULT 0 COMMENT '主页访问无需填写昵称 0否 1是' AFTER `is_show_home`");
        }
        $hasMobile = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'visit_no_need_mobile'");
        if (!$hasMobile) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `visit_no_need_mobile` tinyint(1) NOT NULL DEFAULT 0 COMMENT '主页访问无需授权手机号 0否 1是' AFTER `visit_no_need_nickname`");
        }
        $hasSavePic = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'visit_allow_save_pic'");
        if (!$hasSavePic) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `visit_allow_save_pic` tinyint(1) NOT NULL DEFAULT 0 COMMENT '访客允许保存作品到相册 0否 1是' AFTER `visit_no_need_mobile`");
        }
        $hasWatermark = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_watermark_text'");
        if (!$hasWatermark) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_watermark_text` varchar(255) NOT NULL DEFAULT '' COMMENT '主页自定义水印文字' AFTER `visit_allow_save_pic`");
        }
        $hasServiceName = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_service_name'");
        if (!$hasServiceName) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_service_name` varchar(50) NOT NULL DEFAULT '服务' COMMENT '主页服务功能名称' AFTER `home_watermark_text`");
        }
        $hasShareTitle = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_share_title'");
        if (!$hasShareTitle) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_share_title` varchar(255) NOT NULL DEFAULT '' COMMENT '主页分享标题' AFTER `home_service_name`");
        }
        $hasShareDesc = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_share_desc'");
        if (!$hasShareDesc) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_share_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '主页分享描述' AFTER `home_share_title`");
        }
        $hasShareImage = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'home_share_image'");
        if (!$hasShareImage) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `home_share_image` varchar(255) NOT NULL DEFAULT '' COMMENT '主页分享图片' AFTER `home_share_desc`");
        }
        $hasIndustry = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'industry_info'");
        if (!$hasIndustry) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `industry_info` tinyint(1) NOT NULL DEFAULT 0 COMMENT '行业 0未知 1微供 2网供 3摄影' AFTER `home_share_image`");
        }
        $hasVisitReadTime = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'visit_read_time'");
        if (!$hasVisitReadTime) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `visit_read_time` int(11) NOT NULL DEFAULT 0 COMMENT '浏览记录已读时间' AFTER `industry_info`");
        }
    }

    private function ensureInviteColumns()
    {
        $this->ensureHomePreferenceColumns();
        $hasInviteFrom = Db::query("SHOW COLUMNS FROM `wd_xcx_user` LIKE 'invite_from_code'");
        if (!$hasInviteFrom) {
            Db::execute("ALTER TABLE `wd_xcx_user` ADD COLUMN `invite_from_code` varchar(32) NOT NULL DEFAULT '' AFTER `invite_code`");
        }
    }

    private function generateInviteCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
            $exists = $this->where('invite_code', $code)->find();
        } while ($exists);
        return $code;
    }

    private function generateHomeShareCode()
    {
        do {
            $code = 'H' . strtoupper(substr(md5(uniqid((string)mt_rand(), true) . microtime(true)), 0, 11));
            $exists = $this->where('home_share_code', $code)->find();
        } while ($exists);
        return $code;
    }

    private function backfillEmptyInviteCodes()
    {
        $ids = $this->whereNull('invite_code')->whereOr('invite_code', '')->column('id');
        foreach ($ids as $id) {
            $code = $this->generateInviteCode();
            $this->where('id', $id)->update(['invite_code' => $code]);
        }
    }

    private function backfillEmptyHomeShareCodes()
    {
        $ids = $this->whereNull('home_share_code')->whereOr('home_share_code', '')->column('id');
        foreach ($ids as $id) {
            $code = $this->generateHomeShareCode();
            $this->where('id', $id)->update(['home_share_code' => $code]);
        }
    }

    public function ensureInviteCodeForUser($user)
    {
        $this->ensureHomePreferenceColumns();
        $code = trim((string)($user->invite_code ?? ''));
        if ($code !== '') {
            return $code;
        }
        $code = $this->generateInviteCode();
        $user->invite_code = $code;
        $user->save();
        return $code;
    }

    public function ensureHomeShareCodeForUser($user)
    {
        $this->ensureHomePreferenceColumns();
        $code = trim((string)($user->home_share_code ?? ''));
        if ($code !== '') {
            return $code;
        }
        $code = $this->generateHomeShareCode();
        $user->home_share_code = $code;
        $user->save();
        return $code;
    }

    private function createWechatUser($openid, $unionid = '', $inviteFromCode = '', $extra = [])
    {
        $base_size = (int)WdXcxBase::where('uniacid', 1)->value('space_size');
        if ($base_size <= 0 || $base_size == 300) {
            $base_size = self::DEFAULT_FREE_SPACE_MB;
        }
        $inviteCode = $this->generateInviteCode();
        $homeShareCode = $this->generateHomeShareCode();
        $data = array_merge([
            'uniacid' => $this->uniacid,
            'openid' => $openid,
            'unionid' => $unionid,
            'nickname' => '微信用户',
            'avatar' => getLocalImage('/image/users/user_default.png'),
            'create_time' => time(),
            'user_uuid' => $this->getUuId(),
            'space_size' => $base_size,
            'invite_code' => $inviteCode,
            'home_share_code' => $homeShareCode,
            'invite_from_code' => $inviteFromCode,
        ], $extra);
        $this->create($data);
        $user = $this->where('openid', $openid)->find();
        //关联会员等级默认数据
        WdXcxUserVipGradeInfo::create([
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'grade_level' => 0,
            'end_time' => 0,
            'change_log' => [
                [
                    'change_time' => date('Y-m-d H:i:s'),
                    'change_info' => '用户注册，默认等级为0,默认空间' . $base_size . 'M'
                ]
            ]
        ]);
        return $user;
    }

    /**根据 openid/unionid 获取微信用户信息，优先使用 unionid 保持多端一致
     * @param $openid
     * @param string $unionid
     * @param bool $create
     * @param string $inviteFromCode
     * @param array $extra
     * @return WdXcxUser|array|mixed|Model|null
     */
    public function getUserByWechatIdentity($openid, $unionid = '', $create=false, $inviteFromCode = '', $extra = [])
    {
        $this->ensureInviteColumns();
        Db::startTrans();
        try {
            $user = null;
            if(!empty($unionid)){
                $user = $this->where('unionid', $unionid)->lock(true)->find();
            }
            if(!$user){
                $user = $this->where('openid', $openid)->lock( true)->find();
            }
            if(!$user){
                if($create){
                    $user = $this->createWechatUser($openid, $unionid, $inviteFromCode, $extra);
                }else{
                    throwError('指定用户不存在');
                }
            }else{
                $changed = false;
                if(!empty($unionid) && empty($user->unionid)){
                    $user->unionid = $unionid;
                    $changed = true;
                }
                if(empty($user->openid) && !empty($openid)){
                    $user->openid = $openid;
                    $changed = true;
                }
                if($changed){
                    $user->save();
                }
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();

        return $user;
    }

    /**根据openid获取用户信息
     * @param $openid
     * @return WdXcxUser|array|mixed|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserByOpenid($openid, $create=false, $inviteFromCode = '')
    {
        return $this->getUserByWechatIdentity($openid, '', $create, $inviteFromCode);
    }

    /**根据ID查询用户
     * @param $user_id
     * @return WdXcxUser|array|mixed|Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserById($user_id)
    {
        $user = $this->where('uniacid', $this->uniacid)->where('id', $user_id)->find();
        if(!$user){
            throwError('指定用户不存在');
        }
        return $user;
    }

    /**计算用户会员价
     * @param $user
     * @param $goods_price
     * @return array
     */
    public function getUserDiscountPrice($user, $goods_price)
    {
        $user->VipGradeInfo;
        $discount_price = $goods_price;
        $discount = 0;
        if($user->vip_grade){
            $user_grade = $user->grade;
            if($user_grade && $user_grade->discount_flag){
                $discount = $user_grade->discount_grade;
                $discount_price = bcmul($goods_price, bcdiv($discount, 10, 4), 2);
            }
        }
        return compact('discount_price', 'discount');
    }

    /**根据手机号码查用户
     * @param $key
     * @param $type
     * @return array
     */
    public function searchUserByMobile($key, $type='like')
    {
        $sql = $this->where('uniacid', $this->uniacid);
        if($type == 'like'){
            return $sql->whereLike('mobile', '%'.$key.'%')->column('id');
        }else{
            return $sql->where('mobile', $key)->value('id');
        }
    }

    /**根据昵称与手机号码关键词查询用户
     * @param $key
     * @param $field
     * @return array
     */
    public function searchUserByKey($key, $field=['nickname', 'mobile'])
    {
        $sql = $this->where('uniacid', $this->uniacid);
        if(in_array('nickname', $field)){
            $sql = $sql->whereLike('nickname', '%'.$key.'%');
            if(in_array('mobile', $field)){
                $sql = $sql->whereOr('mobile', 'like', '%'.$key.'%');
            }
            return $sql->column('id');
        }
        if(in_array('mobile', $field)){
            $sql = $sql->where('mobile', 'like', '%'.$key.'%');
        }
        return $sql->column('id');
    }

    /**获取展示的用户信息
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoShow($user_id)
    {
        $result = [
            'nickname' => '用户***',
            'avatar' => getLocalImage('/image/users/user_default.png'),
            'mobile' => '暂无',
            'company_name' => '',
            'company_logo' => '',
            'contact_mobile' => '',
            'contact_wechat' => '',
            'company_desc' => '',
            'is_show_home' => 0,
        ];
        $user = $this->where('id', $user_id)->find();
        if($user){
            $result = [
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'mobile' => $user->mobile,
                'company_name' => $user->company_name,
                'company_logo' => $user->company_logo,
                'contact_mobile' => $user->contact_mobile,
                'contact_wechat' => $user->contact_wechat,
                'company_desc' => $user->company_desc,
                'is_show_home' => $user->is_show_home,
            ];
        }
        return $result;
    }

    /**改变用户会员等级
     * @param $user \app\common\model\user\WdXcxUser 用户
     * @param $grade_level int 改变的会员等级
     * @param $end_time int 0 永久，-1 延长 其他 直接到期时间时间戳
     * @param $change_info string 改变日志
     * @param $add_time int  end_time -1 时 延迟的时间 天数
     * @return void
     */
    public function changeUserVipGrade($user, $grade_level, $end_time=0, $change_info='', $add_time=0)
    {
        $grade_info = $user->gradeInfo;
        if($end_time == 0){
            $grade_info->end_time = 0;
        }elseif ($end_time == -1){
            $grade_info->end_time = $grade_info->end_time == 0 ? strtotime(date('Y-m-d 23:59:59')) + $add_time * 86400 : $grade_info->end_time + $add_time * 86400;;
        }else{
            $grade_info->end_time = $end_time;
        }
        $grade_info->grade_level = $grade_level;
        $change_log = $grade_info->change_log ? $grade_info->change_log : [];
        $grade_info->change_log = array_merge($change_log, [
            [
                'change_time' => date('Y-m-d H:i:s'),
                'change_info' => $change_info
            ]
        ]);
        $grade_info->save();
        $vip_grade_cloud_size = WdXcxVipgrade::where('grade_level', $grade_level)->value('cloud_size');
        $user->vip_grade = $grade_level;
        $user->space_size = $vip_grade_cloud_size * 1024;
        $user->save();
    }

    public function getUuId()
    {
        do {
            // 生成8位数字UUID
            $characters = '0123456789';
            $code = rand(1, 9);
            for ($i = 0; $i < 7; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            // 检查数据库中是否已存在该字符串
            $exists = $this->where('user_uuid', $code)->find();

        } while ($exists); // 如果存在则重新生成

        return $code;
    }

    /**获取订单数量 判断是否为新客
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getHasOrderAttr()
    {
        $buy_grade = WdXcxUserBuyGradeOrderLists::where([
            'user_id' => $this->id,
            'status' => 2
        ])->count();
        return $buy_grade;
    }

    /**获取用户图片大小
     * @return string|null
     */
    public function getUserPicSizeAttr()
    {
        $total_size = WdXcxPic::where('uid', $this->id)
            ->sum('size');
        return bcdiv($total_size, 1024*1024, 2);
    }

    /**获取用户剩余图片大小
     * @return string|null  M
     */
    public function getUserCanUserPicSizeAttr()
    {
        $total_size = WdXcxPic::where('uid', $this->id)
            ->sum('size');
        $vip_grade_cloud_size = $this->VipGradeInfo['space_size'];
        return bcdiv($vip_grade_cloud_size * 1024*1024 - $total_size, 1024*1024, 2);
    }

    public function getTrueUploadSizeAttr()
    {
        if($this->VipGradeInfo){
            $info = $this->VipGradeInfo;
            if($info['upload_size_type'] == 1){
                return $info['upload_size'];
            }else{
                return $info['upload_size']*1024;
            }
        }else{
            return 200;
        }
    }








}
