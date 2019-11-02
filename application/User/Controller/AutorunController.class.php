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
		$this->success("操作成功", U("Admin/index"));
	}
	
	function calVips(){
	    $rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
	    $award = new \Common\Lib\Award();
	    $this->success("操作成功", U("Admin/index"));
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
	
	/**
	 * 方法功能：自动检测是否匹配领导级别条件，匹配则提升至对应级别，否则降级
	 * **/
	/**@see http://localhost/rg_yizhizui/index.php?g=User&m=Autorun&a=sh_level **/
	public function sh_level()
	{
	    // 取得会员数据ID
	    $Ids = M('users')->where(array('user_type'=>2,'user_status'=>1))->field('id,rand,rid,rid_code,rid_counts')->order('id desc')->select();
	    // 根据ID循环检索业绩条件
	    foreach ($Ids as $key => $value) {
	        // id
	        $myid = $value['id'];
	        // rand
	        $rand = $value['rand'];
	        // 
	         while ( $myid > 0 ) {
	         $users = M('users')->where(array("id"=>$myid))->field('id,rid,user_status,rid_code,tz_num')->find();
	         if($users){
    	         //团队业绩
    	         $rid_code 	= $users['rid_code'].$users['id'];
    	         $tz_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"),'user_type'=>2,'user_status'=>1) )->sum('tz_num');
    	         // 团队业绩
    	         $ach = $tz_num * 2000 + $users['tz_num'] * 2000;
    	         $fl_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"), 'rand'=>1,'user_type'=>2,'user_status'=>1) )->count();
    	         if($ach >= 10000 && $fl_num > 1 ){ $this->user_update_rand($myid, 2); }//VIP1
    	         $fl_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"), 'rand'=>2,'user_type'=>2,'user_status'=>1) )->count();
    	         if( $ach >= 30000 && $fl_num > 1 ) { $this->user_update_rand($myid, 3); } //VIP2
    	        
    	         $fl_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"), 'rand'=>3,'user_type'=>2,'user_status'=>1) )->count();
    	         if( $ach >= 70000 && $fl_num > 1 ) { $this->user_update_rand($myid, 4); } //VIP3
    	        
    	         $fl_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"), 'rand'=>4,'user_type'=>2,'user_status'=>1) )->count();
    	         if( $ach >= 140000 && $fl_num > 1 ) { $this->user_update_rand($myid, 5); } //VIP4
    	        
    	         $fl_num 	= M('users')->where( array('rid_code'=>array('like', $rid_code."|%"), 'rand'=>5,'user_type'=>2,'user_status'=>1) )->count();
    	         if( $ach >= 300000 && $fl_num > 1 ) { $this->user_update_rand($myid, 6); } //VIP5
    	         }
    	         $myid = $users['rid'];
	         }
	    }
	    echo "successfully";exit;
	}
	
	private function user_update_rand($user_id,$rand){
	    $ruser_rand = M('users')->where(array("id"=>$user_id,'user_type'=>2,'user_status'=>1))->getField('rand');
	    if($ruser_rand){
	        return M('users')->where('id='.$user_id)->setField('rand',$rand);
	    }
	}
	
}