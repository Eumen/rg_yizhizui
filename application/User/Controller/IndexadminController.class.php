<?php

namespace User\Controller;
use Common\Controller\AdminbaseController;
class IndexadminController extends AdminbaseController {
	protected $users_model, $userinfos_model;
	protected $mid;
	
	function _initialize() {
		parent::_initialize();
		$this->users_model = D("Common/Users");
		$this->userinfos_model = D("Common/UserInfos");
		$this->readd = D("Common/Readd");

		$this->mid = get_current_admin_id();
        $this->assign('mid', $this->mid);
	}

    function index(){
		$where_ands = array("u.user_type = 2");
        if ( I('post.rid_name') != "" ) {
			$_GET['rid_name'] = I('post.rid_name');
            $rid = $this->users_model->where("user_login='" . I('post.rid_name') . "'")->getField('id');
            array_push($where_ands, "u.rid = '$rid'");
        }
		
        if ( isset($_POST['user_status']) && I('post.user_status') != -1 ) {
			$_GET['user_status'] = I('post.user_status');
			$rg_user_status = $_GET['user_status'] == 2 ? 0 : $_GET['user_status'];
            array_push($where_ands, "u.user_status = '".$rg_user_status."'");
        }
		
		$fields=array(
				'start_time'	=> array("field"=>"u.audit_time","operator"=>">="),
				'end_time'		=> array("field"=>"u.audit_time","operator"=>"<="),
				'keyword'		=> array("field"=>"u.user_login","operator"=>"like"),
				'true_name'		=> array("field"=>"ui.true_name","operator"=>"like"),
				'rand'			=> array("field"=>"u.rand","operator"=>"="),
				'agent'			=> array("field"=>"u.agent","operator"=>"="),
				'partner'		=> array("field"=>"u.partner","operator"=>"="),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){$get="%$get%";}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){$get="%$get%";}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where= join(" and ", $where_ands);
		$count	= $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $lists = $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->field('u.*,ui.true_name,ui.tel,ui.weixin')->limit($page->firstRow . ',' . $page->listRows)->order('u.id desc')->select();
        foreach ($lists as $_k => $_v) {
            $lists[$_k]['rid_name'] = $this->users_model->where( array('id'=>$_v['rid']) )->getField('user_login');
            $lists[$_k]['rec_true_name'] = $this->userinfos_model->where( array('user_id'=>$_v['rid']) )->getField('true_name');
        }
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('lists', $lists);
		
		$rg_price = $this->site_options['PRICE'];
		$today_arr = $this->users_model->where("user_type = 2 and user_status > 0 and DATE_FORMAT(audit_time,'%Y-%m-%d') = '".date('Y-m-d')."'")->select();
		$today_rsnum = count($today_arr);
		foreach($today_arr as $_v){$today_jqnum += $rg_price;}
		$this->assign('today_rsnum', $today_rsnum ? $today_rsnum : 0); 
		$this->assign('today_jqnum', $today_jqnum ? $today_jqnum : 0); 

		$rg_arr = $this->users_model->where("user_type = 2 and user_status > 0")->select();
		$rg_rsnum = count($rg_arr);
		foreach($rg_arr as $_v){$rg_jqnum += $rg_price;}
		$this->assign('rg_rsnum', $rg_rsnum ? $rg_rsnum : 0); 
		$this->assign('rg_jqnum', $rg_jqnum ? $rg_jqnum : 0); 
    		
    		
    		//总会partner
     	$condition['partner'] = 1;
     	$this->assign("fspartner",$this->users_model->where($condition)->count());
     	
     	//总会员总数
     	unset($condition['partner']);
     	$condition['agent'] = 1;
     	$this->assign("fsagent",$this->users_model->where($condition)->count());
     	
