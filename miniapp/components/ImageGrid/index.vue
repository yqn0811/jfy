<template>
  <view class="image-grid">
    <view class="grid" :class="normalizedColumns === 1 ? 'grid-1' : 'grid-2'">
      <view
        class="card"
        v-for="(item, index) in list"
        :key="index"
        :data-item="item"
        @click="handleClick($event, item, index)"
      >
        <view
          class="thumb-wrap"
          :class="{ 'ratio-mode': useAspectRatio }"
          :style="thumbWrapStyle"
        >
          <image
            :src="getImageSrc(item)"
            class="thumb"
            :style="imageStyle"
            mode="aspectFill"
          >
          </image>
          <view class="badge" v-if="showBadge">
            <text>{{ getBadgeText(item) }}</text>
          </view>
          <view class="series" v-if="showSeries">{{
            getSeriesText(item)
          }}</view>
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
        return src;
      }
      return this.defaultImage;
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
    handleClick(e, item, index) {
      const data = item ? item : e.currentTarget.dataset.item;
      this.$emit("click", data, index);
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
</style>
