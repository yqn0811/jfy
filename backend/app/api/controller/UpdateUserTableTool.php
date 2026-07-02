<?php
namespace app\api\controller;

use think\facade\Db;
use app\BaseController;

class UpdateUserTableTool extends BaseController
{
    public function index()
    {
        try {
            $sql = "ALTER TABLE `wd_xcx_user` 
                    ADD COLUMN `company_name` varchar(255) DEFAULT NULL COMMENT '公司名称',
                    ADD COLUMN `company_logo` varchar(255) DEFAULT NULL COMMENT '公司Logo',
                    ADD COLUMN `company_desc` text DEFAULT NULL COMMENT '公司简介',
                    ADD COLUMN `contact_mobile` varchar(20) DEFAULT NULL COMMENT '联系电话',
                    ADD COLUMN `contact_wechat` varchar(50) DEFAULT NULL COMMENT '联系微信',
                    ADD COLUMN `address_province` varchar(50) DEFAULT NULL COMMENT '省',
                    ADD COLUMN `address_city` varchar(50) DEFAULT NULL COMMENT '市',
                    ADD COLUMN `address_district` varchar(50) DEFAULT NULL COMMENT '区',
                    ADD COLUMN `address_detail` varchar(255) DEFAULT NULL COMMENT '详细地址',
                    ADD COLUMN `is_show_home` tinyint(1) DEFAULT 0 COMMENT '是否显示主页 0否 1是'";
            
            Db::execute($sql);
            return json(['code' => 1, 'msg' => 'Table updated successfully']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }
}
