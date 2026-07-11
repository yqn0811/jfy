<template>
  <u-popup
    :show="show"
    mode="bottom"
    :round="20"
    :safe-area-inset-bottom="false"
    @close="handleClose"
  >
    <view class="create-popup">
      <view class="popup-title">{{ title }}</view>

      <!-- 新建产品卡片 -->
      <view class="create-card product-card" @click="handleSelect('product')">
        <image
          class="bg-image"
          src="/static/icon/bg-image.png"
          mode="scaleToFill"
        />
        <view class="card-content">
          <view class="card-text">
            <text class="card-title">{{ productTitle }}</text>
            <text class="card-subtitle">{{ productSubtitle }}</text>
          </view>
          <view class="card-icon-wrapper">
            <view class="card-icon product-icon">
              <image class="icon-img" :src="productIcon" mode="aspectFit"></image>
            </view>
          </view>
        </view>
      </view>

      <!-- 新建分类卡片 -->
      <view class="create-card category-card" @click="handleSelect('category')">
        <image
          class="bg-image"
          src="/static/icon/bg-image.png"
          mode="scaleToFill"
        />
        <view class="card-content">
          <view class="card-text">
            <text class="card-title">{{ categoryTitle }}</text>
            <text class="card-subtitle">{{ categorySubtitle }}</text>
          </view>
          <view class="card-icon-wrapper">
            <view class="card-icon category-icon">
              <image class="icon-img" :src="categoryIcon" mode="aspectFit"></image>
            </view>
          </view>
        </view>
      </view>
    </view>
  </u-popup>
</template>

<script>
export default {
  name: "CreatePopup",
  props: {
    // 控制弹窗显示
    show: {
      type: Boolean,
      default: false,
    },
	fromPage:{
		type: String,
		default: "",
	},
    // 弹窗标题
    title: {
      type: String,
      default: "立即新建",
    },
    // 产品选项标题
    productTitle: {
      type: String,
      default: "新建产品",
    },
    // 产品选项副标题
    productSubtitle: {
      type: String,
      default: "用于一款产品的展示",
    },
    // 产品图标
    productIcon: {
      type: String,
      default: "/static/icon/Frame 1000004377@2x.png",
    },
    // 分类选项标题
    categoryTitle: {
      type: String,
      default: "新建分类",
    },
    // 分类选项副标题
    categorySubtitle: {
      type: String,
      default: "用于产品的分类展示",
    },
    // 分类图标
    categoryIcon: {
      type: String,
      default: "/static/icon/Frame 1171279072@2x.png",
    },
  },
  methods: {
    handleClose() {
      this.$emit("update:show", false);
      this.$emit("close");
    },
    handleSelect(type) {
      this.$emit("update:show", false);
      if (type === "product") {
        // 跳转到新建产品页面
        uni.navigateTo({
          url: "/pagesOther/addProduct/addProduct?type=product&fromPage="+this.fromPage,
        });
      } else if (type === "category") {
        // 跳转到新建分类页面
        uni.navigateTo({
          url: "/pagesOther/addClass/addClass?type=category&fromPage="+this.fromPage,
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.create-popup {
  padding: 40rpx 30rpx 60rpx;
  background-color: #ffffff;
  border-radius: 32rpx;
}

.popup-title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333;
  text-align: center;
  margin-bottom: 40rpx;
}

.create-card {
  position: relative;
  height: 180rpx;
  border-radius: 24rpx;
  margin-bottom: 24rpx;
  overflow: hidden;
  padding: 30rpx;
  box-sizing: border-box;
  transition: transform 0.2s;

  &:active {
    transform: scale(0.98);
  }

  .bg-image {
    position: absolute;
    right: -10rpx;
    bottom: -10rpx;
    width: 180rpx;
    height: 180rpx;
  }
}

.product-card {
  background: linear-gradient(
    235deg,
    rgba(255, 227, 41, 0.6) 0%,
    rgba(255, 227, 41, 0.1) 100%
  );
}

.category-card {
  background: linear-gradient(
    235deg,
    #dae9fb 0%,
    rgba(218, 233, 251, 0.1) 100%
  );
}

.category-card .card-bg-plus {
  color: rgba(33, 150, 243, 0.15);
}

.card-content {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 100%;
}

.card-text {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  flex: 1;
}

.card-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
  display: block;
}

.card-subtitle {
  font-size: 24rpx;
  color: #666;
  display: block;
}

.card-icon-wrapper {
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-icon {
  width: 112rpx;
  height: 112rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-icon .icon-img {
  width: 100%;
  height: 100%;
}
</style>
