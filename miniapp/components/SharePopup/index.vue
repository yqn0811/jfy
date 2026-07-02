<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :round="24"
    bgColor="#F5F5F5"
    :safe-area-inset-bottom="false"
    @close="close"
  >
    <view class="root">
      <view class="header">
        <view class="title"
          >分享{{ typeText }} <text class="bold">【{{ title }}】</text></view
        >
        <view v-if="!uid" class="perm" @tap="openSettings">设置访问权限 ›</view>
      </view>

      <view class="icons">
        <button
          open-type="share"
          class="share-button icon-cell"
          @tap.stop="prepareAndShare"
          data-from="sharePopup"
        >
          <view class="icon-bg">
            <image
              src="/static/icon/Frame 1171279097@2x.png"
              class="icon"
              mode="widthFix"
            />
          </view>
          <text class="icon-label">微信好友</text>
        </button>

        <view class="icon-cell" @tap="handleGeneratePoster">
          <view class="icon-bg">
            <image
              src="/static/icon/Frame 1171279098@2x.png"
              class="icon"
              mode="widthFix"
            />
          </view>
          <text class="icon-label">生成海报</text>
        </view>

        <view class="icon-cell" @tap="handleCopyLink">
          <view class="icon-bg">
            <image
              src="/static/icon/Frame 1171279099@2x.png"
              class="icon"
              mode="widthFix"
            />
          </view>
          <text class="icon-label">复制链接</text>
        </view>

        <view class="icon-cell" @tap="handlePreviewMiniCode">
          <view class="icon-bg">
            <image
              src="/static/icon/Frame 1171278239@2x.png"
              class="icon"
              mode="widthFix"
            />
          </view>
          <text class="icon-label">小程序码</text>
        </view>
      </view>

      <view class="help" @tap="openHelp">分享使用说明</view>
    </view>

    <!-- 分享使用说明弹窗 -->
    <u-modal
      :show="helpModalVisible"
      :show-cancel-button="false"
      :round="20"
      confirm-text="我知道了"
      @confirm="closeHelpModal"
    >
      <view class="help-modal-content">
        <view class="help-title">分享使用说明</view>
        <view class="help-section">
          <view class="help-subtitle">1. 微信好友分享</view>
          <view class="help-text">
            点击"微信好友"按钮，选择好友或群聊，即可将内容分享给对方。
          </view>
        </view>
        <view class="help-section">
          <view class="help-subtitle">2. 生成海报</view>
          <view class="help-text">
            点击"生成海报"按钮，系统会自动生成带有小程序码的海报图片，可保存到相册或分享到朋友圈。
          </view>
        </view>
        <view class="help-section">
          <view class="help-subtitle">3. 复制链接</view>
          <view class="help-text">
            点击"复制链接"按钮，将分享链接复制到剪贴板，可粘贴到其他应用中分享。
          </view>
        </view>
        <view class="help-section">
          <view class="help-subtitle">4. 小程序码</view>
          <view class="help-text">
            点击"小程序码"按钮，可预览和保存小程序码图片，扫码即可访问。
          </view>
        </view>
      </view>
    </u-modal>
  </u-popup>
</template>

