<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$logFile = __DIR__ . '/upload.log';

function writeLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if ($data !== null) {
        $logEntry .= "\n" . (is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $data);
    }
    $logEntry .= "\n" . str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// OpenList 配置 - 上传到你的站点
$openListConfig = [
    'baseUrl' => 'http://162.14.113.207:5244',
    'username' => 'admin',
    'password' => '123123123kik',
    'uploadPath' => '/main/appstore/'  // 固定上传到该目录
];

/**
 * 获取 OpenList JWT Token
 */
function getOpenListToken() {
    global $openListConfig;
    $loginUrl = $openListConfig['baseUrl'] . '/api/auth/login';
    
    writeLog("获取 JWT Token", ['url' => $loginUrl]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $loginUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'username' => $openListConfig['username'],
            'password' => $openListConfig['password']
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        writeLog("Token 获取失败(CURL错误)", $error);
        return null;
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['data']['token'])) {
            writeLog("Token 获取成功", substr($result['data']['token'], 0, 30) . '...');
            return $result['data']['token'];
        }
    }
    
    writeLog("Token 获取失败(HTTP $httpCode)", $response);
    return null;
}

/**
 * 获取文件签名（用于生成下载链接）
 */
function getFileSign($filePath, $jwt_token) {
    global $openListConfig;
    $apiUrl = $openListConfig['baseUrl'] . '/api/fs/get';
    
    $postData = json_encode(['path' => $filePath, 'password' => '']);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $jwt_token,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['data']['sign'] ?? null;
}

/**
 * 上传文件到 OpenList - 基于 Upload.js 逻辑
 * 支持策略: stream(流式), form(表单), chunked(分块)
 */
function uploadToOpenList($file, $strategy = 'auto', $options = []) {
    global $openListConfig;
    
    writeLog("开始上传", [
        'filename' => $file['name'],
        'size' => $file['size'],
        'strategy' => $strategy
    ]);
    
    // 1. 获取 Token
    $jwt_token = getOpenListToken();
    if (!$jwt_token) {
        return ['success' => false, 'message' => '无法连接到 OpenList 服务器'];
    }
    
    // 2. 准备文件路径
    $timestamp = time();
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // 清理文件名（移除非ASCII字符避免编码问题）
    $safeName = preg_replace('/[^\x00-\x7F]/u', '_', $originalName);
    if (empty($safeName)) $safeName = 'file';
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeName);
    
    $fileName = $timestamp . '_' . $safeName . '.' . $extension;
    $fullPath = $openListConfig['uploadPath'] . $fileName;
    
    // 3. 根据策略选择上传方式
    $fileSize = $file['size'];
    $useChunked = ($strategy === 'chunked') || ($strategy === 'auto' && $fileSize > 5 * 1024 * 1024);
    
    if ($useChunked && $strategy !== 'stream' && $strategy !== 'form') {
        // 分块上传（大文件）
        $result = uploadChunked($file, $fullPath, $jwt_token, $options);
    } elseif ($strategy === 'form') {
        // 表单上传
        $result = uploadViaForm($file, $fullPath, $jwt_token, $options);
    } else {
        // 默认流式上传（PUT /api/fs/put）
        $result = uploadViaStream($file, $fullPath, $jwt_token, $options);
    }
    
    if (!$result['success']) {
        return $result;
    }
    
    // 4. 获取文件签名生成下载链接
    $sign = getFileSign($fullPath, $jwt_token);
    $downloadUrl = $sign 
        ? $openListConfig['baseUrl'] . '/d' . $fullPath . '?sign=' . urlencode($sign)
        : $openListConfig['baseUrl'] . '/d' . $fullPath;
    
    writeLog("上传完成", ['url' => $downloadUrl]);
    
    return [
        'success' => true,
        'url' => $downloadUrl,
        'filename' => $fileName,
        'path' => $fullPath,
        'size' => $fileSize
    ];
}

/**
 * 流式上传 - 对应 Upload.js 的 BI 函数
 * PUT /api/fs/put
 */
