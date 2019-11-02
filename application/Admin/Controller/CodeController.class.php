<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CodeController extends AdminbaseController {
	protected $users_model, $code_model,$userinfos_model;

	function _initialize() {
		parent::_initialize();
		$this->userinfos_model = D("Common/UserInfos");
		$this->users_model = D("Common/Users");
		$this->code_model = D("Common/Code");
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
    

    public function createCode(){
        // 激活码相关信息
        $count	= $this->code_model->count();
        $page	= $this->page($count, 20);
        $join = "left join ". C ( 'DB_PREFIX' )."users u ON m.aid=u.id"; // 连表查询
        $join2 = "left join ". C ( 'DB_PREFIX' )."users u2 ON m.uid=u2.id"; // 连表查询
        $objCode   = $this->code_model->alias("m")->join($join)->join($join2)->field('m.*,u.user_login as aname,u2.user_login as uname')->limit($page->firstRow . ',' . $page->listRows)->order('m.id desc')->select();
        $this->assign("posts",$objCode);
        // 激活码总数
        $total_num = $this->code_model->count();
        $this->assign('total_num', $total_num ? $total_num : 0);
        // 已分配
        $assigned_num = $this->code_model->where('assign_status = 1')->count();
        $this->assign('assigned_num', $assigned_num ? $assigned_num : 0);
        // 未分配
        $unassigned_num = $this->code_model->where('assign_status = 0')->count();
        $this->assign('unassigned_num', $unassigned_num ? $unassigned_num : 0);
        // 已使用
        $used_num = $this->code_model->where('status = 1')->count();
        $this->assign('used_num', $used_num ? $used_num : 0);
        // 未使用
        $unused_num = $this->code_model->where('status = 0')->count();
        $this->assign('unused_num', $unused_num ? $unused_num : 0);
        
        $this->assign("Page", $page->show('Admin'));
        $this->assign("current_page",$page->GetCurrentPage());
        $this->display();
    }
    
    public function create(){
        $dataArray = array();
        
        for($i=0;$i<100;$i++){
            $uuid = $this->uuid();
            $data=array(
                'code'		=> $uuid,
                'aid'		=> '',
                'assign_status'		=> 0,
                'uid'		=> '',
                'status'		=> 0,
                'create_time'		=> date("Y-m-d H:i:s"),
            );
            array_push($dataArray, $data);
        }
        $user_id = $this->code_model->addAll($dataArray);
        
		if($user_id){
			$this->success("生成成功!", U("Admin/Code/createCode"));
		}else{
			$this->error("生成失败！", U("User/Code/createCode"));
		}
    }
    
    /**
     * Generates an UUID
     *
     * @return     string  the formatted uuid
     */
    function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,32);
        return $uuid;
    }
	public function transferCode(){
        $this->display();
	}
	
	/**
	 * @see 给用户转激活码
	 */
	public function transfer(){
		$rules = array(
				array('login_name', 'require', '会员账号不能为空！', 1 ),
				array('amount', 	'require', '数量不能为空！', 1 ),
		);
		if($this->code_model->validate($rules)->create()===false){
			$this->error($this->code_model->getError());
		}
		// 验证用户名
		$user_obj = $this->users_model->where(array('user_login'=>trim(I('login_name')),'user_type'=>2))->field('id')->find();
		$uid = $user_obj['id'];
		if( empty($uid) ) $this->error('您输入的用户名不存在');
		// 检测未分配的激活码数量
		$unassigned_num = $this->code_model->where('assign_status = 0 and status = 0')->count();
        if (I('amount') > $unassigned_num) {
            $this->error("剩余未分配的激活码不足，请生成激活码后再重试！");
        } else {
            //begin
            if($this->code_model->create()){
                $this->code_model->assign_time  = date('Y-m-d H:i:s');
                $this->code_model->aid  = $uid;
                $this->code_model->assign_status   = 1;
            }
            // 检索出要转移数量的激活码
            $objCode = $this->code_model->alias('c')->where('assign_status = 0 and status = 0')->limit(I('amount'))->order('c.id asc')->select();
            // 循环赋值，锁定激活码
            foreach ($objCode as $p => $v) {
			    $aid =  $uid;
			    $assign_time = date('Y-m-d H:i:s');
			    $objCode[$p] = array(
			        'id'			=> $v['id'],
			        'code'		=> $v['code'],
			        'aid'		=> $uid,
			        'assign_status'	=> 1,
			        'uid'		=> $v['uid'],
			        'status'		=> $v['status'],
			        'reason'		=> $v['reason'],
			        'create_time'	=> $v['create_time'],
			        'assign_time'	=> $assign_time
			    );
			}
            if ($this->dbSaveAll($objCode,"code","id")) {
                $this->success("转激活码成功!", U("Admin/Code/transferCode"));
            } else {
                $this->error("转激活码失败！", U("Admin/Code/transferCode"));
            }
        }
	}
	
	/** @param  [string] $database_table_name [数据库表名]
	  * @param  [string] $primary_key         [主键名]
	  * @return [int]                      [成功修改的条数]
	  */
	function dbSaveAll($datas, $database_table_name, $primary_key){
	
	    $sql   = ''; //Sql
	    $lists = []; //记录集$lists
	    $pk    = $primary_key;//获取主键
	    foreach ($datas as $data) {
	        foreach ($data as $key=>$value) {
	            if($pk===$key){
	                $ids[]=$value;
	            }else{
	                $lists[$key].= sprintf("WHEN %u THEN '%s' ",$data[$pk],$value);
	            }
	        }
	    }
	    foreach ($lists as $key => $value) {
	        $sql.= sprintf("`%s` = CASE `%s` %s END,",$key,$pk,$value);
	    }
	    $sql = sprintf('UPDATE __%s__ SET %s WHERE %s IN ( %s )',strtoupper($database_table_name),rtrim($sql,','),$pk,implode(',',$ids));
	
	    return M()->execute($sql);
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
    
}