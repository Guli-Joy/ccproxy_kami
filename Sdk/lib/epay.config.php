<?php
/* *
 * 配置文件
 */

// 引入数据库配置
require_once(dirname(__FILE__) . '/../../config.php');

// 连接数据库
$conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
if ($conn->connect_error) {
    die(json_encode(handleError('Database connection failed')));
}

// 从数据库获取支付配置
$sql = "SELECT merchant_id, merchant_key, api_url FROM pay_config LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    //支付接口地址
    $epay_config['apiurl'] = trim($row['api_url']);
    
    //商户ID
    $epay_config['pid'] = trim($row['merchant_id']);
    
    //商户密钥
    $epay_config['key'] = trim($row['merchant_key']);
    
    // 记录配置信息用于调试
    error_log("Payment config loaded - PID: {$epay_config['pid']}, API URL: {$epay_config['apiurl']}");
} else {
    die(json_encode(handleError('Payment configuration not found')));
}

$conn->close();

function handleError($message, $context = [], $httpCode = 500) {
    $error = [
        'code' => -1,
        'msg' => '系统错误，请稍后重试',
        'time' => date('Y-m-d H:i:s')
    ];
    
    error_log(json_encode([
        'error' => $message,
        'context' => $context,
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR']
    ]));
    
    http_response_code($httpCode);
    return $error;
}

// 对商户密钥进行加密存储
function encryptKey($key, $secret) {
    return openssl_encrypt($key, 'AES-256-CBC', $secret, 0, substr(md5($secret), 0, 16));
}

function decryptKey($encrypted, $secret) {
    return openssl_decrypt($encrypted, 'AES-256-CBC', $secret, 0, substr(md5($secret), 0, 16));
}
