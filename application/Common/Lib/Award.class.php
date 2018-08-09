<?php
namespace Common\Lib;
class Award{
	private $integrate,$user,$userinfos_model,$site_options;
	/**  @see 始化类 */
	function __construct() {
		$this->integrate 		= new  \Common\Lib\Integrate();
		$this->site_options		= get_site_options();
		$this->user				= M("Users"); 
		$this->incomes			= D('Incomes');
		$this->userinfos_model	= D("Common/UserInfos");
	}
	
	/**@see id 激活会员id，rid推荐人id **/
	public function begin_award($id,$money,$rg_time=null){
        $this->rid_award($id,$money,$rg_time);//done 推荐奖金
        $this->layer_award($id,$money,$rg_time);//done  见点奖金
        $this->agen_award($id,$money,$rg_time);//done  中心奖金
        
        $this->leader_award($id,$money,$rg_time);//done 极差奖金
		$this->country_director_award($id,$money,$rg_time);//done 全国董事
		//用户升级判断
		$this->user_update($id,$money,$rg_time);
		return true;
	}
	
	/**
	 * @see 推荐奖 ，总2层
	 * @param $id 用户id，$money 购物的钱
	 */
	 private function rid_award($id,$money,$rg_time=null){
		if(empty($id)) { return; }
		//当前用户id
		$rid = $id;//记录循环用户id
		$count = 1;//记录总的可以拿到的人数，3个人
		 do {
		 	$rid = $this->user->where(array("id"=>$rid))->getField('rid');
		 	if($rid){
		 		$rg_user = $this->user->where(array("id"=>$rid))->field('id,user_status')->find();
			 	if(!empty($rg_user) && $rg_user['user_status'] == 1 ){
			 		$scale = $this->site_options['tj'.$count]/100;//用户当前可以拿到的比例
			 		$user_price = $scale * $money;//可以拿到的钱
			 		if($user_price > 0 ){
			 			$this->integrate->integrates($rid,'RID','推荐奖金'.$count."代",$user_price,1,$rg_time, $id);
			 		}
			 		$count ++;//次数记录
			 	}
		 	}
		} while($rid > 0 && $count <= 2);
	 }
	 
    /**
     * @see 见点奖（15层） 1%
     * @param $id 用户id
     */
    public function layer_award($id,$money,$rg_time){
        if(empty($id)) { return; }
        //当前用户id
        $currend_id = $id;
        $max_layer 	= 15;
        
        $tz_num		= $money/600;
        $price 		= $this->site_options['POINT'] * $tz_num;
        
        for ($i=1; $i <= $max_layer; $i++) {
            $rg_user 	= $this->user->where(array("id"=>$currend_id))->field('rid')->find();
            $currend_id = $rg_user['rid'];
            // 获取推荐人的状态
            $rg_user_recommend 	= $this->user->where(array("id"=>$currend_id))->field('user_status')->find();
			

            if($rg_user['rid'] > 0 && $rg_user_recommend['user_status'] == 1){
            	$rg_true 	= 0;
            	$count 		= $this->user->where(array('rid'=>$rg_user['rid']))->count('id');
                if($i<=3){
                    $rg_true = 1;
                }else if($i<=7 && $count>=2){
                	$rg_true = 1;
                }else if($i<=11 && $count>=4){
                	$rg_true = 1;
                }else if($count>=6){
               	 	$rg_true = 1;
                }
                if($rg_true){
               	 	$this->integrate->integrates($rg_user['rid'],'POINT','见点奖'.$i."代",$price,1,$rg_time, $id);
                }
            }else{
                break;
            }
        }
    }
    /** 报单中心 **/
    public function agen_award($id,$money,$rg_time){
         $rg_user 	= $this->user->where(array("id"=>$id))->field('biz_id,user_status')->find();
         $currend_id = $rg_user['biz_id'];
         $rg_user_recommend 	= $this->user->where(array("id"=>$currend_id))->field('user_status')->find();
         if($rg_user['biz_id'] > 0 && $rg_user_recommend['user_status'] == 1){
            $get_money = ($this->site_options['bdjl']/100) * $money;
            $this->integrate->integrates($rg_user['biz_id'],'CNETER','中心奖金',$get_money,1,$rg_time, $id);
         }
    }
    
	/** 领导级差奖 **/
    public function leader_award($id,$money,$rg_time){
        if(empty($id)) { return; }
		//当前用户id
		$rid 				= $id;//记录循环用户id
		$user_rand 			= 1;//当前用户的等级，用于用户等级对比，后面用户必须要比前面用户等级大
		$user_rand_price 	= 0;//当前已经拿到那个比例记录
		do {
		 	$rid = $this->user->where(array("id"=>$rid))->getField('pid');
		 	if($rid){
		 		$rg_user = $this->user->where(array("id"=>$rid))->field('id,user_status,rand')->find();
			 	if( $rg_user['user_status'] == 1 && $rg_user['rand'] > $user_rand ){
			 		$rand 	= $rg_user['rand'] ;
			 		$scale 	= $this->site_options['jl_'.$rand];	//用户当前等级可以拿到的比例
			 		$can_get_scale 	= $scale - $user_rand_price;	//可以拿到的用户极差比例
			 		$user_price 	= ($can_get_scale/100) * $money;//可以拿到的钱
			 		if($user_price > 0 ){
			 			$user_rand 			= $rand;//记录到那个等级
			 			$user_rand_price 	= $scale;//比例记录，用于极差比例对减
			 			$this->integrate->integrates($rid,'LEADER','领导级差奖',$user_price,1,$rg_time,$id);
			 		}
			 	}
		 	}
		} while($rid > 0);
    }
    
