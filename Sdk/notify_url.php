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
                    $stmt = $conn->prepare("SELECT days FROM packages WHERE id = ?");
                    $stmt->bind_param("i", $order['package_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $package = $result->fetch_assoc();
                    
                    if (!$package) {
                        throw new Exception("套餐不存在");
                    }
                    
                    // 获取应用服务器信息
                    $stmt = $conn->prepare("SELECT serverip FROM application WHERE appcode = ?");
                    $stmt->bind_param("s", $order['appcode']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $app = $result->fetch_assoc();
                    
                    if (!$app) {
                        throw new Exception("应用不存在");
                    }
                    
                    // 获取服务器配置
                    $stmt = $conn->prepare("SELECT ip,serveruser,password,cport FROM server_list WHERE ip = ?");
                    $stmt->bind_param("s", $app['serverip']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $server = $result->fetch_assoc();
                    
                    if (!$server) {
                        throw new Exception("服务器配置不存在");
                    }
                    
                    // 处理CCProxy账号
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
                        $totalSeconds = round($days * 24 * 3600); // 转换为秒并四舍五入
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
                        
                        $result = AddUser($proxyaddress, $admin_password, $admin_port, $userdata);
                        if (!$result) {
                            throw new Exception("创建用户失败");
                        }
                        
                    } elseif ($order['mode'] === 'renew') {
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
                        $result = UserUpdate(
                            $admin_password,
                            $admin_port,
                            $proxyaddress,
                            $order['account'],
                            '',
                            $newExpiry,
                            -1,
                            -1,
                            -1
                        );
                        
                        if (!$result) {
                            throw new Exception("更新用户失败");
                        }
                    } else {
                        throw new Exception("未知的订单类型");
                    }
                    
                    // 提交事务
                    $conn->commit();
                    echo "success";
                    
                } else {
                    echo "success"; // 订单已处理，返回成功
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                die($e->getMessage());
            } finally {
                $conn->close();
            }
        } else {
            die('trade status error');
        }
    } else {
        die('sign error');
    }
} catch (Exception $e) {
    die($e->getMessage());
}