<?php

namespace app\api\controller;

use app\common\service\CorsOriginService;
use app\common\service\file\FileTransferService;
use think\App;

class FileTransferApiController extends ApiBaseController
{
    private $file_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->file_service = new FileTransferService($app);
    }

    public function registerFile()
    {
        $param = $this->request->postMore([
            ['original_name', ''],
            ['originalName', ''],
            ['object_key', ''],
            ['objectKey', ''],
            ['storage_provider', 'pending'],
            ['storageProvider', ''],
            ['mime_type', ''],
            ['mimeType', ''],
            ['extension', ''],
            ['size_bytes', 0],
            ['sizeBytes', 0],
            ['sha256', ''],
            ['status', 'uploaded'],
            ['preview_url', ''],
            ['previewUrl', ''],
            ['transfer_token', ''],
            ['transferToken', ''],
            ['sso_subject', ''],
            ['ssoSubject', ''],
        ], false, false);
        $param = $this->normalizeFileParam($param, $this->request->post());

        $this->result($this->file_service->registerFile($param, (int)request()->userID()), 0, '登记成功');
    }

    public function uploadFiles()
    {
        $files = $this->request->file();
        if (empty($files)) {
            throwError('请选择上传文件');
        }

        $this->result($this->file_service->uploadFiles($files, $this->request->post(), $this->getOptionalUserId()), 0, '上传成功');
    }

    public function createShare()
    {
        $param = $this->request->postMore([
            ['title', ''],
            [['file_ids', 'a'], []],
            [['fileIds', 'a'], []],
            ['password', ''],
            ['pickup_code', ''],
            ['pickupCode', ''],
            ['expires_at', ''],
            ['expiresAt', ''],
            ['max_downloads', 0],
            ['maxDownloads', 0],
            ['allow_preview', 1],
            ['allowPreview', ''],
            ['notify_on_download', 0],
            ['notifyOnDownload', ''],
            ['transfer_token', ''],
            ['transferToken', ''],
            ['sso_subject', ''],
            ['ssoSubject', ''],
        ], false, false);
        $param = $this->normalizeShareParam($param, $this->request->post());

        $this->result($this->file_service->createShare($param, $this->getOptionalUserId()), 0, '创建成功');
    }

    public function listShares()
    {
        $param = $this->request->getMore([
            ['keyword', ''],
            ['status', ''],
            ['page', 1],
            ['limit', 20],
            ['transfer_token', ''],
            ['transferToken', ''],
            ['sso_subject', ''],
            ['ssoSubject', ''],
        ]);
        $param = $this->normalizeShareListParam($param, $this->request->get());

        $this->result($this->file_service->listShares($param, $this->getOptionalUserId()));
    }

    public function getPublicShare()
    {
        $param = $this->request->getMore([
            ['code', ''],
            ['share_code', ''],
            ['shareCode', ''],
            ['password', ''],
        ], false, false);
        $param['code'] = $this->pickFirst($param, ['code', 'share_code', 'shareCode']);
        if (empty($param['code'])) {
            throwError('分享参数不完整');
        }

        $this->result($this->file_service->getShareByCode($param['code'], true, $param['password']));
    }

    public function getShareByPickupCode()
    {
        $param = $this->request->getMore([
            ['code', ''],
            ['pickup_code', ''],
            ['pickupCode', ''],
        ], false, false);
        $pickupCode = $this->pickFirst($param, ['pickup_code', 'pickupCode', 'code']);
        if ($pickupCode === '') {
            throwError('请输入取件码');
        }

        $this->result($this->file_service->getShareByPickupCode($pickupCode));
    }

    public function verifySharePassword()
    {
        $param = $this->request->postMore([
            ['code', ''],
            ['share_code', ''],
            ['shareCode', ''],
            ['password', ''],
        ], false, false);
        $param['code'] = $this->pickFirst($param, ['code', 'share_code', 'shareCode']);
        if (empty($param['code'])) {
            throwError('分享参数不完整');
        }

        $this->result($this->file_service->verifySharePassword($param['code'], $param['password']), 0, '验证成功');
    }

    public function getShareQrcode()
    {
        $param = $this->request->postMore([
            ['code', ''],
            ['share_code', ''],
            ['shareCode', ''],
            ['url', ''],
        ], false, false);
        $code = $this->pickFirst($param, ['code', 'share_code', 'shareCode']);
        if ($code === '') {
            throwError('分享参数不完整');
        }

        $this->result($this->file_service->getShareQrcode($code, (string)$param['url']), 0, '生成成功');
    }

    public function getShare()
    {
        $param = $this->request->getMore([
            ['code', ''],
            ['share_code', ''],
            ['shareCode', ''],
            ['transfer_token', ''],
            ['transferToken', ''],
            ['sso_subject', ''],
            ['ssoSubject', ''],
        ], false, false);
        $param = $this->normalizeShareListParam($param, $this->request->get());
        $param['code'] = $this->pickFirst($param, ['code', 'share_code', 'shareCode']);
        if (empty($param['code'])) {
            throwError('分享参数不完整');
        }

        $this->result($this->file_service->getOwnerShareByCode($param['code'], $this->getOptionalUserId(), $param));
    }

    public function downloadFile()
    {
        $param = $this->request->getMore([
            ['file_id', 0],
            ['fileId', 0],
        ], false, false);
        $fileId = (int)($param['file_id'] ?: $param['fileId']);

        $download = $this->file_service->getOwnerDownloadFile($fileId, (int)request()->userID());

        $this->streamFile($download['path'], $download['download_name'], $download['mime_type'] ?? '');
    }

    public function downloadSharedFile()
    {
        $param = $this->request->getMore([
            ['file_id', 0],
            ['fileId', 0],
            ['code', ''],
            ['share_code', ''],
            ['shareCode', ''],
            ['password', ''],
            ['pickup_code', ''],
            ['pickupCode', ''],
            ['preview', 0],
        ], false, false);
        $fileId = (int)($param['file_id'] ?: $param['fileId']);
        $code = $this->pickFirst($param, ['code', 'share_code', 'shareCode']);
        $pickupCode = $this->pickFirst($param, ['pickup_code', 'pickupCode']);

        $download = $this->file_service->getSharedDownloadFile($fileId, $code, $param['password'], !empty($param['preview']), $pickupCode);

        $this->streamFile($download['path'], $download['download_name'], $download['mime_type'] ?? '');
    }

    private function normalizeFileParam(array $param, array $raw = [])
    {
        $map = [
            'originalName' => 'original_name',
            'objectKey' => 'object_key',
            'storageProvider' => 'storage_provider',
            'mimeType' => 'mime_type',
            'sizeBytes' => 'size_bytes',
            'previewUrl' => 'preview_url',
            'transferToken' => 'transfer_token',
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

    private function normalizeShareParam(array $param, array $raw = [])
    {
        $map = [
            'fileIds' => 'file_ids',
            'pickupCode' => 'pickup_code',
            'expiresAt' => 'expires_at',
            'maxDownloads' => 'max_downloads',
            'allowPreview' => 'allow_preview',
            'notifyOnDownload' => 'notify_on_download',
            'transferToken' => 'transfer_token',
            'ssoSubject' => 'sso_subject',
        ];
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $raw)) {
                $param[$to] = $raw[$from];
            } elseif ($this->isBlankValue($param[$to] ?? null) && !$this->isBlankValue($param[$from] ?? null)) {
                $param[$to] = $param[$from];
            }
        }
        $param['pickup_code'] = $this->pickFirst($param, ['pickup_code', 'pickupCode', 'password']);
        return $param;
    }

    private function normalizeShareListParam(array $param, array $raw = [])
    {
        $map = [
            'transferToken' => 'transfer_token',
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

    private function pickFirst(array $param, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                return $param[$key];
            }
        }
        return '';
    }

    private function getOptionalUserId()
    {
        try {
            return (int)request()->userID();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function isBlankValue($value)
    {
        return $value === null || $value === '';
    }

    private function streamFile($filePath, $filename, $mimeType = '')
    {
        if (!is_file($filePath)) {
            throwError('文件不存在或已失效');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $origin = CorsOriginService::resolveAllowedOrigin((string)$this->request->header('origin', ''));
        $filename = (string)$filename;
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        if ($fallbackName === '') {
            $fallbackName = 'download';
        }
        $mimeType = trim((string)$mimeType);
        if ($mimeType === '' || preg_match('/[\r\n]/', $mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: ' . (CorsOriginService::allowCredentials($origin) ? 'true' : 'false'));
        header('Access-Control-Expose-Headers: Content-Disposition, Content-Length, Content-Type');
        header('Vary: Origin');
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, no-cache');
        readfile($filePath);
        exit;
    }
}
