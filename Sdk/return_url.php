<?php
// 引入安全配置
if(!defined('IN_COMMON')) {
    require_once(dirname(__DIR__).'/includes/common.php');
}

// 检查请求安全性
if (!$security->handleRequest()) {
    die('非法请求');
}

/* * 
 * 功能：彩虹易支付页面跳转同步通知页面
 */
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");
require_once("../config.php");

?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>支付结果 - 订单详情</title>
        <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/pay/return.css">
    </head>
    <body>
        <div class="container">
        <?php
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyReturn();

        if($verify_result) {
            $out_trade_no = $_GET['out_trade_no'];
            $trade_no = $_GET['trade_no'];
            $trade_status = $_GET['trade_status'];
            $type = $_GET['type'];
            $money = isset($_GET['money']) ? $_GET['money'] : '未知';
            $name = isset($_GET['name']) ? $_GET['name'] : '';

            $pay_type_names = [
                'alipay' => '支付宝',
                'wxpay' => '微信支付',
                'qqpay' => 'QQ钱包'
            ];

            if(strtoupper($_GET['trade_status']) == 'TRADE_SUCCESS') {
                $conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
                if ($conn->connect_error) {
                    die("连接失败: " . $conn->connect_error);
                }

                try {
                    // 获取订单信息
                    $stmt = $conn->prepare("SELECT o.*, p.days FROM orders o LEFT JOIN packages p ON o.package_id = p.id WHERE o.order_no = ?");
                    if($stmt === false) {
                        throw new Exception("准备订单查询语句失败");
                    }
                    
                    $stmt->bind_param("s", $out_trade_no);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $order = $result->fetch_assoc();
                    $stmt->close();

                    if ($order) {
                        if ($order['status'] == 1) {
                            // 获取套餐应用信息
                            $stmt = $conn->prepare("SELECT pa.* FROM package_apps pa 
                                                  LEFT JOIN application a ON pa.appcode = a.appcode 
                                                  WHERE pa.appcode = ? AND pa.status = 1 
                                                  ORDER BY pa.sort_order ASC");
                            if($stmt === false) {
                                throw new Exception("准备应用配置查询语句失败");
                            }
                            
                            $stmt->bind_param("s", $order['appcode']);
                            $stmt->execute();
                            $apps_result = $stmt->get_result();
                            $apps = [];
                            while($app = $apps_result->fetch_assoc()) {
                                $apps[] = $app;
                            }
                            $stmt->close();

                            // 显示账号信息
                            echo '<div class="account-info card">';
                            echo '<div class="title"><i class="fas fa-user-circle"></i>账号信息</div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-user"></i>账号</span><span class="info-value">'.$order['account'].'</span></div>';
                            if ($order['mode'] == 'register') {
                                echo '<div class="info-item"><span class="info-label"><i class="fas fa-key"></i>密码</span><span class="info-value">'.$order['password'].'</span></div>';
                            }
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-clock"></i>套餐时长</span><span class="info-value">'.$order['days'].'天</span></div>';
                            echo '</div>';

                            // 显示应用配置信息
                            if (!empty($apps)) {
                                foreach($apps as $app) {
                                    echo '<div class="account-info card">';
                                    echo '<div class="title"><i class="fas fa-cube"></i>'.$app['app_name'].'</div>';
                                    echo '<div class="info-item"><span class="info-label"><i class="fas fa-server"></i>服务器地址</span><span class="info-value">'.$app['server_address'].'</span></div>';
                                    echo '<div class="info-item"><span class="info-label"><i class="fas fa-network-wired"></i>端口</span><span class="info-value">'.$app['server_port'].'</span></div>';
                                    if (!empty($app['download_url'])) {
                                        echo '<div class="info-item"><span class="info-label"><i class="fas fa-download"></i>下载地址</span><span class="info-value"><a href="'.$app['download_url'].'" target="_blank" class="download-link">点击下载</a></span></div>';
                                    }
                                    if (!empty($app['special_notes'])) {
                                        echo '<div class="info-item"><span class="info-label"><i class="fas fa-info-circle"></i>特别说明</span><span class="info-value">'.$app['special_notes'].'</span></div>';
                                    }
                                    echo '</div>';
                                }
                            }

                            // 查询账号状态
                            $ch = curl_init();
                            $post_data = array(
                                'user' => $order['account'],
                                'appcode' => $order['appcode']
                            );
                            
                            $api_url = dirname(dirname($_SERVER['PHP_SELF'])) . "/api/cpproxy.php?type=query";
                            curl_setopt($ch, CURLOPT_URL, $api_url);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FAILONERROR, true);
                            
                            $response = curl_exec($ch);
                            curl_close($ch);
                            
                            echo '<div class="status-header">';
                            echo '<div class="status-success"><i class="fas fa-check-circle status-icon"></i><br>支付成功</div>';
                            echo '</div>';
                            
                            echo '<div class="order-info card">';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-hashtag"></i>订单编号</span><span class="info-value">'.$out_trade_no.'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-receipt"></i>交易号</span><span class="info-value">'.$trade_no.'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-credit-card"></i>支付方式</span><span class="info-value">'.(isset($pay_type_names[$type]) ? $pay_type_names[$type] : $type).'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-yen-sign"></i>支付金额</span><span class="info-value">￥'.$money.'</span></div>';
                            echo '</div>';
                            
                            $query_result = json_decode($response, true);
                            if ($query_result && $query_result['code'] == 1) {
                                if (preg_match('/到期时间：([\d-: ]+)/', $query_result['msg'], $matches)) {
                                    echo '<div class="expiry-time card">';
                                    echo '<div class="title"><i class="fas fa-calendar-check"></i>账号到期时间</div>';
                                    echo '<div class="value">'.$matches[1].'</div>';
                                    echo '</div>';
                                }
                            }
                        } else {
                            echo '<div class="status-waiting card">';
                            echo '<div class="title"><i class="fas fa-spinner fa-spin"></i>订单处理中</div>';
                            echo '<div class="refresh-tip"><i class="fas fa-sync-alt"></i>正在等待支付结果，页面将自动刷新</div>';
                            echo '</div>';
                            echo '<script>
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000); // 每2秒刷新一次
                            </script>';
                        }
                    }
                } catch (Exception $e) {
                    error_log("支付回调处理错误: " . $e->getMessage());
                    echo '<div class="status-header">';
                    echo '<div class="status-failed"><i class="fas fa-times-circle status-icon"></i><br>处理失败</div>';
                    echo '</div>';
                    echo '<div class="error-message">订单处理出现错误，请联系客服</div>';
                } finally {
                    // 确保数据库连接被关闭
                    if(isset($conn)) {
                        $conn->close();
                    }
                }
            } else {
                echo '<div class="status-header">';
                echo '<div class="status-failed"><i class="fas fa-times-circle status-icon"></i><br>交易进行中</div>';
                echo '</div>';
                echo '<div class="order-info card">';
                echo '<div class="info-item"><span class="info-label"><i class="fas fa-info-circle"></i>当前状态</span><span class="info-value">'.strtoupper($_GET['trade_status']).'</span></div>';
                echo '</div>';
                echo '<script>
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000); // 每2秒刷新一次
                </script>';
            }
        } else {
            echo '<div class="status-header">';
            echo '<div class="status-failed"><i class="fas fa-exclamation-circle status-icon"></i><br>验证失败</div>';
            echo '</div>';
            echo '<div class="order-info card">';
            echo '<div class="info-item"><span class="info-label"><i class="fas fa-exclamation-triangle"></i>状态说明</span><span class="info-value">订单验证失败，请联系客服</span></div>';
            echo '</div>';
        }
        ?>
            <a href="../index.php" class="back-button"><i class="fas fa-home"></i>返回首页</a>
        </div>
    </body>
</html>

<style>
.download-link {
    color: #2d8cf0;
    text-decoration: none;
}
.download-link:hover {
    color: #5cadff;
}
</style>