<?php

namespace app\command;

use app\common\service\file\FileTransferService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class CleanupExpiredAnonymousFiles extends Command
{
    protected function configure()
    {
        $this->setName('file:cleanup-expired-anonymous')
            ->setDescription('清理已过期的匿名文件分享和文件对象')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '最多处理分享数量', 200)
            ->addOption('dry-run', null, Option::VALUE_NONE, '只统计不删除');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->prepareCliServerVars();
        $limit = max(1, min(1000, (int)$input->getOption('limit')));
        $dryRun = (bool)$input->getOption('dry-run');
        $service = new FileTransferService($this->app);
        $stats = $service->cleanupExpiredAnonymousShares($limit, $dryRun);

        $output->writeln(sprintf(
            'done shares=%d files=%d objects=%d failed_objects=%d dry_run=%s',
            $stats['shares'],
            $stats['files'],
            $stats['objects'],
            $stats['failed_objects'],
            $dryRun ? 'yes' : 'no'
        ));
    }

    private function prepareCliServerVars()
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'api-test.jfyuntu.com';
        }
        if (!isset($_SERVER['HTTPS'])) {
            $_SERVER['HTTPS'] = 'off';
        }
    }
}
