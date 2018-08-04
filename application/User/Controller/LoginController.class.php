<?php
/** 会员注册 */
namespace User\Controller;
use Common\Controller\HomeBaseController;
class LoginController extends HomeBaseController {
	
	function index(){
		if( sp_get_current_userid() ){
			$this->redirect('User/Center/index');
		}else{
			$pic = M('slide')->where("slide_cid=2")->order("slide_id desc")->getField('slide_pic');
			$pic = sp_get_asset_upload_path($pic);	
			$this->assign('spic',$pic);
			$this->display(":login");
		}
	}

	function CardID(){
		$this->check_login();

		$uid = sp_get_current_userid();
		$rg_user = M('Users')->find( $uid );
		if( $rg_user['is_code'] ){
			$this->redirect('User/Center/index');
		}else{
			$this->display(":CardID");
		}
	}

	function docode(){
		$this->check_login();
		if(IS_POST){
			if(!empty($_POST['photos'])){
				foreach ($_POST['photos'] as $key=>$url){
					$photourl=sp_asset_relative_url($url);
					$smeta['photo'][]=array("url"=>$photourl);
				}
			}
			if(count($smeta['photo'])>1) $this->error("照片文件最多上传一个");

			$uid = sp_get_current_userid();
			$data['identity_img'] = json_encode($smeta);
			$data['identity_time'] = date('Y-m-d H:i:s');
			$res = M("UserInfos")->where(array('user_id'=>$uid))->save($data);
			if($res){
				$this->success("上传成功，等待审核，重新登录！");
			} else {
				$this->error("上传失败！");
			}
		}
	}
	
	function active(){
		$this->check_login();
		$this->display(":active");
	}
	
	function doactive(){
		$this->check_login();
		$this->success('激活邮件发送成功，激活请重新登录！',U("user/index/logout"));
	}
	
	function forgot_password(){
		$this->display(":forgot_password");
	}
	
	
	function doforgot_password(){
		if(IS_POST){
			$users_model=M("Users");
			$rules = array(
					//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
					array('email', 'require', '邮箱不能为空！', 1 ),
					array('email','email','邮箱格式不正确！',1), // 验证email字段格式是否正确
					
			);
			if($users_model->validate($rules)->create()===false) $this->error($users_model->getError());

			if(!sp_check_verify_code()) $this->error("验证码错误！");
			
			$email=I("post.email");
			$find_user=$users_model->where(array("user_email"=>$email))->find();
			if($find_user){
				$this->_send_to_resetpass($find_user);
				$this->success("密码重置邮件发送成功！",__ROOT__."/");
			}else {
				$this->error("账号不存在！");
			}
		}
	}
	
	protected  function _send_to_resetpass($user){
		$options=get_site_options();
		//邮件标题
		$title = $options['site_name']."密码重置";
		$uid=$user['id'];
		$username=$user['user_login'];
	
		$activekey=md5($uid.time().uniqid());
		$users_model=M("Users");
	
		$result=$users_model->where(array("id"=>$uid))->save(array("user_activation_key"=>$activekey));
		if(!$result){
			$this->error('密码重置激活码生成失败！');
		}
		//生成激活链接
		$url = U('user/login/password_reset',array("hash"=>$activekey), "", true);
		//邮件内容
		$template ='#username#，你好！<br> 请点击或复制下面链接进行密码重置：<br> <a href="http://#link#">http://#link#</a>';
		$content = str_replace(array('http://#link#','#username#'), array($url,$username), $template);
	
		$send_result=sp_send_email($user['user_email'], $title, $content);
	
		if($send_result['error']){
			$this->error('密码重置邮件发送失败！');
		}
	}
	
	
	function password_reset(){
		$this->display(":password_reset");
	}
	
	function dopassword_reset(){
		if(IS_POST){
			if(!sp_check_verify_code()){
				$this->error("验证码错误！");
			}else{
				$users_model=M("Users");
				$rules = array(
						//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
						array('password', 'require', '密码不能为空！', 1 ),
						array('repassword', 'require', '重复密码不能为空！', 1 ),
						array('repassword','password','确认密码不正确',0,'confirm'),
						array('hash', 'require', '重复密码激活码不能空！', 1 ),
				);
				if($users_model->validate($rules)->create()===false){
					$this->error($users_model->getError());
				}else{
					$password=sp_password(I("post.password"));
					$hash=I("post.hash");
					$result=$users_model->where(array("user_activation_key"=>$hash))->save(array("user_pass"=>$password,"user_activation_key"=>""));
					if($result){
						$this->success("密码重置成功，请登录！",U("user/login/index"));
					}else {
						$this->error("密码重置失败，重置码无效！");
					}
					
				}
				
			}
		}
	}
	
	
    //登录验证
    function dologin(){
    	if(!sp_check_verify_code()) $this->error("验证码错误！"); 
    	$username = I("post.username");
    	$password = I("post.password");
    	$users_model=M("Users");
    	$rules = array(
			array('username', 'require', '用户名或者邮箱不能为空！', 1 ),
			array('password','require','密码不能为空！',1),
    	);
    	if($users_model->validate($rules)->create()===false){
    		$this->error($users_model->getError());
    	}
    	extract($_POST);
    	$where['user_type']=2;
	
        if( preg_match('/^0?1[3|4|5|6|7|8][0-9]\d{8}$/', $username) ){
            $user_id = M("UserInfos")->where(array('tel'=>$username))->getField('user_id');
            if($user_id > 0){
            	$where['id']=$user_id;
            }else{
            	$where['user_login']=$username;
            }
        }else{
            $where['user_login']=$username;
        }
        $result = M("Users")->where($where)->find();

        if(!$result['user_status']) $this->error("账号不存在或还没激活！");
	
    	if($result != null)
    	{
    		if($result['user_pass'] == sp_password($password))
    		{
    			$_SESSION["user"]=$result;
    			//写入此次登录信息
    			$data = array( 'last_login_time' => date("Y-m-d H:i:s"), 'last_login_ip' => get_client_ip() );
    			$users_model->where("id=".$result["id"])->save($data);

    			$this->success("登录验证成功！", U('User/Center/index'));
    		}else{
    			$this->error("密码错误！");
    		}
    	}else{
    		$this->error("用户名不存在！");
    	}
    	 
    }
	

    function uploadify(){
        if (IS_POST) {
            $savepath = 'cardId/'.date('Ymd').'/';
            //上传处理类
            $config=array(
                    'rootPath'   => './'.C("UPLOADPATH"),
                    'savePath'   => $savepath,
                    'maxSize'    => 10240000,
                    'saveName'   => array('uniqid',''),
                    'exts'       => array('jpg', 'gif', 'png', 'jpeg'),
                    'autoSub'    => false,
            );
            $upload = new \Think\Upload($config);// 
            $info = $upload->upload();
            //开始上传
            if ($info) {
                //上传成功
                //写入附件数据库信息
                $first=array_shift($info);
                if(!empty($first['url'])){
                    $url=$first['url'];
                }else{
                    $url=C("TMPL_PARSE_STRING.__UPLOAD__").$savepath.$first['savename'];
                }
                echo json_encode(array("error"=>"0","pic"=>$url,"name"=>$first['name'])); 
                exit;
            } else {
                //上传失败，返回错误
                echo json_encode(array("error"=>"上传有误，清检查服务器配置！")); 
                exit;
            }
        }
    }
}