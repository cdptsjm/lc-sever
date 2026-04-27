<?php
session_start();
header('X-Accel-Buffering: no');  // 告诉 Nginx 不要缓冲
require_once 'lang.php';
$currentLang = isset($_GET['lang']) ? $_GET['lang'] : ''; 

if (isset($_GET['lang'])) {
    Lang::setLang($_GET['lang']);
}

// 检查登录状态
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// 检查用户是否被禁用
$currentUser = $_SESSION['username'] ?? '';
$users = json_decode(file_get_contents('data/user.json'), true);
$userDisabled = true; // 默认禁用，找不到用户也视为禁用

foreach ($users as $user) {
    if ($user['email'] === $currentUser) {
        if (isset($user['status']) && $user['status'] == 1) {
            $userDisabled = false; // 状态正常
        }
        break;
    }
}

// 如果用户被禁用，清除登录状态并跳转
if ($userDisabled) {
    // 清除所有会话数据
    $_SESSION = array();
    
    // 销毁会话 Cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // 销毁会话
    session_destroy();
    
    // 显示提示并跳转登录页
    echo "<script>
        alert('" . (Lang::get('账号已被禁用，请联系管理员', $lang) ?? '您的账号已被禁用，请联系管理员') . "');
        window.location.href = 'login.php';
    </script>";
    exit;
}



$lang = Lang::getLang();
$activeTab = $_GET['tab'] ?? 'app';

// 读取 JSON 数据
$appData = json_decode(file_get_contents('data/app.json'), true);
$deviceData = json_decode(file_get_contents('data/deictv.json'), true);
$userData = json_decode(file_get_contents('data/user.json'), true);
$linkData = json_decode(file_get_contents('data/link.json'), true);
$advertiseData = json_decode(file_get_contents('data/advertise.json'), true);
$appgroupData = json_decode(file_get_contents('data/appgroups.json'), true);
$limitData = json_decode(file_get_contents('data/limit.json'), true);
$componentData = json_decode(file_get_contents('data/setting.json'), true);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'en' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Lang::get('admin_title', $lang); ?> - <?php 
                $displayName = 'Admin';
                foreach ($userData as $u) {
                    if ($u['email'] === ($_SESSION['username'] ?? '')) {
                        $displayName = $u['name'];
                        break;
                    }
                }
                ?><?php echo htmlspecialchars($displayName); ?></title>
    <link href="./css/app.css" rel="stylesheet">
    <link href="./css/bootstrap-dialog.min.css" rel="stylesheet">
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet">-->
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap-theme.min.css" rel="stylesheet">-->
    <link href="./css/main.css" rel="stylesheet">
    <link href="./css/271.css" rel="Stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link rel="icon" href="./title.png" type="image/x-icon">
    <style>
@keyframes progressMove {
    0% { background-position: 0 0; }
    100% { background-position: 20px 0; }
}

