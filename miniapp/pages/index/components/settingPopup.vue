<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :round="16"
    :safe-area-inset-bottom="false"
    @close="close"
  >
    <view class="root">
      <view class="head">
        <text class="title">设置</text>
      </view>

      <view class="content">
        <!-- 主页管理 -->
        <view class="section">
          <text class="section-title">主页管理</text>

          <view class="row" @tap="editHomeInfo">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/24＊24@2x(9).png"
                mode="scaleToFill"
              />
              <text class="row-text">编辑主页资料</text>
            </view>
          </view>

          <view class="row" @tap="homeSettings">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/setting.png"
                mode="scaleToFill"
              />
              <text class="row-text">主页偏好设置</text>
            </view>
          </view>
        </view>

        <!-- 主页产品 -->
        <view class="section">
          <text class="section-title">主页产品</text>

          <view class="row" @tap="addProduct">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/plus-rec@2x.png"
                mode="scaleToFill"
              />
              <text class="row-text">新增产品</text>
            </view>
          </view>

          <view class="row" @tap="productSort">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/put-sort-icon.png"
                mode="scaleToFill"
              />
              <text class="row-text">产品排序</text>
            </view>
            <text class="right-hint">调整产品展示顺序</text>
          </view>

          <view class="row" @tap="toggleDisplayMode">
            <view class="left">
              <image
                class="icon"
                :src="
                  displayMode === 'list'
                    ? '/static/icon/Frame@2x(23).png'
                    : '/static/icon/Frame@2x(21).png'
                "
                mode="scaleToFill"
              />
              <text class="row-text">{{ displayModeText }}</text>
            </view>
          </view>
        </view>

        <!-- 主页分类 -->
        <view class="section">
          <text class="section-title">主页分类</text>

          <view class="row" @tap="addCategory">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/plus-rec@2x.png"
                mode="scaleToFill"
              />
              <text class="row-text">新增分类</text>
            </view>
          </view>

          <view class="row" @tap="categorySort">
            <view class="left">
              <image
                class="icon"
                src="/static/icon/Frame@2x(24).png"
                mode="scaleToFill"
              />
              <text class="row-text">分类排序</text>
            </view>
            <text class="right-hint">调整分类展示顺序</text>
          </view>
        </view>
      </view>

      <view class="cancel-wrap">
        <view class="cancel-btn" @tap="close">取消</view>
      </view>
    </view>
  </u-popup>
</template>

<script>
export default {
  name: "SettingPopup",
  props: {
    visible: { type: Boolean, default: false },
    columns: { type: [Number, String], default: 2 },
  },
  emits: ["update:visible", "toggleDisplayMode"],
  data() {
    return {
      displayMode: "grid", // grid: 网格展示, list: 单列展示
    };
  },
  computed: {
    displayModeText() {
      return this.displayMode === "grid" ? "切换为单列展示" : "切换为双列展示";
    },
  },
  watch: {
    visible(val) {
      if (val) this.syncDisplayMode();
    },
    columns() {
      this.syncDisplayMode();
    },
  },
  methods: {
    close() {
      this.$emit("update:visible", false);
    },

    // 编辑主页资料
    editHomeInfo() {
      uni.showToast({
        title: "跳转到编辑主页资料",
        icon: "none",
      });
      // 跳转到编辑主页资料页面
      uni.navigateTo({
        url: "/pagesOther/setInfo/setInfo?fromPage=index",
      });
      this.close();
    },

    // 主页偏好设置
    homeSettings() {
      uni.navigateTo({
        url: "/pagesOther/permissionSetting/permissionSetting",
      });
      this.close();
    },

    // 新增产品
    addProduct() {
      uni.navigateTo({ url: "/pagesOther/addProduct/addProduct" });
      this.close();
    },

    // 从相册选择
    chooseFromAlbum() {
      uni.chooseImage({
        count: 9,
        sizeType: ["original", "compressed"],
        sourceType: ["album"],
        success: (res) => {
          uni.showToast({
            title: `选择了${res.tempFilePaths.length}张图片`,
            icon: "success",
          });
          // 跳转到产品编辑页面
          uni.navigateTo({
            url:
              "/pagesOther/addProduct/addProduct?images=" +
              encodeURIComponent(JSON.stringify(res.tempFilePaths)),
          });
        },
      });
    },

    // 拍照上传
    takePhoto() {
      uni.chooseImage({
        count: 1,
        sizeType: ["original", "compressed"],
        sourceType: ["camera"],
        success: (res) => {
          uni.showToast({
            title: "拍照成功",
            icon: "success",
          });
          // 跳转到产品编辑页面
          uni.navigateTo({
            url:
              "/pagesOther/addProduct/addProduct?images=" +
              encodeURIComponent(JSON.stringify(res.tempFilePaths)),
          });
        },
      });
    },

    // 产品排序
    productSort() {
      uni.showToast({
        title: "跳转到产品排序页面",
        icon: "none",
      });
      // 跳转到产品排序页面
      uni.navigateTo({
        url: "/pagesOther/productSort/productSort?fromPage=index",
      });
      this.close();
    },

    // 切换展示模式
    toggleDisplayMode() {
      this.displayMode = this.displayMode === "grid" ? "list" : "grid";
      const modeText = this.displayMode === "grid" ? "双列展示" : "单列展示";
      uni.setStorageSync("displayMode", this.displayMode);
      uni.showToast({
        title: `已切换为${modeText}`,
        icon: "success",
      });
      this.$emit("toggleDisplayMode", this.displayMode === "grid" ? 2 : 1);
    },
    syncDisplayMode() {
      const savedMode = uni.getStorageSync("displayMode");
      if (savedMode === "grid" || savedMode === "list") {
        this.displayMode = savedMode;
        return;
      }
      this.displayMode = Number(this.columns) === 1 ? "list" : "grid";
    },

    // 新增分类
    addCategory() {
      uni.navigateTo({ url: "/pagesOther/addClass/addClass?fromPage=index&next=addProduct" });
      this.close();
    },

    // 分类排序
    categorySort() {
      uni.showToast({
        title: "跳转到分类排序页面",
        icon: "none",
      });
      // 跳转到分类排序页面
      uni.navigateTo({
        url: "/pagesOther/classSort/classSort?fromPage=index",
      });
      this.close();
    },
  },

  mounted() {
    this.syncDisplayMode();
  },
};
</script>

<style scoped lang="scss">
.root {
  padding-bottom: 20rpx;
  background-color: #ffffff;
}

.head {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 32rpx 20rpx 20rpx;
}

.title {
  font-size: 36rpx;
  color: #333333;
  font-weight: 600;
}

.content {
  padding: 0 32rpx;
}

.section {
  margin-bottom: 60rpx;

  &:last-child {
    margin-bottom: 40rpx;
  }
}

.section-title {
  font-size: 32rpx;
  color: #333333;
  font-weight: 500;
  margin-bottom: 32rpx;
  display: block;
}

.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }
}

.left {
  display: flex;
  align-items: center;
  gap: 24rpx;
  flex: 1;
}

.icon {
  width: 48rpx;
  height: 48rpx;
  opacity: 0.6;
}

.row-text {
  font-size: 32rpx;
  color: #333333;
  font-weight: 400;
}

.right-hint {
  font-size: 28rpx;
  color: #999999;
  margin-left: 16rpx;
}

.cancel-wrap {
  padding: 32rpx;
  padding-top: 20rpx;
}

.cancel-btn {
  height: 96rpx;
  background: #ffffff;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2rpx solid #f0f0f0;
  font-weight: 500;
  font-size: 32rpx;
  color: #333333;
}
</style>
