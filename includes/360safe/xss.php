<?php
/*
 * @Author: yihua
 * @Date: 2025-01-04 17:32:11
 * @LastEditTime: 2025-01-11 13:34:11
 * @LastEditors: yihua
 * @Description: 现代化的Web应用安全防护模块
 * @Version: 2.0.0
 * @Security Level: High
 */

declare(strict_types=1);

// 加载配置文件
require_once('webscan_cache.php');

/*************************************
 * 0. 基础配置和初始化
 *************************************/

// 调试模式（仅在开发环境使用）
$debug_mode = false;

// 初始化日志目录
$log_dir = dirname(dirname(__DIR__)) . '/logs/security';
if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
    error_log("Failed to create log directory: $log_dir");
}

/*************************************
 * 1. 白名单配置
 *************************************/

// 白名单路径配置
$whitelistedPaths = [
    '/sub_admin',
    '/api/cpproxy.php',
    '/',
    '/api/api.php',
    '/install'
];

// IP白名单配置
$allowed_ips = [
    "127.0.0.1",          // 本地测试
    "::1",                // IPv6 本地
    // 添加其他受信任的IP
];

/*************************************
 * 2. 攻击特征库定义
 *************************************/

/**
 * XSS攻击特征
 */
$xssPatterns = [
    // 基础XSS向量
    "[\"'`;\\*<>].*\\bon[a-zA-Z]{3,15}\\s*=.*",
    "<(?:script|iframe|embed|object|style|form|meta|svg|math|xml)[^>]*?>",
    
    // 事件处理
    "\\bon(?:load|error|mouseover|click|submit|focus|blur|change|select)\\s*=",
    
    // 协议处理
    "(?:javascript|data|vbscript|mocha|livescript|blob):",
    
    // 函数调用
    "(?:eval|setTimeout|setInterval|Function|execScript)\\s*\\(",
    
    // URL操作
    "url\\s*\\((?:['\"])*(?:\\#|data:|javascript:|vbscript:)",
    
    // DOM操作
    "(?:document\\.(?:cookie|write|location)|window\\.(?:location|open|eval))",
    
    // 模板注入
    "\\{\\{.*?\\}\\}|\\$\\{.*?\\}",
    
    // 现代框架特定
    "ng-[a-z]+=\".*?\"|v-[a-z]+=\".*?\"|\\[(?:innerHTML|outerHTML)\\]=\".*?\"",
];

/**
 * SQL注入特征
 */
$sqlInjectionPatterns = [
    // 基础SQL注入
    "(?i)(select|update|insert|delete|union|drop|create|alter|truncate|exec|declare|rename)",
    
    // 时间盲注
    "(?i)(sleep\\s*\\([\\s\\d]+\$|benchmark\\s*\\(|pg_sleep|waitfor\\s+delay|delay\\s+'\\d+)",
    
    // 文件操作
    "(?i)(outfile|dumpfile|load_file|into\\s+(?:dump|out)file)",
    
    // 信息收集
    "(?i)(information_schema\\.(?:tables|columns)|sys\\.(?:user_tables|tab)|all_tables)",
    
    // 特权操作
    "(?i)(master\\.\\.|msysaccessobjects|msysqueries|sysobjects|syscolumns|sysusers|xp_cmdshell)",
    
    // NoSQL注入
    "\\$(?:gt|lt|ne|eq|regex|where)|\\{\\s*\\$(?:where|gt|lt|ne|eq)",
    
    // 条件语句
    "(?i)(case\\s+when|if\\s*\\(|substr\\s*\\(|mid\\s*\\(|length\\s*\\()",
];

/**
 * 命令注入特征
 */
$cmdInjectionPatterns = [
    // 命令分隔符
    "(;|\\&\\&|\\|\\||\\||`)",
    
    // 常见命令
    "(?i)(\\b(?:rm|cat|wget|curl|nc|netcat|bash|sh|python|perl|ruby|lua)\\b)",
    
    // PHP函数
    "(?:system|exec|passthru|shell_exec|popen|proc_open|pcntl_exec)\\s*\\(",
    
    // PowerShell
    "(?i)(powershell|iex|invoke-expression|encodedcommand)",
    
    // 环境变量
    "\\$(?:ENV|_ENV|_SERVER|GLOBALS)\\[",
    
    // 反弹shell
    "(?:>|<)\\s*/dev/(?:tcp|udp)/[\\d.]+/\\d+",
];

/**
 * 文件上传特征
 */
$fileUploadPatterns = [
    // 文件类型限制
    "(?i)^(/(?!uploads/).)*\\.(?:php|phtml|php3|php4|php5|phar)$",
    
    // 文件头检查
    "^(?:4D5A|7F454C46|CAFEBABE|FFD8FFE0)",
    
    // 双重扩展名
    "(?i)\\.(?:jpg|gif|png)\\.(php|asp|jsp)$",
    
    // 特殊字符
    "[\\x00-\\x1F\\x7F-\\xFF]",
];

