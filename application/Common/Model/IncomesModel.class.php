<?php
namespace Common\Model;
use Common\Model\CommonModel;
class IncomesModel extends CommonModel {
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
	/**
	 * @see 用户奖金变动记录
	 */
	public function income_record($userid, $award_type, $reason, $money,$status=1,$rg_time=null,$other=null) {
		$this->user_id		= $userid;
		$this->types		= $award_type;
		$this->amount 		= $money;
		$this->reason 		= $reason;
		$this->status 		= $status;
		$this->pay_uid 		= $other['pay_uid'] ? $other['pay_uid'] : 0;
		$this->addtime 		= $rg_time['addtime'] ? $rg_time['addtime'] : date('Y-m-d');
		$this->createtime	= $rg_time['createtime'] ? $rg_time['createtime'] : date('H:i:s');

        return $this->add();
    }
}