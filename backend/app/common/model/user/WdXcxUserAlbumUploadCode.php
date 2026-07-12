<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\WdXcxPic;
use think\facade\Db;
use think\model\concern\SoftDelete;

class WdXcxUserAlbumUploadCode extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_album_upload_code';
    protected $autoWriteTimestamp = true;

    public static function ensureUploadEnabledColumn()
    {
        $hasColumn = Db::query("SHOW COLUMNS FROM `wd_xcx_user_album_upload_code` LIKE 'upload_enabled'");
        if ($hasColumn) {
            return;
        }
        try {
            Db::execute("ALTER TABLE `wd_xcx_user_album_upload_code` ADD COLUMN `upload_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '大批量上传入口 1开启 0关闭' AFTER `upload_code`");
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (strpos($message, 'SQLSTATE[42S21]') === false
                && stripos($message, 'Duplicate column') === false
                && stripos($message, 'Column already exists') === false) {
                throw $e;
            }
        }
    }
}
