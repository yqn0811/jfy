<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="header" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
        <view :style="{ height: statusBarHeight + 'px' }"></view>
        <view
          class="nav-bar-content"
          :style="{ height: navigationBarHeight + 'px' }"
        >
          <view class="left" @click="back">
            <image
              class="backIcon"
              src="/static/icon/back.png"
              mode="aspectFit"
            ></image>
          </view>
          <view class="title">案例中心</view>
          <view class="right">
            <image
              class="menuIcon"
              src="/static/icon/menu.png"
              mode="aspectFit"
            ></image>
            <image
              class="searchIcon"
              src="/static/icon/search.png"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <!-- 分类标签 - 横向滚动 -->
      <scroll-view class="category-scroll" scroll-x>
        <view class="category-list">
          <view
            class="category-item"
            :class="{ active: currentCategory === item.value }"
            v-for="(item, index) in categories"
            :key="index"
            @click="changeCategory(item.value)"
          >
            <text>{{ item.label }}</text>
          </view>
        </view>
      </scroll-view>

      <!-- 案例列表 -->
      <view class="case-list">
        <view class="empty-box" v-if="caseList.length == 0">
          <view class="tip">暂无案例数据</view>
          <image class="emptyIcon" src="/static/icon/empty.png" mode=""></image>
        </view>
        <view
          class="case-item"
          v-for="(item, index) in caseList"
          :key="index"
          @click="toCaseDetail(item)"
        >
          <view class="case-header">
            <view class="case-left">
              <image
                class="case-avatar"
                :src="item.new_thumb || '/static/image/pic.png'"
                mode="aspectFill"
              ></image>
              <view class="case-info">
                <view class="case-name">{{ item.folder_name }}</view>
                <text class="case-tag">{{ item.category }}</text>
              </view>
            </view>
            <image
              class="arrow-icon"
              src="/static/icon/Chevron Right@2x(1).png"
              mode="aspectFit"
            ></image>
          </view>
          <scroll-view class="case-images" scroll-x show-scrollbar="false">
            <view class="images-wrapper">
              <image
                class="case-img"
                v-for="(img, imgIndex) in item.images"
                :key="imgIndex"
                :src="img || '/static/image/pic.png'"
                mode="aspectFill"
                @click.stop="previewImage(item.images, imgIndex)"
              ></image>
            </view>
          </scroll-view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: "",
      totalHeight: "",
      navigationBarHeight: 44,
      page: 1,
      last_page: 1,
      currentCategory: 0, // 当前选中的分类
      categories: [
        { label: "全部", value: 0 },
        { label: "摄影行业", value: 1 },
        { label: "设计行业", value: 2 },
        { label: "化妆行业", value: 3 },
      ],
      caseList: [
        {
          id: 1,
          name: "大久工业设计",
          category: "设计行业",
          avatar: "/static/image/pic.png",
          images: [
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
          ],
        },
        {
          id: 2,
          name: "一栀摄影",
          category: "摄影行业",
          avatar: "/static/image/pic.png",
          images: [
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
          ],
        },
        {
          id: 3,
          name: "一栀摄影",
          category: "摄影行业",
          avatar: "/static/image/pic.png",
          images: [
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
          ],
        },
        {
          id: 4,
          name: "一栀摄影",
          category: "摄影行业",
          avatar: "/static/image/pic.png",
          images: [
            "/static/image/pic.png",
            "/static/image/pic.png",
            "/static/image/pic.png",
          ],
        },
      ],
    };
  },
  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
  },
  onShow() {
    this.page = 1;
    this.getList();
  },
  onReachBottom() {
    if (this.page < this.last_page) {
      this.page++;
      this.getList();
    }
  },
  methods: {
    // 返回上一页
    back() {
      uni.navigateBack();
    },

    // 切换分类
    changeCategory(value) {
      this.currentCategory = value;
      this.page = 1;
      this.getList();
    },

    // 跳转到案例详情
    toCaseDetail(item) {
      uni.navigateTo({
        url: `/pagesOther/caseDetail/caseDetail?id=${item.id}`,
      });
    },

    // 预览图片
    previewImage(images, current) {
      uni.previewImage({
        urls: images,
        current: current,
      });
    },

    // 获取列表数据
    getList() {
      const querys = {
        page: this.page,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
      this.$go("common/example/folder", data, "get", {
        show_err: true,
      }).then((res) => {
        const list = (res.data.lists.data || []).map((item) => ({
          ...item,
          category: item.category || "示例案例",
          images:
            Array.isArray(item.images) && item.images.length
              ? item.images
              : item.new_thumb
                ? [item.new_thumb]
                : [],
        }));
        if (this.page == 1) {
          this.caseList = list;
        } else {
          this.caseList = this.caseList.concat(list);
        }
        this.last_page = res.data.lists.last_page || 1;
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  box-sizing: border-box;
  background: linear-gradient(
    180deg,
    rgba(255, 227, 41, 0.3) 0%,
    rgba(255, 227, 41, 0) 750rpx
  );
}

.header {
  width: 100%;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;

  z-index: 100;

  .custom-nav-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1;

    .nav-bar-content {
      padding: 0 30rpx;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;

      .left {
        display: flex;
        align-items: center;

        .backIcon {
          width: 40rpx;
          height: 40rpx;
        }
      }

      .title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        font-weight: 600;
        font-size: 32rpx;
        color: #333;
      }

      .right {
        display: flex;
        align-items: center;
        gap: 24rpx;

        .menuIcon,
        .searchIcon {
          width: 40rpx;
          height: 40rpx;
        }
      }
    }
  }
}

.content {
  min-height: 100vh;

  /* 分类标签滚动区域 */
  .category-scroll {
    white-space: nowrap;
    padding: 30rpx 0;
  }

  .category-list {
    display: inline-flex;
    padding: 0 30rpx;
    gap: 20rpx;
  }

  .category-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16rpx 40rpx;
    background-color: #ffffff;
    border-radius: 50rpx;
    white-space: nowrap;

    text {
      font-size: 28rpx;
      color: #666;
      font-weight: 400;
    }

    &.active {
      background-color: #333;

      text {
        color: #fff;
        font-weight: 500;
      }
    }
  }

  /* 案例列表 */
  .case-list {
    padding: 0 30rpx 30rpx;
  }

  .empty-box {
    padding-top: 200rpx;
    text-align: center;

    .tip {
      font-size: 28rpx;
      color: #999;
    }

    .emptyIcon {
      width: 300rpx;
      height: 300rpx;
      display: block;
      margin: 40rpx auto 0;
    }
  }

  .case-item {
    background-color: #fff;
    border-radius: 24rpx;
    padding: 30rpx;
    margin-bottom: 24rpx;
  }

  .case-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24rpx;

    .case-left {
      display: flex;
      align-items: center;
      flex: 1;

      .case-avatar {
        width: 96rpx;
        height: 96rpx;
        border-radius: 16rpx;
        margin-right: 20rpx;
      }

      .case-info {
        flex: 1;

        .case-name {
          font-size: 32rpx;
          font-weight: 600;
          color: #333;
          margin-bottom: 8rpx;
        }

        .case-tag {
          display: inline-block;
          padding: 6rpx 20rpx;
          background-color: #333;
          color: #fff;
          font-size: 20rpx;
          border-radius: 20rpx;
        }
      }
    }

    .arrow-icon {
      width: 48rpx;
      height: 48rpx;
    }
  }

  .case-images {
    white-space: nowrap;
    width: 100%;

    .images-wrapper {
      display: inline-flex;
      gap: 16rpx;

      .case-img {
        width: 200rpx;
        height: 200rpx;
        border-radius: 16rpx;
        flex-shrink: 0;
      }
    }
  }
}
</style>
