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
		layui.use(["jquery", "form", "element","util"], function() {
			var $ = layui.$,
				form = layui.form,
				element = layui.element,
                layedit = layui.layedit,
				util = layui.util;
				
			form.on("submit(submit)", function(data) {
				if (data.field.wzgg) {
					data.field['wzgg'] = data.field.wzgg.replace(/< >/g, " ")
						.replace(/<\/ >/g, " ")
						.replace(/document/g, " ")
						.replace(/'/g, '"')
						.replace(/\n|\r/g, "");
				}

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
