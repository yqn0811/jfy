<template>
  <view class="user-card">
    <view class="left">
      <image
        class="avatar"
        :src="displayAvatar"
        mode="aspectFill"
        @error="loadError = true"
      ></image>
      <view class="name-wrap">
        <text class="name">{{ displayName }}</text>
        <text class="subtitle" v-if="displaySubtitle">{{
          displaySubtitle
        }}</text>
      </view>
    </view>
    <view class="right">
      <view class="contact-btn" @tap="onContact">
        <text class="contact-text">联系我</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: "UserCard",
  props: {
    avatar: { type: String, default: "" },
    name: { type: String, default: "" },
    subtitle: { type: String, default: "" },
  },
  data() {
    return {
      defaultAvatar: "/static/image/headurl.jpg",
      loadError: false,
    };
  },
  watch: {
    avatar() {
      this.loadError = false;
    }
  },
  computed: {
    displayAvatar() {
      if (this.loadError) return this.defaultAvatar;
      return this.avatar || this.defaultAvatar;
    },
    displayName() {
      return this.name || "商户名称";
    },
    displaySubtitle() {
      return this.subtitle || "";
    },
  },
  methods: {
    onContact() {
      this.$emit("contact"); // 父组件处理联系逻辑
    },
  },
};
</script>

<style scoped lang="scss">
.user-card {
  background: #fff;
  border-radius: 24rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx;
  gap: 20rpx;
  box-sizing: border-box;
}

.left {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
}

.avatar {
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  margin-right: 18rpx;
  flex: 0 0 96rpx;
  display: block;
  overflow: hidden;
}

.name-wrap {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.name {
  display: block;
  font-size: 28rpx;
  color: #222;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.subtitle {
  display: -webkit-box;
  font-size: 22rpx;
  color: #999;
  margin-top: 6rpx;
  line-height: 1.35;
  max-height: 60rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  word-break: break-all;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.contact-btn {
  width: 132rpx;
  height: 56rpx;
  background: #ffd000;
  border-radius: 96rpx 96rpx 96rpx 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 132rpx;
}

.contact-text {
  font-size: 24rpx;
  color: #222;
}
</style>