function uploadViaStream($file, $fullPath, $jwt_token, $options) {
    global $openListConfig;
    
    $uploadUrl = $openListConfig['baseUrl'] . '/api/fs/put';
    $fileContent = file_get_contents($file['tmp_name']);
    
    $headers = [
        'Authorization: Bearer ' . $jwt_token,
        'File-Path: ' . $fullPath,
        'Content-Type: application/octet-stream',
        'Content-Length: ' . strlen($fileContent),
        'As-Task: ' . (isset($options['as_task']) && $options['as_task'] ? 'true' : 'false'),
        'Overwrite: ' . (isset($options['overwrite']) && $options['overwrite'] ? 'true' : 'false')
    ];
    
    // 如果启用了极速上传，计算哈希
    if (isset($options['rapid']) && $options['rapid'] && $file['size'] < 10 * 1024 * 1024) {
        $headers[] = 'X-File-Md5: ' . md5_file($file['tmp_name']);
        $headers[] = 'X-File-Sha1: ' . sha1_file($file['tmp_name']);
    }
    
    writeLog("流式上传", ['path' => $fullPath, 'size' => $file['size']]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $uploadUrl,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 300, // 5分钟超时
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => '上传错误: ' . $error];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['code']) && $result['code'] === 200) {
        return ['success' => true];
    }
    
    // 处理 401 错误（Token过期）
    if (isset($result['code']) && $result['code'] === 401) {
        return ['success' => false, 'message' => '认证失败，请重试', 'retry' => true];
    }
    
    return ['success' => false, 'message' => $result['message'] ?? "HTTP $httpCode"];
}

/**
 * 表单上传 - 对应 Upload.js 的 gI 函数
 * POST /api/fs/form
 */
function uploadViaForm($file, $fullPath, $jwt_token, $options) {
    global $openListConfig;
    
    $uploadUrl = $openListConfig['baseUrl'] . '/api/fs/form';
    
    $postData = [
        'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
    ];
    
    $headers = [
        'Authorization: Bearer ' . $jwt_token,
        'File-Path: ' . $fullPath,
        'As-Task: ' . (isset($options['as_task']) && $options['as_task'] ? 'true' : 'false'),
        'Overwrite: ' . (isset($options['overwrite']) && $options['overwrite'] ? 'true' : 'false')
    ];
    
    writeLog("表单上传", ['path' => $fullPath]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $uploadUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['code']) && $result['code'] === 200) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => $result['message'] ?? "HTTP $httpCode"];
}

/**
 * 分块上传 - 对应 Upload.js 的 cI 函数
 * 适合大文件，支持断点续传
 */
function uploadChunked($file, $fullPath, $jwt_token, $options) {
    global $openListConfig;
    
    $chunkSize = $options['chunk_size'] ?? 2 * 1024 * 1024; // 默认2MB
    $fileSize = $file['size'];
    $totalChunks = ceil($fileSize / $chunkSize);
    $uploadId = uniqid('upload_');
    
    writeLog("分块上传开始", [
        'path' => $fullPath,
        'chunks' => $totalChunks,
        'chunk_size' => $chunkSize
    ]);
    
    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) {
        return ['success' => false, 'message' => '无法读取文件'];
    }
    
    for ($i = 0; $i < $totalChunks; $i++) {
        $start = $i * $chunkSize;
        $currentChunkSize = min($chunkSize, $fileSize - $start);
        
        fseek($handle, $start);
        $chunkData = fread($handle, $currentChunkSize);
        
        $headers = [
            'Authorization: Bearer ' . $jwt_token,
            'Content-Range: bytes ' . $start . '-' . ($start + $currentChunkSize - 1) . '/' . $fileSize,
            'File-Path: ' . $fullPath,
            'Upload-Id: ' . $uploadId,
            'Chunk-Index: ' . $i,
            'Total-Chunks: ' . $totalChunks
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $openListConfig['baseUrl'] . '/api/fs/put',
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $chunkData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 && $httpCode !== 206) {
            fclose($handle);
            return ['success' => false, 'message' => "分块 $i 上传失败 (HTTP $httpCode)"];
        }
        
        writeLog("分块上传进度", ['chunk' => $i + 1, 'total' => $totalChunks]);
    }
    
    fclose($handle);
    return ['success' => true, 'chunks' => $totalChunks];
}

// 处理请求
$action = $_POST['action'] ?? '';
writeLog("收到请求", ['action' => $action, 'post' => $_POST, 'files' => $_FILES]);

switch ($action) {
    case 'proxy_upload_openlist':
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => '没有选择文件']);
            break;
        }
        
        $strategy = $_POST['strategy'] ?? 'auto';
        $options = [
            'overwrite' => isset($_POST['overwrite']) && $_POST['overwrite'] === 'true',
            'as_task' => isset($_POST['as_task']) && $_POST['as_task'] === 'true',
            'rapid' => isset($_POST['rapid']) && $_POST['rapid'] === 'true',
            'chunk_size' => isset($_POST['chunk_size']) ? intval($_POST['chunk_size']) : 2097152
        ];
        
        $result = uploadToOpenList($_FILES['file'], $strategy, $options);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}
?>