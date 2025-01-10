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
    <title>新增套餐</title>
    <meta name="renderer" content="webkit" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <?php include("foot.php"); ?>
    <style>
    .main-body {
        padding: 20px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }
    .layui-form-label {
        width: 100px;
        color: #666;
    }
    .layui-input-block {
        margin-left: 130px;
    }
    .layui-form-item {
        margin-bottom: 25px;
        position: relative;
    }
    .layui-input {
        transition: all .3s;
        -webkit-transition: all .3s;
        height: 38px;
        line-height: 1.3;
        border-radius: 4px;
    }
    .layui-input:focus {
        border-color: #5FB878!important;
        box-shadow: 0 0 0 3px rgba(95,184,120,.12);
    }
    .layui-input:hover {
        border-color: #009688;
    }
    .btn-group {
        text-align: center;
        margin-top: 40px;
    }
    .btn-group .layui-btn {
        width: 120px;
        margin: 0 10px;
        height: 38px;
        line-height: 38px;
        border-radius: 4px;
    }
    .layui-form-item .layui-input-block .unit {
        position: absolute;
        right: 10px;
        top: 0;
        line-height: 38px;
        color: #999;
        font-size: 14px;
    }
    .layui-form-required:before {
        color: #ff4d4f;
    }
    .layui-form-label {
        font-size: 14px;
    }
    .layui-input::placeholder {
        color: #bbb;
    }
    .layui-form-mid {
        color: #999;
    }
    .layui-form-select dl dd.layui-this {
        background-color: #5FB878;
    }
    </style>
</head>
<body>
    <div class="main-body">
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">所属应用</label>
                <div class="layui-input-block">
                    <select name="appcode" lay-verify="required" lay-filter="appcode">
                        <option value="">请选择应用</option>
                        <?php
                        $apps = $DB->select("SELECT appcode,appname FROM application WHERE username='".$subconf['username']."'");
                        foreach($apps as $app){
                            echo '<option value="'.$app['appcode'].'">'.$app['appname'].'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">套餐名称</label>
                <div class="layui-input-block">
                    <input type="text" name="package_name" required lay-verify="required" placeholder="请输入套餐名称" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">天数</label>
                <div class="layui-input-block">
                    <input type="number" name="days" required lay-verify="required|number|days" placeholder="请输入天数" autocomplete="off" class="layui-input">
                    <span class="unit">天</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">价格</label>
                <div class="layui-input-block">
                    <input type="number" name="price" required lay-verify="required|number|price" placeholder="请输入价格" autocomplete="off" class="layui-input">
                    <span class="unit">元</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="radio" name="status" value="1" title="启用" checked>
                    <input type="radio" name="status" value="0" title="禁用">
                </div>
            </div>
            <div class="btn-group">
                <button class="layui-btn" lay-submit lay-filter="formDemo">
                    <i class="layui-icon layui-icon-ok"></i> 立即提交
                </button>
                <button type="button" class="layui-btn layui-btn-primary" onclick="parent.layer.closeAll();">
                    <i class="layui-icon layui-icon-close"></i> 取消
                </button>
            </div>
        </div>
    </div>

    <script>
    layui.use(['form', 'layer'], function() {
        var form = layui.form,
            layer = layui.layer,
            $ = layui.$;

        // 自定义验证规则
        form.verify({
            days: function(value) {
                if(value <= 0) {
                    return '天数必须大于0';
                }
                if(value > 3650) {
                    return '天数不能超过3650天';
                }
            },
            price: function(value) {
                if(value <= 0) {
                    return '价格必须大于0';
                }
                if(value > 1000000) {
                    return '价格不能超过1000000';
                }
            }
        });

        form.on('submit(formDemo)', function(data) {
            var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
            data.field.status = data.field.status === undefined ? 1 : parseInt(data.field.status);
            $.ajax({
                url: 'ajax.php?act=addpackage',
                type: 'POST',
                dataType: 'json',
                data: data.field,
                success: function(res) {
                    layer.close(loadIndex);
                    if(res.code == 1) {
                        layer.msg(res.msg, {
                            icon: 1,
                            time: 1000
                        }, function() {
                            parent.layer.closeAll();
                            parent.layui.table.reload('packages');
                        });
                    } else {
                        layer.msg(res.msg, {
                            icon: 2
                        });
                    }
                },
                error: function() {
                    layer.close(loadIndex);
                    layer.msg('服务器错误', {
                        icon: 2
                    });
                }
            });
            return false;
        });

        // 输入框获得焦点时移除单位
        $('.layui-input').focus(function() {
            $(this).parent().find('.unit').hide();
        }).blur(function() {
            $(this).parent().find('.unit').show();
        });

        // 初始化表单
        form.render();
    });
    </script>
</body>
</html> 