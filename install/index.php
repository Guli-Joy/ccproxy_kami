<?php

/**
 * 安装程序
 */

// 设置session安全配置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
session_start();

// 设置安装session有效期（30分钟）
if (isset($_SESSION['install_time']) && (time() - $_SESSION['install_time'] > 1800)) {
    session_destroy();
    header("Location: ./");
    exit();
}
$_SESSION['install_time'] = time();

// 检查安装锁
if (file_exists("./install.lock")) {
    // 如果不是安装成功页面，直接跳转到首页
    if (!isset($_GET['type']) || $_GET['type'] !== 'installok') {
        header("Location: ../");
        exit();
    }
    
    // 如果是安装成功页面，检查session
    if ($_GET['type'] === 'installok') {
        if (!isset($_SESSION['install_success']) || $_SESSION['install_success'] !== true) {
            header("Location: ../");
            exit();
        }
    }
}

// 检测PHP版本和必要扩展
$php_version = PHP_VERSION;
if (version_compare($php_version, '7.0.0', '<')) {
    exit('PHP版本太低，最少需要PHP7.0版本！');
}

// 检查必要的PHP扩展
$required_extensions = [
    'mysqli' => '数据库扩展',
    'curl' => 'CURL扩展',
    'openssl' => 'OpenSSL扩展',
    'mbstring' => 'mbstring扩展',
    'gd' => 'GD扩展'
];

$missing_extensions = [];
foreach ($required_extensions as $ext => $name) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $name;
    }
}

if (!empty($missing_extensions)) {
    exit('缺少必要的PHP扩展：' . implode('、', $missing_extensions));
}

// 检查目录权限
$check_dirs = [
    '../' => '根目录',
    '../config.php' => '配置文件',
    '../cache/' => '缓存目录',
    '../logs/' => '日志目录'
];

$permission_errors = [];
foreach ($check_dirs as $dir => $name) {
    if (file_exists($dir)) {
        if (!is_writable($dir)) {
            $permission_errors[] = $name . '不可写';
        }
    } else {
        $permission_errors[] = $name . '不存在';
    }
}

if (!empty($permission_errors)) {
    exit('目录权限检查失败：' . implode('、', $permission_errors));
}

define('VERSION', '3'); //版本号
@header('Content-Type: text/html; charset=UTF-8');
include("../config.php");
$type = $type = isset($_GET['type']) ? addslashes($_GET['type']) : "";;
$a = 1;
function checkfunc($f, $m = false)
{
    if (function_exists($f)) {
        return '<font color="green">可用</font>';
    } else {
        if ($m == false) {
            return '<font color="black">不支持</font>';
        } else {
            return '<font color="red">不支持</font>';
        }
    }
}

// 创建安装锁定文件
function create_install_lock() {
    $lock_file = "./install.lock";
    $content = "Installation completed on: " . date('Y-m-d H:i:s');
    if (@file_put_contents($lock_file, $content)) {
        @chmod($lock_file, 0444); // Make the lock file read-only
        return true;
    }
    return false;
}

