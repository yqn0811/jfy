<?php

namespace app\api\controller;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\user\WdXcxUserExampleSet;
use app\common\service\CommonService;
use app\index\model\WdXcxBase;
use think\App;
use think\facade\Config;
use think\Response;

class CommonApiController extends ApiBaseController
{
    protected $service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new CommonService($app);
    }

    /**上传图片
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function uploadImage()
    {
        $param = $this->request->file();
        $res = $this->service->uploadImage($param, request()->userID());
        $this->result([
            'url' => $res['url'],
            'id' => $res['pid'],
        ]);
    }

    /**获取首页基础信息
     * @return void
     */
    public function indexBaseInfo()
    {
        $this->result($this->service->indexBaseInfo());
    }

    /**公共基础信息
     * @return void
     */
    public function commonBaseInfo()
    {
        $this->result($this->service->commonBaseInfo());
    }
    
    public function getWorkbenchMenu()
    {
        $this->result($this->service->getWorkbenchMenu());
    }

    /**获取游戏排行榜
     * @return void
     */
    public function getUserPlayRankingLists()
    {
        $param = $this->request->getMore([
            ['game_key', 0],
            ['date_index', 2],
        ]);
//        $this->result($this->service->getUserPlayRankingLists($param));
        $this->result($this->service->getUserPlayRankingListsNew($param));
    }

    /**获取新人有礼数据
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserNewGiftLists()
    {
        $param = $this->request->getMore([
            ['openid', ''],
        ]);
        $this->result($this->service->getUserNewGiftLists($param));
    }

    /**新人领取礼包
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function receiveUserNewGiftLists()
    {
        $this->service->receiveUserNewGiftLists(request()->userOpenid());
        $this->result([], 0, '领取成功');
    }

    /**获取示例相册文件夹列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExampleFolderLists()
    {
        $param = $this->request->getMore([
            ['fid', 0],
            ['is_follow', 0],
            ['page', 1],
        ]);
        $fid = $param['fid'];
        $is_follow = $param['is_follow'];
        $info = '';
        $set = '';
        $all_follow = [];
        if($is_follow){
            $all_follow = (new WdXcxUserExampleSet())->getAllFollow(request()->userID());
        }
        if($fid){
            $info = WdXcxAlbumFolder::where('id', $fid)
                ->field('id,folder_name,folder_type,create_time, new_thumb, share_times,visit_times')
                ->find();
            if(!$info){
                throwError('指定内容不存在');
            }
            $set = (new WdXcxUserExampleSet())->getSet($fid, request()->userID());
        }
        $lists = WdXcxAlbumFolder::where('uid', -1)
            ->where('pid', $fid)
            ->where(function ($query)use($param, $is_follow, $all_follow){
                if($is_follow){
                    $query->whereIn('id', $all_follow);
                }
            })
            ->field('id,folder_name,folder_type,create_time, new_thumb, share_times,visit_times')
            ->order('id desc')
            ->paginate(10)->each(function ($item){
                $item->son_count = $item->SonCount;
            });
        $this->result([
            'info' => $info,
            'lists' => $lists,
            'set' => $set,
        ], 0, '成功');
    }

    /**获取示例相册图片列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExamplePicturesLists()
    {
        $param = $this->request->getMore([
            ['fid', 0],
            ['page', 1],
        ]);
        $fid = $param['fid'];
        $set = (new WdXcxUserExampleSet())->getSet($fid, request()->userID());
        $lists = WdXcxUserAlbumPic::where('user_id', -1)
            ->where('folder_id', $fid)
            ->order('id desc')
            ->field('id, pic_id')
            ->paginate(30)->each(function ($item){
                $picture_url = '';
                if($item->picture){
                    $picture_url = $item->picture->TruePic;
                }
                $item->picture_url = $picture_url;
                $item->picture_url_original = removePicStyle($picture_url);
                unset($item->picture);
            });
        $this->result([
            'lists' => $lists,
            'set' => $set,
        ], 0, '成功');
    }

    public function getEwmImage()
    {
        $filename = $this->request->getMore([
            ['filename', ''],
        ])['filename'];
        if (!$filename || !preg_match('/^[A-Za-z0-9_\\-\\.]+$/', $filename)) {
            throwError('参数错误');
        }
        $path = public_path() . 'image/ewm/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            throwError('文件不存在');
        }
        $mime = 'image/jpeg';
        if (preg_match('/\\.png$/i', $filename)) {
            $mime = 'image/png';
        }
        $content = file_get_contents($path);
        return Response::create($content, 'html', 200)->header(['Content-Type' => $mime]);
    }

    public function getStaticImage()
    {
        $path = $this->request->getMore([
            ['path', ''],
        ])['path'];
        if (!$path) {
            throwError('参数错误');
        }
        $path = ltrim($path, '/');
        if (!preg_match('/^image\/(static|ewm)\/[A-Za-z0-9_\-\/\.]+$/', $path) && $path !== 'image/img_default.png') {
            throwError('路径不允许');
        }
        $full = public_path() . $path;
        if (!is_file($full) || !is_readable($full)) {
            throwError('文件不存在');
        }
        $mime = 'image/jpeg';
        if (preg_match('/\\.png$/i', $full)) $mime = 'image/png';
        if (preg_match('/\\.gif$/i', $full)) $mime = 'image/gif';
        $content = file_get_contents($full);
        return Response::create($content, 'html', 200)->header(['Content-Type' => $mime]);
    }

    /**保存示例相册的设置
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveetExampleFolderSet()
    {
        $param = $this->request->postMore([
            ['folder_id', 0],
            ['folder_show', 0],
            ['pic_show', 0],
            ['subscribe_status', 0],
        ]);
        if(empty($param['folder_id'])){
            throwError('请选择要保存的相册');
        }
        (new WdXcxUserExampleSet())->saveSet($param, request()->userID());
        $this->result([], 0, '保存成功');
    }

    /**访问记录
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function commonVisitRecord()
    {
        $base = WdXcxBase::where('uniacid', $this->uniacid)->find();
        $add_folder = mt_rand(1, $base->visit_bei);
        $add_share = mt_rand(1, $base->visit_bei);
        $add_down = mt_rand(1, $base->visit_bei);
        $base->inc('folder_count', $add_folder)
            ->inc('share_count', $add_share)
            ->inc('down_count', $add_down)
            ->update();
        $this->result([], 0, '成功');
    }












}
