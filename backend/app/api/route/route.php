<?php
use think\facade\Route;

Route::group('', function () {
    Route::get('index', 'ApiController/index');
})->middleware('sign', false);

// 省市区
Route::get('region/tree', 'RegionApiController/getRegionTree');

Route::get('/debug/php', function () {
    return json([
        'version' => phpversion(),
        'sapi' => php_sapi_name(),
    ]);
});

Route::get('check_tables', 'CheckTablesController/check');

Route::get('check_columns', 'CheckColumnsController/check');

Route::get('/user/openid', 'UserApiController/getUserOpenid'); // 获取用户openid
Route::get('/user/home/info', 'UserApiController/getHomePageInfo')->middleware('auth', false); // 获取用户主页信息（可选登录）
Route::get('/user/home/categories', 'UserApiController/getHomeCategories')->middleware('auth', false); // 主页分类列表（公开，可选登录）
Route::get('/user/home/products', 'UserApiController/getHomeProducts')->middleware('auth', false); // 主页产品列表（公开，可选登录）
Route::get('/user/home/products/detail', 'UserApiController/getHomeProductsDetails')->middleware('auth', false); // 主页产品详情（公开，可选登录）
Route::get('/user/home/products/details', 'UserApiController/getHomeProductsDetails')->middleware('auth', false); // 主页产品详情别名（兼容）
Route::get('/user/home/picture/detail', 'UserApiController/getHomePictureDetail')->middleware('auth', false); // 主页图片详情（公开，可选登录）
Route::any('/wechat/serve', 'WechatController/serve'); // 微信公众号服务器回调
Route::any('/wechat/push', 'WechatController/serve'); // 微信消息推送回调（配置用）

Route::get('/user/home/minicode', 'UserApiController/getHomeMiniProgramCode')->middleware('auth', false); // 主页小程序码（公开，可选登录）
Route::get('/user/home/share_link', 'UserApiController/getHomeShareLink')->middleware('auth', false); // 主页复制链接（公开，可选登录）
Route::get('/user/home/share_poster', 'UserApiController/getHomeSharePoster')->middleware('auth', false); // 主页生成海报（公开，可选登录）
Route::post('/user/phone', 'UserApiController/getUserPhone'); //获取用户手机号码
Route::get('/user/login/qrcode', 'UserApiController/getLoginQrcode'); // 获取登录二维码
Route::get('/user/login/oauth_config', 'UserApiController/getLoginOauthConfig'); // PC网页登录配置
Route::get('/user/login/callback', 'UserApiController/wechatCallback'); // 微信登录回调
Route::get('/user/login/status', 'UserApiController/checkLoginStatus'); // 检查登录状态
Route::get('/user/testLogin', 'UserApiController/testLogin'); // 测试登录
Route::post('/user/update_info', 'UserApiController/updateUserInfo'); //更新主页信息 = 用户信息
Route::post('/user/update_pc_settings', 'UserApiController/updatePcSettings')->middleware('auth'); // PC端更新主页设置

//公共相关
Route::group('common', function (){
    Route::post('upload', 'CommonApiController/uploadImage')->middleware('auth'); //上传图片
    Route::get('index', 'CommonApiController/indexBaseInfo'); //首页基础信息
    Route::get('base', 'CommonApiController/commonBaseInfo'); //公共基础信息
    Route::get('workbench/menu', 'CommonApiController/getWorkbenchMenu');
    Route::get('visit', 'CommonApiController/commonVisitRecord'); //访问记录
    Route::post('newgift', 'CommonApiController/getUserNewGiftLists'); //获取新礼包列表
    Route::get('newgift', 'CommonApiController/receiveUserNewGiftLists')->middleware('auth'); //领取新礼包
    Route::get('ewm', 'CommonApiController/getEwmImage'); //二维码图片直链
    Route::get('static', 'CommonApiController/getStaticImage'); //静态图片直链
    Route::get('image_proxy', 'CommonApiController/getExternalImageProxy'); //资源库图片代理
    Route::get('resource_image', 'CommonApiController/getResourceImage'); //资源库图片动态签名代理
    //示例相册
    Route::get('example/folder', 'CommonApiController/getExampleFolderLists')->middleware('auth'); //获取相册文件夹
    Route::get('example/pictures', 'CommonApiController/getExamplePicturesLists')->middleware('auth'); //获取相册图片列表
    Route::post('example/set', 'CommonApiController/saveetExampleFolderSet')->middleware('auth')->middleware('sign'); //保存相册设置

    //工具
    Route::get('tool/update_user_table', 'UpdateUserTableTool/index'); // Temporary
});

