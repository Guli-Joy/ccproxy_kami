<?php 
include '../includes/common.php';
if (!($islogin == 1)) {
    exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<link rel="stylesheet" href="../assets/layui/css/layui.css?v=20201111001" />
	<link rel="stylesheet" type="text/css" href="css/admin.css" />
	<link rel="stylesheet" type="text/css" href="css/theme.css" />
	<link rel="stylesheet" type="text/css" href="css/head.css" />
	<link rel="stylesheet" type="text/css" href="css/mobile.css" />
	<link rel="stylesheet" type="text/css" href="css/popup.css" />
	<link rel="stylesheet" type="text/css" href="css/animation.css" />
	<title><?php echo $subconf['hostname'] . '后台管理'; ?></title>
</head>

<body class="layui-layout-body">
	<!-- 修改 loading 结构 -->
	<div class="page-loader">
		<div class="loader-content">
			<div class="loader-icon"></div>
			<div class="loader-text">Loading...</div>
		</div>
	</div>

	<!-- 装饰元素容器 -->
	<div class="decoration-container">
		<div class="deco-crayon"></div>
		<div class="deco-star"></div>
		<div class="deco-circle"></div>
		<div class="deco-star"></div>
		<div class="deco-circle"></div>
		<div class="deco-star"></div>
	</div>

	<!-- 动态背景 -->
	<div class="dynamic-bg"></div>

	<div class="layui-layout layui-layout-admin side-shrink">
		<!-- 头部 -->
		<div class="layui-header custom-header">
			<ul class="layui-nav layui-layout-left">
				<li class="layui-nav-item slide-sidebar" lay-unselect>
					<a href="javascript:;" class="icon-font"><i class="ai ai-menufold"></i></a>
				</li>
			</ul>
			<ul class="layui-nav layui-layout-right">
				<li class="layui-nav-item">
					<a href="javascript:;">
						<i class="layui-icon layui-icon-notice"></i>
						<span>消息</span><span class="layui-badge">0</span>
					</a>
				</li>
				<li class="layui-nav-item">
					<a href="javascript:;">
						<i class="layui-icon layui-icon-username"></i>
						<span id="username"><?php echo $subconf['username']; ?></span>
					</a>
					<dl class="layui-nav-child">
						<dd>
							<a href="javascript:;" id="update_password">
								<i class="layui-icon layui-icon-password"></i>
								<span>修改密码</span>
							</a>
						</dd>
						<dd>
							<a href="javascript:;" id="quit">
								<i class="layui-icon layui-icon-logout"></i>
								<span>退出登录</span>
							</a>
						</dd>
					</dl>
				</li>
			</ul>
		</div>
		<!-- 左侧 -->
		<div class="layui-side custom-admin">
			<div class="layui-side-scroll">
				<div class="custom-logo">
					<h1 id="logos">故离</h1>
					<span id="logowz">管理系统</span>
				</div>
				<ul id="Nav" class="layui-nav layui-nav-tree" lay-filter="tabnav">
					<li class="layui-nav-item">
						<a href="javascript:;">
							<i class="layui-icon layui-icon-console"></i>
							<em>控制台</em>
						</a>
						<dl class="layui-nav-child">
							<dd><a href="primary.php"><span>主页</span></a></dd>
							<dd><a href="log.php"><span>网站日志</span></a></dd>
							<dd><a href="hostset.php"><span>网站管理</span></a></dd>
							<dd><a href="usermanger.php"><span>用户管理</span></a></dd>
						</dl>
					</li>
					<li class="layui-nav-item">
						<a href="javascript:;">
							<i class="layui-icon layui-icon-app"></i>
							<em>应用</em>
						</a>
						<dl class="layui-nav-child">
							<dd><a href="app.php"><span>应用管理</span></a></dd>
							<dd><a href="server_list.php"><span>服务器列表</span></a></dd>
							<dd><a href="kami.php"><span>卡密生成</span></a></dd>
						</dl>
					</li>
					<li class="layui-nav-item">
						<a href="javascript:;">
							<i class="layui-icon">&#xe65e;</i>
							<em>支付管理</em>
						</a>
						<dl class="layui-nav-child">
							<dd><a href="pay_config.php"><span>码支付配置</span></a></dd>
						</dl>
					</li>
					<li class="layui-nav-item">
						<a href="javascript:;">
							<i class="layui-icon">&#xe6b2;</i>
							<em>套餐管理</em>
						</a>
						<dl class="layui-nav-child">
							<dd><a href="packages.php"><span>套餐配置</span></a></dd>
						</dl>
					</li>
					<li class="layui-nav-item">
						<a href="javascript:;">
							<i class="layui-icon layui-icon-order">&#xe60a;</i>
							<em>订单管理</em>
						</a>
						<dl class="layui-nav-child">
							<dd><a href="order.php"><span>订单列表</span></a></dd>
						</dl>
					</li>
				</ul>
			</div>
		</div>
		<!-- 主体 -->
		<div class="layui-body">
			<div class="layui-tab app-container" lay-allowClose="true" lay-filter="tabs">
				<ul id="appTabs" class="layui-tab-title custom-tab"></ul>
				<div id="appTabPage" class="layui-tab-content"></div>
			</div>
		</div>
		<div class="mobile-mask"></div>
	</div>
	<script src="../assets/layui/layui.js"></script>
	<script src="../assets/js/index.js"></script>
	<script>
		layui.use(['jquery', 'element', 'form', 'layer'], function() {
			var $ = layui.$,
				element = layui.element,
				form = layui.form,
				layer = layui.layer;
			
			// 设置默认收起状态
			if(localStorage.getItem('menuShrink') === null) {
				localStorage.setItem('menuShrink', 'true');
			}
			
			// 从localStorage获取菜单状态
			var isShrink = localStorage.getItem('menuShrink') === 'true';
			if(isShrink) {
				$('.layui-layout-admin').addClass('side-shrink');
			} else {
				$('.layui-layout-admin').removeClass('side-shrink');
			}

			// 记住子菜单展开状态
			var expandedMenus = localStorage.getItem('expandedMenus');
			if(expandedMenus) {
				expandedMenus = JSON.parse(expandedMenus);
				expandedMenus.forEach(function(index) {
					$('.layui-nav-item').eq(index).addClass('layui-nav-itemed');
				});
			}

			// 监听子菜单展开/收起
			$('.layui-nav-item').on('click', function() {
				var $this = $(this);
				var index = $('.layui-nav-item').index($this);
				var expandedMenus = [];
				
				// 收集所有展开的菜单索引
				$('.layui-nav-itemed').each(function() {
					var idx = $('.layui-nav-item').index($(this));
					expandedMenus.push(idx);
				});
				
				// 保存到localStorage
				localStorage.setItem('expandedMenus', JSON.stringify(expandedMenus));
			});

			// 添加菜单提示
			$('.layui-nav-tree .layui-nav-item > a').each(function() {
				var title = $(this).find('em').text();
				$(this).attr('data-title', title);
			});

			// 菜单收缩点击事件
			$('.slide-sidebar').on('click', function() {
				var admin = $('.layui-layout-admin');
				admin.toggleClass('side-shrink');
				localStorage.setItem('menuShrink', admin.hasClass('side-shrink'));
			});

			// 移动端遮罩层点击关闭
			$('.mobile-mask').on('click', function() {
				$('.layui-layout-admin').addClass('side-shrink');
				localStorage.setItem('menuShrink', 'true');
			});

			// 窗口大小改变时的处理
			$(window).resize(function() {
				if($(window).width() <= 768) {
					$('.layui-layout-admin').addClass('side-shrink');
					localStorage.setItem('menuShrink', 'true');
				}
			});

			// 初始化时处理移动端
			if($(window).width() <= 768) {
				$('.layui-layout-admin').addClass('side-shrink');
				localStorage.setItem('menuShrink', 'true');
			}

			// 子菜单动画
			$('.layui-nav-item').hover(
				function() {
					if($('.layui-layout-admin').hasClass('side-shrink')) {
						$(this).find('.layui-nav-child').stop().slideDown(200);
					}
				},
				function() {
					if($('.layui-layout-admin').hasClass('side-shrink')) {
						$(this).find('.layui-nav-child').stop().slideUp(200);
					}
				}
			);

			// 优化子菜单位置
			$('.layui-nav-tree .layui-nav-item').hover(function() {
				if($('.layui-layout-admin').hasClass('side-shrink')) {
					var childMenu = $(this).find('.layui-nav-child');
					var offset = childMenu.offset();
					var windowHeight = $(window).height();
					var menuHeight = childMenu.height();
					
					if(offset.top + menuHeight > windowHeight) {
						childMenu.css({
							top: 'auto',
							bottom: '0'
						});
					}
				}
			});

			// 动态创建装饰元素
			function createRandomDeco() {
				var decoTypes = ['star', 'circle'];
				var colors = [
					'var(--primary-color)',
					'var(--secondary-color)',
					'var(--accent-color)'
				];

				setInterval(function() {
					// 检查当前装饰元素数量
					var currentDecoCount = $('.decoration-container .deco-star, .decoration-container .deco-circle').length;
					if (currentDecoCount >= 10) return; // 限制最大数量为10个

					var type = decoTypes[Math.floor(Math.random() * decoTypes.length)];
					var color = colors[Math.floor(Math.random() * colors.length)];
					var size = Math.random() * 10 + 5;

					var deco = $('<div>')
						.addClass('deco-' + type)
						.css({
							position: 'absolute',
							left: Math.random() * 100 + '%',
							top: Math.random() * 100 + '%',
							width: size + 'px',
							height: size + 'px',
							background: color,
							opacity: 0,
							transform: 'scale(0) rotate(0deg)',
							transition: 'all 1s ease-out'
						})
						.appendTo('.decoration-container');

					setTimeout(function() {
						deco.css({
							opacity: 0.3,
							transform: 'scale(1) rotate(360deg)'
						});
					}, 100);

					setTimeout(function() {
						deco.css({
							opacity: 0,
							transform: 'scale(1.5) rotate(720deg)'
						}).on('transitionend', function() {
							$(this).remove();
						});
					}, 3000);
				}, 4000);
			}

			// 初始化动画
			createRandomDeco();

			// 菜单项悬浮效果
			$('.layui-nav-item').hover(
				function() {
					$(this).find('.layui-icon').css('transform', 'scale(1.2) rotate(15deg)');
				},
				function() {
					$(this).find('.layui-icon').css('transform', 'scale(1) rotate(0)');
				}
			);

			// 页面加载完成后移除 loading
			$(window).on('load', function() {
				$('.page-loader').addClass('fade-out');
				setTimeout(function() {
					$('.page-loader').remove();
				}, 500);
			});

			// 如果加载时间过长，也移除 loading（3秒后）
			setTimeout(function() {
				if($('.page-loader').length > 0) {
					$('.page-loader').addClass('fade-out');
					setTimeout(function() {
						$('.page-loader').remove();
					}, 500);
				}
			}, 3000);

			// 创建云朵
			function createCloud() {
				// 检查当前云朵数量
				var currentCloudCount = $('.decoration-container .cloud').length;
				if (currentCloudCount >= 3) return; // 限制最大数量为3个

				const cloud = $('<div>')
					.addClass('cloud')
					.css({
						width: Math.random() * 100 + 50 + 'px',
						height: Math.random() * 30 + 20 + 'px',
						top: Math.random() * 30 + '%',
						opacity: Math.random() * 0.3 + 0.4,
						animationDuration: Math.random() * 20 + 30 + 's'
					})
					.appendTo('.decoration-container');

				cloud.on('animationend', () => cloud.remove());
			}

			// 创建气泡
			function createBubble() {
				// 检查当前气泡数量
				var currentBubbleCount = $('.decoration-container .bubble').length;
				if (currentBubbleCount >= 5) return; // 限制最大数量为5个

				const bubble = $('<div>')
					.addClass('bubble')
					.css({
						width: Math.random() * 20 + 10 + 'px',
						height: Math.random() * 20 + 10 + 'px',
						left: Math.random() * 100 + '%',
						'--tx': Math.random() * 200 - 100 + 'px',
						animationDuration: Math.random() * 3 + 2 + 's'
					})
					.appendTo('.decoration-container');

				bubble.on('animationend', () => bubble.remove());
			}

			// 创建闪烁星星
			function createTwinkleStar() {
				// 检查当前星星数量
				var currentStarCount = $('.decoration-container .twinkle-star').length;
				if (currentStarCount >= 8) return; // 限制最大数量为8个

				const star = $('<div>')
					.addClass('twinkle-star')
					.css({
						left: Math.random() * 100 + '%',
						top: Math.random() * 100 + '%',
						animationDelay: Math.random() * 2 + 's'
					})
					.appendTo('.decoration-container');

				setTimeout(() => star.remove(), 3000);
			}

			// 添加彩虹条
			$('<div>').addClass('rainbow').appendTo('body');

			// 定期创建装饰元素
			setInterval(createCloud, 15000);
			setInterval(createBubble, 2000);
			setInterval(createTwinkleStar, 2000);

			// 按钮点击波纹效果
			$(document).on('click', '.layui-nav-item a, .layui-btn', function(e) {
				const ripple = $('<span>')
					.addClass('ripple')
					.css({
						left: e.pageX - $(this).offset().left + 'px',
						top: e.pageY - $(this).offset().top + 'px'
					})
					.appendTo(this);

				setTimeout(() => ripple.remove(), 600);
			});

			// 优化页面切换动画
			$('.layui-nav-item a').on('click', function() {
				$('.layui-body').css('opacity', '0')
					.delay(200)
					.queue(function() {
						$(this).css('opacity', '1').dequeue();
					});
			});

			// 退出登录处理
			$('#quit').on('click', function() {
				layer.confirm('<div class="logout-confirm">' +
					'<div class="logout-icon"><i class="layui-icon layui-icon-logout"></i></div>' +
					'<div class="logout-title">确定要退出登录吗？</div>' +
					'<div class="logout-subtitle">期待您的下次冒险 (｡◕‿◕｡)</div>' +
					'</div>', {
					title: false,
					closeBtn: 0,
					btn: ['确定退出', '再想想'],
					skin: 'logout-dialog',
					anim: 2,
					btnAlign: 'c',
					area: ['340px', 'auto']
				}, function() {
					$.ajax({
						url: 'login.php?logout=1',
						type: 'GET',
						dataType: 'json',
						success: function(res) {
							if(res.code == '0') {
								// 清除所有cookie
								document.cookie.split(";").forEach(function(c) { 
									document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
								});
								// 清除localStorage
								localStorage.clear();
								// 清除sessionStorage
								sessionStorage.clear();
								
								// 显示退出成功动画
								layer.msg('<div style="text-align:center;">' +
									'<i class="layui-icon layui-icon-ok" style="font-size:30px;color:#52c41a;display:block;margin-bottom:10px;"></i>' +
									'<div style="color:#333;font-size:16px;">退出成功</div>' +
									'<div style="color:#666;font-size:12px;margin-top:5px;">下次再见 (●\'◡\'●)</div>' +
									'</div>', {
									time: 1500,
									anim: 2,
									shade: 0.2,
									shadeClose: true
								}, function() {
									window.location.href = 'login.php';
								});
							} else {
								layer.msg('退出失败，请重试', {
									icon: 2,
									anim: 6,
									time: 2000
								});
							}
						},
						error: function() {
							layer.msg('网络错误，请重试', {
									icon: 2,
									anim: 6,
									time: 2000
							});
						}
					});
				});
			});

			// 修改密码点击事件
			$('#update_password').off('click').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				// 根据屏幕宽度设置弹窗大小
				var area = $(window).width() <= 768 ? ['90%', 'auto'] : ['420px', 'auto'];
				var offset = $(window).width() <= 768 ? '10%' : '20%';
				
				layer.open({
					type: 1,
					title: false,
					closeBtn: 2,
					shadeClose: true,
					skin: 'password-popup',
					area: area,
					offset: offset,
					anim: 2,
					isOutAnim: true,
					content: `
						<div class="password-container layui-form" lay-filter="password-form">
							<!-- 装饰元素 -->
							<div class="deco-1"></div>
							<div class="deco-2"></div>
							<div class="deco-3"></div>

							<!-- 表单头部 -->
							<div class="form-header">
								<div class="form-title">修改密码</div>
								<div class="form-subtitle">为了账号安全，请谨慎操作 (｡◕‿◕｡)</div>
							</div>

							<!-- 表单内容 -->
							<div class="layui-form-item">
								<label class="layui-form-label">旧密码<span class="layui-must">*</span></label>
								<div class="layui-input-block">
									<input type="password" name="out_password" class="layui-input" lay-verify="required" placeholder="请输入当前密码">
									<i class="layui-icon layui-icon-password input-icon"></i>
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">新密码<span class="layui-must">*</span></label>
								<div class="layui-input-block">
									<input type="password" name="password" class="layui-input" lay-verify="required" placeholder="请输入新密码">
									<i class="layui-icon layui-icon-password input-icon"></i>
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">确认新密码<span class="layui-must">*</span></label>
								<div class="layui-input-block">
									<input type="password" name="confirm_password" class="layui-input" lay-verify="required" placeholder="请再次输入新密码">
									<i class="layui-icon layui-icon-password input-icon"></i>
								</div>
							</div>

							<!-- 提交按钮 -->
							<div class="btn-container">
								<button class="layui-btn" lay-submit lay-filter="submit-password">
									<i class="layui-icon layui-icon-ok"></i> 确认修改
								</button>
							</div>
						</div>
					`,
					success: function(layero, index) {
						// 重新渲染表单
						form.render(null, 'password-form');
						
						// 监听表单提交
						form.on('submit(submit-password)', function(data) {
							$.ajax({
								url: "ajax.php?act=updatepwd",
								type: "POST",
								dataType: "json",
								data: data.field,
								beforeSend: function() {
									layer.msg("正在提交", {
										icon: 16,
										shade: 0.05,
										time: false
									});
								},
								success: function(data) {
									if (data.code == "1") {
										layer.closeAll();
										layer.msg(data.msg, {
											icon: 1,
											time: 1500
										}, function() {
											// 清除所有cookie
											document.cookie.split(";").forEach(function(c) { 
												document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
											});
											// 清除localStorage
											localStorage.clear();
											// 清除sessionStorage
											sessionStorage.clear();
											// 跳转到登录页
											window.location.href = 'login.php';
										});
									} else if(data.code == "-1") {
										layer.msg(data.msg, {
											icon: 5
										});
									} else if(data.code == "-2") {
										layer.msg(data.msg, {
											icon: 5
										});
									} else if(data.code == "-3") {
										layer.msg(data.msg, {
											icon: 5
										});
									} else {
										layer.msg("未知错误", {
											icon: 5
										});
									}
								},
								error: function(data) {
									console.log(data);
									layer.msg(data.responseText, {
										icon: 5
									});
								}
							});
							return false;
						});
					},
					end: function() {
						layer.closeAll('loading');
					}
				});
				
				return false;
			});
		});
	</script>
</body>

</html>