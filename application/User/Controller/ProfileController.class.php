<?php
/** 会员中心 */
namespace User\Controller;
use Common\Controller\MemberbaseController;
class ProfileController extends MemberbaseController {
	protected $rid_user_arr;
	protected $users_model, $userinfos_model, $incomes_model;
	function _initialize(){
		parent::_initialize();
		$this->users_model		= D("Common/Users");
		$this->userinfos_model	= D("Common/UserInfos");
		$this->incomes_model	= D("Common/Incomes");
		$this->uid = sp_get_current_userid();
		$this->assign("rg_on", 4);
	}

    public function myresults(){
        $rid_user = $this->get_rid_user( $this->uid );
        
        foreach($rid_user as $_v){
            $rg_data["id"] = $_v;
            $rg_data["user_login"] = $this->users_model->where( array('id'=>$_v) )->getField('user_login');
            $rg_data["rand"] = $this->users_model->where( array('id'=>$_v) )->getField('rand');
            $rg_data["amount"] = $this->users_model->where( array('id'=>$_v) )->getField('amount');
            $rg_data["shopping_number"] = $this->users_model->where( array('id'=>$_v) )->getField('shopping_number');

            $rg_list[] = $rg_data;
        }
        $this->assign("list", $rg_list);

        $this->display();
    }

    public function myaccount(){
        $this->check_pass2();

        $userid = sp_get_current_userid();
        $user_data = $this->users_model->where(array("id"=>$userid))->field("audit_time,pid_code,tz_num,hb_amount,hb_amount2,old_fbnum")->find();

        $condition['pid_code']  = array("like", $user_data['pid_code'].$userid."|%");
        $condition['status']    = array("eq", 1);
        $condition['user_type'] = array("eq", 2);
        $condition['area']      = array("eq", 1);
        $datas['area1_num'] = $this->users_model->where($condition)->sum('tz_num');
        $condition['area']      = array("eq", 2);
        $datas['area2_num'] = $this->users_model->where($condition)->sum('tz_num');

        $yes_num = 0;
        $old_fbamount = $user_data['old_fbnum']*$this->site_options['hongbao'];
        $award_amount = M('award_table')->where( array( 'types'=>'DayHongBao', 'addtime'=>array('gt', $user_data['audit_time']) ) )->sum('amount');
        if($award_amount>$old_fbamount){
            $yes_num += $user_data['old_fbnum'];
        }
        $lists = M('readd')->where( array("user_id"=>$userid) )->order("id asc")->select();
        foreach ($lists as $_v) {
            $readd_dznum = $_v['money']/$this->site_options['readd'];

            $old_fbamount = $readd_dznum*$this->site_options['hongbao'];
            $award_amount = M('award_table')->where( array( 'types'=>'DayHongBao', 'addtime'=>array('gt', date('Y-m-d',$_v['add_times'])) ) )->sum('amount');
            if($award_amount>$old_fbamount){
                $yes_num += $readd_dznum;
            }
        }

        $is_readd = M('readd')->where( array("user_id"=>$userid, 'types'=>'e_amount') )->sum("money");
        $datas['money']     = $user_data['tz_num']*$this->site_options['readd']-$is_readd;
        $datas['yes_num']   = $yes_num;
        $datas['no_num']    = $user_data['tz_num']-$yes_num;

        $rand = $this->users_model->where(array("id"=>$userid))->getField("rand");
        $datas['news_rank']   = M('rand_price')->where(array('rank_mark'=>$rand))->getField("name");
        $datas['next_rank']   = M('rand_price')->where(array('rank_mark'=>($rand<6?$rand+1:6)))->getField("name");

        $this->assign("datas", $datas);

        $this->display();
    }

