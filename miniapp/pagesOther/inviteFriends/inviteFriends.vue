<template>
  <view class="invite-page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-back" @click="goBack">
        <image src="/static/icon/back.png" mode="aspectFit"></image>
      </view>
      <view class="nav-title">邀请好友</view>
      <view class="nav-placeholder"></view>
    </view>
    <!-- 标题 -->
    <view
      class="title-section"
      :style="{ paddingTop: statusBarHeight + 60 + 'px' }"
    >
      <text class="main-title">邀请好友得</text>
      <text class="main-title highlight">积分奖励</text>
      <!-- 礼物图标 - 使用emoji或文字代替 -->
      <view class="gift-icon">
        <image
          class="gift-emoji"
          src="/pagesOther/static/image/image1 1.png"
          mode="scaleToFill"
        />
      </view>
    </view>

    <!-- 主要内容区域 -->
    <view class="content">
      <view class="section-wrap">
        <!-- 顶部黄色渐变背景区域 -->
        <view class="header-section">
          <!-- 副标题 -->
          <view class="subtitle">
            <text>每邀请1位好友，可获得</text>
          </view>

          <!-- 奖励卡片 -->
          <view class="reward-cards">
            <view class="reward-card">
              <view class="reward-points-desc">
                <text class="reward-points">+10</text>
                <text class="reward-text">积分</text>
              </view>
              <text class="reward-desc">成功注册</text>
            </view>
            <view class="reward-card">
              <view class="reward-points-desc">
                <text class="reward-points">+1000</text>
                <text class="reward-text">积分</text>
              </view>
              <text class="reward-desc">首次充值</text>
            </view>
          </view>
        </view>

        <!-- 操作按钮区域 -->
        <view class="action-section">
          <button
            class="action-btn primary-btn"
            open-type="share"
            data-from="sharePopup"
            @tap.stop="handleGroupInvite"
          >
            <image
              src="/static/icon/yqhy-icon.png"
              class="btn-icon"
              mode="aspectFit"
            ></image>
            <text class="btn-text">好友群邀请</text>
          </button>
          <view class="action-btn secondary-btn" @click="handleCopyLink">
            <image
              src="/static/icon/fzlj-icon.png"
              class="btn-icon"
              mode="aspectFit"
            ></image>
            <text class="btn-text">复制邀请链接</text>
          </view>
        </view>
      </view>
      <!-- 统计数据区域 -->
      <view class="stats-section">
        <view class="stats-title">
          <view class="title-line"></view>
          <text class="title-text">我获得的</text>
          <view class="title-line"></view>
        </view>
        <view class="stats-content">
          <view class="stat-item">
            <text class="stat-number">{{ inviteStats.inviteCount }}</text>
            <text class="stat-unit">人</text>
            <text class="stat-label">邀请人数</text>
          </view>
          <view class="stat-item">
            <text class="stat-number">{{ inviteStats.earnedPoints }}</text>
            <text class="stat-unit">积分</text>
            <text class="stat-label">获得积分</text>
          </view>
        </view>
      </view>

      <!-- 活动说明区域 -->
      <view class="rules-section">
        <view class="rules-title">
          <view class="title-line"></view>
          <text class="title-text">活动说明</text>
          <view class="title-line"></view>
        </view>
        <view class="rules-content">
          <text class="rule-item" v-for="(r, index) in rules" :key="index">{{
            r
          }}</text>
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
      inviteStats: {
        inviteCount: 0,
        earnedPoints: 0,
      },
      rules: [],
      userId: "",
      inviteCode: "",
      invitePath: "",
      inviteLink: "",
      isLoading: false,
    };
  },
  onLoad() {
    // 获取状态栏高度
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;

    // 获取用户信息
    const userInfo = uni.getStorageSync("userInfo");
    if (userInfo && userInfo.id) {
      this.userId = userInfo.id;
      this.inviteCode = this.normalizeText(userInfo.invite_code);
    }

    if (this.ensureLogin()) {
      // 获取邀请统计数据
      this.getInviteStats();
    }
  },
  methods: {
    normalizeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    applyInviteData(data = {}) {
      this.inviteCode = this.normalizeText(data.invite_code) || this.inviteCode;
      this.invitePath =
        this.normalizeText(data.invite_path) ||
        (this.inviteCode ? `/pages/index/index?invite_code=${encodeURIComponent(this.inviteCode)}` : "");
      this.inviteLink =
        this.normalizeText(data.url_link) ||
        this.normalizeText(data.invite_link) ||
        this.inviteLink;
    },
    getInviteSharePath() {
      return this.inviteCode
        ? `/pages/index/index?invite_code=${encodeURIComponent(this.inviteCode)}`
        : "/pages/index/index";
    },
    ensureLogin() {
      if (this.$checkLoginStatus()) {
        return true;
      }

      uni.showModal({
        title: "未登录，是否立即登录？",
        content: "",
        showCancel: true,
        success: ({ confirm }) => {
          if (confirm) {
            this.$silentLogin("");
          }
        },
      });
      return false;
    },
    handleAuthExpired() {
      uni.removeStorageSync("token");
      uni.removeStorageSync("user");
      uni.removeStorageSync("userInfo");
      this.ensureLogin();
    },
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 获取邀请统计数据
    getInviteStats() {
      if (!this.ensureLogin()) {
        return;
      }

      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("integral/invite/list", data, "get", {
        show_err: false,
        loading: false,
      })
        .then((res) => {
          if (res && (res.code === 4001 || res.code === 403)) {
            this.handleAuthExpired();
            return;
          }
          if (res && res.data) {
            this.rules = res.data.rules;
            this.inviteStats = {
              inviteCount: res.data.invite_count || 0,
              earnedPoints: res.data.invite_score || 0,
            };
            this.applyInviteData(res.data);
          }
        })
        .catch((err) => {
          console.log("获取邀请统计失败:", err);
          // 使用默认值
          this.inviteStats = {
            inviteCount: 0,
            earnedPoints: 0,
          };
        });
    },

    // 获取邀请链接
    getInviteLink() {
      if (this.isLoading) return;
      if (!this.ensureLogin()) return;
      this.isLoading = true;

      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("integral/invite", data, "get", {
        show_err: false,
        loading: true,
      })
        .then((res) => {
          this.isLoading = false;
          if (res && (res.code === 4001 || res.code === 403)) {
            this.handleAuthExpired();
            return;
          }
          if (res && res.data) {
            this.applyInviteData(res.data);
          }
          if (this.inviteLink) {
            this.copyToClipboard(this.inviteLink);
          } else {
            uni.showToast({
              title: "获取邀请链接失败",
              icon: "none",
            });
          }
        })
        .catch((err) => {
          this.isLoading = false;
          console.log("获取邀请链接失败:", err);
          uni.showToast({
            title: "获取邀请链接失败",
            icon: "none",
          });
        });
    },

    // ---- 分享给好友（组件内部处理准备数据并打开分享菜单） ----
    async handleGroupInvite() {
      if (this.isLoading) return;
      if (!this.ensureLogin()) return;
      // 打开分享菜单（若平台支持）
      if (typeof wx !== "undefined" && wx.showShareMenu) {
        try {
          wx.showShareMenu({ withShareTicket: true });
        } catch (e) {}
      }
    },

    // 复制邀请链接
    handleCopyLink() {
      if (this.isLoading) return;
      if (!this.ensureLogin()) return;

      // 如果已经有邀请链接，直接复制
      if (this.inviteLink) {
        this.copyToClipboard(this.inviteLink);
      } else {
        // 否则先获取邀请链接
        this.getInviteLink();
      }
    },

    // 复制到剪贴板
    copyToClipboard(text) {
      uni.setClipboardData({
        data: text,
        success: () => {
          uni.showToast({
            title: "复制成功",
            icon: "success",
          });
        },
        fail: () => {
          uni.showToast({
            title: "复制失败",
            icon: "none",
          });
        },
      });
    },
  },

  // 分享配置
  onShareAppMessage() {
    return {
      title: "邀请好友得积分奖励",
      path: this.getInviteSharePath(),
      imageUrl: "/static/image/invite-share.png",
    };
  },

  onShareTimeline() {
    return {
      title: "邀请好友得积分奖励",
      query: this.inviteCode ? `invite_code=${encodeURIComponent(this.inviteCode)}` : "",
      imageUrl: "/static/image/invite-share.png",
    };
  },
};
</script>

