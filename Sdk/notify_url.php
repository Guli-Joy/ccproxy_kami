<?php
/* *
 * 功能：彩虹易支付异步通知页面
 */

require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");
require_once("../config.php");
include("../includes/function.php");

function getUserInfo($admin_password, $admin_port, $proxyaddress, $username) {
    $users = queryuserall($admin_password, $admin_port, $proxyaddress);
    foreach ($users as $user) {
        if ($user['user'] === $username) {
            return $user;
        }
    }
    return null;
}

function handlePaymentSuccess($data, $conn) {
    try {
        // 获取订单信息
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_no = ?");
        $stmt->bind_param("s", $data['out_trade_no']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['code' => -1, 'msg' => '订单不存在'];
        }
        $order = $result->fetch_assoc();

        // 获取套餐信息
        $stmt = $conn->prepare("SELECT days FROM packages WHERE id = ?");
        $stmt->bind_param("i", $order['package_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['code' => -1, 'msg' => '套餐不存在'];
        }
        $package = $result->fetch_assoc();

        // 获取应用服务器信息
        $stmt = $conn->prepare("SELECT serverip FROM application WHERE appcode = ?");
        $stmt->bind_param("s", $order['appcode']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['code' => -1, 'msg' => '应用不存在'];
        }
        $app = $result->fetch_assoc();

        // 获取服务器配置信息
        $stmt = $conn->prepare("SELECT ip,serveruser,password,cport FROM server_list WHERE ip = ?");
        $stmt->bind_param("s", $app['serverip']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['code' => -1, 'msg' => '服务器配置不存在'];
        }
        $server = $result->fetch_assoc();

        // 设置CCProxy连接参数
        $proxyaddress = $server['ip'];
        $admin_password = $server['password'];
        $admin_port = $server['cport'];

        // 查询用户是否存在
        $users = queryuserall($admin_password, $admin_port, $proxyaddress);
        $userExists = !existsuser($order['account'], $users);

        // 根据订单类型处理
        if ($order['mode'] === 'register') {
            if ($userExists) {
                return ['code' => -1, 'msg' => '用户已存在'];
            }

            $userdata = [
                'user' => $order['account'],
                'pwd' => $order['password'],
                'expire' => $package['days'],
                'use_date' => date('Y-m-d H:i:s'),
                'connection' => -1,
                'bandwidthup' => -1,
                'bandwidthdown' => -1
            ];

            $result = AddUser($proxyaddress, $admin_password, $admin_port, $userdata);
            
        } elseif ($order['mode'] === 'renew') {
            if (!$userExists) {
                return ['code' => -1, 'msg' => '用户不存在，无法续费'];
            }

            $userInfo = getUserInfo($admin_password, $admin_port, $proxyaddress, $order['account']);
            $currentExpiry = !empty($userInfo['disabletime']) ? strtotime($userInfo['disabletime']) : time();
            
            if ($currentExpiry < time()) {
                $currentExpiry = time();
            }

            $newExpiry = date('Y-m-d H:i:s', $currentExpiry + ($package['days'] * 24 * 3600));
            
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
        } else {
            return ['code' => -1, 'msg' => '未知的订单类型'];
        }

        return $result;

    } catch (Exception $e) {
        return ['code' => -1, 'msg' => '系统异常：' . $e->getMessage()];
    }
}

// 添加防重放机制
function checkReplay($trade_no) {
    $cache_file = __DIR__ . '/../cache/notify_' . md5($trade_no) . '.lock';
    
    if (file_exists($cache_file)) {
        $cache_time = file_get_contents($cache_file);
        if (time() - intval($cache_time) < 3600) { // 1小时内的重复通知
            return false;
        }
    }
    
    file_put_contents($cache_file, time());
    return true;
}

// 添加调试日志
error_log("Received notify parameters: " . json_encode($_GET));

$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyNotify();

error_log("Verify result: " . ($verify_result ? 'true' : 'false'));

if($verify_result) {
    // 验证成功后的处理
    if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
        // 检查是否重复通知
        if (!checkReplay($_GET['out_trade_no'])) {
            error_log("Duplicate notification detected");
            echo "success"; // 对重复通知返回成功
            exit;
        }
        
        $conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            echo "fail";
            exit;
        }

        try {
            // 开始事务
            $conn->begin_transaction();

            // 更新订单状态
            $stmt = $conn->prepare("UPDATE orders SET status = 1 WHERE order_no = ? AND status = 0");
            $stmt->bind_param("s", $_GET['out_trade_no']);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $result = handlePaymentSuccess($_GET, $conn);
                if ($result['code'] === -1) {
                    error_log("Payment processing failed: " . $result['msg']);
                    $conn->rollback();
                    echo "fail";
                    exit;
                }
                
                // 提交事务
                $conn->commit();
                echo "success";
            } else {
                error_log("Order status update failed or order already processed");
                $conn->rollback();
                echo "fail";
            }
        } catch (Exception $e) {
            error_log("Exception occurred: " . $e->getMessage());
            $conn->rollback();
            echo "fail";
        } finally {
            $conn->close();
        }
        exit;
    }
    echo "fail";
    exit;
}

error_log("Sign verification failed");
echo "fail";
exit;
?>