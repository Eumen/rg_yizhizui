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

	/*@SEE 用户注册信息提交 */
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
	    
    	$rules = array(
            array('username',       'require', '订单登录名ID不能为空！', 1 ),
            array('password',       'require', '一级密码不能为空！',1),
            array('password2',      'require', '二级密码不能为空！',1),
            array('tz_num',         'require', '认购单数不能为空！', 1 ),
            array('recname',        'require', '推荐人编号不能为空！', 1 ),
            array('biz_username',   'require', '报单中心编号不能为空！', 1 ),
            array('true_name',      'require', '真实姓名不能为空！', 1 ),
            array('tel',            'require', '联系电话不能为空！',1),
            array('tel',            '/1\d{10}$/','联系电话格式不正确！',1, 'regex'),
            array('identity_id',    'require', '身份证不能为空！', 1 ),
            array('verify',         'require', '验证码不能为空！',1),
    	);
    	if(M("Users")->validate($rules)->create()===false) $this->error(M("Users")->getError()); 
    	extract($_POST);
    	if(!preg_match('/^[0-9a-zA-Z]{2,16}$/', $username)) $this->error('订单登录名ID为2-16位英文、数字、英文数字组合');

    	$banned_usernames=explode(",", sp_get_cmf_settings("banned_usernames"));
    	if(in_array($username, $banned_usernames)) $this->error("此注册会员ID禁止使用！"); 

    	if(!preg_match('/^[0-9a-zA-Z]{8,16}$/', $password)) $this->error('密码为8-16位英文、数字、特殊字符组合');
        if(!preg_match('/^[0-9a-zA-Z]{8,16}$/', $password2)) $this->error('二级密码为8-16位英文、数字、特殊字符组合');
        if(!preg_match('/^[0-9a-zA-Z]{8,16}$/', $password3)) $this->error('二级密码为8-16位英文、数字、特殊字符组合');
        $u_id = M("UserInfos")->where(array('tel'=>$tel))->getField('user_id');
        if($u_id>0) $this->error("该联系电话已经注册！"); 

		if(!sp_check_verify_code()) $this->error("验证码错误！");

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
  
        $rg_user = M("Users")->where(array('user_login'=>$username))->count();
        if($rg_user) $this->error("订单登录名ID已经存在！");

		$rec_user= M('Users')->where(array('user_login'=>$recname,'user_type'=>2,'user_status'=>1))->find();
		if( empty($rec_user) ) $this->error('推荐会员ID不存在或者未被激活');
		
		//节点人 和区位查找
    	$pid_info = $this->get_pid_info($rec_user['id']);
    	if(empty($pid_info)) $this->error('节点区位获取失败'); 

        if($tz_num<=0 && $tz_num>50) $this->error("认购单数错误！");

//         if($account_name){
//         	if($true_name != $account_name) $this->error("银行账户名必须显示和注册人真实姓名一致");
//         }
        
		$data = array(
			'user_login'		=> $username,
			'user_email'		=> $username.'@jlh198.com',
			'user_nicename' 	=> $true_name,
			'user_pass'			=> sp_password($password),
			'user_pass2'		=> sp_password($password2),
			'user_pass3'		=> sp_password($password3),
			'last_login_ip' 	=> get_client_ip(),
			'create_time'		=> date("Y-m-d H:i:s"),
			'last_login_time'	=> date("Y-m-d H:i:s"),
			'audit_time'		=> date("Y-m-d H:i:s"),
			'user_status'		=> 0,
			"user_type"			=> 2,
			"rid"				=> $rec_user['id'],
			'add_user_id' 		=> $this->uid,
            "biz_id"			=> $biz_id,
			"rid_code"			=> $rec_user['rid_code'].$rec_user['id']."|",
            "tz_num"			=> intval($tz_num),
			"rand"				=> 1,
            "goods_id"			=> intval($goods_id),
            "hb_amount"			=> $this->site_options['hongbao'] * intval($tz_num),
			"pid"				=> $pid_info['pid'],
			"area"				=> $pid_info['area'],
            "pid_code"          => $pid_info['pid_code'],
            "old_fbnum"			=> intval($tz_num),
        );
		$user_id = M('Users')->add($data);
		if( $user_id ){
			$rg_data['user_id']			= $user_id;
			$rg_data['true_name']		= $true_name;
			$rg_data['identity_id']		= $identity_id?$identity_id:'无';
			$rg_data['account_type']	= $account_type?$account_type:'无';
			$rg_data['account_no']		= $account_no?$account_no:'无';
			$rg_data['account_name']	= $true_name?$true_name:'无';
			$rg_data['account_info']	= $account_info?$account_info:'无';
			$rg_data['tel']				= $tel;
			$rg_data['address']			= '无';
            $rg_data['info']            = '无';
			D("Common/UserInfos")->add($rg_data);

			cookie('rg_succ_user_id', $user_id, 86400);
			$this->success("系统正在努力排序中...", U("User/Business/add_succ"));
		}else{
			$this->error("注册失败！", U("User/Business/add"));
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