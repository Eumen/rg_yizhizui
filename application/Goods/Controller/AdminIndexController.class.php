<?php
namespace Goods\Controller;
use Common\Controller\AdminbaseController;
class AdminIndexController extends AdminbaseController {
	protected $goods_model;
	function _initialize() {
		parent::_initialize();
		$this->goods_model = D("Common/Goods");
	}
	
	function index(){
		$this->_lists();
		$this->display();
	}
	
	private  function _lists($status=1){
		$count=$this->goods_model->count();
		$page = $this->page($count, 20);
		$posts=$this->goods_model->limit($page->firstRow . ',' . $page->listRows)->select();
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		$this->assign("posts",$posts);
	}
	
	private function get_rand(){
		$this->assign("clists",M('rand_price')->field('rank_mark,name')->select());
	}
	function add(){
		if (IS_POST) {
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
			$article = I("post.post");
			$article['add_times'] = time();
			$article['smeta']=json_encode($_POST['smeta']);
			$article['content']=htmlspecialchars_decode($article['content']);
			if ($this->goods_model->add($article)) {
				$this->success("添加成功！");
			} else {
				$this->error("添加失败！");
			}
		}else{
			$this->get_rand();
			$this->display();
		}
	}
	
	public function edit(){
		if (IS_POST) {
			$_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);

			$article = I("post.post");
			$article['smeta']=json_encode($_POST['smeta']);
			$article['content']=htmlspecialchars_decode($article['content']);

			$datas	= $this->goods_model->where(array('id'=>$post_id))->find();

			$result=$this->goods_model->save($article);
			if ($result !== false) {

				$smeta			= json_decode($datas['smeta'], true);
				if($_POST['smeta']['thumb'] && sp_get_asset_upload_path($smeta['thumb']) != sp_get_asset_upload_path($_POST['smeta']['thumb']) && file_exists('.'.sp_get_asset_upload_path( $smeta['thumb'] ))){
					@unlink( '.'.sp_get_asset_upload_path( $smeta['thumb'] ));
				}

				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}else{
			$id = intval(I('id'));
			if(!empty($id)){
				$good = $this->goods_model->where(array('id'=>$id))->find();
				$this->assign("good", 	$good);
				$this->assign("smeta",	json_decode($good['smeta'],true));
			}
			$this->get_rand();
			$this->display();
		}
	}
	
	function delete(){
		if(isset($_GET['id'])){
			$tid = intval(I("get.id"));
			if ($this->goods_model->where("id=$tid")->delete()) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}
	
}