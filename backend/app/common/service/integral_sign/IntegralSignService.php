<?php

namespace app\common\service\integral_sign;

use app\common\service\BaseService;
use app\common\model\user\WdXcxUser;
use app\common\model\integral_sign\WdXcxUserIntegralSignRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\model\distribution\WdXcxDistributionUserParent;
use app\common\model\integral_sign\WdXcxIntegralTask;
use app\common\model\integral_sign\WdXcxUserIntegralTaskRecord;
use app\common\service\WxService;
use think\facade\Db;
use cores\exception\BaseException;
use think\facade\Log;

class IntegralSignService extends BaseService
{
    // 签到积分规则
    protected $signRules = [
        1 => 10,
        2 => 10,
        3 => 10,
        4 => 10,
        5 => 10,
        6 => 30,
        7 => 50
    ];

    // 邀请奖励规则
    protected $inviteRules = [
        'register' => 10, // 注册奖励
        'recharge' => 1000 // 充值奖励
    ];

    /**
     * 获取积分签到首页数据
     */
    public function getIndexData($userId)
    {
        $user = WdXcxUser::find($userId);
        if (!$user) {
            throw new BaseException(['msg' => '用户不存在']);
        }
        $this->loadSignRules($user->uniacid ?? 1);

        // 1. 获取今日签到状态
        $today = date('Y-m-d');
        $todaySign = WdXcxUserIntegralSignRecord::where('user_id', $userId)
            ->where('sign_date', $today)
            ->find();
        $isSigned = $todaySign ? true : false;

        // 2. 获取连续签到天数
        // 如果今天已签到，取今天的 continue_days
        // 如果今天未签到，取昨天的 continue_days，如果昨天没签，则为 0
        $continueDays = 0;
        if ($isSigned) {
            $continueDays = $todaySign->continue_days;
        } else {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $yesterdaySign = WdXcxUserIntegralSignRecord::where('user_id', $userId)
                ->where('sign_date', $yesterday)
                ->find();
            if ($yesterdaySign) {
                $continueDays = $yesterdaySign->continue_days;
            }
        }

        // 3. 构建7天签到列表状态
        $signList = [];
        // 逻辑：展示第1天到第7天的配置，并标记当前用户处于哪一天（或者已经签到的天数）
        // 这里简化处理：直接展示规则，高亮已签到的天数
        // 如果连续签到中断，重置显示？通常 UI 是固定的 7 个格子
        // 我们返回 7 天的配置，以及当前连续签到进度
        
        // 如果连续天数 >= 7，下一天是第 1 天（循环）还是继续？通常是循环或保持。
        // 假设循环： days = continueDays % 7
        // 但 UI 显示 "已连续签到 6 天"，格子是第1-7天。
        // 我们返回当前周期的签到状态。
        
        $currentCycleDay = $continueDays % 7; 
        if ($currentCycleDay == 0 && $continueDays > 0 && !$isSigned) {
            // 如果刚好是7的倍数且今天没签，说明是新的一轮第1天（但在UI上可能显示上一轮满的状态，或者新一轮第0天）
            // 简单起见，如果今天没签，currentCycleDay 就是昨天的 + 0，即显示下一天待签到
            // 如果 yesterday continueDays = 7, today is day 1 of next cycle.
            // let's stick to simple logic:
        }

        foreach ($this->signRules as $day => $score) {
            // 状态：0-未签/未来，1-已签，2-漏签(通常不显示漏签，只显示进度)
            // 这里简单返回：是否已签到
            // 如果 $day <= $continueDays (且在当前周期内)，则为已签到
            // 复杂点：如果 continueDays = 8，那是第二轮的第1天。
            // 简化：UI通常只显示一轮。
            // 我们计算当前轮次：
            // 实际上，如果连续签到 6 天，那么前 6 个格子亮。
            
            // 处理循环逻辑：
            // 也就是看 continueDays 对 7 取模。
            // 但是如果今天已签到，且是第7天，那么 continueDays=7，显示满。
            // 如果今天未签到，昨天是第7天，那么今天是第8天（新的一轮第1天），UI应该重置。
            
            $isDaySigned = false;
            
            // 逻辑修正：
            // 如果 $isSigned 为 true: 显示 $continueDays 的状态 (例如 6，则 1-6 亮)
            // 如果 $isSigned 为 false: 显示 $continueDays 的状态 (例如昨天是 5，今天是 6，则 1-5 亮，第6个待签)
            // 考虑到循环：
            // displayDays = $continueDays % 7
            // if ($continueDays > 0 && $continueDays % 7 == 0) displayDays = 7; 
            
            $displayDays = $continueDays % 7;
            if ($continueDays > 0 && $continueDays % 7 == 0) {
                 // 刚好满周期
                 // 如果今天已签到，说明刚签了第7天/14天...，显示满格
                 if ($isSigned) {
                     $displayDays = 7;
                 } else {
                     // 今天没签，昨天满格，今天是新的一轮 Day 1，显示空
                     $displayDays = 0;
                 }
            }
            
            if ($day <= $displayDays) {
                $status = 1; // 已签
            } else {
                $status = 0; // 未签
            }

            $signList[] = [
                'day' => $day,
                'score' => $score,
                'status' => $status
            ];
        }

        // 4. 任务列表
        $tasks = $this->getTaskList($userId, $isSigned);

        return [
            'total_integral' => $user->integral,
            'continue_days' => $continueDays,
            'is_signed' => $isSigned,
            'sign_rules' => $signList,
            'tasks' => $tasks
        ];
    }

