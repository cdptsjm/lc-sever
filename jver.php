<?php
header('Content-Type: application/json; charset=utf-8');

/* 兼容原客户端参数校验 */

/* 读 JSON */
$file = __DIR__ . '/data.json';
if (!file_exists($file)) {
    exit(json_encode(['code' => 0, 'data' => []])); // 没有可访问网址
}
$data = json_decode(file_get_contents($file), true);
if (!$data) {
    exit(json_encode(['code' => 0, 'data' => []]));
}

/* 正常返回 */
echo json_encode([
    'code' => 0,
    'data' => $data
]);