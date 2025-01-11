<?php
webscan_error();
//引用配置文件
require_once('webscan_cache.php');
//get拦截规则
$getfilter = "\\<.+javascript:window\\[.{1}\\\\x|<.*=(&#\\d+?;?)+?>|<.*(data|src)=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|\\b(group_)?concat[\\s\\/\\*]*?\\([^\\)]+?\\)|\bcase[\s\/\*]*?when[\s\/\*]*?\([^\)]+?\)|load_file\s*?\\()|<[a-z]+?\\b[^>]*?\\bon([a-z]{4,})\s*?=|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)|<.*(iframe|frame|style|embed|object|frameset|meta|xml)";
//post拦截规则
$postfilter = "<.*=(&#\\d+?;?)+?>|<.*data=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|\\b(group_)?concat[\\s\\/\\*]*?\\([^\\)]+?\\)|\bcase[\s\/\*]*?when[\s\/\*]*?\([^\)]+?\)|load_file\s*?\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)|<.*(iframe|frame|style|embed|object|frameset|meta|xml)";
//cookie拦截规则
$cookiefilter = "benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\(|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
//referer获取
$webscan_referer = empty($_SERVER['HTTP_REFERER']) ? array() : array('HTTP_REFERER'=>$_SERVER['HTTP_REFERER']);

/**
 *   关闭用户错误提示
 */
function webscan_error() {
  if (ini_get('display_errors')) {
    ini_set('display_errors', '0');
  }
}

/**
 *  数据统计回传
 */
function webscan_slog($log_info) {
    global $security_config;
    
    if (!$security_config['enable_logging']) {
        return true;
    }
    
    try {
        // 验证日志目录
        $log_dir = rtrim($security_config['log_path'], '/');
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir, 0755, true)) {
                error_log("Failed to create log directory: $log_dir");
                return false;
            }
        }
        
        // 验证日志目录可写
        if (!is_writable($log_dir)) {
            error_log("Log directory not writable: $log_dir");
            return false;
        }
        
        $log_file = $log_dir . '/' . date('Y-m-d') . '_security.log';
        
        // 清理和验证日志数据
        $log_info = array_map(function($item) {
            return is_string($item) ? strip_tags($item) : $item;
        }, $log_info);
        
        $log_data = date('Y-m-d H:i:s') . ' | ' . 
            json_encode($log_info, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
            
        return file_put_contents($log_file, $log_data, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Log writing error: " . $e->getMessage());
        return false;
    }
}

/**
 *  参数拆分
 */
function webscan_arr_foreach($arr) {
  static $str;
  static $keystr;
  if (!is_array($arr)) {
    return $arr;
  }
  foreach ($arr as $key => $val ) {
    $keystr=$keystr.$key;
    if (is_array($val)) {

      webscan_arr_foreach($val);
    } else {

      $str[] = $val.$keystr;
    }
  }
  return implode($str);
}

/**
 *  防护提示页
 */
