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
					<label class="layui-form-label" title="创建时间">
						日志时间
					</label>
					<div class="layui-input-inline">
						<input type="text" name="logtime" class="layui-input" placeholder="YYYY-MM-DD">
					</div>	
				</div>
			</div>
		</div>
		<!-- 表格 -->
		<div class="layui-card">
			<div class="layui-card-body">
				<table id="log" lay-filter="log"></table>
			</div>
		</div>
	</body>
	<!-- <script src="https://www.layuicdn.com/layui/layui.js?v=20201111001"></script> -->
    <!-- <script src="../assets/layui/layui.js"></script> -->
	<script type="text/html" id="server_listTool">
		<div class="layui-btn-container">
			<button class="layui-btn layui-btn-normal layui-btn-sm" lay-event="search"><i class="layui-icon layui-icon-search"></i><span>搜索</span></button>
			<button class="layui-btn layui-btn-danger layui-btn-sm" lay-event="batchDel"><i class="layui-icon layui-icon-delete"></i><span>批量删除</span></button>
			<button class="layui-btn layui-btn-danger layui-btn-sm" lay-event="clearAll"><i class="layui-icon layui-icon-delete"></i><span>清空日志</span></button>
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
	<span style="width: 20px;height: 20px;" class="layui-badge-dot {{d.expire==1?'green':''}}"></span>
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
					"id", "logtime", "price","user", "state","app"
				];
				var json = {};
				for (var key in data) {
					json[data[key]] = query(data[key]);
					//console.log(query(data[key]))
				}
				return json;
			}
			laydate.render({
				elem: "[name=logtime]",
				//range: true,
				done: function() {
					setTimeout(function() {
						reload("log");
					}, 100);
				}
			});
			form.render("select");
			table.render({
				elem: "#log",
				escape:true,
				height: "full-170",
				url: "ajax.php?act=getlog",
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
						field: "logid",
						title: "ID",
						width: 80,
						sort: true,
						align: "center",
						hide: true
					}, {
						title: "序号",
						width: 80,
						align: "center",
						templet: function(d) {
							return (d.LAY_INDEX + 1);
						}
					}, {
						field: "operation",
						title: "操作",
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}, {
						field: "msg",
						title: "信息",
						//minWidth: 100,
						width: 170,
						align: "center",
						// sort: true
					}, {
						field: "operationdate",
						title: "操作时间",
						//minWidth: 100,
						align: "center",
						width: 170,
						sort: true
						//toolbar: "#stateTool"
						// sort: true
					}, {
						field: "operationer",
						title: "操作人",
						//minWidth: 100,
						width: 170,
						align: "center",
						// toolbar: "#pwddot"
						// sort: true
					}, {
						field: "ip",
						title: "操作IP",
						//minWidth: 100,
						align: "center",
						width: 170,
						// sort: true
					}]
				]
			});

			table.on("toolbar(log)", function(obj) {
				var checkStatus = table.checkStatus(obj.config.id);
				switch (obj.event) {
					case "search":
						reload("log");
						break;
					case "batchDel":
						if(checkStatus.data.length === 0) {
							layer.msg('请选择要删除的日志', {icon: 2});
							return;
						}
						layer.confirm('确定要删除选中的日志吗？', {
							btn: ['确定','取消']
						}, function(){
							var ids = checkStatus.data.map(item => item.logid);
							$.ajax({
								url: 'ajax.php?act=dellog',
								type: 'POST',
								data: {ids: ids},
								dataType: 'json',
								success: function(res) {
									if(res.code === 1) {
										layer.msg(res.msg, {icon: 1});
										reload("log");
									} else {
										layer.msg(res.msg || '删除失败', {icon: 2});
									}
								},
								error: function() {
									layer.msg('网络错误', {icon: 2});
								}
							});
						});
						break;
					case "clearAll":
						layer.confirm('确定要清空所有日志吗？此操作不可恢复！', {
							btn: ['确定','取消'],
							title: '警告',
							icon: 3
						}, function(){
							$.ajax({
								url: 'ajax.php?act=clearlog',
								type: 'POST',
								dataType: 'json',
								success: function(res) {
									if(res.code === 1) {
										layer.msg(res.msg, {icon: 1});
										reload("log");
									} else {
										layer.msg(res.msg || '清空失败', {icon: 2});
									}
								},
								error: function() {
									layer.msg('网络错误', {icon: 2});
								}
							});
						});
						break;
				};
			});
				//选中复选框
				$('body').on("click", ".layui-table-body table.layui-table tbody tr td", function () {
            if ($(this).attr("data-field") === "0") return;
            $(this).siblings().eq(0).find('i').click();
 			});
			//触发行单击事件
			// table.on('row(log)', function(obj){
			// 	$(obj.tr).children().children().children().next().addClass('layui-form-checked')
			// });	
			// table.on("edit(log)", function(obj) {
			// 	// var server = $("[name=server]").val();
			// 	console.log(obj)
			// 	update(obj.data.appcode, obj.data.appname, obj.data.serverip);
			// });
			// table.on("select(serverip)", function(obj) {
			// 	// var server = $("[name=server]").val();
			// 	console.log(obj)
			// 	update(obj.data.appcode, obj.data.appname, obj.data.serverip);
			// });
			form.on("select(server)", function(data) {
				reload("log");
			});
			laydate.render({
				elem: "[name=logtime]",
				//range: true,
				done: function() {
					setTimeout(function() {
						reload("log");
					}, 100);
				}
			});

			form.on("select(state)", function(data) {
				reload("log");
			});


			table.on("tool(log)", function(obj) {
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
