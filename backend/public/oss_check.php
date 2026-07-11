<?php
/**
 * OSS Configuration Diagnostic Script
 * Place this in public/ directory and access via browser.
 */

define('root_path', __DIR__ . '/../');
require __DIR__ . '/../vendor/autoload.php';

use think\facade\Db;
use app\common\service\upload\AliOssService;
use app\common\service\upload\TenOssService;

$app = new \think\App();
$http = $app->http;
$response = $http->run();

echo "<style>body{font-family:sans-serif;padding:20px;} pre{background:#f0f0f0;padding:10px;overflow:auto;} .error{color:red;} .success{color:green;}</style>";
echo "<h1>OSS Configuration Diagnostic</h1>";

// 1. Check Time
echo "<h2>1. System Time Check</h2>";
echo "Current System Time: " . date('Y-m-d H:i:s') . "<br>";
echo "Timestamp: " . time() . "<br>";
echo "<p><strong>Note:</strong> If this time differs significantly (>15 mins) from standard internet time, OSS requests will fail with 'Signature invalid'.</p>";

// 2. Check Configuration
echo "<h2>2. Configuration Check</h2>";
$uniacid = 1; // Default
if (isset($_GET['uniacid'])) $uniacid = intval($_GET['uniacid']);

try {
    if (!function_exists('getRemoteConfig')) {
        echo "<div class='error'>Error: getRemoteConfig function not found.</div>";
    } else {
        $config = getRemoteConfig($uniacid);
        echo "Active Remote Type: " . $config['remote'] . " (1=Local, 2=Qiniu, 3=AliOSS, 4=TencentCOS)<br>";
        
        $safeConfig = $config;
        // Mask secrets
        foreach (['aliOss', 'ten_cos', 'qiniu'] as $key) {
            if (isset($safeConfig[$key])) {
                if (isset($safeConfig[$key]['sk'])) $safeConfig[$key]['sk'] = '******' . substr($safeConfig[$key]['sk'], -4);
                if (isset($safeConfig[$key]['ak'])) $safeConfig[$key]['ak'] = substr($safeConfig[$key]['ak'], 0, 4) . '******';
            }
        }
        echo "<pre>";
        print_r($safeConfig);
        echo "</pre>";

        // 3. Test Connection
        echo "<h2>3. Connection Test</h2>";
        
        // Create a dummy file
        $dummyFile = root_path . 'public/test_upload.txt';
        file_put_contents($dummyFile, 'Test content ' . time());
        $fileObj = new \think\File($dummyFile); // Mock file object if possible, or use path
        
        // Mock UploadedFile
        // We need to use the service which expects UploadedFile or check if we can pass path directly
        // Based on my recent fix, it uses getRealPath(), so we need an object that has getRealPath()
        // OR we can manually call the underlying SDK methods.
        
        if ($config['remote'] == 3 && isset($config['aliOss'])) {
            echo "Testing AliOSS...<br>";
            try {
                // Manually instantiate to test
                // AliOssService expects config in constructor
                // And we can try to use aliAuth->uploadFile directly
                
                require_once root_path . '/vendor/aliyun/autoload.php';
                $aliConfig = $config['aliOss'];
                $ossClient = new \OSS\OssClient($aliConfig['ak'], $aliConfig['sk'], $aliConfig['domain']);
                
                $bucket = $aliConfig['bucket'];
                $object = 'test_diag_' . time() . '.txt';
                $content = 'Diagnostic test content';
                
                echo "Attempting to put object '$object' to bucket '$bucket'...<br>";
                $ossClient->putObject($bucket, $object, $content);
                echo "<div class='success'>Success! Uploaded to AliOSS.</div>";
                
            } catch (\Exception $e) {
                echo "<div class='error'>AliOSS Failed: " . $e->getMessage() . "</div>";
            }
        } elseif ($config['remote'] == 4 && isset($config['ten_cos'])) {
            echo "Testing Tencent COS...<br>";
            try {
                $tenConfig = $config['ten_cos'];
                // Manually instantiate
                $cosClient = new \Qcloud\Cos\Client([
                    'region' => $tenConfig['region'],
                    'credentials' => [
                        'secretId' => $tenConfig['ak'],
                        'secretKey' => $tenConfig['sk']
                    ]
                ]);
                
                $bucket = $tenConfig['bucket'];
                $key = 'test_diag_' . time() . '.txt';
                $content = 'Diagnostic test content';
                
                echo "Attempting to put object '$key' to bucket '$bucket'...<br>";
                $result = $cosClient->putObject([
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'Body' => $content
                ]);
                echo "<div class='success'>Success! Uploaded to Tencent COS.</div>";
                
            } catch (\Exception $e) {
                echo "<div class='error'>Tencent COS Failed: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "Skipping connection test (Local or Qiniu or not configured).";
        }
    }
} catch (\Throwable $e) {
    echo "<div class='error'>System Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
