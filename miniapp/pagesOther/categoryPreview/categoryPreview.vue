<template>
  <view class="page">
    <view class="content">
      <view class="user-card">
        <view class="user-left">
          <image class="avatar" :src="displayAvatar" mode="aspectFill" />
          <text class="merchant-name">{{ displayMerchantName }}</text>
        </view>
        <view class="contact-btn" @tap="openContact">
          <text class="contact-text">联系我</text>
        </view>
      </view>

      <view class="category-panel">
        <view class="category-title-row">
          <image
            src="/static/icon/Frame 1171279070@2x.png"
            class="category-icon"
            mode="widthFix"
          />
          <text class="category-title">{{ displayCategoryTitle }}</text>
        </view>
        <text v-if="categoryDesc" class="category-desc">{{ categoryDesc }}</text>
      </view>

      <view class="empty-state">
        <image
          src="/static/icon/Frame@2x(25).png"
          mode="widthFix"
          class="empty-img"
        />
        <text class="empty-text">您还没有添加作品到当前合集</text>
        <view class="actions">
          <view class="btn" @tap="createProductInCategory">
            <image
              class="btn-icon"
              src="/static/icon/add-yellow-icon.png"
              mode="scaleToFill"
            />
            <text class="btn-text">新建产品</text>
          </view>
          <view class="btn secondary" @tap="selectExistingProducts">
            <image
              class="btn-icon"
              src="/static/icon/image-3@2x(2).png"
              mode="scaleToFill"
            />
            <text class="btn-text">添加产品</text>
          </view>
        </view>
      </view>
    </view>

    <view class="bottom-bar">
      <button
        class="bottom-action share-button"
        open-type="share"
        @tap="prepareShare"
      >
        <image
          src="/static/icon/24＊24@2x(4).png"
          class="bottom-icon"
          mode="widthFix"
        />
        <text class="bottom-text">分享</text>
      </button>
      <view class="bottom-action setting-action" @tap="openCategorySettings">
        <image src="/static/icon/Frame.png" class="bottom-icon" mode="widthFix" />
        <text class="bottom-text">设置</text>
      </view>
    </view>
  </view>
</template>

<script>
const safeDecodeRouteValue = (value = "") => {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  try {
    return decodeURIComponent(text);
  } catch (e) {
    return text;
  }
};

export default {
  data() {
    return {
      categoryId: "",
      categoryName: "",
      categoryDesc: "",
      privateType: 1,
      layoutType: 1,
      userInfo: {},
      shareUrl: "",
      shareTitle: "",
      redirectingToDetail: false,
    };
  },
  computed: {
    displayAvatar() {
      return (
        this.userInfo.company_logo ||
        this.userInfo.avatar ||
        this.userInfo.headimgurl ||
        "/static/image/headurl.jpg"
      );
    },
    displayMerchantName() {
      return (
        this.userInfo.company_name ||
        this.userInfo.nickname ||
        this.userInfo.nick_name ||
        this.userInfo.name ||
        "商户名称"
      );
    },
    displayCategoryTitle() {
      return this.categoryName || "分类名称";
    },
  },
  onLoad(options = {}) {
    this.categoryId = safeDecodeRouteValue(options.id || options.fid || "");
    this.categoryName = safeDecodeRouteValue(options.name || "");
    this.categoryDesc = safeDecodeRouteValue(options.desc || "");
    this.privateType = Number(options.private_type || 1);
    this.layoutType = Number(options.layout_type || 1) === 2 ? 2 : 1;
    this.initUserFromCache();
    this.updateShareMeta();
  },
  onShareAppMessage() {
    this.updateShareMeta();
    return {
      title: this.shareTitle,
      path: this.shareUrl,
    };
  },
  onShow() {
    this.consumeDetailRedirectMarker();
  },
  methods: {
    initUserFromCache() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.userInfo = enterpriseInfo.company_name ? enterpriseInfo : userInfo;
    },
    getOwnerId() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      return (
        userInfo.id ||
        userInfo.uid ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "") ||
        ""
      );
    },
    buildShareUrl() {
      const ownerId = this.getOwnerId();
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("category", this.categoryId, ownerId)
        : `/pagesOther/classDetail/classDetail?id=${this.categoryId}${ownerId ? `&uid=${ownerId}` : ""}`;
    },
    updateShareMeta() {
      this.shareUrl = this.buildShareUrl();
      this.shareTitle = `分享${this.displayMerchantName}的${this.displayCategoryTitle}`;
    },
    buildCurrentCategoryQuery(extra = {}) {
      const query = {
        category_id: this.categoryId,
        category_name: this.displayCategoryTitle,
        ...extra,
      };
      return Object.keys(query)
        .filter((key) => query[key] !== undefined && query[key] !== null && query[key] !== "")
        .map((key) => `${key}=${encodeURIComponent(query[key])}`)
        .join("&");
    },
    createProductInCategory() {
      if (!this.categoryId) return;
      const query = this.buildCurrentCategoryQuery({ fromPage: "categoryPreview" });
      uni.navigateTo({
        url: `/pagesOther/addProduct/addProduct?${query}`,
      });
    },
    selectExistingProducts() {
      if (!this.categoryId) return;
      const query = this.buildCurrentCategoryQuery({
        fromPage: "categoryPreview",
        albumId: this.categoryId,
      });
      uni.navigateTo({
        url: `/pagesOther/productSelect/productSelect?${query}`,
      });
    },
    openContact() {
      uni.showToast({ title: "请在分享预览中联系商户", icon: "none" });
    },
    prepareShare() {
      this.updateShareMeta();
    },
    openCategorySettings() {
      if (!this.categoryId) return;
      const ownerId = this.getOwnerId();
      uni.setStorageSync("folderInfo", {
        id: this.categoryId,
        folder_type: 1,
        folder_name: this.displayCategoryTitle,
        folder_desc: this.categoryDesc || "",
        pid: 0,
        private_type: this.privateType || 1,
        layout_type: this.layoutType || 1,
        pic_layout: this.layoutType || 1,
        uid: ownerId,
        show_connect: 1,
        set_top: 0,
        other_share: 0,
        show_upload_date: 0,
        show_search: 0,
        upload_field: [],
        editer_create: 1,
        editer_delete: 1,
        editer_delete_pic: 1,
      });
      uni.navigateTo({
        url: "/pagesOther/setPage/setPage",
      });
    },
    consumeDetailRedirectMarker() {
      if (this.redirectingToDetail || !this.categoryId) return;
      const redirectId = uni.getStorageSync("categoryPreviewRedirectToDetail");
      if (!redirectId || String(redirectId) !== String(this.categoryId)) return;
      uni.removeStorageSync("categoryPreviewRedirectToDetail");
      this.goToClassDetail();
    },
    goToClassDetail() {
      if (!this.categoryId) return;
      this.redirectingToDetail = true;
      uni.redirectTo({
        url: `/pagesOther/classDetail/classDetail?id=${encodeURIComponent(this.categoryId)}`,
      });
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 24rpx;
  padding-bottom: 160rpx;
  box-sizing: border-box;
}