//用户相关

Route::group('user', function (){
    Route::get('visit/records', 'UserApiController/getUserVisitRecords'); //足迹列表
    Route::get('visitors', 'UserApiController/getUserVisitors'); //访客记录
    Route::post('visitors/read', 'UserApiController/markUserVisitorsRead'); //标记浏览记录已读
    Route::get('collect/records', 'UserApiController/getUserCollectRecords'); //收藏列表

    Route::post('add/collect', 'UserApiController/addUserCollect'); //添加收藏
    Route::post('cancel/collect', 'UserApiController/cancelUserCollect'); //取消收藏

    Route::post('feedback', 'UserApiController/feedback'); //提交反馈

    Route::get('recycle/list', 'UserApiController/getUserAllDeleteProductLists');
    Route::post('restore/product', 'UserApiController/userRestoreDeleteProducts'); //还原回收站产品
    Route::post('destroy/product', 'UserApiController/userDestroyDeleteProducts'); //删除回收站产品

    Route::post('delete/pic', 'UserApiController/userDeleteMyPics'); //用户删除我的照片
    Route::post('add/visit', 'UserApiController/addUserVisitRecord'); //用户访问图片记录
    Route::post('del/visit', 'UserApiController/userDeleteVisitRecord'); //用户删除访问图片记录

    Route::post('visit/pics', 'UserApiController/getVisitAlbumPicLists'); //获取足迹相册图片列表
    Route::post('visit/folder', 'UserApiController/getVisitAlbumFolderLists'); //获取足迹相册文件夹

    Route::post('shortlink', 'UserApiController/getShortLink');


})->middleware('auth')->middleware('sign');
Route::post('feedback', 'UserApiController/feedback')->middleware('auth')->middleware('sign');
Route::post('user/bind/distribution', 'UserApiController/userBindDistribution'); //用户绑定分销上级
Route::get('user/scan_status', 'UserApiController/getUserQrcodeScanStatus')->middleware('auth'); //获取用户二维码扫码状态
Route::get('user/play/record', 'UserApiController/getUserPlayRecord')->middleware('auth'); //获取用户游玩记录
Route::get('user/show_info', 'UserApiController/getUserShowInfo')->middleware('auth'); //获取用户信息
Route::get('user/collect', 'UserApiController/getUserAllCollectPicsLists')->middleware('auth'); //获取用户所有收藏的图片
Route::get('user/pic/collect', 'UserApiController/getUserShowAllCollectPicsLists')->middleware('auth'); 

//分销相关
Route::group('distribution', function (){
    Route::post('statistics', 'DistributionApiController/getUserDistributionData'); //获取分销用户统计数据
    Route::post('orders', 'DistributionApiController/getUserDistributionOrderLists'); //获取分销订单列表
    Route::post('sub_transfer', 'DistributionApiController/getUserDistributionSubmitWithdrawal'); //提交分销提现
})->middleware('auth')->middleware('sign');
Route::get('distribution/junior', 'DistributionApiController/getUserDistributionJunior')->middleware('auth'); //获取分销用户下级
Route::get('distribution/base_withdrawal', 'DistributionApiController/getUserDistributionWithdrawalInfo')->middleware('auth'); //提现页面基础信息
Route::get('distribution/list_withdrawal', 'DistributionApiController/getUserDistributionWithdrawalLists')->middleware('auth');; //获取分销提现记录
Route::get('distribution/qrcode', 'DistributionApiController/getUserDistributionQrcode')->middleware('auth');; //获取分销分享二维码

//排行
Route::get('ranking/games', 'CommonApiController/getSystemGameLists'); //获取游戏名称数据
Route::get('play/ranking', 'CommonApiController/getUserPlayRankingLists'); //获取用户游戏排行

//会员
Route::group('grade', function (){
    Route::get('lists', 'VipgradeApiController/getVipgradeLists'); //会员等级列表
    Route::group('', function (){
        Route::post('order/create', 'VipgradeApiController/createUserByVipgradeOrder'); //创建会员等级购买订单
    })->middleware('auth')->middleware('sign');
});

