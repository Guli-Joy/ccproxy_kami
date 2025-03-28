<?php

// 引入必要的文件
require_once '../includes/common.php';
require_once '../includes/SecurityFilter.php';

@header('Content-Type: application/json; charset=UTF-8');

// 获取并处理act参数
$act = isset($_GET['act']) ? strtolower(trim($_GET['act'])) : '';

// 记录请求日志
$logger->debug('API Request', [
    'act' => $act,
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $_GET,
    'post' => $_POST,
    'ip' => $clientip
]);

function getMainApps($inherit_groups) {
    if(empty($inherit_groups)) {
        error_log("继承组配置为空");
        return [];
    }
    
    try {
        // 递归解码HTML实体
        $decoded_str = $inherit_groups;
        $prev_str = '';
        while($decoded_str !== $prev_str) {
            $prev_str = $decoded_str;
            $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        $config = json_decode($decoded_str, true);
        if($config === null) {
            error_log("JSON解析失败：" . json_last_error_msg());
            return [];
        }
        
        if(!isset($config['groups']) || !is_array($config['groups'])) {
            error_log("继承组配置格式错误：没有groups数组");
            return [];
        }
        
        $mainApps = [];
        foreach($config['groups'] as $group) {
            if(isset($group['main_apps']) && is_array($group['main_apps'])) {
                foreach($group['main_apps'] as $app) {
                    if(!empty($app)) {
                        $mainApps[] = $app;
                    }
                }
            }
        }
        
        $mainApps = array_unique($mainApps);
        return $mainApps;
    } catch(Exception $e) {
        error_log("解析主应用列表失败: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        return [];
    }
}

function gethostapp() {
    global $DB, $subconf;
    
    $apps = [];
    
    // 获取所有应用
    $sql = "SELECT appcode, appname FROM application WHERE username='" . $DB->escape($subconf['username']) . "'";
    $result = $DB->select($sql);
    if($result) {
        $apps = array_merge($apps, $result);
    }
    
    // 如果启用了继承功能且允许显示继承应用
    if($subconf['inherit_enabled'] == 1 && $subconf['show_inherit_apps'] == 1) {
        // 获取继承组配置
        $inherit_config = null;
        if(!empty($subconf['inherit_groups'])) {
            $decoded_str = $subconf['inherit_groups'];
            $prev_str = '';
            while($decoded_str !== $prev_str) {
                $prev_str = $decoded_str;
                $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $inherit_config = json_decode($decoded_str, true);
        }

        if($inherit_config && isset($inherit_config['groups'])) {
            foreach($inherit_config['groups'] as $group) {
                if(isset($group['inherit_apps']) && is_array($group['inherit_apps'])) {
                    foreach($group['inherit_apps'] as $inherit_app) {
                        // 获取继承应用信息
                        $sql = "SELECT appcode, appname FROM application WHERE appcode='" . $DB->escape($inherit_app) . "'";
                        $result = $DB->select($sql);
                        if($result && isset($result[0])) {
                            // 检查是否已经在列表中
                            $exists = false;
                            foreach($apps as $app) {
                                if($app['appcode'] == $result[0]['appcode']) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if(!$exists) {
                                // 添加标记以区分继承应用
                                $result[0]['appname'] .= ' [继承]';
                                $apps[] = $result[0];
                            }
                        }
                    }
                }
            }
        }
    }
    
    if(count($apps) > 0) {
        return ['code' => 1, 'msg' => $apps];
    } else {
        return ['code' => -1, 'msg' => '暂无应用'];
    }
}

switch($act){
    case "gethostapp":
        try {
            // 修改SQL查询，通过server_list表的state字段来判断应用是否可用
            $sql = "SELECT DISTINCT a.appcode, a.appname 
                   FROM application a 
                   INNER JOIN server_list s ON a.serverip = s.ip 
                   WHERE s.state = 1 
                   AND a.username = '" . $DB->escape($subconf['username']) . "' 
                   ORDER BY a.appname ASC";
            
            $apps = $DB->select($sql);
            
            if($apps && !empty($apps)) {
                // 如果继承功能开启
                if($subconf['inherit_enabled'] && !empty($subconf['inherit_groups'])) {
                    $mainApps = getMainApps($subconf['inherit_groups']);
                    
                    if(!empty($mainApps)) {
                        // 如果不显示继承应用，只返回主应用
                        if($subconf['show_inherit_apps'] != 1) {
                            $filtered_apps = [];
                            foreach($apps as $app) {
                                if(in_array($app['appcode'], $mainApps)) {
                                    $filtered_apps[] = $app;
                                }
                            }
                            $apps = $filtered_apps;
                        } else {
                            // 如果显示继承应用，标记主应用和继承应用
                            foreach($apps as &$app) {
                                if(in_array($app['appcode'], $mainApps)) {
                                    $app['appname'] .= ' [主应用]';
                                } else {
                                    $app['appname'] .= ' [继承]';
                                }
                            }
                        }
                    }
                }
                
                if(empty($apps)) {
                    $json = [
                        'code' => -1,
                        'msg' => '没有可用的应用'
                    ];
                } else {
                    $json = [
                        'code' => 1,
                        'msg' => array_values($apps)
                    ];
                }
            } else {
                error_log("没有找到可用的应用");
                $json = [
                    'code' => -1,
                    'msg' => '没有可用的应用，请先添加应用并确保对应服务器已启用'
                ];
            }
        } catch(Exception $e) {
            error_log("获取应用列表异常: " . $e->getMessage());
            error_log("异常堆栈: " . $e->getTraceAsString());
            $json = [
                'code' => -1,
                'msg' => '获取应用失败：系统错误'
            ];
        }
        
        exit(json_encode($json, JSON_UNESCAPED_UNICODE));
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

    case "querykami":
        $kami = isset($_POST['kami']) ? trim($_POST['kami']) : '';
        
        // 参数验证
        if(empty($kami)){
            $logger->warning('Kami Query Failed', [
                'reason' => 'Empty kami',
                'ip' => $clientip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            exit(json_encode(['code' => -1, 'msg' => '卡密不能为空']));
        }
        
        // 安全过滤
        $kami = SecurityFilter::filterInput($kami);
        
        try {
            // 查询卡密信息
            $row = $DB->selectRow("SELECT k.*, a.appname 
                                 FROM kami k 
                                 LEFT JOIN application a ON k.app = a.appcode 
                                 WHERE k.kami = '".$DB->escape($kami)."' 
                                 LIMIT 1");
            
            if(!$row){
                $logger->warning('Kami Query Failed', [
                    'reason' => 'Kami not found',
                    'kami' => substr($kami, 0, 8) . '****', // 只记录部分卡密信息
                    'ip' => $clientip
                ]);
                exit(json_encode(['code' => -1, 'msg' => '卡密不存在']));
            }
            
            // 构造返回数据
            $data = [
                'state' => intval($row['state']),
                'times' => $row['times'],
                'found_date' => $row['found_date'],
                'comment' => SecurityFilter::safeOutput($row['comment']),
                'app' => isset($row['appname']) ? SecurityFilter::safeOutput($row['appname']) : '未知应用'
            ];
            
            // 如果卡密已使用，添加使用信息
            if($row['state'] == 1){
                $data['username'] = SecurityFilter::safeOutput($row['username']);
                $data['use_date'] = $row['use_date'];
                $data['end_date'] = $row['end_date'];
            }
            
            // 记录成功查询日志
            $logger->info('Kami Query Success', [
                'kami' => substr($kami, 0, 8) . '****', // 只记录部分卡密信息
                'ip' => $clientip,
                'state' => $row['state'] ? '已使用' : '未使用',
                'app' => $data['app']
            ]);
            
            exit(json_encode([
                'code' => 1,
                'msg' => '查询成功',
                'data' => $data
            ]));
            
        } catch (Exception $e) {
            // 记录详细错误日志
            $logger->error('Kami Query Error', [
                'kami' => substr($kami, 0, 8) . '****', // 只记录部分卡密信息
                'ip' => $clientip,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            exit(json_encode(['code' => -1, 'msg' => '查询失败，请稍后重试']));
        }
        break;

    case "getinheritapps":
        try {
            $appcode = isset($_POST['appcode']) ? $DB->escape($_POST['appcode']) : '';
            if(empty($appcode)) {
                exit(json_encode(['code' => -1, 'msg' => '应用代码不能为空']));
            }

            // 检查是否启用继承功能
            if(!$subconf['inherit_enabled'] || empty($subconf['inherit_groups'])) {
                exit(json_encode(['code' => 1, 'data' => []]));
            }

            // 获取继承应用列表
            $mainApps = getMainApps($subconf['inherit_groups']);
            if(!in_array($appcode, $mainApps)) {
                exit(json_encode(['code' => 1, 'data' => []]));
            }

            // 解析继承组配置
            $decoded_str = $subconf['inherit_groups'];
            $prev_str = '';
            while($decoded_str !== $prev_str) {
                $prev_str = $decoded_str;
                $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            
            $config = json_decode($decoded_str, true);
            if(!$config || !isset($config['groups']) || !is_array($config['groups'])) {
                exit(json_encode(['code' => 1, 'data' => []]));
            }

            // 查找所有包含该主应用的组，并收集其继承应用
            $inheritApps = [];
            foreach($config['groups'] as $group) {
                if(isset($group['main_apps']) && is_array($group['main_apps']) && 
                   in_array($appcode, $group['main_apps']) && 
                   isset($group['inherit_apps']) && is_array($group['inherit_apps'])) {
                    $inheritApps = array_merge($inheritApps, $group['inherit_apps']);
                }
            }

            // 去重
            $inheritApps = array_unique($inheritApps);

            // 验证继承应用是否存在且可用
            $validApps = [];
            foreach($inheritApps as $app) {
                $sql = "SELECT a.appcode FROM application a 
                       INNER JOIN server_list s ON a.serverip = s.ip 
                       WHERE s.state = 1 AND a.appcode = '" . $DB->escape($app) . "' 
                       AND a.username = '" . $DB->escape($subconf['username']) . "'";
                $result = $DB->select($sql);
                if($result && !empty($result)) {
                    $validApps[] = $app;
                }
            }

            exit(json_encode(['code' => 1, 'data' => $validApps]));
        } catch(Exception $e) {
            error_log("获取继承应用列表失败: " . $e->getMessage());
            exit(json_encode(['code' => -1, 'msg' => '获取继承应用列表失败']));
        }
        break;

    default:
        exit(json_encode(["code"=>-4,"msg"=>"No Act"]));
        break;
}
