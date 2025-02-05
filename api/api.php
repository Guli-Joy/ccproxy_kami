<?php

include("../includes/common.php");
@header('Content-Type: application/json; charset=UTF-8');

$act = isset($_GET['act']) ? strtolower(daddslashes($_GET['act'])) : null;

switch($act){
    case "gethostapp":
    $application=$DB->select("select appcode,appname from application where username=\"".$subconf["username"]."\"");
    if($application){
        $code=[
            "code"=>"1",
            "msg"=>$application
        ];
    }
    else{
        $code=[
            "code"=>"0",
            "msg"=>"未知错误"
        ];
    }
    exit(json_encode($code,JSON_UNESCAPED_UNICODE));
    break;

    case "getpackages":
    $appcode = isset($_POST['appcode'])?daddslashes($_POST['appcode']):null;
    if(!$appcode){
        $code = [
            "code"=>"-1",
            "msg"=>"应用代码不能为空"
        ];
        exit(json_encode($code,JSON_UNESCAPED_UNICODE));
    }
    
    // 先检查应用是否存在
    $app_exists = $DB->select("select appcode from application where appcode=\"".$appcode."\" and username=\"".$subconf["username"]."\"");
    if(!$app_exists) {
        $code = [
            "code"=>"-2",
            "msg"=>"应用不存在或无权限"
        ];
        exit(json_encode($code,JSON_UNESCAPED_UNICODE));
    }
    
    // 查询指定应用的有效套餐
    $packages = $DB->select("select p.id, p.package_name as name, p.days as duration, p.price 
                            from packages p 
                            inner join application a on p.appcode = a.appcode 
                            where p.appcode=\"".$appcode."\" 
                            and a.username=\"".$subconf["username"]."\" 
                            and p.status=1 
                            order by p.price asc");
    
    if($packages){
        $code = [
            "code"=>"1",
            "msg"=>$packages
        ];
    } else {
        // 检查是否有套餐但被禁用
        $disabled_packages = $DB->select("select count(*) as count from packages where appcode=\"".$appcode."\" and status=0");
        if($disabled_packages && $disabled_packages[0]['count'] > 0) {
            $code = [
                "code"=>"0",
                "msg"=>"该应用的套餐已被禁用"
            ];
        } else {
            $code = [
                "code"=>"0",
                "msg"=>"暂无可用套餐，请先在后台添加套餐"
            ];
        }
    }
    exit(json_encode($code,JSON_UNESCAPED_UNICODE));
    break;

    case "getpaymethods":
    case "getPayMethods":
        // 确保配置表中至少有一条记录
        $DB->exe("INSERT IGNORE INTO pay_config (id, status, alipay_status, wxpay_status, qqpay_status) 
                VALUES (1, 1, 1, 1, 1)");
                
        $row = $DB->selectRow("SELECT status, alipay_status, wxpay_status, qqpay_status 
                            FROM pay_config WHERE id=1 LIMIT 1");
        if($row) {
            // 只有在总开关开启时才返回各支付方式状态
            if($row['status'] == 1) {
                exit(json_encode([
                    'code' => 1, 
                    'msg' => 'success', 
                    'data' => [
                        'alipay_status' => intval($row['alipay_status']),
                        'wxpay_status' => intval($row['wxpay_status']),
                        'qqpay_status' => intval($row['qqpay_status'])
                    ]
                ]));
            } else {
                exit(json_encode([
                    'code' => 1,
                    'msg' => 'success',
                    'data' => [
                        'alipay_status' => 0,
                        'wxpay_status' => 0,
                        'qqpay_status' => 0
                    ]
                ]));
            }
        } else {
            exit(json_encode(['code' => -1, 'msg' => '获取支付方式失败']));
        }
        break;

    case "getpayconfig":
    case "getPayConfig":
        // 确保配置表中至少有一条记录
        $DB->exe("INSERT IGNORE INTO pay_config (id, merchant_id, merchant_key, api_url, status) 
                VALUES (1, '', '', '', 1)");
                
        $config = $DB->selectRow("SELECT merchant_id, merchant_key, api_url 
                                FROM pay_config WHERE id=1 LIMIT 1");
        if($config) {
            // 确保所有字段都有值，即使是空值
            $response = [
                'code' => 1,
                'msg' => 'success',
                'data' => [
                    'merchant_id' => $config['merchant_id'] ?: '',
                    'merchant_key' => $config['merchant_key'] ?: '',
                    'api_url' => $config['api_url'] ?: ''
                ]
            ];
            exit(json_encode($response));
        } else {
            exit(json_encode(['code'=>-1, 'msg'=>'获取支付配置失败']));
        }
        break;

    case "getPackageInfo":
    $package_id = isset($_POST['package_id'])?daddslashes($_POST['package_id']):null;
    if(!$package_id) {
        exit(json_encode(['code'=>-1, 'msg'=>'套餐ID不能为空']));
    }
    
    $package = $DB->selectRow("SELECT p.*, a.appname 
                              FROM packages p 
                              INNER JOIN application a ON p.appcode = a.appcode 
                              WHERE p.id = '".$package_id."' 
                              AND a.username = '".$subconf["username"]."' 
                              AND p.status = 1 
                              LIMIT 1");
    
    if($package) {
        exit(json_encode(['code'=>1, 'msg'=>'success', 'data'=>$package]));
    } else {
        exit(json_encode(['code'=>-1, 'msg'=>'套餐不存在或已被禁用']));
    }
    break;

    case "createorder":
    case "createOrder":
        try {
            // 直接使用原始POST数据，避免经过SecurityFilter的处理
            $app = isset($_REQUEST['app']) ? trim($_REQUEST['app']) : null;
            $mode = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : null;
            $account = isset($_REQUEST['account']) ? trim($_REQUEST['account']) : null;
            $password = isset($_REQUEST['password']) ? trim($_REQUEST['password']) : null;
            $package = isset($_POST['package']) ? trim($_POST['package']) : null;
            $pay_method = isset($_POST['pay_method']) ? trim($_POST['pay_method']) : null;
            $order_no = isset($_POST['order_no']) ? trim($_POST['order_no']) : null;

            if(!$app || !$mode || !$account || !$package || !$pay_method || !$order_no) {
                exit(json_encode(['code'=>-1, 'msg'=>'参数不完整']));
            }

            // 检查应用是否存在且属于当前用户
            $app_exists = $DB->selectRow("SELECT * FROM application WHERE appcode='" . $DB->escape($app) . "' AND username='".$DB->escape($subconf['username'])."'");
            if(!$app_exists) {
                exit(json_encode(['code'=>-1, 'msg'=>'应用不存在或无权限']));
            }

            // 检查套餐是否存在且有效，同时获取价格
            $package_info = $DB->selectRow("SELECT id, price FROM packages WHERE id='" . $DB->escape($package) . "' AND appcode='" . $DB->escape($app) . "' AND status=1");
            if(!$package_info) {
                exit(json_encode(['code'=>-1, 'msg'=>'套餐不存在或已禁用']));
            }

            // 准备订单数据
            $data = array(
                'order_no' => $order_no,
                'appcode' => $app,
                'account' => $account,  // 直接使用原始值
                'password' => $mode == 'register' ? $password : '',  // 直接使用原始值
                'package_id' => $package,
                'amount' => $package_info['price'],
                'pay_type' => $pay_method,
                'status' => 0,
                'create_time' => date('Y-m-d H:i:s'),
                'mode' => $mode,
                'username' => $subconf["username"]
            );

            $insert = $DB->insert('orders', $data);
            if($insert) {
                exit(json_encode([
                    'code' => 1, 
                    'msg' => '订单创建成功',
                    'data' => [
                        'order_no' => $order_no,
                        'amount' => $package_info['price']
                    ]
                ]));
            } else {
                exit(json_encode(['code'=>-1, 'msg'=>'订单创建失败']));
            }
        } catch (Exception $e) {
            exit(json_encode(['code'=>-1, 'msg'=>'订单创建失败：'.$e->getMessage()]));
        }
        break;

    case "getOrders":
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;

    // 获取订单总数
    $total = $DB->selectRow("SELECT COUNT(*) as count FROM orders WHERE username=?", [$subconf["username"]]);
    
    // 获取订单列表
    $orders = $DB->select("SELECT o.*, a.appname, p.package_name 
                          FROM orders o 
                          LEFT JOIN application a ON o.appcode = a.appcode 
                          LEFT JOIN packages p ON o.package_id = p.id 
                          WHERE o.username=? 
                          ORDER BY o.create_time DESC 
                          LIMIT ?, ?", 
                          [$subconf["username"], $offset, $limit]);
    
    if($orders !== false) {
        exit(json_encode([
            'code' => 1,
            'msg' => 'success',
            'count' => $total['count'],
            'data' => $orders
        ]));
    } else {
        exit(json_encode([
            'code' => -1,
            'msg' => '获取订单列表失败',
            'count' => 0,
            'data' => []
        ]));
    }
    break;

    default:
        exit(json_encode(["code"=>-4,"msg"=>"No Act"]));
        break;
}
