<?php
/**
 * API 入口文件 - 多线程上传修复版
 * 参考 upload.js 实现大文件分片并发上传
 */
session_start();
ini_set('upload_max_filesize', '4096M');
ini_set('post_max_size', '4096M');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');
ini_set('memory_limit', '512M');
header('Content-Type: application/json; charset=utf-8');

// ==================== 日志类 ====================
class Logger {
    private static $logFile = 'data/logs/openlist_proxy.log';
    
    public static function clear() {
        Utils::ensureDirectory(dirname(self::$logFile));
        file_put_contents(self::$logFile, '');
    }
    
    public static function write($section, $data) {
        $timestamp = date('Y-m-d H:i:s');
        $separator = str_repeat('=', 60);
        $logContent = PHP_EOL . $separator . PHP_EOL;
        $logContent .= "[{$timestamp}] {$section}" . PHP_EOL;
        $logContent .= $separator . PHP_EOL;
        $logContent .= is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $data;
        $logContent .= PHP_EOL;
        file_put_contents(self::$logFile, $logContent, FILE_APPEND | LOCK_EX);
    }
}

// ==================== 配置 ====================
class Config {
    const OPENLIST_BASE_URL = 'https://162.14.113.207:5245';
    const OPENLIST_USERNAME = 'admin';
    const OPENLIST_PASSWORD = '123123123kik';
    const OPENLIST_UPLOAD_PATH = '/main/appstore/';
    const USE_BEARER_PREFIX = false;
    
    const DATA_DIR = 'data/';
    const ICONS_DIR = self::DATA_DIR . 'icons/';
    const MAX_UPLOAD_SIZE = 500 * 1024 * 1024;
    const ALLOWED_ICON_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    // 多线程上传配置（参考 upload.js）
    const UPLOAD_CHUNK_SIZE = 10 * 1024 * 1024; // 10MB 分片
    const UPLOAD_CONCURRENCY = 3; // 并发数（与 upload.js 的 HA(3, ...) 一致）
}

// ==================== 工具 ====================
class Utils {
    public static function jsonResponse($success, $data = [], $message = '') {
        $response = ['success' => (bool)$success];
        if (!$success && !empty($message)) $response['message'] = $message;
        if ($success && !empty($data)) $response = array_merge($response, $data);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function getRequestData() {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'multipart/form-data') !== false) return $_POST;
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    public static function getAction($input) {
        return $_GET['action'] ?? $input['action'] ?? '';
    }
    
    public static function ensureDirectory($path) {
        if (!is_dir($path)) mkdir($path, 0755, true);
    }
    
