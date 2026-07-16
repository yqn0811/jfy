<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class FileRiskReport extends Command
{
    protected function configure()
    {
        $this->setName('file:risk-report')
            ->setDescription('汇总文件传输风险日志并可选发送告警')
            ->addOption('minutes', null, Option::VALUE_OPTIONAL, '统计最近分钟数', 60)
            ->addOption('threshold', null, Option::VALUE_OPTIONAL, '触发告警的风险事件数量', 10)
            ->addOption('webhook', null, Option::VALUE_OPTIONAL, '告警 Webhook URL', '')
            ->addOption('json', null, Option::VALUE_NONE, '输出 JSON');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->prepareCliServerVars();
        $minutes = max(1, min(1440, (int)$input->getOption('minutes')));
        $threshold = max(1, (int)$input->getOption('threshold'));
        $webhook = trim((string)($input->getOption('webhook') ?: env('file_transfer.alert_webhook_url', '')));
        $report = $this->buildReport($minutes);
        $report['threshold'] = $threshold;
        $report['alert'] = $report['total'] >= $threshold;

        if ($report['alert'] && $webhook !== '') {
            $report['alert_sent'] = $this->sendWebhook($webhook, $report);
        }

        if ((bool)$input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return 0;
        }

        $output->writeln(sprintf('file_risk total=%d minutes=%d alert=%s', $report['total'], $minutes, $report['alert'] ? 'yes' : 'no'));
        foreach ($report['events'] as $event => $count) {
            $output->writeln(sprintf('- %s=%d', $event, $count));
        }
        return 0;
    }

    private function buildReport(int $minutes): array
    {
        $since = time() - $minutes * 60;
        $events = [];
        $total = 0;
        foreach ($this->logFiles() as $file) {
            $handle = @fopen($file, 'r');
            if (!$handle) {
                continue;
            }
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, 'file risk event:') === false) {
                    continue;
                }
                if (@filemtime($file) !== false && filemtime($file) < $since) {
                    continue;
                }
                $event = $this->extractEvent($line);
                if ($event === '') {
                    continue;
                }
                $events[$event] = ($events[$event] ?? 0) + 1;
                $total++;
            }
            fclose($handle);
        }
        arsort($events);
        return [
            'total' => $total,
            'events' => $events,
            'minutes' => $minutes,
            'generated_at' => date('c'),
        ];
    }

    private function logFiles(): array
    {
        $dir = rtrim(runtime_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'log';
        $files = glob($dir . DIRECTORY_SEPARATOR . '*single*.log') ?: [];
        $fallback = glob(rtrim(runtime_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . '*single*.log') ?: [];
        return array_values(array_unique(array_merge($files, $fallback)));
    }

    private function extractEvent(string $line): string
    {
        $pos = strpos($line, 'file risk event:');
        if ($pos === false) {
            return '';
        }
        $json = trim(substr($line, $pos + strlen('file risk event:')));
        $payload = json_decode($json, true);
        return is_array($payload) ? (string)($payload['event'] ?? '') : '';
    }

    private function sendWebhook(string $webhook, array $report): bool
    {
        if (!function_exists('curl_init') || !filter_var($webhook, FILTER_VALIDATE_URL)) {
            return false;
        }
        $body = json_encode([
            'text' => sprintf('文件传输风险事件 %d 条，最近 %d 分钟', $report['total'], $report['minutes']),
            'report' => $report,
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
