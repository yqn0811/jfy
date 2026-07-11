<template>
  <view class="page">
    <view class="profile-card">
      <view class="avatar-block">
        <button
          class="avatar-button"
          plain
          open-type="chooseAvatar"
          @chooseavatar="chooseAvatar"
        >
          <image
            class="avatar"
            :src="displayAvatar"
            mode="aspectFill"
            @error="handleAvatarError"
          ></image>
          <view class="avatar-mask">更换头像</view>
        </button>
      </view>

      <view class="form-row">
        <text class="form-label">昵称</text>
        <input
          class="nickname-input"
          type="nickname"
          v-model="form.nickname"
          maxlength="20"
          confirm-type="done"
          placeholder-class="jf-input-placeholder"
          :placeholder="placeholderFor('profileNickname', '请输入昵称')"
          @tap="focusField('profileNickname')"
          @focus="focusField('profileNickname')"
          @blur="blurField('profileNickname')"
        />
      </view>
    </view>

    <view class="section-card">
      <view class="section-title">会员信息</view>
      <view class="info-row">
        <text class="info-label">当前会员</text>
        <view class="info-value-wrap">
          <text class="status-dot" :class="{ active: memberStatusType === 'active' }"></text>
          <text class="info-value">{{ memberName }}</text>
        </view>
      </view>
      <view class="info-row">
        <text class="info-label">会员状态</text>
        <text class="info-value">{{ memberStatusText }}</text>
      </view>
      <view class="info-row">
        <text class="info-label">到期时间</text>
        <text class="info-value">{{ memberExpireText }}</text>
      </view>
    </view>

    <view class="section-card">
      <view class="section-title">资源用量</view>
      <view class="usage-item">
        <view class="usage-head">
          <text class="usage-title">容量</text>
          <text class="usage-value">{{ storageUsageText }}</text>
        </view>
        <view class="progress-track">
          <view class="progress-fill storage" :style="{ width: storagePercent + '%' }"></view>
        </view>
        <view class="usage-foot">
          <text>剩余 {{ storageRemainingText }}</text>
          <text>{{ storagePercent }}%</text>
        </view>
      </view>

      <view class="usage-item">
        <view class="usage-head">
          <text class="usage-title">月度流量</text>
          <text class="usage-value">{{ trafficUsageText }}</text>
        </view>
        <view class="progress-track">
          <view
            class="progress-fill traffic"
            :class="{ warning: isTrafficExceeded }"
            :style="{ width: trafficPercent + '%' }"
          ></view>
        </view>
        <view class="usage-foot" :class="{ warning: isTrafficExceeded }">
          <text>{{ trafficRemainingText }}</text>
          <text>{{ trafficPercent }}%</text>
        </view>
      </view>
    </view>

    <view class="save-bar">
      <button class="save-button" :disabled="saving || uploadingAvatar" @click="submitProfile">
        {{ saveButtonText }}
      </button>
    </view>
  </view>
</template>

<script>
import Upload from "@/common/request/upload.js";
import {
  buildUploadNameFormData,
  getSelectedUploadFileName,
  prepareNamedUploadFile,
} from "@/common/helper/uploadName.js";
import { notifyRefresh } from "@/common/helper/refresh.js";

const uploader = new Upload();