    function myqrcode(){
        $this->check_pass2();
        
        $fqid   = intval($_GET['id']) ? intval($_GET['id']) : $this->uid;

        $rg_user = M("Users")->where(array('id'=>$fqid))->find();
        if(empty($rg_user)) $this->error('亲！该注册会员ID不存在！');

        $rg_userinfo = M("UserInfos")->where(array('user_id'=>$fqid))->find();

        if( $fqid>0 ) $wx_user = M()->table('weixin_user')->where(array('ecuid'=>$fqid))->find();

        $qrcode = DownLoadQr('http://s.jiathis.com/qrcode.php?url='.urlencode($this->site_options['site_host']."index.php?recId=".$rg_user['user_login']), $fqid);

        $image  = new \Cls_image ();
        
        $image->make_thumb("$qrcode", 220, 220, "data/qrcode/$fqid/", '', 'qrcode_1');//缩略图
        $image->add_watermark("data/qrcode/$fqid/qrcode_1.jpg", "data/qrcode/$fqid/qrcode_2.jpg", "data/qrcode/qrcode_logo.jpg", 3, 100, 0);//加logo
        $image->add_watermark("data/qrcode/qrcode_index.jpg", "data/qrcode/$fqid/index.jpg", "data/qrcode/$fqid/qrcode_2.jpg", 3, 100, 386, -192);//加二维码
        if(!empty($rg_userinfo['tel'])) $image->add_watermark("data/qrcode/$fqid/index.jpg", '', '', 3, 100, 0, 0, '财富热线：'.$rg_userinfo['tel'], 22, 340, 1050);//加文字

        if(!empty($wx_user)){
            put_file_from_url_content ( $wx_user['headimgurl'], 'user.jpg', "data/qrcode/$fqid/" );//制作头像
            $image->make_thumb("data/qrcode/$fqid/user.jpg", 100, 100, "data/qrcode/$fqid/", '', 'avatar');//缩略图
            $image->add_watermark("data/qrcode/$fqid/index.jpg", '', "data/qrcode/$fqid/avatar.jpg", 1, 100, 890, 330);//加头像
            $image->add_watermark("data/qrcode/$fqid/index.jpg", '', '', 3, 100, 0, 0, "我是".$wx_user['nickname'], 22, 450, 930);//加文字
            $image->add_watermark("data/qrcode/$fqid/index.jpg", '', '', 3, 100, 0, 0, "我为沃之丰代言", 22, 450, 975);//加文字
        }else{
            $image->add_watermark("data/qrcode/$fqid/index.jpg", '', '', 3, 100, 0, 0, "我是".(!empty($rg_userinfo['true_name'])?$rg_userinfo['true_name']:$rg_user['user_login']), 22, 340, 930);
            $image->add_watermark("data/qrcode/$fqid/index.jpg", '', '', 3, 100, 0, 0, "我为沃之丰代言", 22, 340, 975);//加文字
        }

        $slide_pic = M('slide')->where("slide_cid=4")->order("slide_id desc")->getField('slide_pic');
        $slide_pic = sp_get_asset_upload_path($slide_pic);  
        $image->add_watermark("data/qrcode/$fqid/index.jpg", '', substr($slide_pic,1), 1, 100, 0, 0);//加广告

        $this->assign('qrcode', $this->site_options['site_host']."data/qrcode/$fqid/index.jpg");

        $wx_share_other['share_title']      = $wx_user['nickname']."为广西易之最代言";
        $wx_share_other['share_desc']       = $this->site_options['ico_link'];
        $wx_share_other['share_imgUrl']     = $qrcode;
        $wx_share_other['share_link']       = U('user/qrcode', array('id'=>$fqid));
        $wx_share = array_merge($this->wx_share, $wx_share_other);
        $this->assign("wx_share",   $wx_share);

        $this->display();
    }

	function get_rid_user($uid){
		$rid_user = $this->users_model->where( array('rid'=>$uid) )->getField('id',true);
		foreach( $rid_user as $_k=>$_v ){
			$this->rid_user_arr[] = $_v;
			$this->get_rid_user( $_v );
		}
		
		return $this->rid_user_arr;
	}
	
    //编辑用户资料
	public function edit() {
        $this->check_pass2();
        
		$userid = sp_get_current_userid();
		$this->assign('info',M("UserInfos")->where(array("user_id"=>$userid))->find());
		$this->assign($this->users_model->where(array("id"=>$userid))->find());
        $this->display();
    }
    
    public function edit_post() {
		if(IS_POST){
			/**$_POST['id']=$userid;
			if ($this->users_model->create()) {
				unset($_POST['user_login']);
				if ($this->users_model->save()!==false) {
					$user=$this->users_model->find($userid);
					sp_update_current_user($user);
				}
			} **/
			//用户资料修改
			unset($_POST['id']);
			if (M("UserInfos")->create()) {
	    			M("UserInfos")->where("user_id=".$this->uid)->save();
                $this->success("保存成功！",U("user/profile/edit"));
			} else {
				$this->error($this->users_model->getError());
			}
		}
    }
    
