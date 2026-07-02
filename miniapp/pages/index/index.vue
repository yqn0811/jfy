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
            <view class="nav-left-item" @click="onSetting">
              <image
                src="/static/icon/setting-icon.png"
                class="nav-icon"
                mode="widthFix"
              >
              </image>
              <view class="nav-text">设置</view>
            </view>
            <view class="nav-left-item" @click="onPreview">
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
              :src="userInfo.company_logo || '/static/image/headurl.jpg'"
              mode="aspectFill"
            >
            </image>
            <view class="meta-top">
              <text class="merchant-name">{{
                userInfo.company_name || "商户名称"
              }}</text>
              <view class="gender-wrap">
                <text>{{ industryOptions[userInfo.industry_info] }}</text>
              </view>
            </view>
          </view>
          <view class="stats-row">
            <view class="stats-row-inner">
              <text class="desc">{{ userInfo.company_desc }}</text>
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
                <view class="btn-text">客服</view>
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
          <view class="filter-label">分类</view>
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
                <text class="chip-text">全部</text>
              </view>
              <view
                class="chip"
                :class="{ active: curCategoryId === c.id }"
                v-for="(c, idx) in categories"
                :key="idx"
                @click="selectCategory(c)"
              >
                <text class="chip-text">{{ formatCategoryName(c) }}</text>
              </view>
            </view>
          </scroll-view>
        </view>
        <!-- 内容区：图片网格 -->
        <view class="content" scroll-y="true">
          <ImageGrid
            :list="albumList"
            nameField="folder_name"
            imageField="new_thumb"
            countField="folder_count"
            :columns="columns"
            @click="handleImageClick"
          >
          </ImageGrid>

          <view class="loading-more" v-if="loadingMore">加载中...</view>
          <view class="no-more" v-if="!loadingMore && albumList.length === 0"
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
      @createProduct="showCreatePopup = true"
    />
    <view class="preview-wrap">
      <view class="preview-item" @click="openCustomer">
        <image
          class="preview-item-icon"
          src="/static/icon/user.png"
          mode="scaleToFill"
        />
        <view class="preview-item-text">简介</view>
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
      :visible="showCategory"
      @update:visible="(val) => (showCategory = val)"
      @select="onCategorySelected"
    />
    <SharePopup
      :visible="shareVisible"
      :title="userInfo.company_name || ''"
      :url="shareUrl"
      typeText="主页"
      type="home"
      :hid="homeId"
      :uid="uid || ''"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
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

