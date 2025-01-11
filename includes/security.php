<?php
/*
 * 统一安全入口文件
 * 用于集中管理所有安全相关功能
 */

// 加载必要的安全组件
require_once __DIR__ . '/security_config.php';
require_once __DIR__ . '/SecurityFilter.php';
require_once __DIR__ . '/360safe/xss.php';
require_once __DIR__ . '/ErrorHandler.php';

class Security {
    private static $instance = null;
    private $initialized = false;
    private $securityModules = [];
    private $protectionRules = [];
    private $logger;
    
    private function __construct() {
        global $SECURITY_MODULES, $PROTECTION_RULES;
        $this->securityModules = $SECURITY_MODULES ?? [];
        $this->protectionRules = $PROTECTION_RULES ?? [];
        $this->logger = Logger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初始化安全系统
     */
    public function initialize() {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // 1. 初始化基础安全配置
            init_security();
            
            // 2. 设置错误处理
            set_error_handler([ErrorHandler::class, 'handleError']);
            set_exception_handler([ErrorHandler::class, 'handleException']);
            
            // 3. 注册关闭时的清理函数
            register_shutdown_function([self::class, 'cleanup']);
            
            $this->initialized = true;
            return true;
        } catch (Exception $e) {
            error_log("Security initialization failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查IP是否允许访问
     */
    private function isIpAllowed($ip) {
        global $DEV_MODE, $DEV_ALLOWED_IPS, $ALLOWED_IPS;
        
        // 1. 检查是否在白名单中
        if (in_array($ip, $ALLOWED_IPS)) {
            return true;
        }
        
        // 2. 开发模式下的IP检查
        if ($DEV_MODE) {
            // 检查是否为内网IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
            
            foreach ($DEV_ALLOWED_IPS as $allowed) {
                if (strpos($allowed, '*') !== false) {
                    $pattern = str_replace('*', '\d+', $allowed);
                    if (preg_match('/^' . $pattern . '$/', $ip)) {
                        return true;
                    }
                } elseif ($ip === $allowed) {
                    return true;
                }
            }
        }
        
        // 3. 检查是否为合法的公网IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // 检查是否为恶意IP（可以在这里添加IP黑名单检查）
            if (!SecurityFilter::isIpBanned($ip)) {
                return true;  // 允许所有非黑名单的合法公网IP访问
            }
        }
        
        return false;
    }
    
    /**
     * 处理请求
     */
    public function handleRequest() {
        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            $realIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $ip;
            
            // IP检查 - 只要有一个IP通过验证就允许访问
            if (!$this->isIpAllowed($ip) && !$this->isIpAllowed($realIp)) {
                // 记录被拒绝的访问，但不立即阻止
                $this->logSecurityEvent('IP_CHECK', "IP访问记录: $ip, $realIp");
                
                // 检查是否为移动设备访问
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $isMobile = preg_match('/(Mobile|Android|iPhone|iPad)/i', $userAgent);
                
                if ($isMobile) {
                    // 移动设备访问时放行
                    $this->logSecurityEvent('MOBILE_ACCESS', "移动设备访问放行: $ip");
                } else {
                    // 可疑IP的访问频率限制
                    if (!$this->checkRateLimit()) {
                        throw new Exception("请求过于频繁");
                    }
                }
            }
            
            // 其他安全检查
            if (!$this->checkPath($_SERVER['REQUEST_URI'])) {
                throw new Exception("非法访问路径");
            }
            
            // 输入过滤
            $_GET = SecurityFilter::xssClean($_GET);
            $_POST = SecurityFilter::xssClean($_POST);
            $_COOKIE = SecurityFilter::xssClean($_COOKIE);
            
            // CSRF检查
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
                $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                $isSubAdminRequest = strpos($_SERVER['REQUEST_URI'], '/sub_admin/') === 0;
                
                if (!$isApiRequest && !$isSubAdminRequest && !$isAjaxRequest) {
                    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                    if (!$this->validateCsrfToken($csrfToken)) {
                        $this->logSecurityEvent('CSRF_FAILED', "CSRF验证失败", [
                            'uri' => $_SERVER['REQUEST_URI'],
                            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
                        ]);
                        throw new Exception("CSRF验证失败");
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            $this->logSecurityEvent('ERROR', $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查请求频率
     */
    private function checkRateLimit() {
        global $rate_limit;
        return check_rate_limit();
    }
    
    /**
     * 检查访问路径
     */
    private function checkPath($path) {
        global $whitelistedPaths;
        return isWhitelistedRequest($whitelistedPaths);
    }
    
    /**
     * 清理函数
     */
    public static function cleanup() {
        // 清理临时文件
        $temp_dir = sys_get_temp_dir();
        $files = glob($temp_dir . '/rate_limit:*');
        foreach ($files as $file) {
            if (time() - filemtime($file) > 86400) { // 24小时
                @unlink($file);
            }
        }
        
        // 压缩旧日志
        self::compressOldLogs();
    }
    
    /**
     * 压缩旧日志
     */
    private static function compressOldLogs() {
        global $LOG_SETTINGS;
        
        foreach ($LOG_SETTINGS as $type => $settings) {
            if ($settings['rotate'] && $settings['compress']) {
                $log_dir = $settings['path'];
                $files = glob($log_dir . '/*.log');
                
                foreach ($files as $file) {
                    if (time() - filemtime($file) > 86400) { // 24小时
                        $gz_file = $file . '.gz';
                        if (!file_exists($gz_file)) {
                            $data = file_get_contents($file);
                            file_put_contents($gz_file, gzencode($data, 9));
                            unlink($file);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * 生成安全报告
     */
    public function generateSecurityReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'security_status' => $this->checkSecurityStatus(),
            'banned_ips' => $this->getBannedIps(),
            'recent_attacks' => $this->getRecentAttacks(),
            'system_status' => $this->getSystemStatus()
        ];
        
        return $report;
    }
    
    /**
     * 检查安全状态
     */
    private function checkSecurityStatus() {
        $status = [
            'secure_mode' => SECURE_MODE,
            'maintenance_mode' => MAINTENANCE_MODE,
            'security_modules' => $this->securityModules,
            'protection_rules' => $this->protectionRules
        ];
        
        return $status;
    }
    
    /**
     * 获取被封禁的IP列表
     */
    private function getBannedIps() {
        $ban_file = LOG_PATH . 'banned_ips.php';
        return file_exists($ban_file) ? include($ban_file) : [];
    }
    
    /**
     * 获取最近的攻击记录
     */
    private function getRecentAttacks() {
        $log_file = LOG_PATH . 'security/attack_' . date('Ymd') . '.log';
        $attacks = [];
        
        if (file_exists($log_file)) {
            $lines = file($log_file);
            $attacks = array_slice($lines, -100); // 最近100条记录
        }
        
        return $attacks;
    }
    
    /**
     * 获取系统状态
     */
    private function getSystemStatus() {
        return [
            'disk_usage' => disk_free_space(LOG_PATH),
            'memory_usage' => memory_get_usage(true),
            'load_average' => sys_getloadavg(),
            'php_version' => PHP_VERSION
        ];
    }
    
    /**
     * CSRF令牌管理
     */
    private function initCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function getCsrfToken() {
        return $this->initCsrfToken();
    }

    public function validateCsrfToken($token) {
        global $DEV_MODE;
        
        // 开发模式下的特殊处理
        if ($DEV_MODE) {
            // 检查是否为内部Ajax请求
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            
            if (!empty($referer) && !empty($host)) {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                if ($refererHost === $host || $refererHost === 'cs.glbolg.cn') {
                    // 内部Ajax请求，放宽验证
                    return true;
                }
            }
            
            // 检查是否为本地测试请求
            $ip = $_SERVER['REMOTE_ADDR'];
            if ($ip === '127.0.0.1' || $ip === '::1') {
                return true;
            }
        }
        
        // 正常的CSRF验证
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function refreshCsrfToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * 记录安全事件
     */
    private function logSecurityEvent($type, $message, $data = []) {
        if ($this->logger) {
            $this->logger->security($message, array_merge(['event_type' => $type], $data));
        }
    }
}

// 初始化安全系统
$security = Security::getInstance();
$security->initialize(); 