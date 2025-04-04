<?php


function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;
    $key = md5($key ? $key : ''); //ENCRYPT_KEY
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        if ((intval(substr($result, 0, 10)) == 0 || intval(substr($result, 0, 10)) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

function daddslashes($string, $force = 0, $strip = FALSE)
{
    return addslashes($string);
}
/**
 * Undocumented function
 *
 * @param string $msg
 * @param boolean $die
 * @author 一花 <487735913@qq.com>
 * @copyright Undocumented function string  boolean
 */
function sysmsg($msg = '未知的异常', $die = true)
{
?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>站点提示信息</title>
        <style type="text/css">
            html {
                background: #eee;
                text-align: center;
            }

            body {
                background: #fff;
                color: #333;
                font-family: "微软雅黑", "Microsoft YaHei", sans-serif;
                margin: 2em auto;
                padding: 1em 2em;
                max-width: 700px;
                -webkit-box-shadow: 10px 10px 10px rgba(0, 0, 0, .13);
                box-shadow: 10px 10px 10px rgba(0, 0, 0, .13);
                opacity: .8
            }

            h1 {
                border-bottom: 1px solid #dadada;
                clear: both;
                color: #666;
                font: 24px "微软雅黑", "Microsoft YaHei", sans-serif;
                margin: 30px 0 0 0;
                padding: 0;
                padding-bottom: 7px;
            }

            #error-page {
                margin-top: 50px;
            }

            h3 {
                text-align: center
            }

            #error-page p {
                font-size: 9px;
                line-height: 1.5;
                margin: 25px 0 20px
            }

            #error-page code {
                font-family: Consolas, Monaco, monospace
            }

            ul li {
                margin-bottom: 10px;
                font-size: 9px
            }

            a {
                color: #21759B;
                text-decoration: none;
                margin-top: -10px
            }

            a:hover {
                color: #D54E21
            }

            .button {
                background: #f7f7f7;
                border: 1px solid #ccc;
                color: #555;
                display: inline-block;
                text-decoration: none;
                font-size: 9px;
                line-height: 26px;
                height: 28px;
                margin: 0;
                padding: 0 10px 1px;
                cursor: pointer;
                -webkit-border-radius: 3px;
                border-radius: 3px;
                white-space: nowrap;
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
                -webkit-box-shadow: inset 0 1px 0 #fff, 0 1px 0 rgba(0, 0, 0, .08);
                box-shadow: inset 0 1px 0 #fff, 0 1px 0 rgba(0, 0, 0, .08);
                vertical-align: top;
                -webkit-appearance: none;
                appearance: none;
            }

            .button.button-large {
                height: 29px;
                line-height: 28px;
                padding: 0 12px
            }

            .button:focus,
            .button:hover {
                background: #fafafa;
                border-color: #999;
                color: #222
            }

            .button:focus {
                -webkit-box-shadow: 1px 1px 1px rgba(0, 0, 0, .2);
                box-shadow: 1px 1px 1px rgba(0, 0, 0, .2)
            }

            .button:active {
                background: #eee;
                border-color: #999;
                color: #333;
                -webkit-box-shadow: inset 0 2px 5px -3px rgba(0, 0, 0, .5);
                box-shadow: inset 0 2px 5px -3px rgba(0, 0, 0, .5)
            }

            table {
                table-layout: auto;
                border: 1px solid #333;
                empty-cells: show;
                border-collapse: collapse
            }

            th {
                padding: 4px;
                border: 1px solid #333;
                overflow: hidden;
                color: #333;
                background: #eee
            }

            td {
                padding: 4px;
                border: 1px solid #333;
                overflow: hidden;
                color: #333
            }
        </style>
    </head>

    <body id="error-page">
        <?php echo '<h3>站点提示信息</h3>';
        echo $msg; ?>
    </body>

    </html>
<?php
    if ($die == true) {
        exit;
    }
}
/**
 * Undocumented function
 *
 * @param [type] $length
 * @param [type] $qianzhui
 * @param integer $numeric
 * @author 一花 <487735913@qq.com>
 * @copyright Undocumented function [type]  [type]
 */
