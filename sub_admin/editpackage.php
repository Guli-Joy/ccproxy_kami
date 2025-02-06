<?php
include("../includes/common.php");

if (!($islogin == 1)) {
    exit('<script language=\'javascript\'>alert("您还没有登录，请先登录！");window.location.href=\'login.php\';</script>');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = $DB->selectRow("SELECT p.*,a.appname FROM packages p LEFT JOIN application a ON p.appcode=a.appcode WHERE p.id='".intval($id)."' LIMIT 1");
if (!$row) exit("<script language='javascript'>alert('套餐不存在！');window.location.href='packages.php';</script>");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>编辑套餐</title>
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
    </style>
</head>
<body>
    <div class="main-body">
        <div class="layui-form">
            <input type="hidden" name="id" value="<?php echo $row['id']?>">
            <input type="hidden" name="appcode" value="<?php echo $row['appcode']?>">
            <div class="layui-form-item">
                <label class="layui-form-label">所属应用</label>
                <div class="layui-form-mid"><?php echo htmlspecialchars($row['appname'])?></div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">套餐名称</label>
                <div class="layui-input-block">
                    <input type="text" name="package_name" required lay-verify="required" placeholder="请输入套餐名称" autocomplete="off" class="layui-input" value="<?php echo htmlspecialchars($row['package_name'])?>">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">时长</label>
                <div class="layui-input-block" style="display:flex;gap:10px">
                    <?php
                    // 将天数转换为最适合的单位
                    $days = floatval($row['days']);
                    $totalMinutes = round($days * 24 * 60);
                    
                    if($totalMinutes >= 24 * 60) { // 1天或以上
                        $duration = floor($days);
                        $defaultUnit = 'day';
                    } elseif($totalMinutes >= 60) { // 1小时或以上
                        $duration = floor($totalMinutes / 60);
                        $defaultUnit = 'hour';
                    } else { // 不足1小时
                        $duration = $totalMinutes;
                        $defaultUnit = 'minute';
                    }
                    ?>
                    <input type="number" name="duration" required lay-verify="required|number|duration" 
                           placeholder="请输入时长" autocomplete="off" class="layui-input" 
                           style="width:calc(100% - 120px)" value="<?php echo $duration?>">
                    <select name="duration_unit" lay-verify="required" style="width:110px">
                        <option value="minute" <?php echo $defaultUnit=='minute'?'selected':''?>>分钟</option>
                        <option value="hour" <?php echo $defaultUnit=='hour'?'selected':''?>>小时</option>
                        <option value="day" <?php echo $defaultUnit=='day'?'selected':''?>>天</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label layui-form-required">价格</label>
                <div class="layui-input-block">
                    <input type="number" name="price" required lay-verify="required|number|price" placeholder="请输入价格" autocomplete="off" class="layui-input" value="<?php echo $row['price']?>">
                    <span class="unit">元</span>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="radio" name="status" value="1" title="启用" <?php echo $row['status']==1?'checked':''?>>
                    <input type="radio" name="status" value="0" title="禁用" <?php echo $row['status']==0?'checked':''?>>
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
            duration: function(value) {
                if(value <= 0) {
                    return '时长必须大于0';
                }
                var unit = $('select[name="duration_unit"]').val();
                var maxValue;
                switch(unit) {
                    case 'minute':
                        maxValue = 525600; // 365天的分钟数
                        break;
                    case 'hour':
                        maxValue = 8760; // 365天的小时数
                        break;
                    case 'day':
                        maxValue = 365; // 最大天数
                        break;
                }
                if(value > maxValue) {
                    return '时长不能超过365天';
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
            
            // 转换时长为天数(精确到6位小数)
            var duration = parseFloat(data.field.duration);
            var unit = data.field.duration_unit;
            switch(unit) {
                case 'minute':
                    data.field.days = (duration / (24 * 60)).toFixed(6); // 转换分钟到天
                    break;
                case 'hour':
                    data.field.days = (duration / 24).toFixed(6); // 转换小时到天
                    break;
                case 'day':
                    data.field.days = duration.toFixed(6);
                    break;
            }

            $.ajax({
                url: 'ajax.php?act=editpackage',
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
    });
    </script>
</body>
</html> 