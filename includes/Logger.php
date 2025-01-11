<?php
/**
 * 统一日志管理类
 * 专门用于处理错误日志
 */
class Logger {
    private static $instance = null;
    private $logFile;

    private function __construct() {
        $this->logFile = dirname(__DIR__) . '/logs/error/error.log';
        
        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getClientIPs() {
        $ips = [];
        
        // 获取直接IP
        $direct_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ips[] = $direct_ip;
        
        // 获取真实IP
        if (function_exists('x_real_ip')) {
            $real_ip = x_real_ip();
            if ($real_ip && $real_ip !== $direct_ip) {
                $ips[] = $real_ip;
            }
        }
        
        // 处理代理IP
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $proxy_ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($proxy_ips as $ip) {
                if ($ip && !in_array($ip, $ips)) {
                    $ips[] = $ip;
                }
            }
        }
        
        return array_unique($ips);
    }

    private function log($level, $message, $context = array()) {
        $date = date('d-M-Y H:i:s T');
        $ips = $this->getClientIPs();
        
        // 合并相关的上下文信息
        $context['ips'] = $ips;
        if (isset($context['event_type']) && $context['event_type'] === 'IP_BLOCKED') {
            $message = sprintf("IP访问被拒绝: %s", implode(', ', $ips));
        }
        
        $logData = [
            'datetime' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $ips[0],
            'all_ips' => $ips,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user' => $_SESSION['admin_user'] ?? 'guest',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'context' => $context
        ];
        
        // 生成Array格式日志
        $arrayLog = "[$date] Array\n(\n";
        foreach ($logData as $key => $value) {
            if (is_array($value)) {
                $arrayLog .= "    [$key] => " . print_r($value, true);
            } else {
                $arrayLog .= "    [$key] => $value\n";
            }
        }
        $arrayLog .= ")\n\n";
        
        // 生成JSON格式日志
        $jsonLog = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        // 写入日志
        file_put_contents($this->logFile, $arrayLog . $jsonLog, FILE_APPEND | LOCK_EX);
    }
    
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }
    
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = array()) {
        $this->log('WARNING', $message, $context);
    }
    
    public function security($message, $context = array()) {
        $this->log('SECURITY', $message, $context);
    }
    
    public function debug($message, $context = array()) {
        $this->log('DEBUG', $message, $context);
    }

    private function __clone() {}
    private function __wakeup() {}
} 