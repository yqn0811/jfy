<template>
  <view class="style-result-page">
    <view class="header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view
        class="custom-nav-bar"
        :style="{ height: navigationBarHeight + 'px' }"
      >
        <view class="nav-left" @click="goBack">
          <image
            class="home-icon"
            src="/static/icon/back.png"
            mode="aspectFit"
          ></image>
        </view>
        <view class="nav-title">{{ pageTitle }}</view>
        <view class="nav-right">
          <image
            class="more-icon"
            src="/static/icon/more.png"
            mode="aspectFit"
            @click="showMore"
          ></image>
        </view>
      </view>
    </view>

    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="title-section">
        <text class="title-text">选款单</text>
      </view>

      <view class="info-card">
        <view class="info-item">
          <text class="info-label">编号：</text>
          <text class="info-value">{{ orderInfo.name }}</text>
        </view>
        <view class="info-item">
          <text class="info-label">时间：</text>
          <text class="info-value">{{
            orderInfo.display_time || orderInfo.create_time
          }}</text>
        </view>
        <view class="info-item" v-if="customerInfo.nickname">
          <text class="info-label">客户：</text>
          <text class="info-value">{{
            customerInfo.nickname +
            (customerInfo.mobile ? " · " + customerInfo.mobile : "")
          }}</text>
        </view>
        <view class="info-item" v-if="factoryInfo.nickname && fromPage === 'my'">
          <text class="info-label">厂家：</text>
          <text class="info-value">{{
            factoryInfo.nickname +
            (factoryInfo.mobile ? " · " + factoryInfo.mobile : "")
          }}</text>
        </view>
      </view>

      <view class="summary-card" v-if="productSummary">
        <view class="summary-main">
          <image
            class="summary-image"
            :src="
              productSummary.cover_img ||
              orderInfo.share_img ||
              '/static/image/pic.png'
            "
            mode="aspectFill"
          ></image>
          <view class="summary-content">
            <view class="summary-title">{{
              productSummary.name || "未命名产品"
            }}</view>
            <view class="summary-desc" v-if="productSummary.desc">{{
              productSummary.desc
            }}</view>
            <view class="summary-meta">
              <text v-if="productSummary.missing">产品记录缺失</text>
              <text>已选 {{ selectedPictures.length }} 张</text>
              <text v-if="variantPictures.length"
                >花色 {{ variantPictures.length }} 张</text
              >
              <text v-if="detailPictures.length"
                >细节 {{ detailPictures.length }} 张</text
              >
            </view>
          </view>
        </view>
      </view>

      <view class="section-card" v-if="mainPictures.length">
        <view class="section-header">
          <text class="section-title">主图</text>
          <text class="section-count">{{ mainPictures.length }} 张</text>
        </view>
        <view class="main-picture-list">
          <view
            class="main-picture-item"
            v-for="(item, index) in mainPictures"
            :key="item.render_key"
            @click="handleProductClick(item)"
          >
            <image
              class="main-picture-image"
              :src="item.src || '/static/image/pic.png'"
              mode="aspectFill"
            ></image>
            <view class="main-picture-label">{{
              item.pic_name || item.name || "主图"
            }}</view>
          </view>
        </view>
      </view>

      <view class="section-card" v-if="variantPictures.length || isEditMode">
        <view class="section-header">
          <text class="section-title">花色图</text>
          <text class="section-count">{{ variantPictures.length }} 张</text>
        </view>
        <view class="product-list">
          <view
            class="product-item"
            :class="{ selected: isProductSelected(item.id) }"
            v-for="(item, index) in variantPictures"
            :key="item.render_key"
            @click="handleProductClick(item)"
          >
            <image
              class="product-image"
              :src="item.src || '/static/image/pic.png'"
              mode="aspectFill"
            ></image>
            <view class="product-name">{{
              item.pic_name || item.name || "花色图"
            }}</view>

            <view v-if="isEditMode" class="select-mask">
              <view class="select-checkbox">
                <image
                  v-if="isProductSelected(item.id)"
                  class="select-icon"
                  src="/static/icon/Frame 1000006316@2x.png"
                  mode="aspectFit"
                ></image>
              </view>
            </view>

            <view
              v-if="isEditMode"
              class="delete-btn"
              @click.stop="deleteProduct(item)"
            >
              <image
                class="delete-icon"
                src="/static/icon/退出@2x.png"
                mode="aspectFit"
              ></image>
            </view>
          </view>

          <view
            v-if="isEditMode"
            class="add-product-item"
            @click="addMoreProducts"
          >
            <image
              class="add-icon"
              src="/static/icon/24＊24@2x(6).png"
              mode="aspectFit"
            ></image>
            <text class="add-text">添加花色</text>
          </view>
        </view>
      </view>

      <view class="section-card" v-if="detailPictures.length">
        <view class="section-header">
          <text class="section-title">细节图</text>
          <text class="section-count">{{ detailPictures.length }} 张</text>
        </view>
        <view class="detail-picture-list">
          <view
            class="detail-picture-item"
            v-for="(item, index) in detailPictures"
            :key="item.render_key"
            @click="handleProductClick(item)"
          >
            <image
              class="detail-picture-image"
              :src="item.src || '/static/image/pic.png'"
              mode="aspectFill"
            ></image>
          </view>
        </view>
      </view>

      <view class="empty-box" v-if="selectedPictures.length === 0 && !loading">
        <image
          class="empty-icon"
          src="/static/icon/empty.png"
          mode="aspectFit"
        ></image>
        <view class="empty-text">暂无选款数据</view>
      </view>
    </view>

    <view class="nav-bottom-btns" v-if="fromPage === 'my'">
      <view v-if="!isEditMode" class="bottom-btns-wrap normal-actions">
        <view class="shrae-btns" @click="handleShare">
          <image
            class="item-icon"
            src="/static/icon/24＊24@2x (1).png"
            mode="scaleToFill"
          />
          <text class="item-text">分享</text>
        </view>
        <view class="edit-btns" @click="enterEditMode">
          <image
            class="item-icon"
            src="/static/icon/24＊24@2x(9).png"
            mode="scaleToFill"
          />
          <text class="item-text">编辑</text>
        </view>
      </view>

      <view v-else class="bottom-btns-wrap">
        <view class="btns-items delete">
          <view
            class="item"
            :class="{ disabled: selectedProducts.length === 0 }"
            @click="removeSelectedProducts"
          >
            <image
              class="item-icon"
              src="/static/icon/trash@2x(1).png"
              mode="scaleToFill"
            />
            <text class="item-delete-text">删除</text>
          </view>
        </view>
        <view class="submit-btns">
          <view class="btns" @click="cancelEdit">取消</view>
          <view class="btns yellow" @click="confirmEdit">完成</view>
        </view>
      </view>
    </view>
    <view class="nav-bottom-btns" v-else>
      <view class="shrae-btns" @click="handleShare">
        <image
          class="item-icon"
          src="/static/icon/24＊24@2x (1).png"
          mode="scaleToFill"
        />
        <text class="item-text">分享</text>
      </view>
    </view>

    <SharePopup
      :visible="sharePopupVisible"
      @update:visible="sharePopupVisible = $event"
      :title="orderInfo.name || '选款单'"
      typeText="选款单"
      :url="shareUrl"
      :uid="uid || ''"
      type="selection"
      :hid="styleId"
      :cover-url="shareCoverUrl"
    />
  </view>
