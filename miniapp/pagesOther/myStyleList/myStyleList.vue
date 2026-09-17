<template>
  <view class="page">
    <view class="header" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
        <view :style="{ height: statusBarHeight + 'px' }"></view>
        <view
          class="nav-bar-content"
          :style="{ height: navigationBarHeight + 'px' }"
        >
          <view class="left" @click="back">
            <image
              class="back-icon"
              src="/static/icon/back.png"
              mode="aspectFit"
            ></image>
          </view>
          <view class="title">{{ navTitle }}</view>
          <view class="right"></view>
        </view>
      </view>
    </view>

    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <scroll-view
        class="list-container"
        :style="{ height: listContainerHeight + 'px' }"
        scroll-y
        refresher-enabled
        :refresher-triggered="refreshing"
        @refresherrefresh="onRefresh"
        @scrolltolower="onLoadMore"
        :lower-threshold="100"
      >
        <view class="empty-box" v-if="styleList.length === 0 && !loading">
          <image
            class="empty-icon"
            src="/static/icon/empty.png"
            mode="aspectFit"
          ></image>
          <view class="empty-text">暂无选款单</view>
        </view>

        <view class="style-list" v-else>
          <view
            class="style-item"
            v-for="(item, index) in styleList"
            :key="index"
            @click="toStyleDetail(item)"
          >
            <view class="style-header">
              <view class="header-left">
                <image
                  v-if="fromPage !== 'my'"
                  class="item-avatar"
                  :src="
                    item.customer && item.customer.avatar
                      ? item.customer.avatar
                      : '/static/image/pic.png'
                  "
                  mode="aspectFill"
                />
                <view class="item-title-block">
                  <view class="item-title">{{ item.title }}</view>
                  <view
                    v-if="fromPage !== 'my' && item.customer && item.customer.nickname"
                    class="item-customer"
                  >
                    {{ item.customer.nickname }}
                  </view>
                  <view class="item-product" v-if="item.product_name">{{
                    item.product_name
                  }}</view>
                </view>
              </view>
              <view class="item-time">{{ item.create_time }}</view>
            </view>

            <view class="item-meta">
              <text class="meta-tag" v-if="item.product_count"
                >已选 {{ item.product_count }} 张</text
              >
              <text class="meta-tag" v-if="item.factory && item.factory.nickname"
                >厂家 {{ item.factory.nickname }}</text
              >
            </view>

            <view class="item-images">
              <view
                class="image-box"
                v-for="(img, imgIndex) in item.images"
                :key="imgIndex"
              >
                <image
                  class="item-img"
                  :src="img.url || '/static/image/pic.png'"
                  mode="aspectFill"
                ></image>
                <view class="image-label">{{
                  img.label || `预览${imgIndex + 1}`
                }}</view>
              </view>
            </view>
          </view>

          <view class="load-more" v-if="styleList.length > 0">
            <text v-if="loading">加载中...</text>
            <text v-else-if="page >= last_page">没有更多了</text>
            <text v-else>上拉加载更多</text>
          </view>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,
      navTitle: "我的选款单",
      page: 1,
      last_page: 1,
      styleList: [],
      loading: false,
      refreshing: false,
      listContainerHeight: 0,
      fromPage: "",
    };
  },

  onLoad(options) {
    uni.$on("refreshMyStyleListData", this.handleRefreshData);
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.calculateListHeight();
    const fromPage = options.fromPage;
    if (fromPage) {
      this.navTitle = fromPage === "my" ? "我的选款单" : "客户的选款单";
      this.fromPage = fromPage;
    }
    this.getList();
  },

  onReady() {
    this.calculateListHeight();
  },

  onUnload() {
    uni.$off("refreshMyStyleListData", this.handleRefreshData);
  },

  methods: {
    handleRefreshData() {
      this.page = 1;
      this.styleList = [];
      this.getList();
    },

    back() {
      uni.navigateBack();
    },

    calculateListHeight() {
      this.$nextTick(() => {
        const systemInfo = this.$base.getSystemInfoCompat();
        const windowHeight = systemInfo.windowHeight;
        const navHeight = this.totalHeight;
        this.listContainerHeight = windowHeight - navHeight;
      });
    },

    onRefresh() {
      this.refreshing = true;
      this.page = 1;
      this.styleList = [];
      this.getList().finally(() => {
        this.refreshing = false;
      });
    },

    onLoadMore() {
      if (this.loading || this.page >= this.last_page) {
        return;
      }
      this.page++;
      this.getList();
    },

    buildPreviewImages(item) {
      const images = [];
      const previewList =
        item.selected_preview &&
        Array.isArray(item.selected_preview) &&
        item.selected_preview.length
          ? item.selected_preview
          : item.cover_img;

      if (previewList && Array.isArray(previewList)) {
        previewList.slice(0, 3).forEach((product, index) => {
          if (product) {
            const picture = typeof product === "string" ? {} : product;
            images.push({
              url:
                typeof product === "string"
                  ? product
                  : picture.src || picture.imgurl || item.share_img || "",
              id:
                (typeof product === "string"
                  ? product
                  : picture.id || index) +
                "" +
                index,
              label: picture.is_main
                ? "主图"
                : picture.pic_name || picture.name || `花色${index + 1}`,
            });
          }
        });
      }

      if (!images.length && item.share_img) {
        images.push({
          url: item.share_img,
          id: `${item.id}-share`,
          label: "封面",
        });
      }

      return images;
    },

    getList() {
      if (this.loading) {
        return Promise.resolve();
      }

      this.loading = true;

      const querys = {
        page: this.page,
        limit: 10,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
      const url =
        this.fromPage === "my"
          ? "album/selection/my_lists"
          : "album/selection/customer_lists";

      return this.$go(url, data, "post", {
        show_err: true,
        loading: this.page === 1,
      })
        .then((res) => {
          if (res.code === 0 && res.data) {
            const list = res.data.data || [];
            const formattedList = list.map((item) => {
              const product = item.product || {};
              return {
                ...item,
                id: item.id,
                title: item.title || item.name || `选款单${item.id}`,
                create_time:
                  item.display_time || item.create_time || item.created_at || "",
                images: this.buildPreviewImages(item),
                product_count: item.product_count || 0,
                customer: item.customer || {},
                factory: item.factory || {},
                product_name: product.name || "",
              };
            });

            if (this.page === 1) {
              this.styleList = formattedList;
            } else {
              this.styleList = this.styleList.concat(formattedList);
            }

            this.last_page = res.data.last_page || 1;
          }
        })
        .catch((err) => {
          console.error("获取选款单列表失败:", err);
        })
        .finally(() => {
          this.loading = false;
        });
    },

    toStyleDetail(item) {
      uni.navigateTo({
        url: `/pagesOther/styleResult/styleResult?id=${item.id}&fromPage=${this.fromPage}`,
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
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
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;

    .nav-bar-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30rpx;

      .left {
        width: 80rpx;
        display: flex;
        align-items: center;

        .back-icon {
          width: 40rpx;
          height: 40rpx;
        }
      }

      .title {
        flex: 1;
        text-align: center;
        font-size: 32rpx;
        font-weight: 600;
        color: #333;
      }

      .right {
        width: 80rpx;
      }
    }
  }
}

.content {
  .list-container {
    box-sizing: border-box;
  }
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
    opacity: 0.5;
  }

  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
}

.style-list {
  padding: 30rpx;
}

.style-item {
  background: #fff;
  border-radius: 24rpx;
  margin-bottom: 30rpx;
  padding: 24rpx;
  box-shadow: 0 12rpx 32rpx rgba(0, 0, 0, 0.05);
}

.style-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
}

