<?php


namespace app\common\service;
use app\BaseController;
use app\common\model\user\WdXcxUser;
use think\App;

class BaseService extends BaseController
{
    protected $uniacid = 1;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**从token中获取用户信息
     * @return WdXcxUser|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserInfo()
    {
        $header = request()->header();
        if(isset($header['authorization-token']) || isset($header['authorization'])){
            $result = JwtService::decryptToken();
            if(!empty($result->user_id)){
                return WdXcxUser::where('id', $result->user_id)->find();
            }
        }
        return null;
    }

    /**获取时间区间
     * @param $date_type int 1 今天，2昨天，3近7天，4自定义
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getStartAndEndTime($date_type=1, $start_time='', $end_time='')
    {
        if($date_type == 1){
            $start_time = strtotime(date('Y-m-d 00:00:00'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
        }elseif($date_type == 2){
            $start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
            $end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
        }elseif($date_type == 3){
            $start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-6 day')));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
        }elseif($date_type == 4){
            $start_time = strtotime(date('Y-m-d 00:00:00', strtotime($start_time)));
            $end_time = strtotime(date('Y-m-d 23:59:59', strtotime($end_time)));
        }else{
            $start_time = strtotime(date('Y-m-d 00:00:00'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
        }
        return [$start_time, $end_time];
    }

    protected function weChatImageValidation($image)
    {
        $access_token = (new WxService())->getAccessToken();
        if (!isset($access_token)) {
            $result['data'] = 1;
        } else {
            try {
                $url = "https://api.weixin.qq.com/wxa/img_sec_check?access_token=" . $access_token;
                $res = '@'.realpath($image);
                $obj = new \CURLFile($res);
                $obj->setMimeType("image/jpeg");
                $data['media'] = $res;
                $ret1 = $this->CURLSend($url, "post", $data,'');
                $ret = json_decode($ret1,true);
                if ($ret['errcode'] == 87014) {
                    $result['data'] = 2;
                } else if($ret['errcode'] == 45002){
                    $result['data'] = 1;
                } else {
                    $result['data'] = 0;
                }
            }catch (\Exception $exception){
                $result['data'] = 0;
            }
        }
        return $result;
    }

    /**微信内容检测
     * @param $content
     * @param $openid
     * @return array|void
     * @throws \cores\exception\BaseException
     */
    protected function checkContentWx($content, $openid)
    {
        // 获取微信accessToken
        $access_token = (new WxService())->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=" . $access_token;
        $body = [
            'content' => $content,
            'version' => 2,
            'scene' => 1,
            'openid' => $openid
        ];
        $res = $this->_Postrequest($url, json_encode($body, JSON_UNESCAPED_UNICODE));
        $res = json_decode($res, true);
        if(isset($res['errcode']) && $res['errcode'] == 0){
            if(isset($res['result']['label'])){
                if($res['result']['label'] == 100){
                    return ['code' => 0, 'msg' => '检测通过'];
                }else{
                    $msg = '检测不通过，'.$this->getCheckResultMessage($res['result']['label']);
                    throwError($msg);
                }
            }else{
                throwError('检测失败,请稍后再试');
            }
        }else{
            throwError('检测失败,请稍后再试');

        }
    }

    private function getCheckResultMessage($label)
    {
        switch ($label){
            case 10001:
                return '内容涉及广告';
            case 20001:
                return '内容涉及时政';
            case 20002:
                return '内容涉及色情';
            case 20003:
                return '内容涉及辱骂';
            case 20006:
                return '内容涉及违法犯罪';
            case 20008:
                return '内容涉及欺诈';
            case 20012:
                return '内容涉及低俗';
            case 20013:
                return '内容涉及版权';
            case 21000:
                return '内容涉及其他非法';
            default:
                return '检测异常，请稍后再试';
        }
    }

    private function _Postrequest($url, $data, $ssl = true, $token = '') //0正常， 1头条
    {
        if (!$token) {
            $headers = [
                "Content-type: application/json;charset='utf-8'"
            ];
        } else {

            $headers = [
                "X-Token: " . $token
            ];
        }
        //curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
        //SSL相关
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后cURL将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//检查服务器SSL证书中是否存在一个公用名(common name)。
        }
        // 处理post相关选项
        curl_setopt($curl, CURLOPT_POST, true);// 是否为POST请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 处理请求数据
        // 处理响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            echo '<br>', curl_error($curl), '<br>';
            return false;
        }
        curl_close($curl);
        return $response;
    }

    private function CURLSend($url, $method = 'get', $data = '',$access_token) {
        if (!$access_token) {
            $headers = [
                "Content-type: application/json;charset='utf-8'"
            ];
        } else {

            $headers = [
                "X-Token: " . $access_token
            ];
        }
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $url); //指定请求的URL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method)); //提交方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //不验证SSL
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置HTTP头字段的数组
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE5.01;Windows NT 5.0)'); //头的字符串
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); //自动设置header中的Referer:信息
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //提交数值
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //是否输出到屏幕上,true不直接输出
        $temp = curl_exec($ch); //执行并获取结果
        curl_close($ch);
        return $temp; //return 返回值
    }



}
