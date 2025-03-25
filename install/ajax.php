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
        $in_string = false;
        $string_char = '';
        $line_number = 0;
        
        foreach ($lines as $line) {
            $line_number++;
            $line = trim($line);
            
            // 跳过注释和空行
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
                continue;
            }
            
            // 处理字符串中的分号
            $chars = str_split($line);
            foreach ($chars as $char) {
                if ($char === "'" || $char === '"') {
                    if (!$in_string) {
                        $in_string = true;
                        $string_char = $char;
                    } else if ($char === $string_char) {
                        $in_string = false;
                        $string_char = '';
                    }
                }
                $current_statement .= $char;
            }
            
            // 如果行末尾是分号且不在字符串中，说明一条语句结束
            if (substr(trim($line), -1) === ';' && !$in_string) {
                // 特别处理sub_admin表的INSERT语句
                if (stripos($current_statement, 'INSERT INTO `sub_admin`') !== false) {
                    // 检查是否有列数与值不匹配的问题
                    if (preg_match('/INSERT INTO\s+`?sub_admin`?\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is', $current_statement, $matches)) {
                        $columns = explode(',', $matches[1]);
                        $values = explode(',', $matches[2]);
                        
                        // 简单计数（可能不精确，但是一个快速检查）
                        $column_count = count($columns);
                        $value_count = count($values);
                        
                        // 如果不匹配，则手动修复
                        if ($column_count != $value_count) {
                            // 手动修复sub_admin表的insert语句
                            $fixed_sql = "INSERT INTO `sub_admin` (`id`, `username`, `password`, `hostname`, `cookies`, `found_date`, `over_date`, `siteurl`, `state`, `pan`, `wzgg`, `kf`, `img`, `ggswitch`, `kfswitch`, `panswitch`, `qx`, `dayimg`, `nightimg`, `bgswitch`, `show_online_pay`, `show_kami_pay`, `show_kami_reg`, `show_user_search`, `show_kami_query`, `show_change_pwd`, `multi_domain`, `domain_list`, `inherit_enabled`, `show_inherit_apps`, `inherit_groups`) 
VALUES (1,'admin','123456','故离端口','c93a36XpmjKPlGPcwsKTtXmI0m2bzaYWHkAhQehg/ExyIRZ5bpLQkxcmi1nQlFOO7dxjXmkNhFlD9dx0RicNR4Gggw','2024-12-03 13:17:17','2033-12-31 13:17:17','" . $server_name . "',1,'','# 🌟 欢迎使用故离端口系统\n\n## 🎉 系统说明\n\n### 🚀 主要功能\n- ✨ 支持在线支付\n- 🔒 账号管理系统\n- 🎨 界面美观大方\n- 🔄 稳定性强\n\n### 📝 使用说明\n1. 支持多种注册方式\n2. 灵活的续费选项\n\n> 温馨提示：请遵守使用规则\n\n### 📞 联系方式\n- 客服QQ：请点击客服按钮\n- 问题反馈：请联系客服\n\n---\n*感谢您的使用！*','./assets/img/bj.jpg',1,1,1,1,'https://api.qjqq.cn/api/Img?sort=belle','https://www.dmoe.cc/random.php',1,1,1,1,1,1,1,0,'',0,1,'[]');";
                            
                            $current_statement = $fixed_sql;
                        }
                    }
                }
                
                $statements[] = [
                    'sql' => trim($current_statement),
                    'line' => $line_number
                ];
                $current_statement = '';
            } else {
                $current_statement .= ' ';
            }
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        // 第一阶段：创建表结构
        foreach ($statements as $statement_info) {
            $statement = $statement_info['sql'];
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
                        'sql' => $statement,
                        'line' => $statement_info['line']
                    ]));
                } else {
                    $success++;
                }
            } catch (Exception $e) {
                DB::query("ROLLBACK");  // 回滚事务
                $failed++;
                $errors[] = "SQL: " . substr($statement, 0, 100) . "\n异常: " . $e->getMessage() . "\n行号: " . $statement_info['line'];
            }
        }

        // 第二阶段：执行其他SQL语句（数据插入等）
        foreach ($statements as $statement_info) {
            $statement = $statement_info['sql'];
            if (empty(trim($statement))) continue;
            if (strpos(trim($statement), '--') === 0 || strpos(trim($statement), '/*') === 0) continue;
            
            // 跳过已执行的表结构语句
            if (stripos($statement, 'CREATE TABLE') !== false || stripos($statement, 'DROP TABLE') !== false) {
                continue;
            }
            
            try {
                // 对于INSERT语句，先检查列数和值的数量是否匹配
                if (stripos($statement, 'INSERT INTO') !== false) {
                    // 对于sub_admin表的插入，直接使用预定义的语句
                    if (stripos($statement, 'INSERT INTO `sub_admin`') !== false || stripos($statement, 'INSERT INTO sub_admin') !== false) {
                        // 使用标准化的SQL语句
                        $safe_statement = "INSERT INTO `sub_admin` (`id`, `username`, `password`, `hostname`, `cookies`, `found_date`, `over_date`, `siteurl`, `state`, `pan`, `wzgg`, `kf`, `img`, `ggswitch`, `kfswitch`, `panswitch`, `qx`, `dayimg`, `nightimg`, `bgswitch`, `show_online_pay`, `show_kami_pay`, `show_kami_reg`, `show_user_search`, `show_kami_query`, `show_change_pwd`, `multi_domain`, `domain_list`, `inherit_enabled`, `show_inherit_apps`, `inherit_groups`) 
VALUES (1,'admin','123456','故离端口','c93a36XpmjKPlGPcwsKTtXmI0m2bzaYWHkAhQehg/ExyIRZ5bpLQkxcmi1nQlFOO7dxjXmkNhFlD9dx0RicNR4Gggw','2024-12-03 13:17:17','2033-12-31 13:17:17','" . $server_name . "',1,'','# 🌟 欢迎使用故离端口系统\n\n## 🎉 系统说明\n\n### 🚀 主要功能\n- ✨ 支持在线支付\n- 🔒 账号管理系统\n- 🎨 界面美观大方\n- 🔄 稳定性强\n\n### 📝 使用说明\n1. 支持多种注册方式\n2. 灵活的续费选项\n\n> 温馨提示：请遵守使用规则\n\n### 📞 联系方式\n- 客服QQ：请点击客服按钮\n- 问题反馈：请联系客服\n\n---\n*感谢您的使用！*','./assets/img/bj.jpg',1,1,1,1,'https://api.qjqq.cn/api/Img?sort=belle','https://www.dmoe.cc/random.php',1,1,1,1,1,1,1,0,'',0,1,'[]');";
                        
                        $statement = $safe_statement;
                        continue;
                    }
                
                    // 提取列名和值
                    if (preg_match('/INSERT INTO\s+`?(\w+)`?\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is', $statement, $matches)) {
                        $table = $matches[1];
                        $columns_str = $matches[2];
                        $values_str = $matches[3];
                        
                        // 提取列名列表
                        $columns = [];
                        $in_backtick = false;
                        $current_col = '';
                        
                        for ($i = 0; $i < strlen($columns_str); $i++) {
                            $char = $columns_str[$i];
                            
                            if ($char == '`') {
                                $in_backtick = !$in_backtick;
                                $current_col .= $char;
                            } else if ($char == ',' && !$in_backtick) {
                                $columns[] = trim($current_col);
                                $current_col = '';
                            } else {
                                $current_col .= $char;
                            }
                        }
                        
                        if (!empty($current_col)) {
                            $columns[] = trim($current_col);
                        }
                        
                        // 提取值列表
                        $values = [];
                        $in_string = false;
                        $string_char = '';
                        $current_val = '';
                        $in_parentheses = 0;
                        
                        for ($i = 0; $i < strlen($values_str); $i++) {
                            $char = $values_str[$i];
                            
                            if (($char == "'" || $char == '"') && (empty($string_char) || $string_char == $char)) {
                                if ($in_string && $i > 0 && $values_str[$i-1] == '\\') {
                                    // 转义的引号
                                    $current_val .= $char;
                                } else {
                                    $in_string = !$in_string;
                                    if ($in_string) {
                                        $string_char = $char;
                                    } else {
                                        $string_char = '';
                                    }
                                    $current_val .= $char;
                                }
                            } else if ($char == '(' && !$in_string) {
                                $in_parentheses++;
                                $current_val .= $char;
                            } else if ($char == ')' && !$in_string) {
                                $in_parentheses--;
                                $current_val .= $char;
                            } else if ($char == ',' && !$in_string && $in_parentheses == 0) {
                                $values[] = trim($current_val);
                                $current_val = '';
                            } else {
                                $current_val .= $char;
                            }
                        }
                        
                        if (!empty($current_val)) {
                            $values[] = trim($current_val);
                        }
                        
                        $column_count = count($columns);
                        $value_count = count($values);
                        
                        // 如果是sub_admin表，并且列数与值不匹配，尝试自动修复
                        if ($table == 'sub_admin' && $column_count !== $value_count) {
                            // 创建新的修复后的语句
                            $fixed_statement = "INSERT INTO `sub_admin` (";
                            
                            // 列名保持不变
                            $fixed_statement .= $columns_str;
                            $fixed_statement .= ") VALUES (";
                            
                            // 根据表结构列表自动补全值
                            $fixed_values = [];
                            
                            for ($i = 0; $i < $column_count; $i++) {
                                if ($i < $value_count) {
                                    // 使用现有值
                                    $fixed_values[] = $values[$i];
                                } else {
                                    // 根据列类型添加默认值
                                    $col_name = preg_replace('/[`"\']/', '', $columns[$i]);
                                    
                                    // 根据列名推断合适的默认值
                                    if (strpos($col_name, 'enabled') !== false || 
                                        strpos($col_name, 'switch') !== false || 
                                        strpos($col_name, 'state') !== false) {
                                        $fixed_values[] = '0'; // 布尔开关类型
                                    } else if (strpos($col_name, 'list') !== false || 
                                              strpos($col_name, 'groups') !== false || 
                                              strpos($col_name, 'json') !== false) {
                                        $fixed_values[] = "''"; // 空JSON字符串
                                    } else if (strpos($col_name, 'time') !== false || 
                                              strpos($col_name, 'date') !== false) {
                                        $fixed_values[] = "'".date('Y-m-d H:i:s')."'"; // 当前时间
                                    } else {
                                        $fixed_values[] = "''"; // 默认空字符串
                                    }
                                }
                            }
                            
                            $fixed_statement .= implode(", ", $fixed_values);
                            $fixed_statement .= ");";
                            
                            // 使用修复后的语句替换原语句
                            $statement = $fixed_statement;
                        } else if ($column_count !== $value_count) {
                            throw new Exception("列数($column_count)与值的数量($value_count)不匹配");
                        }
                    }
                }
                
                if (!DB::query($statement)) {
                    DB::query("ROLLBACK");  // 回滚事务
                    die(json_encode([
                        'code' => -1,
                        'msg' => '执行SQL语句失败：' . DB::error(),
                        'sql' => $statement,
                        'line' => $statement_info['line']
                    ]));
                } else {
                    $success++;
                }
            } catch (Exception $e) {
                DB::query("ROLLBACK");  // 回滚事务
                $failed++;
                $errors[] = "SQL: " . substr($statement, 0, 100) . "\n异常: " . $e->getMessage() . "\n行号: " . $statement_info['line'];
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
        if (empty($_POST['host']) || empty($_POST['port']) || empty($_POST['user']) || empty($_POST['pwd']) || empty($_POST['dbname'])) {
            die(json_encode(['code' => -1, 'msg' => '请确保数据库配置不为空！']));
        }
        
        try {
            $config_file = '../config.php';
            $config_content = "<?php\n" .
                "/*数据库配置*/\n" .
                "\$dbconfig=array(\n" .
                "    'host' => '" . addslashes($_POST['host']) . "', //数据库服务器\n" .
                "    'port' => " . intval($_POST['port']) . ", //数据库端口\n" .
                "    'user' => '" . addslashes($_POST['user']) . "', //数据库用户名\n" .
                "    'pwd' => '" . addslashes($_POST['pwd']) . "', //数据库密码\n" .
                "    'dbname' => '" . addslashes($_POST['dbname']) . "', //数据库名\n" .
                ");\n?>";

            // 检查文件权限
            if (!is_writable($config_file) && file_exists($config_file)) {
                die(json_encode(['code' => -1, 'msg' => '配置文件没有写入权限！']));
            }

            if (file_put_contents($config_file, $config_content)) {
                die(json_encode(['code' => 1, 'msg' => '配置更新成功！']));
            } else {
                die(json_encode(['code' => -1, 'msg' => '配置文件写入失败！']));
            }
        } catch (Exception $e) {
            die(json_encode(['code' => -1, 'msg' => '配置更新失败：' . $e->getMessage()]));
        }
        break;
    case 'update_structure':
        if (empty($_QET['host']) || empty($_QET['port']) || empty($_QET['user']) || empty($_QET['pwd']) || empty($_QET['dbname'])) {
            die(json_encode(['code' => -1, 'msg' => '请确保数据库配置不为空！']));
        }

        // 连接数据库
        if (!$con = DB::connect($_QET['host'], $_QET['user'], $_QET['pwd'], $_QET['dbname'], $_QET['port'])) {
            die(json_encode(['code' => -1, 'msg' => '连接数据库失败：' . DB::connect_error()]));
        }

        try {
            // 读取SQL文件
            $sql_file = './ccpy.sql';
            $sql_content = file_get_contents($sql_file);
            if ($sql_content === false) {
                die(json_encode(['code' => -1, 'msg' => 'SQL文件读取失败']));
            }

            // 解析SQL文件中的表结构
            $new_tables = [];
            
            // 将SQL语句按分号分割
            $sql_statements = explode(';', $sql_content);
            
            foreach ($sql_statements as $statement) {
                $statement = trim($statement);
                
                // 只处理CREATE TABLE语句
                if (stripos($statement, 'CREATE TABLE') === false) {
                    continue;
                }
                
                // 提取完整的CREATE TABLE语句
                if (preg_match('/CREATE TABLE\s+`([^`]+)`\s*\((.*)\)([^;]*)/is', $statement, $matches)) {
                    $table_name = $matches[1];
                    $structure = $matches[2];
                    $table_options = $matches[3];
                    
                    // 清理结构中的注释和多余空白
                    $structure = preg_replace('/\/\*.*?\*\//s', '', $structure);
                    $structure = preg_replace('/--.*$/m', '', $structure);
                    $structure = preg_replace('/\s+/', ' ', $structure);
                    $structure = trim($structure);
                    
                    if (!empty($structure)) {
                        $new_tables[$table_name] = [
                            'structure' => $structure,
                            'options' => trim($table_options)
                        ];
                    }
                }
            }

            if (empty($new_tables)) {
                die(json_encode(['code' => -1, 'msg' => '无法从SQL文件解析出表结构']));
            }

            // 开始更新结构
            $updates = 0;
            $errors = [];

            DB::query("SET FOREIGN_KEY_CHECKS = 0");
            DB::query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
            
            foreach ($new_tables as $table => $table_info) {
                // 检查表是否存在
                $table_exists = DB::get_row("SHOW TABLES LIKE '$table'") !== null;
                
                // 使用原始的表选项，如果为空则使用默认值
                $table_options = !empty($table_info['options']) ? 
                               $table_info['options'] : 
                               "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if (!$table_exists) {
                    // 表不存在，创建新表
                    $create_sql = "CREATE TABLE `$table` ({$table_info['structure']}) $table_options";
                    if (DB::query($create_sql)) {
                        $updates++;
                    } else {
                        $errors[] = "创建表 $table 失败: " . DB::error();
                    }
                    continue;
                }

                // 表已存在，创建临时表并迁移数据
                $temp_table = $table . '_temp_' . time();
                $create_temp = "CREATE TABLE `$temp_table` ({$table_info['structure']}) $table_options";
                
                if (!DB::query($create_temp)) {
                    $errors[] = "创建临时表 $temp_table 失败: " . DB::error() . "\nSQL: " . $create_temp;
                    continue;
                }

                // 获取两个表的字段列表
                $old_fields = [];
                $result = DB::query("SHOW COLUMNS FROM `$table`");
                while ($row = DB::fetch($result)) {
                    $old_fields[] = $row['Field'];
                }

                $new_fields = [];
                $result = DB::query("SHOW COLUMNS FROM `$temp_table`");
                while ($row = DB::fetch($result)) {
                    $new_fields[] = $row['Field'];
                }

                // 找出共同的字段
                $common_fields = array_intersect($old_fields, $new_fields);
                
                if (!empty($common_fields)) {
                    // 构建字段列表
                    $field_list = '`' . implode('`, `', $common_fields) . '`';
                    
                    // 复制数据
                    $copy_sql = "INSERT INTO `$temp_table` ($field_list) SELECT $field_list FROM `$table`";
                    if (!DB::query($copy_sql)) {
                        $errors[] = "复制表 $table 数据失败: " . DB::error();
                        DB::query("DROP TABLE IF EXISTS `$temp_table`");
                        continue;
                    }
                }

                // 替换原表
                if (!DB::query("DROP TABLE `$table`")) {
                    $errors[] = "删除原表 $table 失败: " . DB::error();
                    DB::query("DROP TABLE IF EXISTS `$temp_table`");
                    continue;
                }
                
                if (!DB::query("RENAME TABLE `$temp_table` TO `$table`")) {
                    $errors[] = "重命名表 $temp_table 失败: " . DB::error();
                    continue;
                }

                $updates++;
            }

            DB::query("SET FOREIGN_KEY_CHECKS = 1");

            if (!empty($errors)) {
                die(json_encode([
                    'code' => -1,
                    'msg' => "更新过程中发生错误:\n" . implode("\n", $errors)
                ]));
            }

            die(json_encode([
                'code' => 1,
                'msg' => "数据库结构更新成功！共更新 $updates 个表结构",
                'updates' => $updates
            ]));

        } catch (Exception $e) {
            die(json_encode([
                'code' => -1,
                'msg' => '更新数据库结构时发生错误：' . $e->getMessage()
            ]));
        }
        break;
} 