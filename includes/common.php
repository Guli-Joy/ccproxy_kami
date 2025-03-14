<?php

if (defined('IN_COMMON')) {
    return;
}
define('IN_COMMON', true);

error_reporting(0);
if (defined('IN_CRONLITE')) {
    return null;
}
define('CACHE_FILE', 0);
define('IN_CRONLITE', true);
define('VERSION', 'v4');//版本号
define('SYSTEM_ROOT', dirname(__FILE__).'/');//定义域名泛解析用于访问文件
define('ROOT', dirname(SYSTEM_ROOT).'/');
define('SYS_KEY', 'guliiccp');//定义一个常量
define('CC_Defender', 1); //防CC攻击开关(1为session模式)
define('is_defend', true); //防CC攻击开关(1为session模式)
define('TIMESTAMP',time());
date_default_timezone_set("PRC");
$site_url = $_SERVER['HTTP_HOST'];
$date = date('Y-m-d H:i:s');
session_start();
$islogin=-1;
$scriptpath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$sitepath = substr($scriptpath, 0, strrpos($scriptpath, '/'));
include_once(SYSTEM_ROOT.'function.php');
//360安全
$siteurl = ($_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $sitepath . '/';
if (is_file(SYSTEM_ROOT . '360safe/360webscan.php')) {
    include_once SYSTEM_ROOT . '360safe/360webscan.php';
    include_once SYSTEM_ROOT . '360safe/xss.php';
}

//判断是否开启防CC
if ((is_defend==true || CC_Defender==3) && !defined('SKIP_CC_CHECK')) {
    if ((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(!isset($_SERVER['HTTP_X_REQUESTED_WITH']))!='XMLHttpRequest')) {
        include_once(SYSTEM_ROOT.'txprotect.php');
    }
    if ((CC_Defender==1 && check_spider()==false)) {
    }
    if (((CC_Defender==1 && check_spider()==false) || CC_Defender==3)) {
       cc_defender();
    }
}

// 加载必要的安全组件
require_once(SYSTEM_ROOT.'Logger.php');
require_once(SYSTEM_ROOT.'security.php');

// 初始化日志系统
$logger = Logger::getInstance();

// 初始化安全系统
$security = Security::getInstance();
if (!$security->initialize()) {
    $logger->error('安全系统初始化失败');
    die('安全系统初始化失败');
}

// 处理请求安全检查
if (!$security->handleRequest()) {
    $logger->security('请求被拦截', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'uri' => $_SERVER['REQUEST_URI']
    ]);
    die('请求被拦截');
}

// 加载其他组件
require_once(SYSTEM_ROOT.'security_config.php');
require_once(SYSTEM_ROOT.'SecurityFilter.php');

// 对所有输入进行安全过滤
$_GET = SecurityFilter::xssClean($_GET);
$_POST = SecurityFilter::xssClean($_POST);
$_COOKIE = SecurityFilter::xssClean($_COOKIE);

// 检查IP是否被封禁
if (SecurityFilter::isIpBanned($_SERVER['REMOTE_ADDR'])) {
    $logger->security('IP已被封禁', ['ip' => $_SERVER['REMOTE_ADDR']]);
    die('您的IP已被封禁，请稍后再试');
}

//判断
if (!file_exists(ROOT . 'config.php')) {
    header('Content-type:text/html;charset=utf-8');
	//echo '你还没安装！<a href="install/">点此安装</a>';
	echo '<!DOCTYPE html> <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN"> <head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>站点提示信息</title> <style type="text/css"> html{background:#eee;text-align: center;}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",,sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333} </style> </head> <body id="error-page"> <h3>站点提示信息</h3><h2>你还没安装！<a href="install/">点此安装</a><br></h2> </body> </html>';
	exit(0);
}

require ROOT.'config.php';

