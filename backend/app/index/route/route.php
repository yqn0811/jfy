<?php
use think\facade\Route;

Route::get('/', 'LoginController/login');

//图库相关
Route::group('pic', function(){
    Route::get('lists', 'index/PictureResource/getPicList'); //图片列表
    Route::get('groups', 'index/PictureResource/getPicgroup'); //分类列表
    Route::get('groupName', 'index/PictureResource/getGroupNames'); //分类名称
    Route::post('addGroup', 'index/PictureResource/addGroup'); //添加栏目
    Route::post('editGroup', 'index/PictureResource/editGroup'); //编辑栏目名称
    Route::post('delGroup', 'index/PictureResource/deleteGroup'); //删除栏目
    Route::post('movePic', 'index/PictureResource/movePictureGroup'); //批量移动图片所属栏目
    Route::post('delPics', 'index/PictureResource/deletePictures'); //批量删除图片
    Route::post('upImage', 'index/PictureResource/uploadImage'); //上传本地图片
    Route::post('remoteImage', 'index/PictureResource/getRemoteImage'); //获取网络图片
    Route::post('cutImage', 'index/PictureResource/saveCutImage'); //保存裁剪图片
    Route::post('rename', 'index/PictureResource/renamePicName'); //图片重命名
    Route::post('sortGroup', 'index/PictureResource/sortGroupByDrag'); //图片栏目拖拽排序
    Route::get('base', 'index/PictureResource/getPicBaseSet'); //获取图库基础设置
});

Route::any('user/consumption', 'index/WxuserController/getUserConsumption'); // 用户余额流水
Route::any('user/integral_record', 'index/WxuserController/getUserIntegralRecord'); // 用户积分流水
Route::any('user/diamond', 'index/WxuserController/getUserDiamondRecord'); // 用户钻石流水
Route::any('user/property', 'index/WxuserController/setUserProperty'); // 用户资产修改
Route::post('user/realname', 'index/WxuserController/changeUserRealname'); //修改用户真实姓名
Route::post('user/birth', 'index/WxuserController/changeUserBirthDay'); //修改用户生日
Route::post('user/vipgrade', 'index/WxuserController/saveUseVipgrade'); //修改用户会员等级信息
Route::any('user/play', 'index/WxuserController/getUsePlayRecord'); //修改用户游玩记录
Route::any('user/gamecoin', 'index/WxuserController/getUseGamecoinRecord'); //获取用户游戏币流水
Route::any('user/lottery', 'index/WxuserController/getUseLotteryRecord'); //获取用户彩票流水
Route::any('user/giveMoney', 'index/WxuserController/getUseGiveBalanceRecord'); //获取用户零钱流水
Route::any('user/commission', 'index/WxuserController/getUseCommissionRecord'); //获取用户佣金流水

Route::get('gamecoin/info', 'index/GamecoinController/getGamecoinPackageInfo'); //获取套餐内容
Route::post('gamecoin/save', 'index/GamecoinController/saveGamecoinPackageInfo'); //保存套餐内容

//会员等级列表
Route::get('user/vipgrade', 'index/WxuserController/vipGrade'); //会员等级列表