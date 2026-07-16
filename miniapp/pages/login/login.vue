<template>
  <view class="login-page">
    <image class="login-bg" src="/static/image/login-bg.png" mode="aspectFill"></image>

    <!-- Logo和应用名称 -->
    <view class="login-content">
      <view class="logo-section">
        <view class="brand-mark">
          <view class="brand-mark-inner"></view>
        </view>
        <view class="app-name">家纺云相册</view>
      </view>

      <view class="login-main">
        <!-- 用户协议 -->
        <view class="agreement-section">
          <view class="agreement-item" @click="toggleAgreement">
            <view class="checkbox" :class="{ checked: agreed }">
              <text class="checkbox-icon" v-if="agreed">✓</text>
            </view>
            <text class="agreement-text">
              请阅读并同意
              <text class="link-text" @click.stop="openAgreement('user')"
                >《用户服务协议》</text
              >
              和
              <text class="link-text" @click.stop="openAgreement('privacy')"
                >《隐私保护政策》</text
              >
            </text>
          </view>
        </view>

        <!-- 登录按钮 -->
        <view class="login-section">
          <button
            v-if="needPrivacyAuthorization"
            class="login-btn"
            :class="{ disabled: !agreed }"
            :disabled="!agreed || loading"
            open-type="agreePrivacyAuthorization"
            @agreeprivacyauthorization="handleAgreePrivacyAuthorization"
          >
            同意隐私保护指引
          </button>
          <button
            v-else
            class="login-btn"
            :class="{ disabled: !agreed }"
            :disabled="!agreed || loading"
            open-type="getPhoneNumber"
            @getphonenumber="handleGetPhoneNumber"
          >
            {{ loading ? "登录中..." : "手机号一键登录" }}
          </button>
        </view>
      </view>

      <!-- 取消登录 -->
      <view class="cancel-section">
        <text class="cancel-text" @click="handleCancel">取消登录</text>
      </view>
    </view>
  </view>
</template>

<script>
import {
  consumeShareLoginRedirect,
  getMiniCode,
  login,
  setPendingInviteCode,
} from "@/common/request/api.js";

