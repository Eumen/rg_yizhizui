<?php
namespace Common\Controller;
use Common\Controller\AppframeController;
class HomeBaseController extends AppframeController {
	
	public function __construct() {
		$this->set_action_success_error_tpl();
		parent::__construct();
	}
	
	function _initialize() {
		parent::_initialize();
		
		vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
		
		$ucenter_syn=C("UCENTER_ENABLED");
		if($ucenter_syn){
			if(!isset($_SESSION["user"])){
				if(!empty($_COOKIE['thinkcmf_auth'])  && $_COOKIE['thinkcmf_auth']!="logout"){
					$thinkcmf_auth=sp_authcode($_COOKIE['thinkcmf_auth'],"DECODE");
					$thinkcmf_auth=explode("\t", $thinkcmf_auth);
					$auth_username=$thinkcmf_auth[1];
					$users_model=M('Users');
					$where['user_login']=$auth_username;
					$user=$users_model->where($where)->find();
					if(!empty($user)){
						$is_login=true;
						$_SESSION["user"]=$user;
					}
				}
			}else{
			}
		}

        if($this->site_options['open_win'] && !$_SESSION["user"]['cardId20180428']){
            $this->error($this->site_options['open_win_con']);
        }

       // $this->error("亲，网络服务处于停止状态，请您检查网络服务期限是否到期，给您带来不便请谅解！");
		
		if(sp_is_user_login()){
			$uid = sp_get_current_userid();
			$rg_user = M('Users')->find( $uid );
			$wxheadimg = M()->table('weixin_user')->where(array('ecuid'=>$uid))->getField("headimgurl");
			$smeta = json_decode($rg_user['avatar'],true); $photo = $smeta['photo']; $avatar = sp_get_asset_upload_path($photo[0][url]);
			$rg_user['avatar'] = $rg_user['avatar']?$avatar:($wxheadimg?$wxheadimg:"tpl/simplebootx/plugins/images/users/varun.jpg");

			$this->assign("rg_user", $rg_user);
		}
		
	}
	
	protected function check_login(){
		if(!isset($_SESSION["user"])){
			$this->error('您还没有登录！',U("user/login/index"));
		}
	}
	
	protected function check_pass2(){
		if(!isset($_SESSION["user"]["pass2"])){
			$_SESSION["newurl"] = urlencode(get_url());
			$this->redirect('User/Center/user_pass2/');
		}
	}
	
	protected function  check_user(){
		if($_SESSION["user"]['user_status']==2){
			session("user", null);
			$this->error('此账号已经被禁止使用，请联系管理员！',U("user/login/index"));
		}
		
		if($_SESSION["user"]['user_status']==0){
			session("user", null);
			$this->error('您还没有激活账号，请激活后再使用！',U("user/login/index"));
		}
	}
		
	/**
	 * 加载模板和页面输出 可以返回输出内容
	 * @access public
	 * @param string $templateFile 模板文件名
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 * @param string $content 模板输出内容
	 * @return mixed
	 */
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
		//echo $this->parseTemplate($templateFile);
		parent::display($this->parseTemplate($templateFile), $charset, $contentType);
	}
	
	public function fetch($templateFile='',$content='',$prefix=''){
		return parent::fetch($this->parseTemplate($templateFile),$content,$prefix);
	}
	
	/**
	 * 自动定位模板文件
	 * @access protected
	 * @param string $template 模板文件规则
	 * @return string
	 */
	public function parseTemplate($template='') {
		
		$tmpl_path=C("SP_TMPL_PATH");
		// 获取当前主题名称
		$theme      =    C('SP_DEFAULT_THEME');
		if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
			$t = C('VAR_TEMPLATE');
			if (isset($_GET[$t])){
				$theme = $_GET[$t];
			}elseif(cookie('think_template')){
				$theme = cookie('think_template');
			}
			if(!file_exists($tmpl_path."/".$theme)){
				$theme  =   C('SP_DEFAULT_THEME');
			}
			cookie('think_template',$theme,864000);
		}
		
		if(C('MOBILE_TPL_ENABLED')){//开启手机模板支持
			if(sp_is_mobile()){
				if(file_exists($tmpl_path."/".$theme."_mobile")){
					$theme  =   $theme."_mobile";
				}
			}
		}
		
		
		
		
		C('SP_DEFAULT_THEME',$theme);
		
		$current_tmpl_path=$tmpl_path.$theme."/";
		// 获取当前主题的模版路径
		define('THEME_PATH', $current_tmpl_path);
		
		C("TMPL_PARSE_STRING.__TMPL__",__ROOT__."/".$current_tmpl_path);
		
		C('SP_VIEW_PATH',$tmpl_path);
		C('DEFAULT_THEME',$theme);
		
		if(is_file($template)) {
			return $template;
		}
		$depr       =   C('TMPL_FILE_DEPR');
		$template   =   str_replace(':', $depr, $template);
		
		// 获取当前模块
		$module   =  MODULE_NAME;
		if(strpos($template,'@')){ // 跨模块调用模版文件
			list($module,$template)  =   explode('@',$template);
		}
		
		
		// 分析模板文件规则
		if('' == $template) {
			// 如果模板文件名为空 按照默认规则定位
			$template = "/".CONTROLLER_NAME . $depr . ACTION_NAME;
		}elseif(false === strpos($template, '/')){
			$template = "/".CONTROLLER_NAME . $depr . $template;
		}
		
		$file=$current_tmpl_path.$module.$template.C('TMPL_TEMPLATE_SUFFIX');
		if(!is_file($file)) E(L('_TEMPLATE_NOT_EXIST_').':'.$file);
		return $file;
	}
	
	
	private function set_action_success_error_tpl(){
		$theme      =    C('SP_DEFAULT_THEME');
		if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
			if(cookie('think_template')){
				$theme = cookie('think_template');
			}
		}
		$tpl_path=C("SP_TMPL_PATH").$theme."/";
		$defaultjump=THINK_PATH.'Tpl/dispatch_jump.tpl';
		$action_success=$tpl_path.C("SP_TMPL_ACTION_SUCCESS").C("TMPL_TEMPLATE_SUFFIX");
		$action_error=$tpl_path.C("SP_TMPL_ACTION_ERROR").C("TMPL_TEMPLATE_SUFFIX");
		if(file_exists($action_success)){
			C("TMPL_ACTION_SUCCESS",$action_success);
		}else{
			C("TMPL_ACTION_SUCCESS",$defaultjump);
		}
		
		if(file_exists($action_error)){
			C("TMPL_ACTION_ERROR",$action_error);
		}else{
			C("TMPL_ACTION_ERROR",$defaultjump);
		}
	}
	
	
}