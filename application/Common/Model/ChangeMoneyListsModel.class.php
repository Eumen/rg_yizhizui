<?php
namespace Common\Model;
use Common\Model\CommonModel;
class ChangeMoneyListsModel extends CommonModel {
	protected function _before_write(&$data) { parent::_before_write($data); }

    function ChangeMoney($post) {
        $User	= M("Users");
		$uid			= sp_get_current_userid();
		$site_options 	= get_site_options();

        $post['amount'] = intval($post['amount']);
        if ($post['amount'] <= 0) {  return -1000; }

		$condition['user_login'] = array('eq', $post['login_name']);
        $objToUser = $User->where($condition)->find();
		if( empty($objToUser) ) {  return -1; }
		
        $data = $this->create();
        $map['id'] = array('eq', $uid);
        $map['user_status'] = array('eq', 1);
        $objUser = $User->where($map)->find();

        if ($post['login_name'] == $objUser['user_login']) {  return -1001; }
        
        if (is_string($post['types']) ) { 
        	$types = htmlspecialchars($post['types']); 
        }else{
       	 	return -1005;
        }
        if(!in_array($types, array('shop_amount','r_amount'))) { return -1005; }
        
		$fee = $post['fee'];
		$data['type'] 		= $types;
        $data['to_user_id'] = $objToUser['id'];
        $data['user_id']	= $uid;
        $data['amount']		= $post["amount"];
        $data['addtime']	= date('Y-m-d H:i:s');
		$data['bili']		= $fee;

		if ($objUser[$types] >= $data['amount']) {
			$this->add($data);
			$fee > 0 ? $can_get_money = $data['amount'] - ($data['amount'] * $fee) : $can_get_money =  $data['amount'];
			$User->where( array('id'=>$objUser['id']) )->setDec($types, $can_get_money);
			$User->where( array('id'=>$objToUser['id']) )->setInc($types, $can_get_money);
			return 1;
		} else {
			return 0;
		}
    }
	
	function select_pid_user($user_id,$find_user_id){
		$get_pid = M('users')->where(array('id'=>$user_id))->getField('rid');
		if($get_pid){
			if($find_user_id == $get_pid){
				return $find_user_id;
			}else{
				return $this->select_pid_user($get_pid, $find_user_id);
			}
		}
		return false;
	}
	
}

?>