<?php


// 引入配置文件
require_once("./lib/epay.config.php");
require_once("./lib/EpayCore.class.php");
require_once("../config.php");
require_once("../includes/function.php");

// 确保配置正确加载
if(!isset($epay_config) || !is_array($epay_config)) {
    die('config error');
}

try {
    // 初始化
    $epay = new EpayCore($epay_config);
    
    // 验证签名
    $verify_result = $epay->verifyNotify();
    
    if($verify_result) {
        // 验证基本参数
        $required_params = array('out_trade_no', 'trade_no', 'trade_status');
        foreach($required_params as $param) {
            if(!isset($_GET[$param]) || trim($_GET[$param]) === '') {
                die('param error');
            }
        }
        
        // 验证PID
        if(!isset($epay_config['pid']) || $_GET['pid'] != $epay_config['pid']) {
            die('pid error');
        }
        
        // 验证订单状态
        if(strtoupper($_GET['trade_status']) == 'TRADE_SUCCESS') {
            // 连接数据库
            $conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
            if ($conn->connect_error) {
                die('db error');
            }
            
            try {
                // 开始事务
                $conn->begin_transaction();
                
                // 更新订单状态
                $stmt = $conn->prepare("UPDATE orders SET status = 1, pay_type = ? WHERE order_no = ? AND status = 0");
                if (!$stmt) {
                    throw new Exception("SQL准备失败: " . $conn->error);
                }
                
                $stmt->bind_param("ss", $_GET['type'], $_GET['out_trade_no']);
                if (!$stmt->execute()) {
                    throw new Exception("订单状态更新失败: " . $stmt->error);
                }
                
                if ($stmt->affected_rows > 0) {
                    // 获取订单信息
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_no = ?");
                    $stmt->bind_param("s", $_GET['out_trade_no']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $order = $result->fetch_assoc();
                    
                    if (!$order) {
                        throw new Exception("订单不存在");
                    }
                    
                    // 获取套餐信息
                    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
                    $stmt->bind_param("i", $order['package_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $package = $result->fetch_assoc();
                    
                    if (!$package) {
                        throw new Exception("套餐不存在");
                    }
                    
                    // 获取应用服务器信息
                    $stmt = $conn->prepare("SELECT * FROM application WHERE appcode = ?");
                    $stmt->bind_param("s", $order['appcode']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $app = $result->fetch_assoc();
                    
                    if (!$app) {
                        throw new Exception("应用不存在");
                    }
                    
                    // 获取服务器配置
                    $stmt = $conn->prepare("SELECT * FROM server_list WHERE ip = ?");
                    $stmt->bind_param("s", $app['serverip']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $server = $result->fetch_assoc();
                    
                    if (!$server) {
                        throw new Exception("服务器配置不存在");
                    }
                    
                    // 处理主应用账号
                    $proxyaddress = $server['ip'];
                    $admin_password = $server['password'];
                    $admin_port = $server['cport'];
                    
                    // 查询用户列表
                    $users = queryuserall($admin_password, $admin_port, $proxyaddress);
                    if ($users === false) {
                        throw new Exception("获取用户列表失败");
                    }
                    
                    $userExists = !existsuser($order['account'], $users);
                    
                    if ($order['mode'] === 'register') {
                        if ($userExists) {
                            throw new Exception("用户已存在");
                        }
                        
                        // 创建新用户
                        $days = floatval($package['days']);
                        $totalSeconds = round($days * 24 * 3600);
                        $expire = date('Y-m-d H:i:s', strtotime("+{$totalSeconds} seconds"));
                        
                        $userdata = array(
                            'user' => $order['account'],
                            'pwd' => $order['password'],
                            'expire' => $expire,
                            'use_date' => date('Y-m-d H:i:s'),
                            'connection' => -1,
                            'bandwidthup' => -1,
                            'bandwidthdown' => -1
                        );
                        
                        if (!AddUser($proxyaddress, $admin_password, $admin_port, $userdata)) {
                            throw new Exception("创建用户失败");
                        }
                        
                    } else {
                        if (!$userExists) {
                            throw new Exception("用户不存在");
                        }
                        
                        // 获取用户信息
                        $userInfo = null;
                        foreach ($users as $user) {
                            if ($user['user'] === $order['account']) {
                                $userInfo = $user;
                                break;
                            }
                        }
                        
                        if (!$userInfo) {
                            throw new Exception("获取用户信息失败");
                        }
                        
                        // 计算新的到期时间
                        $currentExpiry = !empty($userInfo['disabletime']) ? strtotime($userInfo['disabletime']) : time();
                        if ($currentExpiry < time()) {
                            $currentExpiry = time();
                        }
                        
                        $newExpiry = date('Y-m-d H:i:s', $currentExpiry + ($package['days'] * 24 * 3600));
                        
                        // 更新用户
                        if (!UserUpdate($admin_password, $admin_port, $proxyaddress, $order['account'], '', $newExpiry, -1, -1, -1)) {
                            throw new Exception("更新用户失败");
                        }
                    }
                    
                    // 获取子站配置并处理继承应用
                    $stmt = $conn->prepare("SELECT * FROM sub_admin WHERE username = ?");
                    $stmt->bind_param("s", $order['username']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subconf = $result->fetch_assoc();
                    
                    // 检查是否启用继承功能
                    if ($subconf && $subconf['inherit_enabled'] && !empty($subconf['inherit_groups'])) {
                        // 解析继承组配置
                        $decoded_str = $subconf['inherit_groups'];
                        $prev_str = '';
                        while ($decoded_str !== $prev_str) {
                            $prev_str = $decoded_str;
                            $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        }
                        
                        $config = json_decode($decoded_str, true);
                        if ($config && isset($config['groups']) && is_array($config['groups'])) {
                            // 查找所有包含该主应用的组
                            $inheritApps = [];
                            foreach ($config['groups'] as $group) {
                                if (isset($group['main_apps']) && is_array($group['main_apps']) && 
                                    in_array($order['appcode'], $group['main_apps']) && 
                                    isset($group['inherit_apps']) && is_array($group['inherit_apps'])) {
                                    $inheritApps = array_merge($inheritApps, $group['inherit_apps']);
                                }
                            }
                            
                            // 去重
                            $inheritApps = array_unique($inheritApps);
                            
                            // 处理每个继承应用
                            foreach ($inheritApps as $inheritApp) {
                                try {
                                    // 获取继承应用信息
                                    $stmt = $conn->prepare("SELECT a.*, s.* FROM application a 
                                                         INNER JOIN server_list s ON a.serverip = s.ip 
                                                         WHERE s.state = 1 AND a.appcode = ? 
                                                         AND a.username = ?");
                                    $stmt->bind_param("ss", $inheritApp, $order['username']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $inheritAppInfo = $result->fetch_assoc();
                                    
                                    if ($inheritAppInfo) {
                                        // 处理继承应用账号
                                        $inheritUsers = queryuserall($inheritAppInfo['password'], $inheritAppInfo['cport'], $inheritAppInfo['ip']);
                                        if ($inheritUsers !== false) {
                                            $inheritUserExists = !existsuser($order['account'], $inheritUsers);
                                            
                                            if ($order['mode'] === 'register') {
                                                if (!$inheritUserExists) {
                                                    // 创建继承应用用户
                                                    $userdata = array(
                                                        'user' => $order['account'],
                                                        'pwd' => $order['password'],
                                                        'expire' => $expire,
                                                        'use_date' => date('Y-m-d H:i:s'),
                                                        'connection' => -1,
                                                        'bandwidthup' => -1,
                                                        'bandwidthdown' => -1
                                                    );
                                                    
                                                    if (!AddUser($inheritAppInfo['ip'], $inheritAppInfo['password'], $inheritAppInfo['cport'], $userdata)) {
                                                        error_log("继承应用 {$inheritApp} 创建用户失败");
                                                    }
                                                }
                                            } else {
                                                if ($inheritUserExists) {
                                                    // 获取继承应用用户信息
                                                    $inheritUserInfo = null;
                                                    foreach ($inheritUsers as $user) {
                                                        if ($user['user'] === $order['account']) {
                                                            $inheritUserInfo = $user;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if ($inheritUserInfo) {
                                                        // 计算新的到期时间
                                                        $currentExpiry = !empty($inheritUserInfo['disabletime']) ? strtotime($inheritUserInfo['disabletime']) : time();
                                                        if ($currentExpiry < time()) {
                                                            $currentExpiry = time();
                                                        }
                                                        
                                                        $newExpiry = date('Y-m-d H:i:s', $currentExpiry + ($package['days'] * 24 * 3600));
                                                        
                                                        // 更新继承应用用户
                                                        if (!UserUpdate($inheritAppInfo['password'], $inheritAppInfo['cport'], $inheritAppInfo['ip'], 
                                                                      $order['account'], '', $newExpiry, -1, -1, -1)) {
                                                            error_log("继承应用 {$inheritApp} 续费失败");
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log("处理继承应用 {$inheritApp} 失败: " . $e->getMessage());
                                    continue;
                                }
                            }
                        }
                    }
                    
                    $conn->commit();
                    exit('success');
                }
                
                echo "success";
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("订单处理失败: " . $e->getMessage());
                exit('fail');
            } finally {
                $conn->close();
            }
        } else {
            exit('trade status error');
        }
    } else {
        die('sign error');
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// 注册账号函数
function register_account($appcode, $account, $password, $duration) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM application WHERE appcode=?");
    $stmt->bind_param("s", $appcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    if(!$app) {
        return ['code' => -1, 'msg' => '应用不存在'];
    }
    
    $url = "http://{$app['serverip']}/api/cpproxy.php?type=insert";
    $data = [
        'user' => $account,
        'pwd' => $password,
        'duration' => $duration
    ];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}

// 续费账号函数
function renew_account($appcode, $account, $duration) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM application WHERE appcode=?");
    $stmt->bind_param("s", $appcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    if(!$app) {
        return ['code' => -1, 'msg' => '应用不存在'];
    }
    
    $url = "http://{$app['serverip']}/api/cpproxy.php?type=renew";
    $data = [
        'user' => $account,
        'duration' => $duration
    ];
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}