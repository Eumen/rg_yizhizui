<?php
/**
 * 搜索结果页面
 */
namespace Portal\Controller;
use Common\Controller\HomeBaseController;
class SearchController extends HomeBaseController {
	
	function _initialize(){
		parent::_initialize();

		$this->assign("rg_on", 3);
	}

    //文章内页
    public function index() {
    	$_GET = array_merge($_GET, $_POST);
		$k = I("get.keyword");
		
		if (empty($k)) {
			$this -> error("关键词不能为空！请重新输入！");
		}
		$this -> assign("keyword", $k);
		$this -> display(":search");
    }
    
    
}
