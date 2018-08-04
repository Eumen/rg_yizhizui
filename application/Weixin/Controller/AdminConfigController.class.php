<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminConfigController extends AdminbaseController {
	protected $weixin_config_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_config_model		= D("Weixin/weixin_config");
	}

	function index(){		
		$this->_lists();
		$this->display();
	}

	private  function _lists(){
		$city_id=0; 
		if(!empty($_REQUEST["city"])){
			$city_id = intval($_REQUEST["city"]);
			$this->assign("city",$city_id);
			$_GET['city'] = $city_id;
		}
	
		$where_ands = empty($city_id) ? array() : array("cityid = $city_id");
		
		$fields=array(
				'keyword'  => array("field"=>"name","operator"=>"like"),
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
					$operator = $val['operator'];
					$field    = $val['field'];
					$get = $_GET[$param];
					if($operator=="like"){ $get="%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		
		$where 	= join(" and ", $where_ands);
		$count 	= $this->weixin_config_model->where($where) ->count();
		$page 	= $this->page($count, 15);
		$posts	= $this->weixin_config_model->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id DESC")->select();
				
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
		$this->assign("posts",$posts);
	}

	function add(){

		$this->display();
	}

	function add_post(){
		if (IS_POST) {
			if(empty($_POST['cityid'])) $this->error("请至少选择一个市级！"); 

			$_POST['smeta']['thumb']	= sp_asset_relative_url($_POST['smeta']['thumb']);
			$_POST['smeta']				= json_encode($_POST['smeta']);

			$r = $this->weixin_config_model->add($_POST);
			if ($r !== false) {
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}
  
	public function edit(){
		$id =  intval(I("get.id"));

		$post=$this->weixin_config_model->where("id=$id")->find();

		$this->assign('post',	$post);
		$this->assign("smeta",	json_decode($post['smeta'], true));
		$this->display();
	}

	public function edit_post(){
		if (IS_POST) {
			if(empty($_POST['cityid'])){ $this->error("请至少选择一个市级！"); }
			
			$_POST['smeta']['thumb']	= sp_asset_relative_url($_POST['smeta']['thumb']); 
			$_POST['smeta']				= json_encode($_POST['smeta']);

			$result = $this->weixin_config_model->save($_POST);
			if ($result!==false) {
				
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}
}