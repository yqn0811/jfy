<?php
declare (strict_types = 1);

namespace app\middleware;

use app\Request;

class ApiCheckSign
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next, bool $force = true)
    {
        $this->checkSign($request);
        return $next($request);
    }

    private function checkSign($request)
    {
        return true;
        $params = $request->post();
        if(empty($params['timestamp']) || empty($params['sign'])){
            throwError('验签参数不完整1');
        }
        $sign = $params['sign'];
        unset($params['sign']);
        $check_sign = $this->getSign($request, $params);
        if($check_sign != $sign){
            throwError('验签失败');
        }
    }

    /**获取签名
     * @param $request
     * @param $params
     * @return string
     */
    private function getSign($request, $params)
    {
        $user_openid = $request->userOpenid();
        foreach ($params as $k => $v){
            $params_string[$k] = $v;
        }
        ksort($params_string);
        $string = $this->formatBizQueryParaMap($params_string);
        $sign = strtoupper(md5(md5($user_openid).$string.md5($user_openid)));
//        dd($sign);
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
