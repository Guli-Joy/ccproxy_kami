<?php

include("../includes/common.php");
if (!($islogin == 1)) {
	exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}
$id=$_REQUEST["id"];
$serverip=$_REQUEST["ip"];
$user=$_REQUEST["serveruser"];
$pwd=$_REQUEST["password"];
$state=$_REQUEST["state"];
$cport=$_REQUEST["cport"];
$comment=$_REQUEST["comment"];
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8" />
	<title><?php echo $subconf['hostname'] ?>编辑服务器</title>
	<meta name="renderer" content="webkit" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<?php
	include("foot.php");
	?>
	<!-- <link rel="stylesheet" href="../assets/layui/css/layui.css?v=20201111001?v=20201111001" />
		<link rel="stylesheet" type="text/css" href="./css/theme.css?v=20201111001" /> -->
	<style>
		body {
			background-color: #FFFFFF;
			padding-right: 80px;
		}
		#layui-laydate1{
			top: 0!important;
		}
		.usetime{
			display: none;
		}
	</style>
</head>

<body class="layui-form form">
	<div class="layui-form-item">
		<label class="layui-form-label">
			服务器IP
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<input type="text" name="serverip" value="<?=$serverip; ?>" class="layui-input" lay-verify="required" placeholder="请填写服务器IP" />
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			登录账号
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<input type="text" name="user" value="<?=$user; ?>" class="layui-input" lay-verify="required" placeholder="请填写用户名" />
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			登录密码
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
		<input type="text" name="pwd" value="<?=$pwd; ?>" class="layui-input" lay-verify="required" placeholder="请填写密码" />
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			代理端口
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
		<input type="text" name="cport" value="<?=$cport; ?>" class="layui-input" lay-verify="required" placeholder="请填写端口" />
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label" title="服务器状态">
			服务器状态
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-inline">
		<input type="checkbox" name="state" lay-skin="switch" lay-text="开启|关闭" lay-filter="state" <?=$state=="1" ? "checked" :"";?>/>
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			备注
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
		<input type="text" name="comment" value="<?=$comment;?>" class="layui-input" lay-verify="required" placeholder="请填写备注"  />
		<input style="display:none" type="text" name="id" value="<?=$id;?>" class="layui-input" lay-verify="required" placeholder="ID"  />
		</div>
	</div>
	<!-- <div class="layui-form-item">
		<label class="layui-form-label" title="应用">
			应用
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-inline">
			<select lay-verify="required" name="app" lay-filter="state">
				<option value="">请选择一个应用</option>
			</select>
		</div>
	</div> -->

	<!-- <div class="layui-form-item">
			<label class="layui-form-label">
				密码
				<span class="layui-must">*</span>
			</label>
			<div class="layui-input-block">
				<input type="number" name="password" class="layui-input" lay-verify="required" placeholder="纯数字密码"/>
			</div>
		</div>
		<div class="layui-form-item">
			<label class="layui-form-label">
				到期时间
				<span class="layui-must">*</span>
			</label>
			<div class="layui-input-block">
				<input type="text" name="end_date" class="layui-input" lay-verify="required" placeholder="YYYY-mm-dd HH:ii-dd" />
			</div>
		</div> -->
	<div class="layui-form-item">
		<div class="layui-input-block">
			<button class="layui-btn layui-btn-normal layui-btn-sm" lay-submit lay-filter="submit">确定</button>
		</div>
	</div>
</body>
<!-- <script src="https://www.layuicdn.com/layui/layui.js?v=20201111001"></script> -->
<script type="text/javascript" src="../assets/js/xss.js"></script>
<script>
	layui.use(["jquery", "form", "laydate"], function() {
		var $ = layui.$,
			form = layui.form,
			laydate = layui.laydate;
			
		form.render();
		
		$(".layui-input").eq(0).focus();
		
		form.on("submit(submit)", function(data) {
			var formData = data.field;
			formData.state = formData.state ? "1" : "0";
			
			$.ajax({
				url: "ajax.php?act=editserver",
				type: "POST",
				dataType: "json",
				data: {
					data: formData
				},
				beforeSend: function() {
					layer.msg("正在提交", {
						icon: 16,
						shade: 0.05,
						time: false
					});
				},
				success: function(res) {
					layer.closeAll('loading');
					if (res.code == "1") {
						parent.layer.closeAll();
						parent.layer.msg(res.msg, {
							icon: 1
						});
						setTimeout(function(){
							parent.location.reload();
						},100);
					} else {
						layer.msg(res.msg || "未知错误", {
							icon: 5
						});
					}
				},
				error: function(xhr, status, error) {
					layer.closeAll('loading');
					layer.msg("编辑数据失败: " + error, {
						icon: 5
					});
				}
			});
			return false;
		});
		
		form.on('switch(state)', function(data){});
	});
</script>

</html>