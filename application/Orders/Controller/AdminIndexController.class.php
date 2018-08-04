<?php
namespace Orders\Controller;
use Common\Controller\AdminbaseController;
class AdminIndexController extends AdminbaseController {
	function _initialize() {
		parent::_initialize();
		$this->order_model = D("Common/Orders");
		$this->users_model = D("Common/Users");
	}
	
	function index(){
		$this->_lists();
		$this->display(":index");
	}
	
	private  function _lists(){
		$fields=array(
				'start_time'		=> array("field"=>"os.add_time","operator"=>">="),
				'end_time'		=> array("field"=>"os.add_time","operator"=>"<="),
				'login_name'		=> array("field"=>"us.user_login","operator"=>"like"),
				'true_name'		=> array("field"=>"ui.true_name","operator"=>"like"),
				
		);
		if(IS_POST){
			foreach ($fields as $param =>$val){
				if (isset($_POST[$param]) && !empty($_POST[$param])) {
					$operator = $val['operator'];
					$field    = trim($val['field']);
					$get	      = $_POST[$param];
					$_GET[$param] = $get;
					if($operator  == "like"){ $get = "%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}else{
			foreach ($fields as $param =>$val){
				if (isset($_GET[$param]) && !empty($_GET[$param])) {
					$operator = $val['operator'];
					$field    = trim($val['field']);
					$get 	  = $_GET[$param];
					if($operator=="like"){ $get="%$get%"; }
					array_push($where_ands, "$field $operator '$get'");
				}
			}
		}
		$where  = join(" and ", $where_ands);
		
		$count	= $this->order_model->alias("os")
					   ->join(C ( 'DB_PREFIX' )."users us ON us.id = os.user_id ")
					   ->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id = os.user_id ")
				       ->where( $where )->count();
		$page	= $this->page($count, 20);
		
        $lists = $this->order_model->alias("os")
        				 ->join(C ( 'DB_PREFIX' )."users us ON us.id = os.user_id ")
					 ->join(C ( 'DB_PREFIX' )."user_infos ui ON ui.user_id = os.user_id ")
        				 ->where( $where )
        				 ->field('os.*,ui.true_name,us.user_login')
        				 ->limit($page->firstRow . ',' . $page->listRows)
        				 ->order('os.id desc')->select();
        			
		$this->assign("Page", $page->show('Admin'));
		$this->assign("current_page",$page->GetCurrentPage());
		unset($_GET[C('VAR_URL_PARAMS')]);
		$this->assign("formget",$_GET);
        $this->assign('lists', $lists);
		//统计
		$condition['table_type'] = array('eq',$types);
		$condition['_string'] = 'FROM_UNIXTIME(add_time,"%Y-%m-%d") = "'.date('Y-m-d').'"';
		$this->assign("t_all", $this->order_model->where($condition)->count());
		
		$condition['status'] = array('eq',0);
		$this->assign("t_uncheck", $this->order_model->where($condition)->count());
		unset($condition['_string']);
		unset($condition['status']);
		$this->assign("a_all", $this->order_model->where($condition)->count());
		$condition['status'] = array('eq',0);
		$this->assign("a_uncheck", $this->order_model->where($condition)->count());
	}
	
	
	public function edit(){
		$post_id = intval(I('id'));
		$result = $this->order_model->where(array('id'=>$post_id))->find();
		if ($result['status'] == 0 && $result['pay_status'] == 1) {
			$update_array =  array(
				'status'=>1,
				'admin_check_time'=>time(),
				'admin_id'=>get_current_admin_id(),
			);
			if($this->order_model->where(array('id'=>$post_id))->save($update_array)){
				$rg_time = array('addtime'=>date('Y-m-d'), 'createtime'=>date('H:i:s'));
				$award = new \Common\Lib\Award();
				$award->sale_award($result['user_id'],$result['price'], $rg_time);
				$this->success("发货成功！");
			}
		} else {
			$this->error("订单未支付，或已经处理！");
		}
	}
	
	public function order_cansul(){
		$post_id = intval(I('id'));
		$result = $this->order_model->where(array('id'=>$post_id))->find();
		if ($result['status'] == 0 && $result['pay_status'] == 1) {
			$update_array =  array(
				'status'=>2,
				'admin_check_time'=>time(),
				'admin_id'=>get_current_admin_id(),
			);
			if($this->order_model->where(array('id'=>$post_id))->save($update_array)){
				$this->users_model->where('id='.$result['user_id'])->setInc('amount',$result['price']);
				$this->users_model->where('id='.$result['user_id'])->setDec('shopping_number',$result['price']);
				$this->success("取消成功！");
			}
		} else {
			$this->error("订单未支付，或已经处理！");
		}
	}
	
	public function check_order(){
		$post_id = intval(I('id'));
		$result = $this->order_model->where(array('id'=>$post_id))->find();
		$this->assign($this->users_model->where(array("id"=>$result['user_id']))->find());
		$this->assign(M('user_clothes')->where(array("user_id"=>$result['user_id']))->find());
		$this->display(":check_order");
	}
	
	public function change_order_status(){
		if(IS_POST){
			$order_id = I('order_id');
			$result = $this->order_model->where(array('id'=>$order_id))->setField('status',I('status'));
			$this->success("修改成功！");
		}else{
			$post_id = intval(I('id'));
			$result = $this->order_model->where(array('id'=>$post_id))->find();
			$this->assign("orders",$result);
			$this->assign($this->users_model->where(array("id"=>$result['user_id']))->find());
			$this->assign("order_status",M('order_status')->select());
			$this->display(":change_order_status");
		}
		
	}
	
	public function look_info(){
		$post_id = intval(I('id'));
		$orders = $this->order_model->where(array('id'=>$post_id))->find();
		$g_img = M("goods_sub")->where("id=".$orders['good_id'])->getField("smeta");
		if($g_img){
			$this->assign("smeta",	json_decode($g_img,true));
		}
		$userid  =  $orders['user_id'];
		$condition['user_id'] = array('eq',$userid);
    		$condition['types']   = array('eq',$orders['clothes_type']);
		$option = M('user_measure')->where($condition)->getField('option_value');
    		if($option){ $this->assign((array)json_decode($option)); }
    		$this->assign("orders",$orders);
    		$this->assign('info',M("UserInfos")->where(array("user_id"=>$userid))->find());
		$this->assign($this->users_model->where(array("id"=>$userid))->find());
		
    		if($orders['clothes_type'] == "man"){
    			$this->display(":man");
    		}else if($orders['clothes_type'] == "woman"){
    			$this->display(":woman");
    		}else if($orders['clothes_type'] == "fs_qipao"){
    			$this->display(":fs_qipao");
    		}else{
    			echo "get info error";exit;
    		}
	}
	
	public function change_pay_status(){
		$post_id = intval(I('id'));
		$orders = $this->order_model->where(array('id'=>$post_id))->find();
		if(empty($orders['pay_status'])){
			$data = array('admin_id'=>get_current_admin_id(),'order_id'=>$post_id,'add_times'=>date('Y-m-d H:i:s'));
			if(M('_order_pay_status_change_records')->add($data)){
				$this->order_model->where(array('id'=>$post_id))->setField('pay_status',1);
				$this->success("修改成功！");
			}
		}
	}
	
	
}