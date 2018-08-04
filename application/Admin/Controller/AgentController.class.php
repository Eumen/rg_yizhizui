<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AgentController extends AdminbaseController {
	protected $users_model, $userinfos_model, $centers_model,$mid;

	function _initialize() {
		parent::_initialize();
		$this->users_model = D("Common/Users");
		$this->userinfos_model = D("Common/UserInfos");
		$this->centers_model = D("Common/Centers");
		$this->mid = get_current_admin_id();
	}

    public function index() {
		$where_ands = array("u.user_type = 2");
		$fields=array(
				'start_time'=> array("field"=>"c.addtime","operator"=>">="),
				'end_time'  => array("field"=>"c.addtime","operator"=>"<="),
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
        $count	= $this->centers_model->alias("c")->join(C ( 'DB_PREFIX' )."users u ON c.uid=u.id")->where( $where )->count();
        $page	= $this->page($count, 20);
        $posts = $this->centers_model->alias("c")->join(C ( 'DB_PREFIX' )."users u ON c.uid=u.id")->join(C ( 'DB_PREFIX' )."user_infos ui ON c.uid=ui.user_id")->where( $where )->field('u.*,c.add_times,c.status,ui.true_name')->limit($page->firstRow . ',' . $page->listRows)->order('c.id desc')->select();
		
        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);

        $this->display();
    }

	public function add() {
		if(IS_POST){
			$objUser = $this->users_model->where( array('user_login'=>$_POST['user_login']) )->find();
			if ( empty($objUser) ) $this->error('您输入的用户名不存在');
			if ( $objUser['user_type'] == "1" ) $this->error('管理员不能申请报单中心');
			
			$data['uid']		= $objUser['id'];
			$data['add_times']	= date('Y-m-d H:i:s');

			$objAgent = $this->centers_model->where("uid = '" . $objUser['id'] . "'")->find();

			if (!empty($objAgent)) {
				if ($objAgent['status'] == 1) {
					$this->error('该用户已是报单中心');
				} else {
					$this->error('该用户已提交申请报单中心，无需重复申请。');
				}
			} else {
				$this->centers_model->add( $data );
				$this->success('操作成功');
			}
		}else{
			$this->display();
		}
    }
	
    public function agent_audit(){
		 $get_uid = I('get.uid');
		if($this->centers_model->where( array('uid'=>$get_uid) )->setField('status',1)){
			if($this->users_model->where( array('id'=>$get_uid) )->setField('is_agent',1)){
				$this->success('设置成功');
			} else {
				$this->error('审核报单中心失败');
       	 	}
		} else {
			$this->error('处理审核报单记录失败');
        }
	}

	public function agent_list_del(){
		$condition['uid'] = array('eq', I('get.uid'));
        if ($this->centers_model->where($condition)->getField('status') == 0) {
            $this->centers_model->where($condition)->delete();
            $this->success('删除成功');
        } else {
			$this->error('已审核报单中心无法删除');
        }
	}

}

