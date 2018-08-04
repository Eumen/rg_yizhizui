<?php
namespace Goods\Controller;
use Common\Controller\HomeBaseController;
/**  产品列表 */
class IndexController extends HomeBaseController {
	function _initialize(){
		parent::_initialize();
		$this->goods_model 		= D("Common/Goods");
		$this->orders_model		= D("Common/Orders");
		$this->userinfos_model	= D("Common/UserInfos");
		$this->users_model 		= D("Common/Users");
		$this->uid = sp_get_current_userid();
		$this->assign("rg_on", 5);
	}
	
	public function index() {
		$count = $this->goods_model->count();
		$page  = $this->page($count, 3);
		$goods = $this->goods_model->limit($page->firstRow . ',' . $page->listRows)->select();
		
		foreach($goods as $key=>$val){
			$get_smeta = json_decode($val['smeta']);
			$goods[$key]['smeta'] = sp_get_asset_upload_path($get_smeta->thumb);
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("goods",$goods);
		if(sp_is_mobile()){
			$this->display(":index1"); 
		}else{
			$this->display(":index"); 
		}
	}
	
	public function good_info(){
		if(IS_POST){
			$this->check_login();
			$rules = array(
				array('gid','require','请选择需要兑换的商品！',1),
				array('username','require','发货人姓名不能为空！',1),
				array('addre','require','发货地址名不能为空！',1),
				array('tel','require','发货电话不能为空！',1)
			);
	    	//检查购买的产品是否已经有完善的资料
	    	$goods_id = I("gid");
	    	$ginfo = $this->goods_model->where(array('id'=>$goods_id))->field('price,name,rand')->find();
	    	$price = $ginfo['price'];
	    	//获取用于消费余额
            $good_amount=$this->users_model->where( array('id'=>$this->uid) )->getField('good_amount');
            if ($price>$good_amount){
                $this->error("您的商城积分余额不足！");exit;
            }
	    	$_POST['good_id'] 		= $goods_id ;
	    	$_POST['order_name'] 	= $ginfo['name'] ;
	    	$_POST['price']			= $price;
	    	
	    	$_POST['user_id'] 		= $this->uid ;
    		$_POST['order_number']  = get_only_number('orders','order_number');
    		$_POST['add_time'] 	    = time();
    		$_POST['add_month']		= date('Y-m');
    		$_POST['pay_status']    = 1;
            $_POST['pay_time'] 	    = time();

            $update_money = array('good_amount'=>$good_amount - $price);
            if( $this->users_model->where( array('id'=>$this->uid) )->setField($update_money) ){
                if($this->orders_model->validate($rules)->create() === false ){
                    $this->error($this->orders_model->getError());
                }
                $order_id = 	$this->orders_model->add();
                $this->success("成功下单!", U('Pay/Index/pay_success'));
            }else{
                $this->success("支付失败，请重试!");
            }
		}else{
			$get_id = intval(I('id'));
			$this->assign("info",$this->userinfos_model->where("user_id=".$this->uid)->find());
			$goods = $this->goods_model->where(array('id'=>$get_id))->find();
			
			$get_smeta = json_decode($goods['smeta']);
			$goods['smeta'] = sp_get_asset_upload_path($get_smeta->thumb);
			
			$get_citys = M('ecs_region')->where('parent_id=1')->field('region_id,region_name')->select();
			$this->assign("citys",$get_citys);
		
			$this->assign("goods",$goods);
			$this->display(":good_info");
		}
	}
	
	
	public function ginfo(){
		$id = intval(I('id'));
		if(!empty($id)){
			$goods = $this->goods_model->where(array('id'=>$id))->find();
			$get_smeta = json_decode($goods['smeta']);
			$goods['smeta'] = sp_get_asset_upload_path($get_smeta->thumb);
			
			$this->assign("goods",$goods);
		}
		$this->display(":ginfo");
	}
	
	function ajax_city(){
		$get_citys = M('ecs_region')->where('parent_id='.I('id'))->field('region_id,region_name')->select();
		if($get_citys){
			$this->ajaxReturn(array('status'=>1,'lists'=>$get_citys));
		}else{
			$this->ajaxReturn(array('status'=>0));
		}
	}
	
}