</template>

<script>
import SharePopup from "@/components/SharePopup/index.vue";
import { ensureSharedPageLogin } from "@/common/helper/shareLogin.js";

export default {
  components: {
    SharePopup,
  },
  data() {
    return {
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,
      pageTitle: "家纺云相册",
      orderInfo: {},
      customerInfo: {},
      factoryInfo: {},
      productList: [],
      mainPictures: [],
      variantPictures: [],
      detailPictures: [],
      selectedPictures: [],
      productSummary: null,
      loading: false,
      styleId: "",
      uid: "",
      isEditMode: false,
      selectedProducts: [],
      fromPage: "",
      sharePopupVisible: false,
      shareUrl: "",
    };
  },

  onLoad(options) {
    const sceneOptions = this.parseSceneOptions(options);
    options = {
      ...(options || {}),
      ...sceneOptions,
    };
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    if (options.id) {
      this.styleId = options.id;
      this.uid = this.normalizeShareParam(options.uid);
      this.fromPage = options.fromPage;
      this.shareUrl = this.buildShareUrl();
      if ((this.uid || options.source === "share") && !ensureSharedPageLogin("pagesOther/styleResult/styleResult", options, this.uid)) {
        return;
      }
      this.getStyleDetail();
    }
  },

  computed: {
    shareCoverUrl() {
      return this.getShareImage();
    },
  },

  methods: {
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
    buildShareUrl() {
      const uid = this.getShareOwnerId();
      const query = [`id=${encodeURIComponent(this.styleId)}`];
      if (uid) query.push(`uid=${encodeURIComponent(uid)}`);
      query.push("source=share");
      return `/pagesOther/styleResult/styleResult?${query.join("&")}`;
    },
    getShareOwnerId() {
      return (
        this.normalizeShareParam(this.uid) ||
        this.normalizeShareParam(this.orderInfo.factory_uid) ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "")
      );
    },
    getShareImage() {
      return (
        this.orderInfo.share_img ||
        this.productList[0]?.src ||
        this.productList[0]?.image ||
        ""
      );
    },
    goBack() {
      uni.navigateBack();
    },

    showMore() {
      const itemList =
        this.fromPage === "my" ? ["分享", "删除选款单"] : ["分享"];
      uni.showActionSheet({
        itemList,
        success: (res) => {
          if (res.tapIndex === 0) {
            this.handleShare();
          } else if (this.fromPage === "my" && res.tapIndex === 1) {
            this.confirmDeleteSelection();
          }
        },
      });
    },

    handleShare() {
      this.shareUrl = this.buildShareUrl();
      this.sharePopupVisible = true;
    },

    toProductDetail(data) {
      uni.navigateTo({
        url:
          "/pagesOther/picDetail/picDetail?pic_id=" +
          data.id +
          "&uid=" +
          this.uid +
          "&fromPage=styleResult",
      });
    },

    handleProductClick(item) {
      if (this.isEditMode) {
        this.toggleSelectProduct(item);
        return;
      }
      this.toProductDetail(item);
    },

    enterEditMode() {
      this.isEditMode = true;
      this.selectedProducts = [];
    },

    cancelEdit() {
      this.isEditMode = false;
      this.selectedProducts = [];
    },

    confirmEdit() {
      this.isEditMode = false;
      this.selectedProducts = [];
      uni.showToast({
        title: "编辑完成",
        icon: "success",
      });
    },

    deleteProduct(item) {
      uni.showModal({
        title: "提示",
        content: `确定要删除"${item.pic_name || item.name || "该图片"}"吗？`,
        success: (res) => {
          if (res.confirm) {
            this.performDeleteSingle(item.id);
          }
        },
      });
    },

    performDeleteSingle(productId) {
      this.performRemoveImages([productId]);
    },

    performRemoveImages(productIds) {
      const querys = {
        selection_id: this.styleId,
        pic_ids: productIds.join(","),
        product_id: this.orderInfo.product_id,
        timestamp: new Date().getTime(),
      };

      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      uni.showLoading({ title: "删除中..." });

      this.$go("album/selection/remove_images", data, "post", {
        show_err: true,
      })
        .then((res) => {
          uni.hideLoading();
          if (res.code === 0) {
            uni.showToast({
              title: "删除成功",
              icon: "success",
            });
            this.selectedProducts = this.selectedProducts.filter(
              (id) => !productIds.includes(id),
            );
            this.getStyleDetail();
          }
        })
        .catch((err) => {
          uni.hideLoading();
          console.error("删除产品失败:", err);
        });
    },

    toggleSelectProduct(item) {
      const index = this.selectedProducts.indexOf(item.id);
      if (index > -1) {
        this.selectedProducts.splice(index, 1);
      } else {
        this.selectedProducts.push(item.id);
      }
    },

    isProductSelected(productId) {
      return this.selectedProducts.includes(productId);
    },

    removeSelectedProducts() {
      if (this.selectedProducts.length === 0) {
        uni.showToast({
          title: "请先选择要删除的花色图",
          icon: "none",
        });
        return;
      }
      uni.showModal({
        title: "提示",
        content: `确定要删除已选的${this.selectedProducts.length}张花色图吗？`,
        success: (res) => {
          if (res.confirm) {
            this.performRemoveImages([...this.selectedProducts]);
          }
        },
      });
    },

    confirmDeleteSelection() {
      uni.showModal({
        title: "提示",
        content: "确定要删除当前选款单吗？",
        success: (res) => {
          if (res.confirm) {
            this.performDeleteSelection();
          }
        },
      });
    },

    performDeleteSelection() {
      const querys = {
        selection_id: this.styleId,
        timestamp: new Date().getTime(),
      };

      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      uni.showLoading({ title: "删除中..." });

      this.$go("album/selection/delete", data, "post", {
        show_err: true,
      })
        .then((res) => {
          uni.hideLoading();
          if (res.code !== 0) {
            return;
          }
          uni.showToast({
            title: "删除成功",
            icon: "success",
          });
          setTimeout(() => {
            uni.$emit("refreshMyStyleListData");
            uni.navigateBack();
          }, 1000);
        })
        .catch((err) => {
          uni.hideLoading();
          console.error("删除产品失败:", err);
        });
    },

    addMoreProducts() {
      uni.navigateTo({
        url: `/pagesOther/selectStyle/selectStyle?styleId=${this.styleId}&uid=${this.orderInfo.factory_uid}&id=${this.orderInfo.product_id}&mode=add`,
        success: (res) => {
          res.eventChannel.emit("existingProducts", {
            productList: this.selectedPictures,
          });
        },
        events: {
          acceptDataFromOpenedPage: (data) => {
            if (data && data.refresh) {
              this.getStyleDetail();
            }
          },
        },
      });
    },

    goToStyleList() {
      uni.navigateTo({
        url: "/pagesOther/myStyleList/myStyleList?fromPage=my",
      });
    },

    withRenderKeys(list, prefix) {
      return list.map((item, index) => ({
        ...item,
        render_key: `${prefix}_${item.selection_item_id || item.id || index}`,
      }));
    },

    syncPictureState(detail) {
      const groupedPictures = detail.grouped_pictures || {};
      const mainPictures = Array.isArray(groupedPictures.main_pictures)
        ? this.withRenderKeys(groupedPictures.main_pictures, "main")
        : [];
      const variantPictures = Array.isArray(groupedPictures.variant_pictures)
        ? this.withRenderKeys(groupedPictures.variant_pictures, "variant")
        : [];
      const detailPictures = Array.isArray(groupedPictures.detail_pictures)
        ? this.withRenderKeys(groupedPictures.detail_pictures, "detail")
        : [];
      const selectedPictures = Array.isArray(detail.list)
        ? detail.list
        : [...mainPictures, ...variantPictures];

      this.orderInfo = detail.info || {};
      this.orderInfo.share_img = detail.share_img || this.orderInfo.share_img || "";
      this.customerInfo = detail.customer || {};
      this.factoryInfo = detail.factory || {};
      this.productSummary = detail.product_summary || null;
      this.mainPictures = mainPictures;
      this.variantPictures = variantPictures;
      this.detailPictures = detailPictures;
      this.selectedPictures = selectedPictures;
      this.productList = [...mainPictures, ...variantPictures];
    },

    getStyleDetail() {
      this.loading = true;

      const querys = {
        selection_id: this.styleId,
        timestamp: new Date().getTime(),
      };

      this.$go("album/selection/detail", querys, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          if (res && res.data) {
            this.syncPictureState(res.data);
          }
        })
        .catch((err) => {
          console.error("获取选款单详情失败:", err);
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },

  onShareAppMessage() {
    const userInfo = uni.getStorageSync("userInfo");
    const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
    const sharePath = this.buildShareUrl();
    const imageUrl = this.getShareImage();
    return {
      title: `${enterpriseInfo.company_name || ""}的选款单 - ${this.orderInfo.name || ""}`,
      path: sharePath || `/pagesOther/styleResult/styleResult?id=${this.styleId}&uid=${this.uid || userInfo.id}`,
      imageUrl,
    };
  },

  onShareTimeline() {
    const userInfo = uni.getStorageSync("userInfo");
    const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
    const imageUrl = this.getShareImage();

    return {
      title: `${enterpriseInfo.company_name || ""}的选款单 - ${this.orderInfo.name || ""}`,
      query: `id=${this.styleId}&uid=${this.uid || userInfo.id}`,
      imageUrl,
    };
  },
};
</script>

<style lang="scss" scoped>
.style-result-page {
  background-color: #fff;
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: #fff;
  z-index: 999;
  border-bottom: 1rpx solid #eee;

  .custom-nav-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30rpx;

    .nav-left {
      width: 80rpx;
      display: flex;
      align-items: center;

      .home-icon {
        width: 40rpx;
        height: 40rpx;
      }
    }

    .nav-title {
      flex: 1;
      text-align: center;
      font-size: 32rpx;
      font-weight: 600;
      color: #333;
    }

    .nav-right {
      width: 120rpx;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 24rpx;

      .more-icon,
      .search-icon {
        width: 40rpx;
        height: 40rpx;
      }
    }
  }
}

