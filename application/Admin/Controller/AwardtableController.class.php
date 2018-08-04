<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AwardtableController extends AdminbaseController {
	protected $users_model, $userinfos_model, $centers_model,$mid;

	function _initialize() {
		parent::_initialize();
		$this->award_table = M("award_table");
		$this->mid = get_current_admin_id();
		$this->award = new \Common\Lib\Award();
	}

    public function index() {
    		if(IS_POST){
    			$money = trim(I('money'));
    			if($money > 0){
    				$time = date('Y-m-d');
    				$record = $this->award_table->where(array('addtime'=>$time,'types'=>'分红补贴股'))->find();
    				if(empty($record)){
	    				$data = array(
	    					'user_id'=>$this->mid,
	    					'types'  =>'分红补贴股',
	    					'addtime'=>date('Y-m-d'),
	    					'amount' =>$money,
	    				);
	    				if($this->award_table->add($data)){
						$r1[] = $this->award->member_share_award($money);
				    		$this->success("发奖成功",U('Awardtable/index'));
	    				}
	    				
    				}else{
    					$this->error("今天已经发放过，不可以重复");
    				}
    			}else{
    				$this->error("发奖金不能小于0");
    			}
    		}else{
    			$this->display();
    		}
        
    }
    
     public function partner() {
       if(IS_POST){
    			$money = trim(I('money'));
    			if($money > 0){
    				$time = date('Y-m-d');
    				$record = $this->award_table->where(array('addtime'=>$time,'types'=>'合伙人分红'))->find();
    				if(empty($record)){
	    				$data = array(
	    					'user_id'=>$this->mid,
	    					'types'  =>'合伙人分红',
	    					'addtime'=>date('Y-m-d'),
	    					'amount' =>$money,
	    				);
	    				if($this->award_table->add($data)){
						$r1[] = $this->award->partner_share_award($money);
				    		$this->success("发奖成功",U('Awardtable/partner'));
	    				}
	    				
    				}else{
    					$this->error("今天已经发放过，不可以重复");
    				}
    			}else{
    				$this->error("发奖金不能小于0");
    			}
    		}else{
    			$this->display();
    		}
    }
    
    public function yinlianghui(){
    		 if(IS_POST){
    		 	if($this->award->yinlianghui()){
   		 		$this->success("结算成功",U('Awardtable/yinlianghui'));
    		 	}else {
    		 		$this->error("结算失败，请查看是否已结算",U('Awardtable/yinlianghui'));
    		 	}
    		 	
    		 }else{
    		 	$this->display();
    		 }
    }

    public function hongbao() {
        if(IS_POST){
            $money = trim(I('money'));
            if($money > 0){
                $time = date('Y-m-d');
                $record = $this->award_table->where(array('addtime'=>$time,'types'=>'每天分红包'))->find();
                if(empty($record)){
                    $data = array(
                        'user_id'=>$this->mid,
                        'types'  =>'每天分红包',
                        'addtime'=>date('Y-m-d'),
                        'amount' =>$money,
                    );
                    if($this->award_table->add($data)){
                        $r1[] = $this->award->hongbao_award($money);
                        $this->success("发放成功",U('Awardtable/hongbao'));
                    }

                }else{
                    $this->error("今天已经发放过，不可以重复");
                }
            }else{
                $this->error("发奖金不能小于0");
            }
        }else{
            $this->display();
        }
    }
}

