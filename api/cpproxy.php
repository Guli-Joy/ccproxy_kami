<?php


include("../includes/common.php");
@header('Content-Type: application/json; charset=UTF-8');

if (isset($_REQUEST["type"])) {
    $type = $_REQUEST["type"];
    switch ($type) {
        case "insert":
            $json = checkinsert($DB);
            break;
        case "del":
            $json = del();
            break;
        case "update":
            $json = checkupdate($DB);
            break;
        case "query":
            $json = checkquery($DB);
            break;
        case "changepwd":
            $json = changepwd($DB);
            break;
        default:
            $json = ["code" => "无效事务", "icon" => "5"];
    }
} else {
    $json = ["code" => "非法参数", "icon" => "5"];
}
echo json_encode($json, JSON_UNESCAPED_UNICODE);

/**
 * 查询方法
 */
function checkquery($DB)
{
    if (isset($_POST["appcode"]) && isset($_POST["user"])) {
        $ip = $DB->selectRow("select serverip from application where appcode='" . $_POST["appcode"] . "'");
        $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $ip['serverip'] . "'"); //$ip['serverip']服务器IP
        $proxyaddress = $server['ip'];
        $admin_username = $server['serveruser'];
        $admin_password = $server['password'];
        $admin_port = $server['cport'];
        $ser = query($admin_password, $admin_port, $proxyaddress);
        if (!$ser) {
            $json = [
                "code" => -3,
                "msg" => '<h5 style="color: red;display: inline;">服务器通信出现问题</h5>'
            ];
        } else {
            $json = [
                "code" => 1,
                "msg" => userquer($_POST["user"], $ser)
            ];
        }
    } else {
        $json = ["code" => "非法参数", "icon" => "5"];
    }
    return $json;
}
/***
 * 添加用户
 */
function checkinsert($DB)
{
    try {
        if(!isset($_POST["user"]) || !isset($_POST["pwd"]) || !isset($_POST["code"])) {
            throw new Exception('参数错误');
        }

        // 验证卡密
        $kami = $DB->selectRow("SELECT * FROM kami WHERE kami='" . $DB->escape($_POST["code"]) . "'");
        if(!$kami) {
            throw new Exception('卡密不存在');
        }
        if($kami['state'] == 1) {
            throw new Exception('卡密已被使用');
        }

        // 获取主应用信息
        $app = $DB->selectRow("SELECT * FROM application WHERE appcode='" . $DB->escape($kami['app']) . "'");
        if(!$app) {
            throw new Exception('卡密对应的应用不存在');
        }

        // 获取服务器信息
        $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . $DB->escape($app['serverip']) . "'");
        if(!$server) {
            throw new Exception('应用对应的服务器不存在');
        }

        // 检查用户是否已存在
        $user_list = query($server['password'], $server['cport'], $server['ip']);
        if($user_list === false) {
            throw new Exception('服务器通信出现问题');
        }
        foreach($user_list as $user) {
            if($user['user'] == $_POST["user"]) {
                throw new Exception('账号已经存在');
            }
        }

        // 解析扩展参数
        $ext = json_decode($kami['ext'], true);
        if(!$ext) {
            $ext = [
                'connection' => -1,
                'bandwidthup' => -1,
                'bandwidthdown' => -1,
                'inherit_apps' => []
            ];
        }

        // 注册主应用账号
        $result = insert(
            $server['ip'],
            $server['serveruser'],
            $server['password'],
            $server['cport'],
            $kami['times'],
            $_POST["user"],
            $_POST["pwd"],
            json_encode([
                'connection' => $ext['connection'],
                'bandwidthup' => $ext['bandwidthup'],
                'bandwidthdown' => $ext['bandwidthdown']
            ])
        );

        if(!$result || $result['icon'] != 1) {
            throw new Exception($result['msg'] ?? '注册失败');
        }

        // 处理继承应用
        $inheritErrors = [];
        if(isset($ext['inherit_apps']) && is_array($ext['inherit_apps'])) {
            foreach($ext['inherit_apps'] as $inheritAppcode) {
                try {
                    // 获取继承应用信息
                    $inheritApp = $DB->selectRow("SELECT * FROM application WHERE appcode='" . $DB->escape($inheritAppcode) . "'");
                    if(!$inheritApp) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 不存在";
                        continue;
                    }

                    // 获取继承应用服务器信息
                    $inheritServer = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . $DB->escape($inheritApp['serverip']) . "'");
                    if(!$inheritServer) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 的服务器不存在";
                        continue;
                    }

                    // 注册继承应用账号
                    $inheritResult = insert(
                        $inheritServer['ip'],
                        $inheritServer['serveruser'],
                        $inheritServer['password'],
                        $inheritServer['cport'],
                        $kami['times'],
                        $_POST["user"],
                        $_POST["pwd"],
                        json_encode([
                            'connection' => $ext['connection'],
                            'bandwidthup' => $ext['bandwidthup'],
                            'bandwidthdown' => $ext['bandwidthdown']
                        ])
                    );

                    if(!$inheritResult || $inheritResult['icon'] != 1) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 注册失败: " . ($inheritResult['msg'] ?? '未知错误');
                    }
                } catch(Exception $e) {
                    $inheritErrors[] = "继承应用 {$inheritAppcode} 处理失败: " . $e->getMessage();
                }
            }
        }

        // 更新卡密状态
        $update = [
            'state' => 1,
            'username' => $_POST["user"],
            'use_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . $kami['times']))
        ];
        $DB->update('kami', $update, "kami='" . $DB->escape($_POST["code"]) . "'");

        // 返回结果
        $response = [
            'code' => 1,
            'msg' => '注册成功'
        ];

        if(!empty($inheritErrors)) {
            $response['msg'] .= "\n但部分继承应用处理失败:\n" . implode("\n", $inheritErrors);
        }

        return $response;

    } catch(Exception $e) {
        return [
            'code' => -1,
            'msg' => $e->getMessage()
        ];
    }
}
/**
 * 续费方法
 */
