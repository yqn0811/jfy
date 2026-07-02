<?php

namespace app\index\controller;

use app\BaseController;
use app\index\model\WdXcxSystemManager;
use think\facade\Config;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class LoginController extends BaseController
{
    /**登录页面
     * @return string
     */
    public function login()
    {
        return View::fetch('login/index');
    }

    /**执行登录
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function doLogin()
    {
        Session::clear();
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        if(!$username){
            $this->error('请输入用户名称');
        }
        if(!$password){
            $this->error('请输入密码');
        }
        $user = Db::name('wd_xcx_admin')->where([
            'username' => $username,
            'password' => md5($password)
        ])->where("flag", 1)
            ->where('is_del', 0)
            ->find();
        if(!$user){
            $this->checkManager($username, $password);
            $this->error('用户名或密码错误');
        }
        $ip = request()->ip();
        $jdata['lastloginip'] = ip2long($ip);
        $jdata['lastlogintime'] = time();
        Db::name('wd_xcx_admin')->where("uid", $user['uid'])->update($jdata);
        session('uid', $user['uid']);
        session('name', $user['realname']);
        session('user_group', 1);
        session('user_name', $user['username']);
        $this->setRuleSession(-1);
        $this->redirect('/index/Datashow/index');
    }

    private function checkManager($username, $password)
    {
        $manager = WdXcxSystemManager::where('user_name', $username)
            ->where('user_pwd', md5($password))
            ->find();
        if(!$manager){
            $this->error('用户名或密码错误');
        }
        if($manager->status != 1){
            $this->error('用户已被停用');
        }
        $manager->last_login_time = time();
        $manager->save();
        session('uid', $manager->id);
        session('name', $manager->manager_name);
        session('user_group', 2);
        session('user_name', $manager->user_name);
        $rule_info = $manager->authority->rule_info;
        if(!$rule_info){
            $this->error('用户权限不足');
        }
        $this->setRuleSession($manager->authority->rule_info);
        if(in_array(2, $rule_info)){
            $this->redirect('/index/Datashow/index');
        }else{
            $this->redirect('/index/Datashow/welcomeIndex');
        }
    }

    private function setRuleSession($rule_info)
    {
        $node_ids = [];
        if($rule_info == -1){ //总管理员  全部权限
            $rule_data = Config::get('system_authority');
            foreach ($rule_data as $item){
                $node_ids[] = $item['id'];
                foreach ($item['children'] as $children){
                    $node_ids[] = $children['id'];
                }
            }
        }else{
            $node_ids = $rule_info;
        }
        \session('node_id', $node_ids);
    }
}