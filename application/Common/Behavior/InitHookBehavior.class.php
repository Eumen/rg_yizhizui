<?php
namespace Common\Behavior;
use Think\Behavior;
use Think\Hook;

// 初始化钩子信息
class InitHookBehavior extends Behavior {

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        if(isset($_GET['m']) && $_GET['m'] === 'Install') return;
        
        $data = S('hooks');
        if(!$data){
            S('hooks',Hook::get());
        }else{
           Hook::import($data,false);
        }
    }
}