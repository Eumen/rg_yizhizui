<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class RandpriceController extends AdminbaseController{
	
	function _initialize() {
		parent::_initialize();
		$this->store_price = M('rand_price');
	}
	
	function index(){
		$this->assign("lists",$this->store_price->select());
		$this->display();
	}
	
	function add(){
		if(IS_POST){
			$_POST['add_time'] = time();
			$_POST['admin_id'] = get_current_admin_id();
			if ($this->store_price->create()){
				if ($this->store_price->add()!==false) {
					$this->success(L('ADD_SUCCESS'), U("Randprice/index"));
				} else {
					$this->error(L('ADD_FAILED'));
				}
			} else {
				$this->error($this->store_price->getError());
			}
		}else{
			$this->display();
		}
	}
	
	public function delete(){
		$id = intval(I("get.id"));
		if ($this->store_price->delete($id)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}
	
	function edit(){
		$id = I("get.id");
		$ad = $this->store_price->where("id=$id")->find();
		$this->assign($ad);
		$this->display();
	}
	
	function edit_post(){
		if (IS_POST) {
			if ($this->store_price->create()) {
				if ($this->store_price->save()!==false) {
					$this->success("保存成功！", U("Randprice/index"));
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->store_price->getError());
			}
		}
	}
	
}