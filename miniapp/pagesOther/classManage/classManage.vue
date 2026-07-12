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
          <view
            class="category-card"
            v-for="(c, idx) in categories"
            :key="c.id"
            @tap="openChildren(c)"
          >
            <view
              class="cover"
              :class="{ 'cover-placeholder': !hasCategoryCover(c) }"
            >
              <image
                :src="getCategoryCover(c)"
                :mode="hasCategoryCover(c) ? 'aspectFill' : 'scaleToFill'"
                class="cover-img"
              />
            </view>

            <view class="card-main">
              <view class="title-row">
                <text class="category-name">{{
                  c.folder_name || "未命名分类"
                }}</text>
                <text class="count-pill">{{ getChildCount(c) }}个子分类</text>
              </view>

              <text
                class="category-desc"
                :class="{ muted: !getCategoryDesc(c) }"
                >{{ getCategoryDesc(c) || "未填写分类描述" }}</text
              >

              <view class="meta-row">
                <text
                  class="meta-pill"
                  :class="{
                    private: getPrivateType(c) === 2,
                    share: getPrivateType(c) === 4,
                  }"
                  >{{ getPrivateLabel(c) }}</text
                >
                <text class="meta-pill">{{ getLayoutLabel(c) }}</text>
              </view>
            </view>

            <view class="more-btn" @tap.stop="openEdit(c)">
              <view class="more-dot"></view>
              <view class="more-dot"></view>
              <view class="more-dot"></view>
            </view>
          </view>

          <view v-if="categories.length === 0" class="empty">
            <image src="/static/image/empty-folder.png" class="empty-img" />
            <text class="empty-text">暂无分类</text>
          </view>
        </block>
      </scroll-view>

      <!-- 底部固定栏 -->
      <view class="bottom-bar">
        <view class="left-btn" @tap="createCategory">
          <view class="share-inner">
            <image
              class="icon-box"
              src="/static/icon/24＊24@2x (1).png"
              mode="scaleToFill"
            />
            <text class="share-text">新建分类</text>
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
  </view>
</template>

<script>
import ClassSettingPopup from "./components/ClassSettingPopup.vue"; // 调整路径为你项目实际位置
import ClassPopup from "./components/ClassPopup.vue"; // 调整路径为你项目实际位置
import {
  consumeRefreshMarker,
  markRefreshMarkerConsumed,
} from "@/common/helper/refresh.js";

export default {
  components: { ClassSettingPopup, ClassPopup },
  data() {
    return {
      keyword: "",
      categories: [],
      loading: false,
      defaultIcon: "/static/icon/default_cat.png",
      settingVisible: false,
      classVisible: false,
      editingCategory: null,
      parentId: 0,
      parentInfo: null,
      lastCategoryRefreshAt: "",
    };
  },
  onLoad(options = {}) {
    this.parentId = Number(options.fid || 0);
    if (this.parentId) {
      this.parentInfo = {
        id: this.parentId,
        folder_name: options.name ? decodeURIComponent(options.name) : "",
      };
    }
    this.fetchCategories();
    // 监听分类更新事件
    uni.$on("refreshClassManageData", this.handleRefreshData);
  },
  onShow() {
    this.consumeCategoryRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshClassManageData", this.handleRefreshData);
  },
  methods: {
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
          // 适配返回结构
          this.categories = res && res.data ? res.data.lists.data : [];
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
    getChildCount(category) {
      const count = category
        ? category.child_count ||
          category.children_count ||
          category.count ||
          category.son_count
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
    openChildren(category) {
      if (this.getChildCount(category) > 0) {
        uni.navigateTo({
          url:
            `/pagesOther/classManage/classManage?fid=${category.id}` +
            `&name=${encodeURIComponent(category.folder_name || "")}`,
        });
        return;
      }
      uni.navigateTo({
        url:
          `/pagesOther/classDetail/classDetail?id=${category.id}` +
          `&name=${encodeURIComponent(category.folder_name || "")}`,
      });
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

.category-card {
  background: #ffffff;
  border-radius: 16rpx;
  border: 1rpx solid rgba(33, 37, 44, 0.06);
  padding: 22rpx;
  margin-bottom: 18rpx;
  display: flex;
  align-items: flex-start;
  box-shadow: 0 10rpx 28rpx rgba(25, 31, 39, 0.05);
  box-sizing: border-box;
}

.cover {
  width: 96rpx;
  height: 96rpx;
  flex: 0 0 96rpx;
  border-radius: 14rpx;
  overflow: hidden;
  background: #eef2f6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;
}

.cover-placeholder {
  background: #f2f5f8;
}

.cover-img {
  width: 100%;
  height: 100%;
}

.cover-placeholder .cover-img {
  width: 52rpx;
  height: 52rpx;
}

.card-main {
  flex: 1;
  min-width: 0;
}

.title-row {
  min-height: 42rpx;
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.category-name {
  flex: 1;
  min-width: 0;
  font-weight: 600;
  font-size: 32rpx;
  color: #222222;
  line-height: 42rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.count-pill {
  flex: 0 0 auto;
  height: 38rpx;
  line-height: 38rpx;
  padding: 0 14rpx;
  border-radius: 20rpx;
  background: #fff7cf;
  color: #6e5a00;
  font-size: 22rpx;
}

.category-desc {
  display: block;
  margin-top: 10rpx;
  font-size: 26rpx;
  line-height: 36rpx;
  color: #777f8c;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-desc.muted {
  color: #b4bac3;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 10rpx;
  margin-top: 14rpx;
  flex-wrap: wrap;
}

.meta-pill {
  height: 34rpx;
  line-height: 34rpx;
  padding: 0 12rpx;
  border-radius: 8rpx;
  background: #f3f5f7;
  color: #687180;
  font-size: 22rpx;
}

.meta-pill.private {
  background: #fff0ef;
  color: #d2453f;
}

.meta-pill.share {
  background: #edf4ff;
  color: #2e6fbd;
}

.more-btn {
  width: 52rpx;
  height: 52rpx;
  flex: 0 0 52rpx;
  margin-left: 8rpx;
  border-radius: 50%;
  background: #f5f6f8;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5rpx;
}

.more-dot {
  width: 6rpx;
  height: 6rpx;
  border-radius: 50%;
  background: #555d68;
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
  min-height: calc(env(safe-area-inset-bottom) + 124rpx);
  display: flex;
  align-items: center;
  gap: 22rpx;
  padding: 14rpx 32rpx calc(env(safe-area-inset-bottom) + 14rpx);
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

/* 黄色胶囊（包含图标框和文字） */
.share-inner {
  width: 100%;
  background: #ffd800;
  height: 96rpx;
  border-radius: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

/* 左侧小图标的虚线方块 */
.icon-box {
  width: 48rpx;
  height: 48rpx;
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
  width: 156rpx;
  height: 96rpx;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  border: 1rpx solid #e8ebef;
  border-radius: 96rpx;
  box-sizing: border-box;
}

/* 齿轮图标 */
.settings-icon {
  font-size: 28rpx;
  color: #333;
}
</style>
