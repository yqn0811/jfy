<template>
  <view class="select-style-page">
    <!-- 自定义导航栏 -->
    <view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="navbar-content">
        <view class="navbar-left" @click="handleClose">
          <image
            class="icon-close"
            src="/static/icon/close2.png"
            mode="scaleToFill"
          />
        </view>
        <view class="navbar-title">已选择{{ selectedCount }}项</view>
        <view class="navbar-right"></view>
      </view>
    </view>

    <!-- 图片网格 -->
    <scroll-view
      class="content-scroll"
      scroll-y
      :style="{
        paddingTop: navBarHeight + 'px',
        paddingBottom: bottomBarHeight + 'px',
      }"
    >
      <view class="image-grid">
        <view
          class="grid-item"
          :class="{ disabled: item.disabled }"
          v-for="(item, index) in imageList"
          :key="item.id"
          @click="toggleSelect(index)"
        >
          <image class="item-image" :src="item.image" mode="aspectFill"></image>
          <!-- 禁用遮罩 -->
          <view v-if="item.disabled" class="item-mask"></view>
          <view
            class="item-checkbox"
            :class="{ checked: item.selected, disabled: item.disabled }"
          >
            <image
              v-if="!item.selected"
              class="checkbox-icon"
              src="/static/icon/Frame 1171278273@2x.png"
              mode="scaleToFill"
            />
            <image
              v-else
              class="checkbox-icon"
              src="/static/icon/Frame 1000006316@2x.png"
              mode="scaleToFill"
            />
          </view>
          <view class="item-title">{{ item.title }}</view>
          <!-- 已添加标签 -->
          <view v-if="item.disabled" class="disabled-tag">已添加</view>
        </view>
      </view>
    </scroll-view>

    <!-- 底部操作栏 -->
    <view
      class="bottom-bar"
      :style="{
        height: bottomBarHeight + 'px',
        paddingBottom: bottomBarPaddingBottom + 'px',
      }"
    >
      <view class="bar-left" @click="toggleSelectAll">
        <view class="select-all-checkbox">
          <image
            src="/static/icon/Frame 1171278273@2x.png"
            class="select-all-icon"
            v-if="!isAllSelected"
          ></image>
          <image
            src="/static/icon/Frame 1000006316@2x.png"
            class="select-all-icon"
            v-else
          ></image>
        </view>
        <text class="select-all-text">全选</text>
      </view>
      <view class="bar-center" :class="mode" @click="handleConfirm">
        <view class="confirm-btn">
          <image
            class="confirm-icon"
            src="/static/icon/checkbox-circle.png"
            mode="scaleToFill"
          />
          <text class="confirm-text">选好了</text>
        </view>
      </view>
      <view v-if="mode !== 'add'" class="bar-right" @click="handleDownload">
        <view class="download-btn">
          <image
            class="download-icon"
            src="/static/icon/uoload-icon.png"
            mode="scaleToFill"
          />
          <text class="download-text">下载</text>
        </view>
      </view>
    </view>

    <!-- 确认弹窗 -->
    <view
      class="confirm-popup"
      v-if="showConfirmPopup"
      @click="closeConfirmPopup"
    >
      <view class="popup-content" @click.stop>
        <view class="popup-title">选择了{{ selectedCount }}张图片</view>
        <view class="popup-subtitle">生成选品清单</view>
        <view class="popup-buttons">
          <view class="cancel-btn" @click="closeConfirmPopup">取消</view>
          <view class="confirm-btn-popup" @click="confirmSelection">确定</view>
        </view>
      </view>
    </view>
    <!-- 转发确认弹窗 -->
    <view
      class="confirm-popup"
      v-if="showTranspondPopup"
      @click="closeTranspondPopup"
    >
      <view class="popup-content" @click.stop>
        <view class="popup-title">选款</view>
        <view class="popup-subtitle">需要将选品单转发给厂家吗?</view>
        <view class="popup-buttons">
          <view class="cancel-btn" @click="closeTranspondPopup">取消</view>
          <view class="confirm-btn-popup" @click="confirmTranspond">转发</view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      imageList: [],
      statusBarHeight: 0, // 状态栏高度
      navBarHeight: 0, // 导航栏总高度
      safeAreaBottom: 0, // 底部安全距离
      bottomBarPaddingBottom: 12, // 底部操作栏额外留白
      bottomBarHeight: 0, // 底部操作栏高度
      showConfirmPopup: false, // 确认弹窗显示状态
      showTranspondPopup: false,
      productId: "",
      uid: "",
      styleId: "",
      mode: "",
      existingProductIds: [], // 已存在的产品图片ID列表
    };
  },
  computed: {
    selectedCount() {
      return this.imageList.filter((item) => item.selected && !item.disabled)
        .length;
    },
    isAllSelected() {
      // 只考虑未禁用的项
      const enabledItems = this.imageList.filter((item) => !item.disabled);
      return (
        enabledItems.length > 0 && enabledItems.every((item) => item.selected)
      );
    },
  },
  onLoad(options) {
    this.getSystemInfo();
    if (options.id) {
      this.productId = options.id;
      this.uid = options.uid;
      this.mode = options.mode;
      this.styleId = options.styleId;

      // 如果是 add 模式，接收已存在的产品列表
      if (this.mode === "add") {
        const eventChannel = this.getOpenerEventChannel();
        eventChannel.on("existingProducts", (data) => {
          if (data && data.productList) {
            // 提取所有已存在产品的图片ID
            this.existingProductIds = [];
            data.productList.forEach((product) => {
              this.existingProductIds.push(String(product.id));
            });
            console.log("已存在的图片ID列表:", this.existingProductIds);
          }
          this.loadProductDetail();
        });
      } else {
        this.loadProductDetail();
      }
    }
  },
  methods: {
    async loadProductDetail() {
      try {
        if (this.$go && this.productId) {
          let data = {
            target_user_id: this.uid,
            product_id: this.productId,
            timestamp: Date.now(),
          };
          const url = "user/home/products/detail";
          const res = await this.$go(url, data, "get", { show_err: true });
          const d = res && res.data ? res.data : {};

          // 映射图片列表
          const picList = Array.isArray(d.pic_list) ? d.pic_list : [];
          this.imageList = picList.map((item) => {
            // 如果是 add 模式，检查该图片是否已存在
            let isDisabled = false;
            if (
              this.mode === "add" &&
              this.existingProductIds.includes(String(item.id))
            ) {
              isDisabled = true;
            }

            return {
              ...item,
              id: item.id,
              image: item.imgurl,
              title: item.pic_name,
              selected: isDisabled,
              disabled: isDisabled,
            };
          });

          console.log("加载的图片列表:", this.imageList);
        }
      } catch (e) {
        console.error(e);
      }
    },
    // 获取系统信息
    getSystemInfo() {
      const systemInfo = this.$base.getSystemInfoCompat();
      // 状态栏高度
      this.statusBarHeight = systemInfo.statusBarHeight || 0;
      // 导航栏内容高度（44px转rpx）
      const navContentHeight = 44;
      // 导航栏总高度 = 状态栏高度 + 导航栏内容高度
      this.navBarHeight = this.statusBarHeight + navContentHeight;

      this.safeAreaBottom = systemInfo.safeAreaInsets?.bottom || 0;
      this.bottomBarPaddingBottom = this.safeAreaBottom + 12;
      this.bottomBarHeight = 70 + this.bottomBarPaddingBottom;

      console.log("系统信息:", {
        statusBarHeight: this.statusBarHeight,
        navBarHeight: this.navBarHeight,
        safeAreaBottom: this.safeAreaBottom,
        bottomBarPaddingBottom: this.bottomBarPaddingBottom,
        bottomBarHeight: this.bottomBarHeight,
      });
    },

    // 关闭页面
    handleClose() {
      uni.navigateBack();
    },

    // 更多操作
    handleMore() {
      uni.showActionSheet({
        itemList: ["删除", "移动到", "复制到"],
        success: (res) => {
          console.log("选中了第" + (res.tapIndex + 1) + "个按钮");
        },
      });
    },

    // 完成选择
    handleComplete() {
      const selectedItems = this.imageList.filter(
        (item) => item.selected && !item.disabled,
      );
      if (selectedItems.length === 0) {
        uni.showToast({
          title: "请至少选择一项",
          icon: "none",
        });
        return;
      }
      uni.navigateBack({
        success: () => {
          // 通过事件总线或其他方式传递选中的数据
          uni.$emit("styleSelected", selectedItems);
        },
      });
    },

    // 切换选中状态
    toggleSelect(index) {
      const item = this.imageList[index];

      // 如果该项被禁用，不允许选择
      if (item.disabled) {
        uni.showToast({
          title: "该图片已在选款单中",
          icon: "none",
          duration: 2000,
        });
        return;
      }

      this.imageList[index].selected = !this.imageList[index].selected;
      // 强制更新视图
      this.$forceUpdate();
    },

    // 全选/取消全选
    toggleSelectAll() {
      if (this.isAllSelected) {
        // 如果当前全部选中，则取消全部选中（只取消未禁用的）
        this.imageList.forEach((item) => {
          if (!item.disabled) {
            item.selected = false;
          }
        });
      } else {
        // 如果当前不是全部选中，则全部选中（只选中未禁用的）
        this.imageList.forEach((item) => {
          if (!item.disabled) {
            item.selected = true;
          }
        });
      }
      // 强制更新视图
      this.$forceUpdate();
    },

    // 确认选择
    handleConfirm() {
      const selectedItems = this.imageList.filter(
        (item) => item.selected && !item.disabled,
      );
      if (selectedItems.length === 0) {
        uni.showToast({
          title: "请至少选择一项",
          icon: "none",
        });
        return;
      }
      if (this.mode === "add") {
        this.addImages();
        return;
      }
      // 显示确认弹窗
      this.showConfirmPopup = true;
    },
    // 添加选款花色图
    async addImages() {
      const selectedItems = this.imageList.filter(
        (item) => item.selected && !item.disabled,
      );
      const pic_ids = selectedItems.map((res) => res.id);
      const params = {
        selection_id: this.styleId,
        pic_ids: pic_ids,
        product_id: this.productId,
      };
      try {
        const res = await this.$go(
          "album/selection/add_images",
          params,
          "post",
          {
            show_err: true,
          },
        );
        uni.hideLoading();

        if (res.code === 0) {
          uni.showToast({
            title: "添加成功",
            icon: "success",
            duration: 1500,
          });

          // 通知上一页刷新
          const eventChannel = this.getOpenerEventChannel();
          eventChannel.emit("acceptDataFromOpenedPage", { refresh: true });
          setTimeout(() => {
            uni.navigateBack();
          }, 1500);
        }
      } catch (e) {
        uni.hideLoading();
        console.error("添加失败:", e);
      }
    },

    // 关闭确认弹窗
    closeConfirmPopup() {
      this.showConfirmPopup = false;
    },
    closeTranspondPopup() {
      this.showTranspondPopup = false;
    },

    // 确认生成选品清单
    async confirmSelection() {
      const selectedItems = this.imageList.filter(
        (item) => item.selected && !item.disabled,
      );
      console.log(selectedItems);
      const pic_ids = selectedItems.map((res) => res.id);

      this.showConfirmPopup = false;
      const data = {
        factory_uid: this.uid,
        pic_ids: pic_ids.join(","),
        product_id: this.productId,
      };

      const res = await this.$go("album/selection/create", data, "post", {
        show_err: true,
      });
      if (res.code === 0) {
        this.showTranspondPopup = true;
        this.styleId = res.data.id;
      }
    },
    // 转发确认事件
    confirmTranspond() {
      this.showTranspondPopup = false;
      uni.navigateTo({
        url:
          "/pagesOther/styleResult/styleResult?id=" +
          this.styleId +
          "&uid=" +
          this.uid,
      });
    },

    // 下载
    handleDownload() {
      const selectedItems = this.imageList.filter(
        (item) => item.selected && !item.disabled,
      );
      if (selectedItems.length === 0) {
        uni.showToast({
          title: "请先选择要下载的图片",
          icon: "none",
        });
        return;
      }
      uni.showLoading({
        title: "下载中...",
      });
      // 这里实现下载逻辑
      setTimeout(() => {
        uni.hideLoading();
        uni.showToast({
          title: "下载成功",
          icon: "success",
        });
      }, 1500);
    },
  },
};
</script>

