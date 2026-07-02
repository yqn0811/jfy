<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="header">
      <view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
        <view :style="{ height: statusBarHeight + 'px' }"></view>
        <view
          class="nav-bar-content"
          :style="{ height: navigationBarHeight + 'px' }"
        >
          <view class="left">
            <img class="backIcon" @click="back" src="@/static/icon/back.png" />
          </view>
          <view class="title">{{ pageTitle }}</view>
          <view class="right"></view>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <view v-if="visitors.length > 0" class="list">
        <view class="item" v-for="(item, index) in visitors" :key="getVisitorItemKey(item, index)">
            <image class="avatar" :src="item.visitor_avatar || '/static/image/avatar_default.png'" mode="aspectFill"></image>
            <view class="info">
                <view class="name">{{ item.visitor_name || "未知用户" }}</view>
                <view class="time">{{ item.time_str }}</view>
            </view>
        </view>
      </view>
      <view class="empty-box" v-else>
          <image class="emptyIcon" src="/static/image/empty-folder.png" mode="aspectFit"></image>
          <view class="tip">暂无访客记录</view>
      </view>
    </view>
  </view>
</template>

<script>
import go from "@/common/request/go.js";
import * as base from "@/common/helper/base.js";

export default {
  data() {
    return {
      statusBarHeight: "",
      totalHeight: "",
      navigationBarHeight: 44,
      visitors: [],
      from: "visitor",
      page: 1,
      hasMore: true,
      loading: false
    };
  },
  computed: {
    pageTitle() {
      return this.from === "views" ? "浏览记录" : "访客记录";
    }
  },
  onLoad(options = {}) {
    this.from = options && options.from === "views" ? "views" : "visitor";
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.markViewsRead();
    this.getVisitors();
  },
  onReachBottom() {
      if (this.hasMore && !this.loading) {
          this.page++;
          this.getVisitors();
      }
  },
  methods: {
    back() {
      uni.navigateBack();
    },
    markViewsRead() {
        if (this.from !== "views") return;
        const querys = {
            timestamp: new Date().getTime(),
        };
        const data = {
            ...querys,
            sign: base.getASCII(querys),
        };
        go("user/visitors/read", data, "post", {
            loading: false,
            show_err: false,
        }).then(() => {
            const userInfo = uni.getStorageSync("userInfo") || {};
            uni.setStorageSync("userInfo", {
                ...userInfo,
                view_badge: 0,
            });
        }).catch(() => {});
    },
    getVisitors() {
        if (this.loading) return;
        this.loading = true;
        
        const querys = {
            page: this.page,
            type: this.from === "views" ? "views" : "visitor",
            timestamp: new Date().getTime(),
        };
        const data = {
            ...querys,
            sign: base.getASCII(querys),
        };

        go("user/visitors", data, "get").then(res => {
            this.loading = false;
            if (res.code === 0) {
                const list = Array.isArray(res.data.data) ? res.data.data : [];
                if (this.page === 1) this.visitors = [];
                this.appendVisitors(list);
                
                if (list.length < res.data.per_page) {
                    this.hasMore = false;
                }
            }
        }).catch(() => {
            this.loading = false;
        });
    },
    appendVisitors(list) {
        if (this.from === "views") {
            this.visitors = [...this.visitors, ...list];
            return;
        }
        const seen = new Set(this.visitors.map((item) => this.getVisitorKey(item)));
        const next = [];
        list.forEach((item) => {
            const key = this.getVisitorKey(item);
            if (!seen.has(key)) {
                seen.add(key);
                next.push(item);
            }
        });
        this.visitors = [...this.visitors, ...next];
    },
    getVisitorKey(item) {
        if (!item) return "";
        const fields = [
            item.uid,
            item.visitor_id,
            item.openid,
            item.unionid,
            item.visitor_openid,
        ];
        const value = fields.find((field) => field !== undefined && field !== null && `${field}` !== "");
        if (value !== undefined) return String(value);
        return `${item.visitor_name || ""}_${item.visitor_avatar || ""}`;
    },
    getVisitorItemKey(item, index) {
        return `${this.getVisitorKey(item)}_${item && item.id ? item.id : index}`;
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f8f8f8;
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 999;
  background-color: #f8f8f8;
}

.custom-nav-bar {
  width: 100%;
}

.nav-bar-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
  position: relative;
}

.left,
.right {
  width: 60rpx;
  display: flex;
  align-items: center;
}

.title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}

.backIcon {
  width: 44rpx;
  height: 44rpx;
}

.content {
//   padding: 30rpx; // Remove padding here to let list touch edges if needed, or keep it.
}

.empty-box {
  padding-top: 200rpx;
  display: flex;
  flex-direction: column;
  align-items: center;

  .tip {
    font-size: 28rpx;
    color: #999;
    margin-top: 40rpx; // Changed from margin-bottom to margin-top
  }

  .emptyIcon {
    width: 300rpx;
    height: 300rpx;
  }
}

.list {
    padding: 0 30rpx;
    background-color: #fff;
}
.item {
    display: flex;
    align-items: center;
    padding: 30rpx 0;
    border-bottom: 1rpx solid #eee;
    &:last-child {
        border-bottom: none;
    }
}
.avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    margin-right: 20rpx;
    background-color: #eee;
}
.info {
    flex: 1;
}
.name {
    font-size: 30rpx;
    color: #333;
    margin-bottom: 10rpx;
}
.time {
    font-size: 24rpx;
    color: #999;
}
</style>