function checkupdate($DB)
{
    try {
        if(!isset($_POST["user"]) || !isset($_POST["code"])) {
            throw new Exception('参数错误');
        }

        // 验证卡密
        $kami = $DB->selectRow("SELECT * FROM kami WHERE kami='" . $DB->escape($_POST["code"]) . "'");
        if(!$kami) {
            throw new Exception('卡密不存在');
        }
        if($kami['state'] == 1) {
            throw new Exception('卡密已被使用');
        }

        // 获取主应用信息
        $app = $DB->selectRow("SELECT * FROM application WHERE appcode='" . $DB->escape($kami['app']) . "'");
        if(!$app) {
            throw new Exception('卡密对应的应用不存在');
        }

        // 获取服务器信息
        $server = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . $DB->escape($app['serverip']) . "'");
        if(!$server) {
            throw new Exception('应用对应的服务器不存在');
        }

        // 检查用户是否存在
        $user_list = query($server['password'], $server['cport'], $server['ip']);
        if($user_list === false) {
            throw new Exception('服务器通信出现问题');
        }

        $user_found = false;
        $user_data = null;
        foreach($user_list as $user) {
            if($user['user'] == $_POST["user"]) {
                $user_found = true;
                $user_data = $user;
                break;
            }
        }

        if(!$user_found) {
            throw new Exception('充值账号不存在');
        }

        // 解析扩展参数
        $ext = json_decode($kami['ext'], true);
        if(!$ext) {
            $ext = [
                'connection' => -1,
                'bandwidthup' => -1,
                'bandwidthdown' => -1,
                'inherit_apps' => []
            ];
        }

        // 续费主应用账号
        $result = update(
            $server['ip'],
            $server['serveruser'],
            $server['password'],
            $server['cport'],
            $kami['times'],
            $user_data,
            json_encode([
                'connection' => $ext['connection'],
                'bandwidthup' => $ext['bandwidthup'],
                'bandwidthdown' => $ext['bandwidthdown']
            ])
        );

        if(!$result || $result['icon'] != 1) {
            throw new Exception($result['msg'] ?? '续费失败');
        }

        // 处理继承应用
        $inheritErrors = [];
        if(isset($ext['inherit_apps']) && is_array($ext['inherit_apps'])) {
            foreach($ext['inherit_apps'] as $inheritAppcode) {
                try {
                    // 获取继承应用信息
                    $inheritApp = $DB->selectRow("SELECT * FROM application WHERE appcode='" . $DB->escape($inheritAppcode) . "'");
                    if(!$inheritApp) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 不存在";
                        continue;
                    }

                    // 获取继承应用服务器信息
                    $inheritServer = $DB->selectRow("SELECT * FROM server_list WHERE ip='" . $DB->escape($inheritApp['serverip']) . "'");
                    if(!$inheritServer) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 的服务器不存在";
                        continue;
                    }

                    // 检查继承应用账号是否存在
                    $inheritUserList = query($inheritServer['password'], $inheritServer['cport'], $inheritServer['ip']);
                    if($inheritUserList === false) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 服务器通信失败";
                        continue;
                    }

                    $inheritUserFound = false;
                    $inheritUserData = null;
                    foreach($inheritUserList as $inheritUser) {
                        if($inheritUser['user'] == $_POST["user"]) {
                            $inheritUserFound = true;
                            $inheritUserData = $inheritUser;
                            break;
                        }
                    }

                    if(!$inheritUserFound) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 账号不存在,无法续费";
                        continue;
                    }

                    // 续费继承应用账号
                    $inheritResult = update(
                        $inheritServer['ip'],
                        $inheritServer['serveruser'],
                        $inheritServer['password'],
                        $inheritServer['cport'],
                        $kami['times'],
                        $inheritUserData,
                        json_encode([
                            'connection' => $ext['connection'],
                            'bandwidthup' => $ext['bandwidthup'],
                            'bandwidthdown' => $ext['bandwidthdown']
                        ])
                    );

                    if(!$inheritResult || $inheritResult['icon'] != 1) {
                        $inheritErrors[] = "继承应用 {$inheritAppcode} 续费失败: " . ($inheritResult['msg'] ?? '未知错误');
                    }
                } catch(Exception $e) {
                    $inheritErrors[] = "继承应用 {$inheritAppcode} 处理失败: " . $e->getMessage();
                }
            }
        }

        // 更新卡密状态
        $update = [
            'state' => 1,
            'username' => $_POST["user"],
            'use_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . $kami['times']))
        ];
        $DB->update('kami', $update, "kami='" . $DB->escape($_POST["code"]) . "'");

        // 返回结果
        $response = [
            'code' => 1,
            'msg' => '续费成功'
        ];

        if(!empty($inheritErrors)) {
            $response['msg'] .= "\n但部分继承应用处理失败:\n" . implode("\n", $inheritErrors);
        }

        return $response;

    } catch(Exception $e) {
        return [
            'code' => -1,
            'msg' => $e->getMessage()
        ];
    }
}