<style lang="scss" scoped>
.select-style-page {
  width: 100%;
  height: 100%;
  background: #fff;
}

/* 自定义导航栏 */
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  background-color: #ffffff;
  border-bottom: 1rpx solid #f0f0f0;
  z-index: 999;
  box-sizing: border-box;

  .navbar-content {
    height: 88rpx;
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 0 24rpx;
  }

  .navbar-left {
    .icon-close {
      width: 32rpx;
      height: 32rpx;
    }
  }

  .navbar-title {
    font-size: 32rpx;
    color: #333333;
    font-weight: 500;
  }

  .navbar-right {
    width: 80rpx;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 24rpx;

    .icon-more {
      font-size: 48rpx;
      color: #333333;
      letter-spacing: 2rpx;
    }

    .icon-complete {
      width: 48rpx;
      height: 48rpx;
      display: flex;
      align-items: center;
      justify-content: center;

      .complete-circle {
        width: 40rpx;
        height: 40rpx;
        border: 3rpx solid #333333;
        border-radius: 50%;
      }
    }
  }
}

/* 内容滚动区域 */
.content-scroll {
  flex: 1;
  width: 100%;
  box-sizing: border-box;
}

/* 图片网格 */
.image-grid {
  padding: 20rpx;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;

  .grid-item {
    position: relative;
    width: 100%;
    height: 470rpx;
    aspect-ratio: 1;
    border-radius: 16rpx;
    overflow: hidden;
    background-color: #ffffff;

    &.disabled {
      opacity: 0.6;
    }

    .item-image {
      width: 100%;
      height: 100%;
      display: block;
    }

    .item-mask {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.3);
    }

    .item-checkbox {
      position: absolute;
      top: 20rpx;
      left: 20rpx;
      width: 48rpx;
      height: 48rpx;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;

      &.disabled {
        opacity: 0.5;
      }

      .checkbox-icon {
        width: 48rpx;
        height: 48rpx;
      }
    }

    .item-title {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 16rpx 20rpx;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
      color: #ffffff;
      font-size: 28rpx;
    }

    .disabled-tag {
      position: absolute;
      top: 20rpx;
      right: 20rpx;
      padding: 8rpx 16rpx;
      background-color: rgba(0, 0, 0, 0.6);
      color: #ffffff;
      font-size: 24rpx;
      border-radius: 8rpx;
    }
  }
}