function random($length, $qianzhui = null, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $qianzhui != null ? $qianzhui . $hash : $hash;
}

function queryuserall($adminpassword, $adminport, $proxyaddress)
{
    $url = "http://" . $proxyaddress . ":" . $adminport . "/account";
    parse_url($url);
    $data = array();
    $query_str = http_build_query($data); // http_build_query()函数的作用是使用给出的关联（或下标）数组生成一个经过 URL-encode 的请求字符串
    $info = parse_url($url);
    $fp = fsockopen($proxyaddress, $adminport, $errno, $errstr, 3);
    if (!$fp) {
        // echo "$errstr ($errno)<br>\n";
        return false;
    } else {
        $auth = "Authorization: Basic " . base64_encode("admin:" . $adminpassword);
        $head = "GET " . $info['path']  . $query_str . " HTTP/1.0\r\n";
        $head .= "Host: " . $info['host'] . "\r\n" . $auth . "\r\n" . "\r\n";
        $write = fputs($fp, $head);
        $line = "";
        while (!feof($fp)) {
            $line .= fread($fp, 4096);
            // echo str_replace(array("<",">","/"),array("<",">",""), $line);
        }
        fclose($fp);
    }
    //echo $line; 
    //取出div标籤且id为PostContent的内容，并储存至阵列match
    preg_match_all('/<input .* name="username" .* value="(.*?)"/ui', $line, $match);
    preg_match_all('/<input .* name="password" .* value="(.*?)"/ui', $line, $match2);
    preg_match_all('/<input .* name="enable" .*/', $line, $match3);
    preg_match_all('/<input .* name="usepassword" .*/', $line, $match4);
    preg_match_all('/<input .* name="disabledate" .* value="(.*?)"/ui', $line, $match5);
    preg_match_all('/<input .* name="disabletime" .* value="(.*?)"/ui', $line, $match6);
    preg_match_all('/<input .* name="autodisable" .*/', $line, $match7);
    preg_match_all('/<input .* name="connection" .* value="(.*?)"/ui', $line, $match8);
    preg_match_all('/<input .* name="bandwidth" .* value="(.*?)"/ui', $line, $match9);



    $ccp = array();
    $time = date("Y-m-d H:i:s");
    foreach ($match[1] as $key => $use) {
        // print(str_replace(array("<",">","/"),array(""),$match3[0][$key]));
        //=='input type="checkbox" name="enable" value="1" checked'?$match3[0][$key]=0:$match3[0][$key]=1
        strripos(str_replace(array("<", ">", "/"), array(""), $match3[0][$key]), "checked") != "46" ? $match3[0][$key] = 0 : $match3[0][$key] = 1;
        //str_replace(array("<",">","/"),array(""),$match3[0][$key])=='input type="checkbox" name="enable" value="1" checked'?$match3[0][$key]=0:$match3[0][$key]=1;
        strripos(str_replace(array("<", ">", "/"), array(""), $match4[0][$key]), "checked") != "51" ? $match4[0][$key] = 0 : $match4[0][$key] = 1;
        strripos(str_replace(array("<", ">", "/"), array(""), $match7[0][$key]), "checked") != "51" ? $match7[0][$key] = 0 : $match7[0][$key] = 1;
        if ($match[1][$key] == "") {
            continue;
        }
        $ccp[$key] = array(
            "id" => $key,
            "user" => $match[1][$key],
            "pwd" => $match2[1][$key],
            "state" => $match3[0][$key],
            "pwdstate" => $match4[0][$key] ?? '0',
            "disabletime" => ($match5[1][$key] ?? '') . " " . ($match6[1][$key] ?? ''),
            "expire" => strtotime($time) > strtotime(($match5[1][$key] ?? '') . " " . ($match6[1][$key] ?? '')) ? 1 : 0,
            "connection" => $match8[1][$key] ?? '-1',
            "bandwidthup" => isset($match9[1][$key]) ? (explode("/", $match9[1][$key])[0] ?? '-1') : '-1',
            "bandwidthdown" => isset($match9[1][$key]) ? (explode("/", $match9[1][$key])[1] ?? '-1') : '-1',
            "autodisable" => $match7[0][$key] ?? '0'
        );
    }

    return $ccp;
}

