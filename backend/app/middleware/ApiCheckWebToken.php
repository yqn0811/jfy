<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\model\user\WdXcxUser;
use app\common\service\JwtService;
use app\Request;
use think\facade\Config;

class ApiCheckWebToken
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next, $need_check=true)
    {
        //检查登录
        if($need_check){
            $result = JwtService::decryptToken();
            if(empty($result->user_id)){
                throwError('请先授权登录', Config::get('status.not_logged'));
            }
            $user = WdXcxUser::where('id', $result->user_id)->find();
            if(!$user){
                throwError('用户不存在，请先授权登录', Config::get('status.login_expired'));
            }
            $request->macro('userOpenid', function ()use($result){
                return empty($result->openid) ? '' : $result->openid;
            });
            $request->macro('uuId', function ()use($result){
                return empty($result->user_uuid) ? '' : $result->user_uuid;
            });
            $request->macro('userID', function ()use($result){
                return empty($result->user_id) ? '' : $result->user_id;
            });
            $request->macro('userInfo', function ()use($result){
                return empty($result->user_id) ? '' : $result->user_id;
            });
        }
        return $next($request);
    }
}
