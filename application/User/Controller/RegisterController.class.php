<?php

namespace User\Controller;
use Common\Controller\HomeBaseController;
class RegisterController extends HomeBaseController {

	function index(){
		$rg_user_rid = cookie('rg_user_rid');
		if($rg_user_rid){
			$user_login = M("Users")->where( array('user_login'=>$rg_user_rid, 'user_type'=>2) )->getField('user_login');
			$this->assign("rname", $user_login);
		}
		$pic = M('slide')->where("slide_cid=3")->order("slide_id desc")->getField('slide_pic');
		$pic = sp_get_asset_upload_path($pic);	
		$this->assign('spic',$pic);

        $goods = D("Common/Goods")->select();
        $this->assign("goods",$goods);
		$this->display(":register");
	}
  function checkUser(){
  	$this->users_model = D("Common/Users");
  	$this->userinfos_model = D("Common/UserInfos");
  	$username	= I('post.username');
  	$rg_user	= $this->users_model->where("user_login='$username' and user_type = 2")->find();
  	if( empty($rg_user) ) $this->error("用户名不存在");
  	
  	$rg_userInfo = $this->userinfos_model->where( array('user_id'=>$rg_user['id']) )->find();
  	if( $rg_userInfo ){
  		$true_name = $rg_userInfo['true_name'] ? $rg_userInfo['true_name'] : '无';
  		$identity = $rg_userInfo['identity_id'] ? $rg_userInfo['identity_id'] : '无';
  		$this->success("用户名存在,用户名称：".$true_name."，用户身份证：".$identity);
  	}else{
  		$this->error("用户名还未填写详细信息");
  	}
  }

  function doregister(){
	  	$this->users_model = D("Common/Users");
	  	$this->userinfos_model = D("Common/UserInfos");
		if(IS_POST){
		    $username	= I('post.username');
		    $password	= I('post.password');
		    $password2	= I('post.password2');
		    $tz_num	= I('post.tz_num');
		    $recname	= I('post.recname');
		    $biz_username	= I('post.biz_username');
		    $true_name	= I('post.true_name');
		    $tel	= I('post.tel');
		    $identity_id	= I('post.identity_id');
		    $verify	= I('post.verify');
		    $realname	= I('post.realname');
		    $account_type	= I('post.account_type');
		    $accountno	= I('post.verify');
		    $accountname	= I('post.accountname');
		    $accountinfo	= I('post.accountinfo');
			$users_model = M('Users');
			$rules = array(
					array('username', 'require', '账号不能为空！', 1 ),
					array('recname', 'require', '引介人不能为空！', 1 ),
					array('realname', 'require', '姓名不能为空！', 1 ),
					array('tel', 'require', '联系手机号不能为空！', 1 ),
					array('password', 'require', '密码设置不能为空！', 1 ),
			        array('identity_id',    'require', '身份证不能为空！', 1 ),
					array('tel','/1[34578]{1}\d{9}$/','联系手机号格式不正确！',1, 'regex'),
					array('terms',          'require', '您未同意服务条款！', 1 ),
			);
			if($tz_num<=0 || $tz_num>50) $this->error("认购单数错误！");
			if($users_model->validate($rules)->create()===false){
				$this->error($users_model->getError());
			}

			extract($_POST);
			//用户名需过滤的字符的正则
			$stripChar = '?<*.>\'"';
			if(preg_match('/['.$stripChar.']/is', $username)==1){
				$this->error('用户名中包含'.$stripChar.'等非法字符！');
			}
			$banned_usernames=explode(",", sp_get_cmf_settings("banned_usernames"));
			if(in_array($username, $banned_usernames)){
				$this->error("此用户名禁止使用！");
			}
			if(strlen($password)<6) $this->error("密码设置不够安全");
			$ucenter_syn=C("UCENTER_ENABLED");
			$uc_checkusername=1;
			if($ucenter_syn){
				include UC_CLIENT_ROOT."client.php";
				$uc_checkusername = uc_user_checkname($username);
			}

			$where['user_login']=$username;
			$rg_user	= $this->users_model->where("user_login='".$username."'")->find();
			$rec_user	= $this->users_model->where("user_login='".$recname."'")->find();
			$biz_user	= $this->users_model->where( array('user_login'=>$biz_username, 'user_type'=>2, 'is_agent'=>1) )->find();

			if($rg_user || $uc_checkusername<0) $this->error("用户名已经存在！");
			if( empty($rec_user) ) $this->error('引介人不存在');
			
			//节点人 和区位查找
			$pid_info = $this->get_pid_info($rec_user['id']);
			if(empty($pid_info)) $this->error('节点区位获取失败');
			
			$ruser_code = M("Users")->where( array('id'=>$rec_user['id']) )->getField('rid_code');
			$rid_code   = empty($ruser_code)?'':$ruser_code.$rec_user['id']."|";
			
// 			if(!sp_check_verify_code()) $this->error("验证码错误！");
			if ($biz_username){
			    $biz_user = M("Users")->where(array('user_login'=>$biz_username,'is_agent'=>1))->find();
			    if(!$biz_user['id']>0){
			        $this->error("报单用户不存在！");
			    }else{
			        $biz_id=$biz_user['id'];
			    }
			}else{
			    $biz_id=$this->uid?$this->uid:675;
			}

			$uc_register = true;
			if($ucenter_syn){
				$uc_uid = uc_user_register($username,$password,null);
				if($uc_uid<0) $uc_register=false;
			}
			if($uc_register){
				$data=array(
						'user_login'		=> $username,
						'user_nicename'		=> $realname,
						'user_email'			=> 'a@a.com',
						'user_pass'			=> sp_password($password),
						'user_pass2'		=> sp_password($password2),
						'create_time'		=> date("Y-m-d H:i:s"),
				        "tz_num"			=> intval($tz_num),
						'user_status'		=> 0,
						"user_type"			=> 2,
				        "rand"			    => 1,
						"rid"				=> $rec_user['id'],
						"add_user_id" 		=> $this->mid,
						"biz_id"			=> $biz_user['id']?$biz_user['id']:0,
						"rid_code"          => $rid_code,
				        "hb_amount"			=> $this->site_options['hongbao'] * intval($tz_num),
				        "r_amount"			=> $this->site_options['hongbao'] * intval($tz_num),
						"pid"               => $pid_info['pid'],
						"area"              => $pid_info['area'],
						"pid_code"          => $pid_info['pid_code'],
    				    "old_fbnum"         => intval($tz_num),
				);
				$user_id = $users_model->add($data);
				if($user_id){
					$rg_data['user_id']			= $user_id;
					$rg_data['true_name']		= $realname;
					$rg_data['identity_id']		= $identity_id?$identity_id:'无';
					$rg_data['account_type']	= $account_type;
					$rg_data['account_no']		= $accountno;
					$rg_data['account_name']	= $realname;
					$rg_data['account_info']	= $accountinfo;
					$rg_data['tel']				= $tel;
					$this->userinfos_model->add($rg_data);

					$this->success("注册成功！等待报单中心激活", U("User/Login/index"));
				}else{
					$this->error("注册失败！");
				}
			}else{
				$this->error("注册失败！");
			}
		}
	}
    
//     function get_pid_info($rid){
//         $pid_number = $this->users_model->where(array("pid"=>$rid))->count();
//         if($pid_number < 2 ){
//             $pid_array =  array("pid"=>$rid,"area"=>$pid_number+1);
//         }else{
//             $rid_code = $this->users_model->where(array("id"=>$rid))->getField("rid_code");
//             $condition['rid_code']  = array("like", $rid_code.$rid."|%");
//             $condition['status']    = array("eq", 1);
//             $condition['user_type'] = array("eq", 2);
//             $user_array = $this->users_model->where($condition)->order("id asc")->field('id')->select();
            
//             foreach($user_array as $val){
//                 $pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
//                 if($pid_number < 2){
//                     $pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
//                     $pid_array = array("pid"=>$val['id'], "area"=>$pid_number+1);
//                     break;
//                 }
//             }
//         }
//         if($pid_array){
//             $rid_code = $this->users_model->where(array("id"=>$pid_array['pid']))->getField("pid_code");
//             $pid_array['pid_code'] = $rid_code.$pid_array['pid']."|";
//         }
//         return $pid_array;
//     }

