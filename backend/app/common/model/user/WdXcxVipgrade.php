<?php

namespace app\common\model\user;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxVipgrade extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_vipgrade';
    protected $autoWriteTimestamp = true;

    public function getCardImgAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function setCardImgAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }

    public function getVipEquityAttr($value)
    {
        $result = [];
        if($this->discount_flag == 1){
            $discount_grade = rtrim($this->discount_grade, 0);
            $discount_grade = rtrim($discount_grade, '.');
            $result[] = [
                'title' => '会员折扣',
                'desc' => '会员可享'.$discount_grade.'折优惠',
                'img' => getLocalImage('/image/static/member-bg4.png')
            ];
        }
        if($this->score_flag == 1){
            $result[] = [
                'title' => '积分赠送',
                'desc' => '实时到账'.$this->score_back.'积分',
                'img' => getLocalImage('/image/static/member-bg3.png')
            ];
        }
        return $result;
    }

    public function getShowAnnualDelStrAttr()
    {
        $annual_del = '';
        if($this->grade_level == 1){
            $annual_del = '限时'.bcmul(bcdiv($this->annual_fee, $this->market_annual_fee, 2),10,1).'折!';
        }elseif ($this->grade_level == 2 || $this->grade_level == 3){
            $annual_del = bcmul(bcdiv($this->annual_fee, $this->market_annual_fee, 2),10,1).'折!!';
        }else{
            $annual_del = '省'.bcsub($this->market_annual_fee, $this->annual_fee);
        }
        return $annual_del;
    }

    public function getShowMonethDelStrAttr()
    {
        $month_del = '';
        if($this->grade_level > 3){
            $month_del = '省'.bcsub($this->market_midd_month_fee, $this->midd_month_fee);
        }
        return $month_del;
    }

    public function getCloudSizeStrAttr()
    {
        if($this->cloud_size > 1024){
            return intval(bcdiv($this->cloud_size, 1024)).'T';
        }else{
            return intval($this->cloud_size).'G';
        }
    }
    
    
    public function getUploadSizeValueAttr()
    {
        if($this->upload_size_type == 2){ //G
            return intval(bcmul($this->upload_size, 1024));
        }else{
            return intval($this->upload_size);
        }
    }
    


}