    public static function saveJson($f, $d) {
        self::ensureDirectory(Config::DATA_DIR);
        return file_put_contents(Config::DATA_DIR . $f, 
            json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    public static function loadJson($f, $default = []) {
        $p = Config::DATA_DIR . $f;
        if (!file_exists($p)) return $default;
        $d = json_decode(file_get_contents($p), true);
        return $d ?? $default;
    }
    
    public static function generateSafeFilename($n) {
        $t = time();
        $f = pathinfo($n, PATHINFO_FILENAME);
        $e = pathinfo($n, PATHINFO_EXTENSION);
        $s = preg_replace('/[^\p{L}\p{N}._-]/u', '_', $f);
        return substr($t . '_' . $s, 0, 60) . '.' . $e;
    }
    
    public static function getExtension($f) {
        return strtolower(pathinfo($f, PATHINFO_EXTENSION));
    }
    
    public static function calculateFileHash($filepath, $algo = 'md5') {
        return hash_file($algo, $filepath);
    }
}

// ==================== OpenList 服务 ====================
class OpenListService {
    private $token = null;
    private $tokenExpiry = 0;
    
    private function getAuthHeader() {
        if (Config::USE_BEARER_PREFIX) {
            return 'Authorization: Bearer ' . $this->token;
        } else {
            return 'Authorization: ' . $this->token;
        }
    }
    
    public function authenticate() {
        $url = Config::OPENLIST_BASE_URL . '/api/auth/login';
        $data = json_encode([
            'username' => Config::OPENLIST_USERNAME,
            'password' => Config::OPENLIST_PASSWORD
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        Logger::write("AUTH", ['code' => $code]);
        
        if ($code === 200) {
            $r = json_decode($body, true);
            if (isset($r['code']) && $r['code'] === 200) {
                $this->token = $r['data']['token'] ?? null;
                $this->tokenExpiry = time() + 3000;
                Logger::write("TOKEN OK", ['preview' => substr($this->token, 0, 30) . '...']);
                return $this->token;
            }
        }
        return null;
    }
    
    private function ensureToken() {
        if (!$this->token || time() > $this->tokenExpiry) {
            return $this->authenticate();
        }
        return $this->token;
    }
    
    /**
     * 标准上传（自动判断使用单线程或多线程）
     */
    public function uploadFile($tmpPath, $filename, $mimeType, $fileSize) {
        return $this->uploadFileWithProgress($tmpPath, $filename, $mimeType, $fileSize);
    }
    
    /**
     * 上传到 OpenList（带进度回调，参考 upload.js 实现多线程分片）
     * 小文件：单线程流式上传
     * 大文件：多线程分片并发上传（类似 upload.js 的 cI 函数）
     */
    public function uploadFileWithProgress($tmpPath, $filename, $mimeType, $fileSize, $progressCallback = null) {
        if (!$this->ensureToken()) {
            return ['success' => false, 'error' => 'Authentication failed'];
        }
        
        $targetPath = Config::OPENLIST_UPLOAD_PATH . $filename;
        if (strpos($targetPath, '/') !== 0) {
            $targetPath = '/' . $targetPath;
        }
        
        // 计算文件哈希（用于快速上传验证，参考 upload.js 的 rapid upload）
        $md5 = Utils::calculateFileHash($tmpPath, 'md5');
        $sha1 = Utils::calculateFileHash($tmpPath, 'sha1');
        
        // 小文件使用单线程上传（保持原有逻辑）
        if ($fileSize <= Config::UPLOAD_CHUNK_SIZE) {
            Logger::write("UPLOAD_SINGLE", ['size' => $fileSize, 'file' => $filename]);
            return $this->uploadSingleFile($tmpPath, $targetPath, $fileSize, $md5, $sha1, $progressCallback);
        }
        
        // 大文件使用多线程分片上传（参考 upload.js 的 cI 函数）
        Logger::write("UPLOAD_MULTI", [
            'size' => $fileSize, 
            'chunks' => ceil($fileSize / Config::UPLOAD_CHUNK_SIZE),
            'concurrency' => Config::UPLOAD_CONCURRENCY
        ]);
        
        return $this->uploadChunksConcurrently(
            $tmpPath, 
            $targetPath, 
            $fileSize, 
            Config::UPLOAD_CHUNK_SIZE, 
            Config::UPLOAD_CONCURRENCY,
            $md5,
            $sha1,
            $progressCallback
        );
    }
    
    /**
     * 单文件流式上传（小文件使用，保持原有逻辑）
     */
    private function uploadSingleFile($tmpPath, $targetPath, $fileSize, $md5, $sha1, $progressCallback) {
        $fp = fopen($tmpPath, 'rb');
        if (!$fp) {
            return ['success' => false, 'error' => 'Cannot read file'];
        }
        
        $url = Config::OPENLIST_BASE_URL . '/api/fs/put';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
        
        $headers = [
            $this->getAuthHeader(),
            'File-Path: ' . $targetPath,
            'Content-Type: application/octet-stream',
            'Overwrite: true',
            'X-File-Md5: ' . $md5,
            'X-File-Sha1: ' . $sha1,
        ];
        
        // 如果提供了进度回调，使用 READFUNCTION 来跟踪进度
        if ($progressCallback && is_callable($progressCallback)) {
            $uploaded = 0;
            
            curl_setopt($ch, CURLOPT_READFUNCTION, function($ch, $fd, $length) use (&$uploaded, $fileSize, $progressCallback) {
                $data = fread($fd, $length);
                if ($data !== false && $data !== '') {
                    $uploaded += strlen($data);
                    if ($fileSize > 0) {
                        $percent = min(100, round(($uploaded / $fileSize) * 100));
                        $progressCallback($percent, $uploaded, $fileSize);
                    }
                }
                return $data;
            });
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fp);
        
        Logger::write("UPLOAD_SINGLE_RESPONSE", ['code' => $httpCode, 'error' => $error]);
        
        $data = json_decode($body, true);
        if ($httpCode === 200 && isset($data['code']) && $data['code'] === 200) {
            return ['success' => true, 'data' => $data['data'] ?? null];
        }
        
        return ['success' => false, 'error' => $data['message'] ?? "HTTP {$httpCode}: {$error}"];
    }
    
    /**
     * 多线程分片并发上传（参考 upload.js 的 cI 函数）
     * 使用 curl_multi 实现并发控制，类似 upload.js 的 HA(3, ...)
     */
    private function uploadChunksConcurrently($tmpPath, $targetPath, $fileSize, $chunkSize, $concurrency, $md5, $sha1, $progressCallback) {
        $totalChunks = (int)ceil($fileSize / $chunkSize);
        $multiHandle = curl_multi_init();
        $activeHandles = []; // 存储当前运行的 handle 信息
        $completedChunks = 0;
        $uploadedBytes = 0;
        $chunkIndex = 0;
        $hasError = false;
        $errorMsg = '';
        
        // 准备分片信息数组（类似 upload.js 中的分片准备）
        $chunks = [];
        for ($i = 0; $i < $totalChunks; $i++) {
            $start = $i * $chunkSize;
            $length = min($chunkSize, $fileSize - $start);
            $chunks[] = [
                'index' => $i,
                'start' => $start,
                'length' => $length,
                'uploaded' => 0
            ];
        }
        
        Logger::write("MULTI_INIT", [
            'totalChunks' => $totalChunks,
            'concurrency' => $concurrency,
            'fileSize' => $fileSize
        ]);
        
        // 初始添加前 $concurrency 个分片（类似 upload.js 的 HA 并发控制）
        for ($i = 0; $i < $concurrency && $i < $totalChunks; $i++) {
            if (!$this->addChunkHandle($multiHandle, $tmpPath, $targetPath, $chunks[$i], $activeHandles, $fileSize)) {
                $hasError = true;
                $errorMsg = "Failed to initialize chunk $i";
                break;
            }
            $chunkIndex++;
        }
        
        if ($hasError) {
            curl_multi_close($multiHandle);
            return ['success' => false, 'error' => $errorMsg];
        }
        
        // 主循环：执行并发上传（参考 upload.js 的 Promise.all 逻辑）
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle, 0.1); // 100ms 超时，快速检查状态
            
            // 检查完成的请求
            while ($done = curl_multi_info_read($multiHandle)) {
                $handle = $done['handle'];
                $handleId = (int)$handle;
                
                if (!isset($activeHandles[$handleId])) continue;
                
                $chunkInfo = $activeHandles[$handleId];
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $curlError = curl_error($handle);
                
                // 关闭该分片的文件指针
                if (isset($chunkInfo['fp']) && is_resource($chunkInfo['fp'])) {
                    fclose($chunkInfo['fp']);
                }
                
                // 检查上传结果
                if ($done['result'] !== CURLE_OK) {
                    $hasError = true;
                    $errorMsg = "Chunk {$chunkInfo['index']} CURL error: $curlError";
                    Logger::write("CHUNK_CURL_ERROR", [
                        'chunk' => $chunkInfo['index'],
                        'error' => $curlError
                    ]);
                } elseif ($httpCode !== 200 && $httpCode !== 204 && $httpCode !== 201) {
                    $hasError = true;
                    $errorMsg = "Chunk {$chunkInfo['index']} HTTP error: $httpCode";
                    Logger::write("CHUNK_HTTP_ERROR", [
                        'chunk' => $chunkInfo['index'],
                        'httpCode' => $httpCode
                    ]);
                } else {
                    // 分片上传成功
                    $completedChunks++;
                    $uploadedBytes += $chunkInfo['length'];
                    $chunks[$chunkInfo['index']]['uploaded'] = $chunkInfo['length'];
                    
                    Logger::write("CHUNK_SUCCESS", [
                        'chunk' => $chunkInfo['index'],
                        'completed' => $completedChunks,
                        'total' => $totalChunks,
                        'bytes' => $uploadedBytes
                    ]);
                    
                    // 调用进度回调（汇总所有分片进度）
                    if ($progressCallback && is_callable($progressCallback)) {
                        $percent = min(100, round(($uploadedBytes / $fileSize) * 100));
                        // 计算速度（简化计算，实际可加入时间统计）
                        $progressCallback($percent, $uploadedBytes, $fileSize);
                    }
                }
                
                // 移除已完成的 handle
                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);
                unset($activeHandles[$handleId]);
                
                // 如果出错了，停止添加新任务，等待现有任务完成
                if ($hasError) {
                    break;
                }
                
                // 添加下一个分片（保持并发数恒定，类似 upload.js 的 HA 控制）
                if ($chunkIndex < $totalChunks) {
                    if (!$this->addChunkHandle($multiHandle, $tmpPath, $targetPath, $chunks[$chunkIndex], $activeHandles, $fileSize)) {
                        $hasError = true;
                        $errorMsg = "Failed to add chunk $chunkIndex";
                        break;
                    }
                    $chunkIndex++;
                }
            }
            
        } while (($running > 0 || count($activeHandles) > 0) && !$hasError);
        
        // 清理所有资源
        foreach ($activeHandles as $info) {
            if (isset($info['fp']) && is_resource($info['fp'])) {
                fclose($info['fp']);
            }
        }
        curl_multi_close($multiHandle);
        
        // 返回结果
        if ($hasError) {
            return ['success' => false, 'error' => $errorMsg];
        }
        
        if ($completedChunks === $totalChunks) {
            Logger::write("MULTI_COMPLETE", ['totalBytes' => $uploadedBytes]);
            // 分片上传完成后，可能需要调用 API 合并分片（如果 OpenList 需要）
            // 目前假设 OpenList 支持自动合并（类似 upload.js 的直接追加逻辑）
            return [
                'success' => true, 
                'data' => [
                    'path' => $targetPath,
                    'size' => $fileSize,
                    'chunks' => $totalChunks
                ]
            ];
        } else {
            return [
                'success' => false, 
                'error' => "Incomplete upload: $completedChunks / $totalChunks chunks completed"
            ];
        }
    }
    
    /**
     * 添加单个分片到 curl_multi（类似 upload.js 的分片上传逻辑）
     * 使用 Content-Range 头部标识分片位置
     */
    private function addChunkHandle($multiHandle, $filePath, $targetPath, $chunk, &$activeHandles, $totalSize) {
        // 为每个分片打开独立的文件指针（避免并发冲突）
        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            Logger::write("CHUNK_FOPEN_ERROR", ['chunk' => $chunk['index'], 'path' => $filePath]);
            return false;
        }
        
        // 定位到分片起始位置
        if (fseek($fp, $chunk['start']) !== 0) {
            Logger::write("CHUNK_SEEK_ERROR", ['chunk' => $chunk['index'], 'start' => $chunk['start']]);
            fclose($fp);
            return false;
        }
        
        $url = Config::OPENLIST_BASE_URL . '/api/fs/put';
        $ch = curl_init();
        
        // 计算 Content-Range（参考 upload.js：bytes start-end/total）
        $end = $chunk['start'] + $chunk['length'] - 1;
        $contentRange = "bytes {$chunk['start']}-{$end}/{$totalSize}";
        
        $headers = [
            $this->getAuthHeader(),
            'File-Path: ' . $targetPath,
            'Content-Type: application/octet-stream',
            'Content-Range: ' . $contentRange,
            // 只有第一个分片设置 Overwrite: true，后续追加
            'Overwrite: ' . ($chunk['index'] === 0 ? 'true' : 'false')
        ];
        
        // 使用 READFUNCTION 精确控制读取长度（防止读取超出分片边界）
        $remaining = $chunk['length'];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_INFILE => $fp,
            CURLOPT_INFILESIZE => $chunk['length'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_READFUNCTION => function($ch, $fd, $length) use (&$remaining) {
                if ($remaining <= 0) {
                    return ''; // 已读完本分片
                }
                $toRead = min($length, $remaining);
                $data = fread($fd, $toRead);
                $readLen = strlen($data);
                $remaining -= $readLen;
                return $data;
            }
        ]);
        
