<?php
// 开启所有错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 记录开始
echo "=== Debug Start ===<br>\n";
echo "PHP Version: " . phpversion() . "<br>\n";
echo "SAPI: " . php_sapi_name() . "<br>\n";
echo "Loaded Extensions: " . implode(',', get_loaded_extensions()) . "<br>\n";

// 加载TP6
define('APP_PATH', __DIR__ . '/../app/');
require __DIR__ . '/../vendor/autoload.php';

try {
    $response = (new think\App())->http->run();
    $response->send();
    echo "<br>=== Debug End ===";
} catch (Throwable $e) {
    echo "<h2>错误详情：</h2>";
    echo "<p><strong>错误消息：</strong>" . $e->getMessage() . "</p>";
    echo "<p><strong>文件位置：</strong>" . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<h3>堆栈跟踪：</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
