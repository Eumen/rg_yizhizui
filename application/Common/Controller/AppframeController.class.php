<?php
namespace Common\Controller;
use Think\Controller;

class AppframeController extends Controller {
    protected static $site_options, $wx_config, $weObj, $wx_openid, $wx_share;

    function _initialize() {
        $this->assign("waitSecond", 3);
        $time=time();
        $this->assign("js_debug",APP_DEBUG?"?v=$time":"");
        
        if(APP_DEBUG){ sp_clear_cache(); }

        if(I('get.recId')) cookie('rg_user_rid', I('get.recId'), 86400*7);

        $site_options = get_site_options();
        $this->site_options = $site_options;
        $this->assign($this->site_options);

        $this->wx_config    = M()->table('weixin_config')->where(array('id'=>1))->find();
        $this->assign('wx_config', $this->wx_config);

        import('CoreLibWechat');
        $this->weObj        = new \CoreLibWechat($this->wx_config);

        $fs_key = md5($this->wx_config['appid']."-".$this->wx_config['appsecret']);
        
        $cache_file     = "data/rg_cache/cache_token_".$fs_key.".php";
        $cache_token    = include $cache_file;
        if(!file_exists( $cache_file ) || ($cache_token['cache_time'] + 600 < time())){
            $access_token = $this->weObj->checkAuth('','','',0);
            if( $access_token ){ file_put_contents($cache_file, "<?php\treturn " . var_export(array('access_token'=>$access_token, 'cache_time'=>time()), true) . ";?>"); }
            $cache_token = include $cache_file;
        }

        $cache_file         = "data/rg_cache/cache_ticket_".$fs_key.".php";
        $cache_ticket   = include $cache_file;
        if(!file_exists( $cache_file ) || ($cache_ticket['cache_time'] + 600 < time())){
            $get_js_api_ticket = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$cache_token['access_token']}";
            $content = json_decode(get_html($get_js_api_ticket), true);
            if( $content['ticket'] ){ file_put_contents($cache_file, "<?php\treturn " . var_export(array('js_api_ticket'=>$content['ticket'], 'cache_time'=>time()), true) . ";?>"); }
        }
        
        $this->wx_openid        = cookie('wx_openid');

        if( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') && !IS_POST && !in_array(MODULE_NAME, array('Pay')) && !isset($_REQUEST['timestamp'])  && FALSE){
            $wx_count = M()->table('weixin_user')->where(array('fake_id'=>$this->wx_openid))->count();
            if( !$wx_count ){
                cookie('wx_openid', null);
                $this->wx_openid = '';
            }

            if($_GET['code'] && !$this->wx_openid){
                $wx_content         = $this->weObj->getOauthAccessToken();
                $this->wx_openid    = $wx_content['openid'];
                cookie('wx_openid', $this->wx_openid, 3600);
            }
            if(!$this->wx_openid){
                $url = $this->weObj->getOauthRedirect('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                header("Location:$url");exit;
            }

            $followInfo = M()->table('weixin_user')->where(array('fake_id'=>$this->wx_openid))->find();
            if( (!empty($followInfo)  or $followInfo['expire_in'] - 86400 < time()) && $this->wx_openid ){
                $wx_info        = $this->weObj->getOauthUserinfo( $wx_content['access_token'], $this->wx_openid );
                $wx_userinfo    = $this->weObj->getUserInfo( $this->wx_openid );

                $wx_info    = !empty($wx_info) ? $wx_info : $wx_userinfo;
                if(empty($wx_info)){
                    cookie('wx_openid', null);
                    $this->wx_openid = '';
                }
                
                $wx_info['subscribe']   = $wx_userinfo['subscribe'];

                D("Common/Users")->followUser($this->wx_openid, $wx_info);
            }

            $this->wx_share     = get_jssdk_config($this->wx_config);
            $wx_share_other['share_title']    = $this->site_options['site_name'];
            $wx_share_other['share_desc']     = str_replace(array("\r\n", "\r", "\n"), " ", $this->site_options['site_seo_description']);
            $wx_share_other['share_imgUrl']   = $this->site_options['site_host'].'/statics/images/logo_share.jpg';
            $wx_share = array_merge($this->wx_share, $wx_share_other);
            $this->assign("wx_share",   $wx_share);

            $this->assign("iswei",      1);
        }
    }

