<?php

namespace app\api\controller;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\service\CorsOriginService;
use app\common\service\bridge\JiafangyunEntitlementSyncService;
use app\common\service\bridge\JiafangyunBridgeClient;
use app\common\service\user\UserService;
use app\common\service\WxService;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;
use app\common\model\WdXcxPic;
use think\facade\Db;
use think\App;
use think\Response;
use app\common\model\user\WdXcxUserVisitRecord;

class UserApiController extends ApiBaseController
{
    const ORIGINAL_ZIP_MIN_PICTURE_COUNT = 5;

    private $userService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->userService = new UserService($app);
    }

    private function resolveHomeTargetUserId($params, $requireShareCode = false)
    {
        if (empty($params['target_user_id']) && !empty($params['uid'])) {
            $params['target_user_id'] = $params['uid'];
        }
        $shareCode = $params['code'] ?? ($params['share_code'] ?? '');
        $inviteCode = $params['invite_code'] ?? '';
        if ($shareCode !== '') {
            return $this->userService->resolveHomeTargetUserId($params['target_user_id'] ?? 0, $shareCode);
        }
        $visitorUid = $this->getOptionalVisitorUid();
        if (empty($params['target_user_id']) && $visitorUid) {
            $params['target_user_id'] = $visitorUid;
        }
        if ($requireShareCode && !$visitorUid) {
            throwError('分享链接无效');
        }
        return $this->userService->resolveHomeTargetUserId($params['target_user_id'] ?? 0, $inviteCode, true);
    }

    private function getOptionalVisitorUid()
    {
        try {
            return (int)request()->userID();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function hideHomeMiniProgramInternalFields($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        unset($data['mini_path'], $data['qrcode_path'], $data['scene']);
        return $data;
    }

    /**微信用户获取openid
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getUserOpenid()
    {
        $params = $this->request->getMore([
            ['code', ''],
            ['distribution_id', 0],
            ['invite_code', ''],
        ]);
        $code = $params['code'];
        if(!$code){
            throwError('登录code不能为空');
        }
        $userInfo = $this->userService->getUserInfoByCode($code, $params['invite_code']);

        // 尝试自动绑定分销关系
        if(!empty($params['distribution_id']) && !empty($userInfo['_user_id'])){
            $userId = $userInfo['_user_id'];
            if($userId){
                $this->userService->checkAndBindDistribution($userId, $params['distribution_id']);
            }
        }
        unset($userInfo['_user_id']);

        return $this->result($userInfo);
    }

    /**用户绑定手机号码登录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPhone()
    {
        $params = $this->request->postMore([
            ['code', ''],
            ['openid', ''],
            ['distribution_id', 0],
        ]);
        if(empty($params['code']) || empty($params['openid'])){
            throwError('登录code不能为空');
        }
        $result = $this->userService->getUserPhone($params);

        // 尝试自动绑定分销关系
        if(!empty($params['distribution_id'])){
            $userId = WdXcxUser::where('openid', $params['openid'])->value('id');
            if($userId){
                $this->userService->checkAndBindDistribution($userId, $params['distribution_id']);
            }
        }

        $this->result($result);
    }

    /**获取用户展示二维码
     * @return void
     */
    public function getUserQrcode()
    {
        $ticket_id = $this->request->getMore([
            ['ticket_id', ''],
        ])['ticket_id'];
        $this->result($this->userService->getUserQrcodeInfo($ticket_id));
    }

    /**获取用户财产
     * @return void
     */
    public function getUserProperty()
    {
        $type = $this->request->getMore([
            ['type', 1],
        ])['type'];
        $this->result($this->userService->getUserProperty($type));
    }

    /**更新用户主页与资料
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserInfo()
    {
        $param = $this->request->postMore([
            ['nickname', null],
            ['avatar', null],
            ['openid', ''],
            ['wx_ewm', null],
            ['user_desc', null],
            ['upload_pwd', null],
            ['upload_pwd_expire_time', null],
            // 主页资料相关字段
            ['company_name', null],
            ['company_logo', null],
            ['company_desc', null],
            ['contact_mobile', null],
            ['contact_wechat', null],
            ['address_province', null],
            ['address_city', null],
            ['address_district', null],
            ['address_detail', null],
            ['is_show_home', null],
            ['gender', null],
            ['visit_no_need_nickname', null],
            ['visit_no_need_mobile', null],
            ['visit_allow_save_pic', null],
            ['home_watermark_text', null],
            ['home_service_name', null],
            ['home_share_title', null],
            ['home_share_desc', null],
            ['home_share_image', null],
            ['industry_info', null],
            ['latitude', null],
            ['longitude', null],
        ]);
        $this->userService->updateUserInfo($param);
        $this->result([], 0, '更新成功');
    }

    /**PC端更新主页设置
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function updatePcSettings()
    {
        $param = $this->request->postMore([
            ['visit_no_need_nickname', null],
            ['visit_no_need_mobile', null],
            ['visit_allow_save_pic', null],
            ['home_watermark_text', null],
            ['home_service_name', null],
            ['home_share_title', null],
            ['home_share_desc', null],
            ['home_share_image', null],
            ['company_name', null],
            ['company_logo', null],
            ['company_desc', null],
            ['contact_mobile', null],
            ['contact_wechat', null],
            ['address_province', null],
            ['address_city', null],
            ['address_district', null],
            ['address_detail', null],
            ['is_show_home', null],
            ['industry_info', null],
            ['latitude', null],
            ['longitude', null],
        ]);
        $this->userService->updatePcSettings($param, request()->userID());
        $this->result([], 0, '更新成功');
    }

    /**记录图片/视频保存产生的外网流量
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function recordDownloadTraffic()
    {
        $param = $this->request->postMore([
            ['pic_id', 0],
            ['id', 0],
            ['file_size', 0],
            ['file_url', ''],
        ]);
        $this->result($this->userService->recordDownloadTraffic($param, request()->userID()), 0, '记录成功');
    }

    public function discardUploadedPicture()
    {
        $param = $this->request->postMore([
            ['pic_id', 0],
            ['id', 0],
            ['album_pic_id', 0],
        ]);
        $picId = (int)($param['pic_id'] ?: $param['id']);
        $albumPicId = (int)$param['album_pic_id'];
        if ($albumPicId > 0) {
            $this->userService->discardUploadedAlbumPicture($albumPicId, request()->userID());
            $this->result([], 0, '删除成功');
            return;
        }
        if ($picId <= 0) {
            throwError('请选择要删除的图片');
        }
        $this->userService->discardUploadedPicture($picId, request()->userID());
        $this->result([], 0, '删除成功');
    }

    /**申请原图下载地址，统一校验会员、可见性并记录下载流量
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getOriginalDownloadUrl()
    {
        $param = array_merge($this->request->get(), $this->request->post());
        if (!empty($param['stream'])) {
            return $this->streamOriginalDownload($param, (int)request()->userID());
        }
        $this->result($this->userService->getOriginalDownloadUrl($param, request()->userID()), 0, '获取成功');
    }

    private function streamOriginalDownload($param, $userId)
    {
        $param['record_traffic'] = 0;
        $param['skip_remote_size'] = 1;
        $download = $this->userService->getOriginalDownloadUrl($param, $userId);
        $url = trim((string)($download['download_url'] ?? ($download['downloadUrl'] ?? ($download['url'] ?? ''))));
        if ($url === '') {
            throwError('原图暂不可下载');
        }
        $filename = $this->sanitizeDownloadFilename((string)($download['file_name'] ?? ($download['fileName'] ?? 'image.jpg')));
        $this->streamRemoteOriginalDownload(
            $url,
            (int)($download['pic_id'] ?? ($param['pic_id'] ?? 0)),
            $filename,
            (int)($download['file_size'] ?? 0),
            $userId
        );
    }

    private function streamRemoteOriginalDownload($url, $picId, $filename, $knownFileSize, $userId)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $contentType = $this->detectDownloadMimeFromFilename($filename);
        $contentLength = 0;
        $sourceStatus = 0;
        $headersSent = false;
        $streamError = '';
        $bytesSent = 0;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_USERAGENT => 'JiafangyunOriginalDownload/1.0',
            CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) use (&$sourceStatus, &$contentType, &$contentLength) {
                $line = trim($headerLine);
                if ($line === '') {
                    return strlen($headerLine);
                }
                if (stripos($line, 'HTTP/') === 0) {
                    $parts = preg_split('/\s+/', $line);
                    $sourceStatus = isset($parts[1]) ? (int)$parts[1] : 0;
                    return strlen($headerLine);
                }
                $separator = strpos($line, ':');
                if ($separator === false) {
                    return strlen($headerLine);
                }
                $name = strtolower(trim(substr($line, 0, $separator)));
                $value = trim(substr($line, $separator + 1));
                if ($name === 'content-type') {
                    $contentType = $this->normalizeDownloadMime($value, $contentType);
                } elseif ($name === 'content-length') {
                    $length = (int)$value;
                    if ($length > 0) {
                        $contentLength = $length;
                    }
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (
                $url,
                $picId,
                $filename,
                $knownFileSize,
                $userId,
                &$contentType,
                &$contentLength,
                &$sourceStatus,
                &$headersSent,
                &$streamError,
                &$bytesSent
            ) {
                $length = strlen($chunk);
                if (!$headersSent) {
                    if ($sourceStatus > 0 && ($sourceStatus < 200 || $sourceStatus >= 300)) {
                        $streamError = '原图读取失败';
                        return 0;
                    }
                    $recordSize = $contentLength > 0 ? $contentLength : $knownFileSize;
                    try {
                        $this->userService->recordDownloadTraffic([
                            'pic_id' => $picId,
                            'file_url' => $url,
                            'file_size' => $recordSize,
                        ], $userId);
                    } catch (\Throwable $e) {
                        $streamError = $e->getMessage() ?: '流量扣减失败，请稍后重试';
                        return 0;
                    }
                    $this->sendOriginalDownloadHeaders($filename, $contentType, $contentLength);
                    $headersSent = true;
                }
                $bytesSent += $length;
                echo $chunk;
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
                return $length;
            },
        ]);
        $result = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$headersSent) {
            if ($streamError !== '') {
                throwError($streamError);
            }
            if ($result === false || $status < 200 || $status >= 300 || $bytesSent <= 0) {
                throwError($error ?: '原图读取失败');
            }
        }
        exit;
    }

    private function sendOriginalDownloadHeaders($filename, $contentType, $contentLength)
    {
        $origin = CorsOriginService::resolveAllowedOrigin((string)$this->request->header('origin', ''));
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        if ($fallbackName === '') {
            $fallbackName = 'image.jpg';
        }
        if ($origin !== '') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: ' . (CorsOriginService::allowCredentials($origin) ? 'true' : 'false'));
            header('Access-Control-Expose-Headers: Content-Disposition, Content-Length, Content-Type');
        }
        header('Vary: Origin');
        header('X-Accel-Buffering: no');
        header('Content-Type: ' . $contentType);
        if ($contentLength > 0) {
            header('Content-Length: ' . $contentLength);
        }
        header('Content-Disposition: inline; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=300');
    }

    private function normalizeDownloadMime($mime, $fallback = 'application/octet-stream')
    {
        $mime = strtolower(trim(explode(';', (string)$mime)[0]));
        if (preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/', $mime)) {
            return $mime;
        }
        return $fallback;
    }

    private function detectDownloadMimeFromFilename($filename)
    {
        $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    private function sanitizeDownloadFilename($filename)
    {
        $filename = trim($filename);
        $filename = preg_replace('/[\\\\\/:*?"<>|\r\n]+/', '_', $filename);
        return $filename ?: 'image.jpg';
    }

    public function downloadOriginalZip()
    {
        $param = array_merge($this->request->get(), $this->request->post());
        $picIds = $this->normalizeDownloadPicIds($param['pic_ids'] ?? ($param['picIds'] ?? []));
        if (empty($picIds) && !empty($param['pic_id'])) {
            $picIds = $this->normalizeDownloadPicIds([$param['pic_id']]);
        }
        if (count($picIds) === 1) {
            $param['pic_id'] = $picIds[0];
            return $this->streamOriginalDownload($param, (int)request()->userID());
        }
        if (count($picIds) > 1 && count($picIds) <= self::ORIGINAL_ZIP_MIN_PICTURE_COUNT) {
            throwError('5张以内请逐张下载');
        }
        if (!class_exists('\ZipArchive')) {
            throwError('服务器暂不支持打包下载');
        }

        $items = $this->userService->getOriginalZipDownloadItems($param, (int)request()->userID());
        $workDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'jfy_zip_' . uniqid('', true);
        if (!mkdir($workDir, 0700, true) && !is_dir($workDir)) {
            throwError('打包目录创建失败');
        }
        $zipPath = $workDir . DIRECTORY_SEPARATOR . 'images.zip';

        try {
            $files = $this->fetchOriginalZipFiles($items, $workDir);
            if (empty($files)) {
                throwError('原图读取失败');
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throwError('ZIP创建失败');
            }
            $usedNames = [];
            foreach ($files as $file) {
                $entryName = $this->uniqueZipEntryName($file['file_name'], $usedNames);
                $zip->addFile($file['path'], $entryName);
                if (method_exists($zip, 'setCompressionName') && $this->shouldStoreZipEntry($entryName)) {
                    $zip->setCompressionName($entryName, \ZipArchive::CM_STORE);
                }
            }
            $zip->close();

            foreach ($files as $file) {
                $this->userService->recordDownloadTraffic([
                    'pic_id' => (int)$file['pic_id'],
                    'file_url' => (string)$file['url'],
                    'file_size' => (int)$file['file_size'],
                ], (int)request()->userID());
            }

            $filename = $this->sanitizeDownloadFilename((string)($param['filename'] ?? 'product-images.zip'));
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
                $filename .= '.zip';
            }
            $this->streamZipFile($zipPath, $filename, $workDir);
        } catch (\Throwable $e) {
            $this->cleanupDownloadTempDir($workDir);
            throw $e;
        }
    }

    private function normalizeDownloadPicIds($picIds)
    {
        if (is_string($picIds)) {
            $decoded = json_decode($picIds, true);
            if (is_array($decoded)) {
                $picIds = $decoded;
            } else {
                $picIds = explode(',', $picIds);
            }
        }
        if (!is_array($picIds)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $picIds))));
    }

    private function fetchOriginalZipFiles(array $items, $workDir)
    {
        $queue = array_values($items);
        $maxConcurrency = 4;
        $multi = curl_multi_init();
        $active = [];
        $files = [];
        $failures = [];
        $index = 0;

        $addHandle = function () use (&$queue, &$active, &$index, $multi, $workDir) {
            if (empty($queue)) {
                return false;
            }
            $item = array_shift($queue);
            $index += 1;
            $path = $workDir . DIRECTORY_SEPARATOR . 'source_' . $index . '.bin';
            $fp = fopen($path, 'wb');
            if (!$fp) {
                throwError('临时文件创建失败');
            }
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => (string)$item['url'],
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_NOSIGNAL => true,
                CURLOPT_USERAGENT => 'JiafangyunZipDownload/1.0',
            ]);
            $key = (int)$ch;
            $active[$key] = [
                'handle' => $ch,
                'fp' => $fp,
                'path' => $path,
                'item' => $item,
            ];
            curl_multi_add_handle($multi, $ch);
            return true;
        };

        while (count($active) < $maxConcurrency && $addHandle()) {
        }

        do {
            do {
                $status = curl_multi_exec($multi, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($multi)) {
                $ch = $info['handle'];
                $key = (int)$ch;
                $meta = $active[$key] ?? null;
                if (!$meta) {
                    continue;
                }
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
                fclose($meta['fp']);
                unset($active[$key]);

                $size = is_file($meta['path']) ? (int)filesize($meta['path']) : 0;
                if ($info['result'] === CURLE_OK && $httpCode >= 200 && $httpCode < 300 && $size > 0) {
                    $files[] = [
                        'pic_id' => (int)$meta['item']['pic_id'],
                        'url' => (string)$meta['item']['url'],
                        'file_name' => (string)$meta['item']['file_name'],
                        'file_size' => $size,
                        'path' => $meta['path'],
                    ];
                } else {
                    @unlink($meta['path']);
                    $failures[] = (string)$meta['item']['file_name'] . ($error ? '：' . $error : '');
                }

                while (count($active) < $maxConcurrency && $addHandle()) {
                }
            }

            if (!empty($active)) {
                $selected = curl_multi_select($multi, 1.0);
                if ($selected === -1) {
                    usleep(100000);
                }
            }
        } while (!empty($active));

        curl_multi_close($multi);
        if (!empty($failures)) {
            throwError('部分图片下载失败：' . $failures[0]);
        }
        return $files;
    }

    private function uniqueZipEntryName($filename, array &$usedNames)
    {
        $filename = $this->sanitizeDownloadFilename((string)$filename);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === '') {
            $filename .= '.jpg';
            $ext = 'jpg';
        }
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $key = strtolower($base . '.' . $ext);
        $count = (int)($usedNames[$key] ?? 0);
        $usedNames[$key] = $count + 1;
        if ($count > 0) {
            return $base . '-' . ($count + 1) . '.' . $ext;
        }
        return $base . '.' . $ext;
    }

    private function shouldStoreZipEntry($filename)
    {
        $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    private function streamZipFile($zipPath, $filename, $workDir)
    {
        if (!is_file($zipPath)) {
            throwError('ZIP文件生成失败');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $origin = CorsOriginService::resolveAllowedOrigin((string)$this->request->header('origin', ''));
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        if ($fallbackName === '') {
            $fallbackName = 'product-images.zip';
        }
        if ($origin !== '') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: ' . (CorsOriginService::allowCredentials($origin) ? 'true' : 'false'));
            header('Access-Control-Expose-Headers: Content-Disposition, Content-Length, Content-Type');
        }
        header('Vary: Origin');
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($zipPath));
        header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, no-cache');
        readfile($zipPath);
        $this->cleanupDownloadTempDir($workDir);
        exit;
    }

    private function cleanupDownloadTempDir($dir)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }
        foreach (glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    /**获取指定用户的卡券列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponLists()
    {
        $param = $this->request->postMore([
            ['type', 1],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserCouponLists($param));
    }

    /**获取指定用户的卡券列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponCount()
    {
        $this->result($this->userService->getUserCouponCount());
    }

    /**获取用户指定卡券核销二维码
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponQrcode()
    {
        $param = $this->request->postMore([
            ['cid', 0],
        ]);
        $this->result($this->userService->getUserCouponQrcode($param));
    }

    /**获取指定卡券核销规则
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponUseRule()
    {
        $param = $this->request->postMore([
            ['cid', 0],
        ]);
        $this->result($this->userService->getCouponUseRule($param, request()->userID()));
    }

    /**获取用户代金券列表
     * @return void
     */
    public function getUserCouponVoucher()
    {
        $param = $this->request->postMore([
            ['pay_price', 0],
        ]);
        $this->result($this->userService->getUserCouponVoucher($param, request()->userID()));
    }

    /**获取用户消费流水
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserBalanceRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
        ]);
        $this->result($this->userService->getUserBalanceRecord($param, request()->userID()));
    }

    public function getUserGiveBalanceRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
        ]);
        $this->result($this->userService->getUserGiveBalanceRecord($param, request()->userID()));
    }

    /**获取用户资产流水
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAssetRecord()
    {
        $param = $this->request->postMore([
            ['type', 0],
            ['change_type', 0],
        ]);
        $this->result($this->userService->getUserAssetRecord($param, request()->userID(), request()->leaguerID()));
    }


    /**查询消费状态
     * @return void
     */
    public function getUserQrcodeScanStatus()
    {
        $this->result($this->userService->getUserQrcodeScanStatus(request()->leaguerID()));
    }

    /**获取用户游玩记录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPlayRecord()
    {
        $param = $this->request->getMore([
            ['type', 0],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserPlayRecord($param, request()->userID()));
    }

    /**用户上传游戏记录凭证
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserPlayVoucher()
    {
        $param = $this->request->postMore([
            ['pid', 0],
            ['voucher_thumb', 0],
        ]);
        $this->userService->setUserPlayVoucher($param, request()->userID());
        $this->result([]);
    }

    /**用户兑换零钱
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exchangeUserBalance()
    {
        $param = $this->request->postMore([
            ['balance', 0],
        ]);
        $this->userService->exchangeUserBalance($param, request()->userID());
        $this->result([]);
    }

    /**用户扫码绑定分销商
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userBindDistribution()
    {
        $param = $this->request->postMore([
            ['distribution_id', 0],
            ['openid', 0],
        ]);
        $this->result($this->userService->userBindDistribution($param));
    }

    public function getUserShowInfo()
    {
        $uid = request()->userID();
        (new WdXcxUser())->ensureHomePreferenceColumns();
        $syncedVipGradeInfo = (new JiafangyunEntitlementSyncService(app()))->syncUserQuietly($uid);
        $user = WdXcxUser::where('id', $uid)->find();
        if(!$user){
            throwError('用户不存在');
        }
        $vipGradeInfo = $user->VipGradeInfo;
        if (is_array($syncedVipGradeInfo)) {
            $vipGradeInfo = array_merge($vipGradeInfo, $syncedVipGradeInfo);
        }

        $result = [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => (int)$user->gender,
            'industry_info' => (int)$user->industry_info,
            'user_uuid' => $user->user_uuid,
            'is_new_user' => $user->HasOrder > 0 ? 0 : 1,
            'all_space' => 0,
            'space_used' => '0.00',
            'grade_name' => $vipGradeInfo['grade_name'],
            'grade_level' => $vipGradeInfo['grade_level'],
            'end_time' => $vipGradeInfo['end_time'],
            'join_time' => $user->join_time,
            'wx_ewm' => $user->wx_ewm,
            'user_desc' => $user->user_desc,
            'upload_pwd' => $user->upload_pwd,
            'upload_pwd_expire_time' => (int)$user->upload_pwd_expire_time,
            'id' => $user->id,
            'company_name' => $user->company_name,
            'company_logo' => $user->company_logo,
            'company_desc' => $user->company_desc,
            'contact_mobile' => $user->contact_mobile,
            'contact_wechat' => $user->contact_wechat,
            'address_province' => $user->address_province,
            'address_city' => $user->address_city,
            'address_district' => $user->address_district,
            'address_detail' => $user->address_detail,
            'is_show_home' => (int)$user->is_show_home,
            'visit_no_need_nickname' => (int)$user->visit_no_need_nickname,
            'visit_no_need_mobile' => (int)$user->visit_no_need_mobile,
            'visit_allow_save_pic' => (int)$user->visit_allow_save_pic,
            'home_watermark_text' => $user->home_watermark_text,
            'home_service_name' => $user->home_service_name,
            'home_share_title' => $user->home_share_title,
            'home_share_desc' => $user->home_share_desc,
            'home_share_image' => $user->home_share_image,
            'share_code' => (new WdXcxUser())->ensureHomeShareCodeForUser($user),
            'home_share_code' => isset($user->home_share_code) ? $user->home_share_code : '',
            'invite_code' => (new WdXcxUser())->ensureInviteCodeForUser($user),
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'resource_storage_capacity_bytes' => (int)($syncedVipGradeInfo['resource_storage_capacity_bytes'] ?? 0),
            'resource_storage_used_bytes' => (int)($syncedVipGradeInfo['resource_storage_used_bytes'] ?? 0),
            'resource_storage_remaining_bytes' => (int)($syncedVipGradeInfo['resource_storage_remaining_bytes'] ?? 0),
            'used_traffic_bytes' => (int)($syncedVipGradeInfo['used_traffic_bytes'] ?? 0),
            'used_traffic_gb' => (float)($syncedVipGradeInfo['used_traffic_gb'] ?? 0),
            'traffic_used_gb' => (float)($syncedVipGradeInfo['traffic_used_gb'] ?? ($syncedVipGradeInfo['used_traffic_gb'] ?? 0)),
            'traffic_limit_bytes' => (int)($syncedVipGradeInfo['traffic_limit_bytes'] ?? ($syncedVipGradeInfo['monthly_traffic_limit_bytes'] ?? 0)),
            'traffic_limit_gb' => (float)($syncedVipGradeInfo['traffic_limit_gb'] ?? ($syncedVipGradeInfo['monthly_traffic_limit_gb'] ?? 0)),
            'traffic_gb' => (float)($syncedVipGradeInfo['monthly_traffic_limit_gb'] ?? ($syncedVipGradeInfo['traffic_limit_gb'] ?? 0)),
            'monthly_traffic_limit_bytes' => (int)($syncedVipGradeInfo['monthly_traffic_limit_bytes'] ?? ($syncedVipGradeInfo['traffic_limit_bytes'] ?? 0)),
            'monthly_traffic_limit_gb' => (float)($syncedVipGradeInfo['monthly_traffic_limit_gb'] ?? ($syncedVipGradeInfo['traffic_limit_gb'] ?? 0)),
            'traffic_remaining_bytes' => (int)($syncedVipGradeInfo['traffic_remaining_bytes'] ?? ($syncedVipGradeInfo['monthly_traffic_remaining_bytes'] ?? 0)),
            'traffic_remaining_gb' => (float)($syncedVipGradeInfo['traffic_remaining_gb'] ?? ($syncedVipGradeInfo['monthly_traffic_remaining_gb'] ?? 0)),
            'monthly_traffic_remaining_bytes' => (int)($syncedVipGradeInfo['monthly_traffic_remaining_bytes'] ?? ($syncedVipGradeInfo['traffic_remaining_bytes'] ?? 0)),
            'monthly_traffic_remaining_gb' => (float)($syncedVipGradeInfo['monthly_traffic_remaining_gb'] ?? ($syncedVipGradeInfo['traffic_remaining_gb'] ?? 0)),
            'monthly_traffic_exceeded' => (bool)($syncedVipGradeInfo['monthly_traffic_exceeded'] ?? false),
            'concurrency_limit' => (int)($syncedVipGradeInfo['concurrency_limit'] ?? 0),
        ];
        if($vipGradeInfo['space_size'] > 1024 * 1024){
            $result['all_space'] = bcdiv($vipGradeInfo['space_size'], 1024 * 1024) . 'T';
        }elseif ($vipGradeInfo['space_size'] > 1024){
            $result['all_space'] = bcdiv($vipGradeInfo['space_size'], 1024) . 'G';
        }else{
            $result['all_space'] = $vipGradeInfo['space_size'] . 'M';
        }
        $normalPicSize = (int)WdXcxPic::where('uid', $uid)->sum('size');
        $trashPicSize = (int)WdXcxPic::onlyTrashed()->where('uid', $uid)->sum('size');
        $UserPicSize = $normalPicSize + $trashPicSize;
        $result['normal_space_bytes'] = $normalPicSize;
        $result['trash_space_bytes'] = $trashPicSize;
        $result['use_space'] = $UserPicSize;
        if($UserPicSize > 0 && $vipGradeInfo['space_size'] > 0){
            $result['space_used'] = bcmul(bcdiv($UserPicSize, $vipGradeInfo['space_size'] * 1024 * 1024, 4), 100, 2);
        }else{
            $result['space_used'] = 0;
        }
        $result['legacy_use_space'] = $UserPicSize;
        $result['legacy_all_space'] = $result['all_space'];
        $result['legacy_space_used'] = $result['space_used'];

        $resourceCapacityBytes = (int)($result['resource_storage_capacity_bytes'] ?? 0);
        if ($resourceCapacityBytes > 0) {
            $resourceUsedBytes = max(0, (int)($result['resource_storage_used_bytes'] ?? 0));
            $resourceRemainingBytes = (int)($result['resource_storage_remaining_bytes'] ?? 0);
            if ($resourceRemainingBytes <= 0) {
                $resourceRemainingBytes = max($resourceCapacityBytes - $resourceUsedBytes, 0);
            }
            $result['use_space'] = $resourceUsedBytes;
            $result['all_space'] = $this->formatBytesAsStorageText($resourceCapacityBytes);
            $result['space_used'] = $this->formatPercent($resourceUsedBytes, $resourceCapacityBytes);
            $result['normal_space_bytes'] = $resourceUsedBytes;
            $result['trash_space_bytes'] = 0;
            $result['resource_storage_remaining_bytes'] = $resourceRemainingBytes;
        }
        $result['total_pics'] = WdXcxPic::where('uid', $uid)->count();
        $result['total_collects'] = WdXcxUserCollectPics::where('uid', $uid)->count();
        
        // Add statistics for My Page
        $result['pic_count'] = $result['total_pics'];
        $result['product_count'] = \app\common\model\album\WdXcxAlbumFolder::where('uid', $uid)->where('folder_type', 2)->count();
        $result['category_count'] = \app\common\model\album\WdXcxAlbumFolder::where('uid', $uid)->where('folder_type', 1)->count();
        $visitTable = Db::query("SHOW TABLES LIKE 'wd_xcx_user_visit_record'");
        if ($visitTable) {
            $result['view_count'] = WdXcxUserVisitRecord::where('target_uid', $uid)
                ->where('uid', '<>', $uid)
                ->count();
            $result['visitor_count'] = WdXcxUserVisitRecord::where('target_uid', $uid)
                ->where('uid', '<>', $uid)
                ->distinct(true)
                ->field('uid')
                ->select()
                ->count();
            $readTime = (int)($user->visit_read_time ?? 0);
            $badgeQuery = WdXcxUserVisitRecord::where('target_uid', $uid)
                ->where('uid', '<>', $uid);
            if ($readTime > 0) {
                $badgeQuery->whereRaw('(update_time > ? OR create_time > ?)', [$readTime, $readTime]);
            }
            $result['view_badge'] = $badgeQuery->count();
        } else {
            $result['view_count'] = 0;
            $result['visitor_count'] = 0;
            $result['view_badge'] = 0;
        }

        $this->result($result);
    }

    private function formatBytesAsStorageText($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return '0M';
        }
        $gb = 1024 * 1024 * 1024;
        $mb = 1024 * 1024;
        if ($bytes >= $gb) {
            return $this->trimStorageNumber($bytes / $gb) . 'G';
        }
        return $this->trimStorageNumber($bytes / $mb) . 'M';
    }

    private function trimStorageNumber($value)
    {
        $number = (float)$value;
        if ($number >= 100) {
            return (string)round($number);
        }
        return rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
    }

    private function formatPercent($used, $total)
    {
        $used = (float)$used;
        $total = (float)$total;
        if ($used <= 0 || $total <= 0) {
            return '0.00';
        }
        return number_format(min(100, max(0, ($used / $total) * 100)), 2, '.', '');
    }

    public function markUserVisitorsRead()
    {
        $this->userService->markVisitRecordsRead(request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户所有照片列表
     * @return void
     */
    public function getUserAllPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserAllPicsLists(request()->userID(), $param));
    }

    /**获取用户所有删除产品列表
     * @return void
     */
    public function getUserAllDeleteProductLists()
    {
        $params = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['key', '']
        ]);
        $this->result($this->userService->getUserAllDeleteProductLists(request()->userID(), $params));
    }

    /**用户恢复删除产品
     * @return void
     */
    public function userRestoreDeleteProducts()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
            ['product_ids', ''],
        ]);
        if(empty($param['pic_ids']) && empty($param['product_ids'])){
            throwError('请选择要恢复的产品');
        }
        $this->userService->userRestoreDeleteProducts($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除回收站产品
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDestroyDeleteProducts()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
            ['product_ids', ''],
        ]);
        if(empty($param['pic_ids']) && empty($param['product_ids'])){
            throwError('请选择要删除的产品');
        }
        $this->userService->userDestroyDeleteProducts($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**清空用户回收站
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function clearUserRecycleBin()
    {
        $count = $this->userService->clearUserRecycleBin(request()->userID());
        $this->result(['count' => $count], 0, '操作成功');
    }

    /**获取用户所有收藏照片列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllCollectPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserAllCollectPicsLists(request()->userID(), $param));
    }
    
    public function getUserShowAllCollectPicsLists()
    {
        $param = $this->request->getMore([
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserShowAllCollectPicsLists(request()->userID(), $param));
    }

    /**用户删除收藏照片
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteCollectPics()
    {
        $param = $this->request->postMore([
            ['collect_ids', ''],
        ]);
        if(empty($param['collect_ids'])){
            throwError('请选择要删除的图片');
        }
        $this->userService->userDeleteCollectPics($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除我的照片
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteMyPics()
    {
        $param = $this->request->postMore([
            ['pic_ids', ''],
        ]);
        if(empty($param['pic_ids'])){
            throwError('请选择要删除的图片');
        }
        $this->userService->userDeleteMyPics($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户收藏记录
     * @return void
     */
    public function getUserCollectRecords()
    {
        $param = $this->request->getMore([
            ['type', 'all'],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserCollectRecords(request()->userID(), $param));
    }

    /**添加收藏
     * @return void
     */
    public function addUserCollect()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->addUserCollect($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**取消收藏
     * @return void
     */
    public function cancelUserCollect()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->cancelUserCollect($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**获取用户访问记录
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserVisitRecords()
    {
        $param = $this->request->getMore([
            ['type', 'all'],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getUserVisitRecords(request()->userID(), $param));
    }

    /**获取访客记录
     * @return void
     */
    public function getUserVisitors()
    {
        $param = $this->request->getMore([
            ['page', 1],
            ['type', 'visitor'],
        ]);
        $this->result($this->userService->getUserVisitors(request()->userID(), $param));
    }

    /**添加用户访问记录
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addUserVisitRecord()
    {
        $param = $this->request->postMore([
            ['type', ''],
            ['id', 0],
        ]);
        $this->userService->addUserVisitRecord($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户删除访问记录
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteVisitRecord()
    {
        $param = $this->request->postMore([
            ['visit_ids', 0],
        ]);
        $this->userService->userDeleteVisitRecord($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**足迹文件夹列表
     * @return void
     */
    public function getVisitAlbumFolderLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getVisitAlbumFolderLists($param, request()->userID()));
    }

    public function getVisitAlbumPicLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->userService->getVisitAlbumPicLists($param, request()->userID()));
    }

    /**获取用户主页信息（公开）
     * @return void
     */
    public function getHomePageInfo()
    {
        $targetUserId = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($targetUserId, false);

        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
            // ignore
        }
        
        $this->result($this->userService->getHomePageInfo($targetUserId, $visitorUid));
    }

    public function getHomeCategories()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['fid', 0],
            ['include_current', 0],
            ['share_v', null],
            ['sv', null],
            ['direct_share', 0],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($params, false);
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $shareVersion = $params['share_v'] !== null && $params['share_v'] !== '' ? $params['share_v'] : $params['sv'];
        $this->result($this->userService->getHomeCategories($targetUserId, $visitorUid, $params['fid'], (int)$params['include_current'], $shareVersion, (int)$params['direct_share'] === 1));
    }

    public function getHomeProducts()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['cate_id', 0],
            ['category_id', 0],
            ['fid', 0],
            ['product_id', 0],
            ['direct_share', 0],
        ]);
        if (empty($params['cate_id'])) {
            $params['cate_id'] = $params['category_id'] ?: $params['fid'];
        }
        $targetUserId = $this->resolveHomeTargetUserId($params, false);
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        if (!empty($params['product_id'])) {
            $this->result($this->userService->getHomeProductsDetails($targetUserId, $params['product_id'], $visitorUid, (int)$params['direct_share'] === 1, $params['cate_id']));
        }
        $this->result($this->userService->getHomeProducts($targetUserId, $visitorUid, $params['cate_id'], (int)$params['direct_share'] === 1));
    }

    public function getHomeProductsDetails()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['product_id', 0],
            ['cate_id', 0],
            ['category_id', 0],
            ['fid', 0],
            ['direct_share', 0],
        ]);
        if (empty($params['cate_id'])) {
            $params['cate_id'] = $params['category_id'] ?: $params['fid'];
        }
        $targetUserId = $this->resolveHomeTargetUserId($params, false);
        $productId = $params['product_id'];
        if (!$productId) {
            throwError('参数错误');
        }
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $this->result($this->userService->getHomeProductsDetails($targetUserId, $productId, $visitorUid, (int)$params['direct_share'] === 1, $params['cate_id']));
    }

    public function getHomePictureDetail()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['pic_id', 0],
        ]);
        if (empty($params['target_user_id']) && !empty($params['uid'])) {
            $params['target_user_id'] = $params['uid'];
        }
        $targetUserId = $this->resolveHomeTargetUserId($params, false);
        $picId = (int)$params['pic_id'];
        if (!$picId) {
            throwError('参数错误');
        }
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $this->result($this->userService->getHomePictureDetail($targetUserId, $picId, $visitorUid));
    }

    public function getHomeMiniProgramCode()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['path', ''],
            ['type', 'home'],
            ['id', 0],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($params);
        $data = $this->userService->getHomeMiniProgramCode($targetUserId, $params['path'], $params['type'], $params['id']);
        $this->result($this->hideHomeMiniProgramInternalFields($data));
    }

    public function getHomeMiniProgramCodeImage()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['path', ''],
            ['type', 'home'],
            ['id', 0],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($params);
        $code = $this->userService->getHomeMiniProgramCode($targetUserId, $params['path'], $params['type'], $params['id']);
        $path = $code['qrcode_path'] ?? '';
        if (!$path || !is_file($path) || !is_readable($path)) {
            throwError('小程序码生成失败');
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throwError('小程序码生成失败');
        }
        return Response::create($content, 'html', 200)->header([
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function getHomeShareLink()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['uid', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['path', ''],
            ['type', 'home'],
            ['id', 0],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($params);
        $data = $this->userService->getHomeShareLink($targetUserId, $params['path'], $params['type'], $params['id']);
        $this->result($this->hideHomeMiniProgramInternalFields($data));
    }

    public function getHomeSharePoster()
    {
        $params = $this->request->getMore([
            ['target_user_id', 0],
            ['code', ''],
            ['share_code', ''],
            ['invite_code', ''],
            ['type', 'home'], // home, category, product
            ['id', 0], // category_id or product_id
            ['path', ''],
            ['cover_url', ''],
        ]);
        $targetUserId = $this->resolveHomeTargetUserId($params);
        $visitorUid = 0;
        try {
            $visitorUid = request()->userID();
        } catch (\Exception $e) {
        }
        $this->result($this->userService->getHomeSharePoster($targetUserId, $params['type'], $params['id'], $params['path'], $visitorUid, $params['cover_url']));
    }

    public function getShortLink()
    {
        $param = $this->request->postMore([
            ['path', ''],
            ['title', ''],
            ['is_permanent', 0],
        ]);
        $this->result($this->userService->getShortLink($param));
    }

    /**
     * 获取PC端扫码登录二维码
     */
    public function getLoginQrcode()
    {
        // 1. 调用微信接口生成二维码
        try {
            $app = (new WxService(3))->getAppData();
            $scene = md5(uniqid(mt_rand(), true));
            $result = $app->qrcode->temporary($scene, 600);
            
            if (!isset($result['ticket'])) {
                $errMsg = isset($result['errmsg']) ? $result['errmsg'] : 'Unknown error from WeChat';
                if (isset($result['errcode'])) {
                    $errMsg .= ' (' . $result['errcode'] . ')';
                }
                throw new \Exception($errMsg);
            }

            $url = $app->qrcode->url($result['ticket']);
        } catch (\Exception $e) {
            $msg = '获取二维码失败';
            if ($e->getCode()) {
                $msg .= ' [code: ' . $e->getCode() . ']';
            }
            if ($e->getMessage()) {
                $msg .= ': ' . $e->getMessage();
            }
            throwError($msg);
        }

        // 2. 写入登录态缓存（失败不影响二维码返回）
        try {
            Cache::set('login_scene_' . $scene, 'pending', 600);
        } catch (\Throwable $e) {
        }

        // 3. 尝试转成 dataURL（失败也不阻断）
        $dataUrl = null;
        try {
            $img = @file_get_contents($url);
            if ($img !== false) {
                $dataUrl = 'data:image/png;base64,' . base64_encode($img);
            }
        } catch (\Throwable $e2) {
        }

        $this->result([
            'url' => $url,
            'data_url' => $dataUrl,
            'scene' => $scene
        ]);
    }

    /**
     * 获取PC网页登录配置（微信开放平台网站应用）
     */
    public function getLoginOauthConfig()
    {
        $params = $this->request->getMore([
            ['redirect', ''],
        ]);

        $redirect = html_entity_decode(trim((string)$params['redirect']), ENT_QUOTES, 'UTF-8');
        if (!$redirect) {
            $redirect = $this->getFileWebBaseUrl();
        }
        if (!$this->isAllowedPcLoginRedirect($redirect)) {
            $redirect = $this->getFileWebBaseUrl();
        }

        $loginConfig = $this->resolvePcLoginConfig($redirect);

        if (empty($loginConfig['appid'])) {
            Log::warning('wechat pc login appid missing: ' . ($loginConfig['provider'] ?? 'unknown'));
            throwError('登录服务暂不可用');
        }

        $state = $this->createWechatLoginState($redirect, $loginConfig['provider']);
        $authUrl = 'https://open.weixin.qq.com/connect/qrconnect?' . http_build_query([
            'appid' => $loginConfig['appid'],
            'redirect_uri' => $loginConfig['callback'],
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state,
        ]) . '#wechat_redirect';

        $this->result([
            'appid' => $loginConfig['appid'],
            'scope' => 'snsapi_login',
            'redirect_uri' => $loginConfig['callback'],
            'state' => $state,
            'auth_url' => $authUrl,
            'authUrl' => $authUrl,
        ]);
    }

    private function isAllowedPcLoginRedirect($redirect)
    {
        $parts = parse_url($redirect);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        $host = strtolower($parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }
        return $host === 'jfyuntu.com' || substr($host, -12) === '.jfyuntu.com'
            || $host === 'izhixu.com' || substr($host, -11) === '.izhixu.com';
    }

    private function getFileWebBaseUrl()
    {
        $base = trim((string)env('JIAFANGYUN_FILE_WEB_BASE_URL', getenv('JIAFANGYUN_FILE_WEB_BASE_URL') ?: ''));
        if ($base === '') {
            $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
            $base = strpos($host, 'api-test.jfyuntu.com') !== false
                ? 'https://file-test.jfyuntu.com/'
                : 'https://file.jfyuntu.com/';
        }
        return rtrim($base, '/') . '/';
    }

    private function getWechatWebLoginCallback()
    {
        $callback = trim((string)Config::get('miniprogram.web_login_callback'));
        if ($callback === '') {
            $callback = $this->getFileWebBaseUrl() . 'wechat-login-callback';
        }
        return $callback;
    }

    private function getAlbumPcLoginCallback()
    {
        $callback = trim((string)env('JIAFANGYUN_PC_LOGIN_CALLBACK', getenv('JIAFANGYUN_PC_LOGIN_CALLBACK') ?: ''));
        if ($callback === '') {
            $callback = 'https://api.jfyuntu.com/api/user/login/callback';
        }
        return $callback;
    }

    private function isFileWebLoginRedirect($redirect)
    {
        $host = strtolower((string)parse_url($redirect, PHP_URL_HOST));
        return in_array($host, ['file.jfyuntu.com', 'file-test.jfyuntu.com'], true);
    }

    private function resolvePcLoginConfig($redirect, $provider = '')
    {
        $provider = trim((string)$provider);
        if ($provider === '') {
            $provider = $this->isFileWebLoginRedirect($redirect) ? 'file_web' : 'album_pc';
        }

        if ($provider === 'file_web') {
            return [
                'provider' => 'file_web',
                'appid' => trim((string)Config::get('miniprogram.web_appid')),
                'secret' => trim((string)Config::get('miniprogram.web_appsecret')),
                'callback' => $this->getWechatWebLoginCallback(),
            ];
        }

        return [
            'provider' => 'album_pc',
            'appid' => trim((string)Config::get('miniprogram.account_appid')),
            'secret' => trim((string)Config::get('miniprogram.account_appsecret')),
            'callback' => $this->getAlbumPcLoginCallback(),
        ];
    }

    private function createWechatLoginState($redirect, $provider = '')
    {
        try {
            $state = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $state = md5(uniqid(mt_rand(), true));
        }
        Cache::set('wechat_web_login_state_' . $state, [
            'redirect' => $redirect,
            'provider' => $provider ?: ($this->isFileWebLoginRedirect($redirect) ? 'file_web' : 'album_pc'),
            'created_at' => time(),
        ], 600);
        return $state;
    }

    private function consumeWechatLoginState($state)
    {
        $state = trim((string)$state);
        if ($state !== '') {
            try {
                $cached = Cache::get('wechat_web_login_state_' . $state);
                if (is_array($cached) && !empty($cached['redirect'])) {
                    Cache::delete('wechat_web_login_state_' . $state);
                    return $cached;
                }
            } catch (\Throwable $e) {
            }

            $decoded = base64_decode($state, true);
            if ($decoded !== false) {
                return ['redirect' => (string)$decoded, 'legacy' => true];
            }
        }

        return null;
    }

    private function normalizeWechatLoginRedirect($redirectUrl)
    {
        $redirectUrl = html_entity_decode(trim((string)$redirectUrl), ENT_QUOTES, 'UTF-8');
        if ($redirectUrl === '' || !$this->isAllowedPcLoginRedirect($redirectUrl)) {
            return $this->getFileWebBaseUrl();
        }
        return $redirectUrl;
    }

    private function resolveWechatLoginRedirect($state)
    {
        $cached = $this->consumeWechatLoginState($state);
        return $this->normalizeWechatLoginRedirect(is_array($cached) ? ($cached['redirect'] ?? '') : '');
    }

    private function appendLoginQuery($url, $params)
    {
        $fragment = '';
        $hashPosition = strpos($url, '#');
        if ($hashPosition !== false) {
            $fragment = substr($url, $hashPosition);
            $url = substr($url, 0, $hashPosition);
        }
        $separator = (parse_url($url, PHP_URL_QUERY) === null) ? '?' : '&';
        return $url . $separator . http_build_query($params) . $fragment;
    }

    private function requestWechatJson($url, $params)
    {
        $requestUrl = $url . '?' . http_build_query($params);
        $body = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $body = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($body === false) {
                throw new \Exception('wechat request failed: ' . $curlError);
            }
        } else {
            $context = stream_context_create([
                'http' => ['timeout' => 10],
            ]);
            $body = @file_get_contents($requestUrl, false, $context);
            if ($body === false) {
                throw new \Exception('wechat request failed');
            }
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new \Exception('wechat response invalid');
        }
        if (!empty($data['errcode'])) {
            throw new \Exception('wechat api error: ' . (int)$data['errcode']);
        }
        return $data;
    }

    private function getWechatWebAccessToken($code, $provider = '')
    {
        $loginConfig = $this->resolvePcLoginConfig('', $provider);
        if (empty($loginConfig['appid']) || empty($loginConfig['secret'])) {
            throw new \Exception('wechat web login config missing: ' . ($loginConfig['provider'] ?? 'unknown'));
        }
        $data = $this->requestWechatJson('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => $loginConfig['appid'],
            'secret' => $loginConfig['secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        if (empty($data['access_token']) || empty($data['openid'])) {
            throw new \Exception('wechat access token response incomplete');
        }
        return $data;
    }

    private function getWechatWebUserInfo($accessToken, $openid)
    {
        return $this->requestWechatJson('https://api.weixin.qq.com/sns/userinfo', [
            'access_token' => $accessToken,
            'openid' => $openid,
            'lang' => 'zh_CN',
        ]);
    }

    private function createWechatWebLoginToken($code, $provider = '')
    {
        $tokenInfo = $this->getWechatWebAccessToken($code, $provider);
        $openid = (string)$tokenInfo['openid'];
        $userInfo = $this->getWechatWebUserInfo((string)$tokenInfo['access_token'], $openid);

        $unionid = (string)($userInfo['unionid'] ?? ($tokenInfo['unionid'] ?? ''));

        // 查找或创建用户
        $userModel = new \app\common\model\user\WdXcxUser();
        $user = $userModel->getUserByWechatIdentity($openid, $unionid, true, '', [
            'nickname' => $userInfo['nickname'] ?? '微信用户',
            'avatar' => $userInfo['headimgurl'] ?? '',
            'gender' => $userInfo['sex'] ?? 0,
            'uniacid' => $this->uniacid,
        ]);

        // 生成Token
        $tokenData = [
            'user_id' => $user->id,
            'openid' => $user->openid,
            'user_uuid' => $user->user_uuid,
        ];
        return \app\common\service\JwtService::createToken($tokenData);
    }

    /**
     * 前端回调页使用 code/state 换取登录 token
     */
    public function exchangeWechatLoginCode()
    {
        $code = trim((string)$this->request->param('code'));
        $state = trim((string)$this->request->param('state'));

        if (!$code || !$state) {
            throwError('登录失败，请重试');
        }

        try {
            $cached = $this->consumeWechatLoginState($state);
            if (!is_array($cached) || empty($cached['redirect']) || !empty($cached['legacy'])) {
                throw new \Exception('wechat login state invalid');
            }

            $token = $this->createWechatWebLoginToken($code, (string)($cached['provider'] ?? 'file_web'));
            $this->result([
                'token' => $token,
                'redirect' => $this->normalizeWechatLoginRedirect($cached['redirect']),
            ], 0, '登录成功');
        } catch (\Throwable $e) {
            Log::warning('wechat web login exchange failed: ' . $e->getMessage());
            throwError('登录失败，请重试');
        }
    }

    /**
     * 微信扫码登录回调
     */
    public function wechatCallback()
    {
        $code = trim((string)$this->request->param('code'));
        $state = trim((string)$this->request->param('state'));
        
        if (!$code) {
            return $this->redirectWithError($state);
        }
        
        try {
            $cached = $this->consumeWechatLoginState($state);
            $token = $this->createWechatWebLoginToken($code, (string)(is_array($cached) ? ($cached['provider'] ?? 'album_pc') : 'album_pc'));
            
            // 重定向回前端
            $redirectUrl = $this->normalizeWechatLoginRedirect(is_array($cached) ? ($cached['redirect'] ?? '') : '');
            $finalUrl = $this->appendLoginQuery($redirectUrl, [
                'token' => $token,
                'login' => 'success',
            ]);
            
            return redirect($finalUrl);
            
        } catch (\Throwable $e) {
            Log::warning('wechat web login failed: ' . $e->getMessage());
            return $this->redirectWithError($state);
        }
    }

    private function redirectWithError($state)
    {
        $redirectUrl = $this->resolveWechatLoginRedirect($state);
        return redirect($this->appendLoginQuery($redirectUrl, [
            'login' => 'failed',
            'error' => 'login_failed',
        ]));
    }

    /**
     * 检查登录状态
     */
    public function checkLoginStatus()
    {
        $scene = $this->request->param('scene');
        if (!$scene) {
            throwError('参数错误');
        }
        
        $data = Cache::get('login_scene_' . $scene);
        
        if (!$data) {
            $this->result(['status' => 'expired']);
        } elseif ($data === 'pending') {
            $this->result(['status' => 'pending']);
        } else {
            // $data is the token
            $this->result(['status' => 'success', 'token' => $data]);
        }
    }

    /**
     * 测试账号登录 (userid=19)
     */
    
    public function testLogin()
    {
        $userId = 19;
        $user = WdXcxUser::where('id', $userId)->find();
        
        if (!$user) {
            throwError('测试用户不存在');
        }

        $tokenData = [
            'user_id' => $user->id,
            'openid' => $user->openid,
            'user_uuid' => $user->user_uuid,
        ];
        $token = \app\common\service\JwtService::createToken($tokenData);
        
        $this->result([
            'token' => $token,
            'user_info' => $user
        ]);
    }

    private function ensureFeedbackTable()
    {
        $tableExists = Db::query("SHOW TABLES LIKE 'wd_xcx_album_feedback'");
        if (!$tableExists) {
            Db::execute("
                CREATE TABLE `wd_xcx_album_feedback` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `uid` int(11) NOT NULL DEFAULT 0,
                  `factory_uid` int(11) NOT NULL DEFAULT 0,
                  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1:问题反馈 2:功能建议',
                  `content` text NOT NULL,
                  `images` text COMMENT '图片列表',
                  `contact` varchar(255) DEFAULT '' COMMENT '联系方式',
                  `create_time` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `idx_uid` (`uid`),
                  KEY `idx_factory_uid` (`factory_uid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈';
            ");
        } else {
            // 检查并补充字段
            $fields = Db::query("SHOW COLUMNS FROM `wd_xcx_album_feedback`");
            $fieldNames = array_column($fields, 'Field');
            
            if (!in_array('type', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1:问题反馈 2:功能建议' AFTER `factory_uid`");
            }
            if (!in_array('images', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `images` text COMMENT '图片列表' AFTER `content`");
            }
            if (!in_array('contact', $fieldNames)) {
                Db::execute("ALTER TABLE `wd_xcx_album_feedback` ADD COLUMN `contact` varchar(255) DEFAULT '' COMMENT '联系方式' AFTER `images`");
            }
        }
    }

    public function feedback()
    {
        $param = $this->request->postMore([
            ['type', 1],
            ['content', ''],
            ['images', []],
            ['contact', ''],
        ]);
        
        if ($param['content'] === '') {
            throwError('请输入反馈内容');
        }

        $images = $param['images'];
        if (is_array($images)) {
            $images = json_encode($images, JSON_UNESCAPED_UNICODE);
        }

        $this->ensureFeedbackTable();
        $createTime = time();
        $feedbackId = Db::name('wd_xcx_album_feedback')->insertGetId([
            'uid' => request()->userID(),
            'type' => $param['type'],
            'content' => $param['content'],
            'images' => $images,
            'contact' => $param['contact'],
            'create_time' => $createTime,
        ]);
        $this->syncFeedbackToAiAdmin($feedbackId, $param, $images, $createTime);
        $this->result([], 0, '提交成功');
    }

    private function syncFeedbackToAiAdmin($feedbackId, $param, $images, $createTime)
    {
        try {
            $bridgeClient = new JiafangyunBridgeClient($this->app);
            $user = $bridgeClient->getUser(request()->userID());
            $imageList = [];
            if (is_array($param['images'])) {
                $imageList = $param['images'];
            } elseif (is_string($images) && $images !== '') {
                $decoded = json_decode($images, true);
                if (is_array($decoded)) {
                    $imageList = $decoded;
                }
            }
            $payload = array_merge($bridgeClient->userPayload($user), [
                'source_feedback_id' => (int)$feedbackId,
                'feedback_type' => (int)$param['type'],
                'type' => (int)$param['type'],
                'content' => (string)$param['content'],
                'images' => $imageList,
                'contact' => (string)$param['contact'],
                'created_at' => (int)$createTime,
                'source' => 'miniapp',
            ]);
            $bridgeClient->post('/jiafangyun/bridge/feedbacks', $payload);
        } catch (\Throwable $e) {
            Log::error('[JiafangyunFeedbackBridge] sync failed: feedback_id=' . (int)$feedbackId . ' error=' . $e->getMessage());
        }
    }
}
