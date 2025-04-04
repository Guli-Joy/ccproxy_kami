<?php
/**
 * 登录
**/
require_once "../includes/common.php";
if(isset($_POST['username']) && isset($_POST['password'])){
	$user = SecurityFilter::filterInput($_POST['username']);
	$pass = $_POST['password']; // 不过滤原始密码,后续会进行安全验证
	$code = SecurityFilter::filterInput($_POST['code']);
	
	// 验证码检查
	if (!$code || strtolower($code) != $_SESSION['xx_session_code']) {
		unset($_SESSION['xx_session_code']);
		@header('Content-Type: text/html; charset=UTF-8');
		$json = ["code" => "-1", "msg" => "验证码错误！"];
		exit(json_encode($json,JSON_UNESCAPED_UNICODE));
	}
	
	// 获取用户信息
	$row = $DB->selectRow("SELECT * FROM sub_admin WHERE username='" . $DB->escape($user) . "' LIMIT 1");
	
	// 验证密码
	if($row) {
		// 检查是否是明文密码
		if(strlen($row['password']) < 32) { // 未经过md5加密的密码
			// 如果密码匹配,则更新为md5密码
			if($pass == $row['password']) {
				try {
					$md5_password = md5($pass);
					$DB->exe("UPDATE sub_admin SET password='" . $DB->escape($md5_password) . "' WHERE username='" . $DB->escape($user) . "'");
					$row['password'] = $md5_password; // 更新当前会话的密码
				} catch (Exception $e) {
					error_log("Password update error: " . $e->getMessage());
					$json = ["code" => "-1", "msg" => "系统错误，请稍后再试"];
					exit(json_encode($json,JSON_UNESCAPED_UNICODE));
				}
			}
		}
		
		// 验证密码 - 支持明文和MD5密码
		if($row['password'] === $pass || $row['password'] === md5($pass)) {
			try {
				unset($_SESSION['xx_session_code']);
				$session = md5($user.$pass.$password_hash);
				$cookies = authcode("{$user}\t{$session}", 'ENCODE', SYS_KEY);
				setcookie("sub_admin_token", $cookies, time() + 604800, '/'); // 添加路径参数
				setCookie("tab", "primary.php", time() + 604800, '/'); // 添加路径参数
				
				// 更新cookies
				$DB->exe("UPDATE sub_admin SET cookies='" . $DB->escape($cookies) . "' WHERE username='" . $DB->escape($user) . "'");
				
				// 设置登录session
				$_SESSION['is_login'] = true;
				$_SESSION['admin_user'] = $user;
				
				@header('Content-Type: text/html; charset=UTF-8');
				$json = ["code" => "1", "msg" => "登陆成功,欢迎您使用本系统！"];
				WriteLog("登录日志", "登陆成功", $user, $DB);
				exit(json_encode($json,JSON_UNESCAPED_UNICODE));
			} catch (Exception $e) {
				error_log("Login process error: " . $e->getMessage());
				$json = ["code" => "-1", "msg" => "系统错误，请稍后再试"];
				exit(json_encode($json,JSON_UNESCAPED_UNICODE));
			}
		}
	}
	
	// 登录失败
	unset($_SESSION['xx_session_code']);
	@header('Content-Type: text/html; charset=UTF-8');
	$json = ["code" => "2", "msg" => "用户名或密码不正确！"];
	WriteLog("登录日志","可能暴力破解",null,$DB);
	exit(json_encode($json,JSON_UNESCAPED_UNICODE));
}elseif(isset($_GET['logout'])){
	// 清除所有session
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
	$_SESSION = array();
	session_destroy();
	
	// 清除cookies
	setcookie("sub_admin_token", "", time() - 604800, '/');
	setcookie("tab", "", time() - 604800, '/');
	
	// 更新数据库中的cookies
	$DB->exe("UPDATE sub_admin SET cookies='' WHERE username='" . $DB->escape($subconf['username']) . "'");
	
	@header('Content-Type: text/html; charset=UTF-8');
    $json = ["code" => "0", "msg" => "您已成功注销本次登陆！"];
    exit(json_encode($json,JSON_UNESCAPED_UNICODE));
}elseif($islogin==1){
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('您已登陆了哦,不能重复登陆！');window.location.href='./index.php';</script>");
}
// include("foot.php");
// $arr = range(1, 15);
// shuffle($arr);
// foreach ($arr as $values) {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<title><?php echo $subconf['hostname']?>后台登录</title>
		<?php include("foot.php"); ?>
		<link rel="stylesheet" href="../assets/layui/css/layui.css">
		<link rel="stylesheet" href="../assets/layui/css/logon.css">
		<style>
			/* 添加动画关键帧 */
			@keyframes float {
				0% { transform: translateY(0px); }
				50% { transform: translateY(-10px); }
				100% { transform: translateY(0px); }
			}
			@keyframes rotate {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
			@keyframes loading-dot {
				0%, 100% { opacity: 0.2; }
				50% { opacity: 1; }
			}
			.decoration {
				transition: all 0.5s ease;
			}
			/* 优化加载动画 */
			.loading-container {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 10px;
			}
			.loading-spinner {
				width: 24px;
				height: 24px;
				border: 3px solid #fff;
				border-top-color: transparent;
				border-radius: 50%;
				display: inline-block;
				animation: rotate 0.8s linear infinite;
				vertical-align: middle;
			}
			.loading-text {
				color: #fff;
				font-size: 15px;
				display: inline-flex;
				align-items: center;
			}
			.loading-dots {
				display: inline-flex;
				margin-left: 4px;
			}
			.loading-dots span {
				width: 4px;
				height: 4px;
				background: #fff;
				border-radius: 50%;
				margin: 0 2px;
				display: inline-block;
				animation: loading-dot 1.4s infinite;
			}
			.loading-dots span:nth-child(2) {
				animation-delay: 0.2s;
			}
			.loading-dots span:nth-child(3) {
				animation-delay: 0.4s;
			}
			.custom-loading-layer {
				background: rgba(0, 0, 0, 0.6) !important;
				backdrop-filter: blur(4px);
				border-radius: 12px !important;
			}
			.custom-loading {
				width: 20px;
				height: 20px;
				border: 2px solid #fff;
				border-top-color: transparent;
				border-radius: 50%;
				display: inline-block;
				animation: rotate 1s linear infinite;
				margin-right: 10px;
			}
			.layout-main {
				animation: float 6s ease-in-out infinite;
			}
			.animate-bounceOutUp {
				animation: bounceOutUp 1s forwards;
			}
			.animate-shakeX {
				animation: shakeX 0.8s;
			}
			.animate-flipInY {
				animation: flipInY 0.8s;
			}
			.animate-pulse {
				animation: pulse 1s infinite;
			}
			/* 优化装饰元素样式 */
			.decoration {
				position: fixed;
				pointer-events: none;
				z-index: -1;
			}
			.deco-1 {
				top: 20%;
				left: 15%;
				width: 100px;
				height: 100px;
				background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
				border-radius: 50%;
				opacity: 0.6;
			}
			.deco-2 {
				top: 60%;
				right: 15%;
				width: 150px;
				height: 150px;
				background: linear-gradient(45deg, #4facfe, #00f2fe);
				border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
				opacity: 0.5;
			}
			.deco-3 {
				bottom: 10%;
				left: 30%;
				width: 80px;
				height: 80px;
				background: linear-gradient(45deg, #7367f0, #ce9ffc);
				border-radius: 63% 37% 30% 70% / 50% 45% 55% 50%;
				opacity: 0.4;
			}
		</style>
	</head>
	<body>
		<div class="bg-animation"></div>
		<div class="decoration deco-1"></div>
		<div class="decoration deco-2"></div>
		<div class="decoration deco-3"></div>

		<div class="layout-main">
			<div class="layout-title">欢迎回来！</div>
			<div class="layout-explain">让我们开始今天的冒险吧 (｡◕‿◕｡)</div>
			<div class="layout-content layui-form layui-form-pane">
				<div class="layui-form-item">
					<label class="layui-form-label"><i class="layui-icon layui-icon-username"></i></label>
					<div class="layui-input-block">
						<input type="text" name="username" lay-verify="required" lay-reqtext="请输入用户名" class="layui-input" placeholder="你的名字">
					</div>
				</div>
				<div class="layui-form-item">
					<label class="layui-form-label"><i class="layui-icon layui-icon-password"></i></label>
					<div class="layui-input-block">
						<input type="password" name="password" lay-verify="required" lay-reqtext="请输入密码" class="layui-input" placeholder="悄悄话">
					</div>
				</div>
				<div class="layui-form-item">
					<label class="layui-form-label"><i class="layui-icon layui-icon-vercode"></i></label>
					<div class="layui-input-block">
						<input style="width:50%;display:inline" type="text" name="code" lay-verify="required" lay-reqtext="请输入验证码" class="layui-input" placeholder="验证码">
						<img class="codeimg" style="float: right;" src="./code.php?r=<?php echo time();?>" width="45%" height="45" title="点击更换验证码">
					</div>
				</div>
				<div class="layui-form-item nob">
					<button class="layui-btn layui-btn-fluid" lay-submit lay-filter="submit">开始冒险！</button>
				</div>
			</div>
		</div>
		<div class="layout-copyright">
			© <?php echo date('Y'); ?> <?php echo $subconf['hostname']?> All Rights Reserved
		</div>

		<script>
			layui.use(["jquery", "form"], function() {
				var $ = layui.$,
					form = layui.form;
				
				// 自定义弹窗配置
				layer.config({
					skin: 'layui-layer-custom',
					anim: 5,
					time: 2000,
					offset: '30px'
				});

				$(function() {
					// 输入框焦点动画
					$('.layui-input').focus(function() {
						$(this).parent().find('i').css({
							'transform': 'scale(1.2)',
							'transition': 'transform 0.3s ease'
						});
					}).blur(function() {
						$(this).parent().find('i').css({
							'transform': 'scale(1)',
							'transition': 'transform 0.3s ease'
						});
					});

					// 按钮悬浮效果
					$('.layui-btn').hover(
						function() {
							$(this).addClass('animate-pulse');
						},
						function() {
							$(this).removeClass('animate-pulse');
						}
					);

					// 装饰元素动画
					function animateDecorations() {
						$('.decoration').each(function(index) {
							const randomX = Math.random() * 20 - 10;
							const randomY = Math.random() * 20 - 10;
							const randomRotate = Math.random() * 360;
							const randomScale = 0.8 + Math.random() * 0.4;
							
							$(this).css({
								'transform': `translate(${randomX}px, ${randomY}px) rotate(${randomRotate}deg) scale(${randomScale})`,
								'transition': 'all 3s ease-in-out'
							});
						});
					}

					// 初始化装饰动画
					animateDecorations();
					setInterval(animateDecorations, 3000);

					form.on("submit(submit)", function(data) {
						$.ajax({
							url: "login.php",
							type: "POST",
							dataType: "json",
							data: data.field,
							beforeSend: function() {
								layer.msg(
									'<div class="loading-container">' +
									'<div class="loading-spinner"></div>' +
									'<div class="loading-text">' +
									'登录中' +
									'<div class="loading-dots">' +
									'<span></span><span></span><span></span>' +
									'</div>' +
									'</div>' +
									'</div>', 
									{
										time: 0,
										shade: [0.3, '#000'],
										skin: 'custom-loading-layer',
										area: ['160px', 'auto']
									}
								);
							},
							success: function(data) {
								layer.closeAll('loading');
								if (data.code == "1") {
									// 登录成功动画
									$('.layout-main').addClass('animate-bounceOutUp');
									layer.msg(data.msg, {
										icon: 1,
										skin: 'layui-layer-success',
										anim: 2,
										time: 1000
									});
									setTimeout('window.location.href ="./index.php"', 800);
								} else {
									// 登录失败动画
									$('.layout-main').addClass('animate-shakeX');
									setTimeout(function() {
										$('.layout-main').removeClass('animate-shakeX');
									}, 1000);
									layer.msg(data.msg, {
										icon: 5,
										skin: 'layui-layer-error',
										anim: 6,
										time: 2000
									});
									// 验证码刷新动画
									$('.codeimg').addClass('animate-flipInY');
									setTimeout(function() {
										$('.codeimg').removeClass('animate-flipInY');
									}, 1000);
									$(".codeimg").prop("src", './code.php?r=' + Math.random());
								}
							},
							error: function(data) {
								layer.closeAll('loading');
								layer.msg("登录失败: " + data.code, {
									icon: 5,
									skin: 'layui-layer-error',
									anim: 6,
									time: 2000
								});
								$(".codeimg").prop("src", './code.php?r=' + Math.random());
							}
						});
						return false;
					});

					// 回车提交
					document.onkeydown = function(e) {
						var keyCode = e.keyCode || e.which || e.charCode;
						if (keyCode == 13) {
							$(".layui-btn.layui-btn-fluid").trigger("click");
						}
					};

					// 验证码点击刷新
					$(".codeimg").click(function() {
						$(this).addClass('animate-flipInY');
						setTimeout(() => {
							$(this).removeClass('animate-flipInY');
						}, 1000);
						$(this).prop("src", './code.php?r=' + Math.random());
					});
				});
			});
		</script>
	</body>
</html>