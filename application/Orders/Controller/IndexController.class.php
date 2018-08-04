<?php
namespace Orders\Controller;
use Common\Controller\MemberbaseController;

class IndexController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
		$this->order_model = D("Common/Orders");
		$this->assign("rg_on", 5);
	}
	
	public function index() {
		$count = $this->order_model->where(array("user_id"=>sp_get_current_userid()))->count();
		$page  = $this->page($count, 10);
		
		$orders = $this->order_model->where(array("user_id"=>sp_get_current_userid()))
					->limit($page->firstRow . ',' . $page->listRows)->select();
					
		$this->assign("page", $page->show('Admin'));			
		$this->assign("orders",$orders);
		$this->display(":index"); 
	}
}
