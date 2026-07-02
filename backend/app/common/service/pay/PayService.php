<?php

namespace app\common\service\pay;

use app\common\service\BaseService;
use app\common\service\WxService;
use think\App;
use think\facade\Config;
use think\Log;

class PayService extends BaseService
{

    const PAY_URL = 'https://openapi.tianquetech.com';
    const NOTIFY_URL = '/api/pay/callback';

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function getPayInfo($order_info)
    {
        return (new WxService(2))->getPayInfo($order_info);
    }

    /**订单退款
     * @param $order_info
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function refund($order_info)
    {
        require_once __DIR__.'/../pay/tianque/AopClient.php';
        $aopClient = new \AopClient();
        $data = [
            'mno' => Config::get('pay_config.tianque.mno'),
            'ordNo' => $order_info['refund_order_id'],
            "amt" => $order_info['refund_price'], //订单总金额
            "origOrderNo" => $order_info['origOrderNo'],
        ];

        $reqBean = [
            "orgId" => Config::get('pay_config.tianque.orgId'),
            "reqData"=> $data,
            "reqId" => time(),
            "signType" => "RSA",
            "timestamp" => date("Y-m-d h:i:s"),
            "version" => "1.0",
        ];
        $signContent = $aopClient->generateSign($reqBean, Config::get('pay_config.tianque.private_key'));
        $sign = ["sign" => $signContent];
        $request_data = array_merge($reqBean, $sign);
        $request_data = json_encode($request_data,320);
        $result = $aopClient->curl(self::PAY_URL.'/order/refund', $request_data);
        \think\facade\Log::info('退款结果:'.$result);
        $result = json_decode($result,320);
        if(!($result['code'] == '0000' && $result['respData']['bizCode'] == '0000')){
            $msg = empty($result['msg']) ? '申请退款失败' : $result['msg'];
            throwError($msg);
        }
    }

    private function get_client_ip() {
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
    }

    /**扩展验证字段
     * @param $openid
     * @param $order_id
     * @return string
     */
    private function createExtend($openid, $order_id)
    {
        return strtoupper(md5(substr($openid, 0, 10).substr($order_id, 0, 10)));
    }




    protected function createNoncestr($length = 32) {

        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";

        $str = "";

        for ($i = 0; $i < $length; $i++) {

            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);

        }

        return $str;

    }

    //作用：生成签名

    protected function getSign($Obj) {

        foreach ($Obj as $k => $v) {

            $Parameters[$k] = $v;

        }

        //签名步骤一：按字典序排序参数

        ksort($Parameters);

        $String = $this->formatBizQueryParaMap($Parameters, false);

        //签名步骤二：在string后加入KEY

        $String = $String . "&key=" . Config::get('miniprogram.signkey_v2');

        //签名步骤三：MD5加密

        $String = md5($String);

        //签名步骤四：所有字符转为大写

        $result_ = strtoupper($String);

        return $result_;

    }



    ///作用：格式化参数，签名过程需要使用

    protected function formatBizQueryParaMap($paraMap, $urlencode) {

        $buff = "";

        ksort($paraMap);

        foreach ($paraMap as $k => $v) {

            if ($urlencode) {

                $v = urlencode($v);

            }

            $buff .= $k . "=" . $v . "&";

        }

        $reqPar = '';

        if (strlen($buff) > 0) {

            $reqPar = substr($buff, 0, strlen($buff) - 1);

        }

        return $reqPar;

    }

    //数组转换成xml

    protected function arrayToXml($arr) {

        $xml = "<root>";

        foreach ($arr as $key => $val) {

            if (is_array($val)) {

                $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";

            } else {

                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            }

        }

        $xml .= "</root>";

        return $xml;

    }

    //xml转换成数组

    protected function xmlToArray($xml) {

        //禁止引用外部xml实体

        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;

    }

    private function postXmlCurl($xml, $url, $second = 30)

    {

        $ch = curl_init();

        //设置超时

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验

        //设置header

        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        //要求结果为字符串且输出到屏幕上

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        //post提交方式

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        curl_setopt($ch, CURLOPT_TIMEOUT, 40);

        set_time_limit(0);

        //运行curl

        $data = curl_exec($ch);

        //返回结果

        if ($data) {

            curl_close($ch);

            return $data;

        } else {

            $error = curl_errno($ch);

            curl_close($ch);

            throw new WxPayException("curl出错，错误码:$error");

        }

    }
}