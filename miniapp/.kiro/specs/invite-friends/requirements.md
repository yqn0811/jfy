# Requirements Document

## Introduction

好友邀请功能允许用户通过邀请好友注册来获得积分奖励。用户可以通过好友群邀请或复制邀请链接的方式邀请好友，成功邀请后可获得积分，被邀请的好友首次充值时用户还可获得额外积分奖励。

## Glossary

- **Invite_System**: 好友邀请系统
- **User**: 发起邀请的用户
- **Invitee**: 被邀请的好友
- **Points**: 积分奖励
- **Invitation_Link**: 邀请链接
- **Registration_Reward**: 注册奖励（好友成功注册后获得的积分）
- **Recharge_Reward**: 充值奖励（好友首次充值后获得的积分）

## Requirements

### Requirement 1: 页面UI展示

**User Story:** 作为用户，我想看到清晰的邀请奖励说明和邀请统计，以便了解邀请规则和我的邀请成果

#### Acceptance Criteria

1. WHEN 用户进入邀请页面 THEN THE Invite_System SHALL 显示顶部黄色渐变背景和礼物图标
2. WHEN 页面加载 THEN THE Invite_System SHALL 显示"每邀请1位好友，可获得"的标题文字
3. WHEN 页面加载 THEN THE Invite_System SHALL 显示两个奖励卡片："+10积分 成功注册"和"+1000积分 首次充值"
4. WHEN 页面加载 THEN THE Invite_System SHALL 显示"我获得的"统计区域，包含邀请人数和获得积分数
5. WHEN 页面加载 THEN THE Invite_System SHALL 显示"活动说明"区域，包含完整的活动规则说明

### Requirement 2: 好友群邀请功能

**User Story:** 作为用户，我想通过微信好友群邀请好友，以便快速分享邀请信息

#### Acceptance Criteria

1. WHEN 用户点击"好友群邀请"按钮 THEN THE Invite_System SHALL 触发微信分享到群功能
2. WHEN 分享成功 THEN THE Invite_System SHALL 在群聊中显示邀请卡片
3. WHEN 分享失败 THEN THE Invite_System SHALL 显示错误提示信息

### Requirement 3: 复制邀请链接功能

**User Story:** 作为用户，我想复制邀请链接，以便通过其他方式分享给好友

#### Acceptance Criteria

1. WHEN 用户点击"复制邀请链接"按钮 THEN THE Invite_System SHALL 生成包含用户ID的邀请链接
2. WHEN 链接生成成功 THEN THE Invite_System SHALL 将链接复制到剪贴板
3. WHEN 复制成功 THEN THE Invite_System SHALL 显示"复制成功"的提示信息
4. WHEN 复制失败 THEN THE Invite_System SHALL 显示"复制失败"的提示信息

### Requirement 4: 邀请统计数据展示

**User Story:** 作为用户，我想看到我的邀请统计数据，以便了解我邀请了多少人和获得了多少积分

#### Acceptance Criteria

1. WHEN 页面加载 THEN THE Invite_System SHALL 从服务器获取用户的邀请统计数据
2. WHEN 数据获取成功 THEN THE Invite_System SHALL 显示邀请人数（单位：人）
3. WHEN 数据获取成功 THEN THE Invite_System SHALL 显示获得积分数（单位：积分）
4. WHEN 数据获取失败 THEN THE Invite_System SHALL 显示默认值"0人"和"0积分"
5. WHEN 统计数据更新 THEN THE Invite_System SHALL 实时刷新显示的数值

### Requirement 5: 活动规则说明

**User Story:** 作为用户，我想看到详细的活动规则，以便了解如何获得积分奖励

#### Acceptance Criteria

1. WHEN 页面加载 THEN THE Invite_System SHALL 显示"活动说明"标题
2. WHEN 页面加载 THEN THE Invite_System SHALL 显示规则1："邀请新用户注册成功，即可获得10积分奖励"
3. WHEN 页面加载 THEN THE Invite_System SHALL 显示规则2："被邀请用户充值即可获得1000积分"
4. WHEN 页面加载 THEN THE Invite_System SHALL 显示规则3："所有奖励上不封顶，长期有效"
5. WHEN 页面加载 THEN THE Invite_System SHALL 显示规则4："禁止作弊，发现将取消资格"
6. WHEN 页面加载 THEN THE Invite_System SHALL 显示规则5："最终解释权归平台所有"

### Requirement 6: 页面导航

**User Story:** 作为用户，我想能够返回上一页，以便在查看完邀请信息后继续使用其他功能

#### Acceptance Criteria

1. WHEN 用户点击返回按钮 THEN THE Invite_System SHALL 返回到上一个页面
2. WHEN 页面加载 THEN THE Invite_System SHALL 显示顶部导航栏和返回按钮

### Requirement 7: 响应式布局

**User Story:** 作为用户，我想在不同设备上都能正常查看邀请页面，以便在任何情况下都能使用邀请功能

#### Acceptance Criteria

1. WHEN 页面在不同屏幕尺寸设备上显示 THEN THE Invite_System SHALL 自适应调整布局
2. WHEN 页面滚动 THEN THE Invite_System SHALL 保持顶部导航栏固定
3. WHEN 内容超出屏幕高度 THEN THE Invite_System SHALL 允许页面垂直滚动
