<template>
  <view class="case-detail-page">
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
        <view class="nav-title">{{ info.folder_name || "案例详情" }}</view>
        <view class="nav-right"></view>
      </view>
    </view>

    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="hero-card" v-if="info.id">
        <image
          class="hero-image"
          :src="info.new_thumb || '/static/image/pic.png'"
          mode="aspectFill"
        ></image>
        <view class="hero-title">{{ info.folder_name || "案例详情" }}</view>
        <view class="hero-meta">
          <text>浏览 {{ info.visit_times || 0 }}</text>
          <text>分享 {{ info.share_times || 0 }}</text>
        </view>
      </view>

      <view class="section" v-if="childFolders.length">
        <view class="section-title">子案例</view>
        <view class="folder-list">
          <view
            class="folder-item"
            v-for="(item, index) in childFolders"
            :key="index"
            @click="openChild(item)"
          >
            <image
              class="folder-image"
              :src="item.new_thumb || '/static/image/pic.png'"
              mode="aspectFill"
            ></image>
            <view class="folder-name">{{ item.folder_name }}</view>
          </view>
        </view>
      </view>

      <view class="section" v-if="pictureList.length">
        <view class="section-title">案例图片</view>
        <view class="picture-list">
          <image
            class="picture-item"
            v-for="(item, index) in pictureList"
            :key="index"
            :src="item.picture_url || '/static/image/pic.png'"
            mode="aspectFill"
            @click="previewImage(index)"
          ></image>
        </view>
      </view>

      <view
        class="empty-box"
        v-if="!loading && !childFolders.length && !pictureList.length"
      >
        <image
          class="empty-icon"
          src="/static/icon/empty.png"
          mode="aspectFit"
        ></image>
        <view class="empty-text">暂无案例内容</view>
      </view>
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
      caseId: "",
      info: {},
      childFolders: [],
      pictureList: [],
      loading: false,
    };
  },

  onLoad(options) {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.caseId = options.id || "";
    if (this.caseId) {
      this.loadDetail();
    }
  },

  methods: {
    goBack() {
      uni.navigateBack();
    },

    loadDetail() {
      if (!this.caseId) {
        return;
      }
      this.loading = true;
      const querys = {
        fid: this.caseId,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      Promise.all([
        this.$go("common/example/folder", data, "get", {
          show_err: false,
          loading: true,
        }),
        this.$go("common/example/pictures", data, "get", {
          show_err: false,
          loading: false,
        }),
      ])
        .then(([folderRes, picRes]) => {
          const folderData = folderRes && folderRes.data ? folderRes.data : {};
          const picData = picRes && picRes.data ? picRes.data : {};
          this.info = folderData.info || {};
          this.childFolders =
            folderData.lists && folderData.lists.data ? folderData.lists.data : [];
          this.pictureList =
            picData.lists && picData.lists.data ? picData.lists.data : [];
        })
        .catch((err) => {
          console.error("获取案例详情失败:", err);
        })
        .finally(() => {
          this.loading = false;
        });
    },

    openChild(item) {
      uni.navigateTo({
        url: `/pagesOther/caseDetail/caseDetail?id=${item.id}`,
      });
    },

    previewImage(index) {
      const urls = this.pictureList
        .map((item) => item.picture_url)
        .filter((item) => !!item);
      if (!urls.length) {
        return;
      }
      uni.previewImage({
        urls,
        current: urls[index],
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.case-detail-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: #fff;
  border-bottom: 1rpx solid #eee;
}

.custom-nav-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
}

.nav-left,
.nav-right {
  width: 80rpx;
  display: flex;
  align-items: center;
}

.back-icon {
  width: 40rpx;
  height: 40rpx;
}

.nav-title {
  flex: 1;
  text-align: center;
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
}

.content {
  padding: 30rpx;
}

.hero-card {
  overflow: hidden;
  border-radius: 24rpx;
  background: #fff;
  margin-bottom: 30rpx;
}

.hero-image {
  width: 100%;
  height: 360rpx;
  display: block;
}

.hero-title {
  font-size: 34rpx;
  color: #222;
  font-weight: 600;
  padding: 24rpx 24rpx 12rpx;
}

.hero-meta {
  display: flex;
  gap: 20rpx;
  padding: 0 24rpx 24rpx;

  text {
    font-size: 24rpx;
    color: #999;
  }
}

.section {
  margin-bottom: 30rpx;
}

.section-title {
  font-size: 30rpx;
  color: #222;
  font-weight: 600;
  margin-bottom: 20rpx;
}

.folder-list,
.picture-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16rpx;
}

.folder-item {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
}

.folder-image,
.picture-item {
  width: 100%;
  height: 240rpx;
  display: block;
  background: #eee;
}

.folder-name {
  padding: 18rpx;
  font-size: 26rpx;
  color: #333;
  line-height: 1.4;
}

.empty-box {
  padding: 160rpx 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon {
  width: 200rpx;
  height: 200rpx;
}

.empty-text {
  margin-top: 24rpx;
  font-size: 28rpx;
  color: #999;
}
</style>
