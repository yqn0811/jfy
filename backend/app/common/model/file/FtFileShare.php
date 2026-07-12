<?php

namespace app\common\model\file;

class FtFileShare extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'file_shares';

    public function items()
    {
        return $this->hasMany(FtFileShareItem::class, 'share_id', 'id');
    }
}
