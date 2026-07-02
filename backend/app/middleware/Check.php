<?php
declare (strict_types = 1);

namespace app\middleware;

class Check
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $params = $request->param();
        $newParams = array_merge($params, ['appletid' => 1, 'uniacid' => 1]);
        if($request->isGet()){
            $newRequest = $request->withGet($newParams);
        }else{
            $newRequest = $request->withPost($newParams);
        }
        //
        return $next($newRequest);
    }
}