<script>
export default {
  name: "SharePopup",
  props: {
    visible: { type: Boolean, default: false },
    title: { type: String, default: "" },
    typeText: { type: String, default: "主页" },
    url: { type: String, default: "" },
    miniQr: { type: String, default: "" },
    miniPath: { type: String, default: "" },
    uid: { type: [String, Number], default: "" },
    type: { type: String, default: "" },
    hid: { type: [String, Number], default: "" },
  },
  data() {
    return {
      helpModalVisible: false,
    };
  },
  emits: ["update:visible", "action"],
  methods: {
    close() {
      this.$emit("update:visible", false);
    },
    openSettings() {
      uni.navigateTo({
        url: "/pagesOther/permissionSetting/permissionSetting",
      });
      this.close();
    },
    openHelp() {
      this.helpModalVisible = true;
    },
    closeHelpModal() {
      this.helpModalVisible = false;
    },

    // ---- 分享给好友（组件内部处理准备数据并打开分享菜单） ----
    async prepareAndShare() {
      // 打开分享菜单（若平台支持）
      if (typeof wx !== "undefined" && wx.showShareMenu) {
        try {
          wx.showShareMenu({ withShareTicket: true });
        } catch (e) {}
      }
      // 发通知给父组件（可选）
      this.$emit("action", { type: "prepare-share", payload: {} });
      // this.close()
    },
    async getUserShareLink() {
      const user = uni.getStorageSync("userInfo");
      const data = {
        target_user_id: user.id,
      };
      const res = await this.$go("user/home/share_link", data, "get", {
        show_err: true,
      });
      if (res.code === 0) {
        console.log(res);
      }
    },

    // ---- 复制链接内部实现 ----
    async handleCopyLink() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");
      const shareConfig = await this.$getShareConfig({
        type: "shortlink", // 分享类型
        userId: this.uid ? this.uid : userInfo.id,
        title: `分享${enterpriseInfo.company_name}的${this.typeText}`, // 默认标题
        path: this.url, // 默认路径
      });
      console.log(shareConfig);
      if (shareConfig.code === 0) {
        uni.setClipboardData({
          data: shareConfig.data,
          success: () => {
            uni.showToast({ title: "已复制链接", icon: "none" });
            this.$emit("action", { type: "copy", payload: { url: this.url } });
            this.close();
          },
          fail: () => {
            uni.showToast({ title: "复制失败", icon: "none" });
          },
        });
      } else {
        uni.showToast({
          title: shareConfig.msg,
          icon: "error",
          mask: true,
        });
      }
    },

    // ---- 预览小程序码 ----
    async handlePreviewMiniCode() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");
      const shareConfig = await this.$getShareConfig({
        type: "mini", // 分享类型
        userId: this.uid ? this.uid : userInfo.id,
        title: `分享${enterpriseInfo.company_name}的${this.typeText}`, // 默认标题
        path: this.url, // 默认路径
      });
      console.log(shareConfig);
      if (shareConfig.code === 0) {
        uni.previewImage({
          urls: [shareConfig.data.qrcode],
          current: shareConfig.data.tips,
        });
      } else {
        uni.showToast({
          title: shareConfig.msg,
          icon: "error",
          mask: true,
        });
      }
      this.close();
    },

    // ---- 生成海报：组件内部用 canvas 生成并预览，成功后通知父组件 ----
    async handleGeneratePoster() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");
      console.log(this.type);
      console.log(this.hid);
      const shareConfig = await this.$getShareConfig({
        type: "poster", // 分享类型
        userId: this.uid ? this.uid : userInfo.id,
        title: `分享${enterpriseInfo.company_name}的${this.typeText}`, // 默认标题
        path: this.url, // 默认路径
        atype: this.type,
        hid: this.hid,
      });
      console.log(shareConfig);
      if (shareConfig.code === 0) {
        uni.previewImage({
          urls: [shareConfig.data.share_thumb],
          current: shareConfig.data.show_name,
        });
      } else {
        uni.showToast({
          title: shareConfig.msg,
          icon: "error",
          mask: true,
        });
      }
    },

    // helper: download image url to local (returns url or temp path)
    downloadImage(url) {
      return new Promise((resolve) => {
        if (!url) return resolve(null);
        uni.downloadFile({
          url,
          success: (res) => {
            if (res.statusCode === 200) return resolve(res.tempFilePath);
            resolve(null);
          },
          fail: () => resolve(null),
        });
      });
    },

    // helper: getImageInfo
    getLocalImageInfo(path) {
      return new Promise((resolve, reject) => {
        uni.getImageInfo({
          src: path,
          success: (res) => resolve(res),
          fail: (e) => reject(e),
        });
      });
    },
  },
};
</script>

<style scoped lang="scss">
.root {
  padding: 50rpx 24rpx;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  font-weight: 400;
  font-size: 28rpx;
  color: #666666;
}

.bold {
  font-weight: bold;
}

.perm {
  font-weight: 400;
  font-size: 28rpx;
  color: #333333;
  align-items: center;
}

.icons {
  display: flex;
  justify-content: space-around;
  margin-top: 50rpx;
}

.icon-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
}

.icon-bg {
  width: 96rpx;
  height: 96rpx;
  border-radius: 96rpx;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon {
  width: 96rpx;
  height: 96rpx;
}

.icon-label {
  margin-top: 8rpx;
  font-weight: 400;
  font-size: 28rpx;
  color: #666666;
  line-height: 28rpx;
}

.help {
  text-align: center;
  padding: 12rpx 0 18rpx;
  font-weight: 400;
  font-size: 28rpx;
  color: #6a90ff;
  margin: 48rpx 0;
}

.cancel-wrap {
  padding: 0 18rpx 18rpx;
}

.cancel-btn {
  height: 72rpx;
  border-radius: 36rpx;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26rpx;
  color: #333;
  border: 1rpx solid #f0f0f0;
}

/* 清除 button 默认样式，使其外观像普通视图 */
.share-button {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  margin: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  -webkit-appearance: none !important;
  appearance: none !important;
  /* 保持原有布局类 icon-cell 的行为 */
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: auto;
  height: auto;
  line-height: 28rpx;

  &:after {
    display: none;
  }
}

/* 防止 focus 时出现系统样式（小程序 h5 可选） */
.share-button:focus {
  outline: none;
}

/* 帮助弹窗内容 */
.help-modal-content {
  padding: 20rpx;
  text-align: left;
}

.help-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  text-align: center;
  margin-bottom: 30rpx;
}

.help-section {
  margin-bottom: 30rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.help-subtitle {
  font-size: 28rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 12rpx;
}

.help-text {
  font-size: 26rpx;
  color: #666;
  line-height: 1.6;
}
</style>
