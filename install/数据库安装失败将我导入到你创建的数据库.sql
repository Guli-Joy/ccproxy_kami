-- MySQL dump 10.13  Distrib 5.7.26, for Win64 (x86_64)
--
-- Host: localhost    Database: ccproxy
-- ------------------------------------------------------
-- Server version	5.7.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agent_finance`
--

DROP TABLE IF EXISTS `agent_finance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_finance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `type` tinyint(1) NOT NULL COMMENT '类型 1-收入 2-支出',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `before_balance` decimal(10,2) NOT NULL COMMENT '变动前余额',
  `after_balance` decimal(10,2) NOT NULL COMMENT '变动后余额',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理商财务记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_finance`
--

LOCK TABLES `agent_finance` WRITE;
/*!40000 ALTER TABLE `agent_finance` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_finance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_info`
--

DROP TABLE IF EXISTS `agent_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '代理商用户名',
  `password` varchar(32) NOT NULL COMMENT '登录密码',
  `real_name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '账户余额',
  `total_income` decimal(10,2) DEFAULT '0.00' COMMENT '总收入',
  `commission_rate` decimal(5,2) DEFAULT '0.00' COMMENT '佣金比例',
  `can_add_sub` tinyint(1) DEFAULT '0' COMMENT '是否可以发展下级 0-禁止 1-允许',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0-禁用 1-启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='代理商信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_info`
--

