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
											<div class="wzggs" style="margin-top: 10px;">
											<?php
											if($subconf['ggswitch']==1){
												echo '<div class="layui-form-item"><div class="gg"><div class="layui-input-block"><textarea name="wzgg" class="layui-textarea" placeholder="请输入网站公告内容">'. $subconf['wzgg'].'</textarea></div></div></div>';
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
		layui.use(["jquery", "form", "element", "util", "transfer"], function() {
			var $ = layui.$,
				form = layui.form,
				element = layui.element,
                layedit = layui.layedit,
				transfer = layui.transfer,
				util = layui.util;
				
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
					
					// 获取transfer数据
					var transferId = 'inheritAppsTransfer_' + groupId;
					var transferData = layui.transfer.getData(transferId);
					var inheritApps = [];
					
					if(transferData && Array.isArray(transferData)) {
						inheritApps = transferData.map(function(item) {
							return item.value;
						});
					}
					
					// 只要有主应用或继承应用就保存
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
				console.log('保存的继承配置:', config); // 添加调试日志
			}
			
			// 修改initInheritGroup函数
			function initInheritGroup(groupId, data, mainApps, inheritApps) {
				var group = $(getInheritGroupTemplate(groupId));
				$('#inherit_groups').append(group);
				
				// 填充应用数据
				var mainAppSelect = group.find('.main-app-select');
				var transferData = [];
				
				// 确保mainApps和inheritApps是数组
				mainApps = Array.isArray(mainApps) ? mainApps : [];
				inheritApps = Array.isArray(inheritApps) ? inheritApps : [];
				
				console.log('初始化组' + groupId + ':', {
					mainApps: mainApps,
					inheritApps: inheritApps
				});
				
				if(Array.isArray(data)) {
					data.forEach(function(app) {
						// 添加到主应用选择框
						var mainSelected = mainApps.includes(app.appcode) ? 'selected' : '';
						mainAppSelect.append('<option value="' + app.appcode + '" ' + mainSelected + '>' + app.appname + ' [' + app.appcode + ']</option>');
						
						// 只有不是主应用的才能添加到穿梭框
						if(!mainApps.includes(app.appcode)) {
							transferData.push({
								value: app.appcode,
								title: app.appname + ' [' + app.appcode + ']',
								disabled: false
							});
						}
					});
				}
				
				// 渲染穿梭框
				transfer.render({
					elem: '#inherit_apps_transfer_' + groupId,
					title: ['可选应用', '已选应用'],
					id: 'inheritAppsTransfer_' + groupId,
					data: transferData,
					value: inheritApps, // 设置已选中的值
					text: {
						none: '无数据',
						searchNone: '无匹配数据'
					},
					onchange: function(data, index) {
						console.log('穿梭框数据变化:', data);
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
				
				// 监听主应用选择
				form.on('select(main_app_' + groupId + ')', function(data) {
					// 获取当前选中的值
					var selectedValues = data.value || [];
					
					// 获取所有应用数据
					$.ajax({
						url: "ajax.php?act=getapps",
						type: "POST",
						dataType: "json",
						success: function(response) {
							if(response.code == 1) {
								var allApps = response.data;
								
								// 获取当前已选中的继承应用
								var currentInheritApps = transfer.getData('inheritAppsTransfer_' + groupId).map(function(item) {
									return item.value;
								});
								
								// 重新渲染穿梭框
								var newTransferData = [];
								
								// 过滤出未被选为主应用的应用
								allApps.forEach(function(app) {
									if(!selectedValues.includes(app.appcode)) {
										newTransferData.push({
											value: app.appcode,
											title: app.appname + ' [' + app.appcode + ']',
											disabled: false
										});
									}
								});
								
								// 重新渲染穿梭框
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
										console.log('穿梭框数据变化:', data);
										saveInheritConfig();
									}
								});
								
								// 保存配置
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
							// 保存原有的配置input
							var $configInput = $('input[name="inherit_config"]');
							var configValue = $configInput.val();
							
							// 清空现有继承组
							$('#inherit_groups').empty();
							
							// 还原配置input
							$('#inherit_groups').append($configInput);
							
							// 获取保存的配置
							var config = {groups: []};
							
							try {
								if(configValue) {
									// 递归解码HTML实体
									var decodedStr = configValue;
									var prevStr = '';
									while(decodedStr !== prevStr) {
										prevStr = decodedStr;
										decoded_str = $('<div/>').html(decodedStr).text();
									}
									config = JSON.parse(decodedStr);
									console.log('加载的继承配置:', config); // 添加调试日志
								}
							} catch(e) {
								console.error('解析继承配置失败:', e);
								layer.msg('解析继承配置失败，将重置配置', {icon: 0});
							}
							
							// 确保groups是数组
							if(!Array.isArray(config.groups)) {
								config.groups = [];
							}
							
							// 如果有保存的组,则初始化它们
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
								// 否则创建一个新组
								initInheritGroup(1, data.data, [], []);
							}
							
							saveInheritConfig(); // 保存初始配置
						} else {
							layer.msg(data.msg || "获取应用列表失败", {icon: 5});
						}
					},
					error: function(xhr, status, error) {
						console.error('获取应用列表失败:', error);
						layer.msg("获取应用列表失败: " + error, {icon: 5});
					}
				});
			}
			
			// 添加继承组按钮点击事件
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
			
			// 删除继承组
			$(document).on('click', '.delete-group', function() {
				var $group = $(this).closest('.inherit-group');
				var groupId = $group.data('group-id');
				
				// 移除组元素
				$group.remove();
				saveInheritConfig();
			});
			
			// 页面加载时获取应用列表
			if($('input[name="inherit_enabled"]').prop('checked')) {
				loadApps();
			} else {
				// 继承功能关闭时隐藏继承组界面，但保留配置
				$('#inherit_groups').children().not('input[name="inherit_config"]').hide();
			}
			
			// 监听继承开关
			form.on("switch(inherit_enabled)", function(obj) {
				if(obj.elem.checked) {
					// 开启时加载并显示继承组
					loadApps();
					$('#inherit_groups').children().show();
				} else {
					// 关闭时只隐藏继承组界面，保留配置
					$('#inherit_groups').children().not('input[name="inherit_config"]').hide();
				}
			});

			// 修改表单提交处理
			form.on("submit(submit)", function(data) {
				if (data.field.wzgg) {
					data.field['wzgg'] = data.field.wzgg.replace(/< >/g, " ")
						.replace(/<\/ >/g, " ")
						.replace(/document/g, " ")
						.replace(/'/g, '"')
						.replace(/\n|\r/g, "");
				}
				
				// 保留继承配置，无论开关状态如何
				data.field.inherit_config = $('input[name="inherit_config"]').val();
				
				console.log('提交的数据:', data.field); // 添加调试日志
				
				// 清除所有已有的消息
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
						// 清除加载提示
						layer.closeAll();
						
						if(data.code==1){
							layer.msg("保存成功", {
								icon: 1,
								time: 1000,
								shade: 0.1
							}, function() {
								// 保存成功后等待1秒再刷新,让用户看到成功提示
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
						// 清除加载提示
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
				// 清除所有已有的消息
				layer.closeAll();
				
				layer.confirm('确定要重置背景设置吗？', {
					icon: 3,
					title: '提示',
					btn: ['确定','取消']
				}, function(index){
					// 设置默认值
					$("input[name='dayimg']").val("https://api.qjqq.cn/api/Img?sort=belle");
					$("input[name='nightimg']").val("https://www.dmoe.cc/random.php");
					$("input[name='bgswitch']").prop("checked", true);
					form.render(); // 重新渲染表单
					
					// 自动触发保存
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

			// 处理多域名开关
			form.on("switch(multi_domain)", function(obj) {
				if(obj.elem.checked) {
					$(".domain-list").show();
				} else {
					$(".domain-list").hide();
				}
			});

			// 移除禁用状态的样式
			$("<style>").text(`
				.input-group {
					transition: all 0.3s ease;
				}
				.input-group.hide {
					display: none;
				}
			`).appendTo("head");

			// 初始化开关状态
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
		});
	</script>
</html>
