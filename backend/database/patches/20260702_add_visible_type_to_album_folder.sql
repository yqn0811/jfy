-- 修复相册分类/产品目录表缺少 visible_type 字段的问题。
-- 线上 146 服务器已于 2026-07-02 执行：
--   备份表 bak_20260702_194508_visible_type_album_folder，共 262 行。
--
-- 背景：
--   当前 PHP 后端 AlbumService 会读取/写入 wd_xcx_album_folder.visible_type。
--   老表结构只有 private_type，导致接口报：
--   SQLSTATE[42S22]: Column not found: 1054 Unknown column 'visible_type' in 'field list'
--
-- 语义：
--   visible_type 与 private_type 当前保持一致：
--   1 公开，2 私密，4 分享可见；历史数据中也可能存在 3。

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'wd_xcx_album_folder'
      AND COLUMN_NAME = 'visible_type'
);

SET @add_visible_type_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `wd_xcx_album_folder` ADD COLUMN `visible_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''可见状态 1公开 2私密 4分享可见'' AFTER `private_type`',
    'SELECT 1'
);

PREPARE add_visible_type_stmt FROM @add_visible_type_sql;
EXECUTE add_visible_type_stmt;
DEALLOCATE PREPARE add_visible_type_stmt;

UPDATE `wd_xcx_album_folder`
SET `visible_type` = `private_type`
WHERE `private_type` IS NOT NULL;
