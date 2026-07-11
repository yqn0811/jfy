<template>
  <view class="page">
    <!-- 头部黄色背景区域 -->
    <view class="header">
      <image
        class="header-decor"
        src="/static/image/start-image.png"
        mode="aspectFit"
      ></image>
      <!-- 用户信息卡片 -->
      <view class="user-card" :style="{ paddingTop: statusBarHeight + 'px' }">
        <!-- 左侧头像 -->
        <view class="avatar-container">
          <view class="avatar-bg">
            <image
              class="avatar-icon"
              :src="displayAvatar"
              mode="aspectFill"
              @error="handleAvatarError"
            />
          </view>
        </view>

        <!-- 中间用户信息 -->
        <view class="user-info">
          <view class="user-name-row">
            <text class="user-name">{{ displayName }}</text>
            <view v-if="showMemberUpgrade" class="upgrade-badge" @click.stop="tovip">
              <text class="badge-text">升级会员</text>
            </view>
          </view>
          <view class="user-storage">
            <text class="storage-text">{{ memberName }}</text>
            <text class="storage-mb">{{ memberExpireText }}</text>
          </view>
        </view>

        <!-- 右侧相机图标 -->
        <view class="camera-icon" @click="toPage('setInfo')">
          <image src="/static/icon/设置@2x.png" mode="aspectFit"></image>
        </view>
      </view>

      <!-- 统计栏 -->
      <view class="stats-row">
        <view class="stat-item" @click="handleStatClick('product')">
          <text class="stat-number">{{ getStatValue("product_count") }}</text>
          <text class="stat-label">产品</text>
        </view>
        <view class="stat-item" @click="handleStatClick('category')">
          <text class="stat-number">{{ getStatValue("category_count") }}</text>
          <text class="stat-label">分类</text>
        </view>
        <view class="stat-item" @click="handleStatClick('views')">
          <view class="stat-number-wrapper">
            <text class="stat-number">{{ getStatValue("view_count") }}</text>
            <view class="stat-badge" v-if="getStatValue('view_badge') > 0">
              <text class="badge-number">{{ formatBadge(userInfo.view_badge) }}</text>
            </view>
          </view>
          <text class="stat-label">浏览量</text>
        </view>
        <view class="stat-item" @click="handleStatClick('visitor')">
          <text class="stat-number">{{ getStatValue("visitor_count") }}</text>
          <text class="stat-label">访客</text>
        </view>
      </view>

      <!-- 两个大按钮 -->
      <view class="action-buttons">
        <view class="action-btn create-btn" @click="handleCreate">
          <view class="btn-content">
            <view class="btn-text-wrapper">
              <text class="btn-title">立即新建</text>
              <text class="btn-subtitle">产品/分类</text>
            </view>
            <view class="btn-icon">
              <image class="icon-img"
                src="/static/icon/Frame@2x(12).png"
                mode="aspectFit"
              ></image>
            </view>
          </view>
        </view>
        <view class="action-btn share-btn" @click="openShare">
          <view class="btn-content">
            <view class="btn-text-wrapper">
              <text class="btn-title">分享主页</text>
              <text class="btn-subtitle">分享我的主页</text>
            </view>
            <view class="btn-icon">
              <image class="icon-img"
                src="/static/icon/Frame@2x(11).png"
                mode="aspectFit"
              ></image>
            </view>
          </view>
        </view>
      </view>

      <!-- 标签导航 -->
      <view v-if="isSelectionListEnabled" class="tabs-nav">
        <view
          class="tab-item"
          :class="{ active: activeTab === 0 }"
          @click="switchTab(0)"
        >
          <image
            src="/static/icon/Frame@2x(9).png"
            class="tab-icon"
            mode="aspectFit"
          ></image>
          <text class="tab-text">我的选款</text>
        </view>
        <view class="tab-divider"></view>
        <view
          class="tab-item"
          :class="{ active: activeTab === 1 }"
          @click="switchTab(1)"
        >
          <image
            src="/static/icon/Frame@2x(10).png"
            class="tab-icon"
            mode="aspectFit"
          ></image>
          <text class="tab-text">客户的选款</text>
        </view>
      </view>
    </view>

    <!-- 主要内容区域 -->
    <view class="content">
      <!-- 商家管理 -->
      <view class="section">
        <view class="section-title">商家管理</view>
        <view class="functions-grid">
          <view class="function-item" @click="toPage('productManage')">
            <view class="function-icon orange">
              <image class="icon-img"
                src="/static/icon/商品管理@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">产品管理</text>
          </view>
          <view class="function-item" @click="toPage('classManage')">
            <view class="function-icon orange">
              <image class="icon-img"
                src="/static/icon/更多功能@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">分类管理</text>
          </view>
          <view class="function-item" @click="toPage('setInfo')">
            <view class="function-icon purple">
              <image class="icon-img"
                src="/static/icon/门店管理@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">主页编辑</text>
          </view>
          <view class="function-item" @click="toPage('permissionSetting')">
            <view class="function-icon blue">
              <image class="icon-img"
                src="/static/icon/交易查询@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">权限管理</text>
          </view>
          <view class="function-item" @click="handleStatClick('visitor')">
            <view class="function-icon light-purple">
              <image class="icon-img"
                src="/static/icon/员工管理@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">访客记录</text>
          </view>
          <view v-if="showMemberUpgrade" class="function-item" @click="tovip">
            <view class="function-icon yellow">
              <image class="icon-img"
                src="/static/icon/Frame@2x(8).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">升级会员</text>
          </view>
          <view class="function-item" @click="toPage('trash')">
            <view class="function-icon grey">
              <image class="icon-img" src="/static/icon/trash@2x.png" mode="aspectFit"></image>
            </view>
            <text class="function-label">回收站</text>
          </view>
        </view>
      </view>

      <!-- 常用功能 -->
      <view class="section">
        <view class="section-title">常用功能</view>
        <view class="functions-grid">
          <view class="function-item" @click="toProfile">
            <view class="function-icon outline">
              <image class="icon-img" src="/static/icon/user.png" mode="aspectFit"></image>
            </view>
            <text class="function-label">个人资料</text>
          </view>
          <view class="function-item" @click="toPage('seeRecoed')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/Frame@2x(7).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">我的足迹</text>
          </view>
          <view class="function-item" @click="toPage('seeCollection')">
            <view class="function-icon outline">
              <image class="icon-img" src="/static/icon/star@2x.png" mode="aspectFit"></image>
            </view>
            <text class="function-label">我的收藏</text>
          </view>
          <view class="function-item" @click="handleCheckin">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/24＊24@2x(3).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">签到</text>
          </view>
          <view class="function-item" @click="toPage('inviteFriends')">
            <view class="function-icon outline">
              <image class="icon-img" src="/static/icon/invent.png" mode="aspectFit"></image>
            </view>
            <text class="function-label">邀请好友</text>
          </view>
          <view class="function-item" @click="toPage('points')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/emotion-smile@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">我的积分</text>
          </view>
          <button
            class="function-item function-button"
            open-type="contact"
            session-from="usercenter"
          >
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/24＊24@2x(2).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">客服服务</text>
          </button>
          <view class="function-item" @click="toPage('caseCenter')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/image-3@2x(1).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">案例中心</text>
          </view>
        </view>
      </view>

      <!-- 其他功能 -->
      <view class="section other-section">
        <view class="section-title">其他功能</view>
        <view class="functions-grid">
          <view class="function-item" @click="toWeb(baseInfo.news_link)">
            <view class="function-icon outline">
              <image
                class="icon-img"
                src="/static/icon/slices/Frame@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">关注公众号</text>
          </view>
          <button
            class="function-item function-button"
            open-type="share"
            @tap="prepareHomeShare"
          >
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/slices/分享@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">推荐给好友</text>
          </button>
          <view class="function-item" @click="toHelp">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/slices/24＊24@2x(10).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">使用帮助</text>
          </view>
          <view class="function-item" @click="handleFeedback">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/24＊24@2x(1).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">建议反馈</text>
          </view>
          <view class="function-item" @click="openAgreement('rules')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/slices/24＊24@2x(10).png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">用户规则</text>
          </view>
          <view class="function-item" @click="openAgreement('user')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/slices/Frame@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">用户协议</text>
          </view>
          <view class="function-item" @click="openAgreement('privacy')">
            <view class="function-icon outline">
              <image class="icon-img"
                src="/static/icon/slices/Frame@2x.png"
                mode="aspectFit"
              ></image>
            </view>
            <text class="function-label">隐私政策</text>
          </view>
        </view>
      </view>
    </view>
    <!-- 立即新建弹窗组件 -->
    <CreatePopup :show.sync="showCreatePopup" @close="handleCreateClose">
    </CreatePopup>
    <SharePopup
      :visible="shareVisible"
      :title="getMerchantName()"
      :custom-title="homeShareTitle"
      :url="shareUrl"
      typeText="主页"
      type="home"
      :hid="userInfo.id"
      :uid="shareOwnerId"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
      @update:visible="(val) => (shareVisible = val)"
      @action="handleShareAction"
    />
    <CustomTabBar @createProduct="showCreatePopup = true" />
  </view>