    		$this->display(":index");
    }
    
    public function export() {
    	set_time_limit(0);
		$lists = $this->users_model->alias ( "u" )
			->join ( C ( 'DB_PREFIX') . "user_infos ui ON ui.user_id=u.id" )
			->where ( $where )
			->field ( 'u.id,ui.true_name,ui.tel,u.rid,u.amount,u.e_amount,u.shop_amount,u.good_amount,u.r_amount,u.tz_num,u.user_status,u.rand,u.agent' )
			->order ( 'u.id desc' )->select ();
		$expTableData = $lists;
		foreach ($expTableData as $_k => $_v) {
			$expTableData[$_k]['rec_true_name'] = $this->userinfos_model->where( array('user_id'=>$_v['rid']) )->getField('true_name');
			switch ($_v['user_status']) {
				case 1 :
					$expTableData[$_k]['user_status'] = '已经激活';
					break;
				case 2 :
					$expTableData[$_k]['user_status'] = '锁定';
					break;
				case 3 :
					$expTableData[$_k]['user_status'] = '出局';
					break;
				case 0 :
					$expTableData[$_k]['user_status'] = '未激活';
					break;
			}
			switch ($_v['rand']) {
				case 1 :
					$expTableData[$_k]['rand'] = '会员';
					break;
				case 2 :
					$expTableData[$_k]['rand'] = '一星会员';
					break;
				case 3 :
					$expTableData[$_k]['rand'] = '二星会员';
					break;
				case 4 :
					$expTableData[$_k]['rand'] = '三星会员';
					break;
				case 5 :
					$expTableData[$_k]['rand'] = '四星会员';
					break;
				case 6 :
					$expTableData[$_k]['rand'] = '五星会员';
					break;
				case 7 :
					$expTableData[$_k]['rand'] = '预借会员';
					break;
			}
			switch ($_v['agent']) {
				case 1 :
					$expTableData[$_k]['agent'] = '县代理';
					break;
				case 2 :
					$expTableData[$_k]['agent'] = '市代理';
					break;
				case 3 :
					$expTableData[$_k]['agent'] = '省代理';
					break;
				case 0 :
					$expTableData[$_k]['agent'] = '未是';
					break;
			}
		}
		// 导出的Excel表格的名字
		$xlsName = "会员列表";
			// 导出的Excel表格的表头
		$expCellName = array (
				array (
						'id',
						'序号' 
				),
				array (
						'true_name',
						'用户名' 
				),
				array (
						'tel',
						'手机' 
				),
				array (
						'rec_true_name',
						'推荐人' 
				),
				array (
						'amount',
						'奖金积分' 
				),
				array (
						'e_amount',
						'种子积分' 
				),
				array (
						'shop_amount',
						'电子积分' 
				),
				array (
						'good_amount',
						'商城积分' 
				),
				array (
						'r_amount',
						'注册积分' 
				),
				array (
						'tz_num',
						'认购单数' 
				),
				array (
						'user_status',
						'状态' 
				),
				array (
						'rand',
						'等级' 
				),
				array (
						'agent',
						'代理' 
				) 
		);
		
		$fileName = $xlsName;//or $xlsTitle 文件名称可根据自己情况设定
		$cellNum = count($expCellName);//得到表头的长度
		$dataNum = count($expTableData);//得到内容的长度
		vendor("PHPExcel.PHPExcel");//引入EXCEL类包
		$objPHPExcel = new \PHPExcel();//实例化类
		$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		// 	$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
		// 	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1',$fileName.'学生表'); //输入标题
		$objPHPExcel->setActiveSheetIndex(0)->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );  // 设置单元格水平对齐格式
		$objPHPExcel->setActiveSheetIndex(0)->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );        // 设置单元格垂直对齐格式
  		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
		//输出标题栏
		for($i=0;$i<$cellNum;$i++){
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
		}
		//输出内容栏
		for($i=0;$i<$dataNum;$i++){
			for($j=0;$j<$cellNum;$j++){
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
			}
		}
		//导出
		header('pragma:public');
		header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xls"');
		header("Content-Disposition:attachment;filename=$fileName.xls");
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		unset($objWriter, $expTableData , $lists);
	}
	
	public function readd(){
		
		$where_ands = array("u.user_type = 2");
        if ( I('post.user_status') != "" ) {
			$_GET['user_status'] = I('post.user_status');
			$rg_user_status = $_GET['user_status'] == 2 ? 0 : $_GET['user_status'];
            array_push($where_ands, "u.user_status = '".$rg_user_status."'");
        }
		if(I('start_time')) $_POST['start_time'] = $_GET['start_time'] = strtotime(I('start_time'));
		if(I('end_time')) $_POST['end_time'] = $_GET['end_time'] = strtotime(I('end_time'));
		$fields=array(
			'keyword'		=> array("field"=>"u.user_login","operator"=>"like"),
			'true_name'		=> array("field"=>"ui.true_name","operator"=>"like"),
			'start_time'	=> array("field"=>"ra.add_times","operator"=>">="),
			'end_time'  	=> array("field"=>"ra.add_times","operator"=>"<="),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){$get="%$get%";}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){$get="%$get%";}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		if(I('start_time')) $_GET['start_time'] 	= date('Y-m-d', $_POST['start_time']);
		if(I('end_time')) $_GET['end_time']		= date('Y-m-d', $_POST['end_time']);
		
		$where= join(" and ", $where_ands);
		$count	= $this->readd->alias("ra")->join(C ( 'DB_PREFIX' )."users u ON ra.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);
        $lists = $this->readd->alias("ra")->join(C ( 'DB_PREFIX' )."users u ON ra.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->field('ra.*,u.user_login,ui.true_name')
        			->limit($page->firstRow . ',' . $page->listRows)->order('ra.id desc')->select();
        	$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('lists', $lists);
        
		$this->display(":readd");
	}
	
	public function readd_status(){
		$id=intval($_GET['id']);
        $condition['id'] = array('eq',$id);
        $objUser = M('readd')->where($condition)->setField('is_send',1);
        $this->error("处理成功");
	}
	
	public function add(){ 
		$this->display(":add"); 
	}
	public function add_post(){
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
			);
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
						"rid_code"          => $rid_code,
						"add_user_id" 		=> $this->mid,
				        "hb_amount"			=> $this->site_options['hongbao'] * intval($tz_num),
						"pid"               => $pid_info['pid'],
						"area"              => $pid_info['area'],
						"pid_code"          => $pid_info['pid_code'],
						"biz_id"			=> $biz_user['id']?$biz_user['id']:0,
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

					$this->success("注册成功");
				}else{
					$this->error("注册失败！");
				}
			}else{
				$this->error("注册失败！");
			}
		}
	}
	
