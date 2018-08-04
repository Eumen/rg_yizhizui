<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminQrcodeController extends AdminbaseController {
	protected $weixin_qrcode_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_qrcode_model	= D("Weixin/weixin_qcode");
	}

	function index(){
		$type		= I('post.type');
		$type		= intval($type)>0 ? intval($type) : 4;
		$ex_where	= $type ? "a.type = '$type'" : "";

		$keyword	= I('post.keyword');
		if($type == 1){
			if($keyword) $ex_where .= " and b.post_title LIKE '%".$keyword."%'";

			$count	= $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "posts b ON a.content=b.id" )->where($ex_where)->count();
			$page	= $this->page($count, 20);
			$qrcode = $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "posts b ON a.content=b.id" )->where($ex_where)->field("a.*,b.post_title as title")->limit($page->firstRow . ',' . $page->listRows)->order(array('a.id'=>'desc'))->select();
		}elseif($type == 2){
			if($keyword) $ex_where .= " and b.name LIKE '%".$keyword."%'";

			$count	= $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "channel b ON a.content=b.id" )->where($ex_where)->count();
			$page	= $this->page($count, 20);
			$qrcode = $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "channel b ON a.content=b.id" )->where($ex_where)->field("a.*,b.name as title")->limit($page->firstRow . ',' . $page->listRows)->order(array('a.id'=>'desc'))->select();
		}elseif($type == 3){
			if($keyword) $ex_where .= " and b.post_title LIKE '%".$keyword."%'";

			$count	= $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "demand b ON a.content=b.id" )->where($ex_where)->count();
			$page	= $this->page($count, 20);
			$qrcode = $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "demand b ON a.content=b.id" )->where($ex_where)->field("a.*,b.post_title as title")->limit($page->firstRow . ',' . $page->listRows)->order(array('a.id'=>'desc'))->select();
		}elseif($type == 4){
			if($keyword) $ex_where .= " and b.post_title LIKE '%".$keyword."%'";

			$count	= $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "lives b ON a.content=b.id" )->where($ex_where)->count();
			$page	= $this->page($count, 20);
			$qrcode = $this->weixin_qrcode_model->alias("a")->join( C('DB_PREFIX') . "lives b ON a.content=b.id" )->where($ex_where)->field("a.*,b.post_title as title")->limit($page->firstRow . ',' . $page->listRows)->order(array('a.id'=>'desc'))->select();
		}
		
		$this->assign("page",		$page->show('Admin'));
		$this->assign("qrcode",		$qrcode);
		$this->assign("type",		$type);
		$this->assign("keyword",	$keyword);

		$this->display();
	}

	function add(){
		$this->assign("station_id",	$this->admin_user['station_id']);
		
		$this->display();
	}

	function add_post(){
		if(IS_POST){
			$type	= intval($_POST['type']);
			if($type == 5){
				$content = trim($_POST['content']);
			}else{
				$content = intval($_POST['content']);
			}
			if($content){
				$scene_id = $this->weixin_qrcode_model->max("id");
				$scene_id = $scene_id ? $scene_id+1 : 1;
				$qcode = $this->weObj->getQRCode($scene_id, 1);
				$result = $this->weixin_qrcode_model->add(array('id'=>$scene_id, 'type'=>$type, 'content'=>$content, 'qcode'=>$qcode['ticket']));
			}
			if ($result!=false) {
				$this->success("提交成功", U('AdminQrcode/index'));
			} else {
				$this->error("提交失败，请重试");
			}
		}
	}

	function edit(){
		$id = intval(I('get.id'));
		
		$ret = $this->weixin_qrcode_model->find($id);
		$this->assign('qcode', $ret);
		$this->display();
	}

	function edit_post(){
		$id = intval(I('get.id'));

		if(IS_POST){
			$type	= intval($_POST['type']);
			if($type == 5){
				$content = trim($_POST['content']);
			}else{
				$content = intval($_POST['content']);
			}
			if($content){
				$qcode = $this->weObj->getQRCode($id, 1);
				$result = $this->weixin_qrcode_model->where(array('id'=>$id))->save(array('type'=>$type, 'content'=>$content, 'qcode'=>$qcode['ticket']));
			}
			if ($result!=false) {
				$this->success("提交成功", U('AdminQrcode/index'));
			} else {
				$this->error("提交失败，请重试");
			}
		}
	}

	function delete(){
		$id = intval(I('get.id'));

		if ($this->weixin_qrcode_model->where(array('id'=>$id))->delete()) {
			$this->success("删除成功");
		} else {
			$this->error("删除失败，请重试");
		}
	}
}