.content {
  padding: 30rpx;
  padding-bottom: calc(260rpx + constant(safe-area-inset-bottom));
  padding-bottom: calc(260rpx + env(safe-area-inset-bottom));
}

.title-section {
  margin-bottom: 30rpx;

  .title-text {
    font-size: 40rpx;
    font-weight: bold;
    color: #333;
  }
}

.info-card {
  background-color: #fff;
  border-radius: 16rpx;
  margin-bottom: 30rpx;

  .info-item {
    display: flex;
    align-items: center;
    margin-bottom: 20rpx;

    &:last-child {
      margin-bottom: 0;
    }

    .info-label {
      font-size: 28rpx;
      color: #666;
      min-width: 100rpx;
    }

    .info-value {
      font-size: 28rpx;
      color: #333;
      flex: 1;
    }
  }
}

.summary-card {
  background: #f7f8fa;
  border-radius: 24rpx;
  padding: 24rpx;
  margin-bottom: 30rpx;

  .summary-main {
    display: flex;
    gap: 20rpx;
  }

  .summary-image {
    width: 180rpx;
    height: 180rpx;
    border-radius: 20rpx;
    flex-shrink: 0;
  }

  .summary-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .summary-title {
    font-size: 32rpx;
    color: #222;
    font-weight: 600;
    line-height: 1.4;
  }

  .summary-desc {
    font-size: 24rpx;
    color: #666;
    line-height: 1.5;
    margin-top: 12rpx;
  }

  .summary-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 20rpx;

    text {
      font-size: 22rpx;
      color: #8a5a00;
      background: #fff3cc;
      border-radius: 999rpx;
      padding: 8rpx 16rpx;
    }
  }
}