/**
 * 添加具体方法
 */

function insert($proxyaddress, $admin_username, $admin_password, $admin_port, $day, $user, $pwd, $ext)
{
    $username = $user;
    $password = $pwd;
    if (!CheckStrChinese($username)) {
        return ["code" => "-1", "msg" => "用户名不合法", "icon" => "5"];
    }
    if (strlen($username) < 5) {
        return ["code" => "-1", "msg" => "用户名长度不合法", "icon" => "5"];
    }
    if (!CheckStrPwd($password)) {
        return ["code" => "-1", "msg" => "密码不合法", "icon" => "5"];
    }
    $post = ["user", "pwd"];
    $is = true;
    foreach ($post as $key) {
        if (!isset($_POST[$key])) {
            $is = false;
        }
    }
    if ($is) {
        $ipaddress = "";
        $macaddress = "";
        $connection = json_decode($ext, true)["connection"];
        $bandwidth = json_decode($ext, true)["bandwidthup"] . "/" . json_decode($ext, true)["bandwidthdown"];
        $date = date("Y-m-d H:i:s");
        $enddate = "";

        try {
            $enddate= date('Y-m-d H:i:s', strtotime("$date $day"));
        } catch (Exception $e)
        {
            $enddate= date('Y-m-d H:i:s', strtotime("$date +1 day"));
        }

        if($enddate=="1970-01-01 08:00:00"){
            $enddate= date('Y-m-d H:i:s', strtotime("$date +1 day"));
        }

        //$enddate = date('Y-m-d H:i:s', strtotime("$date + " . $day>0&&$day<1?(int)($day*10) ." hours":((int)$day) . " day"));
        $end_date = explode(" ", $enddate);
        $disabledate = $end_date[0];
        $disabletime = $end_date[1];
        $fp = fsockopen($proxyaddress, $admin_port, $errno, $errstr, 30);
        if (!$fp) {
            // return ["code" => "无法连接到CCProxy", "icon" => "5"];
            return false;
        } else {
            $url_ = "/account";
            $url = "add=1" . "&";
            $url = $url . "autodisable=1" . "&";
            $url = $url . "enable=1" . "&";
            if ($admin_password != "") {
                $url = $url . "usepassword=1" . "&";
            }
            if ($ipaddress != "") {
                $url = $url . "usepassword=1" . "&";
            }
            if ($macaddress != "") {
                $url = $url . "usemacaddress=1" . "&";
            }
            $url = $url . "enablesocks=1" . "&";
            $url = $url . "enablewww=0" . "&";
            $url = $url . "enabletelnet=0" . "&";
            $url = $url . "enabledial=0" . "&";
            $url = $url . "enableftp=0" . "&";
            $url = $url . "enableothers=0" . "&";
            $url = $url . "enablemail=0" . "&";
            $url = $url . "username=" . $username . "&";
            $url = $url . "password=" . $password . "&";
            $url = $url . "ipaddress=" . $ipaddress . "&";
            $url = $url . "macaddress=" . $macaddress . "&";
            $url = $url . "connection=" . $connection . "&";
            $url = $url . "bandwidth=" . $bandwidth . "&";
            $url = $url . "disabledate=" . $disabledate . "&";
            $url = $url . "disabletime=" . $disabletime . "&";
            $url = $url . "userid=-1";
            $len = "Content-Length: " . strlen($url);
            $auth = "Authorization: Basic " . base64_encode($admin_username . ":" . $admin_password);
            $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
            fputs($fp, $msg);
            while (!feof($fp)) {
                $s = fgets($fp, 4096);
            }
            fclose($fp);
            return ["code" => "注册用户成功", "icon" => "1"];
        }
    } else {
        return ["code" => "注册用户存在非法参数", "icon" => "5"];
    }
}
/**
 * 删除方法
 */
