<?php
/*
 * @Description: Web应用安全防护配置文件
 * @Version: 2.0.0
 * @Security Level: High
 */

// 安全开关
$webscan_switch=1;

// 拦截开关
$webscan_post=1;
$webscan_get=1;
$webscan_cookie=1;
$webscan_referre=1;

// API访问限制
$api_limits = [
    'global' => [
        'rate' => 100,  // 每分钟请求次数
        'burst' => 10   // 突发请求数
    ],
    'api' => [
        'rate' => 60,
        'burst' => 5
    ]
];

// 白名单配置
$webscan_white_directory='sub_admin|install|api';  // 使用|分隔多个目录
$webscan_white_url = array(
    'api/api.php' => 'gethostapp',
    'sub_admin/ajax.php' => 'getapp',
    'Sdk/epayapi.php' => ''  // 添加支付接口白名单
);

// 安全配置
$security_config = array(
    // 日志配置
    'enable_logging' => true,                    // 启用安全日志
    'log_path' => dirname(dirname(__DIR__)) . '/logs',  // 日志存储路径
    'log_types' => array(
        'error' => '/error',                     // 错误日志目录
        'security' => '/security',               // 安全日志目录
        'access' => '/access',                   // 访问日志目录
        'audit' => '/audit'                      // 审计日志目录
    ),
    'log_max_size' => 10 * 1024 * 1024,         // 单个日志文件最大大小（10MB）
    'log_rotate_count' => 30,                    // 日志保留天数
    
    // 请求限制
    'max_post_size' => 10 * 1024 * 1024,        // POST请求最大大小（10MB）
    'max_upload_size' => 50 * 1024 * 1024,      // 上传文件最大大小（50MB）
    'allowed_file_types' => 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|zip|rar',  // 允许上传的文件类型
    
    // 内容安全设置
    'enable_xss_protection' => true,             // XSS防护
    'enable_sql_protection' => true,             // SQL注入防护
    'enable_rce_protection' => true,             // 远程代码执行防护
    'enable_upload_protection' => true,          // 文件上传防护
    
    // 访问控制
    'enable_ip_whitelist' => false,              // 启用IP白名单
    'ip_whitelist' => array(),                   // IP白名单列表
    'enable_ip_blacklist' => true,               // 启用IP黑名单
    'ip_blacklist' => array(),                   // IP黑名单列表
    
    // CSRF防护
    'enable_csrf_protection' => true,            // CSRF防护开关
    'csrf_token_expire' => 7200,                 // CSRF Token过期时间（秒）
    
    // 会话安全
    'session_timeout' => 1800,                   // 会话超时时间（30分钟）
    'session_regenerate_id' => true,             // 定期重新生成会话ID
    'session_use_strict_mode' => true,           // 启用会话严格模式
    
    // 密码策略
    'password_min_length' => 8,                  // 密码最小长度
    'password_require_special' => true,          // 要求包含特殊字符
    'password_require_number' => true,           // 要求包含数字
    'password_require_uppercase' => true,        // 要求包含大写字母
    
    // 验证码配置
    'enable_captcha' => true,                    // 启用验证码
    'captcha_length' => 6,                       // 验证码长度
    'captcha_expire' => 300                      // 验证码有效期（5分钟）
);

// 请求频率限制配置
$rate_limit = array(
    'enabled' => true,                           // 启用请求频率限制
    'window' => 60,                              // 时间窗口（秒）
    'max_requests' => 100,                       // 单位时间内最大请求数
    'whitelist_ips' => array(),                  // 不受限制的IP列表
    'log_dir' => dirname(dirname(__DIR__)) . '/logs/security',  // 速率限制日志存储位置
    
    // 针对特定接口的限制
    'api_limits' => array(
        'api/' => array(
            'window' => 60,
            'max_requests' => 60
        ),
        'sub_admin/' => array(
            'window' => 60,
            'max_requests' => 100
        ),
        'login' => array(
            'window' => 300,                     // 5分钟
            'max_requests' => 5                  // 最多5次尝试
        ),
        'register' => array(
            'window' => 3600,                    // 1小时
            'max_requests' => 3                  // 最多3次尝试
        )
    )
);

// 安全响应头配置
$security_headers = array(
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'
);

// 错误处理配置
$error_handling = array(
    'display_errors' => false,                   // 禁止显示错误信息
    'log_errors' => true,                        // 启用错误日志
    'error_reporting' => E_ALL & ~E_NOTICE,      // 错误报告级别
    'custom_error_page' => true,                 // 使用自定义错误页面
    'error_page_path' => '/error.php'            // 自定义错误页面路径
);

// 调试模式（仅在开发环境使用）
$debug_mode = false;

// 如果在调试模式下，修改某些配置
if ($debug_mode) {
    $security_config['enable_logging'] = true;
    $error_handling['display_errors'] = true;
    $rate_limit['enabled'] = false;
}
?>