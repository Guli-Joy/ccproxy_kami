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
$sql = "SELECT siteurl, multi_domain, domain_list FROM sub_admin WHERE state = 1";
$result = $conn->query($sql);
if($result) {
    while($row = $result->fetch_assoc()) {
        $site_urls[] = $row['siteurl'];
        // 添加多域名
        if($row['multi_domain'] == 1 && !empty($row['domain_list'])) {
            $domains = explode("\n", str_replace("\r", "", $row['domain_list']));
            foreach($domains as $domain) {
                $domain = trim($domain);
                if(!empty($domain)) {
                    $site_urls[] = $domain;
                }
            }
        }
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
        if(in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || 
           isset($_GET['trade_status']) || isset($_POST['trade_status']) ||
           strpos($_SERVER['REQUEST_URI'], 'notify_url.php') !== false ||
           strpos($_SERVER['REQUEST_URI'], 'return_url.php') !== false) {
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
                
                // 移除末尾的斜杠进行比较
                $site_url = rtrim($site_url, '/');
                $referer_base = rtrim($referer_full, '/');
                
                if($referer_base === $site_url) {
                    $request_valid = true;
                    break;
                }
                
                // 检查是否为支付相关请求
                if(strpos($_SERVER['REQUEST_URI'], '/Sdk/') === 0) {
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
        // 检查是否为支付相关请求
        if(strpos($_SERVER['REQUEST_URI'], '/Sdk/epayapi.php') !== false ||
           strpos($_SERVER['REQUEST_URI'], '/Sdk/notify_url.php') !== false ||
           strpos($_SERVER['REQUEST_URI'], '/Sdk/return_url.php') !== false) {
            // 支付相关请求特殊处理
            if(isset($_POST['type']) && in_array($_POST['type'], ['alipay', 'wxpay', 'qqpay'])) {
                if(isset($_POST['out_trade_no']) && isset($_POST['money'])) {
                    $request_valid = true;
                }
            }
            // 支付回调验证
            if(isset($_GET['trade_status']) || isset($_POST['trade_status'])) {
                $request_valid = true;
            }
        }
    }
    
    if(!$request_valid) {
        die(json_encode(['code' => -1, 'msg' => 'Unauthorized request']));
    }
} else {
    die(json_encode(['code' => -1, 'msg' => 'Payment configuration not found']));
}

$conn->close();
