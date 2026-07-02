<?php

namespace app\common\service\ylb;

use app\common\service\HttpService;
use cores\utils\HasHttpRequest;
use cores\utils\Utils;
use dh2y\qrcode\QRcode;
use think\facade\Config;
use think\facade\Log;


class YlbApiService
{
    use HasHttpRequest;

    private $data;

    public function __construct()
    {

    }

    public function registeLeaguer(string $openid, string $mobile)
    {
        $user = WdXcxUser::where('openid', $openid)->find();
        if (!$user) {
            $user = new WdXcxUser();
            $user->openid = $openid;
            $user->uniacid = 1;
            $user->save();
        }
        if ($mobile) {
            $user->mobile = $mobile;
        }
        if (empty($user->join_time)) {
            $user->join_time = time();
        }
        $user->save();
        return (string)$user->id;
    }

























}
