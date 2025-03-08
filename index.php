<?php
// 开启会话
session_start();

// 加载公共组件（包含了所有安全检查）
require_once "./includes/common.php";

try {
    // 生成新的支付令牌（如果不存在）
    if (empty($_SESSION['payment_token'])) {
        $_SESSION['payment_token'] = bin2hex(random_bytes(32));
    }
    
    // 设置页面编码
    header('Content-Type: text/html; charset=UTF-8');
    
} catch (Exception $e) {
    // 记录错误并显示安全的错误信息
    error_log("Payment token generation error: " . $e->getMessage());
    die('系统错误，请稍后再试');
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?php echo $subconf['hostname']; ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <link rel="stylesheet" href="./assets/layui/css/layui.css" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="./assets/Message/css/message.css" />
    <link rel="stylesheet" type="text/css" href="./assets/layui/css/theme.css" />
    <link rel="stylesheet" type="text/css" href="./assets/css/main/style_PC.css" media="screen and (min-width: 960px)" />
    <!-- <link rel="stylesheet" type="text/css" href="./assets/css/style_Phone.css" media="screen and (min-width: 720px)" /> -->
    <script src="./assets/Message/js/message.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="./assets/layui/layui.js"></script>
    <script src="./assets//js/lib/jquery-3.5.1.min.js"></script>
    <script src="./assets/js/lib/jquery.cookie.min.js"></script>
    <script src="./assets/js/md5.min.js"></script>
    <script src="./assets/js/sweetalert.min.js"></script>
    <style type="text/css">
        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            width: 100%;
        }

        body {
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            transition: background-image 1s ease-in-out;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: inherit;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            z-index: -1;
        }

        .time {
            width: 80%;
            margin: 0 auto;
            text-align: center;
        }

        .img img {
            border-radius: 10px;
            background-color: #fff;
        }

        .layui-form-selectup dl {
            top: auto;
            bottom: auto;
        }

        .layui-edge {
            right: 70px !important;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-expired {
            color: #dc3545;
            font-weight: bold;
        }

        .query-result {
            padding: 15px;
            background: linear-gradient(145deg, #3fcfbb, #33cabb);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            color: #fff;
            margin: 15px auto;
            width: calc(100% - 30px);
            max-width: calc(100% - 30px);
        }

        .result-header {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .result-header i {
            font-size: 18px;
        }

        .time-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .time-value {
            font-size: 15px;
            font-weight: bold;
        }

        .result-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .result-status i {
            font-size: 16px;
        }

        .status-active {
            color: #fff;
            font-weight: 500;
        }

        .status-expired {
            color: #ff6b6b;
            font-weight: 500;
        }

        .reg-success {
            background: linear-gradient(145deg, #3fcfbb, #33cabb);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .success-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .success-icon i {
            color: #fff;
        }

        .reg-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 15px;
            line-height: 1.6;
        }

        .info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.05);
            padding-left: 8px;
            padding-right: 8px;
            margin: 0 -8px;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0.9;
            font-weight: 500;
        }

        .info-label i {
            font-size: 16px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .info-item:hover .info-label i {
            opacity: 1;
            transform: scale(1.1);
        }

        .info-value {
            font-weight: 500;
            word-break: break-all;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .info-value:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(5px);
        }

        .status-active {
            color: #fff;
            font-weight: 500;
            background: rgba(40, 167, 69, 0.3);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .status-expired {
            color: #fff;
            font-weight: 500;
            background: rgba(255, 107, 107, 0.3);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .success-tips {
            font-size: 14px;
            opacity: 0.8;
            font-style: italic;
        }

        /* 移动端适配样式 */
        @media screen and (max-width: 768px) {
            .query-result {
                padding: 12px;
                margin: 10px auto;
                width: calc(100% - 24px);
                max-width: calc(100% - 24px);
            }

            .result-header {
                margin-bottom: 8px;
                gap: 6px;
            }

            .result-header i {
                font-size: 16px;
            }

            .time-value {
                font-size: 14px;
            }

            .info-item {
                padding: 6px 0;
            }

            .info-label {
                font-size: 13px;
            }

            .info-value {
                font-size: 13px;
            }

            .info-label i {
                font-size: 14px;
            }

            .kami-info {
                padding: 0 35px !important;
            }
        }

        /* 电脑端样式 */
        @media screen and (min-width: 769px) {
            .kami-info {
                padding: 0 70px !important;
            }
        }

        /* 超小屏幕适配 */
        @media screen and (max-width: 320px) {
            .query-result {
                padding: 10px;
                margin: 8px auto;
            }

            .result-header i {
                font-size: 14px;
            }

            .time-value {
                font-size: 13px;
            }

            .info-item {
                padding: 5px 0;
            }

            .info-label, .info-value {
                font-size: 12px;
            }

            .info-label i {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="layui-container">
        <!-- logo部分 -->
        <div class="layui-logo">
            <div class="layui-row">
                <div class="layui-card layui-col-xs12">
                    <div class="wz-title">
                        <h1><?php echo $subconf['hostname']; ?></h1>
                    </div>
                    <div class="img">
                        <!-- <img src="<?php echo $subconf['img']; ?>" alt="logo"> -->
                        <img src="/assets/img/one-by-one.gif" lay-src="<?php echo $subconf['img']; ?>" alt="logo">
                    </div>
                    <div class="layui-col-xs-12 cer">
                        <?php if (!empty($conf['wzgg']) && $conf['ggswitch'] == 1) { ?>
                            <a class="buwz" style="color:white" onclick="showgg()">
                                <div class="layui layui-btn layui-btn-danger">公告</div>
                            </a>
                        <?php } ?>
                        <?php if (!empty($subconf['kf']) && $subconf['kfswitch'] == 1) { ?>
                            <a class="buwz" style="color:white" href="<?php echo $subconf['kf']; ?>">
                                <div class="layui layui-btn layui-btn-normal">客服</div>
                            </a>
                        <?php } ?>
                        <?php if (!empty($subconf['pan']) && $subconf['panswitch'] == 1) { ?>
                            <a class="buwz" style="color:white" href="<?php echo $subconf['pan']; ?>">
                                <div class="layui layui-btn layui-btn-checked">网盘</div>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- 面板部分 -->
        <div class="main">
            <div style="margin: 0;" class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
                <ul class="layui-tab-title">
                    <?php if($subconf['show_online_pay'] == 1) { ?>
                    <li class="layui-this">在线续费/注册</li>
                    <?php } ?>
                    <?php if($subconf['show_kami_pay'] == 1) { ?>
                    <li<?php echo ($subconf['show_online_pay'] != 1 ? ' class="layui-this"' : ''); ?>>卡密充值</li>
                    <?php } ?>
                    <?php if($subconf['show_kami_reg'] == 1) { ?>
                    <li<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 ? ' class="layui-this"' : ''); ?>>卡密注册</li>
                    <?php } ?>
                    <?php if($subconf['show_user_search'] == 1) { ?>
                    <li<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 ? ' class="layui-this"' : ''); ?>>用户查询</li>
                    <?php } ?>
                    <?php if($subconf['show_kami_query'] == 1) { ?>
                    <li<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 && $subconf['show_user_search'] != 1 ? ' class="layui-this"' : ''); ?>>卡密查询</li>
                    <?php } ?>
                    <?php if($subconf['show_change_pwd'] == 1) { ?>
                    <li<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 && $subconf['show_user_search'] != 1 && $subconf['show_kami_query'] != 1 ? ' class="layui-this"' : ''); ?>>修改密码</li>
                    <?php } ?>
                </ul>
                <div class="layui-tab-content" style="height: auto;">
                    <?php if($subconf['show_online_pay'] == 1) { ?>
                    <div class="layui-tab-item layui-show">
                        <form class="layui-form">
                            <div class="layui-form-item" style="margin-bottom: 20px;">
                                <div class="layui-input-block">
                                    <input type="radio" name="mode" value="renew" title="续费模式" lay-filter="mode">
                                    <input type="radio" name="mode" value="register" title="注册模式" lay-filter="mode">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <select id="online-pay-app" name="app" lay-filter="app" lay-verify="required">
                                        <option value="">请选择应用</option>
                                    </select>
                                </div>
                            </div>
                            <div id="renew-mode-inputs" style="display: none; margin-bottom: 20px;">
                                <div class="layui-input-block">
                                    <input type="text" name="account" id="renew-account" class="layui-input inputs" placeholder="请输入账号" lay-verify="required" />
                                </div>
                            </div>
                            <div id="register-mode-inputs" style="display: none; margin-bottom: 20px;">
                                <div class="layui-input-block">
                                    <input type="text" name="account" id="register-account" class="layui-input inputs" placeholder="请输入账号" lay-verify="required" autocomplete="username" />
                                </div>
                                <div class="layui-input-block" style="margin-top: 15px;">
                                    <input type="password" name="password" id="register-password" class="layui-input inputs" placeholder="请输入密码" lay-verify="required" autocomplete="new-password" />
                                </div>
                            </div>
                            <div class="layui-form-item" style="margin-bottom: 20px;">
                                <div class="layui-input-block">
                                    <select id="online-pay-package" name="package" lay-filter="package" lay-verify="required">
                                        <option value="">请选择套餐</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button id="online-pay-submit" type="button" class="layui-btn layui-btn-normal">支付</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php } ?>
                    <?php if($subconf['show_kami_pay'] == 1) { ?>
                    <div class="layui-tab-item<?php echo ($subconf['show_online_pay'] != 1 ? ' layui-show' : ''); ?>">
                        <div class="layui-input-block">
                            <input type="text" name="km" id="pay-user" class="layui-input inputs" placeholder="请输入充值账号" lay-verify="required" />
                        </div>
                        <div class="layui-input-block">
                            <input type="text" name="code" id="pay-code" class="layui-input inputs" placeholder="请输入充值卡密" lay-verify="required" />
                        </div>
                        <div class="layui-input-block layui-btn-xs submit">
                            <button id="pay" type="button" class="layui-btn layui-btn-normal">充值</button>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if($subconf['show_kami_reg'] == 1) { ?>
                    <div class="layui-tab-item<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 ? ' layui-show' : ''); ?>">
                        <div class="layui-input-block">
                            <input type="text" name="km" id="reg-user" class="layui-input inputs" placeholder="请输入账号" lay-verify="required" />
                        </div>
                        <div class="layui-input-block">
                            <input type="text" name="km" id="reg-pwd" class="layui-input inputs" placeholder="请输入密码" lay-verify="required" />
                        </div>
                        <div class="layui-input-block">
                            <input type="text" name="km" id="reg-code" class="layui-input inputs" placeholder="请输入卡密" lay-verify="required" />
                        </div>
                        <div class="layui-input-block layui-btn-xs submit">
                            <button id="registed" type="button" class="layui-btn layui-btn-normal">注册</button>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if($subconf['show_user_search'] == 1) { ?>
                    <div class="layui-tab-item<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 ? ' layui-show' : ''); ?>">
                        <div class="layui-input-block">
                            <div class="layui-form form">
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <select id="sel" name="app" lay-filter="app" lay-verify="required">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-input-block">
                            <input type="text" name="km" id="check-user" class="layui-input inputs" placeholder="请输入查询账号" lay-verify="required" />
                        </div>
                        <div class="time">
                        </div>
                        <div class="layui-input-block layui-btn-xs submit">
                            <button id="check" type="button" class="layui-btn layui-btn-normal">查询</button>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if($subconf['show_kami_query'] == 1) { ?>
                    <div class="layui-tab-item<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 && $subconf['show_user_search'] != 1 ? ' layui-show' : ''); ?>">
                        <div class="layui-input-block">
                            <input type="text" name="km" id="query-kami" class="layui-input inputs" placeholder="请输入要查询的卡密" lay-verify="required" />
                        </div>
                        <div class="kami-info"></div>
                        <div class="layui-input-block layui-btn-xs submit">
                            <button id="query-kami-btn" type="button" class="layui-btn layui-btn-normal">查询</button>
                        </div>
                    </div>
                    <?php } ?>
                    <?php if($subconf['show_change_pwd'] == 1) { ?>
                    <div class="layui-tab-item<?php echo ($subconf['show_online_pay'] != 1 && $subconf['show_kami_pay'] != 1 && $subconf['show_kami_reg'] != 1 && $subconf['show_user_search'] != 1 && $subconf['show_kami_query'] != 1 ? ' layui-show' : ''); ?>">
                        <form class="layui-form">
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <select id="change-pwd-app" name="app" lay-filter="app" lay-verify="required">
                                        <option value="">请选择应用</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-input-block">
                                <input type="text" name="account" id="change-pwd-account" class="layui-input inputs" placeholder="请输入账号" lay-verify="required" autocomplete="username" />
                            </div>
                            <div class="layui-input-block">
                                <input type="password" name="old_password" id="change-pwd-old" class="layui-input inputs" placeholder="请输入原密码" lay-verify="required" autocomplete="current-password" />
                            </div>
                            <div class="layui-input-block">
                                <input type="password" name="new_password" id="change-pwd-new" class="layui-input inputs" placeholder="请输入新密码" lay-verify="required" autocomplete="new-password" />
                            </div>
                            <div class="layui-input-block">
                                <input type="password" name="confirm_password" id="change-pwd-confirm" class="layui-input inputs" placeholder="请确认新密码" lay-verify="required" autocomplete="new-password" />
                            </div>
                            <div class="layui-input-block layui-btn-xs submit">
                                <button id="change-pwd-btn" type="button" class="layui-btn layui-btn-normal">修改密码</button>
                            </div>
                        </form>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- foot底部 -->
        <div class="layui-footer">

        </div>
    </div>

    <script type="text/javascript">
        layui.use(["jquery", "table", "laydate", "form", "upload", "element", "flow"], function() {
            var $ = layui.$,
                table = layui.table,
                laydate = layui.laydate,
                form = layui.form,
                upload = layui.upload,
                flow = layui.flow,
                element = layui.element;
            var height = 0;

            form.on("select(app)", function(obj) {
                // 获取当前select元素的id
                var selectId = $(obj.elem).attr('id');
                
                // 处理高度调整
                if (height != 0) {
                    if ($(".layui-anim-upbit").outerHeight(true) <= $(".layui-show").outerHeight(true)) {
                        $(".layui-show").removeAttr("style");
                    }
                }

                // 只有在线支付的应用选择才需要获取支付方式和套餐
                if (selectId === 'online-pay-app' && obj.value) {
                    // 清除已有的支付方式选择
                    $('#pay-method').closest('.layui-form-item').remove();

                    // 获取支付方式
                    getPayMethods();

                    // 当前套餐选项
                    $("#online-pay-package").empty();
                    $("#online-pay-package").append('<option value="">请选择套餐</option>');

                    // 获取选中应用的套餐列表
                    $.ajax({
                        url: "api/api.php?act=getpackages",
                        type: "POST",
                        dataType: "json",
                        data: {
                            'appcode': obj.value
                        },
                        success: function(res) {
                            if (res.code == "1") {
                                for (var key in res.msg) {
                                    var package = res.msg[key];
                                    
                                    // 转换天数为更友好的显示格式
                                    var days = parseFloat(package.duration);
                                    var totalMinutes = Math.round(days * 24 * 60); // 转换为分钟并四舍五入
                                    var durationDisplay = '';
                                    
                                    if(totalMinutes >= 24 * 60) { // 1天或以上
                                        var remainingDays = Math.floor(totalMinutes / (24 * 60));
                                        totalMinutes %= (24 * 60);
                                        durationDisplay += remainingDays + '天';
                                        
                                        if(totalMinutes >= 60) { // 还有小时
                                            var hours = Math.floor(totalMinutes / 60);
                                            totalMinutes %= 60;
                                            durationDisplay += hours + '小时';
                                        }
                                        
                                        if(totalMinutes > 0) { // 还有分钟
                                            durationDisplay += totalMinutes + '分钟';
                                        }
                                    } else if(totalMinutes >= 60) { // 1小时到24小时
                                        var hours = Math.floor(totalMinutes / 60);
                                        totalMinutes %= 60;
                                        durationDisplay = hours + '小时';
                                        
                                        if(totalMinutes > 0) { // 还有分钟
                                            durationDisplay += totalMinutes + '分钟';
                                        }
                                    } else { // 不足1小时
                                        durationDisplay = totalMinutes + '分钟';
                                    }
                                    
                                    var item = '<option value="' + package.id + '">' +
                                        package.name + ' - ' + package.price + '元/' + durationDisplay +
                                        '</option>';
                                    $("#online-pay-package").append(item);
                                }
                                form.render();
                            } else {
                                layer.msg("获取套餐失败：" + res.msg, {
                                    icon: 5
                                });
                            }
                        },
                        error: function() {
                            layer.msg("获取套餐失败", {
                                icon: 5
                            });
                        }
                    });
                }
            });

            select();
            flow.lazyimg();

            // 初始化表单
            form.render();

            // 监听模式选择
            form.on('radio(mode)', function(data) {
                var mode = data.value;
                if (mode === 'renew') {
                    $('#renew-mode-inputs').show();
                    $('#register-mode-inputs').hide();
                } else if (mode === 'register') {
                    $('#renew-mode-inputs').hide();
                    $('#register-mode-inputs').show();
                }
            });

            // 获取支付方式配置
            function getPayMethods() {
                $.ajax({
                    url: "api/api.php?act=getPayMethods",
                    type: "GET",
                    dataType: "json",
                    success: function(res) {
                        if (res.code == 1) {
                            // 更新支付方式选择
                            var payMethodSelect = '<div class="layui-form-item" style="margin-bottom: 20px;">' +
                                '<div class="layui-input-block">' +
                                '<select id="pay-method" name="pay_method" lay-verify="required">' +
                                '<option value="">请选择支付方式</option>';

                            if (res.data.alipay_status == 1) {
                                payMethodSelect += '<option value="alipay">支付宝</option>';
                            }
                            if (res.data.wxpay_status == 1) {
                                payMethodSelect += '<option value="wxpay">微信支付</option>';
                            }
                            if (res.data.qqpay_status == 1) {
                                payMethodSelect += '<option value="qqpay">QQ钱包</option>';
                            }

                            payMethodSelect += '</select></div></div>';

                            // 在套餐选择后插入支付方式选择
                            $('#online-pay-package').closest('.layui-form-item').after(payMethodSelect);
                            form.render('select'); // 重新渲染表单
                        }
                    },
                    error: function(xhr, status, error) {
                        layer.msg("获取支付配置失败", {
                            icon: 5
                        });
                    }
                });
            }

            // 修改在线支付提交处理
            $('#online-pay-submit').click(function() {
                // 获取所有选择的值
                var app = $('#online-pay-app').val();
                var mode = $('input[name="mode"]:checked').val();
                var account = mode === 'register' ? $('#register-account').val() : $('#renew-account').val();
                var password = mode === 'register' ? $('#register-password').val() : '';
                var package = $('#online-pay-package').val();
                var payMethod = $('#pay-method').val();

                // 基本表单验证...
                if (!app || !mode || !account || !package || !payMethod) {
                    return Qmsg.info("请填写完整信息");
                }
                if (mode === 'register' && !password) {
                    return Qmsg.info("注册模式下请输入密码");
                }

                // 注册模式下验证密码格式
                if (mode === 'register') {
                    // 密码长度检查
                    if (password.length < 8 || password.length > 16) {
                        return Qmsg.warning("密码长度必须在8-16位之间");
                    }

                    // 检查是否同时包含数字和字母
                    var hasNumber = /\d/.test(password);
                    var hasLetter = /[a-zA-Z]/.test(password);

                    if (!hasNumber || !hasLetter) {
                        return Qmsg.warning("密码必须同时包含数字和字母");
                    }

                    // 检查是否只包含合法字符（数字、字母、下划线）
                    if (!/^[\w]+$/.test(password)) {
                        return Qmsg.warning("密码只能包含数字、字母和下划线");
                    }
                }

                // 显示加载层
                var loadIndex = layer.load(1, {
                    shade: [0.1, '#fff']
                });

                // 先验证账号
                $.ajax({
                    url: "api/cpproxy.php?type=query",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'user': account,
                        'appcode': app
                    },
                    success: function(res) {
                        var accountExists = res.msg.includes('到期时间');

                        // 续费模式下账号必须存在
                        if (mode === 'renew' && !accountExists) {
                            layer.close(loadIndex);
                            return layer.msg("该账号不存在,无法续费", {
                                icon: 2
                            });
                        }

                        // 注册模式下账号不能存在
                        if (mode === 'register' && accountExists) {
                            layer.close(loadIndex);
                            return layer.msg("该账号已存在,请更换账号", {
                                icon: 2
                            });
                        }

                        // 验证通过,继续创建订单
                        createOrder();
                    },
                    error: function() {
                        layer.close(loadIndex);
                        layer.msg("验证账号失败,请重试", {
                            icon: 2
                        });
                    }
                });

                // 创建订单的函数
                function createOrder() {
                    // 生成订单号,使用时间戳+随机数确保唯一性
                    var orderNo = new Date().getTime().toString() + Math.random().toString(36).substr(2, 8);
                    
                    $.ajax({
                        url: "api/api.php?act=createOrder", 
                        type: "POST",
                        dataType: "json",
                        data: {
                            app: app,
                            mode: mode,
                            account: account,
                            password: password,
                            package: package,
                            pay_method: payMethod,
                            order_no: orderNo // 使用生成的订单号
                        },
                        success: function(res) {
                            if (res.code == 1) {
                                // 获取支付配置
                                $.ajax({
                                    url: "api/api.php?act=getPayConfig",
                                    type: "GET",
                                    dataType: "json",
                                    success: function(config) {
                                        if (config.code == 1) {
                                            // 构造支付参数
                                            var params = {
                                                'pid': config.data.merchant_id.toString(),
                                                'type': payMethod,
                                                'out_trade_no': orderNo,
                                                'notify_url': window.location.protocol + '//' + window.location.host + '/SDK/notify_url.php',
                                                'return_url': window.location.protocol + '//' + window.location.host + '/SDK/return_url.php',
                                                'name': mode === 'register' ? '新用户注册' : '账号续费',
                                                'money': parseFloat(res.data.amount).toFixed(2),
                                                'sitename': '<?php echo $subconf["hostname"]; ?>',
                                                'token': '<?php echo $_SESSION["payment_token"]; ?>'
                                            };

                                            // 创建支付表单
                                            var form = $('<form action="Sdk/epayapi.php" method="POST"></form>');
                                            
                                            // 添加所有参数到表单
                                            for (var key in params) {
                                                form.append('<input type="hidden" name="' + key + '" value="' + params[key] + '">');
                                            }
                                            
                                            // 添加到页面并提交
                                            $('body').append(form);
                                            form.submit();
                                        } else {
                                            layer.msg("获取支付配置失败", {icon: 5});
                                        }
                                    },
                                    error: function() {
                                        layer.msg("获取支付配置失败", {icon: 5});
                                    }
                                });
                            } else {
                                layer.close(loadIndex);
                                layer.msg(res.msg || "创建订单失败", {icon: 5});
                            }
                        },
                        error: function(xhr, status, error) {
                            layer.close(loadIndex);
                            layer.msg("创建订单失败", {icon: 2});
                        }
                    });
                }
            });

            function select() {
                // 获取应用列表
                $.ajax({
                    url: "api/api.php?act=gethostapp",
                    type: "POST",
                    dataType: "json",
                    timeout: 30000,
                    success: function(data) {
                        if (data.code == "1" && Array.isArray(data.msg)) {
                            // 更新所有应用选择下拉框
                            var appSelects = $("#sel, #online-pay-app, #change-pwd-app");
                            appSelects.each(function() {
                                var $select = $(this);
                                
                                $select.empty(); // 清空现有选项
                                $select.append('<option value="">请选择应用</option>'); // 添加默认选项
                                
                                // 添加应用选项
                                data.msg.forEach(function(app) {
                                    if(app && app.appcode && app.appname) {
                                        var item = '<option value="' + app.appcode + '">' + app.appname + '</option>';
                                        $select.append(item);
                                    }
                                });
                            });

                            // 重新渲染所有表单元素
                            form.render('select');
                        } else {
                            // 如果没有应用，也要清空并添加提示选项
                            var appSelects = $("#sel, #online-pay-app, #change-pwd-app");
                            appSelects.each(function() {
                                $(this).empty().append('<option value="">暂无可用应用</option>');
                            });
                            form.render('select');
                            
                            layer.msg(data.msg || "获取应用失败", {
                                icon: 5
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        error_log("获取应用列表请求失败: " + error);
                        
                        // 请求失败时也要清空并添加提示选项
                        var appSelects = $("#sel, #online-pay-app, #change-pwd-app");
                        appSelects.each(function() {
                            $(this).empty().append('<option value="">获取应用失败</option>');
                        });
                        form.render('select');
                        
                        layer.msg("获取应用失败，请刷新重试", {
                            icon: 5
                        });
                    }
                });
            }

        });
        $(function() {

            $("#pay").click(function() {
                var user = $("#pay-user").val();
                var code = $("#pay-code").val();
                if (user == "") {
                    return Qmsg.info("账号不能为空！")
                }
                if (code == "") {
                    return Qmsg.info("卡密不能为空")
                }
                if (user.length < 3) {
                    return Qmsg.info("账号长度不得小于6位")
                }
                if (code.length < 1) {
                    return Qmsg.info("卡密长度最小为1位")
                }
                if (code.length > 128) {
                    return Qmsg.info("卡密长度最大为128位")
                }
                // $.post("",{})
                $.ajax({
                    url: "api/cpproxy.php?type=update",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'user': user.trim(),
                        'code': code
                        // server: $("[name=server]").val()
                    },
                    timeout: 30000,
                    beforeSend: function() {
                        layer.msg("正在充值", {
                            icon: 16,
                            shade: 0.05,
                            time: false
                        });
                    },
                    success: function(data) {
                        if (data.code == 1) {
                            layer.msg("充值成功", {
                                icon: 1
                            });
                            // $(".time").eq(0).html(data.msg)
                            Qmsg.success("充值成功", {
                                html: true,
                            });
                        } else if (data.code == -1) {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                        } else if (data.code == -2) {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                            Qmsg.error(data.msg);
                        } else if (data.code == -3) {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                            Qmsg.error(data.msg, {
                                html: true,
                            });
                        } else {
                            layer.msg("未知错误", {
                                icon: 5
                            });
                            Qmsg.error("未知错误");
                        }
                    },
                    error: function(data) {
                        // var obj = eval(data);
                        // console.log(obj)
                        layer.alert("充值失败", {
                            icon: 2
                        });
                    }
                });
            });

            function checkUsername(obj) {
                var reg = new RegExp("^[A-Za-z0-9]+$");
                if (!reg.test(obj)) {
                    return true;
                } else {
                    return false;
                }
            }

            $("#registed").click(function() {
                var user = $("#reg-user").val().trim();
                var pwd = $("#reg-pwd").val().trim();
                var code = $("#reg-code").val().trim();
                if (user == "") {
                    return Qmsg.info("账号不能为空")
                }
                if (pwd == "") {
                    return Qmsg.info("密码不能为空")
                }
                if (code == "") {
                    return Qmsg.info("卡密不能为空")
                }
                if (user.length < 5) {
                    return Qmsg.info("账号长度不得小于5位")
                }
                if (pwd.length < 5) {
                    return Qmsg.info("密码要大于5位")
                }
                if (code.length < 15) {
                    return Qmsg.info("卡密长度最小为16位")
                }
                if (checkUsername(user)) {
                    return Qmsg.info("账号请输入数字和英文！")
                }
                if (checkUsername(pwd)) {
                    return Qmsg.info("密码请输入数字和英文！")
                }
                var pattern = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z_]{5,16}$/
                if (!pattern.test(pwd)) {
                    return Qmsg.info("密码可以包含数字、字母、下划线，并且要同时含有数字和字母，且长度要在8-16位之间!")
                }
                //console.log(pattern.test(pwd));
                $.ajax({
                    url: "api/cpproxy.php?type=insert",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'user': user.trim(),
                        'pwd': pwd.trim(),
                        'code': code
                        // server: $("[name=server]").val()
                    },
                    timeout: 30000,
                    beforeSend: function() {
                        $("#registed").prop("disabled", true);
                        layer.msg("正在注册", {
                            icon: 16,
                            shade: 0.05,
                            time: false
                        });
                    },
                    success: function(data) {
                        if (data.code == 1) {
                            layer.msg("注册成功", {
                                icon: 1
                            });

                            // 显示注册成功信息
                            $(".time").eq(0).html(
                                '<div class="reg-success">' +
                                '<div class="success-icon">' +
                                '<i class="layui-icon layui-icon-ok-circle"></i>' +
                                '</div>' +
                                '<div class="reg-info">' + data.msg + '</div>' +
                                '</div>'
                            );

                            // 清空输入框
                            $("#reg-user").val("");
                            $("#reg-pwd").val("");
                            $("#reg-code").val("");

                            Qmsg.success("注册成功", {
                                html: true,
                            });
                        } else if (data.code == -1) {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                            Qmsg.error(data.msg);
                        } else {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                            Qmsg.error(data.msg, {
                                html: true,
                            });

                        }
                        $("#registed").prop("disabled", false);
                    },
                    error: function(data) {
                        // var obj = eval(data);
                        // console.log(obj)
                        layer.alert("注册失败", {
                            icon: 2
                        });
                        $("#registed").prop("disabled", false);
                    }
                });
            });
            $("#check").click(function() {
                var user = $("#check-user").val();
                var checked = $("#sel option:checked").val();
                if (checked == "") {
                    return Qmsg.info("请选择一个应用")
                }
                if (user == "") {
                    return Qmsg.info("账号不能为空")
                }
                if (user.length < 5) {
                    return Qmsg.info("账号长度不得小于6位")
                }
                // $.post("",{})
                $.ajax({
                    url: "api/cpproxy.php?type=query",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'user': user.trim(),
                        'appcode': checked
                    },
                    timeout: 30000,
                    beforeSend: function() {
                        layer.msg("正在查询", {
                            icon: 16,
                            shade: 0.05,
                            time: false,
                        });
                    },
                    success: function(data) {
                        if (data.code == 1) {
                            // 检查是否包含错误消息
                            if (data.msg.includes('账号不存在')) {
                                layer.msg(data.msg, {
                                    icon: 2
                                });
                                return;
                            }

                            layer.msg("查询成功", {
                                icon: 1
                            });

                            // 创建临时div来解析HTML并提取时间
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data.msg;
                            var timeText = tempDiv.textContent || tempDiv.innerText;

                            // 获取当前时间
                            var currentTime = new Date();
                            // 解析到期时间 (格式: YYYY-MM-DD HH:mm:ss)
                            var expiryParts = timeText.split('：')[1].split(/[- :]/);
                            var expiryTime = new Date(
                                expiryParts[0],
                                expiryParts[1] - 1, // 月份从0开始
                                expiryParts[2],
                                expiryParts[3],
                                expiryParts[4],
                                expiryParts[5]
                            );

                            // 确定用户状态
                            var status = currentTime > expiryTime ? '已过期' : '使用中';
                            var statusClass = currentTime > expiryTime ? 'status-expired' : 'status-active';

                            $(".time").eq(0).html(
                                '<div class="query-result">' +
                                '<div class="result-header">' +
                                '<i class="layui-icon layui-icon-time"></i>' +
                                '<span class="time-value" style="color: ' + (currentTime > expiryTime ? '#ff6b6b' : '#fff') + ';">' +
                                timeText +
                                '</span>' +
                                '</div>' +
                                '<div class="result-status">' +
                                '<i class="layui-icon ' + (currentTime > expiryTime ? 'layui-icon-close' : 'layui-icon-ok') + '"></i>' +
                                '<span class="' + statusClass + '">状态：' + status + '</span>' +
                                '</div>' +
                                '</div>'
                            );
                            Qmsg.success(data.msg, {
                                html: true,
                            });
                        } else if (data.code == -3) {
                            layer.msg(data.msg, {
                                icon: 5
                            });
                            Qmsg.error(data.msg, {
                                html: true,
                            });
                        } else {
                            layer.msg("未知错误", {
                                icon: 5
                            });
                            Qmsg.error("未知错误");
                        }
                    },
                    error: function(data) {
                        // var obj = eval(data);
                        // console.log(obj)
                        $(".time").eq(0).html("")
                        layer.alert("查询失败", {
                            icon: 2
                        });

                    }
                });
            });

            // 卡密查询功能
            $("#query-kami-btn").click(function() {
                var kami = $("#query-kami").val().trim();
                if (kami == "") {
                    return Qmsg.info("卡密不能为空");
                }
                if (kami.length < 1) {
                    return Qmsg.info("卡密长度最小为1位");
                }
                if (kami.length > 128) {
                    return Qmsg.info("卡密长度最大为128位");
                }

                $.ajax({
                    url: "api/api.php?act=queryKami",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'kami': kami
                    },
                    timeout: 30000,
                    beforeSend: function() {
                        layer.msg("正在查询", {
                            icon: 16,
                            shade: 0.05,
                            time: false
                        });
                    },
                    success: function(data) {
                        layer.closeAll();
                        if (data.code == 1) {
                            var kamiInfo = data.data;
                            var state = kamiInfo.state == 0 ? '<span class="status-active">未使用</span>' : '<span class="status-expired">已使用</span>';
                            
                            // 处理时长显示格式
                            var duration = kamiInfo.times;
                            if(duration.includes('+')) {
                                duration = duration.replace('+', '');
                                duration = duration.replace('day', '天');
                                duration = duration.replace('days', '天');
                                duration = duration.replace('month', '个月');
                                duration = duration.replace('months', '个月');
                                duration = duration.replace('year', '年');
                                duration = duration.replace('years', '年');
                                duration = duration.replace('hour', '小时');
                                duration = duration.replace('hours', '小时');
                                duration = duration.replace('minute', '分钟');
                                duration = duration.replace('minutes', '分钟');
                                duration = duration.replace('second', '秒');
                                duration = duration.replace('seconds', '秒');
                            }
                            
                            var html = '<div class="query-result">' +
                                '<div class="result-header">' +
                                '<i class="layui-icon layui-icon-note"></i>' +
                                '<span class="time-value">卡密信息</span>' +
                                '</div>' +
                                '<div class="result-content">' +
                                '<div class="info-item">' +
                                '<div class="info-label"><i class="layui-icon layui-icon-app"></i>所属应用</div>' +
                                '<div class="info-value">' + kamiInfo.app + '</div>' +
                                '</div>' +
                                '<div class="info-item">' +
                                '<div class="info-label"><i class="layui-icon layui-icon-time"></i>创建时间</div>' +
                                '<div class="info-value">' + kamiInfo.found_date + '</div>' +
                                '</div>' +
                                '<div class="info-item">' +
                                '<div class="info-label"><i class="layui-icon layui-icon-log"></i>卡密时长</div>' +
                                '<div class="info-value">' + duration + '</div>' +
                                '</div>' +
                                '<div class="info-item">' +
                                '<div class="info-label"><i class="layui-icon layui-icon-circle' + (kamiInfo.state == 0 ? '' : '-dot') + '"></i>使用状态</div>' +
                                '<div class="info-value">' + state + '</div>' +
                                '</div>';

                            // 如果卡密已使用，显示使用账号、使用时间和到期时间
                            if (kamiInfo.state == 1) {
                                html += '<div class="info-item">' +
                                    '<div class="info-label"><i class="layui-icon layui-icon-user"></i>使用账号</div>' +
                                    '<div class="info-value">' + kamiInfo.username + '</div>' +
                                    '</div>' +
                                    '<div class="info-item">' +
                                    '<div class="info-label"><i class="layui-icon layui-icon-time"></i>使用时间</div>' +
                                    '<div class="info-value">' + kamiInfo.use_date + '</div>' +
                                    '</div>' +
                                    '<div class="info-item">' +
                                    '<div class="info-label"><i class="layui-icon layui-icon-date"></i>到期时间</div>' +
                                    '<div class="info-value">' + kamiInfo.end_date + '</div>' +
                                    '</div>';
                            }

                            html += '</div></div>';
                            
                            $(".kami-info").html(html);
                            Qmsg.success("查询成功");
                        } else {
                            $(".kami-info").html('');
                            Qmsg.error(data.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        layer.closeAll();
                        layer.msg("查询失败，请稍后重试", {
                            icon: 2
                        });
                        $(".kami-info").html('');
                        // 清空输入框，避免错误信息被重复提交
                        $("#query-kami").val('');
                        error_log("卡密查询错误: " + error);
                    }
                });
            });

            // 修改密码功能
            $("#change-pwd-btn").click(function() {
                var app = $("#change-pwd-app").val();
                var account = $("#change-pwd-account").val().trim();
                var oldPassword = $("#change-pwd-old").val().trim();
                var newPassword = $("#change-pwd-new").val().trim();
                var confirmPassword = $("#change-pwd-confirm").val().trim();

                // 基本验证
                if (!app) {
                    return Qmsg.info("请选择应用");
                }
                if (!account) {
                    return Qmsg.info("账号不能为空");
                }
                if (!oldPassword) {
                    return Qmsg.info("原密码不能为空");
                }
                if (!newPassword) {
                    return Qmsg.info("新密码不能为空");
                }
                if (!confirmPassword) {
                    return Qmsg.info("请确认新密码");
                }
                if (newPassword !== confirmPassword) {
                    return Qmsg.warning("两次输入的新密码不一致");
                }

                // 密码格式验证
                if (newPassword.length < 8 || newPassword.length > 16) {
                    return Qmsg.warning("新密码长度必须在8-16位之间");
                }
                var hasNumber = /\d/.test(newPassword);
                var hasLetter = /[a-zA-Z]/.test(newPassword);
                if (!hasNumber || !hasLetter) {
                    return Qmsg.warning("新密码必须同时包含数字和字母");
                }
                if (!/^[\w]+$/.test(newPassword)) {
                    return Qmsg.warning("新密码只能包含数字、字母和下划线");
                }

                // 显示加载层
                var loadIndex = layer.load(1, {
                    shade: [0.1, '#fff']
                });

                // 先修改主应用密码
                $.ajax({
                    url: "api/cpproxy.php?type=changepwd",
                    type: "POST",
                    dataType: "json",
                    data: {
                        'appcode': app,
                        'user': account,
                        'old_pwd': oldPassword,
                        'new_pwd': newPassword
                    },
                    success: function(res) {
                        if(res.code == 1) {
                            // 主应用密码修改成功后，检查是否需要修改继承应用密码
                            $.ajax({
                                url: "api/api.php?act=getInheritApps",
                                type: "POST",
                                dataType: "json",
                                data: {
                                    'appcode': app
                                },
                                success: function(inheritRes) {
                                    if(inheritRes.code == 1 && Array.isArray(inheritRes.data) && inheritRes.data.length > 0) {
                                        var inheritErrors = [];
                                        var completedCount = 0;
                                        
                                        // 定义检查完成状态的函数
                                        function checkCompletion() {
                                            if(completedCount === inheritRes.data.length) {
                                                layer.close(loadIndex);
                                                
                                                if(inheritErrors.length > 0) {
                                                    layer.msg("主应用密码修改成功，但部分继承应用密码修改失败", {
                                                        icon: 0,
                                                        time: 2000
                                                    });
                                                } else {
                                                    layer.msg("密码修改成功", {
                                                        icon: 1,
                                                        time: 2000
                                                    });
                                                }
                                                // 清空输入框
                                                $("#change-pwd-old").val("");
                                                $("#change-pwd-new").val("");
                                                $("#change-pwd-confirm").val("");
                                            }
                                        }
                                        
                                        // 修改每个继承应用的密码
                                        inheritRes.data.forEach(function(inheritApp) {
                                            // 先检查账号是否存在
                                            $.ajax({
                                                url: "api/cpproxy.php?type=query",
                                                type: "POST",
                                                dataType: "json",
                                                data: {
                                                    'appcode': inheritApp,
                                                    'user': account
                                                },
                                                success: function(queryRes) {
                                                    // 修改账号存在的判断逻辑
                                                    if(queryRes.code == 1 && !queryRes.msg.includes('账号不存在')) {
                                                        $.ajax({
                                                            url: "api/cpproxy.php?type=changepwd",
                                                            type: "POST",
                                                            dataType: "json",
                                                            data: {
                                                                'appcode': inheritApp,
                                                                'user': account,
                                                                'old_pwd': oldPassword,
                                                                'new_pwd': newPassword
                                                            },
                                                            success: function(pwdRes) {
                                                                completedCount++;
                                                                if(pwdRes.code !== 1) {
                                                                    error_log("继承应用密码修改失败: " + inheritApp + " - " + pwdRes.msg);
                                                                    inheritErrors.push(inheritApp);
                                                                }
                                                                checkCompletion();
                                                            },
                                                            error: function(xhr, status, error) {
                                                                error_log("继承应用密码修改请求失败: " + inheritApp + " - " + error);
                                                                completedCount++;
                                                                inheritErrors.push(inheritApp);
                                                                checkCompletion();
                                                            }
                                                        });
                                                    } else {
                                                        error_log("继承应用账号不存在: " + inheritApp);
                                                        completedCount++;
                                                        inheritErrors.push(inheritApp);
                                                        checkCompletion();
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    error_log("继承应用账号查询失败: " + inheritApp + " - " + error);
                                                    completedCount++;
                                                    inheritErrors.push(inheritApp);
                                                    checkCompletion();
                                                }
                                            });
                                        });
                                    } else {
                                        layer.close(loadIndex);
                            layer.msg("密码修改成功", {
                                            icon: 1,
                                            time: 2000
                            });
                            // 清空输入框
                            $("#change-pwd-old").val("");
                            $("#change-pwd-new").val("");
                            $("#change-pwd-confirm").val("");
                                    }
                                },
                                error: function(xhr, status, error) {
                                    error_log("获取继承应用列表失败: " + error);
                                    layer.close(loadIndex);
                                    layer.msg("获取继承应用失败，仅主应用密码修改成功", {
                                        icon: 0,
                                        time: 2000
                                    });
                                }
                            });
                        } else {
                            layer.close(loadIndex);
                            layer.msg(res.msg || "密码修改失败", {
                                icon: 5,
                                time: 2000
                            });
                        }
                    },
                    error: function() {
                        layer.close(loadIndex);
                        layer.msg("修改密码失败，请稍后重试", {
                            icon: 2,
                            time: 2000
                        });
                    }
                });
            });

            var isModal = <?php echo (empty($conf['wzgg']) || $conf['ggswitch'] != 1) ? 'false' : 'true'; ?>;
            if (!$.cookie('op') && isModal == true) {
                var slider = document.createElement("div");
                slider.innerHTML = '<?php echo $conf['wzgg']; ?>';
                swal({
                    title: "公告",
                    icon: "success",
                    button: "好的",
                    content: slider,
                });
                var cookietime = new Date();
                cookietime.setTime(cookietime.getTime() + (10 * 60 * 1000));
                $.cookie('op', false, {
                    expires: cookietime
                });
            }

        })

        function showgg() {
            <?php if ($conf['ggswitch'] == 1) { ?>
                var slider = document.createElement("div");
                slider.innerHTML = '<?php echo $conf['wzgg']; ?>';
                swal({
                    title: "公告",
                    icon: "success",
                    button: "好的",
                    content: slider,
                });
                var cookietime = new Date();
                cookietime.setTime(cookietime.getTime() + (10 * 60 * 1000));
                $.cookie('op', false, {
                    expires: cookietime
                });
            <?php } else { ?>
                notgg();
            <?php } ?>
        }

        function notgg() {
            swal({
                title: "公告",
                icon: "info",
                button: "好",
                text: "没有公告"
            });
        }

        function CheckHeight() {
            let el = document.getElementsByClassName("layui-tab-title")[0].getElementsByTagName("li");
            if (window.screen.width < 330) {
                for (let index = 0; index < el.length; index++) {
                    el[index].innerText = el[index].innerText.substring(2, el[index].innerText.length);
                    el[index].style.paddingLeft = 0;
                    el[index].style.paddingRight = 0;
                }
            }
        }
        CheckHeight();

        function setBackground() {
            var hour = new Date().getHours();
            var bgswitch = <?php echo $subconf['bgswitch']; ?>;
            var dayimg = '<?php echo $subconf['dayimg']; ?>';
            var nightimg = '<?php echo $subconf['nightimg']; ?>';

            if (bgswitch && (dayimg || nightimg)) {
                if (hour >= 6 && hour < 18) {
                    // 日间模 (6:00 - 17:59)
                    if (dayimg) {
                        document.body.style.backgroundImage = "url('" + dayimg + "')";
                    }
                } else {
                    // 夜间模式 (18:00 - 5:59)
                    if (nightimg) {
                        document.body.style.backgroundImage = "url('" + nightimg + "')";
                    }
                }
            }
        }

        // 页面加载时设置背景
        document.addEventListener('DOMContentLoaded', setBackground);

        // 每分钟检查一次是否需要切换背景
        setInterval(setBackground, 60000);
    </script>
</body>

</html>