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
    <title><?php echo $subconf['hostname']?>应用配置</title>
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
                    <select name="appcode" lay-filter="appcode">
                        <option value="">请选择应用</option>
                        <?php
                        $apps = $DB->select("SELECT appcode,appname FROM application WHERE username='".$subconf['username']."'");
                        foreach($apps as $app){
                            echo '<option value="'.$app['appcode'].'">'.$app['appname'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <label class="layui-form-label" title="应用名称">
                    应用名称:
                </label>
                <div class="layui-input-inline">
                    <input type="text" name="app_name" class="layui-input" placeholder="请输入应用名称"/>
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
            <table id="app-table" lay-filter="app-table"></table>
        </div>
    </div>

    <!-- 新增/编辑配置弹窗 -->
    <script type="text/html" id="configFormTpl">
        <form class="layui-form" style="padding: 20px;" lay-filter="configForm">
            <input type="hidden" name="id">
            <div class="layui-form-item">
                <label class="layui-form-label">所属应用<span class="layui-must">*</span></label>
                <div class="layui-input-block">
                    <select name="appcode" lay-verify="required|checkApp" lay-filter="appcode" lay-search>
                        <option value="">请选择应用</option>
                        <?php
                        // 获取已配置的应用列表
                        $configured_apps = $DB->select("SELECT appcode FROM package_apps");
                        $configured_appcodes = array_column($configured_apps, 'appcode');
                        
                        // 获取所有应用列表并标记已配置的
                        $apps = $DB->select("SELECT appcode,appname FROM application WHERE username='".$subconf['username']."'");
                        foreach($apps as $app){
                            $isConfigured = in_array($app['appcode'], $configured_appcodes);
                            echo '<option value="'.$app['appcode'].'" data-configured="'.($isConfigured ? '1' : '0').'">'
                                 .$app['appname'].($isConfigured ? ' (已配置)' : '').'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">应用名称<span class="layui-must">*</span></label>
                <div class="layui-input-block">
                    <input type="text" name="app_name" lay-verify="required" placeholder="请输入应用名称" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">服务器地址<span class="layui-must">*</span></label>
                <div class="layui-input-block">
                    <input type="text" name="server_address" lay-verify="required" placeholder="请输入服务器地址(IP或域名)" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">服务器端口<span class="layui-must">*</span></label>
                <div class="layui-input-block">
                    <input type="number" name="server_port" lay-verify="required|number|port" placeholder="请输入服务器端口(1-65535)" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">下载地址</label>
                <div class="layui-input-block">
                    <input type="text" name="download_url" lay-verify="url" placeholder="请输入下载地址(http://或https://开头)" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">特殊说明</label>
                <div class="layui-input-block">
                    <textarea name="special_notes" placeholder="请输入特殊说明" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">排序</label>
                <div class="layui-input-block">
                    <input type="number" name="sort_order" value="0" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="checkbox" name="status" lay-skin="switch" lay-text="启用|禁用" checked>
                </div>
            </div>
            <div class="layui-form-item" style="text-align: center;">
                <button class="layui-btn" lay-submit lay-filter="submitConfig">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </form>
    </script>

    <!-- 表格工具栏 -->
    <script type="text/html" id="toolbarTpl">
        <div class="layui-btn-container">
            <button class="layui-btn layui-btn-sm layui-btn-normal" lay-event="add">
                <i class="layui-icon layui-icon-add-1"></i> 新增配置
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-danger" lay-event="delBatch">
                <i class="layui-icon layui-icon-delete"></i> 批量删除
            </button>
        </div>
    </script>

    <!-- 表格操作列 -->
    <script type="text/html" id="operationTpl">
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
layui.use(['table', 'form', 'layer'], function(){
    var table = layui.table,
        form = layui.form,
        layer = layui.layer,
        $ = layui.$;
    
    // 添加自定义验证规则
    form.verify({
        port: function(value) {
            if(value < 1 || value > 65535) {
                return '端口号必须在1-65535之间';
            }
        },
        url: function(value) {
            if(value && !/^https?:\/\/.+/.test(value)) {
                return '下载地址必须以http://或https://开头';
            }
        },
        checkApp: function(value) {
            var option = $('select[name=appcode] option:selected');
            if(option.data('configured') === 1) {
                return '该应用已配置，请选择其他应用';
            }
        }
    });
    
    // 监听应用选择
    form.on('select(appcode)', function(data){
        var option = $(data.elem).find('option:selected');
        if(option.data('configured') === 1) {
            layer.msg('该应用已配置，请选择其他应用', {icon: 2});
            // 可以选择清空选择
            // $(data.elem).val('');
            // form.render('select');
        }
    });
    
    // 初始化表格
    table.render({
        elem: '#app-table',
        url: 'ajax.php?act=getpackageapps',
        toolbar: '#toolbarTpl',
        defaultToolbar: ['filter', 'exports', 'print'],
        page: true,
        limit: 20,
        limits: [10, 20, 30, 50, 100],
        height: 'full-170',
        cols: [[
            {type: 'checkbox', fixed: 'left'},
            {field: 'id', title: '序号', width: 80, sort: true, align: 'center', fixed: 'left'},
            {field: 'appname', title: '所属应用', width: 180, align: 'center'},
            {field: 'app_name', title: '应用名称', width: 150, align: 'center'},
            {field: 'server_address', title: '服务器地址', width: 180, align: 'center'},
            {field: 'server_port', title: '端口', width: 100, align: 'center'},
            {field: 'download_url', title: '下载地址', width: 200, align: 'center', templet: function(d){
                return d.download_url || '--';  // 直接显示链接，如果为空则显示--
            }},
            {field: 'special_notes', title: '特殊说明', minWidth: 200, align: 'center'},
            {field: 'sort_order', title: '排序', width: 80, sort: true, align: 'center'},
            {field: 'status', title: '状态', width: 110, templet: '#statusTpl', align: 'center', unresize: true},
            {title: '操作', toolbar: '#operationTpl', width: 180, align: 'center', fixed: 'right'}
        ]],
        where: {
            appcode: $('select[name="appcode"]').val(),
            app_name: $('input[name="app_name"]').val()
        }
    });

    // 显示配置表单
    function showConfigForm(title, data) {
        layer.open({
            type: 1,
            title: title,
            area: ['600px', '680px'],
            content: $('#configFormTpl').html(),
            success: function(layero) {
                form.render(null, 'configForm');
                if(data) {
                    // 编辑时设置表单值
                    form.val('configForm', data);
                    // 禁用应用选择
                    layero.find('select[name=appcode]').prop('disabled', true);
                    form.render('select');
                }
            }
        });
    }

    // 工具栏事件
    table.on('toolbar(app-table)', function(obj){
        var checkStatus = table.checkStatus(obj.config.id);
        switch(obj.event){
            case 'add':
                showConfigForm('新增配置');
                break;
            case 'delBatch':
                var data = checkStatus.data;
                if(data.length === 0){
                    layer.msg('请选择要删除的配置', {icon: 2});
                    return;
                }
                layer.confirm('确定要删除选中的 ' + data.length + ' 个配置吗?', {
                    btn: ['确定', '取消'],
                    icon: 3
                }, function(){
                    var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
                    $.ajax({
                        url: 'ajax.php?act=delpackageapps',
                        type: 'POST',
                        data: {
                            ids: data.map(function(item){ return item.id; })
                        },
                        dataType: 'json',
                        success: function(res){
                            layer.close(loadIndex);
                            if(res.code == 1){
                                layer.msg(res.msg, {icon: 1});
                                table.reload('app-table');
                            }else{
                                layer.msg(res.msg || '删除失败', {icon: 2});
                            }
                        },
                        error: function(){
                            layer.close(loadIndex);
                            layer.msg('服务器错误，请稍后重试', {icon: 2});
                        }
                    });
                });
                break;
        }
    });

    // 监听工具条
    table.on('tool(app-table)', function(obj){
        var data = obj.data;
        if(obj.event === 'del'){
            layer.confirm('确定删除此配置吗？', function(index){
                var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
                $.ajax({
                    url: 'ajax.php?act=delpackageapp',
                    type: 'POST',
                    data: {id: data.id},
                    dataType: 'json',
                    success: function(res){
                        layer.close(loadIndex);
                        if(res.code == 1){
                            obj.del();
                            layer.msg(res.msg, {icon: 1});
                        }else{
                            layer.msg(res.msg || '删除失败', {icon: 2});
                        }
                    },
                    error: function(){
                        layer.close(loadIndex);
                        layer.msg('服务器错误，请稍后重试', {icon: 2});
                    }
                });
                layer.close(index);
            });
        }else if(obj.event === 'edit'){
            showConfigForm('编辑配置', data);
        }
    });
    
    // 监听状态开关
    form.on('switch(status)', function(obj){
        $.ajax({
            url: 'ajax.php?act=updatepackageappstatus',
            type: 'POST',
            data: {
                id: this.value,
                status: obj.elem.checked ? 1 : 0
            },
            success: function(res){
                if(res.code != 1){
                    layer.msg(res.msg, {icon: 2});
                    $(obj.elem).prop('checked', !obj.elem.checked);
                    form.render('checkbox');
                }
            }
        });
    });

    // 监听表单提交
    form.on('submit(submitConfig)', function(data){
        var field = data.field;
        field.status = field.status ? 1 : 0;
        
        // 显示加载层
        var loadIndex = layer.load(2, {shade: [0.3, '#fff']});
        
        $.ajax({
            url: 'ajax.php?act=' + (field.id ? 'editpackageapp' : 'addpackageapp'),
            type: 'POST',
            data: field,
            dataType: 'json',
            success: function(res){
                layer.close(loadIndex);
                if(res.code == 1){
                    layer.closeAll('page');
                    layer.msg(res.msg, {
                        icon: 1,
                        time: 1000
                    }, function(){
                        table.reload('app-table');
                    });
                }else{
                    layer.msg(res.msg, {
                        icon: 2,
                        time: 2000
                    });
                }
            },
            error: function(){
                layer.close(loadIndex);
                layer.msg('服务器错误，请稍后重试', {
                    icon: 2,
                    time: 2000
                });
            }
        });
        return false;
    });

    // 搜索
    form.on('submit(search)', function(data){
        table.reload('app-table', {
            page: {curr: 1},
            where: {
                appcode: data.field.appcode,
                app_name: data.field.app_name
            }
        });
        return false;
    });

    // 重置
    $('button[type="reset"]').click(function(){
        $('select[name="appcode"]').val('');
        $('input[name="app_name"]').val('');
        form.render('select');
        table.reload('app-table', {
            page: {curr: 1},
            where: {
                appcode: '',
                app_name: ''
            }
        });
    });
});
</script>
</body>
</html> 