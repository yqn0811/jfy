<?php

namespace app\common\service;

use think\App;
use think\facade\Db;

class DatabaseInitService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function init()
    {
        $this->ensureWdxcxUser();
        $this->ensureUserVipGradeInfo();
        $this->ensureVipgrade();
        $this->ensureAdmin();
        $this->ensureBase();
        $this->ensureUserCoupon();
        $this->ensureCoupon();
        $this->ensureUserBuyGradeOrder();
        $this->ensureIntegralSign();
        $this->ensureIntegralTaskBase();
        $this->ensureUserIntegralTaskRecord();
        $this->ensureUserIntegralRecord();
        $this->ensureDistributionUserParent();
    }

    private function tableExists($table)
    {
        $res = Db::query("SHOW TABLES LIKE '" . $table . "'");
        return !empty($res);
    }

    private function ensureDistributionUserParent()
    {
        $table = 'wd_xcx_distribution_user_parent';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_distribution_user_parent` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `user_id` int(11) unsigned NOT NULL,
              `parent_id` int(11) unsigned NOT NULL,
              `lock_time` int(11) unsigned NOT NULL DEFAULT 0,
              `bind_log` json DEFAULT NULL,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_parent_id` (`parent_id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function execute($sql)
    {
        Db::execute($sql);
    }
    
    private function columnExists($table, $column)
    {
        $res = Db::query("SHOW COLUMNS FROM `".$table."` LIKE '".$column."'");
        return !empty($res);
    }

    private function ensureWdxcxUser()
    {
        $table = 'wd_xcx_user';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_user` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `openid` varchar(64) NOT NULL,
              `unionid` varchar(64) DEFAULT NULL,
              `nickname` varchar(64) NOT NULL DEFAULT '',
              `avatar` varchar(255) NOT NULL DEFAULT '',
              `user_uuid` varchar(32) NOT NULL,
              `mobile` varchar(20) DEFAULT NULL,
              `leaguer_id` varchar(64) DEFAULT NULL,
              `vip_grade` int(11) unsigned NOT NULL DEFAULT 0,
              `space_size` int(11) unsigned NOT NULL DEFAULT 300,
              `join_time` int(11) unsigned NOT NULL DEFAULT 0,
              `wx_ewm` varchar(255) DEFAULT NULL,
              `user_desc` text,
              `upload_pwd` varchar(8) DEFAULT NULL,
              `is_show_home` tinyint(1) NOT NULL DEFAULT 1,
              `company_name` varchar(128) DEFAULT NULL,
              `company_logo` varchar(255) DEFAULT NULL,
              `company_desc` text,
              `contact_mobile` varchar(32) DEFAULT NULL,
              `contact_wechat` varchar(64) DEFAULT NULL,
              `address_province` varchar(64) DEFAULT NULL,
              `address_city` varchar(64) DEFAULT NULL,
              `address_district` varchar(64) DEFAULT NULL,
              `address_detail` varchar(255) DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_openid` (`openid`),
              UNIQUE KEY `uk_user_uuid` (`user_uuid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureUserVipGradeInfo()
    {
        $table = 'wd_xcx_user_vip_grade_info';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_user_vip_grade_info` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `user_id` int(11) unsigned NOT NULL,
              `grade_level` int(11) unsigned NOT NULL DEFAULT 0,
              `end_time` int(11) unsigned NOT NULL DEFAULT 0,
              `change_log` json DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureVipgrade()
    {
        $table = 'wd_xcx_vipgrade';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_vipgrade` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `grade_level` int(11) unsigned NOT NULL DEFAULT 0,
              `grade_name` varchar(64) DEFAULT NULL,
              `discount_flag` tinyint(1) NOT NULL DEFAULT 0,
              `discount_grade` decimal(4,1) DEFAULT 0.0,
              `score_flag` tinyint(1) NOT NULL DEFAULT 0,
              `score_back` int(11) unsigned NOT NULL DEFAULT 0,
              `annual_fee` decimal(10,2) DEFAULT 0.00,
              `market_annual_fee` decimal(10,2) DEFAULT 0.00,
              `midd_month_fee` decimal(10,2) DEFAULT 0.00,
              `market_midd_month_fee` decimal(10,2) DEFAULT 0.00,
              `cloud_size` int(11) unsigned NOT NULL DEFAULT 300,
              `upload_size_type` tinyint(1) NOT NULL DEFAULT 1,
              `upload_size` int(11) unsigned NOT NULL DEFAULT 200,
              `card_img` varchar(255) DEFAULT NULL,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureAdmin()
    {
        $table = 'wd_xcx_admin';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_admin` (
              `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `username` varchar(64) NOT NULL,
              `realname` varchar(64) DEFAULT NULL,
              `password` varchar(64) NOT NULL,
              `flag` tinyint(1) NOT NULL DEFAULT 1,
              `is_del` tinyint(1) NOT NULL DEFAULT 0,
              `lastloginip` int(11) unsigned DEFAULT 0,
              `lastlogintime` int(11) unsigned DEFAULT 0,
              PRIMARY KEY (`uid`),
              UNIQUE KEY `uk_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureBase()
    {
        $table = 'wd_xcx_base';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_base` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `space_size` int(11) unsigned NOT NULL DEFAULT 300,
              `about` text,
              `news_link` varchar(255) DEFAULT NULL,
              `kf_link` varchar(255) DEFAULT NULL,
              `kf_ewm` varchar(255) DEFAULT NULL,
              `share_thumb` varchar(255) DEFAULT NULL,
              `folder_count` int(11) unsigned NOT NULL DEFAULT 0,
              `share_count` int(11) unsigned NOT NULL DEFAULT 0,
              `down_count` int(11) unsigned NOT NULL DEFAULT 0,
              `index_banner` json DEFAULT NULL,
              `base_color_t` varchar(32) DEFAULT NULL,
              `video` varchar(255) DEFAULT NULL,
              `index_about_title` varchar(255) DEFAULT NULL,
              `tabbar` json DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureUserCoupon()
    {
        $table = 'wd_xcx_user_coupon';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_user_coupon` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `user_id` int(11) unsigned NOT NULL,
              `coupon_id` int(11) NOT NULL DEFAULT -1,
              `coupon_title` varchar(128) DEFAULT NULL,
              `coupon_type` tinyint(1) NOT NULL DEFAULT 1,
              `bg_image` varchar(255) DEFAULT NULL,
              `btime` int(11) unsigned NOT NULL DEFAULT 0,
              `etime` int(11) unsigned NOT NULL DEFAULT 0,
              `message` varchar(255) DEFAULT NULL,
              `use_rule` json DEFAULT NULL,
              `pay_money` decimal(10,2) DEFAULT 0.00,
              `minus_money` decimal(10,2) DEFAULT 0.00,
              `get_type` varchar(32) DEFAULT NULL,
              `use_status` tinyint(1) NOT NULL DEFAULT 1,
              `use_time` int(11) unsigned NOT NULL DEFAULT 0,
              `use_info` varchar(255) DEFAULT NULL,
              `remote_coupon_id` varchar(64) DEFAULT NULL,
              `qrcode_id` varchar(64) DEFAULT NULL,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureCoupon()
    {
        $table = 'wd_xcx_coupon';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_coupon` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `title` varchar(128) NOT NULL,
              `coupon_type` tinyint(1) NOT NULL DEFAULT 1,
              `bg_image` varchar(255) DEFAULT NULL,
              `use_time_type` tinyint(1) NOT NULL DEFAULT 1,
              `btime` int(11) unsigned NOT NULL DEFAULT 0,
              `etime` int(11) unsigned NOT NULL DEFAULT 0,
              `use_day_limit` int(11) unsigned NOT NULL DEFAULT 0,
              `use_after_limit` int(11) unsigned NOT NULL DEFAULT 0,
              `use_rule` json DEFAULT NULL,
              `pay_money` decimal(10,2) DEFAULT 0.00,
              `minus_money` decimal(10,2) DEFAULT 0.00,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureUserBuyGradeOrder()
    {
        $table = 'wd_xcx_user_buy_grade_order_lists';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_user_buy_grade_order_lists` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `user_id` int(11) unsigned NOT NULL,
              `order_id` varchar(64) DEFAULT NULL,
              `status` tinyint(1) NOT NULL DEFAULT 0,
              `pay_price` decimal(10,2) DEFAULT 0.00,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private function ensureIntegralSign()
    {
        $table = 'wd_xcx_user_integral_sign_record';
        if (!$this->tableExists($table)) {
            $this->execute("
                CREATE TABLE `wd_xcx_user_integral_sign_record` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
                  `user_id` int(11) unsigned NOT NULL,
                  `sign_date` date DEFAULT NULL,
                  `continue_days` int(11) unsigned NOT NULL DEFAULT 0,
                  `score` int(11) unsigned NOT NULL DEFAULT 0,
                  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
                  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `idx_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            if (!$this->columnExists($table, 'continue_days')) {
                $this->execute("ALTER TABLE `wd_xcx_user_integral_sign_record` ADD `continue_days` int(11) unsigned NOT NULL DEFAULT 0 AFTER `sign_date`");
            }
            if (!$this->columnExists($table, 'score')) {
                $this->execute("ALTER TABLE `wd_xcx_user_integral_sign_record` ADD `score` int(11) unsigned NOT NULL DEFAULT 0 AFTER `continue_days`");
            }
        }
    }

    private function ensureIntegralTaskBase()
    {
        $table = 'wd_xcx_integral_task';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_integral_task` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `title` varchar(128) NOT NULL,
              `desc` varchar(255) DEFAULT NULL,
              `icon` varchar(255) DEFAULT NULL,
              `score` int(11) unsigned NOT NULL DEFAULT 0,
              `btn_text` varchar(64) DEFAULT NULL,
              `task_key` varchar(64) NOT NULL,
              `type` tinyint(1) NOT NULL DEFAULT 1,
              `is_show` tinyint(1) NOT NULL DEFAULT 1,
              `sort` int(11) unsigned NOT NULL DEFAULT 0,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_task_key` (`task_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    private function ensureUserIntegralTaskRecord()
    {
        $table = 'wd_xcx_user_integral_task_record';
        if (!$this->tableExists($table)) {
            $this->execute("
                CREATE TABLE `wd_xcx_user_integral_task_record` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
                  `user_id` int(11) unsigned NOT NULL,
                  `task_id` int(11) unsigned NOT NULL,
                  `score` int(11) unsigned NOT NULL DEFAULT 0,
                  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
                  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `idx_user_id` (`user_id`),
                  KEY `idx_task_id` (`task_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            if (!$this->columnExists($table, 'task_id')) {
                $this->execute("ALTER TABLE `wd_xcx_user_integral_task_record` ADD `task_id` int(11) unsigned NOT NULL DEFAULT 0 AFTER `user_id`");
            }
            if (!$this->columnExists($table, 'score')) {
                $this->execute("ALTER TABLE `wd_xcx_user_integral_task_record` ADD `score` int(11) unsigned NOT NULL DEFAULT 0 AFTER `task_id`");
            }
        }
    }
    
    private function ensureUserIntegralRecord()
    {
        $table = 'wd_xcx_user_integral_record';
        if ($this->tableExists($table)) {
            return;
        }
        $this->execute("
            CREATE TABLE `wd_xcx_user_integral_record` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `uniacid` int(11) unsigned NOT NULL DEFAULT 1,
              `user_id` int(11) unsigned NOT NULL,
              `change_type` tinyint(1) NOT NULL DEFAULT 1,
              `change_integral` int(11) unsigned NOT NULL DEFAULT 0,
              `new_integral` int(11) unsigned NOT NULL DEFAULT 0,
              `order_id` varchar(64) DEFAULT NULL,
              `message` varchar(255) DEFAULT NULL,
              `change_source` int(11) unsigned NOT NULL DEFAULT 0,
              `delete_time` int(11) unsigned DEFAULT NULL,
              `create_time` int(11) unsigned NOT NULL DEFAULT 0,
              `update_time` int(11) unsigned NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
}
