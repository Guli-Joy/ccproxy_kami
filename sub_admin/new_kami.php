<?php
include("../includes/common.php");
if (!($islogin == 1)) {
	exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<title>
		<?php echo $subconf['hostname'] ?>新增卡密
	</title>
	<?php
	include("foot.php");
	?>
	<!-- <link rel="stylesheet" href="../assets/layui/css/layui.css?v=20201111001?v=20201111001">
		<link rel="stylesheet" type="text/css" href="./css/theme.css?v=20201111001" /> -->
	<style>
		body {
			background-color: #FFFFFF;
			padding-right: 80px;
		}

		.price {
			color: red;
			font-size: 25px;
		}

		#extparm {
			display: none;
		}

		.kamitype {
			display: none;
		}
	</style>
</head>

<body class="layui-form form" style="text-align:center;">
	<div class="layui-form-item">
		<label class="layui-form-label">
			所属应用
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<select name="app" lay-verify="required" lay-filter="app">
				<option value=""></option>
			</select>
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			前缀
		</label>
		<div class="layui-input-block">
			<input type="text" name="qianzhui" class="layui-input" placeholder="为空则自动生成前缀">
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			卡密长度
		</label>
		<div class="layui-input-block">
			<input type="num" maxlength="128" name="kamilen" class="layui-input" placeholder="卡密长度，默认为16位">
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			卡密时长
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<select name="duration" lay-verify="required" lay-filter="duration">
				<option value=""></option>
				<option value="1">1天</option>
				<option value="7">7天</option>
				<option value="30">30天</option>
				<option value="90">90天</option>
				<option value="180">180天</option>
				<option value="365">365天</option>
				<option value="-1">自定义</option>
			</select>
		</div>
	</div>
	<div class="layui-form-item kamitype">
		<label class="layui-form-label">
			卡密类型
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block" style="text-align:left;">
			<input type="checkbox" name="year" lay-filter="kamitype" title="年">
			<input type="checkbox" name="month" lay-filter="kamitype" title="月">
			<input type="checkbox" name="day" lay-filter="kamitype" title="天" checked>
			<input type="checkbox" name="hour" lay-filter="kamitype" title="时">
			<input type="checkbox" name="minute" lay-filter="kamitype" title="分">
		</div>
	</div>

	<div class="layui-form-item zdydur">
		<label class="layui-form-label">
			自定义时长
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<input type="text" name="kamidur" class="layui-input" placeholder="自定义时长（年/月/天/时/分）">
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			数量
			<span class="layui-must">*</span>
		</label>
		<div class="layui-input-block">
			<input type="num" name="kaminum" maxlength="4" lay-verify="required" class="layui-input" placeholder="生成卡密的数量">
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			拓展参数
		</label>
		<div class="layui-input-block" style="text-align:left;">
			<input type="checkbox" name="ext" title="拓展参数" lay-filter="ext">
		</div>
	</div>
	<div id="extparm">
		<div class="layui-form-item">
			<label class="layui-form-label">
				连接数
			</label>
			<div class="layui-input-block">
				<input type="text" name="connection" class="layui-input" placeholder="为空则为无限制">
			</div>
		</div>
		<div class="layui-form-item">
			<label class="layui-form-label">
				上行带宽
			</label>
			<div class="layui-input-block">
				<input type="text" name="bandwidthup" class="layui-input" placeholder="为空则为无限制 单位MS">
			</div>
		</div>
		<label class="layui-form-label">
			下行带宽
		</label>
		<div class="layui-input-block">
			<input type="text" name="bandwidthdown" class="layui-input" placeholder="为空则为无限制 单位MS">
		</div>
	</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			备注
		</label>
		<div class="layui-input-block">
			<input type="text" name="comment" class="layui-input">
		</div>
	</div>
	<div class="layui-form-item">
		<label class="layui-form-label">
			卡密复制
		</label>
		<div class="layui-input-block" style="text-align:left;">
			<input type="checkbox" name="copy" title="复制卡密" checked>
		</div>
	</div>
	<div class="layui-form-item">
		<div class="layui-input-block">
			<button class="layui-btn layui-btn-normal layui-btn-sm" lay-submit lay-filter="submit">新增卡密</button>
		</div>
	</div>
