<?php
/**
 * 统一日志管理类
 * 专门用于处理错误日志
 */
class Logger {
    private static $instance = null;
    private $logPath;
    private $maxFileSize = 10485760; // 10MB
    private $maxBackupFiles = 30;
    private $logFile;
    private $devMode;

    private function __construct() {
        global $DEV_MODE;
        $this->devMode = $DEV_MODE ?? false;
        $this->logPath = dirname(__DIR__) . '/logs/';
        $this->logFile = $this->logPath . 'error.log';
        $this->initLogDirectory();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initLogDirectory() {
        if (!file_exists($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true)) {
                die('无法创建日志目录');
            }
        }

        if (!is_writable($this->logPath)) {
            die('日志目录不可写');
        }

        // 设置日志文件权限
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0640);
        }
    }

    /**
     * 记录错误日志
     * @param string $message 错误信息
     * @param array $context 上下文信息
     */
    public function error($message, array $context = []) {
        $this->writeLog('ERROR', $message, $context);
    }

    /**
     * 记录系统错误
     * @param int $errno 错误号
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行号
     */
    public function systemError($errno, $errstr, $errfile, $errline) {
        $context = [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
        $this->error($errstr, $context);
    }

    /**
     * 记录异常
     * @param Exception $exception 异常对象
     */
    public function exception($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        $this->error($exception->getMessage(), $context);
    }

    /**
     * 记录安全相关的日志
     * @param string $message 安全事件信息
     * @param array $context 上下文信息
     */
    public function security($message, array $context = []) {
        $context['security_event'] = true;
        $this->writeLog('SECURITY', $message, $context);
    }

    /**
     * 写入日志
     * @param string $level 日志级别
     * @param string $message 日志信息
     * @param array $context 上下文信息
     */
    private function writeLog($level, $message, array $context = []) {
        // 检查文件大小，如果超过限制则进行轮转
        $this->rotateLogFile();

        $logEntry = [
            'datetime' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'real_ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user' => $_SESSION['username'] ?? 'guest',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'context' => $context
        ];

        // 开发模式下同时输出到控制台
        if ($this->devMode) {
            error_log(print_r($logEntry, true));
        }

        $logText = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($this->logFile, $logText, FILE_APPEND | LOCK_EX);
    }

    /**
     * 日志文件轮转
     */
    private function rotateLogFile() {
        if (!file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) < $this->maxFileSize) {
            return;
        }

        $backupFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
        rename($this->logFile, $backupFile);

        // 压缩旧日志文件
        $this->compressLogFile($backupFile);

        // 清理旧的备份文件
        $this->cleanOldBackups();
    }

    /**
     * 压缩日志文件
     * @param string $file 要压缩的文件路径
     */
    private function compressLogFile($file) {
        $gzFile = $file . '.gz';
        $handle = fopen($file, 'rb');
        $gzHandle = gzopen($gzFile, 'wb9');

        while (!feof($handle)) {
            gzwrite($gzHandle, fread($handle, 8192));
        }

        fclose($handle);
        gzclose($gzHandle);
        unlink($file);
    }

    /**
     * 清理旧的备份文件
     */
    private function cleanOldBackups() {
        $backupFiles = glob($this->logFile . '.*');
        if (count($backupFiles) > $this->maxBackupFiles) {
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - $this->maxBackupFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }

    private function __clone() {}
    private function __wakeup() {}
} 