<?php
require_once '../includes/common.php';

try {
    // 使用新的登录检查方式
    if($islogin != 1) {
        exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
    }

    require_once './head.php';
    $title = '后台管理首页';
} catch (Exception $e) {
    error_log("Index page error: " . $e->getMessage());
    exit('<script language=\'javascript\'>alert("系统错误，请稍后再试");window.location.href=\'login.php\';</script>');
}
?>