/**
 * 路径穿越特征
 */
$dirTraversalPatterns = [
    // 基础路径穿越
    '#(?<![a-zA-Z0-9_])\\.\\./.*#',
    
    // 编码变种
    '#(?<![a-zA-Z0-9_])(?:%2e%2e|%2e%2e%2f|%252e%252e%252f)#i',
    
    // NULL字节注入
    '#(%00|\\0|\\u0000|\\x00)#',
    
    // 协议限制
    '#(?:file|https?|ftp|php|zlib|data|glob|phar|ssh2|rar|ogg|expect)://(?!localhost)(?!127\\.0\\.0\\.1)#i',
    
    // Windows路径特征
    '#([A-Za-z]:)?\\\\+(?!\\.\\.)(?:windows|system32|boot|temp)\\\\#i',
];

/**
 * SSRF攻击特征
 */
$ssrfPatterns = [
    // 内网IP
    "(?:10\\.|172\\.(?:1[6-9]|2\\d|3[01])\\.|192\\.168\\.)",
    
    // 危险协议
    "(?:gopher|dict|php|ldap|tftp|ftp)://",
    
    // DNS重绑定
    "\\.(?:10|172|192|169)\\.(?:[\\d]{1,3}\\.){2}[\\d]{1,3}",
];

// 合并所有特征
$globalPatterns = array_merge(
    $xssPatterns,
    $sqlInjectionPatterns,
    $cmdInjectionPatterns,
    $fileUploadPatterns,
    $dirTraversalPatterns,
    $ssrfPatterns
);

/*************************************
 * 3. 请求处理和检测
 *************************************/

// 检查IP白名单
$client_ip = $_SERVER['REMOTE_ADDR'];
if (!in_array($client_ip, $allowed_ips)) {
    // 检查请求来源
    $checkParams = [
        'GET'    => $_GET,
        'POST'   => $_POST,
        'COOKIE' => $_COOKIE,
        'FILES'  => $_FILES,
    ];

    // 其他可疑来源
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // 检查白名单路径
    if (!isWhitelistedRequest($whitelistedPaths)) {
        // 检查所有输入
        checkRequestData([$referer, $queryString, $userAgent], $globalPatterns);
        foreach ($checkParams as $type => $data) {
            checkRequestData($data, $globalPatterns);
        }
    }
}

/*************************************
 * 4. 辅助函数定义
 *************************************/

/**
 * 检查请求是否在白名单中
 */
function isWhitelistedRequest(array $paths): bool {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    foreach ($paths as $path) {
        if (strpos($requestUri, $path) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * 递归检查数据
 */
function checkRequestData($data, array $patterns): void {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (!is_array($key)) {
                checkString((string)$key, $patterns);
            } else {
                checkRequestData($key, $patterns);
            }
            if (!is_array($value)) {
                checkString((string)$value, $patterns);
            } else {
                checkRequestData($value, $patterns);
            }
        }
    } else {
        checkString((string)$data, $patterns);
    }
}

/**
 * 核心检测逻辑
 */
function checkString(string $str, array $patterns): void {
    if (trim($str) === '' || mb_strlen($str) < 2) {
        return;
    }

    $encoded = urlencode($str);
    
    foreach ($patterns as $pattern) {
        $safePattern = str_replace('~', '\~', $pattern);
        $regex = "~{$safePattern}~i";
        
        if (preg_match($regex, $str) === 1 || preg_match($regex, $encoded) === 1) {
            logSuspiciousAttempt($str, $pattern);
            denyRequest();
        }
    }
}

/**
 * 记录可疑请求
 */
function logSuspiciousAttempt(string $str, string $pattern): void {
    global $log_dir;
    
    $logFile = $log_dir . '/attack_' . date('Ymd') . '.log';
    
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
        'matched_string' => $str,
        'matched_pattern' => $pattern,
        'request_data' => [
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'files' => array_keys($_FILES),
        ]
    ];
    
    $logContent = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
    
    // 设置日志文件权限
    chmod($logFile, 0600);
}

/**
 * 拒绝请求处理
 */
function denyRequest(): void {
    global $debug_mode;
    
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/html; charset=utf-8');
    
    // 设置安全响应头
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    if ($debug_mode) {
        // 调试模式下显示详细信息
        $error_message = "检测到潜在的安全威胁\n" .
            "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n" .
            "时间: " . date('Y-m-d H:i:s') . "\n" .
            "页面: " . ($_SERVER['PHP_SELF'] ?? 'UNKNOWN') . "\n" .
            "方法: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN');
    } else {
        $error_message = "检测到潜在的安全威胁，请求已被拦截。";
    }
    
    // 使用模板显示错误页面
    include(__DIR__ . '/templates/error.php');
    exit;
}

// 初始化安全模块
if (!headers_sent()) {
    // 设置安全响应头
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
    
    // 设置Cookie安全属性
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    
    // 禁用错误显示
    if (!$debug_mode) {
        error_reporting(0);
        ini_set('display_errors', '0');
  }
}
?>