<template>
  <view class="image-grid">
    <view
      class="grid"
      :class="[
        normalizedColumns === 1 ? 'grid-1' : 'grid-2',
        cardVariant ? `variant-${cardVariant}` : '',
      ]"
    >
      <view
        class="card"
        :class="[
          item && item.item_type === 'category' ? 'is-category' : '',
          item && item.item_type === 'product' ? 'is-product' : '',
        ]"
        v-for="(item, index) in list"
        :key="getItemKey(item, index)"
        @tap="handleClick($event, item, index)"
      >
        <view
          class="thumb-wrap"
          :class="{ 'ratio-mode': useAspectRatio }"
          :style="thumbWrapStyle"
        >
          <view
            v-if="isClassDetailCategory(item)"
            class="folder-cover"
          >
            <view class="category-tile-icon">
              <view class="category-icon-tab"></view>
              <view class="category-icon-body"></view>
            </view>
            <view class="category-tile-main">
              <text class="category-tile-name">{{
                getSeriesText(item) || "未命名分类"
              }}</text>
              <text class="category-tile-meta">{{ getBadgeText(item) }}子分类</text>
            </view>
          </view>
          <image
            v-else
            :src="getImageSrc(item)"
            class="thumb"
            :style="imageStyle"
            mode="aspectFill"
            :lazy-load="lazyLoad"
            :show-menu-by-longpress="showMenuByLongpress"
          >
          </image>
          <view class="badge" v-if="showBadge && !isClassDetailCategory(item)">
            <text>{{ getBadgeText(item) }}</text>
          </view>
          <view class="series" v-if="showSeries && !isClassDetailCategory(item)">{{
            getSeriesText(item)
          }}</view>
          <view
            v-if="showMoreButton && item && item.item_type === 'category'"
            class="grid-more-btn"
            @tap.stop="handleMore(item, index)"
          >
            <view class="grid-more-dot"></view>
            <view class="grid-more-dot"></view>
            <view class="grid-more-dot"></view>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { imageUrlFor } from "@/common/helper/imageUrls.js";

export default {
  name: "ImageGrid",
  props: {
    // 数据列表
    list: {
      type: Array,
      default: () => [],
    },
    // 列数：1 或 2
    columns: {
      type: [Number, String],
      default: 2,
      validator: (value) => [1, 2].includes(Number(value)),
    },
    // 图片高度，支持rpx或px
    imageHeight: {
      type: String,
      default: "",
    },
    // 图片展示比例，默认 4:3；传 imageHeight 时优先使用固定高度
    aspectRatio: {
      type: String,
      default: "4:3",
    },
    // 间距，支持rpx或px
    gap: {
      type: String,
      default: "30rpx",
    },
    // 是否显示badge
    showBadge: {
      type: Boolean,
      default: true,
    },
    // 是否显示series
    showSeries: {
      type: Boolean,
      default: true,
    },
    // badge 数量单位，产品图片默认“张”，分类页可传“个”
    badgeSuffix: {
      type: String,
      default: "张",
    },
    // 图片字段名
    imageField: {
      type: String,
      default: "imageField",
    },
    // 数量字段名
    countField: {
      type: String,
      default: "countField",
    },
    // 名称字段名
    nameField: {
      type: String,
      default: "nameField",
    },
    // 默认图片
    defaultImage: {
      type: String,
      default: "/static/image/pic.png",
    },
    // 图片处理参数
    imageParams: {
      type: String,
      default: "",
    },
    // 自定义稳定 key 字段，未传时自动从常见 id 字段中选择
    itemKey: {
      type: String,
      default: "",
    },
    lazyLoad: {
      type: Boolean,
      default: true,
    },
    showMenuByLongpress: {
      type: Boolean,
      default: false,
    },
    cardVariant: {
      type: String,
      default: "",
    },
    showMoreButton: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    normalizedColumns() {
      return Number(this.columns) === 1 ? 1 : 2;
    },
    useAspectRatio() {
      return !this.imageHeight && !!this.aspectRatio;
    },
    thumbWrapStyle() {
      if (!this.useAspectRatio) {
        return "";
      }
      return `padding-bottom: ${this.getAspectRatioPadding(this.aspectRatio)};`;
    },
    imageStyle() {
      if (this.useAspectRatio) {
        return "";
      }
      return `height: ${this.imageHeight || "auto"};`;
    },
  },
  methods: {
    getAspectRatioPadding(value) {
      const parts = String(value || "4:3")
        .replace("/", ":")
        .split(":")
        .map((item) => Number(item));
      const width = parts[0] > 0 ? parts[0] : 4;
      const height = parts[1] > 0 ? parts[1] : 3;
      return `${(height / width) * 100}%`;
    },
    getImageSrc(item) {
      const src = item[this.imageField] || imageUrlFor(item, "thumb");
      if (src) {
        return this.appendImageParams(src);
      }
      return this.defaultImage;
    },
    appendImageParams(src) {
      if (!this.imageParams || !src || src.indexOf("/static/") === 0) {
        return src;
      }
      return src.indexOf("?") === -1
        ? `${src}?${this.imageParams}`
        : `${src}&${this.imageParams}`;
    },
    getItemKey(item, index) {
      if (!item || typeof item !== "object") return `idx-${index}`;
      if (this.itemKey && item[this.itemKey] !== undefined && item[this.itemKey] !== null) {
        return `${this.itemKey}-${item[this.itemKey]}`;
      }
      const keyFields = [
        "id",
        "_id",
        "uid",
        "pic_id",
        "picture_id",
        "product_id",
        "folder_id",
        "fid",
        "uuid",
        "imageField",
        this.imageField,
      ];
      for (const field of keyFields) {
        const value = item[field];
        if (value !== undefined && value !== null && value !== "") {
          return `${field}-${value}`;
        }
      }
      return `idx-${index}`;
    },
    getBadgeText(item) {
      const count = item[this.countField];
      const suffix =
        item && item.badgeSuffix !== undefined ? item.badgeSuffix : this.badgeSuffix;
      return `${count || 0}${suffix}`;
    },
    getSeriesText(item) {
      const value = item[this.nameField];
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return /^tmp[_-]/i.test(text) ? "" : text;
    },
    isClassDetailCategory(item) {
      return item && item.item_type === "category" && this.cardVariant === "class-detail";
    },
    handleClick(e, item, index) {
      this.$emit("click", item, index);
    },
    handleMore(item, index) {
      this.$emit("more", item, index);
    },
  },
};
</script>

