<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'game_reward' => \app\index\controller\command\GameRankingCommand::class,
        'dist_settl' => \app\index\controller\command\DistributionSettlCommand::class,
        'setup_visit_table' => \app\index\controller\command\SetupVisitTableCommand::class,
        'test_poster' => \app\index\controller\command\TestPosterCommand::class,
        'test_db' => \app\command\TestDb::class,
        'album:sync-ai-resources' => \app\command\SyncAiResources::class,
    ],
];
