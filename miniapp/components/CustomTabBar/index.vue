<template>
  <view class="custom-tabbar">
    <view
      class="tab-item"
      v-for="(item, index) in items"
      :key="index"
      :class="{ active: current === index, center: item.center }"
      @click="onTap(item, index)"
    >
      <!-- 普通 tab 图标 + 文本 -->
      <image
        v-if="!item.center"
        :src="current === index ? item.selectedIconPath : item.iconPath"
        class="icon"
        mode="widthFix"
      ></image>
      <text v-if="!item.center" class="label">{{ item.text }}</text>

      <!-- 中间大按钮 -->
      <view v-if="item.center" class="center-wrap">
        <view class="center-circle">
          <image
            :src="item.iconPath"
            class="center-icon"
            mode="widthFix"
          ></image>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: "CustomTabBar",
  props: {
    items: {
      type: Array,
      default() {
        return [
          {
            pagePath: "/pages/index/index",
            text: "首页",
            iconPath: "/static/icon/home@2x(1).png",
            selectedIconPath: "/static/icon/home@2x.png",
          },
          {
            pagePath: "",
            text: "",
            iconPath: "/static/icon/Group 427320407@2x.png",
            selectedIconPath: "/static/icon/Group 427320407@2x.png",
            center: true,
          },
          {
            pagePath: "/pages/usercenter/index",
            text: "工作台",
            iconPath: "/static/icon/video-camera@2x.png",
            selectedIconPath: "/static/icon/video-camera@2x(1).png",
          },
        ];
      },
    },
  },
  data() {
    return {
      current: 0,
    };
  },
  mounted() {
    this.setCurrent();
  },
  methods: {
    setCurrent() {
      const pages = getCurrentPages && getCurrentPages();
      const route =
        pages && pages.length ? "/" + pages[pages.length - 1].route : "";
      const idx = this.items.findIndex((i) => i.pagePath === route);
      this.current = idx === -1 ? 0 : idx;
    },
    onTap(item, index) {
      if (item.center) {
        this.$emit("createProduct");
        return;
      }
      uni.redirectTo({
        url: item.pagePath,
      });
      this.current = index;
    },
  },
};
</script>

<style scoped>
.custom-tabbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 124rpx;
  /* was 62px */
  background: #ffffff;
  display: flex;
  justify-content: space-around;
  align-items: center;
  border-top: 2rpx solid #eee;
  /* was 1px */
  z-index: 1000;
  padding-bottom: env(safe-area-inset-bottom);
}

.tab-item {
  flex: 1;
  text-align: center;
  align-items: center;
  justify-content: center;
  display: flex;
  flex-direction: column;
  height: 100%;
  padding-top: 12rpx;
  /* was 6px */
}

.tab-item .icon {
  width: 48rpx;
  /* was 22px */
  height: 48rpx;
  /* was 22px */
  margin-bottom: 8rpx;
  /* was 4px */
}

.tab-item .label {
  font-weight: bold;
  font-size: 20rpx;
  color: #2f2f2f;
}

.tab-item.active .label {
  color: #2a2a2a;
}

/* 中间按钮样式 */
.tab-item.center {
  flex: 0 0 auto;
  width: 144rpx;
  /* was 72px */
  height: 144rpx;
  /* was 72px */
  margin-top: -36rpx;
  /* was -18px */
  padding: 0;
}

.center-wrap {
  width: 144rpx;
  /* was 72px */
  height: 144rpx;
  /* was 72px */
  display: flex;
  align-items: center;
  justify-content: center;
}

.center-circle {
  width: 48rpx;
  /* was 58px */
  height: 48rpx;
  /* was 58px */
}

.center-icon {
  width: 48rpx;
  /* was 26px or 48rpx in variants */
  height: 48rpx;
}
</style>
