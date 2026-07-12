<?php

namespace app\common\model\file;

class FtSubmissionFile extends FileTransferModel
{
    protected $pk = 'id';
    protected $name = 'submission_files';
    protected $updateTime = false;

    public function file()
    {
        return $this->hasOne(FtFile::class, 'id', 'file_id');
    }
}
