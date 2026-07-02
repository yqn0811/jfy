<?php
/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2021/8/26
 * Time: 15:36
 */

namespace app\index\model;


use think\Exception;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxPicgroup extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';

    //关联图片
    public function pictures()
    {
        return $this->hasMany(WdXcxPic::class, 'gid', 'id');
    }

    //关联栏目
    public function groups()
    {
        return $this->hasMany(WdXcxPicgroup::class, 'f_gid', 'id');
    }

    public static function childGroups($cid)
    {
        return self::where('level_path', 'like', '%-'.$cid.'-%')->column('id');
    }

    /**栏目列表
     * @param $condition
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function groupLists($condition)
    {
        $groups = self::where($condition)
            ->order('sort desc, id desc')
            ->field('id, f_gid, name, level')
            ->select();

        if(isset($condition['name'])){
            return $groups;
        }else{
            return self::getParents($groups);
        }
    }

    public static function groupNames($condition)
    {
        $groups = self::where($condition)
            ->order('id desc')
            ->field('id, f_gid, name, level')
            ->select();
        $groups = self::getParentName($groups, 0);
        $name = [];
        foreach ($groups as $item){
            $temp['id'] = $item['id'];
            if($item->level == 1){
                $temp['name'] = $item['name'];
            }
            if($item->level == 2){
                $temp['name'] = '|--' . $item['name'];
            }
            if($item->level == 3){
                $temp['name'] = '|----' . $item['name'];
            }
            array_push($name, $temp);
        }
        return $name;
    }

    /**增加栏目
     * @param $parame
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addGroup($parame)
    {
        $new_group = new self([
            'uniacid' => $parame['uniacid'],
            'name' => $parame['group_name'],
            'shop_id' => $parame['shop_id'] ?? 0,
            'type' => $parame['type'] ?? 0
        ]);
        if($parame['f_gid']){
            $new_group->f_gid = $parame['f_gid'];
            $f_group = self::find($parame['f_gid']);
            if(!$f_group){
                throw new Exception('父级栏目不存在');
            }
            if($f_group->level == 3){
                throw new Exception('当前等级不可创建子级分类');
            }
            $new_group->level = $f_group->level + 1;
            $new_group->level_path = $f_group->level == 1 ? '-'.$f_group->id.'-' : $f_group->level_path.$f_group->id.'-';
        }else{
            $new_group->f_gid = 0;
            $new_group->level = 1;
            $new_group->level_path = '';
        }
        $new_group->save();
    }

    /**编辑栏目名称
     * @param $parame
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public static function editGroup($parame)
    {
        $group = self::find($parame['gid']);
        if(!$group){
            throw new Exception('栏目不存在');
        }
        if($group->name == $parame['group_name']){
            throw new Exception('栏目名称相同，无需修改');
        }
        self::where('id', $parame['gid'])->update(['name' => $parame['group_name']]);
    }

    /**删除栏目
     * @param $parame
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public static function deleteGroup($parame)
    {
        $group = self::where('id', $parame['gid'])->find();
        if(!$group){
            throw new Exception('栏目不存在');
        }
        if($group->pictures()->where('uid', 0)->count() > 0){
            throw new Exception('该栏目下有图片，不可删除');
        }
        if($group->groups()->count() > 0){
            throw new Exception('该栏目下子栏目，不可删除');
        }
        $group->delete();
    }



    //递归 获取栏目
    private static function getParents($groups, $id=0)
    {
        $tree = [];
        foreach ($groups as $key => $item){
            if($item['f_gid'] == $id){
                unset($groups[$key]);
                if($item['level'] < 3){
                    $item['children'] = self::getParents($groups, $item['id']);
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    //递归 获取栏目名称
    private static function getParentName($groups, $id=0)
    {
        static $tree = [];
        foreach ($groups as $key => $item){
            if($item['f_gid'] == $id){
                array_push($tree, $item);
                unset($groups[$key]);
                self::getParentName($groups, $item['id']);
            }
        }
        return $tree;
    }
}
