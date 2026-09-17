<?php

namespace app\index\controller;

use app\common\model\order\WdXcxUserCateringOrderDishes;
use app\common\model\order\WdXcxUserCateringOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\service\catering\CateringService;
use app\common\service\WxService;
use cores\ExcelCommon;
use think\Exception;
use think\facade\Db;
use think\facade\View;

class RestaurantController extends IndexBaseController
{
    /**订单管理
     * @return string
     */
    public function index()
    {
        $this->checkUserRule(8);
        $uniacid = $this->uniacid;
        $search_flag = input('search_flag');
        $key = input('search_keys');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $order_id = input('order_id');
        $param = input();
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : -1;
        $suids = [];
        $order_ids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
            $order_ids = (new WdXcxUserCateringOrderDishes())->searchByKey($key);
        }
        $to_excel = input('excel');
        $lists_sql = WdXcxUserCateringOrderLists::with('dishes')
            ->where('uniacid', $uniacid)
            ->where(function ($query)use($search_flag, $key, $start_get, $end_get, $suids, $order_ids, $order_id, $pay_type){
                if($order_id){
                    $query->where('order_id', $order_id);
                }else{
                    if($search_flag){
                        $query->where('status', $search_flag);
                    }
                    if($start_get){
                        $query->where('create_time', '>', strtotime($start_get));
                    }
                    if($end_get){
                        $query->where('create_time', '<', strtotime($end_get));
                    }
                    if($key){
                        $query->where(function ($query)use($key, $suids, $order_ids){
                            $query->whereLike('order_id', '%'.$key.'%')->whereOr('user_id', 'in', $suids)->whereOr('order_id', 'in', $order_ids);
                        });
                    }
                    if($pay_type != -1){
                        $query->where('pay_type', $pay_type);
                    }
                }
            })->order('id desc');
        if($to_excel){
            $lists = $lists_sql->select();
        }else{
            $lists = $lists_sql->paginate([
                'list_rows' => 10,
                'query' => request()->param()
            ]);
        }

        $total_money = 0;
        if($start_get || $end_get){
            $total_money = WdXcxUserCateringOrderLists::with('dishes')
                ->where('uniacid', $uniacid)
                ->where(function ($query)use($search_flag, $key, $start_get, $end_get, $suids, $order_ids, $order_id, $pay_type){
                    if($order_id){
                        $query->where('order_id', $order_id);
                    }else{
                        if($search_flag){
                            $query->where('status', $search_flag);
                        }
                        if($start_get){
                            $query->where('create_time', '>', strtotime($start_get));
                        }
                        if($end_get){
                            $query->where('create_time', '<', strtotime($end_get));
                        }
                        if($key){
                            $query->where(function ($query)use($key, $suids, $order_ids){
                                $query->whereLike('order_id', '%'.$key.'%')->whereOr('user_id', 'in', $suids)->whereOr('order_id', 'in', $order_ids);
                            });
                        }
                        if($pay_type != -1){
                            $query->where('pay_type', $pay_type);
                        }
                    }

                })->sum('pay_price');
        }
        if($to_excel){
            $this->exportExcel($lists);
        }

