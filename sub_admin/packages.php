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
    <title><?php echo $subconf['hostname']?>套餐管理</title>
    <meta name="renderer" content="webkit" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <?php include("foot.php"); ?>
    <style>
    .layui-form-label {
        width: 100px;
    }
    .layui-input-block {
        margin-left: 130px;
    }
    .layui-card {
        margin-bottom: 15px;
    }
    .layui-table-tool {
        padding-top: 15px;
        padding-bottom: 15px;
    }
    .layui-table-tool .layui-btn-container {
        margin-bottom: 0;
    }
    .layui-btn-group .layui-btn {
        margin-left: 5px!important;
    }
    </style>
</head>
<body>
    <!-- 筛选条件 -->
    <div class="layui-card">
        <div class="layui-card-body layui-form">
            <div class="layui-form-item" style="padding-right: 5vw;padding-top: 15px;">
                <label class="layui-form-label" title="应用">
                    所属应用:
                </label>
                <div class="layui-input-inline" style="width: 200px;">
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
                <label class="layui-form-label" title="套餐名">
                    套餐名称:
                </label>
                <div class="layui-input-inline">
                    <input type="text" name="package_name" class="layui-input" placeholder="请输入套餐名称"/>
                </div>
                <div class="layui-inline">
                    <button class="layui-btn layui-btn-normal" lay-submit lay-filter="search">
                        <i class="layui-icon layui-icon-search"></i> 搜索
                    </button>
                    <button class="layui-btn layui-btn-primary" type="reset">
                        <i class="layui-icon layui-icon-refresh"></i> 重置
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- 表格 -->
    <div class="layui-card">
        <div class="layui-card-body">
            <table id="packages" lay-filter="packages"></table>
        </div>
    </div>
</body>

<script type="text/html" id="packagesTool">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-sm layui-btn-normal" lay-event="New">
            <i class="layui-icon layui-icon-add-1"></i> 新增套餐
        </button>
        <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="Del">
            <i class="layui-icon layui-icon-delete"></i> 批量删除
        </button>
    </div>
</script>

<!-- 表格按钮 -->
<script type="text/html" id="btnTool">
    <div class="layui-btn-group">
        <button class="layui-btn layui-btn-sm layui-btn-normal" lay-event="edit">
            <i class="layui-icon layui-icon-edit"></i> 编辑
        </button>
        <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="del">
            <i class="layui-icon layui-icon-delete"></i> 删除
        </button>
    </div>
</script>

<!-- 状态开关 -->
<script type="text/html" id="statusTpl">
    <input type="checkbox" name="status" value="{{d.id}}" lay-skin="switch" lay-text="启用|禁用" lay-filter="status" {{ d.status == 1 ? 'checked' : '' }}>
</script>

