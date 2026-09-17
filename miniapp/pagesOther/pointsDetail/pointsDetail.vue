<template>
  <view class="points-detail-page">
    <!-- 自定义导航栏 -->
    <view class="header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view
        class="custom-nav-bar"
        :style="{ height: navigationBarHeight + 'px' }"
      >
        <view class="nav-left" @click="goBack">
          <image
            class="back-icon"
            src="/static/icon/back.png"
            mode="aspectFit"
          ></image>
        </view>
        <view class="nav-title">积分明细</view>
        <view class="nav-right"></view>
      </view>
    </view>

    <!-- 总积分卡片 -->
    <view class="total-points-card" :style="{ marginTop: totalHeight + 'px' }">
      <view class="points-label">我的积分</view>
      <view class="points-value">{{ totalPoints }}</view>
      <view class="points-desc">积分可用于兑换会员权益</view>
    </view>

    <!-- 积分明细列表 -->
    <view class="points-list">
      <view class="list-title">积分明细</view>

      <!-- 空状态 -->
      <view class="empty-box" v-if="recordList.length === 0 && !loading">
        <image
          class="empty-icon"
          src="/static/icon/empty.png"
          mode="aspectFit"
        ></image>
        <view class="empty-text">暂无积分记录</view>
      </view>

      <!-- 记录列表 -->
      <view class="record-list" v-else>
        <view
          class="record-item"
          v-for="(item, index) in recordList"
          :key="index"
        >
          <view class="record-left">
            <view class="record-title">{{ item.message}}</view>
            <view class="record-time">{{ item.create_time }}</view>
          </view>
          <view class="record-right">
            <view
              class="record-points"
              :class="{
                'is-add': item.change_type === 2,
                'is-reduce': item.change_type === 1,
              }"
            >
              {{ item.change_type === 2 ? "+" : "-" }}{{ item.change_integral }}
            </view>
          </view>
        </view>
      </view>

      <!-- 加载更多 -->
      <view class="load-more" v-if="recordList.length > 0">
        <view v-if="loading" class="loading-text">加载中...</view>
        <view v-else-if="page >= last_page" class="no-more-text"
          >没有更多了</view
        >
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      // 状态栏高度
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,

      // 总积分
      totalPoints: 0,

      // 积分记录列表
      recordList: [],

      // 分页
      page: 1,
      last_page: 1,
      loading: false,
    };
  },

  onLoad() {
    // 获取系统信息
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    // 加载数据
    this.getPointsDetail();
  },

  onReachBottom() {
    // 加载更多
    if (this.page < this.last_page && !this.loading) {
      this.page++;
      this.getPointsDetail();
    }
  },

  onPullDownRefresh() {
    // 下拉刷新
    this.page = 1;
    this.getPointsDetail().then(() => {
      uni.stopPullDownRefresh();
    });
  },

  methods: {
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 获取积分明细
    getPointsDetail() {
      if (this.loading) return;

      this.loading = true;

      const querys = {
        page: this.page,
        limit: 20,
        timestamp: new Date().getTime(),
      };

      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      return this.$go("integral/records", data, "get", {
        show_err: true,
        loading: this.page === 1,
      })
        .then((res) => {
          if (res && res.data) {
            const list = Array.isArray(res.data.lists) ? res.data.lists : [];
            if (list.length > 0 && typeof list[0].new_integral !== "undefined") {
              this.totalPoints = list[0].new_integral;
            }

            const formattedList = list.map((item) => ({
              ...item,
              create_time: item.create_time_text || item.create_time || "",
            }));

            if (this.page === 1) {
              this.recordList = formattedList;
            } else {
              this.recordList = this.recordList.concat(formattedList);
            }

            this.last_page = res.data.total_page || 1;
          }
        })
        .catch((err) => {
          console.error("获取积分明细失败:", err);
          if (this.page > 1) {
            this.page--;
          }
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.points-detail-page {
  background-color: #f5f5f5;
}

// ==================== 自定义导航栏 ====================
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

      .back-icon {
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
      width: 80rpx;
    }
  }
}

// ==================== 总积分卡片 ====================
.total-points-card {
  margin: 30rpx;
  padding: 60rpx 40rpx;
  background: linear-gradient(135deg, #ffd700 0%, #ffd700 50%, #ffd700 100%);
  border-radius: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(102, 126, 234, 0.3);

  .points-label {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 20rpx;
  }

  .points-value {
    font-size: 80rpx;
    font-weight: bold;
    color: #333;

    line-height: 1;
    margin-bottom: 20rpx;
  }

  .points-desc {
    font-size: 24rpx;
    color: #333;


  }
}

// ==================== 积分明细列表 ====================
.points-list {
  margin: 30rpx;
  background-color: #fff;
  border-radius: 24rpx;
  padding: 30rpx;

  .list-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 30rpx;
  }

  .empty-box {
    padding: 100rpx 0;
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

  .record-list {
    .record-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 30rpx 0;
      border-bottom: 1rpx solid #f5f5f5;

      &:last-child {
        border-bottom: none;
      }

      .record-left {
        flex: 1;

        .record-title {
          font-size: 30rpx;
          color: #333;
          margin-bottom: 12rpx;
          font-weight: 500;
        }

        .record-time {
          font-size: 24rpx;
          color: #999;
        }
      }

      .record-right {
        .record-points {
          font-size: 36rpx;
          font-weight: bold;

          &.is-add {
            color: #52c41a;
          }

          &.is-reduce {
            color: #ff4d4f;
          }
        }
      }
    }
  }

  .load-more {
    padding: 30rpx 0;
    text-align: center;

    .loading-text,
    .no-more-text {
      font-size: 24rpx;
      color: #999;
    }
  }
}
</style>
