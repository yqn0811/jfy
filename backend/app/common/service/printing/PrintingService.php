<?php

namespace app\common\service\printing;

use app\common\model\system_set\WdXcxPrint;
use app\common\service\BaseService;
use think\facade\Log;

class PrintingService extends BaseService
{
    public static function PrintingOrder($uniacid, $order_info)
    {
        try {
            $print = self::getPrintSet($uniacid);
            foreach ($print as $item){
                if($item->models == 2){
                    Geeseprint::geesePrint($item, $order_info);
                }
            }
        }catch (\Exception $exception){
            Log::info('打印错误：'.$exception->getMessage());
        }
    }

    private static function getPrintSet($uniacid)
    {
        return WdXcxPrint::where('uniacid', $uniacid)
            ->where('flag', 1)
            ->select();
    }
}