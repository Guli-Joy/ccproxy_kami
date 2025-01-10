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
    <title>订单管理</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="../assets/layui/css/layui.css" media="all">
    
</head>
<body>
    <div class="layui-fluid">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card"><br><br>
                    <div class="layui-card-header">订单管理</div>
                    <div class="layui-card-body">
                        <div class="layui-btn-group">
                            <button class="layui-btn layui-btn-sm layui-btn-danger" id="delSelected">批量删除</button>
                        </div>
                        <table class="layui-hide" id="order-table" lay-filter="order-table"></table>
                        
                        <script type="text/html" id="statusTpl">
                            {{# if(d.status == 0){ }}
                            <span class="layui-badge layui-bg-orange">未支付</span>
                            {{# } else if(d.status == 1){ }}
                            <span class="layui-badge layui-bg-green">已支付</span>
                            {{# } }}
                        </script>
                        
                        <script type="text/html" id="payTypeTpl">
                            {{# if(d.pay_type == 'alipay'){ }}
                            <span class="layui-badge layui-bg-blue">支付宝</span>
                            {{# } else if(d.pay_type == 'wxpay'){ }}
                            <span class="layui-badge layui-bg-green">微信支付</span>
                            {{# } else if(d.pay_type == 'qqpay'){ }}
                            <span class="layui-badge layui-bg-cyan">QQ钱包</span>
                            {{# } }}
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/layui/layui.js"></script>
    <script>
    layui.use(['table', 'layer', 'form', 'jquery'], function(){
        var table = layui.table
        ,layer = layui.layer
        ,form = layui.form
        ,$ = layui.jquery;
        
        table.render({
            elem: '#order-table'
            ,url: 'ajax.php?act=getOrders'
            ,page: true
            ,limit: 10
            ,limits: [10, 20, 50, 100]
            ,toolbar: true
            ,defaultToolbar: ['filter', 'exports', 'print']
            ,cols: [[
                {type: 'checkbox', fixed: 'left'}
                ,{field: 'order_no', title: '订单号', width: 180, sort: true}
                ,{field: 'appname', title: '应用名称', width: 120}
                ,{field: 'account', title: '账号', width: 120}
                ,{field: 'package_name', title: '套餐名称', width: 120}
                ,{field: 'amount', title: '金额', width: 100, sort: true}
                ,{field: 'pay_type', title: '支付方式', width: 100, templet: '#payTypeTpl'}
                ,{field: 'status', title: '状态', width: 100, templet: '#statusTpl', sort: true}
                ,{field: 'mode', title: '模式', width: 100, templet: function(d){
                    return d.mode === 'register' ? '新用户注册' : '账号续费';
                }}
                ,{field: 'create_time', title: '创建时间', width: 160, sort: true}
            ]]
            ,parseData: function(res) {
                return {
                    "code": res.code === 1 ? 0 : res.code, // layui要求成功的状态码为 0
                    "msg": res.msg,
                    "count": res.count,
                    "data": res.data
                };
            }
            ,response: {
                statusName: 'code'
                ,statusCode: 0
                ,msgName: 'msg'
                ,countName: 'count'
                ,dataName: 'data'
            }
            ,text: {
                none: '暂无订单数据'
            }
        });

        // 批量删除
        $('#delSelected').on('click', function(){
            var checkStatus = table.checkStatus('order-table')
            ,data = checkStatus.data;
            if(data.length === 0){
                layer.msg('请选择要删除的订单', {icon: 2});
                return;
            }
            
            layer.confirm('确定要删除选中的'+data.length+'条订单吗？', function(index){
                var order_nos = [];
                data.forEach(function(item){
                    order_nos.push(item.order_no);
                });
                
                $.ajax({
                    url: 'ajax.php?act=delorders',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        order_nos: order_nos
                    },
                    success: function(res){
                        if(res.code == 1){
                            layer.msg(res.msg, {icon: 1});
                            table.reload('order-table');
                        }else{
                            layer.msg(res.msg || '删除失败', {icon: 2});
                        }
                    },
                    error: function(){
                        layer.msg('服务器错误', {icon: 2});
                    }
                });
                layer.close(index);
            });
        });
    });
    </script>
</body>
</html>