	function get_pid_info($rid){
	    $pid_number = $this->users_model->where(array("pid"=>$rid))->count();
	    if($pid_number < 2 ){
	        $pid_array =  array("pid"=>$rid,"area"=>$pid_number+1);
	    }else{
	        $pid_code = $this->users_model->where(array("id"=>$rid))->getField("pid_code");
	        $condition['pid_code']  = array("like", $pid_code.$rid."|%");
	        $condition['status']    = array("eq", 1);
	        $condition['user_type'] = array("eq", 2);
	        $user_array = $this->users_model->where($condition)->order("id asc")->field('id')->select();
	        foreach($user_array as $val){
	            $pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
	            if($pid_number < 2){
	                $pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
	                $pid_array = array("pid"=>$val['id'], "area"=>$pid_number+1);
	                break;
	            }
	        }
	    }
	    if($pid_array){
	        $rid_code = $this->users_model->where(array("id"=>$pid_array['pid']))->getField("pid_code");
	        $pid_array['pid_code'] = $rid_code.$pid_array['pid']."|";
	    }
	    return $pid_array;
	}

	function active(){
		$hash=I("get.hash","");
		if(empty($hash)){
			$this->error("激活码不存在");
		}

		$users_model=M("Users");
		$find_user=$users_model->where(array("user_activation_key"=>$hash))->find();

		if($find_user){
			$result=$users_model->where(array("user_activation_key"=>$hash))->save(array("user_activation_key"=>"","user_status"=>1));

			if($result){
				$find_user['user_status']=1;
				$_SESSION['user']=$find_user;
				$this->success("用户激活成功，正在登录中...",__ROOT__."/");
			}else{
				$this->error("用户激活失败!",U("user/login/index"));
			}
		}else{
			$this->error("用户激活失败，激活码无效！",U("user/login/index"));
		}
	}
}