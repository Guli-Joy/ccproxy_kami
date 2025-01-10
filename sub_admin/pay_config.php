<?php
include("../includes/common.php");
if (!($islogin == 1)) {
    exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}

// 处理表单提交
if (isset($_POST['submit']) || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
    if (isset($_POST['action']) && $_POST['action'] === 'reset') {
        // 重置配置
        $sql = "INSERT INTO pay_config (id, merchant_id, merchant_key, api_url, status, alipay_status, wxpay_status, qqpay_status) 
                VALUES (1, '', '', '', 1, 1, 1, 1)
                ON DUPLICATE KEY UPDATE 
                merchant_id='',
                merchant_key='',
                api_url='',
                status=1,
                alipay_status=1,
                wxpay_status=1,
                qqpay_status=1,
                update_time=CURRENT_TIMESTAMP";
        
        if ($DB->exe($sql)) {
            exit(json_encode(['code' => 1, 'msg' => '重置成功']));
        } else {
            exit(json_encode(['code' => 0, 'msg' => '重置失败']));
        }
    }

    // 获取并过滤表单数据
    $merchant_id = $DB->escape(trim($_POST['merchant_id']));
    $merchant_key = $_POST['merchant_key'];
    $api_url = $DB->escape(trim($_POST['api_url']));
    
    // 支付开关状态，未勾选时默认为0
    $status = isset($_POST['status']) ? 1 : 0;
    $alipay_status = isset($_POST['alipay_status']) ? 1 : 0;
    $wxpay_status = isset($_POST['wxpay_status']) ? 1 : 0;
    $qqpay_status = isset($_POST['qqpay_status']) ? 1 : 0;

    // 使用 INSERT ... ON DUPLICATE KEY UPDATE 语法
    $sql = "INSERT INTO pay_config (id, merchant_id, merchant_key, api_url, status, alipay_status, wxpay_status, qqpay_status) 
            VALUES (1, '{$merchant_id}', '{$merchant_key}', '{$api_url}', {$status}, {$alipay_status}, {$wxpay_status}, {$qqpay_status})
            ON DUPLICATE KEY UPDATE 
            merchant_id=VALUES(merchant_id),
            merchant_key=VALUES(merchant_key),
            api_url=VALUES(api_url),
            status=VALUES(status),
            alipay_status=VALUES(alipay_status),
            wxpay_status=VALUES(wxpay_status),
            qqpay_status=VALUES(qqpay_status),
            update_time=CURRENT_TIMESTAMP";

    if ($DB->exe($sql)) {
        if (isset($_POST['submit'])) {
            exit('<script language=\'javascript\'>alert("修改支付配置成功！");window.location.href=\'pay_config.php\';</script>');
        } else {
            exit(json_encode(['code' => 1, 'msg' => '保存成功']));
        }
    } else {
        if (isset($_POST['submit'])) {
            exit('<script language=\'javascript\'>alert("修改支付配置失败！");history.go(-1);</script>');
        } else {
            exit(json_encode(['code' => 0, 'msg' => '保存失败']));
        }
    }
}

