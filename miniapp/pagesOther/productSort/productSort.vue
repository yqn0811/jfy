<template>
  <view class="page">
    <!-- 顶部提示 -->
    <view class="tip-wrap" v-if="isTips">
      <view class="tip-text"
        >长按拖动可排序，私密和仅分享可见在主页中不展示</view
      >
      <image
        class="tip-icon"
        @click="closeTips"
        src="/static/icon/close-icon.png"
        mode="scaleToFill"
      />
    </view>

    <!-- 拖拽排序区 -->
    <view class="drag-area">
      <!-- 使用你的 shmily-drag-image 组件 -->
      <!-- 注意：如果组件 prop 名或事件名不同，请按实际改写（我同时监听了 change 和 update:list 两种常见事件） -->
      <shmily-drag-image
        v-if="images.length"
        v-model="images"
        keyName="src"
        @input="onOrderChange"
        @update:modelValue="onOrderUpdate"
        class="drag-component"
      />
      <view v-else class="empty">
        <image
          src="/static/image/empty-folder.png"
          mode="widthFix"
          class="empty-img"
        ></image>
        <text class="empty-text">暂无可排序的产品</text>
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
import ShmilyDragImage from "@/components/shmily-drag-image/components/shmily-drag-image/shmily-drag-image.vue"; // <- 调整为真实路径

export default {
  components: {
    "shmily-drag-image": ShmilyDragImage,
  },
  data() {
    return {
      isTips: true,
      images: [], // { id, cover, title, ... } 列表，组件会渲染并支持拖拽
      albumId: null, // 可通过 options 或 storage 获取当前合集 id
      loading: false,
      // 当组件返回排序结果时，会把排序后的数组（或 id 列表）回写到 this.orderedIds（以便提交）
      orderedIds: [],
      fromPage: "",
    };
  },
  onLoad(options) {
    // 接收 album id（fid / albumId）
    if (options && (options.fid || options.albumId)) {
      this.albumId = options.fid || options.albumId;
    }
    this.fromPage = options.fromPage;
    this.fetchList();
  },
  methods: {
    closeTips() {
      this.isTips = false;
    },
    // 拉取当前合集的产品顺序列表（请替换接口）
    async fetchList() {
      this.loading = true;
      try {
        if (this.$go) {
          const params = {
            fid: this.albumId,
            folder_type: 2,
            timestamp: Date.now(),
          };
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          // TODO: 替换为实际后端接口，例如 'album/sort_list'
          const res = await this.$go("album/lists/folder", signed, "post", {
            show_err: true,
          });
          if (res && res.data && Array.isArray(res.data.lists.data)) {
            this.images = res.data.lists.data.map((item) => ({
              ...item,
              id: item.id,
              src: item.new_thumb,
              title: item.folder_name,
              num: item.detail_pic_ids_arr.length + item.pic_ids_arr.length,
            }));
          }
        }
      } catch (e) {
        console.error("fetchList error", e);
        uni.showToast({ title: "加载失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },

    // 当 shmily-drag-image 组件发出排序变更（常见事件名）
    onOrderChange(newList) {
      console.log(newList);
      // newList 可能是完整对象数组或 id 数组，按实际处理
      if (!newList) return;
      if (Array.isArray(newList) && newList.length > 0) {
        if (typeof newList[0] === "object") {
          this.orderedIds = this.images.map((i, idx) => {
            return {
              id: i.id,
              sort: idx + 1,
            };
          });
          console.log(this.orderedIds);
        }
      }
    },

    // 兼容一些组件使用 update:list 的情况
    onOrderUpdate(newList) {
      // console.log(newList)
      // this.onOrderChange(newList)
    },

    // 提交排序到后端
    async submitOrder() {
      if (this.orderedIds.length === 0) {
        uni.showToast({ title: "没有可提交的排序", icon: "none" });
        return;
      }
      uni.showLoading({ title: "保存中..." });
      try {
        const payload = {
          fid: this.albumId,
          folder_type: 2,
          sort_data: this.orderedIds,
          timestamp: Date.now(),
        };
        const params = this.$base
          ? { ...payload, sign: this.$base.getASCII(payload) }
          : payload;
        if (this.$go) {
          // TODO: 替换为后端真实接口，例如 'album/save_order'
          const res = await this.$go("album/set/folder/sort", params, "post", {
            show_err: true,
          });
          uni.showToast({
            title: res && res.msg ? res.msg : "已保存",
            icon: "none",
          });
          setTimeout(() => {
            if (this.fromPage === "index") {
              uni.$emit("refreshIndexData");
            }
            uni.$emit("refreshProductManageData");
            uni.navigateBack();
          }, 700);
        } else {
          uni.showToast({ title: "已保存（示例）", icon: "none" });
          setTimeout(() => uni.navigateBack(), 700);
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
}

/* 顶部提示 */
.tip-wrap {
  background: rgba(0, 0, 0, 0.75);
  background: #333333;
  border-radius: 16rpx 16rpx 16rpx 16rpx;
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

/* 拖拽区 */
.drag-area {
  min-height: 300rpx;
  padding-bottom: 20rpx;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40rpx 0;
}

.empty-img {
  width: 260rpx;
  height: 180rpx;
  margin-bottom: 14rpx;
}

.empty-text {
  color: #999;
  font-size: 24rpx;
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
  color: #222;
}
</style>
