<template>
  <view class="page">
    <!-- 顶部筛选 + 搜索 -->
    <view class="top-bar">
      <view class="filter" :class="{ active: filterVisible }" @tap.stop="toggleFilter">
        <view class="filter-text">{{ selectedCategoryName }}</view>
        <image
          src="/static/icon/caret-down-small@2x.png"
          class="filter-icon"
          :class="{ rotate: filterVisible }"
          mode="widthFix"
        />
      </view>

      <view class="search-box">
        <image
          class="search-icon"
          @tap="onSearch"
          src="/static/icon/搜索@2x(1).png"
          mode="scaleToFill"
        />
        <input
          class="search-input"
          placeholder-class="jf-input-placeholder"
          :placeholder="placeholderFor('productSearch', '搜索产品')"
          v-model="keyword"
          confirm-type="search"
          @tap="focusField('productSearch')"
          @focus="focusField('productSearch')"
          @blur="blurField('productSearch')"
          @confirm="onSearch"
          placeholder-style="color:#999"
        />
      </view>
    </view>

    <view v-if="filterVisible" class="filter-mask" @tap="closeFilter"></view>
    <view v-if="filterVisible" class="filter-panel" @tap.stop>
      <scroll-view class="filter-scroll" scroll-y>
        <view
          class="filter-option"
          :class="{ selected: selectedCategoryId === '' }"
          @tap="selectCategoryFilter({ id: '', folder_name: '全部', level: 0 })"
        >
          <text class="filter-option-name">全部</text>
          <text v-if="selectedCategoryId === ''" class="filter-option-check">✓</text>
        </view>
        <view
          v-for="item in categoryOptions"
          :key="item.id"
          class="filter-option"
          :class="{ selected: String(selectedCategoryId) === String(item.id) }"
          :style="{ paddingLeft: 28 + item.level * 28 + 'rpx' }"
          @tap="selectCategoryFilter(item)"
        >
          <text class="filter-option-name">{{ item.folder_name || "未命名分类" }}</text>
          <text
            v-if="String(selectedCategoryId) === String(item.id)"
            class="filter-option-check"
            >✓</text
          >
        </view>
        <view v-if="categoryLoading" class="filter-empty">分类加载中...</view>
        <view v-else-if="categoryOptions.length === 0" class="filter-empty">
          暂无分类
        </view>
      </scroll-view>
    </view>

    <!-- 列表 -->
    <view class="list">
      <view v-if="loading" class="loading">加载中...</view>

      <view v-else-if="products.length === 0" class="empty">
        <image
          src="/static/image/empty-folder.png"
          class="empty-img"
          mode="widthFix"
        />
        <text class="empty-text">还没有产品，快去新建一个吧</text>
      </view>
      <view v-else class="cards">
        <ImageGrid
          :list="products"
          :columns="columns"
          @click="handleImageClick"
        >
        </ImageGrid>
      </view>
    </view>

    <!-- 底部操作栏 -->
    <view class="bottom-bar">
      <view class="left-btn" @tap="createProduct">
        <view class="share-inner">
          <image
            class="icon-box"
            src="/static/icon/24＊24@2x (1).png"
            mode="scaleToFill"
          />
          <text class="share-text">新建产品</text>
        </view>
      </view>

      <view class="settings-btn" @tap="onSettings">
        <image
          class="icon-box"
          src="/static/icon/Frame.png"
          mode="scaleToFill"
        />
        <text class="share-text">设置</text>
      </view>
    </view>
    <setting-popup
      :visible="settingVisible"
      :albumId="albumId"
      @update:visible="(val) => (settingVisible = val)"
      @create="createProduct"
      @order="openOrder"
      @change="onSettingChange"
    />
  </view>
</template>

<script>
import ImageGrid from "@/components/ImageGrid"; // ← 根据你项目实际路径调整
import SettingPopup from "./components/settingPopup.vue";
import { getObjectId, showInvalidRecordToast } from "@/common/helper/clickItem.js";