function webscan_pape($message = '检测到潜在的安全威胁') {
    header('Content-Type: text/html; charset=utf-8');
    return <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>站点提示信息</title>
    <style type="text/css">
        html { background: #eee; text-align: center; }
        body {
            background: #fff;
            color: #333;
            font-family: "微软雅黑", "Microsoft YaHei", sans-serif;
            margin: 2em auto;
            padding: 1em 2em;
            max-width: 700px;
            -webkit-box-shadow: 10px 10px 10px rgba(0,0,0,.13);
            box-shadow: 10px 10px 10px rgba(0,0,0,.13);
            opacity: .8
        }
        h1 {
            border-bottom: 1px solid #dadada;
            clear: both;
            color: #666;
            font: 24px "微软雅黑", "Microsoft YaHei", sans-serif;
            margin: 30px 0 0 0;
            padding: 0;
            padding-bottom: 7px
        }
        #error-page { margin-top: 50px }
        h3 { text-align: center }
        .button {
            background: #f7f7f7;
            border: 1px solid #ccc;
            color: #555;
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 26px;
            height: 28px;
            margin: 0;
            padding: 0 10px 1px;
            cursor: pointer;
            border-radius: 3px;
            white-space: nowrap;
        }
    </style>
</head>
<body id="error-page">
    <h3>站点提示信息</h3>
    {$message}
    <p><a href="javascript:history.go(-1)" class="button">返回上一页</a></p>
</body>
</html>
HTML;
}

/**
 *  攻击检查拦截
 */
function webscan_StopAttack($key, $value, $attack_type, $method) {
    global $modern_attack_patterns, $rate_limit, $security_config;
    
    // 参数验证
    if (!is_string($key) && !is_numeric($key)) {
        return false;
    }
    
    // 安全转换
    $key = (string)$key;
    $value = is_array($value) ? webscan_arr_foreach($value) : (string)$value;
    
    try {
        // 请求频率限制检查
        if ($rate_limit['enabled'] && !check_rate_limit()) {
            webscan_slog([
                'ip' => filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP),
                'time' => date('Y-m-d H:i:s'),
                'type' => 'rate_limit'
            ]);
            exit(webscan_pape('请求过于频繁，请稍后再试'));
        }
        
        // 请求大小检查
        if (isset($_SERVER['CONTENT_LENGTH']) && 
            is_numeric($_SERVER['CONTENT_LENGTH']) && 
            $_SERVER['CONTENT_LENGTH'] > $security_config['max_post_size']) {
            exit(webscan_pape('请求数据过大'));
        }
        
        // 检查现代攻击特征
        foreach ($modern_attack_patterns as $pattern_type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match("/$pattern/is", $value) || preg_match("/$pattern/is", $key)) {
                    $attack_info = [
                        'ip' => filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP),
                        'time' => date('Y-m-d H:i:s'),
                        'page' => filter_var($_SERVER["PHP_SELF"], FILTER_SANITIZE_URL),
                        'method' => $method,
                        'type' => $pattern_type,
                        'key' => substr($key, 0, 100),
                        'value' => substr($value, 0, 500),
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? 
                            substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : '',
                        'url' => isset($_SERVER["REQUEST_URI"]) ? 
                            filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL) : ''
                    ];
                    
                    webscan_slog($attack_info);
                    exit(webscan_pape("检测到潜在的{$pattern_type}攻击"));
                }
            }
        }
    } catch (Exception $e) {
        error_log("WebScan Error: " . $e->getMessage());
        return false;
    }
}

/**
 *  拦截目录白名单
 */
function webscan_white($webscan_white_name,$webscan_white_url=array()) {
  $url_path=$_SERVER['SCRIPT_NAME'];
  $url_var=$_SERVER['QUERY_STRING'];
  if (preg_match("/".$webscan_white_name."/is",$url_path)==1&&!empty($webscan_white_name)) {
    return false;
  }
  foreach ($webscan_white_url as $key => $value) {
    if(!empty($url_var)&&!empty($value)){
      if (stristr($url_path,$key)&&stristr($url_var,$value)) {
        return false;
      }
    }
    elseif (empty($url_var)&&empty($value)) {
      if (stristr($url_path,$key)) {
        return false;
      }
    }

  }

  return true;
}

/**
 * 请求频率限制检查
 * 
 * @return bool 如果未超过限制返回true，否则返回false
 */
function check_rate_limit() {
    global $rate_limit;
    
    if (!isset($rate_limit['enabled']) || !$rate_limit['enabled']) {
        return true;
    }

    try {
        $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        if (!$ip) {
            error_log("Invalid IP address detected");
            return false;
        }

        // 检查IP是否在白名单中
        if (isset($rate_limit['whitelist_ips']) && in_array($ip, $rate_limit['whitelist_ips'])) {
            return true;
        }

        // 获取当前请求的路径
        $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $request_path = trim($request_path, '/');

        // 检查是否有特定接口的限制
        foreach ($rate_limit['api_limits'] as $path => $limit) {
            if (strpos($request_path, $path) !== false) {
                return check_specific_rate_limit($ip, $limit['window'], $limit['max_requests'], $path);
            }
        }

        // 使用默认的全局限制
        return check_specific_rate_limit($ip, $rate_limit['window'], $rate_limit['max_requests'], 'global');
    } catch (Exception $e) {
        error_log("Rate limit error: " . $e->getMessage());
        return true; // 出错时默认允许请求
    }
}

