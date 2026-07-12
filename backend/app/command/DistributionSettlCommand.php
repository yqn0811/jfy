<?php

namespace app\command;

use app\common\model\distribution\WdXcxDistributionOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCommissionRecord;
use app\common\service\distribution\DistributionService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class DistributionSettlCommand extends Command
{
    /**分销订单结算命令
     * @return void
     */
    protected function configure()
    {
        $this->setName('DistributionSettl')
            ->setDescription('分销订单结算');
    }

    /**执行内容
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->settlementDistributionOrder($output);
    }

    /**订单结算
     * @param $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function settlementDistributionOrder($output)
    {
        //查询待结算的分销订单
        $orders = WdXcxDistributionOrderLists::where('status', 1)
            ->where('settle_time', '<', time())
            ->select();
        if(count($orders) > 0){
            foreach ($orders as $order){
                Db::startTrans();
                try {
                    (new WdXcxUserCommissionRecord())->addRecord($order->parent_id, [
                        'change_price' => $order->commission_money,
                        'order_id' => $order->order_id,
                        'message' => '订单佣金结算',
                        'change_source' => $order->orderform_type
                    ], WdXcxUserCommissionRecord::COMMISSION_CHANGE_ADD);
                    $order->status = 2;
                    $order->order_log = array_merge($order->order_log, [
                        [
                            'log' => '订单自动结算',
                            'time' => time()
                        ]
                    ]);
                    $order->save();
                }catch (\Exception $e){
                    Db::rollback();
                    $output->writeln('结算异常，订单ID'.$order->id.',错误信息：'.$e->getMessage());
                }
                Db::commit();
            }
        }else{
            $output->writeln('没有待结算的订单');
        }

    }
}