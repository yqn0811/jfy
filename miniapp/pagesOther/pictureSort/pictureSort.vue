<template>
  <view class="page">
    <!-- 顶部提示 -->
    <view class="tip-wrap" v-if="isTips">
      <view class="tip-text">长按拖动可排序，调整图片展示顺序</view>
      <image
        class="tip-icon"
        @click="closeTips"
        src="/static/icon/close-icon.png"
        mode="scaleToFill"
      />
    </view>

    <!-- 花色图排序区 -->
    <view class="sort-section">
      <view class="section-title">
        <text class="title-text">花色图排序</text>
        <text class="title-count">({{ coverImages.length }}张)</text>
      </view>
      <view class="drag-area">
        <shmily-drag-image
          v-if="coverImages.length"
          v-model="coverImages"
          keyName="src"
          @input="onCoverOrderChange"
          class="drag-component"
        />
        <view v-else class="empty">
          <image
            src="/static/image/empty-folder.png"
            mode="widthFix"
            class="empty-img"
          ></image>
          <text class="empty-text">暂无花色图</text>
        </view>
      </view>
    </view>

    <!-- 详情图排序区 -->
    <view class="sort-section">
      <view class="section-title">
        <text class="title-text">详情图排序</text>
        <text class="title-count">({{ detailImages.length }}张)</text>
      </view>
      <view class="drag-area">
        <shmily-drag-image
          v-if="detailImages.length"
          v-model="detailImages"
          keyName="src"
          @input="onDetailOrderChange"
          class="drag-component"
        />
        <view v-else class="empty">
          <image
            src="/static/image/empty-folder.png"
            mode="widthFix"
            class="empty-img"
          ></image>
          <text class="empty-text">暂无详情图</text>
        </view>
      </view>
    </view>

    <!-- 底部 完成 -->
    <view class="foot-wrap">
      <view class="btn-done" @tap="submitOrder">
        <text>完成</text>
      </view>
    </view>
  </view>
</template>

<script>
import ShmilyDragImage from "@/components/shmily-drag-image/components/shmily-drag-image/shmily-drag-image.vue";

export default {
  components: {
    "shmily-drag-image": ShmilyDragImage,
  },
  data() {
    return {
      isTips: true,
      coverImages: [], // 花色图列表
      detailImages: [], // 详情图列表
      productId: null, // 产品ID
      loading: false,
      orderedCoverIds: [], // 花色图排序后的ID列表
      orderedDetailIds: [], // 详情图排序后的ID列表
      fromPage: "",
    };
  },
  onLoad(options) {
    if (options && options.id) {
      this.productId = options.id;
    }
    this.fromPage = options.fromPage || "";
    this.fetchList();
  },
  methods: {
    closeTips() {
      this.isTips = false;
    },

    // 拉取产品的图片列表
    async fetchList() {
      this.loading = true;
      try {
        if (this.$go) {
          const params = {
            fid: this.productId,
            timestamp: Date.now(),
          };
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;

          const res = await this.$go("album/products/detail", signed, "post", {
            show_err: true,
          });

          if (res && res.data) {
            // 处理花色图
            if (res.data.pic_list && Array.isArray(res.data.pic_list)) {
              this.orderedCoverIds = res.data.pic_list.map((item) => item.id);
              this.coverImages = res.data.pic_list.map((item) => ({
                ...item,
                id: item.id,
                title: item.pic_name,
                src: item.imgurl,
              }));
            }

            // 处理详情图
            if (
              res.data.detail_pic_list &&
              Array.isArray(res.data.detail_pic_list)
            ) {
              this.orderedDetailIds = res.data.detail_pic_list.map(
                (item) => item.id,
              );
              this.detailImages = res.data.detail_pic_list.map((item) => ({
                ...item,
                id: item.id,
                title: item.pic_name,
                src: item.imgurl,
              }));
            }
          }
        }
      } catch (e) {
        console.error("fetchList error", e);
        uni.showToast({ title: "加载失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },

    // 花色图排序变更
    onCoverOrderChange(newList) {
      if (!newList || !Array.isArray(newList)) return;
      this.coverImages = newList;
      this.orderedCoverIds = newList.map((item) => item.id);
    },

    // 详情图排序变更
    onDetailOrderChange(newList) {
      if (!newList || !Array.isArray(newList)) return;
      this.detailImages = newList;
      this.orderedDetailIds = newList.map((item) => item.id);
    },
    // 提交排序到后端
    async submitOrder() {
      if (
        this.orderedCoverIds.length === 0 &&
        this.orderedDetailIds.length === 0
      ) {
        uni.showToast({ title: "没有可提交的排序", icon: "none" });
        return;
      }

      uni.showLoading({ title: "保存中..." });
      try {
        const payload = {
          fid: this.productId,
          pic_ids: this.orderedCoverIds,
          detail_pic_ids: this.orderedDetailIds,
          timestamp: Date.now(),
        };
        const params = this.$base
          ? { ...payload, sign: this.$base.getASCII(payload) }
          : payload;

        if (this.$go) {
          const res = await this.$go("album/edit/folder", params, "post", {
            show_err: true,
          });
          uni.showToast({
            title: res && res.msg ? res.msg : "已保存",
            icon: "success",
          });
          setTimeout(() => {
            // 触发刷新事件
            uni.$emit("refreshProductDetailsSelfData");
            uni.$emit("refreshProductManageData");
            uni.$emit("refreshIndexData");
            uni.navigateBack();
          }, 1500);
        }
      } catch (e) {
        console.error("submitOrder error", e);
        uni.showToast({ title: "保存失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },
  },
};
</script>

<style scoped>
.page {
  padding: 18rpx;
  box-sizing: border-box;
  padding-bottom: 180rpx;
}

/* 顶部提示 */
.tip-wrap {
  background: #333333;
  border-radius: 16rpx;
  padding: 24rpx 18rpx;
  margin: 6rpx 0 18rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tip-text {
  font-weight: 400;
  font-size: 26rpx;
  color: #ffffff;
  display: block;
}

.tip-icon {
  width: 48rpx;
  height: 48rpx;
}

/* 排序区块 */
.sort-section {
  margin-bottom: 40rpx;
  background-color: #fff;
  border-radius: 16rpx;
  padding: 30rpx;
}

.section-title {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid #f5f5f5;
}

.title-text {
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
}

.title-count {
  font-size: 26rpx;
  color: #999;
  margin-left: 12rpx;
}

/* 拖拽区 */
.drag-area {
  min-height: 200rpx;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60rpx 0;
}

.empty-img {
  width: 200rpx;
  height: 140rpx;
  margin-bottom: 20rpx;
  opacity: 0.5;
}

.empty-text {
  color: #999;
  font-size: 26rpx;
}

/* 底部保存按钮 */
.foot-wrap {
  position: fixed;
  left: 0;
  right: 0;
  bottom: calc(env(safe-area-inset-bottom) + 12rpx);
  display: flex;
  justify-content: center;
  padding: 18rpx;
  box-sizing: border-box;
  z-index: 100;
  background-color: #f5f5f5;
}

.btn-done {
  width: 686rpx;
  height: 96rpx;
  background: #ffd800;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  font-weight: 500;
  color: #222;
}
</style>
