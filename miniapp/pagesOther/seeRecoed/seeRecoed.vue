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
          <view class="left">
            <img class="backIcon" @click="back" src="@/static/icon/back.png" />
          </view>
          <view class="title">我的足迹</view>
          <view class="right"></view>
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
            <view
              class="underline"
              v-if="currentCategory === item.value"
            ></view>
          </view>
        </view>
      </scroll-view>

      <!-- 搜索框 -->
      <view class="search-box">
        <view class="search-input">
          <image
            class="search-icon"
            src="/static/icon/搜索@2x(1).png"
            mode="aspectFit"
          ></image>
          <input
            type="text"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('recordSearch', '输入名称')"
            v-model="searchKeyword"
            @tap="focusField('recordSearch')"
            @focus="focusField('recordSearch')"
            @blur="blurField('recordSearch')"
            @confirm="handleSearch"
          />
        </view>
        <view class="search-btn" @click="handleSearch">搜索</view>
      </view>

      <!-- 列表内容 - 添加下拉刷新和上拉加载 -->
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
        <view class="empty-box" v-if="visitList.length == 0 && !loading">
          <view class="tip">这里还没有浏览记录哦~</view>
          <image class="emptyIcon" src="/static/icon/empty.png" mode=""></image>
        </view>
        <view class="visit-list" v-else>
          <view
            class="visit-item"
            v-for="(item, index) in visitList"
            :key="index"
            @click="toDetail(item)"
          >
            <view class="item-left">
              <view class="item-title-subtitle">
                <image
                  class="item-avatar"
                  :src="item.avatar || '/static/image/pic.png'"
                  mode="aspectFill"
                ></image>
                <view class="item-center">
                  <view class="item-title">{{ item.title }}</view>
                  <view class="item-subtitle">
                    <text>{{ item.subtitle }}</text>
                  </view>
                </view>
              </view>
              <image
                class="arrow-icon"
                src="/static/icon/Chevron Right@2x(1).png"
                mode="aspectFit"
              ></image>
            </view>

            <view class="item-right">
              <view class="type-wrap">
                <image
                  class="type-icon"
                  :src="getTypeIcon(item.type)"
                  mode="aspectFit"
                ></image>
                <text class="type-text">{{ getTypeText(item.type) }}</text>
              </view>

              <view class="visit-time">{{ item.visitTime }}</view>
            </view>
          </view>

          <!-- 加载更多提示 -->
          <view class="load-more" v-if="visitList.length > 0">
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
      statusBarHeight: "",
      totalHeight: "",
      navigationBarHeight: 44,
      page: 1,
      last_page: 1,
      currentCategory: "all", // 当前选中的分类 all-全部 homepage-主页 product-产品 category-分类
      categories: [
        { label: "全部", value: "all" },
        { label: "主页", value: "homepage" },
        { label: "产品", value: "product" },
        { label: "分类", value: "category" },
      ],
      searchKeyword: "",
      visitList: [],
      loading: false, // 加载状态
      refreshing: false, // 下拉刷新状态
      listContainerHeight: 0, // 列表容器高度
    };
  },
  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.calculateListHeight();
  },
  onShow() {
    this.page = 1;
    this.visitList = [];
    this.getList();
  },
  onReady() {
    // 页面渲染完成后计算高度
    this.calculateListHeight();
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
      this.visitList = [];
      this.getList();
    },

    // 搜索
    handleSearch() {
      this.page = 1;
      this.visitList = [];
      this.getList();
    },

    // 下拉刷新
    onRefresh() {
      this.refreshing = true;
      this.page = 1;
      this.visitList = [];
      this.getList().finally(() => {
        this.refreshing = false;
      });
    },

    // 上拉加载更多
    onLoadMore() {
      console.log("aaaa");
      if (this.loading || this.page >= this.last_page) {
        return;
      }
      this.page++;
      this.getList();
    },

    // 动态计算列表容器高度
    calculateListHeight() {
      this.$nextTick(() => {
        const query = uni.createSelectorQuery().in(this);

        // 获取分类标签高度
        query.select(".category-scroll").boundingClientRect();
        // 获取搜索框高度
        query.select(".search-box").boundingClientRect();

        query.exec((res) => {
          const systemInfo = this.$base.getSystemInfoCompat();
          const windowHeight = systemInfo.windowHeight;

          // 导航栏高度（px）
          const navHeight = this.totalHeight;

          // 分类标签高度（px）
          const categoryHeight = res[0] ? res[0].height : 0;

          // 搜索框高度（px）
          const searchHeight = res[1] ? res[1].height : 0;

          // 计算列表容器高度 = 窗口高度 - 导航栏 - 分类标签 - 搜索框
          this.listContainerHeight =
            windowHeight - navHeight - categoryHeight - searchHeight - 60;
        });
      });
    },

    // 获取类型图标
    getTypeIcon(type) {
      const iconMap = {
        homepage: "/static/icon/home@2x(3).png", // 主页图标
        product: "/static/icon/image-3@2x(2).png", // 产品图标
        category: "/static/icon/folder-open@2x.png", // 分类图标
      };
      return iconMap[type] || "/static/icon/folder-icon.png";
    },
    getTypeText(type) {
      const iconMap = {
        homepage: "查看主页", // 主页图标
        product: "查看产品", // 产品图标
        category: "查看分类", // 分类图标
      };
      return iconMap[type] || "";
    },

    // 跳转到详情页
    toDetail(item) {
      const user = uni.getStorageSync("userInfo");
      const ownerUid =
        item.target_uid && user && item.target_uid !== user.id
          ? item.target_uid
          : "";
      if (item.type === "homepage") {
        // 主页类型
        uni.navigateTo({
          url: `/pages/index/index?uid=${ownerUid}`,
        });
      } else if (item.type === "product") {
        // 产品类型
        uni.navigateTo({
          url: `/pagesOther/productDetail/productDetail?id=${item.target_id}&uid=${ownerUid}`,
        });
      } else if (item.type === "category") {
        // 分类类型
        uni.navigateTo({
          url: `/pagesOther/classDetail/classDetail?id=${item.target_id}&uid=${ownerUid}`,
        });
      }
    },

    // 获取列表数据
    getList() {
      if (this.loading) {
        return Promise.resolve();
      }

      this.loading = true;

      const querys = {
        page: this.page,
        type: this.currentCategory,
        key: this.searchKeyword,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      return this.$go("user/visit/records", data, "get", {
        show_err: true,
        loading: this.page === 1,
      })
        .then((res) => {
          const list = res.data.data || [];
          // 格式化数据
          const formattedList = list.map((item) => ({
            ...item,
            id: item.id,
            type: item.type,
            userId: item.user_id,
            avatar: item.avatar || item.image,
            title: item.title || item.name,
            subtitle: item.source || item.description,
            visitTime: item.time_str,
          }));

          if (this.page == 1) {
            this.visitList = formattedList;
          } else {
            this.visitList = this.visitList.concat(formattedList);
          }
          this.last_page = Math.ceil(res.data.total / 20) || 1;
          console.log(this.last_page);
        })
        .catch((err) => {
          console.error("获取足迹列表失败:", err);
        })
        .finally(() => {
          this.loading = false;
        });
    },

    // 格式化时间
    formatTime(timestamp) {
      const now = Date.now();
      const diff = now - timestamp * 1000;
      const minute = 60 * 1000;
      const hour = 60 * minute;
      const day = 24 * hour;

      if (diff < hour) {
        return Math.floor(diff / minute) + "分钟前浏览";
      } else if (diff < day) {
        return Math.floor(diff / hour) + "小时前浏览";
      } else {
        return Math.floor(diff / day) + "天前浏览";
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  background-color: #f5f5f5;
  box-sizing: border-box;
}

.header {
  width: 100%;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;
  background-color: #ffffff;
  z-index: 100;

  .custom-nav-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    color: #fff;
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
      }

      .backIcon {
        width: 30rpx;
        height: 30rpx;
      }

      .title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        font-weight: bold;
        font-size: 32rpx;
        color: #000000;
      }

      .right {
        width: 30rpx;
      }
    }
  }
}

