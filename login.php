<?php
session_start();
require_once 'lang.php';

if (isset($_GET['lang'])) {
    Lang::setLang($_GET['lang']);
}

$lang = Lang::getLang();

// 已登录则跳转
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 简单的 token 验证（实际应使用更安全的机制）
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        // 验证用户名密码
        $users = json_decode(file_get_contents('data/user.json'), true);
        $valid = false;
        $userFound = null;
        
        foreach ($users as $user) {
            if ($user['email'] === $username && $user['key'] === $password) {
                $userFound = $user;
                $valid = true;
                break;
            }
        }
        
        if ($valid) {
            // 检查用户状态，status=1 时才允许登录
            if (isset($userFound['status']) && $userFound['status'] == 1) {
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = Lang::get('account_disabled', $lang) ?? '用户已被禁用';
            }
        } else {
            $error = Lang::get('login_error', $lang);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'en' ? 'en' : 'zh-CN'; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Lang::get('login_title', $lang); ?></title>
    <link href="css/app.css" rel="stylesheet">
    <link href="css/bootstrap-dialog.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/271.css" rel="stylesheet">
    <link rel="icon" href="./title.png" type="image/x-icon">
    <link rel="shortcut icon" href="./title.png" type="image/x-icon">
</head>
<body>
<article class="container-fluid">
    <header class="page-header">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="navbar-header">
                <a href="#">
                    <img src="logb1.jpg" alt="logo" class="header_logo">
                </a>
            </div>
        </nav>
    </header>
    <div class="article-content">
        <div class="container" style="margin:0;">
            <h3 style="margin: 26px 0;"><?php echo Lang::get('welcome', $lang); ?></h3>
            <div class="row">
                <div class="col-md-12" style="margin-bottom: 20px;"><?php echo Lang::get('use_account', $lang); ?></div>
            </div>
            <div class="row" style="margin-top:20px;">
                <div class="col-md-6 col-md-offset-2 user-login" style="margin-left:0;">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-md-6 text-left" style="padding: 0;"><?php echo Lang::get('password_login', $lang); ?></div>
                                <div class="col-md-6 text-right" style="padding: 0;">
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
                                            </li>
                                        </ul>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form id="login-form" method="POST" action="">
                                <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <div id="password-login">
                                    <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <div class="login">
                                            <h1><?php echo Lang::get('login', $lang); ?></h1>
                                            <input type="text" name="username" id="uname" value="" placeholder="<?php echo Lang::get('username', $lang); ?>" class="form-control" style="margin-bottom: 10px;"/>
                                            <input type="password" name="password" id="pwd" value="" placeholder="<?php echo Lang::get('password', $lang); ?>" class="form-control" style="margin-bottom: 10px;"/>
                                            <div class="btn_login_container">
                                                <input type="submit" id="but" value="<?php echo Lang::get('login', $lang); ?>" class="btn btn-primary btn_submit_login"/> 
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-6 remember-container">
                                                <input type="checkbox" id="remember" name="remember" /> <?php echo Lang::get('remember_me', $lang); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="container-fluid foot-wrap">
        <nav class="navbar navbar-defaul container-fluid foot-wrap">
            <ul class="nav navbar-nav">
                <li><a href="#">Version: V5.0.4</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li>
                    <a href="#">
                        Copyright © 2022-2026,机盟科技(CDPTSJM), All Rights Reserved
                    </a>
                </li>
                <li><a target="_blank" href="http://beian.miit.gov.cn">暂无备案</a></li>
            </ul>
        </nav>
    </footer>
</article>
<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-dialog.min.js"></script>
<script src="js/jquery.cookie.js"></script>
<script type="text/javascript"> 

    // const { ipcRenderer } = require('electron');
    // 页面加载时，从Cookie中获取用户名
    window.onload = function() {
        const uname = $.cookie('uname');
        if (uname) {
            document.getElementById('uname').value = uname;
            document.getElementById('remember').checked = true;
        }
    };

    document.getElementById('login-form').addEventListener('submit', function() {
        if (document.getElementById('remember').checked) {
            $.cookie('uname', document.getElementById('uname').value, { expires: 7, secure: true, sameSite: 'strict' });
        } else {
            $.removeCookie('uname');
        }
    });
    
    // ipcRenderer.on('show-about', () => {
    //   alert('Electron 自定义右键菜单演示\n版本 1.0.0');
    // });
</script>
</body>
</html>