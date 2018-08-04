<?php

function slide_banner($type, $order='slide_id desc', $limit=5){
	$condition['slide_cid']		= $type;
	$condition['slide_status']	= 1;
	$slide_img = M('Slide')->where($condition)->field('slide_url,slide_pic,slide_name')->order($order)->limit($limit)->select();
	foreach ($slide_img as $k => $val) {
		if(!empty($val['slide_pic'])){
			$slide_img[$k]['slide_pic']= sp_get_asset_upload_path($val['slide_pic']);	
		}
	}
   return  $slide_img;
}