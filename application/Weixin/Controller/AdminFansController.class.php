<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminFansController extends AdminbaseController {
	protected $weixin_msg_model, $weixin_user_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_msg_model			= D("Weixin/weixin_msg");
		$this->weixin_user_model		= D("Weixin/weixin_user");
	}
	
	function index(){
		$term_id=0;
		if(!empty($_REQUEST["term"])){
			$term_id = intval($_REQUEST["term"]);
			$_GET['term'] = $term_id;
		}
		if($this->admin_user['station_id']>1){
			$this->assign("is_station", 1);
			$where_ands = array("b.station_id = '".$this->admin_user['station_id']."'");
		}else{
			$where_ands = empty($term_id) ? array() : array("b.station_id = $term_id");
		}

		$fields=array(
				'start_time'=> array("field"=>"a.createymd","operator"=>">"),
				'end_time'  => array("field"=>"a.createymd","operator"=>"<"),
				'keyword'	=> array("field"=>"a.nickname","operator"=>"like"),
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
		$where	= join(" and ", $where_ands);

		$count = $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where($where)->count();
		$page  = $this->page($count, 20);
		$datas = $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where($where)->field("a.*,b.user_login,b.station_id")->order("uid desc")->limit($page->firstRow . ',' . $page->listRows)->select();
		foreach ($datas as $_v){
			$_v['station_name'] = M('Weixin_config')->where(array('stationid'=>$_v["station_id"]))->getField("name");

			$wxusers[] = $_v;
		}

		$this->assign("Page",			$page->show('Admin'));
		$this->assign("current_page",	$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",		$_GET);
		$this->assign("wxusers",		$wxusers);

		$this->display();
	}
	
	function fansmsg(){
		$fake_id = I("get.fake_id");
		if(empty($fake_id)) $this->error("参数错误，请重试");
		
		$weixin_msg = $this->weixin_msg_model->where( array('fake_id'=>$fake_id, 'type'=>'text') )->select();
		foreach($weixin_msg as $_v){
			$_v['nickname'] = $this->weixin_user_model->where( array('fake_id'=>$_v['fake_id']) )->getField("nickname");
			$msg_list[] = $_v;
		}

		$this->assign('msg_list', $msg_list);
		$this->assign('fake_id', $fake_id);

		$this->display();
	}
	
	function fansmsg_post(){
		if (IS_POST) {
			$fake_id 	= I("get.fake_id");
			$station_id	= I("get.sid");
			$content 	= $_POST['content'];

			if(empty($content)) $this->error("回复内容不能为空");

			$ret = pushToUserMsg($fake_id, 'text', array('text'=>$content), 0, 0, $station_id);
			if ($ret) {
				@file_get_contents(get_url(0).U('Weixin/Doauto/index', array('station_id'=>$station_id)));

				$this->success("操作成功");
			} else {
				$this->error("参数错误，请重试");
			}
		}
	}
}