function del()
{
    $username = $_POST["username"];
    if (isset($username)) {
        if (!CheckStrChinese($username)) {
            return ["code" => "-1", "msg" => "用户名不合法", "icon" => "5"];
        }
        if (strlen($username) < 5) {
            return ["code" => "-1", "msg" => "用户名长度不合法", "icon" => "5"];
        }
        $admin_username = isset($_POST["admin_username"]) ? $_POST["admin_username"] : "";
        $admin_password = isset($_POST["admin_password"]) ? $_POST["admin_password"] : "";
        $adminport = isset($_POST["admin_port"]) ? $_POST["admin_port"] : "";
        $proxyaddress = isset($_POST["proxyaddress"]) ? $_POST["proxyaddress"] : "";
        $fp = fsockopen($proxyaddress, $adminport, $errno, $errstr, 30);
        if (!$fp) {
            return ["code" => "无法连接到CCProxy", "icon" => "5"];
        } else {
            $url_ = "/account";
            $url = "delete=1" . "&";
            $url = $url . "userid=" . $username;
            $len = "Content-Length: " . strlen($url);
            $auth = "Authorization: Basic " . base64_encode($admin_username . ":" . $admin_password);
            $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
            fputs($fp, $msg);
            while (!feof($fp)) {
                $s = fgets($fp, 4096);
            }
            fclose($fp);
            return ["code" => "删除用户成功", "icon" => "1"];
        }
    } else {
        return ["code" => "删除用户存在非法参数", "icon" => "5"];
    }
}
/**
 * 更新方法
 */
