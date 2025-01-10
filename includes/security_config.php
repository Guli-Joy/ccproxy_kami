<?php
/*
 * Security Configuration File
 * 安全配置文件
 */

// 基础安全配置
define('SECURE_MODE', true);                      // 安全模式开关
define('DEBUG_MODE', false);                      // 调试模式（生产环境应设为false）
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

// 初始化安全配置
function init_security() {
    if (SECURE_MODE) {
        // 应用安全头
        apply_security_headers();
        
        // 检查维护模式
        if (MAINTENANCE_MODE && !is_admin_ip($_SERVER['REMOTE_ADDR'])) {
            die('系统维护中，请稍后再试...');
        }
        
        // 创建日志目录
        if (!file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0755, true);
        }
        
        // 创建上传目录
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }
    }
}

// 执行安全初始化
init_security();
?>
