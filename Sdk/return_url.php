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
        <link rel="stylesheet" href="../assets/css/fontawesome/all.min.css">
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
                        // 检查订单状态并等待处理完成
                        $maxAttempts = 10; // 最大尝试次数
                        $attempts = 0;
                        $orderProcessed = false;
                        
                        while ($attempts < $maxAttempts) {
                            // 重新查询订单状态
                            $stmt = $conn->prepare("SELECT status FROM orders WHERE order_no = ?");
                            $stmt->bind_param("s", $out_trade_no);
                            $stmt->execute();
                            $statusResult = $stmt->get_result();
                            $currentStatus = $statusResult->fetch_assoc();
                            $stmt->close();
                            
                            if ($currentStatus['status'] == 1) {
                                $orderProcessed = true;
                                break;
                            }
                            
                            $attempts++;
                            if ($attempts < $maxAttempts) {
                                usleep(200000); // 等待200毫秒后重试
                            }
                        }
                        
                        if ($orderProcessed) {
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
                            
                            // 转换时长为友好显示格式
                            $days = floatval($order['days']);
                            $totalMinutes = round($days * 24 * 60);
                            $durationDisplay = '';
                            
                            if($totalMinutes >= 24 * 60) {
                                $remainingDays = floor($totalMinutes / (24 * 60));
                                $totalMinutes %= (24 * 60);
                                $durationDisplay .= $remainingDays . '天';
                                
                                if($totalMinutes >= 60) {
                                    $hours = floor($totalMinutes / 60);
                                    $totalMinutes %= 60;
                                    $durationDisplay .= $hours . '小时';
                                }
                                
                                if($totalMinutes > 0) {
                                    $durationDisplay .= $totalMinutes . '分钟';
                                }
                            } elseif($totalMinutes >= 60) {
                                $hours = floor($totalMinutes / 60);
                                $totalMinutes %= 60;
                                $durationDisplay = $hours . '小时';
                                
                                if($totalMinutes > 0) {
                                    $durationDisplay .= $totalMinutes . '分钟';
                                }
                            } else {
                                $durationDisplay = $totalMinutes . '分钟';
                            }
                            
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-clock"></i>套餐时长</span><span class="info-value">'.$durationDisplay.'</span></div>';
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

                            echo '<div class="status-header">';
                            echo '<div class="status-success"><i class="fas fa-check-circle status-icon"></i><br>支付成功</div>';
                            echo '</div>';
                            
                            echo '<div class="order-info card">';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-hashtag"></i>订单编号</span><span class="info-value">'.$out_trade_no.'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-receipt"></i>交易号</span><span class="info-value">'.$trade_no.'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-credit-card"></i>支付方式</span><span class="info-value">'.(isset($pay_type_names[$type]) ? $pay_type_names[$type] : $type).'</span></div>';
                            echo '<div class="info-item"><span class="info-label"><i class="fas fa-yen-sign"></i>支付金额</span><span class="info-value">￥'.$money.'</span></div>';
                            echo '</div>';
                            
                        } else {
                            // 如果订单未处理完成，显示等待信息
                            echo '<div class="status-waiting card">';
                            echo '<div class="title"><i class="fas fa-spinner fa-spin"></i>订单处理中</div>';
                            echo '<div class="refresh-tip"><i class="fas fa-sync-alt"></i>正在处理您的订单，请稍候...</div>';
                            echo '</div>';
                            echo '<script>
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000); // 1秒后刷新
                            </script>';
                        }
                    } else {
                        echo '<div class="status-header">';
                        echo '<div class="status-failed"><i class="fas fa-times-circle status-icon"></i><br>订单不存在</div>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="status-header">';
                    echo '<div class="status-failed"><i class="fas fa-times-circle status-icon"></i><br>处理失败</div>';
                    echo '</div>';
                    echo '<div class="error-message">订单处理出现错误，请联系客服</div>';
                } finally {
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
                    }, 1000); // 1秒后刷新
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

<?php
// CURL请求函数
function curl_request($url, $post = null) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    if($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}