    /**
     * 执行签到
     */
    public function doSign($userId)
    {
        $user = WdXcxUser::find($userId);
        Log::info('IntegralSignService doSign userId'. $userId);
        if (!$user) {
            throw new BaseException(['msg' => '用户不存在']);
        }
        $this->loadSignRules($user->uniacid ?? 1);

        $today = date('Y-m-d');
        Log::info('IntegralSignService doSign today'. $today);

        $exist = WdXcxUserIntegralSignRecord::where('user_id', $userId)
            ->where('sign_date', $today)
            ->find();

        Log::info('IntegralSignService doSign exist->id'. $exist);
        
        if ($exist) {
            return [
                'score' => (int)$exist->score,
                'continue_days' => (int)$exist->continue_days,
                'already_signed' => 1
            ];
        }

        // 计算连续天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdaySign = WdXcxUserIntegralSignRecord::where('user_id', $userId)
            ->where('sign_date', $yesterday)
            ->find();
        
        $continueDays = $yesterdaySign ? $yesterdaySign->continue_days + 1 : 1;
        
        // 计算积分
        // 循环规则：第8天=第1天
        $ruleDay = $continueDays % 7;
        if ($ruleDay == 0) $ruleDay = 7;
        
        $score = isset($this->signRules[$ruleDay]) ? $this->signRules[$ruleDay] : 10;

        Db::startTrans();
        try {
            // 1. 记录签到
            WdXcxUserIntegralSignRecord::create([
                'uniacid' => $user->uniacid ?? 1,
                'user_id' => $userId,
                'sign_date' => $today,
                'continue_days' => $continueDays,
                'score' => $score
            ]);

            // 2. 增加积分
            (new WdXcxUserIntegralRecord())->addRecord($user, [
                'change_integral' => $score,
                'order_id' => 0,
                'message' => '签到赠送',
                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_SIGN, // 90
                'is_end' => 0
            ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);

            Db::commit();
            return [
                'score' => $score,
                'continue_days' => $continueDays
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw new BaseException(['msg' => '签到失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取任务列表
     */
    public function getTaskList($userId, $isSigned)
    {
        // 1. 获取所有上架任务
        $tasks = WdXcxIntegralTask::where('is_show', 1)
            ->order('sort desc, id asc')
            ->select();

        $result = [];
        $todayStart = strtotime(date('Y-m-d 00:00:00'));

        foreach ($tasks as $task) {
            $isCompleted = false;

            if ($task->type == WdXcxIntegralTask::TYPE_ONCE) {
                // 一次性任务：只要有记录即为完成
                $record = WdXcxUserIntegralTaskRecord::where('user_id', $userId)
                    ->where('task_id', $task->id)
                    ->find();
                if ($record) {
                    $isCompleted = true;
                }
            } elseif ($task->type == WdXcxIntegralTask::TYPE_DAILY) {
                // 每日任务：检查今日记录
                $record = WdXcxUserIntegralTaskRecord::where('user_id', $userId)
                    ->where('task_id', $task->id)
                    ->where('create_time', '>=', $todayStart)
                    ->find();
                if ($record) {
                    $isCompleted = true;
                }
            }

            // 构建返回数据
            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'desc' => $task->desc,
                'icon' => $task->icon,
                'score' => $task->score,
                'btn_text' => $isCompleted ? '已完成' : $task->btn_text,
                'status' => $isCompleted ? 1 : 0,
                'task_key' => $task->task_key,
                'type' => $task->type
            ];
        }

        return $result;
    }

    /**
     * 完成任务
     * @param int $userId 用户ID
     * @param string $taskKey 任务标识
     * @return array ['score' => 获得积分, 'msg' => 提示信息]
     */
    public function finishTask($userId, $taskKey)
    {
        $user = WdXcxUser::find($userId);
        if (!$user) {
            throw new BaseException(['msg' => '用户不存在']);
        }

        $task = WdXcxIntegralTask::where('task_key', $taskKey)
            ->where('is_show', 1)
            ->find();
        
        if (!$task) {
            throw new BaseException(['msg' => '任务不存在或已下架']);
        }

        // 检查是否已完成
        $isCompleted = false;
        if ($task->type == WdXcxIntegralTask::TYPE_ONCE) {
            $exist = WdXcxUserIntegralTaskRecord::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->find();
            if ($exist) $isCompleted = true;
        } elseif ($task->type == WdXcxIntegralTask::TYPE_DAILY) {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $exist = WdXcxUserIntegralTaskRecord::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->where('create_time', '>=', $todayStart)
                ->find();
            if ($exist) $isCompleted = true;
        }

        if ($isCompleted) {
            throw new BaseException(['msg' => '任务已完成，请勿重复操作']);
        }

        Db::startTrans();
        try {
            // 1. 记录完成
            WdXcxUserIntegralTaskRecord::create([
                'user_id' => $userId,
                'task_id' => $task->id,
                'score' => $task->score,
                'create_time' => time()
            ]);

            // 2. 发放奖励
            if ($task->score > 0) {
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'change_integral' => $task->score,
                    'order_id' => 0,
                    'message' => '完成任务：' . $task->title,
                    'change_source' => 131, // 假设131为任务奖励
                    'is_end' => 0
                ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
            }

            Db::commit();
            return ['score' => $task->score, 'msg' => '任务完成'];
        } catch (\Exception $e) {
            Db::rollback();
            throw new BaseException(['msg' => '任务处理失败: ' . $e->getMessage()]);
        }
    }

    public function getIntegralRecords($userId, $page = 1, $limit = 10, $type = null)
    {
        $user = WdXcxUser::find($userId);
        if (!$user) {
            throw new BaseException(['msg' => '用户不存在']);
        }
        $query = WdXcxUserIntegralRecord::where('user_id', $userId);
        if ($type === 1 || $type === 'del') {
            $query = $query->where('change_type', WdXcxUserIntegralRecord::INTEGRAL_CHANGE_DEL);
        } elseif ($type === 2 || $type === 'add') {
            $query = $query->where('change_type', WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
        }
        $lists = $query->order('create_time desc')
            ->paginate(['list_rows' => $limit, 'page' => $page])
            ->each(function ($item) {
                $item->create_time_text = date('Y-m-d H:i', (int)$item->getData('create_time'));
            });
        return [
            'lists' => $lists->toArray()['data'],
            'total_page' => $lists->lastPage(),
            'curr_page' => $lists->currentPage(),
        ];
    }

    protected function loadSignRules($uniacid)
    {
        try {
            $tables = Db::query("SHOW TABLES LIKE 'wd_xcx_integral_sign_set'");
            if (!$tables) {
                return;
            }
            $row = Db::table('wd_xcx_integral_sign_set')
                ->where('uniacid', $uniacid)
                ->order('update_time', 'desc')
                ->find();
            if (!$row) {
                $row = Db::table('wd_xcx_integral_sign_set')
                    ->order('update_time', 'desc')
                    ->find();
            }
            if ($row && isset($row['rules']) && $row['rules']) {
                $data = json_decode($row['rules'], true);
                if (is_array($data)) {
                    $map = [];
                    foreach ($data as $item) {
                        if (isset($item['day']) && isset($item['score'])) {
                            $map[(int)$item['day']] = (int)$item['score'];
                        }
                    }
                    if ($map) {
                        $this->signRules = $map;
                    }
                }
            }
        } catch (\Throwable $e) {
        }
    }
    /**
     * 获取邀请页面数据
     */
    public function getInviteIndex($userId)
    {
        $user = WdXcxUser::find($userId);
        if (!$user) {
            throw new BaseException(['msg' => '用户不存在']);
        }
        $inviteCode = $this->ensureInviteCode($user);
        $invitePath = 'pages/index/index';
        $inviteQuery = http_build_query(['invite_code' => $inviteCode]);
        $inviteMiniPath = $invitePath . '?' . $inviteQuery;
        $inviteLink = '';
        try {
            $inviteLink = (new WxService())->generateUrlLink($invitePath, $inviteQuery);
        } catch (\Throwable $e) {
            Log::error('getInviteIndex generate invite link failed: ' . $e->getMessage());
            try {
                $inviteLink = (new WxService())->generateShortLink('/' . $inviteMiniPath, '邀请好友得积分奖励', false);
            } catch (\Throwable $shortLinkError) {
                Log::error('getInviteIndex generate invite short link failed: ' . $shortLinkError->getMessage());
            }
        }
        
        // 邀请人数
        $inviteCount = $user->son_count ?? 0; // 利用模型中的 getSonCountAttr
        if ($inviteCount === null) {
             // 如果模型属性没生效，手动查
             $inviteCount = WdXcxDistributionUserParent::where('parent_id', $userId)->count();
        }

        // 获得积分
        // 查询 integral_record 中 source=130 (假设为邀请奖励) 的总和
        // 或者 100? WdXcxUser::PROPERTY_CHANGE_SOURCE_REWARD
        // 假设我们定义一个新的 source ID for invite reward, e.g., 130
        $inviteScore = WdXcxUserIntegralRecord::where('user_id', $userId)
            ->where('change_source', 130) 
            ->sum('change_integral');

        return [
            'invite_count' => $inviteCount,
            'invite_score' => $inviteScore,
            'invite_code' => $inviteCode,
            'invite_path' => $inviteMiniPath,
            'invite_link' => $inviteLink,
            'url_link' => $inviteLink,
            'rules' => [
                '邀请新用户并成功注册，即可获得10积分奖励',
                '被邀请用户充值即可获得1000积分',
                '所有奖励上不封顶，长期有效',
                '禁止作弊，发现将取消资格',
                '最终解释权归家纺云相册所有'
            ]
        ];
    }

    private function ensureInviteCode($user)
    {
        $code = trim((string)($user->invite_code ?? ''));
        if ($code !== '') {
            return $code;
        }

        do {
            $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
            $exists = WdXcxUser::where('invite_code', $code)->find();
        } while ($exists);

        $user->invite_code = $code;
        $user->save();
        return $code;
    }

    /**
     * 获取邀请列表
     */
    public function getInviteList($userId, $page = 1, $limit = 10)
    {
        $list = WdXcxDistributionUserParent::alias('p')
            ->join('wd_xcx_user u', 'p.user_id = u.id')
            ->where('p.parent_id', $userId)
            ->field('u.id, u.nickname, u.avatar, p.create_time')
            ->order('p.create_time desc')
            ->paginate(['list_rows' => $limit, 'page' => $page])
            ->each(function($item) {
                $item->create_time_text = date('Y-m-d H:i', (int)$item->getData('create_time'));
            });
            
        return $list;
    }
    
    /**
     * 处理邀请奖励 (供外部调用，如注册/充值回调)
     * @param int $userId 被邀请人ID
     * @param string $type register|recharge
     */
    public function handleInviteReward($userId, $type)
    {
        // 找到上级
        $relation = WdXcxDistributionUserParent::where('user_id', $userId)->find();
        if (!$relation) return;
        
        $parentId = $relation->parent_id;
        $parent = WdXcxUser::find($parentId);
        if (!$parent) return;
        
        $score = 0;
        $msg = '';
        
        if ($type == 'register') {
            $score = $this->inviteRules['register'];
            $msg = '邀请好友注册奖励';
        } elseif ($type == 'recharge') {
            // 需判断是否首次充值？这里简化为每次都送，或者业务逻辑控制
            $score = $this->inviteRules['recharge'];
            $msg = '邀请好友充值奖励';
        }
        
        if ($score > 0) {
             (new WdXcxUserIntegralRecord())->addRecord($parent, [
                'change_integral' => $score,
                'order_id' => 0, // 关联ID
                'message' => $msg,
                'change_source' => 130, // 自定义：邀请奖励
                'is_end' => 0
            ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
        }
    }
}
