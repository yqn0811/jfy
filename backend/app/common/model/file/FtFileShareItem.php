<?php

namespace app\common\model\file;

class FtFileShareItem extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'file_share_items';
    protected $updateTime = false;

    public function file()
    {
        return $this->hasOne(FtFile::class, 'id', 'file_id');
    }
}
