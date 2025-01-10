# 🚀 CCPROXY 代理管理系统

<div align="center">

[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Version](https://img.shields.io/badge/version-V3-brightgreen.svg?style=flat-square)](https://github.com/Guli-Joy/ccproxy_kami)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.0-orange.svg?style=flat-square&logo=php)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/mysql-%3E%3D5.7-blue.svg?style=flat-square&logo=mysql)](https://www.mysql.com)
[![GitHub stars](https://img.shields.io/github/stars/Guli-Joy/ccproxy_kami?style=flat-square)](https://github.com/Guli-Joy/ccproxy_kami/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/Guli-Joy/ccproxy_kami?style=flat-square)](https://github.com/Guli-Joy/ccproxy_kami/network)

<p align="center">
  <b>功能完整的代理服务器管理系统 | 多用户支持 | 安全可靠</b>
</p>

</div>

## 🌟 项目介绍

> CCPROXY是一个功能完整的代理服务器管理系统，支持多用户、多服务器的代理服务管理。系统采用模块化设计，提供完善的用户管理、服务器管理、卡密系统、支付系统等功能，适用于各类代理服务运营场景。本项目基于一花CCPROXY卡密管理系统V1.5.2.2进行二次开发优化，在原有功能基础上进行了多项功能增强和安全性改进。

## ✨ 主要特性

<table>
<tr>
<td>

- 📦 **多用户管理系统**
  - 支持多级用户权限管理
  - 完善的用户组织架构
  
- 🖥️ **代理服务器管理**
  - 集中化管理多台服务器
  - 实时监控服务器状态

- 🎫 **卡密系统**
  - 灵活的卡密生成与管理
  - 批量操作支持

</td>
<td>

- 💳 **在线支付系统**
  - 支持多种支付方式
  - 安全的交易环境

- 🔍 **用户查询功能**
  - 便捷的用户信息查询
  - 完整的数据统计

- 🛡️ **安全防护**
  - 多重安全防护机制
  - 实时监控与预警

</td>
</tr>
</table>

## 🛠️ 技术栈

<table>
<tr>
<td>

### 后端技术
- ⚡ PHP 7.0+
- 📊 MySQL 5.7+
- 🔧 Apache/Nginx

</td>
<td>

### 前端技术
- 🎨 HTML5/CSS3
- 📱 Bootstrap
- 💻 JavaScript

</td>
</tr>
</table>

## 📋 系统要求

### 基础环境
- ⚡ PHP >= 7.0
- 📊 MySQL >= 5.7
- 🌐 Apache/Nginx Web服务器

### 必需PHP扩展
```
✓ PDO
✓ MySQL
✓ curl
✓ openssl
✓ session
✓ json
```

## 📥 快速开始

### 🔄 安装步骤

1️⃣ 下载最新版本源码
2️⃣ 上传程序到网站根目录
3️⃣ 访问 `http://您的域名/install/` 进入安装向导
4️⃣ 按照向导填写数据库等相关信息
5️⃣ 完成安装，删除install目录

### 👤 默认账户
```
管理员账号：admin
管理员密码：123456
⚠️ 请在首次登录后立即修改默认密码！
```

## 🛡️ 安全特性

<table>
<tr>
<td>

- 🔒 XSS防护
- 🛡️ SQL注入防护
- 🔐 CSRF防护

</td>
<td>

- 🚫 CC攻击防护
- 📝 密码加密存储
- 🔑 会话安全控制

</td>
</tr>
</table>

## 📈 更新日志

### 🌟 v4 (2024-01-10)
- 📱 全面优化移动端适配
  - 优化网站设置页面移动端显示
  - 改进支付配置页面移动端布局
  - 优化表单控件在移动端的交互体验
- 🎨 界面交互优化
  - 优化卡片式布局设计
  - 改进表单布局和间距
  - 增强移动端触摸反馈
- ✨ 功能改进
  - 优化开关组件显示效果
  - 改进输入框交互体验
  - 优化按钮布局和样式
- 🛠️ 技术优化
  - 重构CSS架构，提升代码可维护性
  - 优化样式文件组织结构
  - 改进响应式布局实现

### 🌟 v3 (2024-01)
- 🛡️ 修复多处安全漏洞
- ✨ 优化系统性能
- 🎨 改进用户界面交互
- 📦 完善支付功能模块
- 🔧 优化服务器管理功能

### 🌟 v2 (2024-01)
- ✨ 优化安装流程
- 🛡️ 增强系统安全性
- 🎨 改进用户界面体验
- 🐛 修复已知问题
- 📦 新增自动更新功能

### 🌟 v1 (2023-12)
- 🔨 基于一花CCPROXY V1.5.2.2版本进行二次开发
- 🆕 新增支付系统
- 🔄 优化服务器管理
- 📱 移动端适配优化
- 🛠️ 修复若干bug

## ⚠️ 注意事项

<table>
<tr>
<td>

### 📌 安装相关
1. 🚫 安装完成后必须删除install目录
2. 🔐 及时修改默认管理员密码
3. 💾 定期备份数据库数据
4. 📝 确保日志目录可写权限
5. 🔒 建议使用HTTPS协议访问

</td>
<td>

### 🛡️ 安全建议
1. 🔄 定期更新系统版本
2. 🔑 使用强密码策略
3. 🛡️ 开启防火墙保护
4. 🔒 限制管理员IP访问
5. 📋 定期查看安全日志

</td>
</tr>
</table>

## 🤝 技术支持

<table>
<tr>
<td>

### 📢 官方支持
- 💬 官方QQ群：[点击加入](https://qm.qq.com/q/YpoK9Aifei)
- 🌐 官方网站：[Github](https://github.com/Guli-Joy/ccproxy_kami)
- 📧 技术邮箱：573000041@qq.com

</td>
<td>

### 👥 社区支持
- 📌 GitHub Issues
- 💡 技术论坛
- 👨‍💻 开发者社区

</td>
</tr>
</table>

## 📄 许可说明

<table>
<tr>
<td>

### ✅ 您可以：
- 使用
- 复制
- 修改
- 合并
- 出版
- 分发
- 再授权
- 销售该软件的副本

</td>
<td>

### ⚠️ 必须：
- 包含原始许可证
- 包含版权声明

详情请参阅 [LICENSE](LICENSE) 文件

</td>
</tr>
</table>

## ⚖️ 免责声明

1. 本系统仅供学习研究使用，请勿用于非法用途
2. 使用本系统所造成的任何直接或间接损失，作者不承担任何责任
3. 用户需自行承担使用本系统的风险
4. 作者保留对本系统的最终解释权

## 🤝 贡献指南

1. 🔀 Fork 本仓库
2. 🌿 创建新的特性分支
3. ✨ 提交您的更改
4. ✅ 确保测试通过
5. 📤 提交Pull Request

---

<p align="center">
    <b>CCPROXY - 让代理服务管理更简单</b>
    <br>
    <i>Copyright © 2024 CCPROXY. All Rights Reserved.</i>
</p>