LOCK TABLES `agent_info` WRITE;
/*!40000 ALTER TABLE `agent_info` DISABLE KEYS */;
INSERT INTO `agent_info` VALUES (6,'admin','e10adc3949ba59abbe56e057f20f883e','gu li','18026429742','dai573000041@qq.com',0.00,0.00,0.00,1,1,'2025-02-22 21:53:03','2025-02-28 21:02:54');
/*!40000 ALTER TABLE `agent_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_orders`
--

DROP TABLE IF EXISTS `agent_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `amount` decimal(10,2) NOT NULL COMMENT '订单金额',
  `pay_amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0-待支付 1-已支付 2-已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理商订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_orders`
--

LOCK TABLES `agent_orders` WRITE;
/*!40000 ALTER TABLE `agent_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_relation`
--

DROP TABLE IF EXISTS `agent_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `parent_id` int(11) NOT NULL COMMENT '上级代理商ID',
  `level` int(11) DEFAULT '1' COMMENT '代理级别 1-普通代理 2-高级代理 3-钻石代理',
  `path` varchar(255) DEFAULT NULL COMMENT '层级路径',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='代理商层级关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_relation`
--

LOCK TABLES `agent_relation` WRITE;
/*!40000 ALTER TABLE `agent_relation` DISABLE KEYS */;
INSERT INTO `agent_relation` VALUES (2,6,0,3,'6','2025-02-22 21:53:03');
/*!40000 ALTER TABLE `agent_relation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_users`
--

DROP TABLE IF EXISTS `agent_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理商用户关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_users`
--

LOCK TABLES `agent_users` WRITE;
/*!40000 ALTER TABLE `agent_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_inherit_groups`
--

DROP TABLE IF EXISTS `app_inherit_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_inherit_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(64) NOT NULL COMMENT '继承组名称',
  `username` varchar(32) NOT NULL COMMENT '所属用户',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用(0:禁用,1:启用)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COMMENT='应用继承组表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_inherit_groups`
--

LOCK TABLES `app_inherit_groups` WRITE;
/*!40000 ALTER TABLE `app_inherit_groups` DISABLE KEYS */;
INSERT INTO `app_inherit_groups` VALUES (32,'继承组1','admin',1,'2025-03-21 16:23:40','2025-03-21 16:23:40');
/*!40000 ALTER TABLE `app_inherit_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_inherit_logs`
--

DROP TABLE IF EXISTS `app_inherit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_inherit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL COMMENT '继承组ID',
  `main_appcode` varchar(32) NOT NULL COMMENT '主应用代码',
  `inherit_appcode` varchar(32) NOT NULL COMMENT '继承应用代码',
  `action_type` varchar(20) NOT NULL COMMENT '操作类型(register:注册,renew:续费)',
  `action_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  `account` varchar(64) NOT NULL COMMENT '操作账号',
  `duration` decimal(10,2) DEFAULT NULL COMMENT '续费时长(天)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0:失败,1:成功)',
  `error_msg` varchar(255) DEFAULT NULL COMMENT '错误信息',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `main_appcode` (`main_appcode`),
  KEY `inherit_appcode` (`inherit_appcode`),
  KEY `action_time` (`action_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用继承操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_inherit_logs`
--

LOCK TABLES `app_inherit_logs` WRITE;
/*!40000 ALTER TABLE `app_inherit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_inherit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_inherit_relations`
--

DROP TABLE IF EXISTS `app_inherit_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_inherit_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL COMMENT '继承组ID',
  `main_appcode` varchar(32) NOT NULL COMMENT '主应用代码',
  `inherit_appcode` varchar(32) NOT NULL COMMENT '继承应用代码',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `main_appcode` (`main_appcode`),
  KEY `inherit_appcode` (`inherit_appcode`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COMMENT='应用继承关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_inherit_relations`
--

LOCK TABLES `app_inherit_relations` WRITE;
/*!40000 ALTER TABLE `app_inherit_relations` DISABLE KEYS */;
INSERT INTO `app_inherit_relations` VALUES (32,32,'7f40877ef578590fcfd7b0a7dd899184','3353e35121beb45d4d382ad01c72295d','2025-03-21 16:23:40');
/*!40000 ALTER TABLE `app_inherit_relations` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=MyISAM AUTO_INCREMENT=56556 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application`
--

LOCK TABLES `application` WRITE;
/*!40000 ALTER TABLE `application` DISABLE KEYS */;
/*!40000 ALTER TABLE `application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_version`
--

DROP TABLE IF EXISTS `db_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL COMMENT '版本号',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `description` text COMMENT '更新说明',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据库版本信息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_version`
--

LOCK TABLES `db_version` WRITE;
/*!40000 ALTER TABLE `db_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `db_version` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='注册卡密';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kami`
--

LOCK TABLES `kami` WRITE;
/*!40000 ALTER TABLE `kami` DISABLE KEYS */;
INSERT INTO `kami` VALUES (62,'lLgcftZlDfLNul22','+1 day','','2025-03-08 08:06:02','127.0.0.1','admin',1,'2025-03-08 08:06:26','ces123','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 08:06:25','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1}'),(63,'soiI9CycbiTO56bQ','+1 day','','2025-03-08 08:11:07','127.0.0.1','admin',1,'2025-03-08 08:11:21','4F3qdB7d2329h8do','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 08:11:21','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(64,'Is42VHL2HSmJo424','+1 day','','2025-03-08 08:12:00','127.0.0.1','admin',1,'2025-03-08 08:12:20','4F3qdB7d2329h8do','7f40877ef578590fcfd7b0a7dd899184','2025-03-10 08:11:21','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(65,'OWzhVO466oVv4OvZ','+1 day','','2025-03-08 08:16:01','127.0.0.1','admin',1,'2025-03-08 08:16:24','ces123','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 08:16:24','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(66,'UhBBuDVUHIB0IZ2z','+1 day','','2025-03-08 08:16:49','127.0.0.1','admin',1,'2025-03-08 08:16:59','ces123','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 08:16:59','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(67,'1iaI1R3gQm3hsSSq','+1 day','','2025-03-08 08:51:27','127.0.0.1','admin',1,'2025-03-08 08:51:40','ces123','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 08:51:40','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(68,'0bX3512zldTxD6o8','+1 day','','2025-03-08 11:20:10','127.0.0.1','admin',1,'2025-03-08 11:20:33','ces123','7f40877ef578590fcfd7b0a7dd899184','2025-03-09 11:20:33','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"3353e35121beb45d4d382ad01c72295d\"]}'),(69,'3K0Z0F22v0YF29G2','+1 day','','2025-03-21 09:01:36','127.0.0.1','admin',1,'2025-03-21 09:02:04','ces123','3353e35121beb45d4d382ad01c72295d','2025-03-22 09:02:04','{\"connection\":-1,\"bandwidthup\":-1,\"bandwidthdown\":-1,\"inherit_apps\":[\"7f40877ef578590fcfd7b0a7dd899184\"]}');
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
) ENGINE=MyISAM AUTO_INCREMENT=5438 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES (440,'登录日志','可能暴力破解','2025-01-31 10:49:04','','127.0.0.1'),(441,'登录日志','可能暴力破解','2025-01-31 10:49:17','','127.0.0.1'),(445,'登录日志','可能暴力破解','2025-02-03 12:00:40','','127.0.0.1'),(472,'登录日志','可能暴力破解','2025-02-04 12:58:44','','82.152.105.51'),(509,'登录日志','可能暴力破解','2025-02-04 14:36:13','','111.124.250.147'),(510,'登录日志','可能暴力破解','2025-02-04 14:36:19','','111.124.250.147'),(513,'登录日志','可能暴力破解','2025-02-04 14:41:31','','111.124.250.147'),(5048,'登录日志','可能暴力破解','2025-02-13 13:52:18','','127.0.0.1'),(5239,'登录日志','可能暴力破解','2025-03-05 15:35:00','','127.0.0.1'),(5240,'登录日志','可能暴力破解','2025-03-05 15:35:19','','127.0.0.1'),(5437,'修改密码','密码修改成功','2025-04-02 13:07:07','admin','127.0.0.1'),(5436,'更新设置','更新了网站设置','2025-04-02 13:05:07','admin','127.0.0.1'),(5435,'更新设置','更新了网站设置','2025-04-02 13:04:51','admin','127.0.0.1'),(5434,'清空日志','清空了所有日志记录，共 186 条','2025-04-02 13:04:32','admin','127.0.0.1');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='应用配置信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_apps`
--

LOCK TABLES `package_apps` WRITE;
/*!40000 ALTER TABLE `package_apps` DISABLE KEYS */;
INSERT INTO `package_apps` VALUES (1,'7f40877ef578590fcfd7b0a7dd899184','测试','127.0.0.1','11','','',0,1,'2025-03-08 18:00:55','2025-03-08 18:00:55');
/*!40000 ALTER TABLE `package_apps` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COMMENT='套餐表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (2,'tk',1.000000,1.00,1,'d136ce17928bc6493441424aaab3b6a6','2025-01-13 17:12:12'),(3,'tk',1.000000,1.00,1,'51392d0ae9348cb0ccadd5dac225646b','2025-01-17 12:09:53'),(4,'tk',11.000000,1.00,1,'309c37e23444c90fc07fc2bd19cb895c','2025-01-17 13:41:20'),(10,'tk',0.041667,1.00,1,'7b89f91a4947ab6533a7adec787154c0','2025-02-06 14:11:03'),(11,'zk',1.000000,1.00,1,'7b89f91a4947ab6533a7adec787154c0','2025-02-06 14:11:46'),(15,'cs',1.000000,0.01,1,'7f40877ef578590fcfd7b0a7dd899184','2025-03-08 17:08:05'),(16,'天卡',1.000000,0.01,1,'3353e35121beb45d4d382ad01c72295d','2025-03-21 16:54:17');
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
INSERT INTO `pay_config` VALUES (1,'','','',0,0,0,0,'2025-01-17 12:09:32','2025-04-02 21:04:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='服务器列表';
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
  `hostname` varchar(255) NOT NULL COMMENT '网站标题',
  `cookies` varchar(255) NOT NULL COMMENT '登录会话',
  `found_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `over_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '到期时间',
  `siteurl` varchar(255) NOT NULL COMMENT '主域名',
  `state` tinyint(1) NOT NULL DEFAULT '1' COMMENT '站点违规',
  `pan` varchar(255) NOT NULL COMMENT '网盘链接',
  `wzgg` mediumtext CHARACTER SET utf8mb4 NOT NULL COMMENT '网站公告',
  `kf` varchar(255) NOT NULL COMMENT '客服链接',
  `img` varchar(255) NOT NULL COMMENT 'LOGO图片',
  `ggswitch` int(1) NOT NULL DEFAULT '1' COMMENT '公告开关 0=关闭 1=开启',
  `kfswitch` int(1) NOT NULL DEFAULT '1' COMMENT '客服开关 0=关闭 1=开启',
  `panswitch` int(1) NOT NULL DEFAULT '1' COMMENT '网盘开关 0=关闭 1=开启',
  `qx` int(1) NOT NULL DEFAULT '1' COMMENT '权限等级',
  `dayimg` varchar(255) NOT NULL DEFAULT '' COMMENT '日间背景图片',
  `nightimg` varchar(255) NOT NULL DEFAULT '' COMMENT '夜间背景图片',
  `bgswitch` int(1) NOT NULL DEFAULT '1' COMMENT '背景切换开关 0=关闭 1=开启',
  `show_online_pay` int(1) NOT NULL DEFAULT '1' COMMENT '在线续费/注册开关',
  `show_kami_pay` int(1) NOT NULL DEFAULT '1' COMMENT '卡密充值开关',
  `show_kami_reg` int(1) NOT NULL DEFAULT '1' COMMENT '卡密注册开关',
  `show_user_search` int(1) NOT NULL DEFAULT '1' COMMENT '用户查询开关',
  `show_kami_query` int(1) NOT NULL DEFAULT '1' COMMENT '卡密查询开关',
  `show_change_pwd` int(1) NOT NULL DEFAULT '1' COMMENT '修改密码功能开关',
  `multi_domain` int(1) NOT NULL DEFAULT '0' COMMENT '多域名开关 0=关闭 1=开启',
  `domain_list` text COMMENT '多域名列表',
  `inherit_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用应用继承',
  `show_inherit_apps` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否在前端显示继承应用',
  `inherit_groups` text NOT NULL COMMENT '继承组配置JSON',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `hostname_index` (`hostname`),
  KEY `siteurl_index` (`siteurl`)
) ENGINE=MyISAM AUTO_INCREMENT=61 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='普通管理员';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_admin`
--

LOCK TABLES `sub_admin` WRITE;
/*!40000 ALTER TABLE `sub_admin` DISABLE KEYS */;
INSERT INTO `sub_admin` VALUES (1,'admin','e10adc3949ba59abbe56e057f20f883e','故离端口','1311Zq7GJByHw3BC4qBZqkN5C3VzIhUTXuxGnjEW6irSuNcWUQWkKDsVkRifLxpPNyb/UXL7t14G6gLTUlaMlk6fsw','2024-12-03 05:17:17','2033-12-31 05:17:17','127.0.0.1',1,'','# ? 欢迎使用故离端口系统\n\n## ? 最新更新 v4\n我们很高兴地宣布新版本发布了！以下是主要更新内容：\n\n### ? 功能优化\n- ✨ 新增在线支付功能\n- ? 增强账号安全性\n- ? 优化用户界面体验\n- ? 提升系统稳定性\n\n### ? 使用说明\n1. 账号注册：\n   - 支持卡密注册\n   - 支持在线支付注册\n2. 账号续费：\n   - 可使用卡密续费\n   - 支持支付宝/微信支付\n\n### ? 使用技巧\n> **温馨提示**：首次使用请仔细阅读以下内容\n\n### ? 套餐价格\n\n| 套餐类型 | 时长 | 价格 |\n|---------|------|------|\n| 体验套餐 | 1天  | ¥1   |\n| 月卡    | 30天 | ¥15  |\n| 季卡    | 90天 | ¥40  |\n| 年卡    | 365天| ¥150 |\n\n### ? 特别说明\n1. 严禁违规使用\n2. 禁止账号共享\n3. 有问题请联系客服\n\n### ? 快速链接\n- [使用教程](https://example.com/tutorial)\n- [常见问题](https://example.com/faq)\n- [用户协议](https://example.com/terms)\n\n---\n\n### ? 联系方式\n- 客服QQ：[点击添加](http://wpa.qq.com/msgrd?v=3&uin=您的QQ&site=qq&menu=yes)\n- 官方群：123456789\n- 技术支持：support@example.com\n\n> ? 感谢您的使用，我们会持续优化系统，为您提供更好的服务！\n\n---\n*最后更新时间：2025/3/21*','','',1,1,1,1,'https://www.loliapi.com/acg/','https://www.loliapi.com/acg/',1,0,0,1,1,1,1,1,'',1,1,'{&amp;quot;groups&amp;quot;:[]}','2025-04-02 13:07:07');
/*!40000 ALTER TABLE `sub_admin` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-02 21:08:02
