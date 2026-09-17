<?php

namespace app\index\controller;

use app\BaseController;
use think\App;
use think\facade\Config;

class IndexBaseController extends BaseController
{
    public $uniacid;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->uniacid = request()->param('uniacid');
        $this->checkLogin();
    }

    private function checkLogin()
    {
        $uid = session('uid');
        if(!$uid){
            $this->error('请先登录', '/');
        }
    }

    /**检查是否有操作权限
     * @param $rule_id
     * @return void
     */
    protected function checkUserRule($rule_id)
    {
        $node_ids = session('node_id');
        if(!in_array($rule_id, $node_ids)){
            $this->error('暂无该权限');
        }
    }

    /**检查菜单权限
     * @param $top_rule 父级权限ID
     * @param $rule_id当前权限ID
     * @return void
     */
    protected function checkMenuPath($top_rule, $rule_id)
    {
        $node_ids = session('node_id');
        if(!in_array($rule_id, $node_ids)){
            $node_rule = $this->getNodeIdTree();
            $top_rule_id = array_column($node_rule, 'id');
            $k = array_search($top_rule, $top_rule_id);
            $child = $node_rule[$k]['children'];
            if(count($child) > 0){
                $this->redirect($child[0]['path']);
            }else{
                $this->error('暂无该权限，请联系管理员');
            }
        }
    }

    /**获取权限的树桩结构
     * @return array
     */
    private function getNodeIdTree()
    {
        $node_ids = session('node_id');
        $all_rule = Config::get('system_authority');
        $result = [];
        foreach ($all_rule as $item){
            if(in_array($item['id'], $node_ids)){
                $child = $item['children'];
                $temp_child = [];
                foreach ($child as $child_item){
                    if(in_array($child_item['id'], $node_ids)){
                        $temp_child[] = $child_item;
                    }
                }
                $item['children'] = $temp_child;
                $result[] = $item;
            }
        }
        return $result;
    }


}