//充值套餐相关
Route::group('recharge', function (){
    Route::get('lists', 'RechargeApiController/getRechargeList'); //获取充值套餐列表
    Route::group('', function (){
        Route::post('order/create', 'RechargeApiController/createRechargeOrder'); //创建充值订单
        Route::post('order/status', 'RechargeApiController/checkPayStatus'); //查询订单支付状态
    })->middleware('auth')->middleware('sign');;
});

// 网页端统一套餐/扫码支付，走 C/Go 订单与支付，不影响小程序 JSAPI 支付
Route::group('web_payment', function (){
    Route::get('subscription/plans', 'WebPaymentApiController/getSubscriptionPlans'); //网页会员套餐
    Route::get('points/recharge-plans', 'WebPaymentApiController/getRechargePlans'); //网页积分充值套餐
    Route::post('membership/order/create', 'WebPaymentApiController/createMembershipOrder')->middleware('auth'); //网页会员扫码订单
    Route::post('recharge/order/create', 'WebPaymentApiController/createRechargeOrder')->middleware('auth'); //网页充值扫码订单
    Route::get('order/status', 'WebPaymentApiController/getOrderStatus')->middleware('auth'); //网页订单状态
    Route::get('orders', 'WebPaymentApiController/getOrderList')->middleware('auth'); //网页订单列表
});

//积分签到相关
Route::group('integral', function (){
    Route::get('index', 'IntegralSignApiController/index'); //积分签到首页
    Route::post('sign', 'IntegralSignApiController/sign'); //执行签到
    Route::get('records', 'IntegralSignApiController/integralRecords'); //用户积分明细
    Route::get('invite', 'IntegralSignApiController/inviteIndex'); //邀请首页
    Route::get('invite/list', 'IntegralSignApiController/inviteList'); //邀请记录
    Route::post('task/finish', 'IntegralSignApiController/finishTask'); //完成任务
})->middleware('auth')->middleware('sign');

//订单相关
Route::group('order', function (){
    Route::get('pay_info', 'OrderController/getOrderPayInfo'); //获取订单支付信息
    Route::get('lists', 'OrderApiController/getUserAllOrderLists')->middleware('auth'); // 获取用户所有订单列表
});


Route::any('pay/callback', 'PayNotifyController/orderNotify'); //支付回调通知




