<?php

namespace app\index\controller;

use app\common\model\coupon\WdXcxCoupon;
use app\common\model\system_set\WdXcxNewuserGiftSet;
use app\common\model\system_set\WdXcxPrint;
use app\index\model\WdXcxBase;
use think\facade\Config;
use think\facade\Db;
use think\facade\View;

class SystemSetController extends IndexBaseController
{
    /**基础设置
     * @return string
     */
    public function set()
    {
        $this->checkMenuPath(37, 38);
        $this->checkUserRule(38);
        $base = WdXcxBase::where("uniacid",$this->uniacid)->find();
        return View::fetch('system_set/set', [
            'systems' => $base ? $base->toArray() : '',
        ]);
    }

    /**保存基础设置
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveBase()
    {
        $this->checkUserRule(38);
        $uniacid = $this->uniacid;
        $base = WdXcxBase::where("uniacid",$this->uniacid)->find();
        if(!$base){
            $base = new WdXcxBase();
            $base->uniacid = $uniacid;
        }
        $base->about = input('about');
        $base->space_size = input('space_size/d');
//        $base->evaluate_check = input('evaluate_check/d');
        $base->pic_check = input('pic_check/d');
        $base->news_link = input('news_link/s');
        $base->kf_link = input('kf_link/s');
        $base->visit_bei = input('visit_bei/d');
        $base->kf_ewm = input('commonuploadpic1');
        $base->share_thumb = input('commonuploadpic2');
        $base->save();
        $this->success('操作成功');
    }

    /**远程附件设置
     * @return string
     */
    public function remote()
    {
        $this->checkUserRule(39);
        $id = $this->uniacid;
        $base = Db::name('wd_xcx_base')->where("uniacid",$id)->field("remote, use_remote, give_remote_type")->find();
        if(!$base){
            $base['remote'] = 1;
            $base['use_remote'] = 2;
        }
        $remote2 = Db::name('wd_xcx_remote')->where("uniacid",$id)->where("type",2)->find();
        $remote3 = Db::name('wd_xcx_remote')->where("uniacid",$id)->where("type",3)->find();
        $remote4 = Db::name('wd_xcx_remote')->where("uniacid",$id)->where("type",4)->find();
        return View::fetch('system_set/remote', [
            'base' => $base,
            'remote2' => $remote2,
            'remote3' => $remote3,
            'remote4' => $remote4,
        ]);
    }

