<?php

namespace app\index\controller;

use app\common\service\PermissionsService;
use app\index\model\WdXcxSystemAuthority;
use app\index\model\WdXcxSystemManager;
use think\App;
use think\facade\Config;
use think\facade\Db;
use think\facade\View;

class PermissionsController extends IndexBaseController
{
    private $rule_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->rule_service = new PermissionsService($app);
    }

    /**管理员管理
     * @return string
     */
    public function manager()
    {
        $this->checkMenuPath(34, 35);
        $this->checkUserRule(35);
        $lists = WdXcxSystemManager::where('uniacid', $this->uniacid)
            ->order('id desc')
            ->paginate(10);
        return View::fetch('permissions/manager', [
            'execute_data' => $lists
        ]);
    }

    /**添加管理员
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addManager()
    {
        $this->checkUserRule(35);
        $id = input('m_id');
        $info = null;
        if($id){
            $info = WdXcxSystemManager::find($id);
            if(!$info){
                $this->error('指定管理员不存在');
            }
        }
        $rule_lists = WdXcxSystemAuthority::where('uniacid', $this->uniacid)
            ->whereNotNull('rule_info')
            ->order('id desc')
            ->field('id, authority_name')
            ->select();
        return View::fetch('permissions/add_manager', [
            'info' => $info,
            'rule_lists' => $rule_lists
        ]);
    }

    /**保存管理员
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function savePermissionsManager()
    {
        $this->checkUserRule(35);
        $id = input('m_id');
        $manager_name = input('manager_name');
        $authority_id = input('authority_id');
        $user_name = input('user_name');
        $user_pwd = input('user_pwd');
        $status = input('status');
        if(!$manager_name || !$authority_id || !$user_name){
            $this->error('请输入管理员名称、权限角色、管理员账号');
        }
        if($id){
            $info = WdXcxSystemManager::find($id);
            if(!$info){
                $this->error('指定管理员不存在');
            }
            $has = WdXcxSystemManager::where('user_name', $user_name)
                ->where('id', '<>', $id)
                ->find();
            if($has){
                $this->error('管理员账号已存在');
            }
            if($user_pwd){
                $info->user_pwd = md5($user_pwd);
            }
        }else{
            $info = new WdXcxSystemManager();
            $info->uniacid = $this->uniacid;
            $has = WdXcxSystemManager::where('user_name', $user_name)
                ->find();
            if($has){
                $this->error('管理员账号已存在');
            }
            $info->user_pwd = $user_pwd ? md5($user_pwd) : md5('123456');
        }
        $info->user_name = $user_name;
        $info->manager_name = $manager_name;
        $info->authority_id = $authority_id;
        $info->status = $status;
        $info->save();
        $this->success('保存成功', Url('PermissionsController/manager'));
    }

    /**删除管理员
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteManager()
    {
        $this->checkUserRule(35);
        $id = input('id');
        $info = WdXcxSystemManager::find($id);
        if(!$info){
            $this->error('指定管理员不存在');
        }
        $info->delete();
        $this->success('删除成功');
    }

    /**权限角色
     * @return string
     */
    public function authority()
    {
        $this->checkUserRule(36);
        $lists = WdXcxSystemAuthority::where('uniacid', $this->uniacid)
            ->order('id desc')
            ->paginate(10);
        return View::fetch('permissions/authority', [
            'execute_data' => $lists
        ]);
    }

    /**保存权限角色
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveAuthority()
    {
        $this->checkUserRule(36);
        $uniacid = $this->uniacid;
        $id = input('id');
        $partname = input('partname');
        if(!$partname){
            $this->error('请输入角色名称');
        }
        if($id){
            $info = WdXcxSystemAuthority::find($id);
            if(!$info){
                $this->error('指定角色不存在');
            }
        }else{
            $info = new WdXcxSystemAuthority();
            $info->uniacid = $uniacid;
        }
        $info->authority_name = $partname;
        $info->save();
        $this->success('保存成功');
    }

    /**获取角色权限
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAuthority()
    {
        $uniacid = $this->uniacid;
        $id = input('id');
        $info = WdXcxSystemAuthority::find($id);
        if(!$info){
            $this->error('指定角色不存在');
        }
        $this->success('获取成功', '', $info);
    }

    /**获取权限信息
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAuthorityRuleInfo()
    {
        $uniacid = $this->uniacid;
        $id = input('id');
        $info = WdXcxSystemAuthority::find($id);
        if(!$info){
            $this->error('指定角色不存在');
        }
        $all_rule = Config::get('system_authority');
        $parent_rule = array_column($all_rule, 'id');
        if($info->rule_info){
            $info->rule_info = array_diff($info->rule_info, $parent_rule);
        }
        $this->success('获取成功', '', compact('info', 'all_rule'));
    }

    /**保存角色权限
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveAuthorityRuleInfo()
    {
        $this->checkUserRule(36);
        $uniacid = $this->uniacid;
        $id = input('id');
        $info = WdXcxSystemAuthority::find($id);
        if(!$info){
            $this->error('指定角色不存在');
        }
        $all_rule = input('role_parent_child');
        if(!$all_rule){
            $this->error('请选择权限');
        }
        $info->rule_info = $all_rule;
        $info->save();
        $this->success('保存成功');
    }

    /**删除角色
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function partDel()
    {
        $this->checkUserRule(36);
        $uniacid = $this->uniacid;
        $id = input('id');
        $info = WdXcxSystemAuthority::find($id);
        if(!$info){
            $this->error('指定角色不存在');
        }
        if($info->manager()->count() > 0){
            $this->error('该角色下有管理员，无法删除');
        }
        $info->delete();
        $this->success('删除成功');
    }

    /**修改用户信息
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserInfo()
    {
        $user_name = input('user_name');
        $user_password = input('user_password');
        $user_group = session('user_group');
        $uid = session('uid');
        if($user_group == 1){
            $user = Db::name('wd_xcx_admin')->where("uid", $uid)->find();
            if(!$user){
                $this->error('用户不存在');
            }
            if($user_name){
                $update_data['username'] = $user_name;
            }
            if($user_password){
                $update_data['password'] = md5($user_password);
            }
            Db::name('wd_xcx_admin')->where("uid", $uid)->update($update_data);
        }else{
            $user = WdXcxSystemManager::find($uid);
            if(!$user){
                $this->error('用户不存在');
            }
            if($user_name){
               $has = WdXcxSystemManager::where('user_name', $user_name)
               ->where('id', '<>', $uid)
               ->find();
               if($has){
                   $this->error('用户名已存在');
               }
            }
            if($user_name){
                $user->user_name = $user_name;
            }
            if($user_password){
                $user->user_pwd = md5($user_password);
            }
            $user->save();
        }
        $this->success('操作成功');
    }
}