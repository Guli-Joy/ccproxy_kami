<?php
// 引入安全配置
if(!defined('IN_COMMON')) {
    require_once(dirname(__DIR__).'/includes/common.php');
}

// 检查请求安全性
if (!$security->handleRequest()) {
    $logger->security('支付接口请求被拦截', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'uri' => $_SERVER['REQUEST_URI']
    ]);
    die('非法请求');
}

// 只在session未启动时启动session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 安全响应头
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");

require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

function validateParams($params) {
    $required = ['out_trade_no', 'type', 'name', 'money'];
    foreach ($required as $field) {
        if (!isset($params[$field]) || trim($params[$field]) === '') {
            throw new Exception('缺少必要参数: ' . $field);
        }
    }
    
    // 验证订单号格式
    if (!preg_match('/^[A-Za-z0-9]{1,32}$/', $params['out_trade_no'])) {
        throw new Exception('订单号格式不正确');
    }
    
    // 验证支付方式
    if (!in_array($params['type'], ['alipay', 'wxpay', 'qqpay'])) {
        throw new Exception('不支持的支付方式');
    }
    
    // 验证金额
    if (!is_numeric($params['money']) || 
        $params['money'] <= 0 || 
        $params['money'] > 100000) {
        throw new Exception('金额格式不正确');
    }
    
    return true;
}

function validateToken($token) {
    if (!isset($_SESSION['payment_token']) || empty($token)) {
        return false;
    }
    
    if (!hash_equals($_SESSION['payment_token'], $token)) {
        return false;
    }
    
    return true;
}

try {
    // 验证来源站点
    $referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
    if (empty($referer) || $referer !== $_SERVER['HTTP_HOST']) {
        throw new Exception('非法请求来源');
    }
    
    // 验证令牌
    if (!validateToken($_POST['token'])) {
        throw new Exception('令牌验证失败');
    }
    
    // 验证参数
    validateParams($_POST);
    
    // 获取数据库中的站点URL
    $conn = mysqli_connect($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname']);
    if (!$conn) {
        throw new Exception("数据库连接失败");
    }
    
    $query = "SELECT siteurl FROM sub_admin LIMIT 1";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("获取站点配置失败");
    }
    
    $row = mysqli_fetch_assoc($result);
    $siteurl = $row['siteurl'];
    mysqli_close($conn);

    // 构造支付参数
    $parameter = array(
        "pid" => trim($epay_config['pid']),
        "type" => trim($_POST['type']),
        "out_trade_no" => trim($_POST['out_trade_no']),
        "notify_url" => trim("http://".$siteurl."/SDK/notify_url.php"),
        "return_url" => trim("http://".$siteurl."/SDK/return_url.php"),
        "name" => trim($_POST['name']),
        "money" => sprintf("%.2f", $_POST['money']),
        "sitename" => trim($_POST['sitename'])
    );

    // 生成支付表单
    $epay = new EpayCore($epay_config);
    $html_text = $epay->pagePay($parameter);
    
    // 生成新的令牌
    $_SESSION['payment_token'] = bin2hex(random_bytes(32));
    
} catch (Exception $e) {
    die(json_encode([
        'code' => -1, 
        'msg' => $e->getMessage(),
        'time' => date('Y-m-d H:i:s')
    ]));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单支付</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #2c3e50;
        }
        
        .container {
            background: #fff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            margin: 0 auto 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2ecc71;
        }
        
        .amount {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
            color: #e74c3c;
        }
        
        .amount small {
            font-size: 1rem;
            color: #7f8c8d;
        }
        
        .message {
            font-size: 1.1rem;
            color: #34495e;
            margin: 1rem 0;
        }
        
        .tips {
            margin: 1.5rem 0;
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        #dopay {
            margin-top: 1rem;
        }
        
        #dopay input[type="submit"] {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        #dopay input[type="submit"]:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(52, 152, 219, 0.4);
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
                width: 95%;
            }
            
            .amount {
                font-size: 2rem;
            }
            
            .message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="loader"></div>
    <div class="title">订单创建成功</div>
    <div class="amount">￥<?php echo htmlspecialchars(number_format($_POST['money'], 2)); ?> <small>元</small></div>
    <div class="message">
        正在跳转到<?php 
        $pay_type_names = [
            'alipay' => '支付宝',
            'wxpay' => '微信支付',
            'qqpay' => 'QQ钱包'
        ];
        echo isset($pay_type_names[$_POST['type']]) ? 
             htmlspecialchars($pay_type_names[$_POST['type']]) : 
             htmlspecialchars($_POST['type']);
        ?>支付...
    </div>
    <div class="tips">
        <p>如果页面没有自动跳转，请点击下方按钮</p>
    </div>
    <?php 
    // 修改支付按钮样式
    $html_text = str_replace('</form>', '<input type="submit" value="立即支付" /></form>', $html_text);
    echo $html_text; 
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var form = document.getElementById('dopay');
        if(form) {
            form.submit();
        }
    }, 1500);
});
</script>
</body>
</html>