/* 成功状态 */
.progress-success { background: linear-gradient(90deg, #5cb85c 0%, #4cae4c 100%) !important; }
/* 错误状态 */
.progress-error { background: linear-gradient(90deg, #d9534f 0%, #c9302c 100%) !important; }
/* 上传中状态 */
.progress-active { background: linear-gradient(90deg, #5bc0de 0%, #46b8da 100%) !important; }
</style>
    <style>
    
    /* 表格容器 - 自适应宽度，禁止横向滚动 */
    .tab-content-wrapper {
        width: 100%;
        overflow-x: hidden; /* 禁止横向溢出 */
    }
    
    /* 表格基础样式 - 自适应布局 */
    .data-table {
        width: 100%;
        table-layout: fixed; /* 固定表格布局，允许控制列宽 */
        border-collapse: collapse;
        word-wrap: break-word; /* 长单词换行 */
    }
    
    /* 表头样式 */
    .data-table thead th {
        background-color: #f5f5f5;
        font-weight: 600;
        border-bottom: 2px solid #ddd;
        padding: 10px 8px;
        text-align: left;
        font-size: 1.0em;
        white-space: nowrap; /* 表头不换行 */
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* 单元格基础样式 */
    .data-table tbody td {
        padding: 10px 8px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        font-size: 1.0em;
    }
    
    /* 列宽定义 - 应用商店表格 */
    .data-table th:nth-child(1), /* ID */
    .data-table td:nth-child(1) {
        width: 50px;
        text-align: center;
    }
    
    .data-table th:nth-child(2), /* 图标 */
    .data-table td:nth-child(2) {
        width: 50px;
        text-align: center;
    }
    
    .data-table th:nth-child(3), /* 名称 */
    .data-table td:nth-child(3) {
        width: 15%;
        max-width: 150px;
    }
    
    .data-table th:nth-child(4), /* 包名 */
    .data-table td:nth-child(4) {
        width: 20%;
        max-width: 200px;
        font-family: monospace;
        font-size: 1.0em;
    }
    
    .data-table th:nth-child(5), /* 版本名 */
    .data-table td:nth-child(5),
    .data-table th:nth-child(6), /* 版本号 */
    .data-table td:nth-child(6) {
        width: 10%;
        text-align: center;
    }
    
    .data-table th:nth-child(7), /* 隐藏 */
    .data-table td:nth-child(7),
    .data-table th:nth-child(8), /* 可卸载 */
    .data-table td:nth-child(8),
    .data-table th:nth-child(9), /* 强制 */
    .data-table td:nth-child(9) {
        width: 8%;
        text-align: center;
    }
    
    .data-table th:nth-child(10), /* 操作 */
    .data-table td:nth-child(10) {
        width: 100px;
        text-align: center;
    }
    
    /* 长文本截断显示省略号 */
    .data-table td:nth-child(3), /* 名称 */
    .data-table td:nth-child(4) { /* 包名 */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* 鼠标悬停时显示完整内容（tooltip效果） */
    .data-table td:nth-child(3):hover,
    .data-table td:nth-child(4):hover {
        overflow: visible;
        white-space: normal;
        word-break: break-all;
        background-color: #f9f9f9;
        position: relative;
        z-index: 1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* 设备配置表格列宽 */
    #deviceAppTableBody td:nth-child(1),
    #deviceAppTableBody td:nth-child(1) {
        width: 50px;
    }
    
    /* 用户配置表格列宽 */
    #userTableBody td:nth-child(4), /* 学校 */
    #userTableBody td:nth-child(5) { /* 用户组 */
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* 链接配置表格 */
    #linkTableBody td:nth-child(3) { /* URL */
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: monospace;
        font-size: 0.85em;
    }
    
    /* 状态徽章 */
    .status-badge {
        display: inline-block;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 0.8em;
        white-space: nowrap;
    }
    
    .status-enabled {
        background-color: #5cb85c;
        color: white;
    }
    
    .status-disabled {
        background-color: #d9534f;
        color: white;
    }
    
    /* 应用图标 */
    .app-icon {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        object-fit: cover;
    }
    
    /* 操作按钮 */
    .btn-action {
        padding: 4px 8px;
        font-size: 1.0em;
        margin: 0 2px;
    }
    
    /* 小屏幕适配 */
    @media screen and (max-width: 1200px) {
        .data-table {
            font-size: 0.85em;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px 6px;
        }
        
        /* 超小屏幕隐藏次要列 */
        .data-table th:nth-child(6), /* 版本号 */
        .data-table td:nth-child(6),
        .data-table th:nth-child(9), /* 强制状态 */
        .data-table td:nth-child(9) {
            display: none;
        }
    }
    
    @media screen and (max-width: 768px) {
        .content-area {
            padding: 10px;
        }
        
        /* 更小屏幕隐藏更多列 */
        .data-table th:nth-child(5), /* 版本名 */
        .data-table td:nth-child(5),
        .data-table th:nth-child(8), /* 可卸载 */
        .data-table td:nth-child(8) {
            display: none;
        }
        
        .btn-action {
            display: block;
            margin: 2px 0;
            width: 100%;
        }
    }
    
    /* 应用限制样式 */
.time-slot {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 2px;
    padding: 5px 8px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.applimit-time-slots {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    flex: 1;
    margin: 0 10px;
}

.applimit-time-slots:empty::before {
    content: 'No time limit';
    color: #999;
    font-style: italic;
}

/* 时间段输入框样式 */
.time-slot input[type="time"] {
    border: 1px solid #ccc;
    border-radius: 3px;
    padding: 2px 4px;
    font-size: 12px;
}
        
    </style>
</head>
<body>
<div class="main-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <?php echo Lang::get('admin_title', $lang); ?>
        </div>
        <nav class="sidebar-menu">
    <a href="?tab=app<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'app' ? 'active' : ''; ?>">
        <?php echo Lang::get('app_store', $lang); ?>
    </a>
    <a href="?tab=device<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'device' ? 'active' : ''; ?>">
        <?php echo Lang::get('device_config', $lang); ?>
    </a>
    <a href="?tab=policy<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'policy' ? 'active' : ''; ?>">
        <?php echo Lang::get('security_policy', $lang); ?>
    </a>
    <a href="?tab=launch<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
   class="<?php echo $activeTab === 'launch' ? 'active' : ''; ?>">
    <?php echo Lang::get('launch_config', $lang); ?>
    </a>
    <a href="?tab=link<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'link' ? 'active' : ''; ?>">
        <?php echo Lang::get('link_config', $lang); ?>
    </a>
    <a href="?tab=user<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'user' ? 'active' : ''; ?>">
        <?php echo Lang::get('user_config', $lang); ?>
    </a>
    <a href="?tab=browser<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
   class="<?php echo $activeTab === 'browser' ? 'active' : ''; ?>">
    <?php echo Lang::get('browser_management', $lang); ?>
    </a>
            <a href="?tab=advertise<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'advertise' ? 'active' : ''; ?>">
        <?php echo Lang::get('advertise_config', $lang); ?>
    </a>
    <a href="?tab=appgroup<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
       class="<?php echo $activeTab === 'appgroup' ? 'active' : ''; ?>">
        <?php echo Lang::get('appgroup_config', $lang); ?>
    </a>
    <a href="?tab=applimit<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
   class="<?php echo $activeTab === 'applimit' ? 'active' : ''; ?>">
    <?php echo Lang::get('app_limit', $lang); ?>
</a>
<a href="?tab=component<?php echo $currentLang ? '&lang=' . urlencode($currentLang) : ''; ?>" 
   class="<?php echo $activeTab === 'component' ? 'active' : ''; ?>">
    <?php echo Lang::get('component_control', $lang); ?>
</a>
</nav>
        <div class="sidebar-footer">
            <button class="btn btn-default btn-block" onclick="refreshData()" style="margin-bottom: 10px;">
                <span class="glyphicon glyphicon-refresh"></span> <?php echo Lang::get('refresh', $lang); ?>
            </button>
            <button class="btn btn-primary btn-block" onclick="saveAllData()">
                <span class="glyphicon glyphicon-save"></span> <?php echo Lang::get('save', $lang); ?>
            </button>
        </div>
    </aside>
    
    <main class="content-area">
                <div class="top-bar">
            <div>
                <?php 
                $displayName = 'Admin';
                foreach ($userData as $u) {
                    if ($u['email'] === ($_SESSION['username'] ?? '')) {
                        $displayName = $u['name'];
                        break;
                    }
                }
                ?>
                <strong>
                <span class="dropdown">
                    <img src="https://162.14.113.207:883/user.png " alt="user" style="vertical-align: middle; margin-right: 5px;" />
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <?php echo htmlspecialchars($displayName); ?>
                <span class="caret"></span>
                    </a>
                        <ul class="dropdown-menu" style="font-size: 12px">
                            <li>
                                <a href="#" onclick="openAboutModal(); return false;">
                                    <?php echo $lang === 'en' ? 'Privacy Statement' : '隐私声明'; ?>
                                </a>
                                <a href="#" onclick="openAnnouncementModal(); return false;">
                                    <?php echo $lang === 'en' ? 'Platform Announcement' : '平台公告'; ?>
                                </a>
                                <a href="#" onclick="openLogoutModal(); return false;">
                                    <?php echo $lang === 'en' ? 'Logout' : '退出登录'; ?>
                                </a>
                        </li>
                    </ul>
                </span>
                </strong>
            </div>
            <div>
                <span class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <?php echo $lang === 'en' ? 'English' : '中文(简体)'; ?>
                <span class="caret"></span>
                    </a>
                        <ul class="dropdown-menu" style="font-size: 12px; right: 0; left: auto;">
                            <li>
                                <a href="?lang=<?php echo $lang === 'en' ? 'zh' : 'en'; ?>">
                                    <?php echo $lang === 'en' ? '中文(简体)' : 'English'; ?>
                                </a>
                                <a href="?lang=<?php echo $lang === 'en' ? 'en' : 'zh'; ?>">
                                    <?php echo $lang === 'zh' ? '中文(简体)' : 'English'; ?>
                                </a>
                        </li>
                    </ul>
                </span>
            </div>
        </div>
        
        <div class="tab-content-wrapper">
            <?php if ($activeTab === 'app'): ?>
                <!-- 应用商店 -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openAppModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add', $lang); ?>
                    </button>
                </div>
                <div class="table-responsive" tabindex="0" role="region" aria-label="<?php echo Lang::get('app_store', $lang); ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('icon', $lang); ?></th>
                            <th><?php echo Lang::get('name', $lang); ?></th>
                            <th><?php echo Lang::get('package_name', $lang); ?></th>
                            <th><?php echo Lang::get('version_name', $lang); ?></th>
                            <th><?php echo Lang::get('version_code', $lang); ?></th>
                            <th><?php echo Lang::get('hidden', $lang); ?></th>
                            <th><?php echo Lang::get('can_uninstall', $lang); ?></th>
                            <th><?php echo Lang::get('is_force', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="appTableBody">
                        <?php foreach ($appData as $app): ?>
                        <tr data-id="<?php echo $app['id']; ?>">
                            <td><?php echo $app['id']; ?></td>
                            <td><img src="<?php echo htmlspecialchars($app['iconpath'] ?? ''); ?>" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
                            <td><?php echo htmlspecialchars($app['name']); ?></td>
                            <td><?php echo htmlspecialchars($app['packagename']); ?></td>
                            <td><?php echo htmlspecialchars($app['versionname']); ?></td>
                            <td><?php echo $app['versioncode']; ?></td>
                            <td><span class="status-badge <?php echo $app['hide_icon_status'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $app['hide_icon_status'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td><span class="status-badge <?php echo $app['canuninstall'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $app['canuninstall'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td><span class="status-badge <?php echo $app['isforce'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $app['isforce'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editApp(<?php echo $app['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteApp(<?php echo $app['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            
            <?php elseif ($activeTab === 'device'): ?>
                <!-- 设备配置 - 应用管理 -->
                <h3><?php echo Lang::get('app_management', $lang); ?></h3>
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openDeviceAppModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add', $lang); ?>
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('name', $lang); ?></th>
                            <th><?php echo Lang::get('package_name', $lang); ?></th>
                            <th><?php echo Lang::get('version_name', $lang); ?></th>
                            <th><?php echo Lang::get('version_code', $lang); ?></th>
                            <th><?php echo Lang::get('hidden', $lang); ?></th>
                            <th><?php echo Lang::get('enabled', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="deviceAppTableBody">
                        <?php foreach ($deviceData['app_tactics']['applist'] as $app): ?>
                        <tr data-id="<?php echo $app['id']; ?>">
                            <td><?php echo $app['id']; ?></td>
                            <td><?php echo htmlspecialchars($app['name']); ?></td>
                            <td><?php echo htmlspecialchars($app['packagename']); ?></td>
                            <td><?php echo htmlspecialchars($app['versionname']); ?></td>
                            <td><?php echo $app['versioncode']; ?></td>
                            <td><span class="status-badge <?php echo $app['hide_icon_status'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $app['hide_icon_status'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td><span class="status-badge <?php echo $app['status'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $app['status'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editDeviceApp(<?php echo $app['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteDeviceApp(<?php echo $app['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            
            <?php elseif ($activeTab === 'policy'): ?>
                <!-- 安全策略 -->
                <div class="settings-grid">
                    <!-- 设备管理 -->
                    <div class="settings-card">
                        <h4><?php echo Lang::get('device_management', $lang); ?></h4>
                        <?php 
                        $deviceManage = $deviceData['device_tactics']['deviceManage'] ?? [];
                        $deviceManageFields = [
                            'command_gps' => 'command_gps',
                            'command_camera' => 'command_camera',
                            'command_recording' => 'command_recording',
                            'command_data_flow' => 'command_data_flow',
                            'command_wifi_switch' => 'command_wifi_switch',
                            'command_force_open_wifi' => 'command_force_open_wifi',
                            'command_otg' => 'command_otg',
                            'command_sd_card' => 'command_sd_card',
                            'command_bluetooth' => 'command_bluetooth',
                            'command_phone_msg' => 'command_phone_msg',
                            'command_connect_usb' => 'command_connect_usb',
                            'command_wifi_advanced' => 'command_wifi_advanced',
                        ];
                        foreach ($deviceManageFields as $key => $label): 
                        ?>
                        <div class="setting-item">
                            <span><?php echo Lang::get($label, $lang); ?></span>
                            <label class="toggle-switch">
                                <input type="checkbox" class="policy-toggle" data-section="device_tactics.deviceManage.<?php echo $key; ?>" <?php echo !empty($deviceManage[$key]) ? 'checked' : ''; ?> onchange="updatePolicy(this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 违规策略 -->
                    <div class="settings-card">
                        <h4><?php echo Lang::get('illegal_tactics', $lang); ?></h4>
                        <?php 
                        $illegalTactics = ['usb_to_pc', 'already_root', 'change_simcard', 'prohibited_app'];
                        foreach ($illegalTactics as $tactic): 
                            $tacticData = $deviceData['illegal_tactics'][$tactic] ?? [];
                        ?>
                        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <strong><?php echo Lang::get($tactic, $lang); ?></strong>
                            <div style="margin-top: 10px; padding-left: 15px;">
                                <div class="setting-item">
                                    <span><?php echo Lang::get('enable', $lang); ?></span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="policy-toggle" data-section="illegal_tactics.<?php echo $tactic; ?>.enable" <?php echo !empty($tacticData['enable']) ? 'checked' : ''; ?> onchange="updatePolicy(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <span><?php echo Lang::get('notify_admin', $lang); ?></span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="policy-toggle" data-section="illegal_tactics.<?php echo $tactic; ?>.notify_admin" <?php echo !empty($tacticData['notify_admin']) ? 'checked' : ''; ?> onchange="updatePolicy(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <span><?php echo Lang::get('eliminate_data', $lang); ?></span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="policy-toggle" data-section="illegal_tactics.<?php echo $tactic; ?>.eliminate_data" <?php echo !empty($tacticData['eliminate_data']) ? 'checked' : ''; ?> onchange="updatePolicy(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <span><?php echo Lang::get('lock_workspace', $lang); ?></span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="policy-toggle" data-section="illegal_tactics.<?php echo $tactic; ?>.lock_workspace" <?php echo !empty($tacticData['lock_workspace']) ? 'checked' : ''; ?> onchange="updatePolicy(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    
                    <!-- 设备设置 -->
                    <div class="settings-card">
                        <h4><?php echo Lang::get('device_settings', $lang); ?></h4>
                        <?php 
                        $deviceSettings = $deviceData['device_setting'] ?? [];
                                                $settingFields = [
                            'sdcard_and_otg' => 'sdcard_and_otg',
                            'simcard' => 'simcard',
                            'data_flow_status' => 'data_flow_status',
                            'gallery_status' => ['label' => 'gallery_status', 'invert' => true],
                            'camera_status' => ['label' => 'camera_status', 'invert' => true],
                            'alarm_clock_status' => ['label' => 'alarm_clock_status', 'invert' => true],
                            'calendar_status' => ['label' => 'calendar_status', 'invert' => true],
                            'logout_status' => 'logout_status',
                            'only_install_store_app_status' => 'only_install_store_app',
                            'hide_cleanup_status' => 'hide_cleanup',
                            'hide_accelerate_status' => 'hide_accelerate',
                            'allow_change_password_status' => 'allow_change_password',
                            'enable_gesture_pwd_status' => 'enable_gesture_pwd',
                            'school_class_display_status' => 'school_class_display',
                            'enable_wifi_advanced_status' => 'enable_wifi_advanced',
                            'enable_gps_status' => 'enable_gps',
                            'enable_screenshots_status' => 'enable_screenshots',
                            'show_privacy_statement_status' => 'show_privacy_statement',
                            'enable_client_admin_status' => 'enable_client_admin',
                            'disable_reinstall_system_status' => 'disable_reinstall_system',
                            'enable_system_upgrade_status' => 'enable_system_upgrade',
                            'rotate_setting_status' => 'rotate_setting',
                        ];
                        foreach ($settingFields as $key => $field): 
                            if (is_array($field)) {
                                $label = $field['label'];
                                $invert = $field['invert'];
                                $value = !($deviceSettings[$key] ?? false);
                            } else {
                                $label = $field;
                                $invert = false;
                                $value = $deviceSettings[$key] ?? false;
                            }
                        ?>
                        <div class="setting-item">
                            <span><?php echo Lang::get($label, $lang); ?></span>
                            <label class="toggle-switch">
                                <input type="checkbox" class="policy-toggle" data-section="device_setting.<?php echo $key; ?>" <?php echo $value ? 'checked' : ''; ?> onchange="updatePolicy(this, <?php echo $invert ? 'true' : 'false'; ?>)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            
            <?php elseif ($activeTab === 'user'): ?>
                <!-- 用户配置 -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_user', $lang); ?>
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('user_name', $lang); ?></th>
                            <th><?php echo Lang::get('email', $lang); ?></th>
                            <th><?php echo Lang::get('school', $lang); ?></th>
                            <th><?php echo Lang::get('user_group', $lang); ?></th>
                            <th><?php echo Lang::get('status', $lang); ?></th>
                            <th><?php echo Lang::get('free_control', $lang); ?></th>
                            <th><?php echo Lang::get('focus', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($userData as $user): ?>
                        <tr data-id="<?php echo $user['id']; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['schoolinfo']['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['groupinfo'][0]['name'] ?? ''); ?></td>
                            <td><span class="status-badge <?php echo $user['status'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $user['status'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td><span class="status-badge <?php echo $user['free_control'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $user['free_control'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td><span class="status-badge <?php echo $user['focus'] ? 'status-enabled' : 'status-disabled'; ?>"><?php echo $user['focus'] ? Lang::get('yes', $lang) : Lang::get('no', $lang); ?></span></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editUser(<?php echo $user['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteUser(<?php echo $user['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                            <?php elseif ($activeTab === 'link'): ?>
                <!-- 链接配置 -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openLinkModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_link', $lang); ?>
                    </button>
                    <!--<button class="btn btn-success" onclick="saveLinkData()">-->
                    <!--    <span class="glyphicon glyphicon-save"></span> <?php echo Lang::get('save_link', $lang); ?>-->
                    <!--</button>-->
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('link_name', $lang); ?></th>
                            <th>URL</th>
                            <th><?php echo Lang::get('status', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="linkTableBody">
                        <?php foreach ($linkData as $link): ?>
                        <tr data-id="<?php echo $link['id']; ?>">
                            <td><?php echo $link['id']; ?></td>
                            <td><?php echo htmlspecialchars($link['name']); ?></td>
                            <td><?php echo htmlspecialchars($link['url']); ?></td>
                            <td><span class="status-badge <?php echo ($link['status'] ?? 1) ? 'status-enabled' : 'status-disabled'; ?>"><?php echo ($link['status'] ?? 1) ? Lang::get('enabled', $lang) : Lang::get('disabled', $lang); ?></span></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editLink(<?php echo $link['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteLink(<?php echo $link['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                            <?php elseif ($activeTab === 'advertise'): ?>
                <!-- 广告配置 -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openAdvertiseModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_advertise', $lang); ?>
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('name', $lang); ?></th>
                            <th><?php echo Lang::get('icon', $lang); ?></th>
                            <th><?php echo Lang::get('app_count', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="advertiseTableBody">
                        <?php foreach ($advertiseData['advertises'] as $adv): ?>
                        <tr data-id="<?php echo $adv['id']; ?>">
                            <td><?php echo $adv['id']; ?></td>
                            <td><?php echo htmlspecialchars($adv['name']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($adv['iconpath'] ?? ''); ?>" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
                            <td><?php echo count($adv['appids'] ?? []); ?></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editAdvertise(<?php echo $adv['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteAdvertise(<?php echo $adv['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($activeTab === 'launch'): ?>
    <!-- 启动配置 -->
    <div class="settings-grid">
        <div class="settings-card">
            <h4><?php echo Lang::get('launch_app_config', $lang); ?></h4>
            
            <!-- 启用开关 -->
            <div class="setting-item" style="margin-bottom: 20px;">
                <span><?php echo Lang::get('enable_launch_app', $lang); ?></span>
                <label class="toggle-switch">
                    <input type="checkbox" id="launch_app_enabled" 
                           <?php echo !empty($deviceData['device_setting']['launch_app']['launch_package']) ? 'checked' : ''; ?> 
                           onchange="toggleLaunchApp(this)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <!-- 包名输入框 -->
            <div class="setting-item" style="margin-bottom: 20px;">
                <span><?php echo Lang::get('launch_package', $lang); ?></span>
                <input type="text" id="launch_package" class="form-control" style="width: 300px;"
                       placeholder="<?php echo Lang::get('enter_package_name', $lang); ?>"
                       value="<?php echo htmlspecialchars($deviceData['device_setting']['launch_app']['launch_package'] ?? ''); ?>"
                       <?php echo empty($deviceData['device_setting']['launch_app']['launch_package']) ? 'disabled' : ''; ?>>
            </div>
            
            <!-- 启动模式下拉选择框 -->
            <div class="setting-item">
                <span><?php echo Lang::get('launch_mode', $lang); ?></span>
                <select id="launch_mode" class="form-control" style="width: 200px;"
                        <?php echo empty($deviceData['device_setting']['launch_app']['launch_package']) ? 'disabled' : ''; ?>
                        onchange="updateLaunchMode(this.value)">
                    <option value="1" <?php echo ($deviceData['device_setting']['launch_app']['launch_mode'] ?? 2) == 1 ? 'selected' : ''; ?>>
                        <?php echo Lang::get('launch_mode_1', $lang); ?> (<?php echo $lang === 'en' ? 'Normal Mode' : '普通模式'; ?>)
                    </option>
                    <option value="2" <?php echo ($deviceData['device_setting']['launch_app']['launch_mode'] ?? 2) == 2 ? 'selected' : ''; ?>>
                        <?php echo Lang::get('launch_mode_2', $lang); ?> (<?php echo $lang === 'en' ? 'Kiosk Mode' : '单应用模式'; ?>)
                    </option>
                    <option value="3" <?php echo ($deviceData['device_setting']['launch_app']['launch_mode'] ?? 2) == 3 ? 'selected' : ''; ?>>
                        <?php echo Lang::get('launch_mode_3', $lang); ?> (<?php echo $lang === 'en' ? 'Locked Mode' : '锁定模式'; ?>)
                    </option>
                </select>
            </div>
            
            <!-- 提示信息 -->
            <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 4px; font-size: 12px; color: #666;">
                <strong><?php echo $lang === 'en' ? 'Note:' : '提示：'; ?></strong><br>
                - <?php echo $lang === 'en' ? 'Enable to set a default launcher app' : '开启后可以设置默认启动器应用'; ?><br>
                - <?php echo $lang === 'en' ? 'Package name format: com.example.app' : '包名格式：com.example.app'; ?><br>
                - <?php echo $lang === 'en' ? 'Mode 1: Normal mode, Mode 2: Single app mode (kiosk), Mode 3: Locked mode' : '模式1：普通模式，模式2：单应用模式（信息亭），模式3：锁定模式'; ?>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'browser'): ?>
    <!-- 浏览器管理 - 网址收藏 -->
    <div class="action-bar">
        <button class="btn btn-primary" onclick="openBrowserModal()">
            <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_bookmark', $lang); ?>
        </button>
        <!--<button class="btn btn-success" onclick="saveAllBookmarks()">-->
        <!--    <span class="glyphicon glyphicon-save"></span> <?php echo Lang::get('save_all', $lang); ?>-->
        <!--</button>-->
        <button class="btn btn-info" onclick="exportBookmarks()">
            <span class="glyphicon glyphicon-export"></span> <?php echo Lang::get('export_bookmarks', $lang); ?>
        </button>
        <button class="btn btn-warning" onclick="importBookmarks()">
            <span class="glyphicon glyphicon-import"></span> <?php echo Lang::get('import_bookmarks', $lang); ?>
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th><?php echo Lang::get('bookmark_name', $lang); ?></th>
                    <th><?php echo Lang::get('url', $lang); ?></th>
                    <th style="width: 120px;"><?php echo Lang::get('operations', $lang); ?></th>
                </tr>
            </thead>
            <tbody id="browserTableBody">
                <!-- 动态渲染 -->
            </tbody>
        </table>
    </div>
    
    <!-- 添加/编辑书签模态框 -->
    <div id="browserModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h4 id="browserModalTitle"><?php echo Lang::get('add_bookmark', $lang); ?></h4>
            </div>
            <div class="modal-body">
                <form id="browserForm">
                    <input type="hidden" id="bookmark_index" name="index">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo Lang::get('bookmark_name', $lang); ?> <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="bookmark_name" name="name" required 
                                   placeholder="<?php echo $lang === 'en' ? 'e.g., Google' : '例如：谷歌'; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>URL <span style="color: red;">*</span></label>
                            <input type="url" class="form-control" id="bookmark_url" name="url" required 
                                   placeholder="https://www.example.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo Lang::get('test_url', $lang); ?></label>
                            <button type="button" class="btn btn-info btn-sm" onclick="testBookmarkUrl()">
                                <span class="glyphicon glyphicon-link"></span> <?php echo Lang::get('test', $lang); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal('browserModal')"><?php echo Lang::get('cancel', $lang); ?></button>
                <button class="btn btn-primary" onclick="saveBookmark()"><?php echo Lang::get('submit', $lang); ?></button>
            </div>
        </div>
    </div>
    
    <!-- 导入书签模态框 -->
    <div id="importBookmarkModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h4><?php echo Lang::get('import_bookmarks', $lang); ?></h4>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('import_method', $lang); ?></label>
                        <select id="bookmarkImportMethod" onchange="toggleBookmarkImportMethod()">
                            <option value="file"><?php echo Lang::get('upload_file', $lang); ?></option>
                            <option value="text"><?php echo Lang::get('paste_json', $lang); ?></option>
                        </select>
                    </div>
                </div>
                
                <div id="bookmarkImportFileDiv" class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('select_json_file', $lang); ?></label>
                        <input type="file" id="bookmarkImportFile" accept=".json,application/json">
                    </div>
                </div>
                
                <div id="bookmarkImportTextDiv" class="form-row" style="display: none;">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('paste_json_here', $lang); ?></label>
                        <textarea id="bookmarkImportText" class="form-control" rows="8" placeholder='[{"name":"Google","url":"https://www.google.com"}]'></textarea>
                    </div>
                </div>
                
                <div class="form-row checkbox-group">
                    <label>
                        <input type="checkbox" id="bookmarkImportMerge" checked> 
                        <?php echo Lang::get('merge_with_existing', $lang); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal('importBookmarkModal')"><?php echo Lang::get('cancel', $lang); ?></button>
                <button class="btn btn-primary" onclick="processBookmarkImport()"><?php echo Lang::get('import', $lang); ?></button>
            </div>
        </div>
    </div>
    <?php elseif ($activeTab === 'applimit'): ?>
    <!-- 应用限制配置 -->
    <div class="action-bar">
        <button class="btn btn-primary" onclick="openAppLimitModal()">
            <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_limit_group', $lang); ?>
        </button>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo Lang::get('apps', $lang); ?></th>
                <th><?php echo Lang::get('time_limit', $lang); ?></th>
                <th><?php echo Lang::get('operations', $lang); ?></th>
            </tr>
        </thead>
        <tbody id="appLimitTableBody">
            <?php foreach ($limitData as $index => $limitGroup): ?>
            <tr data-index="<?php echo $index; ?>">
                <td><?php echo $index + 1; ?></td>
                <td>
                    <?php 
                    $appNames = array_map(function($app) { 
                        return $app['name']; 
                    }, $limitGroup['app_list'] ?? []);
                    echo htmlspecialchars(implode(', ', array_slice($appNames, 0, 3)));
                    if (count($appNames) > 3) echo '...';
                    ?>
                </td>
                <td>
                    <?php 
                    $days = ['monday' => '周一', 'tuesday' => '周二', 'wednesday' => '周三', 
                            'thursday' => '周四', 'friday' => '周五', 'saturday' => '周六', 'sunday' => '周日'];
                    $hasLimit = false;
                    foreach ($limitGroup['time_limit'] ?? [] as $day => $times) {
                        if (!empty($times)) {
                            $hasLimit = true;
                            echo ($lang === 'en' ? ucfirst($day) : $days[$day]) . ': ';
                            foreach ($times as $time) {
                                echo $time['start'] . '-' . $time['end'] . ' ';
                            }
                            echo '<br>';
                        }
                    }
                    if (!$hasLimit) echo Lang::get('no_limit', $lang);
                    ?>
                </td>
                <td>
                    <button class="btn btn-info btn-xs btn-action" onclick="editAppLimit(<?php echo $index; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                    <button class="btn btn-danger btn-xs btn-action" onclick="deleteAppLimit(<?php echo $index; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif ($activeTab === 'component'): ?>
    <!-- 组件控制 -->
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button class="btn btn-primary" onclick="openComponentModal()">
                <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_component', $lang); ?>
            </button>
            <button class="btn btn-info" onclick="exportComponentData()">
                <span class="glyphicon glyphicon-export"></span> <?php echo Lang::get('export', $lang); ?>
            </button>
            <button class="btn btn-warning" onclick="importComponentData()">
                <span class="glyphicon glyphicon-import"></span> <?php echo Lang::get('import', $lang); ?>
            </button>
        </div>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="componentSearch" class="form-control" style="width: 250px;" 
                   placeholder="<?php echo Lang::get('search_component', $lang); ?>" 
                   onkeyup="searchComponent()">
            <select id="componentFilter" class="form-control" style="width: 150px;" onchange="searchComponent()">
                <option value=""><?php echo Lang::get('all_packages', $lang); ?></option>
                <?php 
                $uniquePackages = array_unique(array_column($componentData, 'package_name'));
                foreach ($uniquePackages as $pkg): 
                ?>
                <option value="<?php echo htmlspecialchars($pkg); ?>"><?php echo htmlspecialchars($pkg); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table" id="componentTable">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th><?php echo Lang::get('package_name', $lang); ?></th>
                    <th><?php echo Lang::get('component', $lang); ?></th>
                    <th style="width: 120px;"><?php echo Lang::get('operations', $lang); ?></th>
                </tr>
            </thead>
            <tbody id="componentTableBody">
                <?php foreach ($componentData as $index => $component): ?>
                <tr data-index="<?php echo $index; ?>" data-package="<?php echo htmlspecialchars($component['package_name']); ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($component['package_name']); ?></td>
                    <td><?php echo htmlspecialchars($component['component']); ?></td>
                    <td>
                        <button class="btn btn-info btn-xs btn-action" onclick="editComponent(<?php echo $index; ?>)">
                            <?php echo Lang::get('edit', $lang); ?>
                        </button>
                        <button class="btn btn-danger btn-xs btn-action" onclick="deleteComponent(<?php echo $index; ?>)">
                            <?php echo Lang::get('delete', $lang); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- 分页控件 -->
    <div class="pagination-container" style="margin-top: 20px; text-align: center;">
        <button class="btn btn-default btn-sm" onclick="changePage('prev')">
            <span class="glyphicon glyphicon-chevron-left"></span> <?php echo Lang::get('previous', $lang); ?>
        </button>
        <span id="pageInfo" style="margin: 0 15px;">1 / 1</span>
        <button class="btn btn-default btn-sm" onclick="changePage('next')">
            <?php echo Lang::get('next', $lang); ?> <span class="glyphicon glyphicon-chevron-right"></span>
        </button>
        <select id="pageSize" class="form-control" style="width: 70px; display: inline-block; margin-left: 10px;" onchange="changePageSize()">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
    <?php elseif ($activeTab === 'appgroup'): ?>
                <!-- 应用组配置 -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="openAppgroupModal()">
                        <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_appgroup', $lang); ?>
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Lang::get('name', $lang); ?></th>
                            <th><?php echo Lang::get('icon', $lang); ?></th>
                            <th><?php echo Lang::get('operations', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="appgroupTableBody">
                        <?php foreach ($appgroupData as $group): ?>
                        <tr data-id="<?php echo $group['id']; ?>">
                            <td><?php echo $group['id']; ?></td>
                            <td><?php echo htmlspecialchars($group['name']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($group['iconpath'] ?? ''); ?>" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
                            <td>
                                <button class="btn btn-info btn-xs btn-action" onclick="editAppgroup(<?php echo $group['id']; ?>)"><?php echo Lang::get('edit', $lang); ?></button>
                                <button class="btn btn-danger btn-xs btn-action" onclick="deleteAppgroup(<?php echo $group['id']; ?>)"><?php echo Lang::get('delete', $lang); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<!-- 应用商店模态框 -->
<div id="appModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="appModalTitle"><?php echo Lang::get('add_app', $lang); ?></h4>
            <!--<button class="btn btn-default btn-xs" onclick="closeModal('appModal')">&times;</button>-->
        </div>
        <div class="modal-body">
            <form id="appForm">
                <input type="hidden" id="app_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('name', $lang); ?></label>
                        <input type="text" class="form-control" id="app_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('package_name', $lang); ?></label>
                        <input type="text" class="form-control" id="app_packagename" name="packagename" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('version_name', $lang); ?></label>
                        <input type="text" class="form-control" id="app_versionname" name="versionname" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('version_code', $lang); ?></label>
                        <input type="number" class="form-control" id="app_versioncode" name="versioncode" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="position: relative;">
                     <label><?php echo Lang::get('icon', $lang); ?> URL</label>
                         <div style="display: flex; gap: 8px; align-items: center;">
                             <input type="url" class="form-control" id="app_iconpath" name="iconpath" style="flex: 1;">
                                 <input type="file" id="app_iconfile" accept="image/*" style="display: none;" onchange="uploadAppIcon(this)">
                                    <button type="button" class="btn btn-success" onclick="document.getElementById('app_iconfile').click()" title="[#]">
                                         <span class="glyphicon glyphicon-upload"></span>
                                     </button>
                                 </div>
                             <div id="app_icon_upload_status" style="font-size: 12px; margin-top: 4px; color: #666;"></div>
                        </div>
                                        <div class="form-group">
                        <label><?php echo Lang::get('download_url', $lang); ?></label>
                        <select id="link_selector" onchange="updateDownloadUrl(this)" style="margin-bottom: 8px;">
                            <option value=""><?php echo $lang === 'en' ? '-- Select Download Link --' : '-- 选择下载链接 --'; ?></option>
                            <?php foreach ($linkData as $link): 
                                $displayName = mb_substr($link['name'], 0, 12, 'UTF-8');
                                $fullName = $link['name'];
                                if (mb_strlen($link['name'], 'UTF-8') > 12) {
                                    $displayName .= '...';
                                }
                            ?>
                            <option value="<?php echo $link['id']; ?>" data-fullname="<?php echo htmlspecialchars($fullName); ?>">
                                <?php echo $link['id']; ?> - <?php echo htmlspecialchars($displayName); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="custom"><?php echo $lang === 'en' ? 'Custom URL' : '自定义URL'; ?></option>
                        </select>
                        
                        <!-- 显示已选择的链接信息（非自定义时显示） -->
                        <div id="selected_link_display" style="font-size: 13px; color: #5cb85c; margin-top: 5px; display: none;">
                            <span class="glyphicon glyphicon-ok-circle"></span>
                            <?php echo $lang === 'en' ? 'Selected: ' : '已选择: '; ?>
                            <strong id="selected_link_text"></strong>
                        </div>
                        
                        <!-- 隐藏字段存储实际URL -->
                        <input type="hidden" id="app_path" name="path">
                        
                        <!-- 自定义URL输入框（仅选择custom时显示） -->
                        <input type="url" id="custom_url_input" class="form-control" style="display: none; margin-top: 8px;" 
                               placeholder="<?php echo $lang === 'en' ? 'Enter custom URL' : '输入自定义URL'; ?>"
                               oninput="document.getElementById('app_path').value = this.value">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('size', $lang); ?> (MB)</label>
                        <input type="number" class="form-control" id="app_size" name="size" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('target_sdk', $lang); ?></label>
                        <input type="number" class="form-control" id="app_target_sdk_version" name="target_sdk_version" value="28">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('screenshots', $lang); ?> (<?php echo $lang === 'en' ? 'One URL per line' : '每行一个网址'; ?>)</label>
                        <textarea id="app_screenshots" class="form-control" name="screenshots" rows="4" placeholder="<?php echo $lang === 'en' ? 'http://example.com/screenshot1.png&#10 ;http://example.com/screenshot2.png ' : 'http://example.com/ 截图1.png&#10;http://example.com/ 截图2.png'; ?>"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('short_descript', $lang); ?> (<?php echo Lang::get('update_desc', $lang); ?>)</label>
                        <textarea id="app_shortdescript" class="form-control" name="shortdescript" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('long_descript', $lang); ?> (<?php echo Lang::get('detail', $lang); ?>)</label>
                        <textarea id="app_longdescript" class="form-control" name="longdescript" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-row checkbox-group">
                    <label><input type="checkbox" id="app_hide_icon_status" name="hide_icon_status" value="1"> <?php echo Lang::get('hidden', $lang); ?></label>
                    <label><input type="checkbox" id="app_canuninstall" name="canuninstall" value="1"> <?php echo Lang::get('can_uninstall', $lang); ?></label>
                    <label><input type="checkbox" id="app_isforce" name="isforce" value="1"> <?php echo Lang::get('is_force', $lang); ?></label>
                    <label><input type="checkbox" id="app_istrust" name="istrust" value="1" checked> <?php echo Lang::get('is_trust', $lang); ?></label>
                    </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('app_group', $lang); ?></label>
                        <select id="app_groupid" name="groupid">
                            <?php foreach ($appgroupData as $group): ?>
                            <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('is_new', $lang); ?></label>
                        <select id="app_isnew"  name="isnew">
                            <option value="1"><?php echo Lang::get('yes', $lang); ?></option>
                            <option value="0"><?php echo Lang::get('no', $lang); ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('appModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveApp()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>

<!-- 设备应用模态框 -->
<div id="deviceAppModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="deviceAppModalTitle"><?php echo Lang::get('add_app', $lang); ?></h4>
            <!--<button class="btn btn-default btn-xs" onclick="closeModal('deviceAppModal')">&times;</button>-->
        </div>
        <div class="modal-body">
            <form id="deviceAppForm">
                <input type="hidden" id="device_app_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('name', $lang); ?></label>
                        <input type="text" class="form-control" id="device_app_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('package_name', $lang); ?></label>
                        <input type="text" class="form-control" id="device_app_packagename" name="packagename" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('version_name', $lang); ?></label>
                        <input type="text" class="form-control" id="device_app_versionname" name="versionname" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('version_code', $lang); ?></label>
                        <input type="number" class="form-control" id="device_app_versioncode" name="versioncode" required>
                    </div>
                </div>
                <div class="form-row checkbox-group">
                    <label><input type="checkbox" id="device_app_hide_icon_status" name="hide_icon_status" value="1"> <?php echo Lang::get('hidden', $lang); ?></label>
                    <label><input type="checkbox" id="device_app_status" name="status" value="1" checked> <?php echo Lang::get('enabled', $lang); ?></label>
                    <label><input type="checkbox" id="device_app_canuninstall" name="canuninstall" value="1"> <?php echo Lang::get('can_uninstall', $lang); ?></label>
                    <label><input type="checkbox" id="device_app_isforce" name="isforce" value="1"> <?php echo Lang::get('is_force', $lang); ?></label>
                    <label><input type="checkbox" id="device_app_istrust" name="istrust" value="1" checked> <?php echo Lang::get('is_trust', $lang); ?></label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('deviceAppModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveDeviceApp()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>

<!-- 用户模态框 -->
<div id="userModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="userModalTitle"><?php echo Lang::get('add_user', $lang); ?></h4>
            <!--<button class="btn btn-default btn-xs" onclick="closeModal('userModal')">&times;</button>-->
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="user_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('user_name', $lang); ?></label>
                        <input type="text" class="form-control" id="user_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('email', $lang); ?></label>
                        <input type="email" class="form-control" id="user_email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('key', $lang); ?></label>
                        <input type="text" class="form-control" id="user_key" name="key" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('school', $lang); ?> ID</label>
                        <input type="number" class="form-control" id="user_school" name="school" value="776883">
                    </div>
                </div>
                <div class="form-row checkbox-group">
                    <label><input type="checkbox" id="user_status" name="status" value="1" checked> <?php echo Lang::get('status', $lang); ?></label>
                    <label><input type="checkbox" id="user_free_control" name="free_control" value="1" checked> <?php echo Lang::get('free_control', $lang); ?></label>
                    <label><input type="checkbox" id="user_focus" name="focus" value="1" checked> <?php echo Lang::get('focus', $lang); ?></label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('userModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveUser()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 链接模态框 -->
<div id="linkModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h4 id="linkModalTitle"><?php echo Lang::get('add_link', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="linkForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID</label>
                        <input type="number" class="form-control" id="link_id" name="id" step="1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('link_name', $lang); ?></label>
                        <input type="text" class="form-control" id="link_name" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>URL</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="url" id="link_url" name="url" required style="flex: 1;">
                            <input type="file" id="link_file" class="form-control" style="display: none;" onchange="uploadLinkFile(this)">
                            <button type="button" class="btn btn-success" onclick="document.getElementById('link_file').click()" title="上传文件">
                                <span class="glyphicon glyphicon-upload"></span> 上传
                            </button>
                        </div>
                        <div id="link_upload_status" style="font-size: 12px; margin-top: 4px; color: #666;"></div>
                    </div>
                </div>
                <div class="form-row checkbox-group">
                    <label><input type="checkbox" id="link_status" name="status" value="1" checked> <?php echo Lang::get('enabled', $lang); ?></label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('linkModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveLink()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 广告模态框 -->
<div id="advertiseModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="advertiseModalTitle"><?php echo Lang::get('add_advertise', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="advertiseForm">
                <input type="hidden" id="advertise_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo Lang::get('name', $lang); ?></label>
                        <input type="text" class="form-control" id="advertise_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('icon', $lang); ?> URL</label>
                        <input type="url" class="form-control" id="advertise_iconpath" name="iconpath">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('select_apps', $lang); ?></label>
                        <select id="advertise_appids" name="appids" multiple size="8" style="width: 100%;">
                            <?php foreach ($appData as $app): ?>
                            <option value="<?php echo $app['id']; ?>"><?php echo htmlspecialchars($app['name']); ?> (<?php echo htmlspecialchars($app['packagename']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small><?php echo $lang === 'en' ? 'Hold Ctrl/Cmd to select multiple' : '按住 Ctrl/Cmd 键多选'; ?></small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('advertiseModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveAdvertise()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 应用组模态框 -->
<div id="appgroupModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="appgroupModalTitle"><?php echo Lang::get('add_appgroup', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="appgroupForm">
                <input type="hidden" id="appgroup_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID</label>
                        <input type="number" class="form-control" id="appgroup_id_input" name="id_input" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo Lang::get('name', $lang); ?></label>
                        <input type="text" class="form-control" id="appgroup_name" name="name" required>
                    </div>
                </div>
                <!-- 应用组模态框中的图标部分 -->
<div class="form-row">
    <div class="form-group" style="flex: 2;">
        <label><?php echo Lang::get('icon', $lang); ?> URL</label>
        <div style="display: flex; gap: 8px; align-items: center;">
            <input type="url" class="form-control" id="appgroup_iconpath" name="iconpath" style="flex: 1;">
            <input type="file" class="form-control" id="appgroup_iconfile" accept="image/*" style="display: none;" onchange="uploadAppgroupIcon(this)">
            <button type="button" class="btn btn-success" onclick="document.getElementById('appgroup_iconfile').click()" title="上传图标">
                <span class="glyphicon glyphicon-upload"></span>
            </button>
        </div>
        <div id="appgroup_icon_upload_status" style="font-size: 12px; margin-top: 4px; color: #666;"></div>
    </div>
</div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('appgroupModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveAppgroup()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 关于模态框 -->
<div id="aboutModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4><?php echo $lang === 'en' ? 'Privacy Statement' : '隐私声明'; ?></h4>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <img src="./title.png" alt="logo" style="width: 80px; height: 80px; margin-bottom: 15px;">
                <h3 style="margin-bottom: 10px;"><?php echo Lang::get('admin_title', $lang); ?></h3>
                <p style="color: #666; margin-bottom: 20px;"><?php echo $lang === 'en' ? 'Privacy Statement' : '隐私声明'; ?></p>
                
            <div style="text-align: left; background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px; max-height: 300px; overflow-y: auto;">
                <?php 
                if (file_exists('about.html')) {
                    echo file_get_contents('about.html');
                 } else {
                    echo '<p style="color: #999; text-align: center;">' . ($lang === 'en' ? 'No content available' : '暂无内容') . '</p>';
                    }
                    ?>
                </div>
                
                <p style="font-size: 12px; color: #999; margin-top: 20px;">
                   2022-2026 © 机盟科技 <?php echo $lang === 'en' ? 'All rights reserved.' : '保留所有权利。'; ?>
                </p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeModal('aboutModal')"><?php echo $lang === 'en' ? 'OK' : '确定'; ?></button>
        </div>
    </div>
</div>
<!-- 退出登录确认模态框 -->
<div id="logoutModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4><?php echo $lang === 'en' ? 'Logout' : '退出登录'; ?></h4>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <!--<span class="glyphicon glyphicon-question-sign" style="font-size: 48px; color: #f0ad4e; margin-bottom: 15px; display: block;"></span>-->
                <p style="font-size: 16px; margin-bottom: 10px;"><?php echo $lang === 'en' ? 'Are you sure you want to logout?' : '确定要退出登录吗？'; ?></p>
                <!--<p style="color: #999; font-size: 12px;"><?php echo $lang === 'en' ? 'You will be redirected to the login page' : '您将被重定向到登录页面'; ?></p>-->
            </div>
        </div>
        <div class="modal-footer" style="">
            <button class="btn btn-default" onclick="closeModal('logoutModal')" style="margin-right: 0px;">
                <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
            </button>
            <button class="btn btn-danger" onclick="confirmLogout()">
                <?php echo $lang === 'en' ? 'logout' : '退出'; ?>
            </button>
        </div>
    </div>
</div>
<!-- 删除确认模态框 -->
<div id="deleteConfirmModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="deleteModalTitle"><?php echo $lang === 'en' ? 'Confirm Delete' : '确认删除'; ?></h4>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <p id="deleteModalMessage" style="font-size: 16px; margin-bottom: 10px;"></p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('deleteConfirmModal')">
                <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
            </button>
            <button class="btn btn-danger" id="confirmDeleteBtn">
                <?php echo $lang === 'en' ? 'Delete' : '删除'; ?>
            </button>
        </div>
    </div>
</div>
<!-- 应用限制模态框 -->
<div id="appLimitModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h4 id="appLimitModalTitle"><?php echo Lang::get('add_limit_group', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="appLimitForm">
                <input type="hidden" id="applimit_index" name="index">
                
                <!-- 应用选择 -->
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('select_apps', $lang); ?></label>
                        <select id="applimit_apps" name="apps" multiple size="6" style="width: 100%;">
                            <?php foreach ($appData as $app): ?>
                            <option value="<?php echo htmlspecialchars(json_encode(['id' => $app['id'], 'name' => $app['name'], 'packagename' => $app['packagename']])); ?>">
                                <?php echo htmlspecialchars($app['name']); ?> (<?php echo htmlspecialchars($app['packagename']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small><?php echo $lang === 'en' ? 'Hold Ctrl/Cmd to select multiple' : '按住 Ctrl/Cmd 键多选'; ?></small>
                    </div>
                </div>
                
                <!-- 时间限制设置 -->
                <div class="form-row" style="flex-direction: column;">
                    <label><?php echo Lang::get('time_limit_settings', $lang); ?></label>
                    <?php 
                    $days = [
                        'monday' => $lang === 'en' ? 'Monday' : '周一',
                        'tuesday' => $lang === 'en' ? 'Tuesday' : '周二',
                        'wednesday' => $lang === 'en' ? 'Wednesday' : '周三',
                        'thursday' => $lang === 'en' ? 'Thursday' : '周四',
                        'friday' => $lang === 'en' ? 'Friday' : '周五',
                        'saturday' => $lang === 'en' ? 'Saturday' : '周六',
                        'sunday' => $lang === 'en' ? 'Sunday' : '周日'
                    ];
                    foreach ($days as $key => $label): 
                    ?>
                    <div class="setting-item" style="margin: 5px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <strong><?php echo $label; ?></strong>
                            <div class="applimit-time-slots" data-day="<?php echo $key; ?>">
                                <!-- 时间段将动态添加到这里 -->
                            </div>
                            <button type="button" class="btn btn-success btn-xs" onclick="addTimeSlot('<?php echo $key; ?>')">
                                <span class="glyphicon glyphicon-plus"></span> <?php echo Lang::get('add_time', $lang); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('appLimitModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveAppLimit()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 组件模态框 -->
<div id="componentModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h4 id="componentModalTitle"><?php echo Lang::get('add_component', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="componentForm">
                <input type="hidden" id="component_index" name="index">
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('package_name', $lang); ?> <span style="color: red;">*</span></label>
                        <input type="text" id="component_package_name" name="package_name" class="form-control" required 
                               placeholder="例如: com.android.settings">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('component', $lang); ?> <span style="color: red;">*</span></label>
                        <input type="text" id="component_component" name="component" class="form-control" required 
                               placeholder="例如: com.android.settings.SettingsActivity">
                        <small style="color: #666;"><?php echo Lang::get('component_help', $lang); ?></small>
                    </div>
                </div>
<div class="form-row">
    <div class="form-group" style="flex: 2;">
        <label><?php echo Lang::get('select_from_apps', $lang); ?></label>
        <select id="appSelectComponent" onchange="selectAppForComponent(this)">
            <option value="">-- <?php echo Lang::get('select_app_to_fill_package', $lang); ?> --</option>
            <?php foreach ($appData as $app): ?>
            <option value="<?php echo htmlspecialchars($app['packagename']); ?>" data-name="<?php echo htmlspecialchars($app['name']); ?>">
                <?php echo htmlspecialchars($app['name']); ?> (<?php echo htmlspecialchars($app['packagename']); ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <small style="color: #666; display: block; margin-top: 5px;">
            <?php echo Lang::get('select_app_help', $lang); ?>
        </small>
    </div>
</div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('componentModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="saveComponent()"><?php echo Lang::get('submit', $lang); ?></button>
        </div>
    </div>
</div>

<!-- 导入组件模态框 -->
<div id="importComponentModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h4><?php echo Lang::get('import_components', $lang); ?></h4>
        </div>
        <div class="modal-body">
            <form id="importComponentForm">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('import_method', $lang); ?></label>
                        <select id="importMethod" onchange="toggleImportMethod()">
                            <option value="file"><?php echo Lang::get('upload_file', $lang); ?></option>
                            <option value="text"><?php echo Lang::get('paste_json', $lang); ?></option>
                        </select>
                    </div>
                </div>
                
                <div id="importFileDiv" class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('select_json_file', $lang); ?></label>
                        <input type="file" id="importFile" accept=".json,application/json">
                    </div>
                </div>
                
                <div id="importTextDiv" class="form-row" style="display: none;">
                    <div class="form-group" style="flex: 2;">
                        <label><?php echo Lang::get('paste_json_here', $lang); ?></label>
                        <textarea id="importText" class="form-control" rows="8" placeholder='[{"package_name":"com.android.settings","component":"com.android.settings.Settings"}]'></textarea>
                    </div>
                </div>
                
                <div class="form-row checkbox-group">
                    <label>
                        <input type="checkbox" id="importMerge" checked> 
                        <?php echo Lang::get('merge_with_existing', $lang); ?>
                    </label>
                    <small style="color: #666; display: block;"><?php echo Lang::get('merge_help', $lang); ?></small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal('importComponentModal')"><?php echo Lang::get('cancel', $lang); ?></button>
            <button class="btn btn-primary" onclick="importComponents()"><?php echo Lang::get('import', $lang); ?></button>
        </div>
    </div>
</div>
<!-- 关于模态框 -->
<div id="AnnouncementModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4><?php echo $lang === 'en' ? 'Platform Announcement' : '平台公告'; ?></h4>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <!--<img src="./title.png" alt="logo" style="width: 80px; height: 80px; margin-bottom: 15px;">-->
                <!--<h3 style="margin-bottom: 10px;"><?php echo Lang::get('admin_title', $lang); ?></h3>-->
                <!--<p style="color: #666; margin-bottom: 20px;"><?php echo $lang === 'en' ? 'Platform Announcement' : '平台公告'; ?></p>-->
                
            <div style="text-align: left; background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px; max-height: 300px; overflow-y: auto;">
                <?php 
                if (file_exists('anu.html')) {
                    echo file_get_contents('anu.html');
                 } else {
                    echo '<p style="color: #999; text-align: center;">' . ($lang === 'en' ? 'No content available' : '暂无内容') . '</p>';
                    }
                    ?>
                </div>
                
                <!--<p style="font-size: 12px; color: #999; margin-top: 20px;">-->
                <!--   2022-2026 © 机盟科技 <?php echo $lang === 'en' ? 'All rights reserved.' : '保留所有权利。'; ?>-->
                <!--</p>-->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeModal('AnnouncementModal')"><?php echo $lang === 'en' ? 'OK' : '确定'; ?></button>
        </div>
    </div>
</div>
<div id="uploadProgressModal" class="modal-overlay" style="z-index: 9999;">
    <div class="modal-content" style="max-width: 500px; text-align: center;">
        <div class="modal-header">
            <h4><?php echo $lang === 'en' ? 'Uploading File...' : '正在上传文件...'; ?></h4>
        </div>
        <div class="modal-body" style="padding: 30px;">
            <!-- 文件信息 -->
            <div style="margin-bottom: 20px; word-break: break-all;">
                <div id="progressFilename" style="font-weight: bold; color: #333; margin-bottom: 5px;"></div>
                <div id="progressFileSize" style="color: #666; font-size: 12px;"></div>
            </div>
            
            <!-- 进度条容器 -->
            <div style="background: #f0f0f0; border-radius: 10px; height: 20px; overflow: hidden; margin: 20px 0; position: relative; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                <div id="progressBar" style="background: linear-gradient(90deg, #5cb85c 0%, #4cae4c 100%); height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 10px; position: relative; overflow: hidden;">
                    <!-- 进度条动画效果 -->
                    <div style="position: absolute; top: 0; left: 0; bottom: 0; right: 0; background: linear-gradient(45deg, rgba(255,255,255,0.2) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.2) 75%, transparent 75%, transparent); background-size: 20px 20px; animation: progressMove 1s linear infinite;"></div>
                </div>
            </div>
            
            <!-- 进度百分比和速度 -->
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #666;">
                <span id="progressPercent">0%</span>
                <span id="progressSpeed">0 MB/s</span>
            </div>
            
            <!-- 状态信息 -->
            <div id="progressStatus" style="margin-top: 15px; font-size: 13px; color: #999;">
                <?php echo $lang === 'en' ? 'Preparing upload...' : '准备上传...'; ?>
            </div>
            
            <!-- 取消按钮 -->
            <button type="button" class="btn btn-default" onclick="cancelUpload()" style="margin-top: 20px; padding: 6px 20px;">
                <span class="glyphicon glyphicon-remove"></span> <?php echo $lang === 'en' ? 'Cancel' : '取消'; ?>
            </button>
        </div>
    </div>
</div>
<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-dialog.min.js"></script>
<script src="js/jquery.cookie.js"></script>
<script>
// const { ipcRenderer } = require('electron');
const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
const LANG = '<?php echo $lang; ?>';

// 打开关于模态框
function openAboutModal() {
    openModal('aboutModal');
}

// 打开退出登录确认模态框
function openLogoutModal() {
    openModal('logoutModal');
}

// 确认退出登录
function confirmLogout() {
    window.location.href = 'logout.php';
}
function openAnnouncementModal() {
    openModal('AnnouncementModal');
}

// 打开删除确认模态框
function openDeleteModal(message, callback) {
    document.getElementById('deleteModalMessage').textContent = message;
    deleteCallback = callback;
    openModal('deleteConfirmModal');
}

// 确认删除
function confirmDelete() {
    closeModal('deleteConfirmModal');
    if (deleteCallback) {
        deleteCallback();
        deleteCallback = null;
    }
}
// 数据缓存（工作副本）
let appData = JSON.parse(JSON.stringify(<?php echo json_encode($appData); ?>));
let deviceData = JSON.parse(JSON.stringify(<?php echo json_encode($deviceData); ?>));
let userData = JSON.parse(JSON.stringify(<?php echo json_encode($userData); ?>));
let linkData = JSON.parse(JSON.stringify(<?php echo json_encode($linkData); ?>));
let advertiseData = <?php echo json_encode($advertiseData); ?>;
let appgroupData = <?php echo json_encode($appgroupData); ?>;
let limitData = JSON.parse(JSON.stringify(<?php echo json_encode($limitData); ?>));
let deleteCallback = null;
let currentUploadXHR = null;  // 用于取消上传
let uploadStartTime = 0;    // 计算上传速度

// 原始数据（用于比较是否修改）
const originalAppData = JSON.parse(JSON.stringify(appData));
const originalDeviceData = JSON.parse(JSON.stringify(deviceData));
const originalUserData = JSON.parse(JSON.stringify(userData));
const originalLinkData = JSON.parse(JSON.stringify(linkData));
const originalAdvertiseData = JSON.parse(JSON.stringify(advertiseData));
const originalAppgroupData = JSON.parse(JSON.stringify(appgroupData));
const originalLimitData = JSON.parse(JSON.stringify(limitData));

// 标记是否有未保存的更改
let hasUnsavedChanges = false;

// 工具函数

// 绑定确认删除按钮事件
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmDelete);
    }
});

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    // 清空表单
    const form = document.getElementById(id.replace('Modal', 'Form'));
    if (form) form.reset();
}

function showAlert(message, type = 'success') {
    alert(message);
}

// 获取当前 URL 参数
function getCurrentUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return params.toString() ? '?' + params.toString() : '';
}

// 标记有未保存的更改
function markAsUnsaved() {
    hasUnsavedChanges = true;
    const saveBtn = document.querySelector('.sidebar-footer .btn-primary');
    if (saveBtn) {
        saveBtn.classList.add('unsaved');
        saveBtn.innerHTML = '<span class="glyphicon glyphicon-save"></span> <?php echo Lang::get('save', $lang); ?> *';
        saveBtn.style.background = '#f0ad4e';
        saveBtn.style.borderColor = '#f0ad4e';
    }
}

// 重置保存按钮状态
function resetSaveButton() {
    hasUnsavedChanges = false;
    const saveBtn = document.querySelector('.sidebar-footer .btn-primary');
    if (saveBtn) {
        saveBtn.classList.remove('unsaved');
        saveBtn.innerHTML = '<span class="glyphicon glyphicon-save"></span> <?php echo Lang::get('save', $lang); ?>';
        saveBtn.style.background = '';
        saveBtn.style.borderColor = '';
    }
}

// 重新渲染策略开关（用于刷新后更新策略页面的开关状态）
function renderPolicySwitches() {
    // 更新设备管理策略开关
    const deviceManage = deviceData.device_tactics?.deviceManage || {};
    document.querySelectorAll('[data-section^="device_tactics.deviceManage"]').forEach(el => {
        const key = el.dataset.section.split('.').pop();
        const value = deviceManage[key] || false;
        el.checked = value;
    });
    
    // 更新违规策略开关
    const illegalTactics = deviceData.illegal_tactics || {};
    ['usb_to_pc', 'already_root', 'change_simcard', 'prohibited_app'].forEach(tactic => {
        const tacticData = illegalTactics[tactic] || {};
        ['enable', 'notify_admin', 'eliminate_data', 'lock_workspace'].forEach(field => {
            const el = document.querySelector(`[data-section="illegal_tactics.${tactic}.${field}"]`);
            if (el) {
                el.checked = tacticData[field] || false;
            }
        });
    });
    
    // 更新设备设置开关
    const deviceSetting = deviceData.device_setting || {};
    const invertFields = ['gallery_status', 'camera_status', 'alarm_clock_status', 'calendar_status'];
    const launchApp = deviceData.device_setting?.launch_app || {};
    const enabledCheckbox = document.getElementById('launch_app_enabled');
    const packageInput = document.getElementById('launch_package');
    const modeSelect = document.getElementById('launch_mode');
    if (enabledCheckbox) {
        const isEnabled = launchApp.launch_package !== null && launchApp.launch_package !== '';
        enabledCheckbox.checked = isEnabled;
        
        if (packageInput) {
            packageInput.value = launchApp.launch_package || '';
            packageInput.disabled = !isEnabled;
        }
        if (modeSelect) {
            modeSelect.value = launchApp.launch_mode || 2;
            modeSelect.disabled = !isEnabled;
        }
    }
    
    document.querySelectorAll('[data-section^="device_setting."]').forEach(el => {
        const key = el.dataset.section.split('.').pop();
        let value = deviceSetting[key] || false;
        // 对于反转字段（0为开启，1为禁用），需要反转显示
        if (invertFields.includes(key)) {
            value = !value;
        }
        el.checked = value;
    });
}

// 刷新数据（重新加载页面）
// 刷新数据（从服务器重新获取）
function refreshData() {
    if (hasUnsavedChanges) {
        const confirmMsg = LANG === 'en' ? 'You have unsaved changes. Refresh anyway?' : '您有未保存的更改，确定要刷新吗？';
        if (!confirm(confirmMsg)) return;
    }
    
    // 重要：在刷新前保存当前书签数据到临时变量
    const currentBookmarkData = bookmarkData && bookmarkData.length > 0 ? JSON.parse(JSON.stringify(bookmarkData)) : null;
    
    // 重置未保存标记
    hasUnsavedChanges = false;
    resetSaveButton();
    
    // 从服务器获取最新数据
    $.ajax({
        url: 'api.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'get_all',
            token: CSRF_TOKEN
        }),
        contentType: 'application/json',
        success: function(res) {
            if (res.success) {
                // 更新本地数据
                if (res.app) appData = res.app;
                if (res.device) deviceData = res.device;
                if (res.user) userData = res.user;
                if (res.link) linkData = res.link;
                if (res.advertise) advertiseData = res.advertise;
                if (res.appgroup) appgroupData = res.appgroup;
                if (res.limit) limitData = res.limit;
                
                // 关键修复：处理书签数据
                if (res.bookmarks !== undefined && res.bookmarks !== null) {
                    // 如果服务器返回了书签数据，使用服务器的
                    bookmarkData = Array.isArray(res.bookmarks) ? res.bookmarks : [];
                } else if (currentBookmarkData && currentBookmarkData.length > 0) {
                    // 如果服务器没有返回书签数据但本地有，保留本地的
                    console.warn('Server did not return bookmarks, keeping local data');
                    bookmarkData = currentBookmarkData;
                } else {
                    bookmarkData = [];
                }
                originalBookmarkData = JSON.parse(JSON.stringify(bookmarkData));
                
                // 更新原始数据副本
                originalAppData = JSON.parse(JSON.stringify(appData));
                originalDeviceData = JSON.parse(JSON.stringify(deviceData));
                originalUserData = JSON.parse(JSON.stringify(userData));
                originalLinkData = JSON.parse(JSON.stringify(linkData));
                originalAdvertiseData = JSON.parse(JSON.stringify(advertiseData));
                originalAppgroupData = JSON.parse(JSON.stringify(appgroupData));
                originalLimitData = JSON.parse(JSON.stringify(limitData));
                
                // 重新渲染所有表格
                renderAppTable();
                renderDeviceAppTable();
                renderUserTable();
                renderLinkTable();
                renderAdvertiseTable();
                renderAppgroupTable();
                renderAppLimitTable();
                // 重新渲染书签表格（如果存在）
                if (document.getElementById('browserTableBody')) {
                    renderBookmarkTable();
                }
                
                // 如果是策略页面，重新渲染策略开关
                renderPolicySwitches();
                
                showAlert(LANG === 'en' ? 'Refresh successful' : '刷新成功');
            } else {
                showAlert(LANG === 'en' ? 'Refresh failed: ' : '刷新失败：' + res.message, 'error');
                // 恢复原始数据
                location.reload();
            }
        },
        error: function() {
            showAlert(LANG === 'en' ? 'Refresh failed' : '刷新失败', 'error');
            location.reload();
        }
    });
}

// ========== 应用限制管理 ==========
function openAppLimitModal(editIndex = null) {
    document.getElementById('appLimitModalTitle').textContent = editIndex !== null ? 
        (LANG === 'en' ? 'Edit Limit Group' : '编辑限制组') : 
        (LANG === 'en' ? 'Add Limit Group' : '添加限制组');
    
    // 清空之前的时间段
    document.querySelectorAll('.applimit-time-slots').forEach(el => el.innerHTML = '');
    
    if (editIndex !== null) {
        const group = limitData[editIndex];
        document.getElementById('applimit_index').value = editIndex;
        
        // 设置选中的应用
        const appSelect = document.getElementById('applimit_apps');
        const selectedPackages = (group.app_list || []).map(app => app.packagename);
        Array.from(appSelect.options).forEach(opt => {
            try {
                const appData = JSON.parse(opt.value);
                opt.selected = selectedPackages.includes(appData.packagename);
            } catch (e) {
                console.error('Error parsing app data:', e);
                opt.selected = false;
            }
        });
        
        // 设置时间限制
        for (const [day, times] of Object.entries(group.time_limit || {})) {
            const container = document.querySelector(`.applimit-time-slots[data-day="${day}"]`);
            times.forEach(time => {
                addTimeSlot(day, time.start, time.end);
            });
        }
    } else {
        document.getElementById('applimit_index').value = '';
        // 重置表单
        const limitForm = document.getElementById('applimitForm');
        if (limitForm) {
            limitForm.reset();
        }
    }
    
    openModal('appLimitModal');
}

function addTimeSlot(day, start = '08:00', end = '22:00') {
    const container = document.querySelector(`.applimit-time-slots[data-day="${day}"]`);
    const slotDiv = document.createElement('div');
    slotDiv.className = 'time-slot';
    slotDiv.style.cssText = 'display: inline-flex; align-items: center; gap: 5px; margin: 2px; padding: 5px; background: white; border-radius: 3px;';
    slotDiv.innerHTML = `
        <input type="time" class="time-start" value="${start}" style="width: 80px;">
        <span>-</span>
        <input type="time" class="time-end" value="${end}" style="width: 80px;">
        <button type="button" class="btn btn-danger btn-xs" onclick="this.parentElement.remove()">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    `;
    container.appendChild(slotDiv);
}

function saveAppLimit() {
    const index = document.getElementById('applimit_index').value;
    
    // 获取选中的应用
    const appSelect = document.getElementById('applimit_apps');
    const selectedApps = Array.from(appSelect.selectedOptions).map(opt => JSON.parse(opt.value));
    
    // 获取时间限制
    const timeLimit = {};
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    days.forEach(day => {
        const container = document.querySelector(`.applimit-time-slots[data-day="${day}"]`);
        const slots = [];
        container.querySelectorAll('.time-slot').forEach(slot => {
            const start = slot.querySelector('.time-start').value;
            const end = slot.querySelector('.time-end').value;
            if (start && end) {
                slots.push({ start: start + ':00', end: end + ':00' });
            }
        });
        timeLimit[day] = slots;
    });
    
    const group = {
        callsms: [],
        app_list: selectedApps,
        time_limit: timeLimit
    };
    
    if (index !== '') {
        limitData[parseInt(index)] = group;
    } else {
        limitData.push(group);
    }
    
    closeModal('appLimitModal');
    markAsUnsaved();
    renderAppLimitTable();
}

function editAppLimit(index) {
    openAppLimitModal(index);
}

function deleteAppLimit(index) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this limit group?' : '确定要删除此限制组吗？';
    openDeleteModal(message, function() {
        limitData.splice(index, 1);
        markAsUnsaved();
        renderAppLimitTable();
    });
}

function renderAppLimitTable() {
    const tbody = document.getElementById('appLimitTableBody');
    if (!tbody) return;
    
    const days = {
        'monday': LANG === 'en' ? 'Mon' : '周一',
        'tuesday': LANG === 'en' ? 'Tue' : '周二',
        'wednesday': LANG === 'en' ? 'Wed' : '周三',
        'thursday': LANG === 'en' ? 'Thu' : '周四',
        'friday': LANG === 'en' ? 'Fri' : '周五',
        'saturday': LANG === 'en' ? 'Sat' : '周六',
        'sunday': LANG === 'en' ? 'Sun' : '周日'
    };
    
    tbody.innerHTML = limitData.map((group, index) => {
        const appNames = (group.app_list || []).map(app => app.name);
        const appDisplay = appNames.slice(0, 3).join(', ') + (appNames.length > 3 ? '...' : '');
        
        let timeDisplay = '';
        let hasLimit = false;
        for (const [day, times] of Object.entries(group.time_limit || {})) {
            if (times.length > 0) {
                hasLimit = true;
                timeDisplay += days[day] + ': ';
                timeDisplay += times.map(t => t.start.substring(0, 5) + '-' + t.end.substring(0, 5)).join(', ');
                timeDisplay += '<br>';
            }
        }
        if (!hasLimit) timeDisplay = LANG === 'en' ? 'No limit' : '无限制';
        
        return `
            <tr data-index="${index}">
                <td>${index + 1}</td>
                <td>${escapeHtml(appDisplay)}</td>
                <td>${timeDisplay}</td>
                <td>
                    <button class="btn btn-info btn-xs btn-action" onclick="editAppLimit(${index})"><?php echo Lang::get('edit', $lang); ?></button>
                    <button class="btn btn-danger btn-xs btn-action" onclick="deleteAppLimit(${index})"><?php echo Lang::get('delete', $lang); ?></button>
                </td>
            </tr>
        `;
    }).join('');
}

//保存所有数据到api.php
function saveAllData() {
    if (!hasUnsavedChanges) {
        // showAlert(LANG === 'en' ? 'No changes to save' : '没有需要保存的更改');
        return;
    }
    
    const advertisePayload = {
        total: advertiseData.advertises ? advertiseData.advertises.length : 0,
        advertises: advertiseData.advertises || []
    };
    
    // 关键修复：确保 bookmarkData 不是空的
    // 如果 bookmarkData 为空但原始数据有值，使用原始数据
    let bookmarksToSave = bookmarkData;
    if ((!bookmarkData || bookmarkData.length === 0) && originalBookmarkData && originalBookmarkData.length > 0) {
        console.warn('bookmarkData is empty but originalBookmarkData has data, using original');
        bookmarksToSave = originalBookmarkData;
    }
    
    const data = {
        action: 'save_all',
        token: CSRF_TOKEN,
        app: appData,
        device: deviceData,
        user: userData,
        advertise: advertiseData,
        appgroup: appgroupData,
        link: linkData,
        limit: limitData,
        bookmarks: bookmarksToSave  // 使用修复后的书签数据
    };
    
    $.ajax({
        url: 'api.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(res) {
            if (res.success) {
                // 更新原始数据
                Object.assign(originalAppData, JSON.parse(JSON.stringify(appData)));
                Object.assign(originalDeviceData, JSON.parse(JSON.stringify(deviceData)));
                Object.assign(originalUserData, JSON.parse(JSON.stringify(userData)));
                Object.assign(originalAdvertiseData, JSON.parse(JSON.stringify(advertiseData)));
                Object.assign(originalAppgroupData, JSON.parse(JSON.stringify(appgroupData)));
                Object.assign(originalLinkData, JSON.parse(JSON.stringify(linkData)));
                Object.assign(originalLimitData, JSON.parse(JSON.stringify(limitData)));
                // 更新书签原始数据为实际保存的数据
                Object.assign(originalBookmarkData, JSON.parse(JSON.stringify(bookmarksToSave)));
                resetSaveButton();
                // 延迟2秒后刷新
                setTimeout(function() {
                    refreshData();
                }, 2000);
            } else {
                showAlert('<?php echo Lang::get('save_failed', $lang); ?>: ' + res.message, 'error');
            }
        },
        error: function() {
            showAlert('<?php echo Lang::get('save_failed', $lang); ?>', 'error');
        }
    });
}

// ========== 应用商店管理 ==========
function openAppModal(editId = null) {
    document.getElementById('appModalTitle').textContent = editId ? '<?php echo Lang::get('edit_app', $lang); ?>' : '<?php echo Lang::get('add_app', $lang); ?>';
    
    refreshAppgroupSelect();
    
    // 重置链接选择器到初始状态
    const linkSelector = document.getElementById('link_selector');
    const displayDiv = document.getElementById('selected_link_display');
    const displayText = document.getElementById('selected_link_text');
    const customInput = document.getElementById('custom_url_input');
    const pathInput = document.getElementById('app_path');
    
    // 重置所有链接相关UI
    linkSelector.value = '';
    displayDiv.style.display = 'none';
    customInput.style.display = 'none';
    customInput.value = '';
    pathInput.value = '';
    
    if (editId) {
        const app = appData.find(a => a.id == editId);
        if (app) {
            document.getElementById('app_id').value = app.id;
            document.getElementById('app_name').value = app.name;
            document.getElementById('app_packagename').value = app.packagename;
            document.getElementById('app_versionname').value = app.versionname;
            document.getElementById('app_versioncode').value = app.versioncode;
            document.getElementById('app_iconpath').value = app.iconpath || '';
            document.getElementById('app_shortdescript').value = app.shortdescript || '';
            document.getElementById('app_longdescript').value = app.longdescript || '';
            const sizeMB = app.size ? (app.size / 1024 / 1024).toFixed(2) : 0;
            document.getElementById('app_size').value = sizeMB;
            document.getElementById('app_target_sdk_version').value = app.target_sdk_version || 28;
            document.getElementById('app_hide_icon_status').checked = app.hide_icon_status == 1;
            document.getElementById('app_canuninstall').checked = app.canuninstall;
            document.getElementById('app_isforce').checked = app.isforce;
            document.getElementById('app_istrust').checked = app.is_trust;
            document.getElementById('app_groupid').value = app.groupid || 1;
            document.getElementById('app_isnew').value = app.isnew ? '1' : '0';
            const screenshotsText = (app.screenshot || []).join('\n');
            document.getElementById('app_screenshots').value = screenshotsText;
            
            // 设置链接选择器状态
            if (app.path && app.path.includes('download.php?id=')) {
                const match = app.path.match(/download\.php\?id=(\d+)&/);
                if (match) {
                    const id = match[1];
                    let found = false;
                    // 遍历选项查找匹配的ID
                    for (let i = 0; i < linkSelector.options.length; i++) {
                        if (linkSelector.options[i].value === id) {
                            linkSelector.selectedIndex = i;
                            const fullName = linkSelector.options[i].getAttribute('data-fullname');
                            const shortName = fullName.length > 12 ? fullName.substring(0, 12) + '...' : fullName;
                            displayText.textContent = id + ' - ' + shortName;
                            displayDiv.style.display = 'block';
                            customInput.style.display = 'none';
                            // 设置 path 值
                            pathInput.value = app.path;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        // 如果没找到匹配的链接，尝试作为自定义URL处理
                        linkSelector.value = 'custom';
                        customInput.value = app.path;
                        customInput.style.display = 'block';
                        displayDiv.style.display = 'none';
                        pathInput.value = app.path;
                    }
                } else {
                    // 无法匹配，作为自定义URL处理
                    linkSelector.value = 'custom';
                    customInput.value = app.path;
                    customInput.style.display = 'block';
                    displayDiv.style.display = 'none';
                    pathInput.value = app.path;
                }
            } else if (app.path && app.path.trim() !== '') {
                // 有path但不是download.php格式，作为自定义URL
                linkSelector.value = 'custom';
                customInput.value = app.path;
                customInput.style.display = 'block';
                displayDiv.style.display = 'none';
                pathInput.value = app.path;
            }
            // 如果 app.path 为空，保持重置后的状态
        }
    } else {
        // 新增模式，清空所有字段
        document.getElementById('app_id').value = '';
        document.getElementById('appForm').reset();
        document.getElementById('app_screenshots').value = '';
        // 链接相关已在上方重置
    }
    
    openModal('appModal');
}

// 确保 editApp 在全局作用域中
function editApp(id) {
    openAppModal(id);
}

function saveApp() {
    const id = document.getElementById('app_id').value;
    const now = new Date().toISOString().slice(0, 8).replace('T', ' ');
    
    const screenshotsText = document.getElementById('app_screenshots').value || '';
    const screenshots = screenshotsText
        .split('\n')
        .map(url => url.trim())
        .filter(url => url.length > 0);
    
    const sizeMB = parseFloat(document.getElementById('app_size').value) || 0;
    const sizeBytes = Math.round(sizeMB * 1024 * 1024);
    
    const generateId = () => {
        const firstDigit = Math.floor(Math.random() * 9) + 1;
        const remainingDigits = Math.floor(Math.random() * 10000000);
        return firstDigit * 10000000 + remainingDigits;
    };
    //写入数据
    const app = {
        id: id ? parseInt(id) : generateId(),
        name: document.getElementById('app_name').value,
        packagename: document.getElementById('app_packagename').value,
        versionname: document.getElementById('app_versionname').value,
        versioncode: parseInt(document.getElementById('app_versioncode').value),
        iconpath: document.getElementById('app_iconpath').value,
        path: document.getElementById('app_path').value,
        shortdescript: document.getElementById('app_shortdescript').value,
        longdescript: document.getElementById('app_longdescript').value,
        size: sizeBytes,
        md5sum: "b83361e8e22ad701c41de48c80bd852f",
        sha1: "90:6D:D9:56:41:47:68:AC:72:A7:89:31:B2:C0:98:96:59:88:D1:BC",
        target_sdk_version: parseInt(document.getElementById('app_target_sdk_version').value) || 28,
        hide_icon_status: document.getElementById('app_hide_icon_status').checked ? 1 : 0,
        canuninstall: document.getElementById('app_canuninstall').checked,
        isforce: document.getElementById('app_isforce').checked,
        is_trust: document.getElementById('app_istrust').checked,
        grant_type: 5,
        grant_to: 776883,
        groupid: parseInt(document.getElementById('app_groupid').value) || 1,
        author: null,
        type: 1,
        status: 1,
        appcatalog: false,
        isnew: document.getElementById('app_isnew').value === '1',
        creator: 1,
        exception_white_url: 0,
        devicetype: "HEY-W09",
        downloadcount: 0,
        sortweight: 0,
        rating: "5.0",
        star_percent_1: 0,
        star_percent_2: 0,
        star_percent_3: 0,
        star_percent_4: 0,
        star_percent_5: 100,
        forcetime: null,
        created_at: id ? (appData.find(a => a.id == id)?.created_at || now) : now,
        updated_at: now,
        screenshot: screenshots,
        totalcomment: id ? (appData.find(a => a.id == id)?.totalcomment || 0) : 0,
        clear_app_cache_status: 1
    };
    
    if (id) {
        const index = appData.findIndex(a => a.id == id);
        if (index > -1) {
            app.created_at = appData[index].created_at;
            appData[index] = app;
        }
    } else {
        appData.push(app);
    }
    
    closeModal('appModal');
    markAsUnsaved();
    renderAppTable();
}

function deleteApp(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this app?' : '确定要删除此应用吗？';
    openDeleteModal(message, function() {
        appData = appData.filter(a => a.id != id);
        markAsUnsaved();
        renderAppTable();
    });
}

function renderAppTable() {
    const tbody = document.getElementById('appTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = appData.map(app => `
        <tr data-id="${app.id}">
            <td>${app.id}</td>
            <td><img src="${app.iconpath || ''}" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
            <td>${escapeHtml(app.name)}</td>
            <td>${escapeHtml(app.packagename)}</td>
            <td>${escapeHtml(app.versionname)}</td>
            <td>${app.versioncode}</td>
            <td><span class="status-badge ${app.hide_icon_status ? 'status-enabled' : 'status-disabled'}">${app.hide_icon_status ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td><span class="status-badge ${app.canuninstall ? 'status-enabled' : 'status-disabled'}">${app.canuninstall ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td><span class="status-badge ${app.isforce ? 'status-enabled' : 'status-disabled'}">${app.isforce ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editApp(${app.id})"><?php echo Lang::get('edit', $lang); ?></button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteApp(${app.id})"><?php echo Lang::get('delete', $lang); ?></button>
            </td>
        </tr>
    `).join('');
}

// 上传应用图标
function uploadAppIcon(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const statusDiv = document.getElementById('app_icon_upload_status');
    
    // 验证文件类型
    if (!file.type.startsWith('image/')) {
        statusDiv.textContent = LANG === 'en' ? 'Please select an image file' : '请选择图片文件';
        statusDiv.style.color = '#d9534f';
        return;
    }
    
    // 验证文件大小
    if (file.size > 5 * 1024 * 1024) {
        statusDiv.textContent = LANG === 'en' ? 'File size must be less than 5MB' : '文件大小不能超过5MB';
        statusDiv.style.color = '#d9534f';
        return;
    }
    
    const formData = new FormData();
    formData.append('icon', file);
    formData.append('token', CSRF_TOKEN);
    formData.append('action', 'upload_icon');  
    
    statusDiv.textContent = LANG === 'en' ? 'Uploading...' : '上传中...';
    statusDiv.style.color = '#f0ad4e';
    
    fetch('api.php', { 
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('app_iconpath').value = data.url;
            statusDiv.textContent = LANG === 'en' ? 'Upload successful!' : '上传成功！';
            statusDiv.style.color = '#5cb85c';
            setTimeout(() => {
                statusDiv.textContent = '';
            }, 3000);
        } else {
            statusDiv.textContent = (LANG === 'en' ? 'Upload failed: ' : '上传失败：') + (data.message || '');
            statusDiv.style.color = '#d9534f';
        }
    })
    .catch(err => {
        statusDiv.textContent = LANG === 'en' ? 'Upload failed' : '上传失败';
        statusDiv.style.color = '#d9534f';
    });
}

// ========== 设备应用管理 ==========
function openDeviceAppModal(editId = null) {
    document.getElementById('deviceAppModalTitle').textContent = editId ? '<?php echo Lang::get('edit_app', $lang); ?>' : '<?php echo Lang::get('add_app', $lang); ?>';
    
    if (editId) {
        const app = deviceData.app_tactics.applist.find(a => a.id == editId);
        if (app) {
            document.getElementById('device_app_id').value = app.id;
            document.getElementById('device_app_name').value = app.name;
            document.getElementById('device_app_packagename').value = app.packagename;
            document.getElementById('device_app_versionname').value = app.versionname;
            document.getElementById('device_app_versioncode').value = app.versioncode;
            document.getElementById('device_app_hide_icon_status').checked = app.hide_icon_status == 1;
            document.getElementById('device_app_status').checked = app.status == 1;
            document.getElementById('device_app_canuninstall').checked = app.canuninstall;
            document.getElementById('device_app_isforce').checked = app.isforce;
            document.getElementById('device_app_istrust').checked = app.is_trust;
        }
    } else {
        document.getElementById('device_app_id').value = '';
    }
    
    openModal('deviceAppModal');
}

function editDeviceApp(id) {
    openDeviceAppModal(id);
}

function saveDeviceApp() {
    const id = document.getElementById('device_app_id').value;
    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    const app = {
        id: id ? parseInt(id) : Date.now(),
        name: document.getElementById('device_app_name').value,
        packagename: document.getElementById('device_app_packagename').value,
        versionname: document.getElementById('device_app_versionname').value,
        versioncode: parseInt(document.getElementById('device_app_versioncode').value),
        sha1: id ? (deviceData.app_tactics.applist.find(a => a.id == id)?.sha1 || "") : "",
        target_sdk_version: id ? (deviceData.app_tactics.applist.find(a => a.id == id)?.target_sdk_version || 28) : 28,
        devicetype: "HEY-W09",
        grant_type: 5,
        grant_to: 776883,
        groupid: 1,
        isnew: false,
        app_notify_status: 0,
        exception_white_url: 0,
        sort_weight: 0,
        created_at: id ? (deviceData.app_tactics.applist.find(a => a.id == id)?.created_at || now) : now,
        updated_at: now,
        hide_icon_status: document.getElementById('device_app_hide_icon_status').checked ? 1 : 0,
        status: document.getElementById('device_app_status').checked ? 1 : 0,
        canuninstall: document.getElementById('device_app_canuninstall').checked,
        isforce: document.getElementById('device_app_isforce').checked,
        is_trust: document.getElementById('device_app_istrust').checked
    };
    
    if (id) {
        const index = deviceData.app_tactics.applist.findIndex(a => a.id == id);
        if (index > -1) {
            deviceData.app_tactics.applist[index] = app;
        }
    } else {
        deviceData.app_tactics.applist.push(app);
    }
    
    closeModal('deviceAppModal');
    markAsUnsaved();
    renderDeviceAppTable();
}

function deleteDeviceApp(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this device app?' : '确定要删除此设备应用吗？';
    openDeleteModal(message, function() {
        deviceData.app_tactics.applist = deviceData.app_tactics.applist.filter(a => a.id != id);
        markAsUnsaved();
        renderDeviceAppTable();
    });
}

function renderDeviceAppTable() {
    const tbody = document.getElementById('deviceAppTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = deviceData.app_tactics.applist.map(app => `
        <tr data-id="${app.id}">
            <td>${app.id}</td>
            <td>${escapeHtml(app.name)}</td>
            <td>${escapeHtml(app.packagename)}</td>
            <td>${escapeHtml(app.versionname)}</td>
            <td>${app.versioncode}</td>
            <td><span class="status-badge ${app.hide_icon_status ? 'status-enabled' : 'status-disabled'}">${app.hide_icon_status ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td><span class="status-badge ${app.status ? 'status-enabled' : 'status-disabled'}">${app.status ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editDeviceApp(${app.id})"><?php echo Lang::get('edit', $lang); ?></button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteDeviceApp(${app.id})"><?php echo Lang::get('delete', $lang); ?></button>
            </td>
        </tr>
    `).join('');
}

// OpenList 文件上传(次函数已废弃但保留以防错误)
function getOpenListToken() {
    return fetch('api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_openlist_token',
            token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.token) {
            return data.token;
        } else {
           
            return '7h8fafafajfda8932742sfafd7a74hiadhf8%8das'; 
        }
    });
}

function uploadLinkFile(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const maxSize = 4096 * 1024 * 1024;
    
    if (file.size > maxSize) {
        alert(LANG === 'en' ? 'File size must be less than 4GB' : '文件大小不能超过4GB');
        return;
    }
    
    // 显示进度模态框
    showUploadProgress(file);
    
    const xhr = new XMLHttpRequest();
    currentUploadXHR = xhr;
    let lastResponseLength = 0;
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState >= 3) {
            const newData = xhr.responseText.substring(lastResponseLength);
            lastResponseLength = xhr.responseText.length;
            
            // 解析 NDJSON（每行一个 JSON）
            const lines = newData.split('\n').filter(l => l.trim());
            
            lines.forEach(line => {
                try {
                    const data = JSON.parse(line);
                    
                    if (data.stage === 'local') {
                        // 阶段1：本地接收完成，开始上传云端
                        updateProgress(100, 0, '0 MB', '0 MB/s', 'cloud', 
                            LANG === 'en' ? 'Uploading to OpenList...' : '正在上传到 OpenList...');
                            
                    } else if (data.stage === 'cloud') {
                        // 阶段2：真实的 OpenList 上传进度
                        updateProgress(
                            data.progress,
                            data.speed,
                            data.uploaded,
                            data.total,
                            'active'
                        );
                        
                    } else if (data.stage === 'complete') {
                        // 完成
                        updateProgress(100, 0, 0, 0, 'success');
                        setTimeout(() => {
                            closeModal('uploadProgressModal');
                            
                            // 填充表单
                            if (document.getElementById('link_url')) {
                                document.getElementById('link_url').value = data.data.url;
                            }
                            if (document.getElementById('link_name') && !document.getElementById('link_name').value.trim()) {
                                const name = file.name.replace(/\.[^/.]+$/, '');
                                document.getElementById('link_name').value = name;
                            }
                            
                            const statusDiv = document.getElementById('link_upload_status');
                            if (statusDiv) {
                                statusDiv.textContent = LANG === 'en' ? 'Upload successful!' : '上传成功！';
                                statusDiv.style.color = '#5cb85c';
                            }
                        }, 500);
                        
                    } else if (data.stage === 'error') {
                        // 错误
                        updateProgress(0, 0, 0, 0, 'error', data.message);
                        setTimeout(() => closeModal('uploadProgressModal'), 2000);
                    }
                } catch (e) {
                    console.error('Parse error:', e, line);
                }
            });
        }
    };
    
    xhr.onerror = function() {
        updateProgress(0, 0, 0, 0, 'error', 'Network error');
    };
    
    xhr.onabort = function() {
        closeModal('uploadProgressModal');
    };
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'proxy_upload_openlist');
    formData.append('token', CSRF_TOKEN);
    
    xhr.open('POST', 'api.php', true);
    xhr.send(formData);
}

function showUploadProgress(file) {
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressBar').className = 'progress-active';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressSpeed').textContent = '0 MB/s';
    document.getElementById('progressStatus').textContent = LANG === 'en' ? 'Preparing...' : '准备中...';
    document.getElementById('progressFilename').textContent = file.name;
    document.getElementById('progressFileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    openModal('uploadProgressModal');
}

function updateProgress(percent, speed, uploaded, total, status, message) {
    const bar = document.getElementById('progressBar');
    const percentText = document.getElementById('progressPercent');
    const speedText = document.getElementById('progressSpeed');
    const statusText = document.getElementById('progressStatus');
    
    bar.style.width = percent + '%';
    percentText.textContent = percent + '%';
    
    if (status === 'active' || status === 'cloud') {
        bar.className = 'progress-active';
        speedText.textContent = speed || '';
        statusText.textContent = message || 
            (LANG === 'en' ? `Uploading: ${uploaded} / ${total}` : `上传中: ${uploaded} / ${total}`);
        statusText.style.color = '#5bc0de';
    } else if (status === 'success') {
        bar.className = 'progress-success';
        speedText.textContent = '';
        statusText.textContent = LANG === 'en' ? 'Complete!' : '完成！';
        statusText.style.color = '#5cb85c';
    } else if (status === 'error') {
        bar.className = 'progress-error';
        speedText.textContent = '';
        statusText.textContent = (LANG === 'en' ? 'Error: ' : '错误: ') + message;
        statusText.style.color = '#d9534f';
    }
}

function cancelUpload() {
    if (currentUploadXHR && currentUploadXHR.readyState !== 4) {
        currentUploadXHR.abort();
    }
    closeModal('uploadProgressModal');
}


// ========== 广告管理 ==========
function openAdvertiseModal(editId = null) {
    document.getElementById('advertiseModalTitle').textContent = editId ? '<?php echo Lang::get('edit_advertise', $lang); ?>' : '<?php echo Lang::get('add_advertise', $lang); ?>';
    
    if (editId) {
        const adv = advertiseData.advertises.find(a => a.id == editId);
        if (adv) {
            document.getElementById('advertise_id').value = adv.id;
            document.getElementById('advertise_name').value = adv.name;
            document.getElementById('advertise_iconpath').value = adv.iconpath || '';
            // 设置多选框
            const select = document.getElementById('advertise_appids');
            Array.from(select.options).forEach(opt => {
                opt.selected = (adv.appids || []).includes(opt.value);
            });
        }
    } else {
        document.getElementById('advertise_id').value = '';
        document.getElementById('advertiseForm').reset();
    }
    
    openModal('advertiseModal');
}

function editAdvertise(id) {
    openAdvertiseModal(id);
}

function saveAdvertise() {
    const id = document.getElementById('advertise_id').value;
    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    
    // 获取选中的应用ID
    const select = document.getElementById('advertise_appids');
    const selectedApps = Array.from(select.selectedOptions).map(opt => opt.value);
    
    const adv = {
        id: id ? parseInt(id) : (advertiseData.advertises.length > 0 ? Math.max(...advertiseData.advertises.map(a => a.id)) + 1 : 1),
        name: document.getElementById('advertise_name').value,
        iconpath: document.getElementById('advertise_iconpath').value,
        appids: selectedApps,
        created_at: id ? (advertiseData.advertises.find(a => a.id == id)?.created_at || now) : now,
        updated_at: now
    };
    
    if (id) {
        const index = advertiseData.advertises.findIndex(a => a.id == id);
        if (index > -1) {
            advertiseData.advertises[index] = adv;
        }
    } else {
        advertiseData.advertises.push(adv);
    }
    
    closeModal('advertiseModal');
    markAsUnsaved();
    renderAdvertiseTable();
}

function deleteAdvertise(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this advertisement?' : '确定要删除此广告吗？';
    openDeleteModal(message, function() {
        advertiseData.advertises = advertiseData.advertises.filter(a => a.id != id);
        markAsUnsaved();
        renderAdvertiseTable();
    });
}

function renderAdvertiseTable() {
    const tbody = document.getElementById('advertiseTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = advertiseData.advertises.map(adv => `
        <tr data-id="${adv.id}">
            <td>${adv.id}</td>
            <td>${escapeHtml(adv.name)}</td>
            <td><img src="${adv.iconpath || ''}" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
            <td>${(adv.appids || []).length}</td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editAdvertise(${adv.id})"><?php echo Lang::get('edit', $lang); ?></button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteAdvertise(${adv.id})"><?php echo Lang::get('delete', $lang); ?></button>
            </td>
        </tr>
    `).join('');
}

// ========== 应用组管理 ==========
function openAppgroupModal(editId = null) {
    document.getElementById('appgroupModalTitle').textContent = editId ? '<?php echo Lang::get('edit_appgroup', $lang); ?>' : '<?php echo Lang::get('add_appgroup', $lang); ?>';
    
    if (editId) {
        const group = appgroupData.find(g => g.id == editId);
        if (group) {
            document.getElementById('appgroup_id').value = group.id;
            document.getElementById('appgroup_id_input').value = group.id;
            document.getElementById('appgroup_id_input').readOnly = true;
            document.getElementById('appgroup_name').value = group.name;
            document.getElementById('appgroup_iconpath').value = group.iconpath || '';
        }
    } else {
        document.getElementById('appgroup_id').value = '';
        document.getElementById('appgroup_id_input').value = '';
        document.getElementById('appgroup_id_input').readOnly = false;
        document.getElementById('appgroupForm').reset();
    }
    
    openModal('appgroupModal');
}

function editAppgroup(id) {
    openAppgroupModal(id);
}

function saveAppgroup() {
    const id = document.getElementById('appgroup_id').value;
    const newId = parseInt(document.getElementById('appgroup_id_input').value);
    const name = document.getElementById('appgroup_name').value.trim();
    const iconpath = document.getElementById('appgroup_iconpath').value.trim();
    
    if (!name) {
        alert('<?php echo Lang::get('name_required', $lang); ?>');
        return;
    }
    
    const group = {
        id: id ? parseInt(id) : newId,
        name: name,
        iconpath: iconpath || 'http://cloud.linspirer.com:880/images/appgroup-default.png '
    };
    
    if (id) {
        const index = appgroupData.findIndex(g => g.id == id);
        if (index > -1) {
            appgroupData[index] = group;
        }
    } else {
        // 检查ID是否已存在
        if (appgroupData.find(g => g.id == newId)) {
            alert('<?php echo Lang::get('id_exists', $lang); ?>');
            return;
        }
        appgroupData.push(group);
    }
    
    // 按ID排序
    appgroupData.sort((a, b) => a.id - b.id);
    
    closeModal('appgroupModal');
    markAsUnsaved();
    renderAppgroupTable();
    // 刷新应用组下拉框
    refreshAppgroupSelect();
}

function deleteAppgroup(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this app group?' : '确定要删除此应用组吗？';
    openDeleteModal(message, function() {
        appgroupData = appgroupData.filter(g => g.id != id);
        markAsUnsaved();
        renderAppgroupTable();
        refreshAppgroupSelect();
    });
}

function renderAppgroupTable() {
    const tbody = document.getElementById('appgroupTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = appgroupData.map(group => `
        <tr data-id="${group.id}">
            <td>${group.id}</td>
            <td>${escapeHtml(group.name)}</td>
            <td><img src="${group.iconpath || ''}" class="app-icon" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22  width=%2240%22 height=%2240%22><rect fill=%22%23ddd%22 width=%2240%22 height=%2240%22/></svg>'"></td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editAppgroup(${group.id})"><?php echo Lang::get('edit', $lang); ?></button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteAppgroup(${group.id})"><?php echo Lang::get('delete', $lang); ?></button>
            </td>
        </tr>
    `).join('');
}

function refreshAppgroupSelect() {
    const select = document.getElementById('app_groupid');
    if (!select) return;
    
    const currentValue = select.value;
    select.innerHTML = appgroupData.map(group => 
        `<option value="${group.id}">${escapeHtml(group.name)}</option>`
    ).join('');
    
    if (currentValue) {
        select.value = currentValue;
    }
}

// ========== 用户管理 ==========
function openUserModal(editId = null) {
    document.getElementById('userModalTitle').textContent = editId ? '<?php echo Lang::get('edit_user', $lang); ?>' : '<?php echo Lang::get('add_user', $lang); ?>';
    
    if (editId) {
        const user = userData.find(u => u.id == editId);
        if (user) {
            document.getElementById('user_id').value = user.id;
            document.getElementById('user_name').value = user.name;
            document.getElementById('user_email').value = user.email;
            document.getElementById('user_key').value = user.key;
            document.getElementById('user_school').value = user.school;
            document.getElementById('user_status').checked = user.status == 1;
            document.getElementById('user_free_control').checked = user.free_control == 1;
            document.getElementById('user_focus').checked = user.focus == 1;
        }
    } else {
        document.getElementById('user_id').value = '';
    }
    
    openModal('userModal');
}

function editUser(id) {
    openUserModal(id);
}

function saveUser() {
    const id = document.getElementById('user_id').value;
    const existingUser = id ? userData.find(u => u.id == id) : null;
    
    const user = {
        id: id ? parseInt(id) : Date.now(),
        key: document.getElementById('user_key').value,
        name: document.getElementById('user_name').value,
        email: document.getElementById('user_email').value,
        school: parseInt(document.getElementById('user_school').value),
        usergroup: existingUser ? existingUser.usergroup : 1128989,
        status: document.getElementById('user_status').checked ? 1 : 0,
        free_control: document.getElementById('user_free_control').checked ? 1 : 0,
        focus: document.getElementById('user_focus').checked ? 1 : 0,
        schoolinfo: existingUser ? existingUser.schoolinfo : {
            id: 776883,
            school_id: "ae164c7d-bf69-46fb-93e2-f19172b1bc61",
            name: "成都市泡桐树中学机盟",
            abbr: "机盟"
        },
        groupinfo: existingUser ? existingUser.groupinfo : [{
            id: 1128989,
            school: 776883,
            name: "(初三)2026学部/2026学部导师02班",
            description: null,
            created_at: "2023-08-28 10:44:48",
            updated_at: "2023-08-28 10:44:48"
        }]
    };
    
    if (id) {
        const index = userData.findIndex(u => u.id == id);
        if (index > -1) {
            userData[index] = user;
        }
    } else {
        userData.push(user);
    }
    
    closeModal('userModal');
    markAsUnsaved();
    renderUserTable();
}

function deleteUser(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this user?' : '确定要删除此用户吗？';
    openDeleteModal(message, function() {
        userData = userData.filter(u => u.id != id);
        markAsUnsaved();
        renderUserTable();
    });
}

function renderUserTable() {
    const tbody = document.getElementById('userTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = userData.map(user => `
        <tr data-id="${user.id}">
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.schoolinfo?.name || '')}</td>
            <td>${escapeHtml(user.groupinfo?.[0]?.name || '')}</td>
            <td><span class="status-badge ${user.status ? 'status-enabled' : 'status-disabled'}">${user.status ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td><span class="status-badge ${user.free_control ? 'status-enabled' : 'status-disabled'}">${user.free_control ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td><span class="status-badge ${user.focus ? 'status-enabled' : 'status-disabled'}">${user.focus ? (LANG === 'en' ? 'Yes' : '是') : (LANG === 'en' ? 'No' : '否')}</span></td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editUser(${user.id})"><?php echo Lang::get('edit', $lang); ?></button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteUser(${user.id})"><?php echo Lang::get('delete', $lang); ?></button>
            </td>
        </tr>
    `).join('');
}

// ========== 策略更新（仅前端修改，不自动保存） ==========
function updatePolicy(element, invert = false) {
    const path = element.dataset.section.split('.');
    let current = deviceData;
    
    for (let i = 0; i < path.length - 1; i++) {
        current = current[path[i]];
    }
    
    let value = element.checked;
    if (invert) {
        value = !value;
    }
    
    current[path[path.length - 1]] = value;
    
    // 仅标记有未保存的更改，不自动保存
    markAsUnsaved();
}

// ========== HTML 转义工具 ==========
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 点击模态框外部关闭
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
}

// 页面离开前提示未保存
window.onbeforeunload = function(e) {
    if (hasUnsavedChanges) {
        const message = LANG === 'en' ? 'You have unsaved changes. Are you sure you want to leave?' : '您有未保存的更改，确定要离开吗？';
        e.returnValue = message;
        return message;
    }
};
// ========== 链接管理 ==========
function openLinkModal(editId = null) {
    document.getElementById('linkModalTitle').textContent = editId ? 
        (LANG === 'en' ? 'Edit Link' : '编辑链接') : 
        (LANG === 'en' ? 'Add Link' : '添加链接');
    
    // 重置表单
    document.getElementById('linkForm').reset();
    document.getElementById('link_upload_status').textContent = '';
    document.getElementById('link_url').value = '';
    document.getElementById('link_name').value = '';
    document.getElementById('link_file').value = '';
    
    if (editId !== null) {
        const link = linkData.find(l => l.id == editId);
        if (link) {
            document.getElementById('link_id').value = link.id;
            document.getElementById('link_id').readOnly = true;
            document.getElementById('link_name').value = link.name;
            document.getElementById('link_url').value = link.url;
            document.getElementById('link_status').checked = (link.status ?? 1) == 1;
        }
    } else {
        document.getElementById('link_id').value = '';
        document.getElementById('link_id').readOnly = false;
        document.getElementById('link_status').checked = true;
        
        // 生成新的 ID（可选）
        if (linkData && linkData.length > 0) {
            const maxId = Math.max(...linkData.map(l => l.id));
            document.getElementById('link_id').value = maxId + 1;
        } else {
            document.getElementById('link_id').value = 1;
        }
    }
    
    openModal('linkModal');
}

function editLink(id) {
    openLinkModal(id);
}

function saveLink() {
    const id = parseInt(document.getElementById('link_id').value);
    const name = document.getElementById('link_name').value.trim();
    const url = document.getElementById('link_url').value.trim();
    const status = document.getElementById('link_status').checked ? 1 : 0;
    
    if (!id && id !== 0) {
        alert(LANG === 'en' ? 'Please enter ID' : '请输入ID');
        return;
    }
    
    if (!name) {
        alert(LANG === 'en' ? 'Please enter link name' : '请输入链接名称');
        return;
    }
    
    if (!url) {
        alert(LANG === 'en' ? 'Please enter URL' : '请输入URL');
        return;
    }
    
    // 检查ID是否已存在（新增时）
    const existingIndex = linkData.findIndex(l => l.id == id);
    const isEdit = document.getElementById('link_id').readOnly;
    
    if (!isEdit && existingIndex > -1) {
        alert(LANG === 'en' ? 'ID already exists' : 'ID已存在');
        return;
    }
    
    const link = {
        id: id,
        name: name,
        url: url,
        status: status
    };
    
    if (isEdit && existingIndex > -1) {
        linkData[existingIndex] = link;
    } else {
        linkData.push(link);
    }
    
    // 按ID排序
    linkData.sort((a, b) => a.id - b.id);
    
    closeModal('linkModal');
    renderLinkTable();
    markAsUnsaved();
}

function deleteLink(id) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this link?' : '确定要删除此链接吗？';
    openDeleteModal(message, function() {
        linkData = linkData.filter(l => l.id != id);
        renderLinkTable();
        markAsUnsaved();
    });
}

function renderLinkTable() {
    const tbody = document.getElementById('linkTableBody');
    if (!tbody) return;
    
    if (!linkData || linkData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">${LANG === 'en' ? 'No data' : '暂无数据'}</td></tr>`;
        return;
    }
    
    tbody.innerHTML = linkData.map(link => `
        <tr data-id="${link.id}">
            <td>${link.id}</td>
            <td>${escapeHtml(link.name)}</td>
            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(link.url)}">${escapeHtml(link.url)}</td>
            <td><span class="status-badge ${(link.status ?? 1) ? 'status-enabled' : 'status-disabled'}">${(link.status ?? 1) ? (LANG === 'en' ? 'Enabled' : '已启用') : (LANG === 'en' ? 'Disabled' : '已禁用')}</span></td>
            <td>
                <button class="btn btn-info btn-xs btn-action" onclick="editLink(${link.id})">${LANG === 'en' ? 'Edit' : '编辑'}</button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteLink(${link.id})">${LANG === 'en' ? 'Delete' : '删除'}</button>
            </td>
        </tr>
    `).join('');
}

// ========== 组件控制管理 ==========
let componentData = JSON.parse(JSON.stringify(<?php echo json_encode($componentData); ?>));
let originalComponentData = JSON.parse(JSON.stringify(componentData));
let currentComponentPage = 1;
let componentPageSize = 10;
let filteredComponentData = [];

// 初始化组件表格
function initComponentTable() {
    filteredComponentData = [...componentData];
    renderComponentTable();
}

// 渲染组件表格（带分页）
function renderComponentTable() {
    const tbody = document.getElementById('componentTableBody');
    if (!tbody) return;
    
    const start = (currentComponentPage - 1) * componentPageSize;
    const end = Math.min(start + componentPageSize, filteredComponentData.length);
    const pageData = filteredComponentData.slice(start, end);
    
    tbody.innerHTML = pageData.map((component, idx) => {
        const globalIndex = componentData.findIndex(c => 
            c.package_name === component.package_name && c.component === component.component
        );
        return `
            <tr data-index="${globalIndex}" data-package="${escapeHtml(component.package_name)}">
                <td>${start + idx + 1}</td>
                <td>${escapeHtml(component.package_name)}</td>
                <td style="word-break: break-all;">${escapeHtml(component.component)}</td>
                <td>
                    <button class="btn btn-info btn-xs btn-action" onclick="editComponent(${globalIndex})">
                        <?php echo Lang::get('edit', $lang); ?>
                    </button>
                    <button class="btn btn-danger btn-xs btn-action" onclick="deleteComponent(${globalIndex})">
                        <?php echo Lang::get('delete', $lang); ?>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    // 更新分页信息
    const totalPages = Math.ceil(filteredComponentData.length / componentPageSize);
    document.getElementById('pageInfo').textContent = `${currentComponentPage} / ${totalPages || 1}`;
    
    // 更新分页按钮状态
    document.querySelector('.pagination-container .btn:first-child').disabled = currentComponentPage === 1;
    document.querySelector('.pagination-container .btn:last-child').disabled = currentComponentPage === totalPages || totalPages === 0;
}

// 搜索组件
function searchComponent() {
    const searchText = document.getElementById('componentSearch').value.toLowerCase();
    const filterPackage = document.getElementById('componentFilter').value;
    
    filteredComponentData = componentData.filter(comp => {
        const matchSearch = searchText === '' || 
            comp.package_name.toLowerCase().includes(searchText) ||
            comp.component.toLowerCase().includes(searchText);
        const matchFilter = filterPackage === '' || comp.package_name === filterPackage;
        return matchSearch && matchFilter;
    });
    
    currentComponentPage = 1;
    renderComponentTable();
}

// 切换页码
function changePage(direction) {
    const totalPages = Math.ceil(filteredComponentData.length / componentPageSize);
    if (direction === 'prev' && currentComponentPage > 1) {
        currentComponentPage--;
    } else if (direction === 'next' && currentComponentPage < totalPages) {
        currentComponentPage++;
    }
    renderComponentTable();
}

// 改变每页显示数量
function changePageSize() {
    componentPageSize = parseInt(document.getElementById('pageSize').value);
    currentComponentPage = 1;
    renderComponentTable();
}

// 打开组件模态框
function openComponentModal(editIndex = null) {
    document.getElementById('componentModalTitle').textContent = editIndex !== null ? 
        (LANG === 'en' ? 'Edit Component' : '编辑组件') : 
        (LANG === 'en' ? 'Add Component' : '添加组件');
    
    if (editIndex !== null) {
        const component = componentData[editIndex];
        document.getElementById('component_index').value = editIndex;
        document.getElementById('component_package_name').value = component.package_name;
        document.getElementById('component_component').value = component.component;
    } else {
        document.getElementById('component_index').value = '';
        const form = document.getElementById('componentForm');
        if (form) form.reset();
    }
    
    openModal('componentModal');
}

// 编辑组件
function editComponent(index) {
    openComponentModal(index);
}

// 快速选择组件
function quickSelectComponent(select) {
    if (!select.value) return;
    const [packageName, component] = select.value.split(',');
    document.getElementById('component_package_name').value = packageName;
    document.getElementById('component_component').value = component;
    select.value = '';
}

// 保存组件
function saveComponent() {
    const index = document.getElementById('component_index').value;
    const packageName = document.getElementById('component_package_name').value.trim();
    const component = document.getElementById('component_component').value.trim();
    
    if (!packageName || !component) {
        alert(LANG === 'en' ? 'Please fill in all required fields' : '请填写所有必填字段');
        return;
    }
    
    const newComponent = {
        package_name: packageName,
        component: component
    };
    
    // 检查是否已存在（除了当前编辑的项）
    const exists = componentData.some((c, i) => 
        i != index && c.package_name === packageName && c.component === component
    );
    
    if (exists) {
        alert(LANG === 'en' ? 'This component already exists' : '该组件已存在');
        return;
    }
    
    if (index !== '') {
        // 编辑现有项
        componentData[parseInt(index)] = newComponent;
    } else {
        // 新增项
        componentData.push(newComponent);
    }
    
    closeModal('componentModal');
    markAsUnsaved();
    
    // 更新筛选器和表格
    updateComponentFilter();
    searchComponent();
}

// 选中的应用自动填充包名
function selectAppForComponent(select) {
    if (!select.value) return;
    
    const packageName = select.value;
    const selectedOption = select.options[select.selectedIndex];
    const appName = selectedOption.getAttribute('data-name') || '';
    
    // 填充包名
    document.getElementById('component_package_name').value = packageName;
    
    // 清空组件名，但保留包名作为提示
    document.getElementById('component_component').value = packageName;
    
    // 启用组件模板选择（因为现在有包名了）
    document.getElementById('componentTemplateSelect').disabled = false;
    
    // 显示提示
    showTooltip(LANG === 'en' ? 
        `Selected app: ${appName}. Now you can select a component template or enter manually.` : 
        `已选择应用：${appName}。现在您可以选择组件模板或手动输入。`);
}

// 应用组件模板
function applyComponentTemplate(select) {
    if (!select.value) return;
    
    const packageName = document.getElementById('component_package_name').value;
    if (!packageName) {
        alert(LANG === 'en' ? 'Please select an app first' : '请先选择一个应用');
        select.value = '';
        return;
    }
    
    const template = select.value;
    const fullComponent = packageName + template;
    document.getElementById('component_component').value = fullComponent;
    
    // 显示预览
    showTooltip(LANG === 'en' ? 
        `Component set to: ${fullComponent}` : 
        `组件已设置为：${fullComponent}`);
}

// 显示临时提示
function showTooltip(message) {
    // 创建或获取提示元素
    let tooltip = document.getElementById('componentTooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'componentTooltip';
        tooltip.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #5cb85c;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(tooltip);
        
        // 添加动画样式
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    tooltip.textContent = message;
    tooltip.style.display = 'block';
    
    // 3秒后自动隐藏
    setTimeout(() => {
        tooltip.style.display = 'none';
    }, 3000);
}

// 这个函数不再使用，但保留避免错误
function quickSelectComponent(select) {
    
    if (select && select.value) {
        select.value = '';
    }
}
// 删除组件
function deleteComponent(index) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this component?' : '确定要删除此组件吗？';
    openDeleteModal(message, function() {
        componentData.splice(index, 1);
        markAsUnsaved();
        updateComponentFilter();
        searchComponent();
    });
}

// 更新组件筛选下拉框
function updateComponentFilter() {
    const filterSelect = document.getElementById('componentFilter');
    const currentValue = filterSelect.value;
    
    const uniquePackages = [...new Set(componentData.map(c => c.package_name))];
    filterSelect.innerHTML = '<option value=""><?php echo Lang::get('all_packages', $lang); ?></option>' +
        uniquePackages.map(pkg => `<option value="${escapeHtml(pkg)}">${escapeHtml(pkg)}</option>`).join('');
    
    if (currentValue && uniquePackages.includes(currentValue)) {
        filterSelect.value = currentValue;
    }
}

// 导出组件数据
function exportComponentData() {
    const dataStr = JSON.stringify(componentData, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `component_export_${new Date().toISOString().slice(0,10)}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// 打开导入模态框
function importComponentData() {
    document.getElementById('importMethod').value = 'file';
    document.getElementById('importFileDiv').style.display = 'block';
    document.getElementById('importTextDiv').style.display = 'none';
    document.getElementById('importFile').value = '';
    document.getElementById('importText').value = '';
    openModal('importComponentModal');
}

// 切换导入方式
function toggleImportMethod() {
    const method = document.getElementById('importMethod').value;
    document.getElementById('importFileDiv').style.display = method === 'file' ? 'block' : 'none';
    document.getElementById('importTextDiv').style.display = method === 'text' ? 'block' : 'none';
}

// 导入组件
function importComponents() {
    const method = document.getElementById('importMethod').value;
    const merge = document.getElementById('importMerge').checked;
    
    let importedData;
    
    if (method === 'file') {
        const file = document.getElementById('importFile').files[0];
        if (!file) {
            alert(LANG === 'en' ? 'Please select a file' : '请选择文件');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                importedData = JSON.parse(e.target.result);
                processImport(importedData, merge);
            } catch (err) {
                alert(LANG === 'en' ? 'Invalid JSON file' : '无效的JSON文件');
            }
        };
        reader.readAsText(file);
    } else {
        try {
            importedData = JSON.parse(document.getElementById('importText').value);
            processImport(importedData, merge);
        } catch (err) {
            alert(LANG === 'en' ? 'Invalid JSON format' : '无效的JSON格式');
        }
    }
}

// 处理导入的数据
function processImport(importedData, merge) {
    if (!Array.isArray(importedData)) {
        alert(LANG === 'en' ? 'Data must be an array' : '数据必须是数组格式');
        return;
    }
    
    // 验证数据格式
    const valid = importedData.every(item => 
        item && typeof item === 'object' && 
        item.package_name && typeof item.package_name === 'string' &&
        item.component && typeof item.component === 'string'
    );
    
    if (!valid) {
        alert(LANG === 'en' ? 'Invalid data format. Each item must have package_name and component fields' : '无效的数据格式，每个项目必须包含 package_name 和 component 字段');
        return;
    }
    
    if (merge) {
        // 合并模式：添加不存在的项
        const existing = new Set(componentData.map(c => `${c.package_name}|${c.component}`));
        const newItems = importedData.filter(item => 
            !existing.has(`${item.package_name}|${item.component}`)
        );
        componentData.push(...newItems);
        alert(LANG === 'en' ? `Imported ${newItems.length} new items` : `导入了 ${newItems.length} 个新项目`);
    } else {
        // 替换模式
        componentData = importedData;
        alert(LANG === 'en' ? `Replaced with ${importedData.length} items` : `替换为 ${importedData.length} 个项目`);
    }
    
    closeModal('importComponentModal');
    markAsUnsaved();
    updateComponentFilter();
    searchComponent();
}

// 在页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 原有的初始化代码...
    initComponentTable();
});
// 单独保存链接数据
function saveLinkData() {
    const data = {
        action: 'save_link',
        token: CSRF_TOKEN,
        link: linkData
    };
    
    $.ajax({
        url: 'api.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(res) {
            if (res.success) {
                Object.assign(originalLinkData, JSON.parse(JSON.stringify(linkData)));
                showAlert(LANG === 'en' ? 'Link saved successfully' : '链接保存成功');
            } else {
                showAlert('<?php echo Lang::get('save_failed', $lang); ?>: ' + res.message, 'error');
            }
        },
        error: function() {
            showAlert('<?php echo Lang::get('save_failed', $lang); ?>', 'error');
        }
    });
}

// 检测表格是否需要横向滚动，添加视觉提示
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.table-responsive');
    
    tables.forEach(function(container) {
        // 检查内容是否溢出
        function checkOverflow() {
            if (container.scrollWidth > container.clientWidth) {
                container.classList.add('scrollable');
            } else {
                container.classList.remove('scrollable');
            }
        }
        
        // 初始检查
        checkOverflow();
        
        // 窗口大小改变时重新检查
        window.addEventListener('resize', checkOverflow);
        
        // 添加键盘提示（可访问性）
        container.addEventListener('focus', function() {
            if (container.scrollWidth > container.clientWidth) {
                // 可以在这里添加提示，告知用户可以使用方向键滚动
                console.log('Use arrow keys to scroll horizontally');
            }
        });
    });
});

function generateSafeFilename(name) {
    const timestamp = Math.floor(Date.now() / 1000);
    const lastDot = name.lastIndexOf('.');
    const ext = lastDot !== -1 ? name.substring(lastDot + 1) : '';
    const base = lastDot !== -1 ? name.substring(0, lastDot) : name;
    // 替换特殊字符为下划线，限制长度
    const safe = base.replace(/[^\p{L}\p{N}._-]/gu, '_').substring(0, 50);
    return timestamp + '_' + safe + (ext ? '.' + ext : '');
}

/**
 * 并发控制函数（参考 Upload.js 的 HA - 限制并发数）
 * @param {number} concurrency - 最大并发数
 * @param {Array} tasks - 任务数组（返回 Promise 的函数数组）
 */
async function asyncPool(concurrency, tasks) {
    const results = [];
    const executing = [];
    
    for (const [index, task] of tasks.entries()) {
        const promise = Promise.resolve().then(() => task());
        results.push(promise);
        
        if (tasks.length - index <= concurrency) {
            // 最后几个任务，顺序等待
            await promise;
        } else {
            executing.push(promise);
            if (executing.length >= concurrency) {
                await Promise.race(executing);
            }
        }
    }
    
    return Promise.all(results);
}

/**
 * 多线程分片上传到 OpenList（参考 Upload.js 的 cI 函数）
 * @param {File} file - 要上传的文件
 * @param {Function} onProgress - 进度回调 (percent, uploadedBytes, totalBytes)
 * @returns {Promise<string>} 返回文件下载 URL
 */
/**
 * 参照 upload.js 的多线程上传实现
 * 关键流程：获取 direct_upload_info → 分片计算 → HA 并发上传 → 验证
 */
/**
 * 使用 /api/fs/form 的多线程分片上传（模拟 OpenList 网页端）
 * 将文件分片 → 并发上传分片（multipart/form-data）→ 服务器自动合并
 */
async function uploadToOpenList(file, onProgress) {
    const fileSize = file.size;
    const targetPath = '/main/appstore/' + generateSafeFilename(file.name);
    
    // 1. 获取 Token
    const authRes = await fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'get_openlist_token', token: CSRF_TOKEN })
    }).then(r => r.json());
    
    if (!authRes.success) throw new Error('Auth failed');
    const token = authRes.token || authRes.data?.token;
    const baseUrl = authRes.base_url || 'https://162.14.113.207:5245';
    
    console.log(`[Upload] Single file upload: ${file.name} (${(fileSize/1024/1024).toFixed(2)} MB)`);
    
    // 2. 整体文件上传（参考 OpenList 网页端实际行为）
    await new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const startTime = Date.now();
        
        // 进度追踪（每秒更新一次避免卡顿）
        let lastProgressTime = 0;
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const now = Date.now();
                // 限制更新频率，避免 UI 卡顿
                if (now - lastProgressTime > 100 || e.loaded === e.total) {
                    const percent = (e.loaded / e.total) * 100;
                    const elapsed = (now - startTime) / 1000;
                    const speed = elapsed > 0 ? (e.loaded / 1024 / 1024) / elapsed : 0;
                    onProgress(percent, e.loaded, e.total, speed);
                    lastProgressTime = now;
                }
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                console.log(`[Upload] Completed in ${((Date.now() - startTime)/1000).toFixed(2)}s`);
                resolve();
            } else {
                reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
            }
        });
        
        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.addEventListener('timeout', () => reject(new Error('Timeout')));
        
        // 关键：整体上传，不设置 Content-Range（避免 123Pan 覆盖问题）
        xhr.open('PUT', `${baseUrl}/api/fs/put`);
        xhr.setRequestHeader('Authorization', token);
        xhr.setRequestHeader('File-Path', encodeURIComponent(targetPath));
        xhr.setRequestHeader('Content-Type', 'application/octet-stream');
        xhr.setRequestHeader('Overwrite', 'true');
        
        // 大文件设置更长超时（20MB 建议 60 秒）
        xhr.timeout = Math.max(30000, fileSize / 1024 / 1024 * 10000); // 每 MB 10 秒
        
        // 直接发送整个文件
        xhr.send(file);
    });
    
    // 3. 验证文件完整性
    await new Promise(r => setTimeout(r, 500)); // 等待服务器索引
    
    const infoRes = await fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'get_file_info',
            token: CSRF_TOKEN,
            path: targetPath
        })
    }).then(r => r.json());
    
    if (!infoRes?.success || !infoRes?.url) {
        throw new Error('Failed to get file URL after upload');
    }
    
    // 严格验证大小
    if (infoRes.info?.size && infoRes.info.size !== fileSize) {
        throw new Error(`Size mismatch: server=${infoRes.info.size}, local=${fileSize}`);
    }
    
    return infoRes.url;
}

// 修改后的 uploadLinkFile（进度回调增加速度参数）
function uploadLinkFile(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const maxSize = 4096 * 1024 * 1024;
    
    if (file.size > maxSize) {
        alert('File size must be less than 4GB');
        return;
    }
    
    // 显示进度
    showUploadProgress(file);
    window.uploadStartTime = Date.now();
    
    uploadToOpenList(file, (percent, uploaded, total, speed) => {
        updateProgress(
            Math.round(percent),
            speed ? speed.toFixed(2) + ' MB/s' : '0 MB/s',
            (uploaded / 1024 / 1024).toFixed(2) + ' MB',
            (total / 1024 / 1024).toFixed(2) + ' MB',
            'active'
        );
    })
    .then(url => {
        updateProgress(100, 0, 0, 0, 'success');
        setTimeout(() => {
            closeModal('uploadProgressModal');
            const urlInput = document.getElementById('link_url');
            if (urlInput) urlInput.value = url;
            
            const nameInput = document.getElementById('link_name');
            if (nameInput && !nameInput.value.trim()) {
                nameInput.value = file.name.replace(/\.[^/.]+$/, '');
            }
        }, 500);
    })
    .catch(err => {
        console.error(err);
        updateProgress(0, 0, 0, 0, 'error', err.message);
        setTimeout(() => closeModal('uploadProgressModal'), 3000);
    });
}

// 更新下载链接选择
function updateDownloadUrl(select) {
    const selectedId = select.value;
    const displayDiv = document.getElementById('selected_link_display');
    const displayText = document.getElementById('selected_link_text');
    const pathInput = document.getElementById('app_path');
    const customInput = document.getElementById('custom_url_input');
    
    if (selectedId === '') {
        // 未选择任何选项
        pathInput.value = '';
        displayDiv.style.display = 'none';
        customInput.style.display = 'none';
        customInput.value = '';
    } else if (selectedId === 'custom') {
        // 选择自定义URL
        pathInput.value = customInput.value || '';
        displayDiv.style.display = 'none';
        customInput.style.display = 'block';
        // 如果自定义输入框有内容，自动设置path
        if (customInput.value) {
            pathInput.value = customInput.value;
        }
        customInput.focus();
    } else {
        // 选择了具体链接ID
        customInput.style.display = 'none';
        customInput.value = '';
        
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption) {
            const fullName = selectedOption.getAttribute('data-fullname');
            const shortName = fullName && fullName.length > 12 ? fullName.substring(0, 12) + '...' : fullName;
            
            // 构造下载URL
            const url = `https://162.14.113.207:883/download.php?id=${selectedId}&`;
            pathInput.value = url;
            
            // 显示已选择的ID+名字
            displayText.textContent = selectedId + ' - ' + shortName;
            displayDiv.style.display = 'block';
        }
    }
}


// 上传应用组图标（参考应用商店的 uploadAppIcon 实现）
function uploadAppgroupIcon(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const statusDiv = document.getElementById('appgroup_icon_upload_status');
    
    // 验证文件类型
    if (!file.type.startsWith('image/')) {
        statusDiv.textContent = LANG === 'en' ? 'Please select an image file' : '请选择图片文件';
        statusDiv.style.color = '#d9534f';
        return;
    }
    
    // 验证文件大小（限制 5MB）
    if (file.size > 5 * 1024 * 1024) {
        statusDiv.textContent = LANG === 'en' ? 'File size must be less than 5MB' : '文件大小不能超过5MB';
        statusDiv.style.color = '#d9534f';
        return;
    }
    
    const formData = new FormData();
    formData.append('icon', file);
    formData.append('token', CSRF_TOKEN);
    formData.append('action', 'upload_icon');  
    
    statusDiv.textContent = LANG === 'en' ? 'Uploading...' : '上传中...';
    statusDiv.style.color = '#f0ad4e';
    
    fetch('api.php', { 
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('appgroup_iconpath').value = data.url;
            statusDiv.textContent = LANG === 'en' ? 'Upload successful!' : '上传成功！';
            statusDiv.style.color = '#5cb85c';
            setTimeout(() => {
                statusDiv.textContent = '';
            }, 3000);
        } else {
            statusDiv.textContent = (LANG === 'en' ? 'Upload failed: ' : '上传失败：') + (data.message || '');
            statusDiv.style.color = '#d9534f';
        }
    })
    .catch(err => {
        statusDiv.textContent = LANG === 'en' ? 'Upload failed' : '上传失败';
        statusDiv.style.color = '#d9534f';
    });
}

// ========== 启动配置管理 ==========
function toggleLaunchApp(checkbox) {
    const packageInput = document.getElementById('launch_package');
    const modeSelect = document.getElementById('launch_mode');
    
    if (checkbox.checked) {
        // 启用：启用输入框和下拉框
        packageInput.disabled = false;
        modeSelect.disabled = false;
        // 设置默认值（如果为空）
        if (!packageInput.value.trim()) {
            packageInput.value = 'com.tblenovo.launcher';
        }
        // 更新 deviceData
        if (!deviceData.device_setting.launch_app) {
            deviceData.device_setting.launch_app = {};
        }
        deviceData.device_setting.launch_app.launch_package = packageInput.value.trim();
        deviceData.device_setting.launch_app.launch_mode = parseInt(modeSelect.value);
    } else {
        // 禁用：禁用输入框和下拉框，设置为 null
        packageInput.disabled = true;
        modeSelect.disabled = true;
        // 设置 launch_package 为 null
        if (!deviceData.device_setting.launch_app) {
            deviceData.device_setting.launch_app = {};
        }
        deviceData.device_setting.launch_app.launch_package = null;
        // launch_mode 保留原值或默认2
        if (!deviceData.device_setting.launch_app.launch_mode) {
            deviceData.device_setting.launch_app.launch_mode = 2;
        }
    }
    
    markAsUnsaved();
}

function updateLaunchMode(mode) {
    if (!deviceData.device_setting.launch_app) {
        deviceData.device_setting.launch_app = {};
    }
    deviceData.device_setting.launch_app.launch_mode = parseInt(mode);
    markAsUnsaved();
}

// ========== 浏览器管理（书签） ==========
let bookmarkData = [];
let originalBookmarkData = [];

// 从服务器获取书签数据
function loadBookmarkData() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        data: JSON.stringify({
            action: 'get_bookmarks',
            token: CSRF_TOKEN
        }),
        contentType: 'application/json',
        success: function(res) {
            if (res.success && res.bookmarks) {
                bookmarkData = Array.isArray(res.bookmarks) ? res.bookmarks : [];
                originalBookmarkData = JSON.parse(JSON.stringify(bookmarkData));
                renderBookmarkTable();
            } else {
                console.error('Failed to load bookmark data');
                bookmarkData = [];
                renderBookmarkTable();
            }
        },
        error: function() {
            console.error('Failed to load bookmark data');
            bookmarkData = [];
            renderBookmarkTable();
        }
    });
}

// 渲染书签表格
function renderBookmarkTable() {
    const tbody = document.getElementById('browserTableBody');
    if (!tbody) return;
    
    if (!bookmarkData || bookmarkData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align: center;">${LANG === 'en' ? 'No bookmarks' : '暂无书签'}</td></tr>`;
        return;
    }
    
    tbody.innerHTML = bookmarkData.map((bookmark, index) => `
        <tr data-index="${index}">
            <td style="text-align: center;">${index + 1}</td>
            <td><strong>${escapeHtml(bookmark.name)}</strong></td>
            <td style="word-break: break-all;">
                <a href="${escapeHtml(bookmark.url)}" target="_blank" style="color: #337ab7; text-decoration: none;">
                    ${escapeHtml(bookmark.url.length > 60 ? bookmark.url.substring(0, 60) + '...' : bookmark.url)}
                </a>
            </td>
            <td style="text-align: center;">
                <button class="btn btn-info btn-xs btn-action" onclick="editBookmark(${index})">
                    <span class="glyphicon glyphicon-edit"></span> ${LANG === 'en' ? 'Edit' : '编辑'}
                </button>
                <button class="btn btn-danger btn-xs btn-action" onclick="deleteBookmark(${index})">
                    <span class="glyphicon glyphicon-trash"></span> ${LANG === 'en' ? 'Delete' : '删除'}
                </button>
            </td>
        </tr>
    `).join('');
}

// 打开书签模态框
function openBrowserModal(editIndex = null) {
    const modalTitle = document.getElementById('browserModalTitle');
    const nameInput = document.getElementById('bookmark_name');
    const urlInput = document.getElementById('bookmark_url');
    const indexInput = document.getElementById('bookmark_index');
    
    if (editIndex !== null && bookmarkData[editIndex]) {
        modalTitle.textContent = LANG === 'en' ? 'Edit Bookmark' : '编辑书签';
        indexInput.value = editIndex;
        nameInput.value = bookmarkData[editIndex].name;
        urlInput.value = bookmarkData[editIndex].url;
    } else {
        modalTitle.textContent = LANG === 'en' ? 'Add Bookmark' : '添加书签';
        indexInput.value = '';
        nameInput.value = '';
        urlInput.value = '';
    }
    
    openModal('browserModal');
}

// 编辑书签
function editBookmark(index) {
    openBrowserModal(index);
}

// 保存书签（修改本地数据，标记未保存）
function saveBookmark() {
    const index = document.getElementById('bookmark_index').value;
    const name = document.getElementById('bookmark_name').value.trim();
    const url = document.getElementById('bookmark_url').value.trim();
    
    if (!name) {
        alert(LANG === 'en' ? 'Please enter bookmark name' : '请输入书签名称');
        return;
    }
    
    if (!url) {
        alert(LANG === 'en' ? 'Please enter URL' : '请输入URL');
        return;
    }
    
    // 验证URL格式
    if (!url.match(/^https?:\/\//i)) {
        alert(LANG === 'en' ? 'URL must start with http:// or https://' : 'URL必须以http://或https://开头');
        return;
    }
    
    const bookmark = {
        name: name,
        url: url
    };
    
    if (index !== '') {
        // 编辑模式
        bookmarkData[parseInt(index)] = bookmark;
    } else {
        // 添加模式
        bookmarkData.push(bookmark);
    }
    
    closeModal('browserModal');
    markAsUnsaved();
    renderBookmarkTable();
}

// 删除书签（修改本地数据，标记未保存）
function deleteBookmark(index) {
    const message = LANG === 'en' ? 'Are you sure you want to delete this bookmark?' : '确定要删除此书签吗？';
    openDeleteModal(message, function() {
        bookmarkData.splice(index, 1);
        markAsUnsaved();
        renderBookmarkTable();
    });
}

// 测试URL是否可访问
function testBookmarkUrl() {
    const url = document.getElementById('bookmark_url').value.trim();
    if (!url) {
        alert(LANG === 'en' ? 'Please enter a URL first' : '请先输入URL');
        return;
    }
    
    // 创建隐藏的iframe或使用fetch测试
    const testWindow = window.open(url, '_blank');
    if (testWindow) {
        setTimeout(() => {
            alert(LANG === 'en' ? 'URL opened in new window' : '已在新窗口打开URL');
        }, 500);
    } else {
        alert(LANG === 'en' ? 'Popup blocked. Please check manually.' : '弹窗被阻止，请手动检查。');
    }
}

// 导出书签为JSON文件
function exportBookmarks() {
    const dataStr = JSON.stringify(bookmarkData, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bookmarks_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// 打开导入书签模态框
function importBookmarks() {
    document.getElementById('bookmarkImportMethod').value = 'file';
    document.getElementById('bookmarkImportFileDiv').style.display = 'block';
    document.getElementById('bookmarkImportTextDiv').style.display = 'none';
    document.getElementById('bookmarkImportFile').value = '';
    document.getElementById('bookmarkImportText').value = '';
    openModal('importBookmarkModal');
}

// 切换导入方式
function toggleBookmarkImportMethod() {
    const method = document.getElementById('bookmarkImportMethod').value;
    document.getElementById('bookmarkImportFileDiv').style.display = method === 'file' ? 'block' : 'none';
    document.getElementById('bookmarkImportTextDiv').style.display = method === 'text' ? 'block' : 'none';
}

// 处理书签导入
function processBookmarkImport() {
    const method = document.getElementById('bookmarkImportMethod').value;
    const merge = document.getElementById('bookmarkImportMerge').checked;
    
    let importedData;
    
    if (method === 'file') {
        const file = document.getElementById('bookmarkImportFile').files[0];
        if (!file) {
            alert(LANG === 'en' ? 'Please select a file' : '请选择文件');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                importedData = JSON.parse(e.target.result);
                processBookmarkData(importedData, merge);
            } catch (err) {
                alert(LANG === 'en' ? 'Invalid JSON file' : '无效的JSON文件');
            }
        };
        reader.readAsText(file);
    } else {
        try {
            importedData = JSON.parse(document.getElementById('bookmarkImportText').value);
            processBookmarkData(importedData, merge);
        } catch (err) {
            alert(LANG === 'en' ? 'Invalid JSON format' : '无效的JSON格式');
        }
    }
}

// 处理导入的书签数据
function processBookmarkData(importedData, merge) {
    if (!Array.isArray(importedData)) {
        alert(LANG === 'en' ? 'Data must be an array' : '数据必须是数组格式');
        return;
    }
    
    // 验证数据格式
    const valid = importedData.every(item => 
        item && typeof item === 'object' && 
        typeof item.name === 'string' &&
        typeof item.url === 'string'
    );
    
    if (!valid) {
        alert(LANG === 'en' ? 'Invalid data format. Each item must have name and url fields' : '无效的数据格式，每个项目必须包含 name 和 url 字段');
        return;
    }
    
    if (merge) {
        // 合并模式：去重添加
        const existing = new Set(bookmarkData.map(b => `${b.name}|${b.url}`));
        const newItems = importedData.filter(item => 
            !existing.has(`${item.name}|${item.url}`)
        );
        bookmarkData.push(...newItems);
        alert(LANG === 'en' ? `Imported ${newItems.length} new bookmarks` : `导入了 ${newItems.length} 个新书签`);
    } else {
        // 替换模式
        bookmarkData = importedData;
        alert(LANG === 'en' ? `Replaced with ${importedData.length} bookmarks` : `替换为 ${importedData.length} 个书签`);
    }
    
    closeModal('importBookmarkModal');
    markAsUnsaved();
    renderBookmarkTable();
}

// 页面加载时初始化书签数据（通过 API）
if (document.getElementById('browserTableBody')) {
    loadBookmarkData();
}

// 监听包名输入框变化
document.addEventListener('DOMContentLoaded', function() {
    const packageInput = document.getElementById('launch_package');
    if (packageInput) {
        packageInput.addEventListener('change', function() {
            if (document.getElementById('launch_app_enabled').checked && this.value.trim()) {
                if (!deviceData.device_setting.launch_app) {
                    deviceData.device_setting.launch_app = {};
                }
                deviceData.device_setting.launch_app.launch_package = this.value.trim();
                markAsUnsaved();
            }
        });
    }
});
// 页面加载完成后初始化表格渲染
document.addEventListener('DOMContentLoaded', function() {
    renderAppTable();
    renderDeviceAppTable();
    renderUserTable();
    renderLinkTable();
    renderAdvertiseTable();
    renderAppgroupTable();
});
</script>
</body>
</html>