<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-bar">
        <!-- 中间标题 -->
        <view class="nav-center">
          <text class="nav-title">家纺云相册</text>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <!-- 中央信息图片 -->
      <view class="center-image-wrapper">
        <image
          class="center-image"
          src="/static/image/start-image.png"
          mode="widthFix"
          @error="handleImageError"
        >
        </image>
      </view>

      <!-- 创建按钮 -->
      <view class="create-btn" @click="openPop()">
        <image
          class="create-icon"
          src="/static/icon/add-icon.png"
          mode="scaleToFill"
        />
        <text class="create-text">创建我的相册</text>
      </view>

      <!-- 底部功能面板 -->
      <view class="bottom-panel">
        <view class="panel-item" @click="handleFollow">
          <view class="panel-icon">
            <image src="/static/icon/Frame@2x(5).png" mode="aspectFit"></image>
          </view>
          <text class="panel-text">关注公众号</text>
        </view>
        <view class="panel-item" @click="handleExample">
          <view class="panel-icon">
            <image src="/static/icon/image-3@2x.png" mode="aspectFit"></image>
          </view>
          <text class="panel-text">示例相册</text>
        </view>
        <view class="panel-item" @click="toBuzz">
          <view class="panel-icon">
            <image src="/static/icon/thumbs-up.png" mode="aspectFit"></image>
          </view>
          <text class="panel-text">使用口碑</text>
        </view>
        <view class="panel-item" @click="handleShare">
          <button class="share-button" open-type="share" @click.stop>
            <view class="panel-icon">
              <image src="/static/icon/分享@2x.png" mode="aspectFit"></image>
            </view>
            <text class="panel-text">推荐给好友</text>
          </button>
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
      totalHeight: 0,
      navigationBarHeight: 44,
      show: false,
      folder_type: 2,
      folder_name: "",
      total: 0,
      baseInfo: {},
      currentTime: "9:41",
      disableAutoRedirect: false,
    };
  },
  onLoad(options = {}) {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.disableAutoRedirect =
      options.disableAutoRedirect === "1" || options.from === "home_info_missing";
    this.updateTime();
  },
  onShow() {
    const token = uni.getStorageSync("token");
    console.log(token);
    if (token && !this.disableAutoRedirect) {
      uni.redirectTo({ url: "/pages/index/index" });
      return;
    }
    this.getList();
    this.getBaseInfo();
  },
  // 分享给好友
  onShareAppMessage(res) {
    // 判断是从按钮触发还是右上角菜单触发
    if (res.from === "button") {
      console.log("来自页面内分享按钮");
    } else if (res.from === "menu") {
      console.log("来自右上角转发菜单");
    }

    return {
      title: "家纺云相册 - 创建你的专属相册",
      path: "/pages/selection/selection",
      imageUrl: "/static/image/start-image.png", // 分享图片，建议尺寸 5:4
      success: (shareRes) => {
        console.log("分享成功", shareRes);
        uni.showToast({
          title: "分享成功",
          icon: "success",
        });
      },
      fail: (err) => {
        console.log("分享失败", err);
        uni.showToast({
          title: "分享失败",
          icon: "none",
        });
      },
    };
  },
  // 分享到朋友圈（仅支持部分小程序）
  onShareTimeline() {
    console.log("分享到朋友圈");
    return {
      title: "家纺云相册 - 创建你的专属相册",
      query: "from=timeline",
      imageUrl: "/static/image/start-image.png",
    };
  },
  methods: {
    updateTime() {
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, "0");
      const minutes = now.getMinutes().toString().padStart(2, "0");
      this.currentTime = `${hours}:${minutes}`;
    },
    handleImageError(e) {
      console.log("图片加载失败:", e);
      // 可以设置默认图片
    },
    handleFollow() {
      if (this.baseInfo && this.baseInfo.news_link) {
        uni.navigateTo({
          url: `/pagesOther/webview/webview?link=${this.baseInfo.news_link}`,
        });
        return;
      }
      uni.showToast({
        title: "暂未配置公众号链接",
        icon: "none",
      });
    },
    handleExample() {
      uni.navigateTo({
        url: "/pagesOther/caseCenter/caseCenter",
      });
    },
    handleShare() {
      // 这个方法现在不需要了，因为使用了 button open-type="share"
      // 但保留以防其他地方调用
      console.log("触发分享");
    },
    getBaseInfo() {
      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("common/base", data, "get", {
        show_err: true,
      })
        .then((res) => {
          this.baseInfo = res.data;
          uni.setStorageSync("baseInfo", res.data);
        })
        .catch((err) => {
          console.error("获取用户信息失败:", err);
        });
    },
    getList() {
      const querys = {
        timestamp: new Date().getTime(),
        purpose: "全部",
        page: 1,
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("submit/evaluate", data, "get", {
        show_err: true,
      })
        .then((res) => {
          this.total = res.data.total;
        })
        .catch((err) => {
          console.error("获取列表失败:", err);
        });
    },
    toBuzz() {
      uni.navigateTo({
        url: "/pagesOther/vipBuzz/vipBuzz",
      });
    },
    openPop() {
      const token = uni.getStorageSync("token");
      if (token) {
        uni.navigateTo({
          url: "/pagesOther/setInfo/setInfo",
        });
        return;
      }
      uni.redirectTo({
        url: "/pages/login/login",
      });
    },
    cancelPop() {
      this.show = false;
    },
    buildRequestData(querys) {
      return {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff9e6; // 浅黄色背景
  box-sizing: border-box;
}

/* 顶部导航栏 */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background-color: transparent;
}

.nav-bar {
  height: 44px;
  display: flex;
  align-items: center;
  padding: 0 30rpx;
  position: relative;
}

.nav-left {
  .time {
    font-size: 32rpx;
    font-weight: 600;
    color: #000;
  }
}

.nav-center {
  .nav-title {
    font-weight: bold;
    font-size: 36rpx;
    color: #000000;
  }
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 20rpx;
}

.status-icons {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.nav-buttons {
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.nav-btn-circle {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
  background-color: rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn-text {
  font-size: 32rpx;
  color: #666;
}

/* 内容区域 */
.content {
  padding: 40rpx 30rpx;
  box-sizing: border-box;
}

/* 中央信息图片 */
.center-image-wrapper {
  width: 100%;
  margin: 24rpx 0 60rpx;
  display: flex;
  justify-content: center;
}

.center-image {
  width: 100%;
  max-width: 840rpx;
  border-radius: 24rpx;
}

/* 创建按钮 */
.create-btn {
  width: 100%;
  height: 112rpx;
  background: #ffd000;
  border-radius: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin: 0 auto 48rpx;
}

.create-icon {
  width: 40rpx;
  height: 40rpx;
}

.create-text {
  font-weight: bold;
  font-size: 32rpx;
  color: #333333;
}

/* 底部功能面板 */
.bottom-panel {
  background-color: #fff;
  border-radius: 24rpx;
  padding: 24rpx;
  display: flex;
  justify-content: space-around;
  align-items: center;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05);
}

.panel-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
}

.share-button {
  background: transparent;
  border: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  line-height: normal;

  &::after {
    border: none;
  }
}

.panel-icon {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  image {
    width: 48rpx;
    height: 48rpx;
  }
}

.panel-text {
  font-weight: 400;
  font-size: 24rpx;
  color: #666666;
}

/* 弹窗样式 */
.popBox {
  width: 600rpx;
  background-color: #ffffff;
  border-radius: 20rpx;

  .pop-title {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-top: 40rpx;
  }

  .title-icon {
    width: 80rpx;
    height: 80rpx;
    margin-bottom: 20rpx;
  }

  .title-text {
    width: 100%;
    line-height: 28rpx;
    font-size: 28rpx;
    margin-bottom: 30rpx;
    text-align: center;
  }
}

.input-content {
  width: 540rpx;
  height: 80rpx;
  margin: 0 auto;
  border-bottom: 1rpx solid #ccc;
  display: flex;
  align-items: center;
  padding-left: 20rpx;
  box-sizing: border-box;

  input {
    font-size: 30rpx;
    flex: 1;
  }
}

.btn-box {
  display: flex;
  align-items: center;
  justify-content: space-around;
  width: 100%;
  margin-top: 30rpx;
  padding: 0 40rpx 30rpx 40rpx;
  box-sizing: border-box;
}

.btn-box .cancel {
  width: 220rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  color: #666;
  background-color: #f5f5f5;
  border-radius: 40rpx;
}

.btn-box .submit {
  width: 220rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 32rpx;
  font-weight: 600;
  background-color: #ffd700;
  border-radius: 40rpx;
}
</style>
