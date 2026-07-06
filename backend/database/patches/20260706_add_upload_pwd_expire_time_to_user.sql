ALTER TABLE `wd_xcx_user`
  ADD COLUMN `upload_pwd_expire_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '批量上传密码过期时间 0永久有效' AFTER `upload_pwd`;