.section-card {
  margin-bottom: 30rpx;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
}

.section-title {
  font-size: 30rpx;
  color: #222;
  font-weight: 600;
}

.section-count {
  font-size: 24rpx;
  color: #999;
}

.main-picture-list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.main-picture-item {
  position: relative;
  border-radius: 24rpx;
  overflow: hidden;
  background: #f5f5f5;
}

.main-picture-image {
  width: 100%;
  height: 520rpx;
  display: block;
}

.main-picture-label {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 24rpx;
  font-size: 28rpx;
  color: #fff;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.72) 100%);
}

.product-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16rpx;

  .product-item {
    background-color: #fff;
    border-radius: 16rpx;
    overflow: hidden;
    position: relative;
    border: 4rpx solid transparent;
    box-sizing: border-box;

    &.selected {
      border-color: #ffd000;
    }

    .product-image {
      width: 100%;
      height: 400rpx;
      display: block;
    }

    .product-name {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 20rpx;
      background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0) 0%,
        rgba(0, 0, 0, 0.6) 100%
      );
      font-size: 28rpx;
      color: #fff;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .delete-btn {
      position: absolute;
      top: 16rpx;
      right: 16rpx;
      width: 56rpx;
      height: 56rpx;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10;

      .delete-icon {
        width: 40rpx;
        height: 40rpx;
      }
    }

    .select-mask {
      position: absolute;
      inset: 0;
      z-index: 9;
      background: rgba(0, 0, 0, 0.08);
      pointer-events: none;
    }

    .select-checkbox {
      position: absolute;
      top: 16rpx;
      left: 16rpx;
      width: 44rpx;
      height: 44rpx;
      border: 3rpx solid #fff;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.25);
      box-sizing: border-box;
      overflow: hidden;
    }

    .select-icon {
      width: 44rpx;
      height: 44rpx;
      display: block;
    }
  }

  .add-product-item {
    background-color: #f5f5f5;
    border-radius: 16rpx;
    height: 400rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16rpx;

    .add-icon {
      width: 80rpx;
      height: 80rpx;
      opacity: 0.5;
    }

    .add-text {
      font-size: 28rpx;
      color: #999;
    }
  }
}

