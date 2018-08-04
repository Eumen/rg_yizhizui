<?php

namespace Weixin\Controller;
use Common\Controller\HomeBaseController; 

class ReurlController extends HomeBaseController {

	function _initialize(){
		parent::_initialize();
	}

	public function index(){
		$station_id = intval(I('get.station_id'));
		if($station_id==6){
			header("Location: https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=MjM5NjQyNTEzNw==&scene=123&from=singlemessage#wechat_redirect");exit;
		}else{
			header("Location: ".$this->site_options['site_host']);exit;
		}
	}
}