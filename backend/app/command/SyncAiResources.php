<?php

namespace app\command;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\service\album\AiResourceBridgeService;
use app\index\model\WdXcxPic;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class SyncAiResources extends Command
{
    protected function configure()
    {
        $this->setName('album:sync-ai-resources')
            ->setDescription('补同步家纺云图片到我的资源库')
            ->addOption('uid', null, Option::VALUE_OPTIONAL, '只同步指定用户ID', 0)
            ->addOption('since', null, Option::VALUE_OPTIONAL, '只同步该日期后的图片，格式 YYYY-MM-DD', date('Y-m-d', strtotime('-30 days')))
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '最多处理图片数量', 500)
            ->addOption('summary', null, Option::VALUE_NONE, '只输出汇总和进度')
            ->addOption('dry-run', null, Option::VALUE_NONE, '只统计不实际同步');
    }

    protected function execute(Input $input, Output $output)
    {
        $uid = max(0, (int)$input->getOption('uid'));
        $limit = max(1, min(5000, (int)$input->getOption('limit')));
        $since = trim((string)$input->getOption('since'));
        $sinceTime = $this->parseSinceTime($since);
        $summary = (bool)$input->getOption('summary');
        $dryRun = (bool)$input->getOption('dry-run');

        $query = WdXcxPic::where('uid', '>', 0)
            ->where('file_type', 1)
            ->where('imgurl', '<>', '')
            ->where('create_time', '>=', $sinceTime)
            ->order('id desc')
            ->limit($limit);
        if ($uid > 0) {
            $query->where('uid', $uid);
        }
        $pictures = $query->select();
        $bridge = new AiResourceBridgeService($this->app);
        $total = 0;
        $synced = 0;
        $failed = 0;
        $albumSynced = 0;

        foreach ($pictures as $picture) {
            $total++;
            if (!$summary) {
                $output->writeln(sprintf('pic_id=%s uid=%s name=%s', $picture->id, $picture->uid, $picture->pic_name));
            } elseif ($total % 50 === 0) {
                $output->writeln(sprintf('processed=%d synced=%d failed=%d album_relations=%d', $total, $synced, $failed, $albumSynced));
            }
            if ($dryRun) {
                continue;
            }
            $result = $bridge->safeSyncPicture($picture->uid, $picture, ['role' => 'upload']);
            if ($result === null) {
                $failed++;
                continue;
            }
            $synced++;
            $relations = WdXcxUserAlbumPic::where('pic_id', $picture->id)
                ->with(['picture'])
                ->order('id desc')
                ->select();
            foreach ($relations as $relation) {
                $ownerUid = $this->resolveRelationOwnerUid($relation);
                if ($ownerUid <= 0) {
                    continue;
                }
                if ($bridge->safeSyncAlbumRelation($ownerUid, $relation, 'album') !== null) {
                    $albumSynced++;
                }
            }
        }

        $output->writeln(sprintf(
            'done total=%d synced=%d failed=%d album_relations=%d dry_run=%s',
            $total,
            $synced,
            $failed,
            $albumSynced,
            $dryRun ? 'yes' : 'no'
        ));
    }

    private function parseSinceTime($since)
    {
        if ($since === '') {
            return strtotime('-30 days');
        }
        if (ctype_digit($since)) {
            return (int)$since;
        }
        $time = strtotime($since . ' 00:00:00');
        return $time ?: strtotime('-30 days');
    }

    private function resolveRelationOwnerUid($relation)
    {
        if (!$relation) {
            return 0;
        }
        $folder = WdXcxAlbumFolder::where('id', $relation->folder_id)->find();
        if ($folder && (int)$folder->uid > 0) {
            return (int)$folder->uid;
        }
        return (int)$relation->user_id;
    }
}
