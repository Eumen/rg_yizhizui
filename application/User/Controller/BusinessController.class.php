<?php
/**  会员注册 */
namespace User\Controller;
use Common\Controller\MemberbaseController;

class BusinessController extends MemberbaseController {
	protected $users_model, $userinfos_model, $layers_model;
	protected $uid;

	function _initialize(){
		parent::_initialize();
		$this->users_model 		= D("Common/Users");
		$this->userinfos_model 	= D("Common/UserInfos");
		$this->layers_model		= D("Common/Layers");
		$this->rand_price 		= M('rand_price');
		$this->uid 				= sp_get_current_userid();
	}

	/*@see 删除 自己注册的用户*/
	public function del_add_user(){
		$get_add_user_id  = I('get.id');
		if($get_add_user_id){
			$condition['id'] 		= array('eq',$get_add_user_id);
			$condition['rid'] 		= array('eq',$this->uid);
			if($this->users_model->where($condition)->find()){
				$condition['user_status'] = array('EQ', 0);
				if($this->users_model->where($condition)->delete()){
					$this->userinfos_model ->where(array('user_id'=>$get_add_user_id))->delete();
					$this->success("删除成功");
				}else{
					$this->error("删除失败");
				}
			}else{
				$this->error("你没有权限删除此用户！");
			}
		}
	}
	
