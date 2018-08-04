<?php
return array (
  'app' => 'User',
  'model' => 'Indexadmin',
  'action' => 'default',
  'data' => '',
  'type' => '1',
  'status' => '1',
  'name' => '会员中心',
  'icon' => 'group',
  'remark' => '',
  'listorder' => '1',
  'children' => 
  array (
    array (
      'app' => 'User',
      'model' => 'Indexadmin',
      'action' => 'index',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '会员列表',
      'icon' => 'leaf',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'User',
          'model' => 'Indexadmin',
          'action' => 'ban',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '拉黑会员',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'User',
          'model' => 'Indexadmin',
          'action' => 'cancelban',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '启用会员',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'User',
      'model' => 'Indexadmin',
      'action' => 'add',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '会员注册',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'User',
          'model' => 'Indexadmin',
          'action' => 'add_post',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '提交添加',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'User',
      'model' => 'Indexadmin',
      'action' => 'net_tree',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '组织结构',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
    ),
  ),
);