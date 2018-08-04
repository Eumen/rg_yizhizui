<?php
namespace Api\Controller;
use Common\Controller\HomeBaseController;
class TestController extends HomeBaseController {

	public function fsmember(){exit;
		$datas = M('fsmember')->where("id>8495")->order("id asc")->limit(150)->select();
		foreach($datas as $_v){
			$puser_code = M('Users')->where( array('id'=>$_v['fatherid']) )->getField('pid_code');
        	$pid_code   = empty($puser_code)?'p0|':$puser_code.$_v['fatherid']."|";

        	$ruser_code = M("Users")->where( array('id'=>$_v['reid']) )->getField('rid_code');
        	$rid_code   = empty($ruser_code)?'r0|':$ruser_code.$_v['reid']."|";

        	$create_time = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['rdt']));
        	$audit_time  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['pdt']));
        	$login_time  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['logintime']));

			$username = M('fsmemberbank')->where( array('uid'=>$_v['id']) )->order("id desc")->getField('username');
			$bankname = M('fsmemberbank')->where( array('uid'=>$_v['id']) )->order("id desc")->getField('bankname');
			$bankcard = M('fsmemberbank')->where( array('uid'=>$_v['id']) )->order("id desc")->getField('bankcard');
			$bankaddress = M('fsmemberbank')->where( array('uid'=>$_v['id']) )->order("id desc")->getField('bankaddress');

			$data = array(
				'id'				=> $_v['id'],
				'user_login'		=> $_v['nickname'],
				'user_email'		=> $_v['nickname'].'@jlh198.com',
				'user_pass'			=> sp_password($_v['password1']),
				'user_pass2'		=> sp_password($_v['password2']),
				'user_pass3'		=> sp_password($_v['password3']),
				'user_nicename' 	=> $_v['username'],
				'last_login_ip' 	=> get_client_ip(),
				'last_login_time'	=> $login_time,
				'create_time'		=> $create_time,
				'user_status'		=> !$_v['islock']?1:0,
				"user_type"			=> 2,
            	"rand"              => $_v['ulevel'],
				"rid"				=> $_v['reid'],
				"pid"				=> $_v['fatherid'],
	            "biz_id"			=> $_v['bdid'],
	            "area"				=> 0,
	            "amount"			=> $_v['mey'],
	            "e_amount"			=> $_v['zzb'],
	            "shop_amount"		=> $_v['dzb'],
				'is_agent'	 		=> $_v['isbd']==2?1:0,
				'audit_id'			=> $_v['bdid'],
				'audit_time'		=> $audit_time,
				'rid_counts' 		=> $_v['recount'],
				'add_user_id' 		=> $_v['reid'],
	            "pid_code"          => $pid_code,
				"rid_code"	    	=> $rid_code,
	            "tz_num"			=> $_v['dan'],
	            "agent"				=> $_v['isdaili'],
	            "hb_amount"			=> $this->site_options['hongbao'] * $_v['dan'],
			);
			M("Users")->add($data);

			$rg_data = array(
				'user_id'		=> $_v['id'],
				'true_name'		=> $_v['username'],
				'identity_id'	=> $_v['user_code']?$_v['user_code']:'无',
				'account_type'	=> $bankname?$bankname:'无',
				'account_no'	=> $bankcard?$bankcard:'无',
				'account_name'	=> $username?$username:'无',
				'account_info'	=> $bankaddress?$bankaddress:'无',
				'tel'			=> $_v['usertel'],
				'address'		=> $_v['sheng'].$_v['shi'].$_v['xian'].$_v['useraddress'],
				'info'			=> '无',
			);
			M("UserInfos")->add($rg_data);
		}
    }

    function fsmenm_save(){
		$datas = M('fsmember')->field('nickname,ulevel')->select();
		foreach($datas as $_v){
			M("Users")->where(array('user_login'=>$_v['nickname']))->save(array('rand'=>$_v['ulevel']));
    	}
    	die('ok');
    }


    function fsusers_save(){exit;
		$datas = M('fsusers2')->select();
		foreach($datas as $_v){
			if($_v['fbnum']) M("Users")->where(array('user_login'=>$_v['nickname']))->save(array('tz_num'=>$_v['fbnum'],'hb_amount'=>760*$_v['fbnum'],'hb_amount2'=>0,'old_fbnum'=>$_v['dan']));
    	}
    	die('ok');
    }

    function fshb_save(){exit;
		$datas = M('incomes')->where(array('types'=>'hongbao','addtime'=>array('gt','2018-05-11')))->order('id desc')->select();
		foreach($datas as $_v){
			$amout 	= $_v['amount'];
			$user 	= M("Users")->where( array('id'=>$_v['user_id']) )->find();

			$manager_amout =  $amout * 0.1;
			$money = $amout - $manager_amout;

			$update_money['amount']     = $user['amount']      - ($money*0.6);
            $update_money['e_amount']   = $user['e_amount']    - ($money*0.4);
            $update_money['shop_amount']= $user['shop_amount'] - ($money*0);

            M("Users")->where( array('id'=>$_v['user_id']) )->setField($update_money);

            M('incomes')->where(array('id'=>$_v['id']))->delete();
            M('incomes')->where(array('user_id'=>$_v['user_id'],'addtime'=>$_v['addtime'],'createtime'=>$_v['createtime'],'types'=>'MANAGER'))->delete();
    	}
    	die('ok');
    }

    function fsfb_save(){exit;
        $rg_time = array('addtime'=>'2018-05-14', 'createtime'=>'00:10:00');

		$this->award = new \Common\Lib\Award();
        $r1[] = $this->award->hongbao_award(2.32,$rg_time,'2018-05-14');
        echo("发放成功");
    }

    function fsmember_save(){exit;
		$datas = M('fsmember')->field("id,isdaili")->order("id asc")->select();
		foreach($datas as $_v){
			M("Users")->where(array('id'=>$_v['id']))->save(array('agent'=>$_v['isdaili']));
    	}
    	die('ok');
    }

    function fszhuanhuan(){exit;
		$rg_arr = array('amount' => '奖金红包', 'shop_amount' => '电子红包', 'good_amount' => '消费红包');

		$datas = M('fszhuanhuan')->where("id>0")->order("id asc")->limit(150)->select();
		foreach($datas as $_v){
        	$addtime  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['zdate']));

			$data = array(
	            "old_id"          	=> $_v['id'],
	            "user_id"          	=> $_v['uid'],
	            "types"          	=> $rg_arr['amount'],
	            "totypes"          	=> $rg_arr['shop_amount'],
	            "addtime"          	=> $addtime,
	            "amount"          	=> $_v['jine'],
	            "fee"          		=> 0,
	            "shop_amount"       => round($_v['jine'] * 0.85, 2),
	            "good_amount"       => round($_v['jine'] * 0.15, 2),
	            "status"      	 	=> 1,
			);
			M("convert_money_lists")->add($data);
    	}
    	die('ok');
    }

    function fszhuanzhang(){exit;
		$datas = M('fszhuanzhang')->order("id asc")->select();
		foreach($datas as $_v){
			$data = array(
	            "old_id"		=> $_v['id'],
	            "user_id"		=> $_v['uid'],
	            "to_user_id"	=> $_v['sid'],
	            "amount"		=> $_v['jine'],
	            "reason"		=> $_v['beizhu'],
	            "status"		=> 1,
	            "bili"       	=> 0,
	            "type"       	=> 'shop_amount',
			);
			M("change_money_lists")->add($data);
    	}
    	die('ok');
    }

    function fszhuanzhang_save(){exit;
		$datas = M('fszhuanzhang')->where("id>1656")->order("id asc")->select();
		foreach($datas as $_v){
        	$addtime  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['zdate']));

			M("change_money_lists")->where(array('old_id'=>$_v['id']))->save(array('addtime'=>$addtime));
    	}
    	die('ok');
    }

    function fschongzhi(){exit;
		$datas = M('fschongzhi')->order("id asc")->select();
		foreach($datas as $_v){
			$data = array(
	            "old_id"		=> $_v['id'],
	            "user_id"		=> $_v['uid'],
	            "types"			=> 'CHARGE',
	            "amount"		=> $_v['jine'],
	            "reason"		=> $_v['beizhu'],
	            "admin_id"		=> 1,
	            "status"       	=> 1,
			);
			M("charges")->add($data);
    	}
    	die('ok');
    }

    function fschongzhi_save(){exit;
		$datas = M('fschongzhi')->order("id asc")->select();
		foreach($datas as $_v){
        	$addtime  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['cdate']));

			M("charges")->where(array('old_id'=>$_v['id']))->save(array('addtime'=>$addtime));
    	}
    	die('ok');
    }

    function fstixian(){exit;
		$datas = M('fstixian')->where("id>0")->order("id asc")->select();
		foreach($datas as $_v){
			$data = array(
	            "old_id"			=> $_v['id'],
	            "user_id"			=> $_v['uid'],
	            "amount"			=> $_v['jine'],
	            "money"				=> $_v['jine']-$_v['shouxu'],
	            "memo"				=> '',
	            "status"			=> 1,
	            "types"       		=> 'amount',
	            "bank_type"       	=> $_v['bankname'],
	            "bank_number"		=> $_v['bankcard'],
	            "bank_user_name"	=> $_v['username'],
	            "bank_adree"       	=> $_v['bankaddress'],
			);
			M("mentions")->add($data);
    	}
    	die('ok');
    }

    function fstixian_save(){exit;
		$datas = M('fstixian')->where("id>0")->order("id asc")->select();
		foreach($datas as $_v){
        	$status  = $_v['ispay']==2?3:$_v['ispay'];

			M("mentions")->where(array('old_id'=>$_v['id']))->save(array('status'=>$status));
    	}
    	die('ok');
    }

    function fsbonusly(){exit;
		$datas = M('fsbonusly')->where("id>572628")->order("id asc")->limit(30000)->select();
		foreach($datas as $_v){
			if($_v['lx']==1){
				$types = 'hongbao';
			}elseif($_v['lx']==2){
				$types = 'RID';
			}elseif($_v['lx']==3){
				$types = 'POINT';
			}elseif($_v['lx']==4){
				$types = 'LEADER';
			}elseif($_v['lx']==6){
				$types = 'CNETER';
			}elseif($_v['lx']==15){
				$types = 'zanliu';
			}

			$data = array(
	            "old_id"		=> $_v['id'],
	            "user_id"		=> $_v['uid'],
	            "types"			=> $types,
	            "amount"		=> $_v['jine'],
	            "reason"		=> $_v['beizhu'],
	            "pay_uid"		=> $_v['yid'],
	            "status"		=> 1,
			);
			M("incomes")->add($data);
    	}
    	die('ok');
    }

    function fsbonusly_save(){
		$datas = M('fsbonusly')->alias("a")->join(C ( 'DB_PREFIX' )."incomes b ON b.old_id=a.id")->field("a.id,a.ydate")->limit(10000)->order("b.comptime asc, a.id asc")->select();
		foreach($datas as $_v){
        	$addtime  = file_get_contents("http://emptest.bgyhnddr.com/time/?date=".str_replace(' ','%20',$_v['ydate']));

			M("incomes")->where(array('old_id'=>$_v['id']))->save(array('addtime'=>date('Y-m-d', strtotime($addtime)), 'createtime'=>date('H:i:s', strtotime($addtime)), 'comptime'=>$addtime));
    	}
    	die('ok');
    }
}