<?php
require __DIR__ . '/../vendor/autoload.php';
use think\App;
use think\facade\Db;

$_SERVER['HTTP_HOST'] = 'localhost';
$app = new App();
$app->initialize();

try {
    echo "--- Users ---\n";
    $users = Db::name('user')->limit(5)->select();
    foreach ($users as $u) {
        echo "ID: " . $u['id'] . ", Name: " . ($u['nickname'] ?? 'N/A') . "\n";
    }

    echo "\n--- Products ---\n";
    $products = Db::name('xcx_product')->limit(5)->select();
    foreach ($products as $p) {
        echo "ID: " . $p['id'] . ", Name: " . $p['product_name'] . "\n";
    }
    
    echo "\n--- Pics ---\n";
    $pics = Db::name('xcx_pic')->limit(5)->select();
    foreach ($pics as $p) {
        echo "ID: " . $p['id'] . ", Name: " . $p['pic_name'] . "\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
