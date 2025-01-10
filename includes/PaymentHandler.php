<?php
class PaymentHandler {
    private $conn;
    private $dbconfig;

    public function __construct($dbconfig) {
        $this->dbconfig = $dbconfig;
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli(
            $this->dbconfig['host'],
            $this->dbconfig['user'],
            $this->dbconfig['pwd'],
            $this->dbconfig['dbname'],
            $this->dbconfig['port']
        );
        
        if ($this->conn->connect_error) {
            throw new Exception("数据库连接失败: " . $this->conn->connect_error);
        }
    }

    /**
     * 处理支付成功后的业务逻辑
     * @param array $paymentData 支付数据
     * @return array 处理结果
     */
    public function handlePaymentSuccess($paymentData) {
        try {
            // 1. 更新订单状态
            if (!$this->updateOrderStatus($paymentData['out_trade_no'])) {
                return ['code' => -1, 'msg' => '订单状态更新失败'];
            }

            // 2. 获取订单信息
            $order = $this->getOrderInfo($paymentData['out_trade_no']);
            if (!$order) {
                return ['code' => -1, 'msg' => '订单不存在'];
            }

            // 3. 获取套餐信息
            $package = $this->getPackageInfo($order['package_id']);
            if (!$package) {
                return ['code' => -1, 'msg' => '套餐不存在'];
            }

            // 4. 获取应用和服务器信息
            $serverInfo = $this->getServerInfo($order['appcode']);
            if (!$serverInfo) {
                return ['code' => -1, 'msg' => '服务器配置不存在'];
            }

            // 5. 处理用户账号
            $result = $this->processUserAccount($order, $package, $serverInfo);
            if ($result['code'] !== 1) {
                return $result;
            }

            return ['code' => 1, 'msg' => '订单处理成功'];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => '系统异常：' . $e->getMessage()];
        }
    }

    private function updateOrderStatus($orderNo) {
        $stmt = $this->conn->prepare("UPDATE orders SET status = 1 WHERE order_no = ? AND status = 0");
        $stmt->bind_param("s", $orderNo);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    private function getOrderInfo($orderNo) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_no = ?");
        $stmt->bind_param("s", $orderNo);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function getPackageInfo($packageId) {
        $stmt = $this->conn->prepare("SELECT * FROM packages WHERE id = ?");
        $stmt->bind_param("i", $packageId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function getServerInfo($appcode) {
        $query = "SELECT s.* FROM server_list s 
                 JOIN application a ON s.ip = a.serverip 
                 WHERE a.appcode = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $appcode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function processUserAccount($order, $package, $server) {
        // 设置CCProxy连接参数
        $proxyaddress = $server['ip'];
        $admin_username = $server['serveruser'];
        $admin_password = $server['password'];
        $admin_port = $server['cport'];

        // 查询用户是否存在
        $users = queryuserall($admin_password, $admin_port, $proxyaddress);
        $userExists = !existsuser($order['account'], $users);

        if ($order['mode'] === 'register') {
            if ($userExists) {
                return ['code' => -1, 'msg' => '用户已存在'];
            }

            // 添加新用户
            $userdata = [
                'user' => $order['account'],
                'pwd' => $order['password'],
                'expire' => $package['days'],
                'use_date' => date('Y-m-d H:i:s'),
                'connection' => -1,
                'bandwidthup' => -1,
                'bandwidthdown' => -1
            ];
            return AddUser($proxyaddress, $admin_password, $admin_port, $userdata);

        } else if ($order['mode'] === 'renew') {
            if (!$userExists) {
                return ['code' => -1, 'msg' => '用户不存在，无法续费'];
            }

            // 续费用户
            return UserUpdate(
                $admin_password,
                $admin_port,
                $proxyaddress,
                $order['account'],
                '',
                $package['days'],
                -1,
                -1,
                -1
            );
        }

        return ['code' => -1, 'msg' => '未知的订单类型'];
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?> 