.detail-picture-list {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16rpx;
}

.detail-picture-item {
  border-radius: 16rpx;
  overflow: hidden;
  background: #f5f5f5;
}

.detail-picture-image {
  width: 100%;
  height: 200rpx;
  display: block;
}

.empty-box {
  padding: 200rpx 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  .empty-icon {
    width: 200rpx;
    height: 200rpx;
    margin-bottom: 30rpx;
  }

  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
}

.nav-bottom-btns {
  width: 100%;
  position: fixed;
  bottom: 0;
  left: 0;
  z-index: 1200;
  padding: 20rpx 32rpx calc(24rpx + constant(safe-area-inset-bottom));
  padding: 20rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -8rpx 24rpx rgba(0, 0, 0, 0.06);
  box-sizing: border-box;
  overflow: hidden;

  .bottom-btns-wrap {
    min-height: 96rpx;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .normal-actions {
    gap: 24rpx;
  }

  .btns-items {
    width: 25%;
    display: flex;
    align-items: center;
    gap: 24rpx;
    padding-left: 24rpx;

    &.delete {
      width: 13%;
    }

    .item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8rpx;

      &.disabled {
        opacity: 0.45;
      }
    }
  }

  .item-icon {
    width: 48rpx;
    height: 48rpx;
  }

  .item-text {
    font-size: 24rpx;
    color: #333;
  }

  .item-delete-text {
    font-size: 24rpx;
    color: #ff3434;
  }

  .shrae-btns {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffd000;
    border-radius: 96rpx;
    padding: 24rpx 0;
    gap: 8rpx;

    .item-text {
      font-size: 32rpx;
      color: #333;
    }
  }

  .edit-btns {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #00000099;
    border-radius: 96rpx;
    padding: 24rpx 0;
    gap: 8rpx;
    box-sizing: border-box;

    .item-text {
      font-size: 32rpx;
      color: #333;
      font-weight: bold;
    }
  }

  .submit-btns {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 24rpx;

    .btns {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      border-radius: 96rpx;
      padding: 24rpx 0;
      font-size: 32rpx;
      color: #333;
      font-weight: bold;
      border: 1px solid #00000099;

      &.yellow {
        background: #ffd000;
        border-color: transparent;
      }
    }
  }
}
</style>