function userquery($column, $ccp)
{
    //    ="admin";
    $result = array_filter($ccp, function ($where) use ($column) {
        return $where['user'] == $column;
    });
    // print_r($result);//打印全部数组
    // $col=array_column($result,'disabletime');//expire
    //  $col2=array_column($result,'expire');
    //	return $col2[0]==1?'<h5 style="color: red;display: inline;">到期时间：'.$col[0].'</h5>':($col[0]!=""?'<h5 style="color: #1E9FFF;display: inline;">到期时间：'.$col[0].'</h5>':'<h5 style="color: red;display: inline;">账号不存在</h5>');
    return $result;
}

function WriteLog($operation, $msg, $operationer, $DB)
{
    $arr = array(
        'operation'  => addslashes(str_replace(array("<", ">", "/"), array("<", ">", ""), $operation)),
        'msg' => addslashes(str_replace(array("<", ">", "/"), array("<", ">", ""), $msg)),
        'operationer'     => addslashes(str_replace(array("<", ">", "/"), array("<", ">", ""), $operationer)),
        'ip'  => addslashes(str_replace(array("<", ">", "/"), array("<", ">", ""), x_real_ip()))
    );
    $exec = $DB->insert('log', $arr);
}
/**
 * @description: 更新用户信息
 */
function UserUpdate($adminpassword, $adminport, $proxyaddress, $user, $password, $day,$connection2,$bandwidthup,$bandwidthdown, $userenable="0",$newuser="")
{
    $ser = queryuserall($adminpassword, $adminport, $proxyaddress);
    $date = userquery($user, $ser);
    
    if (is_null($date)&&(empty($date))) {
        return ["code" => "-1", "msg"=>"用户名不存在", "icon" => "5"];
    }
    
    $username = $user;
    $connection = $connection2;
    $bandwidth = $bandwidthup.'/'.$bandwidthdown;
    
    // 分割传入的日期时间
    $end_date = explode(" ", $day);
    if(count($end_date) != 2) {
        return ["code" => "-1", "msg"=>"日期时间格式错误", "icon" => "5"];
    }
    $disabledate = $end_date[0];
    $disabletime = $end_date[1];

    $fp = fsockopen($proxyaddress, $adminport, $errno, $errstr, 3);
    if (!$fp) {
        return ["code" => "-1", "msg"=>"无法连接到CCProxy", "icon" => "5"];
    }
    
    $url_ = "/account";
    $url = "edit=1" . "&";
    $url = $url . "autodisable=1" . "&";
    $url = $url . "usepassword=1" . "&";
    $url = $url . "enablesocks=1" . "&";
    $url = $url . "enablewww=0" . "&";
    $url = $url . "enabletelnet=0" . "&";
    $url = $url . "enabledial=0" . "&";
    $url = $url . "enableftp=0" . "&";
    $url = $url . "enableothers=0" . "&";
    $url = $url . "enablemail=0" . "&";
    $url = $url . "username=" . (empty($newuser)?$username:$newuser) . "&";
    $url = $password == "" ? $url : $url . "password=" . $password . "&";
    $url = $url . "connection=" . $connection . "&";
    $url = $url . "bandwidth=" . $bandwidth . "&";
    $url = $url . "disabledate=" . $disabledate . "&";
    $url = $url . "disabletime=" . $disabletime . "&";
    $url = $url . "bandwidthquota=4560" . "&";
    if($userenable==0){
        $url = $url . "enable=1" . "&";
    }
    $url = $url . "userid=" . $username;
    
    $len = "Content-Length: " . strlen($url);
    $auth = "Authorization: Basic " . base64_encode("admin:" . $adminpassword);
    $msg = "POST " . $url_ . " HTTP/1.1\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
    fputs($fp, $msg);
    while (!feof($fp)) {
        $s = fgets($fp, 4096);
    }
    fclose($fp);
    return ["code" => "1", "msg"=>"编辑成功", "icon" => "1"];
}
/**
 * @description: 验证用户名是否合法（只允许字母、数字和下划线）
 * @param {*} $str
 * @return {*}
 * @use: 
 */
