<?php


/**
 * 安裝ajax.php文件
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Shanghai');
@header('Content-Type: application/json; charset=UTF-8');
require_once './db.class.php';
include_once("./class.php");
$class = new install();

$_QET = $class->daddslashes($_REQUEST);

// 移除安装锁检查
$act = isset($_GET["act"]) ? $_GET["act"] : "";
switch ($act) {
    case 1: #安装数据库
        if (empty($_QET['host']) || empty($_QET['port']) || empty($_QET['user']) || empty($_QET['pwd']) || empty($_QET['dbname'])) {
            die(json_encode([
                'code' => -1,
                'msg' => '请确保每一项都不为空！',
                'debug' => $_QET  // 输出接收到的参数用于调试
            ]));
        }

        // 1. 首先验证数据库连接
        if (!$con = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
            if (DB::connect_errno() == 2002)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库地址填写错误！']));
            elseif (DB::connect_errno() == 1045)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库用户名或密码填写错误！']));
            elseif (DB::connect_errno() == 1049)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库名不存在！']));
            else
                die(json_encode(['code' => DB::connect_errno(), 'msg' => '连接数据库失败' . DB::connect_error()]));
        }

        // 2. 检查数据库是否已安装
        if (DB::get_row("select * from information_schema.TABLES where TABLE_NAME = 'sub_admin'") != null) {
            die(json_encode(['code' => -2, 'msg' => '数据库已存在相关表,是否清空重新安装?']));
        }

        // 3. 写入配置文件
        $ar = $class->ModifyFileContents($_QET);
        if ($ar['code'] <> 1) {
            die(json_encode(['code' => -1, 'msg' => $ar['msg']]));
        }

        // 4. 导入数据库
        $sql_file = "./ccpy.sql";
        if (!file_exists($sql_file)) {
            die(json_encode([
                'code' => -1,
                'msg' => 'SQL文件不存在，请检查ccpy.sql是否在正确位置'
            ]));
        }

        if ($_QET['state'] != 2) { // state=2 表示更新模式
            try {
                // 确保数据库连接正常
                if (!DB::query("SELECT 1")) {
                    // 重新连接数据库
                    if (!DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
                        die(json_encode(['code' => -1, 'msg' => '数据库连接失败：' . DB::connect_error()]));
                    }
                }

                // 删除所有表
                if (!DB::drop_all_tables()) {
                    die(json_encode(['code' => -1, 'msg' => '清空数据库失败：' . DB::error()]));
                }

                // 验证是否还有表存在
                $check = DB::query("SHOW TABLES");
                $remaining = [];
                while($row = DB::fetch($check)) {
                    $remaining[] = reset($row);
                }
                
                if (!empty($remaining)) {
                    die(json_encode([
                        'code' => -1,
                        'msg' => "清空数据库失败，以下表仍然存在：\n" . implode(", ", $remaining)
                    ]));
                }
            } catch (Exception $e) {
                die(json_encode(['code' => -1, 'msg' => '清空数据库时发生错误：' . $e->getMessage()]));
            }
        }

        // 读取SQL文件
        $sql_file = './ccpy.sql';
        $sql = file_get_contents($sql_file);
        if ($sql === false) {
            die(json_encode(['code' => -1, 'msg' => 'SQL文件读取失败，请检查文件权限']));
        }

        // 设置数据库环境
        DB::query("SET NAMES utf8");
        DB::query("SET FOREIGN_KEY_CHECKS = 0");
        DB::query("SET UNIQUE_CHECKS = 0");
        DB::query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        DB::query("SET AUTOCOMMIT = 0");
        DB::query("START TRANSACTION");

        // 获取当前访问的域名或IP
        $server_name = '';
        
        // 优先使用 HTTP_X_FORWARDED_HOST (内网穿透场景)
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $server_name = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } 
        // 其次使用 HTTP_HOST
        else if (!empty($_SERVER['HTTP_HOST'])) {
            $server_name = $_SERVER['HTTP_HOST'];
        }
        // 最后使用 SERVER_NAME
        else if (!empty($_SERVER['SERVER_NAME'])) {
            $server_name = $_SERVER['SERVER_NAME'];
            $server_port = $_SERVER['SERVER_PORT'];
            // 只有使用 SERVER_NAME 时才需要手动加端口
            if ($server_port != '80' && $server_port != '443') {
                $server_name .= ':' . $server_port;
            }
        }
        
        if (empty($server_name)) {
            exit(json_encode(['code' => -1, 'msg' => '无法获取当前域名，请检查服务器配置'], JSON_UNESCAPED_UNICODE));
        }

        // 替换SQL中的域名
        $sql = str_replace('192.168.31.134:8882', $server_name, $sql);

        // 分割SQL语句
        $statements = [];
        $current_statement = '';
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 跳过注释和空行
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
                continue;
            }
            
            $current_statement .= ' ' . $line;
            
            // 如果行末尾是分号，说明一条语句结束
            if (substr(trim($line), -1) === ';') {
                $statements[] = trim($current_statement);
                $current_statement = '';
            }
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        // 第一阶段：创建表结构
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            if (strpos(trim($statement), '--') === 0 || strpos(trim($statement), '/*') === 0) continue;
            
            // 只执行 CREATE TABLE 和 DROP TABLE 语句
            if (stripos($statement, 'CREATE TABLE') === false && stripos($statement, 'DROP TABLE') === false) {
                continue;
            }
            
            try {
                if (!DB::query($statement)) {
                    DB::query("ROLLBACK");  // 回滚事务
                    die(json_encode([
                        'code' => -1,
                        'msg' => '执行SQL语句失败：' . DB::error(),
                        'sql' => $statement
                    ]));
                } else {
                    $success++;
                }
            } catch (Exception $e) {
                DB::query("ROLLBACK");  // 回滚事务
                $failed++;
                $errors[] = "SQL: " . substr($statement, 0, 100) . "\n异常: " . $e->getMessage();
            }
        }

        // 第二阶段：执行其他SQL语句（数据插入等）
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            if (strpos(trim($statement), '--') === 0 || strpos(trim($statement), '/*') === 0) continue;
            
            // 跳过已执行的表结构语句
            if (stripos($statement, 'CREATE TABLE') !== false || stripos($statement, 'DROP TABLE') !== false) {
                continue;
            }
            
            try {
                if (!DB::query($statement)) {
                    DB::query("ROLLBACK");  // 回滚事务
                    die(json_encode([
                        'code' => -1,
                        'msg' => '执行SQL语句失败：' . DB::error(),
                        'sql' => $statement
                    ]));
                } else {
                    $success++;
                }
            } catch (Exception $e) {
                DB::query("ROLLBACK");  // 回滚事务
                $failed++;
                $errors[] = "SQL: " . substr($statement, 0, 100) . "\n异常: " . $e->getMessage();
            }
        }

        // 提交事务
        if (!DB::query("COMMIT")) {
            DB::query("ROLLBACK");
            die(json_encode(['code' => -1, 'msg' => '提交事务失败：' . DB::error()]));
        }

        // 恢复数据库设置
        DB::query("SET FOREIGN_KEY_CHECKS = 1");
        DB::query("SET UNIQUE_CHECKS = 1");
        DB::query("SET SQL_MODE = ''");

        // 检查执行结果
        if ($failed > 0) {
            die(json_encode([
                'code' => -1,
                'msg' => "安装失败！\nSQL执行情况：成功{$success}句，失败{$failed}句\n错误信息：\n" . implode("\n", $errors)
            ]));
        }

        // 安装完成
        die(json_encode([
            'code' => 1, 
            'msg' => "安装成功！", 
            'sql_count' => $success
        ]));
        break;
    case "clear_db": #清空数据库
        if (empty($_QET['host']) || empty($_QET['port']) || empty($_QET['user']) || empty($_QET['pwd']) || empty($_QET['dbname'])) {
            die(json_encode(['code' => -1, 'msg' => '请确保数据库配置不为空！']));
        }

        // 连接数据库
        if (!$con = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
            die(json_encode(['code' => -1, 'msg' => '连接数据库失败：' . DB::connect_error()]));
        }

        try {
            // 禁用外键检查
            DB::query("SET FOREIGN_KEY_CHECKS = 0");
            
            // 获取所有表名
            $tables = [];
            $result = DB::query("SHOW TABLES");
            while($row = DB::fetch($result)) {
                $tables[] = reset($row);
            }
            
            if (empty($tables)) {
                die(json_encode(['code' => 1, 'msg' => '数据库已经是空的']));
            }
            
            // 删除所有表
            foreach($tables as $table) {
                DB::query("DROP TABLE IF EXISTS `$table`");
            }
            
            // 验证是否还有表存在
            $check = DB::query("SHOW TABLES");
            $remaining = [];
            while($row = DB::fetch($check)) {
                $remaining[] = reset($row);
            }
            
            if (!empty($remaining)) {
                die(json_encode(['code' => -1, 'msg' => '以下表无法删除：' . implode(', ', $remaining)]));
            }
            
            die(json_encode(['code' => 1, 'msg' => '成功删除' . count($tables) . '个数据表']));
        } catch (Exception $e) {
            die(json_encode(['code' => -1, 'msg' => '清空数据库失败：' . $e->getMessage()]));
        } finally {
            DB::query("SET FOREIGN_KEY_CHECKS = 1");
        }
        break;
    case 2:
        if (empty($_QET['host']) || empty($_QET['port']) || empty($_QET['user']) || empty($_QET['pwd']) || empty($_QET['dbname'])) die(json_encode(['code' => -1, 'msg' => '请确保每一项都不为空！']));

        /**
         * 校验
         */

        if (!$con = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
            if (DB::connect_errno() == 2002)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库地址填写错误！']));
            elseif (DB::connect_errno() == 1045)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库用户名或密码填写错误！']));
            elseif (DB::connect_errno() == 1049)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库名不存在！']));
            else
                die(json_encode(['code' => DB::connect_errno(), 'msg' => '连接数据库失败' . DB::connect_error()]));
        }

        $DBS = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port']);
        if ($DBS) {
            if (DB::get_row("select * from information_schema.TABLES where TABLE_NAME  = 'sub_admin'") != null) {
                DB::query("set sql_mode = ''");
                DB::query("set names utf8");
                DB::query("DROP TABLE  application");
                DB::query("DROP TABLE  daili");
                DB::query("DROP TABLE  kami");
                DB::query("DROP TABLE  log");
                DB::query("DROP TABLE  order_list");
                DB::query("DROP TABLE  server_list");
                DB::query("DROP TABLE  sub_admin");
                DB::query("DROP TABLE  sup_admin");
                DB::query("DROP TABLE  app_server");
                $sql_file = "./ccpy.sql";
                if (!file_exists($sql_file)) {
                    die(json_encode([
                        'code' => -1,
                        'msg' => 'SQL文件不存在，请检查ccpy.sql是否在正确位置'
                    ]));
                }

                $sql = file_get_contents($sql_file);
                if ($sql === false) {
                    die(json_encode([
                        'code' => -1,
                        'msg' => 'SQL文件读取失败，请检查文件权限'
                    ]));
                }

                $sql = explode(';', $sql);
                $DBS = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port']);
                if (!$DBS)
                    die(json_encode(['code' => -1, 'msg' => DB::connect_error()]));
                $a = 0;
                $b = 0;
                $e = '';
                foreach ($sql as $v) {
                    $v = trim($v);
                    if (empty($v) || strpos($v, '--') === 0 || strpos($v, '/*') === 0) {
                        continue;
                    }
                    
                    if ($_QET['state'] == 2 && strstr($v, 'DROP TABLE IF EXISTS')) {
                        continue;
                    }
                    
                    try {
                        if (DB::query($v)) {
                            $a++;
                        } else {
                            $b++;
                            $e .= DB::error() . '<br/>';
                        }
                    } catch (Exception $ex) {
                        $b++;
                        $e .= $ex->getMessage() . '<br/>';
                    }
                }
                $site_url = $_SERVER['HTTP_HOST'];
                $sqluser = "UPDATE sub_admin SET siteurl='" . $site_url . "' WHERE username='admin'";
                DB::query($sqluser);
                if ($_QET['state'] == 2) {
                    @file_put_contents("./install.lock", '安装锁');
                    die(json_encode(['code' => 1, 'msg' => '安装完成！<br/>SQL成功' . $a . '句/失败' . $b . '句,未删除原数据,进入下一步即可!']));
                }
                if ($b == 0) {
                    @file_put_contents("./install.lock", '安装锁');
                    die(json_encode(['code' => 1, 'msg' => '安装完成！<br/>SQL成功' . $a . '句/失败' . $b . '句']));
                } else {
                    die(json_encode(['code' => -1, 'msg' => '安装失败,请清空数据库后重试<br/>如果只是更新请直接填写config文件<br/>SQL成功' . $a . '句/失败' . $b . '句<br/>错误信息：' . $e]));
                }
            }
        }else{
            die(json_encode(['code' => -1, 'msg' => DB::connect_error()]));
        }

        break;
    case 3:
        @file_put_contents("./install.lock", '安装锁');
        die(json_encode(['code' => 1, 'msg' => '安装完成！']));
        break;
    case 4:
        if (empty($_QET['host']) || empty($_QET['port']) || empty($_QET['user']) || empty($_QET['pwd']) || empty($_QET['dbname'])) die(json_encode(['code' => -1, 'msg' => '请确保每一项都不为空！']));

        /**
         * 校验
         */
        if (!$con = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
            if (DB::connect_errno() == 2002)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库地址填写错误！']));
            elseif (DB::connect_errno() == 1045)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库用户名或密码填写错误！']));
            elseif (DB::connect_errno() == 1049)
                die(json_encode(['code' => 2002, 'msg' => '连接数据库失败，数据库名不存在！']));
            else
                die(json_encode(['code' => DB::connect_errno(), 'msg' => '连接数据库失败' . DB::connect_error()]));
        }

        $DBS = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port']);
        if ($DBS) {
            if (DB::get_row("select * from information_schema.TABLES where TABLE_NAME  = 'sub_admin'") != null) {
                die(json_encode(['code' => 1, 'msg' => '已经安装过']));
            }else{
                die(json_encode(['code' => 0, 'msg' => '没有安装过']));
            }
        }else{
            die(json_encode(['code' => -1, 'msg' => DB::connect_error()]));
        }
        break;
    case 'check_files':
        $required_files = [
            './ccpy.sql' => 'SQL安装文件',
            '../config.php' => '配置文件'
        ];
        
        $missing_files = [];
        foreach ($required_files as $file => $desc) {
            if (!file_exists($file)) {
                $missing_files[] = $desc . '(' . $file . ')';
            }
        }
        
        if (!empty($missing_files)) {
            die(json_encode([
                'code' => -1,
                'msg' => '以下必要文件不存在：' . implode(', ', $missing_files)
            ]));
        }
        
        die(json_encode(['code' => 1, 'msg' => '文件检查通过']));
        break;
    case 'update_config':
        $host=isset($_POST['host'])?$_POST['host']:null;
        $port=isset($_POST['port'])?$_POST['port']:null;
        $user=isset($_POST['user'])?$_POST['user']:null;
        $pwd=isset($_POST['pwd'])?$_POST['pwd']:null;
        $dbname=isset($_POST['dbname'])?$_POST['dbname']:null;
        
        if($host==null || $port==null || $user==null || $pwd==null || $dbname==null){
            exit('{"code":-1,"msg":"请填写完整！"}');
        }
        
        try {
            $config_file = '../config.php';
            $config_content = "<?php
/*数据库配置*/
\$dbconfig=array(
    'host' => '{$host}', //数据库服务器
    'port' => {$port}, //数据库端口
    'user' => '{$user}', //数据库用户名
    'pwd' => '{$pwd}', //数据库密码
    'dbname' => '{$dbname}', //数据库名
);
?>";
            if(!file_put_contents($config_file, $config_content)){
                exit('{"code":-1,"msg":"配置文件写入失败,请检查文件权限！"}');
            }
            exit('{"code":1,"msg":"配置更新成功！"}');
        } catch (Exception $e) {
            exit('{"code":-1,"msg":"配置更新失败:'.$e->getMessage().'"}');
        }
} 