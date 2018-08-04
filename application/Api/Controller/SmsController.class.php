<?php
namespace Api\Controller;
use Think\Controller;
class SmsController extends Controller {
	public function ajax_send_msg(){
		I('type') == 'self' ? $tel = session('user.tel') : $tel = trim(I('tel'));
		if(!preg_match('/1[34578]{1}\d{9}$/',$tel)){ $this->ajaxReturn(array("status"=>0, 'info'=>"手机号码错误")); }
		if(session('user_code_number') > 3){
			$this->ajaxReturn(array("status"=>0, 'info'=>"您点击手机验证已经超额，请10分钟后再试"));
		}
		$number = mt_rand(100000, 999999);
		$contend = "验证码".$number."，请确认本人操作（向您要验证码是诈骗行为，请勿泄露给他人）【今罗汉】";

        $res = rg_sendSMS($tel, $contend);
		if($res->msg == 'ok' && $res->error == 0){
			session('user_code', $number);
			session('user_code_number', session('user_code_number')+1, 600);
			$this->ajaxReturn(array("status"=>1,'info'=>"发送成功"));
		}else{
			$this->ajaxReturn(array("status"=>0,'info'=>"发送失败"));
		}
    }
}