function CheckStrChinese($str)
{
    if(empty($str)) {
        return false;
    }
    // 允许字母、数字和下划线
    $isMatched = preg_match('/^[A-Za-z0-9_-]+$/', $str);
    return $isMatched === 1;
}
/**
 * @description: 匹配密码密码可以包含数字、字母、下划线，并且要同时含有数字和字母，且长度要在8-16位之间!
 * @param {*} $str
 * @return {*}
 * @use: 
 */
function CheckStrPwd($str)
{
    $isMatched = preg_match_all('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z_]{5,16}$/', $str);
    if ($isMatched&&(!empty($str))) {
        return true;
    }
    return false;
}
function ForServer($server, $user)
{
   try {
    $user_arr = array();
    foreach ($server as $key => $value) {
        $alldata = queryuserall($value["password"], $value["cport"], $value["ip"]);
        if (!$alldata) {
            continue;
        }
        if (is_array($alldata)) {
            foreach ($alldata as $userData) {
                if (empty($userData['user'])) {
                    continue;
                }
                $getdata = array(
                    "id" => $userData['id'],
                    "user" => $userData['user'],
                    "pwd" => $userData['pwd'],
                    "state" => $userData['state'],
                    "pwdstate" => $userData['pwdstate'],
                    "disabletime" => $userData['disabletime'],
                    "expire" => $userData['expire'],
                    "user" => $userData['user'],
                    'serverip' => $value["ip"],
                    "connection" => isset($userData['connection']) ? $userData['connection'] : -1,
                    "bandwidthup" => isset($userData['bandwidthup']) ? $userData['bandwidthup'] : -1,
                    "bandwidthdown" => isset($userData['bandwidthdown']) ? $userData['bandwidthdown'] : -1,
                    "autodisable" => isset($userData['autodisable']) ? $userData['autodisable'] : 1
                );
                $user_arr[] = $getdata;
            }
        }
    }
    
    if (empty($user_arr)) {
        yield [["code" => "-1", "msg" => "未找到任何用户数据", "icon" => "5"]];
        return;
    }

    if (!empty($user)) {
        $filtered = userquery($user, $user_arr);
        if (empty($filtered)) {
            yield [["code" => "-1", "msg" => "未找到匹配的用户", "icon" => "5"]];
            return;
        }
        yield $filtered;
    } else {
        yield $user_arr;
    }
   } catch (Exception $th) {
     yield [["code" => "-1", "msg" => "处理用户数据时出错: " . $th->getMessage(), "icon" => "5"]];
   }
}
/***
 * 搜索全部服务器，也可以根据条件来
 */
function SerchearchAllServer($app, $user, $DB)
{
   try {
    $tj = (!empty($app)) ? "where appcode='$app'" : "";
    $ip = $DB->select("select serverip from application " . $tj);
    $serverarr = array();
    foreach ($ip as $valuel) {
        $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $valuel['serverip'] . "'"); //$ip['serverip']服务器IP
        if($server) {
            Array_push($serverarr, $server);
        }
    };
    if(empty($serverarr)) {
        yield [["code" => "-1", "msg" => "未找到有效的服务器", "icon" => "5"]];
        return;
    }
    yield from ForServer($serverarr, $user);
   } catch (Exception $th) {
    yield [["code" => "-1", "msg" => "无法连接到CCProxy: " . $th->getMessage(), "icon" => "5"]];
   }
}
/**
 * 删除用户
 */