// 清空安装目录
function deldir($path = '../install')
{
    if (!is_dir($path)) {
        return false;
    }
    
    try {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $full_path = $path . '/' . $file;
                if (is_dir($full_path)) {
                    deldir($full_path);
                    @rmdir($full_path);
                } else {
                    @unlink($full_path);
                }
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("删除安装目录失败：" . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>CCPROXY系统安装模块</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="CCPROXY系统安装模块,CCPROXY系统安装模块,CCPROXY系统安装模块,免费,免费引流程序" name="description" />
    <meta content="Coderthemes" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="../favicon.ico">

    <!-- App css -->
    <link href="../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="../assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="../assets/layui/css/layui.css" />

</head>
<style>
    /* 蜡笔小新主题配色 */
    :root {
        --primary-color: #FF6B6B;    /* 小新红色衣服 */
        --secondary-color: #4FC3F7;  /* 小新蓝色短裤 */
        --accent-color: #FFD54F;     /* 小新黄色 */
        --bg-color: #FFF8E1;         /* 温暖的背景色 */
        --text-color: #37474F;       /* 深色文字 */
        --border-color: #FFE0B2;     /* 边框色 */
        --success-color: #81C784;    /* 成功绿色 */
        --warning-color: #FFB74D;    /* 警告橙色 */
        --error-color: #E57373;      /* 错误红色 */
    }

    /* 全局样式 */
    body {
        font-family: 'Comic Sans MS', 'Comic Neue', cursive;
        background: var(--bg-color);
        background-image: 
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="%23FFD54F" opacity="0.3"/></svg>'),
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 100 100"><path d="M20,50 Q50,20 80,50" stroke="%234FC3F7" stroke-width="1" fill="none" opacity="0.2"/></svg>');
        background-size: 30px 30px, 150px 150px;
        animation: bgParallax 30s linear infinite;
    }

    @keyframes bgParallax {
        0% { background-position: 0 0, 0 0; }
        100% { background-position: 30px 30px, 150px 150px; }
    }

    /* 卡片样式 */
    .card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        border: 2px dashed var(--border-color);
        box-shadow: 
            0 10px 20px rgba(0,0,0,0.05),
            0 6px 6px rgba(0,0,0,0.1),
            inset -2px -2px 5px rgba(0,0,0,0.1),
            inset 2px 2px 5px rgba(255,255,255,0.5);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 
            0 15px 30px rgba(0,0,0,0.1),
            0 8px 8px rgba(0,0,0,0.15);
    }

    /* 标题样式 */
    .header-title {
        color: var(--primary-color) !important;
        font-size: 24px !important;
        text-align: center;
        margin-bottom: 20px !important;
        position: relative;
        text-shadow: 2px 2px 0 rgba(255,107,107,0.2);
    }

    /* 进度条样式 */
    .progress {
        background-color: rgba(255,107,107,0.1);
        border-radius: 10px;
        height: 15px !important;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(255,107,107,0.5);
    }

    /* 导航样式 */
    .nav-pills .nav-link {
        border-radius: 15px;
        padding: 15px;
        margin: 0 5px;
        color: var(--text-color);
        background: rgba(255,255,255,0.8);
        border: 2px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link:hover {
        transform: translateY(-2px);
        background: rgba(255,255,255,0.9);
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        box-shadow: 0 5px 15px rgba(255,107,107,0.3);
    }

    /* 表单样式 */
    .form-control {
        border-radius: 10px;
        border: 2px solid var(--border-color);
        padding: 12px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(255,107,107,0.25);
    }

    /* 按钮样式 */
    .btn {
        border-radius: 12px;
        padding: 10px 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .btn::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
        transform: rotate(45deg);
        animation: btnShine 3s infinite;
    }

    @keyframes btnShine {
        0% { transform: translateX(-100%) rotate(45deg); }
        100% { transform: translateX(100%) rotate(45deg); }
    }

    .btn-success {
        background: linear-gradient(45deg, var(--success-color), #4CAF50);
        box-shadow: 0 4px 15px rgba(129,199,132,0.3);
    }

    .btn-primary {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        box-shadow: 0 4px 15px rgba(255,107,107,0.3);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    /* 表格样式 */
    .table {
        background: rgba(255,255,255,0.9);
        border-radius: 15px;
        overflow: hidden;
    }

    .table thead th {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        padding: 15px;
    }

    .table td {
        padding: 12px;
        border-color: var(--border-color);
        transition: all 0.3s ease;
    }

    .table tr:hover td {
        background: rgba(255,107,107,0.05);
    }

    /* 开关样式 */
    .layui-form-switch {
        border-radius: 20px;
        background-color: var(--border-color);
    }

    .layui-form-onswitch {
        background-color: var(--primary-color) !important;
        box-shadow: 0 2px 5px rgba(255,107,107,0.3);
    }

    /* 完成页面样式 */
    .text-success {
        color: var(--success-color) !important;
    }

    .mdi-check-all {
        font-size: 48px;
        animation: checkmark 0.5s ease-in-out;
    }

    @keyframes checkmark {
        0% { transform: scale(0) rotate(-45deg); }
        70% { transform: scale(1.2) rotate(10deg); }
        100% { transform: scale(1) rotate(0); }
    }

    /* 装饰元素 */
    .decoration {
        position: absolute;
        pointer-events: none;
        z-index: 1;
    }

    .crayon {
        width: 30px;
        height: 120px;
        background: linear-gradient(to bottom, #FF9999, #FF6B6B);
        border-radius: 4px;
        transform: rotate(-45deg);
        box-shadow: 
            2px 2px 5px rgba(0,0,0,0.2),
            inset -2px -2px 5px rgba(0,0,0,0.1),
            inset 2px 2px 5px rgba(255,255,255,0.5);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: rotate(-45deg) translateY(0); }
        50% { transform: rotate(-40deg) translateY(-10px); }
    }

    /* 加载动画 */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--bg-color);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid var(--border-color);
        border-top-color: var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    /* 波纹效果 */
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.4);
        transform: scale(0);
        animation: rippleEffect 0.6s linear;
        pointer-events: none;
    }

    @keyframes rippleEffect {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    /* 优化其他动画效果 */
    .btn {
        position: relative;
        overflow: hidden;
    }

    .card {
        animation: cardAppear 0.5s ease-out;
    }

    @keyframes cardAppear {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 确保装饰元素不影响交互 */
    .decoration {
        pointer-events: none;
        z-index: 1;
    }
</style>

<!-- 添加装饰元素 -->
<div class="decoration crayon" style="top: 20px; right: 50px;"></div>
<div class="decoration crayon" style="bottom: 40px; left: 30px;"></div>



<body>

    <!-- Begin page -->
    <div class="wrapper">
        <div class="content">
            <!-- Start Content-->
            <div class="container-fluid">
                <div class="row mt-4 text-center">
                    <div class="col-xl-6" style="margin:auto">
                        <div class="card">
                            <?php if ($type == "installok") {
                                // 生成安装锁
                                if (!file_exists("./install.lock") && !file_exists("./install.lock")) {
                                    create_install_lock();
                                    // 设置安装成功session
                                    $_SESSION['install_success'] = true;
                                }
                            ?>
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="mt-0"><i class="mdi mdi-check-all text-success"></i></h2>
                                        <h3 class="mt-0">安装成功</h3>
                                        <p class="w-75 mb-2 mt-2 mx-auto">恭喜您，系统已经安装完成！</p>
                                        <div class="mb-3">
                                            <div class="alert alert-success" role="alert">
                                                <h4 class="alert-heading">登录信息</h4>
                                                <p>管理员账号：<code>admin</code></p>
                                                <p>管理员密码：<code>123456</code></p>
                                                <hr>
                                                <p class="mb-0">请及时修改默认密码以确保安全！</p>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <a href="../sub_admin/" target="_blank" class="btn btn-primary mr-2">
                                                <i class="mdi mdi-account-key"></i> 打开后台管理
                                            </a>
                                            <a href="../" target="_blank" class="btn btn-info">
                                                <i class="mdi mdi-home"></i> 打开前台首页
                                            </a>
                                        </div>
                                        <p class="text-muted">
                                            <small>为了安全起见，请删除网站根目录下的 <code>install</code> 文件夹。</small>
                                        </p>
                                    </div>
                                    <?php
                                    // 清除安装成功session
                                    unset($_SESSION['install_success']);
                                    ?>
                                </div>
                            <?php } else { ?>
                                <div class="card-body">

                                    <h2 class="header-title mb-3 text-success font-18 font-weight-light">CCPROXY在线安装引导程序</h2>

                                    <div id="progressbarwizard">
                                        <ul class="nav nav-pills nav-justified form-wizard-header mb-3 ">
                                            <li class="nav-item">
                                                <a href="#account-2" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-account-circle mr-1"></i>
                                                    <span class="d-none d-sm-inline font-weight-light">环境检测</span>
                                                </a>
                                            </li>
                                            <?php if (!file_exists("./install.lock")) { ?>
                                                <li class="nav-item">
                                                    <a style="<?php
                                                                if (!(version_compare(PHP_VERSION, '7.3', '>'))) {
                                                                    echo 'pointer-events: none;';
                                                                }
                                                                ?>" href="#profile-tab-2" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                        <i class="mdi mdi-face-profile mr-1"></i>
                                                        <span class="d-none d-sm-inline font-weight-light">数据配置</span>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>

                                        <div class="tab-content b-0 mb-0">

                                            <div id="bar" class="progress mb-3" style="height: 10px;">
                                                <div class="bar progress-bar progress-bar-striped progress-bar-animated bg-danger"></div>
                                            </div>

                                            <div class="tab-pane" id="account-2">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="card">
                                                            <div class="card-body">

                                                                <h4 class="header-title font-weight-light">环境检测</h4>
                                                                <p class="w-75 mb-2 mt-2 mx-auto">官方QQ群(o´ω`o)ﾉ：
                                                                    <font color="red" class="font-18"><a href="https://qm.qq.com/q/YpoK9Aifei">点击加群</a></font>，关注可了解更多资讯！
                                                                </p>
                                                                <p class="text-muted font-14 mb-3">
                                                                    <code>为了更好的使用程序,下列环境须支持才可正常运行</code>.
                                                                </p>

                                                                <div class="table-responsive-sm">
                                                                    <table class="table table-striped mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>检测项目</th>
                                                                                <th>需求</th>
                                                                                <th>当前</th>
                                                                                <th>用途</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>PHP 7.0+</td>
                                                                                <td>必须</td>
                                                                                <td>
                                                                                    <?php
                                                                                    if (!(version_compare(PHP_VERSION, '7.0', '>=')) || !(version_compare(PHP_VERSION, '8.0', '<'))) {
                                                                                        $a = 2;
                                                                                    }
                                                                                    echo (version_compare(PHP_VERSION, '7.0', '>=')) && (version_compare(PHP_VERSION, '8.0', '<')) ? '<font color="green">' . PHP_VERSION . '</font>' : '<font color="red">' . PHP_VERSION . '</font>'; ?>
                                                                                </td>
                                                                                <td>PHP版本支持</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>PDO扩展</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo extension_loaded('pdo') ? '<font color="green">可用</font>' : '<font color="red">不支持</font>'; ?></td>
                                                                                <td>数据库连接</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>PDO_MySQL扩展</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo extension_loaded('pdo_mysql') ? '<font color="green">可用</font>' : '<font color="red">不支持</font>'; ?></td>
                                                                                <td>MySQL数据库支持</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>curl_exec()</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo checkfunc('curl_exec', true); ?></td>
                                                                                <td>网络请求</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>file_get_contents()</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo checkfunc('file_get_contents', true); ?></td>
                                                                                <td>文件读取</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>file_put_contents()</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo checkfunc('file_put_contents', true); ?></td>
                                                                                <td>文件写入</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>session支持</td>
                                                                                <td>必须</td>
                                                                                <td><?php $_SESSION['checksession'] = 1;
                                                                                    echo $_SESSION['checksession'] == 1 ? '<font color="green">可用</font>' : '<font color="red">不支持</font>'; ?></td>
                                                                                <td>会话支持</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>openssl扩展</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo extension_loaded('openssl') ? '<font color="green">可用</font>' : '<font color="red">不支持</font>'; ?></td>
                                                                                <td>加密功能</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>mbstring扩展</td>
                                                                                <td>必须</td>
                                                                                <td><?php echo extension_loaded('mbstring') ? '<font color="green">可用</font>' : '<font color="red">不支持</font>'; ?></td>
                                                                                <td>多字节字符支持</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>目录写入权限</td>
                                                                                <td>必须</td>
                                                                                <td><?php 
                                                                                    $write_dirs = ['../cache/', '../logs/', '../config.php'];
                                                                                    $write_check = true;
                                                                                    foreach($write_dirs as $dir) {
                                                                                        if(!is_writable($dir)) {
                                                                                            $write_check = false;
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                    echo $write_check ? '<font color="green">可用</font>' : '<font color="red">不支持</font>';
                                                                                ?></td>
                                                                                <td>文件读写权限</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <?php if (file_exists("./install.lock")) { ?>
                                                                    <ul class="list-inline mb-0 wizard">
                                                                        <li class="list-inline-item float-right">
                                                                            <a href="#">
                                                                                <button type="button" class="btn btn-danger" onclick="layer.alert('检测到您已经安装过程序<br>请先删除install目录下的<font color=red>./install.lock</font>文件再来安装!',{icon:2,title:'警告'})">
                                                                                    进行下一步
                                                                                </button>
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                <?php } else { ?>
                                                                    <ul class="list-inline mb-0 wizard">
                                                                        <li class="next list-inline-item float-right">
                                                                            <a href="#">
                                                                                <button type="button" class="btn btn-success" <?= $a == 1 ? '' : 'disabled=""'; ?>>
                                                                                    进入下一步
                                                                                </button>
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                            </div>
                                            <?php if (!file_exists("./install.lock")) { ?>
                                                <div class="tab-pane" id="profile-tab-2">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <span class="text-center mb-2 d-block">可手动前往文件：<code>config.php</code> 配置数据!</span>
                                                            <form class="layui-form form-horizontal">
                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="host">数据库地址</label>
                                                                    <div class="col-md-9">
                                                                        <input type="text" id="host" name="host" class="form-control" lay-verify="required" value="<?= $dbconfig["host"] ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="port">数据库端口</label>
                                                                    <div class="col-md-9">
                                                                        <input type="number" id="port" name="port" class="form-control" lay-verify="required" value="<?= $dbconfig["port"] ?>">
                                                                    </div>
                                                                </div>

                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="user">数据库用户名</label>
                                                                    <div class="col-md-9">
                                                                        <input type="text" id="user" name="user" class="form-control" lay-verify="required" value="<?= $dbconfig["user"] ?>">
                                                                    </div>
                                                                </div>

                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="pwd">数据库密码</label>
                                                                    <div class="col-md-9">
                                                                        <input type="text" id="pwd" name="pwd" class="form-control" lay-verify="required" value="<?= $dbconfig["pwd"] ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="dbname">数据库名</label>
                                                                    <div class="col-md-9">
                                                                        <input type="text" id="dbname" name="dbname" class="form-control" lay-verify="required" value="<?= $dbconfig["dbname"] ?>">
                                                                    </div>
                                                                </div>

                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light" for="url">当前程序版本</label>
                                                                    <div class="col-md-9">
                                                                        <input type="text" id="versions" name="versions" class="form-control" lay-verify="required" value="V <?= VERSION ?>" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group row mb-3">
                                                                    <label class="col-md-3 col-form-label font-weight-light">安装协议</label>
                                                                    <div class="col-md-9">
                                                                        <input type="checkbox" name="agreement" lay-skin="primary" lay-filter="agreement" title="我已阅读并同意安装协议">
                                                                        <a href="javascript:;" class="text-primary ml-2" onclick="showAgreement()">查看协议</a>
                                                                    </div>
                                                                </div>
                                                                <ul class="list-inline mb-0 wizard">
                                                                    <li class="list-inline-item float-right" id="install">
                                                                        <a href="#">
                                                                            <button type="submit" lay-submit lay-filter="install" class="btn btn-success">开始安装程序
                                                                            </button>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </form>
                                                        </div> <!-- end col -->
                                                    </div> <!-- end row -->
                                                </div>
                                            <?php } ?>
                                        </div> <!-- tab-content -->
                                    </div> <!-- end #progressbarwizard-->
                                </div> <!-- end card-body -->
                            <?php } ?>
                        </div> <!-- end card-->
                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div> <!-- container -->
        </div> <!-- content -->
    </div>
    <!-- END wrapper -->
    <div class="rightbar-overlay"></div>
    <!-- /Right-bar -->
    <!-- App js -->
    <script src="../assets/js/app.min.js"></script>
    <script src="../assets/layui/layui.js"></script>
    <!-- end demo js-->
    <?php if (!file_exists("./install.lock") && empty($type)) { ?>
        <script>
            $(document).ready(function() {
                "use strict";
                $("#basicwizard").bootstrapWizard(), $("#progressbarwizard").bootstrapWizard({
                    onTabShow: function(t, r, a) {
                        var o = (a + 1) / r.find("li").length * 100;
                        $("#progressbarwizard").find(".bar").css({
                            width: o + "%"
                        })
                    }
                }), $("#btnwizard").bootstrapWizard({
                    nextSelector: ".button-next",
                    previousSelector: ".button-previous",
                    firstSelector: ".button-first",
                    lastSelector: ".button-last"
                }), $("#rootwizard").bootstrapWizard({
                    onNext: function(t, r, a) {
                        var o = $($(t).data("targetForm"));
                        if (o && (o.addClass("was-validated"), !1 === o[0].checkValidity())) return event.preventDefault(), event.stopPropagation(), !1
                    }
                });
            });
            layui.use('form', function() {
                var form = layui.form;

                // 自定义验证规则
                form.verify({
                    agreement: function(value, item) {
                        var checked = item.checked;
                        if (!checked) {
                            return '请先阅读并同意安装协议';
                        }
                    }
                });

                // 监听协议复选框变化
                form.on('checkbox(agreement)', function(data){
                    if (data.elem.checked) {
                        // 自动打开协议
                        showAgreement();
                    }
                });

                // 显示协议内容
                window.showAgreement = function() {
                    layer.open({
                        type: 2,
                        title: "CCPROXY系统使用协议",
                        area: ["500px", "500px"],
                        maxmin: false,
                        content: "disclaimer.html?v=20201111001",
                        success: function(layero, index) {
                            // 如果是通过勾选框触发的，设置遮罩层点击不关闭
                            if (document.querySelector('input[name="agreement"]').checked) {
                                layer.setTop(layero);
                                $('.layui-layer-shade').off('click');
                            }
                        }
                    });
                };

                form.on('submit(install)', function(data) {
                    // 验证是否同意协议
                    if (!data.field.agreement) {
                        layer.msg('请先阅读并同意安装协议', {icon: 2});
                        return false;
                    }

                    var index = layer.msg('正在安装中,请稍后...', {
                        icon: 16,
                        time: 999999
                    });
                    $.ajax({
                        url: 'ajax.php?act=1',
                        type: 'POST',
                        data: data.field,
                        dataType: 'json',
                        success: function(res) {
                            layer.close(index);
                            if (res.code == -2) {
                                layer.confirm('数据库已存在相关表,是否清空重新安装?', {
                                    btn: ['确定', '跳过', '取消'],
                                    icon: 3,
                                    title: "提示"
                                }, function() {
                                    // 确认清空数据库
                                    var clearIndex = layer.msg('正在清空数据库...', {
                                        icon: 16,
                                        time: 999999
                                    });
                                    $.ajax({
                                        url: 'ajax.php?act=clear_db',
                                        type: 'POST',
                                        data: data.field,
                                        dataType: 'json',
                                        success: function(res) {
                                            layer.close(clearIndex);
                                            if (res.code == 1) {
                                                // 数据库清空成功，继续安装
                                                layer.msg(res.msg, {icon: 1});
                                                setTimeout(function() {
                                                    // 重新提交安装
                                                    data.field.state = 1; // 标记为全新安装
                                                    var installIndex = layer.msg('正在安装中,请稍后...', {
                                                        icon: 16,
                                                        time: 999999
                                                    });
                                                    $.ajax({
                                                        url: 'ajax.php?act=1',
                                                        type: 'POST',
                                                        data: data.field,
                                                        dataType: 'json',
                                                        success: function(res) {
                                                            layer.close(installIndex);
                                                            if (res.code == 1) {
                                                                layer.msg('安装成功！<br>成功执行 ' + res.sql_count + ' 条SQL语句<br>3秒后自动跳转...', {
                                                                    icon: 1,
                                                                    time: 3000,
                                                                    shade: 0.3,
                                                                    shadeClose: false,
                                                                    end: function() {
                                                                        window.location.href = 'index.php?type=installok';
                                                                    }
                                                                });
                                                            } else {
                                                                layer.alert(res.msg, {
                                                                    icon: 2,
                                                                    title: '安装失败'
                                                                });
                                                            }
                                                        },
                                                        error: function(xhr) {
                                                            layer.close(installIndex);
                                                            layer.alert('安装请求失败！', {
                                                                icon: 2,
                                                                title: '错误'
                                                            });
                                                        }
                                                    });
                                                }, 1000);
                                            } else {
                                                layer.alert(res.msg, {
                                                    icon: 2,
                                                    title: '清空失败'
                                                });
                                            }
                                        },
                                        error: function(xhr) {
                                            layer.close(clearIndex);
                                            layer.alert('清空数据库请求失败！', {
                                                icon: 2,
                                                title: '错误'
                                            });
                                        }
                                    });
                                }, function() {
                                    // 跳过，直接进入安装成功页面
                                    window.location.href = 'index.php?type=installok';
                                }, function() {
                                    // 取消操作，什么都不做
                                });
                            } else if (res.code == 1) {
                                layer.msg('安装成功！<br>成功执行 ' + res.sql_count + ' 条SQL语句<br>3秒后自动跳转...', {
                                    icon: 1,
                                    time: 3000,
                                    shade: 0.3,
                                    shadeClose: false,
                                    end: function() {
                                        window.location.href = 'index.php?type=installok';
                                    }
                                });
                            } else {
                                layer.alert(res.msg, {
                                    icon: 2,
                                    title: '安装失败'
                                });
                            }
                        },
                        error: function(xhr) {
                            layer.close(index);
                            layer.alert('安装请求失败！', {
                                icon: 2,
                                title: '错误'
                            });
                        }
                    });
                    return false;
                });
            });

            // 修复加载动画
            $(document).ready(function() {
                // 页面加载完成后移除加载动画
                $('.loading-overlay').fadeOut(500, function() {
                    $(this).remove();
                });

                // 如果3秒后还没消失，强制移除
                setTimeout(function() {
                    $('.loading-overlay').fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 3000);

                // 添加按钮点击波纹效果
                $('.btn').on('click', function(e) {
                    let ripple = $('<span>');
                    ripple.addClass('ripple');
                    ripple.css({
                        left: e.offsetX,
                        top: e.offsetY
                    });
                    $(this).append(ripple);
                    setTimeout(() => ripple.remove(), 600);
                });
            });

            // 确保在window加载完成后也执行一次
            $(window).on('load', function() {
                $('.loading-overlay').fadeOut(500, function() {
                    $(this).remove();
                });
            });

            // 修复进度条相关代码
            $(document).ready(function() {
                "use strict";
                $("#progressbarwizard").bootstrapWizard({
                    onTabShow: function(tab, navigation, index) {
                        var $total = navigation.find('li').length;
                        var $current = index + 1;
                        var $percent = ($current / $total) * 100;
                        
                        // 更新进度条
                        var $progressbar = $("#progressbarwizard").find('.progress-bar');
                        $progressbar.css({width: $percent + '%'});
                        $progressbar.attr('aria-valuenow', $percent);
                        
                        // 根据进度更新颜色
                        if ($percent <= 50) {
                            $progressbar.removeClass('bg-success bg-info').addClass('bg-warning');
                        } else if ($percent <= 99) {
                            $progressbar.removeClass('bg-warning bg-success').addClass('bg-info');
                        } else {
                            $progressbar.removeClass('bg-warning bg-info').addClass('bg-success');
                        }
                        
                        // 添加动画效果
                        $progressbar.addClass('progress-bar-animated');
                    },
                    onTabChange: function(tab, navigation, index) {
                        // 验证所有必需的扩展和功能是否可用
                        var allRequired = true;
                        $('.table-responsive-sm tbody tr').each(function() {
                            var requirement = $(this).find('td:eq(1)').text();
                            var status = $(this).find('td:eq(2)').text();
                            if (requirement === '必须' && status === '不支持') {
                                allRequired = false;
                                return false;
                            }
                        });
                        
                        if (!allRequired) {
                            layer.alert('请确保所有必需的扩展和功能都可用后再继续！', {icon: 2});
                            return false;
                        }
                        return true;
                    }
                });
            });
        </script>
    <?php }else{?>

<?php }?>
</body>

</html>