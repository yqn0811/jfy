<?php

namespace app\common\model\appointment;

use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\order\WdXcxUserAppointmentOrderLists;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxAppointmentContent extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_appointment_content';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'appoint_time_info' => 'array',
    ];

    public function setAppointThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }

    public function getAppointThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function setAppointPicsAttr($value)
    {
        if($value){
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 2);
            }
            return json_encode($value);
        }
        return '';
    }

    public function getAppointPicsAttr($value)
    {
        if($value){
            $value = json_decode($value, true);
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 1);
            }
            return $value;
        }
        return '';
    }

    public function cate()
    {
        return $this->hasOne(WdXcxAppointmentCate::class, 'id', 'cate_id');
    }

    public function getInfoById($id)
    {
        $info = $this->where('id', $id)->find();
        if(!$info){
            throwError('指定预约不存在');
        }
        return $info;
    }

    public function getAppointTimeInfoArrAttr()
    {
        $appoint_time_info = $this->appoint_time_info;
        $result = [];
        foreach ($appoint_time_info as $item){
            $result[] = [
                'time_str' => $item['start_time'].'-'.$item['end_time'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
            ];
        }
        return $result;
    }

    public function getAppointTimeShow($has_appoint, $choose_date)
    {
        $choose_date = date('Y-').str_replace('月', '-', rtrim($choose_date, '日'));
        $appoint_time_info = $this->appoint_time_info;
        foreach ($appoint_time_info as &$item){
            $item['can_reserve'] = 1;
            if(in_array($item['start_time'].'-'.$item['end_time'], $has_appoint) || time() > strtotime($choose_date .' '.$item['start_time'].':00')){
                $item['can_reserve'] = 2;
            }
        }
        return $appoint_time_info;
    }

    /**检查指定日期是否可约
     * @param $date
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getReserveStatus($date)
    {
        $can_reserve = 1; // 1 可约  2 不可约  3 已约满
        $appoint_time_info = $this->appoint_time_info;
        $has_appoint = [];
        $has_end = [];
        foreach ($appoint_time_info as $item){
            $record = WdXcxUserAppointmentOrderLists::whereIn('status', [1,2])
            ->where([
                'appoint_id' => $this->id,
                'appoint_date' => $date,
                'appoint_time' => $item['start_time'].'-'.$item['end_time'],
            ])->find();
            if($record){
                $has_appoint[] = $item['start_time'].'-'.$item['end_time'];
            }
            if($date == date('n月d日') && time() > strtotime(date('Y-m-d').' '.$item['start_time'].':00')){
                $has_end[] = $item['start_time'].'-'.$item['end_time'];
            }
        }
        if(count($has_appoint) == count($appoint_time_info)){
            $can_reserve = 3;
        }
        if(count($has_end) == count($appoint_time_info)){
            $can_reserve = 2;
        }
        return compact('has_appoint', 'can_reserve');
    }


}