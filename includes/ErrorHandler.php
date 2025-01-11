<?php
class ErrorHandler {
    private static $logger;
    
    public static function init() {
        if (self::$logger === null) {
            self::$logger = Logger::getInstance();
            
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            set_error_handler([self::class, 'handleError']);
            set_exception_handler([self::class, 'handleException']);
            register_shutdown_function([self::class, 'handleFatalError']);
        }
    }
    
    private static function ensureLogger() {
        if (self::$logger === null) {
            self::init();
        }
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        self::ensureLogger();
        self::$logger->systemError($errno, $errstr, $errfile, $errline);
        return true;
    }
    
    public static function handleException($exception) {
        self::ensureLogger();
        self::$logger->exception($exception);
        self::showErrorPage();
    }
    
    public static function handleFatalError() {
        self::ensureLogger();
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private static function showErrorPage() {
        if(!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        include __DIR__ . '/../templates/error.php';
        exit;
    }
    
    public static function handleDBError($error) {
        self::ensureLogger();
        self::$logger->error("数据库错误", ['message' => $error]);
    }
} 