function update($proxyaddress, $admin_username, $admin_password, $admin_port, $day, $date, $ext)
{
    if (!CheckStrChinese($admin_username)) {
        return ["code" => "-1", "msg" => "用户名不合法", "icon" => "5"];
    }
    if (strlen($admin_username) < 5) {
        return ["code" => "-1", "msg" => "用户名长度不合法", "icon" => "5"];
    }
    $post = ["user"];
    $is = true;
    foreach ($post as $key) {
        if (!isset($_POST[$key])) {
            $is = false;
        }
    }
    if ($is) {
        $username = $_POST["user"];
        $connection = json_decode($ext, true)["connection"];
        $bandwidth = json_decode($ext, true)["bandwidthup"] . "/" . json_decode($ext, true)["bandwidthdown"];
        $cdate = date("Y-m-d H:i:s");
        
        $enddate = "";
        try{
            $enddate = $date['expire'] == 0 ? date('Y-m-d H:i:s', strtotime($date['disabletime'] . $day)) : date('Y-m-d H:i:s', strtotime($cdate . $day));
        }catch(Exception $e){
            error_log("计算到期时间出错: " . $e->getMessage());
            $enddate = $date['expire'] == 0 ? date('Y-m-d H:i:s', strtotime($date['disabletime'] . "+1 day")) : date('Y-m-d H:i:s', strtotime($cdate . "+1 day"));
        }

        if($enddate=="1970-01-01 08:00:00")
        {
            $enddate = $date['expire'] == 0 ? date('Y-m-d H:i:s', strtotime($date['disabletime'] . "+1 day")) : date('Y-m-d H:i:s', strtotime($cdate . "+1 day"));
        }

        $end_date = explode(" ", $enddate);
        $disabledate = $end_date[0];
        $disabletime = $end_date[1];
        $fp = fsockopen($proxyaddress, $admin_port, $errno, $errstr, 30);
        if (!$fp) {
            return ["code" => "无法连接到CCProxy", "icon" => "5"];
        } else {
            $url_ = "/account";
            $url = "edit=1" . "&";
            $url = $url . "autodisable=1" . "&";
            $url = $url . "enable=1" . "&";
            $url = $url . "usepassword=1" . "&";
            $url = $url . "enablesocks=1" . "&";
            $url = $url . "enablewww=0" . "&";
            $url = $url . "enabletelnet=0" . "&";
            $url = $url . "enabledial=0" . "&";
            $url = $url . "enableftp=0" . "&";
            $url = $url . "enableothers=0" . "&";
            $url = $url . "enablemail=0" . "&";
            $url = $url . "username=" . $username . "&";
            $url = $url . "connection=" . $connection . "&";
            $url = $url . "bandwidth=" . $bandwidth . "&";
            $url = $url . "disabledate=" . $disabledate . "&";
            $url = $url . "disabletime=" . $disabletime . "&";
            $url = $url . "userid=" . $username;
            $len = "Content-Length: " . strlen($url);
            $auth = "Authorization: Basic " . base64_encode($admin_username . ":" . $admin_password);
            $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
            fputs($fp, $msg);
            while (!feof($fp)) {
                $s = fgets($fp, 4096);
            }
            fclose($fp);
            return ["code" => "更新用户成功", "icon" => "1"];
        }
    } else {
        return ["code" => "编辑数据存在非法参数", "icon" => "5"];
    }
}


/**
 * Undocumented function
 *
 * @param [type] $url
 * @param [type] $adminpassword
 * @param [type] $adminport
 * @param [type] $proxyaddress
 * @author 一花 <487735913@qq.com>
 * @copyright Undocumented function [type]  [type]
 */
function query($adminpassword, $adminport, $proxyaddress)
{
    $url = "http://" . $proxyaddress . ":" . $adminport . "/account";
    parse_url($url);
    $data = array();
    $query_str = http_build_query($data);
    $info = parse_url($url);
    $fp = fsockopen($proxyaddress, $adminport, $errno, $errstr, 30);
    if (!$fp) {
        return false;
    } else {
        $auth = "Authorization: Basic " . base64_encode("admin:" . $adminpassword);
        $head = "GET " . $info['path']  . $query_str . " HTTP/1.0\r\n";
        $head .= "Host: " . $info['host'] . "\r\n" . $auth . "\r\n" . "\r\n";
        $write = fputs($fp, $head);
        $line = "";
        while (!feof($fp)) {
            $line .= fread($fp, 4096);
        }
        fclose($fp);
    }
    
    preg_match_all('/<input .* name="username" .* value="(.*?)"/ui', $line, $match);
    preg_match_all('/<input .* name="password" .* value="(.*?)"/ui', $line, $match2);
    preg_match_all('/<input .* name="enable" .*/', $line, $match3);
    preg_match_all('/<input .* name="usepassword" .*/', $line, $match4);
    preg_match_all('/<input .* name="disabledate" .* value="(.*?)"/ui', $line, $match5);
    preg_match_all('/<input .* name="disabletime" .* value="(.*?)"/ui', $line, $match6);
    preg_match_all('/<input .* name="autodisable" .*/', $line, $match7);
    $ccp = array();
    $time = date("Y-m-d H:i:s");
    foreach ($match[1] as $key => $use) {
        strripos(str_replace(array("<", ">", "/"), array(""), $match3[0][$key]), "checked") != "46" ? $match3[0][$key] = 0 : $match3[0][$key] = 1;
        strripos(str_replace(array("<", ">", "/"), array(""), $match4[0][$key]), "checked") != "51" ? $match4[0][$key] = 0 : $match4[0][$key] = 1;
        strripos(str_replace(array("<", ">", "/"), array(""), $match7[0][$key]), "checked") != "51" ? $match7[0][$key] = 0 : $match7[0][$key] = 1;

        $ccp[$key] = array(
            "user" => $match[1][$key],
            "pwd" => $match2[1][$key],
            "state" => $match3[0][$key],
            "pwdstate" => $match4[0][$key],
            "disabletime" => $match5[1][$key] . " " . $match6[1][$key],
            "expire" => strtotime($time) > strtotime($match5[1][$key] . " " . $match6[1][$key]) ? 1 : 0,
        );
    }
    return $ccp;
}

