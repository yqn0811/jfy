<?php

namespace app\common\service;

use cores\utils\HasHttpRequest;
use EasyWeChat\Factory;
use think\facade\Config;

class WxService
{
    use HasHttpRequest;

    const NOTIFY_URL = '/api/pay/callback';
    private function ensureDir($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }

    private $app_type = 1;
    public function __construct($app_type=1)
    {
        $this->app_type = $app_type;
    }

    private function getApp()
    {
        if($this->app_type == 1){ //小程序
            $config = [
                'app_id' => Config::get('miniprogram.appid'),
                'secret' => Config::get('miniprogram.appsecret'),
            ];
            return Factory::miniProgram($config);
        }
        if($this->app_type == 2){ //小程序支付
            $config = [
                'app_id' => Config::get('miniprogram.appid'),
                'mch_id' => Config::get('miniprogram.mchid'),
                'key' => Config::get('miniprogram.signkey_v2'),
                'notify_url' => $this->getPayNotifyUrl(),
            ];
            return Factory::payment($config);
        }
        if($this->app_type == 3){ //公众号
            $config = [
                'app_id' => Config::get('miniprogram.account_appid'),
                'secret' => Config::get('miniprogram.account_appsecret'),
                'token' => Config::get('miniprogram.account_token'),
                'aes_key' => Config::get('miniprogram.account_aes_key'),
                'response_type' => 'array',
            ];
            return Factory::officialAccount($config);
        }
        if($this->app_type == 4){ //公众号支付
            $config = [
                'app_id' => Config::get('miniprogram.account_appid'),
                'mch_id' => Config::get('miniprogram.account_mchid'),
                'key' => Config::get('miniprogram.account_key_v2'),
                'notify_url' => $this->getPayNotifyUrl(),
            ];
            return Factory::payment($config);
        }

    }

    public function getPayInfo($order_info)
    {
        $payments = $this->getApp()->order->unify([
            'body' => $order_info['body'],
            'out_trade_no' => $order_info['order_id'],
            'total_fee' => $order_info['pay_price'],
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $order_info['openid'],
        ]);
        if(isset($payments['result_code']) && isset($payments['return_code']) && $payments['result_code'] == 'SUCCESS' && $payments['return_code'] == 'SUCCESS'){
            $json = $this->getApp()->jssdk->bridgeConfig($payments['prepay_id']);
            return json_decode($json, true);
        }else{
            $msg = isset($payments['err_code_des']) ? $payments['err_code_des'] : '';
            $msg = $msg ? $msg : (isset($payments['return_msg']) ? $payments['return_msg'] : '获取支付信息失败');
            throwError($msg);
        }
        return $result;
    }

    public function getAppData()
    {
        return $this->getApp();
    }

    public function getPayCallBackInfo()
    {
        return $this->getApp();
    }

    /**获取用户openid
     * @param $code
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \cores\exception\BaseException
     */
    public function getUserOpenid($code)
    {
//        $result = [
//            'openid' => 'oCzgA7RnL28lGwTP5bK1Nt09_wfU',
//        ];
        $this->getApp()->access_token->getToken();
        $result = $this->getApp()->auth->session($code);
        if(!empty($result['errcode'])){
            throwError($result['errmsg']);
        }
        return $result;
    }

    public function getAccessToken()
    {
        $access_token = $this->getApp()->access_token->getToken();
        return $access_token['access_token'];
    }

    /**获取用户绑定的手机号码
     * @param $code
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function getUserPhone($code)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber';
        $data = [
            'code' => $code
        ];
        $result = $this->postJson($url, $data, ['access_token' => $this->getAccessToken()]);
        if(!empty($result['errcode'])){
            throwError($result['errmsg']);
        }
        return  $result['phone_info']['purePhoneNumber'];
    }

    public function generateShortLink($pageUrl, $pageTitle = '', $isPermanent = false)
    {
        $url = 'https://api.weixin.qq.com/wxa/genwxashortlink';
        $data = [
            'page_url' => $pageUrl,
            'is_permanent' => (bool)$isPermanent,
        ];
        if ($pageTitle !== '') {
            $data['page_title'] = $pageTitle;
        }
        $result = $this->postJson($url, $data, ['access_token' => $this->getAccessToken()]);
        if (!empty($result['errcode']) && $result['errcode'] != 0) {
            $msg = isset($result['errmsg']) ? $result['errmsg'] : '获取短链失败';
            throwError($msg);
        }
        if (empty($result['link'])) {
            throwError('获取短链失败');
        }
        return $result['link'];
    }

    /**生成小程序二维码
     * @param $data
     * @return void
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function getWxQrcode($data)
    {
        $result = $this->getApp()->app_code->get($data['path'], [
            'width' => 100,
        ]);
        if ($result instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $this->ensureDir($data['filepath']);
            $result->save($data['filepath'], $data['filename']);
        }else{
            $msg = is_array($result) ? ($result['errmsg'] ?? '生成二维码失败') : '生成二维码失败';
            throwError($msg);
        }
    }

    /**不限制小程序码
     * @param $data
     * @return string|void
     * @throws \cores\exception\BaseException
     */
    public function getUnlimitQrcode($data)
    {
        $options = [];
        if (!empty($data['path'])) {
            $options['page'] = ltrim($data['path'], '/');
        }
        $options['check_path'] = false;
        $result = $this->getApp()->app_code->getUnlimit($data['scene'], $options);
        if ($result instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $this->ensureDir($data['filepath']);
            $result->save($data['filepath'], $data['filename']);
        }else{
            $msg = is_array($result) ? ($result['errmsg'] ?? '生成二维码失败') : '生成二维码失败';
            throwError($msg);
        }
    }

    /**回调地址
     * @return string
     */
    private function getPayNotifyUrl()
    {
        return 'https://'. $_SERVER['HTTP_HOST'] . self::NOTIFY_URL;
    }
}