// 获取现有配置
$row = $DB->selectRow("SELECT * FROM pay_config WHERE id=1 LIMIT 1");
if (!$row) {
    // 如果配置不存在，创建默认配置
    $DB->exe("INSERT INTO pay_config (id,merchant_id,merchant_key,api_url,status,alipay_status,wxpay_status,qqpay_status) 
             VALUES (1,'','','','',1,1,1)");
    $row = array(
        'merchant_id' => '', 
        'merchant_key' => '', 
        'api_url' => '', 
        'status' => 1,
        'alipay_status' => 1,
        'wxpay_status' => 1,
        'qqpay_status' => 1
    );
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>支付配置</title>
    <?php include("foot.php"); ?>
</head>

<body>
    <div class="layui-card layui-form">
        <div class="layui-card-body">
            <div class="layui-tab">
                <ul class="layui-tab-title">
                    <li class="layui-this">支付接口配置</li>
                </ul>
                <div class="layui-tab-content">
                    <div class="layui-tab-item layui-show">
                        <div class="layui-form-item">
                            <label class="layui-form-label">支付接口地址 <span class="layui-must">*</span></label>
                            <div class="layui-input-block">
                                <input type="url" name="api_url" value="<?php echo htmlspecialchars($row['api_url']); ?>" class="layui-input" placeholder="例如：https://api.example.com/pay" required>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">商户ID <span class="layui-must">*</span></label>
                            <div class="layui-input-block">
                                <input type="text" name="merchant_id" value="<?php echo htmlspecialchars($row['merchant_id']); ?>" class="layui-input" placeholder="请输入商户ID" required>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">商户密钥 <span class="layui-must">*</span></label>
                            <div class="layui-input-block">
                                <div class="password-wrapper">
                                    <input type="password" name="merchant_key" id="merchant_key" value="<?php echo htmlspecialchars($row['merchant_key']); ?>" class="layui-input" placeholder="请输入商户密钥" required>
                                    <i class="layui-icon layui-icon-eye password-toggle" onclick="togglePassword('merchant_key', this)" style="display: <?php echo empty($row['merchant_key']) ? 'none' : 'block'; ?>"></i>
                                </div>
                            </div>
                        </div>

                        

                        <div class="layui-form-item">
                            <label class="layui-form-label">接口状态<span class="layui-must">*</span></label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="status" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo $row['status'] == 1 ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">支付方式<span class="layui-must">*</span></label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="alipay_status" value="1" title="支付宝" lay-skin="primary" <?php echo $row['alipay_status'] == 1 ? 'checked' : ''; ?>>
                                <input type="checkbox" name="wxpay_status" value="1" title="微信支付" lay-skin="primary" <?php echo $row['wxpay_status'] == 1 ? 'checked' : ''; ?>>
                                <input type="checkbox" name="qqpay_status" value="1" title="QQ钱包" lay-skin="primary" <?php echo $row['qqpay_status'] == 1 ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-normal layui-btn-sm" lay-submit lay-filter="submit">保存设置</button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" lay-submit lay-filter="reset">重置</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        layui.use(['form', 'element', 'layer'], function() {
            var $ = layui.$,
                form = layui.form,
                element = layui.element,
                layer = layui.layer;

            // 记录密码显示状态
            var passwordVisible = false;

            // 监听密码输入框变化
            $('#merchant_key').on('input', function() {
                var value = $(this).val();
                var icon = $(this).siblings('.password-toggle');
                if (value) {
                    icon.show();
                } else {
                    icon.hide();
                }
            });

            // 密码显示切换
            window.togglePassword = function(inputId, icon) {
                var input = document.getElementById(inputId);
                passwordVisible = !passwordVisible;
                
                if (passwordVisible) {
                    input.type = 'text';
                    icon.classList.remove('layui-icon-eye');
                    icon.classList.add('layui-icon-eye-invisible');
                } else {
                    input.type = 'password';
                    icon.classList.remove('layui-icon-eye-invisible');
                    icon.classList.add('layui-icon-eye');
                }
            };

            // 表单提交
            form.on('submit(submit)', function(data) {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    dataType: 'json',
                    data: data.field,
                    beforeSend: function() {
                        layer.msg('正在保存', {
                            icon: 16,
                            shade: 0.05,
                            time: false
                        });
                    },
                    success: function(res) {
                        if (res.code == 1) {
                            layer.msg(res.msg, {
                                icon: 1
                            });
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            layer.msg(res.msg, {
                                icon: 2
                            });
                        }
                    },
                    error: function(xhr, textStatus, error) {
                        layer.msg('保存失败: ' + error, {
                            icon: 2
                        });
                    }
                });
                return false;
            });

            // 重置按钮点击事件
            form.on('submit(reset)', function() {
                layer.confirm('确定要重置所有配置吗？', {
                    btn: ['确定', '取消']
                }, function() {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'reset'
                        },
                        beforeSend: function() {
                            layer.msg('正在重置', {
                                icon: 16,
                                shade: 0.05,
                                time: false
                            });
                        },
                        success: function(res) {
                            if (res.code == 1) {
                                layer.msg(res.msg, {
                                    icon: 1
                                });
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                layer.msg(res.msg, {
                                    icon: 2
                                });
                            }
                        },
                        error: function(xhr, textStatus, error) {
                            layer.msg('重置失败: ' + error, {
                                icon: 2
                            });
                        }
                    });
                });
                return false;
            });
        });
    </script>

    <style>
        .layui-form-label {
            width: 120px;
        }

        .layui-input-block {
            margin-left: 150px;
        }

        .layui-must {
            color: red;
            margin-right: 5px;
        }

        .layui-form-mid {
            float: right;
            margin-right: 10px;
        }

        .layui-icon-eye {
            color: #1E9FFF;
            cursor: pointer;
        }

        .layui-tab-title {
            border-bottom: 1px solid #f6f6f6;
        }

        .layui-card {
            margin: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .05);
        }

        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #1E9FFF;
            font-size: 20px;
        }
        .password-toggle:hover {
            color: #0d8adc;
        }
    </style>

</body>

</html>