<style lang="scss" scoped>
.image-grid {
  width: 100%;
  height: 100%;
}

.grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}

.grid-1 {
  .card {
    width: 100%;
  }
}

.grid-2 {
  .card {
    width: calc(50% - 10rpx);
  }
}

.card {
  width: 100%;
  flex-shrink: 0;
  margin-bottom: 30rpx;
}

.thumb-wrap {
  position: relative;
  border-radius: 8rpx;
  overflow: hidden;
  background: #f3f3f3;
}

.thumb-wrap.ratio-mode {
  width: 100%;
  height: 0;
}

.thumb {
  width: 100%;
  object-fit: cover;
  display: block;
}

.ratio-mode .thumb {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
}

.badge {
  position: absolute;
  left: 16rpx;
  top: 16rpx;
  background: rgba(0, 0, 0, 0.4);
  color: #fff;
  padding: 6rpx 20rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
}

.series {
  box-sizing: border-box;
  width: 100%;
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  color: #fff;
  font-size: 26rpx;
  line-height: 1.3;
  background: linear-gradient(
    0deg,
    rgba(0, 0, 0, 0.8) 0%,
    rgba(0, 0, 0, 0) 100%
  );
  padding: 28rpx 20rpx 18rpx;
  border-radius: 8rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.variant-class-detail {
  .card {
    margin-bottom: 28rpx;
  }

  .thumb-wrap {
    border-radius: 10rpx;
    background: #eeeeee;
    box-shadow: 0 8rpx 22rpx rgba(0, 0, 0, 0.04);
  }

  .badge {
    left: 18rpx;
    top: 18rpx;
    min-width: 58rpx;
    height: 42rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: rgba(35, 35, 35, 0.56);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    line-height: 42rpx;
    box-sizing: border-box;
    backdrop-filter: blur(6rpx);
  }

  .series {
    min-height: 88rpx;
    padding: 34rpx 20rpx 18rpx;
    font-size: 28rpx;
    line-height: 36rpx;
    border-radius: 0;
    background: linear-gradient(
      0deg,
      rgba(0, 0, 0, 0.7) 0%,
      rgba(0, 0, 0, 0.35) 44%,
      rgba(0, 0, 0, 0) 100%
    );
  }

  .grid-more-btn {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    width: 48rpx;
    height: 48rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.86);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    box-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.08);
    z-index: 3;
  }

  .grid-more-dot {
    width: 6rpx;
    height: 6rpx;
    border-radius: 50%;
    background: #4c535d;
  }
}

.variant-class-detail .is-category {
  .thumb-wrap {
    background: #ffffff;
    border: 1rpx solid rgba(38, 45, 56, 0.06);
    box-shadow: 0 8rpx 20rpx rgba(32, 38, 48, 0.05);
  }

  .badge {
    left: 16rpx;
    top: 16rpx;
    min-width: 54rpx;
    height: 38rpx;
    padding: 0 16rpx;
    background: rgba(41, 47, 56, 0.58);
    color: #ffffff;
    font-size: 22rpx;
    line-height: 38rpx;
  }

  .series {
    color: #252b33;
    min-height: 68rpx;
    padding: 24rpx 18rpx 16rpx;
    font-weight: 600;
    font-size: 27rpx;
    line-height: 34rpx;
    text-shadow: none;
    background: linear-gradient(
      0deg,
      rgba(255, 255, 255, 0.98) 0%,
      rgba(255, 255, 255, 0.92) 70%,
      rgba(255, 255, 255, 0) 100%
    );
  }

  .grid-more-btn {
    width: 44rpx;
    height: 44rpx;
    background: rgba(255, 255, 255, 0.92);
  }
}

.folder-cover {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 24rpx 20rpx 20rpx;
  box-sizing: border-box;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
}

.category-tile-icon {
  position: relative;
  width: 78rpx;
  height: 62rpx;
  margin-top: 16rpx;
}

.category-icon-tab {
  position: absolute;
  left: 0;
  top: 0;
  width: 38rpx;
  height: 18rpx;
  border-radius: 10rpx 10rpx 4rpx 4rpx;
  background: #ffd45a;
}

.category-icon-body {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 48rpx;
  border-radius: 14rpx;
  background: linear-gradient(180deg, #ffc93d 0%, #f5a400 100%);
  box-shadow: 0 8rpx 16rpx rgba(236, 156, 0, 0.14);
}

.category-tile-main {
  min-width: 0;
}

.category-tile-name {
  display: block;
  font-weight: 600;
  font-size: 28rpx;
  line-height: 36rpx;
  color: #222832;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-tile-meta {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  line-height: 28rpx;
  color: #8b929d;
}
</style>