        return View::fetch('restaurant/order', [
            'search_flag' => $search_flag,
            'search_keys' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'pay_type' => $pay_type,
            'lists' => $lists,
            'total_money' => $total_money,
        ]);
    }

    /**点餐订单导出
     * @param $lists
     * @return void
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function exportExcel($lists)
    {
        $excel_title = [
            '订单号',
            '下单时间',
            '下单人',
            '电话',
            '取餐号/桌号',
            '菜品名称',
            '单价',
            '数量',
            '实付',
            '订单类型',
            '状态',
        ];
        $excel_data = [];
        $total_money = 0;
        foreach ($lists as $item){
            $orderItems = $item->dishes;
            foreach ($orderItems as $k => $order_item_info){
                $temp[$excel_title[0]] = [
                    'value' =>  $item->order_id . ' ',
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                $temp[$excel_title[1]] = [
                    'value' => $item->create_time,
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                $temp[$excel_title[2]] = [
                    'value' => filterEmoji($item->UserInfo['nickname']),
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                $temp[$excel_title[3]] = [
                    'value' => $item->UserInfo['mobile'],
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                if($item->order_type == 1){
                    $str = '桌号：'.$item->table_number."\r\n";
                }else{
                    $str = '取餐号：'.$item->order_number."\r\n";
                    $str .= '取餐时间：';
                    if($item->take_time){
                        $str .= date('Y-m-d H:i:s', $item->take_timestamp);
                    }else{
                        $str .= '立即取餐';
                    }
                }
                $temp[$excel_title[4]] = [
                    'value' => $str,
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                    'need_brek' => true,
                ];
                $temp[$excel_title[5]] = [
                    'value' => $order_item_info->dishes_title."【".$order_item_info->spec_value."】",
                    'need_merge' => false,
                    'can_show' => true,
                    'need_brek' => true,
                ];
                $temp[$excel_title[6]] = [
                    'value' => $order_item_info->dishes_price,
                    'need_merge' => false,
                    'can_show' => true,
                    'is_num' => true,
                ];
                $temp[$excel_title[7]] = [
                    'value' => $order_item_info->num,
                    'need_merge' => false,
                    'can_show' => true,
                    'is_num' => true,
                ];
                $temp[$excel_title[8]] = [
                    'value' => $item->pay_price,
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                    'is_num' => true,
                ];

                $temp[$excel_title[9]] = [
                    'value' => $item->order_type == 1 ? '扫码点餐' : '线上点餐',
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                $temp[$excel_title[10]] = [
                    'value' => $item->status == 1 ? '待使用' : ($item->status == -1 ? '已取消' : '已使用'),
                    'need_merge' => true,
                    'can_show' => $k == 0 ? true : false,
                ];
                $temp['son'] = count($orderItems);
                $excel_data[] = $temp;
            }
            $total_money = bcadd($total_money, $item->pay_price, 2);

        }
        $total_money = '总计金额：'.$total_money;
        (new ExcelCommon())->exportExcel($excel_data, $excel_title, '点餐订单列表', $total_money);
    }

    /**核销订单
     * @return void
     */
    public function checkUserOrder()
    {
        $this->checkUserRule(8);
        $uniacid = $this->uniacid;
        $order_id = input('order_id');
        (new CateringService($this->app))->checkOutUserOrder($order_id);
        $this->success('操作成功');
    }

    /**后台取消点餐订单
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelUserOrder()
    {
        $this->checkUserRule(8);
        $uniacid = $this->uniacid;
        $order_id = input('order_id');
        (new CateringService($this->app))->cancelUserOrder($order_id);
        $this->success('操作成功');
    }

    /**桌号管理
     * @return string
     */
    public function tables()
    {
        $this->checkUserRule(5);
        $appletid = $this->uniacid;
        $op = input("op");
        if($op=="ewm"){
            $tnum = input('tnum');
            $id = input('id');
            $access_token = getTokenIsIn($appletid);
            $ewmurl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
            $sjc = time().rand(1000,9999);
            $data = [
                'page' => "sudu8_page_plugin_food/food/food",
                'width' => '500',
                'scene' => $id
            ];
            $data=json_encode($data);
            $result = $this->_requestPost($ewmurl,$data);
            $save_path = ROOT_PATH."/public/ewmimg/";
            if(!file_exists($save_path)){
                mkdir($save_path);
            }
            file_put_contents(ROOT_PATH."/public/ewmimg/".$sjc.".jpg", $result);
            $path = ROOT_HOST."/ewmimg/".$sjc.".jpg";
            $tdata = array(
                "thumb" => $path
            );
            Db::name('wd_xcx_food_tables')->where('id',$id)->update($tdata);
            $this->success("二维码生成成功");
            exit;
        }else{
            $listV_s = Db::name('wd_xcx_food_tables')
                ->where("uniacid",$appletid)
                ->order('tnum desc')
                ->paginate(10);
            $listV = $listV_s->toArray()['data'];
            return View::fetch('restaurant/tables', [
                'tables' => $listV,
                'lists' => $listV_s
            ]);
        }
    }

    /**添加桌号
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addTable()
    {
        $this->checkUserRule(5);
        $table_id = input("table_id");
        if($table_id){
            //有栏目号时，先判断该栏目是不是属于该小程序！
            $table = Db::name('wd_xcx_food_tables')->where("id", $table_id)->find();
            if(!$table){
                $this->error('指定桌号不存在');
            }
        }else{
            $table_id = 0;
            $table = "";
        }
        return View::fetch('restaurant/add_table', [
            'table_id' => $table_id,
            'table' => $table
        ]);

    }

    public function saveTable()
    {
        $this->checkUserRule(5);
        $data = [];
        //小程序ID
        $data['uniacid'] = $this->uniacid;
        //排序
        $num = input("num");
        if($num){
            $data['tnum'] = $num;
        }else{
            $this->error('请输入桌号');
        }
        $title = input("title");
        if($title){
            $data['title'] = $title;
        }else{
            $this->error('请输入区域名称');
        }
        $id = input("table_id");
        if($id!=0){
            $res = Db::name('wd_xcx_food_tables')->where("id",$id)->update($data);
        }else{
            $res = Db::name('wd_xcx_food_tables')->insert($data);
        }
        $this->success('操作成功！', Url('RestaurantController/tables'));
    }

    /**删除桌号
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function delTable()
    {
        $this->checkUserRule(5);
        $data['id'] = input("table_id");
        Db::name('wd_xcx_food_tables')->where($data)->delete();
        $this->success('删除成功');
    }

    /**分类管理
     * @return string
     */
    public function cate()
    {
        $this->checkUserRule(6);
        $lists = Db::name('wd_xcx_food_cate')
            ->where("uniacid",$this->uniacid)
            ->order('num desc, id desc')
            ->paginate([
                'list_rows' => 10,
            ]);
        return View::fetch('restaurant/cate', [
            'cates' => $lists
        ]);
    }

    /**添加分类
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addCate()
    {
        $this->checkUserRule(6);
        $cateid = input("cateid");
        if($cateid){
            //有栏目号时，先判断该栏目是不是属于该小程序！
            $cateinfo = Db::name('wd_xcx_food_cate')->where("id",$cateid)->find();
            if(!$cateinfo){
                $this->error('指定分类不存在');
            }
        }else{
            $cateinfo = null;
        }
        return View::fetch('restaurant/add_cate', [
            'cateinfo' => $cateinfo,
        ]);
    }

    /**保存分类
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function saveCate()
    {
        $this->checkUserRule(6);
        $data = array();
        //小程序ID
        $data['uniacid'] = input("appletid");
        //排序
        $num = input("num");
        if($num){
            $data['num'] = $num;
        }
        $title = input("title");
        if($title){
            $data['title'] = $title;
        }else{
            $this->error('请输入分类名称');
        }
        $id = input("cateid");
        if($id){
            Db::name('wd_xcx_food_cate')->where("id",$id)->update($data);
        }else{
            Db::name('wd_xcx_food_cate')->insert($data);
        }
        $this->success('操作成功！',Url('RestaurantController/cate'));
    }

    /**删除分类
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delCate()
    {
        $this->checkUserRule(6);
        $data['id'] = input("cateid");
        $is = Db::name("wd_xcx_food")->where('cid', input("cateid"))->find();
        if($is){
            $this->error('该分类下还有商品，删除失败');
        }
        Db::name('wd_xcx_food_cate')->where($data)->delete();
        $this->success('删除成功');
    }


    /**菜品管理
     * @return string
     */
    public function dishes()
    {
        $this->checkMenuPath(4, 7);
        $this->checkUserRule(7);
        $cid = input("cid") ? input("cid") : 0;
        $title = input("key");
        $list = Db::name('wd_xcx_food')
            ->where(function ($query)use($cid, $title){
                if($cid){
                    $query->where('cid', $cid);
                }
                if($title){
                    $query->whereLike('title', '%'.$title.'%');
                }
            })
            ->where("uniacid", $this->uniacid)
            ->order('num desc,id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => input()
            ]);
        $listV = $list->all();
        foreach ($listV as $key => &$value) {
            $value['catename'] = Db::name('wd_xcx_food_cate')->where('id',$value['cid'])->value("title");
            if($value['thumb']){
                $value['thumb'] = remote($this->uniacid, $value['thumb'],1);
            }else{
                $value['thumb'] = '/image/noimage_1.png';
            }
        }
        $cate = Db::name('wd_xcx_food_cate')->where('uniacid',$this->uniacid)->select();

        return View::fetch('restaurant/dishes', [
            'listV' => $listV,
            'list' => $list,
            'key' => $title,
            'cid' => $cid,
            'cate' => $cate
        ]);
    }

    /**添加菜品
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addDish()
    {
        $this->checkUserRule(7);
        $uniacid = $this->uniacid;
        $goodsid = input("goodsid");
        $allimg = [];
        if($goodsid){
            //有栏目号时，先判断该栏目是不是属于该小程序！
            $lanmu = Db::name('wd_xcx_food')->where("id",$goodsid)->find();
            if($lanmu['uniacid'] == $uniacid){
                $id = $lanmu['id'];
                $goodsinfo = $lanmu;
                if($goodsinfo['thumb']){
                    $goodsinfo['thumb'] = remote($uniacid,$goodsinfo['thumb'],1);
                }
                if($goodsinfo['descimg']){
                    $goodsinfo['descimg'] = remote($uniacid,$goodsinfo['descimg'],1);
                }
                if($goodsinfo['types']==2){
                    $proarr = Db::name('wd_xcx_food_type_value')->where('pid',$goodsid)->order('id asc')->select();
                    //构建规格组
                    $counttypes=0;
                    $typesarr=array();
                    $typesjson = [];
                    if($proarr){
                        $types = $proarr[0]['comment'];
                        // 构建规格组json
                        $typesarr = explode(",", $types);
                        $counttypes = count($typesarr);

                        foreach ($typesarr as $key => &$rec) {
                            $str = "type".($key+1);
                            $ziji = Db::name('wd_xcx_food_type_value')->where('pid',$goodsid)->order("id asc")->field($str)->select();
                            $xarr = array();
                            foreach ($ziji as $key => $res) {
                                array_push($xarr, $res[$str]);
                            }
                            $typesjson[$rec] = $xarr;
                        }
                    }
                    // 构建对应的数值
                    $datajson = [];
                    foreach ($proarr as $key => &$rec) {
                        $strs = $rec['type1'].$rec['type2'].$rec['type3'];
                        $strv = $rec['kc'].",".$rec['price'].",".$rec['salenum'].",".$rec['vsalenum'];
                        $datajson[$strs]=$strv;
                    }
                    foreach ($typesjson as $key => &$value) {
                        $value = array_unique($value);
                    }
                }
                if($goodsinfo['types']==1){
                    $proarr = Db::name('wd_xcx_food_type_value')->where('pid',$goodsid)->order("id asc")->find();
                    $goodsinfo['kc'] = 1;
                    $counttypes = 0;
                    $typesarr = [];
                    $typesjson = [];
                    $datajson = [];
                }
                if($goodsinfo['food_pics']){
                    $allimg = json_decode($goodsinfo['food_pics'], true);
                    foreach ($allimg as $k => $item){
                        $allimg[$k] = remote($this->uniacid, $item, 1);
                    }
                }
            }else{
                $usergroup = Session::get('usergroup');
                if($usergroup==1){
                    $this->error("找不到该产品，或者该产品不属于本小程序");
                }
                if($usergroup==2){
                    $this->error("找不到该产品，或者该产品不属于本小程序");
                }
            }
        }else{
            $goodsid=0;
            $goodsinfo = "";
            $id = 0;
            $counttypes=0;
            $datajson = [];
            $typesjson = [];
            $typesarr = [];
        }
        $cate = Db::name('wd_xcx_food_cate')->where('uniacid',$uniacid)->select();
        return View::fetch('restaurant/add_dish', [
            'goodsid' => $goodsid,
            'goodsinfo' => $goodsinfo,
            'cate' => $cate,
            'id' => $id,
            'counttypes' => $counttypes,
            'datajson' => $datajson,
            'typesjson' => $typesjson,
            'typesarr' => $typesarr,
            'allimg' => $allimg,
        ]);
    }

    public function saveDish()
    {
        $this->checkUserRule(7);
        $id = intval(input("goodsid"));
        //小程序ID
        $data['uniacid'] = $this->uniacid;
        $data['cid'] = $_POST['cid'];
        $data['num'] = $_POST['num'];
        $data['title'] = $_POST['title'];
        // $data['counts'] = $_POST['counts'];
//        $data['price'] = $_POST['price'];
        $data['desccon'] = $_POST['desccon'];
        $data['product_txt'] = input('product_txt');
//        $data['unit'] = $_POST['unit'];
        $data['vip_dis'] = input('vip_dis');
        $data['status'] = input('status');
        //缩略图
        $thumb = input("commonuploadpic1");
        if($thumb){
            $data['thumb'] = remote($data['uniacid'],$thumb,2);
        }
        //缩略图
        $descimg = input("commonuploadpic2");
        if($descimg){
            $data['descimg'] = remote($data['uniacid'],$descimg,2);
        }
        $food_pics = input('imgsrcs/a') ? input('imgsrcs/a') : '';
        if($food_pics){
            foreach ($food_pics as $k => $item){
                $food_pics[$k] = remote($this->uniacid, $item, 2);
            }
            $data['food_pics'] = json_encode($food_pics);
        }
        $guig = input("ischeck");
        $data["types"] = intval($guig);
        $is_del = 0; //是否需要删除规格
        if($id != 0){
            Db::name('wd_xcx_food')->where("id",$id)->update($data);
        }else{
            $id = Db::name('wd_xcx_food')->insertGetId($data);
            $is_del = 1;
        }
        if($guig == 2){
            $ggarr = stripslashes(html_entity_decode(input('biaogedata')));
            $proarr = json_decode($ggarr,true);
            // 规格组长度
            $typelen = input('typelen');
            // 规格数组
            $types = input('typesarr');
            $typezz = $types;
            $typesarr = explode(",", $types);
            $count = 0;
            if($proarr){
                if(input("goodsid")){
                    $vals = Db::name('wd_xcx_food_type_value')->where('pid', input("goodsid"))->field('comment, type1, type2, type3')->select();
                    if($vals->count() > 0){
                        $comment_arr = explode(',', $vals[0]['comment']);
                        if (count($comment_arr) == $typelen) {
                            if(count(array_diff($comment_arr, $typesarr)) == 0){//规格组未改变
                                $vals1 = [];
                                $vals2 = [];
                                $vals3 = [];
                                foreach ($vals as $kz => $vz) {
                                    if ($typelen == 1) {
                                        array_push($vals1, $vz['type1']);
                                    } else if ($typelen == 2) {
                                        array_push($vals1, $vz['type1']);
                                        array_push($vals2, $vz['type2']);
                                    } else if ($typelen == 3) {
                                        array_push($vals1, $vz['type1']);
                                        array_push($vals2, $vz['type2']);
                                        array_push($vals3, $vz['type3']);
                                    }
                                }
                                $vals1 = array_unique($vals1);
                                $vals2 = array_unique($vals2);
                                $vals3 = array_unique($vals3);

                                $ggzjsons = stripslashes(html_entity_decode(input('ggzjsons')));
                                $ggzjsons = json_decode($ggzjsons, true);
                                if($typelen == 1){
                                    if (count($vals1) == count($ggzjsons[$comment_arr[0]])) {
                                        if (count(array_diff($vals1, $ggzjsons[$comment_arr[0]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                }elseif ($typelen == 2){
                                    if (count($vals1) == count($ggzjsons[$comment_arr[0]])) {
                                        if (count(array_diff($vals1, $ggzjsons[$comment_arr[0]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                    if (count($vals2) == count($ggzjsons[$comment_arr[1]])) {
                                        if (count(array_diff($vals2, $ggzjsons[$comment_arr[1]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                }elseif ($typelen == 3){
                                    if (count($vals1) == count($ggzjsons[$comment_arr[0]])) {
                                        if (count(array_diff($vals1, $ggzjsons[$comment_arr[0]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                    if (count($vals2) == count($ggzjsons[$comment_arr[1]])) {
                                        if (count(array_diff($vals2, $ggzjsons[$comment_arr[1]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                    if (count($vals3) == count($ggzjsons[$comment_arr[2]])) {
                                        if (count(array_diff($vals3, $ggzjsons[$comment_arr[2]])) != 0) {
                                            $is_del = 1;
                                        }
                                    } else {
                                        $is_del = 1;
                                    }
                                }
                            }else{
                                $is_del = 1;
                            }
                        }else{
                            $is_del = 1;
                        }
                        if($is_del == 1){
                            Db::name('wd_xcx_food_type_value')->where('pid',$id)->delete(); //规格组发生改变  需要删除所有的重新添加
                        }
                    }
                }
                foreach ($proarr as $key => $rec) {
                    if($typelen == 1){
                        $type1 = $rec[$typesarr[0]];
                        $type2 = "";
                        $type3 = "";
                    }
                    if($typelen == 2){
                        $type1 = $rec[$typesarr[0]];
                        $type2 = $rec[$typesarr[1]];
                        $type3 = "";
                    }
                    if($typelen == 3){
                        $type1 = $rec[$typesarr[0]];
                        $type2 = $rec[$typesarr[1]];
                        $type3 = $rec[$typesarr[2]];
                    }
                    $datas = array(
                        "pid" => $id,
                        "type1" => $type1,
                        "type2" => $type2,
                        "type3" => $type3,
                        "kc" => $rec['库存'],
                        "price" => $rec['价格'],
                        "salenum" => $rec['已售数量'],
                        "comment" => $typezz,
                        "vsalenum"=>$rec['虚拟销量']
                    );
                    if($is_del == 0){
                        $where['type1'] = !is_null($type1) ? $type1 : NULL;
                        $where['type2'] = !is_null($type2) ? $type2 : NULL;
                        $where['type3'] = !is_null($type3) ? $type3 : NULL;
                        // 先查询有没有这个条件下的规格
                        $list = Db::name("wd_xcx_food_type_value")->where('pid', $id)->where($where)->find();
                        // 如果有的话进行更新
                        if ($list != NULL) {
                            Db::name("wd_xcx_food_type_value")->where('pid', $id)->where($where)->update($datas);
                        } else { // 没有的话去找到更新条件是空字符串的
                            $where['type1'] = !is_null($type1) ? $type1 : ['eq', ''];
                            $where['type2'] = !is_null($type2) ? $type2 : ['eq', ''];
                            $where['type3'] = !is_null($type3) ? $type3 : ['eq', ''];
                            Db::name("wd_xcx_food_type_value")->where('pid', $id)->where($where)->update($datas);
                        }
                        $count++;
                        if ($count == count($proarr)) {
                            $minprice = Db::name('wd_xcx_food_type_value')->where('pid', $id)->field('price * 1 as price')->order("price asc")->limit(1)->find();
                            Db::name("wd_xcx_food")->where("id", $id)->update(array("price" => $minprice['price']));
                            $this->success('操作成功', Url('RestaurantController/dishes'));
                        }
                    }else{
                        $res = Db::name('wd_xcx_food_type_value')->insert($datas);
                        if($res){
                            $count++;
                            if($count == count($proarr)){
                                $minprice = Db::name('wd_xcx_food_type_value')->where('pid',$id)->field('price') ->select();
                                $min = $minprice[0]['price']*1;
                                foreach ($minprice as $key => $value) {
                                    if($value['price']*1 < $min){
                                        $min = $value['price'];
                                    }
                                }
                                Db::name("wd_xcx_food")->where("id", $id)->update(array("price"=>$min));
                                $this->success('操作成功', Url('RestaurantController/dishes'));
                            }
                        }
                    }
                }
            }else{
                $this->success('操作成功', Url('RestaurantController/dishes'));
            }
        }else{
            $datas = array(
                "pid" => $id,
                "type1" => "默认",
                "type2" => "",
                "type3" => "",
                "kc" => 1,
                "price" =>1,
                "salenum" => 0,
                "comment" => "规格",
                "vsalenum"=>0
            );
            Db::name("wd_xcx_food")->where("id",$id)->update(array("price"=>1));
            $res = Db::name('wd_xcx_food_type_value')->insert($datas);
            if($res){
                $this->success('操作成功', Url('RestaurantController/dishes'));
            }
        }
    }

    /**删除菜品
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function delDish()
    {
        $this->checkUserRule(7);
        $data['id'] = input("proid");
        Db::name('wd_xcx_food')->where($data)->delete();
        Db::name('wd_xcx_food_type_value')->where('pid', $data['id'])->delete();
        $this->success('操作成功');
    }

    /**修改菜品状态
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeDishesStatus()
    {
        $this->checkUserRule(7);
        $uniacid = $this->uniacid;
        $did = input('did');
        $dishes = Db::name('wd_xcx_food')->where('id', $did)
            ->where('uniacid', $uniacid)
            ->find();
        if(!$dishes){
            $this->error('菜品不存在');
        }
        $status = $dishes['status'] == 1 ? 0 : 1;
        Db::name('wd_xcx_food')->where('id', $did)
            ->where('uniacid', $uniacid)
            ->update(['status' => $status]);
        $this->success('操作成功');
    }

    /**餐饮基础设置
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function base()
    {
        $bases = Db::name('wd_xcx_food_sj')->where("uniacid", $this->uniacid)->find();
        View::assign([
            'bases' => $bases,
        ]);
        return View::fetch('restaurant/base');
    }

    /**保存基础信息
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function saveBase()
    {
        $uniacid = $this->uniacid;
        $data = array();
        //门店LOGO
        $logo = input("commonuploadpic");
        if ($logo) {
            $data['thumb'] = remote($uniacid, $logo, 2);
        }

        $back_thumb = input("commonuploadpic2");
        if ($back_thumb) {
            $data['back_thumb'] = remote($uniacid, $back_thumb, 2);
        }

        //商家名称
        $notice = $_POST['notice'];
        $data['notice'] = $notice;

        //商家名称
        $phone = $_POST['phone'];
        $data['phone'] = $phone;

        //商家名称
        $address = $_POST['address'];
        $data['address'] = $address;

        //商家标签
        $tags = $_POST['tags'];
        $data['tags'] = $tags;
        //商家名称
        $name = $_POST['name'];
        if ($name) {
            $data['names'] = $name;
        }
        //单个订单最多抵扣
        $score = $_POST['score'];
        if ($score) {
            $data['score'] = $score;
        }else{
            $data['score'] = 0;
        }
        //营业时间
        $times = $_POST['times'];
        $data['times'] = $times;
        //配送说明
        $fuwu = $_POST['fuwu'];
        $data['fuwu'] = $fuwu;
        //配送说明(其他)

        $qita = $_POST['qita'];
        $data['qita'] = $qita;
        //是否填写姓名
        $usname = input('usname');
        if ($usname) {
            $data['usname'] = $usname;
        } else {
            $data['usname'] = 0;
        }
        //是否填写联系方式
        $ustel = input('ustel');
        if ($ustel) {
            $data['ustel'] = $ustel;
        } else {
            $data['ustel'] = 0;
        }
        //是否填写地址
        $usadd = input('usadd');
        if ($usadd) {
            $data['usadd'] = $usadd;
        } else {
            $data['usadd'] = 0;
        }
        //是否填写日期
        $usdate = input('usdate');
        if ($usdate) {
            $data['usdate'] = $usdate;
        } else {
            $data['usdate'] = 0;
        }
        //是否填写时间
        $ustime = input('ustime');
        if ($ustime) {
            $data['ustime'] = $ustime;
        } else {
            $data['ustime'] = 0;
        }

        $bases = Db::name('wd_xcx_food_sj')->where("uniacid", $uniacid)->count();
        if ($bases > 0) {
            Db::name('wd_xcx_food_sj')->where("uniacid", $uniacid)->update($data);
        } else {
            $data['uniacid'] = $uniacid;
            Db::name('wd_xcx_food_sj')->insert($data);
        }
        $this->success('操作成功！');
    }

    /**获取桌码二维码
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getTableQrcode()
    {
        $uniacid = $this->uniacid;
        $tableid = input('tableid');
        try {
            $file_path = public_path().'image/ewm';
            $file_name = 'table_'.$tableid.'.jpg';
            (new WxService())->getWxQrcode([
                'path' => 'pagesFood/ordering?table_num='.$tableid,
                'filename' => $file_name,
                'filepath' => $file_path,
            ]);
            $qrcode_path = '/image/ewm/'.$file_name;
        }catch (Exception $exception){
            throwError($exception->getMessage());
        }
        $this->success('success', '', $qrcode_path);
    }

    public function downloadTableEwm()
    {
        $files = input("src");
        $type = input("type");

        $files = explode('ewm/', $files);
        $files_path = ROOT_PATH.'public/image/ewm/'.$files[1];
        $filename = $files[1];
        $fp = fopen($files_path,"r");
        $file_size = filesize($files_path);
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$filename};");
        header("Content-Length: ". $file_size);
        $buffer=1024;
        $file_count=0;
        //向浏览器返回数据
        while(!feof($fp) && $file_count<$file_size){
            $file_con=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_con;
        }
        fclose($fp);
        exit;
    }



















}