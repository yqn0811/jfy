<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\model\user\WdXcxUser;
use app\common\service\JwtService;
use app\Request;
use think\facade\Config;

class ApiCheckToken
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
        // 检查登录
        if ($need_check) {
            // 必须有 token，失败直接抛错
            $result = JwtService::decryptToken();
            if (empty($result->openid) || empty($result->user_id)) {
                throwError('请先授权登录', Config::get('status.not_logged'));
            }
            $user = WdXcxUser::where('openid', $result->openid)->find();
            if (!$user) {
                throwError('用户不存在，请先授权登录', Config::get('status.login_expired'));
            }
        } else {
            // 可选 token，有就解析，没有或无效就当未登录
            $result = JwtService::tryDecryptToken();
            if (empty($result) || empty($result->user_id)) {
                return $next($request);
            }
            $user = WdXcxUser::where('openid', $result->openid)->find();
            if (!$user) {
                return $next($request);
            }
        }

        // 设置请求辅助方法（两种模式下只要解析成功都挂到 request 上）
        $request->macro('userOpenid', function () use ($result) {
            return empty($result->openid) ? '' : $result->openid;
        });
        $request->macro('uuId', function () use ($result) {
            return empty($result->user_uuid) ? '' : $result->user_uuid;
        });
        $request->macro('userID', function () use ($result) {
            return empty($result->user_id) ? '' : $result->user_id;
        });
        $request->macro('userInfo', function () use ($result) {
            return empty($result->user_id) ? '' : $result->user_id;
        });
        return $next($request);
    }
}
