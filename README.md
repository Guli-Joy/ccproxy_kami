# 🚀 CCPROXY 代理管理系统

<div align="center">

[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Version](https://img.shields.io/badge/version-V4-brightgreen.svg?style=flat-square)](https://github.com/Guli-Joy/ccproxy_kami)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.0-orange.svg?style=flat-square&logo=php)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/mysql-%3E%3D5.7-blue.svg?style=flat-square&logo=mysql)](https://www.mysql.com)
[![QQ Group](https://img.shields.io/badge/QQ_Group-1015197745-blue.svg?style=flat-square&logo=tencent-qq)](https://qm.qq.com/q/YpoK9Aifei)
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

### 🌟 V4.1.2 (2024-01-25)
- 🛠️ 功能修复
  - 修复修改管理员密码时无法修改的问题
  - 修复服务器列表编辑时无法保存的问题
  - 修复md5加密的密码无法登录的问题
- 🔍 代码优化
  - 删除newserver.php中的调试日志输出
  - 删除editserver.php中的调试日志输出
  - 删除ajax.php中的调试日志输出
  - 优化代码结构，提升可读性
- 💡 性能优化
  - 减少不必要的日志输出
  - 提升系统运行效率
  - 优化代码执行性能

### 🌟 V4.1.1 (2024-01-13)
- 🛠️ 功能修复
  - 修复批量删除用户时的服务器检查逻辑
  - 优化用户删除失败的错误提示
  - 修复数组转字符串导致的日志记录错误
- 🔒 安全性增强
  - 完善服务器存在性验证
  - 加强数据过滤处理
  - 优化日志记录格式
- 💡 性能优化
  - 优化代码结构
  - 提升系统稳定性
  - 改进错误处理机制

### 🌟 V4.0.0 (2024-01-10)
- 📱 移动端优化
  - 全面优化移动端适配
  - 改进响应式布局
  - 优化触摸交互体验
- 🎨 界面升级
  - 优化卡片式布局设计
  - 改进表单布局和间距
  - 优化按钮和开关组件样式
- 🛠️ 技术改进
  - 重构CSS架构
  - 优化样式文件结构
  - 提升代码可维护性

### 🌟 V3.0.0 (2024-01)
- 🛡️ 安全升级
  - 修复多处安全漏洞
  - 增强系统安全防护
  - 优化权限控制机制
- ✨ 功能优化
  - 完善支付功能模块
  - 优化服务器管理功能
  - 改进用户界面交互

### 🌟 V2.0.0 (2023-12)
- 🔨 系统优化
  - 优化安装流程
  - 增强系统安全性
  - 改进用户界面体验
- 📦 功能新增
  - 新增自动更新功能
  - 修复已知问题
  - 优化系统性能

### 🌟 V1.0.0 (2023-12)
- 🎯 首次发布
  - 基于一花CCPROXY V1.5.2.2版本进行二次开发
  - 新增支付系统功能
  - 优化服务器管理模块
  - 完善移动端适配
  - 修复基础功能bug

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
- 💬 官方QQ群：[点击加入](https://qm.qq.com/q/YpoK9Aifei) | 1015197745
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

## 🔗 项目来源

本项目基于 [yeuxuan/ccproxy_kami](https://github.com/yeuxuan/ccproxy_kami) 进行二次开发。感谢原作者的开源贡献！

## ⚖️ 免责声明

1. 本项目仅供学习和研究使用，严禁用于任何非法用途。使用者应遵守当地法律法规，如有违反，后果自负。

2. 使用本系统所造成的任何直接或间接损失，包括但不限于：
   - 数据丢失或泄露
   - 服务器安全问题
   - 经济损失
   - 法律风险
   作者均不承担任何责任。

3. 本系统的使用者需要自行承担以下责任：
   - 服务器和数据的安全维护
   - 用户数据的合规收集和使用
   - 遵守相关法律法规和政策
   - 确保合法合规的经营行为

4. 原作者保留对本项目的最终解释权和所有权利。任何未经授权的商业使用、分发或修改等行为都可能构成侵权。

5. 如发现本项目被用于任何非法用途，作者有权立即终止对相关用户的服务，并保留追究法律责任的权利。

6. 使用本系统即表示您已完全理解并同意以上免责声明的所有内容。

## 🤝 贡献指南

1. 🔀 Fork 本仓库
2. 🌿 创建新的特性分支
3. ✨ 提交您的更改
4. ✅ 确保测试通过
5. 📤 提交Pull Request

注意：提交代码时请确保：
- 遵守原作者的开源协议
- 不包含任何敏感信息
- 代码符合项目规范
- 已经过充分测试

---

<p align="center">
    <b>CCPROXY - 让代理服务管理更简单</b>
    <br>
    <i>Copyright © 2024 CCPROXY. All Rights Reserved.</i>
    <br>
    <i>原创作者 © yeuxuan - <a href="https://github.com/yeuxuan/ccproxy_kami">ccproxy_kami</a></i>
</p>