        $handleId = (int)$ch;
        $activeHandles[$handleId] = [
            'index' => $chunk['index'],
            'fp' => $fp,          // 保存指针以便后续关闭
            'length' => $chunk['length'],
            'start' => $chunk['start']
        ];
        
        curl_multi_add_handle($multiHandle, $ch);
        
        Logger::write("CHUNK_ADDED", [
            'chunk' => $chunk['index'],
            'range' => $contentRange,
            'length' => $chunk['length']
        ]);
        
        return true;
    }
    
    public function getFileInfo($filePath) {
        if (!$this->ensureToken()) return null;
        
        $url = Config::OPENLIST_BASE_URL . '/api/fs/get';
        $data = json_encode(['path' => $filePath, 'password' => '']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $this->getAuthHeader(),
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code === 200) {
            return json_decode($body, true);
        }
        return null;
    }
    
    public function buildDownloadUrl($filePath, $sign) {
        return Config::OPENLIST_BASE_URL . '/d' . $filePath . '?sign=' . urlencode($sign);
    }
}

// ==================== 业务处理 ====================
class ApiHandler {
    private $openlist;
    
    public function handleGetBookmarks() {
    $bookmarks = Utils::loadJson('lsllq.json', []);
    Utils::jsonResponse(true, ['bookmarks' => $bookmarks]);
}
    
