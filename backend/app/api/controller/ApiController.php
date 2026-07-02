<?php

namespace app\api\controller;

use app\common\service\IndexService;
use app\Request;


class ApiController extends ApiBaseController
{
    public function index(Request $request)
    {
//        dd(app('request')->get());
        $aa = $request->getMore(['test', 'user']);
        dd($aa);
        (new IndexService())->index('api 11');
    }
}