.content {
  min-height: calc(100vh - 220rpx);
}

.user-card {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 24rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-sizing: border-box;
}

.user-left {
  min-width: 0;
  display: flex;
  align-items: center;
}

.avatar {
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  margin-right: 24rpx;
  flex: 0 0 96rpx;
}

.merchant-name {
  font-size: 32rpx;
  color: #222222;
  font-weight: 600;
  max-width: 360rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.contact-btn {
  width: 132rpx;
  height: 56rpx;
  background: #ffd800;
  border-radius: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 132rpx;
}

.contact-text {
  font-size: 24rpx;
  color: #222222;
}

.category-panel {
  margin-top: 34rpx;
}

.category-title-row {
  display: flex;
  align-items: center;
}

.category-icon {
  width: 48rpx;
  height: 48rpx;
  margin-right: 12rpx;
}

.category-title {
  font-size: 36rpx;
  color: #333333;
  font-weight: 600;
  line-height: 50rpx;
}

.category-desc {
  display: block;
  margin-top: 12rpx;
  font-size: 28rpx;
  color: #666666;
  line-height: 42rpx;
  word-break: break-all;
}

.empty-state {
  margin-top: 30rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-img {
  width: 545rpx;
  height: 545rpx;
}

.empty-text {
  margin-top: 10rpx;
  color: #999999;
  font-size: 26rpx;
}

.actions {
  display: flex;
  gap: 16rpx;
  margin-top: 40rpx;
}

.btn {
  width: 268rpx;
  height: 96rpx;
  border-radius: 96rpx;
  background: #222222;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
}

.btn-icon {
  width: 40rpx;
  height: 40rpx;
}

.btn-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #ffd800;
}

.bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: calc(env(safe-area-inset-bottom) + 124rpx);
  padding: 18rpx 48rpx calc(env(safe-area-inset-bottom) + 18rpx);
  box-sizing: border-box;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 28rpx;
  z-index: 20;
}

.bottom-action {
  border: 0;
  padding: 0;
  margin: 0;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  box-sizing: border-box;
}

.share-button {
  flex: 1;
  border-radius: 80rpx;
  background: #ffd800;
  gap: 10rpx;
}

.share-button::after {
  border: 0;
}

.setting-action {
  width: 160rpx;
  gap: 8rpx;
}

.bottom-icon {
  width: 44rpx;
  height: 44rpx;
}

.bottom-text {
  color: #333333;
  font-size: 30rpx;
  font-weight: 600;
}
</style>
