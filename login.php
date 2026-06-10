<?php
// 延长 Session 有效期至 30 天（与持久登录保持一致）
ini_set('session.gc_maxlifetime', 30 * 24 * 3600);

// ========== 持久登录配置 ==========
define('REMEMBER_SECRET', 'cdptsjm-persistent-login-secret-2026');
define('REMEMBER_DAYS', 30);

function generateRememberToken($username, $password, $expires) {
    return hash_hmac('sha256', $username . '|' . $password . '|' . $expires, REMEMBER_SECRET);
}

function validateRememberCookie($users) {
    if (empty($_COOKIE['remember_auth'])) return false;
    $parts = explode(':', base64_decode($_COOKIE['remember_auth']));
    if (count($parts) !== 3) return false;
    list($username, $expires, $token) = $parts;
    if ($expires < time()) return false;
    foreach ($users as $user) {
        if ($user['email'] === $username) {
            $expected = generateRememberToken($username, $user['key'], $expires);
            if (hash_equals($expected, $token)) {
                return $user;
            }
        }
    }
    return false;
}
// ==================================

// 先读取用户数据，用于判断是否有持久登录
$users = json_decode(file_get_contents('data/user.json'), true);
$userFromCookie = validateRememberCookie($users);

// 设置 Session Cookie 参数
// 如果存在有效的 remember_auth，则使用长期 Cookie；否则使用会话级 Cookie（浏览器关闭即失效）
$sessionLifetime = $userFromCookie ? (30 * 24 * 3600) : 0;

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
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

// 未登录时，尝试用持久 Cookie 自动恢复
if ($userFromCookie) {
    if (isset($userFromCookie['status']) && $userFromCookie['status'] == 1) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $userFromCookie['email'];
        // 恢复会话后，确保 Session Cookie 也是长期的（因为用户选择了记住我）
        // 注意：session_set_cookie_params 只在 session_start() 前有效，这里需要重新生成会话ID并设置Cookie
        session_regenerate_id(true);
        setcookie(session_name(), session_id(), [
            'expires' => time() + 30 * 24 * 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        header('Location: index.php');
        exit;
    } else {
        // Cookie 有效但用户已被禁用，清除 Cookie
        setcookie('remember_auth', '', time() - 3600, '/');
    }
}

$error = '';

// ========== 应用登录（GET 参数方式）==========
// 供 Android WebView 应用调用，通过 URL 参数直接登录
$appUsername = $_GET['usern'] ?? '';
$appPassword = $_GET['pwd'] ?? '';

if (!empty($appUsername) && !empty($appPassword)) {
    // 应用登录模式：跳过 CSRF 验证，直接验证凭据
    $valid = false;
    $userFound = null;
    
    foreach ($users as $user) {
        if ($user['email'] === $appUsername && $user['key'] === $appPassword) {
            $userFound = $user;
            $valid = true;
            break;
        }
    }
    
    if ($valid) {
        if (isset($userFound['status']) && $userFound['status'] == 1) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $appUsername;
            
            // 应用登录默认记住我，签发持久化 Cookie
            $expires = time() + REMEMBER_DAYS * 24 * 3600;
            $authToken = generateRememberToken($appUsername, $userFound['key'], $expires);
            $cookieValue = base64_encode($appUsername . ':' . $expires . ':' . $authToken);
            setcookie('remember_auth', $cookieValue, [
                'expires' => $expires,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // 设置长期 Session Cookie（30天）
            setcookie(session_name(), session_id(), [
                'expires' => time() + 30 * 24 * 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // 应用登录标记 Cookie（用于区分应用登录和网页登录）
            setcookie('app_login', '1', [
                'expires' => $expires,
                'path' => '/',
                'secure' => true,
                'httponly' => false,  // 允许 JavaScript 读取
                'samesite' => 'Strict'
            ]);
            
            header('Location: index.php');
            exit;
        } else {
            // 应用登录：账号被禁用，返回 JSON
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => Lang::get('account_disabled', $lang) ?? '用户已被禁用']);
            exit;
        }
    } else {
        // 应用登录：用户名或密码错误，返回 JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => Lang::get('login_error', $lang) ?? '用户名或密码错误']);
        exit;
    }
}
// ==================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
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
            if (isset($userFound['status']) && $userFound['status'] == 1) {
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = $username;
                
                // 勾选"记住我"时，签发持久化 Cookie 并设置长期 Session
                if (!empty($_POST['remember'])) {
                    $expires = time() + REMEMBER_DAYS * 24 * 3600;
                    $authToken = generateRememberToken($username, $userFound['key'], $expires);
                    $cookieValue = base64_encode($username . ':' . $expires . ':' . $authToken);
                    setcookie('remember_auth', $cookieValue, [
                        'expires' => $expires,
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    
                    // 同时设置长期 Session Cookie（30天）
                    setcookie(session_name(), session_id(), [
                        'expires' => time() + 30 * 24 * 3600,
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                } else {
                    // 未勾选"记住我"：清除旧持久化 Cookie
                    setcookie('remember_auth', '', time() - 3600, '/');
                    
                    // 设置会话级 Session Cookie（浏览器关闭即失效）
                    // 由于 session_start() 已经调用，需要手动覆盖 Cookie
                    setcookie(session_name(), session_id(), [
                        'expires' => 0,  // 0 表示浏览器会话级别
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }
                
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
<<article class="container-fluid">
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
                                                 <input type="checkbox" id="remember" name="remember" checked /> <?php echo Lang::get('remember_me', $lang); ?>
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
    window.onload = function() {
        const uname = $.cookie('uname');
        if (uname) {
            document.getElementById('uname').value = uname;
            document.getElementById('remember').checked = true;
        }

        // ========== 应用退出通知 ==========
        // 检测是否为应用登录后的退出（URL 带 app_logout=1 参数）
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('app_logout') === '1') {
            // 通知 Android 应用清除本地 XML 登录信息
            if (typeof AndroidApp !== 'undefined' && AndroidApp.onAppLogout) {
                AndroidApp.onAppLogout();
            }
        }
        // ==================================
    };

    document.getElementById('login-form').addEventListener('submit', function() {
        if (document.getElementById('remember').checked) {
            $.cookie('uname', document.getElementById('uname').value, { expires: 7, secure: true, sameSite: 'strict' });
        } else {
            $.removeCookie('uname');
        }
    });
</script>
</body>
</html>
