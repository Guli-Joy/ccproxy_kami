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
        $preserve_case_fields = [
            'merchant_key', 
            'key', 
            'secret', 
            'token', 
            'api_key',
            'user',         
            'username',     
            'newuser',      
            'olduser',
            'account',      
            'password',     
            'pwd',          
            'qianzhui',     // 卡密前缀
            'kami',         // 卡密字段
            // 网站设置相关字段
            'hostname',     // 网站标题
            'siteurl',      // 主域名
            'domain_list',  // 多域名列表
            'img',          // LOGO链接
            'kf',           // 客服链接
            'pan',          // 网盘链接
            'dayimg',       // 日间背景
            'nightimg',     // 夜间背景
            'wzgg',         // 网站公告
            'user_key',     // 用户密钥
            'logo',         // LOGO设置
            // 支付配置相关字段
            'api_url',      // 支付接口地址
            'merchant_id',  // 商户ID
            'merchant_key', // 商户密钥
            'notify_url',   // 通知回调地址
            'return_url',   // 支付返回地址
            // 应用配置相关字段
            'appcode',      // 应用代码
            'app_name',     // 应用名称
            'appname',      // 应用名称
            'server_address', // 服务器地址
            'server_port',  // 服务器端口
            'download_url', // 下载地址
            'special_notes' // 特殊说明
        ];

        // 检查是否是支付相关请求
        $is_payment_request = false;
        if(isset($_SERVER['REQUEST_URI'])) {
            if(strpos($_SERVER['REQUEST_URI'], '/api/api.php') !== false && 
               (isset($_POST['act']) && in_array(strtolower($_POST['act']), ['createorder', 'createOrder']))) {
                $is_payment_request = true;
            }
        }

        if (in_array($key, $preserve_case_fields) || $is_payment_request) {
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
        
        // 其他字段的正常处理会转换为小写
        $data = strtolower($data);
        
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
        $logger = Logger::getInstance();
        $logger->error($event, array_merge($context, [
            'security_level' => $level,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
        ]));
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
        // 增强XSS过滤，但不转换大小写
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        // 保持原有的addslashes
        $data = addslashes($data);
        // 过滤特殊字符但保留原有功能和大小写
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
