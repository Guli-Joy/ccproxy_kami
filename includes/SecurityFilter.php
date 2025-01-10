<?php
/**
 * 安全过滤类
 * 用于处理输入验证和清理
 */
class SecurityFilter {
    /**
     * XSS过滤
     */
    public static function xssClean($data, $key = '') {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $k => $v) {
                $result[$k] = self::xssClean($v, $k);
            }
            return $result;
        }
        
        // 特殊字段保持原始大小写
        $preserve_case_fields = ['merchant_key', 'key', 'secret', 'token', 'api_key'];
        if (in_array($key, $preserve_case_fields)) {
            // 对于这些字段，只做基本的XSS过滤，不转换大小写
            $data = strval($data);
            $data = strip_tags($data);
            $data = str_replace(
                ['javascript:', 'vbscript:', 'data:', 'alert', 'onclick', 'onerror', '<script', '</script>'],
                ['', '', '', '', '', '', '', ''],
                $data  // 移除 strtolower
            );
            return $data;
        }
        
        // 其他字段的正常处理
        $data = strval($data);
        $data = strip_tags($data);
        $data = str_replace(
            ['javascript:', 'vbscript:', 'data:', 'alert', 'onclick', 'onerror', '<script', '</script>'],
            ['', '', '', '', '', '', '', ''],
            strtolower($data)
        );
        
        // 过滤所有可能的XSS向量
        $data = preg_replace([
            '/\bon\w+\s*=.*?(?=[\s>])/i',  // 事件处理程序
            '/javascript\s*:/i',            // javascript协议
            '/expression\s*\(.*?\)/i',      // CSS表达式
            '/behaviour\s*:.*?(\;|$)/i',    // 行为
            '/vbscript\s*:/i',             // vbscript协议
            '/data\s*:/i',                 // data协议
            '/<[^>]*>/'                    // 所有剩余的HTML标签
        ], '', $data);
        
        // HTML实体编码
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
        
        return $data;
    }
    
    /**
     * SQL注入过滤
     */
    public static function sqlClean($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sqlClean'], $data);
        }
        
        if (is_numeric($data)) {
            // 确保数值类型安全
            return is_float($data) ? floatval($data) : intval($data);
        }
        
        // 使用预处理语句的占位符
        return '?';
    }
    
    /**
     * 文件名安全过滤
     */
    public static function fileNameClean($filename) {
        // 移除路径信息
        $filename = basename($filename);
        
        // 只允许字母数字、点和下划线
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // 防止双扩展名攻击
        $filename = preg_replace('/\.(?![^.]*$)/', '', $filename);
        
        return $filename;
    }
    
    /**
     * 验证上传文件类型
     */
    public static function validateFileType($filename) {
        $allowed_types = unserialize(ALLOWED_FILE_TYPES);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowed_types);
    }
    
    /**
     * 验证密码强度
     */
    public static function validatePassword($password) {
        // 检查长度(至少12位)
        if (strlen($password) < 12) {
            return ['valid' => false, 'message' => '密码长度至少需要12位'];
        }
        
        // 必须包含大小写字母、数字和特殊字符
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => '密码必须包含大写字母'];
        }
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => '密码必须包含小写字母'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => '密码必须包含数字'];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['valid' => false, 'message' => '密码必须包含特殊字符'];
        }
        
        // 检查是否包含常见密码
        if (self::isCommonPassword($password)) {
            return ['valid' => false, 'message' => '密码过于简单,请使用更复杂的密码'];
        }
        
        return ['valid' => true, 'message' => '密码符合要求'];
    }
    
    /**
     * 生成CSRF令牌
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
            $_SESSION['csrf_token_time'] = time();
        }
        
        // 检查令牌是否过期
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_AGE) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 验证CSRF令牌
     */
    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        // 检查令牌是否过期
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_AGE) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 记录安全日志
     */
    public static function logSecurityEvent($event, $level = 'INFO', $context = []) {
        if (!defined('LOG_PATH')) {
            return false;
        }
        
        $log_file = LOG_PATH . 'security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'];
        $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'guest';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
        
        // 添加上下文信息
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $log_entry = sprintf(
            "[%s] [%s] [IP:%s] [User:%s] [UA:%s] [%s %s] %s %s\n",
            $timestamp,
            $level,
            $ip,
            $user,
            $userAgent,
            $requestMethod,
            $requestUri,
            $event,
            $contextStr
        );
        
        // 确保日志文件权限正确
        if (!file_exists($log_file)) {
            touch($log_file);
            chmod($log_file, 0640);
        }
        
        return error_log($log_entry, 3, $log_file);
    }
    
    /**
     * 检查IP是否被封禁
     */
    public static function isIpBanned($ip) {
        $ban_file = LOG_PATH . 'banned_ips.php';
        if (!file_exists($ban_file)) {
            return false;
        }
        
        $banned_ips = include($ban_file);
        if (!is_array($banned_ips)) {
            return false;
        }
        
        return isset($banned_ips[$ip]) && $banned_ips[$ip] > time();
    }
    
    /**
     * 封禁IP
     */
    public static function banIp($ip, $duration = 3600) {
        $ban_file = LOG_PATH . 'banned_ips.php';
        $banned_ips = file_exists($ban_file) ? include($ban_file) : array();
        
        if (!is_array($banned_ips)) {
            $banned_ips = array();
        }
        
        $banned_ips[$ip] = time() + $duration;
        
        $content = "<?php\nreturn " . var_export($banned_ips, true) . ";\n?>";
        return file_put_contents($ban_file, $content);
    }
    
    /**
     * 增强的输入过滤函数
     */
    public static function filterInput($data) {
        if(is_array($data)) {
            return array_map([self::class, 'filterInput'], $data);
        }
        
        $data = trim($data);
        // 保持原有的strip_tags过滤
        $data = strip_tags($data);
        // 增强XSS过滤
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        // 保持原有的addslashes
        $data = addslashes($data);
        // 过滤特殊字符但保留原有功能
        $data = str_replace(
            array('&', '"', '<', '>', '\'', '(', ')', '{', '}', '\\'),
            array('&amp;', '&quot;', '&lt;', '&gt;', '&#039;', '&#40;', '&#41;', '&#123;', '&#125;', '&#92;'),
            $data
        );
        return $data;
    }

    /**
     * 增强的SQL注入防护
     */
    public static function escapeSql($string) {
        if(is_array($string)) {
            return array_map([self::class, 'escapeSql'], $string);
        }
        if(is_numeric($string)) {
            return $string;
        }
        $string = self::filterInput($string);
        return $string;
    }

    /**
     * 检查常见密码
     */
    private static function isCommonPassword($password) {
        $commonPasswords = [
            '123456', 'password', 'qwerty', 'admin123',
            // 添加更多常见密码...
        ];
        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * 密码哈希
     */
    public static function hashPassword($password) {
        $options = [
            'cost' => 12  // bcrypt的cost参数，12是推荐值
        ];
        return password_hash($password, PASSWORD_DEFAULT, $options);
    }

    /**
     * 密码验证
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * 输出时的XSS防护
     */
    public static function safeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'safeOutput'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 预处理参数绑定
     */
    public static function prepareParams($sql, $params) {
        if (!is_array($params)) {
            $params = [$params];
        }
        
        // 验证参数数量是否匹配
        $expectedCount = substr_count($sql, '?');
        if (count($params) !== $expectedCount) {
            throw new Exception('SQL参数数量不匹配');
        }
        
        // 过滤每个参数
        foreach ($params as &$param) {
            if (is_string($param)) {
                // 字符串类型的特殊处理
                $param = self::filterInput($param);
            } elseif (is_numeric($param)) {
                // 数值类型的处理
                $param = is_float($param) ? floatval($param) : intval($param);
            } elseif (is_null($param)) {
                $param = null;
            } else {
                throw new Exception('不支持的参数类型');
            }
        }
        
        return $params;
    }
}
?>
