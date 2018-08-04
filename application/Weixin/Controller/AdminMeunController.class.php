<?php

namespace Weixin\Controller;
use Common\Controller\AdminbaseController;
class AdminMeunController extends AdminbaseController {
	protected $weixin_menu_model;
	
	function _initialize() {
		parent::_initialize();
		$this->weixin_menu_model		= D("Weixin/weixin_menu");
	}
	
	function other_meun(){
		if($this->station_id == 1) $this->error("不能更新该菜单");

		$button = $this->set_button_con();
		//$this->error( var_export($button, true) );

		$res = $this->weObj->createMenu(array('button'=>$button));
		if($res === false){
			$this->error("更新菜单出错：".$this->weObj->errMsg);
		}else{
			$this->success("更新成功！");
		}
	}


	function set_button_con(){
		$station_user = M('Users')->where(array('user_status'=>1, 'user_type'=>1,'station_id'=>$this->station_id))->order(array('id'=>'asc'))->find();

		if($this->station_id ==6){
			$button = array(
				array(
					'name' => $station_user['user_nicename'] ? $station_user['user_nicename'] : '聚焦广西',
					'sub_button' => array(
						array('type'=>'view', 'name'=>'视频直播', 'url'=>$this->site_options['site_host'].U('Live/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'前往首页', 'url'=>$this->site_options['site_host'].U('Portal/Index/index',array('station_id'=>$this->station_id)))
					)
				),
				array(
					'type'	=> 'view',
					'name' 	=> '靖西佰事通',
					'url'   => $this->site_options['site_host'].U('Weixin/Reurl/index',array('station_id'=>$this->station_id))
				),
				array(
					'name' => '更多信息',
					'sub_button' => array(
						array('type'=>'view', 'name'=>'微社区', 'url'=>'https://shequ.yunzhijia.com/thirdapp/forum/network/5631a434e4b03e99e41d5c90?pcode=4hzq9c&pinvite=59c4b5aee4b075905c0cc5b2&shared=shared'),
						array('type'=>'view', 'name'=>'便民服务', 'url'=>'http://zquan.cc/0776jxbst/'),
						array('type'=>'view', 'name'=>'靖西黄页', 'url'=>'http://944294437.sh0001.com/index.php?g=Webapp&m=City114&a=index&vkey=944294437&cvkey=944294437'),
						array('type'=>'view', 'name'=>'靖西同城', 'url'=>'http://www.lvboyuan.club/Weixin/Home/Index?sourceId=dadd14d7-dd43-4eaf-8f31-3da406e8a218'),
						array('type'=>'view', 'name'=>'电视直播', 'url'=>'http://zb.zqseo.org.cn/m/')
					)
				)
			);
		}else{
			$button = array(
				array(
					'name' => $station_user['user_nicename'] ? $station_user['user_nicename'] : '聚焦广西',
					'sub_button' => array(
						array('type'=>'view', 'name'=>'视频直播', 'url'=>$this->site_options['site_host'].U('Live/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'微影展播', 'url'=>$this->site_options['site_host'].U('Demand/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'前往首页', 'url'=>$this->site_options['site_host'].U('Portal/Index/index',array('station_id'=>$this->station_id)))
					)
				),
				array(
					'name' => '聚焦栏目',
					'sub_button' => array(
						array('type'=>'view', 'name'=>'聚焦美食', 'url'=>$this->site_options['site_host'].U('Food/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'聚焦旅游', 'url'=>$this->site_options['site_host'].U('Tour/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'聚焦专访', 'url'=>$this->site_options['site_host'].U('Interview/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'多彩民族', 'url'=>$this->site_options['site_host'].U('National/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'今日快报', 'url'=>$this->site_options['site_host'].U('News/Index/index',array('station_id'=>$this->station_id)))
					)
				),
				array(
					'name' => '更多栏目',
					'sub_button' => array(
						array('type'=>'view', 'name'=>'VR直通车', 'url'=>$this->site_options['site_host'].U('Vrico/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'当地特产', 'url'=>$this->site_options['site_host'].U('Specialty/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'善商教育', 'url'=>$this->site_options['site_host'].U('Education/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'便民服务', 'url'=>$this->site_options['site_host'].U('Coser/Index/index',array('station_id'=>$this->station_id))),
						array('type'=>'view', 'name'=>'互联网+',  'url'=>$this->site_options['site_host'].U('Inter/Index/index',array('station_id'=>$this->station_id)))
					)
				)
			);
		}
		
		return $button;
	}
}