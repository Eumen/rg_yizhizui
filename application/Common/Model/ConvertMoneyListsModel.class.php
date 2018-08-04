<?php
namespace Common\Model;
use Common\Model\CommonModel;
class ConvertMoneyListsModel extends CommonModel
{
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}

    function ConvertMoney($post) {
        $User = M('Users');
		$rg_arr = array('amount' => '奖金积分', 'shop_amount' => '电子积分', 'good_amount' => '商城积分');
		$uid = sp_get_current_userid();
		if(is_numeric($post['amount']) && $post['amount'] >= 100){
			$conv_amount = intval(trim($post['amount']));
		}else{
			return -1000;
		} 
        if ($conv_amount <= 0) return -1001; 
        if (is_string($post['types']) && is_string($post['totypes'])) {
            $types      = htmlspecialchars($post['types']);
            $totypes    = htmlspecialchars($post['totypes']);
        }else{
       	 	return -1000;
        }
        if(!in_array($types,array('amount')) || $totypes != 'shop_amount') return -1000; 

        $fee_s = $post['fee_s'];
        $fee_g = $post['fee_g'];
        $condition['id'] = array('eq', $uid);
        $user_amount = $User->where($condition)->getField($types);

        if ($user_amount >= $conv_amount) {
			$User->where( array('id'=>$uid) )->setDec($types, $conv_amount);

            $fee_s_money = round($conv_amount * ($fee_s/100), 2);
            $fee_g_money = round($conv_amount * ($fee_g/100), 2);
            $User->where( array('id'=>$uid) )->setInc($totypes, $fee_s_money);
            $User->where( array('id'=>$uid) )->setInc('good_amount', $fee_g_money);
            
            $data['user_id']		= $uid;
            $data['types']		    = $rg_arr[$types];
            $data['totypes']		= $rg_arr[$totypes];
            $data['addtime']		= date('y-m-d H:i:s');
            $data['amount']         = $conv_amount;
            $data['shop_amount']    = $fee_s_money;
            $data['good_amount']    = $fee_g_money;
            $data['fee']			= 0;
            $data['status']		    = 1;
            $this->add($data);
            return 1;
        }else{
            return 0;
        }
        
    }

}

?>