function IDelUser($username, $admin_password, $adminport, $proxyaddress)
{
   try {
    if (!empty($username)) {
        $url = "http://" . $proxyaddress . ":" . $adminport . "/account";
        $fp = fsockopen($proxyaddress, $adminport, $errno, $errstr, 3);
        if (!$fp) {
            yield ["code" => "无法连接到CCProxy", "icon" => "5"];
        } else {
            $url_ = "/account";
            $url = "delete=1" . "&";
            $url = $url . "userid=" . $username;
            $len = "Content-Length: " . strlen($url);
            $auth = "Authorization: Basic " . base64_encode("admin:" . $admin_password);
            $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
            fputs($fp, $msg);
            while (!feof($fp)) {
                $s = fgets($fp, 4096);
            }
            fclose($fp);
            yield true;
        }
    } else {
        yield false;
    }
   } catch (Exception $th) {
    //yield null;
    //throw $th;
    return ["code" => "-1", "msg"=>"无法连接到CCProxy", "icon" => "5"];
   }
}
/**
 * 具体删除，批量 线程 删除
 */
function DelUser($user, $serverip, $DB)
{
try {
    $server = $DB->selectRow("select ip,serveruser,password,cport from server_list where ip='" . $serverip . "'"); //$ip['serverip']服务器IP
    yield from IDelUser($user, $server['password'], $server['cport'], $server['ip']);
} catch (Exception $th) {
   // throw $th;
    //yield null;
    return ["code" => "-1", "msg"=>"无法连接到CCProxy", "icon" => "5"];
}
}


function AddUser($proxyaddress,$admin_password,$admin_port,$userdata)
{
  try {
    $user=queryuserall($admin_password,$admin_port,$proxyaddress);
    if(!existsuser($userdata["user"],$user)){
        $json=[
            "code"=>-1,
            "msg"=>"账号已经存在",
            "icon" => "5"
        ];
        return $json;
    }
    
    $ipaddress = "";
    $macaddress = "";
    $connection = "-1";
    $bandwidth = "-1";
    $date=date("Y-m-d H:i:s");
    if(isset($userdata["expire"]) && $userdata["expire"] == "-1") {
        // 自定义时间
        if(empty($userdata["use_date"])) {
            return ["code" => "-1", "msg"=>"自定义时间不能为空", "icon" => "5"];
        }
        // 确保日期格式正确
        if(!is_Date($userdata["use_date"])) {
            $enddate = date('Y-m-d H:i:s', strtotime($userdata["use_date"] . " 23:59:59"));
        } else {
            $enddate = $userdata["use_date"];
        }
    } else if(isset($userdata["expire"]) && is_numeric($userdata["expire"]) && $userdata["expire"] > 0) {
        // 固定天数
        $days = floatval($userdata["expire"]);
        $totalSeconds = round($days * 24 * 3600);
        $enddate = date('Y-m-d H:i:s', strtotime("+{$totalSeconds} seconds"));
    } else if(isset($userdata["expire"]) && !empty($userdata["expire"])) {
        // 直接使用传入的expire值(用于支付接口等场景)
        $enddate = $userdata["expire"];
    } else {
        return ["code" => "-1", "msg"=>"无效的到期时间设置", "icon" => "5"];
    }
       
    $end_date = explode(" ", $enddate);
    $disabledate = $end_date[0];
    $disabletime = $end_date[1];
    $fp = fsockopen($proxyaddress, $admin_port, $errno, $errstr, 3);
    if (!$fp) {
        return ["code" => "-1", "msg"=>"无法连接到CCProxy", "icon" => "5"];
    }
    
    $url_ = "/account";
    $url = "add=1" . "&";
    $url = $url . "autodisable=1" . "&";
    $url = $url . "enable=1" . "&";
    if($admin_password!="") {
        $url = $url . "usepassword=1" . "&";
    }
    if($ipaddress!=""){
        $url = $url . "usepassword=1" . "&";
    }
    if($macaddress!=""){
        $url = $url . "usemacaddress=1" . "&";
    }
    $url = $url . "enablesocks=1" . "&";
    $url = $url . "enablewww=0" . "&";
    $url = $url . "enabletelnet=0" . "&";
    $url = $url . "enabledial=0" . "&";
    $url = $url . "enableftp=0" . "&";
    $url = $url . "enableothers=0" . "&";
    $url = $url . "enablemail=0" . "&";
    $url = $url . "username=" . $userdata["user"] . "&";
    $url = $url . "password=" . $userdata["pwd"] . "&";
    $url = $url . "ipaddress=" . $ipaddress . "&";
    $url = $url . "macaddress=" . $macaddress . "&";
    $url = $url . "connection=" . $connection . "&";
    $url = $url . "bandwidth=" . $bandwidth . "&";
    $url = $url . "disabledate=" . $disabledate . "&";
    $url = $url . "disabletime=" . $disabletime . "&";
    $url = $url . "userid=-1";
    $len = "Content-Length: " . strlen($url);
    $auth = "Authorization: Basic " . base64_encode("admin:" . $admin_password);
    $msg = "POST " . $url_ . " HTTP/1.0\r\nHost: " . $proxyaddress . "\r\n" . $auth . "\r\n" . $len . "\r\n" . "\r\n" . $url;
    fputs($fp, $msg);
    while (!feof($fp)) {
        $s = fgets($fp, 4096);
    }
    fclose($fp);
    return ["code" => "1", "msg"=>"注册用户成功", "icon" => "1"];
  } catch (Exception $th) {
    return ["code" => "-1", "msg"=>"无法连接到CCProxy", "icon" => "5"];
  }
}
/**
 * @description: 账号存在 false 不存在为 true
 * @param {*} $column
 * @param {*} $ccp
 * @return {*} bool
 * @use: 
 */
