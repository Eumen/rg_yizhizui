<?php
return array (
  'app' => 'Admin',
  'model' => 'Order',
  'action' => 'default',
  'data' => '',
  'type' => '1',
  'status' => '1',
  'name' => '订单管理',
  'icon' => 'shopping-cart',
  'remark' => '',
  'listorder' => '4',
  'children' => 
  array (
    array (
      'app' => 'Order',
      'model' => 'AdminOrder',
      'action' => 'index',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '订单列表',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Order',
          'model' => 'AdminOrder',
          'action' => 'add',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '添加订单',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Order',
          'model' => 'AdminOrder',
          'action' => 'edit',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '编辑订单',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
        array (
          'app' => 'Order',
          'model' => 'AdminOrder',
          'action' => 'delete',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '删除订单',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
    array (
      'app' => 'Order',
      'model' => 'AdminOrder',
      'action' => 'basksingle',
      'data' => '',
      'type' => '1',
      'status' => '1',
      'name' => '晒单列表',
      'icon' => '',
      'remark' => '',
      'listorder' => '0',
      'children' => 
      array (
        array (
          'app' => 'Order',
          'model' => 'AdminOrder',
          'action' => 'del_bs',
          'data' => '',
          'type' => '1',
          'status' => '0',
          'name' => '删除晒单',
          'icon' => '',
          'remark' => '',
          'listorder' => '0',
        ),
      ),
    ),
  ),
);