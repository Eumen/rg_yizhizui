<?php
namespace Api\Controller;
use Think\Controller;
class WeixinpayController extends  Controller{
	/** @see 阿里云 回调方式 **/
	public function weixinpay_call_back(){
        vendor('WeixinPay.WxPayPubHelper');
        $file_n = getcwd()."/fs_log/Logs_".date("Ymd").".txt";
        $file	= fopen($file_n, "a+");
        fwrite($file,"\n------------is begin --".date("Y-m-d H:i:s")."\n");
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        //获取订单
        $get_orders = $notify->data["out_trade_no"];

        $returnXml = $notify->returnXml();
        
        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======
        
        //以log文件形式记录回调信息
        fwrite($file,"【接收到的notify通知】:\n".$xml."\n");
        if($notify->data["result_code"] == "SUCCESS"){
            //此处应该更新一下订单状态，商户自行增删操作
            fwrite($file,$get_orders."【支付成功 验证成功】:\n".$xml."\n");
            
            $order = D("Common/Orders")->where(array('order_number'=>$out_trade_no))->find();

            D("Common/Orders")->where(array('order_number'=>$out_trade_no))->save( array("pay_status"=>1, "pay_time"=>time()) );

            //用户激活
            $rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
            D("Common/Users")->Activation($order['user_id'], $order['price'], $rg_time);

            exit("success"); 
        } else {
            //此处应该更新一下订单状态，商户自行增删操作
            fwrite($file,"【【业务出错】:\n".$xml."\n");
            exit("fail"); 
        }
               
       fwrite($file,"\n-------------- end --".date("Y-m-d H:i:s")."-------\n");
       fclose($file);
	}
    
}



