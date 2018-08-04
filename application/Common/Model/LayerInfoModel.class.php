<?php
namespace Common\Model;
use Common\Model\CommonModel;
class LayerInfoModel extends CommonModel {
	/**
	 * @see 数据添加
	 * @param layer 当前层级 all_member 层级满时候多少人 now_member 现在有多少人 is_fill 是否已经满
	 */
	public function add_info($layer = 0,$all_member = 0,$now_member = 0,$is_fill = 0){
		$this->layer = $layer;
		$this->all_member = $all_member;
		$this->now_member = $now_member;
		$this->is_fill = $is_fill;
		$this->	add_times = date('Y-m-d H:i:s');
		return $this->add();
	}	
		
	/**
	 * @see 数据修改
	 * @param field_name 字段名称 field_val 修改数值 condition 条件
	 */
	public function set_field($field_name,$field_val,$condition){
		return $this->where($condition)->setField($field_name,$field_val);
	}
	
	
	/**
	 * @see 数据获取
	 * @param field_name 字段名称  condition 条件
	 */
	public function get_field($field_name,$condition){
		return $this->where($condition)->getField($field_name);
	}
}