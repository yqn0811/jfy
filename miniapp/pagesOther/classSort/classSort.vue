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
      <!-- 注意：如果组件 prop 名或事件名不同，请按实际改写（我同时监听了 change 和 update:list 两种常见事件） -->
      <HM-dragSorts
        v-if="list.length"
        ref="dragSorts"
        :list="list"
        :autoScroll="true"
        :feedbackGenerator="true"
        :listHeight="listHeight || 800"
        listBackgroundColor="#f5f5f5"
        :rowHeight="144"
        @change="change"
        @confirm="confirm"
        @onclick="onclick"
      ></HM-dragSorts>
      <view v-else class="empty">
        <image
          src="/static/image/empty-folder.png"
          mode="widthFix"
          class="empty-img"
        ></image>
        <text class="empty-text">暂无可排序的分类</text>
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
import HMDragSorts from "@/components/HM-dragSorts/components/HM-dragSorts/HM-dragSorts.vue"; // <- 调整为真实路径

export default {
  components: {
    "HM-dragSorts": HMDragSorts,
  },
  data() {
    return {
      list: [],
      isTips: true,
      albumId: null, // 父级分类 id；为空时排序一级分类
      loading: false,
      // 当组件返回排序结果时，会把排序后的数组（或 id 列表）回写到 this.orderedIds（以便提交）
      orderedIds: [],
      listHeight: 0,
      fromPage:''
    };
  },
  onLoad(options) {
    // 接收 album id（fid / albumId）
    if (options && (options.fid || options.albumId)) {
      this.albumId = options.fid || options.albumId;
    }
    this.fromPage = options.fromPage
    this.fetchList();
    // 计算列表高度（放到 nextTick，保证 DOM 渲染完成）
    this.$nextTick(() => {
      this.computeListHeight();
    });
  },
  methods: {
    // 动态计算可用高度：windowHeight - topHeight - bottomHeight - margin
    computeListHeight() {
      const sys = this.$base.getSystemInfoCompat();
      const windowHeight = sys.windowHeight;

      const query = uni.createSelectorQuery().in(this);
      query.select(".tip-wrap").boundingClientRect();
      query.select(".foot-wrap").boundingClientRect();
      query.select(".page").boundingClientRect();
      query.exec((res) => {
        // res 顺序与 select 顺序对应
        const tipRect = res[0] || null;
        const footRect = res[1] || null;
        // const pageRect = res[2] || null // not used

        const tipH = tipRect && tipRect.height ? tipRect.height : 0;
        const footH = footRect && footRect.height ? footRect.height : 0;

        // 额外留白（px）
        const extra = 20;

        let available = windowHeight - tipH - footH - extra;

        // 最小高度保护
        if (available < 200) available = 200;
        console.log(Math.floor(available));
        this.listHeight = Math.floor(available);
      });
    },
    closeTips() {
      this.isTips = false;
    },
    // 拉取当前层级的分类顺序列表
    async fetchList() {
      this.loading = true;
      try {
        if (this.$go) {
          const params = { folder_type: 1, timestamp: Date.now() };
          if (this.albumId) {
            params.fid = this.albumId;
          }
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          // TODO: 替换为实际后端接口，例如 'album/sort_list'
          const res = await this.$go("album/lists/folder", signed, "post", {
            show_err: true,
          });
          if (res && res.data && Array.isArray(res.data.lists.data)) {
            // 期望每项含 id/cover/title 等字段
            this.list = res.data.lists.data.map((item) => ({
              ...item,
              name: item.folder_name,
              desc: item.folder_desc,
              icon: item.new_thumb,
              cont: item.product_count,
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

    push() {
      // 和数组的push使用方法一致，可以push单行，也可以push多行
      this.$refs.dragSorts.push({
        name: "push行",
        icon: "/static/img/2.png",
      });
    },
    unshift() {
      // 和数组的unshift使用方法一致，可以unshift单行，也可以unshift多行
      this.$refs.dragSorts.unshift({
        name: "unshift行",
        icon: "/static/img/2.png",
      });
    },
    splice() {
      // 和数组的unshift使用方法一致 下标1开始删除1个并在下标1位置插入行
      this.$refs.dragSorts.splice(1, 1, {
        name: "splice行",
        icon: "/static/img/2.png",
      });
    },
    onclick(e) {
      console.log("=== onclick start ===");

      console.log("=== onclick end ===");
    },
    change(e) {
      console.log("=== change start ===");

      console.log("=== change end ===");
    },
    confirm(e) {
      console.log("=== confirm start ===");
      this.orderedIds = e.list.map((res, idx) => {
        return {
          id: res.id,
          sort: idx + 1,
        };
      });
      console.log(this.orderedIds);
      console.log("被拖动行: " + JSON.stringify(e.moveRow));
      console.log("原始下标：", e.index);
      console.log("移动到：", e.moveTo);
      console.log("=== confirm end ===");
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
          folder_type: 1,
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
          if (res.code === 0) {
            uni.showToast({
              title: res && res.msg ? res.msg : "已保存",
              icon: "none",
            });
            setTimeout(() => {
              if(this.fromPage === 'index'){
                uni.$emit("refreshIndexData");
              }
              uni.$emit("refreshClassManageData");
              uni.navigateBack();
            }, 700);
          }
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
