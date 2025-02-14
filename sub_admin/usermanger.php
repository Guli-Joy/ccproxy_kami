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
					"id", "code", "price","user", "state","app"
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
					</div>`
				});

				// 分批处理函数
				function processBatch(formData, offset) {
					$.ajax({
						url: 'ajax.php',
						type: 'POST',
						dataType: 'json',
						timeout: 60000, // 增加超时时间到60秒
						data: {
							act: 'compensatetime',
							app: formData.compensate_app,
							expire_filter: formData.compensate_filter,
							value: formData.compensate_value,
							unit: formData.compensate_unit,
							offset: offset,
							batch_size: 20 // 减小批次大小
						},
						success: function(res) {
							if(res.code == 1) {
								// 更新进度条
								var percent = Math.round((res.details.total_processed / res.details.total) * 100);
								element.progress('compensateProgress', percent + '%');
								$('#compensateStatus').html('已处理: ' + res.details.total_processed + '/' + res.details.total);
								
								// 检查是否还有更多数据需要处理
								if(res.details.has_more && res.details.total_processed < res.details.total) {
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
								// 如果是已处理过的错误，直接关闭并显示完成信息
								if(res.msg && res.msg.indexOf('该批次已经处理过') !== -1) {
									layer.close(progressIndex);
									showCompletionMessage(res);
								} else {
									// 其他错误则重试当前批次
									console.error('处理出错，将在3秒后重试:', res.msg);
									setTimeout(function() {
										processBatch(formData, offset);
									}, 3000);
								}
							}
						},
						error: function(xhr, status, error) {
							// 请求失败时重试
							console.error('请求失败，将在3秒后重试:', status, error);
							setTimeout(function() {
								processBatch(formData, offset);
							}, 3000);
						}
					});
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
					resultMsg += '- 失败：' + res.details.failed + '个<br>';
					if(res.details.skipped > 0) {
						resultMsg += '- 跳过：' + res.details.skipped + '个<br>';
					}
					if(res.details.errors && res.details.errors.length > 0) {
						resultMsg += '<br>失败账号：<br>' + res.details.errors.join('<br>');
					}
				}
				
				layer.alert(resultMsg, {
					icon: 1,
					title: '补偿完成',
					btn: ['确定'],
					anim: 1,
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
					content: "newuser.php?v=20201111001&preserve_case=1"
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
