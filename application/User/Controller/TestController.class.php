<?php
namespace User\Controller;
use Common\Controller\HomeBaseController;
class TestController extends HomeBaseController {
	/**url: http://localhost/zxjinluohan/index.php?g=User&m=Test&a=test_award **/
	function test_award(){exit;
//		$award = new \Common\Lib\Award();
//		D("Common/Users")->Activation(318,1000,null,1);
		$lists = M('incomes')->where("status=0")->select();
		foreach($lists as $val){
			if($val['amount'] > 0){
				M('users')->where("id=".$val['user_id'])->setInc('amount',$val['amount']);
			}
		}
		die("is end");
	}

	function gettime(){
		echo date('Y-m-d H:i:s','1526423151');exit;
	}

	function set_rid_code(){exit;
		$datas = M('Users')->where(array('user_type'=>2))->field("id")->order("id asc")->select();
		foreach($datas as $_v){
			$this->set_data($_v['id']);
		}
	}

	function set_point(){exit;
		$award = new \Common\Lib\Award();
		$datas = M('readd')->where("id>=83 and id<=225")->order("id asc")->select();
		foreach($datas as $_v){
			$rg_time = array('addtime'=>date('Y-m-d', $_v['add_times']), 'createtime'=>date('H:i:s', $_v['add_times']));
			$award->layer_award($_v['user_id'], $_v['money'], $rg_time);
		}
		die('ok');
	}

	function set_point_roll(){exit;
		$datas = M('incomes')->where("types='POINT' and id>764877 and id<852169")->order("id asc")->select();
		foreach($datas as $_v){
			$money = $_v['amount'];
	        $bl_amount      = $this->site_options['bl_amount']/100;
	        $bl_e_amount    = $this->site_options['bl_e_amount']/100;
	        $bl_shop_amount = $this->site_options['bl_shop_amount']/100;
			
			$user =  M('Users')->where( array('id'=>$_v['user_id']) )->find();
	        $update_money['amount']     = $user['amount']      - ($money*$bl_amount);
	        $update_money['e_amount']   = $user['e_amount']    - ($money*$bl_e_amount);
	        $update_money['shop_amount']= $user['shop_amount'] - ($money*$bl_shop_amount);

        	M('Users')->where( array('id'=>$_v['user_id']) )->setField($update_money);

        	M('incomes')->where("id = '".$_v['id']."'")->delete();
		}
		die('ok');
	}
	
	function set_data($id){exit;
		$data_u = M('Users')->where(array('id'=>$id))->find();
		if(empty($data_u['rid'])) return;

		$ruser_code = M('Users')->where(array('id'=>$data_u['rid']))->getField('rid_code');
        $rid_code   = $ruser_code.$data_u['rid']."|";

		M('Users')->where(array('id'=>$id))->save(array('rid_code'=>$rid_code));
	}
	
	/**url: http://localhost/yizhizui/index.php?g=User&m=Test&a=test_layer **/
	public function test_layer(){exit;
		$award = new \Common\Lib\Award();
//		$award->hongbao_award(10);
//		for($i=0;$i<4;$i++){ $award->hongbao_award(380); echo "<br><br>";}
//		$award->layer_award(9240);
//		$award->layer_award(9241);
		die("is end");
	}
	
	/**url: http://localhost/yizhizui/index.php?g=User&m=Test&a=bufa_users **/
	public function bufa_users(){exit;
		if(session("bufa")){ echo "pleaes do not again";exit; }
		$all_users = M('Users')->where("id >=9239 and tz_num > 1 and user_status = 1")->order("id asc")->field('id,tz_num,audit_time')->select();
		$rmoney = $this->site_options['readd'];
		foreach($all_users as $val){
			if(M("layer_readd")->where( array('user_id'=>$val['id']) )->find()){ continue ; }
			$is_readd = M('readd')->where("money > 600 and user_id='".$val['id']."'")->find();
			if($is_readd){
				$reunit = $is_readd['money'] / $rmoney;
				if($reunit > 1){
					$reunit = $reunit -1;//已经发了一单
					if($reunit > 0){
						$re_date = date('Y-m-d',$is_readd['add_times']);
						$this->bufa($val['id'],$reunit,$re_date);
					}
				}
			}
			
			$readd_tatol =  M('readd')->where( array('user_id'=>$val['id']) )->sum("money");
			$readd_number = 0;
			if($readd_tatol){ $readd_number = $readd_tatol / $rmoney; }
			$unit = $val['tz_num'] - $readd_number - 1;
			if($unit > 0) {
				$add_date = date('Y-m-d',strtotime($val['audit_time']));
				$this->bufa($val['id'],$unit,$add_date);
			}
		}
		session("bufa",1);
		echo "is end";exit;
	}
	
	function bufa($id,$unit,$addtime){
		 if(empty($id)) { return; }
        //当前用户id
        $price = $this->site_options['POINT'];
        $price = $price * $unit;
        //记录数据
        $rdata = array("user_id"=>$id,"unit"=>$unit,"readd_date"=>$addtime,"money"=>$price,"add_times"=>date("Y-m-d H:i:s"));
        if(M("layer_readd")->add($rdata)){
        		//begin
	        $condition['pay_uid']= array("eq",$id);
	        $condition['types']= array("eq",'POINT');
	        $condition['addtime']= array("eq",$addtime);
	        $all_user = M("incomes")->where($condition)->select();
	        
			foreach($all_user as $val){
				//管理费
				$manager_amout =  $price * $this->site_options['gsglf']/100;
				$money = $price - $manager_amout;
				//重新设置奖金总额
				M("incomes")->where("id=".$val['id'])->setInc("amount",$price);
				//重新设置奖金收的管理费
				$condition['types']= array("eq",'MANAGER');
				$condition['user_id']= array("eq",$val['user_id']);
				$manager_money = M("incomes")->where($condition)->find();
				if($manager_money){
					M("incomes")->where("id=".$manager_money['id'])->setInc("amount",'-'.$manager_amout);
				}
				$this->update_money($val['user_id'],$money);
			}   
			//end    
        }
        
	}
	
	public function update_money($user_id,$money){
		if(empty($user_id)) { return; }
        $bl_amount      = $this->site_options['bl_amount']/100;
        $bl_e_amount    = $this->site_options['bl_e_amount']/100;
        $bl_shop_amount = $this->site_options['bl_shop_amount']/100;
		
		$user =  M('Users')->where( array('id'=>$user_id))->find();
        $update_money['amount']     = $user['amount']      + ($money*$bl_amount);
        $update_money['e_amount']   = $user['e_amount']    + ($money*$bl_e_amount);
        $update_money['shop_amount']= $user['shop_amount'] + ($money*$bl_shop_amount);
        M('Users')->where( array('id'=>$user_id) )->setField($update_money);
	}
	
	
}