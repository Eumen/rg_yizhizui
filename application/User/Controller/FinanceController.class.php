<?php

namespace User\Controller;
use Common\Controller\MemberbaseController;
class FinanceController extends MemberbaseController {
	protected $users_model, $userinfos_model, $incomes_model, $convertmoneylist_model, $changemoneylist_model, $mentions_model;
	protected $uid;

	function _initialize(){
		parent::_initialize();
		$this->users_model	   	= D("Common/Users");
		$this->userinfos_model 	= D("Common/UserInfos");
		$this->incomes_model		= D("Common/Incomes");
		$this->convertmoneylist_model = D("Common/ConvertMoneyLists");
		$this->changemoneylist_model	= D("Common/ChangeMoneyLists");
		$this->mentions_model	=	D("Common/Mentions");
		$this->yinlianhui		= M('yinlianhui');
		$this->uid = sp_get_current_userid();
		$this->assign("rg_on", 2);
        
        $this->check_pass2();
        
	}
	
	/**  奖金明细  */
    public function award_list() {
        // 获取级别
        $this->assign("rand", $this->users_model->where('id = '.$this->uid)->getField('rand'));
        //获取今天的奖金总和
        $date_string  = date('Y-m-d');
        $condition['user_id'] = array('eq',$this->uid);
        $condition['types'] = array('not in','CHARGE,REGUSER,RECHARGE');
        $condition['addtime'] = array('eq',date('Y-m-d'));
        $this->assign("today_amount", $today_num_info = $this->incomes_model->where($condition)->sum('amount'));
        unset($condition['addtime']);
        //获取所有的奖金总和
        $this->assign("rg_sum", $total_num_info = $this->incomes_model->where($condition)->sum('amount'));
        $num_info = $this->incomes_model->where($condition)->group("addtime")->select();
        $count = count( $num_info );
        $page   = $this->page($count, 20);

        $info = $this->incomes_model->where($condition)->group("addtime")->limit($page->firstRow . ',' . $page->listRows)->order('addtime DESC')->select();

        foreach($info as $_v){
            $condition['addtime'] = array('eq',$_v['addtime']);
            
            $condition['types'] = array('eq','RID');
            $month["sum_rid"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','POINT');
            $month["sum_point"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','CNETER');
            $month["sum_center"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','LEADER');
            $month["sum_leader"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','PJJ');
            $month["sum_pjj"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','QGFH');
            $month["sum_qgfh"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','hongbao');
            $month["sum_hongbao"] = $this->incomes_model->where($condition)->sum("amount");
            
            $condition['types'] = array('eq','MANAGER');
            $month["sum_manager"] = $this->incomes_model->where($condition)->sum("amount");
            
            $month["addtime"] = $_v['addtime'];
            $month["total"] = $month["sum_rid"] + $month["sum_point"] + $month["sum_center"] + $month["sum_leader"] + $month["sum_pjj"] + $month["sum_qgfh"] + $month["sum_hongbao"] + $month["sum_manager"];
            $list[] = $month;
        }
        $this->assign("list", $list);
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page",$page->GetCurrentPage());

        $this->display();
    }

    public function recei_list() {
		$condition['a.user_id']	= array('eq', $this->uid);
		$condition['a.types']	= array('in', 'PID,LAYER');
		$condition['a.addtime']	= array('eq', date('Y-m-d'));
    		$today_sum = $this->incomes_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.pay_uid")->join(C( 'DB_PREFIX' )."user_infos c ON c.user_id = b.id")->where( $condition )->sum("a.amount");
		$this->assign("today_sum", $today_sum);

		unset($condition['a.addtime']);
    		$all_sum = $this->incomes_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.pay_uid")->join(C( 'DB_PREFIX' )."user_infos c ON c.user_id = b.id")->where( $condition )->sum("a.amount");
		$this->assign("all_sum", $all_sum);

    		$incomes = $this->incomes_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.pay_uid")->join(C( 'DB_PREFIX' )."user_infos c ON c.user_id = b.id")->where(array('a.user_id'=>$this->uid, 'a.types'=>array('in',array('PID','LAYER'))))->field("b.user_nicename,c.tel,c.weixin,a.amount,a.addtime,a.createtime,a.comptime,a.status,a.id")->select();
		$this->assign("list", $incomes);

        $this->display();
    }
    
	/**  钱包转换 */
    public function convert_money() {
        $this->assign('fee_s', $this->site_options['CHANGE_FEE_S']);
        $this->assign('fee_g', $this->site_options['CHANGE_FEE_G']);
        $this->display();
    }

	public function convert_money_post(){
        $_POST['fee_s'] = $this->site_options['CHANGE_FEE_S'];
        $_POST['fee_g'] = $this->site_options['CHANGE_FEE_G'];
		$return_value = $this->convertmoneylist_model->ConvertMoney($_POST);
		if ($return_value == -1000 || $return_value == -1001) {
			$this->error('请输入正确的转换金额');
		} else if ($return_value == 0) {
			$this->error('转换余额不足');
		} else if ($return_value == 1) {
			$this->success('转换完成');
		}
	}
	
    public function convert_list() {
        $count	= $this->convertmoneylist_model->where( array('user_id'=>$this->uid) )->count(); // 查询满足要求的总记录数
        $page	= $this->page($count, 20);
        $list	= $this->convertmoneylist_model->where( array('user_id'=>$this->uid) )->limit($page->firstRow . ',' . $page->listRows)->order('id DESC')->select();
        $this->assign('list', $list);
        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
        $this->display();
    }

	/**@see 资金转账 */
    public function change_money() {
		$user = $this->users_model->where(array('id'=> $this->uid))->field('shop_amount')->find();
        $this->assign('users', $user);
        $this->display();
    }
	/**@see 资金转账提交 */
	public function change_money_post(){
		$_POST['fee'] = 0;
		$result = $this->changemoneylist_model->ChangeMoney($_POST);
		if ($result == -1000) {
			$this->error('转账金额有误，请查看您输入的金额是否有误');
		} else if ($result == -1001) {
			$this->error('操作失败,自己不能转账给自己');
		}else if ($result == -1002) {
			$this->error('操作失败,转账金额只能为100元的整数倍');
		} else if ($result == -1005) {
			$this->error('转账类型错误');
		} else if ($result == -1) {
			$this->error('您输入的用户名不存在');
		} else if ($result == 0) {
			$this->error('操作失败,您电子积分余额不足');
		} else if ($result == 1) {
			$this->success('操作成功');
		}
	}

	public function change_list() {
        $count	= $this->changemoneylist_model->where("user_id='" . $this->uid . "' or to_user_id= " . $this->uid)->count();
        $page	= $this->page($count, 20);

        $list = $this->changemoneylist_model->where("user_id='" . $this->uid . "' or to_user_id= " . $this->uid)->limit($page->firstRow . ',' . $page->listRows)->order('id DESC')->select();
        $types_array = array('shop_amount'=>'电子积分','r_amount'=>'注册积分');
        foreach ($list as $p => $v) {
            if ($v['user_id'] == $this->uid) {
                $user_name = '本人';
            } else {
                $user_name = $this->users_model->where( array('id'=>$v['user_id']) )->getField('user_login');
            }
            if ($v['to_user_id'] == $this->uid) {
                $to_user_name = '本人';
            } else {
                $to_user_name = $this->users_model->where( array('id'=>$v['to_user_id']) )->getField('user_login');
            }
			$get_money = $v['amount'] - $v['amount']*$v['bili'];
			$free = $v['amount']*$v['bili']."(". $v['bili']*100 ."%)";
            $tmp [] = array( 
	            'get_money'    => $get_money,
	            'free'         => $free,
	            'user_name'    => $user_name, 
	            'to_user_name' => $to_user_name, 
	            'reason'       => $v ['reason'], 
	            'amount'       => $v ['amount'], 
	            'addtime'      => $v ['addtime'] ,
	            'types'        => $types_array[$v['type']]
            );
        }
        $this->assign('list', $tmp);
        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());

        $this->display();
    }

	/**
     * 奖金提现
     */
    public function mention() {
        $this->assign('time', date('Y-m-d'));
        // 最小额度  // 提现手续费
        $this->assign('min_money', $this->site_options['GETMONEYMIN']);
        $this->assign('charge', ($this->site_options['GETMONEYFEE'])*100);
        $this->assign('multiple', $this->site_options['MULTIPLE']); // 最小额度倍数
        $this->assign('GETMONENUMBER', $this->site_options['GETMONENUMBER']); // 当天可以体现次数

        $this->display();
    }

	public function mention_post(){
		//&& (date('Y-m-d H:i')< date('Y-m-d 08:00') || date('Y-m-d H:i')>date('Y-m-d 18:00'))
//		if ( !in_array(date('w'), array(1,4)) && date('Y-m-d H:i') > date('Y-m-d 08:00') && date('Y-m-d H:i') < date('Y-m-d 18:00')){ 
//				$this->error('提现时间为周一，周四8点到18点');
//		} 
		if (is_numeric(trim($_POST['amount']))) {
			$amount = intval(trim($_POST['amount']));
		} else {
			$this->error('请输入正确的金额');
		}
		$user = $this->users_model->where("id=".$this->uid)->find();
//		if($user['audit_time'] < '2015-10-16') $this->error('您暂不能提现,给您带来不便请谅解'); 

		$userinfos = $this->userinfos_model->where( array('user_id'=>$this->uid) )->field('account_type,account_no,account_name,account_info')->find();
		if ( !$userinfos['account_type'] || !$userinfos['account_no'] || !$userinfos['account_name'] ) $this->error('您还未填写收款银行信息,先填写', U('User/Profile/edit')); 

		$types = trim($_POST['types']);
		$min_money = $this->site_options['GETMONEYMIN'];
		$multiple  = $this->site_options['MULTIPLE'];
        $getnum  = $this->site_options['GETMONENUMBER'];
        $fee = $this->site_options['GETMONEYFEE'];
		
// 		if ($types != 'amount') $this->error('请输入选择正确的提现类型'); 
        if ($min_money > $amount) $this->error('最小提现额度为' . $min_money.'$'); 
//         if (1000 < $amount) $this->error('最大提现额度为1000'); 
		if ($amount > $user['amount'] ) $this->error('奖金积分余额不足'); 
// 		if ($amount % $multiple != 0) $this->error('提现数额只能为'.$multiple.'元的整数倍'); 

        $m_num = $this->mentions_model->where("addtime > '".date('Y-m-d 00:00:00')."' and addtime < '".date('Y-m-d 23:59:59')."' and user_id = '".$this->uid."'")->count();
        if($m_num>=$getnum) $this->error('当天可以提现次数已超过'.$getnum.'次'); 

        $s_num = $this->mentions_model->where("status=0 and user_id = '".$this->uid."'")->count();
        if($s_num>=1) $this->error('您的账户存在未确认的提现记录，不能再次操作'); 

		$data ['user_id']			= $this->uid;
		$data ['addtime']			= date('Y-m-d H:i:s');
        $data ['amount']            = $amount;
        $data ['money']             = $amount*$fee;
		$data ['types']				= $types;
        $data ['memo']              = $_POST ['memo'];
		$data ['bank_type']			= $userinfos['account_type'];
		$data ['bank_number']		= $userinfos['account_no'];
		$data ['bank_user_name']	= $userinfos['account_name'];
		$data ['bank_adree']		= $userinfos['account_info'];
		
	   if($this->mentions_model->add($data)){
			$this->users_model->where( array('id'=>$this->uid) )->setField('amount', $user['amount'] - $amount);
			$this->success('提现成功');
	   }else{
		   $this->error('提现失败');
	   }
	}

    // 提现记录
    public function mention_list() {
        $charge		= $this->site_options['GETMONEYFEE'];
      
        $count = $this->mentions_model->where( array('user_id'=>$this->uid) )->count(); 
		$page	= $this->page($count, 20);
        $list = $this->mentions_model->where( array('user_id'=>$this->uid) )->limit($page->firstRow . ',' . $page->listRows)->order('id DESC')->select();
		
        foreach ($list as $p => $v) {
			$act_amount =  $v['amount'] - ($v['amount'] * $charge);
			$rg_charge =   ($v['amount'] * $charge).'('.($charge*100).'%)';
            $tmp[] = array(
                'id'				=> $v['id'],
                'amount'			=> $v['amount'],
                'act_amount'		=> $act_amount,
                'charge'			=> $rg_charge,
                'status'			=> $v['status'],
                'addtime'			=> $v['addtime'],
                'bank_type'			=> $v['bank_type'],
                'bank_number'		=> $v['bank_number'],
                'bank_user_name'	=> $v['bank_user_name'],
                'addtime'			=> $v['addtime'],
                'bank_adree'		=> $v['bank_adree'],
                'memo'				=> $v['memo']
            );
        }
        $this->assign('list', $tmp);
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());

        $this->display();
    }

    public function mention_cansel() {
        $id = I('get.id');
        $objMention = $this->mentions_model->find( $id );
        if ($objMention['status'] == 0 && !empty($objMention)) {
            $money = $objMention['amount'];
            $this->users_model->where( array('id'=>$objMention['user_id']) )->setInc('amount',$money);

            $data ['id']        = $id;
            $data ['status']    = 3;
            $this->mentions_model->save($data);

            $this->success("撤销成功", U("User/Finance/mention_list"));
        } else {
            $this->error('提现已成功，不可撤销');
        }
    }
	
	function rg_isexit() {
        $username	= I('post.username');
        $rg_user	= $this->users_model->where("user_login='$username' and user_type = 2")->find();
		if( empty($rg_user) ) $this->error("用户名不存在");

		$this->success("用户名可使用！");
    }

}
