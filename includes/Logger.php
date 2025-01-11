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
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function log($level, $message, $context = array()) {
        $date = date('d-M-Y H:i:s T');
        $contextStr = print_r($context, true);
        
        $logEntry = "[$date] Array\n(\n";
        $logEntry .= "    [datetime] => " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= "    [level] => " . strtoupper($level) . "\n";
        $logEntry .= "    [message] => $message\n";
        $logEntry .= "    [ip] => " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $logEntry .= "    [real_ip] => " . (function_exists('x_real_ip') ? x_real_ip() : 'unknown') . "\n";
        $logEntry .= "    [uri] => " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
        $logEntry .= "    [user] => " . ($_SESSION['admin_user'] ?? 'guest') . "\n";
        $logEntry .= "    [user_agent] => " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
        $logEntry .= "    [referer] => " . ($_SERVER['HTTP_REFERER'] ?? '') . "\n";
        $logEntry .= "    [method] => " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n";
        $logEntry .= "    [context] => " . $contextStr . "\n";
        $logEntry .= ")\n\n";
        
        // 添加JSON格式的日志
        $jsonLog = json_encode([
            'datetime' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'real_ip' => function_exists('x_real_ip') ? x_real_ip() : 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user' => $_SESSION['admin_user'] ?? 'guest',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'context' => $context
        ]) . "\n";
        
        file_put_contents($this->logFile, $logEntry . $jsonLog, FILE_APPEND | LOCK_EX);
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