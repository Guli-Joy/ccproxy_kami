<?php
include("../includes/common.php");
if (!($islogin == 1)) {
    exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title><?php echo $subconf['hostname']?>用户管理</title>
		<meta name="renderer" content="webkit" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
		<?php
include("foot.php");
?>
		<!-- <link rel="stylesheet" href="../assets/layui/css/layui.css?v=20201111001?v=20201111001" />
		<link rel="stylesheet" type="text/css" href="./css/theme.css?v=20201111001" /> -->
	</head>
	<body>
		<!-- 筛选条件 -->
		<div class="layui-card">
			<div class="layui-card-body layui-form">
				<div class="layui-form-item" style="padding-right: 5vw;padding-top: 15px;">
					<label class="layui-form-label" title="用户名">
						用户名：
					</label>
					<div class="layui-input-inline">
						<input type="text" name="user" class="layui-input" />
					</div>
					<label class="layui-form-label" title="应用">
						应用：
					</label>
					<div class="layui-input-inline">
						<select name="app" lay-filter="state">
							<option value=""></option>
						</select>
					</div>
					<label class="layui-form-label" title="账号状态">
						账号状态：
					</label>
					<div class="layui-input-inline">
						<select name="expire_filter" lay-filter="expire_filter">
							<option value="all">全部账号</option>
							<option value="expired">已到期</option>
							<option value="unexpired">未到期</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<!-- 表格 -->
		<div class="layui-card">
			<div class="layui-card-body">
				<table id="server_list" lay-filter="server_list"></table>
			</div>
		</div>
	</body>
	<!-- <script src="https://www.layuicdn.com/layui/layui.js?v=20201111001"></script> -->
    <!-- <script src="../assets/layui/layui.js"></script> -->
	<script type="text/html" id="server_listTool">
		<div class="layui-btn-container">
			<button class="layui-btn layui-btn-normal layui-btn-sm" lay-event="search"><i class="layui-icon layui-icon-search"></i><span>搜索</span></button>
			<button class="layui-btn layui-btn-warm layui-btn-sm" lay-event="compensateTime"><i class="layui-icon layui-icon-time"></i><span>补偿时间</span></button>
			<button class="layui-btn layui-btn-danger layui-btn-sm" lay-event="cleanExpired"><i class="layui-icon layui-icon-delete"></i><span>清理过期</span></button>
			<button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="New"><i class="layui-icon layui-icon-add-1"></i><span>新增</span></button>
			<button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="batchNew"><i class="layui-icon layui-icon-template"></i><span>批量新增</span></button>
			<button class="layui-btn layui-btn-sm layui-btn-primary" lay-event="edit"><i class="layui-icon layui-icon-edit"></i><span>编辑</span></button>
			<button class="layui-btn layui-btn-danger layui-btn-sm" lay-event="Del"><i class="layui-icon layui-icon-delete"></i><span>删除</span></button>
		</div>
	</script>
	<!-- 表格按钮 -->
	<script type="text/html" id="btnTool">
		<a class="layui-btn layui-btn-sm layui-btn-normal" lay-event="modify">修改</a>
		<a class="layui-btn layui-btn-sm layui-btn-normal" lay-event="select">选择</a>
		<a class="layui-btn layui-btn-sm layui-btn-danger" lay-event="del">删除</a>
	</script>
	<!-- 表格开关 -->
	<script type="text/html" id="stateTool">
		<input type="checkbox" name="state" value="{{d.state}}" lay-skin="switch" lay-text="开启|关闭" lay-filter="state" {{ d.state == "1" ? 'checked' : '' }} />
	</script>
	<!-- 表格开关 //background-color:#33cabb-->
	<style>
		.green{
			background-color:#33cabb;
		}
	</style>
	<script type="text/html" id="pwddot">
	<span style="width: 20px;height: 20px;" class="layui-badge-dot {{d.pwdstate==1?'green':''}}"></span>
	</script>
	<script type="text/html" id="expirdot">
	<span style="width: 20px;height: 20px;" class="layui-badge-dot {{d.expire==0?'green':''}}"></span>
	</script>
	<!-- 表格链接 -->
	<script type="text/html" id="certificateTool">
		<a href="{{d.certificate}}" class="layui-table-link" target="_blank">{{ d.certificate }}</a>
	</script>
	<!-- 表格图片 -->
	<script type="text/html" id="imgTpl">
		<a href="{{d.url}}" class="layui-table-link" target="_blank"><img src="{{ d.url }}" /></a>
	</script>
	<script>
		layui.use(["jquery", "table", "laydate", "form", "upload", "element"], function() {
			var $ = layui.$,
				table = layui.table,
				laydate = layui.laydate,
				form = layui.form,
				upload = layui.upload,
				element = layui.element;

				window.where = function() {
				var data = [
					"id", "code", "price", "user", "state", "app", "expire_filter"
				];
				var json = {};
				for (var key in data) {
					json[data[key]] = query(data[key]);
				}
				return json;
			}
			select();
			form.render("select");
			table.render({
				elem: "#server_list",
				escape:true,
				height: "full-170",
				url: "ajax.php?act=getuserall",
				page: true,
				limit: 100,
				limits: [10, 20, 30, 50, 100, 200, 300, 500, 1000, 2000, 3000, 5000, 10000],
				title: "用户",
				// skin: "line",
				// size: "lg",
				toolbar: "#server_listTool",
				where: where(),
                cols: [
					[{
						type: "checkbox"
					}, {
						field: "id",
						title: "序号",
						width: 100,
						sort: true,
						align: "center"
					}, {
						field: "user",
						title: "用户名",
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}, {
						field: "pwd",
						title: "密码",
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}, {
						field: "state",
						title: "账号状态",
						//minWidth: 100,
						align: "center",
						width: 170,
						toolbar: "#stateTool"
						// sort: true
					}, {
						field: "pwdstate",
						title: "密码状态",
						//minWidth: 100,
						width: 170,
						align: "center",
						toolbar: "#pwddot"
						// sort: true
					}, {
						field: "connection",
						title: "连接数",
						//minWidth: 100,
						align: "center",
						width: 100,
						// hide: true
						// sort: true
					}, {
						field: "bandwidthup",
						title: "上行带宽",
						//minWidth: 100,
						align: "center",
						width: 100,
						// hide: true
						// sort: true
					}, {
						field: "bandwidthdown",
						title: "下行带宽",
						//minWidth: 100,
						align: "center",
						width: 100,
						// hide: true
						// sort: true
					}, {
						field: "disabletime",
						title: "到期时间",
						//minWidth: 100,
						align: "center",
						width: 170,
						// hide: true
						sort: true
					}, {
						field: "expire",
						title: "到期状态",
						//minWidth: 100,
						width: 170,
						// sort:true,
						align: "center",
						toolbar:"#expirdot"
						// sort: true
					}, {
						field: "appname",
						title: "所属应用",
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}, {
						field: "serverip",
						title: "IP",
						hide:true,
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}]
				]
			});
			function select() {
			$.ajax({
				url: "ajax.php?act=getapp",
				type: "POST",
				dataType: "json",
				success: function(data) {
					if (data.code == "1") {
						var elem = $("[name=app]");
						// var elem2 = $("[name=serverip]");
						for (var key in data.msg) {
							// console.log(elem2);
							var json = data.msg[key],
								appname = json.appname,
								appcode = json.appcode;
							item = '<option value="' + appcode + '">' + appname + '</option>';
							// item2 = '<option value="' + ip + '">' + comment + '[' + ip + ']</option>';
							elem.append(item);
							// elem2.append(item2);
						}
						form.render("select");
					}
				},
				error: function(data) {
					// console.log(data);
					layer.msg("获取用户失败", {
						icon: 5
					});
				}
			});
		}
			//监听工具栏事件
			table.on("toolbar(server_list)", function(obj) {
				var checkStatus = table.checkStatus(obj.config.id);
				switch (obj.event) {
					case 'search':
						reload("server_list");
						break;
					case 'New':
						New();
						break;
					case 'batchNew':
						batchNew();
						break;
					case 'edit':
						if (checkStatus.data.length != 1) {
							showMsg("请选择1条记录");
							return;
						}
						edit(checkStatus);
						break;
					case 'Del':
						if (checkStatus.data.length == 0) {
							showMsg("未选择记录");
							return;
						}
						Del(table, checkStatus);
						break;
					case 'compensateTime':
						layer.open({
							type: 1,
							title: '账号时间补偿',
							area: ['500px', '400px'],
							content: `
								<div class="layui-form" lay-filter="compensateForm" style="padding: 20px;">
									<div class="layui-form-item">
										<label class="layui-form-label">选择应用</label>
										<div class="layui-input-block">
											<select name="compensate_app" lay-verify="required" lay-filter="compensate_app">
												<option value="">请选择应用</option>
											</select>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">账号状态</label>
										<div class="layui-input-block">
											<select name="compensate_filter" lay-verify="required">
												<option value="all">全部账号</option>
												<option value="expired">已到期</option>
												<option value="unexpired">未到期</option>
											</select>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label">补偿时间</label>
										<div class="layui-input-inline" style="width: 100px;">
											<input type="number" name="compensate_value" required lay-verify="required|number" placeholder="请输入数值" class="layui-input">
										</div>
										<div class="layui-input-inline" style="width: 100px;">
											<select name="compensate_unit" lay-verify="required">
												<option value="days">天</option>
												<option value="hours">小时</option>
												<option value="minutes">分钟</option>
											</select>
										</div>
									</div>
									<div class="layui-form-item">
										<div class="layui-input-block">
											<button class="layui-btn" lay-submit lay-filter="compensateSubmit">确定补偿</button>
											<button type="reset" class="layui-btn layui-btn-primary">重置</button>
										</div>
									</div>
								</div>
							`,
							success: function() {
								// 初始化应用下拉框
								$.ajax({
									url: 'ajax.php?act=getapp',
									type: 'GET',
									dataType: 'json',
									success: function(res) {
										if(res.code == "1") {
											var html = '<option value="">请选择应用</option>';
											for(var i = 0; i < res.msg.length; i++) {
												html += '<option value="' + res.msg[i].appcode + '">' + res.msg[i].appname + '</option>';
											}
											$('[name=compensate_app]').html(html);
											form.render('select');
										} else {
											layer.msg("获取应用列表失败", {icon: 2});
										}
									},
									error: function() {
										layer.msg("获取应用列表失败", {icon: 2});
									}
								});
								form.render();
							}
						});
						break;
					case 'cleanExpired':
						layer.open({
							type: 1,
							title: '清理过期账号',
							area: ['400px', '300px'],
							content: `
								<div class="layui-form" lay-filter="cleanExpiredForm" style="padding: 20px;">
									<div class="layui-form-item">
										<label class="layui-form-label">选择应用</label>
										<div class="layui-input-block">
											<select name="clean_app" lay-verify="required">
												<option value="">请选择应用</option>
												<option value="all">所有应用</option>
											</select>
										</div>
									</div>
									<div class="layui-form-item">
										<div class="layui-input-block">
											<button class="layui-btn" lay-submit lay-filter="cleanExpiredSubmit">开始清理</button>
											<button type="reset" class="layui-btn layui-btn-primary">重置</button>
										</div>
									</div>
								</div>
							`,
							success: function() {
								// 初始化应用下拉框
								$.ajax({
									url: 'ajax.php?act=getapp',
									type: 'GET',
									dataType: 'json',
									success: function(res) {
										if(res.code == "1") {
											var html = '<option value="">请选择应用</option>';
											html += '<option value="all">所有应用</option>';
											for(var i = 0; i < res.msg.length; i++) {
												html += '<option value="' + res.msg[i].appcode + '">' + res.msg[i].appname + '</option>';
											}
											$('[name=clean_app]').html(html);
											form.render('select');
										} else {
											layer.msg("获取应用列表失败", {icon: 2});
										}
									},
									error: function() {
										layer.msg("获取应用列表失败", {icon: 2});
									}
								});
								form.render();
							}
						});

						// 监听清理表单提交
						form.on('submit(cleanExpiredSubmit)', function(data) {
							layer.confirm('确定要清理选中应用的过期账号吗？此操作不可恢复！', {
								icon: 3,
								title: '警告',
								btn: ['确定清理','取消']
							}, function(index){
								// 创建进度提示层
								var progressIndex = layer.open({
									type: 1,
									title: '清理进度',
									closeBtn: 0,
									area: ['500px', '200px'],
									content: `<div style="padding: 20px;">
										<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="cleanProgress">
											<div class="layui-progress-bar" lay-percent="0%"></div>
										</div>
										<div id="cleanStatus" style="margin-top: 15px;text-align: center;">
											正在处理中...
										</div>
									</div>`
								});

								// 获取选中的应用
								var selectedApp = data.field.clean_app === 'all' ? '' : data.field.clean_app;

								// 分批处理函数
								function processBatch(offset) {
									$.ajax({
										url: 'ajax.php',
										type: 'POST',
										dataType: 'json',
										timeout: 60000,
										data: {
											act: 'cleanexpired',
											app: selectedApp,
											offset: offset,
											batch_size: 20
										},
										success: function(res) {
											if(res.code == 1) {
												// 更新进度条
												var percent = Math.round((res.details.total_processed / res.details.total) * 100);
												element.progress('cleanProgress', percent + '%');
												$('#cleanStatus').html('已处理: ' + res.details.total_processed + '/' + res.details.total);
												
												// 检查是否还有更多数据需要处理
												if(res.details.has_more && res.details.total_processed < res.details.total) {
													// 继续处理下一批，增加延迟
													setTimeout(function() {
														processBatch(res.details.next_offset);
													}, 500);
												} else {
													// 当前批次处理完成，检查是否需要开始新一轮清理
													checkAndStartNewRound(res);
												}
											} else {
												layer.close(progressIndex);
												layer.msg(res.msg || "清理失败", {icon: 5});
											}
										},
										error: function() {
											layer.close(progressIndex);
											layer.msg("清理失败，请重试", {icon: 2});
										}
									});
								}

								// 添加检查并开始新一轮清理的函数
								function checkAndStartNewRound(lastResult) {
									// 检查是否还有过期账号
									$.ajax({
										url: 'ajax.php',
										type: 'POST',
										dataType: 'json',
										data: {
											act: 'checkexpired',
											app: selectedApp
										},
										success: function(res) {
											if(res.code == 1 && res.has_expired) {
												// 还有过期账号，更新状态消息
												$('#cleanStatus').html('发现新的过期账号，开始新一轮清理...');
												
												// 重置进度条
												element.progress('cleanProgress', '0%');
												
												// 开始新一轮清理
												setTimeout(function() {
													processBatch(0);
												}, 1000);
											} else {
												// 所有过期账号都已清理完成
												layer.close(progressIndex);
												showFinalCleanResults(lastResult);
												// 刷新表格
												reload("server_list");
											}
										},
										error: function() {
											layer.close(progressIndex);
											showFinalCleanResults(lastResult);
											// 刷新表格
											reload("server_list");
										}
									});
								}

								// 添加显示最终清理结果的函数
								function showFinalCleanResults(res) {
									var resultMsg = res.details.cleaned > 0 ? '清理成功' : '没有需要清理的过期账号';
									
									layer.alert(resultMsg, {
										icon: 1,
										title: '清理完成',
										btn: ['确定'],
										anim: 1,
										shadeClose: true,
										shade: 0.1
									});
								}

								// 开始第一批处理
								processBatch(0);
								layer.close(index);
							});
						});
						break;
				}
			});

			// 修改补偿时间的处理代码
			form.on('submit(compensateSubmit)', function(data) {
				// 创建进度提示层
				var progressIndex = layer.open({
					type: 1,
					title: '补偿进度',
					closeBtn: 0,
					area: ['500px', '200px'],
					content: `<div style="padding: 20px;">
						<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="compensateProgress">
							<div class="layui-progress-bar" lay-percent="0%"></div>
						</div>
						<div id="compensateStatus" style="margin-top: 15px;text-align: center;">
							正在处理中...
						</div>
						<div id="compensateDetail" style="margin-top: 10px;font-size: 12px;color: #666;text-align: center;"></div>
					</div>`
				});

				// 记录重试次数
				var retryCount = {};
				var maxRetries = 3;
				var isProcessing = false;

				// 分批处理函数
				function processBatch(formData, offset) {
					// 防止重复请求
					if (isProcessing) return;
					isProcessing = true;
					
					// 更新状态
					$('#compensateStatus').html('正在处理第 ' + offset + ' 批...');
					
					$.ajax({
						url: 'ajax.php',
						type: 'POST',
						dataType: 'json',
						timeout: 60000, // 60秒超时
						data: {
							act: 'compensatetime',
							app: formData.compensate_app,
							expire_filter: formData.compensate_filter,
							value: formData.compensate_value,
							unit: formData.compensate_unit,
							offset: offset,
							batch_size: 20
						},
						success: function(res) {
							isProcessing = false;
							if(res.code == 1) {
								// 重置当前批次的重试计数
								if(retryCount[offset]) {
									delete retryCount[offset];
								}
								
								// 更新进度条
								var percent = Math.round((res.details.total_processed / res.details.total) * 100);
								element.progress('compensateProgress', percent + '%');
								
								// 更新状态信息
								var statusMsg = '已处理: ' + res.details.total_processed + '/' + res.details.total;
								statusMsg += ' (成功: ' + res.details.success + ', 跳过: ' + res.details.skipped + ', 失败: ' + res.details.failed + ')';
								$('#compensateStatus').html(statusMsg);
								
								// 显示详细信息
								if(res.details.errors && res.details.errors.length > 0) {
									var errorMsg = '最近错误: ' + res.details.errors[res.details.errors.length - 1];
									$('#compensateDetail').html(errorMsg);
								} else {
									$('#compensateDetail').html('');
								}
								
								// 检查是否还有更多数据需要处理
								if(res.details.has_more) {
									// 继续处理下一批，增加延迟
									setTimeout(function() {
										processBatch(formData, res.details.next_offset);
									}, 500);
								} else {
									// 处理完成，关闭进度层并显示结果
									layer.close(progressIndex);
									showCompletionMessage(res);
								}
							} else {
								// 如果是已处理过的提示，继续处理
								if(res.msg && res.msg.indexOf('该批次已经处理过') !== -1) {
									console.log('该批次已处理，继续下一批');
									if(res.details && res.details.has_more) {
										setTimeout(function() {
											processBatch(formData, res.details.next_offset);
										}, 500);
									} else {
										layer.close(progressIndex);
										showCompletionMessage(res);
									}
								} else {
									// 其他错误则重试当前批次
									handleRetry(formData, offset, '处理出错: ' + res.msg);
								}
							}
						},
						error: function(xhr, status, error) {
							isProcessing = false;
							handleRetry(formData, offset, '请求失败: ' + status);
						}
					});
				}
				
				// 处理重试逻辑
				function handleRetry(formData, offset, errorMsg) {
					if(!retryCount[offset]) {
						retryCount[offset] = 0;
					}
					
					retryCount[offset]++;
					
					if(retryCount[offset] <= maxRetries) {
						var waitTime = retryCount[offset] * 2000; // 递增等待时间
						$('#compensateStatus').html('重试中... (' + retryCount[offset] + '/' + maxRetries + ')');
						$('#compensateDetail').html(errorMsg + '，' + (waitTime/1000) + '秒后重试');
						
						setTimeout(function() {
							processBatch(formData, offset);
						}, waitTime);
					} else {
						// 重试次数过多，记录并继续下一批
						$('#compensateDetail').html('批次' + offset + '多次失败，已跳过');
						
						// 重试限制达到，尝试跳到下一批
						setTimeout(function() {
							processBatch(formData, offset + 20);
						}, 3000);
					}
				}

				// 开始第一批处理
				processBatch(data.field, 0);
				return false;
			});

			// 修改完成消息显示函数
			function showCompletionMessage(res) {
				var resultMsg = res.msg;
				if(res.details) {
					var unitText = {
						'days': '天',
						'hours': '小时', 
						'minutes': '分钟'
					}[res.details.unit] || '天';
					
					resultMsg = '补偿处理已完成<br><br>处理详情：<br>';
					resultMsg += '- 补偿时间：' + res.details.value + unitText + '<br>';
					resultMsg += '- 总处理：' + res.details.total_processed + '/' + res.details.total + '个<br>';
					resultMsg += '- 成功：' + res.details.success + '个<br>';
					resultMsg += '- 跳过：' + res.details.skipped + '个<br>';
					resultMsg += '- 失败：' + res.details.failed + '个<br>';
					
					// 限制显示的错误数量
					if(res.details.errors && res.details.errors.length > 0) {
						var maxErrorsToShow = 5;
						var errorCount = res.details.errors.length;
						var shownErrors = res.details.errors.slice(0, maxErrorsToShow);
						
						resultMsg += '<br>失败账号' + (errorCount > maxErrorsToShow ? '(仅显示前' + maxErrorsToShow + '个)' : '') + '：<br>';
						resultMsg += shownErrors.join('<br>');
					}
				}
				
				layer.alert(resultMsg, {
					icon: 1,
					title: '补偿完成',
					btn: ['确定'],
					anim: 1,
					area: ['500px', 'auto'],
					maxHeight: '600px',
					shadeClose: true,
					shade: 0.1,
					yes: function(index) {
						layer.close(index);
						table.reload('server_list');
					}
				});
			}

			// 优化其他消息提示
			function showMsg(msg, icon) {
				layer.msg(msg, {
					icon: icon || 2,
					time: 1000,
					anim: 1,
					shade: 0.1
				});
			}

			table.on('rowDouble(server_list)', function(obj){
				var data=obj.data;
				if(data!=null) {
					layer.open({
						type: 2,
						title: "编辑用户",
						area: ["400px", "400px"],
						maxmin: false,
						content: "edituser.php?user="+data.user+"&pwd="+data.pwd+"&use_date="+data.disabletime+"&serverip="+data.serverip+"&connection="+data.connection+"&bandwidthup="+data.bandwidthup+"&bandwidthdown="+data.bandwidthdown,
						cancel: function(index, layero) {
							reload("server_list");
						}
					});
				}else{
					layer.msg("选中错误！",{
						icon: "3"
					});
				}
				//edit(1);
				});




				//选中复选框
				$('body').on("click", ".layui-table-body table.layui-table tbody tr td", function () {
            if ($(this).attr("data-field") === "0") return;
            $(this).siblings().eq(0).find('i').click();
 			});
			//触发行单击事件
			// table.on('row(server_list)', function(obj){
			// 	$(obj.tr).children().children().children().next().addClass('layui-form-checked')
			// });	
			table.on("edit(server_list)", function(obj) {
				// var server = $("[name=server]").val();
				update(obj.data.appcode, obj.data.appname, obj.data.serverip);
			});
			// table.on("select(serverip)", function(obj) {
			// 	// var server = $("[name=server]").val();
			// 	update(obj.data.appcode, obj.data.appname, obj.data.serverip);
			// });
			form.on("select(server)", function(data) {
				reload("server_list");
			});
			laydate.render({
				elem: "[name=found_date]",
				//range: true,
				done: function() {
					setTimeout(function() {
						reload("server_list");
					}, 100);
				}
			});

			form.on("select(state)", function(data) {
				reload("server_list");
			});
			form.on("select(expire_filter)", function(data) {
				reload("server_list");
			});
			$(".layui-input").keydown(function(e) {
				if (e.keyCode == 13) {
					if($("[name=app]").val()==""){
						layer.msg("请选择查询的应用！");
					}else{
						reload("server_list");
					}
					
				}
			});

			table.on("tool(server_list)", function(obj) {
				//表格按钮事件
				var data = obj.data;
				switch (obj.event) {
					case "del":
						modifyBtn(obj);
						break;
					case "modify":
						modifyBtn(obj);
						break;
					case "continued":
						continued(obj);
						break;
				};
			});

			form.on("switch(state)", function(obj) {
				
				elem=$(this).parent().parent().parent().children();
				user=elem.eq(2).text();
				pwd=elem.eq(3).text();
				day=elem.eq(9).text();
				ip=elem.eq(12).text();
				sw=$(this).val();
				connection=elem.eq(6).text()=="无限制"?-1:elem.eq(6).text();
				bandwidthup=elem.eq(7).text()=="无限制"?-1:elem.eq(7).text();
				bandwidthdown=elem.eq(8).text()=="无限制"?-1:elem.eq(8).text();
				$.ajax({
					url: "ajax.php?act=upswitchuser",
					type: "POST",
					dataType: "json",
					data: {
						usermodel:{
							user:user,
							pwd:pwd,
							day:day,
							serverip:ip,
							sw:sw,
							connection:connection,
							bandwidthup:bandwidthup,
							bandwidthdown:bandwidthdown
						}
					},
					beforeSend: function() {
						layer.msg("正在更新", {
							icon: 16,
							shade: 0.05,
							time: false
						});
					},
					success: function(data) {
						layer.msg(data.msg, {
							icon: data.icon
						});
						setTimeout(function() {
							reload("server_list");
						},1000);
					},
					error: function(data) {
						layer.alert("更新失败:"+data.msg, {
							icon: 2
						});
					}
				});
			});
			
			function New() {
				layer.open({
					type: 2,
					title: "新增用户",
					area: ["400px", "400px"],
					maxmin: false,
					content: "newuser.php?v=20201111001&preserve_case=1&inherit=1"
				});
			}

            function edit(checkStatus) {
				if (checkStatus.data.length == 1) {
					layer.open({
						type: 2,
						title: "编辑用户",
						area: ["400px", "400px"],
						maxmin: false,
						content: "edituser.php?user="+encodeURIComponent(checkStatus.data[0].user)+"&pwd="+encodeURIComponent(checkStatus.data[0].pwd)+"&use_date="+checkStatus.data[0].disabletime+"&serverip="+checkStatus.data[0].serverip+"&preserve_case=1",
						cancel: function(index, layero) {
							reload("server_list");
						}
					});
				} else {
					layer.msg("请选择1条记录", {
						icon: 3
					});
				}
			}
			function Del(table, checkStatus) {
				var data = checkStatus.data;
				var user=[];
				for (var i = 0; i < data.length; i++) {
					user.push({
							"user":data[i]["user"],
							"serverip":data[i]["serverip"]
						});
				}
				if (data.length > 0) {
					layer.confirm("确定删除选中的用户吗？", {
						icon: 3
					}, function() {
						$.ajax({
							url: "ajax.php?act=seldeluser",
							type: "POST",
							dataType: "json",
							beforeSend: function() {
								layer.msg("删除中", {
									icon: 16,
									shade: 0.05,
									time: false
								});
							},
							data: {
								item: user,
								// server: $("[name=server]").val()
							},
							success: function(data) {
								layer.msg(data.msg, {
									icon: 1
								});
								if (data.code == "1") {
									reload("server_list");
								}
							},
							error: function(data) {
								layer.msg("删除失败", {
									icon: 5
								});
							}
						});
					});
				} else {
					layer.msg("未选择记录", {
						icon: 3
					});
				}
			}
			
			

			function update(appcode, appname, serverip) {
				$.ajax({
					url: "ajax.php?act=update",
					type: "POST",
					dataType: "json",
					beforeSend: function() {
						layer.msg("正在更新数据", {
							icon: 16,
							shade: 0.05,
							time: false
						});
					},
					data: {
						appcode: appcode,
						appname: appname,
						serverip: serverip
					},
					success: function(data) {
						if (data.code== "1") {
							layer.msg(data.msg, {
								icon: 1
							});
						} else {
							layer.msg(data.msg, {
								icon: 5
							});
						}
					},
					error: function(data) {
						// console.log(data);
						layer.msg(data.msg, {
							icon: 5
						});
					}
				});
			}

			

			function query(name) {
				return $("[name=" + name + "]").val();
			}

			function batchNew() {
				layer.open({
					type: 1,
					title: "批量创建账号",
					area: ["600px", "550px"],
					content: `
						<div class="layui-form" lay-filter="batchForm" style="padding: 20px;">
							<div class="layui-form-item">
								<label class="layui-form-label">选择应用</label>
								<div class="layui-input-block">
									<select name="batch_app" lay-verify="required" lay-filter="batch_app">
										<option value="">请选择应用</option>
									</select>
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">启用继承</label>
								<div class="layui-input-block">
									<input type="checkbox" name="batch_inherit" lay-skin="switch" lay-text="开启|关闭" lay-filter="batch_inherit" value="1">
									<div class="layui-form-mid layui-word-aux">启用后将同时为继承组应用创建账号</div>
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">账号数量</label>
								<div class="layui-input-inline">
									<input type="number" name="batch_count" required lay-verify="required|number" placeholder="请输入数量" value="10" min="1" max="1000" class="layui-input">
								</div>
								<div class="layui-form-mid layui-word-aux">最多1000个</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">账号前缀</label>
								<div class="layui-input-inline">
									<input type="text" name="batch_prefix" placeholder="可选前缀" class="layui-input">
								</div>
								<div class="layui-form-mid layui-word-aux">不填则使用随机字符</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">账号格式</label>
								<div class="layui-input-block">
									<input type="radio" name="batch_user_type" value="random" title="随机字符" checked>
									<input type="radio" name="batch_user_type" value="number" title="纯数字">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">账号长度</label>
								<div class="layui-input-inline">
									<input type="number" name="batch_user_length" required lay-verify="required|number" placeholder="账号长度" value="6" min="4" max="12" class="layui-input">
								</div>
								<div class="layui-form-mid layui-word-aux">不含前缀的长度，建议4-12位</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">密码格式</label>
								<div class="layui-input-block">
									<input type="radio" name="batch_pwd_type" value="same" title="相同密码" checked>
									<input type="radio" name="batch_pwd_type" value="random" title="随机字符">
									<input type="radio" name="batch_pwd_type" value="number" title="纯数字">
								</div>
							</div>
							<div class="layui-form-item" id="batch_same_pwd_item">
								<label class="layui-form-label">相同密码</label>
								<div class="layui-input-inline">
									<input type="text" name="batch_same_pwd" placeholder="请输入密码" value="123456" class="layui-input">
								</div>
							</div>
							<div class="layui-form-item" id="batch_pwd_length_item" style="display:none;">
								<label class="layui-form-label">密码长度</label>
								<div class="layui-input-inline">
									<input type="number" name="batch_pwd_length" placeholder="密码长度" value="6" min="4" max="12" class="layui-input">
								</div>
								<div class="layui-form-mid layui-word-aux">建议4-12位</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">使用期限</label>
								<div class="layui-input-inline">
									<select name="batch_expire" lay-verify="required" lay-filter="batch_expire">
										<option value="1">1天</option>
										<option value="7">7天</option>
										<option value="30" selected>30天</option>
										<option value="90">90天</option>
										<option value="180">180天</option>
										<option value="365">365天</option>
										<option value="-1">自定义</option>
									</select>
								</div>
							</div>
							<div class="layui-form-item" id="batch_custom_date_item" style="display:none;">
								<label class="layui-form-label">到期日期</label>
								<div class="layui-input-inline">
									<input type="text" name="batch_custom_date" placeholder="YYYY-MM-DD" class="layui-input batch-date">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label">导出结果</label>
								<div class="layui-input-block">
									<input type="checkbox" name="batch_export" lay-skin="switch" lay-text="开启|关闭" value="1" checked>
									<div class="layui-form-mid layui-word-aux">创建完成后自动导出账号密码为CSV文件</div>
								</div>
							</div>
							<div class="layui-form-item">
								<div class="layui-input-block">
									<button class="layui-btn" lay-submit lay-filter="batchSubmit">开始创建</button>
									<button type="reset" class="layui-btn layui-btn-primary">重置</button>
								</div>
							</div>
						</div>
					`,
					success: function() {
						// 初始化应用下拉框
						$.ajax({
							url: 'ajax.php?act=getapp',
							type: 'GET',
							dataType: 'json',
							success: function(res) {
								if(res.code == "1") {
									var html = '<option value="">请选择应用</option>';
									for(var i = 0; i < res.msg.length; i++) {
										html += '<option value="' + res.msg[i].appcode + '">' + res.msg[i].appname + '</option>';
									}
									$('[name=batch_app]').html(html);
									form.render('select');
								} else {
									layer.msg("获取应用列表失败", {icon: 2});
								}
							},
							error: function() {
								layer.msg("获取应用列表失败", {icon: 2});
							}
						});
						
						// 获取继承配置
						$.ajax({
							url: "ajax.php?act=getset",
							type: "GET",
							dataType: "json",
							success: function(res) {
								if(res.code == "1" && res.data.inherit_enabled == "1") {
									// 显示继承选项
									$('[name=batch_inherit]').closest('.layui-form-item').show();
								}
							}
						});
						
						// 监听应用选择变化
						form.on('select(batch_app)', function(data) {
							var selectedApp = data.value;
							if(!selectedApp) return;
							
							// 检查是否是主应用，如果是则自动勾选继承
							$.ajax({
								url: "ajax.php?act=getset",
								type: "GET",
								dataType: "json",
								success: function(res) {
									if(res.code == "1" && res.data.inherit_enabled == "1" && res.data.inherit_groups) {
										try {
											// 解析继承组配置
											var inheritGroups = null;
											var decodedStr = '';
											
											// 先尝试直接解析
											try {
												inheritGroups = JSON.parse(res.data.inherit_groups);
											} catch(e) {
												console.log('直接解析继承组数据失败，尝试HTML解码: ', e.message);
												// 尝试HTML解码后再解析
												decodedStr = $('<div/>').html(res.data.inherit_groups).text();
												try {
													inheritGroups = JSON.parse(decodedStr);
												} catch(e2) {
													console.log('HTML解码后解析继承组数据失败: ', e2.message);
													console.log('解码后的数据: ', decodedStr);
													
													// 尝试修复常见的HTML实体编码问题
													decodedStr = decodedStr.replace(/&amp;quot;/g, '"')
														.replace(/&quot;/g, '"')
														.replace(/&amp;/g, '&');
													try {
														inheritGroups = JSON.parse(decodedStr);
													} catch(e3) {
														console.log('尝试修复后解析继承组数据失败: ', e3.message);
														throw new Error('无法解析继承组数据');
													}
												}
											}
											
											// 确保inheritGroups有效且包含groups属性
											if(inheritGroups && inheritGroups.groups) {
												var isMainApp = false;
												var inheritAppCount = 0;
												
												// 检查是否为主应用
												$.each(inheritGroups.groups, function(i, group) {
													if(!group.main_apps) return true; // 跳过没有main_apps的组
													
													if($.inArray(selectedApp, group.main_apps) !== -1) {
														isMainApp = true;
														inheritAppCount = group.inherit_apps ? group.inherit_apps.length : 0;
														return false; // 退出循环
													}
												});
												
												if(isMainApp) {
													// 是主应用，自动勾选继承选项
													$('[name=batch_inherit]').prop('checked', true);
													form.render('checkbox');
													
													if(inheritAppCount > 0) {
														layer.msg('检测到选择的应用是主应用，已自动启用继承功能', {
															icon: 1,
															time: 3000
														});
													}
												} else {
													// 不是主应用，取消勾选
													$('[name=batch_inherit]').prop('checked', false);
													form.render('checkbox');
												}
											} else {
												console.log('无效的继承组数据结构');
											}
										} catch(e) {
											console.error('解析继承组数据失败', e);
											// 不显示错误给用户，但取消勾选继承
											$('[name=batch_inherit]').prop('checked', false);
											form.render('checkbox');
										}
									}
								},
								error: function(xhr, status, error) {
									console.error('获取继承配置失败', status, error);
								}
							});
						});
						
						// 添加继承选择框的事件监听
						form.on('switch(batch_inherit)', function(data){
							console.log('继承选项状态变更：', data.elem.checked);
							// 这里只需记录状态，提交表单时会自动包含此选项
							if(data.elem.checked) {
								layer.msg('已启用继承功能', {icon: 1, time: 1000});
							} else {
								layer.msg('已关闭继承功能', {icon: 5, time: 1000});
							}
						});
						
						// 监听密码类型变化
						form.on('radio()', function(data){
							if(data.elem.name === 'batch_pwd_type') {
								if(data.value === 'same') {
									$('#batch_same_pwd_item').show();
									$('#batch_pwd_length_item').hide();
								} else {
									$('#batch_same_pwd_item').hide();
									$('#batch_pwd_length_item').show();
								}
							}
						});
						
						// 监听过期时间选择
						form.on('select(batch_expire)', function(data){
							if(data.value === '-1') {
								$('#batch_custom_date_item').show();
							} else {
								$('#batch_custom_date_item').hide();
							}
						});
						
						// 初始化日期选择器
						laydate.render({
							elem: '.batch-date'
						});
						
						form.render();
					}
				});
				
				// 监听批量创建表单提交
				form.on('submit(batchSubmit)', function(data) {
					// 验证表单
					if(!data.field.batch_app) {
						layer.msg('请选择应用', {icon: 2});
						return false;
					}
					
					var count = parseInt(data.field.batch_count || 0);
					if(count < 1 || count > 1000) {
						layer.msg('账号数量必须在1-1000之间', {icon: 2});
						return false;
					}
					
					if(data.field.batch_pwd_type === 'same' && !data.field.batch_same_pwd) {
						layer.msg('请输入相同密码', {icon: 2});
						return false;
					}
					
					if(data.field.batch_expire === '-1' && !data.field.batch_custom_date) {
						layer.msg('请选择到期日期', {icon: 2});
						return false;
					}
					
					// 创建进度提示层
					var progressIndex = layer.open({
						type: 1,
						title: '创建进度',
						closeBtn: 0,
						area: ['500px', '300px'],
						content: `<div style="padding: 20px;">
							<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="batchProgress">
								<div class="layui-progress-bar" lay-percent="0%"></div>
							</div>
							<div id="batchStatus" style="margin-top: 15px;text-align: center;">
								准备创建...
							</div>
							<div id="batchDetail" style="margin-top: 10px;font-size: 12px;color: #666;text-align: center;"></div>
							<div id="batchPreview" style="margin-top: 15px;max-height: 150px;overflow-y: auto;display: none;">
								<table class="layui-table" lay-size="sm">
									<thead>
										<tr>
											<th>用户名</th>
											<th>密码</th>
											<th>到期时间</th>
											<th>状态</th>
										</tr>
									</thead>
									<tbody id="batchPreviewBody"></tbody>
								</table>
							</div>
						</div>`
					});
					
					// 准备批量创建参数
					var batchData = {
						app: data.field.batch_app,
						inherit: data.field.batch_inherit === '1' ? 1 : 0,
						count: count,
						prefix: data.field.batch_prefix || '',
						user_type: data.field.batch_user_type,
						user_length: parseInt(data.field.batch_user_length || 6),
						pwd_type: data.field.batch_pwd_type,
						same_pwd: data.field.batch_same_pwd || '',
						pwd_length: parseInt(data.field.batch_pwd_length || 6),
						expire: data.field.batch_expire,
						custom_date: data.field.batch_expire === '-1' ? data.field.batch_custom_date : '',
						export: data.field.batch_export === '1' ? 1 : 0
					};
					
					// 创建账号列表
					var createdAccounts = [];
					var failedAccounts = [];
					var currentBatch = 0;
					var totalBatches = Math.ceil(count / 10); // 每批10个账号
					
					// 分批处理函数
					function processBatch(start) {
						currentBatch++;
						var end = Math.min(start + 10, count);
						var batchSize = end - start;
						
						$('#batchStatus').html('正在创建第 ' + currentBatch + '/' + totalBatches + ' 批 (' + start + '-' + (end-1) + ')');
						
						$.ajax({
							url: 'ajax.php?act=batchadduser',
							type: 'POST',
							dataType: 'json',
							data: {
								batch_data: batchData,
								start_index: start,
								batch_size: batchSize
							},
							success: function(res) {
								if(res.code == 1) {
									// 更新进度条
									var percent = Math.round((end / count) * 100);
									element.progress('batchProgress', percent + '%');
									
									// 更新预览表格
									if(res.accounts && res.accounts.length > 0) {
										$('#batchPreview').show();
										for(var i = 0; i < res.accounts.length; i++) {
											var account = res.accounts[i];
											var rowClass = account.status ? '' : 'layui-bg-gray';
											var statusText = account.status ? '<span class="layui-badge layui-bg-green">成功</span>' : '<span class="layui-badge layui-bg-gray">失败</span>';
											var row = '<tr class="' + rowClass + '">' +
												'<td>' + account.user + '</td>' +
												'<td>' + account.pwd + '</td>' +
												'<td>' + account.expire + '</td>' +
												'<td>' + statusText + '</td>' +
											'</tr>';
											$('#batchPreviewBody').append(row);
											
											// 添加到结果数组
											if(account.status) {
												createdAccounts.push(account);
											} else {
												failedAccounts.push(account);
											}
										}
									}
									
									// 检查是否还有账号需要创建
									if(end < count) {
										// 继续处理下一批
										setTimeout(function() {
											processBatch(end);
										}, 500);
									} else {
										// 全部处理完成
										$('#batchStatus').html('创建完成，共 ' + createdAccounts.length + ' 个成功，' + failedAccounts.length + ' 个失败');
										
										// 如果需要导出
										if(batchData.export && createdAccounts.length > 0) {
											exportToCSV(createdAccounts);
										}
										
										// 添加关闭按钮
										$('#batchDetail').html('<button class="layui-btn layui-btn-primary layui-btn-sm" id="batchCloseBtn">关闭</button>');
										$('#batchCloseBtn').on('click', function() {
											layer.close(progressIndex);
											reload("server_list");
										});
									}
								} else {
									layer.close(progressIndex);
									layer.alert(res.msg || '批量创建失败', {icon: 2});
								}
							},
							error: function() {
								layer.close(progressIndex);
								layer.alert('批量创建请求失败，请重试', {icon: 2});
							}
						});
					}
					
					// 导出CSV函数
					function exportToCSV(accounts) {
						var csvContent = "用户名,密码,到期时间\n";
						for(var i = 0; i < accounts.length; i++) {
							csvContent += accounts[i].user + "," + accounts[i].pwd + "," + accounts[i].expire + "\n";
						}
						
						var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
						var link = document.createElement("a");
						var url = URL.createObjectURL(blob);
						link.setAttribute("href", url);
						link.setAttribute("download", "账号_" + new Date().getTime() + ".csv");
						link.style.visibility = 'hidden';
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);
					}
					
					// 开始第一批处理
					processBatch(0);
					
					return false;
				});
			}
		});

		function reload(id) {
			layui.use(["jquery", "table"], function() {
				var $ = layui.$,
					table = layui.table;
				table.reload(id, {
					page: {
						curr: 1
					},
					where: where()
				});
			});
		}
	
		
	</script>
</html>
