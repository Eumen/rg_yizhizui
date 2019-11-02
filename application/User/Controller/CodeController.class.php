<?php
namespace User\Controller;
use Common\Controller\MemberbaseController;
class CodeController extends MemberbaseController{
	
	protected $uid;
	
	function _initialize() {
		parent::_initialize();
		$this->uid = sp_get_current_userid();
	}
	
	function index(){
	    // 激活码相关信息
	    $count	= M('code')->where(array('aid'=>$this->uid))->order('status asc')->count();
        $page	= $this->page($count, 20);
	    $join = "left join ". C ( 'DB_PREFIX' )."users u ON m.aid=u.id"; // 连表查询
	    $join2 = "left join ". C ( 'DB_PREFIX' )."users u2 ON m.uid=u2.id"; // 连表查询
	    $objCode   = M('code')->alias("m")->where(array('m.aid'=>$this->uid))->join($join)->join($join2)->field('m.*,u.user_login as aname,u2.user_login as uname')->limit($page->firstRow . ',' . $page->listRows)->order('status asc')->select();
	    $this->assign("posts",$objCode);
	    $this->assign("Page", $page->show());
	    $this->assign("current_page",$page->GetCurrentPage());
		$this->display();
	}
}