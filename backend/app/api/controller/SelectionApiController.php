<?php

namespace app\api\controller;

use app\common\service\album\SelectionService;
use think\App;

class SelectionApiController extends ApiBaseController
{
    private $selection_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->selection_service = new SelectionService($app);
    }

    public function createSelection()
    {
        $param = $this->request->postMore([
            ['product_id', 0],
            ['pic_ids', []],
            ['factory_uid', 0],
        ]);
        if (empty($param['product_id'])) {
            throwError('请选择产品');
        }
        if (empty($param['pic_ids'])) {
            throwError('请选择花色图');
        }
        if (empty($param['factory_uid'])) {
            throwError('请选择厂家');
        }
        $info = $this->selection_service->createSelection(request()->userID(), $param['product_id'], $param['pic_ids'], $param['factory_uid']);
        $this->result($info, 0, '创建成功');
    }

    public function getSelectionDetail()
    {
        $param = $this->request->postMore([
            ['selection_id', 0],
            ['uid', 0],
            ['target_user_id', 0],
            ['code', ''],
            ['share_code', ''],
        ]);
        $factoryTarget = $param['code'] ?: ($param['share_code'] ?: ($param['target_user_id'] ?: $param['uid']));
        $info = $this->selection_service->getSelectionDetail($param['selection_id'], request()->userID(), $factoryTarget);
        $this->result($info);
    }

    public function getMySelectionLists()
    {
        $param = $this->request->postMore([
            ['limit', 10],
        ]);
        $this->result($this->selection_service->getMySelectionLists($param, request()->userID()));
    }

    public function getCustomerSelectionLists()
    {
        $param = $this->request->postMore([
            ['limit', 10],
        ]);
        $this->result($this->selection_service->getCustomerSelectionLists($param, request()->userID()));
    }

    public function updateSelection()
    {
        $param = $this->request->postMore([
            ['selection_id', 0],
            ['name', ''],
        ]);
        if (empty($param['selection_id'])) {
            throwError('请选择选款单');
        }
        $this->selection_service->updateSelectionName($param['selection_id'], request()->userID(), $param['name']);
        $this->result([], 0, '修改成功');
    }

    public function addSelectionImages()
    {
        $param = $this->request->postMore([
            ['selection_id', 0],
            ['pic_ids', []],
        ]);
        if (empty($param['selection_id'])) {
            throwError('请选择选款单');
        }
        if (empty($param['pic_ids'])) {
            throwError('请选择花色图');
        }
        $this->selection_service->addSelectionImages($param['selection_id'], request()->userID(), $param['pic_ids']);
        $this->result([], 0, '操作成功');
    }

    public function removeSelectionImages()
    {
        $param = $this->request->postMore([
            ['selection_id', 0],
            ['pic_ids', []],
        ]);
        if (empty($param['selection_id'])) {
            throwError('请选择选款单');
        }
        if (empty($param['pic_ids'])) {
            throwError('请选择花色图');
        }
        $this->selection_service->removeSelectionImages($param['selection_id'], request()->userID(), $param['pic_ids']);
        $this->result([], 0, '操作成功');
    }

    public function deleteSelection()
    {
        $param = $this->request->postMore([
            ['selection_id', 0],
        ]);
        if (empty($param['selection_id'])) {
            throwError('请选择选款单');
        }
        $this->selection_service->deleteSelection($param['selection_id'], request()->userID());
        $this->result([], 0, '删除成功');
    }
}
