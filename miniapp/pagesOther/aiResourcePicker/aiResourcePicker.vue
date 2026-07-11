<template>
  <view class="page">
    <view class="search-bar">
      <input
        class="search-input"
        v-model="keyword"
        confirm-type="search"
        placeholder-class="jf-input-placeholder"
        :placeholder="placeholderFor('aiResourceSearch', '搜索资源库图片')"
        @tap="focusField('aiResourceSearch')"
        @focus="focusField('aiResourceSearch')"
        @blur="blurField('aiResourceSearch')"
        @confirm="refresh"
      />
      <view class="search-btn" @click="refresh">搜索</view>
    </view>

    <scroll-view
      class="content"
      scroll-y
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="refresh"
      @scrolltolower="loadMore"
    >
      <view v-if="loading && list.length === 0" class="state-text">加载中...</view>
      <view v-else-if="!loading && list.length === 0" class="state-text">暂无资源库图片</view>
      <view v-else class="grid">
        <view
          v-for="(item, index) in list"
          :key="item.id"
          class="card"
          :class="{ selected: isSelected(item.id) }"
          :data-index="index"
          @click="toggle(item, index, $event)"
        >
          <image class="thumb" :src="imageUrl(item)" mode="aspectFill"></image>
          <view class="check" :class="{ active: isSelected(item.id) }">
            <text v-if="isSelected(item.id)">✓</text>
          </view>
          <view class="name">{{ item.name || "资源图片" }}</view>
        </view>
      </view>
      <view v-if="loading && list.length > 0" class="load-more">加载中...</view>
      <view v-if="finished && list.length > 0" class="load-more">已加载全部</view>
    </scroll-view>

    <view class="footer">
      <view class="count">已选 {{ selectedList.length }}/{{ limit }}</view>
      <view class="confirm" :class="{ disabled: selectedList.length === 0 || importing }" @click="confirm">
        {{ importing ? "导入中..." : "确认选择" }}
      </view>
    </view>
  </view>
</template>