export default {
  components: { ImageGrid, SettingPopup },
  data() {
    return {
      settingVisible: false,
      albumId: null, // 如果页面有传入的相册/合集 id，可在 onLoad 填充
      products: [],
      keyword: "",
      loading: false,
      filterVisible: false,
      categoryLoading: false,
      selectedCategoryId: "",
      selectedCategoryName: "全部",
      categoryOptions: [],
      totalCount: 0,
      columns: 2,
      page: 1,
      pageSize: 20,
      lastProductRefreshAt: "",
      placeholder: "/static/image/pic.png",
    };
  },
  onLoad(options) {
    // ... 你已有的加载逻辑
    if (options && (options.fid || options.albumId)) {
      this.selectedCategoryId = String(options.fid || options.albumId);
      this.albumId = this.selectedCategoryId;
    }
    // 监听产品更新事件
    uni.$on("refreshProductManageData", this.handleRefreshData);
    this.fetchCategories();
    this.fetchList();
  },
  onShow() {
    this.consumeProductRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshProductManageData", this.handleRefreshData);
  },
  methods: {
    handleRefreshData(marker) {
      this.markProductRefreshConsumed(marker);
      this.fetchCategories();
      this.fetchList(true);
    },
    consumeProductRefreshMarker() {
      const marker = uni.getStorageSync("productListNeedsRefresh");
      const consumedMarker = uni.getStorageSync(
        "productListNeedsRefreshManageConsumed",
      );
      if (!marker || marker === this.lastProductRefreshAt || marker === consumedMarker) return;
      this.markProductRefreshConsumed(marker);
      this.handleRefreshData();
    },
    markProductRefreshConsumed(marker) {
      if (!marker) return;
      this.lastProductRefreshAt = marker;
      uni.setStorageSync("productListNeedsRefreshManageConsumed", marker);
    },
    handleImageClick(data) {
      const productId = getObjectId(data, ["id", "product_id", "folder_id"]);
      if (!productId) {
        showInvalidRecordToast();
        return;
      }
      uni.navigateTo({
        url: "/pagesOther/productDetailsSelf/productDetailsSelf?id=" + productId+'&fromPage=manage',
      });
    },
    // 打开设置弹窗（替换原 onSettings 行为）
    onSettings() {
      this.settingVisible = true;
    },

    // 弹窗 emit order -> 进入排序页或处理排序
    openOrder() {
      const query = [];
      if (this.albumId) {
        query.push(`fid=${this.albumId}`);
      }
      query.push("fromPage=productManage");
      uni.navigateTo({
        url: `/pagesOther/productSort/productSort?${query.join("&")}`,
      });
    },

    // 弹窗设置改变回调（例如切换单列展示）
    onSettingChange(payload) {
      // payload = { single: boolean } 或其它字段，按需处理
      this.columns = payload.single ? 1 : 2;
    },
    toggleFilter() {
      this.filterVisible = !this.filterVisible;
      if (this.filterVisible && this.categoryOptions.length === 0) {
        this.fetchCategories();
      }
    },
    closeFilter() {
      this.filterVisible = false;
    },
    selectCategoryFilter(item) {
      const categoryId = getObjectId(item, ["id", "category_id", "folder_id"]);
      this.selectedCategoryId = categoryId ? String(categoryId) : "";
      this.selectedCategoryName =
        item && item.folder_name ? item.folder_name : "全部";
      this.albumId = this.selectedCategoryId || null;
      this.filterVisible = false;
      this.fetchList(true);
    },
    async fetchCategories() {
      this.categoryLoading = true;
      try {
        if (!this.$go) return;
        const params = {
          fid: 0,
          folder_type: 1,
          timestamp: Date.now(),
        };
        const signed = this.$base
          ? { ...params, sign: this.$base.getASCII(params) }
          : params;
        const res = await this.$go("album/lists/folder", signed, "post", {
          show_err: false,
        });
        const rawList =
          res && res.data && res.data.lists
            ? Array.isArray(res.data.lists)
              ? res.data.lists
              : res.data.lists.data
            : [];
        this.categoryOptions = this.flattenCategoryTree(rawList);
        this.syncSelectedCategoryName();
      } catch (e) {
        console.error("分类加载失败", e);
        uni.showToast({ title: "分类加载失败", icon: "none" });
      } finally {
        this.categoryLoading = false;
      }
    },
    flattenCategoryTree(list, level = 0) {
      if (!Array.isArray(list)) return [];
      return list.reduce((result, item) => {
        result.push({
          ...item,
          id: String(item.id),
          level,
        });
        if (Array.isArray(item.children) && item.children.length) {
          result.push(...this.flattenCategoryTree(item.children, level + 1));
        }
        return result;
      }, []);
    },
    syncSelectedCategoryName() {
      if (!this.selectedCategoryId) {
        this.selectedCategoryName = "全部";
        return;
      }
      const current = this.categoryOptions.find(
        (item) => String(item.id) === String(this.selectedCategoryId)
      );
      if (current && current.folder_name) {
        this.selectedCategoryName = current.folder_name;
      }
    },
    async fetchList(refresh = false) {
      if (refresh) {
        this.page = 1;
        this.products = [];
      }
      this.loading = true;
      try {
        if (this.$go) {
          const params = {
            key: this.keyword,
            folder_type: 2,
            timestamp: Date.now(),
          };
          if (this.selectedCategoryId) {
            params.fid = this.selectedCategoryId;
          }
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          const res = await this.$go("album/lists/folder", signed, "post", {
            show_err: true,
          });
          if (res && res.data) {
            if (res.data.folder_info && res.data.folder_info.id) {
              this.albumId = this.selectedCategoryId || res.data.folder_info.id;
              this.columns = this.normalizeListColumns(
                res.data.folder_info.layout_type
              );
            }
            const list = Array.isArray(res.data.lists.data)
              ? res.data.lists.data
              : [];
            this.totalCount =
              res.data.lists.total || list.length || this.totalCount;
            const mapped = list.map((item) => ({
              ...item,
              id: item.id,
              imageField: this.getProductCover(item),
              nameField: item.folder_name,
              countField: this.getProductImageCount(item),
            }));
            this.products = mapped;
          }
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "加载失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },

    onSearch() {
      this.fetchList(true);
    },

    normalizeListColumns(layoutType) {
      return Number(layoutType || 1) === 2 ? 1 : 2;
    },

    normalizeArray(value) {
      if (Array.isArray(value)) return value;
      if (typeof value === "string" && value) {
        return value.split(",").filter(Boolean);
      }
      return [];
    },

    getProductImageCount(item) {
      const colorPics = this.normalizeArray(item.pic_ids_arr || item.pic_ids);
      const detailPics = this.normalizeArray(
        item.detail_pic_ids_arr || item.detail_pic_ids
      );
      return colorPics.length + detailPics.length;
    },

    getProductCover(item) {
      return (
        item.new_thumb ||
        item.imageField ||
        item.imgurl ||
        item.picture_url ||
        this.placeholder
      );
    },

    openProduct(p) {
      uni.navigateTo({
        url: `/pagesOther/productDetailsSelf/productDetailsSelf?id=${p.id}&fromPage=manage`,
      });
    },

    createProduct() {
      uni.navigateTo({ url: "/pagesOther/addProduct/addProduct?fromPage=productManage" });
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  position: relative;
  padding: 18rpx;
  box-sizing: border-box;
  padding-bottom: 180rpx;
}

/* top bar */
.top-bar {
  position: relative;
  z-index: 30;
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 18rpx;
}

.filter {
  position: relative;
  z-index: 32;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 132rpx;
  height: 88rpx;
  padding: 0 18rpx;
  border-radius: 44rpx;
  box-sizing: border-box;
  transition: background-color 0.2s;
}

.filter.active {
  background: #ffffff;
}

.filter-text {
  font-weight: 400;
  font-size: 28rpx;
  color: rgba(0, 0, 0, 0.4);
  margin-right: 8rpx;
  max-width: 150rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  .filter-num {
    font-weight: bold;
    font-size: 28rpx;
    color: #333333;
  }
}

.filter-icon {
  width: 28rpx;
  height: 28rpx;
  transition: transform 0.2s;
}

.filter-icon.rotate {
  transform: rotate(180deg);
}

.filter-mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 20;
  background: transparent;
}

.filter-panel {
  position: absolute;
  left: 18rpx;
  right: 18rpx;
  top: 116rpx;
  z-index: 31;
  background: #ffffff;
  border-radius: 18rpx;
  box-shadow: 0 16rpx 44rpx rgba(0, 0, 0, 0.12);
  overflow: hidden;
}

.filter-scroll {
  max-height: 520rpx;
}

.filter-option {
  min-height: 88rpx;
  padding: 0 28rpx;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1rpx solid #f4f4f4;
}

.filter-option.selected {
  background: #fff8d8;
}

.filter-option-name {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  color: #333333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.filter-option-check {
  flex-shrink: 0;
  margin-left: 18rpx;
  color: #ffbb00;
  font-size: 30rpx;
  font-weight: bold;
}

.filter-empty {
  padding: 34rpx 0;
  text-align: center;
  color: #999999;
  font-size: 26rpx;
}

.search-box {
  position: relative;
  z-index: 32;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 16rpx;
  background: #fff;
  border-radius: 80rpx;
  padding: 16rpx 14rpx;
  box-sizing: border-box;
}

.search-box .search-input {
  flex: 1;
  height: 56rpx;
  font-weight: 400;
  font-size: 28rpx;
  color: rgba(0, 0, 0, 0.4);
  border: none;
  background: transparent;
  padding: 0;
  outline: none;
}

.search-icon {
  width: 40rpx;
  height: 40rpx;
}

/* list */
.list {
  padding-top: 6rpx;
}

.loading {
  text-align: center;
  color: #999;
  padding: 40rpx 0;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40rpx 0;
}

.empty-img {
  width: 320rpx;
  height: 240rpx;
  margin-bottom: 18rpx;
}

.empty-text {
  color: #999;
  font-size: 24rpx;
}

/* card grid: 两列均匀排列 */
.cards {
  flex-wrap: wrap;
  gap: 18rpx;
}

.card {
  width: calc((100% - 18rpx) / 2);
  border-radius: 12rpx;
  overflow: hidden;
  position: relative;
  background: #f6f6f6;
  height: 320rpx;
}

/* 这里我们复用 ImageGrid 的输出并强制它在卡片内占满：
     使用 ::v-deep 深度选择器覆盖 ImageGrid 内部样式，使单张图片填满卡片 */
.image-grid-wrapper {
  width: 100%;
  height: 100%;
  overflow: hidden;
}

/* 深度覆盖子组件内部 .cell 尺寸，使单图全图铺满 */
.image-grid-wrapper ::v-deep .grid {
  gap: 0 !important;
}

.image-grid-wrapper ::v-deep .cell {
  width: 100% !important;
  height: 100% !important;
  border-radius: 0 !important;
  overflow: hidden !important;
}

.image-grid-wrapper ::v-deep .thumb {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover;
}

/* 左上角张数 */
.badge {
  position: absolute;
  left: 10rpx;
  top: 10rpx;
  background: rgba(0, 0, 0, 0.35);
  color: #fff;
  padding: 6rpx 10rpx;
  border-radius: 16rpx;
  font-size: 20rpx;
}

/* title overlay */
.title-wrap {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 56rpx;
  display: flex;
  align-items: center;
  padding-left: 12rpx;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.45) 100%
  );
}

.title {
  color: #fff;
  font-size: 22rpx;
}

/* 选中样式 */
.card.is-selected {
  box-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.08);
  transform: translateY(-4rpx);
}

/* 底部栏容器（兼容安全区） */
.bottom-bar {
  position: fixed;
  background: #ffffff;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  gap: 60rpx;
  padding: 24rpx 48rpx;
  box-sizing: border-box;
  z-index: 50;
  padding-bottom: calc(env(safe-area-inset-bottom) + 10rpx);
}

/* 左侧大按钮容器（居中宽度受限） */
.left-btn {
  width: 438rpx;
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
  font-size: 32rpx;
  color: #333333;
}

/* 右侧齿轮按钮 */
.settings-btn {
  height: 88rpx;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

/* 齿轮图标 */
.settings-icon {
  font-size: 28rpx;
  color: #333;
}
</style>
