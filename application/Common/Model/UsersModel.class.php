<?php
namespace Common\Model;
use Common\Model\CommonModel;
class UsersModel extends CommonModel
{
	
	protected $_validate = array(
			array('user_login', 'require', '用户名称不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT  ),
			array('user_pass', 'require', '密码不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT ),
			array('user_login', 'require', '用户名称不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
			array('user_pass', 'require', '密码不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
			array('user_login','','用户名已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_login字段是否唯一
			array('user_email','email','邮箱格式不正确！',0,'',CommonModel:: MODEL_BOTH ), // 验证user_email字段格式是否正确
	);
	
	//用于获取时间，格式为2012-02-03 12:12:12,注意,方法不能为private
	function mGetDate() {
		return date('Y-m-d H:i:s');
	}
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
		
		if(!empty($data['user_pass']) && strlen($data['user_pass'])<25){
			$data['user_pass']=sp_password($data['user_pass']);
		}
	}

	public function CheckArea($user_login, $area) {
		$user_id = $this->where ( "user_login='$user_login'" )->getField ( 'id' );
		$count_rigth_rid = $this->where ( "rid='$user_id' and area=1" )->count ( 'id' );
		$count_left_rid = $this->where ( "rid='$user_id' and area=2" )->count ( 'id' );
		
		if ($count_rigth_rid > $count_left_rid) {
			$get_remain = $count_rigth_rid - $count_left_rid;
			if ($get_remain >= 2) {
				$is_area = 2;
			} else {
				$is_area = 0;
			}
		} else if ($count_left_rid > $count_rigth_rid) {
			$get_remain = $count_left_rid - $count_rigth_rid;
			if ($get_remain >= 2) {
				$is_area = 1;
			} else {
				$is_area = 0;
			}
		} else {
			$is_area = 0;
		}
		
		if ($is_area == 0 || $is_area == $area) {
			return $this->get_are_pid ( $user_id, $area );
		} else {
			return false;
		}
	}
	public function get_are_pid($user_id, $area) {
		$id = $this->where( "pid='$user_id' and area='$area'" )->getField ( 'id' );
		if ($id == 0 || empty ( $id )) {
			return $user_id;
		} else {
			return $this->get_are_pid( $id, $area );
		}
	}

	public function add_money($userid, $amout) {
		$this->where( array('id'=>$userid) )->setInc( 'amount', $amout );
	}
	
	/**
	 * @see 用户奖金计算统一调用
	 * @param 激活用户的id
	 */
	public function Activation($id,$price,$rg_time) {
		//开启事物
        M()->startTrans();
		//数据累积区
		$user_rid = $this->where( array('id'=>$id) )->getField ( 'rid' );
		if($user_rid) $this->where( array('id'=>$user_rid) )->setInc ( 'rid_counts', 1 );

		//奖金区域
		$award = new \Common\Lib\Award();
		$r1[] = $award->begin_award($id,$price,$rg_time);

        foreach ($r1 as $v) { $r[] = $v;  }
		if (in_array(false, $r)) {
            M()->rollback();
        } else {
            M()->commit();
        }
        return true;
	}
	
	/**@see 用户点击升级时候，进去检查**/
	public function user_update(){
		$user_id = sp_get_current_userid();
		$map['user_id'] = array('eq',$user_id);
		$map['status'] 	= array('eq',0);
		$layers = M('layers')->where($map)->order("layer asc")->find();
		if($layers){
			if(M('incomes')->where(array('status'=>0, 'pay_uid'=>$layers['user_id'], 'types'=>array('in',array('PID','LAYER'))))->count()){
				return -2;
			}else{
				if(M('layers')->where(array('id'=>$layers['id']))->setField('status',1)){
					$layer_number = M('layers')->where(array('id'=>$layers['id']))->getField('layer');
					$this->where(array('id'=>$user_id))->setField('layer', $layer_number);
					return 1;
				}
				return 0;
			}
		}else{
			return -1;
		}
	}
	
	public function followUser($wxid, $info=array()){
		if(empty($wxid)) return false;

		$sex			= intval($info['sex']);
		$nickname		= $info['nickname']			? filterEmoji($info['nickname']) : '';
		$country		= $info['country'] 			? $info['country'] 			: '';
		$province		= $info['province'] 		? $info['province'] 		: '';
		$city			= $info['city'] 			? $info['city'] 			: '';
		$access_token	= $info['access_token'] 	? $info['access_token'] 	: '';
		$headimgurl		= $info['headimgurl'] 		? $info['headimgurl'] 		: '';
		$subscribe		= $info['subscribe'] 		? $info['subscribe'] 		: 0;
		$expire_in		= $subscribe 				? (86400 * 2) + time() 		: 0;

		$wx_user = M()->table('weixin_user')->where( array('fake_id'=>$wxid) )->find();
		if(!$wx_user['ecuid'] && sp_is_user_login()){
			$uid = sp_get_current_userid();

			M()->table('weixin_user')->where( array('uid'=>$wx_user['uid']) )->save(array('ecuid'=>$uid));
		}

		if( $wx_user['uid'] > 0 ){
			$rg_data = array();
			$rg_data['isfollow'] 	= $subscribe;
			$rg_data['expire_in']	= $expire_in;
			if($info){
				$rg_data['nickname']	= $nickname;
				$rg_data['sex']			= $sex;
				$rg_data['country']		= $country;
				$rg_data['province']	= $province;
				$rg_data['city']		= $city;
				$rg_data['access_token']= $access_token;
				$rg_data['headimgurl']	= $headimgurl;
			}
			M()->table('weixin_user')->where( array('uid'=>$wx_user['uid']) )->save($rg_data);
		}else{
			$rg_data = array();
			$rg_data['createtime']	= time();
			$rg_data['createymd']	= date('Y-m-d');
			$rg_data['fake_id']		= $wxid;
			$rg_data['nickname']	= $nickname;
			$rg_data['sex']			= $sex;
			$rg_data['country']		= $country;
			$rg_data['province']	= $province;
			$rg_data['city']		= $city;
			$rg_data['access_token']= $access_token;
			$rg_data['expire_in']	= $expire_in;
			$rg_data['headimgurl']	= $headimgurl;

			M()->table('weixin_user')->add($rg_data);
		}
		return true;
	}
}