.header-left {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.item-avatar {
  width: 64rpx;
  height: 64rpx;
  border-radius: 50%;
  flex-shrink: 0;
}

.item-title-block {
  flex: 1;
  min-width: 0;
}

.item-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  line-height: 1.4;
}

.item-customer,
.item-product {
  font-size: 24rpx;
  color: #666;
  margin-top: 8rpx;
}

.item-time {
  font-size: 22rpx;
  color: #999;
  white-space: nowrap;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin: 20rpx 0 24rpx;
}

.meta-tag {
  font-size: 22rpx;
  color: #8a5a00;
  background: #fff3cc;
  border-radius: 999rpx;
  padding: 8rpx 16rpx;
}

.item-images {
  display: flex;
  gap: 15rpx;
  flex-wrap: wrap;
}

.image-box {
  width: 198rpx;
  height: 198rpx;
  position: relative;
  border-radius: 16rpx;
  overflow: hidden;
  background: #f5f5f5;

  .item-img {
    width: 100%;
    height: 100%;
  }

  .image-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(
      180deg,
      rgba(0, 0, 0, 0) 0%,
      rgba(0, 0, 0, 0.6) 100%
    );
    padding: 16rpx;
    font-size: 22rpx;
    color: #fff;
    text-align: center;
  }
}

.load-more {
  padding: 40rpx 0;
  text-align: center;
  font-size: 24rpx;
  color: #999;
}
</style>