    public function handleSaveBookmarks($input) {
    $bookmarks = $input['bookmarks'] ?? [];
    if (!is_array($bookmarks)) {
        Utils::jsonResponse(false, [], 'Invalid data format');
    }
    
    $filePath = Config::DATA_DIR . 'lsllq.json';
    Utils::ensureDirectory(Config::DATA_DIR);
    
    $result = file_put_contents($filePath, json_encode($bookmarks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result !== false) {
        Utils::jsonResponse(true);
    } else {
        Utils::jsonResponse(false, [], 'Failed to save bookmarks');
    }
}
    
    public function __construct() {
        $this->openlist = new OpenListService();
    }
    
    public function handleSave($type, $input) {
        $map = [
            'save_app' => ['f' => 'app.json', 'k' => 'data'],
            'save_device' => ['f' => 'deictv.json', 'k' => 'data'],
            'save_user' => ['f' => 'user.json', 'k' => 'data'],
            'save_link' => ['f' => 'link.json', 'k' => 'link']
        ];
        if (!isset($map[$type])) Utils::jsonResponse(false, [], 'Unknown');
        $cfg = $map[$type];
        $d = $input[$cfg['k']] ?? null;
        if ($d === null) Utils::jsonResponse(false, [], 'Missing data');
        Utils::jsonResponse(Utils::saveJson($cfg['f'], $d));
    }
    
    public function handleGetAll() {
    $files = ['app' => 'app.json', 'device' => 'deictv.json', 'user' => 'user.json', 
             'link' => 'link.json', 'advertise' => 'advertise.json', 
             'appgroup' => 'appgroups.json', 'limit' => 'limit.json'];
    $result = ['success' => true];
    foreach ($files as $k => $f) {
        $d = $k === 'advertise' ? ['total' => 0, 'advertises' => []] : ($k === 'appgroup' ? [] : []);
        $result[$k] = Utils::loadJson($f, $d);
    }
    
    // 添加书签数据 - 确保始终返回数组，即使文件不存在
    $bookmarksFile = Config::DATA_DIR . 'lsllq.json';
    if (file_exists($bookmarksFile)) {
        $bookmarks = Utils::loadJson('lsllq.json', []);
        $result['bookmarks'] = is_array($bookmarks) ? $bookmarks : [];
    } else {
        // 文件不存在时返回空数组，不返回 null
        $result['bookmarks'] = [];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
    
   public function handleSaveAll($input) {
    $map = ['app' => 'app.json', 'device' => 'deictv.json', 'user' => 'user.json',
           'link' => 'link.json', 'advertise' => 'advertise.json', 
           'appgroup' => 'appgroups.json', 'limit' => 'limit.json'];
    $ok = true;
    foreach ($map as $k => $f) {
        if (isset($input[$k]) && !Utils::saveJson($f, $input[$k])) {
            $ok = false;
            error_log("Failed to save {$f}");
        }
    }
    
    // 保存书签数据 - 添加日志
    if (isset($input['bookmarks'])) {
        $bookmarks = is_array($input['bookmarks']) ? $input['bookmarks'] : [];
        error_log("Saving bookmarks count: " . count($bookmarks));
        if (!Utils::saveJson('lsllq.json', $bookmarks)) {
            $ok = false;
            error_log("Failed to save bookmarks");
        }
    } else {
        // 如果请求中没有 bookmarks 字段，记录日志但不保存
        error_log("No bookmarks field in save request, skipping bookmark save");
    }
    
    Utils::jsonResponse($ok);
}
    
    public function handleGetOpenListToken() {
    $token = $this->openlist->authenticate();
    if ($token) {
        // 改为嵌套在 data 中，与 get_file_info 保持一致
        Utils::jsonResponse(true, [
            'data' => [
                'token' => $token,
                'base_url' => Config::OPENLIST_BASE_URL,
                'upload_path' => Config::OPENLIST_UPLOAD_PATH,
                'chunk_size' => 10 * 1024 * 1024,
                'concurrency' => 3
            ]
        ]);
    } else {
        Utils::jsonResponse(false, [], 'Authentication failed');
    }
}
    
    /**
     * 获取文件信息和下载链接（上传完成后调用）
     */
    public function handleGetFileInfo($input) {
    $path = $input['path'] ?? '';
    if (empty($path)) {
        Utils::jsonResponse(false, [], 'Path required');
    }
    
    // 添加调试日志
    Logger::write("GET_FILE_INFO", ['path' => $path]);
    
    $info = $this->openlist->getFileInfo($path);
    
    if ($info && isset($info['data']) && isset($info['data']['sign'])) {
        $url = $this->openlist->buildDownloadUrl($path, $info['data']['sign']);
        
        // 确保返回格式一致（url 在根级别）
        Utils::jsonResponse(true, [
            'url' => $url,
            'path' => $path,
            'sign' => $info['data']['sign'],
            'info' => $info['data']
        ]);
    } else {
        // 文件可能还在索引中，返回特定错误码便于前端重试
        Utils::jsonResponse(false, [
            'path' => $path,
            'raw_response' => $info  // 调试信息
        ], 'File not found or not ready');
    }
}
    
    /**
     * 流式上传接口（支持真实进度，自动使用多线程分片上传大文件）
     * 参考 upload.js 实现，小文件单线程，大文件（>10MB）自动分片并发上传
     */
    public function handleProxyUpload() {
        Logger::clear();
        
        if (!isset($_FILES['file'])) {
            Utils::jsonResponse(false, [], 'No file uploaded');
        }
        
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Utils::jsonResponse(false, [], 'Upload error: ' . $file['error']);
        }
        
        $safeName = Utils::generateSafeFilename($file['name']);
        $tmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // 设置流式输出头（用于进度通知）
        header('Content-Type: application/x-ndjson');
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        // 阶段 1：本地接收完成
        echo json_encode([
            'stage' => 'local',
            'progress' => 100,
            'message' => 'Local received, uploading to cloud...'
        ]) . "\n";
        flush();
        
        // 阶段 2：上传到 OpenList（自动判断单/多线程）
        $lastPercent = 0;
        $startTime = microtime(true);
        
        // 判断是否为多线程上传（用于前端显示）
        $isMultiThread = $fileSize > Config::UPLOAD_CHUNK_SIZE;
        if ($isMultiThread) {
            echo json_encode([
                'stage' => 'prepare',
                'progress' => 0,
                'message' => 'Using multi-thread upload (' . Config::UPLOAD_CONCURRENCY . ' threads)...',
                'chunks' => ceil($fileSize / Config::UPLOAD_CHUNK_SIZE)
            ]) . "\n";
            flush();
        }
        
        $result = $this->openlist->uploadFileWithProgress(
            $tmpPath,
            $safeName,
            $file['type'],
            $fileSize,
            function($percent, $uploaded, $total) use (&$lastPercent, $startTime, $isMultiThread) {
                // 每 1% 或每秒发送一次进度（避免过于频繁）
                $now = microtime(true);
                $timeElapsed = $now - $startTime;
                
                if ($percent - $lastPercent >= 1 || $timeElapsed >= 1 || $percent === 100) {
                    $speed = ($timeElapsed > 0) ? round(($uploaded / 1024 / 1024) / $timeElapsed, 2) : 0;
                    
                    echo json_encode([
                        'stage' => 'cloud',
                        'progress' => $percent,
                        'uploaded' => round($uploaded / 1024 / 1024, 2) . ' MB',
                        'total' => round($total / 1024 / 1024, 2) . ' MB',
                        'speed' => $speed . ' MB/s',
                        'multi_thread' => $isMultiThread
                    ]) . "\n";
                    flush();
                    
                    $lastPercent = $percent;
                }
            }
        );
        
        // 阶段 3：结果
        if ($result['success']) {
            $filePath = Config::OPENLIST_UPLOAD_PATH . $safeName;
            $info = $this->openlist->getFileInfo($filePath);
            $url = Config::OPENLIST_BASE_URL . '/d' . $filePath;
            if ($info && isset($info['data']['sign'])) {
                $url = $this->openlist->buildDownloadUrl($filePath, $info['data']['sign']);
            }
            
            echo json_encode([
                'stage' => 'complete',
                'success' => true,
                'data' => [
                    'url' => $url,
                    'filename' => $safeName,
                    'path' => $filePath,
                    'size' => $fileSize,
                    'multi_thread' => $isMultiThread ?? false
                ]
            ]);
        } else {
            echo json_encode([
                'stage' => 'error',
                'success' => false,
                'message' => $result['error']
            ]);
        }
        
        exit;
    }
    
    public function handleUploadIcon() {
        if (!isset($_FILES['icon'])) Utils::jsonResponse(false, [], 'No file');
        $file = $_FILES['icon'];
        if ($file['error'] !== UPLOAD_ERR_OK) Utils::jsonResponse(false, [], 'Error');
        
        $ext = Utils::getExtension($file['name']);
        if (!in_array($ext, Config::ALLOWED_ICON_EXTENSIONS)) {
            Utils::jsonResponse(false, [], 'Invalid type');
        }
        if ($ext !== 'svg' && !getimagesize($file['tmp_name'])) {
            Utils::jsonResponse(false, [], 'Invalid image');
        }
        
        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        Utils::ensureDirectory(Config::ICONS_DIR);
        if (!move_uploaded_file($file['tmp_name'], Config::ICONS_DIR . $name)) {
            Utils::jsonResponse(false, [], 'Save failed');
        }
        
        Utils::jsonResponse(true, [
            'url' => 'https://162.14.113.207/disk/linspirersever/data/icons/' . $name,
            'filename' => $name
        ]);
    }
}

// ==================== 路由 ====================
class Router {
    private $handler;
    
    public function __construct() {
        $this->handler = new ApiHandler();
    }
    
    public function dispatch() {
        $input = Utils::getRequestData();
        $action = Utils::getAction($input);
        
        $routes = [
            'save_app' => fn() => $this->handler->handleSave('save_app', $input),
            'save_device' => fn() => $this->handler->handleSave('save_device', $input),
            'save_user' => fn() => $this->handler->handleSave('save_user', $input),
            'save_link' => fn() => $this->handler->handleSave('save_link', $input),
            'get_all' => fn() => $this->handler->handleGetAll(),
            'save_all' => fn() => $this->handler->handleSaveAll($input),
            'proxy_upload_openlist' => fn() => $this->handler->handleProxyUpload(), // 保留作为备选
            'upload_icon' => fn() => $this->handler->handleUploadIcon(),
            // 新增接口：
            'get_openlist_token' => fn() => $this->handler->handleGetOpenListToken(),
            'get_file_info' => fn() => $this->handler->handleGetFileInfo($input),
            'get_bookmarks' => fn() => $this->handler->handleGetBookmarks(),
            'save_bookmarks' => fn() => $this->handler->handleSaveBookmarks($input),
        ];
        
        if (isset($routes[$action])) {
            try {
                $routes[$action]();
            } catch (Exception $e) {
                Utils::jsonResponse(false, [], 'Error: ' . $e->getMessage());
            }
        } else {
            Utils::jsonResponse(false, [], 'Unknown: ' . $action);
        }
    }
}

$router = new Router();
$router->dispatch();