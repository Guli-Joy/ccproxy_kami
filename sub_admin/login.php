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
		if(strlen($row['password']) < 60) { // 未经过hash的密码
			// 如果密码匹配,则更新为hash密码
			if($pass == $row['password']) {
				try {
					$hashed_password = SecurityFilter::hashPassword($pass);
					$DB->exe("UPDATE sub_admin SET password='" . $DB->escape($hashed_password) . "' WHERE username='" . $DB->escape($user) . "'");
					$row['password'] = $hashed_password; // 更新当前会话的密码
				} catch (Exception $e) {
					error_log("Password update error: " . $e->getMessage());
					$json = ["code" => "-1", "msg" => "系统错误，请稍后再试"];
					exit(json_encode($json,JSON_UNESCAPED_UNICODE));
				}
			}
		}
		
		// 验证密码
		if(SecurityFilter::verifyPassword($pass, $row['password'])) {
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
	setcookie("sub_admin_token", "", time() - 604800);
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
					anim: 5, // 使用缩放动画
					time: 2000, // 默认2秒后自动关闭
					offset: '30px' // 距离顶部位置
				});

				$(function() {
					// 输入框焦点动画
					$('.layui-input').focus(function() {
						$(this).parent().find('i').css('transform', 'scale(1.2)');
					}).blur(function() {
						$(this).parent().find('i').css('transform', 'scale(1)');
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

					form.on("submit(submit)", function(data) {
						$.ajax({
							url: "login.php",
							type: "POST",
							dataType: "json",
							data: data.field,
							beforeSend: function() {
								// 自定义加载提示
								layer.msg('<div class="custom-loading"></div>正在登录...', {
									icon: 16,
									shade: 0.05,
									time: false,
									skin: 'layui-layer-loading'
								});
							},
							success: function(data) {
								if (data.code == "1") {
									// 登录成功动画
									$('.layout-main').addClass('animate-bounceOutUp');
									layer.msg(data.msg, {
										icon: 1,
										skin: 'layui-layer-success',
										anim: 2
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
										anim: 6
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
								layer.msg("登录失败: " + data.code, {
									icon: 5,
									skin: 'layui-layer-error',
									anim: 6
								});
								$(".codeimg").prop("src", './code.php?r=' + Math.random());
							}
						});
						return false; // 阻止表单默认提交
					});

					// 回车提交
					document.onkeydown = function(e) {
						var keyCode = e.keyCode || e.which || e.charCode;
						if (keyCode == 13) {
							$(".layui-btn.layui-btn-fluid").trigger("click");
						}
					};

					// 验证码点击动画
					$(".codeimg").click(function() {
						$(this).addClass('animate-flipInY');
						setTimeout(() => {
							$(this).removeClass('animate-flipInY');
						}, 1000);
						$(this).prop("src", './code.php?r=' + Math.random());
					});

					// 装饰元素动画
					setInterval(() => {
						$('.decoration').each(function() {
							$(this).css({
								'transform': `translate(${Math.random() * 20 - 10}px, ${Math.random() * 20 - 10}px) rotate(${Math.random() * 360}deg)`
							});
						});
					}, 3000);
				});
			});
		</script>
	</body>
</html>