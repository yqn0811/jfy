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
        <view v-if="isOwnerShare" class="perm" @tap="openSettings">设置访问权限 ›</view>
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
    coverUrl: { type: String, default: "" },
    uid: { type: [String, Number], default: "" },
    type: { type: String, default: "" },
    hid: { type: [String, Number], default: "" },
    customTitle: { type: String, default: "" },
  },
  data() {
    return {
      helpModalVisible: false,
    };
  },
  computed: {
    ownerId() {
      return this.uid || (this.$getShareOwnerId ? this.$getShareOwnerId() : "");
    },
    isOwnerShare() {
      const currentUserId = this.$getCurrentUserId ? this.$getCurrentUserId() : "";
      return this.ownerId && String(this.ownerId) === String(currentUserId);
    },
    sharePath() {
      if (this.url) return this.url;
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath(this.type || "home", this.hid, this.ownerId)
        : "/pages/index/index";
    },
    shareTitle() {
      const customTitle = this.normalizeText(this.customTitle);
      if (customTitle) return customTitle;
      const targetName = this.normalizeText(this.title);
      const merchantName =
        this.type === "home"
          ? targetName
          : this.$getMerchantShareName
            ? this.$getMerchantShareName()
            : "";
      return this.$buildTypedShareTitle
        ? this.$buildTypedShareTitle({
            typeText: this.typeText,
            targetName: this.type === "home" ? "" : targetName,
            merchantName,
            prefix: "分享",
          })
        : `分享${targetName || this.typeText}`;
    },
  },
  emits: ["update:visible", "action"],
  methods: {
    normalizeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
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
    // ---- 复制链接内部实现 ----
    async handleCopyLink() {
      if (!this.ownerId) {
        uni.showToast({ title: "分享信息缺失", icon: "none" });
        return;
      }
      uni.showLoading({ title: "生成链接中", mask: true });
      try {
        const shareConfig = await this.withTimeout(
          this.$getShareConfig({
            type: "link",
            userId: this.ownerId,
            title: this.shareTitle,
            path: this.getMiniPagePath(),
            atype: this.type,
            hid: this.hid,
          }),
          20000,
          "链接生成超时"
        );
        const data = shareConfig && shareConfig.data ? shareConfig.data : {};
        const link =
          shareConfig && Number(shareConfig.code) === 0
            ? data.url_link || data.link || data.share_link || data.short_link || ""
            : "";
        if (!this.isOpenableShareLink(link)) {
          uni.showToast({ title: "链接生成失败", icon: "none" });
          return;
        }
        uni.setClipboardData({
          data: link,
          success: () => {
            uni.showToast({ title: "已复制链接", icon: "none" });
            this.$emit("action", { type: "copy", payload: { url: link } });
            this.close();
          },
          fail: () => {
            uni.showToast({ title: "复制失败", icon: "none" });
          },
        });
      } catch (e) {
        uni.showToast({ title: "链接生成失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },
    isOpenableShareLink(link) {
      const text = this.normalizeText(link);
      if (!text) return false;
      if (text.charAt(0) === "/") return false;
      return (
        /^https?:\/\//i.test(text) ||
        /^#小程序:\/\//.test(text) ||
        /^weixin:\/\//i.test(text)
      );
    },
    isPreviewableImageUrl(url) {
      const text = this.normalizeText(url);
      if (!text) return false;
      if (text.indexOf("/image/img_default.png") !== -1) return false;
      return /^https?:\/\//i.test(text) || text.charAt(0) === "/";
    },
    withTimeout(promise, ms, message) {
      let timer = null;
      const timeout = new Promise((resolve, reject) => {
        timer = setTimeout(() => reject(new Error(message || "请求超时")), ms);
      });
      return Promise.race([promise, timeout]).finally(() => {
        if (timer) clearTimeout(timer);
      });
    },

    // ---- 预览小程序码 ----
    async handlePreviewMiniCode() {
      if (!this.ownerId) {
        uni.showToast({ title: "分享信息缺失", icon: "none" });
        return;
      }
      uni.showLoading({ title: "生成小程序码中", mask: true });
      try {
        const shareConfig = await this.withTimeout(
          this.$getShareConfig({
            type: "mini",
            userId: this.ownerId,
            title: this.shareTitle,
            path: this.getMiniPagePath(),
            atype: this.type,
            hid: this.hid,
          }),
          20000,
          "小程序码生成超时"
        );
        const qrcode =
          (shareConfig && shareConfig.data && shareConfig.data.qrcode) ||
          "";
        if (shareConfig && Number(shareConfig.code) === 0 && this.isPreviewableImageUrl(qrcode)) {
          uni.previewImage({
            urls: [qrcode],
            current: qrcode,
          });
          this.$emit("action", { type: "preview-mini", payload: { url: qrcode } });
          this.close();
          return;
        }
        uni.showToast({
          title: (shareConfig && shareConfig.msg) || "小程序码生成失败",
          icon: "none",
        });
      } catch (e) {
        uni.showToast({ title: "小程序码生成失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },

    // ---- 生成海报：组件内部用 canvas 生成并预览，成功后通知父组件 ----
    async handleGeneratePoster() {
      if (!this.ownerId) {
        uni.showToast({ title: "分享信息缺失", icon: "none" });
        return;
      }
      uni.showLoading({ title: "生成海报中", mask: true });
      try {
        const shareConfig = await this.withTimeout(
          this.$getShareConfig({
            type: "poster",
            userId: this.ownerId,
            title: this.shareTitle,
            path: this.getMiniPagePath(),
            atype: this.type,
            hid: this.hid,
            coverUrl: this.coverUrl,
          }),
          20000,
          "海报生成超时"
        );
        const data = shareConfig && shareConfig.data ? shareConfig.data : {};
        const poster = data.share_thumb || data.poster || data.imageUrl || "";
        if (shareConfig && Number(shareConfig.code) === 0 && this.isPreviewableImageUrl(poster)) {
          uni.previewImage({
            urls: [poster],
            current: poster,
          });
          this.$emit("action", { type: "poster", payload: { url: poster } });
          this.close();
          return;
        }
        uni.showToast({
          title: (shareConfig && shareConfig.msg) || "海报生成失败",
          icon: "none",
        });
      } catch (e) {
        uni.showToast({ title: "海报生成失败", icon: "none" });
      } finally {
        uni.hideLoading();
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
    getMiniPagePath() {
      if (this.miniPath) {
        return this.miniPath.charAt(0) === "/" ? this.miniPath.slice(1) : this.miniPath;
      }
      const path = this.sharePath || "";
      if (path) {
        return path.charAt(0) === "/" ? path.slice(1) : path;
      }
      if (this.type === "product") return "pagesOther/productDetail/productDetail";
      if (this.type === "category") return "pagesOther/classDetail/classDetail";
      return "pages/index/index";
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
  padding: 50rpx 24rpx 34rpx;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18rpx;
}

.title {
  flex: 1;
  min-width: 0;
  font-weight: 400;
  font-size: 28rpx;
  color: #666666;
  line-height: 40rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bold {
  font-weight: bold;
}

.perm {
  flex-shrink: 0;
  font-weight: 400;
  font-size: 28rpx;
  color: #333333;
  align-items: center;
  white-space: nowrap;
}

.icons {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  justify-items: center;
  column-gap: 8rpx;
  margin-top: 50rpx;
}

.icon-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
  width: 100%;
  min-width: 0;
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
  line-height: 34rpx;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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
  width: 100%;
  min-width: 0;
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
