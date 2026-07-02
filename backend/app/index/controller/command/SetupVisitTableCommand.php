<?php

namespace app\index\controller\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class SetupVisitTableCommand extends Command
{
    protected function configure()
    {
        $this->setName('setup_visit_table')
            ->setDescription('Create wd_xcx_user_visit_record table');
    }

    protected function execute(Input $input, Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `wd_xcx_user_visit_record` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `uid` int(11) NOT NULL DEFAULT '0' COMMENT '访问者ID',
          `target_uid` int(11) NOT NULL DEFAULT '0' COMMENT '被访问者ID',
          `visit_count` int(11) DEFAULT '1' COMMENT '访问次数',
          `create_time` int(11) DEFAULT NULL,
          `update_time` int(11) DEFAULT NULL,
          `delete_time` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_uid` (`uid`),
          KEY `idx_target_uid` (`target_uid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户访问记录表';";

        try {
            Db::execute($sql);
            $output->writeln("Table wd_xcx_user_visit_record created successfully.");
        } catch (\Exception $e) {
            $output->writeln("Error: " . $e->getMessage());
        }
    }
}