function existsuser($column,$ccp){
    if(empty($column)){
        return "不能为空！";
    }
//    ="admin";
    $result = array_filter($ccp, function ($where) use ($column) {
       return $where['user'] == $column;
   });
   return empty($result);
    // print_r($result);//打印全部数组
//    $col=array_column($result,'disabletime');//expire
//    $col2=array_column($result,'expire');
    //return $col2[0]==1?'<h5 style="color: red;display: inline;">到期时间：'.$col[0].'</h5>':($col[0]!=""?'<h5 style="color: #1E9FFF;display: inline;">到期时间：'.$col[0].'</h5>':'<h5 style="color: red;display: inline;">账号不存在</h5>');
}

function ValidIp($ip){
    $preg="/^((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))/";
    preg_match($preg,$ip,$matches);
    if(!empty($matches)&&!empty($ip)){
       return true;
    }
    return false;
}

function ValidPort($port){
    if ($port<=65535&&!empty($port)) {
        return true;
    }
    return false;
}

function check_spider() {
    $useragent=strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($useragent,'baiduspider')!==false) {
        return 'baiduspider';
    }
    if (strpos($useragent,'360spider')!==false) {
        return '360spider';
    }
    if (strpos($useragent,'soso')!==false) {
        return 'soso';
    }
    if (strpos($useragent,'bing')!==false) {
        return 'bing';
    }
    if (strpos($useragent,'yahoo')!==false) {
        return 'yahoo';
    }
    if (strpos($useragent,'sohu-search')!==false) {
        return 'Sohubot';
    }
    if (strpos($useragent,'sogou')!==false) {
        return 'sogou';
    }
    if (strpos($useragent,'youdaobot')!==false) {
        return 'YoudaoBot';
    }
    if (strpos($useragent,'yodaobot')!==false) {
        return 'YodaoBot';
    }
    if (strpos($useragent,'robozilla')!==false) {
        return 'Robozilla';
    }
    if (strpos($useragent,'msnbot')!==false) {
        return 'msnbot';
    }
    if (strpos($useragent,'lycos')!==false) {
        return 'Lycos';
    }
    if (!strpos($useragent,'ia_archiver')===false) {
    } else {
        if (!strpos($useragent,'iaarchiver')===false) {
            return 'alexa';
        }
    }
    if (strpos($useragent,'robozilla')!==false) {
        return 'Robozilla';
    }
    if (strpos($useragent,'sitebot')!==false) {
        return 'SiteBot';
    }
    if (strpos($useragent,'mj12bot')!==false) {
        return 'MJ12bot';
    }
    if (strpos($useragent,'gosospider')!==false) {
        return 'gosospider';
    }
    if (strpos($useragent,'gigabot')!==false) {
        return 'Gigabot';
    }
    if (strpos($useragent,'yrspider')!==false) {
        return 'YRSpider';
    }
    if (strpos($useragent,'gigabot')!==false) {
        return 'Gigabot';
    }
    if (strpos($useragent,'jikespider')!==false) {
        return 'jikespider';
    }
    if (strpos($useragent,'etaospider')!==false) {
        return 'EtaoSpider';
    }
    if (strpos($useragent,'foxspider')!==false) {
        return 'FoxSpider';
    }
    if (strpos($useragent,'docomo')!==false) {
        return 'DoCoMo';
    }
    if (strpos($useragent,'yandexbot')!==false) {
        return 'YandexBot';
    }
    if (strpos($useragent,'sinaweibobot')!==false) {
        return 'SinaWeiboBot';
    }
    if (strpos($useragent,'catchbot')!==false) {
        return 'CatchBot';
    }
    if (strpos($useragent,'surveybot')!==false) {
        return 'SurveyBot';
    }
    if (strpos($useragent,'dotbot')!==false) {
        return 'DotBot';
    }
    if (strpos($useragent,'purebot')!==false) {
        return 'Purebot';
    }
    if (strpos($useragent,'ccbot')!==false) {
        return 'CCBot';
    }
    if (strpos($useragent,'mlbot')!==false) {
        return 'MLBot';
    }
    if (strpos($useragent,'adsbot-google')!==false) {
        return 'AdsBot-Google';
    }
    if (strpos($useragent,'ahrefsbot')!==false) {
        return 'AhrefsBot';
    }
    if (strpos($useragent,'spbot')!==false) {
        return 'spbot';
    }
    if (strpos($useragent,'augustbot')!==false) {
        return 'AugustBot';
    }
    return false;
}
function cc_defender() {
    $iptoken=md5(x_real_ip().date('Ymd')).md5(TIMESTAMP.rand(11111,99999));
    if ((!isset($_COOKIE['sec_defend']) || !substr($_COOKIE['sec_defend'],0,32)===substr($iptoken,0,32))) {
        if (!isset($_COOKIE['sec_defend_time'])) {
            $_COOKIE['sec_defend_time']=0;
        }
        $sec_defend_time=$_COOKIE['sec_defend_time']+1;
        include_once(SYSTEM_ROOT.'hieroglyphy.class.php');
        $x=new hieroglyphy();
        $setCookie=$x->hieroglyphyString($iptoken);
        header('Content-type:text/html;charset=utf-8');
        if ($sec_defend_time>=10) {
            exit('浏览器不支持COOKIE或者不正常访问！');
        }
        echo '<html><head><meta http-equiv="pragma" content="no-cache"><meta http-equiv="cache-control" content="no-cache"><meta http-equiv="content-type" content="text/html;charset=utf-8"><title>正在加载中</title><script>function setCookie(name,value){var exp = new Date();exp.setTime(exp.getTime() + 60*60*1000);document.cookie = name + "="+ escape (value).replace(/\\+/g, \'%2B\') + ";expires=" + exp.toGMTString() + ";path=/";}function getCookie(name){var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");if(arr=document.cookie.match(reg))return unescape(arr[2]);else return null;}var sec_defend_time=getCookie(\'sec_defend\')||0;sec_defend_time++;setCookie(\'sec_defend\','.$setCookie.');setCookie(\'sec_defend_time\',sec_defend_time);if(sec_defend_time>1)window.location.href="./index.php";else window.location.reload();</script></head><body></body></html>';
        exit(0);
    } elseif (isset($_COOKIE['sec_defend_time'])) {
        setcookie('sec_defend_time', '', TIMESTAMP - 604800, '/');
    }
}

