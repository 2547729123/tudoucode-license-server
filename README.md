tudoucode-license-server/
├── tudoucode-license-server.php
├── /admin
│   ├── admin-license-page.php             # 原授权管理页（已函数化）
├── /includes
│   ├── class-license-api.php
│   ├── class-license-manager.php
│   └── class-license-logger.php
├── /assets
│   └── style.css
└── README.md
__________________________________________________________________________________________
# 码铃薯授权中心 - 云授权服务器

## 简介

**码铃薯授权中心**是一款基于WordPress的云端授权管理插件，专为码铃薯系列插件提供授权码生成、管理和验证的云端解决方案。  
通过该插件，开发者可以轻松管理授权码，实现绑定域名、授权状态、过期时间等多维度控制，支持REST API接口便于插件端远程验证授权。

---

## 主要功能

- **授权码管理后台界面**  
  方便添加、删除、查看授权码，支持绑定域名与过期时间设置。  
- **授权状态自动判定**  
  实时显示授权码有效、过期、无效状态。  
- **REST API接口**  
  支持远程授权码验证，简化插件端对接流程。  
- **批量导入导出（CSV格式）**  
  方便授权码数据备份与迁移。  
- **后台分页显示**  
  授权码列表支持分页，提升管理体验。  
- **前端授权码查询界面**  
  允许用户输入授权码和域名进行状态查询，方便客服或用户自助验证。  

---

## 安装与使用

1. 下载插件文件夹上传至 WordPress 插件目录 `/wp-content/plugins/`。  
2. 在 WordPress 后台插件管理中激活“码铃薯授权中心”。  
3. 在左侧菜单栏找到“授权中心”，进入管理页面。  
4. 使用“添加授权码”表单新增授权信息，授权码可自定义，绑定域名和过期时间必须填写。  
5. 授权码列表支持分页，点击“删除”可移除无效授权。  
6. 开发者可通过REST API接口 `/wp-json/tudoucode-license/v1/verify` 远程验证授权状态。  

---

## REST API接口说明

### 授权验证接口

- **请求地址**  
  `POST /wp-json/tudoucode-license/v1/verify`

- **请求参数**（JSON格式）  
  ```json
  {
    "license_key": "授权码字符串",
    "domain": "绑定域名"
  }


响应示例

    成功

{
  "success": true,
  "message": "授权验证成功",
  "data": {
    "license_key": "xxxxxxx",
    "domain": "example.com",
    "expire": "2025-12-31"
  }
}

失败

        {
          "success": false,
          "message": "授权码不存在"
        }

文件结构

tudoucode-license-server/
├── assets/
│   └── style.css          # 后台管理样式
├── includes/
│   ├── admin-page.php     # 后台管理页面代码
│   ├── api.php            # REST API接口代码
│   ├── license-manager.php# 授权码管理核心逻辑
│   └── utils.php          # 工具函数
├── tudoucode-license-server.php  # 主插件入口文件
└── README.md              # 插件说明文档

兼容性

    WordPress 5.6及以上版本

    PHP 7.2及以上版本

版权信息

本插件采用GPL2协议开源，欢迎自由使用与修改。
开发者：码铃薯
官方网站：https://www.tudoucode.cn
联系方式

如有疑问或需求，欢迎访问官方网站获取支持。

感谢您使用码铃薯授权中心，愿它助力您的插件授权管理更高效、更便捷！
