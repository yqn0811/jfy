<?php

namespace app\common\service\file;

use app\common\model\file\FtCollectionField;
use app\common\model\file\FtCollectionMaterial;
use app\common\model\file\FtCollectionTask;
use app\common\model\file\FtFile;
use app\common\model\file\FtSubmission;
use app\common\model\file\FtSubmissionFile;
use app\common\model\file\FtReviewLog;
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

    public function archiveTask(int $taskId, int $uid)
    {
        $task = $this->findOwnerTask($taskId, $uid);
        if ((string)$task->status === 'archived') {
            return $this->getTask((int)$task->id, $uid);
        }

        $task->status = 'archived';
        $task->archived_at = date('Y-m-d H:i:s');
        $task->save();

        return $this->getTask((int)$task->id, $uid);
    }

    public function getTaskQrcode(int $taskId, int $uid, string $url)
    {
        $task = $this->findOwnerTask($taskId, $uid);
        $url = $this->normalizeTaskSubmissionUrl($url, (int)$task->id);

        $qrcodeDir = public_path() . 'uploads/qrcode';
        if (!is_dir($qrcodeDir) && !mkdir($qrcodeDir, 0755, true) && !is_dir($qrcodeDir)) {
            throwError('二维码目录创建失败');
        }

        try {
            $qrcode = \cores\utils\Utils::createQrcode($url, '', true);
        } catch (\Throwable $e) {
            throwError('二维码生成失败');
        }

        return [
            'task_id' => (int)$task->id,
            'taskId' => (string)$task->id,
            'url' => $url,
            'qrcode' => $qrcode,
            'qrCode' => $qrcode,
        ];
    }

    public function listSubmissions(array $param, int $uid)
    {
        $taskId = (int)($param['task_id'] ?? 0);
        $task = $this->findOwnerTask($taskId, $uid);
        $page = max(1, (int)($param['page'] ?? 1));
        $limit = max(1, min(100, (int)($param['limit'] ?? 20)));
        $status = trim((string)($param['status'] ?? ''));
        $keyword = trim((string)($param['keyword'] ?? ''));

        $query = FtSubmission::where('task_id', $task->id)
            ->whereNull('deleted_at')
            ->order('id desc');

        $items = [];
        foreach ($query->select() as $submission) {
            $row = $this->formatSubmissionRow($submission);
            if ($status !== '' && $status !== 'all' && $row['status'] !== $status) {
                continue;
            }
            if ($keyword !== '') {
                $haystack = mb_strtolower($row['submitterName'] . ' ' . $row['submitterPhone'] . ' ' . $row['submitterDepartment']);
                if (mb_strpos($haystack, mb_strtolower($keyword)) === false) {
                    continue;
                }
            }
            $items[] = $row;
        }
        $total = count($items);
        $items = array_slice($items, ($page - 1) * $limit, $limit);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getSubmission(int $submissionId, int $uid)
    {
        $submission = FtSubmission::where('id', $submissionId)
            ->whereNull('deleted_at')
            ->find();
        if (!$submission) {
            throwError('提交记录不存在');
        }

        $task = $this->findOwnerTask((int)$submission->task_id, $uid);
        $files = FtSubmissionFile::with(['file'])
            ->where('submission_id', $submission->id)
            ->order('id asc')
            ->select();
        $reviewLogs = FtReviewLog::where('submission_id', $submission->id)
            ->order('id desc')
            ->select()
            ->toArray();

        return array_merge($this->formatSubmissionRow($submission), [
            'task' => $this->formatTaskRow($task),
            'files' => $this->formatSubmissionFiles($files),
            'review_logs' => array_map([$this, 'formatReviewLog'], $reviewLogs),
            'reviewLogs' => array_map([$this, 'formatReviewLog'], $reviewLogs),
            'missing_check' => $this->formatMissingCheck($submission),
            'missingCheck' => $this->formatMissingCheck($submission),
            'resubmission_notice' => null,
            'resubmissionNotice' => null,
        ]);
    }

    public function approveSubmission(int $submissionId, int $uid, string $remark = '')
    {
        return $this->reviewSubmission($submissionId, $uid, 'approve', 'approved', $remark ?: '材料审核通过');
    }

    public function rejectSubmission(int $submissionId, int $uid, string $remark)
    {
        $remark = trim($remark);
        if ($remark === '') {
            throwError('请填写退回原因');
        }
        return $this->reviewSubmission($submissionId, $uid, 'reject', 'resubmission_needed', $remark);
    }

    public function remindSubmissions(int $taskId, int $uid, array $submissionIds, string $remark = '')
    {
        $task = $this->findOwnerTask($taskId, $uid);
        $submissionIds = $this->normalizeIdList($submissionIds);
        if (empty($submissionIds)) {
            throwError('请选择需要提醒的提交记录');
        }

        $submissions = FtSubmission::where('task_id', $task->id)
            ->whereNull('deleted_at')
            ->whereIn('id', $submissionIds)
            ->select();

        $foundIds = [];
        foreach ($submissions as $submission) {
            $foundIds[] = (int)$submission->id;
        }
        if (count($foundIds) !== count($submissionIds)) {
            throwError('部分提交记录不存在或无权限');
        }

        $remark = trim($remark);
        if ($remark === '') {
            $remark = '已记录催办，请线下通知提交人查看收集链接或补交要求';
        }
        $remark = mb_substr($remark, 0, 1000);

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            foreach ($submissions as $submission) {
                FtReviewLog::create([
                    'submission_id' => $submission->id,
                    'reviewer_user_id' => $uid,
                    'action' => 'remind',
                    'result' => 'recorded',
                    'remark' => $remark,
                ]);
            }
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return [
            'task_id' => (int)$task->id,
            'taskId' => (string)$task->id,
            'reminded_count' => count($foundIds),
            'remindedCount' => count($foundIds),
            'submission_ids' => $foundIds,
            'submissionIds' => array_map('strval', $foundIds),
        ];
    }

    public function prepareTaskSubmissionsZip(int $taskId, int $uid)
    {
        $task = $this->findOwnerTask($taskId, $uid);
        if (!class_exists('\ZipArchive')) {
            throwError('服务器暂不支持打包下载');
        }

        $submissionIds = FtSubmission::where('task_id', $task->id)
            ->whereNull('deleted_at')
            ->column('id');
        if (empty($submissionIds)) {
            throwError('暂无可下载文件');
        }

        $items = FtSubmissionFile::with(['file'])
            ->whereIn('submission_id', $submissionIds)
            ->order('submission_id asc, id asc')
            ->select();
        if (count($items) === 0) {
            throwError('暂无可下载文件');
        }

        $workDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'jfy_collection_zip_' . uniqid('', true);
        if (!mkdir($workDir, 0700, true) && !is_dir($workDir)) {
            throwError('打包目录创建失败');
        }

        $zipPath = $workDir . DIRECTORY_SEPARATOR . 'submissions.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->removeDirectory($workDir);
            throwError('ZIP创建失败');
        }

        try {
            $usedNames = [];
            foreach ($items as $item) {
                if (!$item->file) {
                    continue;
                }
                $path = $this->getLocalFilePath($item->file);
                $entryName = $this->uniqueZipEntryName('submission-' . (int)$item->submission_id . '/' . (string)$item->file->original_name, $usedNames);
                $zip->addFile($path, $entryName);
                if (method_exists($zip, 'setCompressionName') && $this->shouldStoreZipEntry($entryName)) {
                    $zip->setCompressionName($entryName, \ZipArchive::CM_STORE);
                }
            }
            $zip->close();
        } catch (\Throwable $e) {
            $zip->close();
            $this->removeDirectory($workDir);
            throw $e;
        }

        if (!is_file($zipPath) || filesize($zipPath) <= 0) {
            $this->removeDirectory($workDir);
            throwError('暂无可打包文件');
        }

        return [
            'path' => $zipPath,
            'download_name' => $this->sanitizeDownloadFilename((string)$task->name . '-提交文件.zip'),
            'work_dir' => $workDir,
        ];
    }

    public function getPublicTask(int $taskId, string $accessCode = '')
    {
        $task = $this->findPublicTask($taskId);
        return $this->formatPublicTask($task, $this->isAccessCodeVerified($task, $accessCode));
    }

    public function verifyAccessCode(int $taskId, string $accessCode)
    {
        $task = $this->findPublicTask($taskId);
        if (!$this->isAccessCodeVerified($task, $accessCode)) {
            throwError('访问密码不正确');
        }
        return $this->formatPublicTask($task, true);
    }

    public function submitPublic(array $param, array $files)
    {
        $taskId = (int)($param['task_id'] ?? $param['taskId'] ?? $param['id'] ?? 0);
        $task = $this->findPublicTask($taskId);
        $this->assertTaskAccepting($task);

        $accessCode = (string)($param['access_code'] ?? $param['accessCode'] ?? '');
        if (!$this->isAccessCodeVerified($task, $accessCode)) {
            throwError('访问密码不正确');
        }
        $sourceSubmissionId = (int)($param['source_submission_id'] ?? $param['sourceSubmissionId'] ?? $param['resubmission_of'] ?? $param['resubmissionOf'] ?? 0);
        if ($sourceSubmissionId > 0 && empty($task->allow_resubmission)) {
            throwError('此任务未开启补交');
        }
        $sourceSubmission = $this->findPublicResubmissionSource($sourceSubmissionId, (int)$task->id);

        $fields = FtCollectionField::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select()
            ->toArray();
        $materials = FtCollectionMaterial::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select()
            ->toArray();

        $submitterSnapshot = $this->normalizeSubmitterSnapshot($param);
        $this->validateSubmitterSnapshot($fields, $submitterSnapshot);

        $uploadFiles = $this->normalizeUploadedFiles($files);
        if (empty($uploadFiles)) {
            throwError('请上传材料文件');
        }

        $materialIds = $this->normalizeMaterialIdList($param['material_ids'] ?? ($param['materialIds'] ?? []));
        if (count($materialIds) < count($uploadFiles)) {
            throwError('材料文件参数不完整');
        }

        $materialMap = [];
        foreach ($materials as $material) {
            $materialMap[(int)$material['id']] = $material;
        }

        $preparedFiles = [];
        $materialFileCount = [];
        foreach ($uploadFiles as $index => $file) {
            $materialId = (int)$materialIds[$index];
            if (empty($materialMap[$materialId])) {
                throwError('材料项不存在或已失效');
            }

            $originalName = $this->getUploadOriginalName($file, $index, $param);
            if ($originalName === '') {
                throwError('文件名不正确');
            }

            $sizeBytes = method_exists($file, 'getSize') ? (int)$file->getSize() : 0;
            if ($sizeBytes < 0) {
                throwError('文件大小不正确');
            }

            $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
            $this->validateMaterialFile($materialMap[$materialId], $extension, $sizeBytes);

            $preparedFiles[] = [
                'file' => $file,
                'material_id' => $materialId,
                'original_name' => $originalName,
                'extension' => $extension,
                'size_bytes' => $sizeBytes,
            ];
            $materialFileCount[$materialId] = ($materialFileCount[$materialId] ?? 0) + 1;
        }

        foreach ($materials as $material) {
            if (!empty($material['required']) && empty($materialFileCount[(int)$material['id']])) {
                throwError('请上传' . $material['material_name']);
            }
        }

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            $submission = FtSubmission::create([
                'task_id' => $task->id,
                'submitter_user_id' => null,
                'submitter_snapshot' => json_encode($submitterSnapshot, JSON_UNESCAPED_UNICODE),
                'status' => 'submitted',
                'review_state' => 'waiting',
                'has_missing' => 0,
                'file_count' => 0,
                'total_size_bytes' => 0,
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);

            $totalSize = 0;
            foreach ($preparedFiles as $index => $prepared) {
                $fileModel = $this->storeSubmissionFile($task, $prepared, $index);
                FtSubmissionFile::create([
                    'submission_id' => $submission->id,
                    'material_id' => $prepared['material_id'],
                    'file_id' => $fileModel->id,
                    'status' => 'uploaded',
                ]);
                $totalSize += (int)$prepared['size_bytes'];
            }

            $submission->file_count = count($preparedFiles);
            $submission->total_size_bytes = $totalSize;
            $submission->save();

            $task->submit_count = (int)$task->submit_count + 1;
            $task->file_count = (int)$task->file_count + count($preparedFiles);
            $task->total_size_bytes = (int)$task->total_size_bytes + $totalSize;
            $task->save();

            if ($sourceSubmission) {
                FtReviewLog::create([
                    'submission_id' => $sourceSubmission->id,
                    'reviewer_user_id' => 0,
                    'action' => 'resubmit',
                    'result' => 'submitted',
                    'remark' => '提交人已重新提交，补交记录编号：' . (int)$submission->id,
                ]);
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return $this->formatSubmissionReceipt($submission, $task, $sourceSubmission);
    }

    public function getPublicSubmissionReceipt(int $submissionId)
    {
        $submission = FtSubmission::where('id', $submissionId)
            ->whereNull('deleted_at')
            ->find();
        if (!$submission) {
            throwError('提交记录不存在');
        }

        $task = FtCollectionTask::where('id', $submission->task_id)
            ->whereNull('deleted_at')
            ->find();
        if (!$task) {
            throwError('收集任务不存在');
        }

        return $this->formatSubmissionReceipt($submission, $task);
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

    private function findPublicTask(int $taskId)
    {
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }

        $task = FtCollectionTask::where('id', $taskId)
            ->whereNull('deleted_at')
            ->find();
        if (!$task) {
            throwError('收集任务不存在或已失效');
        }

        return $task;
    }

    private function findOwnerTask(int $taskId, int $uid)
    {
        if ($taskId <= 0) {
            throwError('任务参数不完整');
        }

        $task = FtCollectionTask::where('id', $taskId)
            ->where('owner_user_id', $uid)
            ->whereNull('deleted_at')
            ->find();
        if (!$task) {
            throwError('收集任务不存在');
        }
        return $task;
    }

    private function findOwnerSubmission(int $submissionId, int $uid)
    {
        if ($submissionId <= 0) {
            throwError('提交记录参数不完整');
        }

        $submission = FtSubmission::where('id', $submissionId)
            ->whereNull('deleted_at')
            ->find();
        if (!$submission) {
            throwError('提交记录不存在');
        }

        $this->findOwnerTask((int)$submission->task_id, $uid);
        return $submission;
    }

    private function findPublicResubmissionSource(int $submissionId, int $taskId)
    {
        if ($submissionId <= 0) {
            return null;
        }

        $submission = FtSubmission::where('id', $submissionId)
            ->where('task_id', $taskId)
            ->whereNull('deleted_at')
            ->find();
        if (!$submission) {
            throwError('补交来源记录不存在');
        }
        if ((string)$submission->review_state !== 'rejected') {
            throwError('当前提交记录无需补交');
        }
        return $submission;
    }

    private function normalizeIdList(array $ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $result[$id] = $id;
            }
        }
        return array_values($result);
    }

    private function normalizeTaskSubmissionUrl(string $url, int $taskId)
    {
        $url = trim($url);
        if ($url === '') {
            $url = '/submission-upload?taskId=' . $taskId;
        }
        if (mb_strlen($url) > 2000 || !preg_match('/^(https?:\/\/|\/)/i', $url)) {
            throwError('二维码链接不合法');
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throwError('二维码链接不合法');
        }

        $path = isset($parts['path']) ? rtrim((string)$parts['path'], '/') : '';
        if (!in_array($path, ['/submission-upload', '/submission-upload.html'], true)) {
            throwError('二维码链接必须为本任务提交页');
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $query);
        }
        $linkTaskId = (int)($query['taskId'] ?? ($query['task_id'] ?? ($query['id'] ?? 0)));
        if ($linkTaskId !== $taskId) {
            throwError('二维码链接与任务不匹配');
        }

        return $url;
    }

    private function reviewSubmission(int $submissionId, int $uid, string $action, string $result, string $remark)
    {
        $submission = $this->findOwnerSubmission($submissionId, $uid);
        $task = $this->findOwnerTask((int)$submission->task_id, $uid);
        if ($action !== 'approve' && empty($task->allow_resubmission)) {
            throwError('此任务未开启补交');
        }

        $conn = Db::connect('pgsql_file');
        $conn->startTrans();
        try {
            if ($action === 'approve') {
                $submission->review_state = 'approved';
                $submission->has_missing = 0;
            } else {
                $submission->review_state = 'rejected';
                $submission->has_missing = 1;
            }
            $submission->status = 'submitted';
            $submission->save();

            FtReviewLog::create([
                'submission_id' => $submission->id,
                'reviewer_user_id' => $uid,
                'action' => $action,
                'result' => $result,
                'remark' => mb_substr(trim($remark), 0, 1000),
            ]);

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return $this->getSubmission((int)$submission->id, $uid);
    }

    private function assertTaskAccepting($task)
    {
        if ((string)$task->status !== 'collecting') {
            throwError('此任务暂不接受新提交');
        }
        if (!empty($task->due_at) && strtotime((string)$task->due_at) < time()) {
            throwError('此任务已过期，暂不接受新提交');
        }
    }

    private function formatPublicTask($task, bool $accessCodeVerified)
    {
        $fields = FtCollectionField::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select()
            ->toArray();
        $materials = FtCollectionMaterial::where('task_id', $task->id)
            ->order('sort_order asc, id asc')
            ->select()
            ->toArray();
        $accessCodeRequired = !empty($task->access_code_hash);
        $canExposeForm = !$accessCodeRequired || $accessCodeVerified;
        $formattedFields = $canExposeForm ? array_map([$this, 'formatField'], $fields) : [];
        $formattedMaterials = $canExposeForm ? array_map([$this, 'formatMaterial'], $materials) : [];

        return [
            'id' => (int)$task->id,
            'task_id' => (int)$task->id,
            'taskId' => (string)$task->id,
            'task_name' => (string)$task->name,
            'taskName' => (string)$task->name,
            'organization_name' => '织序传输助手',
            'organizationName' => '织序传输助手',
            'description' => (string)$task->description,
            'due_at' => (string)$task->due_at,
            'dueAt' => (string)$task->due_at,
            'status' => $this->getPublicTaskStatus($task),
            'access_code_required' => $accessCodeRequired,
            'accessCodeRequired' => $accessCodeRequired,
            'access_code_verified' => !$accessCodeRequired || $accessCodeVerified,
            'accessCodeVerified' => !$accessCodeRequired || $accessCodeVerified,
            'submitter_fields' => $formattedFields,
            'submitterFields' => $formattedFields,
            'materials' => $formattedMaterials,
        ];
    }

    private function getPublicTaskStatus($task)
    {
        if ((string)$task->status !== 'collecting') {
            return (string)$task->status;
        }
        if (!empty($task->due_at) && strtotime((string)$task->due_at) < time()) {
            return 'expired';
        }
        return 'active';
    }

    private function isAccessCodeVerified($task, string $accessCode)
    {
        $hash = (string)$task->access_code_hash;
        if ($hash === '') {
            return true;
        }
        return $accessCode !== '' && password_verify($accessCode, $hash);
    }

    private function formatSubmissionRow($submission)
    {
        $snapshot = $this->decodeJsonObject($submission->submitter_snapshot ?? []);
        $name = $this->pickSnapshotValue($snapshot, ['name', '姓名', 'submitterName', 'submitter_name']);
        $phone = $this->pickSnapshotValue($snapshot, ['phone', 'mobile', '手机号', '联系电话', 'submitterPhone', 'submitter_phone']);
        $department = $this->pickSnapshotValue($snapshot, ['department', '部门', 'submitterDepartment', 'submitter_department']);
        $status = $this->normalizeSubmissionStatus($submission);

        return [
            'id' => (int)$submission->id,
            'collection_task_id' => (int)$submission->task_id,
            'collectionTaskId' => (string)$submission->task_id,
            'submitter_name' => $name,
            'submitterName' => $name,
            'submitter_phone' => $phone,
            'submitterPhone' => $phone,
            'submitter_department' => $department,
            'submitterDepartment' => $department,
            'submitter_snapshot' => $snapshot,
            'submitterSnapshot' => $snapshot,
            'status' => $status,
            'review_state' => (string)$submission->review_state,
            'reviewState' => (string)$submission->review_state,
            'has_missing' => (bool)$submission->has_missing,
            'hasMissing' => (bool)$submission->has_missing,
            'file_count' => (int)$submission->file_count,
            'fileCount' => (int)$submission->file_count,
            'total_size_bytes' => (int)$submission->total_size_bytes,
            'totalSizeBytes' => (int)$submission->total_size_bytes,
            'submitted_at' => (string)$submission->submitted_at,
            'submittedAt' => (string)$submission->submitted_at,
            'updated_at' => (string)$submission->updated_at,
            'updatedAt' => (string)$submission->updated_at,
        ];
    }

    private function normalizeSubmissionStatus($submission)
    {
        if ((string)$submission->review_state === 'approved') {
            return 'approved';
        }
        if ((string)$submission->review_state === 'rejected') {
            return 'need_resubmission';
        }
        return (string)$submission->status === 'submitted' ? 'pending_review' : (string)$submission->status;
    }

    private function decodeJsonObject($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function pickSnapshotValue(array $snapshot, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($snapshot[$key]) && trim((string)$snapshot[$key]) !== '') {
                return trim((string)$snapshot[$key]);
            }
        }
        return '';
    }

    private function getLocalFilePath($file)
    {
        if ((string)$file->storage_provider !== 'local_private') {
            throwError('文件暂不支持本地下载');
        }
        $objectKey = str_replace('\\', '/', (string)$file->object_key);
        if (strpos($objectKey, '..') !== false || strpos($objectKey, 'file_transfer/') !== 0) {
            throwError('文件路径不合法');
        }

        $runtimeRoot = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $base = realpath($runtimeRoot . 'file_transfer');
        $path = $runtimeRoot . str_replace('/', DIRECTORY_SEPARATOR, $objectKey);
        $realPath = realpath($path);
        if (!$base || !$realPath || strpos($realPath, $base) !== 0 || !is_file($realPath)) {
            throwError('文件不存在或已失效');
        }
        return $realPath;
    }

    private function uniqueZipEntryName(string $name, array &$usedNames)
    {
        $name = str_replace('\\', '/', trim($name));
        $parts = array_filter(explode('/', $name), function ($part) {
            return $part !== '' && $part !== '.' && $part !== '..';
        });
        $cleanParts = array_map([$this, 'sanitizeZipPathPart'], $parts);
        $entry = implode('/', $cleanParts);
        if ($entry === '') {
            $entry = 'file';
        }

        $extension = pathinfo($entry, PATHINFO_EXTENSION);
        $base = $extension ? mb_substr($entry, 0, -(mb_strlen($extension) + 1)) : $entry;
        $candidate = $entry;
        $index = 2;
        while (isset($usedNames[$candidate])) {
            $candidate = $base . '-' . $index . ($extension ? '.' . $extension : '');
            $index++;
        }
        $usedNames[$candidate] = true;
        return $candidate;
    }

    private function sanitizeZipPathPart(string $part)
    {
        $part = trim((string)preg_replace('/[\\\\\/:*?"<>|\r\n]+/', '_', $part));
        return $part === '' ? 'file' : mb_substr($part, 0, 120);
    }

    private function sanitizeDownloadFilename(string $filename)
    {
        $filename = trim((string)preg_replace('/[\\\\\/:*?"<>|\r\n]+/', '_', $filename));
        return $filename === '' ? 'submissions.zip' : mb_substr($filename, 0, 180);
    }

    private function shouldStoreZipEntry(string $entryName)
    {
        return in_array(strtolower(pathinfo($entryName, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'zip', 'pdf'], true);
    }

    private function removeDirectory(string $dir)
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

    private function formatSubmissionFiles($items)
    {
        $files = [];
        foreach ($items as $item) {
            if (!$item->file) {
                continue;
            }
            $file = $item->file;
            $files[] = [
                'id' => (int)$file->id,
                'submission_file_id' => (int)$item->id,
                'submissionFileId' => (int)$item->id,
                'material_id' => (int)$item->material_id,
                'materialId' => (int)$item->material_id,
                'file_name' => (string)$file->original_name,
                'fileName' => (string)$file->original_name,
                'file_size_mb' => round(((int)$file->size_bytes) / 1024 / 1024, 2),
                'fileSizeMb' => round(((int)$file->size_bytes) / 1024 / 1024, 2),
                'file_type' => (string)$file->extension,
                'fileType' => (string)$file->extension,
                'preview_url' => '',
                'previewUrl' => '',
                'download_url' => '/api/file/files/download?file_id=' . (int)$file->id,
                'downloadUrl' => '/api/file/files/download?file_id=' . (int)$file->id,
                'status' => (string)$item->status,
                'uploaded_at' => (string)$item->created_at,
                'uploadedAt' => (string)$item->created_at,
            ];
        }
        return $files;
    }

    private function formatReviewLog($log)
    {
        return [
            'id' => (int)$log['id'],
            'submission_id' => (int)$log['submission_id'],
            'submissionId' => (int)$log['submission_id'],
            'reviewer_id' => (int)($log['reviewer_user_id'] ?? 0),
            'reviewerId' => (int)($log['reviewer_user_id'] ?? 0),
            'reviewer_name' => '管理员',
            'reviewerName' => '管理员',
            'action' => (string)$log['action'],
            'result' => (string)$log['result'],
            'remark' => (string)$log['remark'],
            'created_at' => (string)$log['created_at'],
            'createdAt' => (string)$log['created_at'],
        ];
    }

    private function formatMissingCheck($submission)
    {
        if (empty($submission->has_missing)) {
            return [
                'id' => 'missing-' . (int)$submission->id,
                'submissionId' => (string)$submission->id,
                'missingNames' => [],
                'summary' => '材料已提交，等待审核',
                'checkedAt' => (string)$submission->submitted_at,
                'state' => 'passing',
            ];
        }

        return [
            'id' => 'missing-' . (int)$submission->id,
            'submissionId' => (string)$submission->id,
            'missingNames' => [],
            'summary' => '材料可能不完整，请审核确认',
            'checkedAt' => (string)$submission->submitted_at,
            'state' => 'warning',
        ];
    }

    private function normalizeSubmitterSnapshot(array $param)
    {
        $fields = $param['submitter_fields'] ?? ($param['submitterFields'] ?? []);
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            $fields = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($fields)) {
            $fields = [];
        }

        $snapshot = [];
        foreach ($fields as $key => $value) {
            $fieldKey = trim((string)$key);
            if ($fieldKey === '') {
                continue;
            }
            $snapshot[$fieldKey] = mb_substr(trim((string)$value), 0, 500);
        }
        return $snapshot;
    }

    private function validateSubmitterSnapshot(array $fields, array $snapshot)
    {
        foreach ($fields as $field) {
            if (empty($field['required'])) {
                continue;
            }
            $fieldKey = (string)$field['field_key'];
            if (!isset($snapshot[$fieldKey]) || trim((string)$snapshot[$fieldKey]) === '') {
                throwError('请填写' . $field['field_label']);
            }
        }
    }

    private function normalizeMaterialIdList($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_filter(explode(',', $value));
            }
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_map('intval', $value));
    }

    private function normalizeUploadedFiles($files)
    {
        $result = [];
        foreach ($files as $file) {
            if (is_array($file)) {
                $result = array_merge($result, $this->normalizeUploadedFiles($file));
            } elseif (is_object($file) && method_exists($file, 'move')) {
                $result[] = $file;
            }
        }
        return $result;
    }

    private function getUploadOriginalName($file, int $index, array $param)
    {
        $names = $param['original_names'] ?? ($param['originalNames'] ?? []);
        if (is_string($names)) {
            $decoded = json_decode($names, true);
            $names = is_array($decoded) ? $decoded : explode(',', $names);
        }
        if (is_array($names) && isset($names[$index]) && $names[$index] !== '') {
            return $this->cleanFileName((string)$names[$index]);
        }
        if (method_exists($file, 'getOriginalName')) {
            return $this->cleanFileName((string)$file->getOriginalName());
        }
        if (method_exists($file, 'getInfo')) {
            $info = $file->getInfo();
            if (!empty($info['name'])) {
                return $this->cleanFileName((string)$info['name']);
            }
        }
        return '';
    }

    private function cleanFileName(string $name)
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        return $name === '' ? '' : mb_substr($name, 0, 255);
    }

    private function validateMaterialFile(array $material, string $extension, int $sizeBytes)
    {
        $maxSizeMb = max(1, (int)($material['max_size_mb'] ?? 100));
        if ($sizeBytes > $maxSizeMb * 1024 * 1024) {
            throwError($material['material_name'] . '超过最大限制' . $maxSizeMb . 'MB');
        }

        $types = json_decode((string)($material['file_types'] ?? '[]'), true);
        $types = is_array($types) ? array_map('strtolower', $types) : [];
        if (!empty($types) && $extension !== '' && !in_array(strtolower($extension), $types, true)) {
            throwError($material['material_name'] . '文件格式不符合要求');
        }
    }

    private function storeSubmissionFile($task, array $prepared, int $index)
    {
        $extension = (string)$prepared['extension'];
        $saveName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
        $relativeDir = 'file_transfer/' . (int)$task->owner_user_id . '/submissions/' . (int)$task->id . '/' . date('Ymd');
        $targetDir = rtrim(app()->getRuntimePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throwError('文件存储目录创建失败');
        }

        $info = $prepared['file']->move($targetDir, $saveName);
        if (!$info) {
            throwError(method_exists($prepared['file'], 'getError') ? $prepared['file']->getError() : '文件上传失败');
        }

        $path = $targetDir . DIRECTORY_SEPARATOR . $saveName;
        $objectKey = $relativeDir . '/' . $saveName;
        $file = FtFile::create([
            'owner_user_id' => (int)$task->owner_user_id,
            'sso_subject' => $task->sso_subject ?? null,
            'original_name' => (string)$prepared['original_name'],
            'object_key' => $objectKey,
            'storage_provider' => 'local_private',
            'mime_type' => $this->detectMimeType($path),
            'extension' => $extension,
            'size_bytes' => (int)$prepared['size_bytes'],
            'sha256' => is_file($path) ? hash_file('sha256', $path) : null,
            'status' => 'uploaded',
            'preview_url' => '',
        ]);
        $file->preview_url = '/api/file/files/download?file_id=' . $file->id;
        $file->save();
        return $file;
    }

    private function detectMimeType(string $path)
    {
        if (is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                return $mime ?: '';
            }
        }
        return '';
    }

    private function formatSubmissionReceipt($submission, $task, $sourceSubmission = null)
    {
        return [
            'id' => (int)$submission->id,
            'submission_id' => (int)$submission->id,
            'submissionId' => (string)$submission->id,
            'task_id' => (int)$task->id,
            'taskId' => (string)$task->id,
            'source_submission_id' => $sourceSubmission ? (int)$sourceSubmission->id : 0,
            'sourceSubmissionId' => $sourceSubmission ? (string)$sourceSubmission->id : '',
            'receipt_number' => 'FT-' . date('Ymd', strtotime((string)$submission->submitted_at ?: 'now')) . '-' . str_pad((string)$submission->id, 6, '0', STR_PAD_LEFT),
            'receiptNumber' => 'FT-' . date('Ymd', strtotime((string)$submission->submitted_at ?: 'now')) . '-' . str_pad((string)$submission->id, 6, '0', STR_PAD_LEFT),
            'submitted_at' => (string)$submission->submitted_at,
            'submittedAt' => (string)$submission->submitted_at,
            'material_summary' => '已提交' . (int)$submission->file_count . '个文件',
            'materialSummary' => '已提交' . (int)$submission->file_count . '个文件',
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
