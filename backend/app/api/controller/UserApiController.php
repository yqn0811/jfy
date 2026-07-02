<?php

namespace app\api\controller;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\service\bridge\JiafangyunEntitlementSyncService;
use app\common\service\user\UserService;
use app\common\service\WxService;
use think\facade\Cache;
use app\index\model\WdXcxPic;
use think\facade\Db;
use think\App;

class UserApiController extends ApiBaseController
{
    private $userService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->userService = new UserService($app);
    }

    /**微信用户获取openid
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getUserOpenid()
    {
        $params = $this->request->getMore([
            ['code', ''],
            ['distribution_id', 0],
            ['invite_code', ''],
        ]);
        $code = $params['code'];
        if(!$code){
            throwError('登录code不能为空');
        }
        $userInfo = $this->userService->getUserInfoByCode($code, $params['invite_code']);

        // 尝试自动绑定分销关系
        if(!empty($params['distribution_id']) && !empty($userInfo['_user_id'])){
            $userId = $userInfo['_user_id'];
            if($userId){
                $this->userService->checkAndBindDistribution($userId, $params['distribution_id']);
            }
        }
        unset($userInfo['_user_id']);

        return $this->result($userInfo);
    }

    /**用户绑定手机号码登录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPhone()
    {
        $params = $this->request->postMore([
            ['code', ''],
            ['openid', ''],
            ['distribution_id', 0],
        ]);
        if(empty($params['code']) || empty($params['openid'])){
            throwError('登录code不能为空');
        }
        $result = $this->userService->getUserPhone($params);

        // 尝试自动绑定分销关系
        if(!empty($params['distribution_id'])){
            $userId = WdXcxUser::where('openid', $params['openid'])->value('id');
            if($userId){
                $this->userService->checkAndBindDistribution($userId, $params['distribution_id']);
            }
        }

        $this->result($result);
    }

    /**获取用户展示二维码
     * @return void
     */
    public function getUserQrcode()
    {
        $ticket_id = $this->request->getMore([
            ['ticket_id', ''],
        ])['ticket_id'];
        $this->result($this->userService->getUserQrcodeInfo($ticket_id));
    }

    /**获取用户财产
     * @return void
     */
    public function getUserProperty()
    {
        $type = $this->request->getMore([
            ['type', 1],
        ])['type'];
        $this->result($this->userService->getUserProperty($type));
    }

    /**更新用户主页与资料
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserInfo()
    {
        $param = $this->request->postMore([
            ['nickname', null],
            ['avatar', null],
            ['openid', ''],
            ['wx_ewm', null],
            ['user_desc', null],
            ['upload_pwd', null],
            // 主页资料相关字段
            ['company_name', null],
            ['company_logo', null],
            ['company_desc', null],
            ['contact_mobile', null],
            ['contact_wechat', null],
            ['address_province', null],
            ['address_city', null],
            ['address_district', null],
            ['address_detail', null],
            ['is_show_home', null],
            ['gender', null],
            ['visit_no_need_nickname', null],
            ['visit_no_need_mobile', null],
            ['visit_allow_save_pic', null],
            ['home_watermark_text', null],
            ['home_service_name', null],
            ['home_share_title', null],
            ['home_share_desc', null],
            ['home_share_image', null],
            ['industry_info', null],
            ['latitude', null],
            ['longitude', null],
        ]);
        $this->userService->updateUserInfo($param);
        $this->result([], 0, '更新成功');
    }

    /**获取指定用户的卡券列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponLists()
    {
        $param = $this->request->postMore([
            ['type', 1],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserCouponLists($param));
    }

    /**获取指定用户的卡券列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponCount()
    {
        $this->result($this->userService->getUserCouponCount());
    }

    /**获取用户指定卡券核销二维码
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponQrcode()
    {
        $param = $this->request->postMore([
            ['cid', 0],
        ]);
        $this->result($this->userService->getUserCouponQrcode($param));
    }

    /**获取指定卡券核销规则
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponUseRule()
    {
        $param = $this->request->postMore([
            ['cid', 0],
        ]);
        $this->result($this->userService->getCouponUseRule($param, request()->userID()));
    }

    /**获取用户代金券列表
     * @return void
     */
    public function getUserCouponVoucher()
    {
        $param = $this->request->postMore([
            ['pay_price', 0],
        ]);
        $this->result($this->userService->getUserCouponVoucher($param, request()->userID()));
    }

    /**获取用户消费流水
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserBalanceRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
        ]);
        $this->result($this->userService->getUserBalanceRecord($param, request()->userID()));
    }

    public function getUserGiveBalanceRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
        ]);
        $this->result($this->userService->getUserGiveBalanceRecord($param, request()->userID()));
    }

    /**获取用户资产流水
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAssetRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
            ['change_type', 0],
        ]);
        $this->result($this->userService->getUserAssetRecord($param, request()->userID(), request()->leaguerID()));
    }


    /**查询消费状态
     * @return void
     */
    public function getUserQrcodeScanStatus()
    {
        $this->result($this->userService->getUserQrcodeScanStatus(request()->leaguerID()));
    }

    /**获取用户游玩记录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPlayRecord()
    {
        $param = $this->request->getMore([
            ['type', 0],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserPlayRecord($param, request()->userID()));
    }

    /**用户上传游戏记录凭证
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserPlayVoucher()
    {
        $param = $this->request->postMore([
            ['pid', 0],
            ['voucher_thumb', 0],
        ]);
        $this->userService->setUserPlayVoucher($param, request()->userID());
        $this->result([]);
    }

    /**用户兑换零钱
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exchangeUserBalance()
    {
        $param = $this->request->postMore([
            ['balance', 0],
        ]);
        $this->userService->exchangeUserBalance($param, request()->userID());
        $this->result([]);
    }

    /**用户扫码绑定分销商
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userBindDistribution()
    {
        $param = $this->request->postMore([
            ['distribution_id', 0],
            ['openid', 0],
        ]);
        $this->result($this->userService->userBindDistribution($param));
    }

    public function getUserShowInfo()
    {
        $uid = request()->userID();
        $syncedVipGradeInfo = (new JiafangyunEntitlementSyncService(app()))->syncUserQuietly($uid);
        $user = WdXcxUser::where('id', $uid)->find();
        if(!$user){
            throwError('用户不存在');
        }
        $vipGradeInfo = $user->VipGradeInfo;
        if (is_array($syncedVipGradeInfo)) {
            $vipGradeInfo = array_merge($vipGradeInfo, $syncedVipGradeInfo);
        }

        $result = [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => (int)$user->gender,
            'industry_info' => (int)$user->industry_info,
            'user_uuid' => $user->user_uuid,
            'is_new_user' => $user->HasOrder > 0 ? 0 : 1,
            'all_space' => 0,
            'space_used' => '0.00',
            'grade_name' => $vipGradeInfo['grade_name'],
            'grade_level' => $vipGradeInfo['grade_level'],
            'end_time' => $vipGradeInfo['end_time'],
            'join_time' => $user->join_time,
            'wx_ewm' => $user->wx_ewm,
            'user_desc' => $user->user_desc,
            'upload_pwd' => $user->upload_pwd,
            'id' => $user->id,
            'visit_no_need_nickname' => (int)$user->visit_no_need_nickname,
            'visit_no_need_mobile' => (int)$user->visit_no_need_mobile,
            'visit_allow_save_pic' => (int)$user->visit_allow_save_pic,
            'home_watermark_text' => $user->home_watermark_text,
            'home_service_name' => $user->home_service_name,
            'home_share_title' => $user->home_share_title,
            'home_share_desc' => $user->home_share_desc,
            'home_share_image' => $user->home_share_image,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ];
        if($vipGradeInfo['space_size'] > 1024 * 1024){
            $result['all_space'] = bcdiv($vipGradeInfo['space_size'], 1024 * 1024) . 'T';
        }elseif ($vipGradeInfo['space_size'] > 1024){
            $result['all_space'] = bcdiv($vipGradeInfo['space_size'], 1024) . 'G';
        }else{
            $result['all_space'] = $vipGradeInfo['space_size'] . 'M';
        }
        $UserPicSize = WdXcxPic::where('uid', $uid)->sum('size');
        $result['use_space'] = $UserPicSize;
        if($UserPicSize > 0 && $vipGradeInfo['space_size'] > 0){
            $result['space_used'] = bcmul(bcdiv($UserPicSize, $vipGradeInfo['space_size'] * 1024 * 1024, 4), 100, 2);
        }else{
            $result['space_used'] = 0;
        }
        $result['total_pics'] = WdXcxPic::where('uid', $uid)->count();
        $result['total_collects'] = WdXcxUserCollectPics::where('uid', $uid)->count();
        
        // Add statistics for My Page
        $result['pic_count'] = $result['total_pics'];
        $result['product_count'] = \app\common\model\album\WdXcxAlbumFolder::where('uid', $uid)->where('folder_type', 2)->count();
        $result['category_count'] = \app\common\model\album\WdXcxAlbumFolder::where('uid', $uid)->where('folder_type', 1)->count();
        $visitTable = Db::query("SHOW TABLES LIKE 'wd_xcx_user_visit_record'");
        if ($visitTable) {
            $result['view_count'] = \app\common\model\user\WdXcxUserVisitRecord::where('target_uid', $uid)->count();
            $result['visitor_count'] = \app\common\model\user\WdXcxUserVisitRecord::where('target_uid', $uid)->distinct(true)->field('uid')->select()->count();
        } else {
            $result['view_count'] = 0;
            $result['visitor_count'] = 0;
        }

        $this->result($result);
    }

    /**获取用户所有照片列表
     * @return void
     */
    public function getUserAllPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserAllPicsLists(request()->userID(), $param));
    }

    /**获取用户所有删除产品列表
     * @return void
     */
    public function getUserAllDeleteProductLists()
    {
        $params = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['key', '']
        ]);
        $this->result($this->userService->getUserAllDeleteProductLists(request()->userID(), $params));
    }

    /**用户恢复删除产品
     * @return void
     */
    public function userRestoreDeleteProducts()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
            ['product_ids', ''],
        ]);
        if(empty($param['pic_ids']) && empty($param['product_ids'])){
            throwError('请选择要恢复的产品');
        }
        $this->userService->userRestoreDeleteProducts($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除回收站产品
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDestroyDeleteProducts()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
            ['product_ids', ''],
        ]);
        if(empty($param['pic_ids']) && empty($param['product_ids'])){
            throwError('请选择要删除的产品');
        }
        $this->userService->userDestroyDeleteProducts($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户所有收藏照片列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllCollectPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserAllCollectPicsLists(request()->userID(), $param));
    }
    
    public function getUserShowAllCollectPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserShowAllCollectPicsLists(request()->userID(), $param));
    }

    /**用户删除收藏照片
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteCollectPics()
    {
        $param = $this->request->postMore([
            ['collect_ids', ''],
        ]);
        if(empty($param['collect_ids'])){
            throwError('请选择要删除的图片');
        }
        $this->userService->userDeleteCollectPics($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除我的照片
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteMyPics()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
        ]);
        if(empty($param['pic_ids'])){
            throwError('请选择要删除的图片');
        }
        $this->userService->userDeleteMyPics($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户收藏记录
     * @return void
     */
    public function getUserCollectRecords()
    {
        $param = $this->request->getMore([
            ['type', 'all'],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserCollectRecords(request()->userID(), $param));
    }

    /**添加收藏
     * @return void
     */
    public function addUserCollect()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->addUserCollect($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**取消收藏
     * @return void
     */
    public function cancelUserCollect()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->cancelUserCollect($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户访问记录
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserVisitRecords()
    {
        $param = $this->request->getMore([
            ['type', 'all'],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserVisitRecords(request()->userID(), $param));
    }

    /**获取访客记录
     * @return void
     */
    public function getUserVisitors()
    {
        $param = $this->request->getMore([
            ['page', 1],
        ]);
        $this->result($this->userService->getUserVisitors(request()->userID(), $param));
    }

    /**添加用户访问记录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addUserVisitRecord()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->addUserVisitRecord($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除访问记录
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteVisitRecord()
    {
        $param = $this->request->postMore([
            ['visit_ids', 0],
        ]);
        $this->userService->userDeleteVisitRecord($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**足迹文件夹列表
     * @return void
     */
    public function getVisitAlbumFolderLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getVisitAlbumFolderLists($param, request()->userID()));
    }

    public function getVisitAlbumPicLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getVisitAlbumPicLists($param, request()->userID()));
    }

    /**获取用户主页信息（公开）
     * @return void
     */
    public function getHomePageInfo()
    {
        $targetUserId = $this->request->getMore([
            ['target_user_id', 0],
        ])['target_user_id'];
        
        if (!$targetUserId) {
            throwError('参数错误');
        }

        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
            // ignore
        }
        
        $this->result($this->userService->getHomePageInfo($targetUserId, $visitorUid));
    }

    public function getHomeCategories()
    {
        $targetUserId = $this->request->getMore([
            ['target_user_id', 0],
        ])['target_user_id'];
        if (!$targetUserId) {
            throwError('参数错误');
        }
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $this->result($this->userService->getHomeCategories($targetUserId, $visitorUid));
    }

    public function getHomeProducts()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['cate_id', 0],
            ['product_id', 0],
        ]);
        $targetUserId = $params['target_user_id'];
        if (!$targetUserId) {
            throwError('参数错误');
        }
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        if (!empty($params['product_id'])) {
            $this->result($this->userService->getHomeProductsDetails($targetUserId, $params['product_id'], $visitorUid));
        }
        $this->result($this->userService->getHomeProducts($targetUserId, $visitorUid, $params['cate_id']));
    }

    public function getHomeProductsDetails()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['product_id', 0],
        ]);
        $targetUserId = $params['target_user_id'];
        $productId = $params['product_id'];
        if (!$targetUserId || !$productId) {
            throwError('参数错误');
        }
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $this->result($this->userService->getHomeProductsDetails($targetUserId, $productId, $visitorUid));
    }

    public function getHomeMiniProgramCode()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['path', ''],
        ]);
        $targetUserId = $params['target_user_id'];
        if (!$targetUserId) {
            throwError('参数错误');
        }
        $this->result($this->userService->getHomeMiniProgramCode($targetUserId, $params['path']));
    }

    public function getHomeShareLink()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['path', ''],
        ]);
        $targetUserId = $params['target_user_id'];
        if (!$targetUserId) {
            throwError('参数错误');
        }
        $this->result($this->userService->getHomeShareLink($targetUserId, $params['path']));
    }

    public function getHomeSharePoster()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['type', 'home'], // home, category, product
            ['id', 0], // category_id or product_id
            ['path', ''],
        ]);
        $targetUserId = $params['target_user_id'];
        if (!$targetUserId) {
            throwError('参数错误');
        }
        $this->result($this->userService->getHomeSharePoster($targetUserId, $params['type'], $params['id'], $params['path']));
    }

    public function getShortLink()
    {
        $param = $this->request->postMore([
            ['path', ''],
            ['title', ''],
            ['is_permanent', 0],
        ]);
        $this->result($this->userService->getShortLink($param));
    }

    /**
     * 获取PC端扫码登录二维码
     */
    public function getLoginQrcode()
    {
        // 1. 调用微信接口生成二维码
        try {
            $app = (new WxService(3))->getAppData();
            $scene = md5(uniqid(mt_rand(), true));
            $result = $app->qrcode->temporary($scene, 600);
            
            if (!isset($result['ticket'])) {
                $errMsg = isset($result['errmsg']) ? $result['errmsg'] : 'Unknown error from WeChat';
                if (isset($result['errcode'])) {
                    $errMsg .= ' (' . $result['errcode'] . ')';
                }
                throw new \Exception($errMsg);
            }

            $url = $app->qrcode->url($result['ticket']);
        } catch (\Exception $e) {
            $msg = '获取二维码失败';
            if ($e->getCode()) {
                $msg .= ' [code: ' . $e->getCode() . ']';
            }
            if ($e->getMessage()) {
                $msg .= ': ' . $e->getMessage();
            }
            throwError($msg);
        }

        // 2. 写入登录态缓存（失败不影响二维码返回）
        try {
            Cache::set('login_scene_' . $scene, 'pending', 600);
        } catch (\Throwable $e) {
        }

        // 3. 尝试转成 dataURL（失败也不阻断）
        $dataUrl = null;
        try {
            $img = @file_get_contents($url);
            if ($img !== false) {
                $dataUrl = 'data:image/png;base64,' . base64_encode($img);
            }
        } catch (\Throwable $e2) {
        }

        $this->result([
            'url' => $url,
            'data_url' => $dataUrl,
            'scene' => $scene
        ]);
    }

    /**
     * 微信扫码登录回调
     */
    public function wechatCallback()
    {
        $code = $this->request->param('code');
        $state = $this->request->param('state');
        
        if (!$code) {
            return $this->redirectWithError($state, '授权失败，未获取到code');
        }
        
        try {
            $app = (new WxService(3))->getAppData();
            $oauthUser = $app->oauth->user();
            $original = $oauthUser->getOriginal();
            
            $openid = $original['openid'];
            $unionid = $original['unionid'] ?? '';
            
            // 查找或创建用户
            $userModel = new \app\common\model\user\WdXcxUser();
            $user = $userModel->getUserByWechatIdentity($openid, $unionid, true, '', [
                'nickname' => $original['nickname'] ?? '微信用户',
                'avatar' => $original['headimgurl'] ?? '',
                'gender' => $original['sex'] ?? 0,
                'uniacid' => $this->uniacid,
            ]);
            
            // 生成Token
            $tokenData = [
                'user_id' => $user->id,
                'openid' => $user->openid,
                'user_uuid' => $user->user_uuid,
            ];
            $token = \app\common\service\JwtService::createToken($tokenData);
            
            // 重定向回前端
            $redirectUrl = $state ? base64_decode($state) : '/';
            if (empty($redirectUrl)) $redirectUrl = '/';
            
            $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == NULL) ? '?' : '&';
            $finalUrl = $redirectUrl . $separator . 'token=' . $token . '&login=success';
            
            return redirect($finalUrl);
            
        } catch (\Exception $e) {
            return $this->redirectWithError($state, '登录失败: ' . $e->getMessage());
        }
    }

    private function redirectWithError($state, $msg) {
        $redirectUrl = $state ? base64_decode($state) : '/';
        if (empty($redirectUrl)) $redirectUrl = '/';
        $separator = (parse_url($redirectUrl, PHP_URL_QUERY) == NULL) ? '?' : '&';
        return redirect($redirectUrl . $separator . 'error=' . urlencode($msg));
    }

    /**
     * 检查登录状态
     */
    public function checkLoginStatus()
    {
        $scene = $this->request->param('scene');
        if (!$scene) {
            throwError('参数错误');
        }
        
        $data = Cache::get('login_scene_' . $scene);
        
        if (!$data) {
            $this->result(['status' => 'expired']);
        } elseif ($data === 'pending') {
            $this->result(['status' => 'pending']);
        } else {
            // $data is the token
            $this->result(['status' => 'success', 'token' => $data]);
        }
    }

    /**
     * 测试账号登录 (userid=19)
     */
    
    public function testLogin()
    {
        $userId = 19;
        $user = WdXcxUser::where('id', $userId)->find();
        
        if (!$user) {
            throwError('测试用户不存在');
        }

        $tokenData = [
            'user_id' => $user->id,
            'openid' => $user->openid,
            'user_uuid' => $user->user_uuid,
        ];
        $token = \app\common\service\JwtService::createToken($tokenData);
        
        $this->result([
            'token' => $token,
            'user_info' => $user
        ]);
    }

    private function ensureFeedbackTable()
    {
        $tableExists = Db::query("SHOW TABLES LIKE 'wd_xcx_album_feedback'");
        if (!$tableExists) {
            Db::execute("
                CREATE TABLE `wd_xcx_album_feedback` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `uid` int(11) NOT NULL DEFAULT 0,
                  `factory_uid` int(11) NOT NULL DEFAULT 0,
                  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1:问题反馈 2:功能建议',
                  `content` text NOT NULL,
                  `images` text COMMENT '图片列表',
                  `contact` varchar(255) DEFAULT '' COMMENT '联系方式',
                  `create_time` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `idx_uid` (`uid`),
                  KEY `idx_factory_uid` (`factory_uid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈';
            ");
        } else {
            // 检查并补充字段
            $fields = Db::query("SHOW COLUMNS FROM `wd_xcx_album_feedback`");
            $fieldNames = array_column($fields, 'Field');
            
            if (!in_array('type', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1:问题反馈 2:功能建议' AFTER `factory_uid`");
            }
            if (!in_array('images', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `images` text COMMENT '图片列表' AFTER `content`");
            }
            if (!in_array('contact', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `contact` varchar(255) DEFAULT '' COMMENT '联系方式' AFTER `images`");
            }
        }
    }

    public function feedback()
    {
        $param = $this->request->postMore([
            ['type', 1],
            ['content', ''],
            ['images', []],
            ['contact', ''],
        ]);
        
        if ($param['content'] === '') {
            throwError('请输入反馈内容');
        }

        $images = $param['images'];
        if (is_array($images)) {
            $images = json_encode($images, JSON_UNESCAPED_UNICODE);
        }

        $this->ensureFeedbackTable();
        Db::name('wd_xcx_album_feedback')->insert([
            'uid' => request()->userID(),
            'type' => $param['type'],
            'content' => $param['content'],
            'images' => $images,
            'contact' => $param['contact'],
            'create_time' => time(),
        ]);
        $this->result([], 0, '提交成功');
    }
}
