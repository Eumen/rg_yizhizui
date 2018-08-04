<?php

namespace Weixin\Controller;
use Common\Controller\HomeBaseController; 

class IndexController extends HomeBaseController {
	protected $weixin_msg_model, $weixin_user_model, $weixin_qrcode_model;
	protected $wxid, $type, $event, $content;
	
	function _initialize(){
		parent::_initialize();
		
		$this->weixin_msg_model		= M()->table('weixin_msg');
		$this->weixin_user_model	= M()->table('weixin_user');
		$this->weixin_qrcode_model	= M()->table('weixin_qcode');
		
		$this->weObj->valid();
		
		$this->wxid = $this->weObj->getRev()->getRevFrom();
		$this->type = $this->weObj->getRev()->getRevType();
		$reMsg = "";
		switch($this->type) {
			case 'text':
				$this->content	= $this->weObj->getRev()->getRevContent();
				break;
			case 'event':
				$this->event	= $this->weObj->getRev()->getRevEvent();
				$this->content	= json_encode($this->event);
				break;
			case 'image':
				$this->content	= json_encode($this->weObj->getRev()->getRevPic());
				$reMsg			= "图片很美！";
				break;
			case 'location':
				$this->content	= json_encode($this->weObj->getRev()->getRevGeo());
				$reMsg			= "您所在的位置很安全！";
				break;
			default:
				$reMsg			= $this->wx_config['helpmsg'];
		}
		if($reMsg){ $this->weObj->text($reMsg)->reply();exit; }
	}

	public function index(){
		$followInfo = $this->getFollowUserInfo($this->wxid);
		if(!empty($followInfo) or $followInfo['expire_in'] - 86400 < time()){
			$info = $this->weObj->getUserInfo($this->wxid);
			$info['station_id']  = $this->station_id;
			D("Common/Users")->followUser($this->wxid, $info);
		}
		
		if(empty($followInfo)){
			$followInfo = $this->getFollowUserInfo($this->wxid);
		}
		
		if($this->content){
			M('weixin_msg')->add(array('uid'=>$followInfo['ecuid'], 'fake_id'=>$this->wxid, 'createtime'=>time(), 'createymd'=>date('Y-m-d'), 'content'=>$this->content, 'type'=>$this->type));
		}

		//用户关注
		if ($this->event['event'] == "subscribe") {
			$this->scan_subscribe();

			$this->weObj->text($this->wx_config['followmsg'])->reply(); exit;
		}
		// 用户取消关注
		if ($this->event['event'] == "unsubscribe"){
			$this->unFollowUser($this->wxid); exit;
		}
		//用户扫码动作处理
		if ($this->event['event'] == "SCAN"){
			$this->scan_subscribe();
		}
		//判断用户是否点击的菜单
		if ($this->event['event'] == "CLICK"){
			$this->content = $this->event['key'];
			
			$this->weObj->text("未定义菜单事件{$this->content}")->reply();exit;
		}

		if($this->content == "1"){
			$this->weObj->text("详情请咨询07715905703")->reply();exit;
		}elseif($this->content == "2"){
			$this->weObj->text("详情请咨询07715905703")->reply();exit;
		}else{
			$newsData = $this->getContentByKey($this->content);
			if(!empty($newsData)){
				$this->weObj->news($newsData)->reply();exit;
			}else{
				$this->weObj->text($this->wx_config['followmsg'])->reply();exit;
			}
		}
	}

//------------------------------------------------------------------
//微信调用函数
//------------------------------------------------------------------
	function getstr($str){
		return htmlspecialchars($str,ENT_QUOTES);
	}
	function saveMsg($content, $wxid, $type){
		if($content){
			$user		= $this->getFollowUserInfo($wxid);
			$uid		= intval($user['ecuid']);
			$createtime = time();
			$createymd	= date('Y-m-d');
			$content	= $this->getstr($content);
			$rg_data	= array(
				'uid'		=> $uid,
				'fake_id'	=> $wxid,
				'createtime'=> $createtime,
				'createymd'	=> $createymd,
				'content'	=> $content,
				'type'		=> $type
			);
			$this->weixin_msg_model->add($rg_data);
			return true;
		}
		return false;
	}
	function getFollowUserInfo($wxid){
		return $this->weixin_user_model->where( array('fake_id'=>$wxid) )->find();
	}
	function unFollowUser($wxid){
		$this->weixin_user_model->where(array('fake_id'=>$wxid))->save(array('isfollow'=>0, 'expire_in'=>0));
		return true;
	}
	
