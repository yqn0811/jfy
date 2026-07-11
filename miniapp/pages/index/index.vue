<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 头部信息区 -->
      <view class="header">
        <!-- 顶部状态栏 + 导航 -->
        <view
          class="top-safe"
          :style="{ height: statusBarHeight + 'px' }"
        ></view>
        <view class="nav-wrap" :style="{ height: navigationBarHeight + 'px' }">
          <view v-if="!previewMode" class="nav-left">
            <view
              class="nav-left-item"
              :style="{ height: navButtonHeight + 'px' }"
              @click="onSetting"
            >
              <image
                src="/static/icon/setting-icon.png"
                class="nav-icon"
                mode="widthFix"
              >
              </image>
              <view class="nav-text">设置</view>
            </view>
            <view
              class="nav-left-item"
              :style="{ height: navButtonHeight + 'px' }"
              @click="onPreview"
            >
              <image
                src="/static/icon/eye@2x.png"
                class="nav-icon"
                mode="widthFix"
              >
              </image>
              <view class="nav-text">预览</view>
            </view>
          </view>
          <view v-else class="nav-left" @click="onPreview">
            <image
              src="/static/icon/home@2x(2).png"
              class="nav-icon"
              mode="widthFix"
            ></image>
          </view>
        </view>
        <view class="header-inner">
          <view class="header-avatar">
            <image
              class="avatar"
              :src="displayCompanyLogo"
              mode="aspectFill"
            >
            </image>
            <view class="meta-top">
              <text class="merchant-name">{{ displayMerchantName }}</text>
              <view class="gender-wrap" v-if="displayIndustryLabel">
                <text>{{ displayIndustryLabel }}</text>
              </view>
            </view>
          </view>
          <text class="desc" v-if="displayCompanyDesc">{{
            displayCompanyDesc
          }}</text>
          <view class="stats-row">
            <view class="stats-row-inner">
              <view class="stat">
                <text class="stat-number">{{ total_num || 0 }}</text>
                <text class="stat-label">产品</text>
              </view>
            </view>
            <view class="actions" v-if="uid">
              <view class="actions-inner btn-outline" @click="openCustomer">
                <image
                  src="/static/icon/客服@2x.png"
                  class="stat-icon"
                  mode="aspectFill"
                ></image>
                <view class="btn-text">{{ serviceActionLabel }}</view>
              </view>
              <view class="actions-inner btn-follow">
                <image
                  src="/static/icon/star@2x.png"
                  class="stat-icon"
                  mode="aspectFill"
                ></image>
                <view class="btn-text" @click="toggleFollow">{{
                  isFollow ? "已关注" : "关注"
                }}</view>
              </view>
            </view>
          </view>
        </view>
      </view>
      <view class="content-warp">
        <!-- 分类筛选 -->
        <view class="category-filter">
          <scroll-view
            class="chips-scroll"
            scroll-x="true"
            scroll-with-animation="true"
            :style="{ paddingBottom: safeAreaBottom + 'rpx' }"
            show-scrollbar="false"
            enable-flex="true"
          >
            <view class="chips-inner">
              <view
                class="chip"
                :class="{ active: curCategoryId === '' }"
                @click="selectCategory({ id: '' })"
              >
                <image
                  class="chip-icon"
                  src="/static/icon/elements.png"
                  mode="aspectFit"
                />
                <text class="chip-text">全部</text>
              </view>
              <view
                class="chip"
                :class="{ active: curCategoryId === c.id }"
                v-for="(c, idx) in categories"
                :key="getCategoryKey(c, idx)"
                @click="selectCategory(c)"
              >
                <image
                  class="chip-icon"
                  src="/static/icon/folder-open@2x.png"
                  mode="aspectFit"
                />
                <text class="chip-text">{{ formatCategoryName(c) }}</text>
              </view>
            </view>
          </scroll-view>
        </view>
        <!-- 内容区：图片网格 -->
        <view class="content">
          <ImageGrid
            :list="albumList"
            nameField="folder_name"
            imageField="new_thumb"
            countField="folder_count"
            :columns="columns"
            @click="handleImageClick"
          >
          </ImageGrid>

          <view class="loading-more" v-if="isAlbumLoading">加载中...</view>
          <view class="no-more" v-if="!isAlbumLoading && albumList.length === 0"
            >暂无内容</view
          >
        </view>
      </view>
    </view>

    <personal-details
      :use-popup="true"
      :uid="uid || ''"
      :visible="personalVisible"
      @update:visible="(val) => (personalVisible = val)"
    />
    <!-- 自定义底部导航 -->
    <custom-tab-bar
      v-if="!previewMode"
      @createProduct="navigateToCreate"
    />
    <view v-if="previewMode" class="preview-wrap">
      <view class="preview-item" @click="openCustomer">
        <image
          class="preview-item-icon"
          src="/static/icon/user.png"
          mode="scaleToFill"
        />
        <view class="preview-item-text">{{ serviceActionLabel }}</view>
      </view>
      <view class="preview-item" @click="openCategory">
        <image
          class="preview-item-icon"
          src="/static/icon/elements.png"
          mode="scaleToFill"
        />
        <view class="preview-item-text">分类</view>
      </view>
      <view class="preview-item" @tap="openShare">
        <image
          class="preview-item-icon"
          src="/static/icon/24_24.png"
          mode="scaleToFill"
        />
        <view class="preview-item-text">分享</view>
      </view>
    </view>
    <category-popup
      :uid="uid || ''"
      :categories="categories"
      :visible="showCategory"
      @update:visible="(val) => (showCategory = val)"
      @select="onCategorySelected"
    />
    <SharePopup
      :visible="shareVisible"
      :title="userInfo.company_name || ''"
      :custom-title="homeShareTitle"
      :url="shareUrl"
      typeText="主页"
      type="home"
      :hid="homeId"
      :uid="uid || ''"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
      :cover-url="shareCoverUrl"
      @update:visible="(val) => (shareVisible = val)"
      @action="handleShareAction"
    />
    <setting-popup
      :visible="showSettingPopup"
      :columns="columns"
      @toggleDisplayMode="toggleDisplayMode"
      @update:visible="(val) => (showSettingPopup = val)"
    />
    <!-- 立即新建弹窗组件 -->
    <CreatePopup
      :show.sync="showCreatePopup"
      fromPage="index"
      @close="handleCreateClose"
    >
    </CreatePopup>
  </view>
