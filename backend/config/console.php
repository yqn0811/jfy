<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'game_reward' => \app\command\GameRankingCommand::class,
        'dist_settl' => \app\command\DistributionSettlCommand::class,
        'setup_visit_table' => \app\command\SetupVisitTableCommand::class,
        'test_poster' => \app\command\TestPosterCommand::class,
        'test_db' => \app\command\TestDb::class,
        'album:sync-ai-resources' => \app\command\SyncAiResources::class,
        'file:cleanup-expired-anonymous' => \app\command\CleanupExpiredAnonymousFiles::class,
        'file:upload-health' => \app\command\FileUploadHealthCheck::class,
        'file:risk-report' => \app\command\FileRiskReport::class,
    ],
];
