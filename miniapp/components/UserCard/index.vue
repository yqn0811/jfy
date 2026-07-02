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
}

.left {
  display: flex;
  align-items: center;
}

.avatar {
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  margin-right: 18rpx;
}

.name-wrap {
  display: flex;
  flex-direction: column;
}

.name {
  font-size: 28rpx;
  color: #222;
}

.subtitle {
  font-size: 22rpx;
  color: #999;
  margin-top: 6rpx;
}

.contact-btn {
  width: 132rpx;
  height: 56rpx;
  background: #ffd000;
  border-radius: 96rpx 96rpx 96rpx 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.contact-text {
  font-size: 24rpx;
  color: #222;
}
</style>