/**
 * 查询用户信息
 */
function userquer($column, $ccp)
{
    if (empty($column)) {
        return "不能为空！";
    }
    $result = array_filter($ccp, function ($where) use ($column) {
        return $where['user'] == $column;
    });
    
    // 如果没有找到用户，直接返回账号不存在
    if(empty($result)) {
        return '<h5 style="color: red;display: inline;">账号不存在</h5>';
    }
    
    $col = array_column($result, 'disabletime');
    $col2 = array_column($result, 'expire');
    
    // 确保数组不为空且索引存在
    if(empty($col) || empty($col2)) {
        return '<h5 style="color: red;display: inline;">账号不存在</h5>';
    }
    
    return $col2[0] == 1 ? 
        '<h5 style="color: red;display: inline;">到期时间：' . $col[0] . '</h5>' : 
        ($col[0] != "" ? '<h5 style="color: #1E9FFF;display: inline;">到期时间：' . $col[0] . '</h5>' : 
        '<h5 style="color: red;display: inline;">账号不存在</h5>');
}


/**
 * 更新用户信息
 */
function updatequer($column, $ccp)
{
    if (empty($column)) {
        return "不能为空！";
    }
    $result = array_filter($ccp, function ($where) use ($column) {
        return $where['user'] == $column;
    });
    $col = array_column($result, 'disabletime'); //expire
    $col2 = array_column($result, 'expire'); //expire
    return ["disabletime" => $col[0], "expire" => $col2[0]];
}

/**
 * 修改密码方法
 */
