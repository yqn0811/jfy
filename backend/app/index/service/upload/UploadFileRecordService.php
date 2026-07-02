<?php
/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/3/14
 * Time: 13:28
 */

namespace app\index\service\upload;


use app\index\model\WdXcxPic;
use app\index\model\WdXcxUpfileRecord;
use think\facade\Db;

/**上传文件记录，获取可上传文件的空间
 * Class UploadFileRecordService
 * @package app\index\service\upload
 */
class UploadFileRecordService
{
    private $uniacid;


    public function __construct($uniacid)
    {
        $this->uniacid = $uniacid;
    }

    /**获取当前剩余空间
     * @return int  -1 不限制
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSurplusRemoteSize()
    {
        $config = $this->getCurrentRemoteSet();
        if($config['need_limit'] == 1){
            $hasUseSize = bcadd($this->getPicTotalSize(), $this->getOtherFileTotalSize(), 2);
            $size = bcsub($config['size_limit'], $hasUseSize, 2);
            $size = $size < 0 ? 0 : $size;
        }else{
            $size = -1;
        }
        return $size;
    }

    /**获取图库内容占用空间
     * @return string|null
     */
    private function getPicTotalSize()
    {
        $totalSize = WdXcxPic::withTrashed()
                        ->where('uniacid', $this->uniacid)
                        ->sum('size');
        return bcdiv($totalSize, 1024*1024, 2);
    }

    /**
     * 获取图库之外的 上传文件记录
     */
    private function getOtherFileTotalSize()
    {
        $totalSize = WdXcxUpfileRecord::where('uniacid', $this->uniacid)
                        ->sum('size');
        return bcdiv($totalSize, 1024*1024, 2);
    }



    /**获取当前使用的远程附件设置 是否需要空间限制
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCurrentRemoteSet()
    {
        $need_limit = 1; //  1需要限制  2 不需要限制
        $size_limit = -1; // -1 空间不限
        $remote_info = Db::name("wd_xcx_base")->where("uniacid", $this->uniacid)->field("remote, use_remote, remote_size")->find();  //当前项目设置
        if (!$remote_info) {
            $need_limit = 1;
            $size_limit = 200;
        } else {
            $use_remote = $remote_info['use_remote'];
            if($remote_info['remote_size'] > 0){
                if($use_remote == 2){
                    $need_limit = $remote_info['remote'] == 1 ? 1 : 2; //单独设置时只有 使用服务器需要现在
                }
                $size_limit = $remote_info['remote_size'] == 0 ? -1 : $remote_info['remote_size'];
            }else{
                $need_limit = 2;
            }
        }
        return compact('need_limit', 'size_limit');

    }
}