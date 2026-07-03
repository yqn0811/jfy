<?php
/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2021/8/26
 * Time: 15:37
 */

namespace app\index\model;


use app\common\model\user\WdXcxUser;
use think\Exception;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxPic extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';


    //关联栏目
    public function cate()
    {
        return $this->belongsTo(WdXcxPicgroup::class, 'gid', 'id');
    }

    public function getTruePicAttr()
    {
        return self::normalizePictureUrl($this->imgurl, $this->uniacid, $this->file_type);

    }

    public static function normalizePictureUrl($url, $uniacid = 1, $fileType = 1)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }
        if (strpos($url, '//') === 0) {
            return proxyExternalImageUrl('https:' . $url);
        }
        if (self::isHttpUrl($url)) {
            return proxyExternalImageUrl($url);
        }
        if (self::isSchemeLessHttpUrl($url)) {
            return proxyExternalImageUrl('https://' . ltrim($url, '/'));
        }
        if((int)$fileType == 1){
            return remote($uniacid ?: 1, $url, 1);
        }else{
            return removePicStyle(remote($uniacid ?: 1, $url, 1));
        }
    }

    public static function isHttpUrl($url)
    {
        return is_string($url) && preg_match('/^https?:\/\//i', trim($url)) === 1;
    }

    public static function isSchemeLessHttpUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '' || strpos($url, '/') === false || substr($url, 0, 1) === '/') {
            return false;
        }
        $host = explode('/', $url, 2)[0];
        return preg_match('/^[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(:\d+)?$/i', $host) === 1;
    }

    public function getSizeAttr($value)
    {
        return $value ? number_format($value / 1024, 2) : '未知';
    }


    public function getUserInfoAttr()
    {
        $user = WdXcxUser::where('id', $this->uid)->find();
        if($user){
            $info = $user->getUserInfoShow($user->id);
            unset($info['mobile']);
            return $info;
        }else{
            $info = (new WdXcxUser())->getUserInfoShow($this->uid);
            unset($info['mobile']);
            return $info;
        }
    }

    public function getTypeAttr($value)
    {
        $arr = [
            1 => '本地',
            2 => '七牛云',
            3 => '阿里云OSS',
            5 => '腾讯云OSS',
        ];
        return $arr[$value];
    }

    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getdeleteTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**获取图片列表
     * @param $condition
     * @param $pageData
     * @return array
     * @throws \think\exception\DbException
     */
    public static function lists($condition, $pageData)
    {
        $lists = self::where($condition)
                    ->where('uid', 0)
                    ->field('id, imgurl as src, pic_name as name, uniacid')
                    ->order('id desc')
                    ->paginate($pageData['pageSize'])
                    ->each(function ($item){
                        $item->width = '';
                        $item->height = '';
                        $item->src = remote($item->uniacid, $item->src, 1);
                    });
        return [
            'lists' => $lists->items(),
            'size' => $lists->listRows(),
            'curr_page' => $lists->currentPage(),
            'total_page' => $lists->lastPage(),
            'pic_limit' => $pageData['picLimit'],
        ];
    }

    /**批量移动图片
     * @param $parame
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public static function movePictureGroup($parame)
    {

        $group = WdXcxPicgroup::find($parame['gid']);
        if($parame['gid'] != -1){
            if(!$group){
                throw new Exception('选择的栏目不存在');
            }
        }
        self::where('id', 'in', $parame['pic_ids'])
            ->where('uniacid', $parame['uniacid'])
            ->update(['gid' => $parame['gid']]);
    }

    //批量删除图片
    public static function deletePictures($parame)
    {
        WdXcxPic::destroy($parame['pic_ids']);
    }

    //保存图片
    public static function savePicture($data)
    {
       return self::create($data);
    }
}
