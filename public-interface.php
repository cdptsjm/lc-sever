<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// AES加密类
class AesHelper {
    private static $key = "1191ADF18489D8DA";
    private static $iv = "5E9B755A8B674394";
    
    public static function encrypt($data) {
        $key = str_pad(self::$key, 16, "\0");
        $iv = str_pad(self::$iv, 16, "\0");
        
        $encrypted = openssl_encrypt(
            $data, 
            'AES-128-CBC', 
            $key, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        return base64_encode($encrypted);
    }
    
    public static function decrypt($data) {
        $key = str_pad(self::$key, 16, "\0");
        $iv = str_pad(self::$iv, 16, "\0");
        
        $decrypted = openssl_decrypt(
            base64_decode($data), 
            'AES-128-CBC', 
            $key, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        return $decrypted;
    }
}

// 处理请求
$raw = file_get_contents('php://input');
$req = json_decode($raw, true);
$params = null;

if (isset($req['params'])) {
    try {
        $json = AesHelper::decrypt($req['params']);
        $params = json_decode($json, true);
    } catch (Exception $e) {
        $params = $req['params'];
    }
} else {
    $params = $req;
}

$method = $req['method'] ?? '';

try {
    switch ($method) {
        case 'com.linspirer.user.login':
            userlogin($params);
            break;
        case 'com.linspirer.user.getuserinfo':
            userlogin($params);
            break;
        case 'com.linspirer.device.updateonlinetime':
            updateonlinetime();
            break;
        case 'com.linspirer.control.setdevicecontrol':
            setdevicecontrol();
            break;
        case 'com.linspirer.tactics.gettactics':
            gettactics($params);
            break;
        case 'com.linspirer.app.getapptimelimit':
            getapptimelimit($params);
            break;
        case 'com.linspirer.app.getdisableenableapps':
            getapptimelimit($params);
            break;
        case 'com.linspirer.component.getsetting':
            getsetting($params);
            break;
        case 'com.linspirer.setting.screen':
            getsetting($params);
            break;
        case 'com.linspirer.whitelist.getwhitelist':
            getwhitelist($params);
            break;
        case 'com.linspirer.sburl.getspecialbrowingurl':
            getspecialbrowingurl($params);
            break;
        case 'com.linspirer.common.getsetting':
            commongetsetting($params);
            break;
        case 'com.linspirer.wallpaper.getwallpaper':
            getwallpaper($params);
            break;
        case 'com.linspirer.device.setdevice':
            setdevice($params);
            break;
        case 'com.linspirer.app.getdetail':
            getAppDetail($params);
            break;
        case 'com.linspirer.app.getappgroups':
            getAppGroups();
            break;
        case 'com.linspirer.advertise.getadvertise':
            getAdvertise();
            break;
        case 'com.linspirer.app.getappbyids':
            getAppByIds($params);
            break;
        case 'com.linspirer.app.getlauncerbypackage':
            getlauncerbypackage($params);
            break;
        default:
            // 对于未找到的方法，也返回加密的响应
            $response = ['code' => -25000, 'msg' => 'method not found: ' . $method];
            $encryptedResponse = AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE));
            echo json_encode(['code' =>-25000, 'data' => $response]);
            exit;
    }
} catch (Throwable $e) {
    // 异常情况也返回加密的响应
    $response = ['code' => -25000, 'msg' => $e->getMessage()];
    $encryptedResponse = AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE));
    echo json_encode(['code' => -25000, 'data' => $encryptedResponse]);
    exit;
}