<style lang="scss" scoped>
.invite-page {
  background: linear-gradient(
    180deg,
    #ffd700 0%,
    #fff9e6 40%,
    #f2f2f2 50%,
    #f2f2f2 100%
  );
}

// 顶部导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
  padding-bottom: 20rpx;
  background: transparent;

  .nav-back {
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    image {
      width: 40rpx;
      height: 40rpx;
    }
  }

  .nav-title {
    font-size: 48;
    font-weight: 600;
    color: #333333;
  }

  .nav-placeholder {
    width: 80rpx;
  }
}
.title-section {
  position: relative;
  display: flex;
  flex-direction: column;
  margin-bottom: 20rpx;
  padding: 0 20rpx;

  .main-title {
    font-size: 48rpx;
    font-weight: bold;
    color: #333333;

    &.highlight {
      font-size: 80rpx;
      color: #ff6b3d;
    }
  }

  .gift-icon {
    position: absolute;
    right: 0;
    top: 160rpx;
    width: 360rpx;
    height: 308rpx;
    margin-bottom: 40rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    .gift-emoji {
      width: 360rpx;
      height: 308rpx;
    }
  }
}

// 主要内容区域
.content {
  position: relative;
  z-index: 100;
  padding-bottom: 60rpx;
  padding: 0 20rpx;
  /* 安全区兼容：先声明常规值，再使用 env */
  padding-bottom: calc(constant(safe-area-inset-bottom));
  padding-bottom: calc(env(safe-area-inset-bottom));
  .section-wrap {
    background: #fff;
    border-radius: 48rpx;
    padding: 40rpx 0 20rpx 0;
  }
}

