<?php

namespace app\command;

use app\common\service\file\FileUploadSecurityService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class FileUploadHealthCheck extends Command
{
    protected function configure()
    {
        $this->setName('file:upload-health')
            ->setDescription('检查文件传输上传安全运行环境')
            ->addOption('webhook', null, Option::VALUE_OPTIONAL, '失败告警 Webhook URL', '')
            ->addOption('json', null, Option::VALUE_NONE, '输出 JSON');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->prepareCliServerVars();
        $health = (new FileUploadSecurityService())->runtimeHealth();
        $webhook = trim((string)($input->getOption('webhook') ?: env('file_transfer.alert_webhook_url', '')));
        if (!$health['ok'] && $webhook !== '') {
            $health['alert_sent'] = $this->sendWebhook($webhook, $health);
        }

        if ((bool)$input->getOption('json')) {
            $output->writeln(json_encode($health, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return $health['ok'] ? 0 : 1;
        }

        $output->writeln('upload_health=' . ($health['ok'] ? 'ok' : 'failed'));
        foreach ($health['checks'] as $check) {
            $output->writeln(sprintf(
                '- %s: %s %s',
                $check['name'],
                $check['ok'] ? 'ok' : 'failed',
                json_encode($check['detail'], JSON_UNESCAPED_UNICODE)
            ));
        }

        return $health['ok'] ? 0 : 1;
    }

    private function sendWebhook(string $webhook, array $health): bool
    {
        if (!function_exists('curl_init') || !filter_var($webhook, FILTER_VALIDATE_URL)) {
            return false;
        }

        $failed = array_values(array_filter($health['checks'] ?? [], function ($check) {
            return empty($check['ok']);
        }));
        $body = json_encode([
            'text' => '文件传输上传安全健康检查失败',
            'failed_checks' => array_map(function ($check) {
                return $check['name'] ?? 'unknown';
            }, $failed),
            'health' => $health,
        ], JSON_UNESCAPED_UNICODE);

        $curl = curl_init($webhook);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_exec($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $code >= 200 && $code < 300;
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
