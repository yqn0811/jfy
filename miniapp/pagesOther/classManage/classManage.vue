<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 搜索 -->
      <view class="search-wrap">
        <image
          class="search-icon"
          @tap="onSearch"
          src="/static/icon/搜索@2x(1).png"
          mode="scaleToFill"
        />
        <input
          class="search-input"
          placeholder-class="jf-input-placeholder"
          :placeholder="placeholderFor('classSearch', '搜索分类')"
          v-model="keyword"
          confirm-type="search"
          @tap="focusField('classSearch')"
          @focus="focusField('classSearch')"
          @blur="blurField('classSearch')"
          @confirm="onSearch"
        />
      </view>

      <!-- 列表 -->
      <scroll-view class="list" scroll-y>
        <block v-if="loading">
          <view class="loading">加载中...</view>
        </block>

        <block v-else>
          <view v-if="categories.length" class="section-block">
            <view class="section-header">
              <view class="section-title-wrap">
                <text class="section-emoji">📁</text>
                <text class="section-title">子分类管理</text>
                <text class="section-count">{{ categories.length }}</text>
              </view>
              <view class="section-toggle" @tap="toggleSubcategory">
                <text>{{ subcategoryCollapsed ? "展开" : "收起" }}</text>
                <text
                  class="toggle-arrow"
                  :class="{ collapsed: subcategoryCollapsed }"
                  >⌃</text
                >
              </view>
            </view>

            <view
              v-if="!subcategoryCollapsed"
              class="manage-category-grid"
            >
              <view
                class="manage-folder-cell"
                v-for="(category, index) in categories"
                :key="getCategoryKey(category, index)"
              >
                <view
                  class="manage-folder-tile"
                  @tap="openChildrenByIndex(index)"
                >
                  <view class="manage-folder-more" @tap.stop="openEditByIndex(index)">
                    <view class="manage-more-dot"></view>
                    <view class="manage-more-dot"></view>
                    <view class="manage-more-dot"></view>
                  </view>
                  <view class="manage-folder-icon">
                    <view class="manage-folder-tab"></view>
                    <view class="manage-folder-body"></view>
                  </view>
                  <text class="manage-folder-title">{{
                    getDisplayCategoryName(category)
                  }}</text>
                  <text class="manage-folder-meta"
                    >{{ getChildCount(category) }}类/{{
                      getProductCount(category)
                    }}品</text
                  >
                </view>
              </view>
            </view>
          </view>

          <view
            v-if="showProductSection"
            class="section-block product-section"
          >
            <view class="section-header">
              <view class="section-title-wrap">
                <text class="section-emoji">🧾</text>
                <text class="section-title">产品列表</text>
                <text class="section-count">{{ productContent.length }}</text>
              </view>
            </view>

            <ImageGrid
              :list="productContent"
              :columns="2"
              card-variant="class-detail"
              @click="handleGridItemClick"
            />

            <view v-if="productContent.length === 0" class="product-empty">
              <text>暂无产品</text>
            </view>
          </view>

          <view v-if="mixedContent.length === 0" class="empty">
            <image src="/static/image/empty-folder.png" class="empty-img" />
            <text class="empty-text">暂无分类或产品</text>
          </view>
        </block>
      </scroll-view>

      <!-- 底部固定栏 -->
      <view class="bottom-bar">
        <view
          v-if="canShareCurrentCategory"
          class="share-category-btn"
          @tap="openShareCategory"
        >
          <image
            class="icon-box"
            src="/static/icon/分享@2x.png"
            mode="scaleToFill"
          />
          <text class="share-text">分享</text>
        </view>

        <view class="left-btn" @tap="createCategory">
          <view class="share-inner">
            <image
              class="icon-box"
              src="/static/icon/24＊24@2x (1).png"
              mode="scaleToFill"
            />
            <text class="share-text">新建</text>
          </view>
        </view>

        <view class="settings-btn" @tap="openGlobalClass">
          <image
            class="icon-box"
            src="/static/icon/Frame.png"
            mode="scaleToFill"
          />
          <text class="share-text">设置</text>
        </view>
      </view>
    </view>

    <!-- 编辑弹窗 -->
    <class-setting-popup
      :visible="settingVisible"
      :category="editingCategory"
      @update:visible="(val) => (settingVisible = val)"
      @add-child-category="onAddChildCategory"
      @edit-info="onEditInfo"
      @order="onOrder"
      @toggle-single="onToggleSingle"
      @toggle-share="onToggleShare"
      @set-private="onSetPrivate"
      @delete-category="onDeleteCategory"
    />
    <class-popup
      :visible="classVisible"
      @update:visible="(val) => (classVisible = val)"
      @class-sort="onClassSort"
    />
    <share-popup
      :visible="shareVisible"
      :title="shareTitle"
      :uid="getShareOwnerId() || ''"
      typeText="分类"
      type="category"
      :hid="parentId"
      :url="shareUrl"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
      :cover-url="shareCoverUrl"
      @update:visible="(val) => (shareVisible = val)"
      @action="onShareAction"
    />
  </view>
