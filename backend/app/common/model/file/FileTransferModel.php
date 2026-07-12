<?php

namespace app\common\model\file;

use think\Model;

class FileTransferModel extends Model
{
    protected $connection = 'pgsql_file';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
