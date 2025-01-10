<?php

if (extension_loaded('mysqli')) {
    class DB
    {
        private static $link;
        private static $last_error;
        private static $debug = true;

        public static function connect($db_host, $db_user, $db_pass, $db_name, $db_port)
        {
            self::$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
            if (self::$link) {
                mysqli_set_charset(self::$link, 'utf8');
            }
            return self::$link;
        }

        public static function connect_errno()
        {
            return mysqli_connect_errno();
        }

        public static function connect_error()
        {
            return mysqli_connect_error();
        }

        public static function fetch($q)
        {
            if (!$q) {
                self::$last_error = "Invalid query result";
                return false;
            }
            return mysqli_fetch_assoc($q);
        }

        public static function get_row($q)
        {
            $result = self::query($q);
            if (!$result) {
                return null;
            }
            return mysqli_fetch_assoc($result);
        }

        public static function count($q)
        {
            $result = self::query($q);
            if (!$result) {
                return 0;
            }
            $count = mysqli_fetch_array($result);
            return $count[0];
        }

        public static function query($q)
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            // 记录查询
            if (self::$debug) {
                error_log("SQL Query: " . $q);
            }

            $result = mysqli_query(self::$link, $q);
            if (!$result) {
                self::$last_error = mysqli_error(self::$link);
                if (self::$debug) {
                    error_log("SQL Error: " . self::$last_error);
                }
            }
            return $result;
        }

        public static function multi_query($q)
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            // 记录查询
            if (self::$debug) {
                error_log("SQL Multi Query: " . substr($q, 0, 1000) . "...");
            }

            $result = mysqli_multi_query(self::$link, $q);
            if (!$result) {
                self::$last_error = mysqli_error(self::$link);
                if (self::$debug) {
                    error_log("SQL Error: " . self::$last_error);
                }
                return false;
            }

            // 处理所有结果
            do {
                if ($result = mysqli_store_result(self::$link)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_next_result(self::$link));

            return true;
        }

        public static function escape($str)
        {
            if (!self::$link) {
                return addslashes($str);
            }
            return mysqli_real_escape_string(self::$link, $str);
        }

        public static function affected()
        {
            return mysqli_affected_rows(self::$link);
        }

        public static function errno()
        {
            return mysqli_errno(self::$link);
        }

        public static function error()
        {
            return self::$last_error ?: mysqli_error(self::$link);
        }

        public static function close()
        {
            if (self::$link) {
                return mysqli_close(self::$link);
            }
            return true;
        }

        public static function drop_all_tables()
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            self::query("SET FOREIGN_KEY_CHECKS = 0");
            
            // 获取所有表名
            $tables = [];
            $result = self::query("SHOW TABLES");
            if (!$result) {
                self::$last_error = "Failed to get tables list";
                return false;
            }

            while ($row = self::fetch($result)) {
                $tables[] = reset($row);
            }

            // 删除所有表
            foreach ($tables as $table) {
                if (!self::query("DROP TABLE IF EXISTS `$table`")) {
                    if (self::$debug) {
                        error_log("Failed to drop table: $table");
                    }
                }
            }

            self::query("SET FOREIGN_KEY_CHECKS = 1");
            return true;
        }

        public static function get_all_tables()
        {
            // 获取所有表名
            $tables = [];
            $result = self::query("SHOW TABLES");
            while($row = self::fetch($result)) {
                $tables[] = reset($row); // 获取第一个元素
            }
            return $tables;
        }
    }
} else {
    class DB
    {
        private static $link;
        private static $last_error;
        private static $debug = true;

        public static function connect($db_host, $db_user, $db_pass, $db_name, $db_port)
        {
            self::$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
            if (self::$link) {
                mysqli_set_charset(self::$link, 'utf8');
            }
            return self::$link;
        }

        public static function connect_errno()
        {
            return mysqli_connect_errno();
        }

        public static function connect_error()
        {
            return mysqli_connect_error();
        }

        public static function fetch($q)
        {
            if (!$q) {
                self::$last_error = "Invalid query result";
                return false;
            }
            return mysqli_fetch_assoc($q);
        }

        public static function get_row($q)
        {
            $result = self::query($q);
            if (!$result) {
                return null;
            }
            return mysqli_fetch_assoc($result);
        }

        public static function count($q)
        {
            $result = self::query($q);
            if (!$result) {
                return 0;
            }
            $count = mysqli_fetch_array($result);
            return $count[0];
        }

        public static function query($q)
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            // 记录查询
            if (self::$debug) {
                error_log("SQL Query: " . $q);
            }

            $result = mysqli_query(self::$link, $q);
            if (!$result) {
                self::$last_error = mysqli_error(self::$link);
                if (self::$debug) {
                    error_log("SQL Error: " . self::$last_error);
                }
            }
            return $result;
        }

        public static function multi_query($q)
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            // 记录查询
            if (self::$debug) {
                error_log("SQL Multi Query: " . substr($q, 0, 1000) . "...");
            }

            $result = mysqli_multi_query(self::$link, $q);
            if (!$result) {
                self::$last_error = mysqli_error(self::$link);
                if (self::$debug) {
                    error_log("SQL Error: " . self::$last_error);
                }
                return false;
            }

            // 处理所有结果
            do {
                if ($result = mysqli_store_result(self::$link)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_next_result(self::$link));

            return true;
        }

        public static function escape($str)
        {
            if (!self::$link) {
                return addslashes($str);
            }
            return mysqli_real_escape_string(self::$link, $str);
        }

        public static function affected()
        {
            return mysqli_affected_rows(self::$link);
        }

        public static function errno()
        {
            return mysqli_errno(self::$link);
        }

        public static function error()
        {
            return self::$last_error ?: mysqli_error(self::$link);
        }

        public static function close()
        {
            if (self::$link) {
                return mysqli_close(self::$link);
            }
            return true;
        }

        public static function drop_all_tables()
        {
            if (!self::$link) {
                self::$last_error = "No database connection";
                return false;
            }

            self::query("SET FOREIGN_KEY_CHECKS = 0");
            
            // 获取所有表名
            $tables = [];
            $result = self::query("SHOW TABLES");
            if (!$result) {
                self::$last_error = "Failed to get tables list";
                return false;
            }

            while ($row = self::fetch($result)) {
                $tables[] = reset($row);
            }

            // 删除所有表
            foreach ($tables as $table) {
                if (!self::query("DROP TABLE IF EXISTS `$table`")) {
                    if (self::$debug) {
                        error_log("Failed to drop table: $table");
                    }
                }
            }

            self::query("SET FOREIGN_KEY_CHECKS = 1");
            return true;
        }

        public static function get_all_tables()
        {
            // 获取所有表名
            $tables = [];
            $result = self::query("SHOW TABLES");
            while($row = self::fetch($result)) {
                $tables[] = reset($row); // 获取第一个元素
            }
            return $tables;
        }
    }

}
?>