	//扫码事件处理
	function scan_subscribe(){
		//场景值ID，临时二维码时为32位非0整型，永久二维码时最大值为100000
		if( $this->event['event'] == 'subscribe' && !is_numeric($this->event['key']) ){
			$rg_key		= explode('_', $this->event['key']);
			$content	= intval($rg_key[1]);
		}else{
			$content	= intval($this->event['key']);
		}
		$res = $this->weixin_qrcode_model->find($content);
		if($res){
			if($res['type'] == 1){
				$fsInfo=sp_sql_post($res['content'],'');
				$smeta=json_decode($fsInfo['smeta'],true);
				$newsData[0]['Title']		= $fsInfo['post_title'];
				$newsData[0]['Description'] = strip_tags($fsInfo['post_excerpt']);
				$newsData[0]['PicUrl']		= get_url(0).sp_get_asset_upload_path($smeta['thumb']);
				$newsData[0]['Url']			= get_url(0).U('News/Show/index', array('id'=>$fsInfo['tid']));

				$this->weObj->news($newsData)->reply();exit;
			}elseif($res['type'] == 2){
				
			}elseif($res['type'] == 3){
				$fsInfo = M('demand')->find($res['content']);
				$smeta=json_decode($fsInfo['smeta'],true);
				$newsData[0]['Title']		= $fsInfo['post_title'];
				$newsData[0]['Description'] = strip_tags($fsInfo['remark']);
				$newsData[0]['PicUrl']		= get_url(0).sp_get_asset_upload_path($smeta['thumb']);
				$newsData[0]['Url']			= get_url(0).U('Demand/Show/index', array('id'=>$fsInfo['id']));

				$this->weObj->news($newsData)->reply();exit;
			}elseif($res['type'] == 4){
				$fsInfo = M('lives')->find($res['content']);
				$smeta=json_decode($fsInfo['smeta'],true);
				$newsData[0]['Title']		= $fsInfo['post_title'];
				$newsData[0]['Description'] = strip_tags($fsInfo['remark']);
				$newsData[0]['PicUrl']		= get_url(0).sp_get_asset_upload_path($smeta['thumb']);
				$newsData[0]['Url']			= get_url(0).U('Live/Show/index', array('id'=>$fsInfo['id']));

				$this->weObj->news($newsData)->reply();exit;
			}else{
				$this->weObj->text( $res['content'] )->reply();exit;
			}
		}else{
			$login = M('weixin_login')->where(array('ticket'=>$this->content['ticket']))->order('id desc')->find();
			if($login && $login['uid'] == 0 && $login['createtime'] + 600 > time() ){
				$user = $this->getFollowUserInfo($this->wxid);
				if($user['ecuid'] > 0 && $user['isfollow']==1) {
					 M('weixin_login')->where(array('id'=>$login['id']))->save( array( 'uid'=>$user['uid'], 'open_id'=>$this->wxid ) );
					 $this->weObj->text("您使用扫一扫功能登陆成功！")->reply();exit;
				}
			}else{
				
			}
		}
	}
	
	function getContentByKey($content=''){
		$newsData 	= array();
		$_key		= 0;
		$content 	= $this->getstr($content);
		
		return empty($newsData) ? array() : array_slice($newsData, 0, 8);
	}
}