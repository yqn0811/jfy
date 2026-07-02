<?php

namespace app\common\service;

class IndexService
{
    public function index($plat)
    {
        throwError($plat.' service');
        dd($plat.' service');
    }
}