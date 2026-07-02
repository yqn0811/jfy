<?php

namespace app\api\controller;

use app\BaseController;
use think\App;

class ApiBaseController extends BaseController
{
    protected $uniacid = 1;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    protected function getResponseType()
    {
        return 'json';
    }
}
