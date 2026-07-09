<?php

namespace app\common\model\file;

class FtArchiveItem extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'archive_items';
    protected $createTime = false;
    protected $updateTime = false;
}
