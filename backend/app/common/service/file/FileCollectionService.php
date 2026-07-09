<?php

namespace app\common\service\file;

use app\common\model\file\FtCollectionField;
use app\common\model\file\FtCollectionMaterial;
use app\common\model\file\FtCollectionTask;
use app\common\service\BaseService;
use think\App;
use think\facade\Db;

class FileCollectionService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function createTask(array $param, int $uid)
    {
        $name = trim((string)($param['name'] ?? ''));
        if ($name === '') {
            throwError('请填写任务名称');
        }

        $fields = $this->normalizeFields($param['fields'] ?? []);
        $materials = $this->normalizeMaterials($param['materials'] ?? []);
        if (empty($fields)) {
            throwError('请至少配置一个提交人字段');
        }
        if (empty($materials)) {
            throwError('请至少配置一个材料项');
        }

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            $task = FtCollectionTask::create([
                'owner_user_id' => $uid,
                'sso_subject' => $param['sso_subject'] ?? null,
                'template_id' => $param['template_id'] ?? null,
                'name' => $name,
                'description' => (string)($param['description'] ?? ''),
                'due_at' => $this->normalizeDateTime($param['due_at'] ?? null),
                'submit_target_description' => (string)($param['submit_target_description'] ?? ''),
                'access_code_hash' => $this->makePasswordHash($param['access_code'] ?? ''),
                'allow_resubmission' => !empty($param['allow_resubmission']) ? 1 : 0,
                'enable_ai_check' => !empty($param['enable_ai_check']) ? 1 : 0,
                'anonymous_submit' => !empty($param['anonymous_submit']) ? 1 : 0,
                'allow_preview' => !empty($param['allow_preview']) ? 1 : 0,
                'naming_rule' => (string)($param['naming_rule'] ?? ''),
                'reminder_before_due_hours' => max(0, (int)($param['reminder_before_due_hours'] ?? 24)),
                'status' => 'collecting',
            ]);

            foreach ($fields as $field) {
                $field['task_id'] = $task->id;
                FtCollectionField::create($field);
            }
            foreach ($materials as $material) {
                $material['task_id'] = $task->id;
                FtCollectionMaterial::create($material);
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return $this->getTask((int)$task->id, $uid);
    }

    public function listTasks(array $param, int $uid)
    {
        $page = max(1, (int)($param['page'] ?? 1));
        $limit = max(1, min(100, (int)($param['limit'] ?? 20)));
        $status = trim((string)($param['status'] ?? ''));
        $keyword = trim((string)($param['keyword'] ?? ''));

        $query = FtCollectionTask::where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->whereLike('name', '%' . $keyword . '%');
            })
            ->order('id desc');

        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        return [
            'items' => array_map([$this, 'formatTaskRow'], $list->items()),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
        ];
    }

    public function getTask(int $taskId, int $uid)
    {
        $task = FtCollectionTask::where('id', $taskId)
            ->where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->find();
        if (!$task) {
            throwError('收集任务不存在');
        }

        $fields = FtCollectionField::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select();
        $materials = FtCollectionMaterial::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select();

        return array_merge($this->formatTaskRow($task), [
            'access_code_required' => !empty($task->access_code_hash),
            'fields' => array_map([$this, 'formatField'], $fields->toArray()),
            'materials' => array_map([$this, 'formatMaterial'], $materials->toArray()),
        ]);
    }

    private function normalizeFields($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }
        $result = [];
        $sort = 0;
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = trim((string)($item['field_key'] ?? $item['key'] ?? ''));
            $label = trim((string)($item['field_label'] ?? $item['label'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }
            $result[] = [
                'field_key' => $key,
                'field_label' => $label,
                'field_type' => trim((string)($item['field_type'] ?? $item['fieldType'] ?? $item['type'] ?? 'text')),
                'required' => !empty($item['required']) ? 1 : 0,
                'placeholder' => (string)($item['placeholder'] ?? ''),
                'sort_order' => isset($item['sort_order']) ? (int)$item['sort_order'] : (isset($item['order']) ? (int)$item['order'] : $sort),
            ];
            $sort++;
        }
        return $result;
    }

    private function normalizeMaterials($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }
        $result = [];
        $sort = 0;
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string)($item['material_name'] ?? $item['materialName'] ?? $item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $fileTypes = $item['file_types'] ?? ($item['fileTypes'] ?? []);
            if (is_string($fileTypes)) {
                $fileTypes = array_filter(array_map('trim', explode(',', $fileTypes)));
            }
            $result[] = [
                'material_name' => $name,
                'file_types' => json_encode(array_values($fileTypes ?: []), JSON_UNESCAPED_UNICODE),
                'required' => !empty($item['required']) ? 1 : 0,
                'max_size_mb' => max(1, (int)($item['max_size_mb'] ?? ($item['maxSizeMb'] ?? 100))),
                'sort_order' => isset($item['sort_order']) ? (int)$item['sort_order'] : (isset($item['order']) ? (int)$item['order'] : $sort),
            ];
            $sort++;
        }
        return $result;
    }

    private function formatTaskRow($task)
    {
        $row = [
            'id' => (int)$task->id,
            'team_id' => '',
            'teamId' => '',
            'owner_user_id' => (int)$task->owner_user_id,
            'ownerUserId' => (int)$task->owner_user_id,
            'ownerId' => (string)$task->owner_user_id,
            'template_id' => (string)$task->template_id,
            'templateId' => (string)$task->template_id,
            'name' => (string)$task->name,
            'description' => (string)$task->description,
            'due_at' => (string)$task->due_at,
            'dueAt' => (string)$task->due_at,
            'submit_target_description' => (string)$task->submit_target_description,
            'submitTargetDescription' => (string)$task->submit_target_description,
            'allow_resubmission' => (bool)$task->allow_resubmission,
            'allowResubmission' => (bool)$task->allow_resubmission,
            'enable_ai_check' => (bool)$task->enable_ai_check,
            'enableAICheck' => (bool)$task->enable_ai_check,
            'anonymous_submit' => (bool)$task->anonymous_submit,
            'anonymousSubmit' => (bool)$task->anonymous_submit,
            'allow_preview' => (bool)$task->allow_preview,
            'allowPreview' => (bool)$task->allow_preview,
            'naming_rule' => (string)$task->naming_rule,
            'namingRule' => (string)$task->naming_rule,
            'reminder_before_due_hours' => (int)$task->reminder_before_due_hours,
            'reminderBeforeDueHours' => (int)$task->reminder_before_due_hours,
            'status' => (string)$task->status,
            'submit_count' => (int)$task->submit_count,
            'submitCount' => (int)$task->submit_count,
            'file_count' => (int)$task->file_count,
            'fileCount' => (int)$task->file_count,
            'total_size_bytes' => (int)$task->total_size_bytes,
            'totalSizeBytes' => (int)$task->total_size_bytes,
            'totalSizeMb' => round(((int)$task->total_size_bytes) / 1024 / 1024, 2),
            'created_at' => (string)$task->created_at,
            'createdAt' => (string)$task->created_at,
            'updated_at' => (string)$task->updated_at,
            'updatedAt' => (string)$task->updated_at,
            'archived_at' => (string)$task->archived_at,
            'archivedAt' => (string)$task->archived_at,
        ];
        return $row;
    }

    private function formatField($field)
    {
        return [
            'id' => (int)$field['id'],
            'field_key' => (string)$field['field_key'],
            'fieldKey' => (string)$field['field_key'],
            'field_label' => (string)$field['field_label'],
            'fieldLabel' => (string)$field['field_label'],
            'field_type' => (string)$field['field_type'],
            'fieldType' => (string)$field['field_type'],
            'required' => (bool)$field['required'],
            'placeholder' => (string)$field['placeholder'],
            'sort_order' => (int)$field['sort_order'],
            'order' => (int)$field['sort_order'],
        ];
    }

    private function formatMaterial($material)
    {
        $types = json_decode((string)$material['file_types'], true);
        return [
            'id' => (int)$material['id'],
            'material_name' => (string)$material['material_name'],
            'materialName' => (string)$material['material_name'],
            'file_types' => is_array($types) ? $types : [],
            'fileTypes' => is_array($types) ? $types : [],
            'required' => (bool)$material['required'],
            'max_size_mb' => (int)$material['max_size_mb'],
            'maxSizeMb' => (int)$material['max_size_mb'],
            'sort_order' => (int)$material['sort_order'],
            'order' => (int)$material['sort_order'],
        ];
    }

    private function normalizeDateTime($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private function makePasswordHash($password)
    {
        $password = trim((string)$password);
        return $password === '' ? null : password_hash($password, PASSWORD_DEFAULT);
    }
}
