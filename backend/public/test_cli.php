<?php
require __DIR__ . '/../vendor/autoload.php';
use think\App;
use app\common\service\album\SelectionService;
use app\common\model\album\WdXcxAlbumSelectionItem;
use think\facade\Db;

$_SERVER['HTTP_HOST'] = 'localhost';

$app = new App();
$app->initialize();

$service = new SelectionService($app);

try {
    echo "Creating selection...\n";
    $uid = 1;
    $product_id = 5;
    $pic_ids = [8, 9];
    $factory_uid = 2;

    $selection = $service->createSelection($uid, $product_id, $pic_ids, $factory_uid);
    echo "Selection created: " . $selection->id . "\n";
    print_r($selection->toArray());

    echo "\nCurrent selection items in DB...\n";
    $items = WdXcxAlbumSelectionItem::where('selection_id', $selection->id)->select();
    print_r($items ? $items->toArray() : []);

    echo "\nItems with pic relation...\n";
    $itemsWithPic = WdXcxAlbumSelectionItem::where('selection_id', $selection->id)
        ->with(['pic'])
        ->select();
    foreach ($itemsWithPic as $i) {
        echo "Item {$i->id} -> pic: ";
        if ($i->pic) {
            print_r($i->pic->toArray());
        } else {
            echo "null\n";
        }
    }
    
    echo "\nAdding images...\n";
    $service->addSelectionImages($selection->id, $uid, [10]);
    $countAfterAdd = WdXcxAlbumSelectionItem::where('selection_id', $selection->id)->count();
    echo "Item count after add: " . $countAfterAdd . "\n";
    
    echo "\nRemoving images...\n";
    $service->removeSelectionImages($selection->id, $uid, [9, 10]);
    $itemsAfterRemove = WdXcxAlbumSelectionItem::where('selection_id', $selection->id)->select();
    echo "Item count after remove: " . ($itemsAfterRemove ? count($itemsAfterRemove) : 0) . "\n";
    foreach($itemsAfterRemove as $item) {
        echo "Remaining Pic ID: " . $item->pic_id . "\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
