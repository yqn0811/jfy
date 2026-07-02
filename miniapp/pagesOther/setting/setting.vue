<template>
  <view class="setting-page">
    <!-- 主要内容区域 -->
    <view class="content">
      <!-- 产品设置区域 -->
      <view class="section">
        <view class="section-title">产品设置</view>
        <view class="setting-card">
          <!-- 修改产品 -->
          <view class="setting-item" @click="handleEdit">
            <view class="item-left">
              <image
                src="/static/icon/24＊24@2x(9).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">编辑产品</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>

          <view class="divider"></view>

          <!-- 置顶该产品 -->
          <view class="setting-item">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(20).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">置顶</text>
            </view>
            <u-switch
              v-model="isTop"
              activeColor="#333"
              inactiveColor="#e4e4e4"
              @change="handleTopChange"
            ></u-switch>
          </view>

          <view class="divider"></view>

          <!-- 设置为热门产品 -->
          <view class="setting-item">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(19).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">热门</text>
            </view>
            <u-switch
              v-model="isHot"
              activeColor="#333"
              inactiveColor="#e4e4e4"
              @change="handleHotChange"
            ></u-switch>
          </view>

          <view class="divider"></view>

          <!-- 设置为私密产品 -->
          <view class="setting-item private">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(18).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">私密</text>
            </view>
            <u-switch
              v-model="isPrivate"
              activeColor="#333"
              inactiveColor="#e4e4e4"
              @change="handlePrivateChange"
            ></u-switch>
          </view>

          <view class="divider"></view>

          <!-- 产品分类设置 -->
          <view class="setting-item" @click="handleCategory">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(17).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">产品分类</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>

      <!-- 展现样式区域 -->
      <view class="section">
        <view class="section-title">展现样式</view>
        <view class="setting-card">
          <!-- 产品封面主图设置 -->
          <view class="setting-item" @click="handleCover">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(16).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">封面图</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>

          <view class="divider"></view>

          <!-- 产品内图片排序管理 -->
          <view class="setting-item" @click="handleSort">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(22).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">图片排序</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>

          <view class="divider"></view>

          <!-- 花色图切换为单列模式 -->
          <view class="setting-item" @click="handleSingleColumn">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(21).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">单列展示</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>

          <view class="divider"></view>

          <!-- 花色图切换为双列模式 -->
          <view class="setting-item" @click="handleDoubleColumn">
            <view class="item-left">
              <image
                src="/static/icon/Frame@2x(23).png"
                class="item-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text">双列展示</text>
            </view>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>

      <!-- 删除该产品区域 -->
      <view class="section">
        <view class="setting-card delete-card" @click="handleDelete">
          <view class="setting-item">
            <view class="item-left">
              <image
                src="/static/icon/trash@2x(2).png"
                class="item-icon delete-icon"
                mode="aspectFit"
              ></image>
              <text class="item-text delete-text">删除产品</text>
            </view>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      productId: "",
      isTop: false,
      isHot: false,
      isPrivate: false,
      currentCover: "",
      picLayout: 2,
    };
  },
  onLoad(options) {
    // 获取状态栏高度
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;

    // 获取产品ID
    if (options.id) {
      this.productId = options.id;
      this.getProductInfo();
    }
  },
  methods: {
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 获取产品信息
    getProductInfo() {
      const querys = {
        fid: this.productId,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      this.$go("album/products/detail", data, "post", {
        show_err: false,
        loading: false,
      })
        .then((res) => {
          if (res && res.data) {
            this.isTop = res.data.set_top === 1;
            this.isHot = res.data.is_hot === 1;
            this.isPrivate = res.data.private_type === 2;
            this.currentCover = res.data.new_thumb || "";
            this.picLayout = Number(res.data.pic_layout || 2);
          }
        })
        .catch((err) => {
          console.log("获取产品信息失败:", err);
        });
    },

    // 修改产品
    handleEdit() {
      uni.navigateTo({
        url: `/pagesOther/addProduct/addProduct?id=${this.productId}`,
      });
    },

    // 置顶切换
    handleTopChange(e) {
      const nextValue = this.normalizeSwitchValue(e);
      this.isTop = nextValue;
      const querys = {
        product_id: this.productId,
        is_top: nextValue ? 1 : 0,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("album/set_top/product", data, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          if (res.code === 0) {
            this.emitProductRefresh();
            uni.showToast({ title: "设置成功", icon: "success" });
          } else {
            this.isTop = !nextValue;
          }
        })
        .catch((err) => {
          this.isTop = !nextValue;
          console.log("置顶设置失败:", err);
        });
    },

    // 热门切换
    handleHotChange(e) {
      this.isHot = this.normalizeSwitchValue(e);
      this.updateProductStatus("is_hot", this.isHot ? 1 : 0);
    },

    // 私密切换
    handlePrivateChange(e) {
      this.isPrivate = this.normalizeSwitchValue(e);
      this.updateProductStatus("private_type", this.isPrivate ? 2 : 1);
    },

    normalizeSwitchValue(e) {
      if (typeof e === "boolean") return e;
      if (e && typeof e.value === "boolean") return e.value;
      if (e && e.detail && typeof e.detail.value === "boolean") {
        return e.detail.value;
      }
      return !!e;
    },

    // 更新产品状态
    updateProductStatus(field, value, options = {}) {
      const querys = {
        id: this.productId,
        [field]: value,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      return this.$go("album/product/update_status", data, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          if (res.code === 0) {
            this.emitProductRefresh();
            if (!options.silent) {
              uni.showToast({
                title: "设置成功",
                icon: "success",
              });
            }
            return res;
          }
          this.rollbackStatus(field);
          return Promise.reject({ ...res, rolledBack: true });
        })
        .catch((err) => {
          console.log("更新产品状态失败:", err);
          if (!err || !err.rolledBack) {
            this.rollbackStatus(field);
          }
          return Promise.reject(err);
        });
    },

    rollbackStatus(field) {
      if (field === "is_top") this.isTop = !this.isTop;
      if (field === "is_hot") this.isHot = !this.isHot;
      if (field === "private_type") this.isPrivate = !this.isPrivate;
    },

    emitProductRefresh() {
      uni.$emit("refreshIndexData");
      uni.$emit("refreshProductManageData");
      uni.$emit("refreshProductDetailsSelfData");
      uni.$emit("refreshClassManageData");
    },

    // 产品分类设置
    handleCategory() {
      uni.navigateTo({
        url: `/pagesOther/classSelect/classSelect?id=${this.productId}`,
      });
    },

    // 产品封面主图设置
    handleCover() {
      const coverParam = this.currentCover
        ? `&cover=${encodeURIComponent(this.currentCover)}`
        : "";
      uni.navigateTo({
        url: `/pagesOther/editCover/editCover?id=${this.productId}&folder_type=2${coverParam}`,
      });
    },

    // 产品内图片排序管理
    handleSort() {
      uni.navigateTo({
        url: `/pagesOther/pictureSort/pictureSort?id=${this.productId}`,
      });
    },

    // 切换为单列模式
    handleSingleColumn() {
      this.updateDisplayMode(1);
    },

    // 切换为双列模式
    handleDoubleColumn() {
      this.updateDisplayMode(2);
    },

    // 更新显示模式
    updateDisplayMode(mode) {
      if (this.picLayout === mode) {
        uni.showToast({
          title: mode === 1 ? "当前已是单列模式" : "当前已是双列模式",
          icon: "none",
        });
        return;
      }
      this.updateProductStatus("pic_layout", mode, { silent: true })
        .then(() => {
          this.picLayout = mode;
          uni.showToast({
            title: mode === 1 ? "已切换为单列模式" : "已切换为双列模式",
            icon: "success",
          });
        })
        .catch((err) => {
          console.log("更新显示模式失败:", err);
        });
    },

    // 删除产品
    handleDelete() {
      uni.showModal({
        title: "提示",
        content: "确定要删除该产品吗？",
        success: (res) => {
          if (res.confirm) {
            this.deleteProduct();
          }
        },
      });
    },

    // 执行删除
    deleteProduct() {
      const querys = {
        fid: this.productId,
        del_type: 1,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      this.$go("album/delete/folder", data, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          uni.showToast({
            title: "删除成功",
            icon: "success",
            duration: 1500,
            success: () => {
              setTimeout(() => {
                uni.$emit("refreshIndexData");
                uni.$emit("refreshProductManageData");
                uni.$emit("refreshClassManageData");
                uni.navigateBack({ delta: 2 });
              }, 1500);
            },
          });
        })
        .catch((err) => {
          console.log("删除产品失败:", err);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.setting-page {
  min-height: 100vh;
  background: #fff;
}

// 主要内容区域
.content {
  padding: 0 30rpx;
  padding-bottom: 60rpx;
}

// 区块
.section {
  margin-bottom: 40rpx;

  .section-title {
    font-size: 28rpx;
    font-weight: 600;
    color: #333333;
    margin-bottom: 20rpx;
    padding-left: 10rpx;
  }
}

// 设置卡片
.setting-card {
  background: #f5f5f5;
  border-radius: 24rpx;
  overflow: hidden;
}

// 设置项
.setting-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx 30rpx;
  border-bottom: 1rpx solid rgba(0, 0, 0, 0.05);

  .item-left {
    display: flex;
    align-items: center;
    flex: 1;

    .item-icon {
      width: 48rpx;
      height: 48rpx;
      margin-right: 24rpx;

      &.delete-icon {
        width: 48rpx;
        height: 48rpx;
      }
    }

    .item-text {
      font-size: 30rpx;
      color: #333333;

      &.delete-text {
        color: #ff4444;
        font-weight: 500;
      }
    }
  }

  .arrow-icon {
    width: 48rpx;
    height: 48rpx;
  }
}

// 分割线
.divider {
  height: 1rpx;
  background: #f5f5f5;
  margin: 0 30rpx;
}
</style>
