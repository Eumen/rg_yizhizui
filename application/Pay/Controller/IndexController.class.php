<?php
namespace Pay\Controller;
use Common\Controller\HomeBaseController;
class IndexController extends HomeBaseController {
	function _initialize() {
		parent::_initialize();
		
		$this->user_id = get_current_userid();
	}
	
	public function pay_index() {
		$this->display(":pay_index");
	}
	
	public function pay_success() {
		$this->display(":pay_success");
	}
}