/* 底部操作栏 */
.bottom-bar {
  position: fixed;
  bottom: 0;
  width: 100%;
  height: 70px;
  background-color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  padding: 0 24rpx;
  padding-bottom: calc(env(safe-area-inset-bottom) + 24rpx);
  box-sizing: border-box;
  border-top: 1rpx solid #f0f0f0;

  .bar-left {
    display: flex;
    align-items: center;
    gap: 16rpx;

    .select-all-checkbox {
      width: 48rpx;
      height: 48rpx;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      .select-all-icon {
        width: 48rpx;
        height: 48rpx;
      }
    }

    .select-all-text {
      font-size: 28rpx;
      color: #333333;
    }
  }

  .bar-center {
    display: flex;
    justify-content: center;
    padding: 0 20rpx;
    &.app {
      flex: 1;
    }

    .confirm-btn {
      min-width: 180rpx;
      height: 80rpx;
      background-color: #ffffff;
      border: 2rpx solid #e0e0e0;
      border-radius: 40rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12rpx;
      padding: 0 40rpx;

      .confirm-icon {
        width: 48rpx;
        height: 48rpx;
      }

      .confirm-text {
        font-size: 28rpx;
        color: #333333;
      }
    }
  }

  .bar-right {
    .download-btn {
      min-width: 160rpx;
      height: 80rpx;
      background-color: #ffd700;
      border-radius: 40rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12rpx;
      padding: 0 32rpx;

      .download-icon {
        width: 48rpx;
        height: 48rpx;
      }

      .download-text {
        font-size: 28rpx;
        color: #333333;
        font-weight: 500;
      }
    }
  }
}

/* 确认弹窗 */
.confirm-popup {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;

  .popup-content {
    width: 686rpx;
    background-color: #ffffff;
    border-radius: 32rpx;
    padding: 60rpx 40rpx 40rpx;
    margin: 0 40rpx;
    box-sizing: border-box;

    .popup-title {
      font-size: 36rpx;
      color: #333333;
      font-weight: bold;
      text-align: center;
      margin-bottom: 20rpx;
    }

    .popup-subtitle {
      font-size: 28rpx;
      color: #333;
      text-align: center;
      margin-bottom: 60rpx;
    }

    .popup-buttons {
      display: flex;
      gap: 24rpx;

      .cancel-btn {
        flex: 1;
        height: 88rpx;
        background-color: #f5f5f5;
        border-radius: 44rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32rpx;
        color: #333333;
      }

      .confirm-btn-popup {
        flex: 1;
        height: 88rpx;
        background-color: #ffd700;
        border-radius: 44rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32rpx;
        color: #333333;
        font-weight: 500;
      }
    }
  }
}
</style>
