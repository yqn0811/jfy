<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :round="16"
    :safe-area-inset-bottom="false"
    @close="close"
  >
    <view class="root">
      <!-- header -->
      <view class="header">
        <view class="title-left">
          <text class="title-main">所有分类</text>
          <view class="search-box">
            <image src="/static/icon/搜索@2x.png" class="search-icon" />
            <input
              class="search-input"
              v-model="q"
              placeholder-class="jf-input-placeholder"
              :placeholder="placeholderFor('categoryPopupSearch', '搜索分类')"
              @tap="focusField('categoryPopupSearch')"
              @focus="focusField('categoryPopupSearch')"
              @blur="blurField('categoryPopupSearch')"
              @confirm="onInput"
            />
            <image
              v-if="q"
              src="/static/icon/clear.png"
              class="clear-icon"
              @tap="clear"
            />
          </view>
        </view>
        <view class="close-wrap" @tap="close"
          ><text class="close-x">✕</text></view
        >
      </view>

      <!-- list -->
      <scroll-view class="list" scroll-y :style="{ height: listHeight + 'px' }">
        <view v-if="loading" class="empty">加载中...</view>
        <view v-else>
          <view v-if="all.length === 0" class="empty">没有匹配的分类</view>
          <block v-else>
            <view
              class="item"
              v-for="(c, idx) in all"
              :key="c.id"
              :data-item="c"
              @tap="select($event, c)"
            >
              <view class="item-body">
                <image
                  class="item-icon"
                  :src="c.icon || defaultIcon"
                  mode="widthFix"
                />
                <view class="item-row">
                  <text class="item-title"
                    >{{ c.folder_name
                    }}<text class="count"
                      >({{ getChildCount(c) }})</text
                    ></text
                  >
                  <image src="/static/icon/arrow-right.png" class="arrow" />
                </view>
              </view>
              <text v-if="c.folder_desc" class="item-sub">{{
                c.folder_desc
              }}</text>
            </view>
          </block>
        </view>
      </scroll-view>
    </view>
  </u-popup>
</template>

<script>
export default {
  name: "CategoryPopup",
  props: {
    visible: { type: Boolean, default: false },
    // 可传入初始分类数组（优先使用）
    categories: { type: Array, default: () => [] },
    // 可选：后端接口名（如果不传则使用 this.$go 或本地数据）
    api: { type: String, default: "" },
    // 可配置列表高度（px），不传会自动计算
    height: { type: Number, default: 520 },
    uid: { type: [String, Number], default: "" },
  },
  emits: ["update:visible", "select"],
  data() {
    return {
      q: "",
      all: [], // 原始列表
      loading: false,
      listHeight: this.height,
      defaultIcon: "/static/icon/Frame 1171279070@2x.png",
    };
  },
  watch: {
    visible(val) {
      if (val) {
        this.opened();
      }
    },
    categories: {
      immediate: true,
      handler(v) {
        if (Array.isArray(v)) {
          this.all = v;
        }
      },
    },
  },
  mounted() {
    // 如果未传高度，尝试基于窗口计算
    if (!this.height) {
      const sys = this.$base.getSystemInfoCompat();
      this.listHeight = Math.max(300, sys.windowHeight * 0.6);
    }
  },
  methods: {
    close() {
      this.$emit("update:visible", false);
    },
    opened() {
      if (!this.all || this.all.length === 0) {
        this.fetchCategories();
      }
    },
    onInput() {
      this.fetchCategories();
    },
    clear() {
      this.q = "";
      this.fetchCategories();
    },
    getChildCount(item) {
      if (!item) return 0;
      const fields = [
        "product_count",
        "product_num",
        "folder_count",
        "goods_count",
        "child_count",
        "children_count",
        "son_count",
        "count",
      ];
      for (const field of fields) {
        if (item[field] !== undefined && item[field] !== null && item[field] !== "") {
          const value = Number(item[field]);
          return Number.isFinite(value) ? value : 0;
        }
      }
      return 0;
    },
    async fetchCategories() {
      this.loading = true;
      try {
        if (this.$go) {
          const params = { folder_type: 1, key: this.q, timestamp: Date.now() };

          if (this.uid) {
            params.target_user_id = this.uid;
          }
          const url = this.uid ? "user/home/categories" : "album/lists/folder";
          const methods = this.uid ? "get" : "post";
          const signed = this.$base
            ? { ...params, sign: this.$base.getASCII(params) }
            : params;
          const res = await this.$go(url, signed, methods, {
            show_err: true,
          });
          const list = this.uid ? res.data :  res.data.lists.data || [];
          this.all = list;
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "获取分类失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },
    select(e, item) {
      const data = item ? item : e.currentTarget.dataset.item;
      this.$emit("select", data);
      this.close();
    },
  },
};
</script>

<style scoped lang="scss">
.root {
  padding-bottom: 8rpx;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx;
}

.title-left {
  display: flex;
  align-items: center;
  gap: 32rpx;
  width: 100%;
}

.title-main {
  font-weight: bold;
  font-size: 28rpx;
  color: #333333;
}

.search-box {
  display: flex;
  align-items: center;
  background: #f2f2f2;
  border-radius: 40rpx;
  padding: 16rpx 24rpx;
  width: 340rpx;
  box-sizing: border-box;
}

.search-icon {
  width: 32rpx;
  height: 32rpx;
  margin-right: 8rpx;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-weight: 400;
  font-size: 28rpx;
  color: rgba(0, 0, 0, 0.6);
  padding: 0;
}

.clear-icon {
  width: 20rpx;
  height: 20rpx;
  margin-left: 8rpx;
}

.close-wrap {
  width: 44rpx;
  height: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 22rpx;
  background: #f2f2f2;
}

.close-x {
  font-size: 22rpx;
  color: #999;
}

.list {
  margin: 0 24rpx;
}

.item {
  display: flex;
  flex-direction: column;
  padding: 36rpx 16rpx;
  background: #fff;
  border-radius: 16rpx 16rpx 16rpx 16rpx;
  border-bottom: 1rpx solid rgba(0, 0, 0, 0.1);
}

.item-icon {
  width: 40rpx;
  height: 40rpx;
  margin-right: 12rpx;
}

.item-body {
  flex: 1;
  display: flex;
  align-items: center;
}

.item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 400;
  font-size: 32rpx;
  color: #333333;
}

.arrow {
  width: 18rpx;
  height: 18rpx;
  opacity: 0.5;
}

.item-sub {
  font-weight: 400;
  font-size: 24rpx;
  color: #999999;
  margin-top: 16rpx;
}

.divider {
  height: 1rpx;
  background: #f6f6f6;
  margin: 8rpx 0;
}

.empty {
  text-align: center;
  color: #999;
  padding: 36rpx 0;
}
</style>
