<?php
// download.php
// 设置响应头
header('Content-Type: text/html; charset=utf-8');

// 获取传入的ID参数
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// JSON文件路径
$jsonFile = './data/link.json';

// 检查文件是否存在
if (!file_exists($jsonFile)) {
    die('错误：配置文件不存在');
}

// 读取并解析JSON
$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

// 检查JSON解析是否成功
if ($data === null) {
    die('错误：配置文件格式错误');
}

// 查找对应ID的URL
$targetUrl = null;
foreach ($data as $item) {
    if (isset($item['id']) && $item['id'] == $id) {
        $targetUrl = $item['url'];
        break;
    }
}

// 如果找到URL，执行跳转
if ($targetUrl !== null && !empty($targetUrl)) {
    // 302临时重定向
    header("Location: " . $targetUrl, true, 302);
    exit;
} else {
    // 未找到或URL为空
    exit;
}
?>