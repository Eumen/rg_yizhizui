<?php

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class BusinessController extends AdminbaseController {
	protected $users_model, $incomes_model;
	protected $rid_user_arr;

	function _initialize() {
		parent::_initialize();
		$this->users_model=D("Common/Users");
		$this->incomes_model = D("Common/Incomes");
	}

    public function index() {
		$where_ands = array("u.user_type = 2");
		
		$fields=array(
				'start_time'=> array("field"=>"i.addtime","operator"=>">="),
				'end_time'  => array("field"=>"i.addtime","operator"=>"<="),
				'reason'	=> array("field"=>"i.reason","operator"=>"="),
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

		$count	= $this->incomes_model->alias("i")->join(C ( 'DB_PREFIX' )."users u ON i.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $posts = $this->incomes_model->alias("i")->join(C ( 'DB_PREFIX' )."users u ON i.user_id=u.id")->where( $where )->field('i.*, u.user_login')->limit($page->firstRow . ',' . $page->listRows)->order('i.id desc')->select();

		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('posts', $posts);

        $this->display();
    }

    public function results(){
		$where_ands = array("u.user_type = 2");

		if ( I('post.rid_name') != "" ) {
			$_GET['rid_name'] = I('post.rid_name');
            $rid = $this->users_model->where("user_login='" . I('post.rid_name') . "'")->getField('id');
            array_push($where_ands, "u.rid = '$rid'");
        }
		
		$fields=array(
				'start_time'	=> array("field"=>"u.audit_time","operator"=>">="),
				'end_time'		=> array("field"=>"u.audit_time","operator"=>"<="),
				'keyword'		=> array("field"=>"u.user_login","operator"=>"like"),
				'true_name'		=> array("field"=>"ui.true_name","operator"=>"like"),
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

		$count	= $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->count();
		$page	= $this->page($count, 20);

        $lists = $this->users_model->alias("u")->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id=u.id")->where( $where )->field('u.id,u.user_login,u.layer,ui.true_name')->limit($page->firstRow . ',' . $page->listRows)->order('u.id desc')->select();

		foreach ($lists as $_k => $_v) {
			$rid_user = $this->get_rid_user( $_v['id'] );
			$lists[$_k]["rid_num"] = count($rid_user);

			$lists[$_k]["layer"] = $_v['layer'];
			
			$condition['user_id'] = array('eq',$_v['id']);
			
			//直推奖
			$condition['types'] = array('eq','RID');
			$lists[$_k]["sum_rid"] = $this->incomes_model->where($condition)->sum("amount");
			
			//注册4层奖
			$condition['types'] = array('eq','PID');
			$lists[$_k]["sum_pid"] = $this->incomes_model->where($condition)->sum("amount");
			
			//报单奖
			$condition['types'] = array('eq','LAYER');
			$lists[$_k]["sum_layer"] = $this->incomes_model->where($condition)->sum("amount");

			//总数
			$lists[$_k]["sum"] = $lists[$_k]["sum_rid"] + $lists[$_k]["sum_pid"] + $lists[$_k]["sum_layer"];
        }

		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('lists', $lists);

		$this->display();
	}

	function get_rid_user($uid){
		$rid_user = $this->users_model->where( array('rid'=>$uid) )->getField('id',true);
		foreach( $rid_user as $_k=>$_v ){
			$this->rid_user_arr[] = $_v;
			$this->get_rid_user( $_v );
		}
		
		return $this->rid_user_arr;
	}

}

