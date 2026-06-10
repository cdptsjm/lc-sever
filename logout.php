<?php
header('X-Content-Type-Options: nosniff');
session_start();

// 检测是否为应用登录
$isAppLogin = isset($_COOKIE['app_login']) && $_COOKIE['app_login'] === '1';

// 总是清除持久化登录 Cookie（无论是否勾选过记住我）
if (isset($_COOKIE['remember_auth'])) {
    setcookie('remember_auth', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// 清除应用登录标记 Cookie
if (isset($_COOKIE['app_login'])) {
    setcookie('app_login', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => false,
        'samesite' => 'Strict'
    ]);
}

// 清除 Session 数据
$_SESSION = array();

// 清除 Session Cookie（使当前会话立即失效）
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_destroy();

// 如果是应用登录，重定向到带 app_logout 标记的登录页，供 Android WebView 拦截处理
if ($isAppLogin) {
    header('Location: login.php?app_logout=1');
} else {
    header('Location: login.php');
}
exit;