//相册相关
Route::group('album', function (){
    Route::post('lists/folder', 'AlbumApiController/getAlbumFolderLists'); //获取分类、产品列表
    Route::post('create/folder', 'AlbumApiController/createAlbumFolder'); //创建分类、产品
    Route::post('edit/folder', 'AlbumApiController/editAlbumFolder'); //编辑分类、产品
    Route::post('set/folder/sort', 'AlbumApiController/setAlbumSort'); //分类拖拽排序
    Route::post('products/detail', 'AlbumApiController/getProductDetail'); //获取所有产品（用于选择）

    Route::post('products/all', 'AlbumApiController/getAllProducts'); //获取所有产品（用于选择）
    Route::post('category/add_products', 'AlbumApiController/addProductsToCategory'); //分类添加现有产品
    Route::post('product/add_categories', 'AlbumApiController/addProductToCategories'); //产品添加到多个分类
    Route::post('product/update_status', 'AlbumApiController/updateProductStatus'); //产品状态设置（热门等）
    Route::get('ai/resources', 'AlbumApiController/listAiResources'); // 我的资源库图片列表
    Route::post('ai/import_resource', 'AlbumApiController/importAiResource'); // 我的资源库图片导入产品
    Route::get('batch_link', 'AlbumApiController/getBatchUploadLink'); //获取大批量上传链接
    Route::post('reset_batch_link', 'AlbumApiController/resetBatchUploadLink'); //重置大批量上传链接
    Route::post('batch_upload_password', 'AlbumApiController/saveBatchUploadPassword'); //保存大批量上传入口和密码
    
    Route::post('delete/folder', 'AlbumApiController/deleteAlbumFolder'); //删除相册文件夹
    Route::post('delete/category', 'AlbumApiController/deleteCategory'); //删除分类
    Route::post('show/folder', 'AlbumApiController/getShowAlbumFolderLists'); //获取相册文件夹 不分页 带收藏
    Route::post('report/folder', 'AlbumApiController/userReportAlbumFolder'); //用户举报相册
    Route::post('lists/pics', 'AlbumApiController/getAlbumPicLists'); //获取相册图片列表

    Route::post('all/pics', 'AlbumApiController/getAlbumOnlyPicLists'); //获取相册内所有图片列表

    // Route::post('visit/folder', 'AlbumApiController/userVisitAlbum')->middleware('auth')->middleware('sign'); //用户访问相册
    Route::post('share/folder', 'AlbumApiController/userShareAlbum')->middleware('auth')->middleware('sign'); //用户分享相册
    Route::post('upload/folder', 'AlbumApiController/userUploadFileAlbum')->middleware('auth'); //用户上传内容到相册
    Route::post('set_top/pic', 'AlbumApiController/userChangePicSetTop')->middleware('auth')->middleware('sign'); //用户上传内容到相册
    Route::post('set_top/product', 'AlbumApiController/userChangeProductSetTop')->middleware('auth')->middleware('sign'); //产品置顶/取消置顶
    Route::post('delete/pic', 'AlbumApiController/userDeletePic')->middleware('auth')->middleware('sign'); //用户上传内容到相册
    Route::post('update/folder', 'AlbumApiController/userUpdateFolderSet')->middleware('auth')->middleware('sign'); //用户上传内容到相册
    Route::post('move/pics', 'AlbumApiController/userMovePictures')->middleware('auth')->middleware('sign'); //用户将图片收藏或添加到相册
    
    Route::post('pics/rename', 'AlbumApiController/userRenamePicture')->middleware('auth')->middleware('sign');

    
    Route::post('pics/beizhu', 'AlbumApiController/userSavePictureBeizhu')->middleware('auth')->middleware('sign'); //用户修改图片备注
    Route::post('pics/rename', 'AlbumApiController/userRenamePicture')->middleware('auth')->middleware('sign');
    Route::post('reset/share', 'AlbumApiController/userResetShareLink')->middleware('auth')->middleware('sign'); //用户重置分享链接
    Route::post('pwd/folder', 'AlbumApiController/changeFolderShowPwd')->middleware('auth')->middleware('sign'); //设置文件密码
    Route::post('user/visit/pwd', 'AlbumApiController/userVisitFolderByPwd')->middleware('auth')->middleware('sign'); //用户使用密码访问相册

    Route::post('selection/create', 'SelectionApiController/createSelection');
    Route::post('selection/detail', 'SelectionApiController/getSelectionDetail');
    Route::post('selection/my_lists', 'SelectionApiController/getMySelectionLists');
    Route::post('selection/customer_lists', 'SelectionApiController/getCustomerSelectionLists');
    Route::post('selection/update', 'SelectionApiController/updateSelection');
    Route::post('selection/add_images', 'SelectionApiController/addSelectionImages');
    Route::post('selection/remove_images', 'SelectionApiController/removeSelectionImages');
    Route::post('selection/delete', 'SelectionApiController/deleteSelection');
    Route::post('product/categories', 'AlbumApiController/getProductCategories'); //获取产品所属分类

    Route::post('recycle/lists', 'AlbumApiController/getRecycleBinLists'); //回收站列表
    Route::post('recycle/restore', 'AlbumApiController/restoreAlbum'); //恢复回收站
    Route::post('recycle/delete', 'AlbumApiController/deleteForever'); //彻底删除
})->middleware('auth')->middleware('sign');

//口碑
Route::get('cate/evaluate', 'AlbumApiController/getEvaluateCateLists'); //获取口碑分类(带数量)
Route::get('scate/evaluate', 'AlbumApiController/getEvaluateCate'); //获取口碑分类(不带数量)
Route::post('submit/evaluate', 'AlbumApiController/userSubmitEvaluateInfo')->middleware('auth')->middleware('sign');//用户提口碑
Route::get('submit/evaluate', 'AlbumApiController/getUserSubmitEvaluate');//获取用户提口碑

//公众号相关
Route::group('account', function (){
    Route::get('login', 'AccountApiController/getUserAuthInfo');
    Route::post('order/create', 'AccountApiController/createUserOrder');
    Route::post('init', 'AccountApiController/initAccountConfig');
});

//PC上传相关
Route::group('web', function (){
    Route::get('upload', 'WebUploadApiController/getAlbumInfo'); //根据链接获取文件夹信息
    Route::post('token/upload', 'WebUploadApiController/getAlbumUploadToken'); //根据上传token
    Route::get('folder/upload', 'WebUploadApiController/getUploadFolderInfo')->middleware('web'); //获取上传文件夹信息
    Route::post('folder/pic/upload', 'WebUploadApiController/userUploadFolderPic')->middleware('web'); //上传图片
});