</template>

<script>
import CreatePopup from "@/components/CreatePopup/index.vue";
import CategoryPopup from "./components/CategoryPopup.vue";
import CustomTabBar from "@/components/CustomTabBar/index.vue";
import ImageGrid from "@/components/ImageGrid/index.vue";
import PersonalDetails from "@/components/PersonalDetails/index.vue";
import SharePopup from "@/components/SharePopup/index.vue";
import settingPopup from "./components/settingPopup.vue";
import {
  consumeRefreshMarker,
  getRefreshMarker,
  markRefreshMarkerConsumed,
} from "@/common/helper/refresh.js";
import { getObjectId, showInvalidRecordToast } from "@/common/helper/clickItem.js";

import { getMiniCode, setPendingInviteCode } from "@/common/request/api.js";

export default {
  name: "IndexPage",
  components: {
    CreatePopup,
    CustomTabBar,
    ImageGrid,
    PersonalDetails,
    SharePopup,
    CategoryPopup,
    settingPopup,
  },
  data() {
    return {
      columns: 2,
      previewMode: false,
      showCreatePopup: false,
      showCategory: false,
      personalVisible: false,
      showSettingPopup: false, // 设置弹窗显示状态
      statusBarHeight: 0,
      navigationBarHeight: 44,
      navButtonHeight: 32,
      headerHeight: 520, // 头部占用高度（rpx）
      tabbarHeight: 140, // 自定义tabbar高度（rpx）
      safeAreaBottom: 0,
      total_num: 0,
      albumList: [],
      categories: [],
      isAlbumLoading: false,
      albumRequestSeq: 0,
      userInfo: {},
      isFollow: false,
      shareVisible: false,
      shareUrl: "/pages/index/index",
      shareMiniPath: "",
      shareMiniQr: "",
      curCategoryId: "",
      uid: "",
      homeId: "",
      lastProductRefreshAt: "",
      lastCategoryRefreshAt: "",
      lastHomeRefreshAt: "",
      isUserInfoLoading: false,
      hasRedirectedForHomeInfo: false,
      industryOptions: {
        1: "微供",
        2: "网供",
        3: "摄影",
      },
    };
  },
  onLoad(options) {
    const sceneOptions = this.parseSceneOptions(options);
    options = {
      ...(options || {}),
      ...sceneOptions,
    };
    this.initNavigationMetrics();
    this.safeAreaBottom = 0;
    const userInfo = uni.getStorageSync("userInfo") || {};
    this.homeId = userInfo.id || userInfo.uid || "";
    const optionUid = this.normalizeShareParam(options.uid);
    const inviteCode = this.normalizeShareParam(options.invite_code);
    if (inviteCode) {
      setPendingInviteCode(inviteCode);
    }
    if (optionUid) {
      this.uid = optionUid;
      this.homeId = optionUid;
      this.previewMode = true;
    }
    this.shareUrl = this.buildHomeSharePath();
    if (this.redirectSceneTarget(options)) {
      return;
    }
    this.getUserInfo();
    // 监听分类更新事件
    uni.$on("refreshIndexData", this.handleRefreshData);
  },
  onShow() {
    this.consumePendingRefreshMarkers();
  },
  onUnload() {
    uni.$off("refreshIndexData", this.handleRefreshData);
  },
  onShareAppMessage() {
    return {
      title: this.homeShareTitle,
      path: this.buildHomeSharePath(),
      imageUrl: this.shareCoverUrl,
    };
  },
  computed: {
    displayCompanyLogo() {
      return this.safeText(this.userInfo.company_logo) || "/static/image/headurl.jpg";
    },
    displayMerchantName() {
      return (
        this.safeText(this.userInfo.company_name) ||
        this.safeText(this.userInfo.nickname) ||
        "商户名称"
      );
    },
    displayCompanyDesc() {
      return this.safeText(this.userInfo.company_desc);
    },
    displayIndustryLabel() {
      return this.industryOptions[Number(this.userInfo.industry_info)] || "";
    },
    homeShareTitle() {
      return (
        this.safeText(this.userInfo.home_share_title) ||
        `分享${this.displayMerchantName || "商户"}的主页`
      );
    },
    serviceActionLabel() {
      return this.safeText(this.userInfo.home_service_name) || "服务";
    },
    shareCoverUrl() {
      const firstAlbum = this.albumList.find((item) => this.safeText(item.new_thumb));
      return this.safeText(firstAlbum && firstAlbum.new_thumb);
    },
  },
  methods: {
    getCategoryKey(item, index) {
      return item && item.id ? `category-${item.id}` : `category-${index}`;
    },
    initNavigationMetrics() {
      const sys = this.$base.getSystemInfoCompat();
      const statusBarHeight = sys.statusBarHeight || 0;
      let navigationBarHeight = 44;
      let navButtonHeight = 32;

      if (uni.getMenuButtonBoundingClientRect) {
        const menuButton = uni.getMenuButtonBoundingClientRect();
        if (menuButton && menuButton.height && menuButton.top >= statusBarHeight) {
          const verticalGap = menuButton.top - statusBarHeight;
          navigationBarHeight = menuButton.height + verticalGap * 2;
          navButtonHeight = menuButton.height;
        }
      }

      this.statusBarHeight = statusBarHeight;
      this.navigationBarHeight = navigationBarHeight;
      this.navButtonHeight = navButtonHeight;
    },
    parseSceneOptions(options = {}) {
      if (!options.scene || !this.$parseShareScene) return {};
      return this.$parseShareScene(options.scene);
    },
    normalizeShareParam(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    buildHomeSharePath() {
      const ownerId =
        this.uid ||
        this.homeId ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "");
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("home", "", ownerId)
        : `/pages/index/index${ownerId ? `?uid=${ownerId}` : ""}`;
    },
    redirectSceneTarget(options) {
      const type = options.type;
      const id = this.normalizeShareParam(options.id);
      const uid = this.normalizeShareParam(options.uid);
      if (!id || !uid) return false;
      if (type !== "product" && type !== "category" && type !== "selection") {
        return false;
      }
      const path =
        type === "selection"
          ? `/pagesOther/styleResult/styleResult?id=${encodeURIComponent(id)}&uid=${encodeURIComponent(uid)}`
          : this.$buildPublicSharePath
            ? this.$buildPublicSharePath(type, id, uid)
            : `/pages/index/index?uid=${encodeURIComponent(uid)}`;
      uni.redirectTo({ url: path });
      return true;
    },
    safeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    redirectToSelectionForMissingHome() {
      if (this.hasRedirectedForHomeInfo) return;
      this.hasRedirectedForHomeInfo = true;
      uni.redirectTo({
        url: "/pages/selection/selection?from=home_info_missing&disableAutoRedirect=1",
      });
    },
    consumePendingRefreshMarkers() {
      if (this.uid) return;
      const productMarker = consumeRefreshMarker(
        "product",
        "productListNeedsRefreshIndexConsumed",
        this.lastProductRefreshAt,
      );
      const categoryMarker = consumeRefreshMarker(
        "category",
        "categoryListNeedsRefreshIndexConsumed",
        this.lastCategoryRefreshAt,
      );
      const homeMarker = consumeRefreshMarker(
        "home",
        "homeDataNeedsRefreshIndexConsumed",
        this.lastHomeRefreshAt,
      );
      if (!productMarker && !categoryMarker && !homeMarker) return;
      this.markProductRefreshConsumed(productMarker);
      this.markCategoryRefreshConsumed(categoryMarker);
      this.markHomeRefreshConsumed(homeMarker);
      this.handleRefreshData();
    },
    consumeProductRefreshMarker() {
      const marker = consumeRefreshMarker(
        "product",
        "productListNeedsRefreshIndexConsumed",
        this.lastProductRefreshAt,
      );
      if (!marker) return;
      this.markProductRefreshConsumed(marker);
      this.handleRefreshData();
    },
    markProductRefreshConsumed(marker) {
      if (!marker) return;
      if (getRefreshMarker("product") !== marker) return;
      this.lastProductRefreshAt = marker;
      markRefreshMarkerConsumed("productListNeedsRefreshIndexConsumed", marker);
    },
    markCategoryRefreshConsumed(marker) {
      if (!marker) return;
      if (getRefreshMarker("category") !== marker) return;
      this.lastCategoryRefreshAt = marker;
      markRefreshMarkerConsumed("categoryListNeedsRefreshIndexConsumed", marker);
    },
    markHomeRefreshConsumed(marker) {
      if (!marker) return;
      if (getRefreshMarker("home") !== marker) return;
      this.lastHomeRefreshAt = marker;
      markRefreshMarkerConsumed("homeDataNeedsRefreshIndexConsumed", marker);
    },
    toggleDisplayMode(data) {
      this.columns = this.normalizeDirectColumns(data);
    },
    // 处理刷新数据事件
    handleRefreshData(marker) {
      this.markProductRefreshConsumed(marker);
      this.markCategoryRefreshConsumed(marker);
      this.markHomeRefreshConsumed(marker);
      this.curCategoryId = "";
      this.getUserInfo();
    },
    openCategory() {
      this.showCategory = true;
    },
    onCategorySelected(c) {
      uni.navigateTo({
        url: this.$buildPublicSharePath
          ? this.$buildPublicSharePath("category", c.id, this.uid)
          : `/pagesOther/classDetail/classDetail?id=${c.id}&uid=${this.uid}`,
        success: (res) => {
          // 通过 eventChannel 向被打开页面传送数据
          res.eventChannel.emit("acceptDataFromOpenerPage", { data: c });
        },
      });
    },
    openShare() {
      if (!this.uid && !this.$checkLoginStatus()) {
        uni.showModal({
          title: "未登录，是否立即登录？",
          content: "",
          showCancel: true,
          success: ({ confirm, cancel }) => {
            if (confirm) {
              this.$silentLogin(this.uid);
            }
          },
        });
        return;
      }
      this.shareUrl = this.buildHomeSharePath();
      this.shareVisible = true;
    },
    handleShareAction(event) {
      // event.type: 'share'|'copy'|'preview-mini'|'poster'|'open-settings'|'open-help'
      // event.payload 包含对应数据
      // 按需处理（多数情况父组件不必处理）
    },
    resolveProductClickItem(item, index) {
      if (item && item.detail && Array.isArray(item.detail.__args__)) {
        return item.detail.__args__[0] || null;
      }
      if (item && item.detail && item.detail.item) {
        return item.detail.item;
      }
      if (item && typeof item === "object" && !item.currentTarget) {
        return item;
      }
      if (Number.isInteger(index) && this.albumList[index]) {
        return this.albumList[index];
      }
      return null;
    },
    handleImageClick(item, index) {
      const current = this.resolveProductClickItem(item, index);
      const productId = getObjectId(current, ["id", "product_id", "folder_id", "fid"]);
      if (!current || !productId) {
        showInvalidRecordToast();
        return;
      }
      if (this.previewMode) {
        uni.navigateTo({
          url: this.$buildPublicSharePath
            ? this.$buildPublicSharePath("product", productId, this.uid)
            : "/pagesOther/productDetail/productDetail?id=" + productId + "&uid=" + this.uid,
        });
      } else {
        uni.navigateTo({
          url:
            "/pagesOther/productDetailsSelf/productDetailsSelf?id=" + productId,
        });
      }
    },
    onPreview() {
      if (this.uid) {
        uni.redirectTo({ url: "/pages/index/index" });
        return;
      }
      this.previewMode = !this.previewMode;
    },
    onSetting() {
      // 显示设置弹窗
      this.showSettingPopup = true;
    },
    openCustomer() {
      this.personalVisible = true;
    },
    async toggleFollow() {
      if (!this.$checkLoginStatus()) {
        uni.showModal({
          title: "未登录，是否立即登录？",
          content: "",
          showCancel: true,
          success: ({ confirm, cancel }) => {
            if (confirm) {
              this.$silentLogin(this.uid);
            }
          },
        });
        return;
      }
      const isFollow = !this.isFollow;
      const newStatus = await this.$toggleFavorite({
        type: "homepage", // 'homepage' | 'product' | 'category'
        id: this.uid, // 目标ID
        isFavorite: isFollow, // 当前是否已收藏
      });
      if (newStatus) {
        this.isFollow = !this.isFollow;
      }
    },
    selectCategory(item) {
      this.curCategoryId = item.id;
      this.columns = !item.id
        ? this.getSavedHomeColumns()
        : this.getCategoryColumns(item);
      this.getAlbumList();
    },
    formatCategoryName(item) {
      const name = item && item.folder_name ? String(item.folder_name).trim() : "";
      return name || "未命名";
    },
    normalizeDirectColumns(value) {
      return Number(value) === 1 ? 1 : 2;
    },
    getCategoryColumns(item) {
      if (item && item.layout_type !== undefined && item.layout_type !== null && item.layout_type !== "") {
        return Number(item.layout_type) === 2 ? 1 : 2;
      }
      if (item && item.pic_layout !== undefined && item.pic_layout !== null && item.pic_layout !== "") {
        return Number(item.pic_layout) === 1 ? 1 : 2;
      }
      return 2;
    },
    getSavedHomeColumns() {
      return uni.getStorageSync("displayMode") === "list" ? 1 : 2;
    },
    navigateToCreate() {
      this.showCreatePopup = true;
    },
    handleCreateClose() {
      this.showCreatePopup = false;
    },
    createAlbum() {
      this.showCreatePopup = false;
      uni.navigateTo({ url: "/pagesOther/addSetInfo/addSetInfo?type=album" });
    },
    createFolder() {
      this.showCreatePopup = false;
      uni.navigateTo({ url: "/pagesOther/addSetInfo/addSetInfo?type=folder" });
    },
    getUserInfo() {
      if (this.isUserInfoLoading) return;
      const data = {};
      const uid = this.normalizeShareParam(this.uid);
      if (uid) {
        data.target_user_id = uid;
      }
      this.isUserInfoLoading = true;
      this.$go("user/home/info", data, "get", {
        show_err: false,
      })
        .then((res) => {
          this.isUserInfoLoading = false;
          if (res.code === 0) {
            this.hasRedirectedForHomeInfo = false;
            // 合并默认数据和实际数据，确保所有字段都有值
            const userInfo = {
              ...(res.data || {}),
            };
            this.userInfo = userInfo;
            this.homeId =
              this.normalizeShareParam(userInfo.id || userInfo.uid || userInfo.user_id) ||
              this.homeId;
            const products = Array.isArray(userInfo.products)
              ? userInfo.products
              : [];
            this.total_num =
              products.length ||
              Number(userInfo.product_count || userInfo.total_num || 0);
            this.isFollow = userInfo.is_collect;
            if (!this.uid) {
              uni.setStorageSync("enterpriseInfo", userInfo);
            }
            this.getAlbumList();
            this.getAllClassList();
          } else {
            if (this.uid) {
              uni.showToast({
                title: "主页暂不可访问",
                icon: "none",
              });
              return;
            }
            this.redirectToSelectionForMissingHome();
          }
        })
        .catch((err) => {
          this.isUserInfoLoading = false;
          console.error("获取商户信息失败:", err);
        });
    },
    // 获取产品列表
    getAlbumList() {
      const requestSeq = this.albumRequestSeq + 1;
      this.albumRequestSeq = requestSeq;
      this.isAlbumLoading = true;
      const data = {
        folder_type: 2,
        fid: this.curCategoryId,
      };
      if (this.uid) {
        data.target_user_id = this.uid;
        data.cate_id = this.curCategoryId;
      }
      const url = this.uid ? "user/home/products" : "album/lists/folder";
      const methods = this.uid ? "get" : "post";
      this.$go(url, data, methods, {
        show_err: true,
      })
        .then((res) => {
          if (requestSeq !== this.albumRequestSeq) return;
          if (res.code === 0 && res.data) {
            const dataList = this.uid
              ? Array.isArray(res.data)
                ? res.data
                : []
              : res.data.lists && Array.isArray(res.data.lists.data)
                ? res.data.lists.data
                : [];
            this.albumList = dataList.map((item) => {
              const count = this.uid
                ? item.son_count
                : this.normalizeArray(item.detail_pic_ids_arr || item.detail_pic_ids)
                    .length +
                  this.normalizeArray(item.pic_ids_arr || item.pic_ids).length;
              return {
                ...item,
                folder_count: count,
              };
            });
          }
        })
        .catch((err) => {
          if (requestSeq !== this.albumRequestSeq) return;
          this.albumList = [];
          console.error("获取商户信息失败:", err);
        })
        .finally(() => {
          if (requestSeq === this.albumRequestSeq) {
            this.isAlbumLoading = false;
          }
        });
    },
    // 获取所有分类
    getAllClassList() {
      const data = {
        folder_type: 1,
      };
      if (this.uid) {
        data.target_user_id = this.uid;
      }
      const url = this.uid ? "user/home/categories" : "album/lists/folder";
      const methods = this.uid ? "get" : "post";
      this.$go(url, data, methods, {
        show_err: true,
      })
        .then((res) => {
          if (res.code === 0 && res.data) {
            this.categories = this.uid
              ? Array.isArray(res.data)
                ? res.data
                : []
              : res.data.lists && Array.isArray(res.data.lists.data)
                ? res.data.lists.data
                : [];
          }
        })
        .catch((err) => {
          console.error("获取商户信息失败:", err);
        });
    },
    normalizeArray(value) {
      if (Array.isArray(value)) return value;
      if (typeof value === "string" && value) {
        return value.split(",").filter(Boolean);
      }
      return [];
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  width: 100vw;
  height: 100vh;
  position: relative;
  overflow-y: scroll;
  display: flex;
  flex-direction: column;

  .page-scoll {
    width: 100%;
    padding-bottom: 200rpx;
  }
}

/* 顶部导航 */
.top-safe {
  width: 100%;
  background: transparent;
}

.nav-wrap {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0;
  color: #333;
  position: relative;
}

.nav-left-item {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 96rpx;
  box-sizing: border-box;
  padding: 0 26rpx;
  gap: 10rpx;
}

.nav-left,
.nav-right {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
}

.nav-center {
  font-size: 36rpx;
  font-weight: 700;
  color: #222;
}

.nav-icon {
  width: 44rpx;
  height: 44rpx;
}

.nav-text {
  font-weight: 400;
  font-size: 28rpx;
  color: #ffffff;
}

/* 头部信息 */
.header {
  padding: 0 20rpx;
  background: linear-gradient(0deg, #4d482e 0%, #b6a764 100%);
  padding-bottom: 60rpx;
}

.header-inner {
  position: relative;
}

.header-avatar {
  display: flex;
  align-items: center;
  gap: 24rpx;
  margin-top: 32rpx;
  min-width: 0;
}

.avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 60rpx;
  object-fit: cover;
  flex: 0 0 120rpx;
  display: block;
  overflow: hidden;
}

.meta-top {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  flex: 1;
  min-width: 0;
}

.merchant-name {
  display: block;
  font-weight: 500;
  font-size: 40rpx;
  color: #ffffff;
  text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.2);
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.gender-wrap {
  align-self: flex-start;
  min-width: 64rpx;
  max-width: 112rpx;
  height: 36rpx;
  padding: 0 14rpx;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 18rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20rpx;
  line-height: 36rpx;
  color: #fff;
  box-sizing: border-box;

  text {
    display: block;
    max-width: 84rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 36rpx;
  }
}

.gender {
  width: 28rpx;
  height: 28rpx;
}

.desc {
  display: -webkit-box;
  width: 100%;
  box-sizing: border-box;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-top: 28rpx;
  line-height: 1.45;
  max-height: 104rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  word-break: break-all;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

/* 统计与按钮 */
.stats-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 24rpx;
  gap: 20rpx;

  .stats-row-inner {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex: 1;
    min-width: 0;
  }
}

.stat {
  display: flex;
  align-items: center;
  gap: 10rpx;
  min-height: 64rpx;
}

.stat-number {
  font-size: 36rpx;
  font-weight: 700;
  color: #fff;
}

.stat-label {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-left: 6rpx;
}

.actions {
  display: flex;
  align-items: center;
  gap: 16rpx;
  flex: 0 0 auto;
  align-self: center;

  .actions-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    box-sizing: border-box;
    flex: 0 0 auto;

    .stat-icon {
      width: 32rpx;
      height: 32rpx;
      flex: 0 0 32rpx;
    }

    .btn-text {
      font-weight: 400;
      font-size: 28rpx;
      line-height: 1;
      white-space: nowrap;
    }
  }
}

.btn-outline {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  border: none;
  padding: 12rpx 24rpx;
  border-radius: 28rpx;
  font-size: 24rpx;
  border: 1rpx solid #ffffff;
  min-width: 104rpx;
  height: 64rpx;
}

.btn-follow {
  background: #ffd700;
  color: #222;
  border: none;
  padding: 12rpx 28rpx;
  border-radius: 28rpx;
  font-size: 24rpx;
  min-width: 104rpx;
  height: 64rpx;
}

.content-warp {
  flex: 1;
  background: #f5f5f5;
  border-radius: 32rpx 32rpx 0rpx 0rpx;
  padding: 0 20rpx;
  overflow: hidden;
  margin-top: -32rpx;
}

/* 分类筛选 */
.category-filter {
  display: flex;
  align-items: center;
  padding: 26rpx 0 4rpx;
}

.chips-scroll {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
}

.chips-inner {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12rpx;
}

.chip {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  height: 56rpx;
  max-width: 190rpx;
  padding: 0 18rpx;
  background: rgba(255, 255, 255, 0.86);
  border: 1rpx solid rgba(217, 217, 217, 0.88);
  border-radius: 18rpx;
  box-sizing: border-box;
  white-space: nowrap;

  &.active {
    background: #fff4bf;
    border-color: #ffd000;
    box-shadow: 0 6rpx 14rpx rgba(255, 208, 0, 0.16);

    .chip-icon {
      opacity: 1;
    }

    .chip-text {
      color: #5f4a00;
      font-weight: 700;
    }
  }

  .chip-icon {
    width: 24rpx;
    height: 24rpx;
    flex: 0 0 auto;
    opacity: 0.74;
  }

  .chip-text {
    display: block;
    max-width: 126rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 24rpx;
    font-weight: 500;
    color: #555555;
    line-height: 1;
  }
}

/* 内容区与网格 */
.content {
  height: 100%;
  background: transparent;
  margin-top: 24rpx;
}

.grid {
  display: flex;
  flex-wrap: wrap;
  gap: 30rpx;
  justify-content: space-between;
}

.card {
  width: calc(50% - 15rpx);
}

.thumb-wrap {
  position: relative;
  border-radius: 16rpx;
  overflow: hidden;
  background: #f3f3f3;
}

.thumb {
  width: 100%;
  height: 360rpx;
  object-fit: cover;
  display: block;
}

.badge {
  position: absolute;
  left: 16rpx;
  top: 16rpx;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  padding: 6rpx 14rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
}

.series {
  position: absolute;
  left: 12rpx;
  bottom: 12rpx;
  color: #fff;
  font-size: 26rpx;
  background: linear-gradient(90deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2));
  padding: 8rpx 12rpx;
  border-radius: 8rpx;
}