// 	function get_pid_info($rid){
// 		$pid_number = $this->users_model->where(array("pid"=>$rid))->count();
// 		if($pid_number < 2 ){
// 			$pid_array =  array("pid"=>$rid,"area"=>$pid_number+1);
// 		}else{
// 			$rid_code = $this->users_model->where(array("id"=>$rid))->getField("rid_code");
// 			$condition['rid_code']  = array("like", $rid_code.$rid."|%");
// 			$condition['status']    = array("eq", 1);
// 			$condition['user_type'] = array("eq", 2);
// 			$user_array = $this->users_model->where($condition)->order("id asc")->field('id')->select();
// 			foreach($user_array as $val){
// 				$pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
// 				if($pid_number < 2){
// 					$pid_number = $this->users_model->where(array("pid"=>$val['id']))->count();
// 					$pid_array = array("pid"=>$val['id'], "area"=>$pid_number+1);
// 					break;
// 				}
// 			}
// 		}
// 		if($pid_array){
// 			$rid_code = $this->users_model->where(array("id"=>$pid_array['pid']))->getField("pid_code");
// 			$pid_array['pid_code'] = $rid_code.$pid_array['pid']."|";
// 		}
// 		return $pid_array;
// 	}

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

	public function rg_isexit() {
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
	
	public function edit_user(){
		$user = $this->users_model->find(I('get.id'));
		if($user['rid']){
			$user['rec_name'] = $this->users_model->where( array('id'=>$user['rid']) )->getField('user_login');
		}
		$this->assign($user);
		$userinfo = $this->userinfos_model->where(array('user_id'=>$user['id']))->find();
		$this->assign('userinfo', $userinfo); 

		$rand_price = M('rand_price')->field('id,name,rank_mark')->select();
		$this->assign('rand_price', $rand_price); 

		$this->display(":edit");
	}
	
	public function show_user(){
		$user = $this->users_model->find(I('get.id'));
		if($user['rid']){
			$user['rec_name'] = $this->users_model->where( array('id'=>$user['rid']) )->getField('user_login');
			$user['rec_tname'] = $this->userinfos_model->where( array('user_id'=>$user['rid']) )->getField('true_name');
		}
		$this->assign($user);
		$this->assign('info',$this->userinfos_model->where('user_id='.$user['id'])->find());
		
		$rand_price = M('rand_price')->field('id,name,rank_mark')->select();
		$this->assign('rand_price', $rand_price); 

		$this->display(":show_user");
	}
    
    public function edit_post() {
		if(IS_POST){
			$user = $this->users_model->find(I('get.id'));
			$_POST['id']		 	= $user['id'];
			$_POST['user_login'] = $user['user_login'];
			$_POST['user_pass']  = $user['user_pass'];
			$post_rid = I('rid');
			if($post_rid){
				$rid = $user = $this->users_model->where(array("user_login"=>$post_rid))->getField('id');
				if(empty($rid)){ $this->error("推荐人无法找到，请核对！"); }
				
				if($user['rid'] == $rid){ 
					unset($_POST['rid']); 
				}else if($rid){
					$_POST['rid'] = $rid;
				}
			}else{
				if(empty($post_rid)){ unset($_POST['rid']); }
			}
			
			if ($this->users_model->create()) {
				if ($this->users_model->save()!==false) {
					$this->userinfos_model->where(array('user_id'=>$user['id']))->save(array('true_name'=>$_POST['true_name']));
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
		}
    }

	public function password(){
		$user = $this->users_model->find(I('get.id'));
		if($user['rid']){
			$user['rec_name'] = $this->users_model->where( array('id'=>$user['rid']) )->getField('user_login');
		}
		$this->assign($user);
		$this->display(":password");
	}
    
    public function password_post() {
    	if(IS_POST){
			$user = $this->users_model->find(I('get.id'));
    		if(empty($_POST['password'])) $this->error("一级密码不能为空！");
    		if(empty($_POST['password2'])) $this->error("二级密码不能为空！");
    		if(empty($_POST['password3'])) $this->error("三级密码不能为空！");

    		$password = I('post.password');
    		$password2 = I('post.password2');
    		$password3 = I('post.password3');
    		
			$data['id']			= $user['id'];
			$data['user_pass']	= sp_password($password);
			$data['user_pass2']	= sp_password($password2);
			$data['user_pass3']	= sp_password($password3);
			$r=$this->users_model->save($data);
			if ($r!==false) {
				$this->success("修改成功！");
			} else {
				$this->error("修改失败！");
			}
    	}
    }
    
   public function lock() {
        $id=intval($_GET['id']);
        
        $condition['id'] = array('eq',$id);
        $objUser = $this->users_model->where($condition)->field('id, user_status')->find();
        if ($objUser ['user_status'] == 1) {
            $data ["user_status"] = 2;
            $this->users_model->where($condition)->save($data);
          	$this->success("会员已经锁定");
        }elseif ($objUser ['user_status'] == 2) {
            $data ["user_status"] = 1;
        	$this->users_model->where($condition)->save($data);
        	$this->success("会员已经解锁");
        }else {
			$this->error("未激活的用户无法锁定");
        }
    }

	public function net_tree(){
		
		$this->display(":net_tree");
	}


	public function user_login(){
		$id = I('get.id');
		$condition['id'] = array('eq',$id);
		$result = $this->users_model->where($condition)->find();

		if($result != null){
			$result['cardId20180428'] = 1;
			$_SESSION["user"] = $result;
			$this->redirect("User/Center/index");
    	}else{
    		$this->error("用户名不存在！");
    	}
	}

	public function delete(){
		$get_add_user_id  = I('get.id');
		if($get_add_user_id){
			$condition['id']			= array('eq',$get_add_user_id);
			$condition['user_status']	= array('EQ', 0);
			if($this->users_model->where($condition)->delete()){
				$this->userinfos_model ->where(array('user_id'=>$get_add_user_id))->delete();
				$this->success("删除成功");
			}else{
				$this->error("删除失败");
			}
		}
	}

	public function cardId(){
		$where_ands = array("u.user_type = 2 and ui.identity_img != ''");
		
		$where= join(" and ", $where_ands);
		$count	= $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $datas = $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->field('u.*,ui.true_name,ui.tel,ui.identity_id,ui.identity_img,ui.weixin')->limit($page->firstRow . ',' . $page->listRows)->order('ui.identity_time desc, u.is_code asc')->select();
        foreach ($datas as $key => $value) {
        	$value['code_num'] = $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( "u.is_code=1 and ui.identity_id='".$value['identity_id']."'" )->count('u.id');
        	$lists[] = $value;
        }

		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('lists', $lists);
		
		$this->display(":cardId");
	}
	public function do_cardId(){
        $id=intval($_GET['id']);
        
        $condition['id'] = array('eq',$id);
        $objUser = $this->users_model->where($condition)->field('id, is_code')->find();
        if ($objUser ['is_code'] == 1) {
            $data ["is_code"] = 0;
            $this->users_model->where($condition)->save($data);
          	$this->success("会员身份验证未通过");
        }else {
            $data ["is_code"] = 1;
        	$this->users_model->where($condition)->save($data);
        	$this->success("会员身份验证已经通过");
        }
    }

	public function input_data(){
		$this->display(":input_data");
	}
}