</template>

<script>
import { getMiniCode } from "@/common/request/api.js";
import CustomTabBar from "@/components/CustomTabBar/index.vue";
import CreatePopup from "@/components/CreatePopup/index.vue";
import SharePopup from "@/components/SharePopup/index.vue";
import { computed } from "vue";

export default {
  components: {
    CustomTabBar,
    CreatePopup,
    SharePopup
  },
  data() {
    return {
      shareVisible: false,
      shareUrl: "/pages/index/index",
      shareMiniPath: "",
      shareMiniQr: "",
      statusBarHeight: 0,
      totalHeight: 0,
      navigationBarHeight: 44,
      userInfo: {},
      baseInfo: {},
      activeTab: 0, // 0: 我的选款, 1: 客户的选款
      currentTime: "9:41",
      showCreatePopup: false,
      defaultUserInfo: {
        nickname: "微信用户",
        avatar: "/static/image/headurl.jpg",
        all_space: "1G",
        space_used: "30.8M",
        product_count: 0,
        category_count: 0,
        view_count: 0,
        visitor_count: 0,
        view_badge: 0,
      },
      shareOwnerId: "",
      isUserInfoLoading: false,
      didLoadUserInfoOnLoad: false,
      showMemberUpgrade: false,
    };
  },
  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.updateTime();
    const cachedUserInfo = uni.getStorageSync("userInfo") || {};
    this.userInfo = this.normalizeUserInfo(cachedUserInfo);
    this.shareOwnerId = this.getOwnerId();
    this.shareUrl = this.buildHomeSharePath();
    this.baseInfo = uni.getStorageSync("baseInfo") || {};
    this.didLoadUserInfoOnLoad = true;
    this.getMemberUpgradeConfig();
    this.getUserInfo();
    this.getBaseInfo();
  },
  onShow() {
    if (this.didLoadUserInfoOnLoad) {
      this.didLoadUserInfoOnLoad = false;
      return;
    }
    this.getMemberUpgradeConfig();
    this.getUserInfo();
  },
  computed: {
    isSelectionListEnabled() {
      return Boolean(this.$config && this.$config.features && this.$config.features.selectionList);
    },
    displayName() {
      return this.userInfo.display_name || this.userInfo.nickname || "微信用户";
    },
    displayAvatar() {
      return this.userInfo.display_avatar || "/static/image/headurl.jpg";
    },
    memberName() {
      if (this.hasActiveMembership()) {
        return "标准会员";
      }
      const gradeName = this.safeText(this.userInfo.grade_name);
      return this.isFreeMemberName(gradeName) ? "免费版" : (gradeName || "免费版");
    },
    memberStatusType() {
      if (!this.hasActiveMembership()) return "free";
      return this.isMemberExpired() ? "expired" : "active";
    },
    memberStatusText() {
      if (this.memberStatusType === "active") return "会员中";
      if (this.memberStatusType === "expired") return "已过期";
      return "免费版";
    },
    memberExpireText() {
      const endTime =
        this.userInfo.end_time ||
        this.userInfo.vip_end_time ||
        this.userInfo.expire_time ||
        this.userInfo.membership_expire_at;
      if (!endTime || Number(endTime) === 0) {
        return this.memberStatusType === "free" ? "未开通会员" : "永久有效";
      }
      return `${this.formatDateValue(endTime)} 到期`;
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
    storagePercent() {
      return this.getPercent(this.storageUsedBytes, this.storageLimitBytes);
    },
    storageUsageText() {
      return `${this.formatBytes(this.storageUsedBytes)} / ${this.formatBytes(this.storageLimitBytes)}`;
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
    trafficPercent() {
      return this.getPercent(this.trafficUsedBytes, this.trafficLimitBytes);
    },
    trafficUsageText() {
      return `${this.formatBytes(this.trafficUsedBytes)} / ${this.formatBytes(this.trafficLimitBytes)}`;
    },
    isTrafficExceeded() {
      return Boolean(this.userInfo.monthly_traffic_exceeded) || (
        this.trafficLimitBytes > 0 && this.trafficUsedBytes > this.trafficLimitBytes
      );
    },
    homeShareTitle() {
      return (
        this.safeText(this.userInfo.home_share_title) ||
        `分享${this.getMerchantName()}的主页`
      );
    },
    shareCoverUrl() {
      return (
        this.safeText(this.userInfo.home_share_image) ||
        this.safeText(this.userInfo.company_logo) ||
        this.safeText(this.userInfo.display_avatar)
      );
    },
  },
  onShareAppMessage() {
    return {
      title: this.homeShareTitle,
      path: this.buildHomeSharePath(),
      imageUrl: this.shareCoverUrl,
    };
  },
  methods: {
    getOwnerId() {
      return (
        this.userInfo.id ||
        this.userInfo.uid ||
        (this.$getShareOwnerId ? this.$getShareOwnerId() : "")
      );
    },
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
    isFreeMembershipLevel(value) {
      const level = this.safeText(value).toLowerCase();
      return !level || level === "free" || level === "user";
    },
    isFreeMemberName(value) {
      const text = this.safeText(value);
      return !text || text === "普通用户" || text === "免费版" || text === "未开通会员";
    },
    hasActiveMembership() {
      const gradeLevel = Number(this.userInfo.grade_level || this.userInfo.vip_grade || 0);
      if (gradeLevel > 0) return true;
      if (!this.isFreeMembershipLevel(this.userInfo.membership_level)) return true;
      if (Number(this.userInfo.membership_plan_id || 0) > 0) return true;
      const freeBytes = 50 * 1024 * 1024;
      return Number(this.userInfo.resource_storage_capacity_bytes || 0) > freeBytes;
    },
    parseDateTime(value) {
      if (value === null || value === undefined || value === "") return 0;
      if (typeof value === "number" || /^\d+$/.test(String(value))) {
        const number = Number(value);
        if (number === 0) return 0;
        return number < 10000000000 ? number * 1000 : number;
      }
      const time = new Date(String(value).replace(/-/g, "/")).getTime();
      return Number.isFinite(time) ? time : 0;
    },
    isMemberExpired() {
      const endTime =
        this.userInfo.end_time ||
        this.userInfo.vip_end_time ||
        this.userInfo.expire_time ||
        this.userInfo.membership_expire_at;
      if (!this.hasActiveMembership() || !endTime || Number(endTime) === 0) return false;
      const time = this.parseDateTime(endTime);
      return time > 0 && time < Date.now();
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
    getMerchantName() {
      return (
        this.safeText(this.userInfo.company_name) ||
        this.safeText(this.userInfo.display_name) ||
        this.safeText(this.userInfo.nickname) ||
        "商户"
      );
    },
    buildHomeSharePath() {
      const ownerId = this.shareOwnerId || this.getOwnerId();
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("home", "", ownerId)
        : `/pages/index/index${ownerId ? `?uid=${ownerId}` : ""}`;
    },
    openShare() {
      if (!this.$checkLoginStatus()) {
        uni.showModal({
          title: "未登录，是否立即登录？",
          content: "",
          showCancel: true,
          success: ({ confirm, cancel }) => {
            if (confirm) {
              this.$silentLogin(this.uid);
            }
          },
        });
        return;
      }
      this.shareOwnerId = this.getOwnerId();
      this.shareUrl = this.buildHomeSharePath();
      this.shareVisible = true;
    },
    prepareHomeShare() {
      this.shareOwnerId = this.getOwnerId();
      this.shareUrl = this.buildHomeSharePath();
    },
    handleShareAction(event) {
      // event.type: 'share'|'copy'|'preview-mini'|'poster'|'open-settings'|'open-help'
      // event.payload 包含对应数据
      console.log("share action:", event);
      // 按需处理（多数情况父组件不必处理）
    },
    handleCreate() {
      this.showCreatePopup = true;
    },
    handleCreateClose() {
      // 弹窗关闭时的处理（可选）
      this.showCreatePopup = false;
    },
    updateTime() {
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, "0");
      const minutes = now.getMinutes().toString().padStart(2, "0");
      this.currentTime = `${hours}:${minutes}`;
    },
    formatStorage(size) {
      if (!size) return "0M";
      if (typeof size === "string") return size;
      if (size < 1024) return size + "M";
      return (size / 1024).toFixed(1) + "G";
    },
    getStatValue(field) {
      const value = Number(this.userInfo && this.userInfo[field]);
      return Number.isFinite(value) ? value : 0;
    },
    formatBadge(value) {
      const count = Number(value);
      if (!Number.isFinite(count) || count <= 0) return "";
      return count > 99 ? "99+" : String(count);
    },
    normalizeUserInfo(info = {}) {
      const merged = {
        ...this.defaultUserInfo,
        ...info,
      };
      const name =
        merged.nickname ||
        merged.nickName ||
        merged.company_name ||
        merged.name ||
        this.defaultUserInfo.nickname;
      const avatar =
        merged.avatar ||
        merged.avatarUrl ||
        merged.headimgurl ||
        merged.logo ||
        merged.company_logo ||
        this.defaultUserInfo.avatar;
      return {
        ...merged,
        nickname: name,
        avatar: this.normalizeImageUrl(avatar),
        display_name: name,
        display_avatar: this.normalizeImageUrl(avatar),
      };
    },
    normalizeImageUrl(url) {
      if (!url || typeof url !== "string") return "/static/image/headurl.jpg";
      if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("/static/")) {
        return url;
      }
      if (url.indexOf("users/user_default.png") !== -1 || url.startsWith("/image/")) {
        const domain = this.$config && this.$config.domain ? this.$config.domain : "https://api-test.jfyuntu.com";
        const path = url.startsWith("/") ? url : `/${url}`;
        return `${domain}${path}`;
      }
      if (url.indexOf("upimages/") !== -1) {
        return "/static/image/headurl.jpg";
      }
      const domain = this.$config && this.$config.domain ? this.$config.domain : "https://api-test.jfyuntu.com";
      const path = url.startsWith("/") ? url : `/${url}`;
      return `${domain}${path}`;
    },
    handleStatClick(type) {
      const routeMap = {
        product: "/pagesOther/productManage/productManage",
        category: "/pagesOther/classManage/classManage",
        views: "/pagesOther/visitorRecord/visitorRecord?from=views",
        visitor: "/pagesOther/visitorRecord/visitorRecord?from=visitor",
      };
      const url = routeMap[type];
      if (!url) return;
      if (type === "views") {
        this.userInfo = {
          ...this.userInfo,
          view_badge: 0,
        };
        uni.setStorageSync("userInfo", this.userInfo);
      }
      uni.navigateTo({ url });
    },
    handleMenu() {
      uni.showToast({
        title: "菜单",
        icon: "none",
      });
    },
    handleNotification() {
      uni.showToast({
        title: "通知",
        icon: "none",
      });
    },
    switchTab(index) {
      if (!this.isSelectionListEnabled) {
        return;
      }
      this.activeTab = index;
      const fromPage = index === 1 ? "customer" : "my";
      uni.navigateTo({
        url: "/pagesOther/myStyleList/myStyleList?fromPage=" + fromPage,
      });
    },
    handleCheckin() {
      uni.navigateTo({ url: "/pagesOther/signIn/signIn" });
    },
    handleFeedback() {
      uni.navigateTo({
        url: "/pagesOther/feedback/feedback",
      });
    },
    openAgreement(type) {
      uni.navigateTo({
        url: `/pagesOther/agreement/agreement?type=${type}`,
      });
    },
    handleService() {
      if (this.baseInfo && this.baseInfo.kf_link) {
        this.toWeb(this.baseInfo.kf_link);
        return;
      }
      uni.showToast({
        title: "暂未配置客服方式",
        icon: "none",
      });
    },
    toWeb(link) {
      if (!link) return;
      uni.navigateTo({
        url: `/pagesOther/webview/webview?link=${link}`,
      });
    },
    getBaseInfo() {
      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("common/base", data, "get", {
        show_err: false,
      })
        .then((res) => {
          if (res && res.data) {
            this.baseInfo = res.data;
            uni.setStorageSync("baseInfo", res.data);
          }
        })
        .catch(() => {});
    },
    getMemberUpgradeConfig() {
      this.$go("common/member_upgrade_config", {}, "get", {
        loading: false,
        show_err: false,
      })
        .then((res) => {
          const data = (res && res.data) || {};
          this.showMemberUpgrade = Number(data.show_upgrade) === 1;
        })
        .catch(() => {
          this.showMemberUpgrade = false;
        });
    },
    toHelp() {
      uni.navigateTo({
        url: "/pagesOther/usageHelp/usageHelp",
      });
    },
    getUserInfo() {
      if (this.isUserInfoLoading) return;
      const querys = {
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.isUserInfoLoading = true;
      this.$go("user/show_info", data, "get", {
        show_err: false,
      })
        .then((res) => {
          this.userInfo = this.normalizeUserInfo(res.data || {});
          this.shareOwnerId = this.getOwnerId();
          this.shareUrl = this.buildHomeSharePath();
          uni.setStorageSync("userInfo", this.userInfo);
        })
        .catch((err) => {
          console.error("获取用户信息失败:", err);
          this.userInfo = { ...this.defaultUserInfo };
        })
        .finally(() => {
          this.isUserInfoLoading = false;
        });
    },
    toPage(page) {
      if (page === "points") {
        uni.navigateTo({
          url: "/pagesOther/signIn/signIn",
        });
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/${page}/${page}`,
      });
    },
    toProfile() {
      uni.navigateTo({
        url: "/pagesOther/profile/profile",
      });
    },
    handleAvatarError() {
      this.userInfo = {
        ...this.userInfo,
        avatar: "/static/image/headurl.jpg",
        display_avatar: "/static/image/headurl.jpg",
      };
    },
    tovip() {
      if (!this.showMemberUpgrade) {
        return;
      }
      uni.navigateTo({
        url: "/pagesOther/vipPage/vipPage",
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  background:
    linear-gradient(180deg, rgba(255, 245, 189, 0.98) 0%, rgba(255, 250, 224, 0.92) 360rpx, #ffffff 520rpx);
  width: 100%;
  height: auto;
  min-height: 100vh;
  box-sizing: border-box;
  padding-bottom: 200rpx;
  overflow-x: hidden;
  overflow-y: visible;
}

/* 顶部状态栏 */
.top-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 30rpx;
  height: 44px;
}

.top-left {
  .time {
    font-size: 32rpx;
    font-weight: 600;
    color: #000;
  }
}

.top-right {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.nav-btn-circle {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
  background-color: rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn-text {
  font-size: 32rpx;
  color: #666;
}

/* 头部黄色背景区域 */
.header {
  padding-top: 88rpx;
  padding-bottom: 32rpx;
  position: relative;
  box-sizing: border-box;
  overflow: hidden;
  background: transparent;
}

.header-decor {
  position: absolute;
  top: 22rpx;
  right: -42rpx;
  width: 430rpx;
  height: 430rpx;
  opacity: 0.2;
  pointer-events: none;
  z-index: 0;
}

.header::after {
  display: none;
}

/* 用户信息卡片 */
.user-card {
  display: flex;
  align-items: center;
  padding: 0 30rpx;
  margin-bottom: 40rpx;
  position: relative;
  z-index: 1;
  box-sizing: border-box;
}

.avatar-container {
  margin-right: 24rpx;
  flex-shrink: 0;
}

.avatar-bg {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 12rpx 28rpx rgba(186, 132, 40, 0.16);
  border: 4rpx solid rgba(255, 255, 255, 0.86);
  overflow: hidden;
  .avatar-icon {
    width: 100%;
    height: 100%;
    border-radius: 50%;
  }
}

.user-info {
  flex: 1;
  min-width: 0;
  padding-right: 12rpx;
}

.user-name-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-bottom: 12rpx;
}

.user-name {
  font-weight: bold;
  font-size: 32rpx;
  color: #333333;
  max-width: 260rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.upgrade-badge {
  width: 110rpx;
  height: 40rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #333333;
  border-radius: 40rpx;
}

.badge-text {
  font-weight: bold;
  font-size: 20rpx;
  color: #ffd000;
}

.user-storage {
  display: flex;
  align-items: center;
  gap: 16rpx;

  .storage-text {
    font-weight: 400;
    font-size: 24rpx;
    color: #9d6c1d;
  }

  .storage-mb {
    font-weight: 400;
    font-size: 24rpx;
    color: #333333;
  }

  .storage-gb {
    color: #999999;
  }
}

.camera-icon {
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .icon-img {
    width: 40rpx;
    height: 40rpx;
  }
}

/* 统计栏 */
.stats-row {
  display: flex;
  justify-content: space-around;
  padding: 0 30rpx;
  margin-bottom: 32rpx;
  position: relative;
  z-index: 1;
  box-sizing: border-box;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  min-width: 120rpx;
  padding: 8rpx 0;
  border-radius: 12rpx;
}

.stat-item:active {
  background: rgba(255, 216, 0, 0.18);
}

.stat-number-wrapper {
  position: relative;
}

.stat-number {
  font-weight: 500;
  font-size: 32rpx;
  color: #333333;
}

.stat-badge {
  position: absolute;
  top: -8rpx;
  right: -30rpx;
  min-width: 32rpx;
  height: 32rpx;
  padding: 0 8rpx;
  background-color: #ff3b30;
  border-radius: 999rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}

.badge-number {
  font-size: 18rpx;
  color: #ffffff;
  font-weight: bold;
}

.stat-label {
  font-weight: 400;
  font-size: 24rpx;
  color: #666666;
}

/* 两个大按钮 */
.action-buttons {
  display: flex;
  gap: 20rpx;
  padding: 0 30rpx;
  margin-bottom: 20rpx;
  position: relative;
  z-index: 1;
  min-height: 136rpx;
  box-sizing: border-box;
}

.action-btn {
  flex: 1;
  height: 136rpx;
  background: linear-gradient(135deg, rgba(255, 248, 206, 0.96) 0%, #ffd93d 100%);
  border-radius: 24rpx;
  padding: 24rpx;
  box-sizing: border-box;
  box-shadow: 0 14rpx 28rpx rgba(210, 158, 30, 0.16);
  border: 1rpx solid rgba(255, 255, 255, 0.72);
}

.btn-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 100%;
}

.btn-text-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.btn-title {
  font-weight: bold;
  font-size: 32rpx;
  color: #b74f00;
}

.btn-subtitle {
  font-weight: 400;
  font-size: 24rpx;
  color: #666666;
}

.btn-icon {
  width: 64rpx;
  height: 64rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  .icon-img {
    width: 100%;
    height: 100%;
  }
}

/* 标签导航 */
.tabs-nav {
  display: flex;
  align-items: center;
  padding: 0 30rpx;
  margin-bottom: 40rpx;
  background: #eeeeee;
  border-radius: 16rpx 16rpx 16rpx 16rpx;
  margin: 0 30rpx;
  position: relative;
  z-index: 1;
}

.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 20rpx 0;
  position: relative;
}

.tab-icon {
  width: 48rpx;
  height: 48rpx;
}

.tab-badge {
  position: absolute;
  top: -4rpx;
  right: -4rpx;
  width: 16rpx;
  height: 16rpx;
  background-color: #ffd700;
  border-radius: 50%;
  border: 2rpx solid #ffffff;
}

.tab-text {
  font-weight: 400;
  font-size: 32rpx;
  color: #333333;
}

.tab-divider {
  width: 2rpx;
  height: 40rpx;
  background-color: #e5e5e5;
}

/* 主要内容区域 */
.content {
  padding: 0 30rpx;
  background-color: #ffffff;
  border-radius: 0;
  padding-top: 28rpx;
  position: static;
  z-index: auto;
}

.section {
  margin-bottom: 60rpx;
}

.section-title {
  font-weight: bold;
  font-size: 28rpx;
  color: #333333;
  margin-bottom: 20rpx;
}

.functions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 30rpx 20rpx;
}

.function-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
}

.function-icon {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: visible;

  .icon-img {
    width: 56rpx;
    height: 56rpx;
    display: block;
    object-fit: contain;
  }

  &.outline .icon-img {
    width: 54rpx;
    height: 54rpx;
  }
}

.other-section {
  .function-item {
    gap: 14rpx;
  }

  .function-icon {
    width: 72rpx;
    height: 72rpx;
    overflow: hidden;

    .icon-img {
      width: 48rpx;
      height: 48rpx;
      display: block;
      object-fit: contain;
    }
  }
}

.function-label {
  font-size: 24rpx;
  color: #333;
  text-align: center;
}

.function-button {
  background: none;
  border: none;
  padding: 0;
  margin: 0;
  line-height: normal;
  overflow: visible;
}

.function-button::after {
  border: none;
}

</style>
