<template>
  <view class="grid-root">
    <!-- 顶部已选择计数 -->
    <view class="count-text" v-if="showHeader"
      >已选择{{ selectedIds.length }}</view
    >

    <!-- 网格 -->
    <view class="grid">
      <view
        class="cell"
        v-for="(item, idx) in productList"
        :key="item._key"
        :class="{ 'is-selected': selectedMap[item._selectId] }"
        :data-index="idx"
        @tap="toggle(idx)"
      >
        <image class="thumb" :src="item.new_thumb || '/static/image/pic.png'" mode="aspectFill"></image>

        <!-- 左上角张数 -->
        <view
          class="badge"
          v-if="item.detail_pic_ids_arr.length || item.pic_ids_arr.length"
        >
          <view class="badge-text"
            >{{
              item.detail_pic_ids_arr.length + item.pic_ids_arr.length
            }}张</view
          >
        </view>

        <!-- 右上角选择 -->
        <view class="check" :class="{ active: selectedMap[item._selectId] }">
          <image
            class="check-icon"
            v-if="selectedMap[item._selectId]"
            src="/static/icon/Frame 1000006316@2x.png"
            mode="scaleToFill"
          />
        </view>

        <!-- 底部标题遮罩 -->
        <view class="title-wrap">
          <view class="title">{{ item.folder_name }}</view>
        </view>
      </view>
    </view>

    <view class="empty-line" v-if="productList && productList.length === 0"
      >没有更多了~</view
    >

    <!-- 底部完成按钮 -->
    <view class="foot-wrap">
      <view class="btn-done" @tap="submit">
        <text>完成</text>
      </view>
    </view>
  </view>
</template>