	/**@ 复投**/
	function readd(){
        $this->check_pass2();
        
		if(IS_POST){
			extract($_POST);
			if(!sp_check_verify_code()) $this->error("验证码错误！");

			$tz_num = intval($tz_num);
        	if($tz_num<=0 && $tz_num>50) $this->error("认购单数错误！");

			$amount_type = I('types');
			if($amount_type == 'shop_amount' || $amount_type == 'e_amount'){ 
				$user_obj = $this->users_model->where(array('id'=>$this->uid))->find();
				$user_money = $user_obj[$amount_type];
				$neet_money = $this->site_options['readd']*$tz_num;
				if($user_money < $neet_money){ $this->error("金额不足！"); }
				
				if($this->users_model->where(array('id'=>$this->uid))->setDec($amount_type, $neet_money)){
					$data = array('user_id'=>$this->uid,'types'=>$amount_type,'money'=>$neet_money,'add_times'=>time());
					if(M('readd')->add($data)){
						//红包发放
						$user_data['tz_num'] 	= $user_obj['tz_num'] + $tz_num;
						$user_data['hb_amount'] = $user_obj['hb_amount'] + ($this->site_options['hongbao']*$tz_num);
						$this->users_model->where(array('id'=>$this->uid))->save($user_data);
						//奖金发放
						$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
						$award = new \Common\Lib\Award();
						$r1[] = $award->begin_award($this->uid, $neet_money, $rg_time);
					}
					$this->success("复投成功");
				}
			}else{
				$this->error("类型错误！");
			}
		}else{
			$this->display();
		}
	}
	function readd_list(){
		$count	= M('readd')->alias("ra")->join(C ( 'DB_PREFIX' )."users u ON ra.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where(  array('ra.user_id'=>$this->uid)  )->count();
		$page	= $this->page($count, 20);
        $list = M('readd')->alias("ra")->join(C ( 'DB_PREFIX' )."users u ON ra.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where(  array('ra.user_id'=>$this->uid)  )->field('ra.*,u.user_login,ui.true_name')
        			->limit($page->firstRow . ',' . $page->listRows)->order('ra.id DESC')->select();
		
        $this->assign('list', $list);
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());

        $this->display();
	}
	
	/*@SEE 用户注册页面 */
	function add(){
        $goods = D("Common/Goods")->select();
        $this->assign("goods",$goods);
        $pid = intval(I('id'));
		if($pid){$this->assign('pname',$this->users_model->where(array("id"=>$pid))->getField('user_login'));}
		$area = intval(I('area'));
		if($area){ $this->assign('area',$area); }
		$this->display();
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
	/*@SEE 用户注册信息提交 */
	function doregister(){

		$this->users_model = D("Common/Users");
		$this->userinfos_model = D("Common/UserInfos");
		
		if(IS_POST){
			$username	= I('post.username');
			$email	= I('post.email');
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
					array('email', 'require', '邮箱不能为空！', 1 ),
					array('password', 'require', '密码设置不能为空！', 1 ),
					array('identity_id',    'require', '身份证不能为空！', 1 ),
					array('tel','/1[34578]{1}\d{9}$/','联系手机号格式不正确！',1, 'regex'),
					array('email','email','邮箱格式不正确！',1),
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
			$uc_checkemail=1;
			$uc_checkusername=1;
			if($ucenter_syn){
				include UC_CLIENT_ROOT."client.php";
				$uc_checkemail = uc_user_checkemail($email);
				$uc_checkusername = uc_user_checkname($username);
			}
		
			$where['user_login']=$username;
			$rg_user	= $this->users_model->where("user_login='".$username."'")->find();
			$rec_user	= $this->users_model->where("user_login='".$recname."'")->find();
			$biz_user	= $this->users_model->where( array('user_login'=>$biz_username, 'user_type'=>2, 'is_agent'=>1) )->find();
		
			if($rg_user || $uc_checkemail<0 || $uc_checkusername<0) $this->error("用户名已经存在！");
			if( empty($rec_user) ) $this->error('引介人不存在');
				
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
				$uc_uid = uc_user_register($username,$password,$email);
				if($uc_uid<0) $uc_register=false;
			}
			if($uc_register){
				$data=array(
						'user_login'		=> $username,
						'user_email'		=> $email,
						'user_nicename'		=> $username,
						'user_pass'			=> sp_password($password),
						'user_pass2'		=> sp_password($password2),
						'create_time'		=> date("Y-m-d H:i:s"),
						"tz_num"			=> intval($tz_num),
						'user_status'		=> 0,
						"user_type"			=> 2,
						"rand"			    => 1,
						"rid"				=> $rec_user['id'],
						"add_user_id" 		=> $this->uid,
						"biz_id"			=> $biz_user['id']?$biz_user['id']:0,
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
		
					$this->success("注册成功");
				}else{
					$this->error("注册失败！");
				}
			}else{
				$this->error("注册失败！");
			}
		}
	}
	
	function get_pid_info($pid){
		$pid_number = $this->users_model->where(array("pid"=>$pid))->count();
		if($pid_number < 2 ){
			$pid_array =  array("pid"=>$pid,"area"=>$pid_number+1);
		}else{
			$pid_code = $this->users_model->where(array("id"=>$pid))->getField("pid_code");
			$condition['pid_code'] 		= array("like", $pid_code.$pid."|%");
			$condition['user_status'] 	= array("eq", 1);
			$condition['user_type'] 	= array("eq", 2);
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
	
	function add_succ(){
		$uid				= cookie('rg_succ_user_id');
		$add_user		= $this->users_model->find($uid);
		$add_userinfo	= $this->userinfos_model->where( array('user_id'=>$uid) )->find();
		$this->assign('add_user', $add_user);
		$this->assign('add_userinfo', $add_userinfo);
		$this->display();
	}

	function upgrade(){
		$uid = $this->uid;
		for($i=0;$i<4;$i++){
			$uid = $this->users_model->where(array("id"=>$uid))->getField("pid");
			if(!$uid) break;

			$user_arr[] = $this->users_model->alias("a")->join(C( 'DB_PREFIX' )."user_infos b ON b.user_id = a.id")->where(array("a.id"=>$uid))->field("a.user_nicename,b.tel,b.weixin")->find();
		}
		$this->assign('user_arr', $user_arr);

		$layer = $this->users_model->where(array("id"=>$this->uid))->getField("layer");
		$this->assign('layer', 	$layer);

		$layer_num = $this->layers_model->where(array('user_id'=>$this->uid,'status'=>0))->count();
		if($layer_num && count($user_arr)>1){
			if($layer<=1){
				$up_data = array(2=>array('user'=>$user_arr[1], 'fee'=>$this->site_options['update_layer2_2_money']), 4=>array('user'=>$user_arr[3], 'fee'=>$this->site_options['update_layer2_4_money']));
			}elseif($layer==2){
				$up_data = array(3=>array('user'=>$user_arr[2], 'fee'=>$this->site_options['update_layer3_3_money']), 4=>array('user'=>$user_arr[3], 'fee'=>$this->site_options['update_layer3_4_money']));
			}elseif($layer==3){
				$up_data = array(4=>array('user'=>$user_arr[3], 'fee'=>$this->site_options['update_layer4_4_money']));
			}
		}
		$this->assign('up_data', $up_data);

		$this->display();
	}

	function upgrade_post(){
		$layer = $this->users_model->where(array("id"=>$this->uid))->getField("layer");
		if($layer==4) $this->error("您已目前所在位置已经是最高级，无法升级");

		$res = $this->users_model->user_update();
		if($res==1){
			$this->success("您已成功升级");
		}elseif($res==-1){
			$this->error("您当前并未符合升级条件");
		}elseif($res==-2){
			$this->error("收款方未确认，无法升级");
		}else{
			$this->error("升级失败");
		}
	}
	
	/*@see 判断 用户名已经存在 */
	function rg_isexit() {
        $username	= I('post.username');
        $rg_user	= $this->users_model->where("user_login='$username' and user_type = 2")->find();
		if( $rg_user ) $this->error("用户名已经存在！");

		$this->success("用户名可使用！");
    }

	/*@see 判断 引介人不存在 */
	function rg_isexit_rid() {
        $username	= I('post.username');
        $rg_user	= $this->users_model->where("user_login='$username' and user_type = 2")->find();
		if( empty($rg_user) ) $this->error("引介人不存在");

		if( $rg_user ){
			$user_nicename = $rg_user['user_nicename'] ? $rg_user['user_nicename'] : '无';

			$this->success("引介人：".$user_nicename);
		}else{
			$this->error("引介人不可使用！");
		}
    }

	/*@see 判断 接点人不存在 */
	function rg_isexit_pid() {
        $username	= I('post.username');
        $rg_user	= $this->users_model->where("user_login='$username' and user_type = 2")->find();
		if( empty($rg_user) ) $this->error("接点人不存在");

		if( $rg_user ){
			$user_nicename = $rg_user['user_nicename'] ? $rg_user['user_nicename'] : '无';

			$this->success("接点人：".$user_nicename);
		}else{
			$this->error("接点人不可使用！");
		}
    }

	/*@see 用户推荐人数列表 */
	function lists_user_rid() {
        $this->check_pass2();
        
		$user_id = I('uid');
		if(empty($user_id)){ $user_id = $this->uid;}
		
        $count	= $this->users_model->where( array('rid'=>$user_id, 'user_type'=>2) )->count();
		$page	= $this->page($count, 15);
        $list   = $this->users_model->where( array('rid'=>$user_id, 'user_type'=>2) )->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();
        foreach ($list as $v) {
            $user_info = $this->userinfos_model->where( array('user_id'=>$v["id"]) )->find();
            $rname = $this->users_model->where( array('id'=>$v["rid"]) )->getField('user_login');
            $posts[] = array(
				"id"				=> $v["id"],
                "login_name"		=> $v["user_login"],
                "true_name"		=> $user_info["true_name"],
                "audit_time"		=> $v["audit_time"],
                "status"			=> $v["user_status"],
            	   "rand"			=> $v["rand"],
               "price"			=> $this->rand_price->where("rank_mark=".$v["rand"])->getField('price'),
               "rname"			=> $rname,
            );
        }
        
        $this->assign("rname", $this->users_model->where("id=".$user_id)->getField('user_login'));
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
        $this->assign('posts', $posts);
        $this->display();
    }
    public function rmy(){
    		$rid =  $this->users_model->where( array('id'=>$this->uid) )->getField('rid');
    		$this->assign('ruser', $this->users_model->where("id=".$rid)->find());
    		$this->assign('rinfo', $this->userinfos_model->where("user_id=".$rid)->find());
    		$this->display();
    }
	/**   @see 用户注册人数列表  */
	function lists_user_add() {
        $this->check_pass2();
        
        $count	= $this->users_model->where( array('add_user_id'=>$this->uid, 'user_type'=>2) )->count();
        $page	= $this->page($count, 15);
        $list = $this->users_model->where( array('add_user_id'=>$this->uid, 'user_type'=>2) )->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();
        foreach ($list as $p => $v) {
            $rip_name = $this->users_model->where( array('id'=>$v['rid']) )->getField('user_login');
            $audit_name = $this->users_model->where( array('id'=>$v['audit_id']) )->getField('user_login');
            $true_name = $this->userinfos_model->where( array('user_id'=>$v['id']) )->getField('true_name');
            if(empty($audit_name)) $audit_name ='--';

			$wxheadimg = M()->table('weixin_user')->where(array('muid'=>$v['id']))->getField("headimgurl");
			$smeta = json_decode($v['avatar'],true); $photo = $smeta['photo']; $avatar = sp_get_asset_upload_path($photo[0][url]);
			$avatar = $rg_user['avatar']?$avatar:($wxheadimg?$wxheadimg:"tpl/simplebootx/plugins/images/users/varun.jpg");

            $posts[] = array(
                'id'				=> $v['id'],
                'user_login'		=> $v['user_login'],
                'rand'			=> $v['rand'],
                'status'			=> $v['user_status'],
                'audit_id'		=> $v['audit_id'],
                'rip_name'		=> $rip_name,
                'audit_name'		=> $audit_name,
                'audit_time'		=> $v['audit_time'],
				'true_name'		=> $true_name,
				'avatar'			=> $avatar,
            );
        }
        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
        $this->assign('posts', $posts);
		$this->assign('money', $this->site_options['PRICE']);
        $this->display();
    }

	/** 
	 * @see 报单中心列表
	*/
	function lists_centers() {
        $this->check_pass2();
        
		$array = array('biz_id'=>$this->uid, 'user_type'=>2);
        $count	= $this->users_model->where($array)->count();
        $page	= $this->page($count, 15);
        $list = $this->users_model->where($array)->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();
        foreach ($list as $p => $v) {
            $rip_name 	= $this->users_model->where( array('id'=>$v['rid']) )->getField('user_login');
            $biz_name 	= $this->users_model->where( array('id'=>$v['biz_id']) )->getField('user_login');
            $audit_name = $this->users_model->where( array('id'=>$v['audit_id']) )->getField('user_login');
            $user_info 	= $this->userinfos_model->where( array('user_id'=>$v["id"]) )->find();
            if(empty($audit_name)) $audit_name ='--';

            $price = $this->rand_price->where( array('rank_mark'=>$v["rand"]) )->getField('price');
            $tatol_money = $price * $v['tz_num'];
//             $money = "费用电子积分".($tatol_money*0.5).",注册积分".($tatol_money*0.5);
            $money = "费用电子积分".$tatol_money;
            $posts[] = array(
                'id'				=> $v['id'],
                'user_login'		=> $v['user_login'],
                'true_name'			=> $v['user_nicename'],
                'status'			=> $v['user_status'],
                'audit_id'			=> $v['audit_id'],
                'rip_name'			=> $rip_name,
                'biz_name'			=> $biz_name,
                'audit_name'		=> $audit_name,
                'audit_time'		=> $v['audit_time'],
				'tel'				=> $user_info['tel'],
                'area'				=> $v['area'],
                'rand'				=> $v['rand'],
                'tz_num'			=> $v['tz_num'],
                'need_money'		=> $money
            );
        }
        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
        $this->assign('posts', $posts);
		$this->assign('money', $this->site_options['PRICE']);
        $rand_price = M('rand_price')->select();
        $rand_price=change_arr_key($rand_price,'rank_mark');
        $this->assign('rand_price', $rand_price);
        $this->display();
    }
	/**   @see 用户推荐图  */
	function net_tree() {
        $this->check_pass2();
        
		$user_id = I('uid');
		if(empty($user_id)){ $user_id = $this->uid;}
		
        $list   = $this->users_model->where( array('rid'=>$user_id, 'user_type'=>2) )->order('id desc')->select();
		$this->assign("rg_members",$list);

        $this->display();
    }
	/**   @see 用户双轨图   */
	function net_pyramid() {
        $this->check_pass2();
        
        $id_byp = $this->users_model->where( array('user_login'=>$_POST['keyword'], 'user_type'=>2, 'id'=>array('EGT', $this->uid)) )->getField('id');

		$id = $id_byp ? $id_byp  : $this->uid;
		$id = !empty($_GET['id']) ? $_GET['id'] : $id;
		
        $condition['user_type'] = array('eq', 2);

        // 第1层
        $condition['id'] = array('eq',$id);
        $objUser_1 = $this->users_model->where($condition)->find();
        $this->assign('objUser_1', $objUser_1);
        unset($condition);

		 // 第2层 左边 
		$condition['pid'] = array('eq',$objUser_1['id']);
		$condition['area'] = array('eq',1);
        $objUser_2_1 = $this->users_model->where($condition)->find();
        $this->assign('objUser_2_1', $objUser_2_1);
 		
 		// 第2层 右边
 		$condition['area'] = array('eq',2);
        $objUser_2_2 = $this->users_model->where($condition)->find();
        $this->assign('objUser_2_2', $objUser_2_2);

        // 第3层 左边 第一个 分支
        //分支第一个
        $condition['pid'] = array('eq',$objUser_2_1 ['id']);
		$condition['area'] = array('eq',1);
        $objUser_3_1 = $this->users_model->where($condition)->find();
        $this->assign('objUser_3_1', $objUser_3_1);

		//分支第2个
		$condition['area'] = array('eq',2);
        $objUser_3_2 = $this->users_model->where($condition)->find();
        $this->assign('objUser_3_2', $objUser_3_2);

		 // 第3层 左边 第2个 分支
		$condition['pid']  = array('eq',$objUser_2_2 ['id']);
		$condition['area'] = array('eq',1);
        $objUser_3_3 =$this->users_model->where($condition)->find();
        $this->assign('objUser_3_3', $objUser_3_3);

		//分支第2个
		$condition['area'] = array('eq',2);
        $objUser_3_4 = $this->users_model->where($condition)->find();
        $this->assign('objUser_3_4', $objUser_3_4);

        $this->display();
    }

	/**   @see 用户激活   */
	public function activit(){
        $get_user_id = I('get.id');
        $user_obj = $this->users_model->where(array('id'=>$get_user_id))->find();
		if (empty($user_obj)) $this->error('无法找到激活用户!'); 
        if ($user_obj['user_status'] == 1) $this->error('已经激活，不能重复激活!'); 
//         if ($user_obj['add_user_id'] != $this->uid) $this->error('此会员不是您添加的，不能激活');

        $rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
        //$rg_time = array('addtime'=>date('Y-m-d',strtotime($user_obj['create_time'])+100), 'createtime'=>date('H:i:s',strtotime($user_obj['create_time'])+100));

        $price = $this->rand_price->where( array('rank_mark'=>$user_obj["rand"]) )->getField('price');
        $neet_money 	= $price * $user_obj['tz_num'];

        $shop_amount 	= $this->users_model->where( array('id'=>$this->uid) )->getField('shop_amount');
        $r_amount   	= $this->users_model->where( array('id'=>$this->uid) )->getField('r_amount');

        if ($shop_amount < ($neet_money)) $this->error('您的账户电子积分不足，不能激活！');
//         if ($shop_amount < ($neet_money*0.5)) $this->error('您的账户电子积分不足，不能激活！');
//         if ($r_amount 	 < ($neet_money*0.5)) $this->error('您的账户注册积分不足，不能激活！');
        $update_money = array('shop_amount'=>$shop_amount-($neet_money));
// 	    $update_money = array('shop_amount'=>$shop_amount-($neet_money*0.5), 'r_amount'=>$r_amount-($neet_money*0.5));

        // 扣除激活所需的相应币种金额
        if( $this->users_model->where( array('id'=>$this->uid) )->setField($update_money) ){
            D("Common/Incomes")->income_record($this->uid, "REGUSER", "激活普通会员", '-'.$neet_money, 1, $rg_time, array('pay_uid'=>$get_user_id));

			$this->users_model->Activation($get_user_id, $neet_money, $rg_time); //激活并发放奖励

            $update_user['user_status']	= 1;
            $update_user['audit_id']	= $this->uid;
            $update_user['audit_time']	= $rg_time['addtime'].' '.$rg_time['createtime'];
            $this->users_model->where( array('id'=>$get_user_id) )->setField($update_user);

            $this->success('恭喜您,激活会员成功！');
        }else{
        	$this->error('不能激活！'); 
        }
	}
}