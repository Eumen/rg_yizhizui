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

  function doregister(){
      $username	= I('post.username');
      $email	= I('post.email');
      $password	= I('post.password');
      $password2	= I('post.password2');
      $password3	= I('post.password3');
      $tz_num	= I('post.tz_num');
      $recname	= I('post.recname');
      $biz_username	= I('post.biz_username');
      $true_name	= I('post.true_name');
      $tel	= I('post.tel');
      $identity_id	= I('post.identity_id');
      $verify	= I('post.verify');
      $realname	= I('post.realname');
      $account_type	= I('post.account_type');
      $account_no	= I('post.account_no');
      $account_name	= I('post.account_name');
      $account_info	= I('post.account_info');
      $rname	= I('post.rname');
    	$rules = array(
            array('username',       'require', '订单登录名ID不能为空！', 1 ),
            array('password',       'require', '一级密码不能为空！',1),
            array('password2',      'require', '二级密码不能为空！',1),
            array('tz_num',         'require', '认购单数不能为空！', 1 ),
            array('rname',        'require', '推荐人编号不能为空！', 1 ),
            array('biz_username',   'require', '报单中心编号不能为空！', 1 ),
            array('true_name',      'require', '真实姓名不能为空！', 1 ),
            array('tel',            'require', '联系电话不能为空！',1),
            array('tel',            '/1\d{10}$/','联系电话格式不正确！',1, 'regex'),
            array('identity_id',    'require', '身份证不能为空！', 1 ),
            array('terms',          'require', '您未同意服务条款！', 1 ),
    	);
    	if(M("Users")->validate($rules)->create()===false) $this->error(M("Users")->getError()); 
    	extract($_POST);

    	if(!preg_match('/^[0-9a-zA-Z]{2,16}$/', $username)) $this->error('订单登录名ID为2-16位英文、数字、英文数字组合');

    	$banned_usernames=explode(",", sp_get_cmf_settings("banned_usernames"));
    	if(in_array($username, $banned_usernames)) $this->error("此注册会员ID禁止使用！"); 

//     	if(!preg_match('/^[0-9a-zA-Z]{8,16}$/', $password)) $this->error('密码为8-16位英文、数字、特殊字符组合');
//         if(!preg_match('/^[0-9a-zA-Z]{8,16}$/', $password2)) $this->error('二级密码为8-16位英文、数字、特殊字符组合');
        $u_id = M("UserInfos")->where(array('tel'=>$tel))->getField('user_id');
        if($u_id>0) $this->error("该联系电话已经注册！"); 

        if($verify != session('user_code') && $verify!=5544) $this->error("验证码错误！");//丢一个固定验证码测试使用

        if ($biz_username){
            $biz_user = M("Users")->where(array('user_login'=>$biz_username,'is_agent'=>1))->find();
            if(!$biz_user['id']>0){
                $this->error("报单中心不存在！");
            }else{
                $biz_id=$biz_user['id'];
            }
        }else{
            $biz_id=675;
        }

        $rg_user = M("Users")->where(array('user_login'=>$username))->count();
        if($rg_user) $this->error("订单登录名ID已经存在！");

        $rec_user= M('Users')->where(array('user_login'=>$rname,'user_type'=>2,'user_status'=>1))->find();
        if( empty($rec_user) ) $this->error('推荐会员ID不存在或者未被激活');

        //节点人 和区位查找
        $pid_info = $this->get_pid_info($rec_user['id']);
        if(empty($pid_info)) $this->error('节点区位获取失败'); 

        $ruser_code = M("Users")->where( array('id'=>$rec_user['id']) )->getField('rid_code');
        $rid_code   = empty($ruser_code)?'':$ruser_code.$rec_user['id']."|";

        if($tz_num<=0 && $tz_num>50) $this->error("认购单数错误！");

        if($account_name){
            if($true_name != $account_name) $this->error("银行账户名必须显示和注册人真实姓名一致");
        }

		$data=array(
			'user_login'        => $username,
			'user_email'        => $username.'@jlh198.com',
			'user_nicename'		=> $true_name,
			'user_pass'			=> sp_password($password),
            'user_pass2'        => sp_password($password2),//二级密码
            'user_pass3'        => sp_password($password3),
			'last_login_ip'		=> get_client_ip(),
			'create_time'		=> date("Y-m-d H:i:s"),
			'last_login_time'	=> date("Y-m-d H:i:s"),
			'audit_time'		=> date("Y-m-d H:i:s"),
			'user_status'		=> 0,
			"user_type"			=> 2,
			"rid"				=> $rec_user['id'],
            "add_user_id"       => $rec_user['id'],
            "biz_id"            => $biz_id,
            "rid_code"          => $rid_code,
            "tz_num"            => intval($tz_num),
            "rand"              => 1,
            "goods_id"			=> intval($goods_id),
            "hb_amount"			=> $this->site_options['hongbao'] * intval($tz_num),
            "pid"               => $pid_info['pid'],
            "area"              => $pid_info['area'],
            "pid_code"          => $pid_info['pid_code'],
            "old_fbnum"         => intval($tz_num),
		);
		$user_id = M("Users")->add($data);
		if($user_id){
            $rg_data['user_id']         = $user_id;
            $rg_data['true_name']       = $true_name;
            $rg_data['identity_id']     = $identity_id;
            $rg_data['account_type']    = $account_type?$account_type:'无';
            $rg_data['account_no']      = $account_no?$account_no:'无';
            $rg_data['account_name']    = $account_name?$account_name:'无';
            $rg_data['account_info']    = $account_info?$account_info:'无';
            $rg_data['tel']             = $tel;
            $rg_data['address']			= '无';
            $rg_data['info']            = '无';
            D("Common/UserInfos")->add($rg_data);

			$this->success("注册成功！等待报单中心激活", U("User/Login/index"));
		}else{
			$this->error("注册失败！",U("User/Register/index"));
		}
	}
    
    function get_pid_info($rid){
        $pid_number = $this->users_model->where(array("pid"=>$rid))->count();
        if($pid_number < 2 ){
            $pid_array =  array("pid"=>$rid,"area"=>$pid_number+1);
        }else{
            $rid_code = $this->users_model->where(array("id"=>$rid))->getField("rid_code");
            $condition['rid_code']  = array("like", $rid_code.$rid."|%");
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