<script>
import {
  getObjectId,
  resolveClickedListItem,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";

export default {
  data() {
    return {
      target: "cover",
      limit: 20,
      keyword: "",
      page: 1,
      pageSize: 30,
      total: 0,
      list: [],
      selected: {},
      loading: false,
      refreshing: false,
      finished: false,
      importing: false,
    };
  },
  computed: {
    selectedList() {
      return Object.values(this.selected);
    },
  },
  onLoad(options) {
    this.target = options.target || "cover";
    this.limit = Math.max(1, Number(options.limit || 20));
    this.loadResources(true);
  },
  methods: {
    imageUrl(item) {
      return item.thumbnail_url || item.preview_url || item.file_url || "";
    },
    resourceKey(item) {
      const url =
        (item && (item.file_url || item.preview_url || item.thumbnail_url)) || "";
      return url ? `url:${String(url).split("?")[0]}` : `id:${item && item.id}`;
    },
    dedupeResources(list) {
      const seen = new Set();
      return (Array.isArray(list) ? list : []).filter((item) => {
        const key = this.resourceKey(item);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    },
    isSelected(id) {
      return !!this.selected[id];
    },
    toggle(item, index, event) {
      const current = resolveClickedListItem(item, index, event, this.list);
      const resourceId = getObjectId(current, ["id", "resource_id"]);
      if (!resourceId) {
        showInvalidRecordToast();
        return;
      }
      if (this.isSelected(resourceId)) {
        this.$delete(this.selected, resourceId);
        return;
      }
      if (this.selectedList.length >= this.limit) {
        uni.showToast({ title: "已达到可选上限", icon: "none" });
        return;
      }
      this.$set(this.selected, resourceId, current);
    },
    refresh() {
      this.refreshing = true;
      this.loadResources(true).finally(() => {
        this.refreshing = false;
      });
    },
    loadMore() {
      if (this.loading || this.finished) return;
      this.page += 1;
      this.loadResources(false);
    },
    async loadResources(reset) {
      if (this.loading) return;
      if (reset) {
        this.page = 1;
        this.finished = false;
      }
      this.loading = true;
      try {
        const res = await this.$go(
          "album/ai/resources",
          {
            page: this.page,
            page_size: this.pageSize,
            keyword: this.keyword,
          },
          "get",
          { show_err: true, loading: reset }
        );
        const data = res.data || {};
        const next = data.resources || data.list || [];
        this.total = Number(data.total || 0);
        this.list = this.dedupeResources(reset ? next : this.list.concat(next));
        this.finished =
          next.length < this.pageSize ||
          (this.total > 0 && this.list.length >= this.total);
      } catch (e) {
        console.error("我的资源库加载失败", e);
        uni.showToast({ title: "资源库加载失败", icon: "none" });
        if (this.page > 1) this.page -= 1;
      } finally {
        this.loading = false;
      }
    },
    async confirm() {
      if (this.importing || this.selectedList.length === 0) return;
      this.importing = true;
      uni.showLoading({ title: "导入中..." });
      try {
        const imported = [];
        for (const item of this.selectedList) {
          const resourceId = getObjectId(item, ["id", "resource_id"]);
          if (!resourceId) {
            continue;
          }
          const res = await this.$go(
            "album/ai/import_resource",
            {
              resource_id: resourceId,
              role: this.target,
            },
            "post",
            { show_err: true, loading: false }
          );
          if (res && res.code === 0 && res.data && res.data.id) {
            imported.push(res.data);
          }
        }
        uni.setStorageSync(
          "pickedAiResources",
          JSON.stringify({
            page: "addProduct",
            target: this.target,
            items: imported,
          })
        );
        uni.hideLoading();
        uni.navigateBack();
      } catch (e) {
        console.error("我的资源库导入失败", e);
        uni.hideLoading();
        uni.showToast({ title: "导入失败", icon: "none" });
      } finally {
        this.importing = false;
      }
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  background: #f6f7f9;
  padding-bottom: 128rpx;
  box-sizing: border-box;
}

.search-bar {
  display: flex;
  gap: 16rpx;
  padding: 20rpx 24rpx;
  background: #ffffff;
}

.search-input {
  flex: 1;
  height: 76rpx;
  padding: 0 24rpx;
  border-radius: 38rpx;
  background: #f1f3f5;
  font-size: 28rpx;
  box-sizing: border-box;
}

.search-btn {
  width: 120rpx;
  height: 76rpx;
  border-radius: 38rpx;
  background: #ffd800;
  color: #333;
  font-size: 28rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.content {
  height: calc(100vh - 224rpx);
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
  padding: 24rpx;
}

.card {
  position: relative;
  background: #ffffff;
  border-radius: 16rpx;
  overflow: hidden;
  border: 2rpx solid transparent;
}

.card.selected {
  border-color: #ffd800;
}

.thumb {
  width: 100%;
  height: 250rpx;
  display: block;
  background: #eeeeee;
}

.check {
  position: absolute;
  right: 14rpx;
  top: 14rpx;
  width: 42rpx;
  height: 42rpx;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.35);
  color: #333;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
}

.check.active {
  background: #ffd800;
}

.name {
  padding: 14rpx 16rpx 18rpx;
  font-size: 24rpx;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.state-text,
.load-more {
  padding: 80rpx 0;
  text-align: center;
  color: #999;
  font-size: 26rpx;
}

.load-more {
  padding: 24rpx 0 40rpx;
}

.footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 20rpx 24rpx calc(20rpx + env(safe-area-inset-bottom));
  background: #ffffff;
  border-top: 1rpx solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-sizing: border-box;
}

.count {
  font-size: 28rpx;
  color: #666;
}

.confirm {
  width: 240rpx;
  height: 84rpx;
  border-radius: 42rpx;
  background: #ffd800;
  color: #333;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30rpx;
  font-weight: 500;
}

.confirm.disabled {
  opacity: 0.5;
}
</style>