     /**@全国董事**/
	 public function country_director_award($id,$money,$rg_time){
	 	$country_director  = $this->site_options['qgyye']/100;
	 	if($country_director > 0){
	 		$can_get_money = $money * $country_director;
	 		$guser = $this->user->where(array("user_status"=>1,'rand'=>6))->field('id')->select();
	 		if($guser){
	 			$one_money = round($can_get_money/count($guser),2);
	 			if($one_money>0){
	 				foreach($guser as $val){
	 					$this->integrate->integrates($val['id'],'QGFH','全国董事分红',$one_money,1,$rg_time, $id);
	 				}
	 			}
	 		}
	 	}
	 }
	
    /**@see 每天分红包/ silence
     * @param $money
     */
    public function hongbao_award($money,$rg_time=null,$time=0){
        $where['user_status']	= 1;
        //获取符合条件的所有会员，已经的分红小于需要发的总额
        $where['hb_amount']		= array('exp',">`hb_amount2`");//hb_amount>hb_amount2
        if($time){ $where['create_time'] = array('lt',$time); }
        
        $get_all_users = $this->user->where($where)->field('id,audit_time,tz_num,hb_amount,hb_amount2,old_fbnum')->select();
		$hongbao = $this->site_options['hongbao'];
        if($get_all_users){
            foreach($get_all_users as $val){
            	$can_money = 0;
            	//判断这次加钱 后是不是已经大于会员单数需要分红的总额
                if ($val['hb_amount2'] + $money * $val['tz_num'] > $val['hb_amount']){
                	//剩余多少没发
                    $can_money = $val['hb_amount'] - $val['hb_amount2'];
                }else{
            		//分红的钱乘于单数
			        $yes_num = 0;
			        $old_fbamount = $val['old_fbnum']*$this->site_options['hongbao'];
			        $award_amount = M('award_table')->where( array( 'types'=>'DayHongBao', 'addtime'=>array('gt', $val['audit_time']) ) )->sum('amount');
			        if($award_amount>$old_fbamount){
			            $yes_num += $val['old_fbnum'];
			        }
			        $lists = M('readd')->where( array("user_id"=>$val['id']) )->order("id asc")->select();
			        foreach ($lists as $_v) {
			            $readd_dznum = $_v['money']/$this->site_options['readd'];

			            $old_fbamount = $readd_dznum*$this->site_options['hongbao'];
			            $award_amount = M('award_table')->where( array( 'types'=>'DayHongBao', 'addtime'=>array('gt', date('Y-m-d',$_v['add_times'])) ) )->sum('amount');
			            if($award_amount>$old_fbamount){
			                $yes_num += $readd_dznum;
			            }
			        }
            		$return_money_number = $val['tz_num'] - $yes_num;
            		if( $return_money_number > 0 ){ $can_money = $money * $return_money_number; }
                }
                
				if($can_money>0){
                	//更新会员已经拿到分红的总额
	                $update_money['hb_amount2'] = $val['hb_amount2'] + $can_money;

					//更新数据
	                if( $this->user->where( array('id'=>$val['id']) )->setField($update_money) ) {
	                	//发奖金
	                    $this->integrate->integrates($val['id'], 'hongbao', '每天分红包', $can_money, 1, $rg_time);
	                }
                }
           }
        }
    }
    
    private function user_update_rand($user_id,$rand){
	 	$ruser_rand = $this->user->where(array("id"=>$user_id,'user_status'=>1))->getField('rand');
	 	if($ruser_rand && $ruser_rand < $rand){
	 		return $this->user->where('id='.$user_id)->setField('rand',$rand);
	 	}
	 }
	 
	 
	 /**
	 * @see 用户升级
	 * @param $id 用户id，$money 购物的钱
	 */
	 public function user_update($id,$money,$rg_time=null){
	 	/*
	 	while ( $id > 0 ) {
	 		$users = $this->user->where(array("id"=>$id))->field('id,rid,user_status,rid_code')->find();
	 		if($users){
 				//团队业绩
 				$rid_code 	= $users['rid_code'].$users['id'];
                $tz_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%")) )->sum('tz_num');
                if(600*$tz_num >= 150000 ){ $this->user_update_rand($id, 2); }//主任

                $fl_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>1, 'rand'=>2) )->count();
                $fr_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>2, 'rand'=>2) )->count();
		 		if( $fl_num + $fr_num > 1 ) { $this->user_update_rand($id, 3); } //经理

                $fl_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>1, 'rand'=>3) )->count();
                $fr_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>2, 'rand'=>3) )->count();
		 		if( $fl_num + $fr_num > 1 ) { $this->user_update_rand($id, 4); } //总监

                $fl_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>1, 'rand'=>4) )->count();
                $fr_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>2, 'rand'=>4) )->count();
		 		if( $fl_num + $fr_num > 1 ) { $this->user_update_rand($id, 5); } //董事

                $fl_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>1, 'rand'=>5) )->count();
                $fr_num 	= $this->user->where( array('rid_code'=>array('like', $rid_code."|%"), 'area'=>2, 'rand'=>5) )->count();
		 		//if( $fl_num + $fr_num > 1 ) { $this->user_update_rand($id, 6); } //全国董事
		 	}
		 	$id = $users['rid'];
		}*/
	 }
}