/**
 * 检查特定限制的请求频率
 * 
 * @param string $ip IP地址
 * @param int $window 时间窗口（秒）
 * @param int $max_requests 最大请求数
 * @param string $type 限制类型
 * @return bool
 */
function check_specific_rate_limit($ip, $window, $max_requests, $type = 'global') {
    try {
        global $rate_limit, $security_config;
        // 使用项目的logs目录
        $rate_limit_dir = $rate_limit['log_dir'];
        
        // 安全处理类型名称，移除任何非法字符
        $safe_type = preg_replace('/[^a-zA-Z0-9]/', '_', $type);
        $cache_key = "rate_limit_" . $safe_type . "_" . md5($ip);
        
        // 验证日志目录
        if (!is_dir($rate_limit_dir)) {
            if (!@mkdir($rate_limit_dir, 0777, true)) {
                error_log("Rate limit directory creation failed: $rate_limit_dir", 3, $security_config['log_path'] . '/error/error.log');
                return true;
            }
        }
        
        if (!is_writable($rate_limit_dir)) {
            error_log("Rate limit directory not writable: $rate_limit_dir", 3, $security_config['log_path'] . '/error/error.log');
            return true;
        }
        
        $today = date('Y-m-d');
        $cache_file = $rate_limit_dir . DIRECTORY_SEPARATOR . $today . '_' . $cache_key;
        
        if (file_exists($cache_file)) {
            $data = @unserialize(file_get_contents($cache_file));
            if ($data === false || !is_array($data)) {
                @unlink($cache_file);
                $data = ['count' => 1, 'start' => time()];
            } else {
                if (time() - $data['start'] <= $window) {
                    if ($data['count'] >= $max_requests) {
                        // 记录超限访问
                        error_log(
                            json_encode([
                                'time' => date('Y-m-d H:i:s'),
                                'ip' => $ip,
                                'type' => $type,
                                'count' => $data['count'],
                                'limit' => $max_requests
                            ]), 
                            3, 
                            $security_config['log_path'] . '/security/rate_limit.log'
                        );
                        return false;
                    }
                    $data['count']++;
                } else {
                    $data = ['count' => 1, 'start' => time()];
                }
            }
        } else {
            $data = ['count' => 1, 'start' => time()];
        }
        
        $result = @file_put_contents($cache_file, serialize($data), LOCK_EX);
        if ($result === false) {
            error_log(
                "Failed to write rate limit data to file: $cache_file", 
                3, 
                $security_config['log_path'] . '/error/error.log'
            );
            return true;
        }
        
        return true;
    } catch (Exception $e) {
        error_log(
            "Specific rate limit error for {$type}: " . $e->getMessage(),
            3,
            $security_config['log_path'] . '/error/error.log'
        );
        return true;
    }
}

// 定义现代化的攻击模式
$modern_attack_patterns = [
    'xss' => [
        '\\<.+javascript:window\\[.{1}\\\\x',
        '<.*=(&#\\d+?;?)+?>',
        '<.*(data|src)=data:text\\/html.*>',
        '<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b'
    ],
    'sqli' => [
        '\\b(and|or)\\b\\s*?([\\(\\)\'\"\\d]+?=[\\(\\)\'\"\\d]+?|[\\(\\)\'\"a-zA-Z]+?=[\\(\\)\'\"a-zA-Z]+?|>|<|\\s+?[\\w]+?\\s+?\\bin\\b\\s*?\\(|\\blike\\b\\s+?[\"\'])',
        'UNION.+?SELECT\\s*',
        'INSERT\\s+INTO.+?VALUES',
        '(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)'
    ],
    'rce' => [
        '\\b(alert\\(|confirm\\(|expression\\(|prompt\\()',
        'benchmark\\s*?\\(.*\\)|sleep\\s*?\\(.*\\)',
        '\\b(group_)?concat[\\s\\/\\*]*?\\([^\\)]+?\\)',
        'load_file\\s*?\\('
    ]
];

