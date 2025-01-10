<?php
class ErrorHandler {
    private static $logPath = __DIR__ . '/../logs/';
    
    public static function init() {
        $logDir = self::$logPath;
        
        // 检查并创建日志目录
        if (!file_exists($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                die('Unable to create log directory');
            }
        }
        
        // 检查日志目录权限
        if (!is_writable($logDir)) {
            die('Log directory is not writable');
        }
        
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $error = [
            'type' => 'ERROR',
            'datetime' => date('Y-m-d H:i:s'),
            'errno' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uri' => $_SERVER['REQUEST_URI'],
            'user' => isset($_SESSION['user']) ? $_SESSION['user'] : 'guest'
        ];
        
        self::logError($error);
        return true;
    }
    
    public static function handleException($exception) {
        $error = [
            'type' => 'EXCEPTION',
            'datetime' => date('Y-m-d H:i:s'),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uri' => $_SERVER['REQUEST_URI'],
            'user' => isset($_SESSION['user']) ? $_SESSION['user'] : 'guest'
        ];
        
        self::logError($error);
        
        // 显示友好的错误页面
        self::showErrorPage();
    }
    
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private static function logError($error) {
        $logFile = self::$logPath . 'error.log';
        
        // 检查日志文件大小
        if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
            $backupFile = self::$logPath . 'error.log.' . date('Y-m-d-H-i-s');
            rename($logFile, $backupFile);
            
            // 保留最近的10个备份文件
            $backupFiles = glob(self::$logPath . 'error.log.*');
            if (count($backupFiles) > 10) {
                array_map('unlink', array_slice($backupFiles, 0, -10));
            }
        }
        
        error_log(json_encode($error, JSON_UNESCAPED_UNICODE) . "\n", 3, $logFile);
    }
    
    private static function showErrorPage() {
        if(!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        include __DIR__ . '/../templates/error.php';
        exit;
    }
    
    public static function handleDBError($error) {
        $errorInfo = [
            'type' => 'DATABASE_ERROR',
            'datetime' => date('Y-m-d H:i:s'),
            'message' => $error,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uri' => $_SERVER['REQUEST_URI'],
            'user' => isset($_SESSION['user']) ? $_SESSION['user'] : 'guest'
        ];
        
        self::logError($errorInfo);
    }
} 