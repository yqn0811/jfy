<?php
$dsn = 'mysql:host=115.190.245.200;port=3306;dbname=yunce_jiumirw_co;charset=utf8mb4';
$user = 'yunce_jiumirw_co';
$pass = 'JEj9bHykFznH';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add product_id to wd_xcx_album_selection
    try {
        $pdo->exec("ALTER TABLE `wd_xcx_album_selection` ADD COLUMN `product_id` int(11) NOT NULL DEFAULT 0 COMMENT '产品ID' AFTER `factory_uid`");
        echo "Added product_id to wd_xcx_album_selection\n";
    } catch (Exception $e) {
        echo "wd_xcx_album_selection error (maybe exists): " . $e->getMessage() . "\n";
    }

    // Add pic_id to wd_xcx_album_selection_item
    try {
        $pdo->exec("ALTER TABLE `wd_xcx_album_selection_item` ADD COLUMN `pic_id` int(11) NOT NULL DEFAULT 0 COMMENT '图片ID' AFTER `selection_id`");
        echo "Added pic_id to wd_xcx_album_selection_item\n";
    } catch (Exception $e) {
        echo "wd_xcx_album_selection_item error (maybe exists): " . $e->getMessage() . "\n";
    }
    
    echo "\n--- wd_xcx_album_selection ---\n";
    $stmt = $pdo->query("DESCRIBE wd_xcx_album_selection");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n--- wd_xcx_album_selection_item ---\n";
    $stmt = $pdo->query("DESCRIBE wd_xcx_album_selection_item");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
