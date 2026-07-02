<?php

namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class Remote extends IndexBaseController
{
    public function index(){
        // if(check_login()){
        $appletid = $this->uniacid;
        $from = input('from');
        if(!$from){
            $from = 0;
        }
        $gid = input("gid");

        $type = input("type");
        $group = Db::name('wd_xcx_picgroup')->where("uniacid",$appletid)->where('delete_time', null)->order('id desc')->select()->toArray();
        if($group){
            foreach ($group as $k => $v) {
                $group[$k]['count'] = Db::name("wd_xcx_pic")->where("gid",$v['id'])->where('delete_time', null)->count();
            }
        }
        if($gid){
            $all = Db::name('wd_xcx_pic')->where("uniacid",$appletid)->where("gid",$gid)->where('delete_time', null)->order('id desc')->paginate(12,false,[ 'query' => array('appletid'=>input("appletid"),'type'=>input("type"), 'gid'=>$gid, 'from'=>$from)]);
        }else{
            $all = Db::name('wd_xcx_pic')->where("uniacid",$appletid)->where('delete_time', null)->order('id desc')->paginate(12,false,[ 'query' => array('appletid'=>input("appletid"),'type'=>input("type"), 'from'=>$from)]);
            $gid = 0;
        }
        $list = $all->toArray();

        foreach($list['data'] as $k =>$v){
            $list['data'][$k]['imgurl'] = remote($appletid, $v['imgurl'], 1);
        }
        $count = $all->total();
        View::assign([
            'type' => $type,
            'group' => $group,
            'gid' => $gid,
            'all' => $all,
            'list' => $list['data'],
            'uniacid' => $appletid,
            'count' => $count,
            'from' => $from,
            'is_admin' => session('is_admin')
        ]);
        return View::fetch('remote/index');
    }

    /**多媒体
     * @return mixed
     */
    public function media()
    {
        $appletid = input("appletid");
        $this->assign('uniacid',$appletid);
        return $this->fetch('');
    }

    public function imgupload(){
        $uniacid = input("uniacid");
        $groupid = input("groupid");
        //检查上传空间
        $size = (new UploadFileRecordService($uniacid))->getSurplusRemoteSize();
        $set = (new PictureResourceService())->getPicBaseSet(['uniacid' => $uniacid]);
        $files = \request()->file('uploadfile');
        if(count($files) > 0){
            $file = $files[0];
            $file_size = $file->getInfo()['size'];
            if($file_size > $set['pic_size']){
                return '';
            }
            if($size != -1 && $file_size > $size*1024*1024){
                return '';
            }
        }else{
            return '';
        }
        $url = getRemoteType($uniacid, $groupid, 1);
        if(!$url){
            return '';
        }
        if(input('from')){
            $url = json_decode($url, true);
            if(!empty($url)){
                foreach ($url as $key => $value){
                    $url[$key]['url'] = remote($uniacid, $value['url'], 1);
                }
            }
            $url = json_encode(($url));
        }

        return $url;
    }


    public function makegroup(){
        $uniacid = input("uniacid");
        $name = input("name");
        $is = Db::name("wd_xcx_picgroup")->where("uniacid",$uniacid)->where("name",$name)->where('delete_time', null)->find();
        if($is){
            echo json_encode(array("is"=>0));
        }else{
            $data = array();
            $data['uniacid'] = $uniacid;
            $data['name'] = $name;
            $id = Db::name("wd_xcx_picgroup")->insertGetId($data);
            if($id){
                echo json_encode(array("is"=>1,"id"=>$id));
            }else{
                echo json_encode(array("is"=>2));
            }
        }

    }


    public function save(){
        $data = array();
        //小程序ID
        $data['uniacid'] = input("appletid");

        //排序
        $num = input("num");
        if($num){
            $data['num'] = $num;
        }

        $name = input("name");
        if($name){
            $data['name'] = $name;
        }

        //栏目图片
        $catepic = $this->onepic_uploade("catepic");
        if($catepic){
            $data['catepic'] = $catepic;
        }

        // var_dump($data);exit;


        $id = input("cateid");

        if($id!=0){
            $res = Db::name('wd_xcx_score_cate')->where("id",$id)->update($data);
        }else{
            $res = Db::name('wd_xcx_score_cate')->insert($data);
        }



        if($res){
            $this->success('栏目信息更新成功！');
        }else{
            $this->error('栏目信息更新失败，没有修改项！');
            exit;
        }



    }

    // 删除操作
    public function del(){
        $data['id'] = input("cateid");
        $res = Db::name('wd_xcx_score_cate')->where($data)->delete();
        if($res){
            $this->success('删除成功');
        }else{
            $this->success('删除失败');
        }
    }




    //单个图片上传操作
    function onepic_uploade($file){
        $thumb = request()->file($file);
        if(isset($thumb)){
            $dir = upload_img();
            $info = $thumb->validate(['ext' => 'jpg,png,gif,jpeg'])->move($dir);
            if($info){
                $imgurl = ROOT_HOST."/upimages/".date("Ymd",time())."/".$info->getFilename();
                return $imgurl;
            }
        }
    }

    //删除图片
    public function delpic(){
        $ids = input('ids');
        if($ids){
            $ids = explode(',', $ids);
//            $res = Db::name('wd_xcx_pic') ->where('id', 'IN', $ids) ->delete();   //删除数据库
            WdXcxPic::destroy($ids);
            return 1;
        }else{
            return 2;
        }
    }


    //修改相册名称
    public function changegname(){
        $gid = input('id');
        $gname = input('gname');
        $res =  Db::name('wd_xcx_picgroup')->where("id",$gid)->update(['name'=>$gname]);
        if($res){
            return 1;
        }else{
            return 2;
        }
    }
}