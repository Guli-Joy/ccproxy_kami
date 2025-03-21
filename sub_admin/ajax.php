<?php
require_once("../includes/Task.php");
require_once("../includes/Scheduler.php");
include("../includes/common.php");
if ($islogin == 1) {
} else exit("<script language='javascript'>window.location.href='./login.php';</script>");

// 将 act 参数统一转换为小写
$act = strtolower(isset($_GET['act']) ? daddslashes($_GET['act']) : (isset($_POST['act']) ? daddslashes($_POST['act']) : null));
@header('Content-Type: application/json; charset=UTF-8');

try {
    // 验证用户权限
    if($islogin != 1) {
        logSecurityEvent('UNAUTHORIZED_ACCESS', '未授权的访问尝试');
        throw new Exception('请先登录');
    }
    
switch ($act) {
    case 'getserver':
        $sql = 'select id,ip,comment from server_list where username=\'' . $subconf['username'] . '\' ';
        $server_list = $DB->select($sql);
        $code = [
            "code" => "1",
            "msg" => $server_list
        ];
        exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        break;
    case 'newapp':
        $server = $_POST['server'];
        $username = $_POST['username'];
        $sql = 'select appname from application';
        $dist_name = $DB->select($sql);
        // print_r($dist_name);
        $flag = true;
        foreach ($dist_name as $key => $name) {
            if ($username == $name['appname']) {
                $flag = false;
            }
        }
        if ($flag) {
            $appcode = md5(uniqid(mt_rand(), 1) . time());
            $arr = array(
                'appname'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $username)),
                'appcode' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $appcode)),
                'serverip'     => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $server)),
                'username' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $subconf['username'])),
            );
            $exec = $DB->insert('application', $arr);
            if ($exec) {
                $code = [
                    "code" => "1",
                    "msg" => "添加成功"
                ];

                // $cxserver=$DB->selectRow("SELECT applist FROM server_list WHERE ip='".addslashes($server)."'");
            
                // $sqlserver="UPDATE server_list set applist='".((empty($cxserver['applist'])?"":$cxserver['applist'].",").$appcode)."' where ip='".addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $server))."' ";
    
                // $result = $DB->exe($sqlserver);

                WriteLog("添加用户", "添加了" . $username, $subconf['username'], $DB);


            } else {
                $code = [
                    "code" => "-1",
                    "msg" => "添加失败"
                ];
            }
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        } else {
            $code = [
                "code" => "0",
                "msg" => "应用名重复"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }

        break;

    case "apptable":
        $sqlj = "";
        if (isset($_REQUEST['page']) && isset($_REQUEST['limit']) && isset($_REQUEST['server']) && isset($_REQUEST['appname'])) {
            //服务器sql
            $sqlj .= $_REQUEST['server'] != "" && $_REQUEST['server'] != "*" ? "and serverip=\"" . $_REQUEST['server'] . "\"" : "";
            //应用名字搜索
            $sqlj .= $_REQUEST['appname'] != "" ? " and appname LIKE '%" . $_REQUEST["appname"] . "%'" : "";
            //  $sqlj .= $_REQUEST['appname'] != "" ? " and appname=\"" . $_REQUEST['appname'] . "\"" : "";
            $sql = 'SELECT appid,appcode,appname,serverip,found_time FROM application where username=\'' . $subconf['username'] . '\' ' . $sqlj . ' ';
           
            // // $DB->pageNo=$_REQUEST['page'];当前页码
            // //$DB->pageRows=$_REQUEST['limit'];多少行数
            $countpage = $DB->selectRow("select count(*) as num from application where username=\"" . $subconf['username'] . "\"");
           
            $app = $DB->selectPage($sql, $DB->pageNo = $_REQUEST['page'], $DB->pageRows = $_REQUEST['limit']);
            
            foreach ($app as $key => $apps) {
                $app[$key]['appid'] = $key + 1;
            }

            $json = ["code" => "0", "count" => $countpage['num'], "data" => $app, "icon" => "1"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        } else {
            $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "delapp":
        if (isset($_POST['appcode'])) {

            $exesql = $DB->delete("application", "where appcode=\"" . $_REQUEST['appcode'] . "\"");

            if ($exesql) {
                $code = [
                    "code" => "1",
                    "msg" => "删除成功"
                ];

                WriteLog("删除应用", "删除了" . $_REQUEST['appcode'], $subconf['username'], $DB);
                exit(json_encode($code, JSON_UNESCAPED_UNICODE));
            } else {
                $code = [
                    "code" => "0",
                    "msg" => "未知错误"
                ];
                exit(json_encode($code, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "seldel":
        if (!isset($_POST['item'])) {
            $code = [
                "code" => "-1",
                "msg" => "删除失败"
            ];
            WriteLog("删除失败", "删除失败参数为空" . $_POST['item'], $subconf['username'], $DB);
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        $arr = $_POST['item'];
        $execs = 0;
        $execf = 0;
        for ($i = 0; $i < count($arr); $i++) {
            $exesql = $DB->delete("application", "where appcode=\"" . $arr[$i] . "\"");
            if ($exesql) {
                $execs++;
            } else {
                $execf++;
            }
        }
        if ($execs == count($arr)) {
            $code = [
                "code" => "1",
                "msg" => "删除成功"
            ];
            WriteLog("删除", "删除了" . implode(", ", $arr), $subconf['username'], $DB);
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        } else {
            $code = [
                "code" => "1",
                "msg" => "删除成功：" . $execs . "，删除失败：" . $execf,
            ];
            WriteLog("删除", "删除了" . implode(", ", $arr) . "，成功：" . $execs . "，失败：" . $execf, $subconf['username'], $DB);
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "serverdel":
        $arr = $_POST['item'];
        $execs = 0;
        $execf = 0;
        for ($i = 0; $i < count($arr); $i++) {
            $exesql = $DB->delete("server_list", "where ip=\"" . $arr[$i] . "\"");
            if ($exesql) {
                $execs++;
            } else {
                $execf++;
            }
        }
        if ($execs == count($arr)) {
            $code = [
                "code" => "1",
                "msg" => "删除成功"
            ];
            WriteLog("删除服务器", "删除了服务器: " . implode(", ", $arr), $subconf['username'], $DB);
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        } else {
            $code = [
                "code" => "1",
                "msg" => "删除成功：" . $execs . "，删除失败：" . $execf,
            ];
            WriteLog("删除服务器", "删除了服务器: " . implode(", ", $arr) . "，成功：" . $execs . "，失败：" . $execf, $subconf['username'], $DB);
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "update":
        // .addslashes($_REQUEST['serverip'])." WHERE appcode=".$_REQUEST['appcode'].
        if (isset($_REQUEST['appcode']) && isset($_REQUEST['appname']) && isset($_REQUEST['serverip'])) {
            $sql = "UPDATE application SET appname=\"" . addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $_REQUEST['appname'])) . "\",serverip=\"" . addslashes($_REQUEST['serverip']) . "\" WHERE appcode=\"" . $_REQUEST['appcode'] . "\" ";
            $result = $DB->exe($sql);

            $cxserver=$DB->selectRow("SELECT applist FROM server_list WHERE ip='".addslashes($_REQUEST['serverip'])."'");
            

            if ($result) {
                $code = [
                    "code" => "1",
                    "msg" => "更新成功！"
                ];
                
                // $sqlserver="UPDATE server_list set applist='".((empty($cxserver['applist'])?"":$cxserver['applist'].",").$_REQUEST['appcode'])."' where ip='".addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $_REQUEST['serverip']))."' ";

                // $result = $DB->exe($sqlserver);

                WriteLog("更新", "更新了" . $_REQUEST['appname'], $subconf['username'], $DB);
                exit(json_encode($code, JSON_UNESCAPED_UNICODE));
            } else {
                $code = [
                    "code" => "-1",
                    "msg" => "更新失败！"
                ];
                exit(json_encode($code, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $json = ["code" => "-1", "msg" => "参数错误！"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "servertable":
        // print_r($_REQUEST);
        $sqlj = "";
        if (isset($_REQUEST['page']) && isset($_REQUEST['limit']) && isset($_REQUEST['ip']) && isset($_REQUEST['comment'])) {
            //服务器IP
            $sqlj .= $_REQUEST['ip'] != "" ? "and ip=\"" . $_REQUEST['ip'] . "\"" : "";
            $sqlj .= $_REQUEST['comment'] != "" ? " and comment LIKE '%" . $_REQUEST["comment"] . "%'" : "";
            // $sqlj .= $_REQUEST['comment'] != "" ? " and comment=\"" . $_REQUEST['comment'] . "\"" : "";
            $sql = 'SELECT id,ip,serveruser,password,cport,state,comment FROM server_list where username=\'' . $subconf['username'] . '\' ' . $sqlj . ' ';
            // // $DB->pageNo=$_REQUEST['page'];当前页码
            // //$DB->pageRows=$_REQUEST['limit'];多少行数
            $countpage = $DB->selectRow("select count(*) as num from server_list where username=\"" . $subconf['username'] . "\"");
            $app = $DB->selectPage($sql, $DB->pageNo = $_REQUEST['page'], $DB->pageRows = $_REQUEST['limit']);
            // foreach ($app as $key => $apps) {
            //     $app[$key]['id'] = $key + 1;
            // }
            $json = ["code" => "0", "count" => $countpage['num'], "data" => $app, "icon" => 1];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        } else {
            $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "newserver":

        $serverip = $_POST['serverip'];
        $ccpusername = $_POST['ccpusername'];
        $ccppassword = $_POST['ccppassword'];
        $ccpport = $_POST['ccpport'];
        $state = $_POST['state'] == null ? "0" : "1";
        $comment = $_POST['comment'];

        $sql = 'select ip from server_list';
        $dist_ip = $DB->select($sql);
        // print_r($dist_ip);
        $flag = true;
        foreach ($dist_ip as $key => $name) {
            if ($serverip == $name['ip']) {
                $flag = false;
            }
        }
      
        if ($flag) {
            $valid=count(explode(".",$serverip));
            if($valid<2){
                $json = [
                    "code" => "-1",
                    "msg" => "输入了错误的IP或者域名",
                    "icon"=>"5"
                ];
                exit(json_encode($json, JSON_UNESCAPED_UNICODE));
            }

            if(!ValidPort($ccpport)){
                $json = [
                    "code" => "-1",
                    "msg" => "输入了错误的端口号",
                    "icon"=>"5"
                ];
                exit(json_encode($json, JSON_UNESCAPED_UNICODE));
            }
            $arr = array(
                'ip'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $serverip)),
                'serveruser'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $ccpusername)),
                'password'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $ccppassword)),
                'cport'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $ccpport)),
                'state'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $state)),
                'comment'  => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $comment)),
                'username' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $subconf['username']))
            );
            $exec = $DB->insert('server_list', $arr);
            if ($exec) {
                $code = [
                    "code" => "1",
                    "msg" => "添加成功"
                ];
                WriteLog("添加服务器", "添加了一个服务器" . $serverip, $subconf['username'], $DB);
            } else {
                $code = [
                    "code" => "-1",
                    "msg" => "添加失败"
                ];
            }
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        } else {
            $code = [
                "code" => "0",
                "msg" => "服务器IP重复"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "upswitch":
        if (isset($_POST['ip']) && isset($_POST["state"])) {
            $sql = "UPDATE server_list SET state=\"" . addslashes($_POST["state"]) . "\" WHERE ip=\"" . addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $_POST['ip'])) . "\" ";
            $result = $DB->exe($sql);
            if ($result) {
                $code = [
                    "code" => "1",
                    "msg" => "更新成功"
                ];
                    WriteLog("新", "开关" . $_POST['ip'], $subconf['username'], $DB);
            } else {
                $code = [
                    "code" => "0",
                    "msg" => "更新失败"
                ];
            }
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        } else {
            $code = [
                "code" => "0",
                "msg" => "参数错误"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "editserver":
        try {
            if(!isset($_POST['data'])) {
                throw new Exception('参数错误');
            }

            $data = $_POST['data'];
            
            // 验证必要参数
            if(empty($data['serverip']) || empty($data['user']) || empty($data['pwd']) || empty($data['cport'])) {
                throw new Exception('请填写完整信息');
            }

            // 验证端口号
            if(!is_numeric($data['cport']) || $data['cport'] < 1 || $data['cport'] > 65535) {
                throw new Exception('端口号必须在1-65535之间');
            }

            // 验证IP格式
            $valid = count(explode(".", $data['serverip']));
            if($valid < 2) {
                throw new Exception('输入了错误的IP或者域名');
            }

            // 检查IP是否已存在（排除自身）
            $check = $DB->selectRow("SELECT id FROM server_list WHERE ip='" . $DB->escape($data['serverip']) . "' AND id != '" . $DB->escape($data['id']) . "'");
            if($check) {
                throw new Exception('该服务器IP已存在');
            }

            // 更新服务器信息
            $update = [
                'ip' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $data['serverip'])),
                'serveruser' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $data['user'])),
                'password' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $data['pwd'])),
                'cport' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $data['cport'])),
                'state' => $data['state'],
                'comment' => addslashes(str_replace(array("<", ">", "/"), array("&lt;", "&gt;", ""), $data['comment']))
            ];

            $sql = "UPDATE server_list SET ";
            foreach($update as $key => $value) {
                $sql .= "`$key`='$value',";
            }
            $sql = rtrim($sql, ',');
            $sql .= " WHERE id='" . $DB->escape($data['id']) . "' AND username='" . $DB->escape($subconf['username']) . "'";

            $result = $DB->exe($sql);
            if($result !== false) {
                WriteLog("编辑服务器", "编辑服务器 [{$data['serverip']}] " . $data['comment'], $subconf['username'], $DB);
                exit(json_encode([
                    'code' => 1,
                    'msg' => '编辑成功'
                ], JSON_UNESCAPED_UNICODE));
            } else {
                throw new Exception('编辑失败');
            }

        } catch(Exception $e) {
            exit(json_encode([
                'code' => -1,
                'msg' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;
    case "getkami":
        $sqlj = "";
        if (isset($_REQUEST['page']) && isset($_REQUEST['limit']) && isset($_REQUEST['code']) && isset($_REQUEST['found_date']) && isset($_REQUEST['use_date']) && isset($_REQUEST['sc_user']) && isset($_REQUEST['state']) && isset($_REQUEST['comment']) && isset($_REQUEST['app'])) {
            $sqlj .= $_REQUEST['code'] != "" ? "and k.kami=\"" . $_REQUEST['code'] . "\"" : "";
            $sqlj .= $_REQUEST['found_date'] != "" ? " and k.found_date=\"" . $_REQUEST['found_date'] . "\"" : "";
            $sqlj .= $_REQUEST['use_date'] != "" ? " and k.use_date=\"" . $_REQUEST['use_date'] . "\"" : "";
            $sqlj .= $_REQUEST['sc_user'] != "" ? " and k.sc_user=\"" . $_REQUEST['sc_user'] . "\"" : "";
            $sqlj .= $_REQUEST['state'] != "" ? " and k.state=\"" . $_REQUEST['state'] . "\"" : "";
            $sqlj .= $_REQUEST['comment'] != "" ? " and k.comment=\"" . $_REQUEST['comment'] . "\"" : "";
            $sqlj .= $_REQUEST['app'] != "" ? " and k.app=\"" . $_REQUEST['app'] . "\"" : "";
            $sqlj .= " order by k.found_date desc";
            
            $sql = 'SELECT k.*, a.appname 
                    FROM kami k 
                    LEFT JOIN application a ON k.app = a.appcode 
                    WHERE k.sc_user="' . $subconf['username'] . '" ' . $sqlj;
            
            $countpage = $DB->selectRow("select count(*) as num from kami where sc_user=\"" . $subconf['username'] . "\"");
            $app = $DB->selectPage($sql, $DB->pageNo = $_REQUEST['page'], $DB->pageRows = $_REQUEST['limit']);
            foreach ($app as $key => $apps) {
                $app[$key]['id'] = $key + 1;
                if ($app[$key]['state'] == 1) {
                    $app[$key]['state'] = "<span style='color:red'>已激活</span>";
                } else {
                    $app[$key]['state'] = "<span style='color:green'>未激活</span>";
                }
                $app[$key]['times']=KamiPaeseString($app[$key]['times']);
                if(empty($app[$key]['appname'])) {
                    $app[$key]['appname'] = $app[$key]['app'];
                }
                $app[$key]['app'] = $app[$key]['appname']; // 替换显示内容
            }

            $json = ["code" => "0", "count" => $countpage['num'], "data" => $app, "icon" => "1"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        } else {
            $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "newkami":
        try {
            // 验证必要参数
            if(!isset($_POST['app']) || !isset($_POST['duration']) || !isset($_POST['kaminum'])) {
                throw new Exception('参数错误');
            }

            // 获取继承配置
            $inheritConfig = null;
            if($subconf['inherit_enabled']) {
                if(!empty($subconf['inherit_groups'])) {
                    $decoded_str = $subconf['inherit_groups'];
                    $prev_str = '';
                    while($decoded_str !== $prev_str) {
                        $prev_str = $decoded_str;
                        $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    $inheritConfig = json_decode($decoded_str, true);
                }
            }

            // 获取继承应用列表
            $inheritApps = [];
            if($inheritConfig && isset($inheritConfig['groups'])) {
                foreach($inheritConfig['groups'] as $group) {
                    if(in_array($_POST['app'], $group['main_apps'])) {
                        $inheritApps = array_merge($inheritApps, $group['inherit_apps']);
                    }
                }
                $inheritApps = array_unique($inheritApps);
            }

            // 构建卡密时长
            $kamidurdangwei = "";
            if(!empty($_POST["kamidur"])) {
                $kamidurdangwei = "+" . floatval($_POST["kamidur"]);
                if(isset($_POST["year"]) && $_POST["year"]=="on") $kamidurdangwei .= " year";
                if(isset($_POST["month"]) && $_POST["month"]=="on") $kamidurdangwei .= " month";
                if(isset($_POST["day"]) && $_POST["day"]=="on") $kamidurdangwei .= " day";
                if(isset($_POST["hour"]) && $_POST["hour"]=="on") $kamidurdangwei .= " hour";
                if(isset($_POST["minute"]) && $_POST["minute"]=="on") $kamidurdangwei .= " minute";
            } else {
                $kamidurdangwei = "+" . $_POST["duration"] . " day";
            }

            // 构建扩展参数
            $ext = [
                "connection" => empty($_POST["connection"]) || $_POST["connection"]<=0 ? -1 : (int)$_POST["connection"],
                "bandwidthup" => empty($_POST["bandwidthup"]) || $_POST["bandwidthup"]<=0 ? -1 : (int)$_POST["bandwidthup"]*1024,
                "bandwidthdown" => empty($_POST["bandwidthdown"]) || $_POST["bandwidthdown"]<=0 ? -1 : (int)$_POST["bandwidthdown"]*1024,
                "inherit_apps" => $inheritApps // 添加继承应用信息到扩展参数
            ];

            // 生成卡密
            $kami = [];
            for($i = 0; $i < $_POST["kaminum"]; $i++) {
                $kamiCode = random($_POST["kamilen"] == "" ? 16 : $_POST["kamilen"], $_POST['qianzhui'] == "" ? null : $_POST['qianzhui']);
                $kami[] = [
                    "kami" => $kamiCode
                ];

                // 保存卡密到数据库
                $data = [
                    'kami' => $kamiCode,
                    'times' => $kamidurdangwei,
                    'host' => $subconf['siteurl'],
                    'sc_user' => $subconf['username'],
                    'state' => 0,
                    'app' => $_POST["app"],
                    'comment' => $_POST["comment"] ?? '',
                    'ext' => json_encode($ext)
                ];
                
                $DB->insert('kami', $data);
            }

            // 返回结果
            if(isset($_POST['copy'])) {
                exit(json_encode([
                        "code" => "2",
                    "msg" => "生成成功",
                        "kami" => $kami
                ]));
                } else {
                exit(json_encode([
                        "code" => "1",
                    "msg" => "生成成功"
                ]));
            }

        } catch(Exception $e) {
            exit(json_encode([
                "code" => "-1",
                "msg" => $e->getMessage()
            ]));
        }
        break;
    case "getapp":
        $sql = 'SELECT appcode,appname FROM application where username=\'' . $subconf['username'] . '\' ';
        $query = $DB->select($sql);
        $code = [
            "code" => "1",
            "msg" => $query
        ];
        exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        break;
    case "delkami":
        try {
            // 验证输入参数
            if(empty($_POST['item']) || !is_array($_POST['item'])) {
                logSecurityEvent('INVALID_REQUEST', '删除卡密时提供了无效参数');
                throw new Exception('参数错误');
            }

            // 限制单次删除数量
            if(count($_POST['item']) > 1000) {
                throw new Exception('单次最多删除1000个卡密');
            }

            // 过滤和验证输入数组
            $arr = array_map(function($item) use ($DB) {
                // 额外的输入验证
                if(!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $item)) {
                    return false;
                }
                return $DB->escape($item);
            }, array_unique($_POST['item'])); // 去重处理
            
            // 过滤掉无效的卡密
            $arr = array_filter($arr, function($value) {
                return $value !== false;
            });
            
            if(empty($arr)) {
                throw new Exception('没有有效的卡密可以删除');
            }

            // 验证卡密是否存在
            $kamiList = "'" . implode("','", $arr) . "'";
            $checkQuery = "SELECT kami, state FROM kami WHERE kami IN ({$kamiList}) AND sc_user='" . $DB->escape($subconf['username']) . "'";

            $existingKamis = $DB->select($checkQuery);
            if(!$existingKamis) {
                throw new Exception('未找到任何可删除的卡密');
            }

            // 构建卡密状态映射
            $kamiStatus = [];
            foreach($existingKamis as $k) {
                $kamiStatus[$k['kami']] = $k;
            }

            // 记录操作日志
            logSecurityEvent('KAMI_DELETE', '批量删除卡密', [
                'items' => $arr,
                'operator' => $subconf['username']
            ]);

            // 初始化计数器和错误数组
            $success = 0;
            $failed = 0;
            $errors = [];

            // 批量删除卡密
            foreach ($arr as $kami) {
                try {
                    $deleteQuery = "DELETE FROM kami WHERE kami = '" . $kami . "' AND sc_user='" . $DB->escape($subconf['username']) . "'";
                    $result = $DB->exe($deleteQuery);

                    if($result) {
                        $success++;
                    } else {
                        $failed++;
                        $errors[] = "卡密 {$kami} 不存在或无权删除";
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "删除卡密 {$kami} 时发生错误: " . $e->getMessage();
                    ErrorHandler::handleError(E_USER_WARNING, "删除卡密出错: {$e->getMessage()}", $e->getFile(), $e->getLine());
                }
            }

            if($success > 0) {
                    $response = [
                        'code' => 1,
                    'msg' => $failed > 0 ? "成功删除 {$success} 个卡密，失败 {$failed} 个" : "成功删除 {$success} 个卡密",
                    'success_count' => $success,
                    'failed_count' => $failed
                ];
                
                if($failed > 0) {
                    $response['errors'] = $errors;
                }
            } else {
                throw new Exception("删除失败：" . implode("; ", $errors));
            }

            exit(json_encode($response, JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            // 记录错误日志
            ErrorHandler::handleError(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
            
            $response = [
                'code' => -1,
                'msg' => $e->getMessage()
            ];
            exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "compensatetime":
        try {
            // 验证和获取必要参数
            if(!isset($_POST['app']) || !isset($_POST['expire_filter']) || 
               !isset($_POST['value']) || !isset($_POST['unit'])) {
                throw new Exception('缺少必要参数');
            }

            $app = SecurityFilter::filterInput($_POST['app']);
            $expire_filter = SecurityFilter::filterInput($_POST['expire_filter']);
            $value = floatval($_POST['value']);
            $unit = SecurityFilter::filterInput($_POST['unit']);

            // 验证参数有效性
            if(empty($app)) {
                throw new Exception('请选择应用');
            }
            if($value <= 0) {
                throw new Exception('补偿时间必须大于0');
            }
            if(!in_array($unit, ['days', 'hours', 'minutes'])) {
                throw new Exception('无效的时间单位');
            }

            // 获取应用信息
            $app_info = $DB->selectRow("SELECT * FROM application WHERE appcode='" . 
                $DB->escape($app) . "' AND username='" . $DB->escape($subconf['username']) . "'");
            if(!$app_info) {
                throw new Exception('应用不存在或无权限访问');
            }

            // 获取服务器信息
            $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . 
                $DB->escape($app_info['serverip']) . "'");
            if(!$server) {
                throw new Exception('服务器信息不存在');
            }

            // 获取分页参数
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 20; // 减小批次大小

            // 使用session来防止重复处理
            $session_key = "compensate_{$app}_{$value}_{$unit}_" . date('Y-m-d');
            if($offset == 0) {
                // 如果是新的补偿任务，清除之前的session
                if(isset($_SESSION[$session_key])) {
                    unset($_SESSION[$session_key]);
                }
            } else {
                // 检查是否已经处理过这个offset
                if(isset($_SESSION[$session_key]['processed_offsets']) && 
                   in_array($offset, $_SESSION[$session_key]['processed_offsets'])) {
                    // 获取最终的统计数据
                    $final_stats = [
                        'code' => 1,  // 改为1，表示成功
                        'msg' => '补偿处理已完成',
                        'details' => [
                            'total' => $_SESSION[$session_key]['total'],
                            'total_processed' => $_SESSION[$session_key]['total'], // 使用total作为total_processed
                            'success' => $_SESSION[$session_key]['success'],
                            'failed' => $_SESSION[$session_key]['failed'],
                            'skipped' => $_SESSION[$session_key]['skipped'],
                            'errors' => $_SESSION[$session_key]['errors'],
                            'value' => $value,
                            'unit' => $unit,
                            'has_more' => false  // 表示处理已完成
                        ]
                    ];
                    exit(json_encode($final_stats, JSON_UNESCAPED_UNICODE));
                }
            }

            // 获取用户列表
            $users_generator = SerchearchAllServer($app, "", $DB);
            if(!$users_generator) {
                throw new Exception('获取用户列表失败');
            }

            // 初始化或获取统计数据
            if(!isset($_SESSION[$session_key])) {
                $_SESSION[$session_key] = [
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'errors' => array(),
                    'processed_offsets' => array()
                ];

                // 计算总用户数
                foreach($users_generator as $users) {
                    if(is_array($users)) {
                        $_SESSION[$session_key]['total'] += count($users);
                    }
                }
            }

            // 重新获取用户列表用于处理
            $users_generator = SerchearchAllServer($app, "", $DB);
            $current_time = date('Y-m-d H:i:s');
            $current_offset = 0;
            $processed = 0;

            // 处理用户
            foreach($users_generator as $users) {
                if(!is_array($users) || empty($users)) {
                    continue;
                }

                foreach($users as $user) {
                    // 跳过不在当前批次的用户
                    if($current_offset++ < $offset) {
                        continue;
                    }

                    // 达到批次大小时停止
                    if($processed >= $batch_size) {
                        break 2;
                    }

                    try {
                        // 每处理5个用户就刷新一次session，避免session锁定
                        if($processed > 0 && $processed % 5 == 0) {
                            session_write_close();
                            session_start();
                        }

                        if(!isset($user['user']) || !isset($user['disabletime'])) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        $is_expired = strtotime($user['disabletime']) < time();

                        // 根据过滤条件跳过不符合的用户
                        if($expire_filter == 'expired' && !$is_expired) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }
                        if($expire_filter == 'unexpired' && $is_expired) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        // 计算新的到期时间
                        $compensation = "+{$value} {$unit}";
                        $new_time = $is_expired 
                            ? date('Y-m-d H:i:s', strtotime($current_time . " " . $compensation))
                            : date('Y-m-d H:i:s', strtotime($user['disabletime'] . " " . $compensation));

                        // 更新用户时间
                        $update_result = UserUpdate(
                            $server["password"],
                            $server["cport"],
                            $server["ip"],
                            $user["user"],
                            $user["pwd"],
                            $new_time,
                            $user["connection"] ?? "-1",
                            $user["bandwidthup"] ?? "-1",
                            $user["bandwidthdown"] ?? "-1",
                            "0"
                        );

                        if($update_result && isset($update_result['code']) && $update_result['code'] == "1") {
                            $_SESSION[$session_key]['success']++;
                        } else {
                            throw new Exception(isset($update_result['msg']) ? $update_result['msg'] : '未知错误');
                        }

                        $processed++;
                        $_SESSION[$session_key]['processed']++;

                    } catch (Exception $e) {
                        $_SESSION[$session_key]['failed']++;
                        $_SESSION[$session_key]['errors'][] = "用户 {$user['user']} 补偿失败: " . $e->getMessage();
                        $processed++;
                        $_SESSION[$session_key]['processed']++;
                    }
                }
            }

            // 记录已处理的offset
            $_SESSION[$session_key]['processed_offsets'][] = $offset;

            // 返回结果
            $result = [
                'code' => 1,
                'msg' => sprintf(
                    "批次处理完成：处理%d个账号，成功%d个，失败%d个", 
                    $processed,
                    $_SESSION[$session_key]['success'],
                    $_SESSION[$session_key]['failed']
                ),
                'details' => [
                    'total' => $_SESSION[$session_key]['total'],
                    'total_processed' => $_SESSION[$session_key]['processed'],
                    'batch_processed' => $processed,
                    'success' => $_SESSION[$session_key]['success'],
                    'failed' => $_SESSION[$session_key]['failed'],
                    'skipped' => $_SESSION[$session_key]['skipped'],
                    'has_more' => $_SESSION[$session_key]['processed'] < $_SESSION[$session_key]['total'],
                    'next_offset' => $offset + $processed,
                    'errors' => $_SESSION[$session_key]['errors'],
                    'value' => $value,
                    'unit' => $unit
                ]
            ];

            exit(json_encode($result, JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 补偿时间错误: " . $e->getMessage() . "\n", 3, "../logs/error.log");
            exit(json_encode([
                'code' => -1,
                'msg' => "补偿失败: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'getorders':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        try {
            // 构建查询条件
            $where = [];
            $where[] = "o.username='" . $DB->escape($subconf['username']) . "'";
            
            // 订单号筛选
            if(!empty($_GET['order_no'])) {
                $where[] = "o.order_no LIKE '%" . $DB->escape($_GET['order_no']) . "%'";
            }
            
            // 应用名称筛选
            if(!empty($_GET['appname'])) {
                $where[] = "a.appname LIKE '%" . $DB->escape($_GET['appname']) . "%'";
            }
            
            // 账号筛选
            if(!empty($_GET['account'])) {
                $where[] = "o.account LIKE '%" . $DB->escape($_GET['account']) . "%'";
            }
            
            // 支付状态筛选
            if(isset($_GET['status']) && $_GET['status'] !== '') {
                $where[] = "o.status = '" . $DB->escape($_GET['status']) . "'";
            }
            
            // 支付方式筛选
            if(!empty($_GET['pay_type'])) {
                $where[] = "o.pay_type = '" . $DB->escape($_GET['pay_type']) . "'";
            }
            
            // 创建时间范围筛选
            if(!empty($_GET['create_time'])) {
                $time_range = explode(' - ', $_GET['create_time']);
                if(count($time_range) == 2) {
                    $where[] = "o.create_time BETWEEN '" . $DB->escape($time_range[0]) . "' AND '" . $DB->escape($time_range[1]) . "'";
                }
            }
            
            $where_clause = implode(' AND ', $where);

            // 获取总数
            $count_sql = "SELECT COUNT(*) as total FROM orders o 
                         LEFT JOIN application a ON o.appcode = a.appcode 
                         LEFT JOIN packages p ON o.package_id = p.id 
                         WHERE $where_clause";
            $count_result = $DB->selectRow($count_sql);
            $total = $count_result ? intval($count_result['total']) : 0;

            // 获取订单列表
            $sql = "SELECT o.*, a.appname, p.package_name 
                   FROM orders o 
                   LEFT JOIN application a ON o.appcode = a.appcode 
                   LEFT JOIN packages p ON o.package_id = p.id 
                   WHERE $where_clause
                   ORDER BY o.create_time DESC 
                   LIMIT $offset, $limit";
            
            $orders = $DB->select($sql);
            $orders = $orders ?: [];
            
            exit(json_encode([
                'code' => 0,
                'msg' => '',
                'count' => $total,
                'data' => $orders
            ], JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            exit(json_encode([
                'code' => 1,
                'msg' => '获取订单失败：' . $e->getMessage(),
                'count' => 0,
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'delorders':
        try {
            if (!isset($_POST['order_nos']) || !is_array($_POST['order_nos'])) {
                throw new Exception('参数错误');
            }

            // 过滤并转义订单号
            $order_nos = array_map(function($order_no) use ($DB) {
                return $DB->escape($order_no);
            }, $_POST['order_nos']);

            if (empty($order_nos)) {
                throw new Exception('未选择要删除的订单');
            }

            // 构建 IN 查询条件
            $order_nos_str = "'" . implode("','", $order_nos) . "'";
            
            // 删除订单
            $sql = "DELETE FROM orders WHERE order_no IN ({$order_nos_str})";
            $result = $DB->exe($sql);

            if ($result !== false) {
                exit(json_encode([
                    'code' => 1,
                    'msg' => '删除成功'
                ], JSON_UNESCAPED_UNICODE));
            } else {
                throw new Exception('删除失败');
            }

        } catch (Exception $e) {
            exit(json_encode([
                'code' => -1,
                'msg' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case "compensatetime":
        try {
            // 验证和获取必要参数
            if(!isset($_POST['app']) || !isset($_POST['expire_filter']) || 
               !isset($_POST['value']) || !isset($_POST['unit'])) {
                throw new Exception('缺少必要参数');
            }

            $app = SecurityFilter::filterInput($_POST['app']);
            $expire_filter = SecurityFilter::filterInput($_POST['expire_filter']);
            $value = floatval($_POST['value']);
            $unit = SecurityFilter::filterInput($_POST['unit']);

            // 验证参数有效性
            if(empty($app)) {
                throw new Exception('请选择应用');
            }
            if($value <= 0) {
                throw new Exception('补偿时间必须大于0');
            }
            if(!in_array($unit, ['days', 'hours', 'minutes'])) {
                throw new Exception('无效的时间单位');
            }

            // 获取应用信息
            $app_info = $DB->selectRow("SELECT * FROM application WHERE appcode='" . 
                $DB->escape($app) . "' AND username='" . $DB->escape($subconf['username']) . "'");
            if(!$app_info) {
                throw new Exception('应用不存在或无权限访问');
            }

            // 获取服务器信息
            $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . 
                $DB->escape($app_info['serverip']) . "'");
            if(!$server) {
                throw new Exception('服务器信息不存在');
            }

            // 获取分页参数
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 20; // 减小批次大小

            // 使用session来防止重复处理
            $session_key = "compensate_{$app}_{$value}_{$unit}_" . date('Y-m-d');
            if($offset == 0) {
                // 如果是新的补偿任务，清除之前的session
                if(isset($_SESSION[$session_key])) {
                    unset($_SESSION[$session_key]);
                }
            } else {
                // 检查是否已经处理过这个offset
                if(isset($_SESSION[$session_key]['processed_offsets']) && 
                   in_array($offset, $_SESSION[$session_key]['processed_offsets'])) {
                    // 获取最终的统计数据
                    $final_stats = [
                        'code' => 1,  // 改为1，表示成功
                        'msg' => '补偿处理已完成',
                        'details' => [
                            'total' => $_SESSION[$session_key]['total'],
                            'total_processed' => $_SESSION[$session_key]['total'], // 使用total作为total_processed
                            'success' => $_SESSION[$session_key]['success'],
                            'failed' => $_SESSION[$session_key]['failed'],
                            'skipped' => $_SESSION[$session_key]['skipped'],
                            'errors' => $_SESSION[$session_key]['errors'],
                            'value' => $value,
                            'unit' => $unit,
                            'has_more' => false  // 表示处理已完成
                        ]
                    ];
                    exit(json_encode($final_stats, JSON_UNESCAPED_UNICODE));
                }
            }

            // 获取用户列表
            $users_generator = SerchearchAllServer($app, "", $DB);
            if(!$users_generator) {
                throw new Exception('获取用户列表失败');
            }

            // 初始化或获取统计数据
            if(!isset($_SESSION[$session_key])) {
                $_SESSION[$session_key] = [
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'errors' => array(),
                    'processed_offsets' => array()
                ];

                // 计算总用户数
                foreach($users_generator as $users) {
                    if(is_array($users)) {
                        $_SESSION[$session_key]['total'] += count($users);
                    }
                }
            }

            // 重新获取用户列表用于处理
            $users_generator = SerchearchAllServer($app, "", $DB);
            $current_time = date('Y-m-d H:i:s');
            $current_offset = 0;
            $processed = 0;

            // 处理用户
            foreach($users_generator as $users) {
                if(!is_array($users) || empty($users)) {
                    continue;
                }

                foreach($users as $user) {
                    // 跳过不在当前批次的用户
                    if($current_offset++ < $offset) {
                        continue;
                    }

                    // 达到批次大小时停止
                    if($processed >= $batch_size) {
                        break 2;
                    }

                    try {
                        // 每处理5个用户就刷新一次session，避免session锁定
                        if($processed > 0 && $processed % 5 == 0) {
                            session_write_close();
                            session_start();
                        }

                        if(!isset($user['user']) || !isset($user['disabletime'])) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        $is_expired = strtotime($user['disabletime']) < time();

                        // 根据过滤条件跳过不符合的用户
                        if($expire_filter == 'expired' && !$is_expired) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }
                        if($expire_filter == 'unexpired' && $is_expired) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        // 计算新的到期时间
                        $compensation = "+{$value} {$unit}";
                        $new_time = $is_expired 
                            ? date('Y-m-d H:i:s', strtotime($current_time . " " . $compensation))
                            : date('Y-m-d H:i:s', strtotime($user['disabletime'] . " " . $compensation));

                        // 更新用户时间
                        $update_result = UserUpdate(
                            $server["password"],
                            $server["cport"],
                            $server["ip"],
                            $user["user"],
                            $user["pwd"],
                            $new_time,
                            $user["connection"] ?? "-1",
                            $user["bandwidthup"] ?? "-1",
                            $user["bandwidthdown"] ?? "-1",
                            "0"
                        );

                        if($update_result && isset($update_result['code']) && $update_result['code'] == "1") {
                            $_SESSION[$session_key]['success']++;
                        } else {
                            throw new Exception(isset($update_result['msg']) ? $update_result['msg'] : '未知错误');
                        }

                        $processed++;
                        $_SESSION[$session_key]['processed']++;

                    } catch (Exception $e) {
                        $_SESSION[$session_key]['failed']++;
                        $_SESSION[$session_key]['errors'][] = "用户 {$user['user']} 补偿失败: " . $e->getMessage();
                        $processed++;
                        $_SESSION[$session_key]['processed']++;
                    }
                }
            }

            // 记录已处理的offset
            $_SESSION[$session_key]['processed_offsets'][] = $offset;

            // 返回结果
            $result = [
                'code' => 1,
                'msg' => sprintf(
                    "批次处理完成：处理%d个账号，成功%d个，失败%d个", 
                    $processed,
                    $_SESSION[$session_key]['success'],
                    $_SESSION[$session_key]['failed']
                ),
                'details' => [
                    'total' => $_SESSION[$session_key]['total'],
                    'total_processed' => $_SESSION[$session_key]['processed'],
                    'batch_processed' => $processed,
                    'success' => $_SESSION[$session_key]['success'],
                    'failed' => $_SESSION[$session_key]['failed'],
                    'skipped' => $_SESSION[$session_key]['skipped'],
                    'has_more' => $_SESSION[$session_key]['processed'] < $_SESSION[$session_key]['total'],
                    'next_offset' => $offset + $processed,
                    'errors' => $_SESSION[$session_key]['errors'],
                    'value' => $value,
                    'unit' => $unit
                ]
            ];

            exit(json_encode($result, JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 补偿时间错误: " . $e->getMessage() . "\n", 3, "../logs/error.log");
            exit(json_encode([
                'code' => -1,
                'msg' => "补偿失败: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'packagetable':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = ($page - 1) * $limit;
        
        try {
            // 构建查询条件
            $where = [];
            $where[] = "p.appcode IN (SELECT appcode FROM application WHERE username='" . $DB->escape($subconf['username']) . "')";
            
            // 应用筛选
            if(!empty($_GET['appcode'])) {
                $where[] = "p.appcode = '" . $DB->escape($_GET['appcode']) . "'";
            }
            
            // 套餐名称筛选
            if(!empty($_GET['package_name'])) {
                $where[] = "p.package_name LIKE '%" . $DB->escape($_GET['package_name']) . "%'";
            }
            
            $where_clause = implode(' AND ', $where);

            // 获取总数
            $count_sql = "SELECT COUNT(*) as total FROM packages p WHERE $where_clause";
            $count_result = $DB->selectRow($count_sql);
            $total = $count_result ? intval($count_result['total']) : 0;

            // 获取套餐列表
            $sql = "SELECT p.*, a.appname 
                   FROM packages p 
                   LEFT JOIN application a ON p.appcode = a.appcode 
                   WHERE $where_clause 
                   ORDER BY p.id DESC 
                   LIMIT $offset, $limit";
            
            $packages = $DB->select($sql);
            $packages = $packages ?: [];
            
            exit(json_encode([
                'code' => 0,
                'msg' => '',
                'count' => $total,
                'data' => $packages
            ], JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            exit(json_encode([
                'code' => 1,
                'msg' => '获取套餐列表失败：' . $e->getMessage(),
                'count' => 0,
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'updatestatus':
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
            
            if(!$id) {
                exit(json_encode(['code' => 0, 'msg' => '参数错误']));
            }
            
            // 验证权限
            $package = $DB->selectRow("SELECT p.* FROM packages p 
                                     LEFT JOIN application a ON p.appcode = a.appcode 
                                     WHERE p.id = $id AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$package) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作此套餐']));
            }
            
            $result = $DB->update('packages', ['status' => $status], "id = $id");
            if($result !== false) {
                WriteLog("修改套餐状态", "套餐ID: {$id}, 状态: " . ($status ? '启用' : '禁用'), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '更新成功']));
        } else {
                exit(json_encode(['code' => 0, 'msg' => '更新失败']));
        }
        } catch (Exception $e) {
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;

    case 'updatepackage':
        try {
            // 验证必填参数
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $field = isset($_POST['field']) ? trim($_POST['field']) : '';
            $value = isset($_POST['value']) ? trim($_POST['value']) : '';
            
            if(!$id || empty($field)) {
                throw new Exception('参数错误');
            }
            
            // 处理时长值
            if($field === 'days') {
                // 解析输入的时长字符串
                if(preg_match('/^(\d+)天(\d+)小时(\d+)分钟$/', $value, $matches)) {
                    $days = floatval($matches[1]);
                    $hours = floatval($matches[2]) / 24;
                    $minutes = floatval($matches[3]) / (24 * 60);
                    $value = $days + $hours + $minutes;
                } else if(preg_match('/^(\d+)小时(\d+)分钟$/', $value, $matches)) {
                    $hours = floatval($matches[1]) / 24;
                    $minutes = floatval($matches[2]) / (24 * 60);
                    $value = $hours + $minutes;
                } else if(preg_match('/^(\d+)分钟$/', $value, $matches)) {
                    $value = floatval($matches[1]) / (24 * 60);
                } else {
                    throw new Exception('时长格式错误');
                }
                
                if($value <= 0 || $value > 365) {
                    throw new Exception('时长必须大于0且不超过365天');
                }
            }
            
            // 验证权限
            $package = $DB->selectRow("SELECT p.* FROM packages p 
                                     LEFT JOIN application a ON p.appcode = a.appcode 
                                     WHERE p.id = $id AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$package) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作此套餐']));
            }
            
            $update = [];
            if(isset($_POST['package_name'])) {
                $update['package_name'] = $_POST['package_name'];
            }
            if(isset($_POST['days'])) {
                $update['days'] = intval($_POST['days']);
            }
            if(isset($_POST['price'])) {
                $update['price'] = floatval($_POST['price']);
            }
            
            if(empty($update)) {
                exit(json_encode(['code' => 0, 'msg' => '无更新内容']));
            }
            
            $result = $DB->update('packages', $update, "id = $id");
            if($result !== false) {
                WriteLog("修改套餐", "套餐ID: {$id}, 更新内容: " . json_encode($update, JSON_UNESCAPED_UNICODE), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '更新成功']));
            } else {
                exit(json_encode(['code' => 0, 'msg' => '更新失败']));
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;

    case 'delpackage':
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if(!$id) {
                exit(json_encode(['code' => 0, 'msg' => '参数错误：ID不能为空']));
            }
            
            // 验证权限并获取套餐信息
            $package = $DB->selectRow("SELECT p.*, a.appname 
                                     FROM packages p 
                                     LEFT JOIN application a ON p.appcode = a.appcode 
                                     WHERE p.id = $id AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$package) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作此套餐或套餐不存在']));
            }
            
            // 执行删除操作
            $delete_sql = "DELETE FROM packages WHERE id = $id AND EXISTS (
                SELECT 1 FROM application a 
                WHERE a.appcode = packages.appcode 
                AND a.username = '" . $DB->escape($subconf['username']) . "'
            )";
            
            $result = $DB->exec($delete_sql);
            if($result !== false) {
                WriteLog("删除套餐", "删除套餐 [{$package['appname']}] {$package['package_name']}", $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '删除成功']));
            } else {
                exit(json_encode(['code' => 0, 'msg' => '删除失败：数据库操作错误']));
            }
        } catch (Exception $e) {
            WriteLog("删除套餐错误", "删除套餐失败 ID: $id, 错误: " . $e->getMessage(), $subconf['username'], $DB);
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;

    case 'delpackages':
        try {
            $ids = isset($_POST['ids']) ? (is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']]) : [];
            if(empty($ids)) {
                exit(json_encode(['code' => 0, 'msg' => '请选择要删除的套餐']));
            }
            
            // 验证权限并获取套餐信息
            $id_str = implode(',', array_map('intval', $ids));
            $packages = $DB->select("SELECT p.*, a.appname 
                                   FROM packages p 
                                   LEFT JOIN application a ON p.appcode = a.appcode 
                                   WHERE p.id IN ($id_str) AND a.username = '" . $DB->escape($subconf['username']) . "'");
            
            if(empty($packages)) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作这些套餐或套餐不存在']));
            }
            
            // 执行删除操作
            $delete_sql = "DELETE FROM packages WHERE id IN ($id_str) AND EXISTS (
                SELECT 1 FROM application a 
                WHERE a.appcode = packages.appcode 
                AND a.username = '" . $DB->escape($subconf['username']) . "'
            )";
            
            $result = $DB->exec($delete_sql);
            if($result !== false) {
                $details = [];
                foreach($packages as $package) {
                    $details[] = "[{$package['appname']}] {$package['package_name']}";
                }
                WriteLog("批量删除套餐", "删除套餐：" . implode('、', $details), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '删除成功']));
            } else {
                exit(json_encode(['code' => 0, 'msg' => '删除失败：数据库操作错误']));
            }
        } catch (Exception $e) {
            WriteLog("批量删除套餐错误", "删除套餐失败 IDs: $id_str, 错误: " . $e->getMessage(), $subconf['username'], $DB);
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;

    case 'addpackage':
        try {
            // 验证必填参数
            $appcode = isset($_POST['appcode']) ? trim($_POST['appcode']) : '';
            $package_name = isset($_POST['package_name']) ? trim($_POST['package_name']) : '';
            $days = isset($_POST['days']) ? floatval($_POST['days']) : 0; // 改为浮点数以支持小数天数
            $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
            
            if(empty($appcode)) {
                exit(json_encode(['code' => 0, 'msg' => '请选择应用']));
            }
            if(empty($package_name)) {
                exit(json_encode(['code' => 0, 'msg' => '请输入套餐名称']));
            }
            if($days <= 0) {
                exit(json_encode(['code' => 0, 'msg' => '时长必须大于0']));
            }
            if($days > 365) { // 统一限制最大365天
                exit(json_encode(['code' => 0, 'msg' => '时长不能超过365天']));
            }
            if($price < 0) {
                exit(json_encode(['code' => 0, 'msg' => '价格不能小于0']));
            }
            
            // 验证权限
            $app = $DB->selectRow("SELECT * FROM application WHERE appcode = '" . $DB->escape($appcode) . "' AND username = '" . $DB->escape($subconf['username']) . "'");
            if(!$app) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作此应用']));
            }
            
            // 检查套餐名称是否重复
            $check = $DB->selectRow("SELECT id FROM packages WHERE appcode = '" . $DB->escape($appcode) . "' AND package_name = '" . $DB->escape($package_name) . "'");
            if($check) {
                exit(json_encode(['code' => 0, 'msg' => '套餐名称已存在']));
            }
            
            // 添加套餐
            $data = [
                'appcode' => $appcode,
                'package_name' => $package_name,
                'days' => $days,
                'price' => $price,
                'status' => 1,
                'addtime' => date('Y-m-d H:i:s')
            ];
            
            $result = $DB->insert('packages', $data);
            if($result !== false) {
                // 转换天数为更友好的显示格式
                $time_display = '';
                if($days >= 1) {
                    $time_display = floor($days) . '天';
                    $hours = ($days - floor($days)) * 24;
                    if($hours > 0) {
                        $time_display .= floor($hours) . '小时';
                        $minutes = ($hours - floor($hours)) * 60;
                        if($minutes > 0) {
                            $time_display .= floor($minutes) . '分钟';
                        }
                    }
                } else {
                    $hours = $days * 24;
                    if($hours >= 1) {
                        $time_display = floor($hours) . '小时';
                        $minutes = ($hours - floor($hours)) * 60;
                        if($minutes > 0) {
                            $time_display .= floor($minutes) . '分钟';
                        }
                    } else {
                        $minutes = $days * 24 * 60;
                        $time_display = floor($minutes) . '分钟';
                    }
                }
                
                WriteLog("添加套餐", "添加套餐 [{$app['appname']}] {$package_name} ({$time_display})", $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '添加成功']));
            } else {
                exit(json_encode(['code' => 0, 'msg' => '添加失败：数据库操作错误']));
            }
        } catch (Exception $e) {
            WriteLog("添加套餐错误", "添加套餐失败，错误: " . $e->getMessage(), $subconf['username'], $DB);
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;

    case 'editpackage':
        try {
            // 验证必填参数
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $package_name = isset($_POST['package_name']) ? trim($_POST['package_name']) : '';
            $days = isset($_POST['days']) ? floatval($_POST['days']) : 0; // 改为浮点数以支持小数天数
            $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            
            if(!$id) {
                exit(json_encode(['code' => 0, 'msg' => '参数错误：ID不能为空']));
            }
            if(empty($package_name)) {
                exit(json_encode(['code' => 0, 'msg' => '请输入套餐名称']));
            }
            if($days <= 0) {
                exit(json_encode(['code' => 0, 'msg' => '时长必须大于0']));
            }
            if($days > 365) { // 统一限制最大365天
                exit(json_encode(['code' => 0, 'msg' => '时长不能超过365天']));
            }
            if($price < 0) {
                exit(json_encode(['code' => 0, 'msg' => '价格不能小于0']));
            }
            
            // 验证权限并获取套餐信息
            $package = $DB->selectRow("SELECT p.*, a.appname 
                                     FROM packages p 
                                     LEFT JOIN application a ON p.appcode = a.appcode 
                                     WHERE p.id = $id AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$package) {
                exit(json_encode(['code' => 0, 'msg' => '无权操作此套餐或套餐不存在']));
            }
            
            // 检查套餐名称是否重复(排除自身)
            $check = $DB->selectRow("SELECT id FROM packages WHERE appcode = '" . $DB->escape($package['appcode']) . "' AND package_name = '" . $DB->escape($package_name) . "' AND id != $id");
            if($check) {
                exit(json_encode(['code' => 0, 'msg' => '套餐名称已存在']));
            }
            
            // 更新套餐
            $data = [
                'package_name' => $package_name,
                'days' => $days,
                'price' => $price,
                'status' => $status
            ];
            
            $result = $DB->update('packages', $data, "id = $id");
            if($result !== false) {
                // 转换天数为更友好的显示格式
                $time_display = '';
                if($days >= 1) {
                    $time_display = floor($days) . '天';
                    $hours = ($days - floor($days)) * 24;
                    if($hours > 0) {
                        $time_display .= floor($hours) . '小时';
                        $minutes = ($hours - floor($hours)) * 60;
                        if($minutes > 0) {
                            $time_display .= floor($minutes) . '分钟';
                        }
                    }
                } else {
                    $hours = $days * 24;
                    if($hours >= 1) {
                        $time_display = floor($hours) . '小时';
                        $minutes = ($hours - floor($hours)) * 60;
                        if($minutes > 0) {
                            $time_display .= floor($minutes) . '分钟';
                        }
                    } else {
                        $minutes = $days * 24 * 60;
                        $time_display = floor($minutes) . '分钟';
                    }
                }
                
                WriteLog("编辑套餐", "编辑套餐 [{$package['appname']}] {$package_name}, 状态: " . ($status ? '启用' : '禁用'), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '编辑成功']));
            } else {
                exit(json_encode(['code' => 0, 'msg' => '编辑失败：数据库操作错误']));
            }
        } catch (Exception $e) {
            WriteLog("编辑套餐错误", "编辑套餐失败 ID: $id, 错误: " . $e->getMessage(), $subconf['username'], $DB);
            exit(json_encode(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]));
        }
        break;
        case "getlog":
            if (isset($_REQUEST['page']) && isset($_REQUEST['limit'])) {
                $sqlpage = isset($_REQUEST['logtime']) != "" ? " and operationdate LIKE '%" . $_REQUEST['logtime'] . "%' " : "1";
                $sql = "SELECT * FROM `log` WHERE operationer=\"" . $subconf['username'] . "\"" . $sqlpage . " ORDER BY operationdate DESC";
                $countpage = $DB->selectRow("select count(*) as num from log where operationer=\"" . $subconf['username'] . "\"" . $sqlpage . "");
                $app = $DB->selectPage($sql, $DB->pageNo = $_REQUEST['page'], $DB->pageRows = $_REQUEST['limit']);
                
                // 不再重新计算序号，直接使用数据库的 logid
                $json = ["code" => "0", "count" => $countpage['num'], "data" => $app, "icon" => 1];
                exit(json_encode($json, JSON_UNESCAPED_UNICODE));
            } else {
                $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
                exit(json_encode($json, JSON_UNESCAPED_UNICODE));
            }
        break;

        case "dellog":
            try {
                if(!isset($_POST['ids']) || !is_array($_POST['ids'])) {
                    throw new Exception('参数错误');
                }

                $ids = array_map('intval', $_POST['ids']);
                if(empty($ids)) {
                    throw new Exception('请选择要删除的日志');
                }

                // 验证所有ID是否属于当前用户
                $validIds = $DB->select("SELECT logid FROM log WHERE logid IN (" . implode(',', $ids) . ") AND operationer='" . $DB->escape($subconf['username']) . "'");
                if(empty($validIds)) {
                    throw new Exception('未找到要删除的日志或无权限删除');
                }

                // 只删除验证过的ID
                $validIdArray = array_column($validIds, 'logid');

                // 获取要删除的日志信息用于记录
                $logs = $DB->select("SELECT * FROM log WHERE logid IN (" . implode(',', $validIdArray) . ")");
                
                // 执行删除
                $result = $DB->delete('log', "logid IN (" . implode(',', $validIdArray) . ")");
                if($result !== false) {
                    // 记录删除操作
                    $logDetails = array_map(function($log) {
                        return sprintf("[%s] %s: %s", 
                            $log['operationdate'],
                            $log['operation'],
                            $log['msg']
                        );
                    }, $logs);
                    
                    WriteLog("删除日志", "批量删除日志：\n" . implode("\n", $logDetails), $subconf['username'], $DB);
                    
                    exit(json_encode([
                        'code' => 1,
                        'msg' => '成功删除 ' . count($validIdArray) . ' 条日志'
                    ], JSON_UNESCAPED_UNICODE));
                } else {
                    throw new Exception('删除失败：数据库操作错误');
                }
            } catch(Exception $e) {
                exit(json_encode([
                    'code' => -1,
                    'msg' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE));
            }
        break;

        case "clearlog":
            try {
                // 获取当前用户的日志总数
                $count = $DB->selectRow("SELECT COUNT(*) as total FROM log WHERE operationer='" . $DB->escape($subconf['username']) . "'");
                if($count && $count['total'] > 0) {
                    // 执行清空操作
                    $result = $DB->delete('log', "operationer='" . $DB->escape($subconf['username']) . "'");
                    if($result !== false) {
                        WriteLog("清空日志", "清空了所有日志记录，共 {$count['total']} 条", $subconf['username'], $DB);
                        exit(json_encode([
                            'code' => 1,
                            'msg' => "成功清空 {$count['total']} 条日志记录"
                        ], JSON_UNESCAPED_UNICODE));
                    } else {
                        throw new Exception('清空失败：数据库操作错误');
                    }
                } else {
                    exit(json_encode([
                        'code' => 1,
                        'msg' => '没有需要清空的日志'
                    ], JSON_UNESCAPED_UNICODE));
                }
            } catch(Exception $e) {
                exit(json_encode([
                    'code' => -1,
                    'msg' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE));
            }
        break;

    case "getuserall":
        $sqlj = "";
        if (isset($_REQUEST['page']) && isset($_REQUEST['limit'])) {
            if ($_REQUEST['user'] == "") {
                if ($_REQUEST['app'] == "") {
                    $ser = SerchearchAllServer("", "", $DB);
                    $user_data = array();
                    while ($ser->valid()) {
                        $current = $ser->current();
                        if (!empty($current)) {
                            array_push($user_data, $current);
                        }
                        $ser->next();
                    }
                    
                    if (empty($user_data)) {
                        $json = ["code" => "0", "count" => 0, "data" => [], "icon" => 1];
                        exit(json_encode($json, JSON_UNESCAPED_UNICODE));
                    }
                    
                    $result = array_reduce($user_data, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, array());
                    
                    $user_updata = array();
                    $current_time = time();
                    foreach ($result as $key => $value) {
                        if (empty($value)) continue;
                        
                        $serverip = isset($value["serverip"]) ? $value["serverip"] : '';
                        $appname = '';
                        if ($serverip) {
                            $app = $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $serverip . "'");
                            $appname = $app ? $app['appname'] : '';
                        }
                        
                        $disabletime = isset($value['autodisable']) ? ($value['autodisable']==0 ? '2099-10-13 14:34:26' : (isset($value['disabletime']) ? $value['disabletime'] : '')) : '';
                        $is_expired = $disabletime ? (strtotime($disabletime) <= $current_time) : false;
                        
                        // 根据expire_filter参数过滤
                        if (isset($_REQUEST['expire_filter']) && $_REQUEST['expire_filter'] !== 'all') {
                            if ($_REQUEST['expire_filter'] === 'expired' && !$is_expired) {
                                continue;
                            }
                            if ($_REQUEST['expire_filter'] === 'unexpired' && $is_expired) {
                                continue;
                            }
                        }
                        
                        $getdata = array(
                            "id" => isset($value['id']) ? $value['id'] : '',
                            "user" => isset($value['user']) ? $value['user'] : '',
                            "pwd" => isset($value['pwd']) ? $value['pwd'] : '',
                            "state" => isset($value['state']) ? $value['state'] : '',
                            "pwdstate" => isset($value['pwdstate']) ? $value['pwdstate'] : '',
                            "disabletime" => $disabletime,
                            "expire" => isset($value['autodisable']) ? ($value['autodisable']==0 ? 0 : (isset($value['expire']) ? $value['expire'] : 0)) : 0,
                            'serverip' => $serverip,
                            'appname' => $appname,
                            "connection" => isset($value['connection']) ? ($value['connection']==-1 ? "无限制" : $value['connection']) : "无限制",
                            "bandwidthup" => isset($value['bandwidthup']) ? ($value['bandwidthup']==-1 ? "无限制" : ($value['bandwidthup']<-1 ? $value['bandwidthup'] : $value['bandwidthup']/1024)) : "无限制",
                            "bandwidthdown" => isset($value['bandwidthdown']) ? ($value['bandwidthdown']==-1 ? "无限制" : ($value['bandwidthdown']<-1 ? $value['bandwidthdown'] : $value['bandwidthdown']/1024)) : "无限制"
                        );
                        array_push($user_updata, $getdata);
                    }
                    $json = ["code" => "0", "count" => count($user_updata), "data" => $user_updata, "icon" => 1];
                    exit(json_encode($json, JSON_UNESCAPED_UNICODE));
                } else {
                    $ser = SerchearchAllServer($_REQUEST['app'], "", $DB);
                    $user_data = array();
                    while ($ser->valid()) {
                        array_push($user_data, $ser->current());
                        $ser->next();
                    }
                    $result = array_reduce($user_data, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, array());
                    $user_updata = array();
                    $current_time = time();
                    foreach ($result as $key => $value) {
                        if (empty($value)) continue;
                        
                        $serverip = isset($value["serverip"]) ? $value["serverip"] : '';
                        $appname = '';
                        if ($serverip) {
                            $app = $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $serverip . "'");
                            $appname = $app ? $app['appname'] : '';
                        }
                        
                        $disabletime = isset($value['autodisable']) ? ($value['autodisable']==0 ? '2099-10-13 14:34:26' : (isset($value['disabletime']) ? $value['disabletime'] : '')) : '';
                        $is_expired = $disabletime ? (strtotime($disabletime) <= $current_time) : false;
                        
                        // 根据expire_filter参数过滤
                        if (isset($_REQUEST['expire_filter']) && $_REQUEST['expire_filter'] !== 'all') {
                            if ($_REQUEST['expire_filter'] === 'expired' && !$is_expired) {
                                continue;
                            }
                            if ($_REQUEST['expire_filter'] === 'unexpired' && $is_expired) {
                                continue;
                            }
                        }
                        
                        $getdata = array(
                            "id" => $value['id'],
                            "user" => $value['user'],
                            "pwd" => $value['pwd'],
                            "state" => $value['state'],
                            "pwdstate" => $value['pwdstate'],
                            "disabletime" => $disabletime,
                            "expire" => $value['autodisable']==0?0:$value['expire'],
                            "user" => $value['user'],
                            'serverip' => $value["serverip"],
                            'appname' => $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $value["serverip"] . "'")['appname'],
                            "connection"=>$value['connection']==-1?"无限制":$value['connection'],
                            "bandwidthup"=>$value['bandwidthup']==-1?"无限制":($value['bandwidthup']<-1?$value['bandwidthup']:$value['bandwidthup']/1024),
                            "bandwidthdown"=>$value['bandwidthdown']==-1?"无限制":($value['bandwidthdown']<-1?$value['bandwidthdown']:$value['bandwidthdown']/1024)
                        );
                        array_push($user_updata, $getdata);
                    }
                    $json = ["code" => "0", "count" => count($user_updata), "data" => $user_updata, "icon" => 1];
                    exit(json_encode($json, JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ($_REQUEST['app'] != "") {
                    $ser = SerchearchAllServer($_REQUEST['app'], $_REQUEST['user'], $DB);
                    $user_data = array();
                    while ($ser->valid()) {
                        array_push($user_data, $ser->current());
                        $ser->next();
                    }
                    $result = array_reduce($user_data, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, array());
                    $user_updata = array();
                    $current_time = time();
                    foreach ($result as $key => $value) {
                        if (empty($value)) continue;
                        
                        $serverip = isset($value["serverip"]) ? $value["serverip"] : '';
                        $appname = '';
                        if ($serverip) {
                            $app = $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $serverip . "'");
                            $appname = $app ? $app['appname'] : '';
                        }
                        
                        $disabletime = isset($value['autodisable']) ? ($value['autodisable']==0 ? '2099-10-13 14:34:26' : (isset($value['disabletime']) ? $value['disabletime'] : '')) : '';
                        $is_expired = $disabletime ? (strtotime($disabletime) <= $current_time) : false;
                        
                        // 根据expire_filter参数过滤
                        if (isset($_REQUEST['expire_filter']) && $_REQUEST['expire_filter'] !== 'all') {
                            if ($_REQUEST['expire_filter'] === 'expired' && !$is_expired) {
                                continue;
                            }
                            if ($_REQUEST['expire_filter'] === 'unexpired' && $is_expired) {
                                continue;
                            }
                        }
                        
                        $getdata = array(
                            "id" => $value['id'],
                            "user" => $value['user'],
                            "pwd" => $value['pwd'],
                            "state" => $value['state'],
                            "pwdstate" => $value['pwdstate'],
                            "disabletime" => $disabletime,
                            "expire" => $value['autodisable']==0?0:$value['expire'],
                            "user" => $value['user'],
                            'serverip' => $value["serverip"],
                            'appname' => $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $value["serverip"] . "'")['appname'],
                            "connection"=>$value['connection']==-1?"无限制":$value['connection'],
                            "bandwidthup"=>$value['bandwidthup']==-1?"无限制":($value['bandwidthup']<-1?$value['bandwidthup']:$value['bandwidthup']/1024),
                            "bandwidthdown"=>$value['bandwidthdown']==-1?"无限制":($value['bandwidthdown']<-1?$value['bandwidthdown']:$value['bandwidthdown']/1024)
                        );
                        array_push($user_updata, $getdata);
                    }
                    $json = ["code" => "0", "count" => count($user_updata), "data" => $user_updata, "icon" => 1];
                    exit(json_encode($json, JSON_UNESCAPED_UNICODE));
                } else {
                    $ser = SerchearchAllServer($_REQUEST['app'], $_REQUEST['user'], $DB);
                    $user_data = array();
                    while ($ser->valid()) {
                        array_push($user_data, $ser->current());
                        $ser->next();
                    }
                    $result = array_reduce($user_data, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, array());
                    $user_updata = array();
                    $current_time = time();
                    foreach ($result as $key => $value) {
                        if (empty($value)) continue;
                        
                        $serverip = isset($value["serverip"]) ? $value["serverip"] : '';
                        $appname = '';
                        if ($serverip) {
                            $app = $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $serverip . "'");
                            $appname = $app ? $app['appname'] : '';
                        }
                        
                        $disabletime = isset($value['autodisable']) ? ($value['autodisable']==0 ? '2099-10-13 14:34:26' : (isset($value['disabletime']) ? $value['disabletime'] : '')) : '';
                        $is_expired = $disabletime ? (strtotime($disabletime) <= $current_time) : false;
                        
                        // 根据expire_filter参数过滤
                        if (isset($_REQUEST['expire_filter']) && $_REQUEST['expire_filter'] !== 'all') {
                            if ($_REQUEST['expire_filter'] === 'expired' && !$is_expired) {
                                continue;
                            }
                            if ($_REQUEST['expire_filter'] === 'unexpired' && $is_expired) {
                                continue;
                            }
                        }
                        
                        $getdata = array(
                            "id" => $value['id'],
                            "user" => $value['user'],
                            "pwd" => $value['pwd'],
                            "state" => $value['state'],
                            "pwdstate" => $value['pwdstate'],
                            "disabletime" => $disabletime,
                            "expire" => $value['autodisable']==0?0:$value['expire'],
                            "user" => $value['user'],
                            'serverip' => $value["serverip"],
                            'appname' => $DB->selectRow("SELECT appname FROM application WHERE serverip='" . $value["serverip"] . "'")['appname'],
                            "connection"=>$value['connection']==-1?"无限制":$value['connection'],
                            "bandwidthup"=>$value['bandwidthup']==-1?"无限制":($value['bandwidthup']<-1?$value['bandwidthup']:$value['bandwidthup']/1024),
                            "bandwidthdown"=>$value['bandwidthdown']==-1?"无限制":($value['bandwidthdown']<-1?$value['bandwidthdown']:$value['bandwidthdown']/1024)
                        );
                        array_push($user_updata, $getdata);
                    }
                    $json = ["code" => "0", "count" => count($user_updata), "data" => $user_updata, "icon" => 1];
                    exit(json_encode($json, JSON_UNESCAPED_UNICODE));
                }
            }
        } else {
            $json = ["code" => "-1", "count" => null, "data" => "参数错误！", "icon" => "5"];
            exit(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "seldeluser":
        $deldata = $_POST['item'];
        if ($deldata == null || !(isset($deldata)) || empty($deldata)) {
            exit(json_encode([
                "code" => "-1",
                "msg" => "删除失败：参数为空!"
            ], JSON_UNESCAPED_UNICODE));
        }
        
        $znum = count($deldata);
        $zxnum = 0;
        $failedUsers = [];
        
        $scheduler = new Scheduler;
        foreach ($deldata as $key => $value) {
            // 检查服务器是否存在
            $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $value['serverip'] . "'");
            if (!$server) {
                $failedUsers[] = $value['user'] . "(服务器不存在)";
                continue;
            }
            
            $scheduler->addTask(DelUser($value['user'], $value['serverip'], $DB));
            $res = $scheduler->run();
            if ($res) {
                $zxnum = $zxnum + 1;
            } else {
                $failedUsers[] = $value['user'];
            }
        }
        
        // 记录操作日志
        $userList = array_map(function($item) {
            return $item['user'] . '(' . $item['serverip'] . ')';
        }, $deldata);
        WriteLog("批量删除用户", "删除用户：" . implode('、', $userList), $subconf['username'], $DB);
        
        // 返回结果
        if ($znum == $zxnum) {
            exit(json_encode([
                "code" => "1",
                "msg" => "已成功执行全部删除!"
            ], JSON_UNESCAPED_UNICODE));
        } else {
            $failMsg = !empty($failedUsers) ? "，失败用户：" . implode('、', $failedUsers) : '';
            exit(json_encode([
                "code" => "0",
                "msg" => "删除部分完成，成功：{$zxnum}，失败：" . ($znum - $zxnum) . $failMsg
            ], JSON_UNESCAPED_UNICODE));
        }
        break;
    case "userupdate":
        $usermodel = $_POST["usermodel"];
        if (isset($usermodel) && is_array($usermodel) && !empty($usermodel)) {
            // 保持用户名原始大小写
            $usermodel["olduser"] = trim($usermodel["olduser"]); // 只去除空格，不转换大小写
            $usermodel["newuser"] = trim($usermodel["newuser"]); // 只去除空格，不转换大小写
            
            if(!empty($usermodel["connection"])&&!is_numeric($usermodel["connection"])){
                exit(json_encode( $code = [ "code" => "-1",  "msg" => "输入类型错误"], JSON_UNESCAPED_UNICODE));
            }
            if(!empty($usermodel["bandwidthup"])&&!is_numeric($usermodel["bandwidthup"])){
                exit(json_encode( $code = [ "code" => "-1",  "msg" => "输入类型错误"], JSON_UNESCAPED_UNICODE));
            }
            if(!empty($usermodel["bandwidthdown"])&&!is_numeric($usermodel["bandwidthdown"])){
                exit(json_encode( $code = [ "code" => "-1",  "msg" => "输入类型错误"], JSON_UNESCAPED_UNICODE));
            }
            if($usermodel["connection"]<=0) {
                $usermodel["connection"]=-1;
            }
            $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $usermodel['serverip'] . "'");
            $result = UserUpdate(
                $server["password"], 
                $server["cport"], 
                $server["ip"], 
                $usermodel["olduser"], // 使用原始大小写
                $usermodel["pwd"], 
                $usermodel["day"],
                $usermodel["connection"],
                $usermodel["bandwidthup"]<=0?-1:$usermodel["bandwidthup"]*1024,
                $usermodel["bandwidthdown"]<=0?-1:$usermodel["bandwidthdown"]*1024,
                "0",
                $usermodel["newuser"] // 使用原始大小写
            );
            
            $logContent = sprintf(
                "用户编辑 [%s] - 新用户名：%s，密码：%s，天数：%s，连接数：%s，上行带宽：%s，下行带宽：%s，服务器：%s",
                $usermodel["olduser"],
                $usermodel["newuser"] ?? $usermodel["olduser"],
                str_repeat('*', strlen($usermodel["pwd"])),
                $usermodel["day"],
                $usermodel["connection"] == -1 ? "无限制" : $usermodel["connection"],
                $usermodel["bandwidthup"] <= 0 ? "无限制" : $usermodel["bandwidthup"] . "KB",
                $usermodel["bandwidthdown"] <= 0 ? "无限制" : $usermodel["bandwidthdown"] . "KB",
                $usermodel["serverip"]
            );
            WriteLog("用户编辑", $logContent, $subconf['username'], $DB);
            exit(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        break;
    case "adduser":
        if (!isset($_POST['userdata'])) {
            $code = [
                "code" => "-1",
                "msg" => "参数错误"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }
        $userdata = $_POST['userdata'];
        
        // 获取应用信息
        $app_info = $DB->selectRow("SELECT * FROM application WHERE appcode='" . addslashes($userdata['app']) . "'");
        if(!$app_info) {
            $code = [
                "code" => "-1",
                "msg" => "应用不存在"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }

        // 获取服务器信息
        $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . addslashes($app_info['serverip']) . "'");
        if(!$server) {
            $code = [
                "code" => "-1", 
                "msg" => "服务器不存在"
            ];
            exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        }

        // 处理到期时间
        $expire_time = '';
        if($userdata['expire'] == -1) {
            $expire_time = $userdata['use_date'] . " 23:59:59";
        } else {
            $expire_time = date('Y-m-d H:i:s', strtotime('+' . $userdata['expire'] . ' day'));
        }

        // 准备要添加的用户数据
        $apps_to_add = array($app_info);

        // 如果启用了继承且是主应用
        if(isset($userdata['inherit']) && $userdata['inherit'] == '1') {
            // 获取继承配置
            $inherit_config = $DB->selectRow("SELECT inherit_enabled, inherit_groups FROM sub_admin WHERE username='" . $DB->escape($subconf['username']) . "'");
            
            if($inherit_config && $inherit_config['inherit_enabled']) {
                // 解码继承组配置
                $inherit_groups = json_decode($inherit_config['inherit_groups'], true);
                if($inherit_groups && isset($inherit_groups['groups'])) {
                    foreach($inherit_groups['groups'] as $group) {
                        // 检查当前应用是否为主应用
                        if(in_array($userdata['app'], $group['main_apps'])) {
                            // 获取该组下所有继承应用的信息
                            if(!empty($group['inherit_apps'])) {
                                $inherit_apps_str = "'" . implode("','", array_map(function($app) use ($DB) {
                                    return $DB->escape($app);
                                }, $group['inherit_apps'])) . "'";
                                
                                $inherit_apps = $DB->select("SELECT * FROM application WHERE appcode IN ($inherit_apps_str)");
                                if($inherit_apps) {
                                    $apps_to_add = array_merge($apps_to_add, $inherit_apps);
                                }
                            }
                        }
                    }
                }
            }
        }

        // 开始添加用户
        $success_count = 0;
        $failed_apps = array();

        foreach($apps_to_add as $app) {
            // 获取对应服务器信息
            $app_server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . addslashes($app['serverip']) . "'");
            if(!$app_server) {
                $failed_apps[] = $app['appname'] . "(服务器不存在)";
                continue;
            }

            // 调用添加用户函数
            $result = AddUser(
                $app_server["ip"],
                $app_server["password"],
                $app_server["cport"],
                array(
                    'user' => $userdata['user'],
                    'pwd' => $userdata['pwd'],
                    'expire' => $expire_time,
                    'connection' => '-1',
                    'bandwidthup' => '-1',
                    'bandwidthdown' => '-1'
                )
            );

            if($result && isset($result['code']) && $result['code'] == "1") {
                $success_count++;
            } else {
                $failed_apps[] = $app['appname'] . (isset($result['msg']) ? "(" . $result['msg'] . ")" : "");
            }
        }

        // 返回结果
        if($success_count == count($apps_to_add)) {
            $code = [
                "code" => "1",
                "msg" => "添加成功"
            ];
            WriteLog("添加用户", "添加了用户: " . $userdata['user'], $subconf['username'], $DB);
        } else if($success_count > 0) {
            $code = [
                "code" => "1",
                "msg" => "部分添加成功，失败的应用：" . implode(", ", $failed_apps)
            ];
            WriteLog("添加用户", "部分添加成功，用户: " . $userdata['user'] . "，失败应用：" . implode(", ", $failed_apps), $subconf['username'], $DB);
        } else {
            $code = [
                "code" => "-1",
                "msg" => "添加失败: " . implode(", ", $failed_apps)
            ];
            WriteLog("添加用户", "添加失败，用户: " . $userdata['user'] . "，原因：" . implode(", ", $failed_apps), $subconf['username'], $DB);
        }
        exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        break;
    case "siteinfo":
        $ser = SerchearchAllServer("", "", $DB);
        $user_data = array();
        while ($ser->valid()) {
            array_push($user_data, $ser->current());
            $ser->next();
        }
        $serverlist=$DB->selectRow("select COUNT(*) as count from server_list");
       
        $lognum=$DB->selectRow("select COUNT(*) as count from log");
        $todaykami=$DB->selectRow("select COUNT(*) as count from kami where use_date>DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00') and use_date<DATE_ADD(DATE_ADD(DATE_ADD(DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00'),INTERVAL 23 HOUR),INTERVAL 59 MINUTE),INTERVAL 59 SECOND) and state='1'");
        $kaminum=$DB->selectRow("select COUNT(*) as count from kami");
        $appnum=$DB->selectRow("select COUNT(*) as count from application");
        $ordernum=$DB->selectRow("select COUNT(*) as count from orders where username='".$subconf['username']."'");
        
        $json= [
            "code" => "1",
            "msg" => "获取成功!",
            "usernum"=>count($user_data[0]),
            "servercount"=>$serverlist["count"],
            "lognum"=>$lognum["count"],
            "todaykami"=> $todaykami["count"],
            "kaminum"=>$kaminum["count"],
            "appnum"=>$appnum["count"],
            "ordernum"=>$ordernum["count"]
        ];
        exit(json_encode($json, JSON_UNESCAPED_UNICODE));
    break;
    case 'upswitchuser':
        $usermodel = $_POST["usermodel"];
        if (isset($usermodel) && is_array($usermodel)) {
            $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $usermodel['serverip'] . "'"); //$ip['serverip']服务器IP
            $code = UserUpdate($server["password"], $server["cport"], $server["ip"], $usermodel["user"], $usermodel["pwd"], $usermodel["day"], $usermodel["connection"],$usermodel["bandwidthup"],$usermodel["bandwidthdown"],$usermodel["sw"]);
        } else {
            $code = [
                "code" => "-1",
                "msg" => "失败参数为空或者其他错误!",
            ];
        }
        exit(json_encode($code, JSON_UNESCAPED_UNICODE));
        break;
        case "updateset":
            try {
                // 验证必要参数
                if(!isset($_POST['user_key']) || !isset($_POST['siteurl'])) {
                    throw new Exception('缺少必要参数');
                }

                // 基础数据准备
                $update = array(
                    'hostname' => $_POST['user_key'],
                    'siteurl' => $_POST['siteurl'],
                    'img' => isset($_POST['img']) ? $_POST['img'] : '',
                    'show_online_pay' => isset($_POST['show_online_pay']) ? 1 : 0,
                    'show_kami_pay' => isset($_POST['show_kami_pay']) ? 1 : 0,
                    'show_kami_reg' => isset($_POST['show_kami_reg']) ? 1 : 0,
                    'show_user_search' => isset($_POST['show_user_search']) ? 1 : 0,
                    'show_kami_query' => isset($_POST['show_kami_query']) ? 1 : 0,
                    'show_change_pwd' => isset($_POST['show_change_pwd']) ? 1 : 0,
                    'ggswitch' => isset($_POST['ggswitch']) ? 1 : 0,
                    'kfswitch' => isset($_POST['kfswitch']) ? 1 : 0,
                    'panswitch' => isset($_POST['panswitch']) ? 1 : 0,
                    'bgswitch' => isset($_POST['bgswitch']) ? 1 : 0,
                    'multi_domain' => isset($_POST['multi_domain']) ? 1 : 0,
                    'inherit_enabled' => isset($_POST['inherit_enabled']) ? 1 : 0,
                    'show_inherit_apps' => isset($_POST['show_inherit_apps']) ? 1 : 0,
                    'wzgg' => isset($_POST['wzgg']) ? $_POST['wzgg'] : '',
                    'kf' => isset($_POST['kf']) ? $_POST['kf'] : '',
                    'pan' => isset($_POST['pan']) ? $_POST['pan'] : '',
                    'dayimg' => isset($_POST['dayimg']) ? $_POST['dayimg'] : '',
                    'nightimg' => isset($_POST['nightimg']) ? $_POST['nightimg'] : '',
                    'domain_list' => isset($_POST['domain_list']) ? $_POST['domain_list'] : '',
                    'inherit_groups' => isset($_POST['inherit_config']) ? $_POST['inherit_config'] : '{"groups":[]}'
                );

                // 更新数据
                $where = "username='" . $DB->escape($subconf['username']) . "'";
                $result = $DB->update('sub_admin', $update, $where);

                if($result !== false) {
                    WriteLog("更新设置", "更新了网站设置", $subconf['username'], $DB);
                    exit(json_encode([
                        'code' => 1,
                        'msg' => '保存成功'
                    ], JSON_UNESCAPED_UNICODE));
                } else {
                    throw new Exception('数据库更新失败');
                }

            } catch(Exception $e) {
                exit(json_encode([
                    'code' => -1,
                    'msg' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE));
            }
            break;
    case "updatepwd":
        try {
            // 验证用户是否登录
            if($islogin != 1) {
                throw new Exception('请先登录');
            }

            // 验证必要参数
            if(!isset($_POST['oldpwd'])) {
                throw new Exception('请输入原密码');
            }
            if(!isset($_POST['newpwd'])) {
                throw new Exception('请输入新密码');
            }

            $oldpwd = trim($_POST['oldpwd']);
            $newpwd = trim($_POST['newpwd']);

            // 验证密码不能为空
            if(empty($oldpwd)) {
                throw new Exception('原密码不能为空');
            }
            if(empty($newpwd)) {
                throw new Exception('新密码不能为空');
            }

            // 验证新密码长度
            if(strlen($newpwd) < 6) {
                throw new Exception('新密码长度不能小于6位');
            }

            // 验证旧密码是否正确
            $user = $DB->selectRow("SELECT * FROM sub_admin WHERE username='" . $DB->escape($subconf['username']) . "'");
            if(!$user) {
                throw new Exception('用户不存在');
            }

            // 检查原密码 - 支持明文密码和MD5密码
            if($user['password'] !== $oldpwd && $user['password'] !== md5($oldpwd)) {
                throw new Exception('原密码错误');
            }

            // 验证新旧密码不能相同
            if($oldpwd === $newpwd || ($user['password'] === md5($newpwd))) {
                throw new Exception('新密码不能与原密码相同');
            }

            // 更新密码 - 使用MD5加密存储
            $sql = "UPDATE sub_admin SET password='" . md5($newpwd) . "' WHERE username='" . $DB->escape($subconf['username']) . "'";
            $result = $DB->exe($sql);

            if($result !== false) {
                WriteLog("修改密码", "密码修改成功", $subconf['username'], $DB);
                exit(json_encode([
                    'code' => 1,
                    'msg' => '密码修改成功'
                ], JSON_UNESCAPED_UNICODE));
            } else {
                throw new Exception('密码修改失败');
            }

        } catch(Exception $e) {
            exit(json_encode([
                'code' => -1,
                'msg' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;
    case 'start_compensate':
        // 生成任务ID并保存任务信息到session
        $task_id = uniqid('comp_');
        $_SESSION['compensate_task'] = [
            'id' => $task_id,
            'start_time' => time(),
            'total' => 0,
            'processed' => 0,
            'completed' => false,
            'params' => [
                'app' => $_POST['app'],
                'expire_filter' => $_POST['expire_filter'],
                'value' => $_POST['value'],
                'unit' => $_POST['unit']
            ]
        ];
        
        // 启动异步处理
        startAsyncCompensate($task_id);
        
        exit(json_encode([
            'code' => 1,
            'msg' => '任务已启动',
            'task_id' => $task_id
        ]));
        break;

    case 'check_compensate_progress':
        $task_id = $_POST['task_id'];
        $task = $_SESSION['compensate_task'] ?? null;
        
        if(!$task || $task['id'] !== $task_id) {
            exit(json_encode([
                'code' => -1,
                'msg' => '任务不存在'
            ]));
        }
        
        exit(json_encode([
            'code' => 1,
            'processed' => $task['processed'],
            'total' => $task['total'],
            'completed' => $task['completed'],
            'details' => $task['details'] ?? null
        ]));
        break;

    case 'getpackageapps':
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = ($page - 1) * $limit;
            
            // 构建查询条件
            $where = [];
            $where[] = "pa.appcode IN (SELECT appcode FROM application WHERE username='" . $DB->escape($subconf['username']) . "')";
            
            // 应用筛选
            if(!empty($_GET['appcode'])) {
                $where[] = "pa.appcode = '" . $DB->escape($_GET['appcode']) . "'";
            }
            
            // 应用名称筛选
            if(!empty($_GET['app_name'])) {
                $where[] = "pa.app_name LIKE '%" . $DB->escape($_GET['app_name']) . "%'";
            }
            
            $where_clause = implode(' AND ', $where);

            // 获取总数
            $count_sql = "SELECT COUNT(*) as total FROM package_apps pa WHERE $where_clause";
            $count_result = $DB->selectRow($count_sql);
            $total = $count_result ? intval($count_result['total']) : 0;

            // 获取应用配置列表
            $sql = "SELECT pa.*, a.appname 
                   FROM package_apps pa 
                   LEFT JOIN application a ON pa.appcode = a.appcode 
                   WHERE $where_clause 
                   ORDER BY pa.sort_order ASC, pa.id DESC 
                   LIMIT $offset, $limit";
            
            $apps = $DB->select($sql);
            
            // 处理数据
            if($apps) {
                foreach($apps as &$app) {
                    // 格式化时间
                    $app['create_time'] = date('Y-m-d H:i:s', strtotime($app['create_time']));
                    $app['update_time'] = date('Y-m-d H:i:s', strtotime($app['update_time']));
                    // 不再转换下载地址为按钮
                }
            }
            
            exit(json_encode([
                'code' => 0,
                'msg' => '',
                'count' => $total,
                'data' => $apps ?: []
            ], JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            exit(json_encode([
                'code' => 1,
                'msg' => '获取应用配置列表失败：' . $e->getMessage(),
                'count' => 0,
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'getapps':  // 改为小写
        try {
            // 获取应用列表
            $sql = "SELECT appcode, appname FROM application WHERE username='" . $DB->escape($subconf['username']) . "' ORDER BY appname ASC";
            $apps = $DB->select($sql);
            
            if($apps === false) {
                throw new Exception('数据库查询失败');
            }
            
            // 确保返回数组而不是null
            $apps = $apps ?: [];
            
            exit(json_encode([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $apps
            ], JSON_UNESCAPED_UNICODE));
            
        } catch (Exception $e) {
            exit(json_encode([
                'code' => -1,
                'msg' => '获取应用列表失败：' . $e->getMessage(),
                'data' => []
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'addpackageapp':  // 改为小写
        try {
            // 验证必填参数
            $required = ['appcode', 'app_name', 'server_address', 'server_port'];
            foreach($required as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    throw new Exception('请填写完整信息');
                }
            }
            
            // 验证端口号
            if(!is_numeric($_POST['server_port']) || $_POST['server_port'] < 1 || $_POST['server_port'] > 65535) {
                throw new Exception('端口号必须在1-65535之间');
            }
            
            // 验证权限
            $app = $DB->selectRow("SELECT * FROM application WHERE appcode = '" . $DB->escape($_POST['appcode']) . 
                                 "' AND username = '" . $DB->escape($subconf['username']) . "'");
            if(!$app) {
                throw new Exception('无权操作此应用');
            }
            
            // 检查应用是否已经配置过
            $check = $DB->selectRow("SELECT id FROM package_apps WHERE appcode = '" . $DB->escape($_POST['appcode']) . "'");
            if($check) {
                throw new Exception('该应用已配置，请勿重复添加');
            }
            
            // 验证服务器地址格式
            if(!filter_var($_POST['server_address'], FILTER_VALIDATE_IP) && 
               !filter_var($_POST['server_address'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new Exception('服务器地址格式不正确');
            }
            
            // 验证下载地址格式(如果有)
            if(!empty($_POST['download_url']) && !filter_var($_POST['download_url'], FILTER_VALIDATE_URL)) {
                throw new Exception('下载地址格式不正确');
            }
            
            // 添加配置
            $data = [
                'appcode' => $_POST['appcode'],
                'app_name' => $_POST['app_name'],
                'server_address' => $_POST['server_address'],
                'server_port' => $_POST['server_port'],
                'download_url' => $_POST['download_url'] ?? '',
                'special_notes' => $_POST['special_notes'] ?? '',
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'status' => isset($_POST['status']) ? 1 : 0,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            $result = $DB->insert('package_apps', $data);
            if($result !== false) {
                WriteLog("添加应用配置", "添加应用配置 [{$app['appname']}] {$_POST['app_name']}, " . 
                        "服务器: {$_POST['server_address']}:{$_POST['server_port']}", 
                        $subconf['username'], $DB);
                
                exit(json_encode(['code' => 1, 'msg' => '添加成功']));
            } else {
                throw new Exception('添加失败：数据库操作错误');
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => $e->getMessage()]));
        }
        break;

    case 'editpackageapp':  // 改为小写
        try {
            // 验证必填参数
            if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('参数错误');
            }
            
            $required = ['appcode', 'app_name', 'server_address', 'server_port'];
            foreach($required as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    throw new Exception('请填写完整信息');
                }
            }
            
            // 验证端口号
            if(!is_numeric($_POST['server_port']) || $_POST['server_port'] < 1 || $_POST['server_port'] > 65535) {
                throw new Exception('端口号必须在1-65535之间');
            }
            
            // 验证权限
            $app = $DB->selectRow("SELECT * FROM application WHERE appcode = '" . $DB->escape($_POST['appcode']) . 
                                 "' AND username = '" . $DB->escape($subconf['username']) . "'");
            if(!$app) {
                throw new Exception('无权操作此应用');
            }
            
            // 检查应用名称是否重复(排除自身)
            $check = $DB->selectRow("SELECT id FROM package_apps WHERE appcode = '" . $DB->escape($_POST['appcode']) . 
                                   "' AND app_name = '" . $DB->escape($_POST['app_name']) . 
                                   "' AND id != " . intval($_POST['id']));
            if($check) {
                throw new Exception('应用名称已存在');
            }
            
            // 验证服务器地址格式
            if(!filter_var($_POST['server_address'], FILTER_VALIDATE_IP) && 
               !filter_var($_POST['server_address'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new Exception('服务器地址格式不正确');
            }
            
            // 验证下载地址格式(如果有)
            if(!empty($_POST['download_url']) && !filter_var($_POST['download_url'], FILTER_VALIDATE_URL)) {
                throw new Exception('下载地址格式不正确');
            }
            
            // 更新配置
            $data = [
                'app_name' => $_POST['app_name'],
                'server_address' => $_POST['server_address'],
                'server_port' => $_POST['server_port'],
                'download_url' => $_POST['download_url'] ?? '',
                'special_notes' => $_POST['special_notes'] ?? '',
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'status' => isset($_POST['status']) ? 1 : 0,
                'update_time' => date('Y-m-d H:i:s')
            ];
            
            $result = $DB->update('package_apps', $data, "id = " . intval($_POST['id']));
            if($result !== false) {
                WriteLog("编辑应用配置", "编辑应用配置 [{$app['appname']}] {$_POST['app_name']}, " . 
                        "服务器: {$_POST['server_address']}:{$_POST['server_port']}", 
                        $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '编辑成功']));
            } else {
                throw new Exception('编辑失败：数据库操作错误');
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => $e->getMessage()]));
        }
        break;

    case 'delpackageapp':
        try {
            if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('参数错误');
            }
            
            $id = intval($_POST['id']);
            
            // 验证权限并获取配置信息
            $config = $DB->selectRow("SELECT pa.*, a.appname 
                                FROM package_apps pa 
                                LEFT JOIN application a ON pa.appcode = a.appcode 
                                WHERE pa.id = $id 
                                AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$config) {
                throw new Exception('无权操作此配置或配置不存在');
            }
            
            // 执行删除
            $result = $DB->delete('package_apps', "id = $id");
            if($result !== false) {
                WriteLog("删除应用配置", "删除应用配置 [{$config['appname']}] {$config['app_name']}", $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '删除成功']));
            } else {
                throw new Exception('删除失败：' . $DB->errMsg);
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => $e->getMessage()]));
        }
        break;

    case 'delpackageapps':
        try {
            if(!isset($_POST['ids']) || !is_array($_POST['ids'])) {
                throw new Exception('参数错误');
            }
            
            $ids = array_map('intval', $_POST['ids']);
            if(empty($ids)) {
                throw new Exception('请选择要删除的配置');
            }
            
            // 验证权限并获取配置信息
            $configs = $DB->select("SELECT pa.*, a.appname 
                              FROM package_apps pa 
                              LEFT JOIN application a ON pa.appcode = a.appcode 
                              WHERE pa.id IN (" . implode(',', $ids) . ") 
                              AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(empty($configs)) {
                throw new Exception('无权操作这些配置或配置不存在');
            }
            
            // 执行批量删除
            $result = $DB->delete('package_apps', "id IN (" . implode(',', $ids) . ")");
            if($result !== false) {
                $details = [];
                foreach($configs as $config) {
                    $details[] = "[{$config['appname']}] {$config['app_name']}";
                }
                WriteLog("批量删除应用配置", "删除应用配置：" . implode('、', $details), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '删除成功']));
            } else {
                throw new Exception('删除失败：' . $DB->errMsg);
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => $e->getMessage()]));
        }
        break;

    case 'updatepackageappstatus':  // 改为小写
        try {
            if(!isset($_POST['id']) || !isset($_POST['status'])) {
                throw new Exception('参数错误');
            }
            
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            
            // 验证权限
            $config = $DB->selectRow("SELECT pa.*, a.appname 
                                FROM package_apps pa 
                                LEFT JOIN application a ON pa.appcode = a.appcode 
                                WHERE pa.id = $id 
                                AND a.username = '" . $DB->escape($subconf['username']) . "'");
            if(!$config) {
                throw new Exception('无权操作此配置或配置不存在');
            }
            
            $result = $DB->update('package_apps', ['status' => $status], "id = $id");
            if($result !== false) {
                WriteLog("修改应用配置状态", "应用配置 [{$config['appname']}] {$config['app_name']} " . 
                        ($status ? '启用' : '禁用'), $subconf['username'], $DB);
                exit(json_encode(['code' => 1, 'msg' => '更新成功']));
            } else {
                throw new Exception('更新失败：数据库操作错误');
            }
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => $e->getMessage()]));
        }
        break;

    case "cleanexpired":
        try {
            // 设置脚本执行时间和内存限制
            set_time_limit(0);  // 取消时间限制
            ini_set('memory_limit', '512M');  // 增加内存限制
            
            // 获取分页参数
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10; // 减小批次大小
            $app = isset($_POST['app']) ? trim($_POST['app']) : '';
            
            // 使用session来防止重复处理
            $session_key = "clean_expired_" . date('Y-m-d') . ($app ? "_" . $app : "");
            if($offset == 0) {
                // 如果是新的清理任务，清除之前的session
                if(isset($_SESSION[$session_key])) {
                    unset($_SESSION[$session_key]);
                }
                $_SESSION[$session_key] = [
                    'total' => 0,
                    'cleaned' => 0,
                    'skipped' => 0,
                    'errors' => array(),
                    'processed' => 0,
                    'processed_offsets' => array()
                ];

                // 计算总数时只统计过期账号
                $total = 0;
                $users_generator = SerchearchAllServer($app, "", $DB);
                foreach($users_generator as $users) {
                    if(is_array($users)) {
                        foreach($users as $user) {
                            if(isset($user['disabletime'])) {
                                $expire_time = strtotime($user['disabletime']);
                                if($expire_time <= time()) {
                                    $total++;
                                }
                            }
                        }
                    }
                }
                $_SESSION[$session_key]['total'] = $total;
                
                // 释放内存
                unset($users_generator);
                unset($users);
                gc_collect_cycles();
            }

            // 获取用户列表
            $users_generator = SerchearchAllServer($app, "", $DB);
            if(!$users_generator) {
                throw new Exception('获取用户列表失败');
            }

            $current_time = time();
            $current_offset = 0;
            $processed = 0;
            $to_delete = array();

            // 处理用户
            foreach($users_generator as $users) {
                if(!is_array($users) || empty($users)) {
                    continue;
                }

                foreach($users as $user) {
                    // 跳过不在当前批次的用户
                    if($current_offset++ < $offset) {
                        continue;
                    }

                    // 达到批次大小时停止
                    if($processed >= $batch_size) {
                        break 2;
                    }

                    try {
                        if(!isset($user['user']) || !isset($user['disabletime']) || !isset($user['serverip'])) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        // 检查是否过期
                        $expire_time = strtotime($user['disabletime']);
                        if($expire_time > $current_time) {
                            $_SESSION[$session_key]['skipped']++;
                            continue;
                        }

                        // 添加到待删除列表
                        $to_delete[] = [
                            'user' => $user['user'],
                            'serverip' => $user['serverip']
                        ];

                        $processed++;
                        $_SESSION[$session_key]['processed']++;

                    } catch (Exception $e) {
                        $_SESSION[$session_key]['errors'][] = "处理用户 {$user['user']} 时出错: " . $e->getMessage();
                        $_SESSION[$session_key]['skipped']++;
                    }
                }
            }

            // 释放内存
            unset($users_generator);
            unset($users);
            gc_collect_cycles();

            // 批量删除过期账号
            if(!empty($to_delete)) {
                $scheduler = new Scheduler;
                foreach($to_delete as $user) {
                    $scheduler->addTask(DelUser($user['user'], $user['serverip'], $DB));
                }
                $results = $scheduler->run();

                foreach($results as $index => $success) {
                    if($success) {
                        $_SESSION[$session_key]['cleaned']++;
                    } else {
                        $_SESSION[$session_key]['errors'][] = "删除用户 {$to_delete[$index]['user']} 失败";
                        $_SESSION[$session_key]['skipped']++;
                    }
                }

                // 释放内存
                unset($scheduler);
                unset($results);
                gc_collect_cycles();
            }

            // 返回结果
            $result = [
                'code' => 1,
                'msg' => '批次处理完成',
                'details' => [
                    'total' => $_SESSION[$session_key]['total'],
                    'total_processed' => $_SESSION[$session_key]['processed'],
                    'batch_processed' => $processed,
                    'cleaned' => $_SESSION[$session_key]['cleaned'],
                    'skipped' => $_SESSION[$session_key]['skipped'],
                    'has_more' => $processed > 0 && $_SESSION[$session_key]['processed'] < $_SESSION[$session_key]['total'],
                    'next_offset' => $processed > 0 ? $offset + $processed : $_SESSION[$session_key]['total'],
                    'errors' => $_SESSION[$session_key]['errors']
                ]
            ];

            // 记录操作日志
            if($_SESSION[$session_key]['cleaned'] > 0) {
                WriteLog("清理过期账号", "清理了 {$_SESSION[$session_key]['cleaned']} 个过期账号", $subconf['username'], $DB);
            }

            exit(json_encode($result, JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 清理过期账号错误: " . $e->getMessage() . "\n", 3, "../logs/error.log");
            exit(json_encode([
                'code' => -1,
                'msg' => "清理失败: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case "checkexpired":
        try {
            $app = isset($_POST['app']) ? trim($_POST['app']) : '';
            
            // 获取用户列表
            $users_generator = SerchearchAllServer($app, "", $DB);
            if(!$users_generator) {
                throw new Exception('获取用户列表失败');
            }

            $current_time = time();
            $has_expired = false;

            // 检查是否存在过期账号
            foreach($users_generator as $users) {
                if(!is_array($users) || empty($users)) {
                    continue;
                }

                foreach($users as $user) {
                    if(isset($user['disabletime'])) {
                        $expire_time = strtotime($user['disabletime']);
                        if($expire_time <= $current_time) {
                            $has_expired = true;
                            break 2;
                        }
                    }
                }
            }

            // 释放内存
            unset($users_generator);
            unset($users);
            gc_collect_cycles();

            exit(json_encode([
                'code' => 1,
                'has_expired' => $has_expired,
                'msg' => $has_expired ? '发现过期账号' : '没有过期账号'
            ], JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 检查过期账号错误: " . $e->getMessage() . "\n", 3, "../logs/error.log");
            exit(json_encode([
                'code' => -1,
                'msg' => "检查失败: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    case 'getset':
        // 从sub_admin表获取所有网站设置
        $sql = "SELECT * FROM sub_admin WHERE username='" . $DB->escape($subconf['username']) . "' LIMIT 1";
        $result = $DB->select($sql);
        
        if($result && isset($result[0])) {
            $response = [
                'code' => 1,
                'msg' => '获取成功',
                'data' => $result[0]  // 返回所有设置数据
            ];
        } else {
            $response = [
                'code' => -1,
                'msg' => '获取失败'
            ];
        }
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        break;

    case 'setinherit':
        try {
            error_log("[" . date('Y-m-d H:i:s') . "] 开始处理setinherit请求\n", 3, "../logs/error.log");
            
            // 验证必要参数
            if(!isset($_POST['inherit_config'])) {
                throw new Exception('缺少继承配置参数');
            }

            // 记录接收到的继承配置
            error_log("[" . date('Y-m-d H:i:s') . "] 接收到的inherit_config: " . $_POST['inherit_config'] . "\n", 3, "../logs/error.log");
            
            // 解析继承配置
            $inherit_config_str = $_POST['inherit_config'];
            $prev_str = '';
            while($inherit_config_str !== $prev_str) {
                $prev_str = $inherit_config_str;
                $inherit_config_str = html_entity_decode($inherit_config_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            
            error_log("[" . date('Y-m-d H:i:s') . "] 完全解码后的inherit_config: " . $inherit_config_str . "\n", 3, "../logs/error.log");
            
            $inherit_config = json_decode($inherit_config_str, true);
            if($inherit_config === null) {
                error_log("[" . date('Y-m-d H:i:s') . "] JSON解析失败: " . json_last_error_msg() . "\n", 3, "../logs/error.log");
                throw new Exception('继承配置格式错误: ' . json_last_error_msg());
            }
            
            // 验证继承配置格式
            if(!isset($inherit_config['groups']) || !is_array($inherit_config['groups'])) {
                error_log("[" . date('Y-m-d H:i:s') . "] 继承配置缺少groups字段或格式错误\n", 3, "../logs/error.log");
                $inherit_config = ['groups' => []];
            }
            
            // 验证每个组的格式
            foreach($inherit_config['groups'] as $group) {
                if(!isset($group['id']) || !is_numeric($group['id'])) {
                    throw new Exception('继承组ID格式错误');
                }
                if(!isset($group['main_apps']) || !is_array($group['main_apps'])) {
                    throw new Exception('主应用配置格式错误');
                }
                if(!isset($group['inherit_apps']) || !is_array($group['inherit_apps'])) {
                    throw new Exception('继承应用配置格式错误');
                }
            }

            // 更新继承配置
            $sql = "UPDATE sub_admin SET 
                    inherit_enabled = 1,
                    inherit_groups = '" . $DB->escape($inherit_config_str) . "'
                    WHERE username = '" . $DB->escape($subconf['username']) . "'";
            
            error_log("[" . date('Y-m-d H:i:s') . "] 执行SQL更新\n", 3, "../logs/error.log");
            
            $result = $DB->exe($sql);
            if($result !== false) {
                WriteLog("更新继承配置", "更新继承应用配置，组数：" . count($inherit_config['groups']), $subconf['username'], $DB);
                exit(json_encode([
                    'code' => 1,
                    'msg' => '设置成功'
                ], JSON_UNESCAPED_UNICODE));
            } else {
                throw new Exception('数据库更新失败');
            }
            
        } catch(Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] 设置继承配置失败: " . $e->getMessage() . "\n", 3, "../logs/error.log");
            exit(json_encode([
                'code' => -1,
                'msg' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
        }
        break;

    default:
        exit(json_encode(["code"=>-4,"msg"=>"No Act"]));
        break;
    }
} catch(Exception $e) {
    $response = [
        'code' => -1,
        'msg' => $e->getMessage()
    ];
    exit(json_encode($response, JSON_UNESCAPED_UNICODE));
}

/**
 * 启动异步补偿处理
 * @param string $task_id 任务ID
 * @return void
 */
function startAsyncCompensate($task_id) {
    global $DB, $subconf;
    
    try {
        $task = &$_SESSION['compensate_task'];
        if(!$task || $task['id'] !== $task_id) {
            throw new Exception('任务不存在');
        }

        // 获取应用信息
        $app_info = $DB->selectRow("SELECT * FROM application WHERE appcode='" . 
            $DB->escape($task['params']['app']) . "' AND username='" . 
            $DB->escape($subconf['username']) . "'");
        
        if(!$app_info) {
            throw new Exception('应用不存在或无权限访问');
        }

        // 获取服务器信息
        $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . 
            $DB->escape($app_info['serverip']) . "'");
        
        if(!$server) {
            throw new Exception('服务器信息不存在');
        }

        // 获取用户列表
        $users_generator = SerchearchAllServer($task['params']['app'], "", $DB);
        if(!$users_generator) {
            throw new Exception('获取用户列表失败');
        }

        // 初始化统计数据
        $task['success'] = 0;
        $task['failed'] = 0;
        $task['errors'] = array();
        $task['processed'] = 0;
        $task['skipped'] = 0;
        $task['total'] = 0;

        // 计算总用户数
        foreach($users_generator as $users) {
            if(is_array($users)) {
                $task['total'] += count($users);
            }
        }

        // 重新获取用户列表用于处理
        $users_generator = SerchearchAllServer($task['params']['app'], "", $DB);
        $current_time = date('Y-m-d H:i:s');

        // 处理每个用户
        foreach($users_generator as $users) {
            if(!is_array($users) || empty($users)) {
                continue;
            }

            foreach($users as $user) {
                try {
                    if(!isset($user['user']) || !isset($user['disabletime'])) {
                        $task['skipped']++;
                        continue;
                    }

                    $is_expired = strtotime($user['disabletime']) < time();

                    // 根据过滤条件跳过不符合的用户
                    if($task['params']['expire_filter'] == 'expired' && !$is_expired) {
                        $task['skipped']++;
                        continue;
                    }
                    if($task['params']['expire_filter'] == 'unexpired' && $is_expired) {
                        $task['skipped']++;
                        continue;
                    }

                    // 计算新的到期时间
                    $compensation = "+{$task['params']['value']} {$task['params']['unit']}";
                    $new_time = $is_expired 
                        ? date('Y-m-d H:i:s', strtotime($current_time . " " . $compensation))
                        : date('Y-m-d H:i:s', strtotime($user['disabletime'] . " " . $compensation));

                    // 更新用户时间
                    $update_result = UserUpdate(
                        $server["password"],
                        $server["cport"],
                        $server["ip"],
                        $user["user"],
                        $user["pwd"],
                        $new_time,
                        $user["connection"] ?? "-1",
                        $user["bandwidthup"] ?? "-1",
                        $user["bandwidthdown"] ?? "-1",
                        "0"
                    );

                    if($update_result && isset($update_result['code']) && $update_result['code'] == "1") {
                        $task['success']++;
                    } else {
                        throw new Exception(isset($update_result['msg']) ? $update_result['msg'] : '未知错误');
                    }

                } catch (Exception $e) {
                    $task['failed']++;
                    $task['errors'][] = "用户 {$user['user']} 补偿失败: " . $e->getMessage();
                }

                $task['processed']++;
                
                // 每处理10个用户保存一次session，避免session过大
                if($task['processed'] % 10 == 0) {
                    session_write_close();
                    session_start();
                }
            }
        }

        // 更新任务状态
        $task['completed'] = true;
        $task['end_time'] = time();
        
        // 记录完成日志
        error_log(sprintf(
            "[%s] 补偿任务完成 - ID:%s, 总数:%d, 成功:%d, 失败:%d, 跳过:%d\n",
            date('Y-m-d H:i:s'),
            $task_id,
            $task['total'],
            $task['success'],
            $task['failed'],
            $task['skipped']
        ), 3, "../logs/error.log");

    } catch (Exception $e) {
        error_log(sprintf(
            "[%s] 补偿任务异常 - ID:%s, Error:%s\n",
            date('Y-m-d H:i:s'),
            $task_id,
            $e->getMessage()
        ), 3, "../logs/error.log");
        
        $task['completed'] = true;
        $task['error'] = $e->getMessage();
    }
}
?>
