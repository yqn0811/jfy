<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\album\WdXcxAlbumFolder;

class TestDb extends Command
{
    protected function configure()
    {
        $this->setName('test:db')->setDescription('Test DB Content');
    }

    protected function execute(Input $input, Output $output)
    {
        $id = 197;
        $output->writeln("Checking Product ID: $id");

        $product = WdXcxAlbumFolder::find($id);
        if ($product) {
            $output->writeln("Product found data: " . json_encode($product->toArray()));
        } else {
            $output->writeln("Product NOT found");
        }

        $count = WdXcxUserAlbumPic::where('folder_id', $id)->count();
        $output->writeln("Count of patterns for folder_id=$id: $count");

        $patterns = WdXcxUserAlbumPic::where('folder_id', $id)->select();
        foreach ($patterns as $p) {
            $output->writeln("Pattern ID: {$p->id}, FolderID: {$p->folder_id}, PicID: {$p->pic_id}");
        }
    }
}
