<?php

namespace app\common\model\album;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxAlbumSelection extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_album_selection';
    protected $autoWriteTimestamp = true;

    public function items()
    {
        return $this->hasMany(WdXcxAlbumSelectionItem::class, 'selection_id', 'id');
    }
}
