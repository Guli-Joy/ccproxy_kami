-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: ccproxy
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application` (
  `appid` int(11) NOT NULL AUTO_INCREMENT,
  `appcode` varchar(255) NOT NULL COMMENT 'appcode',
  `appname` varchar(255) NOT NULL,
  `serverip` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL COMMENT '属于user',
  `found_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`appid`) USING BTREE,
  UNIQUE KEY `appcode` (`appcode`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=56545 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application`
--

LOCK TABLES `application` WRITE;
/*!40000 ALTER TABLE `application` DISABLE KEYS */;
/*!40000 ALTER TABLE `application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kami`
--

DROP TABLE IF EXISTS `kami`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kami` (
  `id` int(15) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `kami` varchar(128) NOT NULL COMMENT '卡密',
  `times` varchar(20) NOT NULL COMMENT '时长',
  `comment` varchar(20) NOT NULL COMMENT '备注',
  `found_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `host` varchar(255) NOT NULL COMMENT '站点',
  `sc_user` varchar(20) NOT NULL COMMENT '生成用户',
  `state` int(1) NOT NULL DEFAULT '0' COMMENT '状态:0=未使用,1=已使用',
  `use_date` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  `username` varchar(25) DEFAULT NULL COMMENT '使用账号',
  `app` varchar(255) NOT NULL COMMENT '使用app软件',
  `end_date` timestamp NULL DEFAULT NULL COMMENT '到期时间',
  `ext` text COMMENT '拓展参数',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `kami` (`kami`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='注册卡密';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kami`
--

LOCK TABLES `kami` WRITE;
/*!40000 ALTER TABLE `kami` DISABLE KEYS */;
/*!40000 ALTER TABLE `kami` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `operation` varchar(255) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `operationdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `operationer` varchar(255) NOT NULL COMMENT '操作人',
  `ip` varchar(255) NOT NULL COMMENT 'ip',
  PRIMARY KEY (`logid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=264 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `appcode` varchar(32) NOT NULL COMMENT '应用代码',
  `account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '账号',
  `password` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '密码（注册模式）',
  `package_id` int(11) NOT NULL COMMENT '套餐ID',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `pay_type` varchar(20) NOT NULL COMMENT '支付方式',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0未支付，1已支付',
  `mode` varchar(20) NOT NULL COMMENT '模式：register/renew',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `username` varchar(32) NOT NULL COMMENT '所属用户',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `appcode` (`appcode`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(255) NOT NULL COMMENT '套餐名称',
  `days` decimal(10,6) NOT NULL COMMENT '天数(支持小数)',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `appcode` varchar(32) NOT NULL COMMENT '应用码',
  `addtime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `appcode` (`appcode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='套餐表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_config`
--

DROP TABLE IF EXISTS `pay_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(50) NOT NULL COMMENT '商户ID',
  `merchant_key` varbinary(100) NOT NULL COMMENT '商户密钥',
  `api_url` varchar(255) NOT NULL COMMENT '支付接口地址',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0关闭 1开启',
  `alipay_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '支付宝状态 0关闭 1开启',
  `wxpay_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '微信支付状态 0关闭 1开启',
  `qqpay_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'QQ钱包状态 0关闭 1开启',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_config`
--

LOCK TABLES `pay_config` WRITE;
/*!40000 ALTER TABLE `pay_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `server_list`
--

DROP TABLE IF EXISTS `server_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server_list` (
  `id` int(15) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `ip` varchar(60) NOT NULL COMMENT '服务器ip',
  `serveruser` varchar(40) NOT NULL COMMENT 'ccproxy登录账号',
  `password` varchar(40) NOT NULL COMMENT 'ccproxy登录密码',
  `state` int(1) NOT NULL DEFAULT '1' COMMENT '是否可用:0=不可用,1=可用',
  `comment` varchar(200) NOT NULL COMMENT '备注',
  `found_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `certificate` varchar(200) DEFAULT NULL COMMENT '证书地址',
  `cport` int(5) NOT NULL COMMENT 'CCProxy端口',
  `username` varchar(255) NOT NULL COMMENT '所属账号',
  `applist` text COMMENT '应用大全',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `ip` (`ip`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='服务器列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server_list`
--

LOCK TABLES `server_list` WRITE;
/*!40000 ALTER TABLE `server_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `server_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_admin`
--

DROP TABLE IF EXISTS `sub_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `hostname` varchar(20) NOT NULL COMMENT '网站标题',
  `cookies` varchar(255) NOT NULL COMMENT ' 登录会话',
  `found_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `over_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '到期时间',
  `siteurl` varchar(255) NOT NULL COMMENT '主域名',
  `state` tinyint(1) NOT NULL DEFAULT '1' COMMENT '站点违规',
  `pan` varchar(255) NOT NULL COMMENT '网盘',
  `wzgg` text NOT NULL COMMENT '网站公告',
  `kf` varchar(255) NOT NULL COMMENT '客服',
  `img` varchar(255) NOT NULL COMMENT '图片',
  `ggswitch` int(1) NOT NULL COMMENT '公告开关',
  `kfswitch` int(1) NOT NULL DEFAULT '1' COMMENT '客服开关',
  `panswitch` int(1) NOT NULL DEFAULT '1' COMMENT '网盘开关',
  `qx` int(1) NOT NULL COMMENT '权限',
  `dayimg` varchar(255) NOT NULL DEFAULT '' COMMENT '日间背景图片',
  `nightimg` varchar(255) NOT NULL DEFAULT '' COMMENT '夜间背景图片',
  `bgswitch` int(1) NOT NULL DEFAULT '1' COMMENT '背景切换开关',
  `show_online_pay` int(1) NOT NULL DEFAULT '1' COMMENT '在线续费/注册开关',
  `show_kami_pay` int(1) NOT NULL DEFAULT '1' COMMENT '卡密充值开关',
  `show_kami_reg` int(1) NOT NULL DEFAULT '1' COMMENT '卡密注册开关',
  `show_user_search` int(1) NOT NULL DEFAULT '1' COMMENT '用户查询开关',
  `show_kami_query` int(1) NOT NULL DEFAULT '1' COMMENT '卡密查询开关',
  `show_change_pwd` int(1) NOT NULL DEFAULT '1' COMMENT '修改密码功能开关',
  `multi_domain` int(1) NOT NULL DEFAULT '0' COMMENT '多域名开关 0=关闭 1=开启',
  `domain_list` text COMMENT '多域名列表',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=61 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='普通管理员';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_admin`
--

LOCK TABLES `sub_admin` WRITE;
/*!40000 ALTER TABLE `sub_admin` DISABLE KEYS */;
INSERT INTO `sub_admin` (`id`, `username`, `password`, `hostname`, `cookies`, `found_date`, `over_date`, `siteurl`, `state`, `pan`, `wzgg`, `kf`, `img`, `ggswitch`, `kfswitch`, `panswitch`, `qx`, `dayimg`, `nightimg`, `bgswitch`, `show_online_pay`, `show_kami_pay`, `show_kami_reg`, `show_user_search`, `show_kami_query`, `show_change_pwd`, `multi_domain`, `domain_list`) 
VALUES (1,'admin','123456','故离端口','c93a36XpmjKPlGPcwsKTtXmI0m2bzaYWHkAhQehg/ExyIRZ5bpLQkxcmi1nQlFOO7dxjXmkNhFlD9dx0RicNR4Gggw','2024-12-03 13:17:17','2033-12-31 13:17:17','192.168.31.134:8882',1,'','测试公告公告公告测试公告','','./assets/img/bj.jpg',1,1,1,1,'https://api.qjqq.cn/api/Img?sort=belle','https://www.dmoe.cc/random.php',1,1,1,1,1,1,1,0,'');
/*!40000 ALTER TABLE `sub_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_apps`
--

DROP TABLE IF EXISTS `package_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appcode` varchar(32) NOT NULL COMMENT '关联应用代码',
  `app_name` varchar(64) NOT NULL COMMENT '应用名称',
  `server_address` varchar(255) NOT NULL COMMENT '服务器地址',
  `server_port` varchar(32) NOT NULL COMMENT '服务器端口',
  `download_url` varchar(255) NOT NULL COMMENT '下载地址',
  `special_notes` text COMMENT '特殊说明',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appcode` (`appcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用配置信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'ccproxy'
--

--
-- Dumping routines for database 'ccproxy'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-03 14:54:49
