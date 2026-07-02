<?php

namespace app\common\service;

use think\App;

class PermissionsService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

}