<?php
$dsn = 'mysql:host=115.190.245.200;port=3306;dbname=yunce_jiumirw_co;charset=utf8mb4';
$user = 'yunce_jiumirw_co';
$pass = 'JEj9bHykFznH';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Products (Folder Type 2?) ---\n";
    $stmt = $pdo->query("SELECT id, folder_name, uid FROM wd_xcx_album_folder WHERE folder_type = 2 LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($products);
    
    if (!empty($products)) {
        $pid = $products[0]['id'];
        $uid = $products[0]['uid'];
        echo "\n--- Images for Product $pid ---\n";
        // Assuming images are in wd_xcx_pic with gid = pid (if gid maps to folder)
        // Or via WdXcxUserAlbumPic?
        // Let's check wd_xcx_pic where gid = $pid
        $stmt = $pdo->query("SELECT id, imgurl, delete_time FROM wd_xcx_pic LIMIT 10");
        $pics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($pics);
    }
    
    echo "\n--- Users ---\n";
    $stmt = $pdo->query("SELECT id, nickname FROM wd_xcx_user LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
