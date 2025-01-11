<?php
/*
 * @Author: guli
 * @Date: 2022-06-25 19:37:15
 * @LastEditTime: 2022-08-23 17:15:04
 * @LastEditors: guli
 * @Description: 
 * @FilePath: \ccpy\install\class.php
 * 
 */

/**
 * Class install
 */
class install
{
    /**
     * 安全过滤用户输入
     * @param mixed $string 需要过滤的内容
     * @param int $force 强制过滤
     * @param bool $strip 是否去除斜杠
     * @return array|string
     */
    public function daddslashes($string, $force = 0, $strip = FALSE)
    {
        if (!is_array($string)) {
            return htmlspecialchars(addslashes(trim($string)));
        }
        
        $new_array = array();
        foreach ($string as $key => $value) {
            $new_array[$key] = is_array($value) ? 
                $this->daddslashes($value, $force, $strip) : 
                htmlspecialchars(addslashes(trim($value)));
        }
        return $new_array;
    }
    

    /**
     * 修改配置文件内容
     * @param array $dbconfig 数据库配置
     * @return array
     */
    public function ModifyFileContents($dbconfig)
    {
        $FILE = '../config.php';
        
        // 检查配置参数
        $required_fields = ['host', 'port', 'user', 'pwd', 'dbname'];
        foreach ($required_fields as $field) {
            if (!isset($dbconfig[$field]) || empty($dbconfig[$field])) {
                return ['code' => -1, 'msg' => "数据库配置参数 {$field} 不能为空！"];
            }
        }
        
        // 检查文件权限
        if (!file_exists($FILE)) {
            return ['code' => -1, 'msg' => '配置文件(config.php)不存在！'];
        }
        if (!is_writable($FILE)) {
            return ['code' => -1, 'msg' => '配置文件(config.php)没有写入权限，请设置666权限！'];
        }
        
        try {
            // 验证数据库连接
            $conn = @new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], '', intval($dbconfig['port']));
            if ($conn->connect_error) {
                return ['code' => -1, 'msg' => '数据库连接失败：' . $conn->connect_error];
            }
            $conn->close();
            
            $data = "<?php\n/*数据库配置*/\n\$dbconfig=array(\n" .
                    "\t'host' => '" . addslashes($dbconfig['host']) . "', //数据库服务器\n" .
                    "\t'port' => " . intval($dbconfig['port']) . ", //数据库端口\n" .
                    "\t'user' => '" . addslashes($dbconfig['user']) . "', //数据库用户名\n" .
                    "\t'pwd' => '" . addslashes($dbconfig['pwd']) . "', //数据库密码\n" .
                    "\t'dbname' => '" . addslashes($dbconfig['dbname']) . "', //数据库名\n" .
                    ");\n?>";
                    
            if (@file_put_contents($FILE, $data)) {
                return ['code' => 1, 'msg' => '数据库配置更新成功！'];
            } else {
                return ['code' => -1, 'msg' => '配置文件写入失败，请检查文件权限！'];
            }
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => '写入配置发生错误：' . $e->getMessage()];
        }
    }
}
