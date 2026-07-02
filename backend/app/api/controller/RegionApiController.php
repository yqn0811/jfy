<?php

namespace app\api\controller;

use app\common\service\RegionService;
use think\App;

class RegionApiController extends ApiBaseController
{
    private $region_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->region_service = new RegionService($app);
    }

    /**
     * 获取省市区树状结构
     */
    public function getRegionTree()
    {
        $this->result($this->region_service->getRegionTree());
    }
}
