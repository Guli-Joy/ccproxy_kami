<?php
/*
 * Security Configuration File
 * 安全配置文件
 */

// 基础安全配置
define('SECURE_MODE', true);                      // 安全模式开关
define('DEBUG_MODE', true);                      // 调试模式（生产环境应设为false）
define('MAINTENANCE_MODE', false);                // 维护模式

// Session安全配置
ini_set('session.cookie_httponly', 1);           // 防止XSS攻击读取Cookie
ini_set('session.use_strict_mode', 1);           // 启用严格模式
ini_set('session.use_only_cookies', 1);          // 仅使用cookie保存session id
ini_set('session.hash_function', 'sha256');      // 使用更安全的哈希算法
ini_set('session.hash_bits_per_character', 5);   // 增加每个字符的位数
ini_set('session.gc_maxlifetime', 7200);         // 会话过期时间:2小时

// 增强Cookie安全配置
ini_set('session.cookie_samesite', 'Lax');       // 允许同站点访问但增加安全性
ini_set('session.cookie_path', '/');             // 限制Cookie作用路径
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);         // 仅在HTTPS下设置secure
}

// 错误处理配置
error_reporting(E_ALL & ~E_NOTICE);              // 报告除notice外的所有错误
ini_set('display_errors', 0);                    // 生产环境不显示错误
ini_set('log_errors', 1);                        // 开启错误日志
ini_set('error_log', dirname(__DIR__) . '/logs/error.log'); // 错误日志路径

// 创建日志目录
$logDir = dirname(__DIR__) . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0750, true);
}

// 设置日志文件权限
$errorLog = $logDir . '/error.log';
if (!file_exists($errorLog)) {
    touch($errorLog);
    chmod($errorLog, 0640);
}

// 文件上传安全配置
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);      // 最大上传文件大小（5MB）
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/'); // 上传目录

// 允许的文件类型
define('ALLOWED_FILE_TYPES', serialize(array(
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
)));

// 创建上传目录并设置权限
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0750, true);
}

// 文件名随机化函数
function getSecureFileName($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return md5(uniqid(mt_rand(), true)) . '.' . $ext;
}

// 密码安全配置
define('PASSWORD_MIN_LENGTH', 12);
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('PASSWORD_MAX_AGE', 90); // 密码有效期(天)
define('PASSWORD_HISTORY_SIZE', 5); // 记住最近5个密码,防止重复使用

// API安全配置
define('API_RATE_LIMIT', 100);                   // API请求限制（次/小时）
define('API_KEY_LENGTH', 32);                    // API密钥长度
define('JWT_EXPIRE_TIME', 3600);                 // JWT令牌过期时间（秒）

// 防CC攻击配置
define('CC_CHECK_TIME', 60);                     // CC检查时间窗口（秒）
define('CC_MAX_REQUESTS', 100);                  // 允许的最大请求数
define('CC_BAN_TIME', 3600);                    // 封禁时间（秒）

// XSS防护配置
define('XSS_CLEAN', true);
define('HTML_PURIFIER', true);
define('CSP_ENABLED', true);

// SQL注入防护配置
define('PREPARED_STATEMENTS', true);
define('SQL_LOGGING', true);

// 日志配置
define('LOG_LEVEL', 'WARNING');                  // 日志级别
define('LOG_PATH', dirname(__DIR__) . '/logs/'); // 日志目录
define('AUDIT_LOG', true);                       // 审计日志开关

// CSRF防护配置
define('CSRF_PROTECTION', true);                 // CSRF保护开关
define('CSRF_TOKEN_LENGTH', 32);                 // CSRF令牌长度
define('CSRF_TOKEN_AGE', 7200);                 // CSRF令牌有效期（2小时）

// IP白名单配置
$ALLOWED_IPS = array(
    '127.0.0.1',                                // 本地测试
    // 添加其他允许的IP
);

// 管理员IP白名单
$ADMIN_IPS = array(
    '127.0.0.1',                                // 本地管理
    // 添加其他管理员IP
);

// 安全头配置
$SECURITY_HEADERS = array(
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'X-Content-Type-Options' => 'nosniff',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'",
    'Referrer-Policy' => 'strict-origin-when-cross-origin'
);

// 自定义安全函数
function apply_security_headers() {
    global $SECURITY_HEADERS;
    foreach ($SECURITY_HEADERS as $header => $value) {
        header("$header: $value");
    }
}

function is_ip_allowed($ip) {
    global $ALLOWED_IPS;
    return in_array($ip, $ALLOWED_IPS);
}

function is_admin_ip($ip) {
    global $ADMIN_IPS;
    return in_array($ip, $ADMIN_IPS);
}

// 整合360safe配置
$SECURITY_MODULES = [
    '360safe' => true,           // 360网站安全检测
    'xss_filter' => true,        // XSS过滤
    'sql_filter' => true,        // SQL注入过滤
    'upload_filter' => true,     // 文件上传过滤
    'csrf_protection' => true,   // CSRF保护
    'rate_limit' => true         // 请求频率限制
];

// 目录权限配置
define('LOG_DIR_PERMISSION', 0750);
define('LOG_FILE_PERMISSION', 0640);
define('UPLOAD_DIR_PERMISSION', 0750);
define('UPLOAD_FILE_PERMISSION', 0640);

