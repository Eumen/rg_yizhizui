<?php
namespace User\Controller;
use Common\Controller\MemberbaseController;
class GuestbookController extends MemberbaseController{
	
	protected $guestbook_model;
	protected $uid;
	
	function _initialize() {
		parent::_initialize();
		$this->guestbook_model=D("Common/Guestbook");
		
		$this->uid = sp_get_current_userid();

		$this->assign("rg_on", 3);
	}
	
	function index(){

		$this->display();
	}
	
	function addmsg(){
		if (IS_POST) {
			$_POST['user_id'] = $this->uid;
			if ($this->guestbook_model->create()) {
				$result=$this->guestbook_model->add();
				if ($result!==false) {
					$this->success("留言成功！", U('User/Guestbook/messages'));
				} else {
					$this->error("留言失败！");
				}
			} else {
				$this->error($this->guestbook_model->getError());
			}
		}
	}

	public function messages() {
		$count	= $this->guestbook_model->where( array('user_id'=>$this->uid) )->count();
		$page	= $this->page($count, 20);

        $content = $this->guestbook_model->where( array('user_id'=>$this->uid) )->limit($page->firstRow . ',' . $page->listRows)->order('id desc')->select();

        $this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
        $this->assign('content', $content);

        $this->display();
    }

	public function show(){
		$id = I('get.id');
		$data = $this->guestbook_model->find($id);

		$this->assign($data);

        $this->display();
	}
}