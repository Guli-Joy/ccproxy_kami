<?php
/* *
 * 配置文件
 */

// 防止直接访问
if(!defined('IN_SYSTEM')) {
    define('IN_SYSTEM', true);
}

// 引入数据库配置
require_once(dirname(__FILE__) . '/../../config.php');

// 连接数据库
$conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
if ($conn->connect_error) {
    die(json_encode(['code' => -1, 'msg' => 'Database connection failed']));
}

// 获取站点配置
$site_urls = [];
$sql = "SELECT siteurl FROM sub_admin WHERE state = 1";
$result = $conn->query($sql);
if($result) {
    while($row = $result->fetch_assoc()) {
        $site_urls[] = $row['siteurl'];
    }
}

// 获取支付配置
$sql = "SELECT merchant_id, merchant_key, api_url FROM pay_config WHERE status = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $epay_config['apiurl'] = trim($row['api_url']);
    $epay_config['pid'] = trim($row['merchant_id']);
    $epay_config['key'] = trim($row['merchant_key']);
    
    // 验证请求来源
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $request_valid = false;
    $referer_host = '';
    $referer_full = '';
    
    if(empty($referer)) {
        // 如果是内网访问或支付回调，允许空referer
        if(in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || isset($_GET['trade_status'])) {
            $request_valid = true;
        }
    } else {
        $referer_info = parse_url($referer);
        $referer_host = isset($referer_info['host']) ? $referer_info['host'] : '';
        $referer_scheme = isset($referer_info['scheme']) ? $referer_info['scheme'] : 'http';
        $referer_port = isset($referer_info['port']) ? ':'.$referer_info['port'] : '';
        $referer_full = $referer_scheme . '://' . $referer_host . $referer_port;
        
        // 检查是否来自授权的站点
        foreach($site_urls as $site_url) {
            // 确保站点URL包含协议
            if(strpos($site_url, 'http') !== 0) {
                $site_url = 'http://' . $site_url;
            }
            
            // 处理站点URL
            $site_info = parse_url($site_url);
            if($site_info === false) {
                continue;
            }
            
            $site_host = isset($site_info['host']) ? $site_info['host'] : '';
            if(empty($site_host)) {
                continue;
            }
            
            // 比较主机名（忽略协议和端口）
            if($referer_host === $site_host) {
                $request_valid = true;
                break;
            }
        }
        
        // 如果主机名匹配失败，尝试完整URL匹配
        if(!$request_valid) {
            foreach($site_urls as $site_url) {
                if(strpos($site_url, 'http') !== 0) {
                    $site_url = 'http://' . $site_url;
                }
                if(rtrim($referer, '/') === rtrim($site_url, '/')) {
                    $request_valid = true;
                    break;
                }
            }
        }
        
        // 检查是否来自支付平台
        if(!$request_valid && !empty($epay_config['apiurl'])) {
            $pay_platform_info = parse_url($epay_config['apiurl']);
            if($pay_platform_info !== false) {
                $pay_platform_host = isset($pay_platform_info['host']) ? $pay_platform_info['host'] : '';
                if(!empty($pay_platform_host) && $referer_host === $pay_platform_host) {
                    $request_valid = true;
                }
            }
        }
    }
    
    // 如果不是有效请求源，记录并返回错误
    if(!$request_valid) {
        die(json_encode(['code' => -1, 'msg' => 'Unauthorized request']));
    }
} else {
    die(json_encode(['code' => -1, 'msg' => 'Payment configuration not found']));
}

$conn->close();
