<template>
  <view class="sign-in-page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-left" @click="goBack">
        <image
          class="back-text"
          src="/static/icon/back.png"
          mode="scaleToFill"
        />
      </view>
      <view class="nav-title">我的积分</view>
      <view class="nav-right">
        <text class="more-text">⋯</text>
      </view>
    </view>

    <!-- 积分显示区域 -->
    <view class="points-section">
      <view class="points-display">
        <text class="points-number">{{ userPoints }}</text>
        <image
          class="coin-icon"
          src="/static/icon/jifen-icon.png"
          mode="scaleToFill"
        />
      </view>
      <view class="points-detail-btn" @click="showPointsDetail">
        <text class="detail-text">积分明细</text>
      </view>
    </view>

    <!-- 签到卡片 -->
    <view class="check-in-card">
      <view class="card-header">
        <text class="consecutive-text"
          >已连续签到
          <text class="days-highlight">{{ consecutiveDays }}</text> 天</text
        >
        <view
          class="check-in-status"
          :class="{ checked: isCheckedToday, 'can-check': !isCheckedToday }"
          @click="checkIn"
        >
          <text class="status-text">{{
            isCheckedToday ? "已签到" : "签到"
          }}</text>
        </view>
      </view>

      <!-- 7天签到日历 -->
      <view class="calendar-grid">
        <view
          v-for="(day, index) in checkInDays"
          :key="index"
          class="calendar-item"
          :class="{
            checked: day.status === 1,
            current: index === consecutiveDays - 1 && isCheckedToday,
            special: index === 6,
          }"
        >
          <view class="day-label"
            >第{{ ["一", "二", "三", "四", "五", "六", "七"][index] }}天</view
          >
          <view class="day-icon">
            <image
              v-if="index === 6"
              class="reward-emoji"
              src="/static/icon/001@2x.png"
              mode="scaleToFill"
            />
            <image
              v-else
              class="coin-emoji"
              src="/static/icon/jifen-icon.png"
              mode="scaleToFill"
            />
          </view>
          <view class="day-points">{{ day.score }}积分</view>
        </view>
      </view>
    </view>

    <!-- 获取积分区域 -->
    <view class="earn-points-section">
      <view class="section-header">
        <text class="section-title">获取积分</text>
        <view class="points-usage" @click="showPointsUsage">
          <image
            class="question-icon"
            src="/static/icon/Frame@2x.png"
            mode="scaleToFill"
          />
          <text class="usage-text">积分用途</text>
        </view>
      </view>

      <!-- 邀请新用户 -->
      <view class="earn-item" v-for="task in tasks" :key="task.id">
        <view class="earn-left">
          <image
            class="earn-icon invite-icon"
            :src="task.icon"
            mode="scaleToFill"
          />
          <view class="earn-info">
            <text class="earn-title">{{ task.title }}</text>
            <text class="earn-desc">{{ task.desc }}</text>
          </view>
        </view>
        <view class="earn-action invite-btn" @click="inviteUser(task)">
          <text class="action-text">{{ task.btn_text }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      userPoints: 30,
      consecutiveDays: 0,
      isCheckedToday: false,
      feedbackCompleted: false,
      tasks: [],
      checkInDays: [
        { day: 1, score: 10, status: 0 },
        { day: 2, score: 10, status: 0 },
        { day: 3, score: 10, status: 0 },
        { day: 4, score: 10, status: 0 },
        { day: 5, score: 10, status: 0 },
        { day: 6, score: 30, status: 0 },
        { day: 7, score: 50, status: 0 },
      ],
    };
  },
  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;

    // 尝试加载真实数据，如果失败则使用模拟数据
    this.loadCheckInData();
  },
  methods: {
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 加载签到数据
    loadCheckInData() {
      // 如果没有配置 $go 方法，使用模拟数据
      if (!this.$go) {
        this.useMockCheckInData();
        return;
      }

      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      this.$go("integral/index", data, "get", {
        show_err: true,
      })
        .then((res) => {
          if (res.code === 0) {
            this.userPoints = res.data.total_integral || 0;
            this.checkInDays = res.data.sign_rules;
            this.consecutiveDays = res.data.continue_days || 0;
            this.isCheckedToday = res.data.is_signed || false;
            this.feedbackCompleted = res.data.feedback_completed || false;
            this.tasks = res.data.tasks;
          }
        })
        .catch((err) => {
          console.log("加载签到数据失败，使用模拟数据:", err);
          this.useMockCheckInData();
        });
    },

    // 执行签到
    checkIn() {
      if (this.isCheckedToday) {
        uni.showToast({
          title: "今天已经签到过了",
          icon: "none",
        });
        return;
      }

      // 如果没有配置 $go 方法，使用模拟签到
      if (!this.$go) {
        this.mockCheckIn();
        return;
      }

      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      this.$go("integral/sign", data, "post", {
        show_err: false,
      })
        .then((res) => {
          if (res.code === 0) {
            const earnedPoints = res.data.earned_points || 10;

            uni.showToast({
              title: `签到成功，获得${earnedPoints}积分`,
              icon: "success",
            });

            this.loadCheckInData();
          }
        })
        .catch((err) => {
          console.log("签到失败，使用模拟签到:", err);
          this.mockCheckIn();
        });
    },

    useMockCheckInData() {
      this.userPoints = this.userPoints || 30;
      this.consecutiveDays = this.consecutiveDays || 0;
      this.isCheckedToday = false;
      this.tasks = this.tasks && this.tasks.length
        ? this.tasks
        : [
            {
              id: 1,
              title: "邀请好友",
              desc: "邀请好友注册可获得积分奖励",
              btn_text: "去邀请",
              icon: "/static/icon/yqhy-icon.png",
            },
          ];
      this.checkInDays = [
        { day: 1, score: 10, status: 0 },
        { day: 2, score: 10, status: 0 },
        { day: 3, score: 10, status: 0 },
        { day: 4, score: 10, status: 0 },
        { day: 5, score: 10, status: 0 },
        { day: 6, score: 30, status: 0 },
        { day: 7, score: 50, status: 0 },
      ];
    },

    mockCheckIn() {
      if (this.isCheckedToday) {
        return;
      }
      const currentDay = (this.consecutiveDays % 7) + 1;
      const currentRule =
        this.checkInDays.find((item) => item.day === currentDay) || {};
      const earnedPoints = currentRule.score || 10;
      this.userPoints += earnedPoints;
      this.consecutiveDays += 1;
      this.isCheckedToday = true;
      this.checkInDays = this.checkInDays.map((item) => ({
        ...item,
        status: item.day <= ((this.consecutiveDays - 1) % 7) + 1 ? 1 : item.status,
      }));
      uni.showToast({
        title: `签到成功，获得${earnedPoints}积分`,
        icon: "success",
      });
    },

    // 显示积分明细
    showPointsDetail() {
      uni.navigateTo({
        url: "/pagesOther/pointsDetail/pointsDetail",
      });
    },

    // 显示积分用途
    showPointsUsage() {
      uni.showModal({
        title: "积分用途",
        content: "积分可用于兑换会员权益、存储空间等福利",
        showCancel: false,
      });
    },

    // 邀请用户
    inviteUser(data) {
      uni.navigateTo({
        url: "/pagesOther/inviteFriends/inviteFriends",
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.sign-in-page {
  background: linear-gradient(180deg, #ffd700 0%, #fff9e6 40%, #ffffff 100%);
  padding-bottom: 40rpx;
}

/* 顶部导航栏 */
.nav-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 30rpx;
  padding-bottom: 20rpx;
}

.nav-left,
.nav-right {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.back-text,
.more-text {
  width: 40rpx;
  height: 40rpx;
}

.nav-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
}

/* 积分显示区域 */
.points-section {
  position: relative;
  padding: 40rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.points-display {
  display: flex;
  align-items: flex-end;
  gap: 16rpx;
}

.points-number {
  font-size: 96rpx;
  font-weight: bold;
  color: #333333;
  line-height: 1;
}

.coin-icon {
  width: 48rpx;
  height: 48rpx;
  margin-bottom: 10rpx;
}

.points-detail-btn {
  padding: 16rpx 32rpx;
  background: #ffd700;
  border-radius: 40rpx;
  box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.3);
}

.detail-text {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
}

/* 签到卡片 */
.check-in-card {
  margin: 0 20rpx;
  background: #ffffff;
  border-radius: 32rpx;
  padding: 20rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fcf7dd;
  padding: 24rpx;
  margin-bottom: 40rpx;
  border-radius: 24rpx;
}

.consecutive-text {
  font-size: 32rpx;
  color: #333333;
  font-weight: bold;
}

.days-highlight {
  color: #ff7125;
  font-weight: bold;
  font-size: 32rpx;
}

.check-in-status {
  padding: 12rpx 32rpx;
  background: #e5e5e5;
  border-radius: 40rpx;
  cursor: pointer;
}

.check-in-status.checked {
  background: #e5e5e5;
}

.check-in-status.can-check {
  background: #ffd700;
  box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.4);
}

.status-text {
  font-size: 24rpx;
  color: #999999;
}

.check-in-status.can-check .status-text {
  color: #333333;
  font-weight: bold;
}

/* 7天签到日历 */
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20rpx;
}

.calendar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24rpx 16rpx;
  background: #f5f5f5;
  border-radius: 16rpx;
  gap: 12rpx;
}

.calendar-item.checked {
  background: #ffd700;
}

.calendar-item.current {
  background: #ffd700;
  box-shadow: 0 4rpx 12rpx rgba(255, 215, 0, 0.4);
}

.calendar-item.special {
  align-items: flex-start;
  justify-content: flex-start;
  grid-column: 3 / 5;
  grid-row: 2;
  position: relative;
}

.day-label {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
}

.day-icon {
  font-size: 48rpx;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.coin-emoji {
  height: 48rpx;
  width: 48rpx;
}

.reward-emoji {
  position: absolute;
  right: 20rpx;
  top: 50%;
  transform: translateY(-50%);
  width: 132rpx;
  height: 132rpx;
}

.day-points {
  font-size: 24rpx;
  color: #666666;
}

.calendar-item.checked .day-points {
  color: #333333;
  font-weight: 500;
}

/* 获取积分区域 */
.earn-points-section {
  margin: 40rpx 30rpx 0;
  background: #fff;
  border-radius: 24rpx;
  padding: 20rpx;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
}

.points-usage {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.question-icon {
  width: 32rpx;
  height: 32rpx;
}

.usage-text {
  font-size: 28rpx;
  color: #999999;
}

/* 获取积分项 */
.earn-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16rpx 0;
  background: #ffffff;
  border-radius: 24rpx;
  margin-bottom: 20rpx;
}

.earn-left {
  display: flex;
  align-items: center;
  gap: 24rpx;
  flex: 1;
}

.earn-icon {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 56rpx;
  background: #f5f5f5;
  border-radius: 50%;
}

.earn-info {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.earn-title {
  font-size: 32rpx;
  color: #333333;
  font-weight: 500;
}

.earn-desc {
  font-size: 24rpx;
  color: #999999;
}

.earn-action {
  padding: 16rpx 32rpx;
  background: #e5e5e5;
  border-radius: 40rpx;
}

.earn-action.invite-btn {
  background: #ffd700;
}

.earn-action.completed {
  background: #e5e5e5;
}

.action-text {
  font-size: 28rpx;
  color: #333333;
  font-weight: 500;
}

.earn-action.invite-btn .action-text {
  color: #333333;
}

.earn-action.completed .action-text {
  color: #999999;
}
</style>