    /**保存远程附件
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveRemote()
    {
        $this->checkUserRule(39);
        $appletid = $this->uniacid;
        $remote = input("remote");
        $use_remote = 2;
        if($remote == 1){
            Db::name('wd_xcx_base')->where("uniacid",$appletid)->update(array("remote"=>$remote));
        }else if($remote == 2 && $use_remote == 2){
            Db::name('wd_xcx_base')->where("uniacid",$appletid)->update(array("remote"=>$remote));
            $data = array();
            $data['area_code'] = input('area_code', 'ECN');
            if(input("bucket2")){
                $data['bucket'] = input("bucket2");
            }else{
                $this->error("存储空间名称(Bucket)不能为空");
            }
            if(input("domain2")){
                $data['domain'] = input("domain2");
            }else{
                $this->error("绑定域名（或测试域名）不能为空");
            }
            if(input("ak2")){
                $data['ak'] = input("ak2");
            }else{
                $this->error("AccessKey（AK）不能为空");
            }
            if(input("sk2")){
                $data['sk'] = input("sk2");
            }else{
                $this->error("SecretKey（SK）不能为空");
            }
            $data['folder_name'] = input('folder_name_1');
            $data['type'] = $remote;
            $is = Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",2)->find();
            if($is){
                Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",2)->update($data);
            }else{
                $data['uniacid'] = $appletid;
                Db::name("wd_xcx_remote")->insert($data);
            }
        }else if($remote == 3 && $use_remote == 2){
            Db::name('wd_xcx_base')->where("uniacid",$appletid)->update(array("remote"=>$remote));
            $data = array();
            if(input("bucket3")){
                $data['bucket'] = input("bucket3");
            }else{
                $this->error("存储空间名称(Bucket)不能为空");
            }
            if(input("domain3")){
                $data['domain'] = input("domain3");
            }else{
                $this->error("Endpoint（或自定义域名）不能为空");
            }
            $data['domain_bind'] = input("domain_bind");

            if(input("ak3")){
                $data['ak'] = input("ak3");
            }else{
                $this->error("Access Key ID不能为空");
            }
            if(input("sk3")){
                $data['sk'] = input("sk3");
            }else{
                $this->error("Access Key Secret不能为空");
            }
            $data['imgstyle'] = input("imgstyle3");
            $data['domainIs'] = input("domainIs");
            $data['folder_name'] = input('folder_name_2');
            $data['type'] = $remote;
            $is = Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",3)->find();
            if($is){
                Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",3)->update($data);
            }else{
                $data['uniacid'] = $appletid;
                Db::name("wd_xcx_remote")->insert($data);
            }
        } else if ($remote == 4 && $use_remote == 2){
            Db::name('wd_xcx_base')->where("uniacid",$appletid)->update(array("remote"=>$remote));
            $data = array();
            if(input("bucket4")){
                $data['bucket'] = input("bucket4");
            }else{
                $this->error("存储空间名称(Bucket)不能为空");
            }
            $data['domain_bind'] = input("domain_bind4");

            if(input("ak4")){
                $data['ak'] = input("ak4");
            }else{
                $this->error("SecretKEY不能为空");
            }
            if(input("sk4")){
                $data['sk'] = input("sk4");
            }else{
                $this->error("SecretID不能为空");
            }
            $data['folder_name'] = input('folder_name_3');
            $data['type'] = $remote;
            $data['region'] = input('region4') ?: '';
            $is = Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",4)->find();
            if($is){
                Db::name("wd_xcx_remote")->where("uniacid",$appletid)->where("type",4)->update($data);
            }else{
                $data['uniacid'] = $appletid;
                Db::name("wd_xcx_remote")->insert($data);
            }
        }
        //强制修改缓存
        cacheRemoteSet($appletid, true);
        $this->success('操作成功');
    }

    /**打印机列表
     * @return string
     */
    public function printer()
    {
        $this->checkUserRule(40);
        $id = $this->uniacid;
        $list = Db::name('wd_xcx_print')->where("uniacid",$id)->paginate([
            'list_rows' => 10,
            'query' => input(),
        ]);
        $lists = $list->toArray()['data'];
        foreach ($lists as $key => &$value) {
            $value['protype'] = json_decode($value['protype'], true);
        }
        return View::fetch('system_set/printer', [
            'lists' => $lists,
            'list' => $list
        ]);
    }