// 登录领创
function userlogin($req) {
    // 从请求参数中获取 email 和 password
    $email = $req['email'] ?? '';
    $password = $req['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $response = [
            'code' => -1,
            'msg' => '邮箱和密码不能为空'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 读取 user.json 文件
    $file = __DIR__ . '/data/user.json';
    if (!file_exists($file)) {
        $response = [
            'code' => -21602,
            'msg' => '用户数据文件不存在'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    $users = json_decode(file_get_contents($file), true);
    if (!is_array($users)) {
        $response = [
            'code' => -1,
            'msg' => '用户数据格式错误'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 查找匹配的用户
    $matchedUser = null;
    foreach ($users as $user) {
        if (isset($user['email']) && $user['email'] === $email) {
            // 验证密码
            if (isset($user['key']) && $user['key'] === $password) {
                $matchedUser = $user;
                break;
            } else {
                $response = [
                    'code' => -21602,
                    'msg' => '密码错误'
                ];
                exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
            }
        }
    }
    
    if ($matchedUser === null) {
        $response = [
            'code' => -21602,
            'msg' => '用户不存在'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 检查用户状态，如果 status = 0 则返回错误
    if (isset($matchedUser['status']) && $matchedUser['status'] == 0) {
        $response = [
            'code' => -25000,
            'data' => '该用户已被管理员禁用！'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 构建返回数据（按你提供的示例格式）
    $response = [
        'code' => 0,
        'type' => 'object',
        'data' => [
            'id' => $matchedUser['id'] ?? 0,
            'school' => $matchedUser['school'] ?? 0,
            'usergroup' => $matchedUser['usergroup'] ?? 0,
            'name' => $matchedUser['name'] ?? '',
            'email' => $matchedUser['email'] ?? '',
            'status' => $matchedUser['status'] ?? 0,
            'free_control' => $matchedUser['free_control'] ?? 0,
            'focus' => $matchedUser['focus'] ?? 0,
            'schoolinfo' => $matchedUser['schoolinfo'] ?? [],
            'groupinfo' => $matchedUser['groupinfo'] ?? []
        ]
    ];
    
    // 加密并返回响应
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}


/**
 * 获取时间
 */
function updateonlinetime() {
    $time = date('Y-m-d H:i:s');
    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type' => 'string',
        'data' => $time
        
        ];
    
    // 整个返回体一起加密
   exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

/**
 * 领创设置设备，直接返回true
 */
function setdevicecontrol() {
    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type' => 'boolean',
        'data' => true
    ];
    
  exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

/**
 * 获取应用,设备策略
 */
function gettactics($req) {
    $email = $req['email'] ?? '';
    
    // 读取 user.json 验证用户状态
    $userFile = __DIR__ . '/data/user.json';
    if (!file_exists($userFile)) {
        $response = [
            'code' => -1,
            'msg' => '用户数据文件不存在'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    $users = json_decode(file_get_contents($userFile), true);
    if (!is_array($users)) {
        $response = [
            'code' => -1,
            'msg' => '用户数据格式错误'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    $foundUser = null;
    foreach ($users as $user) {
        if (isset($user['email']) && $user['email'] === $email) {
            $foundUser = $user;
            break;
        }
    }
    
    // 如果用户不存在，返回错误
    if ($foundUser === null) {
        $response = [
            'code' => -22405,
            'data' => '用户不存在！'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 如果用户 status 不为 1，返回禁用提示
    if (isset($foundUser['status']) && $foundUser['status'] != 1) {
        $response = [
            'code' => -22801,
            'data' => '该用户已被管理员禁用！'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    $file = __DIR__ . '/data/deictv.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'object',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取黑名单应用
function getapptimelimit($req) {
    $file = __DIR__ . '/data/limit.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'array',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取设置禁用项
function getsetting($req) {
    $file = __DIR__ . '/data/setting.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'object',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取白名单网址
function getwhitelist($req) {
    $file = __DIR__ . '/weblist.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'array',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取绿色浏览器网址
function getspecialbrowingurl($req) {
    $file = __DIR__ . '/lsllq.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'array',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}


//获取日常设置
function commongetsetting($req) {
    $file = __DIR__ . '/commonset.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type'  => 'object',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取壁纸
function getwallpaper($req) {
    $file = __DIR__ . '/data/backgroud.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 1,
        'type'  => 'string',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

//获取管控状态
function setdevice() {
    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'type' => 'boolean',
        'data' => true
    ];
    
  exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}
function getAppDetail($req)
{
    // 1. 取参数
    $appid = $req['appid'] ?? null;
    if ($appid === null) {
        $rsp = ['code' => -1, 'msg' => 'appid is required'];
        exit(AesHelper::encrypt(json_encode($rsp, JSON_UNESCAPED_UNICODE)));
    }

    // 2. 读静态文件
    $file = __DIR__ . '/data/app.json';
    if (!file_exists($file)) {
        $rsp = ['code' => -1, 'msg' => 'appd.json not found'];
        exit(AesHelper::encrypt(json_encode($rsp, JSON_UNESCAPED_UNICODE)));
    }
    $list = json_decode(file_get_contents($file), true);
    if (!is_array($list)) {
        $rsp = ['code' => -1, 'msg' => 'appd.json format error'];
        exit(AesHelper::encrypt(json_encode($rsp, JSON_UNESCAPED_UNICODE)));
    }

    // 3. 精准过滤
    $found = null;
    foreach ($list as $item) {
        if (isset($item['id']) && (string)$item['id'] === (string)$appid) {
            $found = $item;
            break;
        }
    }

    // 4. 组装响应并整包加密
    if ($found === null) {
        $rsp = ['code' => -1, 'msg' => 'app not found'];
    } else {
        $rsp = ['code' => 0, 'type' => 'object', 'data' => $found];
    }

    exit(AesHelper::encrypt(json_encode($rsp, JSON_UNESCAPED_UNICODE)));
}



/**
 * 获取分组列表
 */
function getAppGroups() {
    // JSON 文件路径
    $jsonFilePath = __DIR__ . '/data/appgroups.json'; 
    
    // 检查文件是否存在
    if (!file_exists($jsonFilePath)) {
        $errorResponse = [
            'code' => 1,
            'message' => '配置文件不存在'
        ];
        exit(AesHelper::encrypt(json_encode($errorResponse, JSON_UNESCAPED_UNICODE)));
    }
    
    // 读取 JSON 文件内容
    $jsonContent = file_get_contents($jsonFilePath);
    
    // 解析 JSON
    $data = json_decode($jsonContent, true);
    
    // 检查 JSON 解析是否成功
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $errorResponse = [
            'code' => 2,
            'message' => 'JSON 格式错误: ' . json_last_error_msg()
        ];
        exit(AesHelper::encrypt(json_encode($errorResponse, JSON_UNESCAPED_UNICODE)));
    }
    
    // 构建返回体
    $response = [
        'code' => 0,
        'type' => 'array',
        'data' => $data
    ];
    
    // 加密并输出
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

/**
 * 获取广告位列表
 */
function getAdvertise() {
    // JSON 文件路径
    $jsonFilePath = __DIR__ . '/data/advertise.json'; 
    
    // 检查文件是否存在
    if (!file_exists($jsonFilePath)) {
        $errorResponse = [
            'code' => 1,
            'message' => '配置文件不存在'
        ];
        exit(AesHelper::encrypt(json_encode($errorResponse, JSON_UNESCAPED_UNICODE)));
    }
    
    // 读取 JSON 文件内容
    $jsonContent = file_get_contents($jsonFilePath);
    
    // 解析 JSON
    $data = json_decode($jsonContent, true);
    
    // 检查 JSON 解析是否成功
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $errorResponse = [
            'code' => 2,
            'message' => 'JSON 格式错误: ' . json_last_error_msg()
        ];
        exit(AesHelper::encrypt(json_encode($errorResponse, JSON_UNESCAPED_UNICODE)));
    }
    
    // 构建返回体
    $response = [
        'code' => 0,
        'type' => 'object',
        'data' => $data
    ];
    
    // 加密并输出
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

/**
 * 根据包名列表获取应用详情
 */
function getlauncerbypackage($req) {
    // 1. 获取参数
    $packages = $req['packages'] ?? [];
    $model = $req['model'] ?? '';
    
    // 验证参数
    if (empty($packages) || !is_array($packages)) {
        $response = [
            'code' => -25000,
            'msg' => 'packages参数不能为空且必须为数组'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 2. 读取应用数据文件
    $file = __DIR__ . '/data/app.json';
    if (!file_exists($file)) {
        $response = [
            'code' => -25000,
            'msg' => '应用数据文件不存在'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    $apps = json_decode(file_get_contents($file), true);
    if (!is_array($apps)) {
        $response = [
            'code' => -25000,
            'msg' => '应用数据格式错误'
        ];
        exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
    }
    
    // 3. 根据包名筛选应用
    $result = [];
    foreach ($apps as $app) {
        if (isset($app['packagename']) && in_array($app['packagename'], $packages)) {
            // 如果请求中有model参数，可以添加devicetype字段
            if (!empty($model) && !isset($app['devicetype'])) {
                $app['devicetype'] = $model;
            }
            $result[] = $app;
        }
    }
    
    // 4. 构建响应
    $response = [
        'code' => 0,
        'type' => 'array',
        'data' => $result
    ];
    
    // 5. 加密并返回
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}

/**
 * 根据ID数组批量获取应用
 */
function getAppByIds($req) {
    $file = __DIR__ . '/data/app.json';
    $data = file_exists($file) 
        ? json_decode(file_get_contents($file), true) 
        : [];

    // 先构建完整的返回体
    $response = [
        'code' => 0,
        'msg'  => 'success',
        'data' => $data
    ];

    
    // 输出加密后的数据
    exit(AesHelper::encrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
}