<?php

namespace cores\utils;

use dh2y\qrcode\QRcode;

class Utils
{
    /**生成二维码
     * @param $url
     * @param $path
     * @param $is_base64
     * @return string
     */
    public static function createQrcode($url, $path='', $is_base64=false)
    {
        $qrcode = new QRcode();
        if(!$path){
            $path = 'uploads/qrcode/'.time().rand(10000, 99999).'.png';
        }
        $res = $qrcode->png($url, $path, 10, 10);
        if($is_base64){
            $file_path = public_path().$path;
            $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($file_path));
            unlink($file_path);
            return $base64;
        }else{
            return $res->entry();
        }

    }

    /**
     * 格式化时间为友好显示（如：刚刚、xx分钟前）
     * @param $time
     * @return string
     */
    public static function timeAgo($time)
    {
        if (empty($time)) {
            return '';
        }
        
        $time = is_numeric($time) ? (int)$time : strtotime($time);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 2592000) { // 30天内
            return floor($diff / 86400) . '天前';
        } else {
            return date('Y-m-d', $time);
        }
    }
}