export default {
  data() {
    return {
      uid: "",
      inviteCode: "",
      agreed: false, // 是否同意协议
      loading: false, // 登录中状态
      needPrivacyAuthorization: false,
    };
  },
  onLoad(options) {
    if(options.uid){
      this.uid = options.uid
    }
    if (options.invite_code) {
      this.inviteCode = String(options.invite_code || "").trim();
      setPendingInviteCode(this.inviteCode);
    }
    // 页面加载时先获取 openid
    this.initOpenId();
    this.checkPrivacySetting();
  },
  methods: {
    // 初始化获取 openid
    async initOpenId() {
      try {
        await getMiniCode(this.inviteCode);
      } catch (error) {
        console.error("获取 openid 失败:", error);
      }
    },

    // 切换协议同意状态
    toggleAgreement() {
      this.agreed = !this.agreed;
    },

    // 打开协议页面
    openAgreement(type) {
      uni.navigateTo({
        url: `/pagesOther/agreement/agreement?type=${type}`,
      });
    },

    checkPrivacySetting() {
      if (typeof wx === "undefined" || !wx.getPrivacySetting) {
        return;
      }
      wx.getPrivacySetting({
        success: (res) => {
          this.needPrivacyAuthorization = Boolean(res.needAuthorization);
        },
      });
    },

    handleAgreePrivacyAuthorization(e) {
      const errMsg = (e && e.detail && e.detail.errMsg) || "";
      if (errMsg && errMsg.indexOf("ok") === -1) {
        uni.showToast({
          title: "请先同意隐私保护指引",
          icon: "none",
        });
        return;
      }
      this.needPrivacyAuthorization = false;
      uni.showToast({
        title: "已同意隐私保护指引",
        icon: "none",
      });
    },

    // 处理获取手机号
    async handleGetPhoneNumber(e) {
      if (!this.agreed) {
        uni.showToast({
          title: "请先同意用户协议",
          icon: "none",
        });
        return;
      }

      if (this.needPrivacyAuthorization) {
        uni.showToast({
          title: "请先同意隐私保护指引",
          icon: "none",
        });
        return;
      }

      if (e.detail.errMsg === "getPhoneNumber:ok") {
        const code = e.detail.code;
        if (!code) {
          uni.showToast({
            title: "获取手机号授权失败",
            icon: "none",
          });
          return;
        }

        // 开始登录
        this.loading = true;
        try {
          // 确保有 openid
          await getMiniCode(this.inviteCode);

          // 调用登录接口
          const loginSuccess = await login(code);

          if (loginSuccess) {
            uni.showToast({
              title: "登录成功",
              icon: "success",
            });

            // 登录成功后，延迟跳转
            setTimeout(() => {
              this.redirectAfterLogin();
            }, 1500);
          } else {
            uni.showToast({
              title: "登录失败，请重试",
              icon: "none",
            });
          }
        } catch (error) {
          console.error("登录失败:", error);
          uni.showToast({
            title: error?.message || "登录失败，请重试",
            icon: "none",
          });
        } finally {
          this.loading = false;
        }
      } else {
        // 用户拒绝授权
        if (e.detail.errMsg !== "getPhoneNumber:fail user deny") {
          uni.showToast({
            title: "获取手机号失败",
            icon: "none",
          });
        }
      }
    },

    // 取消登录
    handleCancel() {
      const pages = getCurrentPages();
      if (pages.length > 1) {
        uni.navigateBack();
      } else {
        uni.redirectTo({
          url: "/pages/selection/selection",
        });
      }
    },
    redirectAfterLogin() {
      const redirectUrl = consumeShareLoginRedirect();
      if (redirectUrl) {
        uni.reLaunch({
          url: redirectUrl,
          fail: () => {
            uni.redirectTo({ url: redirectUrl });
          },
        });
        return;
      }
      const pages = getCurrentPages();
      if (pages.length > 1 || this.uid) {
        uni.navigateBack();
      } else {
        uni.redirectTo({
          url: "/pages/index/index",
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.login-page {
  position: relative;
  min-height: 100vh;
  background: #ffffff;
  overflow: hidden;
  box-sizing: border-box;
}

.login-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
}

.login-content {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 104rpx 60rpx 76rpx;
  box-sizing: border-box;
}

/* Logo区域 */
.logo-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 0;
}

.brand-mark {
  width: 156rpx;
  height: 156rpx;
  margin-bottom: 46rpx;
  border-radius: 26rpx;
  background: linear-gradient(180deg, #ffbc00 0%, #ffd333 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 18rpx 44rpx rgba(236, 160, 0, 0.24);
}

.brand-mark-inner {
  width: 90rpx;
  height: 58rpx;
  border-radius: 14rpx;
  background: rgba(255, 255, 255, 0.34);
}

.app-name {
  font-size: 48rpx;
  font-weight: bold;
  color: #2f2f2f;
  text-align: center;
  line-height: 1.2;
}

.login-main {
  width: 100%;
  margin-top: auto;
  padding-bottom: 84rpx;
}

/* 用户协议区域 */
.agreement-section {
  width: 100%;
  margin-bottom: 64rpx;
}

.agreement-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
}

.checkbox {
  width: 36rpx;
  height: 36rpx;
  border: 2rpx solid #cccccc;
  border-radius: 6rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 4rpx;
  transition: all 0.3s;

  &.checked {
    background-color: #ffd700;
    border-color: #ffd700;
  }
}

.checkbox-icon {
  color: #ffffff;
  font-size: 24rpx;
  font-weight: bold;
}

.agreement-text {
  font-weight: 400;
  font-size: 24rpx;
  color: #999999;
  flex: 1;
  line-height: 1.6;
}

.link-text {
  color: #ff8c00;
  text-decoration: underline;
}

/* 登录按钮区域 */
.login-section {
  width: 100%;
  margin-bottom: 60rpx;
}

.login-btn {
  width: 100%;
  height: 96rpx;
  background: #ffd000;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
  border: none;
  box-shadow: 0 8rpx 24rpx rgba(255, 215, 0, 0.3);
  transition: all 0.3s;

  &::after {
    border: none;
  }

  &.disabled {
    background: #e5e5e5;
    color: #999999;
    box-shadow: none;
  }

  &:not(.disabled):active {
    transform: scale(0.98);
    opacity: 0.9;
  }
}

/* 取消登录区域 */
.cancel-section {
  margin-top: auto;
  padding-top: 44rpx;
}

.cancel-text {
  font-size: 28rpx;
  color: #666666;
  text-decoration: none;
}
</style>