if(!defined('SQLITE') && (!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']))//检测安装
{
header('Content-type:text/html;charset=utf-8');
//echo '你还没安装！<a href="install/">点此安装</a>';
echo '<!DOCTYPE html> <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN"> <head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>站点提示信息</title> <style type="text/css"> html{background:#eee;text-align: center;}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",,sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333} </style> </head> <body id="error-page"> <h3>站点提示信息</h3><h2>你还没安装！<a href="install/">点此安装</a><br></h2> </body> </html>';
exit(0);
}

// 连接数据库
include_once SYSTEM_ROOT . 'dbhelp.php';
try {
    $DB = new SpringMySQLi($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname']);
    error_log("数据库连接状态：成功");
    
    $sql = 'SELECT * FROM `sub_admin`';
    $count = $DB->select($sql)==NULL ? array() : $DB->select($sql);
    $installcheck = count($count)>0 ? true : false;
    
    if ($installcheck == false) {
        error_log("数据库未安装");
        @header('Content-Type: text/html; charset=UTF-8');
        exit('<script>alert("检测到您的数据库并未安装我们系统，自动为您跳转安装界面!");window.location.href="../install";</script>');
    }
    
    error_log("数据库检查完成：已安装");
} catch (Exception $e) {
    error_log("数据库操作失败：" . $e->getMessage());
    die("数据库操作失败");
}

$password_hash='!@#%!s!0';
include_once SYSTEM_ROOT . 'authcode.php';
define('authcode', $authcode);

include_once SYSTEM_ROOT . 'member.php';

$clientip = x_real_ip();
$cookiesid = isset($_COOKIE['mysid'])?$_COOKIE['mysid']:false;//获取mysid
if (!$cookiesid || !preg_match('/^[0-9a-z]{32}$/i', $cookiesid)) {
    $cookiesid = md5(uniqid(mt_rand(), 1) . time());
    setcookie('mysid', $cookiesid, time() + 604800, '/'); //设置一个MYID
}

$current_host = $_SERVER['HTTP_HOST'];

// 首先尝试通过主域名查找
$subconf = $DB->selectRow('SELECT * FROM sub_admin WHERE siteurl=\'' . $DB->escape($current_host) . '\' limit 1');

// 如果主域名未找到,且域名包含端口,尝试匹配不带端口的域名
if($subconf == NULL && strpos($current_host, ':') !== false) {
    $host_without_port = explode(':', $current_host)[0];
    $subconf = $DB->selectRow('SELECT * FROM sub_admin WHERE siteurl=\'' . $DB->escape($host_without_port) . '\' limit 1');
}

// 如果还是未找到,检查是否在多域名列表中
if($subconf == NULL) {
    $sql = "SELECT * FROM sub_admin WHERE multi_domain=1 AND domain_list IS NOT NULL";
    $sites = $DB->select($sql);
    if($sites) {
        foreach($sites as $site) {
            $domains = explode("\n", str_replace("\r", "", $site['domain_list']));
            foreach($domains as $domain) {
                $domain = trim($domain);
                if(!empty($domain)) {
                    // 完全匹配
                    if($domain === $current_host) {
                        $subconf = $site;
                        break 2;
                    }
                    // 如果访问域名带端口,尝试匹配不带端口的域名
                    if(strpos($current_host, ':') !== false) {
                        $host_without_port = explode(':', $current_host)[0];
                        if($domain === $host_without_port) {
                            $subconf = $site;
                            break 2;
                        }
                    }
                }
            }
        }
    }
}

if($subconf == NULL) {
    sysmsg('<h2>您的站点没有绑定,请联系管理员绑定域名<b style="color:red;">'.$current_host.'</b><br/>', true);
    exit(0);
}

// 安全检查
if(!isset($subconf['state']) || $subconf['state'] != 1) {
    sysmsg('<h2>您的站点违反规定,现已被管理员关闭.<br/>', true);
    exit(0);
}

if(!isset($subconf['over_date']) || $date > $subconf['over_date']) {
    sysmsg('<h2>您的站点已到期,请联系管理员续费.<br/>', true);
    exit(0);
}

// 记录访问日志
$logger->info('Domain Access', [
    'host' => $current_host,
    'ip' => $clientip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);

// 修改域名验证函数
if (!function_exists('isValidDomain')) {
    function isValidDomain($domain, $subconf) {
        // 移除端口号进行比较
        $domain = explode(':', $domain)[0];
        $site_url = explode(':', $subconf['siteurl'])[0];
        
        // 检查主域名
        if($domain === $site_url) {
            return true;
        }
        
        // 检查多域名列表
        if($subconf['multi_domain'] == 1 && !empty($subconf['domain_list'])) {
            $domains = explode("\n", str_replace("\r", "", $subconf['domain_list']));
            foreach($domains as $allowed_domain) {
                $allowed_domain = trim($allowed_domain);
                if(!empty($allowed_domain)) {
                    // 移除端口号进行比较
                    $allowed_domain = explode(':', $allowed_domain)[0];
                    if($domain === $allowed_domain) {
                        return true;
                    }
                }
            }
        }
        
        // 支付回调特殊处理
        if(strpos($_SERVER['REQUEST_URI'], '/Sdk/epayapi.php') !== false || 
           strpos($_SERVER['REQUEST_URI'], '/Sdk/notify_url.php') !== false || 
           strpos($_SERVER['REQUEST_URI'], '/Sdk/return_url.php') !== false) {
            return true;
        }
        
        return false;
    }
}

// 修改安全检查部分
if(isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $forwarded_host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    if(!isValidDomain($forwarded_host, $subconf)) {
        $logger->security('Suspicious Host Header', [
            'forwarded_host' => $forwarded_host,
            'current_host' => $current_host
        ]);
        sysmsg('非法的域名访问', true);
        exit(0);
    }
}

// 修改 Referer 检查
if(isset($_SERVER['HTTP_REFERER'])) {
    $referer_info = parse_url($_SERVER['HTTP_REFERER']);
    $referer_host = $referer_info['host'] ?? '';
    
    if($referer_host && !isValidDomain($referer_host, $subconf)) {
        // 检查是否为支付相关请求
        if(strpos($_SERVER['REQUEST_URI'], '/Sdk/') === false) {
            $logger->security('Invalid Referer', [
                'referer' => $_SERVER['HTTP_REFERER'],
                'current_host' => $current_host
            ]);
            // 仅记录日志，不阻止请求
        }
    }
}

if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false ) {//&& $xxs['qqtz'] == 1 判断站点开启QQ跳转
    include_once ROOT . 'jump.php';
    exit(0);
}


if(count($subconf)<=0){
    sysmsg('<h2>您的站点没有开通,请联系管理员.<br/>', true);
}
if ($subconf) {
    $conf = $subconf;
    if ($date > $conf['over_date']) {
        sysmsg('<h2>您的站点已到期,请联系管理员续费.<br/>', true);
    }
}
if ($subconf) {
    $conf = $subconf;
    if ($conf['state'] == 0) {
        sysmsg('<h2>您的站点违反规定,现已被管理员关闭.<br/>', true);
    }
}
//数据库更新
// if ($install == false) {
//     if (!($xxs['version'] >= VERSION)) {
//         echo '您尚未更新数据库，请立即<a href="/install/updata.php">前往更新</a>';
//         exit(0);
//     }
// }

if (!function_exists('checkAdminLogin')) {
    function checkAdminLogin() {
        global $DB;
        if(!isset($_SESSION['is_login']) || !$_SESSION['is_login']) {
            if(isset($_COOKIE["sub_admin_token"])) {
                $token = $_COOKIE["sub_admin_token"];
                $token_array = explode("\t", authcode($token, 'DECODE', SYS_KEY));
                if($token_array && count($token_array) >= 2) {
                    $username = $token_array[0];
                    $session = $token_array[1];
                    
                    try {
                        // 验证token
                        $row = $DB->selectRow("SELECT * FROM sub_admin WHERE username='" . 
                            $DB->escape($username) . "' AND cookies='" . $DB->escape($token) . "' LIMIT 1");
                        
                        if($row) {
                            $_SESSION['is_login'] = true;
                            $_SESSION['admin_user'] = $username;
                            return true;
                        }
                    } catch (Exception $e) {
                        error_log("Login check error: " . $e->getMessage());
                        return false;
                    }
                }
            }
            return false;
        }
        return true;
    }
}

// 修改登录状态检查
$islogin = checkAdminLogin() ? 1 : -1;

// 初始化错误处理
require_once __DIR__ . '/ErrorHandler.php';
ErrorHandler::init();

// 修改 CSP 头，放宽支付相关域名的限制
$csp_domains = [];
if($subconf['multi_domain'] == 1 && !empty($subconf['domain_list'])) {
    $domains = explode("\n", str_replace("\r", "", $subconf['domain_list']));
    foreach($domains as $domain) {
        $domain = trim($domain);
        if(!empty($domain)) {
            $csp_domains[] = "https://{$domain}";
            $csp_domains[] = "http://{$domain}";
        }
    }
}

// 添加支付相关域名
$csp_domains[] = "https://*.alipay.com";
$csp_domains[] = "https://*.qq.com";
$csp_domains[] = "https://*.weixin.qq.com";

$csp_domains = implode(' ', array_unique($csp_domains));
$csp = "Content-Security-Policy: default-src 'self' {$csp_domains}; " .
       "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net {$csp_domains}; " .
       "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net {$csp_domains}; " .
       "img-src 'self' data: https: {$csp_domains}; " .
       "form-action 'self' {$csp_domains}; " .  // 添加form-action
       "font-src 'self' data: https: {$csp_domains};";

header($csp);
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// 检查函数是否已定义
if (!function_exists('logSecurityEvent')) {
    // 创建安全日志记录函数
    function logSecurityEvent($type, $message, $data = []) {
        global $logger;
        $logger->security($message, array_merge(['event_type' => $type], $data));
    }
}

// 标识是否为支付相关请求
$is_payment_request = false;
if(strpos($_SERVER['REQUEST_URI'], '/Sdk/') === 0) {
    $is_payment_request = true;
}
