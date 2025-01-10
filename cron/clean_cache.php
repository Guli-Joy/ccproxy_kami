<?php
// 清理过期的缓存文件
$cache_dir = __DIR__ . '/../cache/';
$files = glob($cache_dir . 'notify_*.lock');

foreach ($files as $file) {
    if (time() - filemtime($file) > 86400) { // 24小时后清理
        unlink($file);
    }
}
?> 