function changepwd($DB) {
    try {
        if (isset($_POST["appcode"]) && isset($_POST["user"]) && isset($_POST["old_pwd"]) && isset($_POST["new_pwd"])) {
            // 获取服务器信息
            $ip = $DB->selectRow("select serverip from application where appcode='" . $_POST["appcode"] . "'");
            if(!$ip) {
                return [
                    "code" => -1,
                    "msg" => "应用不存在"
                ];
            }
            
            $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $ip['serverip'] . "'");
            if(!$server) {
                return [
                    "code" => -1,
                    "msg" => "服务器不存在"
                ];
            }
            
            $proxyaddress = $server['ip'];
            $admin_username = $server['serveruser'];
            $admin_password = $server['password'];
            $admin_port = $server['cport'];
            
            // 获取用户列表
            $user_list = query($admin_password, $admin_port, $proxyaddress);
            if (!$user_list) {
                return [
                    "code" => -3,
                    "msg" => "服务器通信出现问题"
                ];
            }
            
            // 验证用户是否存在并获取用户信息
            $current_user = null;
            foreach ($user_list as $user) {
                if ($user['user'] == $_POST["user"]) {
                    $current_user = $user;
                    break;
                }
            }
            
            if (!$current_user) {
                return [
                    "code" => -1,
                    "msg" => "账号不存在"
                ];
            }
            
            if ($current_user['pwd'] != $_POST["old_pwd"]) {
                return [
                    "code" => -1,
                    "msg" => "原密码错误"
                ];
            }
            
            // 验证新密码格式
            if (!CheckStrPwd($_POST["new_pwd"])) {
                return [
                    "code" => -1,
                    "msg" => "新密码格式不正确"
                ];
            }
            
            // 修改密码
            $fp = fsockopen($proxyaddress, $admin_port, $errno, $errstr, 30);
            if (!$fp) {
                return [
                    "code" => -3,
                    "msg" => "无法连接到服务器"
                ];
            }
            
            $url_ = "/account";
            $url = "edit=1" . "&";
            $url = $url . "autodisable=1" . "&";
            $url = $url . "enable=1" . "&";
            $url = $url . "usepassword=1" . "&";
            $url = $url . "enablesocks=1" . "&";
            $url = $url . "enablewww=0" . "&";
            $url = $url . "enabletelnet=0" . "&";
            $url = $url . "enabledial=0" . "&";
            $url = $url . "enableftp=0" . "&";
            $url = $url . "enableothers=0" . "&";
            $url = $url . "enablemail=0" . "&";
            $url = $url . "username=" . $_POST["user"] . "&";
            $url = $url . "password=" . $_POST["new_pwd"] . "&";
            $url = $url . "connection=" . ($current_user['connection'] ?? "-1") . "&";
            $url = $url . "bandwidth=" . ($current_user['bandwidthup'] ?? "-1") . "/" . ($current_user['bandwidthdown'] ?? "-1") . "&";
            $end_date = explode(" ", $current_user['disabletime']);
            $url = $url . "disabledate=" . $end_date[0] . "&";
            $url = $url . "disabletime=" . $end_date[1] . "&";
            $url = $url . "userid=" . $_POST["user"];
            
            $len = "Content-Length: " . strlen($url);
            $auth = "Authorization: Basic " . base64_encode($admin_username . ":" . $admin_password);
            $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
            
            fputs($fp, $msg);
            $response = '';
            while (!feof($fp)) {
                $response .= fgets($fp, 4096);
            }
            fclose($fp);
            
            if (strpos($response, '200 OK') !== false || strpos($response, '302 Found') !== false) {
                $verify_user_list = query($admin_password, $admin_port, $proxyaddress);
                if ($verify_user_list) {
                    foreach ($verify_user_list as $verify_user) {
                        if ($verify_user['user'] == $_POST["user"]) {
                            if ($verify_user['pwd'] == $_POST["new_pwd"]) {
                                return [
                                    "code" => 1,
                                    "msg" => "密码修改成功"
                                ];
                            } else {
                                error_log("密码修改失败：新密码未生效");
                                return [
                                    "code" => -1,
                                    "msg" => "密码修改失败：新密码未生效"
                                ];
                            }
                        }
                    }
                }
                
                error_log("密码修改失败：无法验证新密码");
                return [
                    "code" => -1,
                    "msg" => "密码修改失败：无法验证新密码"
                ];
            } else {
                error_log("密码修改失败：服务器返回错误");
                return [
                    "code" => -1,
                    "msg" => "密码修改失败：服务器返回错误"
                ];
            }
        } else {
            return [
                "code" => -1,
                "msg" => "参数不完整"
            ];
        }
    } catch (Exception $e) {
        error_log("修改密码出现异常：" . $e->getMessage());
        return [
            "code" => -1,
            "msg" => "修改密码出现异常：" . $e->getMessage()
        ];
    }
}

// 获取继承配置
function getInheritConfig($DB) {
    global $subconf;
    
    if(!$subconf['inherit_enabled']) {
        return null;
    }
    
    try {
        if(empty($subconf['inherit_groups'])) {
            return null;
        }
        
        // 递归解码HTML实体
        $decoded_str = $subconf['inherit_groups'];
        $prev_str = '';
        while($decoded_str !== $prev_str) {
            $prev_str = $decoded_str;
            $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        $config = json_decode($decoded_str, true);
        if(!$config || !isset($config['groups']) || !is_array($config['groups'])) {
            return null;
        }
        
        return $config;
    } catch(Exception $e) {
        error_log("解析继承配置失败: " . $e->getMessage());
        return null;
    }
}

// 获取应用的继承应用列表
function getInheritApps($appcode, $config) {
    if(!$config || !isset($config['groups'])) {
        return [];
    }
    
    $inheritApps = [];
    foreach($config['groups'] as $group) {
        if(in_array($appcode, $group['main_apps'])) {
            $inheritApps = array_merge($inheritApps, $group['inherit_apps']);
        }
    }
    
    return array_unique($inheritApps);
}



 