// 安全配置
$security_config = [
    'enable_logging' => true,
    'log_path' => dirname(dirname(__DIR__)) . '/logs',
    'log_types' => [
        'error' => '/error',
        'security' => '/security',
        'access' => '/access',
        'audit' => '/audit'
    ],
    'max_post_size' => 10 * 1024 * 1024, // 10MB
];

// 请求频率限制配置
$rate_limit = [
    'enabled' => true,
    'window' => 60, // 时间窗口（秒）
    'max_requests' => 100, // 最大请求数
    'api_limits' => [
        'api/' => [
            'window' => 60,
            'max_requests' => 60
        ],
        'sub_admin/' => [
            'window' => 60,
            'max_requests' => 100
        ]
    ],
    'log_dir' => dirname(dirname(__DIR__)) . '/logs/security'  // 速率限制日志存储位置
];

// 安全响应头
$security_headers = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
];

// 确保API限制配置存在
if (!isset($api_limits) || !is_array($api_limits)) {
    $api_limits = [
        'global' => [
            'rate' => 100,
            'burst' => 10
        ],
        'api' => [
            'rate' => 60,
            'burst' => 5
        ]
    ];
}

// 临时目录处理
$temp_dir = rtrim(sys_get_temp_dir(), '/\\');
$rate_limit_dir = $temp_dir . DIRECTORY_SEPARATOR . 'rate_limits';

// 创建临时目录（Windows兼容）
if (!is_dir($rate_limit_dir)) {
    if (!@mkdir($rate_limit_dir, 0777, true)) {
        error_log("Failed to create rate limit directory: $rate_limit_dir");
        // 使用备用目录
        $rate_limit_dir = $temp_dir;
    }
}

// 文件操作函数
function saveRateLimit($key, $data) {
    global $rate_limit_dir;
    $file = $rate_limit_dir . DIRECTORY_SEPARATOR . 'rate_limit:' . md5($key);
    $old_umask = umask(0);
    $result = @file_put_contents($file, serialize($data));
    if ($result !== false) {
        @chmod($file, 0644);
    }
    umask($old_umask);
    return $result !== false;
}

// 检查请求频率
function checkRequestRate($key) {
    global $api_limits;
    
    if (empty($api_limits) || !is_array($api_limits)) {
        return true; // 如果配置不存在，默认允许请求
    }
    
    $limit = $api_limits[$key] ?? $api_limits['global'] ?? null;
    if (!$limit) {
        return true;
    }
    
    // 实现请求频率检查逻辑
    $current_time = time();
    $rate_key = "rate:$key:" . $_SERVER['REMOTE_ADDR'];
    
    return true; // 临时返回true，实际应实现完整的频率检查
}

if ($webscan_switch&&webscan_white($webscan_white_directory,$webscan_white_url)) {
  if ($webscan_get) {
    foreach($_GET as $key=>$value) {
      webscan_StopAttack($key,$value,$getfilter,"GET");
    }
  }
  if ($webscan_post) {
    foreach($_POST as $key=>$value) {
      webscan_StopAttack($key,$value,$postfilter,"POST");
    }
  }
  if ($webscan_cookie) {
    foreach($_COOKIE as $key=>$value) {
      webscan_StopAttack($key,$value,$cookiefilter,"COOKIE");
    }
  }
  if ($webscan_referre) {
    foreach($webscan_referer as $key=>$value) {
      webscan_StopAttack($key,$value,$postfilter,"REFERRER");
    }
  }
}

// 设置安全响应头
foreach ($security_headers as $header => $value) {
    if (is_string($header) && is_string($value)) { // 检查是否为字符串
        $headerValue = trim($value); // Remove any leading/trailing whitespace
        if (strpos($headerValue, "\n") === false && strpos($headerValue, "\r") === false) {
            header("$header: $headerValue");
        } else {
            error_log('Invalid header value detected: ' . $headerValue);
        }
    } else {
        error_log("Invalid header: $header or value: $value");
    }
}

?>