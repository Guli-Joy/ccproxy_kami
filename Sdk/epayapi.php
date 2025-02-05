<?php
// 引入安全配置
if(!defined('IN_COMMON')) {
    require_once(dirname(__DIR__).'/includes/common.php');
}

// 检查请求安全性
if (!$security->handleRequest()) {
    // 检查是否为支付回调
    $is_payment_callback = false;
    if (isset($_POST['type']) && in_array($_POST['type'], ['alipay', 'wxpay', 'qqpay'])) {
        if (isset($_POST['out_trade_no']) && isset($_POST['money'])) {
            $is_payment_callback = true;
        }
    }
    
    if (!$is_payment_callback) {
        die('非法请求');
    }
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
    // 获取当前请求的主机名和来源
    $current_host = $_SERVER['HTTP_HOST'];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $referer_info = parse_url($referer);
    $referer_host = $referer_info['host'] ?? '';
    
    // 获取站点配置 - 使用单一查询
    $sql = "SELECT * FROM sub_admin WHERE state = 1 AND (siteurl = ? OR (multi_domain = 1 AND domain_list LIKE ?)) LIMIT 1";
    $domain_pattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $referer_host) . '%';
    $row = $DB->getRow($sql, [$referer_host, $domain_pattern]);
    
    // 如果通过 referer 未找到，尝试使用当前域名
    if (!$row) {
        $row = $DB->getRow("SELECT * FROM sub_admin WHERE state = 1 AND siteurl = ? LIMIT 1", [$current_host]);
    }
    
    // 如果还是未找到，尝试本地测试环境
    if (!$row && in_array($current_host, ['localhost', '127.0.0.1'])) {
        $row = $DB->getRow("SELECT * FROM sub_admin WHERE state = 1 ORDER BY id ASC LIMIT 1");
    }
    
    if (!$row) {
        throw new Exception('未找到站点配置');
    }
    
    // 验证参数
    validateParams($_POST);
    
    // 检查是否为支付回调
    $is_payment_callback = false;
    if (isset($_POST['type']) && in_array($_POST['type'], ['alipay', 'wxpay', 'qqpay'])) {
        if (isset($_POST['out_trade_no']) && isset($_POST['money'])) {
            $is_payment_callback = true;
        }
    }
    
    // 验证支付令牌
    if (!isset($_POST['token']) || !validateToken($_POST['token'])) {
        if (!$is_payment_callback) {
            throw new Exception('支付令牌验证失败');
        }
    }
    
    // 确定要使用的域名
    $use_domain = $row['siteurl']; // 默认使用主域名
    
    // 如果当前访问域名不是主域名，检查是否可以使用当前域名
    if ($current_host !== $row['siteurl']) {
        if ($row['multi_domain'] == 1 && !empty($row['domain_list'])) {
            $domains = explode("\n", str_replace("\r", "", $row['domain_list']));
            foreach ($domains as $domain) {
                $domain = trim($domain);
                if (!empty($domain)) {
                    // 移除端口号进行比较
                    $current_host_base = explode(':', $current_host)[0];
                    $domain_base = explode(':', $domain)[0];
                    
                    if ($current_host_base === $domain_base) {
                        // 当前域名在允许列表中，使用当前域名
                        $use_domain = $current_host;
                        break;
                    }
                }
            }
        }
        
        // 如果当前域名不在允许列表中且不是主域名，拦截请求
        if ($use_domain !== $current_host && $current_host !== $row['siteurl']) {
            throw new Exception('非法的域名访问');
        }
    }
    
    // 确定协议
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    
    // 构造支付参数，使用确定的域名
    $parameter = array(
        "pid" => trim($epay_config['pid']),
        "type" => trim($_POST['type']),
        "out_trade_no" => trim($_POST['out_trade_no']),
        "notify_url" => $scheme . $use_domain . "/SDK/notify_url.php",
        "return_url" => $scheme . $use_domain . "/SDK/return_url.php",
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
        'msg' => $e->getMessage()
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