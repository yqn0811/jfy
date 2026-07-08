<template>
  <view class="agreement-page">
    <view class="hero">
      <text class="title">{{ current.title }}</text>
      <text class="date">更新日期：2026年7月6日</text>
    </view>

    <view class="content">
      <view
        v-for="section in current.sections"
        :key="section.title"
        class="section"
      >
        <text class="section-title">{{ section.title }}</text>
        <text
          v-for="paragraph in section.paragraphs"
          :key="paragraph"
          class="paragraph"
        >
          {{ paragraph }}
        </text>
      </view>
    </view>
  </view>
</template>

<script>
import { getAgreementDocument } from "@/common/agreementDocuments.js";

export default {
  data() {
    return {
      type: "user",
    };
  },
  computed: {
    current() {
      return getAgreementDocument(this.type);
    },
  },
  onLoad(options = {}) {
    const type = ["user", "privacy", "rules"].includes(options.type)
      ? options.type
      : "user";
    this.type = type;
    uni.setNavigationBarTitle({
      title: this.current.title,
    });
  },
};
</script>

<style lang="scss" scoped>
.agreement-page {
  min-height: 100vh;
  background: #f7f7f7;
  color: #333333;
  box-sizing: border-box;
  padding-bottom: 56rpx;
}

.hero {
  background: linear-gradient(180deg, #fff5bd 0%, #ffffff 100%);
  padding: 52rpx 32rpx 36rpx;
  box-sizing: border-box;
}

.title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  line-height: 1.3;
  color: #2f2f2f;
}

.date {
  display: block;
  margin-top: 14rpx;
  font-size: 24rpx;
  line-height: 1.5;
  color: #8a6a1f;
}

.content {
  padding: 24rpx 28rpx 0;
  box-sizing: border-box;
}

.section {
  background: #ffffff;
  border-radius: 8rpx;
  padding: 28rpx 28rpx 18rpx;
  margin-bottom: 20rpx;
  box-sizing: border-box;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.04);
}

.section-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  line-height: 1.45;
  color: #333333;
  margin-bottom: 16rpx;
}

.paragraph {
  display: block;
  font-size: 27rpx;
  line-height: 1.8;
  color: #555555;
  margin-bottom: 14rpx;
  word-break: break-all;
}
</style>
