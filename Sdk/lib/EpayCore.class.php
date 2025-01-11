<?php
/* *
 * 彩虹易支付SDK服务类
 * 说明：
 * 包含发起支付、查询订单、回调验证等功能
 */

class EpayCore {
	private $config = array();
	private $sign_type = 'MD5';
	private $error_msg = '';
	private $last_request_info = array();
	private $valid_api_url = '';

	public function __construct($config) {
		// 验证必要的配置参数
		$required = ['pid', 'key', 'apiurl'];
		foreach($required as $key) {
			if(!isset($config[$key])) {
				throw new Exception("Missing required config: {$key}");
			}
		}
		
		// 从数据库验证API URL
		$this->validateApiUrl($config['apiurl']);
		
		// 设置基础配置
		$this->config = $config;
		
		// 设置API URL
		$this->config['submit_url'] = $this->valid_api_url.'submit.php';
		$this->config['mapi_url'] = $this->valid_api_url.'mapi.php';
		$this->config['api_url'] = $this->valid_api_url.'api.php';
	}

	// 验证API URL
	private function validateApiUrl($apiurl) {
		global $dbconfig;
		
		// 连接数据库
		$conn = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['pwd'], $dbconfig['dbname'], $dbconfig['port']);
		if ($conn->connect_error) {
			throw new Exception("Database connection failed");
		}
		
		// 验证API URL是否匹配数据库配置
		$sql = "SELECT api_url FROM pay_config WHERE status = 1 LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result && $result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$db_api_url = $row['api_url'];
			
			// 处理URL，支持不同的域名和端口
			$db_url_info = parse_url($db_api_url);
			$input_url_info = parse_url($apiurl);
			
			// 如果数据库中的URL包含端口，则必须完全匹配
			if(isset($db_url_info['port'])) {
				if($db_api_url === $apiurl) {
					$this->valid_api_url = $db_api_url;
				}
			} else {
				// 否则只比较路径部分
				$db_path = isset($db_url_info['path']) ? rtrim($db_url_info['path'], '/') : '';
				$input_path = isset($input_url_info['path']) ? rtrim($input_url_info['path'], '/') : '';
				
				if($db_path === $input_path) {
					$this->valid_api_url = $apiurl;
				}
			}
		}
		
		if(empty($this->valid_api_url)) {
			throw new Exception("Invalid API URL");
		}
		
		$stmt->close();
		$conn->close();
	}

	// 发起支付（页面跳转）
	public function pagePay($param) {
		$param['pid'] = trim($this->config['pid']);
		$sign = $this->getSign($param);
		$param['sign'] = $sign;
		$param['sign_type'] = $this->sign_type;
		$html = "<form id='dopay' name='dopay' action='".$this->config['submit_url']."' method='post'>";
		foreach ($param as $key => $val) {
			$html .= "<input type='hidden' name='".$key."' value='".$val."'/>";
		}
		$html .= "</form>";
		return $html;
	}

	// 发起支付（获取链接）
	public function getPayLink($param_tmp){
		$param = $this->buildRequestParam($param_tmp);
		$url = $this->config['submit_url'].'?'.http_build_query($param);
		return $url;
	}

	// 发起支付（API接口）
	public function apiPay($param_tmp){
		$param = $this->buildRequestParam($param_tmp);
		$response = $this->getHttpResponse($this->config['mapi_url'], http_build_query($param));
		$arr = json_decode($response, true);
		return $arr;
	}

	// 异步回调验证
	public function verifyNotify(){
		if(empty($_GET)) {
			return false;
		}
		
		// 必要参数检查
		$required_params = ['pid', 'trade_no', 'out_trade_no', 'type', 'sign', 'sign_type'];
		foreach($required_params as $param) {
			if(!isset($_GET[$param]) || trim($_GET[$param]) === '') {
				return false;
			}
		}
		
		// 验证PID
		if($_GET['pid'] != $this->config['pid']) {
			return false;
		}
		
		// 验证金额格式
		if(isset($_GET['money']) && !preg_match('/^\d+(\.\d{1,2})?$/', $_GET['money'])) {
			return false;
		}
		
		$params = $this->filterParam($_GET);
		$isSign = $this->getSignVeryfy($params, $params["sign"], $params['sign_type']);
		
		return $isSign;
	}

	// 同步回调验证
	public function verifyReturn(){
		if(empty($_GET)) return false;
		$sign = $this->getSign($_GET);
		if($sign === $_GET['sign']){
			return true;
		}
		return false;
	}

	// 查询订单支付状态
	public function orderStatus($trade_no){
		$result = $this->queryOrder($trade_no);
		if($result['status']==1){
			return true;
		}
		return false;
	}

	// 查询订单
	public function queryOrder($trade_no){
		$url = $this->config['api_url'].'?act=order&pid='.$this->config['pid'].'&key='.$this->config['key'].'&trade_no='.$trade_no;
		$response = $this->getHttpResponse($url);
		$arr = json_decode($response, true);
		return $arr;
	}

	// 订单退款
	public function refund($trade_no, $money){
		$url = $this->config['api_url'].'?act=refund';
		$post = 'pid='.$this->config['pid'].'&key='.$this->config['key'].'&trade_no='.$trade_no.'&money='.$money;
		$response = $this->getHttpResponse($url, $post);
		$arr = json_decode($response, true);
		return $arr;
	}

	private function buildRequestParam($param){
		$mysign = $this->getSign($param);
		$param['sign'] = $mysign;
		$param['sign_type'] = $this->sign_type;
		return $param;
	}

	// 计算签名
	public function getSign($param) {
		// 处理参数值的大小写
		if(isset($param['trade_status'])) {
			$param['trade_status'] = strtoupper($param['trade_status']);
		}
		
		$param = $this->paraFilter($param);
		$param = $this->argSort($param);
		$prestr = $this->createLinkstring($param);
		$prestr = $prestr . $this->config['key'];
		$sign = md5($prestr);
		return $sign;
	}

	// 请求外部资源
	private function getHttpResponse($url, $post = false, $timeout = 10){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: close";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($post){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	private function getSignVeryfy($params, $sign, $sign_type) {
		// 处理参数值的大小写
		if(isset($params['trade_status'])) {
			$params['trade_status'] = strtoupper($params['trade_status']);
		}
		
		$params = $this->paraFilter($params);
		$params = $this->argSort($params);
		$prestr = $this->createLinkstring($params);
		$prestr = $prestr . $this->config['key'];
		
		$mysgin = md5($prestr);
		
		if($mysgin == $sign) {
			return true;
		}
		return false;
	}
	
	private function paraFilter($para) {
		$para_filter = array();
		foreach($para as $key => $val) {
			if($key == "sign" || $key == "sign_type" || $val === "") continue;
			else $para_filter[$key] = $val;
		}
		return $para_filter;
	}
	
	private function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}
	
	private function createLinkstring($para) {
		$arg  = "";
		foreach($para as $key => $val) {
			$arg .= $key . "=" . $val . "&";
		}
		$arg = substr($arg, 0, -1); //去掉最后一个&字符
		return $arg;
	}

	// 参数过滤
	protected function filterParam($param) {
		$filtered = array();
		foreach($param as $key => $value) {
			if($value === '' || $value === null) continue;
			
			// 移除PHP标记和其他危险字符
			if(preg_match('/<\?|<%|{|\[|\(|\)|]|}|>|<|\"|\'/i', $value)) {
				continue;
			}
			
			// XSS过滤
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			// SQL注入过滤
			$value = addslashes($value);
			// 移除控制字符
			$value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
			
			$filtered[$key] = $value;
		}
		return $filtered;
	}
}
