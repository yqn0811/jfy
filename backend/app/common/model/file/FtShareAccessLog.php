<?php

namespace app\common\model\file;

class FtShareAccessLog extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'share_access_logs';
    protected $createTime = false;
    protected $updateTime = false;
}
