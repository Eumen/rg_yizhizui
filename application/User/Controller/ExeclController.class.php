<?php
namespace User\Controller;
use Common\Controller\AdminbaseController;
class ExeclController extends AdminbaseController {
	function _initialize() { parent::_initialize(); }
	
	public function upload_execl(){
		$upload = new \Think\Upload();
		$upload->maxSize   =     3145728 ;
	    $upload->exts      =     array('xls','xlsx');
	    $upload->savePath  =     "" ;
		$upload->rootPath  =     C("UPLOADPATH") . "execl/";
	    $info =  $upload->upload();
	    if(!$info) {
	   	 	$data['status']  = 0;
			$data['file_name'] = "上传失败！请检查您上传的文件格式";
	    }else{
	    		$data['file_name']  = $info['files']['savepath']."".$info['files']['savename'];
			$data['name']  = $info['files']['savename'];
			$data['status']  = 1;
	    }
		$this->ajaxReturn($data);
	}
	
	public function insert_into_data(){
		$get_file = trim(I('get_file_name'));
		if($get_file){
			$get_url = C("UPLOADPATH") . "execl/".$get_file; 
			$this->importExecl($get_url);
			$data['status']  = 1;
		 }else{
		 	$data['status']  = 0;
		 }
		$this->ajaxReturn($data);
	}
	
	/**
     +----------------------------------------------------------
     * Import Excel | 2016.01.14
     +----------------------------------------------------------
     * @param  $file   upload file $_FILES
     +----------------------------------------------------------
     * @return array   array("error","message")
     +----------------------------------------------------------     
     */   
    public function importExecl($file){
        if(!file_exists($file)){ return array("error"=>0,'message'=>'file not found!');  }
        vendor('PHPExcel.PHPExcel');
        try{
        		$_excelVersion = 'Excel5';
			if (strstr($file,".xlsx")) $_excelVersion = 'Excel2007';
			$objReader = \PHPExcel_IOFactory::createReader($_excelVersion); 
            $PHPReader = $objReader->load($file);
        }catch(Exception $e){}
        if(!isset($PHPReader)) return array("error"=>0,'message'=>'read error!');
		$objActSheet = $PHPReader->getActiveSheet ();
		$allRow = $objActSheet->getHighestRow ();
		$get_user_obj = D("Common/Users");
		$rand_price  = D("Common/RandPrice");
		$user_infos  = D("Common/user_infos");
		
		for($i = 3; $i <= $allRow; $i ++) {
				$username = trim($objActSheet->getCell ( 'A' . $i )->getValue());
				$username = trim($username);
				if(empty($username)){ continue; }
				if($get_user_obj->where(array('user_login'=>$username))->find()){ continue; }
						
				if ($username) {
					$true_name = trim($objActSheet->getCell ( 'B' . $i )->getValue ());
					$tel = trim($objActSheet->getCell ( 'C' . $i )->getValue ());
					$create_time = trim($objActSheet->getCell ( 'D' . $i )->getValue ());
					
					$rand_string = $objActSheet->getCell ( 'E' . $i )->getValue ();
					$user_rank = $rand_price->where(array('name'=>$rand_string))->getField('rank_mark');
					
					$partner = trim($objActSheet->getCell ( 'F' . $i )->getValue ());
					empty($partner) ? $partner = 0 : $partner = 1;
					
					$ylh_id = $objActSheet->getCell ( 'G' . $i )->getValue ();
					$ylh_tel = $objActSheet->getCell ( 'H' . $i )->getValue ();
					empty($ylh_tel) ? $ylh_tel = '无' : $ylh_tel = $ylh_tel;
					
					$rid_name = trim($objActSheet->getCell ( 'I' . $i )->getValue ());
					$get_rid = $get_user_obj->where(array('user_login'=>$rid_name))->getField('id');
					empty($get_rid) ? $rid = 3 : $rid = $get_rid;
					
					$user_data = array(
						'user_login'   => $username,
						'user_pass'	   => sp_password($username),
						'user_type'	   => 2,
						'create_time'  => date('Y-m-d H:i:s'),
						'user_nicename'=> $username,
						'rid'		   => $rid,
						'partner'	   =>$partner,
						'rand'		   =>$user_rank,
						'user_status'  =>1,
					);
					$get_user_id = M('users')->add($user_data);
					if($get_user_id){
						$info = array(
							'user_id'  =>$get_user_id,
							'true_name'=>$true_name,
							'tel'      =>$tel,
							'ylh_id' 	=>$ylh_id,
							'ylh_tel'	=>$ylh_tel
						);
						$user_infos->add($info);
					}
				}
			}//end
    }

}

