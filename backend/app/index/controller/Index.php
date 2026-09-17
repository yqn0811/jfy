<?php

namespace app\index\controller;

use app\BaseController;
use app\common\service\IndexService;

class Index extends BaseController
{
    public function index()
    {
        dd(123);
//        (new IndexService())->index('index');
//        $this->success('成功');
    }
}