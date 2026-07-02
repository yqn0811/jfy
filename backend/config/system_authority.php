<?php

return [
    [
        'id' => 1,
        'title' => '总览',
        'children' => [
            ['id' => 2,'title' => '数据预览', 'path' => '/index/Datashow/index'],
            ['id' => 3,'title' => '扫码核销', 'path' => '/index/Datashow/scan'],
        ],
    ],
    [
        'id' => 4,
        'title' => '点餐',
        'children' => [
            ['id' => 5,'title' => '桌码管理', 'path' => '/index/RestaurantController/tables'],
            ['id' => 6,'title' => '菜品分类', 'path' => '/index/RestaurantController/cate'],
            ['id' => 7,'title' => '菜品管理', 'path' => '/index/RestaurantController/dishes'],
            ['id' => 8,'title' => '订单管理', 'path' => '/index/RestaurantController/index'],
        ],
    ],
    [
        'id' => 9,
        'title' => '预约',
        'children' => [
            ['id' => 10,'title' => '预约分类', 'path' => '/index/AppointmentController/cate'],
            ['id' => 11,'title' => '预约管理', 'path' => '/index/AppointmentController/appointment'],
            ['id' => 12,'title' => '预约订单', 'path' => '/index/AppointmentController/orders'],
        ],
    ],
    [
        'id' => 13,
        'title' => '票务',
        'children' => [
            ['id' => 14,'title' => '套票管理', 'path' => '/index/TicketingController/ticketing'],
            ['id' => 15,'title' => '套票订单', 'path' => '/index/TicketingController/orders'],
        ],
    ],
    [
        'id' => 16,
        'title' => '卡券',
        'children' => [
            ['id' => 17,'title' => '卡券管理', 'path' => '/index/CouponsController/coupons'],
            ['id' => 18,'title' => '获取记录', 'path' => '/index/CouponsController/records'],
            ['id' => 51,'title' => '批量发券', 'path' => '/index/CouponsController/batch'],
            ['id' => 52,'title' => '发券记录', 'path' => '/index/CouponsController/batchRecords'],
        ],
    ],
    [
        'id' => 19,
        'title' => '会员',
        'children' => [
            ['id' => 20,'title' => '会员管理', 'path' => '/index/WxuserController/lists'],
            ['id' => 21,'title' => '会员等级', 'path' => '/index/WxuserController/vipGrade'],
            ['id' => 22,'title' => '充值记录', 'path' => '/index/WxuserController/recharge'],
            ['id' => 23,'title' => '余额记录', 'path' => '/index/WxuserController/consumption'],
            ['id' => 49,'title' => '零钱记录', 'path' => '/index/WxuserController/giveBalanceRecord'],
            ['id' => 24,'title' => '积分流水', 'path' => '/index/WxuserController/integralRecord'],
            ['id' => 25,'title' => '钻石流水', 'path' => '/index/WxuserController/diamond'],
            ['id' => 50,'title' => '微信支付', 'path' => '/index/WxuserController/wxpay'],
        ],
    ],
    [
        'id' => 57,
        'title' => '分销',
        'children' => [
            ['id' => 58,'title' => '基础设置', 'path' => '/index/DistributionController/base'],
            ['id' => 59,'title' => '分销商', 'path' => '/index/DistributionController/lists'],
            ['id' => 60,'title' => '分销订单', 'path' => '/index/DistributionController/orders'],
            ['id' => 61,'title' => '佣金提现', 'path' => '/index/DistributionController/commission'],
        ],
    ],
    [
        'id' => 26,
        'title' => '充值',
        'children' => [
            ['id' => 27,'title' => '充值套餐', 'path' => '/index/RechargeController/aggregate'],
        ],
    ],
    [
        'id' => 28,
        'title' => '签到',
        'children' => [
            ['id' => 29,'title' => '规则设置', 'path' => '/index/IntegralSignController/set'],
            ['id' => 30,'title' => '签到记录', 'path' => '/index/IntegralSignController/record'],
        ],
    ],
    [
        'id' => 31,
        'title' => '兑换',
        'children' => [
            ['id' => 48,'title' => '实物分类', 'path' => '/index/GiftExchangeController/cates'],
            ['id' => 32,'title' => '兑换管理', 'path' => '/index/GiftExchangeController/gifts'],
            ['id' => 33,'title' => '兑换订单', 'path' => '/index/GiftExchangeController/orders'],
        ],
    ],
    [
        'id' => 34,
        'title' => '权限',
        'children' => [
            ['id' => 35,'title' => '管理人员', 'path' => '/index/PermissionsController/manager'],
            ['id' => 36,'title' => '角色列表', 'path' => '/index/PermissionsController/manager'],
        ],
    ],
    [
        'id' => 37,
        'title' => '系统',
        'children' => [
            ['id' => 38,'title' => '基础设置', 'path' => '/index/SystemSetController/set'],
            ['id' => 39,'title' => '远程附件', 'path' => '/index/SystemSetController/remote'],
            ['id' => 40,'title' => '小票打印', 'path' => '/index/SystemSetController/printer'],
            ['id' => 44,'title' => '奖励设置', 'path' => '/index/SystemSetController/rewardSet'],
            ['id' => 53,'title' => '新人有礼', 'path' => '/index/SystemSetController/newUserGift'],
        ],
    ],
    [
        'id' => 41,
        'title' => '排行',
        'children' => [
            ['id' => 42,'title' => '游戏排行', 'path' => '/index/GameRankingController/index'],
            ['id' => 43,'title' => '游戏审核', 'path' => '/index/GameRankingController/gameCheck'],
        ],
    ],
    [
        'id' => 45,
        'title' => '游戏币',
        'children' => [
            ['id' => 46,'title' => '套餐管理', 'path' => '/index/GamecoinController/index'],
            ['id' => 47,'title' => '订单管理', 'path' => '/index/GamecoinController/orders'],
        ],
    ],
    [
        'id' => 54,
        'title' => '相册',
        'children' => [
            ['id' => 55,'title' => '相册分类', 'path' => '/index/AlbumController/cates'],
            ['id' => 56,'title' => '相册管理', 'path' => '/index/AlbumController/albumLists'],
        ],
    ],
];