import { getMiniCode } from "@/common/request/api.js";

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
      showCreatePopup: false,
      previewMode: false,
      showCategory: false,
      personalVisible: false,
      showSettingPopup: false, // 设置弹窗显示状态
      statusBarHeight: 0,
      navigationBarHeight: 88, // 显示区域高度（rpx等比转换后按样式处理）
      headerHeight: 520, // 头部占用高度（rpx）
      tabbarHeight: 140, // 自定义tabbar高度（rpx）
      safeAreaBottom: 0,
      page: 1,
      last_page: 1,
      total_num: 0,
      albumList: [],
      categories: [],
      loadingMore: false,
      showCreatePopup: false,
      userInfo: {},
      isFollow: false,
      shareVisible: false,
      shareUrl: "/pages/index/index",
      shareMiniPath: "",
      shareMiniQr: "",
      curCategoryId: "",
      uid: "",
      homeId: "",
      industryOptions: {
        1: "微供",
        2: "网供",
        3: "摄影",
      },
    };
  },
  onLoad(options) {
    const sys = this.$base.getSystemInfoCompat();
    // 在小程序中返回单位为 px，需要转换为 rpx 时通常用样式 rpx；这里只读取状态栏高度 px
    this.statusBarHeight = sys.statusBarHeight || 0;
    this.safeAreaBottom = 0;
    const userInfo = uni.getStorageSync("userInfo");
    this.homeId = userInfo.id;
    console.log(options);
    if (options.uid) {
      this.uid = options.uid;
      this.homeId = options.uid;
      this.previewMode = true;
    }
    this.getUserInfo();
    // 监听分类更新事件
    uni.$on("refreshIndexData", this.handleRefreshData);
  },
  async onShareAppMessage() {
    const enterpriseInfo = this.uid
      ? this.userInfo
      : uni.getStorageSync("enterpriseInfo");
    const userInfo = uni.getStorageSync("userInfo") || {};
    // 使用接口获取分享配置
    const shareConfig = await this.$getShareConfig({
      type: "link", // 分享类型
      userId: this.uid ? this.uid : userInfo.id,
      title: `分享${enterpriseInfo.company_name || ""}的主页`, // 默认标题
      path: "/pages/index/index", // 默认路径
    });
    if (shareConfig) {
      console.log(shareConfig);
      if (shareConfig.code === 0) {
        return {
          title: `分享${enterpriseInfo.company_name || ""}的主页`, // 默认标题
          path: shareConfig.share_link, // 默认路径
          imageUrl: enterpriseInfo.home_share_image,
          content: enterpriseInfo.home_share_title,
          desc: enterpriseInfo.home_share_desc,
        };
      } else {
        uni.showToast({
          title: "主页未公开",
          icon: "error",
          mask: true,
        });
      }
    }
  },
  methods: {
    toggleDisplayMode(data) {
      this.columns = this.normalizeDirectColumns(data);
    },
    // 处理刷新数据事件
    handleRefreshData() {
      this.curCategoryId = "";
      this.getUserInfo();
    },
    openCategory() {
      this.showCategory = true;
    },
    onCategorySelected(c) {
      uni.navigateTo({
        url: `/pagesOther/classDetail/classDetail?id=${c.id}&uid=${this.uid}`,
        success: (res) => {
          // 通过 eventChannel 向被打开页面传送数据
          res.eventChannel.emit("acceptDataFromOpenerPage", { data: c });
        },
      });
    },
    openShare() {
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
      this.shareVisible = true;
    },
    handleShareAction(event) {
      // event.type: 'share'|'copy'|'preview-mini'|'poster'|'open-settings'|'open-help'
      // event.payload 包含对应数据
      console.log("share action:", event);
      // 按需处理（多数情况父组件不必处理）
    },
    handleImageClick(item, index) {
      console.log(this.previewMode);
      if (this.previewMode) {
        uni.navigateTo({
          url:
            "/pagesOther/productDetail/productDetail?id=" +
            item.id +
            "&uid=" +
            this.uid,
        });
      } else {
        uni.navigateTo({
          url:
            "/pagesOther/productDetailsSelf/productDetailsSelf?id=" + item.id,
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
      console.log(this.curCategoryId);
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
    createAlbum() {
      this.showCreatePopup = false;
      uni.navigateTo({ url: "/pagesOther/addSetInfo/addSetInfo?type=album" });
    },
    createFolder() {
      this.showCreatePopup = false;
      uni.navigateTo({ url: "/pagesOther/addSetInfo/addSetInfo?type=folder" });
    },
    getUserInfo() {
      const user = uni.getStorageSync("userInfo") || {};
      const targetUid = this.uid ? Number(this.uid) : Number(user.id || 0);
      const uid = targetUid || user.id;
      const data = {
        target_user_id: uid,
      };
      this.$go("user/home/info", data, "get", {
        show_err: true,
      })
        .then((res) => {
          if (res.code === 0) {
            // 合并默认数据和实际数据，确保所有字段都有值
            const userInfo = {
              ...res.data,
            };
            this.userInfo = userInfo;
            this.total_num = userInfo.products.length;
            this.isFollow = userInfo.is_collect;
            if (!this.uid) {
              uni.setStorageSync("enterpriseInfo", userInfo);
            }
            this.getAlbumList();
            this.getAllClassList();
          } else {
            uni.redirectTo({ url: "/pages/selection/selection" });
          }
        })
        .catch((err) => {
          console.error("获取商户信息失败:", err);
        });
    },
    // 获取产品列表
    getAlbumList() {
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
          if (res.code === 0 && res.data) {
            const dataList = this.uid ? res.data : res.data.lists.data;
            this.albumList = dataList.map((item) => {
              const count = this.uid
                ? item.son_count
                : item.detail_pic_ids_arr.length + item.pic_ids_arr.length;
              return {
                ...item,
                folder_count: count,
              };
            });
            console.log(this.albumList);
          }
        })
        .catch((err) => {
          console.error("获取商户信息失败:", err);
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
            this.categories = this.uid ? res.data : res.data.lists.data;
          }
        })
        .catch((err) => {
          console.error("获取商户信息失败:", err);
        });
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
  height: 88px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
  color: #333;
  position: relative;
}

.nav-left-item {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 96rpx;
  padding: 16rpx 26rpx;
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
}

.avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 60rpx;
  object-fit: cover;
}

.meta-top {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.merchant-name {
  font-weight: 500;
  font-size: 40rpx;
  color: #ffffff;
  text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.2);
}

.gender-wrap {
  max-width: 76rpx;
  padding: 16rpx 8rpx;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24rpx;
  color: #fff;
}

.gender {
  width: 28rpx;
  height: 28rpx;
}

.desc {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-top: 10rpx;
  display: block;
}

/* 统计与按钮 */
.stats-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 40rpx;

  .stats-row-inner {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
  }
}

.stat {
  display: flex;
  align-items: center;
  gap: 10rpx;
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

  .actions-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4rpx;

    .stat-icon {
      width: 32rpx;
      height: 32rpx;
    }

    .ben-text {
      font-weight: 400;
      font-size: 28rpx;
      color: #ffffff;
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
}

.btn-follow {
  background: #ffd700;
  color: #222;
  border: none;
  padding: 12rpx 28rpx;
  border-radius: 28rpx;
  font-size: 24rpx;
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
  gap: 16rpx;
  padding: 26rpx 0 4rpx;
}

.filter-label {
  flex: 0 0 auto;
  height: 56rpx;
  line-height: 56rpx;
  font-size: 24rpx;
  font-weight: 600;
  color: #8c7a35;
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
  height: 56rpx;
  max-width: 168rpx;
  padding: 0 24rpx;
  background: rgba(255, 255, 255, 0.86);
  border: 1rpx solid rgba(217, 217, 217, 0.88);
  border-radius: 999rpx;
  box-sizing: border-box;
  white-space: nowrap;

  &.active {
    background: #fff4bf;
    border-color: #ffd000;
    box-shadow: 0 6rpx 14rpx rgba(255, 208, 0, 0.16);

    .chip-text {
      color: #5f4a00;
      font-weight: 700;
    }
  }

  .chip-text {
    display: block;
    max-width: 120rpx;
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
