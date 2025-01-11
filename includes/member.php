<?php


if (!defined('IN_CRONLITE')) exit();

$islogin = 0;
if (isset($_COOKIE["sub_admin_token"])) {
	$cookies = authcode(daddslashes($_COOKIE['sub_admin_token']), 'DECODE', SYS_KEY);
	if ($cookies) {
		$cookie_parts = explode("\t", $cookies);
		if (count($cookie_parts) >= 2) {
			$user = $cookie_parts[0];
			$sid = $cookie_parts[1];
			
			if ($DB->selectRow("select * from sub_admin where username=? and cookies=? limit 1", [$user, $_COOKIE['sub_admin_token']])) {
				if ($users = $DB->selectRow("select * from sub_admin where username=? limit 1", [$user])) {
					$session = md5($users['username'] . $users['password'] . $password_hash);
					if ($session == $sid) {
						$islogin = 1;
					}
				}
			}
		}
	}
}

// 安全获取请求URI
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$uri_parts = parse_url($request_uri);
$path = $uri_parts['path'] ?? '';
$query = $uri_parts['query'] ?? '';

// 解析查询参数
$query_params = [];
if (!empty($query)) {
    parse_str($query, $query_params);
}

// 过滤参数
array_walk_recursive($query_params, function(&$value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
});
