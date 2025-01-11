<?php
class ErrorHandler {
    public static function init() {
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleFatalError']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        $logger = Logger::getInstance();
        
        $context = [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
        
        // 使用error方法替代systemError
        $logger->error($errstr, $context);
        
        return false;
    }

    public static function handleException($exception) {
        $logger = Logger::getInstance();
        
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        // 使用error方法替代exception
        $logger->error($exception->getMessage(), $context);
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
} 