.content {
  background-color: #f5f5f5;
  padding-bottom: 60rpx;

  /* 分类标签滚动区域 */
  .category-scroll {
    background-color: #fff;
    white-space: nowrap;
    padding: 30rpx 0 20rpx;
  }

  .category-list {
    display: inline-flex;
    padding: 0 30rpx;
  }

  .category-item {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    margin-right: 60rpx;
    position: relative;
    padding-bottom: 10rpx;

    text {
      font-size: 32rpx;
      color: #999999;
      font-weight: 400;
    }

    &.active text {
      color: #000000;
      font-weight: 600;
      font-size: 36rpx;
    }

    .underline {
      width: 40rpx;
      height: 6rpx;
      background-color: #000000;
      border-radius: 3rpx;
      margin-top: 8rpx;
    }
  }

  /* 搜索框 */
  .search-box {
    display: flex;
    align-items: center;
    background-color: #fff;
    padding: 10rpx;
    margin: 0 20rpx;
    margin-bottom: 20rpx;
    border-radius: 50rpx;
    margin-top: 20rpx;
  }

  .search-input {
    flex: 1;
    display: flex;
    align-items: center;
    background-color: #fff;
    border-radius: 50rpx;
    padding: 0 30rpx;
    margin-right: 20rpx;

    .search-icon {
      width: 32rpx;
      height: 32rpx;
      margin-right: 16rpx;
    }

    input {
      flex: 1;
      font-size: 28rpx;
      color: #333;
    }

    input::placeholder {
      color: #999;
    }
  }

  .search-btn {
    background-color: #ffd700;
    color: #333;
    font-size: 28rpx;
    font-weight: 500;
    padding: 16rpx 40rpx;
    border-radius: 50rpx;
  }

  /* 列表容器 */
  .list-container {
    /* 高度通过 JS 动态计算 */
  }

  .empty-box {
    padding-top: 200rpx;

    .tip {
      font-size: 28rpx;
      text-align: center;
      color: #999;
    }

    .emptyIcon {
      width: 300rpx;
      height: 300rpx;
      display: block;
      margin: 40rpx auto 0;
    }
  }

  /* 访问记录列表 */
  .visit-list {
    padding: 0 30rpx;
  }

  .visit-item {
    display: flex;
    flex-direction: column;
    padding: 30rpx 20rpx;
    border-radius: 24rpx;
    background: #fff;
    margin-bottom: 20rpx;

    .item-left {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-right: 24rpx;
      .item-title-subtitle {
        display: flex;
        align-items: center;
        gap: 24rpx;
      }

      .item-avatar {
        width: 96rpx;
        height: 96rpx;
        border-radius: 16rpx;
      }
      .item-center {
        display: flex;
        flex-direction: column;
        .item-title {
          font-size: 32rpx;
          font-weight: 500;
          color: #333;
          margin-bottom: 12rpx;
        }

        .item-subtitle {
          display: flex;
          align-items: center;
          font-size: 24rpx;
          color: #999;
        }
      }

      .arrow-icon {
        width: 32rpx;
        height: 32rpx;
      }
    }

    .item-right {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 16rpx;
      .type-wrap {
        display: flex;
        align-items: center;
        gap: 8rpx;
      }
      .type-text {
        font-size: 28rpx;
        color: #333333;
        font-weight: bold;
      }
      .type-icon {
        width: 32rpx;
        height: 32rpx;
        margin-right: 8rpx;
      }
      .visit-time {
        font-size: 24rpx;
        color: #999;
        margin-bottom: 8rpx;
      }
    }
  }

  /* 加载更多提示 */
  .load-more {
    padding: 40rpx 0;
    text-align: center;
    font-size: 24rpx;
    color: #999;
  }
}
</style>
