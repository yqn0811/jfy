<?php

namespace app\common\model\file;

class FtCollectionTask extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'collection_tasks';

    public function fields()
    {
        return $this->hasMany(FtCollectionField::class, 'task_id', 'id');
    }

    public function materials()
    {
        return $this->hasMany(FtCollectionMaterial::class, 'task_id', 'id');
    }
}
