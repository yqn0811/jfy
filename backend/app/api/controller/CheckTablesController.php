<?php
namespace app\api\controller;

use think\facade\Db;

class CheckTablesController
{
    public function check()
    {
        $tables = Db::query("SHOW TABLES LIKE 'wd_xcx_user_collect_%'");
        return json($tables);
    }
}