</body>
<!-- <script src="https://www.layuicdn.com/layui/layui.js?v=20201111001"></script> -->
<script>
	layui.use(["jquery", "form", "laydate"], function() {
		var $ = layui.$,
			form = layui.form,
			laydate = layui.laydate,
			unit = 0;
		form.on('select(duration)', function(data) {
			// console.log(data.value==-1?$(da):)
			if (data.value == -1) {
				// layer.open({
				// 	title: 'Tips',
				// 	content: '自定义默认单位是天，如需要其他时间请换算！<br> <b  style="color: red;">1小时==0.1 <b> <br> <b  style="color: red;">2小时==0.2 <b><br> <b  style="color: red;">大于 1 则 单位为 天<b> <br> <b  style="color: red;">不能带小数如 0.24<b>'
				// });
				$(".zdydur").eq(0).css("display", "block");
				$(".kamitype").eq(0).css("display", "block");
			} else {
				$(".zdydur").eq(0).css("display", "none");
				$(".kamitype").eq(0).css("display", "none");
			}
			// var duration = Number(data.value);
			// var price = duration * unit;
		});

		form.on('checkbox(ext)', function(e) {
			if (e.elem.checked) { //判断当前多选框是选中还是取消选中
				$("#extparm").eq(0).css("display", "block");
			} else {
				$("#extparm").eq(0).css("display", "none");
			}
		});


		/**
		 * 复选框变单选框
		 */
		form.on('checkbox(kamitype)', function(e) {
			var flag = 0;
			$("[lay-filter='kamitype']").each(function(e) {
				if ($(this).prop("checked")) {
					flag++;
				}
			});
			if (flag > 1) {
				$(this).parent().find(".layui-form-checked").each(function(e){
					$(this).removeClass("layui-form-checked");
					$(this).prev().removeAttr("checked")
				});
				$(this).prop("checked",true);
				flag=0;
			}
			form.render("checkbox");
		});


		//复制文本内容
		function copy(txval) {
			let that = this
			let txa = document.createElement('textarea')
			// let txval = 'SN:' + that.sn1 + '\n' + 'MAC:' + that.mac1 + '\n' + 'IMEI:' + that.imei1 + '\n' + 'PORT:' + that
			// 	.port1
			// console.log('copy val:', txval)
			txa.value = txval
			document.body.appendChild(txa)
			txa.select()
			let res = document.execCommand('copy')
			document.body.removeChild(txa)
			console.log('copy success')
		}
		form.on("submit(submit)", function(data) {
			if (data.field.duration == -1) {
				if (data.field.kamidur == "") {
					layer.msg("自定义时长不能为空！", {
						icon: 5
					});
					return;
				}
			}

			// 保存表单数据到外部变量
			var formData = data.field;

			$.ajax({
				url: "ajax.php?act=newkami",
				type: "POST",
				dataType: "json",
				data: formData,
				beforeSend: function() {
					layer.msg("正在提交", {
						icon: 16,
						shade: 0.05,
						time: false
					});
				},
				success: function(res) {
					if (res.code == "1") {
						window.parent.frames.reload("daili_kami");
						parent.layer.closeAll();
						parent.layer.msg("生成成功", {
							icon: 1
						});
					} else if (res.code == "2") {
						window.parent.frames.reload("daili_kami");
						parent.layer.closeAll();
						
						// 构建卡密显示内容
						var kami = "您生成的卡密为：\n\n";
						var num = 0;
						for (var key in res.kami) {
							kami += res.kami[key]["kami"] + "\n"
							num++;
						}

						// 获取卡密时长显示
						var duration = "";
						if (formData.duration == -1) {
							if (formData.year == "on") duration = formData.kamidur + "年";
							else if (formData.month == "on") duration = formData.kamidur + "月";
							else if (formData.day == "on") duration = formData.kamidur + "天";
							else if (formData.hour == "on") duration = formData.kamidur + "小时";
							else if (formData.minute == "on") duration = formData.kamidur + "分钟";
						} else {
							duration = formData.duration + "天";
						}
						
						// 创建美观的卡密显示弹窗
						var mainLayer = parent.layer;  // 保存layer引用
						mainLayer.open({
							type: 1,
							title: '卡密生成成功',
							area: ['400px', 'auto'],
							maxHeight: '500px',
							shadeClose: true,
							content: `
								<div style="padding: 20px;">
									<div style="text-align: center; margin-bottom: 15px;">
										<i class="layui-icon layui-icon-ok-circle" style="font-size: 50px; color: #52c41a;"></i>
										<p style="margin-top: 10px; font-size: 16px;">成功生成 ${num} 张卡密</p>
										<p style="margin-top: 5px; color: #666;">有效期：${duration}</p>
									</div>
									<div style="background: #f8f8f8; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
										<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">${kami}</pre>
									</div>
								</div>
							`,
							success: function(layero, index) {
								// 自动复制
								try {
									var tempTextarea = document.createElement('textarea');
									tempTextarea.value = kami;
									document.body.appendChild(tempTextarea);
									tempTextarea.select();
									if (document.execCommand('copy')) {
										mainLayer.msg('卡密已自动复制到剪贴板', {
											icon: 1,
											time: 1500
										});
									}
									document.body.removeChild(tempTextarea);
								} catch (err) {
									console.error('自动复制失败:', err);
								}
							}
						});
					} else {
						layer.msg(res.msg, {
							icon: 5
						});
					}
				},
				error: function(res) {
					layer.msg("未知错误", {
						icon: 5
					});
				}
			});
			return false;
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
					layer.msg("获取服务器失败", {
						icon: 5
					});
				}
			});
		}
		select();
		// function initapp() {
		// 	$.ajax({
		// 		url: "ajax.php?act=getapp",
		// 		type: "POST",
		// 		dataType: "json",
		// 		success: function(data) {
		// 			if (data.data.unit != false) {
		// 				unit = data.data.unit;
		// 			}
		// 		},
		// 		error: function(data) {
		// 			console.log(data);
		// 			layer.msg(data.responseText, {
		// 				icon: 5
		// 			});
		// 		}
		// 	});
		// }
		// init();
	});
</script>
<!-- 用户信息新增数据页面文件 -->

</html>