/**
 * x_real_ip function
 *
 * @author 一花 <487735913@qq.com>
 * @copyright x_real_ip function 一花  487735913@qq.com
 */
function x_real_ip()
{
	$ip=$_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}#s',$_SERVER['HTTP_X_FORWARDED_FOR'],$matches)) 
	{
		foreach($matches[0] as $xip)
		{
			if (!preg_match('#^(10|172\\.16|192\\.168)\\.#',$xip)) 
			{
				$ip=$xip;
			}
			else 
			{
				continue;
			}
		}
	}
	else 
	{
		if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CLIENT_IP'])) 
		{
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		else 
		{
			if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && preg_match('/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CF_CONNECTING_IP'])) 
			{
				$ip=$_SERVER['HTTP_CF_CONNECTING_IP'];
			}
			else 
			{
				if ((isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^([0-9]{1,3}\\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_X_REAL_IP']))) 
				{
					$ip=$_SERVER['HTTP_X_REAL_IP'];
				}
			}
		}
	}
	return $ip;
}


/* 

* 方法 isDate 

* 功能 判断日期格式是否正确 

* 参数 $str 日期字符串 

$format 日期格式 

* 返回 ��� 

*/
function is_Date($str,$format='Y-m-d H:i:s',$split='-'){ 

    $validStr=explode($split,explode(" ",$str)[0]);

    if(intval($validStr[0])<=0) return false; 

    if(intval($validStr[1])<=0) return false; 

    if(intval($validStr[2])<=0) return false; 

    $unixTime_1=strtotime($str); 

    if(!is_numeric($unixTime_1)) return false; //���果不是数字格式，则直接返回 

    $checkDate=date($format,$unixTime_1); 

    $unixTime_2=strtotime($checkDate); 

    if($unixTime_1==$unixTime_2){ 

        return true; 

    }else{ 

        return false; 

    } 

}

/**
 * 转换卡密时长为汉字
 */
function KamiPaeseString($str)
{
    $res=str_replace(array("+", " ", "year", "month", "day", "hour", "minute"), array("", "", "年","月","天","时","分"), $str);
    return $res;
}

/**
 * 增强的密码哈希函数,保持向后兼容
 */
function safePasswordHash($password) {
    if(empty($password)) return '';
    // 如果密码已经是md5,保持原样
    if(preg_match('/^[a-f0-9]{32}$/', $password)) {
        return $password;
    }
    // 新密码使用更安全的方式
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 增强的密码验证函数,保持向后兼容
 */
function safePasswordVerify($password, $hash) {
    if(empty($password) || empty($hash)) return false;
    // 如果是md5格式,使用旧的验证方式
    if(preg_match('/^[a-f0-9]{32}$/', $hash)) {
        return md5($password) === $hash;
    }
    // 新密码使用新的验证方式
    return password_verify($password, $hash);
}

/**
 * 增强的密码复杂度检查,但保持兼容性
 */
function checkPasswordStrength($password) {
    // 如果是旧系统的密码,直接返回true
    if(strlen($password) == 32 && preg_match('/^[a-f0-9]{32}$/', $password)) {
        return true;
    }
    
    // 新密码复杂度要求
    $length = strlen($password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
    
    // 基本要求:长度>=8
    if($length < 8) {
        return false;
    }
    
    // 必须满足以下条件之一:
    // 1. 包含数字和字母,长度>=8
    // 2. 包含大小写字母和数字,长度>=6
    // 3. 包含特殊字符,长度>=6
    if(($hasNumber && ($hasLower || $hasUpper) && $length >= 8) ||
       ($hasLower && $hasUpper && $hasNumber && $length >= 6) ||
       ($hasSpecial && $length >= 6)) {
        return true;
    }
    
    return false;
}

?>