.subtitle {
  font-size: 28rpx;
  color: #333333;
  margin-bottom: 40rpx;
}
// 顶部区域
.header-section {
  position: relative;
  padding: 0 30rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 60rpx;

  .reward-cards {
    display: flex;
    gap: 24rpx;
    width: 100%;

    .reward-card {
      flex: 1;
      background: rgba(245, 245, 245, 1);
      border-radius: 16rpx;
      padding: 32rpx 24rpx;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16rpx;
      box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);
      .reward-points-desc {
        display: flex;
        align-items: center;
        gap: 8rpx;
      }

      .reward-points {
        font-size: 40rpx;
        font-weight: bold;
        color: rgba(255, 51, 16, 1);
        line-height: 1;
      }

      .reward-unit {
        font-size: 24rpx;
        color: #ff6b3d;
        margin-top: 8rpx;
        margin-bottom: 16rpx;
      }
      .reward-text {
        font-weight: bold;
        font-size: 24rpx;
        color: #333;
      }

      .reward-desc {
        font-size: 26rpx;
        color: rgba(0, 0, 0, 0.6);
      }
    }
  }
}

// 操作按钮区域
.action-section {
  padding: 0 30rpx;

  .action-btn {
    height: 88rpx;
    border-radius: 44rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    margin-bottom: 24rpx;
    transition: all 0.3s;

    &:active {
      transform: scale(0.98);
    }

    .btn-icon {
      width: 48rpx;
      height: 48rpx;
    }

    .btn-text {
      font-size: 32rpx;
      font-weight: 500;
    }

    &.primary-btn {
      background: linear-gradient(90deg, #f9341c 0%, #fe8f43 100%);

      box-shadow: 0 8rpx 16rpx rgba(255, 107, 61, 0.3);

      .btn-text {
        color: #ffffff;
      }
    }

    &.secondary-btn {
      background: #ffffff;
      border: 1px solid;
      border-image-source: linear-gradient(90deg, #f9341c 0%, #fe8f43 100%);

      .btn-text {
        color: #ff6b3d;
      }
    }
  }
}

// 统计数据区域
.stats-section {
  padding: 0 30rpx;
  margin-bottom: 60rpx;
  margin-top: 20rpx;
  background: #fff;
  border-radius: 48rpx;
  padding: 20rpx 0;

  .stats-title {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 40rpx;

    .title-line {
      flex: 1;
      height: 2rpx;
      background: linear-gradient(to right, transparent, #ff6b3d, transparent);
    }

    .title-text {
      font-size: 32rpx;
      font-weight: bold;
      color: #ff6b3d;
      padding: 0 24rpx;
    }
  }

  .stats-content {
    display: flex;
    justify-content: space-around;

    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;

      .stat-number {
        font-size: 56rpx;
        font-weight: bold;
        color: #ff6b3d;
        line-height: 1;
      }

      .stat-unit {
        font-size: 28rpx;
        color: #ff6b3d;
        margin-top: 8rpx;
        margin-bottom: 16rpx;
      }

      .stat-label {
        font-size: 24rpx;
        color: #999999;
      }
    }
  }
}

// 活动说明区域
.rules-section {
  padding: 0 30rpx;
  background: #fff;
  border-radius: 48rpx;
  padding: 20rpx;

  .rules-title {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 40rpx;

    .title-line {
      flex: 1;
      height: 2rpx;
      background: linear-gradient(to right, transparent, #ff6b3d, transparent);
    }

    .title-text {
      font-size: 32rpx;
      font-weight: bold;
      color: #ff6b3d;
      padding: 0 24rpx;
    }
  }

  .rules-content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;

    .rule-item {
      font-size: 28rpx;
      color: #666666;
      line-height: 1.6;
    }
  }
}
</style>
