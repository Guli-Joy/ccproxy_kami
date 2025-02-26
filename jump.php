<?php
$is_defend = true;
@header('Content-Type: text/html; charset=UTF-8');
include("./includes/common.php");
?>
<!DOCTYPE html>
<html lang="zh">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $subconf['hostname']; ?></title>
	<link rel="stylesheet" type="text/css" href="./assets/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="./assets/css/htmleaf-demo.css">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="black" name="apple-mobile-web-app-status-bar-style">
<meta name="format-detection" content="telephone=no">
<meta content="false" name="twcClient" id="twcClient">
	<style type="text/css">
		.loader {
		    width: 320px;
		    margin: 50px auto 70px;
		    position: relative;
		}
		.loader .loading-1 {
        	margin:0px auto;
		    position: relative;
		    width: 70%;
		    height: 10px;
		    border: 1px solid #69d2e7;
		    border-radius: 10px;
		    animation: turn 4s linear 1.75s infinite;
		}
		.loader .loading-1:before {
		    content: "";
		    display: block;
		    position: absolute;
		    width: 0%;
		    height: 100%;
		    background: #69d2e7;
		    box-shadow: 10px 0px 15px 0px #69d2e7;
		    animation: load 2s linear infinite;
		}
		.loader .loading-2 {
		    width: 100%;
		    position: absolute;
		    top: 20px;
		    color: #69d2e7;
		    font-size: 22px;
		    text-align: center;
		    animation: bounce 2s  linear infinite;
		}
		@keyframes load {
		    0% {
		        width: 0%;
		    }
		    87.5%, 100% {
		        width: 100%;
		    }
		}
		@keyframes turn {
		    0% {
		        transform: rotateY(0deg);
		    }
		    6.25%, 50% {
		        transform: rotateY(180deg);
		    }
		    56.25%, 100% {
		        transform: rotateY(360deg);
		    }
		}
		@keyframes bounce {
		    0%,100% {
		        top: 10px;
		    }
		    12.5% {
		        top: 30px;
		    }
		}
        footer img{
        width:50px;
        
        }
        footer{
        	text-align:center;
            margin-bottom:40px;
            margin-top:60%;
        }
        h5{
        	text-align:center;
        }
	</style>
</head>
<body>
	<div class="htmleaf-container">
	<header class="htmleaf-header">
			<h1><?php echo $subconf['hostname']; ?>正在请求你<span>使用其他浏览器打开本站</span></h1>
		</header>
		<div class="demo" >
		        <div class="container">
		            <div class="row">
		                <div class="col-md-12">
		                    <div class="loader">
		                        <div class="loading-1"></div>
		                        <div class="loading-2">因QQ内置浏览器协议问题<br>请点击右上角使用其他浏览器</div>
		                    </div>
		                </div>
		            </div>
		        </div>
		    </div>
	</div>
    <footer>
    <h5>点击下方已安装的图标直接跳转</h5>
    <a href="mttbrowser://url=<?=$site_url; ?>"><img src="./assets/img/mtt.png"></img></a>
    <a href="googlechrome://browse?url=<?=$site_url; ?>"><img src="./assets/img/360chrome.png"></img></a>
    <a href="googlechrome://browse?url=<?php echo $site_url; ?>"><img src="./assets/img/chrome.png"></img></a>
    <a href="ucweb://<?=$site_url; ?>"><img src="./assets/img/UCMobile.png"></img></a>
	<a href="alipays://platformapi/startapp?appId=20000067&url='<?=$site_url; ?>"><img src="./assets/img/taobao.png"></img></a>
    <a id="taobao" href="taobao://<?=$site_url?>"><img src="./assets/img/browser.png"></img></a>
    </footer>
<script src="./assets/js/lib/jquery-3.5.1.min.js"></script>
<script type="text/javascript">
	// 检测设备类型和浏览器
	function detectBrowser() {
		var ua = navigator.userAgent.toLowerCase();
		var isWeixin = ua.indexOf('micromessenger') !== -1;
		var isAndroid = ua.indexOf('android') !== -1;
		var isIos = /iphone|ipad|ipod/.test(ua);
		var isMobile = isAndroid || isIos;
		
		return {
			isWeixin: isWeixin,
			isAndroid: isAndroid,
			isIos: isIos,
			isMobile: isMobile
		};
	}

	// 智能跳转处理
	function smartRedirect(url) {
		var browser = detectBrowser();
		var targetUrl = url;

		// 处理特殊协议
		if (url.startsWith('taobao://')) {
			if (browser.isMobile) {
				// 移动设备尝试打开淘宝应用
				window.location.href = url;
				// 如果无法打开应用，3秒后跳转到网页版
				setTimeout(function() {
					window.location.href = 'https://www.taobao.com';
				}, 3000);
			} else {
				// PC设备直接跳转到淘宝网页版
				window.location.href = 'https://www.taobao.com';
			}
			return;
		}

		// 其他URL直接跳转
		window.location.href = targetUrl;
	}

	// 页面加载完成后执行跳转
	$(document).ready(function() {
		var targetUrl = '<?php echo isset($_GET["url"]) ? htmlspecialchars($_GET["url"]) : ""; ?>';
		if (targetUrl) {
			setTimeout(function() {
				smartRedirect(targetUrl);
			}, 2000); // 2秒后执行跳转
		}
	});

	// 防止页面滚动
	document.body.addEventListener("touchmove", function(evt) {
		if (!evt._isScroller) {
			evt.preventDefault();
		}
	}, {passive: false});
</script>
</body>
</html>