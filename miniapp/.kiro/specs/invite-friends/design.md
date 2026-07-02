# Design Document: 好友邀请功能

## Overview

好友邀请功能是一个基于 uni-app 框架开发的 Vue 单页面应用，允许用户通过微信分享或复制链接的方式邀请好友注册，并获得积分奖励。页面采用黄色渐变主题，展示清晰的奖励机制和用户邀请统计数据。

## Architecture

### 技术栈
- **框架**: uni-app (Vue 2)
- **UI库**: uview-ui
- **样式**: SCSS
- **API通信**: 基于项目现有的 `$go` 方法
- **平台**: 微信小程序

### 页面结构
```
inviteFriends.vue
├── template (页面模板)
│   ├── 顶部导航栏
│   ├── 黄色渐变背景区域
│   │   ├── 礼物图标
│   │   ├── 标题文字
│   │   └── 奖励卡片区域
│   ├── 操作按钮区域
│   │   ├── 好友群邀请按钮
│   │   └── 复制邀请链接按钮
│   ├── 统计数据区域
│   └── 活动说明区域
├── script (业务逻辑)
│   ├── 数据初始化
│   ├── 获取邀请统计
│   ├── 好友群邀请
│   └── 复制邀请链接
└── style (样式定义)
```

## Components and Interfaces

### 数据模型

```javascript
data() {
  return {
    statusBarHeight: 0,        // 状态栏高度
    inviteStats: {             // 邀请统计数据
      inviteCount: 0,          // 邀请人数
      earnedPoints: 0          // 获得积分
    },
    userId: '',                // 当前用户ID
    inviteLink: ''             // 邀请链接
  }
}
```

### API接口

#### 1. 获取邀请统计数据
```javascript
// 接口路径: invite/stats
// 方法: GET
// 参数: 
{
  timestamp: Number,
  sign: String
}
// 返回:
{
  code: 0,
  data: {
    invite_count: Number,    // 邀请人数
    earned_points: Number    // 获得积分
  }
}
```

#### 2. 生成邀请链接
```javascript
// 接口路径: invite/link
// 方法: GET
// 参数:
{
  timestamp: Number,
  sign: String
}
// 返回:
{
  code: 0,
  data: {
    invite_link: String     // 邀请链接
  }
}
```

### 核心方法

#### 1. getInviteStats()
获取用户的邀请统计数据
```javascript
getInviteStats() {
  const querys = {
    timestamp: new Date().getTime()
  }
  const data = {
    ...querys,
    sign: this.$base.getASCII(querys)
  }
  this.$go('invite/stats', data, 'get', {
    show_err: true
  }).then(res => {
    this.inviteStats = {
      inviteCount: res.data.invite_count || 0,
      earnedPoints: res.data.earned_points || 0
    }
  })
}
```

#### 2. handleGroupInvite()
处理好友群邀请
```javascript
handleGroupInvite() {
  uni.shareToWeChat({
    title: '邀请好友得积分奖励',
    path: `/pages/index/index?inviter=${this.userId}`,
    imageUrl: '/static/image/invite-share.png'
  })
}
```

#### 3. handleCopyLink()
处理复制邀请链接
```javascript
handleCopyLink() {
  if (!this.inviteLink) {
    this.getInviteLink()
    return
  }
  uni.setClipboardData({
    data: this.inviteLink,
    success: () => {
      uni.showToast({
        title: '复制成功',
        icon: 'success'
      })
    },
    fail: () => {
      uni.showToast({
        title: '复制失败',
        icon: 'none'
      })
    }
  })
}
```

#### 4. getInviteLink()
获取邀请链接
```javascript
getInviteLink() {
  const querys = {
    timestamp: new Date().getTime()
  }
  const data = {
    ...querys,
    sign: this.$base.getASCII(querys)
  }
  this.$go('invite/link', data, 'get', {
    show_err: true
  }).then(res => {
    this.inviteLink = res.data.invite_link
    this.handleCopyLink()
  })
}
```

## Data Models

### InviteStats (邀请统计)
```javascript
{
  inviteCount: Number,      // 邀请人数
  earnedPoints: Number      // 获得积分
}
```

### UserInfo (用户信息)
```javascript
{
  id: String,              // 用户ID
  nickname: String         // 用户昵称
}
```

## Correctness Properties

*属性是关于系统应该做什么的特征或行为的正式陈述，它应该在所有有效执行中保持为真。属性是人类可读规范和机器可验证正确性保证之间的桥梁。*

### Property 1: 统计数据非负性
*对于任何*邀请统计数据，邀请人数和获得积分数都应该是非负整数
**Validates: Requirements 4.2, 4.3**

### Property 2: 复制成功后剪贴板内容正确
*对于任何*成功的复制操作，剪贴板中的内容应该等于邀请链接
**Validates: Requirements 3.2**

### Property 3: 页面加载时必须显示所有必需元素
*对于任何*页面加载完成的状态，页面应该包含：顶部背景、奖励卡片、操作按钮、统计区域、活动说明
**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

## Error Handling

### 网络错误处理
- API请求失败时显示错误提示
- 使用默认值（0人，0积分）作为降级方案
- 提供重试机制

### 用户操作错误处理
- 复制失败时显示明确的错误提示
- 分享失败时提供备选方案（复制链接）
- 防止重复点击（按钮防抖）

### 数据验证
- 验证API返回的数据格式
- 确保数值类型正确（Number）
- 处理空值和undefined情况

## Testing Strategy

### Unit Tests
使用 Jest 或 uni-app 推荐的测试框架进行单元测试：

1. **数据格式化测试**
   - 测试数字格式化显示
   - 测试空值处理

2. **方法功能测试**
   - 测试 getInviteStats 方法
   - 测试 handleCopyLink 方法
   - 测试 getInviteLink 方法

3. **边界条件测试**
   - 测试邀请人数为0的情况
   - 测试积分为0的情况
   - 测试网络请求失败的情况

### Integration Tests
1. **页面渲染测试**
   - 验证页面元素正确渲染
   - 验证数据绑定正确

2. **用户交互测试**
   - 测试按钮点击事件
   - 测试分享功能
   - 测试复制功能

3. **API集成测试**
   - 测试API调用流程
   - 测试数据更新流程

### Manual Testing Checklist
- [ ] 页面UI与设计稿一致
- [ ] 好友群邀请功能正常
- [ ] 复制链接功能正常
- [ ] 统计数据显示正确
- [ ] 活动说明文字完整
- [ ] 返回按钮功能正常
- [ ] 不同屏幕尺寸适配正常
- [ ] 网络异常时降级方案正常

## UI/UX Specifications

### 颜色规范
- 主背景渐变: `linear-gradient(180deg, #FFF7BF 0%, #FFFFFF 100%)`
- 主按钮颜色: `#FF6B3D`
- 次按钮边框: `#FF6B3D`
- 标题文字: `#FF6B3D`
- 正文文字: `#333333`
- 辅助文字: `#999999`
- 统计数字: `#FF6B3D`

### 尺寸规范
- 页面内边距: `30rpx`
- 按钮高度: `88rpx`
- 按钮圆角: `44rpx`
- 卡片圆角: `16rpx`
- 标题字号: `48rpx`
- 正文字号: `28rpx`
- 辅助字号: `24rpx`

### 间距规范
- 区块间距: `40rpx`
- 元素间距: `24rpx`
- 小元素间距: `16rpx`

### 动画效果
- 按钮点击: 缩放动画 (scale 0.95)
- 页面进入: 淡入动画
- 数据更新: 数字滚动动画（可选）