<script>
layui.use(["jquery", "table", "form", "element"], function() {
    var $ = layui.$,
        table = layui.table,
        form = layui.form,
        element = layui.element;

    window.where = function() {
        var data = ["package_name", "appcode"];
        var json = {};
        for (var key in data) {
            json[data[key]] = query(data[key]);
        }
        return json;
    }

    table.render({
        elem: "#packages",
        escape: true,
        height: "full-170",
        url: "ajax.php?act=packagetable",
        page: true,
        limit: 20,
        limits: [10, 20, 30, 50, 100],
        title: "套餐管理",
        toolbar: "#packagesTool",
        defaultToolbar: ['filter', 'exports', 'print'],
        where: {
            appcode: $('select[name="appcode"]').val(),
            package_name: $('input[name="package_name"]').val()
        },
        cols: [
            [{
                type: "checkbox",
                fixed: "left"
            }, {
                field: "id",
                title: "序号",
                width: 80,
                sort: true,
                align: "center",
                fixed: "left"
            },
            {
                field: "appname",
                title: "所属应用",
                width: 180,
                align: "center"
            },
            {
                field: "package_name",
                title: "套餐名称",
                minWidth: 180,
                align: "center",
                edit: 'text'
            },
            {
                field: "days",
                title: "时长",
                width: 120,
                align: "center",
                templet: function(d) {
                    var days = parseFloat(d.days);
                    var totalMinutes = Math.round(days * 24 * 60); // 转换为分钟并四舍五入
                    
                    if(totalMinutes < 1) {
                        return "小于1分钟";
                    }
                    
                    var result = [];
                    
                    // 计算天数
                    var daysPart = Math.floor(totalMinutes / (24 * 60));
                    if(daysPart > 0) {
                        result.push(daysPart + '天');
                        totalMinutes %= (24 * 60);
                    }
                    
                    // 计算小时
                    var hoursPart = Math.floor(totalMinutes / 60);
                    if(hoursPart > 0) {
                        result.push(hoursPart + '小时');
                        totalMinutes %= 60;
                    }
                    
                    // 剩余分钟
                    if(totalMinutes > 0) {
                        result.push(totalMinutes + '分钟');
                    }
                    
                    return result.join('');
                }
            },
            {
                field: "price",
                title: "价格",
                width: 120,
                align: "center",
                edit: 'text',
                templet: function(d) {
                    return '￥' + d.price;
                }
            },
            {
                field: "status",
                title: "状态",
                width: 110,
                align: "center",
                templet: function(d) {
                    return '<input type="checkbox" name="status" value="'+d.id+'" lay-skin="switch" lay-text="启用|禁用" lay-filter="status" '+(d.status == 1 ? 'checked' : '')+'>';
                },
                unresize: true
            },
            {
                title: "操作",
                toolbar: "#btnTool",
                width: 180,
                align: "center",
                fixed: "right"
            }]
        ]
    });

    // 搜索
    form.on('submit(search)', function(data) {
        table.reload('packages', {
            page: {
                curr: 1
            },
            where: {
                appcode: data.field.appcode,
                package_name: data.field.package_name
            }
        });
        return false;
    });

    // 重置
    $('button[type="reset"]').click(function() {
        $('select[name="appcode"]').val('');
        $('input[name="package_name"]').val('');
        form.render('select');
        table.reload('packages', {
            page: {
                curr: 1
            },
            where: {
                appcode: '',
                package_name: ''
            }
        });
    });

    // 状态切换
    form.on('switch(status)', function(obj){
        var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
        $.ajax({
            url: 'ajax.php?act=updatestatus',
            type: 'POST',
            dataType: 'json',
            data: {
                id: this.value,
                status: obj.elem.checked ? 1 : 0
            },
            success: function(res) {
                layer.close(loadIndex);
                if(res.code == 1) {
                    layer.msg(res.msg, {
                        icon: 1,
                        time: 1000
                    });
                } else {
                    layer.msg(res.msg, {
                        icon: 2
                    });
                    obj.elem.checked = !obj.elem.checked;
                    form.render('checkbox');
                }
            },
            error: function() {
                layer.close(loadIndex);
                layer.msg('服务器错误', {
                    icon: 2
                });
                obj.elem.checked = !obj.elem.checked;
                form.render('checkbox');
            }
        });
    });

    table.on("toolbar(packages)", function(obj) {
        var checkStatus = table.checkStatus(obj.config.id);
        switch (obj.event) {
            case "New":
                layer.open({
                    type: 2,
                    title: "新增套餐",
                    area: ['500px', '400px'],
                    shadeClose: false,
                    maxmin: true,
                    content: 'newpackage.php'
                });
                break;
            case "Del":
                var data = checkStatus.data;
                if(data.length === 0) {
                    layer.msg('请选择要删除的套餐', {icon: 2});
                    return;
                }
                layer.confirm('确定要删除选中的 ' + data.length + ' 个套餐吗?', {
                    btn: ['确定', '取消'],
                    icon: 3
                }, function() {
                    var ids = [];
                    for(var i = 0; i < data.length; i++) {
                        ids.push(data[i].id);
                    }
                    var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
                    $.ajax({
                        url: 'ajax.php?act=delpackages',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            ids: ids
                        },
                        success: function(res) {
                            layer.close(loadIndex);
                            if(res.code == 1) {
                                layer.msg(res.msg, {
                                    icon: 1,
                                    time: 1000
                                }, function() {
                                    table.reload('packages');
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
                });
                break;
        };
    });

    table.on("tool(packages)", function(obj) {
        var data = obj.data;
        switch (obj.event) {
            case "edit":
                layer.open({
                    type: 2,
                    title: "编辑套餐",
                    area: ['500px', '400px'],
                    shadeClose: false,
                    maxmin: true,
                    content: 'editpackage.php?id=' + data.id
                });
                break;
            case "del":
                layer.confirm("确定要删除该套餐吗？", {
                    icon: 3,
                    title: '提示'
                }, function(index) {
                    $.ajax({
                        url: 'ajax.php?act=delpackage',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            'id': data.id
                        },
                        success: function(res) {
                            if(res.code == 1) {
                                layer.msg(res.msg, {icon: 1});
                                obj.del();
                            } else {
                                layer.msg(res.msg, {icon: 2});
                            }
                        },
                        error: function() {
                            layer.msg('服务器错误', {icon: 2});
                        }
                    });
                    layer.close(index);
                });
                break;
        };
    });

    table.on('edit(packages)', function(obj){
        var value = obj.value,
            data = obj.data,
            field = obj.field;
        
        var updateData = {
            id: data.id
        };
        updateData[field] = value;
        
        if(field === 'price') {
            updateData.price = value.replace('￥', '');
        }
        if(field === 'days') {
            updateData.days = value.replace(' 天', '');
        }
        
        $.ajax({
            url: 'ajax.php?act=updatepackage',
            type: 'POST',
            dataType: 'json',
            data: updateData,
            success: function(res) {
                if(res.code == 1) {
                    layer.msg(res.msg, {icon: 1});
                    reload('packages');
                } else {
                    layer.msg(res.msg, {icon: 2});
                    reload('packages');
                }
            },
            error: function() {
                layer.msg('服务器错误', {icon: 2});
                reload('packages');
            }
        });
    });

    function Del(table, checkStatus) {
        var data = checkStatus.data;
        var ids = [];
        for (var i = 0; i < data.length; i++) {
            ids.push(data[i].id);
        }
        layer.confirm("确定要删除选中的套餐吗？", {
            icon: 3,
            title: '提示'
        }, function(index) {
            $.ajax({
                url: "ajax.php?act=delpackages",
                type: "POST",
                dataType: "json",
                data: {
                    ids: ids
                },
                success: function(res) {
                    if(res.code == 1) {
                        layer.msg(res.msg, {icon: 1});
                        reload("packages");
                    } else {
                        layer.msg(res.msg, {icon: 2});
                    }
                },
                error: function() {
                    layer.msg("服务器错误", {icon: 2});
                }
            });
            layer.close(index);
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