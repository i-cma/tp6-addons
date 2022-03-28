# ThinkPHP应用模块介绍
## 应用目录结构
```html
www  WEB部署目录（或者子目录）
├─addons           插件目录
│  ├─controller    控制器目录
│  │  ├─admin      后台控制器目录
│  │  ├─api        API控制器目录
│  │  ├─index      前台控制器目录
│  ├─model         模型目录
│  ├─view          应用前端模板文件
│  ├─ ...          更多类库目录
│  │
│  ├─common.php    公共函数文件
│  ├─config.php    应用介绍配置文件
│  ├─menus.php     应用菜单配置文件
│  ├─Init.php      应用入口文件
│  ├─install.sql   应用安装SQL文件
│  ├─uninstall.sql 应用卸载SQL文件
│  └─event.php     微信回调事件文件
```

## config.php 应用介绍配置说明
+ info：配置应用基本信息

+ platform：配置应用支持平台

+ mp_msg：配置应用微信公众号订阅消息，`当 platform.mp == true 时有效`
    - subscribes：订阅事件 
    - handles：处理事件
    具体请参考微信开发文档：
    <https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_standard_messages.html>

## menus.php 菜单配置介绍
菜单最多支持三级菜单，一级菜单可以配置icon图标，若要引用自定义图标，可以自行导入图标文件。

菜单路径参考TP路由。

若包含`submenu`则表示有下级菜单，若需实现选中菜单高亮或者页面有默认菜单高亮则需要配置`uris`值，值为页面路径。

## event.php 微信回调事件通知

在开发微信公众号项目时，可以接受微信推送的消息订阅。
我们可以通过该文件来接受对应的订阅事件并处理。
文件格式请参考TP6文档->事件章节 
<https://www.kancloud.cn/manual/thinkphp6_0/1037492>

## install.sql 安装SQL文件
安装应用所需SQL文件，文件为标准SQL语句，单独语句用英文分号`;`分割。

## uninstall.sql 安装SQL文件
卸载应用所需SQL文件，文件为标准SQL语句，单独语句用英文分号`;`分割。

## 注册事件 / 钩子
+ AddonsBegin ： 应用开始时
+ AddonInit   ： 应用初始化
+ AddonsActionBegin ： 应用方法开始时
+ AddonMiddleware   ： 应用中间件路由
