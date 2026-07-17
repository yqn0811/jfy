<?php

namespace app\common\model\system_set;

use think\Model;
use think\facade\Db;

class WdXcxWorkbenchMenuSet extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_workbench_menu_set';
    protected $autoWriteTimestamp = true;

    // No JSON casting needed for new structure as fields are flat
    protected $type = [
    ];

    public function getBase($uniacid)
    {
        // Check if table has the 'type' column (indicating new structure)
        // If not, drop it to force recreation with new schema
        $hasColumn = Db::query("SHOW COLUMNS FROM `wd_xcx_workbench_menu_set` LIKE 'type'");
        if (!$hasColumn) {
            Db::execute("DROP TABLE IF EXISTS `wd_xcx_workbench_menu_set`");
        }

        // ensure table exists
        $tables = Db::query("SHOW TABLES LIKE 'wd_xcx_workbench_menu_set'");
        if (!$tables) {
            Db::execute("
                CREATE TABLE IF NOT EXISTS `wd_xcx_workbench_menu_set` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `uniacid` int(11) NOT NULL DEFAULT 1,
                    `type` varchar(50) NOT NULL COMMENT 'merchants,tool,other',
                    `icon` varchar(255) DEFAULT NULL,
                    `title` varchar(100) DEFAULT NULL,
                    `url` varchar(255) DEFAULT NULL,
                    `is_show` tinyint(1) DEFAULT 1,
                    `is_login` tinyint(1) DEFAULT 1,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `create_time` int(11) DEFAULT NULL,
                    `update_time` int(11) DEFAULT NULL,
                    `delete_time` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_uniacid` (`uniacid`),
                    KEY `idx_type` (`type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        $count = $this->where('uniacid', $uniacid)->count();
        if ($count == 0) {
            $data = [];
            $merchants = [
                ['icon' => 'product', 'title' => '产品管理', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'category', 'title' => '分类管理', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'homepage', 'title' => '主页编辑', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'authority', 'title' => '权限管理', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'visitor', 'title' => '访客记录', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'vip', 'title' => '升级会员', 'url' => '', 'is_show' => 0, 'is_login' => 1],
                ['icon' => 'recycle', 'title' => '回收站', 'url' => '', 'is_show' => 1, 'is_login' => 1],
            ];
            foreach ($merchants as $item) {
                $item['uniacid'] = $uniacid;
                $item['type'] = 'merchants';
                $item['status'] = 1;
                $data[] = $item;
            }

            $tools = [
                ['icon' => 'footprint', 'title' => '我的足迹', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'collect', 'title' => '我的收藏', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'sign', 'title' => '签到', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'integral', 'title' => '我的积分', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'service', 'title' => '客服服务', 'url' => '', 'is_show' => 1, 'is_login' => 1],
                ['icon' => 'cases', 'title' => '案例中心', 'url' => '', 'is_show' => 1, 'is_login' => 1],
            ];
            foreach ($tools as $item) {
                $item['uniacid'] = $uniacid;
                $item['type'] = 'tool';
                $item['status'] = 1;
                $data[] = $item;
            }

            $others = [
                ['icon' => 'account', 'title' => '关注公众号', 'url' => '', 'is_show' => 1, 'is_login' => 0],
                ['icon' => 'recommend', 'title' => '推荐给好友', 'url' => '', 'is_show' => 1, 'is_login' => 0],
                ['icon' => 'help', 'title' => '使用帮助', 'url' => '', 'is_show' => 1, 'is_login' => 0],
                ['icon' => 'feedback', 'title' => '建议反馈', 'url' => '', 'is_show' => 1, 'is_login' => 0],
            ];
            foreach ($others as $item) {
                $item['uniacid'] = $uniacid;
                $item['type'] = 'other';
                $item['status'] = 1;
                $data[] = $item;
            }

            $this->saveAll($data);
        }

        // Fetch and format
        $all = $this->where('uniacid', $uniacid)->where('status', 1)->select()->toArray();
        $merchantsList = [];
        $toolList = [];
        $otherList = [];

        foreach ($all as $item) {
            // Keep only required fields for frontend
            $formatted = [
                'icon' => $item['icon'],
                'title' => $item['title'],
                'url' => $item['url'],
                'is_show' => $item['is_show'],
                'is_login' => $item['is_login']
            ];

            if ($item['type'] == 'merchants') {
                $merchantsList[] = $formatted;
            } elseif ($item['type'] == 'tool') {
                $toolList[] = $formatted;
            } elseif ($item['type'] == 'other') {
                $otherList[] = $formatted;
            }
        }

        return [
            ['merchants' => $merchantsList],
            ['tool' => $toolList],
            ['other' => $otherList]
        ];
    }
}
