<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class FinancialController extends AdminbaseController {
	protected $users_model, $userinfos_model, $mentions_model, $charges_model, $incomes_model, $options_model;

	function _initialize() {
		parent::_initialize();
		$this->users_model = D("Common/Users");
		$this->userinfos_model = D("Common/UserInfos");
		$this->mentions_model = D("Common/Mentions");
		$this->charges_model = D("Common/Charges");
		$this->incomes_model = D("Common/Incomes");
		$this->options_model = D("Common/Options");
	}

    public function index() {
		$status=0;
		if(!empty($_REQUEST["status"])){
			$status=intval($_REQUEST["status"]);
			$_GET['status']=$status;
		}

		$where_ands = empty($status) ? array("u.user_type = 2") : array("u.user_type = 2 and m.status=$status");
		
		$fields=array(
				'start_time'=> array("field"=>"m.addtime","operator"=>">="),
				'end_time'  => array("field"=>"m.addtime","operator"=>"<="),
				'keyword'	=> array("field"=>"u.user_login","operator"=>"like"),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where= join(" and ", $where_ands);
        
		$count	= $this->mentions_model->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $list = $this->mentions_model->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where($where)->field('m.*,ui.account_type,ui.account_no,ui.true_name,ui.account_name,ui.account_info,u.user_login')->limit($page->firstRow . ',' . $page->listRows)->order('m.id desc')->select();
        
        $charge = $this->site_options['GETMONEYFEE'];
        foreach ($list as $p => $v) {
			$act_amount =  $v['amount'] - ($v['amount'] * $charge);
			$rg_charge = ($v['amount'] * $charge).'('.($charge*100).'%)';
            $posts [] = array(
                'id'			=> $v['id'],
                'amount'		=> $v['amount'],
                'i_amount'		=> $v['amount']*$rg_pro,
                'act_amount'	=> $act_amount,
                'charge'		=> $rg_charge,
                'status'		=> $v['status'],
                'addtime'		=> $v['addtime'],
                'account_type'	=> $v['bank_type'],
                'account_no'	=> $v['bank_number'],
                'account_name'	=> $v['bank_user_name'],
                'account_info'	=> $v['bank_adree'],
                'login_name'	=> $v['user_login'],
                'memo'			=> $v['memo']
            );
        }
        
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);
		
		$rg_where = array('addtime'=>array('between', array(date('Y-m-d'), date('Y-m-d 23:59:59'))));
		$today_arr = $this->mentions_model->where( $rg_where )->select();
		$this->assign('today_user', count($today_arr)); 
		$rg_allarr = $this->mentions_model->select();
		$this->assign('num_user', count($rg_allarr)); 

		foreach($today_arr as $_v){
			$act_amount =  $_v['types'] == 'amount' ? $_v['amount'] - ($_v['amount'] * $charge) : $_v['amount'];
			
			$today_txnum += $_v['amount'];
			$today_dznum += $act_amount;
		}
		$this->assign('today_txnum', $today_txnum ? $today_txnum : 0); 
		$this->assign('today_dznum', $today_dznum ? $today_dznum : 0); 

		foreach($rg_allarr as $_v){
			$act_amount =  $_v['types'] == 'amount' ? $_v['amount'] - ($_v['amount'] * $charge) : $_v['amount'];
			
			$txnum += $_v['amount'];
			$dznum += $act_amount;
		}
		$this->assign('txnum', $txnum ? $txnum : 0); 
		$this->assign('dznum', $dznum ? $dznum : 0); 

        $this->display();
    }
    
    
    public function export() {
    	set_time_limit(0);
    	$list = $this->mentions_model->alias ( "m" )
	    	->join ( C ( 'DB_PREFIX' ) . "users u ON m.user_id=u.id" )
	    	->join ( C ( 'DB_PREFIX' ) . "user_infos ui ON ui.user_id=u.id" )
	    	->field ( 'm.*,ui.account_type,ui.account_no,ui.true_name,ui.account_name,ui.account_info,u.user_login' )
	    	->order ( 'm.id desc' )->select ();
    	
    	$charge = $this->site_options['GETMONEYFEE'];
    	$status=array("0"=>"未审核","1"=>"已审核","3"=>"已撤销");
        foreach ($list as $p => $v) {
			$act_amount =  $v['amount'] - ($v['amount'] * $charge);
			$rg_charge = ($v['amount'] * $charge).'('.($charge*100).'%)';
            $posts [] = array(
                'id'			=> $v['id'],
                'amount'		=> $v['amount'],
                'i_amount'		=> $v['amount']*$rg_pro,
                'act_amount'	=> $act_amount,
                'charge'		=> $rg_charge,
                'status'		=> $status[$v['status']],
                'addtime'		=> $v['addtime'],
                'account_type'	=> $v['bank_type'],
                'account_no'	=> $v['bank_number'].' ',
                'account_name'	=> $v['bank_user_name'],
                'account_info'	=> $v['bank_adree'],
                'login_name'	=> $v['user_login'],
                'memo'			=> $v['memo']
            );
        }
    	$expTableData = $posts;
        
    	// 导出的Excel表格的名字
    	$xlsName = "提现列表";
    	// 导出的Excel表格的表头
    	$expCellName = array (
    			array (
    					'id',
    					'序号'
    			),
    			array (
    					'login_name',
    					'用户名'
    			),
    			array (
    					'amount',
    					'提现金额'
    			),
    			array (
    					'charge',
    					'手续费'
    			),
    			array (
    					'act_amount',
    					'到帐金额'
    			),
    			array (
    					'account_type',
    					'银行类型'
    			),
    			array (
    					'account_no',
    					'银行账户'
    			),
    			array (
    					'account_name',
    					'账户名'
    			),
    			array (
    					'account_info',
    					'开户行址'
    			),
    			array (
    					'memo',
    					'备注'
    			),
    			array (
    					'addtime',
    					'申请时间'
    			),
    			array (
    					'status',
    					'状态'
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
    

    public function change_list() {
		$status=0;
		if(!empty($_REQUEST["status"])){
			$status=intval($_REQUEST["status"]);
			$_GET['status']=$status;
		}

		$where_ands = empty($status) ? array() : array("m.status=$status");
		
		$fields=array(
				'start_time'=> array("field"=>"m.addtime","operator"=>">="),
				'end_time'  => array("field"=>"m.addtime","operator"=>"<="),
				'keyword'	=> array("field"=>"u.user_login","operator"=>"="),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where= join(" and ", $where_ands);
        
		$count	= M('change_money_lists')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $list = M('change_money_lists')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where($where)->field('m.*,u.user_login,ui.true_name')->limit($page->firstRow . ',' . $page->listRows)->order('m.id desc')->select();
        
        $types_array = array('shop_amount'=>'电子积分', 'r_amount'=>'注册积分');
        foreach ($list as $p => $v) {
			$to_user = $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( array('u.id'=>$v['to_user_id']) )->field('u.user_login,ui.true_name')->find();

			$get_money = $v['amount'] - $v['amount']*$v['bili'];
			$fee = $v['amount']*$v['bili']."(". $v['bili']*100 ."%)";
            $posts [] = array(
	            'id'    		=> $v['id'],
	            'user_login'    => $v['user_login'], 
	            'true_name'    	=> $v['true_name'], 
	            'to_user_login' => $to_user['user_login'], 
	            'to_true_name' 	=> $to_user['true_name'], 
	            'get_money'    	=> $get_money,
	            'fee'         	=> $fee,
	            'reason'       	=> $v['reason'], 
	            'amount'       	=> $v['amount'], 
	            'addtime'      	=> $v['addtime'] ,
	            'types'        	=> $types_array[$v['type']]
            );
        }
        
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);

        $this->display();
    }

    public function convert_list() {
		$status=0;
		if(!empty($_REQUEST["status"])){
			$status=intval($_REQUEST["status"]);
			$_GET['status']=$status;
		}

		$where_ands = empty($status) ? array("u.user_type = 2") : array("u.user_type = 2 and m.status=$status");
		
		$fields=array(
				'start_time'=> array("field"=>"m.addtime","operator"=>">="),
				'end_time'  => array("field"=>"m.addtime","operator"=>"<="),
				'keyword'	=> array("field"=>"u.user_login","operator"=>"like"),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where= join(" and ", $where_ands);
        
		$count	= M('convert_money_lists')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $posts  = M('convert_money_lists')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where($where)->field('m.*,u.user_login,ui.true_name')->limit($page->firstRow . ',' . $page->listRows)->order('m.id desc')->select();
        
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);

        $this->display();
    }

    public function mention_audit() {
		$id = I('get.id');
        $objMention = $this->mentions_model->find( $id );
        if ($objMention ['status'] == 0) {
            $this->mentions_model->where( array('id'=>$id) )->setField('status', '1');

            $this->success("审核成功", U("Financial/index"));
        } else {
            $this->error('该用户已审核，勿重复操作');
        }
    }

	public function mention_cansel() {
		$id = I('get.id');
        $objMention = $this->mentions_model->find( $id );
        if ($objMention ['status'] == 0 && !empty($objMention)) {
            $money = $objMention ['amount'];
            $this->users_model->where( array('id'=>$objMention['user_id']) )->setInc($objMention['types'], $money);

            $data ['id']		= $id;
            $data ['status']	= 3;
            $this->mentions_model->save($data);

            $this->success("撤销成功", U("Financial/index"));
        } else {
            $this->error('提现已成功，不可撤销');
        }
    }

	public function recharge_list(){
		$where_ands = array("u.user_type = 2 and status = 1");
		$fields=array(
				'start_time'=> array("field"=>"c.addtime","operator"=>">="),
				'end_time'  => array("field"=>"c.addtime","operator"=>"<="),
				'keyword'	=> array("field"=>"u.user_login","operator"=>"like"),
				'true_name'	=> array("field"=>"ui.true_name","operator"=>"like"),
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){
						$get="%$get%";
					}
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where= join(" and ", $where_ands);
		
		$count	= $this->charges_model->alias("c")->join(C ( 'DB_PREFIX' )."users u ON c.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON u.id=ui.user_id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $posts = $this->charges_model->alias("c")->join(C ( 'DB_PREFIX' )."users u ON c.user_id=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON u.id=ui.user_id")->where( $where )->field('c.*,u.user_login,ui.true_name')->limit($page->firstRow . ',' . $page->listRows)->order("c.id desc")->select();
        
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);

		$rg_where = array('addtime'=>array('between', array(date('Y-m-d'), date('Y-m-d 23:59:59'))));
		$today_arr = $this->charges_model->where( $rg_where )->select();
		$this->assign('today_user', count($today_arr)); 
		$rg_allarr = $this->charges_model->select();
		$this->assign('num_user', count($rg_allarr)); 

		foreach($today_arr as $_v){
			$today_txnum += $_v['amount'];
		}
		$this->assign('today_txnum', $today_txnum ? $today_txnum : 0); 

		foreach($rg_allarr as $_v){
			$txnum += $_v['amount'];
		}
		$this->assign('txnum', $txnum ? $txnum : 0); 

        $this->display();
	}
	
	public function recharge_add(){
		$this->display();
	}
	/**
	 * @see 用户充值 CHARGE   RECHARGE
	 */
	public function recharge_add_post(){
		$rules = array(
				array('login_name', 'require', '会员账号不能为空！', 1 ),
				array('amount', 	'require', '金额不能为空！', 1 ),
				array('reason', 	'require', '备注不能为空！', 1 ),
		);
		if($this->charges_model->validate($rules)->create()===false){
			$this->error($this->charges_model->getError());
		}

		$user_obj = $this->users_model->where(array('user_login'=>trim(I('login_name')),'user_type'=>2))->field('e_amount,amount,id')->find();
		$user_id = $user_obj['id'];
		if( empty($user_id) ) $this->error('您输入的用户名不存在');
		//begin
		if($this->charges_model->create()){
			$this->charges_model->addtime  = date('Y-m-d H:i:s');
			$this->charges_model->user_id  = $user_id;
			$this->charges_model->status   = 1;
			$this->charges_model->admin_id = get_current_admin_id();
			$this->charges_model->charges_type = I("amount_types");
			if(I('types') == "CHARGE"){
				if($this->charges_model->add()){
					$this->users_model->where(array('id'=>$user_id))->setInc(I("amount_types"),I("amount"));
					if($this->incomes_model->income_record($user_id, 'CHARGE', '帐户充值', I('amount'),2)){
						$this->success("操作成功", U("Financial/recharge_list"));
					}else{
						$this->error("记录失败");
					}
				}else{ $this->error("操作失败");}
				
			}else if(I('types') == "RECHARGE"){
				if($user_obj[I("amount_types")] < I('amount')){
					$this->error('用户金额不足');
				}else{
					if($this->charges_model->add()){
						$this->users_model->where(array('id'=>$user_id))->setDec(I("amount_types"),I("amount"));
						if($this->incomes_model->income_record($user_id, 'RECHARGE', '帐户扣减', I('amount'),2 )){
							$this->success("操作成功", U("Financial/recharge_list"));
						}else{
							$this->error("记录失败");
						}
					}
				}
			}
		}
	}

	public function recharge_del(){
        if($this->charges_model->where('id='.I('get.id'))->setField('status',2)){
        		$this->success("删除成功", U("Financial/recharge_list"));
        }else{
        		$this->error("删除失败");
        }
	}

	public function rg_isexit() {
        $name		= I('post.username');
        $rg_user	= $this->users_model->where("user_login='$name' and user_type = 2")->find();
		if( empty($rg_user) ) $this->error("用户名不存在");

		$rg_userInfo = $this->userinfos_model->where( array('user_id'=>$rg_user['id']) )->find();
		if( $rg_userInfo ){
			$true_name = $rg_userInfo['true_name'] ? $rg_userInfo['true_name'] : '无';
			$this->success("用户名存在,用户名称：".$true_name);
		}else{
			$this->error("用户名还未填写详细信息");
		}
    }
    
    
     public function fscount() {
     	$get_start_time = I('start_time');
     	empty($get_start_time) ? $month = date('Y-m') : $month = $get_start_time;
     	//已经激活
     	$condition['user_status'] = 1;
     	$condition['user_type'] = 2;
     	$this->assign("act_count_user",$this->users_model->where($condition)->count());
     	//未激活
     	$condition['user_status'] = 0;
     	$this->assign("count_untivi_user",$this->users_model->where($condition)->count());
     	
     	//总会员总数
     	unset($condition['user_status']);
     	$this->assign("tatol_user",$this->users_model->where($condition)->count());

     	//会员奖金统计
     	unset($condition['user_status']);
     	$this->assign("amount",$this->users_model->where($condition)->sum('amount'));
     	$this->assign("e_amount",$this->users_model->where($condition)->sum('e_amount'));
     	$this->assign("shop_amount",$this->users_model->where($condition)->sum('shop_amount'));
     	$this->assign("good_amount",$this->users_model->where($condition)->sum('good_amount'));
     	$this->assign("r_amount",$this->users_model->where($condition)->sum('r_amount'));
     	$this->assign("tz_nums",$this->users_model->where($condition)->sum('tz_num'));
     	     	
     	//今日激活
     	$condition['user_status'] = 1;
     	$condition['user_type'] = 2;
     	$date = date('Y-m-d');
     	$condition['_string'] = "DATE_FORMAT(audit_time,'%Y-%m-%d')= '$date'";
     	
     	$this->assign("today_act_count_user",$this->users_model->where($condition)->count());
     	
     	//总拨出
     	$award_type = array('SHOPPING','RID','ZFENHONG','DFENHONG');
     	unset($condition);
     	$condition['types'] = array('in',$award_type);
     	$condition['status'] = array('eq',1);
     	$this->assign("user_output",$this->incomes_model->where($condition)->sum('amount'));
     	unset($condition);
     	$sum_toal = 0 ;
     	//单个奖金统计
     	$condition['status'] = array('eq',1);
     	foreach($award_type as $key=>$val){
	     	$condition['types'] = array('eq',$val);
	     	$amoney = $this->incomes_model->where($condition)->sum('amount');
	     	$sum_toal += $amoney;
	     	$this->assign($val,$amoney);
     	}
     	$this->assign("award_type",$award_type);
     	$this->assign("sum_toal",$sum_toal);
     	
     	//今日拨出
     	$today_sum_toal = 0 ;
     	//单个奖金统计
     	$condition['status'] = array('eq',1);
     	$condition['_string'] = "addtime='$date'";
     	foreach($award_type as $val){
	     	$condition['types'] = array('eq',$val);
	     	$amoney = $this->incomes_model->where($condition)->sum('amount');
	     	$today_sum_toal += $amoney;
	     	$this->assign("T".$val,$amoney);
     	}
     	
     	$this->assign("today_sum_toal",$today_sum_toal);

     	$parent_id = I('pid') ? I('pid') : 1;
		$get_citys = M('ecs_region')->where(array('parent_id'=>$parent_id))->field('region_id,region_name')->select();
		$this->assign("citys",$get_citys);
		
		unset($condition);
		$condition['addtime'] = "DATE_FORMAT(addtime,'%Y-%m-%d')= '$date'";
		$total_amount = $this->incomes_model->alias("i")->join(C ( 'DB_PREFIX' )."users u ON i.user_id=u.id")
			->where( 'u.user_type = 2 and i.types = "hongbao" and i.addtime ="'.$date.'"' )->field('IFNULL(sum(i.amount),0) as packageAmount')->order('i.id desc')->select();
		$this->assign("packageAmount",$total_amount[0][packageamount]);
		
		$option=$this->options_model->where("option_name='site_options'")->getField("option_value");
		$jsonoption = json_decode($option,true);
		$hongbao_day = $jsonoption['hongbao_day'];
		$this->assign("packageNumber",$total_amount[0][packageamount] / $hongbao_day);
		
     	$this->display();
     }
     
     public function yinlian(){
     	$status=0;
		if(!empty($_REQUEST["status"])){
			$status=intval($_REQUEST["status"]);
			$_GET['status']=$status;
		}

		$where_ands = empty($status) ? array("u.user_type = 2") : array("u.user_type = 2 and m.status=$status");
		
		$fields=array(
				'start_time'=> array("field"=>"m.addtime","operator"=>">="),
				'end_time'  => array("field"=>"m.addtime","operator"=>"<="),
				'keyword'	=> array("field"=>"u.user_login","operator"=>"like"),
		);
		if(IS_POST){
			
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if($operator=="like"){ $get="%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_GET[$param];
					if($operator=="like"){ $get="%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		$where= join(" and ", $where_ands);
        
		$count	= M('yinlianhui')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);
        $list   = M('yinlianhui')->alias("m")->join(C ( 'DB_PREFIX' )."users u ON m.user_id=u.id")->where($where)->field('m.*,u.user_login')->limit($page->firstRow . ',' . $page->listRows)->order('m.id desc')->select();
        
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $list);
        $this->display();
     }
     
     function rank_users(){
     	$rank = I('rank');
     	if($rank=='parnert'){
     		$where= "partner=1";
     	}else if($rank=='agent'){
     		$where= "agent=1";
     	}else{
     		$where= "rand=".$rank;
     		if(empty($rank)){ $rank = 0; }
     	}
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
        $this->assign('lists', $lists);
     	$this->display();
     }
}