/* 悬浮中间加号 */
.floating-action {
  position: fixed;
  bottom: 160rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 140rpx;
  height: 140rpx;
  border-radius: 70rpx;
  background: #ffd700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.12);
  z-index: 50;
}

.floating-action image {
  width: 80rpx;
  height: 80rpx;
}

/* 自定义弹窗（简洁） */
.create-popup {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  top: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 200;
}

.popup {
  width: 100%;
  padding: 30rpx;
  background: #fff;
  border-top-left-radius: 30rpx;
  border-top-right-radius: 30rpx;
}

.popup-row {
  display: flex;
  gap: 30rpx;
}

.option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30rpx 0;
  background: #f8f8f8;
  border-radius: 16rpx;
}

.option image {
  width: 100rpx;
  height: 100rpx;
  margin-bottom: 12rpx;
}

.option text {
  font-size: 26rpx;
}

/* loading / empty */
.loading-more,
.no-more {
  text-align: center;
  font-size: 24rpx;
  color: #999;
  margin: 30rpx 0;
}

.preview-wrap {
  width: 100%;
  position: fixed;
  bottom: 0;
  left: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: #ffffff;
  /* 安全区兼容：先声明常规值，再使用 env */
  padding-top: 16rpx;
  padding-bottom: calc(constant(safe-area-inset-bottom));
  padding-bottom: calc(env(safe-area-inset-bottom));

  .preview-item {
    display: flex;
    flex-direction: column;

    .preview-item-icon {
      width: 48rpx;
      height: 48rpx;
    }

    .preview-item-text {
      font-weight: 400;
      font-size: 24rpx;
      color: #333333;
    }
  }
}

/* 兼容底部安全区 */
</style>
