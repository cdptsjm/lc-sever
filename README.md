# 领创后端-gui

一个纯PHP实现的配置管理网站，支持应用商店配置、设备策略配置和用户管理。

## 功能特性

- **应用商店配置**: 添加、编辑、删除应用
- **设备策略配置**: 
  - 应用管理（添加、编辑、删除设备应用）
  - 设备功能管理（GPS、相机、WiFi等开关）
  - 违规处理策略配置
  - 设备详细设置
- **用户管理**: 添加、编辑、删除用户
- **实时保存**: 修改后自动保存，支持手动保存
- **数据刷新**: 支持重新加载JSON文件
- **HTTPS支持**: 内置HTTPS强制跳转配置（需取消注释）

## 文件结构

```
/
├── index.php          # 主页面（前端界面 + 内联CSS）
├── api.php            # API接口（数据加载/保存）
├── .htaccess          # Apache重写规则和安全配置
├── web.config         # IIS配置文件
├── README.md          # 本文件
├── app.json           # 应用商店数据
├── deictv.json        # 设备策略数据
├── user.json          # 用户数据
└── backup/            # 自动备份目录
```

## 安装部署

git本仓库并创建网站

### 环境要求

- PHP 7.0 或更高版本
- Apache/Nginx/IIS 服务器
- 启用 `json` 扩展

### Apache 配置

确保已启用 `mod_rewrite` 模块：
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### Nginx 配置

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name your-domain.com;
    
    root /var/www/html;
    index index.php;
    
    # HTTPS 强制跳转（生产环境启用）
    # if ($scheme != "https") {
    #     return 301 https://$host$request_uri;
    # }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 保护JSON文件
    location ~* \.(json|bak|tmp|log)$ {
        deny all;
    }
    
    # 安全响应头
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
}
```

### 文件权限

确保Web服务器有读写权限：
```bash
chmod -R 755 /var/www/html
chown -R www-data:www-data /var/www/html
```

## 启用HTTPS

### 方法1: 使用 .htaccess（Apache）

编辑 `.htaccess` 文件，取消以下行的注释：
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 方法2: 使用 Nginx

在 server 块中添加：
```nginx
if ($scheme != "https") {
    return 301 https://$host$request_uri;
}
```

### 方法3: PHP 强制跳转

编辑 `api.php`，取消以下代码的注释：
```php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
```

## 使用说明

1. **访问系统**: 打开浏览器访问 `http://your-server/` 或 `https://your-server/`

2. **切换标签页**: 
   - 点击左侧导航栏或顶部标签切换不同配置页面

3. **编辑数据**:
   - 点击表格中的"编辑"按钮修改项目
   - 修改完成后点击"保存"按钮
   - 系统会自动保存，也可以点击侧栏"保存"按钮手动保存

4. **添加数据**:
   - 点击卡片右上角的"+ 添加"按钮
   - 填写表单后保存

5. **删除数据**:
   - 点击表格中的"删除"按钮
   - 确认后删除

6. **刷新数据**:
   - 点击侧栏"刷新"按钮重新加载JSON文件

7. **设备策略配置**:
   - 使用开关控件启用/禁用功能
   - 修改后立即自动保存

## 数据备份

系统会自动创建备份：
- 每次保存时，原文件会被备份到 `backup/` 目录
- 备份文件命名格式：`文件名.时间戳.bak`
- 系统保留最近30个备份，旧备份自动删除

## 安全特性

- 安全响应头（X-Frame-Options, X-XSS-Protection等）
- JSON文件访问保护
- 隐藏文件保护
- 原子写入操作（先写临时文件再重命名）
- 自动数据备份

## 浏览器兼容性

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 许可证

MIT License
