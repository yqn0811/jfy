<?php
namespace app\api\controller;

use think\facade\Db;

class CheckColumnsController
{
    public function check()
    {
        $c1 = Db::query("DESCRIBE wd_xcx_user_visit_record");
        $c2 = Db::query("DESCRIBE wd_xcx_user_collect_users");
        return json(['visit' => $c1, 'collect' => $c2]);
    }
}
