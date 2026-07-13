<?php

namespace app\api\controller;

use app\common\service\CorsOriginService;
use app\common\service\file\FileCollectionService;
use app\common\service\file\FileRiskGuardService;
use think\App;

class FileCollectionApiController extends ApiBaseController
{
    private $collection_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->collection_service = new FileCollectionService($app);
    }

    public function createTask()
    {
        $param = $this->request->postMore([
            ['template_id', ''],
            ['templateId', ''],
            ['name', ''],
            ['description', ''],
            ['due_at', ''],
            ['dueAt', ''],
            ['submit_target_description', ''],
            ['submitTargetDescription', ''],
            ['access_code', ''],
            ['accessCode', ''],
            [['fields', 'a'], []],
            [['materials', 'a'], []],
            ['allow_resubmission', 1],
            ['allowResubmission', ''],
            ['enable_ai_check', 0],
            ['enableAICheck', ''],
            ['anonymous_submit', 0],
            ['anonymousSubmit', ''],
            ['allow_preview', 0],
            ['allowPreview', ''],
            ['naming_rule', ''],
            ['namingRule', ''],
            ['reminder_before_due_hours', 24],
            ['reminderBeforeDueHours', ''],
            ['sso_subject', ''],
            ['ssoSubject', ''],
        ], false, false);
        $param = $this->normalizeTaskParam($param, $this->request->post());

        $this->result($this->collection_service->createTask($param, (int)request()->userID()), 0, '创建成功');
    }

    public function listTasks()
    {
        $param = $this->request->getMore([
            ['keyword', ''],
            ['status', ''],
            ['page', 1],
            ['limit', 20],
        ]);

        $this->result($this->collection_service->listTasks($param, (int)request()->userID()));
    }

    public function getTask()
    {
        $param = $this->request->getMore([
            ['id', 0],
        ]);
        if (empty($param['id'])) {
            throwError('任务参数不完整');
        }

        $this->result($this->collection_service->getTask((int)$param['id'], (int)request()->userID()));
    }

    public function archiveTask()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['task_id', 0],
            ['taskId', 0],
        ], false, false);
        $taskId = (int)($param['id'] ?: ($param['task_id'] ?: $param['taskId']));
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }

        $this->result($this->collection_service->archiveTask($taskId, (int)request()->userID()), 0, '归档成功');
    }

    public function getTaskQrcode()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['task_id', 0],
            ['taskId', 0],
            ['url', ''],
        ], false, false);
        $taskId = (int)($param['id'] ?: ($param['task_id'] ?: $param['taskId']));
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }

        $this->result($this->collection_service->getTaskQrcode($taskId, (int)request()->userID(), (string)$param['url']), 0, '生成成功');
    }

    public function listSubmissions()
    {
        $param = $this->request->getMore([
            ['task_id', 0],
            ['taskId', 0],
            ['keyword', ''],
            ['status', ''],
            ['page', 1],
            ['limit', 20],
        ], false, false);
        $param['task_id'] = (int)($param['task_id'] ?: $param['taskId']);

        $this->result($this->collection_service->listSubmissions($param, (int)request()->userID()), 0, '获取成功');
    }

    public function getSubmission()
    {
        $param = $this->request->getMore([
            ['id', 0],
            ['submission_id', 0],
            ['submissionId', 0],
        ], false, false);
        $submissionId = (int)($param['id'] ?: ($param['submission_id'] ?: $param['submissionId']));
        if ($submissionId <= 0) {
            throwError('提交记录参数不完整');
        }

        $this->result($this->collection_service->getSubmission($submissionId, (int)request()->userID()), 0, '获取成功');
    }

    public function approveSubmission()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['submission_id', 0],
            ['submissionId', 0],
            ['remark', ''],
        ], false, false);
        $submissionId = (int)($param['id'] ?: ($param['submission_id'] ?: $param['submissionId']));
        if ($submissionId <= 0) {
            throwError('提交记录参数不完整');
        }

        $this->result($this->collection_service->approveSubmission($submissionId, (int)request()->userID(), (string)$param['remark']), 0, '审核通过');
    }

    public function rejectSubmission()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['submission_id', 0],
            ['submissionId', 0],
            ['remark', ''],
            ['reason', ''],
        ], false, false);
        $submissionId = (int)($param['id'] ?: ($param['submission_id'] ?: $param['submissionId']));
        if ($submissionId <= 0) {
            throwError('提交记录参数不完整');
        }
        $remark = $this->pickFirst($param, ['remark', 'reason']);

        $this->result($this->collection_service->rejectSubmission($submissionId, (int)request()->userID(), $remark), 0, '已退回补交');
    }

    public function remindSubmissions()
    {
        $param = $this->request->postMore([
            ['task_id', 0],
            ['taskId', 0],
            ['remark', ''],
            ['message', ''],
        ], false, false);
        $raw = array_merge($param, $this->request->post());
        $taskId = (int)($raw['task_id'] ?: ($raw['taskId'] ?? 0));
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }
        $submissionIds = $this->pickArray($raw, ['submission_ids', 'submissionIds', 'ids']);
        $remark = $this->pickFirst($raw, ['remark', 'message']);

        $this->result($this->collection_service->remindSubmissions($taskId, (int)request()->userID(), $submissionIds, $remark), 0, '已记录提醒');
    }

    public function downloadTaskSubmissions()
    {
        $param = $this->request->getMore([
            ['id', 0],
            ['task_id', 0],
            ['taskId', 0],
        ], false, false);
        $taskId = (int)($param['id'] ?: ($param['task_id'] ?: $param['taskId']));
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }

        $download = $this->collection_service->prepareTaskSubmissionsZip($taskId, (int)request()->userID());
        $this->streamZipFile($download['path'], $download['download_name'], $download['work_dir'], $download['lock'] ?? []);
    }

    public function getPublicTask()
    {
        $param = $this->request->getMore([
            ['id', 0],
            ['task_id', 0],
            ['taskId', 0],
            ['access_code', ''],
            ['accessCode', ''],
        ], false, false);
        $taskId = (int)($param['id'] ?: ($param['task_id'] ?: $param['taskId']));
        $accessCode = $this->pickFirst($param, ['access_code', 'accessCode']);

        $this->result($this->collection_service->getPublicTask($taskId, $accessCode), 0, '获取成功');
    }

    public function verifyPublicTaskAccessCode()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['task_id', 0],
            ['taskId', 0],
            ['access_code', ''],
            ['accessCode', ''],
        ], false, false);
        $taskId = (int)($param['id'] ?: ($param['task_id'] ?: $param['taskId']));
        $accessCode = $this->pickFirst($param, ['access_code', 'accessCode']);

        $this->result($this->collection_service->verifyAccessCode($taskId, $accessCode), 0, '验证成功');
    }

    public function submitPublicTask()
    {
        $this->result($this->collection_service->submitPublic($this->request->post(), $this->request->file()), 0, '提交成功');
    }

    public function getPublicSubmissionReceipt()
    {
        $param = $this->request->getMore([
            ['id', 0],
            ['submission_id', 0],
            ['submissionId', 0],
            ['receipt_token', ''],
            ['receiptToken', ''],
        ], false, false);
        $submissionId = (int)($param['id'] ?: ($param['submission_id'] ?: $param['submissionId']));
        if ($submissionId <= 0) {
            throwError('提交记录参数不完整');
        }
        $receiptToken = $this->pickFirst($param, ['receipt_token', 'receiptToken']);

        $this->result($this->collection_service->getPublicSubmissionReceipt($submissionId, $receiptToken), 0, '获取成功');
    }

    private function normalizeTaskParam(array $param, array $raw = [])
    {
        $map = [
            'templateId' => 'template_id',
            'dueAt' => 'due_at',
            'submitTargetDescription' => 'submit_target_description',
            'accessCode' => 'access_code',
            'allowResubmission' => 'allow_resubmission',
            'enableAICheck' => 'enable_ai_check',
            'anonymousSubmit' => 'anonymous_submit',
            'allowPreview' => 'allow_preview',
            'namingRule' => 'naming_rule',
            'reminderBeforeDueHours' => 'reminder_before_due_hours',
            'ssoSubject' => 'sso_subject',
        ];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $raw)) {
                $param[$to] = $raw[$from];
            } elseif ($this->isBlankValue($param[$to] ?? null) && !$this->isBlankValue($param[$from] ?? null)) {
                $param[$to] = $param[$from];
            }
        }
        return $param;
    }

    private function isBlankValue($value)
    {
        return $value === null || $value === '';
    }

    private function pickFirst(array $param, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                return $param[$key];
            }
        }
        return '';
    }

    private function pickArray(array $param, array $keys)
    {
        foreach ($keys as $key) {
            if (!isset($param[$key]) || $param[$key] === '') {
                continue;
            }
            $value = $param[$key];
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                return array_filter(array_map('trim', explode(',', $value)));
            }
        }
        return [];
    }

    private function streamZipFile($zipPath, $filename, $workDir, array $lock = [])
    {
        if (!is_file($zipPath)) {
            (new FileRiskGuardService())->releaseLock($lock);
            throwError('ZIP文件生成失败');
        }
        register_shutdown_function(function () use ($lock) {
            (new FileRiskGuardService())->releaseLock($lock);
        });
        register_shutdown_function(function () use ($workDir) {
            $this->removeDirectory($workDir);
        });
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $origin = CorsOriginService::resolveAllowedOrigin((string)$this->request->header('origin', ''));
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        if ($fallbackName === '') {
            $fallbackName = 'submissions.zip';
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: ' . (CorsOriginService::allowCredentials($origin) ? 'true' : 'false'));
        header('Access-Control-Expose-Headers: Content-Disposition, Content-Length, Content-Type');
        header('Vary: Origin');
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($zipPath));
        header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, no-cache');
        readfile($zipPath);
        $this->removeDirectory($workDir);
        (new FileRiskGuardService())->releaseLock($lock);
        exit;
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if (!$items) {
            @rmdir($dir);
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