</template>

<script>
import ClassSettingPopup from "./components/ClassSettingPopup.vue"; // 调整路径为你项目实际位置
import ClassPopup from "./components/ClassPopup.vue"; // 调整路径为你项目实际位置
import ImageGrid from "@/components/ImageGrid";
import SharePopup from "@/components/SharePopup";
import {
  consumeRefreshMarker,
  markRefreshMarkerConsumed,
} from "@/common/helper/refresh.js";
import {
  getObjectId,
  resolveClickedListItem,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";
import { imageUrlFor } from "@/common/helper/imageUrls.js";

export default {
  components: { ClassSettingPopup, ClassPopup, ImageGrid, SharePopup },
  data() {
    return {
      keyword: "",
      categories: [],
      products: [],
      loading: false,
      defaultIcon: "/static/icon/default_cat.png",
      settingVisible: false,
      classVisible: false,
      editingCategory: null,
      parentId: 0,
      parentInfo: null,
      lastCategoryRefreshAt: "",
      subcategoryCollapsed: false,
      shareVisible: false,
      shareTitle: "",
      shareUrl: "",
      shareMiniQr: "",
      shareMiniPath: "",
      shareOwnerId: "",
      shareOwnerInfo: {},
      defaultShareCover: "/static/icon/share-category-empty.png",
    };
  },
  computed: {
    pageTitle() {
      const name =
        this.parentInfo && this.parentInfo.folder_name
          ? String(this.parentInfo.folder_name).trim()
          : "";
      return name ? `${name}分类管理` : "分类管理";
    },
    mixedContent() {
      return [
        ...this.categories.map((item) => ({
          ...item,
          item_type: "category",
          nameField: this.getDisplayCategoryName(item),
          imageField: this.getCategoryCover(item),
          countField: this.getChildCount(item),
          badgeSuffix: "个",
        })),
        ...this.products.map((item) => ({
          ...item,
          item_type: "product",
          nameField: item.folder_name || "未命名产品",
          imageField: this.getProductCover(item),
          countField: this.getProductImageCount(item),
          badgeSuffix: "张",
        })),
      ];
    },
    productContent() {
      return this.products.map((item) => ({
        ...item,
        item_type: "product",
        nameField: item.folder_name || "未命名产品",
        imageField: this.getProductCover(item),
        countField: this.getProductImageCount(item),
        badgeSuffix: "张",
      }));
    },
    showProductSection() {
      return !!this.parentId || this.productContent.length > 0;
    },
    canShareCurrentCategory() {
      return !!this.parentId;
    },
    shareCoverUrl() {
      const product = this.products.find((item) => {
        const cover = this.normalizeShareText(
          this.getProductShareCover(item)
        );
        return cover && cover.indexOf("/static/") !== 0;
      });
      return (
        this.normalizeShareText(product && this.getProductShareCover(product)) ||
        this.defaultShareCover
      );
    },
  },
  onLoad(options = {}) {
    this.parentId = Number(options.fid || 0);
    if (this.parentId) {
      this.parentInfo = {
        id: this.parentId,
        folder_name: options.name ? decodeURIComponent(options.name) : "",
      };
    }
    this.updateNavigationTitle();
    this.fetchCategories();
    this.initShareOwnerInfo();
    // 监听分类更新事件
    uni.$on("refreshClassManageData", this.handleRefreshData);
  },
  onShow() {
    this.consumeCategoryRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshClassManageData", this.handleRefreshData);
  },
  onShareAppMessage() {
    this.updateShareMeta();
    return {
      title: this.shareTitle || this.buildCategoryShareTitle(),
      path: this.shareUrl || this.buildCurrentCategoryShareUrl(),
      imageUrl: this.shareCoverUrl,
    };
  },
  methods: {
    updateNavigationTitle() {
      uni.setNavigationBarTitle({
        title: this.pageTitle,
      });
    },
    toggleSubcategory() {
      this.subcategoryCollapsed = !this.subcategoryCollapsed;
    },
    getCategoryKey(category, index) {
      return category && category.id ? category.id : `category-${index}`;
    },
    handleRefreshData(marker) {
      this.markCategoryRefreshConsumed(marker);
      this.fetchCategories();
    },
    consumeCategoryRefreshMarker() {
      const marker = consumeRefreshMarker(
        "category",
        "categoryListNeedsRefreshManageConsumed",
        this.lastCategoryRefreshAt,
      );
      if (!marker) return;
      this.markCategoryRefreshConsumed(marker);
      this.handleRefreshData();
    },
    markCategoryRefreshConsumed(marker) {
      if (!marker) return;
      this.lastCategoryRefreshAt = marker;
      markRefreshMarkerConsumed("categoryListNeedsRefreshManageConsumed", marker);
    },
    async fetchCategories() {
      this.loading = true;
      try {
        if (this.$go) {
          const params = {
            folder_type: 1,
            key: this.keyword,
            timestamp: Date.now(),
          };
          if (this.parentId) {
            params.fid = this.parentId;
          }
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          const res = await this.$go("album/lists/folder", signed, "post", {
            show_err: true,
          });
          this.categories = this.extractFolderList(res);
          await this.fetchProducts();
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "加载失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },
    onSearch() {
      this.fetchCategories();
    },
    async fetchProducts() {
      try {
        if (!this.parentId) {
          this.products = [];
          return;
        }
        if (!this.$go) {
          this.products = [];
          return;
        }
        const params = {
          folder_type: 2,
          key: this.keyword,
          timestamp: Date.now(),
        };
        if (this.parentId) {
          params.fid = this.parentId;
        }
        const signed = this.$base
          ? { ...params, sign: this.$base.getASCII(params) }
          : params;
        const res = await this.$go("album/lists/folder", signed, "post", {
          show_err: true,
        });
        this.products = this.extractFolderList(res);
      } catch (e) {
        console.error(e);
        this.products = [];
      }
    },
    extractFolderList(res) {
      const data = res && res.data ? res.data : {};
      if (Array.isArray(data)) return data;
      if (data.lists) {
        if (Array.isArray(data.lists)) return data.lists;
        if (Array.isArray(data.lists.data)) return data.lists.data;
      }
      return [];
    },
    getChildCount(category) {
      const count = category
        ? category.child_count ||
          category.children_count ||
          category.count ||
          category.son_count
        : 0;
      return Number(count) || 0;
    },
    getProductCount(category) {
      const count = category
        ? category.product_count ||
          category.productCount ||
          category.album_count ||
          category.pic_count
        : 0;
      return Number(count) || 0;
    },
    getCategoryDesc(category) {
      const desc = category && category.folder_desc;
      if (desc === null || desc === undefined) return "";
      const value = String(desc).trim();
      if (!value || value === "null" || value === "undefined") return "";
      return value;
    },
    hasCategoryCover(category) {
      return !!(category && category.new_thumb);
    },
    getCategoryCover(category) {
      return (
        (category && (category.new_thumb || category.icon)) ||
        "/static/icon/folder-open@2x.png"
      );
    },
    getDisplayCategoryName(category) {
      const name = category && category.folder_name;
      const text = name === null || name === undefined ? "" : String(name).trim();
      return text && text !== "null" && text !== "undefined" ? text : "未命名子分类";
    },
    normalizeShareText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      return text && text !== "null" && text !== "undefined" ? text : "";
    },
    getCurrentCategoryName() {
      return (
        this.normalizeShareText(this.parentInfo && this.parentInfo.folder_name) ||
        "分类"
      );
    },
    getShareOwnerId() {
      return (
        this.shareOwnerId ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "")
      );
    },
    initShareOwnerInfo() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.shareOwnerInfo = enterpriseInfo.company_name ? enterpriseInfo : userInfo;
      const owner = this.shareOwnerInfo || {};
      const ownerId =
        this.normalizeShareText(owner.id) ||
        this.normalizeShareText(owner.uid) ||
        this.normalizeShareText(owner.user_id);
      if (ownerId && !this.shareOwnerId) {
        this.shareOwnerId = ownerId;
      }
    },
    buildCategoryShareTitle() {
      const targetName = this.getCurrentCategoryName();
      return this.$buildTypedShareTitle
        ? this.$buildTypedShareTitle({
            typeText: "分类",
            targetName,
            prefix: "分享",
          })
        : `分享${targetName}`;
    },
    buildCurrentCategoryShareUrl() {
      if (!this.parentId) {
        return "/pagesOther/classDetail/classDetail";
      }
      const ownerId = this.getShareOwnerId();
      const path = this.$buildPublicSharePath
        ? this.$buildPublicSharePath("category", this.parentId, ownerId)
        : `/pagesOther/classDetail/classDetail?id=${this.parentId}${
            ownerId ? `&uid=${ownerId}` : ""
          }`;
      return `${path}${path.indexOf("?") === -1 ? "?" : "&"}source=share`;
    },
    updateShareMeta() {
      this.shareTitle = this.buildCategoryShareTitle();
      this.shareUrl = this.buildCurrentCategoryShareUrl();
      this.shareMiniPath = this.shareUrl;
    },
    async loadCurrentUserShareOwner() {
      if (this.getShareOwnerId()) return;
      if (!this.$go) return;
      try {
        const res = await this.$go("user/home/info", {}, "get", {
          show_err: false,
          loading: false,
        });
        const info = res && res.data ? res.data : {};
        this.shareOwnerInfo = info || {};
        const ownerId =
          this.normalizeShareText(info.id) ||
          this.normalizeShareText(info.uid) ||
          this.normalizeShareText(info.user_id);
        if (ownerId) {
          this.shareOwnerId = ownerId;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async openShareCategory() {
      if (!this.canShareCurrentCategory) {
        uni.showToast({ title: "当前页面不能分享分类", icon: "none" });
        return;
      }
      if (this.$checkLoginStatus && !this.$checkLoginStatus()) {
        uni.showModal({
          title: "未登录，是否立即登录？",
          content: "",
          showCancel: true,
          success: ({ confirm }) => {
            if (confirm && this.$silentLogin) {
              this.$silentLogin();
            }
          },
        });
        return;
      }
      await this.loadCurrentUserShareOwner();
      if (!this.getShareOwnerId()) {
        uni.showToast({ title: "分享信息缺失，请稍后重试", icon: "none" });
        return;
      }
      this.updateShareMeta();
      this.shareVisible = true;
    },
    normalizeArray(value) {
      if (Array.isArray(value)) return value;
      if (typeof value === "string" && value) {
        return value.split(",").filter(Boolean);
      }
      return [];
    },
    getProductImageCount(item) {
      const colorPics = this.normalizeArray(item && (item.pic_ids_arr || item.pic_ids));
      const detailPics = this.normalizeArray(
        item && (item.detail_pic_ids_arr || item.detail_pic_ids),
      );
      return (
        Number(item && (item.son_count || item.pic_count || item.picture_count || 0)) ||
        colorPics.length + detailPics.length
      );
    },
    getProductCover(item) {
      return (
        (item && (item.new_thumb || item.imageField || item.imgurl || item.picture_url)) ||
        "/static/image/pic.png"
      );
    },
    getProductShareCover(item) {
      if (!item) return "";
      return (
        imageUrlFor(item, "origin") ||
        imageUrlFor(item, "preview") ||
        imageUrlFor(item, "thumb") ||
        item.share_image ||
        item.new_thumb ||
        item.imageField ||
        item.picture_url ||
        item.imgurl ||
        ""
      );
    },
    getPrivateType(category) {
      return Number((category && category.private_type) || 1);
    },
    getPrivateLabel(category) {
      const type = this.getPrivateType(category);
      if (type === 2) return "私密";
      if (type === 4) return "仅分享可见";
      return "公开";
    },
    getLayoutLabel(category) {
      return Number(category && category.layout_type) === 2 ? "单列" : "双列";
    },
    createCategory() {
      const query = this.parentId ? `?parent_id=${this.parentId}` : "";
      uni.navigateTo({ url: `/pagesOther/addClass/addClass${query}` });
    },
    openGlobalSettings() {
      // 如果需要打开全局设置弹窗，可复用 ClassSettingPopup 但无需 category
      this.editingCategory = null;
      this.settingVisible = true;
    },
    openGlobalClass() {
      console.log(123);
      this.classVisible = true;
    },
    openEdit(category) {
      // 打开针对某个分类的编辑弹窗
      this.editingCategory = category;
      this.settingVisible = true;
    },
    openEditByIndex(index) {
      const category = this.categories[Number(index)];
      if (!category) {
        showInvalidRecordToast("分类数据异常，请刷新后重试");
        return;
      }
      this.openEdit(category);
    },
    openChildren(category) {
      if (!category || !category.id) {
        showInvalidRecordToast("分类数据异常，请刷新后重试");
        return;
      }
      uni.navigateTo({
        url:
          `/pagesOther/classManage/classManage?fid=${category.id}` +
          `&name=${encodeURIComponent(category.folder_name || "")}`,
      });
    },
    openChildrenByIndex(index) {
      const category = this.categories[Number(index)];
      this.openChildren(category);
    },
    openProduct(product) {
      const productId = getObjectId(product, ["id", "product_id", "folder_id", "fid"]);
      if (!productId) {
        showInvalidRecordToast("产品数据异常，请刷新后重试");
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/productDetailsSelf/productDetailsSelf?id=${productId}&fromPage=manage`,
      });
    },
    handleGridItemClick(data, index, event) {
      const current = resolveClickedListItem(data, index, event, this.productContent);
      if (!current) {
        showInvalidRecordToast();
        return;
      }
      if (current.item_type === "product") {
        this.openProduct(current);
        return;
      }
      this.openChildren(current);
    },
    // 弹窗事件处理：下面是示例实现，按需替换为后端接口
    onAddChildCategory(category) {
      uni.navigateTo({
        url: `/pagesOther/addClass/addClass?parent_id=${category.id}`,
      });
    },
    onEditInfo(category) {
      uni.navigateTo({
        url: `/pagesOther/addClass/addClass?fid=${category.id}`,
      });
    },
    onOrder(category) {
      uni.navigateTo({
        url: `/pagesOther/classSort/classSort?fid=${category.id}`,
      });
    },
    async onToggleSingle(payload) {
      // payload = { categoryId, single }
      try {
        if (this.$go) {
          const nextLayout = payload.single ? 2 : 1;
          const params = {
            fid: payload.categoryId,
            layout_type: nextLayout,
            timestamp: Date.now(),
          };
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          await this.$go("album/edit/folder", signed, "post", {
            show_err: true,
          });
          uni.showToast({ title: "已保存", icon: "none" });
          this.fetchCategories();
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "保存失败", icon: "none" });
      }
    },
    async saveCategoryPrivateType(categoryId, privateType) {
      if (!categoryId) {
        uni.showToast({ title: "分类信息缺失", icon: "none" });
        return false;
      }
      try {
        if (this.$go) {
          const params = {
            fid: categoryId,
            private_type: privateType,
            timestamp: Date.now(),
          };
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          await this.$go("album/edit/folder", signed, "post", {
            show_err: true,
          });
          uni.showToast({ title: "已保存", icon: "none" });
          this.fetchCategories();
          return true;
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "保存失败", icon: "none" });
      }
      return false;
    },
    async onToggleShare(payload = {}) {
      const privateType = payload.share ? 4 : 1;
      return this.saveCategoryPrivateType(payload.categoryId, privateType);
    },
    async onSetPrivate(payload = {}) {
      const privateType = payload.private ? 2 : 1;
      return this.saveCategoryPrivateType(payload.categoryId, privateType);
    },
    onDeleteCategory() {
      this.fetchCategories();
    },
    onClassSort() {
      const query = this.parentId ? `?fid=${this.parentId}` : "";
      uni.navigateTo({ url: `/pagesOther/classSort/classSort${query}` });
    },
    onShareAction(event) {
      console.log("share action", event);
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  height: 100vh;
  padding: 18rpx 20rpx 0;
  background: #f6f7f9;
  box-sizing: border-box;
  overflow: hidden;

  .page-scoll {
    height: 100%;
    display: flex;
    flex-direction: column;
    padding-bottom: calc(env(safe-area-inset-bottom) + 144rpx);
    box-sizing: border-box;
  }
}

.search-wrap {
  display: flex;
  align-items: center;
  gap: 14rpx;
  height: 88rpx;
  background: #ffffff;
  border-radius: 80rpx;
  padding: 0 28rpx;
  margin: 4rpx 0 18rpx;
  box-sizing: border-box;
  box-shadow: 0 8rpx 24rpx rgba(28, 35, 45, 0.04);
}

.search-input {
  flex: 1;
  height: 88rpx;
  border: none;
  background: transparent;
  font-weight: 400;
  font-size: 28rpx;
  color: #222222;
  padding: 0;
}

.search-icon {
  width: 38rpx;
  height: 38rpx;
}

.list {
  flex: 1;
  min-height: 0;
  margin-top: 8rpx;
  padding-bottom: 16rpx;
  box-sizing: border-box;
}

.section-block {
  margin-bottom: 24rpx;
}

.product-section {
  margin-top: 2rpx;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 58rpx;
  margin-bottom: 14rpx;
}

.section-title-wrap {
  display: flex;
  align-items: center;
  min-width: 0;
}

.section-emoji {
  width: 34rpx;
  height: 34rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 10rpx;
  font-size: 28rpx;
  line-height: 34rpx;
}

.section-title {
  font-size: 28rpx;
  font-weight: 700;
  line-height: 38rpx;
  color: #252b33;
}

.section-count {
  min-width: 34rpx;
  height: 30rpx;
  margin-left: 10rpx;
  padding: 0 10rpx;
  border-radius: 30rpx;
  background: #eef1f5;
  color: #8a929d;
  font-size: 20rpx;
  font-weight: 600;
  line-height: 30rpx;
  text-align: center;
  box-sizing: border-box;
}

.section-toggle {
  height: 48rpx;
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 0 14rpx;
  border-radius: 48rpx;
  background: #ffffff;
  border: 1rpx solid #e8ebef;
  color: #727b86;
  font-size: 24rpx;
  line-height: 48rpx;
  box-sizing: border-box;
}

.toggle-arrow {
  display: inline-block;
  font-size: 22rpx;
  line-height: 1;
  transform: rotate(0deg);
  transition: transform 0.18s ease;
}

.toggle-arrow.collapsed {
  transform: rotate(180deg);
}

.manage-category-grid {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -8rpx;
}

.manage-folder-cell {
  width: 33.3333%;
  padding: 0 9rpx 18rpx;
  box-sizing: border-box;
}

.manage-folder-tile {
  position: relative;
  height: 164rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 16rpx 8rpx 12rpx;
  background: #ffffff;
  border: 1rpx solid rgba(226, 229, 234, 0.92);
  border-radius: 16rpx;
  box-shadow: 0 6rpx 16rpx rgba(28, 35, 45, 0.035);
  box-sizing: border-box;
}

.manage-folder-icon {
  position: relative;
  width: 66rpx;
  height: 52rpx;
  flex: 0 0 52rpx;
}

.manage-folder-tab {
  position: absolute;
  left: 0;
  top: 0;
  width: 34rpx;
  height: 15rpx;
  border-radius: 10rpx 10rpx 4rpx 4rpx;
  background: #ffd45a;
}

.manage-folder-body {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 40rpx;
  border-radius: 11rpx;
  background: linear-gradient(180deg, #ffc93d 0%, #f4a500 100%);
  box-shadow: 0 7rpx 14rpx rgba(236, 156, 0, 0.14);
}

.manage-folder-title {
  display: block;
  width: 100%;
  margin-top: 13rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: center;
  font-size: 25rpx;
  font-weight: 600;
  line-height: 30rpx;
  color: #252b33;
}

.manage-folder-meta {
  display: block;
  width: 100%;
  margin-top: 4rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: center;
  font-size: 20rpx;
  line-height: 24rpx;
  color: #8a929d;
}

.manage-folder-more {
  position: absolute;
  top: 8rpx;
  right: 8rpx;
  width: 34rpx;
  height: 34rpx;
  border-radius: 50%;
  background: rgba(246, 247, 249, 0.94);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3rpx;
}

.manage-more-dot {
  width: 4rpx;
  height: 4rpx;
  border-radius: 50%;
  background: #535b66;
}

.product-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 112rpx;
  margin-top: -6rpx;
  color: #9ca3ad;
  font-size: 24rpx;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 110rpx 0 40rpx;
}

.empty-img {
  width: 260rpx;
  height: 180rpx;
  margin-bottom: 12rpx;
}

.empty-text {
  color: #9ca3ad;
  font-size: 26rpx;
}

/* 底部栏容器（兼容安全区） */
.bottom-bar {
  position: fixed;
  background: #ffffff;
  left: 0;
  right: 0;
  bottom: 0;
  min-height: calc(env(safe-area-inset-bottom) + 108rpx);
  display: flex;
  align-items: center;
  gap: 18rpx;
  padding: 12rpx 28rpx calc(env(safe-area-inset-bottom) + 12rpx);
  box-sizing: border-box;
  z-index: 50;
  border-top: 1rpx solid #edf0f3;
  box-shadow: 0 -8rpx 24rpx rgba(20, 27, 36, 0.04);
}

/* 左侧大按钮容器（居中宽度受限） */
.left-btn {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.share-category-btn {
  flex: 1;
  min-width: 0;
  height: 84rpx;
  border-radius: 84rpx;
  background: #ffffff;
  border: 1rpx solid #e8ebef;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  box-sizing: border-box;
}

.share-category-btn .share-text {
  color: #333333;
}

/* 黄色胶囊（包含图标框和文字） */
.share-inner {
  width: 100%;
  background: #ffd800;
  height: 84rpx;
  border-radius: 84rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

/* 左侧小图标的虚线方块 */
.icon-box {
  width: 38rpx;
  height: 38rpx;
}

/* 图标与文字样式 */
.icon {
  font-size: 30rpx;
  color: #222;
}

.share-text {
  font-weight: bold;
  font-size: 30rpx;
  color: #333333;
}

/* 右侧齿轮按钮 */
.settings-btn {
  width: 146rpx;
  height: 84rpx;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  border: 1rpx solid #e8ebef;
  border-radius: 84rpx;
  box-sizing: border-box;
}

/* 齿轮图标 */
.settings-icon {
  font-size: 28rpx;
  color: #333;
}
</style>
