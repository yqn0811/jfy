<?php

namespace app\api\controller;

use app\common\service\file\FileCollectionService;
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
}