export default {
  data() {
    return {
      userInfo: {},
      form: {
        nickname: "",
        avatar: "",
      },
      saving: false,
      uploadingAvatar: false,
      defaultAvatar: "/static/image/headurl.jpg",
    };
  },
  computed: {
    displayAvatar() {
      return this.normalizeImageUrl(this.form.avatar || this.userInfo.avatar) || this.defaultAvatar;
    },
    memberName() {
      return this.safeText(this.userInfo.grade_name) || "免费版";
    },
    memberStatusType() {
      const level = Number(this.userInfo.grade_level || this.userInfo.vip_grade || 0);
      if (level <= 0) return "free";
      return this.isMemberExpired() ? "expired" : "active";
    },
    memberStatusText() {
      if (this.memberStatusType === "active") return "会员中";
      if (this.memberStatusType === "expired") return "已过期";
      return "免费版";
    },
    memberExpireText() {
      const endTime = this.userInfo.end_time || this.userInfo.vip_end_time || this.userInfo.expire_time;
      if (!endTime || Number(endTime) === 0) {
        return this.memberStatusType === "free" ? "未开通" : "永久有效";
      }
      return this.formatDateValue(endTime);
    },
    storageUsedBytes() {
      return this.pickPositiveNumber([
        this.userInfo.resource_storage_used_bytes,
        this.userInfo.use_space,
        this.userInfo.normal_space_bytes,
      ]);
    },
    storageLimitBytes() {
      return this.pickPositiveNumber([
        this.userInfo.resource_storage_capacity_bytes,
        this.parseStorageToBytes(this.userInfo.all_space),
      ]);
    },
    storageRemainingBytes() {
      const value = this.pickNumber([this.userInfo.resource_storage_remaining_bytes], -1);
      if (value > 0) return value;
      return Math.max(this.storageLimitBytes - this.storageUsedBytes, 0);
    },
    storagePercent() {
      return this.getPercent(this.storageUsedBytes, this.storageLimitBytes);
    },
    storageUsageText() {
      return `${this.formatBytes(this.storageUsedBytes)} / ${this.formatBytes(this.storageLimitBytes)}`;
    },
    storageRemainingText() {
      return this.formatBytes(this.storageRemainingBytes);
    },
    trafficUsedGb() {
      return this.pickPositiveNumber([
        this.userInfo.used_traffic_gb,
        this.userInfo.traffic_used_gb,
      ]);
    },
    trafficUsedBytes() {
      return this.pickPositiveNumber([
        this.userInfo.used_traffic_bytes,
        this.userInfo.traffic_used_bytes,
        this.trafficUsedGb * 1024 * 1024 * 1024,
      ]);
    },
    trafficLimitGb() {
      return this.pickPositiveNumber([
        this.userInfo.monthly_traffic_limit_gb,
        this.userInfo.traffic_limit_gb,
        this.userInfo.traffic_gb,
      ]);
    },
    trafficLimitBytes() {
      return this.pickPositiveNumber([
        this.userInfo.monthly_traffic_limit_bytes,
        this.userInfo.traffic_limit_bytes,
        this.trafficLimitGb * 1024 * 1024 * 1024,
      ]);
    },
    trafficRemainingGb() {
      const value = this.pickPositiveNumber([
        this.userInfo.monthly_traffic_remaining_gb,
        this.userInfo.traffic_remaining_gb,
      ], -1);
      if (value > 0) return value;
      return Math.max(this.trafficLimitGb - this.trafficUsedGb, 0);
    },
    trafficRemainingBytes() {
      const value = this.pickNumber([
        this.userInfo.monthly_traffic_remaining_bytes,
        this.userInfo.traffic_remaining_bytes,
      ], -1);
      if (value > 0) return value;
      return Math.max(this.trafficLimitBytes - this.trafficUsedBytes, 0);
    },
    trafficPercent() {
      return this.getPercent(this.trafficUsedBytes, this.trafficLimitBytes);
    },
    trafficUsageText() {
      return `${this.formatBytes(this.trafficUsedBytes)} / ${this.formatBytes(this.trafficLimitBytes)}`;
    },
    trafficRemainingText() {
      if (this.isTrafficExceeded) return "本月流量已超出";
      return `剩余 ${this.formatBytes(this.trafficRemainingBytes)}`;
    },
    isTrafficExceeded() {
      return Boolean(this.userInfo.monthly_traffic_exceeded) || (
        this.trafficLimitBytes > 0 && this.trafficUsedBytes > this.trafficLimitBytes
      );
    },
    saveButtonText() {
      if (this.uploadingAvatar) return "头像上传中";
      if (this.saving) return "保存中";
      return "保存资料";
    },
  },
  onLoad() {
    this.userInfo = this.normalizeUserInfo(uni.getStorageSync("userInfo") || {});
    this.syncFormFromUser();
    this.getUserInfo();
  },
  methods: {
    safeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    pickNumber(values, fallback = 0) {
      for (let i = 0; i < values.length; i += 1) {
        const value = Number(values[i]);
        if (Number.isFinite(value)) return value;
      }
      return fallback;
    },
    pickPositiveNumber(values, fallback = 0) {
      for (let i = 0; i < values.length; i += 1) {
        const value = Number(values[i]);
        if (Number.isFinite(value) && value > 0) return value;
      }
      return fallback;
    },
    normalizeUserInfo(info = {}) {
      return {
        ...info,
        nickname: this.safeText(info.nickname || info.nickName || info.company_name || info.name) || "微信用户",
        avatar: this.normalizeImageUrl(info.avatar || info.avatarUrl || info.headimgurl || info.logo || info.company_logo),
      };
    },
    syncFormFromUser() {
      this.form = {
        nickname: this.safeText(this.userInfo.nickname) || "微信用户",
        avatar: this.userInfo.avatar || this.defaultAvatar,
      };
    },
    normalizeImageUrl(url) {
      if (!url || typeof url !== "string") return this.defaultAvatar;
      if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("/static/")) {
        return url;
      }
      if (url.indexOf("upimages/") !== -1) {
        return this.defaultAvatar;
      }
      const domain = this.$config && this.$config.domain ? this.$config.domain : "https://api-test.jfyuntu.com";
      const path = url.startsWith("/") ? url : `/${url}`;
      return `${domain}${path}`;
    },
    isMemberExpired() {
      const level = Number(this.userInfo.grade_level || this.userInfo.vip_grade || 0);
      const endTime = this.userInfo.end_time || this.userInfo.vip_end_time || this.userInfo.expire_time;
      if (level <= 0 || !endTime || Number(endTime) === 0) return false;
      const time = this.parseDateTime(endTime);
      return time > 0 && time < Date.now();
    },
    parseDateTime(value) {
      if (value === null || value === undefined || value === "") return 0;
      if (typeof value === "number" || /^\d+$/.test(String(value))) {
        const number = Number(value);
        if (number === 0) return 0;
        return number < 10000000000 ? number * 1000 : number;
      }
      const normalized = String(value).replace(/-/g, "/");
      const time = new Date(normalized).getTime();
      return Number.isFinite(time) ? time : 0;
    },
    formatDateValue(value) {
      if (typeof value === "string" && /^\d{4}-\d{1,2}-\d{1,2}$/.test(value)) return value;
      const time = this.parseDateTime(value);
      if (!time) return String(value);
      const date = new Date(time);
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${date.getFullYear()}-${month}-${day}`;
    },
    parseStorageToBytes(value) {
      if (typeof value === "number") return value * 1024 * 1024;
      const text = this.safeText(value).toUpperCase();
      if (!text) return 0;
      const match = text.match(/^([\d.]+)\s*([KMGTP]?B?|[KMGTP])$/);
      if (!match) return 0;
      const number = Number(match[1]);
      if (!Number.isFinite(number)) return 0;
      const unit = match[2].replace("B", "") || "M";
      const unitMap = {
        K: 1024,
        M: 1024 * 1024,
        G: 1024 * 1024 * 1024,
        T: 1024 * 1024 * 1024 * 1024,
        P: 1024 * 1024 * 1024 * 1024 * 1024,
      };
      return number * (unitMap[unit] || unitMap.M);
    },
    formatBytes(bytes) {
      const value = Number(bytes);
      if (!Number.isFinite(value) || value <= 0) return "0MB";
      const gb = 1024 * 1024 * 1024;
      const mb = 1024 * 1024;
      if (value >= gb) return `${this.trimNumber(value / gb)}GB`;
      return `${this.trimNumber(value / mb)}MB`;
    },
    formatGb(value) {
      const number = Number(value);
      if (!Number.isFinite(number) || number <= 0) return "0GB";
      return `${this.trimNumber(number)}GB`;
    },
    trimNumber(value) {
      const number = Number(value);
      if (!Number.isFinite(number)) return "0";
      if (number >= 100) return String(Math.round(number));
      return number.toFixed(1).replace(/\.0$/, "");
    },
    getPercent(used, total) {
      const usedNumber = Number(used);
      const totalNumber = Number(total);
      if (!Number.isFinite(usedNumber) || !Number.isFinite(totalNumber) || totalNumber <= 0) return 0;
      return Math.min(100, Math.max(0, Math.round((usedNumber / totalNumber) * 100)));
    },
    getUserInfo() {
      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("user/show_info", data, "get", {
        show_err: false,
      })
        .then((res) => {
          if (!res || res.code !== 0) return;
          this.userInfo = this.normalizeUserInfo(res.data || {});
          this.syncFormFromUser();
          uni.setStorageSync("userInfo", this.userInfo);
        })
        .catch((err) => {
          console.error("获取个人资料失败:", err);
        });
    },
    chooseAvatar(e) {
      const avatarUrl = e && e.detail ? e.detail.avatarUrl : "";
      if (!avatarUrl) {
        uni.showToast({ title: "请选择头像", icon: "none" });
        return;
      }
      const uploadName = getSelectedUploadFileName({ name: "avatar.jpg" }, avatarUrl, 1);
      this.uploadingAvatar = true;
      uni.showLoading({ title: "上传中...", mask: true });
      prepareNamedUploadFile(avatarUrl, uploadName)
        .then((uploadPath) => {
          return uploader.upload(uploadPath, {
            endpoint: "/api/common/upload",
            formData: buildUploadNameFormData(uploadName),
            showErrorToast: false,
          });
        })
        .then((res) => {
          if (!res || res.code !== 0 || !res.data) {
            throw new Error((res && res.msg) || "上传失败");
          }
          const url = res.data.full_url || res.data.url || "";
          if (!url) throw new Error("上传响应异常");
          this.form.avatar = url;
          uni.showToast({ title: "上传成功", icon: "success" });
        })
        .catch((err) => {
          console.error("头像上传失败:", err);
          uni.showToast({ title: err.message || "上传失败,请重试", icon: "none" });
        })
        .finally(() => {
          this.uploadingAvatar = false;
          uni.hideLoading();
        });
    },
    submitProfile() {
      if (this.saving || this.uploadingAvatar) return;
      const nickname = this.safeText(this.form.nickname);
      if (!nickname) {
        uni.showToast({ title: "请输入昵称", icon: "none" });
        return;
      }
      const openid = uni.getStorageSync("openid") || this.userInfo.openid || "";
      if (!openid) {
        uni.showToast({ title: "用户未登录", icon: "none" });
        return;
      }
      const formData = {
        openid,
        nickname,
        avatar: this.safeText(this.form.avatar),
      };
      const querys = {
        ...formData,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.saving = true;
      uni.showLoading({ title: "保存中...", mask: true });
      this.$go("user/update_info", data, "post", {
        show_err: true,
      })
        .then((res) => {
          if (!res || res.code !== 0) return;
          const nextUserInfo = this.normalizeUserInfo({
            ...this.userInfo,
            nickname,
            avatar: this.form.avatar,
          });
          this.userInfo = nextUserInfo;
          uni.setStorageSync("userInfo", nextUserInfo);
          notifyRefresh("home");
          uni.showToast({ title: "保存成功", icon: "success" });
          setTimeout(() => {
            uni.navigateBack();
          }, 600);
        })
        .catch((err) => {
          console.error("保存个人资料失败:", err);
          uni.showToast({ title: "保存失败,请重试", icon: "none" });
        })
        .finally(() => {
          this.saving = false;
          uni.hideLoading();
        });
    },
    handleAvatarError() {
      this.form.avatar = this.defaultAvatar;
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 24rpx 30rpx 180rpx;
  box-sizing: border-box;
}

.profile-card,
.section-card {
  background: #ffffff;
  border-radius: 16rpx;
  padding: 28rpx;
  box-sizing: border-box;
  margin-bottom: 24rpx;
}

.avatar-block {
  display: flex;
  justify-content: center;
  margin: 12rpx 0 34rpx;
}

.avatar-button {
  width: 152rpx;
  height: 152rpx;
  padding: 0;
  margin: 0;
  border: none;
  background: transparent;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
}

.avatar-button::after {
  border: none;
}

.avatar {
  width: 152rpx;
  height: 152rpx;
  border-radius: 50%;
  background: #f0f0f0;
  display: block;
}

.avatar-mask {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 44rpx;
  line-height: 44rpx;
  background: rgba(0, 0, 0, 0.56);
  color: #ffffff;
  font-size: 20rpx;
  text-align: center;
}

.form-row,
.info-row {
  min-height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24rpx;
  border-top: 1rpx solid #f1f1f1;
}

.form-row {
  border-top: 0;
}

.form-label,
.info-label {
  font-size: 28rpx;
  color: #666666;
  flex-shrink: 0;
}

.nickname-input {
  flex: 1;
  height: 80rpx;
  line-height: 80rpx;
  text-align: right;
  font-size: 30rpx;
  color: #333333;
}

.section-title {
  font-size: 30rpx;
  line-height: 42rpx;
  font-weight: 600;
  color: #333333;
  margin-bottom: 12rpx;
}

.info-value-wrap {
  display: flex;
  align-items: center;
  gap: 10rpx;
  min-width: 0;
}

.status-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #b9b9b9;
  flex-shrink: 0;
}

.status-dot.active {
  background: #21b36b;
}

.info-value {
  font-size: 28rpx;
  color: #333333;
  text-align: right;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.usage-item {
  padding: 24rpx 0 10rpx;
}

.usage-item + .usage-item {
  border-top: 1rpx solid #f1f1f1;
  margin-top: 8rpx;
}

.usage-head,
.usage-foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20rpx;
}

.usage-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
}

.usage-value {
  font-size: 26rpx;
  color: #333333;
}

.progress-track {
  height: 14rpx;
  background: #f0f0f0;
  border-radius: 999rpx;
  overflow: hidden;
  margin: 20rpx 0 14rpx;
}

.progress-fill {
  height: 100%;
  border-radius: 999rpx;
  transition: width 0.2s ease;
}

.progress-fill.storage {
  background: #ffd000;
}

.progress-fill.traffic {
  background: #4c8dff;
}

.progress-fill.warning {
  background: #ff6b35;
}

.usage-foot {
  font-size: 24rpx;
  color: #999999;
}

.usage-foot.warning {
  color: #ff6b35;
}

.save-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 20rpx 30rpx calc(20rpx + env(safe-area-inset-bottom));
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 -8rpx 24rpx rgba(0, 0, 0, 0.06);
}

.save-button {
  height: 88rpx;
  line-height: 88rpx;
  background: #ffd000;
  color: #333333;
  font-size: 30rpx;
  font-weight: 600;
  border-radius: 44rpx;
}

.save-button[disabled] {
  color: #999999;
  background: #eeeeee;
}

.save-button::after {
  border: none;
}
</style>
