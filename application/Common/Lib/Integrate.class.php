<?php
namespace Common\Lib;
class Integrate {
	protected $users_model, $userinfos_model, $site_options;
	function __construct(){
        $this->users_model      = D("Common/Users");
        $this->userinfos_model  = D("Common/UserInfos");
        $this->site_options     = get_site_options();
	}
    /**
     * @param type $userid       (用户id)
     * @param type $award_type   (类型)
     * @param type $remark       (描述)
     * @param type $amout        (奖金数额)
     * @param type $feng_ding    (0 为不设置封顶，1 为日封顶,2 为月封顶)
     * @param type $deduction    (0 为不扣除奖金，1 为扣除部份奖金进入重复消费)
     * @param type $network_fee  (0 为不扣网络维护费，1 为扣网络维护费)
     */
    public function integrates($userid, $award_type, $remark, $amout, $status=1, $rg_time=null, $pay_uid=0) {
		$user =  $this->users_model->where( array('id'=>$userid) )->find();
		if($user['user_status'] != 1 ) return ;
        if ($amout >= 0.01) {
            $other= array('pay_uid'=>$pay_uid);

            self::remark($userid, $award_type, $remark, $amout, $status, $rg_time, $other);  // 记录奖金
			if($status == 1){
				//管理费
				$manager_amout =  $amout * $this->site_options['gsglf']/100;
				$money = $amout - $manager_amout;

                $bl_amount      = $this->site_options['bl_amount']/100;
                $bl_e_amount    = $this->site_options['bl_e_amount']/100;
                $bl_shop_amount = $this->site_options['bl_shop_amount']/100;

	            $update_money['amount']     = $user['amount']      + ($money*$bl_amount);
	            $update_money['e_amount']   = $user['e_amount']    + ($money*$bl_e_amount);
	            $update_money['shop_amount']= $user['shop_amount'] + ($money*$bl_shop_amount);
	            $this->users_model->where( array('id'=>$user['id']) )->setField($update_money);

                self::remark($userid, 'MANAGER', '管理费扣除', '-'.$manager_amout, $status, $rg_time, $other);  // 记录奖金
			}
        }
    }

    public function remark($userid, $award_type, $remark, $money, $status=1, $rg_time=null,$other=null) {
        $income = D('Incomes');
        $income->income_record($userid,$award_type,$remark,$money,$status,$rg_time,$other);
    }
}