// 日志配置扩展
$LOG_SETTINGS = [
    'security' => [
        'path' => LOG_PATH . 'security/',
        'rotate' => true,
        'max_files' => 30,
        'compress' => true
    ],
    'error' => [
        'path' => LOG_PATH . 'error/',
        'rotate' => true,
        'max_files' => 30,
        'compress' => true
    ],
    'access' => [
        'path' => LOG_PATH . 'access/',
        'rotate' => true,
        'max_files' => 30,
        'compress' => true
    ],
    'audit' => [
        'path' => LOG_PATH . 'audit/',
        'rotate' => true,
        'max_files' => 30,
        'compress' => true
    ]
];

// 防护规则配置
$PROTECTION_RULES = [
    'xss' => [
        'enabled' => true,
        'level' => 'high',
        'custom_rules' => []
    ],
    'sql' => [
        'enabled' => true,
        'level' => 'high',
        'custom_rules' => []
    ],
    'upload' => [
        'enabled' => true,
        'max_size' => UPLOAD_MAX_SIZE,
        'allowed_types' => unserialize(ALLOWED_FILE_TYPES),
        'scan_virus' => true
    ],
    'csrf' => [
        'enabled' => true,
        'token_length' => CSRF_TOKEN_LENGTH,
        'token_age' => CSRF_TOKEN_AGE
    ]
];

// 通知配置
$NOTIFICATION_SETTINGS = [
    'admin_email' => 'admin@example.com',
    'alert_level' => 'high',
    'notification_methods' => ['email', 'log'],
    'throttle' => [
        'enabled' => true,
        'max_notifications' => 10,
        'time_window' => 3600
    ]
];

// 初始化目录结构
function init_directory_structure() {
    $directories = [
        LOG_PATH,
        LOG_PATH . 'security/',
        LOG_PATH . 'error/',
        LOG_PATH . 'access/',
        LOG_PATH . 'audit/',
        UPLOAD_PATH
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, LOG_DIR_PERMISSION, true);
        }
    }
}

// 扩展安全初始化函数
function init_security() {
    global $SECURITY_MODULES, $PROTECTION_RULES, $NOTIFICATION_SETTINGS;
    
    if (SECURE_MODE) {
        // 初始化目录结构
        init_directory_structure();
        
        // 应用安全头
        apply_security_headers();
        
        // 检查维护模式
        if (MAINTENANCE_MODE && !is_admin_ip($_SERVER['REMOTE_ADDR'])) {
            die('系统维护中，请稍后再试...');
        }
        
        // 初始化安全模块
        foreach ($SECURITY_MODULES as $module => $enabled) {
            if ($enabled) {
                init_security_module($module);
            }
        }
        
        // 设置保护规则
        foreach ($PROTECTION_RULES as $type => $rules) {
            if ($rules['enabled']) {
                apply_protection_rules($type, $rules);
            }
        }
        
        // 配置通知系统
        configure_notifications($NOTIFICATION_SETTINGS);
    }
}

// 初始化安全模块
function init_security_module($module) {
    switch ($module) {
        case '360safe':
            require_once __DIR__ . '/360safe/xss.php';
            break;
        case 'xss_filter':
            // 初始化XSS过滤器
            break;
        case 'sql_filter':
            // 初始化SQL注入过滤器
            break;
        case 'upload_filter':
            // 初始化上传过滤器
            break;
        case 'csrf_protection':
            // 初始化CSRF保护
            break;
        case 'rate_limit':
            // 初始化请求频率限制
            break;
    }
}

// 应用保护规则
function apply_protection_rules($type, $rules) {
    switch ($type) {
        case 'xss':
            // 应用XSS保护规则
            break;
        case 'sql':
            // 应用SQL注入保护规则
            break;
        case 'upload':
            // 应用文件上传保护规则
            break;
        case 'csrf':
            // 应用CSRF保护规则
            break;
    }
}

// 配置通知系统
function configure_notifications($settings) {
    // 配置通知系统
    if ($settings['throttle']['enabled']) {
        // 设置通知限制
    }
}

// 开发环境配置
$DEV_MODE = true;  // 开发模式开关

// 开发环境IP白名单
$DEV_ALLOWED_IPS = array(
    '127.0.0.1',          // 本地回环
    '::1',                // IPv6本地
    'localhost',          // 本地域名
    '192.168.*.*',        // 内网IP段
    '172.16.*.*',         // Docker默认网段
    '10.*.*.*'           // 内网IP段
);

// 修改IP白名单配置
$ALLOWED_IPS = array_merge($ALLOWED_IPS, $DEV_ALLOWED_IPS);

// 开发环境下的安全配置调整
if ($DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    if (!defined('LOG_DIR_PERMISSION')) {
        define('LOG_DIR_PERMISSION', 0755);
    }
    if (!defined('LOG_FILE_PERMISSION')) {
        define('LOG_FILE_PERMISSION', 0644);
    }
    $SECURITY_HEADERS['Content-Security-Policy'] = "default-src * 'unsafe-inline' 'unsafe-eval'; img-src * data:";
}

// 执行安全初始化
init_security();
?>
