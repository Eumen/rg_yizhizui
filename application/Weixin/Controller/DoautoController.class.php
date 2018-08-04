<?php

namespace Weixin\Controller;
use Common\Controller\HomeBaseController; 

class DoautoController extends HomeBaseController {
	protected $weixin_corn_model, $weixin_user_model, $weixin_send_model;

	function _initialize(){
		parent::_initialize();
		
		$this->weixin_corn_model	= D("Weixin/weixin_corn");
		$this->weixin_user_model	= D("Weixin/weixin_user");
		$this->weixin_send_model	= D("Weixin/weixin_send");
	}

	public function index(){
		$id			= intval($_GET['id']);
		$uid		= intval($_GET['uid']);
		$all		= intval($_GET['all']);

		$sql_where['issend']	= 0;
		if($id > 0) $sql_where['id'] = $id;

		$wxcorn = $this->weixin_corn_model->where( $sql_where )->order("sendtime desc")->find();
		if($wxcorn){
			$content	= unserialize( $wxcorn['content'] );

			$msg		= array();
			$msgtype 	= $content['msgtype'];
			if($msgtype == 'news'){
				$msg['msgtype'] = $msgtype;
				foreach($content['news']['content'] as $_k=>$_v){
					$msg['news']['articles'][$_k]['title']			= $_v['title'];
					$msg['news']['articles'][$_k]['description'] 	= $_v['description'];
					$msg['news']['articles'][$_k]['url']			= $_v['url'];
					$msg['news']['articles'][$_k]['picurl']			= $_v['picurl'];
				}
			}else{
				$msg = $content;
			}

			if(empty($content['touser'])){
				if($all){
					$user_now 	= $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.uid > '".$uid."' and a.expire_in>'".time()."'")->order("a.uid asc")->find();
					$user_next 	= $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.uid > '".$user_now['uid']."' and a.expire_in>'".time()."'")->order("a.uid asc")->find();
				}else{
					$user_now 	= $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.uid > '".$uid."' and b.station_id = '".$this->station_id."' and a.expire_in>'".time()."'")->order("a.uid asc")->find();
					$user_next 	= $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and a.uid > '".$user_now['uid']."' and b.station_id = '".$this->station_id."' and a.expire_in>'".time()."'")->order("a.uid asc")->find();
				}

				$msg['touser'] = $user_now['fake_id'];
			}else{
				$msg['touser'] = $content['touser'];
			}
			$res_send = $this->weObj->sendCustomMessage($msg);

			if(!empty($user_now)){
				$send_ico = $this->weixin_send_model->where(array('object_id'=>$wxcorn['id']))->find();
				if(!empty($send_ico)){
					$data = array('id'=>$send_ico['id'], 'wxuser_id'=>$user_now['uid'], 'send_num'=>array("exp","send_num+1"), 'send_sid'=>$user_now['station_id']);
					if($res_send) $data['send_resu'] = array("exp","send_resu+1");
					$this->weixin_send_model->save($data);
				}else{
					$data = array('object_id'=>$wxcorn['id'], 'wxuser_id'=>$user_now['uid'], 'send_num'=>1, 'send_type'=>$all, 'send_sid'=>$user_now['station_id']);
					if($res_send) $data['send_resu'] = 1;
					$this->weixin_send_model->add($data);
				}
			}
		}
		if(!empty($user_next)){
			die(json_encode(array('error'=>0, 'nickname'=>$user_now['nickname'], uid=>$user_now['uid'], all=>$all)));
		}else{
			$this->weixin_corn_model->save(array("id"=>$wxcorn['id'], "issend"=>2));
			die(json_encode(array('error'=>1, 'nickname'=>'', uid=>0, all=>$all)));
		}
	}

	function expire_num(){
		$expire_num = $this->weixin_user_model->alias("a")->join(C( 'DB_PREFIX' )."users b ON b.id = a.ecuid")->where("a.isfollow=1 and b.station_id = 1 and a.expire_in>'".time()."'")->field()->count();

		die($expire_num);
	}
}