<script>
import {
  createRefreshMarker,
  emitRefreshEvents,
  markRefresh,
} from "@/common/helper/refresh.js";
import {
  getObjectId,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";

export default {
  data() {
    return {
      productList: [], // { id, cover, title, count }
      selectedIds: [], // 选中 id 列表
      selectedMap: {},
      maxSelect: 0, // 0 不限，或从 options 中传入限制
      showHeader: true,
      // 接口/参数相关
      albumId: 0, // 目标合集 id（从 options 传入）
      loading: false,
      fromPage: "",
      mode: "",
      categoryName: "",
    };
  },
  onLoad(options = {}) {
    // 接受参数：可传 albumId / fid / maxSelect / preselected (逗号分隔 id)
    const aid = options.albumId || options.fid || options.album_id;
    if (aid) this.albumId = aid;
    this.fromPage = options.fromPage;
    this.mode = options.mode || "";
    this.categoryName = this.safeDecodeRouteValue(
      options.category_name || options.name || "",
    );
    if (options.maxSelect) this.maxSelect = Number(options.maxSelect) || 0;
    if (options.preselected) {
      const arr = String(options.preselected)
        .split(",")
        .filter(Boolean)
        .map((x) => (isNaN(x) ? x : Number(x)));
      this.selectedIds = arr;
      arr.forEach((id) => (this.selectedMap[id] = true));
    }
    this.loadProductData();
  },
  computed: {
    selectedItems() {
      return this.productList.filter((item) => this.isProductSelected(item));
    },
  },
  methods: {
    normalizeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    safeDecodeRouteValue(value) {
      const text = this.normalizeText(value);
      if (!text) return "";
      try {
        return decodeURIComponent(text);
      } catch (e) {
        return text;
      }
    },
    getProductId(item) {
      return getObjectId(item, ["id", "product_id", "folder_id"]);
    },
    isProductSelected(item) {
      const id = this.getProductId(item);
      return !!(id && this.selectedMap[id]);
    },
    refreshPreviousCategoryDetail(marker) {
      const pages = typeof getCurrentPages === "function" ? getCurrentPages() : [];
      const previousPage = pages && pages[pages.length - 2];
      const previousVm = previousPage && previousPage.$vm;
      if (
        previousVm &&
        previousVm.categoryId &&
        String(previousVm.categoryId) === String(this.albumId) &&
        typeof previousVm.handleRefreshData === "function"
      ) {
        previousVm.handleRefreshData(marker);
        return true;
      }
      uni.$emit("refreshClassDetailData", marker);
      return false;
    },
    returnToPreviousPage(marker) {
      const refreshed = this.refreshPreviousCategoryDetail(marker);
      uni.navigateBack({
        fail: () => {
          if (!refreshed) {
            this.openCategoryDetail(this.albumId, marker);
          }
        },
      });
    },
    openCategoryDetail(categoryId, marker = "") {
      if (!categoryId) {
        uni.navigateBack();
        return;
      }
      const query = [
        `id=${encodeURIComponent(categoryId)}`,
        "refresh=1",
        `ts=${Date.now()}`,
      ];
      if (marker) {
        query.push(`marker=${encodeURIComponent(marker)}`);
      }
      if (this.categoryName) {
        query.push(`name=${encodeURIComponent(this.categoryName)}`);
      }
      const detailUrl = `/pagesOther/classDetail/classDetail?${query.join("&")}`;
      uni.redirectTo({ url: detailUrl });
    },
    // 获取所有产品
    async getAllProduct() {
      if (this.$go) {
        const res = await this.$go(
          "album/lists/folder",
          { folder_type: 2, timestamp: Date.now() },
          "post",
          { show_err: true },
        );
        return res.data.lists.data || [];
      }
      return [];
    },
    // 获取分类下的产品
    async getFidProduct() {
      if (this.$go) {
        const res = await this.$go(
          "album/lists/folder",
          { fid: this.albumId, folder_type: 2, timestamp: Date.now() },
          "post",
          { show_err: true },
        );
        return res.data.lists.data || [];
      }
      return [];
    },
    // 加载并对比产品数据
    async loadProductData() {
      try {
        // 并行请求两个接口
        const [allProducts, categoryProducts] = await Promise.all([
          this.getAllProduct(),
          this.getFidProduct(),
        ]);

        // 提取分类下已有产品的 id 列表
        this.selectedIds = categoryProducts
          .map((item) => this.getProductId(item))
          .filter(Boolean);

        // 构建 selectedMap 对象 { id: true }
        this.selectedMap = {};
        categoryProducts.forEach((item) => {
          const id = this.getProductId(item);
          if (id) {
            this.$set(this.selectedMap, id, true);
          }
        });

        // 设置所有产品列表
        this.productList = allProducts.map((item, index) => ({
          ...item,
          _selectId: this.getProductId(item),
          _key: this.getProductId(item) || `product-${index}`,
        }));

        console.log("所有产品数量:", this.productList.length);
        console.log("已选择产品数量:", this.selectedIds.length);
        console.log("selectedMap:", this.selectedMap);
      } catch (err) {
        console.log("加载产品数据失败:", err);
      }
    },

    // 切换选中
    toggle(index) {
      const current = this.productList[index];
      const id = current && (current._selectId || this.getProductId(current));
      if (!id) {
        showInvalidRecordToast();
        return;
      }
      const isSelected = !!this.selectedMap[id];
      if (isSelected) {
        this.$delete(this.selectedMap, id);
        const idx = this.selectedIds.indexOf(id);
        if (idx > -1) this.selectedIds.splice(idx, 1);
      } else {
        // 校验最大选择数
        if (this.maxSelect > 0 && this.selectedIds.length >= this.maxSelect) {
          uni.showToast({
            title: `最多选择 ${this.maxSelect} 项`,
            icon: "none",
          });
          return;
        }
        this.$set(this.selectedMap, id, true);
        this.selectedIds.push(id);
      }
      // 可选：触发 change 事件供页面内部监听
      this.$emit("change", this.selectedItems.slice());
    },

    // 点击完成：调用后端接口把选中的产品添加到合集（示例接口，请替换）
    async submit() {
      console.log(this.selectedItems);
      if (this.selectedIds.length === 0) {
        if (this.mode !== "remove") {
          uni.showToast({ title: "请先选择产品", icon: "none" });
          return;
        }
      }
      // 如果需要 albumId 必须存在
      if (!this.albumId) {
        uni.showToast({ title: "缺少目标合集信息", icon: "none" });
        return;
      }
      uni.showLoading({ title: "提交中..." });
      try {
        const payload = {
          fid: this.albumId,
          product_ids: this.selectedIds.join(","),
          timestamp: Date.now(),
        };
        // 使用项目签名函数（若有）
        const params = this.$base
          ? { ...payload, sign: this.$base.getASCII(payload) }
          : payload;

        if (this.$go) {
          // 请替换接口名为后端实际接口，例如 'album/add_products'
          const res = await this.$go(
            "album/category/add_products",
            params,
            "post",
            {
              show_err: true,
            },
          );
          if (!res || Number(res.code) !== 0) {
            return;
          }
          // 后端成功返回后处理（可根据实际返回逻辑调整）
          uni.showToast({
            title: res && res.msg ? res.msg : "操作成功",
            icon: "none",
          });
          const marker = createRefreshMarker();
          markRefresh(["product", "category", "home"], marker);
          emitRefreshEvents(["product", "home"], marker);
          setTimeout(() => {
            if (this.fromPage === "categoryPreview") {
              this.openCategoryDetail(this.albumId, marker);
              return;
            }
            if (this.fromPage === "classDetail") {
              this.returnToPreviousPage(marker);
              return;
            } else {
              uni.$emit("refreshClassManageData", marker);
            }
            uni.navigateBack();
          }, 300);
        }
      } catch (e) {
        console.error("submit error", e);
        uni.showToast({ title: "提交失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },
  },
};
</script>

<style scoped lang="scss">
.grid-root {
  padding: 20rpx;
  background: #f5f5f5;
  box-sizing: border-box;
}

/* 顶部已选择文本 */
.count-text {
  font-weight: bold;
  font-size: 32rpx;
  color: #333333;
  margin-bottom: 14rpx;
}

/* 网格容器（按 4 列均匀排列） */
.grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  justify-content: flex-start;
}

/* 单元格宽度：4列 */
.cell {
  width: calc((100% - 3 * 20rpx) / 4);
  height: 200rpx;
  border-radius: 12rpx;
  overflow: hidden;
  position: relative;
  background: #f6f6f6;
  box-sizing: border-box;
}

/* 图片 */
.thumb {
  width: 100%;
  height: 100%;
  display: block;
}

/* 左上角张数角标 */
.badge {
  position: absolute;
  left: 8rpx;
  top: 8rpx;
  background: rgba(0, 0, 0, 0.35);
  padding: 8rpx;
  border-radius: 40rpx;
}

.badge-text {
  font-weight: 400;
  font-size: 18rpx;
  color: #ffffff;
}

/* 右上角选择圆 */
.check {
  position: absolute;
  right: 8rpx;
  top: 8rpx;
  width: 36rpx;
  height: 36rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 22rpx;
  border: 2rpx solid rgba(0, 0, 0, 0.06);

  .check-icon {
    width: 36rpx;
    height: 36rpx;
  }
}

.check.active {
  background: #ffd800;
  color: #222;
  border-color: rgba(0, 0, 0, 0.08);
}

/* 底部标题遮罩 */
.title-wrap {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 50rpx;
  background: linear-gradient(
    0deg,
    rgba(0, 0, 0, 0.8) 0%,
    rgba(0, 0, 0, 0) 100%
  );
  display: flex;
  align-items: center;
  padding-left: 10rpx;
  box-sizing: border-box;
}

.title {
  color: #fff;
  font-size: 20rpx;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 空提示 */
.empty-line {
  text-align: center;
  color: #999;
  margin-top: 22rpx;
}

/* 底部完成按钮 */
.foot-wrap {
  position: fixed;
  left: 0;
  right: 0;
  bottom: calc(env(safe-area-inset-bottom) + 20rpx);
  display: flex;
  justify-content: center;
  padding: 20rpx;
  box-sizing: border-box;
  background: transparent;
  z-index: 100;
}

.btn-done {
  width: 686rpx;
  height: 96rpx;
  background: #ffd800;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30rpx;
  color: #222;
}
</style>
