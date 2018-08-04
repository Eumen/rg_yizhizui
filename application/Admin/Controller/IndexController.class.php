<?php

/**
 * 后台首页
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class IndexController extends AdminbaseController {
	function _initialize() {
		parent::_initialize();
		$this->initMenu();
	}
    //后台框架首页
    public function index() {
        $this->assign("SUBMENU_CONFIG", json_encode(D("Common/Menu")->menu_json()));
       	$this->display();
    }

	public function init_data(){return false;
		$award = new \Common\Lib\Award();
		$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
		$get_user = M('users');
		for ($i=0; $i <=634 ; $i++) {
			$get_user_id = M('users')->where("user_type = 2 and layer=".$i)->getField('id');
			echo $i."---".$get_user_id."<br>";
			$award-> area($get_user_id, $rg_time); 
		}
	}
	public function change_center(){return false;
		$get_lists = M('users')->where('is_agent = 1')->select();
		$center = M('centers');
		foreach($get_lists as $val){
			$data=array(
				'uid'			=> $val['id'],
				'status'			=> 1,
				'add_times'		=>date('Y-m-d H:i:s'),
			);
			$user_id = $center->add($data);
		}
	}
	
	public function add_unact(){return false;
		$get_lists = M('users2')->where('user_type = 2 and id <> 3 and user_status = 0')->select();
		$get_user = D('Users');
		$user_infos2 = M('user_infos2');
		$user_infos = M('user_infos');
		foreach($get_lists as $val){
			$user_login = M('Users2')->where( array('id'=>$val['rid']) )->getField('user_login');
			$rid = M('Users')->where( array('user_login'=>$user_login) )->getField('id');
			
			$user_login2 = M('Users2')->where( array('id'=>$val['add_user_id']) )->getField('user_login');
			$add_user_id = M('Users')->where( array('user_login'=>$user_login2) )->getField('id');
			
			$user_login3 = M('Users2')->where( array('id'=>$val['biz_id']) )->getField('user_login');
			$biz_id = M('Users')->where( array('user_login'=>$user_login3) )->getField('id');
			
			$user_login4 = M('Users2')->where( array('id'=>$val['audit_id']) )->getField('user_login');
			$audit_id = M('Users')->where( array('user_login'=>$user_login4) )->getField('id');
			$data=array(
				'user_login'		=> trim($val['user_login']),
				'user_email'		=> $val['user_email'],
				'user_nicename'		=> $val['user_nicename'],
				'user_pass'			=> $val['user_pass'],
				'user_pass2'		=> $val['user_pass2'],
				'user_pass3'		=> $val['user_pass3'],
				'create_time'		=> $val['create_time'],
				'user_status'		=> $val['user_status'],
				"user_type"			=> $val['user_type'],
				"rid"				=> $rid?$rid:0,
				'add_user_id' 		=> $add_user_id?$add_user_id:0,
				"biz_id"			=> $biz_id?$biz_id:0,
				"e_amount"			=> $val['e_amount'],
				"audit_id"			=> $audit_id?$audit_id:0,
				'is_agent'			=> $val['is_agent'],
				"audit_time"		=> $val['audit_time'],
			);
			if( $user_id = $get_user->add($data) ){
				$get_user_info = $user_infos2->where('user_id='.$val['id'])->find();
				$get_user_info['user_id'] = $user_id;
				$user_infos->add($get_user_info);
			}
			echo $user_id."--- <br>";
		}
	}
	public function change_data(){return false;
		$get_lists = M('Users2')->where('user_type = 2 and id <> 3 and user_status = 1')->order('id asc')->select();
		$get_user = D('Users');
		$user_infos2 = M('user_infos2');
		$user_infos = M('user_infos');
		foreach($get_lists as $val){
			$user_login = M('Users2')->where( array('id'=>$val['rid']) )->getField('user_login');
			$rid = M('Users')->where( array('user_login'=>$user_login) )->getField('id');
			
			$user_login2 = M('Users2')->where( array('id'=>$val['add_user_id']) )->getField('user_login');
			$add_user_id = M('Users')->where( array('user_login'=>$user_login2) )->getField('id');
			
			$user_login3 = M('Users2')->where( array('id'=>$val['biz_id']) )->getField('user_login');
			$biz_id = M('Users')->where( array('user_login'=>$user_login3) )->getField('id');
			
			$user_login4 = M('Users2')->where( array('id'=>$val['audit_id']) )->getField('user_login');
			$audit_id = M('Users')->where( array('user_login'=>$user_login4) )->getField('id');
			$data=array(
				'user_login'		=> trim($val['user_login']),
				'user_email'		=> $val['user_email'],
				'user_nicename'		=> $val['user_nicename'],
				'user_pass'			=> $val['user_pass'],
				'user_pass2'		=> $val['user_pass2'],
				'user_pass3'		=> $val['user_pass3'],
				'create_time'		=> $val['create_time'],
				'user_status'		=> $val['user_status'],
				"user_type"			=> $val['user_type'],
				"rid"				=> $rid?$rid:0,
				'add_user_id' 		=> $add_user_id?$add_user_id:0,
				"biz_id"			=> $biz_id?$biz_id:0,
				"e_amount"			=> $val['e_amount'],
				"audit_id"			=> $audit_id?$audit_id:0,
				'is_agent'			=> $val['is_agent'],
				"audit_time"		=> $val['audit_time'],
			);
			if( $user_id = $get_user->add($data) ){
				$get_user_info = $user_infos2->where('user_id='.$val['id'])->find();
				$get_user_info['user_id'] = $user_id;
				$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
				$user_infos->add($get_user_info);
				$get_user->Activation($user_id,$rg_time);
			}
			echo $user_id."--- <br>";
			sleep(1);
		}
	}
	public function change_dataCharges(){return false;
		$get_lists = M('Charges')->select();
		foreach($get_lists as $val){
			$user_login = M('Users2')->where( array('id'=>$val['user_id']) )->getField('user_login');
			$user_id = M('Users')->where( array('user_login'=>$user_login) )->getField('id');

			M('Charges')->where( array('user_id'=>$val['user_id']) )->save( array('user_id'=>$user_id) );
		}
	}
}