    /**添加打印机
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addPrinter()
    {
        $this->checkUserRule(40);
        $appletid = $this->uniacid;
        $id = intval(input('pid'));
        $info = null;
        if($id > 0){
            $info = Db::name('wd_xcx_print')->where("uniacid",$appletid)->where("id",$id)->find();
            $info['protype'] = json_decode($info['protype'], true);
        }
        return View::fetch('system_set/add_printer', [
            'info' => $info,
            'id' => $id,
        ]);
    }

    public function savePrinter()
    {
        $this->checkUserRule(40);
        $appletid = $this->uniacid;
        $id = input("pid");
        $data = array();
        //小程序ID
        $data['uniacid'] = $appletid;
        if(input("models") == 1){
            $data['flag'] = input("flag");
            $data['pname'] = input("pname");
            $data['models'] = input("models");
            $data['nid'] = input("nid");
            $data['nkey'] = input("nkey");
            $data['uid'] = input("uid");
            $data['apikey'] = input("apikey");
            $data['title'] = input("title");
            $data['protype'] = json_encode([1]);
            $data['num'] = input("num");
            $data['geese_account'] = '';
            $data['geese_ukey'] = '';
            $data['geese_sn'] = '';
        }else if(input("models") == 2){
            $data['flag'] = input("flag");
            $data['pname'] = input("pname");
            $data['models'] = input("models");
            $data['nid'] = 0;
            $data['nkey'] = 0;
            $data['uid'] = 0;
            $data['apikey'] = 0;
            $data['title'] = input("title");
            $data['protype'] = json_encode([1]);
            $data['num'] = input("num");
            $data['geese_account'] = input('geese_account');
            $data['geese_ukey'] = input('geese_ukey');
            $data['geese_sn'] = input('geese_sn');
        }else{
            $this->error('发生错误');
        }

        if($id > 0){
            $res = Db::name('wd_xcx_print')->where('id', $id)->update($data);
        }else{
            $res = Db::name('wd_xcx_print')->insert($data);
        }
        $this->success('操作成功',  Url('SystemSetController/printer'));
    }

    /**删除打印机
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deletePrinter()
    {
        $this->checkUserRule(40);
        $appletid = $this->uniacid;
        $id = input("pid");
        $res = WdXcxPrint::where('uniacid', $appletid)
            ->where("id",$id)
            ->find();
        if(!$res){
            $this->error('指定内容不存在');
        }
        $res->delete();
        $this->success('操作成功',  Url('SystemSetController/printer'));
    }

    /**排行奖励设置
     * @return string
     */
    public function rewardSet()
    {
//        $terminal_name = Config::get('ylb.terminal_name');
        $terminal_name = Config::get('ylb.terminal_ranking_name');
        $set = WdXcxBase::where('uniacid', $this->uniacid)->find()->about;
        $set = $set ? array_values($set) : null;
        return View::fetch('system_set/reward_set', [
            'terminal_name' => $terminal_name,
            'set' => $set
        ]);
    }

    /**保存排行奖励设置
     * @return void
     */
    public function rewardSave()
    {

    }

    /**新人有礼设置
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function newUserGift()
    {
        $this->checkUserRule(53);
        $coupon = WdXcxCoupon::where('uniacid', $this->uniacid)
            ->order('id desc')
            ->field('id, title')
            ->select();
        $info = WdXcxNewuserGiftSet::where('uniacid', $this->uniacid)->find();
        $coupon_count = 0;
        if($info){
            $coupon_count = count($info->coupon_info);
            $info = $info->toArray();
        }
        return View::fetch('system_set/new_user_gift', [
            'info' => $info,
            'coupon' => $coupon,
            'coupon_count' => $coupon_count,
        ]);
    }

    public function saveNewUserGift()
    {
        $info = WdXcxNewuserGiftSet::where('uniacid', $this->uniacid)->find();
        if(!$info){
            $info = new WdXcxNewuserGiftSet();
            $info->uniacid = $this->uniacid;
        }
        $info->status = input('status');
        $coupon_id = input('coupon_id/a');
        $coupon_num = input('coupon_num/a');
        $info->give_gamecoin = input('give_gamecoin') ? input('give_gamecoin') : 0;
        if($info->status){
            if(empty($coupon_id) && empty($coupon_num) && !$info->give_gamecoin){
                $this->error('请先设置赠送内容');
            }
        }
        $coupon_info = [];
        if(!empty($coupon_id) && !empty($coupon_num)){

            foreach ($coupon_id as $k => $v){
                if(!empty($coupon_num[$k])){
                    $coupon_info[] = [
                        'coupon_id' => $v,
                        'coupon_num' => $coupon_num[$k],
                    ];
                }
            }
        }
        $info->show_thumb = input('commonuploadpic1');
        $info->coupon_info = $coupon_info;
        $info->save();
        $this->success('保存成功');
    }

}