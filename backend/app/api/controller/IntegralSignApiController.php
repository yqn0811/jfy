<?php

namespace app\api\controller;

use app\common\service\integral_sign\IntegralSignService;
use cores\exception\BaseException;
use think\App;

class IntegralSignApiController extends ApiBaseController
{
    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new IntegralSignService($app);
    }

    /**
     * 获取积分签到页数据
     */
    public function index()
    {
        $userId = $this->request->userID();
        $data = $this->service->getIndexData($userId);
        return $this->result($data, 0, '', 'json');
    }

    /**
     * 执行签到
     */
    public function sign()
    {
        $userId = $this->request->userID();
        try {
            $data = $this->service->doSign($userId);
            $msg = isset($data['already_signed']) ? '当天已签到' : '签到成功';
            return $this->result($data, 0, $msg, 'json');
        } catch (BaseException $e) {
            return $this->result([], 500, $e->getMessage(), 'json');
        }
    }

    /**
     * 获取邀请页面数据
     */
    public function inviteIndex()
    {
        $userId = $this->request->userID();
        $data = $this->service->getInviteIndex($userId);
        return $this->result($data, 0, '', 'json');
    }

    /**
     * 获取邀请记录
     */
    public function inviteList()
    {
        $userId = $this->request->userID();
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        if ($limit <= 0) $limit = 10;
        if ($page <= 0) $page = 1;
        $data = $this->service->getInviteList($userId, $page, $limit);
        return $this->result($data, 0, '', 'json');
    }

    /**
     * 完成任务（演示/前端触发类任务）
     */
    public function finishTask()
    {
        $userId = $this->request->userID();
        $taskKey = $this->request->param('task_key');
        if (empty($taskKey)) {
            return $this->result([], 500, '参数错误', 'json');
        }
        $data = $this->service->finishTask($userId, $taskKey);
        return $this->result($data, 0, '操作成功', 'json');
    }

    /**
     * 用户积分明细
     */
    public function integralRecords()
    {
        $userId = $this->request->userID();
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $type = $this->request->param('type', null); // 1|del 消费, 2|add 增加
        
        if ($limit <= 0) $limit = 10;
        if ($page <= 0) $page = 1;

        try {
            $data = $this->service->getIntegralRecords($userId, $page, $limit, $type);
            return $this->result($data, 0, '', 'json');
        } catch (BaseException $e) {
            return $this->result([], 500, $e->getMessage(), 'json');
        }
    }
}
