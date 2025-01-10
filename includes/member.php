<?php


if (!defined('IN_CRONLITE')) exit();
if (isset($_COOKIE["sub_admin_token"])) {
	$cookies = authcode(daddslashes($_COOKIE['sub_admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid) = explode("\t", $cookies);
	if ($cookies && $DB->selectRow("select * from sub_admin where username='$user' and cookies='{$_COOKIE['sub_admin_token']}' limit 1")) {
		if ($users = $DB->selectRow("select * from sub_admin where username='$user' limit 1")) {
			$session = md5($users['username'] . $users['password'] . $password_hash);
			if ($session == $sid) {
				$islogin = 1;
			}
		}
	}
}
