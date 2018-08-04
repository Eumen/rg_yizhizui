<?php
namespace User\Controller;
use Think\Controller;
class AutorunController extends Controller {
	/**@see 消费返利**/
	function auto_run(){
		$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
		$award = new \Common\Lib\Award();
		echo "successfully";exit;
	}
	
	function rg_test2(){
		$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
		$award = new \Common\Lib\Award();
		echo "successfully";exit;
	}
	/**@see http://localhost/huixin201509/index.php?g=User&m=Autorun&a=rg_test **/
	function rg_test(){
		$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
		$rid = 55;
		$award = new \Common\Lib\Award();
		$user_id = M('users')->where("id=".$rid)->setField('rid_counts',12);
		$get_max_layer = M('users')->order('layer desc')->getField('layer');
			
		for($i=1;$i<=1;$i++){
			$get_max_layer = $get_max_layer + 1;
			$user_name = 'test_'.$rid."_".$i;
			$data = array(
				'user_login'=>$user_name,
				'user_pass'=>1,
				'user_pass2'=>1,
				'user_pass3'=>1,
				'user_nicename'=>1,
				'user_email'=>1,
				'user_url'=>1,
				'user_qq'=>1,
				'avatar'=>1,
				'signature'=>1,
				'rid'=>$rid,
				'user_type'=>2,
				'biz_id'=>55,
				'layer'=>$get_max_layer
			);
			$user_id = M('users')->add($data);
			if($user_id){
				//用户信息
				$rg_data['user_id']			= $user_id;
				$rg_data['true_name']		= $user_name;
				$rg_data['account_type']		= "1";
				$rg_data['account_no']		= "1";
				$rg_data['account_name']		= "1";
				$rg_data['account_info']		= "1";
				$rg_data['tel']				= "1";
				D("Common/UserInfos")->add($rg_data);
				M('Users')->where('id='.$rid)->setInc('rid_counts',1);
				$award->rid_award ($user_id,$rg_time);
				$award->line_award($user_id,$rg_time );	
				$award->center_award($user_id,$rg_time);
				//$rid = $user_id;
			}
		}
		echo "successfully";exit;
	}
	
}