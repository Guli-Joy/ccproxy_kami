<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>正在跳转到支付页面</title>
	<style type="text/css">
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: "Microsoft YaHei", Arial, sans-serif;
			background: #f8f9fa;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			color: #333;
		}
		
		.container {
			background: white;
			padding: 2rem;
			border-radius: 15px;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
			text-align: center;
			max-width: 90%;
			width: 400px;
			animation: fadeIn 0.5s ease;
		}
		
		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(20px); }
			to { opacity: 1; transform: translateY(0); }
		}
		
		.loader {
			width: 60px;
			height: 60px;
			margin: 0 auto 25px;
			position: relative;
		}
		
		.loader:before,
		.loader:after {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			border-radius: 50%;
			border: 3px solid transparent;
			border-top-color: #3498db;
		}
		
		.loader:before {
			animation: spin 1.5s linear infinite;
		}
		
		.loader:after {
			border: 3px solid #f3f3f3;
		}
		
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		
		.title {
			font-size: 1.5rem;
			font-weight: 500;
			margin-bottom: 1rem;
			color: #2c3e50;
		}
		
		.message {
			font-size: 1rem;
			color: #666;
			line-height: 1.6;
			margin-bottom: 1.5rem;
		}
		
		.tips {
			font-size: 0.9rem;
			color: #999;
			margin-top: 1rem;
			padding-top: 1rem;
			border-top: 1px solid #eee;
		}
		
		#dopay {
			display: none;
		}
		
		.amount {
			font-size: 1.8rem;
			color: #e74c3c;
			font-weight: bold;
			margin: 1rem 0;
		}
		
		.amount small {
			font-size: 1rem;
			color: #666;
		}
	</style>
</head>
<body>
<?php
// 开始会话
session_start();

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
    // 调试日志
    error_log("Session token: " . (isset($_SESSION['payment_token']) ? $_SESSION['payment_token'] : 'not set'));
    error_log("Received token: " . $token);
    
    if (!isset($_SESSION['payment_token']) || empty($token)) {
        error_log("Token validation failed: Token missing");
        return false;
    }
    
    if (!hash_equals($_SESSION['payment_token'], $token)) {
        error_log("Token validation failed: Token mismatch");
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

    // 记录参数和签名过程
    error_log("Payment parameters before sign: " . json_encode($parameter));

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
	<?php echo $html_text; ?>
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