    public function check_user_info(){
    		$info = M("UserInfos")->where("user_id=".$this->uid)->find();
    		if(empty($info['true_name']) || empty($info['identity_id']) || empty($info['tel'])){
    			$this->ajaxReturn(array("status"=>1));
    		}else{
    			$this->ajaxReturn(array("status"=>0));
    		}
    }
    
    public function password() {
        $this->check_pass2();
    
    	$userid=sp_get_current_userid();
    	$user=$this->users_model->where(array("id"=>$userid))->find();
    	$this->assign($user);
    	$this->display();
    }
    
    public function password_post() {
        $users_model=M("Users");
        $rules = array(
                //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
                array('old_password', 'require', '原始密码不能为空！', 1 ),
                array('password','require','密码不能为空！',1),
                array('repassword', 'require', '重复密码不能为空！', 1 ),
                array('repassword','password','确认密码不正确',0,'confirm'),
                array('user_pass2', 'require', '二级密码不能为空！', 1 ),
                array('user_pass3', 'require', '三级密码不能为空！', 1 ),
        );
        if($users_model->validate($rules)->create()===false) $this->error($users_model->getError()); 

        extract($_POST);

        if(strlen($password) < 5 || strlen($password) > 20) $this->error("密码长度至少5位，最多20位！"); 
        if(strlen($user_pass2) < 5 || strlen($user_pass2) > 20) $this->error("二级密码长度至少5位，最多20位！"); 
        if(strlen($user_pass3) < 5 || strlen($user_pass3) > 20) $this->error("三级密码长度至少5位，最多20位！"); 

    	if (IS_POST) {
    		$uid=sp_get_current_userid();
    		$fs_user=$this->users_model->where("id=$uid")->find();
    		if(sp_password($old_password)==$fs_user['user_pass']){
				if($fs_user['user_pass']==sp_password($password)){
					$this->error("新密码不能和原始密码相同！");
				}else{
                    $data['user_pass']=sp_password($password);
                    $data['user_pass2']=sp_password($user_pass2);
                    $data['user_pass3']=sp_password($user_pass3);
					$data['id']=$uid;
					$r=$this->users_model->save($data);
					if ($r!==false) {
						$this->success("修改成功！");
					} else {
						$this->error("修改失败！");
					}
				}
    		}else{
    			$this->error("原始密码不正确！");
    		}
    	}
    }
    
    
    function avatar(){
        $this->check_pass2();
        
    	$userid=sp_get_current_userid();
		$user=$this->users_model->where(array("id"=>$userid))->find();
		$this->assign($user);
    	$this->display();
    }
    
    function avatar_upload(){
    	$config=array(
    			'FILE_UPLOAD_TYPE' => sp_is_sae()?"Sae":'Local',//TODO 其它存储类型暂不考虑
    			'rootPath'   => './'.C("UPLOADPATH"),
    			'savePath'   => './avatar/',
    			'maxSize'    => 512000,//500K
    			'saveName'   => array('uniqid',''),
    			'exts'       => array('jpg', 'png', 'jpeg'),
    			'autoSub'    => false,
    	);
    	$upload = new \Think\Upload($config);//
    	$info=$upload->upload();
    	//开始上传
    	if ($info) {
    	//上传成功
    	//写入附件数据库信息
    		$first=array_shift($info);
            if(!empty($first['url'])){
                $url=$first['url'];
            }else{
                $url=C("TMPL_PARSE_STRING.__UPLOAD__").'avatar/'.$first['savename'];
            }
            echo json_encode(array("error"=>"0","pic"=>$url,"name"=>$first['name'])); 
            exit;
    	} else {
            //上传失败，返回错误
            echo json_encode(array("error"=>"上传有误，清检查服务器配置！")); 
            exit;
    	}
    }
    
    function avatar_update(){
        if(IS_POST){
            if(!empty($_POST['photos'])){
                foreach ($_POST['photos'] as $key=>$url){
                    $photourl=sp_asset_relative_url($url);
                    $smeta['photo'][]=array("url"=>$photourl);
                }
            }
            if(count($smeta['photo'])>1) $this->error("照片文件最多上传一个");

            $uid = sp_get_current_userid();
            $result=$this->users_model->where(array("id"=>$uid))->save(array("avatar"=>json_encode($smeta)));
            if($result){
                $this->success("上传成功！");
            } else {
                $this->error("上传失败！");
            }
        }
    }
    
    
}
    
