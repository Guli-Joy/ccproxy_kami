<?php 
include '../includes/common.php';
if (!($islogin == 1)) {
    exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>
		<?php echo $subconf['hostname']; ?>-网站设置
		</title>
		<?php include("foot.php"); ?>
		<link rel="stylesheet" href="css/hostset.css">
		<!-- Markdown相关依赖 -->
		<link rel="stylesheet" href="../../assets/css/main/github.min.css">
		<script src="../../assets/js/marked.umd.js"></script>
		<script src="../../assets/js/highlight.min.js"></script>
		<script>
		// 确保marked和highlight.js正确加载
		window.addEventListener('load', function() {
			if (typeof marked === 'undefined') {
				layer.msg('Markdown解析器未能加载，部分功能可能无法使用', {icon: 2});
			}
			if (typeof hljs === 'undefined') {
				layer.msg('代码高亮插件未能加载，部分功能可能无法使用', {icon: 2});
			}
		});
		</script>
		<style>
		/* 卡片样式优化 */
		.setting-card {
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 6px rgba(0,0,0,0.05);
			margin-bottom: 20px;
			overflow: hidden;
		}

		.card-title {
			padding: 15px 20px;
			font-size: 16px;
			font-weight: 600;
			border-bottom: 1px solid #f0f0f0;
			background: #fafafa;
			color: #333;
		}

		.card-content {
			padding: 20px;
		}

		/* Markdown编辑器样式 */
		.md-editor-container {
			border: 1px solid #e6e6e6;
			border-radius: 4px;
			background: #fff;
		}

		.md-toolbar {
			padding: 10px;
			background: #f8f8f8;
			border-bottom: 1px solid #e6e6e6;
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			position: sticky;
			top: 0;
			z-index: 100;
		}

		.toolbar-group {
			display: flex;
			gap: 4px;
			padding-right: 8px;
			border-right: 1px solid #e6e6e6;
		}

		.toolbar-group:last-child {
			border-right: none;
		}

		.layui-btn-sm {
			height: 28px;
			line-height: 28px;
			padding: 0 10px;
			font-size: 12px;
		}

		.layui-btn-sm i {
			font-size: 14px;
		}

		/* 编辑器主体 */
		.editor-main {
			display: flex;
			min-height: 400px;
		}

		#wzggs {
			flex: 1;
			min-height: 400px;
			padding: 15px;
			font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
			line-height: 1.6;
			font-size: 14px;
			border: none;
			resize: vertical;
			background: #fff;
		}

		#wzggs:focus {
			border: none;
			outline: none;
		}

		/* 预览区域样式优化 */
		.markdown-preview {
			flex: 1;
			padding: 20px 30px;
			border-left: 1px solid #e6e6e6;
			background: #fff;
			overflow-y: auto;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
			font-size: 15px;
			line-height: 1.7;
			word-wrap: break-word;
			transition: all 0.3s ease;
		}

		/* Markdown内容样式 */
		.markdown-preview h1,
		.markdown-preview h2,
		.markdown-preview h3,
		.markdown-preview h4,
		.markdown-preview h5,
		.markdown-preview h6 {
			margin-top: 24px;
			margin-bottom: 16px;
			font-weight: 600;
			line-height: 1.25;
		}

		.markdown-preview h1 { font-size: 2em; padding-bottom: .3em; border-bottom: 1px solid #eaecef; }
		.markdown-preview h2 { font-size: 1.5em; padding-bottom: .3em; border-bottom: 1px solid #eaecef; }
		.markdown-preview h3 { font-size: 1.25em; }
		.markdown-preview h4 { font-size: 1em; }
		.markdown-preview h5 { font-size: 0.875em; }
		.markdown-preview h6 { font-size: 0.85em; color: #6a737d; }

		.markdown-preview p {
			margin-bottom: 16px;
		}

		.markdown-preview blockquote {
			padding: 0 1em;
			color: #6a737d;
			border-left: 0.25em solid #dfe2e5;
			margin: 0 0 16px 0;
		}

		.markdown-preview code {
			padding: 0.2em 0.4em;
			margin: 0;
			font-size: 85%;
			background-color: rgba(27,31,35,0.05);
			border-radius: 3px;
			font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
		}

		.markdown-preview pre code {
			padding: 16px;
			overflow: auto;
			font-size: 85%;
			line-height: 1.45;
			background-color: #f6f8fa;
			border-radius: 3px;
			display: block;
		}

		.markdown-preview table {
			display: block;
			width: 100%;
			overflow: auto;
			margin: 16px 0;
			border-spacing: 0;
			border-collapse: collapse;
		}

		.markdown-preview table th,
		.markdown-preview table td {
			padding: 6px 13px;
			border: 1px solid #dfe2e5;
		}

		.markdown-preview table tr:nth-child(2n) {
			background-color: #f6f8fa;
		}

		.markdown-preview hr {
			height: 0.25em;
			padding: 0;
			margin: 24px 0;
			background-color: #e1e4e8;
			border: 0;
		}

		.markdown-preview ul,
		.markdown-preview ol {
			padding-left: 2em;
			margin-bottom: 16px;
		}

		.markdown-preview img {
			max-width: 100%;
			box-sizing: content-box;
			background-color: #fff;
			border-radius: 3px;
		}

		.markdown-preview a {
			color: #0366d6;
			text-decoration: none;
		}

		.markdown-preview a:hover {
			text-decoration: underline;
		}

		/* 动画效果 */
		.markdown-preview.fade-enter {
			opacity: 0;
			transform: translateX(10px);
		}

		.markdown-preview.fade-enter-active {
			opacity: 1;
			transform: translateX(0);
			transition: opacity 300ms, transform 300ms;
		}

		/* 响应式布局 */
		@media screen and (max-width: 768px) {
			.card-content {
				padding: 15px;
			}

			.md-toolbar {
				padding: 8px;
				gap: 4px;
			}

			.toolbar-group {
				padding-right: 4px;
			}

			.layui-btn-sm {
				padding: 0 8px;
			}
		}

		/* 美化滚动条 */
		#wzggs::-webkit-scrollbar,
		.markdown-preview::-webkit-scrollbar {
			width: 6px;
			height: 6px;
		}

		#wzggs::-webkit-scrollbar-thumb,
		.markdown-preview::-webkit-scrollbar-thumb {
			background: rgba(0,0,0,0.1);
			border-radius: 3px;
		}

		#wzggs::-webkit-scrollbar-track,
		.markdown-preview::-webkit-scrollbar-track {
			background: transparent;
		}
		</style>
	</head>
	<body>
		<div class="layui-card layui-form">
			<div class="layui-card-body">
				<div class="layui-tab">
					
					<div class="layui-tab-content">
						<div class="layui-tab-item layui-show layui-line form">
							<!-- 基础设置 -->
							<div class="setting-card">
								<div class="card-title">基础设置</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">网站标题<span class="layui-badge-dot"></span></label>
										<div class="layui-input-block">
											<input type="text" name="user_key" class="layui-input" value="<?php echo $subconf['hostname']; ?>" placeholder="请输入网站标题">
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">首页LOGO</label>
										<div class="layui-input-block">
											<input type="text" name="logo" class="layui-input" value="<?php echo $subconf['img']; ?>" placeholder="LOGO直链接">
										</div>
									</div>
								</div>
							</div>

							<!-- 功能开关 -->
							<div class="setting-card">
								<div class="card-title">功能开关</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">在线续费/注册</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_online_pay" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_online_pay"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制在线续费/注册功能的显示</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">卡密充值</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_kami_pay" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_kami_pay"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制卡密充值功能的显示</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">卡密注册</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_kami_reg" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_kami_reg"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制卡密注册功能的显示</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">用户查询</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_user_search" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_user_search"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制用户查询功能的显示</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">卡密查询</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_kami_query" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_kami_query"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制卡密查询功能的显示</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">修改密码</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_change_pwd" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_change_pwd"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">控制用户修改密码功能的显示</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 继承应用设置 -->
							<div class="setting-card">
								<div class="card-title">继承应用设置</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">启用继承</label>
										<div class="layui-input-block">
											<input type="checkbox" name="inherit_enabled" lay-skin="switch" lay-text="开启|关闭" lay-filter="inherit_enabled" <?php echo($subconf["inherit_enabled"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">开启后,主应用的注册和续费操作将同步到继承应用</div>
										</div>
									</div>
									
									<div class="layui-form-item">
										<label class="layui-form-label">显示继承应用</label>
										<div class="layui-input-block">
											<input type="checkbox" name="show_inherit_apps" lay-skin="switch" lay-text="开启|关闭" <?php echo($subconf["show_inherit_apps"]==1 ? 'checked':'');?> />
											<div class="layui-form-mid layui-word-aux">开启后,前端将显示继承应用,关闭则只显示主应用</div>
										</div>
									</div>
									
									<!-- 继承组列表 -->
									<div id="inherit_groups">
										<?php 
										$inherit_config = '';
										if(isset($subconf["inherit_groups"]) && !empty($subconf["inherit_groups"])) {
											if(is_string($subconf["inherit_groups"])) {
												// 递归解码HTML实体
												$decoded_str = $subconf["inherit_groups"];
												$prev_str = '';
												while($decoded_str !== $prev_str) {
													$prev_str = $decoded_str;
													$decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
												}
												$inherit_config = $decoded_str;
											} else if(is_array($subconf["inherit_groups"])) {
												$inherit_config = json_encode($subconf["inherit_groups"]);
											}
										}
										if(empty($inherit_config)) {
											$inherit_config = '{"groups":[]}';
										}
										?>
										<input type="hidden" name="inherit_config" value='<?php echo htmlspecialchars($inherit_config, ENT_QUOTES, 'UTF-8'); ?>'>
									</div>
									
									<!-- 添加继承组按钮 -->
									<div class="layui-form-item">
										<div class="layui-input-block" style="margin-left: 0;">
											<button type="button" class="layui-btn layui-btn-normal" id="add_inherit_group">
												<i class="layui-icon layui-icon-add-1"></i> 添加继承组
											</button>
										</div>
									</div>
								</div>
							</div>

							<!-- 客服与网盘 -->
							<div class="setting-card">
								<div class="card-title">客服与网盘</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">网站客服</label>
										<div class="layui-input-block">
											<input type="checkbox" name="kfswitch" lay-skin="switch" lay-text="开启|关闭" lay-filter="kfswitch" <?php echo($subconf["kfswitch"]==1 ? 'checked':'');?> />
											<div class="input-group">
												<input type="text" name="kf" class="layui-input" value="<?php echo $subconf['kf'];?>" placeholder="请输入客服QQ的链接">
												<span class="input-group-addon">QQ链接</span>
											</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">网盘</label>
										<div class="layui-input-block">
											<input type="checkbox" name="panswitch" lay-skin="switch" lay-text="开启|关闭" lay-filter="panswitch" <?php echo($subconf["panswitch"]==1 ? 'checked':'');?> />
											<div class="input-group">
												<input type="text" name="pan" class="layui-input" value="<?php echo $subconf['pan']; ?>" placeholder="请输入网盘链接">
												<span class="input-group-addon">网盘链接</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 公告设置 -->
							<div class="setting-card">
								<div class="card-title">公告设置</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">网站公告</label>
										<div class="layui-input-block">
											<input type="checkbox" name="ggswitch" lay-skin="switch" lay-text="开启|关闭" lay-filter="ggswitch" <?php echo($subconf["ggswitch"]==1 ? 'checked':'');?> />
											<div class="wzggs" style="margin-top: 15px;">
											<?php
											if($subconf['ggswitch']==1){
												echo '<div class="layui-form-item">
													<div class="gg">
														<div class="md-editor-container">
															<!-- Markdown工具栏 -->
															<div class="md-toolbar">
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="h1">H1</button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="h2">H2</button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="h3">H3</button>
																</div>
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="bold"><i class="layui-icon">&#xe756;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="italic"><i class="layui-icon">&#xe754;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="strike"><i class="layui-icon">&#xe755;</i></button>
																</div>
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="link"><i class="layui-icon">&#xe64c;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="image"><i class="layui-icon">&#xe64a;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="code"><i class="layui-icon">&#xe64e;</i></button>
																</div>
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="ul"><i class="layui-icon">&#xe63b;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="ol"><i class="layui-icon">&#xe63c;</i></button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="quote"><i class="layui-icon">&#xe63a;</i></button>
																</div>
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="table">表格</button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="hr">分割线</button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" data-md="emoji">表情</button>
																</div>
																<div class="toolbar-group">
																	<button type="button" class="layui-btn layui-btn-normal layui-btn-sm" id="previewMd">预览</button>
																	<button type="button" class="layui-btn layui-btn-primary layui-btn-sm" id="insertTemplate">插入模板</button>
																</div>
															</div>
															<div class="editor-main">
																<textarea name="wzgg" id="wzggs" class="layui-textarea" placeholder="支持Markdown格式">'. $subconf['wzgg'].'</textarea>
																<!-- 预览区域 -->
																<div id="mdPreview" class="markdown-preview" style="display:none;"></div>
															</div>
														</div>
													</div>
												</div>';
											}
											?>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 背景设置 -->
							<div class="setting-card">
								<div class="card-title">背景设置</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">背景切换</label>
										<div class="layui-input-block">
											<input type="checkbox" name="bgswitch" lay-skin="switch" lay-text="开启|关闭" lay-filter="bgswitch" <?php echo($subconf["bgswitch"]==1 ? 'checked':'');?> />
											<div class="input-group">
												<input type="text" name="dayimg" class="layui-input" value="<?php echo $subconf['dayimg']; ?>" placeholder="请输入日间背景图片链接">
												<span class="input-group-addon">日间背景</span>
											</div>
											<div class="input-group">
												<input type="text" name="nightimg" class="layui-input" value="<?php echo $subconf['nightimg']; ?>" placeholder="请输入夜间背景图片链接">
												<span class="input-group-addon">夜间背景</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 域名设置 -->
							<div class="setting-card">
								<div class="card-title">域名设置</div>
								<div class="card-content">
									<div class="layui-form-item">
										<label class="layui-form-label">主域名</label>
										<div class="layui-input-block">
											<input type="text" name="siteurl" class="layui-input" value="<?php echo $subconf['siteurl']; ?>" placeholder="请输入主域名">
											<div class="layui-form-mid layui-word-aux">主要访问域名,如: example.com</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">多域名</label>
										<div class="layui-input-block">
											<input type="checkbox" name="multi_domain" lay-skin="switch" lay-text="开启|关闭" lay-filter="multi_domain" <?php echo($subconf["multi_domain"]==1 ? 'checked':'');?> />
											<div class="domain-list" style="margin-top: 10px;<?php echo($subconf["multi_domain"]==0 ? 'display:none':'');?>">
												<textarea name="domain_list" class="layui-textarea" placeholder="请输入其他域名,每行一个"><?php echo $subconf['domain_list']; ?></textarea>
												<div class="layui-form-mid layui-word-aux">每行输入一个域名,如: domain1.com</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 底部按钮 -->
							<div class="setting-footer">
								<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit">
									<i class="layui-icon layui-icon-ok"></i> 保存设置
								</button>
								<button class="layui-btn layui-btn-primary" lay-submit lay-filter="reset">
									<i class="layui-icon layui-icon-refresh"></i> 重置
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
	<script>
		// 初始化marked
		var initMarked = function() {
			if (typeof marked === 'undefined') {
				return;
			}

			// 配置marked
			marked.setOptions({
				renderer: new marked.Renderer(),
				highlight: function(code, language) {
					if (language && hljs.getLanguage(language)) {
						try {
							return hljs.highlight(code, {
								language: language,
								ignoreIllegals: true
							}).value;
						} catch (err) {}
					}
					return code;
				},
				langPrefix: 'hljs language-',
				pedantic: false,
				gfm: true,
				breaks: true,
				sanitize: false,
				smartypants: false,
				xhtml: false
			});
		};

		layui.use(["jquery", "form", "element", "util", "transfer"], function() {
			var $ = layui.$,
				form = layui.form,
				element = layui.element,
				transfer = layui.transfer,
				util = layui.util;

			// 初始化marked
			initMarked();
			
			// 继承组模板
			function getInheritGroupTemplate(groupId) {
				return '<div class="inherit-group" data-group-id="' + groupId + '">' +
					'<div class="layui-form-item">' +
						'<label class="layui-form-label">继承组' + groupId + '</label>' +
						'<div class="layui-input-block">' +
							'<div class="layui-btn-group" style="margin-bottom: 10px;">' +
								'<button type="button" class="layui-btn layui-btn-danger layui-btn-sm delete-group">' +
									'<i class="layui-icon layui-icon-delete"></i> 删除本组' +
								'</button>' +
							'</div>' +
						'</div>' +
					'</div>' +
					'<div class="layui-form-item">' +
						'<label class="layui-form-label">主应用</label>' +
						'<div class="layui-input-block">' +
							'<select class="main-app-select" lay-filter="main_app_' + groupId + '" multiple>' +
								'<option value="">请选择主应用</option>' +
							'</select>' +
							'<div class="layui-form-mid layui-word-aux">可以选择多个主应用</div>' +
						'</div>' +
					'</div>' +
					'<div class="layui-form-item">' +
						'<label class="layui-form-label">继承应用</label>' +
						'<div class="layui-input-block">' +
							'<div id="inherit_apps_transfer_' + groupId + '"></div>' +
							'<div class="layui-form-mid layui-word-aux">从左侧选择应用添加到右侧作为继承应用</div>' +
						'</div>' +
					'</div>' +
					'<hr class="layui-border-green">' +
				'</div>';
			}
			
			// 修改保存继承配置函数
			function saveInheritConfig() {
				var groups = [];
				$('.inherit-group').each(function() {
					var groupId = $(this).data('group-id');
					var mainApps = $(this).find('.main-app-select').val() || [];
					
					var transferId = 'inheritAppsTransfer_' + groupId;
					var transferData = layui.transfer.getData(transferId);
					var inheritApps = [];
					
					if(transferData && Array.isArray(transferData)) {
						inheritApps = transferData.map(function(item) {
							return item.value;
						});
					}
					
					if(mainApps.length > 0 || inheritApps.length > 0) {
						groups.push({
							id: parseInt(groupId),
							main_apps: mainApps,
							inherit_apps: inheritApps
						});
					}
				});
				
				var config = JSON.stringify({groups: groups});
				$('input[name="inherit_config"]').val(config);
			}
			
			// 修改initInheritGroup函数
			function initInheritGroup(groupId, data, mainApps, inheritApps) {
				var group = $(getInheritGroupTemplate(groupId));
				$('#inherit_groups').append(group);
				
				var mainAppSelect = group.find('.main-app-select');
				var transferData = [];
				
				mainApps = Array.isArray(mainApps) ? mainApps : [];
				inheritApps = Array.isArray(inheritApps) ? inheritApps : [];
				
				if(Array.isArray(data)) {
					data.forEach(function(app) {
						var mainSelected = mainApps.includes(app.appcode) ? 'selected' : '';
						mainAppSelect.append('<option value="' + app.appcode + '" ' + mainSelected + '>' + app.appname + ' [' + app.appcode + ']</option>');
						
						if(!mainApps.includes(app.appcode)) {
							transferData.push({
								value: app.appcode,
								title: app.appname + ' [' + app.appcode + ']',
								disabled: false
							});
						}
					});
				}
				
				transfer.render({
					elem: '#inherit_apps_transfer_' + groupId,
					title: ['可选应用', '已选应用'],
					id: 'inheritAppsTransfer_' + groupId,
					data: transferData,
					value: inheritApps,
					text: {
						none: '无数据',
						searchNone: '无匹配数据'
					},
					onchange: function(data, index) {
						saveInheritConfig();
					},
					parseData: function(item) {
						return {
							value: item.value,
							title: item.title,
							disabled: item.disabled,
							checked: inheritApps.includes(item.value)
						};
					}
				});
				
				form.on('select(main_app_' + groupId + ')', function(data) {
					var selectedValues = data.value || [];
					
					$.ajax({
						url: "ajax.php?act=getapps",
						type: "POST",
						dataType: "json",
						success: function(response) {
							if(response.code == 1) {
								var allApps = response.data;
								
								var currentInheritApps = transfer.getData('inheritAppsTransfer_' + groupId).map(function(item) {
									return item.value;
								});
								
								var newTransferData = [];
								
								allApps.forEach(function(app) {
									if(!selectedValues.includes(app.appcode)) {
										newTransferData.push({
											value: app.appcode,
											title: app.appname + ' [' + app.appcode + ']',
											disabled: false
										});
									}
								});
								
								transfer.render({
									elem: '#inherit_apps_transfer_' + groupId,
									title: ['可选应用', '已选应用'],
									id: 'inheritAppsTransfer_' + groupId,
									data: newTransferData,
									value: currentInheritApps.filter(function(appcode) {
										return !selectedValues.includes(appcode);
									}),
									text: {
										none: '无数据',
										searchNone: '无匹配数据'
									},
									onchange: function(data, index) {
										saveInheritConfig();
									}
								});
								
								saveInheritConfig();
							} else {
								layer.msg(response.msg || "获取应用列表失败", {icon: 5});
							}
						},
						error: function(xhr, status, error) {
							layer.msg("获取应用列表失败: " + error, {icon: 5});
						}
					});
				});
				
				form.render('select');
			}
			
			// 修改loadApps函数
			function loadApps() {
				$.ajax({
					url: "ajax.php?act=getapps",
					type: "POST",
					dataType: "json",
					success: function(data) {
						if(data.code == 1) {
							var $configInput = $('input[name="inherit_config"]');
							var configValue = $configInput.val();
							
							$('#inherit_groups').empty();
							
							$('#inherit_groups').append($configInput);
							
							var config = {groups: []};
							
							try {
								if(configValue) {
									var decodedStr = configValue;
									var prevStr = '';
									while(decodedStr !== prevStr) {
										prevStr = decodedStr;
										decoded_str = $('<div/>').html(decodedStr).text();
									}
									config = JSON.parse(decodedStr);
								}
							} catch(e) {
								layer.msg('解析继承配置失败，将重置配置', {icon: 0});
							}
							
							if(!Array.isArray(config.groups)) {
								config.groups = [];
							}
							
							if(config.groups.length > 0) {
								config.groups.forEach(function(group) {
									if(group && group.id) {
										initInheritGroup(
											parseInt(group.id), 
											data.data, 
											Array.isArray(group.main_apps) ? group.main_apps : [], 
											Array.isArray(group.inherit_apps) ? group.inherit_apps : []
										);
									}
								});
							} else {
								initInheritGroup(1, data.data, [], []);
							}
							
							saveInheritConfig();
						} else {
							layer.msg(data.msg || "获取应用列表失败", {icon: 5});
						}
					},
					error: function(xhr, status, error) {
						layer.msg("获取应用列表失败: " + error, {icon: 5});
					}
				});
			}
			
			$('#add_inherit_group').click(function() {
				var newGroupId = $('.inherit-group').length + 1;
				$.ajax({
					url: "ajax.php?act=getapps",
					type: "POST",
					dataType: "json",
					success: function(data) {
						if(data.code == 1) {
							initInheritGroup(newGroupId, data.data);
						} else {
							layer.msg(data.msg || "获取应用列表失败", {icon: 5});
						}
					},
					error: function(xhr, status, error) {
						layer.msg("获取应用列表失败: " + error, {icon: 5});
					}
				});
			});
			
			$(document).on('click', '.delete-group', function() {
				var $group = $(this).closest('.inherit-group');
				$group.remove();
				saveInheritConfig();
			});
			
			if($('input[name="inherit_enabled"]').prop('checked')) {
				loadApps();
			} else {
				$('#inherit_groups').children().not('input[name="inherit_config"]').hide();
			}
			
			form.on("switch(inherit_enabled)", function(obj) {
				if(obj.elem.checked) {
					loadApps();
					$('#inherit_groups').children().show();
				} else {
					$('#inherit_groups').children().not('input[name="inherit_config"]').hide();
				}
			});

			form.on("submit(submit)", function(data) {
				if (data.field.wzgg) {
					data.field['wzgg'] = data.field.wzgg
						.replace(/< >/g, " ")
						.replace(/<\/ >/g, " ")
						.replace(/document/g, " ")
						.replace(/'/g, '"');
				}
				
				data.field.inherit_config = $('input[name="inherit_config"]').val();
				
				layer.closeAll();

				$.ajax({
					url: "ajax.php?act=updateset",
					type: "POST",
					dataType: "json",
					data: data.field,
					beforeSend: function() {
						layer.msg("正在更新", {
							icon: 16,
							shade: 0.05,
							time: 1000
						});
					},
					success: function(data) {
						layer.closeAll();
						
						if(data.code==1){
							layer.msg("保存成功", {
								icon: 1,
								time: 1000,
								shade: 0.1
							}, function() {
								setTimeout(function() {
									window.location.reload();
								}, 1000);
							});
						}
						else{
							layer.msg(data.msg, {
								icon: 5,
								time: 2000
							});
						}
					},
					error: function(data) {
						layer.closeAll();
						
						layer.msg("操作失败，请重试", {
							icon: 5,
							time: 2000
						});
					}
				});
				return false;
			});

			form.on("submit(reset)", function(data) {
				layer.closeAll();
				
				layer.confirm('确定要重置背景设置吗？', {
					icon: 3,
					title: '提示',
					btn: ['确定','取消']
				}, function(index){
					$("input[name='dayimg']").val("https://api.qjqq.cn/api/Img?sort=belle");
					$("input[name='nightimg']").val("https://www.dmoe.cc/random.php");
					$("input[name='bgswitch']").prop("checked", true);
					form.render();
					
					$("button[lay-filter='submit']").click();
					
					layer.close(index);
				});
				return false;
			});

			form.on("switch(ggswitch)", function(obj) {
				var checked = obj.elem.checked;
				if(checked){
					$(".wzggs").html('<div class="layui-form-item"><div class="gg"><div class="layui-input-block"><textarea name="wzgg" id="wzggs" class="layui-textarea" placeholder="请输入网站公告内容"></textarea></div></div></div>');
					$("#wzggs").val('<?php echo str_replace(array("\r\n", "\r", "\n"), "", addslashes($subconf['wzgg'])); ?>');
				} else {
					$(".wzggs").hide();
				}
			});

			form.on("switch(kfswitch)", function(obj) {
				var checked = obj.elem.checked;
				var $input = $("input[name='kf']");
				if(!checked) {
					$input.closest('.input-group').hide();
				} else {
					$input.closest('.input-group').show();
					if(!$input.val()) {
						$input.val('<?php echo addslashes($subconf['kf']); ?>');
					}
				}
			});

			form.on("switch(panswitch)", function(obj) {
				var checked = obj.elem.checked;
				var $input = $("input[name='pan']");
				if(!checked) {
					$input.closest('.input-group').hide();
				} else {
					$input.closest('.input-group').show();
					if(!$input.val()) {
						$input.val('<?php echo addslashes($subconf['pan']); ?>');
					}
				}
			});

			form.on("switch(bgswitch)", function(obj) {
				var checked = obj.elem.checked;
				var $dayInput = $("input[name='dayimg']");
				var $nightInput = $("input[name='nightimg']");
				if(!checked) {
					$dayInput.closest('.input-group').hide();
					$nightInput.closest('.input-group').hide();
				} else {
					$dayInput.closest('.input-group').show();
					$nightInput.closest('.input-group').show();
					if(!$dayInput.val()) {
						$dayInput.val('<?php echo addslashes($subconf['dayimg']); ?>');
					}
					if(!$nightInput.val()) {
						$nightInput.val('<?php echo addslashes($subconf['nightimg']); ?>');
					}
				}
			});

			form.on("switch(multi_domain)", function(obj) {
				if(obj.elem.checked) {
					$(".domain-list").show();
				} else {
					$(".domain-list").hide();
				}
			});

			$("<style>").text(`
				.input-group {
					transition: all 0.3s ease;
				}
				.input-group.hide {
					display: none;
				}
			`).appendTo("head");

			$("input[type='checkbox']").each(function() {
				var name = $(this).attr('name');
				if(name) {
					if(name === 'ggswitch') {
						$(this).trigger('change');
					} else if(name === 'kfswitch' || name === 'panswitch' || name === 'bgswitch') {
						if(!$(this).prop('checked')) {
							var $input = $("input[name='" + name.replace('switch', '') + "']");
							$input.closest('.input-group').hide();
						}
					}
				}
			});

			$('.md-toolbar button[data-md]').click(function() {
				var type = $(this).data('md');
				var textarea = $('#wzggs');
				var start = textarea[0].selectionStart;
				var end = textarea[0].selectionEnd;
				var text = textarea.val();
				var selectedText = text.substring(start, end);
				
				var insertion = '';
				switch(type) {
					case 'h1':
						insertion = '# ' + (selectedText || '标题1');
						break;
					case 'h2':
						insertion = '## ' + (selectedText || '标题2');
						break;
					case 'h3':
						insertion = '### ' + (selectedText || '标题3');
						break;
					case 'bold':
						insertion = '**' + (selectedText || '粗体文本') + '**';
						break;
					case 'italic':
						insertion = '*' + (selectedText || '斜体文本') + '*';
						break;
					case 'strike':
						insertion = '~~' + (selectedText || '删除线文本') + '~~';
						break;
					case 'link':
						insertion = '[' + (selectedText || '链接文本') + '](https://example.com)';
						break;
					case 'image':
						insertion = '![' + (selectedText || '图片描述') + '](https://example.com/image.jpg)';
						break;
					case 'code':
						if(selectedText.includes('\n')) {
							insertion = '```\n' + (selectedText || 'code') + '\n```';
						} else {
							insertion = '`' + (selectedText || 'code') + '`';
						}
						break;
					case 'ul':
						insertion = '- ' + (selectedText || '列表项');
						break;
					case 'ol':
						insertion = '1. ' + (selectedText || '列表项');
						break;
					case 'quote':
						insertion = '> ' + (selectedText || '引用文本');
						break;
					case 'table':
						insertion = '\n| 表头1 | 表头2 | 表头3 |\n|--------|--------|--------|\n| 内容1 | 内容2 | 内容3 |\n';
						break;
					case 'hr':
						insertion = '\n---\n';
						break;
					case 'emoji':
						insertion = ':smile:';
						break;
				}
				
				textarea.val(text.substring(0, start) + insertion + text.substring(end));
				textarea.focus();
				return false;
			});

			$(document).on('click', '#previewMd', function() {
				if (typeof marked === 'undefined') {
					layer.msg('Markdown解析器尚未加载完成，请稍后再试', {icon: 2});
					return false;
				}

				var content = $('#wzggs').val();
				var $preview = $('#mdPreview');
				var $textarea = $('#wzggs');
				
				if($preview.is(':visible')) {
					$preview.fadeOut(300, function() {
						$(this).removeClass('fade-enter fade-enter-active');
						$textarea.fadeIn(300);
					});
					$(this).text('预览');
				} else {
					try {
						var htmlContent = marked.parse(content);
						$preview.html(htmlContent);
						
						if (typeof hljs !== 'undefined') {
							$preview.find('pre code').each(function(i, block) {
								try {
									hljs.highlightElement(block);
								} catch (e) {}
							});
						}
						
						$textarea.fadeOut(300, function() {
							$preview.addClass('fade-enter')
								.show()
								.offset(); // 触发重排以应用动画
							$preview.addClass('fade-enter-active');
						});
						$(this).text('编辑');
					} catch(e) {
						layer.msg('预览生成失败', {icon: 2});
					}
				}
				return false;
			});

			$('#insertTemplate').click(function() {
				var template = `# 🌟 欢迎使用故离端口系统\n\n## 🎉 最新更新 v4\n我们很高兴地宣布新版本发布了！以下是主要更新内容：\n\n### 🚀 功能优化\n- ✨ 新增在线支付功能\n- 🔒 增强账号安全性\n- 🎨 优化用户界面体验\n- 🔄 提升系统稳定性\n\n### 📝 使用说明\n1. 账号注册：\n   - 支持卡密注册\n   - 支持在线支付注册\n2. 账号续费：\n   - 可使用卡密续费\n   - 支持支付宝/微信支付\n\n### 💡 使用技巧\n> **温馨提示**：首次使用请仔细阅读以下内容\n\n### 📊 套餐价格\n\n| 套餐类型 | 时长 | 价格 |\n|---------|------|------|\n| 体验套餐 | 1天  | ¥1   |\n| 月卡    | 30天 | ¥15  |\n| 季卡    | 90天 | ¥40  |\n| 年卡    | 365天| ¥150 |\n\n### 🎯 特别说明\n1. 严禁违规使用\n2. 禁止账号共享\n3. 有问题请联系客服\n\n### 🔗 快速链接\n- [使用教程](https://example.com/tutorial)\n- [常见问题](https://example.com/faq)\n- [用户协议](https://example.com/terms)\n\n---\n\n### 📞 联系方式\n- 客服QQ：[点击添加](http://wpa.qq.com/msgrd?v=3&uin=您的QQ&site=qq&menu=yes)\n- 官方群：123456789\n- 技术支持：support@example.com\n\n> 🌈 感谢您的使用，我们会持续优化系统，为您提供更好的服务！\n\n---\n*最后更新时间：${new Date().toLocaleDateString()}*`;
				
				$('#wzggs').val(template);
				return false;
			});
		});
	</script>
</html>
