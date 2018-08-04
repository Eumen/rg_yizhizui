<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminWxController extends AdminbaseController {
	protected $weixin_corn_model;
	protected $weixin_menu_model;
	protected $posts_model;
	protected $term_relationships_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_corn_model		= D("Weixin/weixin_corn");
		$this->weixin_menu_model		= D("Weixin/weixin_menu");
		$this->posts_model				= D("Portal/Posts");
		$this->term_relationships_model = D("Portal/TermRelationships");
	}
	
	function meun(){
		$weixin_menu = $this->weixin_menu_model->order("`order` desc")->select();
		$menu = $pmenu = array();
		foreach ($weixin_menu as $v){
			if($v['pid'] == 0){
				$pmenu[] = $v;
			}else{
				$menu[$v['pid']][] = $v;
			}
		}
		$this->assign ( 'menu', $menu );
		$this->assign ( 'pmenu', $pmenu );
		$this->display();
	}
	
	function addmeun(){
		$ret = $this->weixin_menu_model->where( array('pid'=>0) )->select();
		$this->assign ( 'pmenu', $ret );
		$this->display();
	}
	
	function addmeun_post(){
		if (IS_POST) {
			$_POST['smeta']['thumb']	= sp_asset_relative_url($_POST['thumb']);
			$article['post_title']		= $_POST['title'];
			$article['post_excerpt']	= $_POST['description'];
			$article['post_modified']	= date("Y-m-d H:i:s",time());
			$article['post_date']		= date("Y-m-d H:i:s",time());
			$article['post_author']		= get_current_admin_id();
			$article['smeta']			= json_encode($_POST['smeta']);
			$article['post_content']	= htmlspecialchars_decode($article['post_excerpt']);

			$type	= $_POST['type'];
			$value	= $_POST['value'];

			if(empty($_POST['name'])) $this->error("菜单名称不能为空");
			if(!in_array($_POST['type'], array(1,2,3))) $this->error("菜单类型错误");

			if($type == 3){
				if(empty($_POST['title'])) $this->error("标题不能为空");
				if(empty($_POST['description'])) $this->error("描述内容不能为空");
				
				$aid = $this->posts_model->add($article);
				if($aid > 0){
					$this->term_relationships_model->add(array("term_id"=>32, "object_id"=>$aid));
					$value = "article_".$aid;
				}
			}

			$r = $this->weixin_menu_model->add( array('pid'=>intval($_POST['pid']), 'name'=>$_POST['name'], 'type'=>$type, 'value'=>$value, 'order'=>intval($_POST['order'])) );
			if ($r !== false) {
				$this->update_menu();
				$this->success("添加成功");
			} else {
				$this->error("添加失败，请重试");
			}
		}
	}
	
	function editmeun(){
		$id = intval(I("get.id"));

		$ret = $this->weixin_menu_model->where( array('pid'=>0) )->select();
		$menu = $this->weixin_menu_model->where( array('id'=>$id) )->find();
		if($menu['type'] == 3){
			$articleId = str_replace('article_','',$menu['value']);
			$artInfo = $this->posts_model->where( array('id'=>$articleId) )->find();
			$this->assign('article', $artInfo );
			$this->assign("smeta",json_decode($artInfo['smeta'],true));
		}
		$this->assign ( 'menu', $menu );
		$this->assign ( 'pmenu', $ret );
		
		$this->display();
	}
	
	function editmeun_post(){
		if (IS_POST) {
			$id = intval(I("get.id"));

			$_POST['smeta']['thumb']	= sp_asset_relative_url($_POST['thumb']);

			$article['post_title']		= $_POST['title'];
			$article['post_excerpt']	= $_POST['description'];
			$article['post_modified']	= date("Y-m-d H:i:s",time());
			$article['post_date']		= date("Y-m-d H:i:s",time());
			$article['post_author']		= get_current_admin_id();
			$article['smeta']			= json_encode($_POST['smeta']);
			$article['post_content']	= htmlspecialchars_decode($article['post_excerpt']);

			$type	= $_POST['type'];
			$value	= $_POST['value'];

			if(empty($_POST['name'])) $this->error("菜单名称不能为空");
			if(!in_array($_POST['type'], array(1,2,3))) $this->error("菜单类型错误");

			if($type == 3){
				if(empty($_POST['title'])) $this->error("标题不能为空");
				if(empty($_POST['description'])) $this->error("描述内容不能为空");
				if(!$_POST['article_id']){
					$aid = $this->posts_model->add($article);
					if($aid > 0){
						$this->term_relationships_model->add(array("term_id"=>32, "object_id"=>$aid));
						$value = "article_".$aid;
					}
				}else{
					$article['id']	= $_POST['article_id'];
					$this->posts_model->save($article);
					$value = "article_".$article['id'];
				}
			}

			$r = $this->weixin_menu_model->save(array('id'=>$id, 'pid'=>intval($_POST['pid']), 'name'=>$_POST['name'], 'type'=>$type, 'value'=>$value, 'order'=>intval($_POST['order'])));
			if ($r !== false) {
				$this->update_menu();
				$this->success("提交成功");
			} else {
				$this->error("提交失败，请重试");
			}
		}
	}
	
	function delmeun(){
		$id = intval(I("get.id"));
		if ($this->weixin_menu_model->where("id=$id")->delete()!==false) {
			$this->update_menu();
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	function update_menu($wxico = 1){
		$ret = $this->weixin_menu_model->where( array('pid'=>0, 'wxico'=>$wxico) )->order("`order` desc")->select();
		foreach($ret as $k=>$v){
			$button[$k]['name'] = $v['name'];
			$ret2 = $this->weixin_menu_model->where( array('pid'=>$v['id'], 'wxico'=>$wxico) )->order("`order` desc")->select();
			if($ret2){
				foreach($ret2 as $kk=>$vv){
					$button[$k]['sub_button'][$kk]['name'] = $vv['name'];
					if(in_array($vv['type'], array(1,3))){
						$button[$k]['sub_button'][$kk]['key']	= $vv['value'];
						$button[$k]['sub_button'][$kk]['type']	= "click";
					}else{
						$button[$k]['sub_button'][$kk]['url']	= $vv['value'];
						$button[$k]['sub_button'][$kk]['type']	= "view";
					}
				}
			}else{
				if(in_array($v['type'], array(1,3))){
					$button[$k]['key']	= $v['value'];
					$button[$k]['type'] = "click";
				}else{
					$button[$k]['url']	= $v['value'];
					$button[$k]['type'] = "view";
				}
			}
		}

		$res = $this->weObj->createMenu(array('button'=>$button));
		if($res === false){
			$this->error("更新菜单出错：".$this->weObj->errMsg);
		}else{
			return true;
		}
	}
}