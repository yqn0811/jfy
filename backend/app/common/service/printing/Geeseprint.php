<?php

namespace app\common\service\printing;

use app\common\model\order\WdXcxUserOrderLists;
use cores\utils\HasHttpRequest;
use think\facade\Log;

class Geeseprint
{
    public static function geesePrint($set, $order_info, $type=WdXcxUserOrderLists::ORDER_TYPE_CATERING)
    {
        $print_info = '';
        if($type == WdXcxUserOrderLists::ORDER_TYPE_CATERING){
            $print_info = self::getCaterPrintInfo($set, $order_info);
        }
        if($print_info){
            self::executePrint($set, $print_info);
        }
    }

    /**发送打印
     * @param $set
     * @param $content
     * @return void
     */
    private static function executePrint($set, $content)
    {
        $timestamp = time();
        $postData = [
            'user' => $set->geese_account,
            'stime' => $timestamp,
            'sig' => self::getSignature($set, $timestamp),
            'apiname' => 'Open_printMsg',
            'sn' => $set->geese_sn,
            'content' => $content,
            'times' => $set->num,
        ];
        $url = 'api.feieyun.cn/Api/Open/';
        $result = self::curl_post($url, $postData);
        Log::info('订单打印结果:'.$result);
    }

    protected static function curl_post($url, $post) {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return $result;
    }

    /**打印参数签名
     * @param $set
     * @param $timestamp
     * @return string
     */
    private static function getSignature($set, $timestamp)
    {
        return sha1($set->geese_account.$set->geese_ukey.$timestamp);
    }

    /**组装点餐的打印数据
     * @param $set
     * @param $order_info
     * @return string
     */
    private static function getCaterPrintInfo($set, $order_info)
    {
        $content = '';
        $content .= '<CB>' .$set->title. '</CB><BR>';
        $content .= '下单时间：' . $order_info->create_time . '<BR>';
        $content .= '订单号：' . replacePrivacy($order_info->order_id, '****', 4, 24) . '<BR>';
//        $content .= '订单类型：餐饮订单<BR>';                      //打印内容
        $content .= '<BR>';
        if ($order_info->table_number) {
            $content .=  '<B>桌号：'.$order_info->table_number . '</B><BR>';
        } else {
            $content .= '<B>取餐号：#'.$order_info->order_number . '</B><BR>';
        }
        $content .= '<BR>';
        $content .= self::getDishesContent($order_info->dishes, 14, 6, 3, 6);
        $content .= '--------------------------------<BR>';
//        $content .= '总计：' . $order_info->total_price . '元<BR>';                      //打印内容
        $content .= self::LR('总计', $order_info->total_price, 32).'<BR>';
        if($order_info->total_price > $order_info->pay_price){
//            $content .= '折扣：' . bcsub($order_info->total_price, $order_info->pay_price, 2) . '元<BR>';
            $content .= self::LR('折扣', bcsub($order_info->total_price, $order_info->pay_price, 2), 32).'<BR>';
        }
//        $content .= '实付: ' . $order_info->pay_price . '元<BR><BR>';
        $content .= self::LR('实付', $order_info->pay_price, 32).'<BR>';
        $content .= '<BR>';
        $content .= '电话：' . replacePrivacy($order_info->user_mobile) . '<BR>';
        if ($order_info->take_timestamp) {
            $content .= '取餐时间： ' . date('Y-m-d H:i', $order_info->take_timestamp) . '<BR>';
        }else{
            $content .= '取餐时间:  立即取餐<BR>';
        }
        $content .= '备注：' . $order_info->order_remark;
        return $content;
    }

    private static function LR($str_left,$str_right,$length){
        if( empty($str_left) || empty($str_right) || empty($length) ) return '请输入正确的参数';
        $kw = '';
        $str_left_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_left));
        $str_right_lenght = strlen(iconv("UTF-8", "GBK//IGNORE", $str_right));
        $k = $length - ($str_left_lenght+$str_right_lenght);
        for($q=0;$q<$k;$q++){
            $kw .= ' ';
        }
        return $str_left.$kw.$str_right;
    }

    /**点餐内容排版
     * @param $arr
     * @param $A
     * @param $B
     * @param $C
     * @param $D
     * @return string
     */
    private static function getDishesContent($arr,$A,$B,$C,$D)
    {
        $orderInfo = '';
        $orderInfo .= '名称           单价  数量 金额<BR>';
        $orderInfo .= '--------------------------------<BR>';
        foreach ($arr as $k5 => $v5) {
            $name = $v5->dishes_title.'('.$v5->spec_value.')';
            $price = $v5->dishes_price;
            $num = $v5->num;
            $prices = bcmul($price, $num, 2);
            $kw3 = '';
            $kw1 = '';
            $kw2 = '';
            $kw4 = '';
            $str = $name;
            $blankNum = $A;//名称控制为14个字节
            $lan = mb_strlen($str,'utf-8');
            $m = 0;
            $j=1;
            $blankNum++;
            $result = array();
            if(strlen($price) < $B){
                $k1 = $B - strlen($price);
                for($q=0;$q<$k1;$q++){
                    $kw1 .= ' ';
                }
                $price = $price.$kw1;
            }
            if(strlen($num) < $C){
                $k2 = $C - strlen($num);
                for($q=0;$q<$k2;$q++){
                    $kw2 .= ' ';
                }
                $num = $num.$kw2;
            }
            if(strlen($prices) < $D){
                $k3 = $D - strlen($prices);
                for($q=0;$q<$k3;$q++){
                    $kw4 .= ' ';
                }
                $prices = $prices.$kw4;
            }
            for ($i=0;$i<$lan;$i++){
                $new = mb_substr($str,$m,$j,'utf-8');
                $j++;
                if(mb_strwidth($new,'utf-8')<$blankNum) {
                    if($m+$j>$lan) {
                        $m = $m+$j;
                        $tail = $new;
                        $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                        $k = $A - strlen($lenght);
                        for($q=0;$q<$k;$q++){
                            $kw3 .= ' ';
                        }
                        if($m==$j){
                            $tail .= $kw3.' '.$price.' '.$num.' '.$prices;
                        }else{
                            $tail .= $kw3.'<BR>';
                        }
                        break;
                    }else{
                        $next_new = mb_substr($str,$m,$j,'utf-8');
                        if(mb_strwidth($next_new,'utf-8')<$blankNum) continue;
                        else{
                            $m = $i+1;
                            $result[] = $new;
                            $j=1;
                        }
                    }
                }
            }
            $head = '';
            foreach ($result as $key=>$value) {
                if($key < 1){
                    $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                    $v_lenght = strlen($v_lenght);
                    if($v_lenght == 13) $value = $value." ";
                    $head .= $value.' '.$price.' '.$num.' '.$prices;
                }else{
                    $head .= $value.'<BR>';
                }
            }
            $orderInfo .= $head.$tail;
            @$nums += $prices;
        }
        return $orderInfo;
    }
}