    //获取表单令牌
    protected function getToken() {
        $tokenName = C('TOKEN_NAME');
        // 标识当前页面唯一性
        $tokenKey = md5($_SERVER['REQUEST_URI']);
        $tokenAray = session($tokenName);
        //获取令牌
        $tokenValue = $tokenAray[$tokenKey];
        return $tokenKey . '_' . $tokenValue;
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @return void
     */
    protected function ajaxReturn($data, $type = '',$json_option=0) {
        
        $data['referer']=$data['url'] ? $data['url'] : "";
        $data['state']=$data['status'] ? "success" : "fail";
        
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
        	case 'JSON' :
        		// 返回JSON数据格式到客户端 包含状态信息
        		header('Content-Type:application/json; charset=utf-8');
        		exit(json_encode($data,$json_option));
        	case 'XML'  :
        		// 返回xml格式数据
        		header('Content-Type:text/xml; charset=utf-8');
        		exit(xml_encode($data));
        	case 'JSONP':
        		// 返回JSON数据格式到客户端 包含状态信息
        		header('Content-Type:application/json; charset=utf-8');
        		$handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
        		exit($handler.'('.json_encode($data,$json_option).');');
        	case 'EVAL' :
        		// 返回可执行的js脚本
        		header('Content-Type:text/html; charset=utf-8');
        		exit($data);
        	case 'AJAX_UPLOAD':
        		// 返回JSON数据格式到客户端 包含状态信息
        		header('Content-Type:text/html; charset=utf-8');
        		exit(json_encode($data,$json_option));
        	default :
        		// 用于扩展其他返回格式数据
        		Hook::listen('ajax_return',$data);
        }
        
    }



    
    //分页
    protected function page($Total_Size = 1, $Page_Size = 0, $Current_Page = 1, $listRows = 6, $PageParam = '', $PageLink = '', $Static = FALSE) {
    	import('Page');
    	if ($Page_Size == 0) {
    		$Page_Size = C("PAGE_LISTROWS");
    	}
    	if (empty($PageParam)) {
    		$PageParam = C("VAR_PAGE");
    	}
    	$Page = new \Page($Total_Size, $Page_Size, $Current_Page, $listRows, $PageParam, $PageLink, $Static);
    	$Page->SetPager('default', '{first}{prev}{liststart}{list}{listend}{next}{last}', array("listlong" => "9", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
    	return $Page;
    }


    /**
     * 验证码验证
     * @param type $verify 验证码
     * @param type $type 验证码类型
     * @return boolean
     */
    static public function verify($verify, $type = "verify") {
        $verifyArr = session("_verify_");
        if (!is_array($verifyArr)) {
            $verifyArr = array();
        }
        if ($verifyArr[$type] == strtolower($verify)) {
            unset($verifyArr[$type]);
            if (!$verifyArr) {
                $verifyArr = array();
            }
            session('_verify_', $verifyArr);
            return true;
        } else {
            return false;
        }
    }

    //空操作
    public function _empty() {
        $this->error('该页面不存在！');
    }
    
    /**
     * 检查操作频率
     * @param int $duration 距离最后一次操作的时长
     */
    protected function check_last_action($duration){
    	
    	$action=MODULE_NAME."-".CONTROLLER_NAME."-".ACTION_NAME;
    	$time=time();
    	
    	if(!empty($_SESSION['last_action']['action']) && $action==$_SESSION['last_action']['action']){
    		$mduration=$time-$_SESSION['last_action']['time'];
    		if($duration>$mduration){
    			$this->error("您的操作太过频繁，请稍后再试~~~");
    		}else{
    			$_SESSION['last_action']['time']=$time;
    		}
    	}else{
    		$_SESSION['last_action']['action']=$action;
    		$_SESSION['last_action']['time']=$time;
    	}
    }

}