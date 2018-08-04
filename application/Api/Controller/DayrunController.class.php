<?php
namespace Api\Controller;
use Think\Controller;
class DayrunController extends  Controller{

    /**
     * 每天派送红包功能
     * http://q.com/index.php/Api/Dayrun/hongbao
     * http://localhost/yizhizui/index.php/Api/Dayrun/hongbao
     */
    public function hongbao() {
        $rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));

        $this->site_options = get_site_options();
        $money = $this->site_options['hongbao_day'];
        if($money > 0){
            $record = M("award_table")->where(array('addtime'=>$rg_time['addtime'],'types'=>'DayHongBao'))->find();
            if(empty($record)){
                $data = array(
                    'user_id'=>0,
                    'types'  =>'DayHongBao',
                    'addtime'=>date('Y-m-d'),
                    'amount' =>$money,
                );
                if(M("award_table")->add($data)){
                    $this->award = new \Common\Lib\Award();
                    $r1[] = $this->award->hongbao_award($money,$rg_time);
                    echo("发放成功");
                }
            }else{
                echo("今天已经发放过，不可以重复");
            }
        }else{
            echo($money."红包金额不能小于0");
        }
    }
	
	
}