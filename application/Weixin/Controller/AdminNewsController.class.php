<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminNewsController extends AdminbaseController {
	protected $weixin_corn_model;
	protected $posts_model;
	protected $term_relationships_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_corn_model		= D("Weixin/weixin_corn");
		$this->posts_model				= D("Portal/Posts");
		$this->term_relationships_model = D("Portal/TermRelationships");
	}

	function index(){
		$type_id=0;
		if(!empty($_REQUEST["type"])){
			$type_id = intval($_REQUEST["type"]);
			$this->assign("type", $type_id);
			$_GET['type'] = $type_id;
		}
		$where_ands = empty($type_id) ? array("typeid > 0") : array("typeid = $type_id");
		
		if($this->admin_user['station_id']>1){
			array_push($where_ands, "station_id = '".$this->admin_user['station_id']."'");
		}

		$fields=array(
				'start_time'=> array("field"=>"createtime","operator"=>">"),
				'end_time'  => array("field"=>"createtime","operator"=>"<")
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator=$val['operator'];
					$field   =$val['field'];
					$get=$_POST[$param];
					$_GET[$param]=$get;
					if(in_array($param, array("start_time", "end_time"))){ $get=strtotime($get); }
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
					if(in_array($param, array("start_time", "end_time"))){ $get=strtotime($get); }
					if($operator=="like"){ $get="%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		$where	= join(" and ", $where_ands);

		$count	= $this->weixin_corn_model->where($where)->count();
		$page	= $this->page($count, 15);

		$datas	= $this->weixin_corn_model->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id DESC")->select();
		foreach($datas as $_v){
			$news = unserialize($_v['content']);
			if($news['news']['content']){
				foreach($news['news']['content'] as $val){
					$_v['title'] .= "<a href='{$val['url']}' target='_blank'>{$val['title']}</a><br>";
				}
			}else{
				$_v['title'] = $news['text']['content'];
			}
			$_v['msgtype']		= $news['msgtype'];
	        $_v['createtime']	= date('Y-m-d H:i:s', $_v['createtime']);

	        $posts[] = $_v;
		}

		$this->assign("Page",			$page->show('Admin'));
		$this->assign("current_page",	$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",		$_GET);
		$this->assign("posts",			$posts);

		$this->display();
	}

	function add(){

		$this->display();
	}
	
	function add_post(){
		$id = I("get.id");
		if (IS_POST) {
			if($_POST['msgtype']==1){
				if(preg_match('/[^\d,]+/', $_POST['artid'])) $this->error("推送的文章不存在格式错误");
				$artid = explode(',', $_POST['artid']);
				if($_POST['plate'] == 1){
					foreach($artid as $_v){
						$Infos = M('Posts')->where( array('id'=>$_v) )->field("id,post_title,post_excerpt,smeta")->find();
						if(empty($Infos)) continue;

						$tid = M('TermRelationships')->where( array('object_id'=>$Infos['id']) )->getField("tid");

						$smeta=json_decode($Infos['smeta'],true);$thumb=sp_get_asset_upload_path($smeta['thumb']);
						
						$data['title']			= $Infos['post_title'];
						$data['description']	= $Infos['post_excerpt'];
						$data['url']			= get_url(0).U('News/Show/index', array('id'=>$tid));
						$data['picurl']			= get_url(0).$thumb;

						$datas[] = $data;
					}
				}else if($_POST['plate'] == 2){
					foreach($artid as $_v){
						$Infos = M('Channel')->where( array('id'=>$_v) )->field("id,tid,name,mark,smeta")->find();
						if(empty($Infos)) continue;

						$food_all_type=array();
						$food_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>16))->GetField('id',true);
						if(!empty($food_all_type)) array_push($food_all_type, 16); else $food_all_type=array(16);
						
						$tour_all_type=array();
						$tour_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>25))->GetField('id',true);
						if(!empty($tour_all_type)) array_push($tour_all_type, 25); else $tour_all_type=array(25);
						
						$inter_all_type=array();
						$inter_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>32) )->GetField('id',true);
						if(!empty($inter_all_type)) array_push($inter_all_type, 32); else $inter_all_type=array(32);
						
						$nati_all_type=array();
						$nati_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>46) )->GetField('id',true);
						if(!empty($nati_all_type)) array_push($nati_all_type, 46); else $nati_all_type=array(46);
						
						$spec_all_type=array();
						$spec_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>51) )->GetField('id',true);
						if(!empty($spec_all_type)) array_push($spec_all_type, 51); else $spec_all_type=array(51);
						
						$educ_all_type=array();
						$educ_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>58) )->GetField('id',true);
						if(!empty($educ_all_type)) array_push($educ_all_type, 58); else $educ_all_type=array(58);
						
						$vric_all_type=array();
						$vric_all_type=D("Common/ChannelType")->where(array('status'=>1, 'parent'=>65) )->GetField('id',true);
						if(!empty($vric_all_type)) array_push($vric_all_type, 65); else $vric_all_type=array(65);

						$fs_url = '';
						if(in_array($Infos['tid'], $food_all_type)){
							$fs_url = U('Food/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $tour_all_type)){
							$fs_url = U('Tour/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $inter_all_type)){
							$fs_url = U('Interview/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $nati_all_type)){
							$fs_url = U('National/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $spec_all_type)){
							$fs_url = U('Specialty/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $educ_all_type)){
							$fs_url = U('Education/Show/index', array('id'=>$Infos['id']));
						}elseif(in_array($Infos['tid'], $vric_all_type)){
							$fs_url = U('Vrico/Show/index', array('id'=>$Infos['id']));
						}
						
						$smeta=json_decode($Infos['smeta'],true);$thumb=sp_get_asset_upload_path($smeta['thumb']);
						
						$data['title']			= $Infos['name'];
						$data['description']	= $Infos['mark'];
						$data['url']			= get_url(0).$fs_url;
						$data['picurl']			= get_url(0).$thumb;

						$datas[] = $data;
					}
				}else if($_POST['plate'] == 3){
					foreach($artid as $_v){
						$Infos = M('Demand')->where( array('id'=>$_v) )->field("id,post_title,remark,smeta")->find();
						if(empty($Infos)) continue;

						$smeta=json_decode($Infos['smeta'],true);$thumb=sp_get_asset_upload_path($smeta['thumb']);
						
						$data['title']			= $Infos['post_title'];
						$data['description']	= $Infos['remark'];
						$data['url']			= get_url(0).U('Demand/Show/index', array('id'=>$Infos['id']));
						$data['picurl']			= get_url(0).$thumb;

						$datas[] = $data;
					}
				}else if($_POST['plate'] == 4){
					foreach($artid as $_v){
						$Infos = M('Lives')->where( array('id'=>$_v) )->field("id,post_title,remark,smeta")->find();
						if(empty($Infos)) continue;

						$smeta=json_decode($Infos['smeta'],true);$thumb=sp_get_asset_upload_path($smeta['thumb']);
						
						$data['title']			= $Infos['post_title'];
						$data['description']	= $Infos['remark'];
						$data['url']			= get_url(0).U('Live/Show/index', array('id'=>$Infos['id']));
						$data['picurl']			= get_url(0).$thumb;

						$datas[] = $data;
					}
				}
				if(!$datas) $this->error("推送的文章不存在");

				$content = array( 'touser'=>'', 'msgtype'=>'news', 'news'=>array('content'=>$datas) );
			}else{
				$content = array( 'touser'=>'', 'msgtype'=>'text', 'text'=>array('content'=>$_POST['artid']) );
			}
			$content = serialize($content);
			if($id){
				$r = $this->weixin_corn_model->save(array('id'=>$id, 'sendtime'=>time(), 'conten'=>$content, 'typeid'=>intval($_POST['plate'])));
			}else{
				$r = $this->weixin_corn_model->add(array('ecuid'=>0, 'content'=>$content, 'createtime'=>time(), 'sendtime'=>time(), 'issend'=>0, 'sendtype'=>1, 'typeid'=>$_POST['plate'], 'station_id'=>$this->admin_user['station_id']));
			}
			if ($r !== false) {
				$this->success("操作成功");
			} else {
				$this->error("操作失败，请重试");
			}
		}
	}

	function send(){
		$id 		= I("get.id");
		$uid 		= I("get.uid");
		$all 		= I("get.all");
		$this->assign('id',		$id);
		$this->assign('uid',	$uid);
		$this->assign('all',	$all);
		
		if(I("get.do") == 'ajax'){
			$issend 	= M('WeixinCorn')->where(array('id'=>$id))->getField('issend');
			if($issend == 2) $this->error("该信息已经推送完成");

			@file_get_contents( get_url(0).U('Weixin/Doauto/index',array('station_id'=>$this->station_id, 'id'=>$id, 'all'=>$all)) );

			$this->success("发送成功！");
		}else{
			$this->display();
		}
	}

	function getsendstatus(){
		$id		= I("post.id");

		$send_data 	= M('WeixinSend')->where(array('object_id'=>$id))->find();
		if($send_data['send_type']){
			$expire_num = M('WeixinUser')->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.expire_in>'".time()."'")->count();

			$station_id = M('WeixinUser')->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.uid > '".$send_data['wxuser_id']."' and a.expire_in>'".time()."'")->order("a.uid asc")->getField("b.station_id");
		}else{
			$expire_num = M('WeixinUser')->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and b.station_id = '".$this->station_id."' and a.expire_in>'".time()."'")->count();

			$station_id = $this->station_id;
		}
		
		if($send_data && $expire_num){
			@file_get_contents( get_url(0).U('Weixin/Doauto/index',array('station_id'=>$station_id, 'id'=>$id, 'uid'=>$send_data['wxuser_id'], 'all'=>$send_data['send_type'])) );

			$arr['per'] 	= round($send_data['send_num']/$expire_num, 2)*100;
			$arr['issend'] 	= M('WeixinCorn')->where(array('id'=>$id))->getField('issend');
			$arr['msg'] 	= '发送中...';

			$this->success($arr);
		}else{
			$arr['per'] 	= 0;
			$arr['issend'] 	= M('WeixinCorn')->where(array('id'=>$id))->getField('issend');
			$arr['msg'] 	= "发送中...";

			$this->error($arr);
		}
	}
}