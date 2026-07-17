<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 头部：复用用户卡组件 -->
      <user-card
        v-if="shouldShowOwnerCard"
        :avatar="user.avatar"
        :name="user.name"
        :subtitle="user.subtitle"
        :show-contact="shouldShowContactButton"
        @contact="contactOwner"
      />

      <!-- 分类标题与简介 -->
      <view v-if="shouldShowCategoryPanel" class="category-panel">
        <view class="category-title-row">
          <image
            src="/static/icon/Frame 1171279070@2x.png"
            class="category-icon"
            mode="widthFix"
          />
          <view class="title-wrap">
            <text class="category-title">{{ displayCategoryTitle }}</text>
            <text class="category-desc" v-if="category.desc">{{
              category.desc
            }}</text>
          </view>
        </view>
      </view>

      <view v-if="canManageChildCategory" class="child-actions">
        <view class="child-action-btn primary" @tap="createChildCategory">
          <image
            class="child-action-icon"
            src="/static/icon/add-yellow-icon.png"
            mode="scaleToFill"
          />
          <text class="child-action-text">新建子分类</text>
        </view>
        <view class="child-action-btn" @tap="openShare">
          <image
            class="child-action-icon"
            src="/static/icon/分享@2x.png"
            mode="scaleToFill"
          />
          <text class="child-action-text">分享分类</text>
        </view>
        <view class="child-action-btn" @tap="openChildSort">
          <image
            class="child-action-icon"
            src="/static/icon/image-3@2x(2).png"
            mode="scaleToFill"
          />
          <text class="child-action-text">子分类排序</text>
        </view>
      </view>

      <view v-if="pageError && !loading" class="state-card">
        <image
          src="/static/icon/lock-2.png"
          mode="scaleToFill"
          class="state-icon"
        />
        <text class="state-title">暂时无法访问</text>
        <text class="state-desc">{{ pageError }}</text>
        <view class="state-btn" @tap="retryLoad">重新加载</view>
      </view>

      <!-- 子分类与产品列表 -->
      <view class="content" v-if="!pageError && hasContent">
        <view v-if="hasChildren" class="section-block">
          <view class="section-header">
            <view class="section-title-wrap">
              <text class="section-emoji">📁</text>
              <text class="section-title">子分类管理</text>
              <text class="section-count">{{ children.length }}</text>
            </view>
          </view>

          <view class="manage-category-grid">
            <view
              class="manage-folder-cell"
              v-for="(category, index) in children"
              :key="getCategoryKey(category, index)"
            >
              <view
                class="manage-folder-tile"
                @tap="handleCategoryClick(category)"
              >
                <view class="manage-folder-icon">
                  <view class="manage-folder-tab"></view>
                  <view class="manage-folder-body"></view>
                </view>
                <text class="manage-folder-title">{{
                  getDisplayCategoryName(category)
                }}</text>
                <text class="manage-folder-meta"
                  >{{ getChildCount(category) }}个子分类</text
                >
              </view>
            </view>
          </view>
        </view>

        <view v-if="hasProducts" class="section-block product-section">
          <view class="section-header">
            <view class="section-title-wrap">
              <text class="section-emoji">🧾</text>
              <text class="section-title">产品列表</text>
              <text class="section-count">{{ products.length }}</text>
            </view>
          </view>

          <ImageGrid
            :list="productContent"
            :columns="2"
            card-variant="class-detail"
            @click="handleProductGridClick"
          >
          </ImageGrid>
        </view>
      </view>
      <!-- 无内容时的占位 -->
      <view v-if="!pageError && !hasContent && !loading" class="class-empty">
        <view v-if="canManageCategoryContent" class="empty-visual">
          <view class="visual-card visual-card-back"></view>
          <view class="visual-card visual-card-front">
            <view class="visual-image"></view>
            <view class="visual-lines">
              <view class="visual-line visual-line-long"></view>
              <view class="visual-line visual-line-short"></view>
            </view>
          </view>
          <view class="visual-plus">+</view>
        </view>
        <text v-if="canManageCategoryContent" class="empty-title">当前合集还没有作品</text>
        <text class="empty-text">{{ canManageCategoryContent ? "新建产品或从已有产品中添加，作品会展示在这里" : emptyMessage }}</text>
        <view v-if="canManageCategoryContent" class="actions">
          <view class="btn" @tap="createProductInCategory">
            <image
              class="btn-icon"
              src="/static/icon/add-yellow-icon.png"
              mode="scaleToFill"
            />
            <text class="btn-text">新建产品</text>
          </view>
          <view class="btn secondary" @tap="selectExistingProducts">
            <image
              class="btn-icon"
              src="/static/icon/image-3@2x(2).png"
              mode="scaleToFill"
            />
            <text class="btn-text">添加产品</text>
          </view>
        </view>
      </view>

      <!-- 底部固定操作栏 -->
      <view v-if="!pageError" class="bottom-bar">
        <view v-if="shouldShowContactButton" class="action-btn" @tap="contactOwner">
          <image
            src="/static/icon/user.png"
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">{{ serviceActionLabel }}</text>
        </view>

        <view v-if="uid" class="action-btn" @tap="handleFavorite">
          <image
            :src="
              isFavorited
                ? '/static/icon/star@2x(1).png'
                : '/static/icon/star@2x(2).png'
            "
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">{{ isFavorited ? "已收藏" : "收藏" }}</text>
        </view>

        <view v-if="canShareCategory" class="action-btn" @tap="openShare">
          <image
            src="/static/icon/24＊24@2x(4).png"
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">分享</text>
        </view>

        <view v-if="!uid" class="action-btn" @tap="openCategorySettings">
          <image
            src="/static/icon/Frame.png"
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">设置</text>
        </view>
      </view>
    </view>

    <personal-details
      :use-popup="true"
      :uid="getShareOwnerId() || ''"
      :visible="personalVisible"
      @update:visible="(val) => (personalVisible = val)"
    />

    <!-- 分享弹窗（复用组件） -->
    <share-popup
      :visible="shareVisible"
      :title="getCategoryShareName()"
      :uid="getShareOwnerId() || ''"
      typeText="分类"
      type="category"
      :hid="categoryId"
      :url="shareUrl"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
      :cover-url="shareCoverUrl"
      @update:visible="(val) => (shareVisible = val)"
      @action="onShareAction"
    />
  </view>
</template>

<script>
import UserCard from "@/components/UserCard";
import ImageGrid from "@/components/ImageGrid";
import SharePopup from "@/components/SharePopup";
import PersonalDetails from "@/components/PersonalDetails/index.vue";
import {
  consumeRefreshMarker,
  markRefreshMarkerConsumed,
} from "@/common/helper/refresh.js";
import { ensureSharedPageLogin } from "@/common/helper/shareLogin.js";
import { imageUrlFor } from "@/common/helper/imageUrls.js";
import {
  getObjectId,
  resolveClickedListItem,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";
import { getUserInfo } from "@/common/request/api.js";

export default {
  components: { UserCard, ImageGrid, SharePopup, PersonalDetails },
  data() {
    return {
      personalVisible: false,
      user: {
        avatar: "",
        name: "",
        subtitle: "",
        serviceName: "",
        logo: "",
        shareImage: "",
      },
      categoryId: null,
      category: {
        title: "",
        desc: "",
        pid: 0,
        level: 1,
        private_type: 1,
        show_connect: 1,
        other_share: 0,
        set_top: 0,
        share_version: 0,
      },
      children: [],
      products: [],
      loading: false,
      pageError: "",
      isFavorited: false,
      shareVisible: false,
      shareTitle: "",
      shareUrl: "",
      shareMiniQr: "",
      shareMiniPath: "",
      minShowCount: 1, // 控制“没有更多了~”显示逻辑
      columns: 2,
      uid: "", // 用户分享的用户ID
      routeShareVersion: "",
      shareOwnerId: "",
      lastCategoryRefreshAt: "",
      createdPreview: false,
      directShareAccess: false,
      defaultShareCover: "/static/icon/share-category-empty.png",
      shareLoginPending: false,
      hasStartedInitialLoad: false,
    };
  },
  computed: {
    hasChildren() {
      return !!this.children.length;
    },
    hasProducts() {
      return !!this.products.length;
    },
    hasContent() {
      return this.hasChildren || this.hasProducts;
    },
    mixedContent() {
      return [
        ...this.children.map((item) => ({
          ...item,
          item_type: "category",
          badgeSuffix: "个",
        })),
        ...this.products.map((item) => ({
          ...item,
          item_type: "product",
          nameField: item.folder_name,
          imageField: item.new_thumb,
          countField: item.folder_count,
          badgeSuffix: "张",
        })),
      ];
    },
    productContent() {
      return this.products.map((item) => ({
        ...item,
        item_type: "product",
        nameField: item.folder_name || "未命名产品",
        imageField: item.new_thumb || item.imageField,
        countField: item.folder_count,
        badgeSuffix: "张",
      }));
    },
    totalContentCount() {
      return this.children.length + this.products.length;
    },
    displayColumns() {
      return 2;
    },
    shouldShowLessThanMinHint() {
      return this.totalContentCount > 0 && this.totalContentCount < this.minShowCount;
    },
    canManageChildCategory() {
      return !this.uid && this.isTopLevelCategory;
    },
    canManageCategoryContent() {
      return !this.uid && !!this.categoryId;
    },
    hasOwnerInfo() {
      return !!(
        this.normalizeText(this.user.name) ||
        this.normalizeText(this.user.avatar) ||
        this.normalizeText(this.user.subtitle)
      );
    },
    hasCategoryInfo() {
      return !!(
        this.normalizeText(this.category.title) ||
        this.normalizeText(this.category.desc)
      );
    },
    shouldShowOwnerCard() {
      return !this.uid || this.hasOwnerInfo;
    },
    shouldShowCategoryPanel() {
      return !this.uid || this.hasCategoryInfo;
    },
    displayCategoryTitle() {
      return this.normalizeText(this.category.title) || "分类名称";
    },
    emptyMessage() {
      if (!this.uid) return "暂无内容";
      if (Number(this.category.private_type) === 4) {
        return "该分类仅分享可见，暂无公开内容";
      }
      return "该分类暂无公开内容";
    },
    isTopLevelCategory() {
      const pid = Number(this.category.pid || 0);
      const level = Number(this.category.level || 1);
      return pid === 0 && level <= 1;
    },
    serviceActionLabel() {
      return this.normalizeText(this.user.serviceName) || "服务";
    },
    shouldShowContactButton() {
      return !!this.uid && Number(this.category.show_connect) !== 0;
    },
    canShareCategory() {
      if (Number(this.category.private_type) === 2) return false;
      if (this.uid && Number(this.category.other_share) === 1) return false;
      return true;
    },
    shareCoverUrl() {
      const product = this.products.find((item) =>
        this.normalizeText(
          item.share_image ||
            item.new_thumb ||
            item.imageField ||
            item.picture_url ||
            item.imgurl,
        ),
      );
      return (
        this.normalizeText(
          product &&
            (product.share_image ||
              product.new_thumb ||
              product.imageField ||
              product.picture_url ||
              product.imgurl),
        ) ||
        this.defaultShareCover
      );
    },
  },
  onLoad(options) {
    const sceneOptions = this.parseSceneOptions(options);
    options = {
      ...(options || {}),
      ...sceneOptions,
    };
    // 支持通过 options 传入分类 id
    if (options && options.id) this.categoryId = options.id;
    this.uid = this.resolveOwnerUid(options);
    this.routeShareVersion = this.normalizeShareParam(options.share_v || options.sv || "");
    this.createdPreview = !this.uid && String(options.created_preview || "") === "1";
    this.directShareAccess = !!(
      this.uid &&
      (
        this.normalizeShareParam(options.source) === "share" ||
        this.routeShareVersion
      )
    );
    this.debugShare("onLoad-options", {
      options,
      sceneOptions,
      categoryId: this.categoryId,
      uid: this.uid,
      directShareAccess: this.directShareAccess,
      hasToken: !!uni.getStorageSync("token"),
      hasUserInfo: !!uni.getStorageSync("userInfo"),
    });
    this.applyRouteCategoryOptions(options);
    if (!this.categoryId) {
      this.debugShare("onLoad-missing-category", { options });
      this.setPageError("分类信息缺失，请返回后重试");
      return;
    }
    if (this.createdPreview) {
      this.applyCreatedPreviewOptions(options);
    }
    this.shareOwnerId = this.uid;
    this.shareUrl = this.buildShareUrl();
    if (this.directShareAccess && !ensureSharedPageLogin("pagesOther/classDetail/classDetail", options, this.uid, { strict: true })) {
      this.shareLoginPending = true;
      return;
    }
    this.shareLoginPending = false;
    this.initUserFromCache();

    // 通过 eventChannel 接收上一页面传递的数据
    const eventChannel = this.getOpenerEventChannel();
    if (eventChannel) {
      eventChannel.on("acceptDataFromOpenerPage", (data) => {
        this.debugShare("eventChannel-category-data", {
          hasData: !!(data && data.data),
          id: data && data.data && data.data.id,
          folderName: data && data.data && data.data.folder_name,
        });
        if (data && data.data) {
          const classDetailData = data.data;
          this.isFavorited = classDetailData.is_collect ? true : false;
          this.applyOwnerFromCategory(classDetailData);
          // 使用完整数据预填充
          this.category.title = this.extractCategoryName(classDetailData);
          this.category.desc =
            classDetailData.folder_desc ||
            classDetailData.category_desc ||
            classDetailData.class_desc ||
            classDetailData.desc ||
            "";
          if (classDetailData.private_type !== undefined && classDetailData.private_type !== null) {
            this.category.private_type = this.normalizePrivateType(classDetailData.private_type);
          }
          this.applyCategorySwitchOptions(classDetailData);
          this.applyCategoryLevel(classDetailData);
          this.columns = this.normalizeColumns(
            classDetailData.layout_type || classDetailData.pic_layout || 2
          );
          this.updateShareMeta();
        }
      });
    }

    if (!this.createdPreview) {
      if (!this.directShareAccess) {
        this.loadOwnerInfo();
      }
      this.debugShare("onLoad-start-load", {
        createdPreview: this.createdPreview,
        directShareAccess: this.directShareAccess,
      });
      this.hasStartedInitialLoad = true;
      this.loadInitialData();
    }
    const token = uni.getStorageSync("token");
    if (token && !this.createdPreview) {
      this.$addVisit({ id: options.id, type: "category" });
    }
    // 监听分类更新事件
    uni.$on("refreshClassDetailData", this.handleRefreshData);
  },
  onShow() {
    if (this.createdPreview) return;
    this.syncShareMenu();
    this.resumeShareLoadAfterLogin();
    this.consumeCategoryRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshClassDetailData", this.handleRefreshData);
  },
  onShareAppMessage() {
    if (!this.canShareCategory) {
      uni.showToast({ title: "该分类不允许分享", icon: "none" });
      return {
        title: this.shareTitle || this.buildCategoryShareTitle(),
        path: this.shareUrl || this.buildShareUrl(),
        imageUrl: this.shareCoverUrl,
      };
    }
    this.updateShareMeta();
    return {
      title: this.shareTitle || this.buildCategoryShareTitle(),
      path: this.shareUrl || this.buildShareUrl(),
      imageUrl: this.shareCoverUrl,
    };
  },
  methods: {
    debugShare(stage, payload = {}) {
      if (!this.uid && !this.directShareAccess) return;
      try {
        const logger = typeof console !== "undefined" ? console : null;
        if (!logger || !logger.log) return;
        logger.log("[classDetail-share-debug]", stage, {
          categoryId: this.categoryId,
          uid: this.uid,
          directShareAccess: this.directShareAccess,
          loading: this.loading,
          pageError: this.pageError,
          childrenCount: this.children.length,
          productsCount: this.products.length,
          hasContent: this.hasContent,
          ...payload,
        });
      } catch (e) {}
    },
    normalizeText(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    getResponseErrorMessage(res, fallback = "该分类暂不可访问") {
      return (
        this.normalizeText(res && res.msg) ||
        this.normalizeText(res && res.message) ||
        fallback
      );
    },
    getExceptionMessage(err, fallback = "该分类暂不可访问") {
      return (
        this.normalizeText(err && err.msg) ||
        this.normalizeText(err && err.message) ||
        fallback
      );
    },
    safeDecodeRouteValue(value) {
      const text = this.normalizeText(value);
      if (!text) return "";
      try {
        return decodeURIComponent(text);
      } catch (e) {
        return text;
      }
    },
    applyRouteCategoryOptions(options = {}) {
      const routeName = this.safeDecodeRouteValue(
        options.category_name || options.name || options.title || "",
      );
      const routeDesc = this.safeDecodeRouteValue(
        options.category_desc || options.desc || "",
      );
      if (routeName) {
        this.category.title = routeName;
      }
      if (routeDesc) {
        this.category.desc = routeDesc;
      }
      if (options.private_type !== undefined && options.private_type !== null) {
        this.category.private_type = this.normalizePrivateType(options.private_type);
      }
      this.applyCategorySwitchOptions(options);
      if (options.parent_id !== undefined || options.pid !== undefined) {
        this.category.pid = Number(options.parent_id || options.pid || 0);
        this.category.level = this.category.pid > 0 ? 2 : 1;
      }
      const layoutValue =
        options.layout_type !== undefined &&
        options.layout_type !== null &&
        options.layout_type !== ""
          ? options.layout_type
          : options.pic_layout;
      if (layoutValue !== undefined && layoutValue !== null && layoutValue !== "") {
        this.columns = this.normalizeColumns(layoutValue);
      }
      this.updateShareMeta();
    },
    applyCreatedPreviewOptions(options = {}) {
      this.category.title =
        this.safeDecodeRouteValue(options.name) || this.category.title;
      this.category.desc =
        this.safeDecodeRouteValue(options.desc) || this.category.desc;
      if (options.private_type !== undefined && options.private_type !== null) {
        this.category.private_type = this.normalizePrivateType(options.private_type);
      }
      this.applyCategorySwitchOptions(options);
      this.category.pid = Number(options.parent_id || options.pid || 0);
      this.category.level = this.category.pid > 0 ? 2 : 1;
      if (options.layout_type !== undefined && options.layout_type !== null) {
        this.columns = this.normalizeColumns(options.layout_type);
      }
      this.children = [];
      this.products = [];
      this.clearPageError();
      this.updateShareMeta();
    },
    setPageError(message) {
      this.pageError = this.normalizeText(message) || "该分类暂不可访问";
      this.children = [];
      this.products = [];
      this.debugShare("setPageError", { message: this.pageError });
    },
    clearPageError() {
      this.pageError = "";
    },
    async loadInitialData() {
      this.loading = true;
      this.clearPageError();
      this.debugShare("loadInitialData-start");
      try {
        if (this.uid) {
          const loaded = await this.loadPublicCategoryInfo();
          this.debugShare("loadInitialData-public-loaded", { loaded });
          if (!loaded) return;
        }
        await this.loadCategoryContent({ manageLoading: false });
      } finally {
        this.loading = false;
        this.debugShare("loadInitialData-end");
      }
    },
    retryLoad() {
      this.hasStartedInitialLoad = true;
      this.loadInitialData();
    },
    resumeShareLoadAfterLogin() {
      if (!this.directShareAccess || this.loading || this.hasContent) return;
      if (this.$checkLoginStatus && !this.$checkLoginStatus()) return;
      if (!this.shareLoginPending && this.hasStartedInitialLoad) return;
      this.debugShare("resumeShareLoadAfterLogin", {
        shareLoginPending: this.shareLoginPending,
        hasStartedInitialLoad: this.hasStartedInitialLoad,
      });
      this.shareLoginPending = false;
      this.hasStartedInitialLoad = true;
      this.initUserFromCache();
      this.loadInitialData();
    },
    initUserFromCache() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
      const userInfo = uni.getStorageSync("userInfo") || {};
      const info = this.uid
        ? {}
        : enterpriseInfo.company_name
          ? enterpriseInfo
          : userInfo;
      this.applyUserInfo(info);
    },
    applyUserInfo(info = {}) {
      const ownerId =
        this.normalizeShareParam(info.id) ||
        this.normalizeShareParam(info.uid) ||
        this.normalizeShareParam(info.user_id);
      if (!this.uid && ownerId && !this.shareOwnerId) {
        this.shareOwnerId = ownerId;
      }
      const avatar =
        info.company_logo ||
        info.avatar ||
        info.headimgurl ||
        info.head_url ||
        "";
      const logo = info.company_logo || info.logo || "";
      const shareImage = info.home_share_image || info.share_image || "";
      const name =
        info.company_name ||
        info.nickname ||
        info.nick_name ||
        info.name ||
        "";
      const subtitle = info.company_desc || info.desc || "";
      this.user = {
        avatar: avatar || this.user.avatar,
        name: name || this.user.name,
        subtitle: subtitle || this.user.subtitle,
        serviceName: this.normalizeText(info.home_service_name) || this.user.serviceName,
        logo: logo || this.user.logo,
        shareImage: shareImage || this.user.shareImage,
      };
      this.updateShareMeta();
    },
    getCategoryShareName() {
      return this.$getCategoryShareName
        ? this.$getCategoryShareName(this.category)
        : this.normalizeText(this.category.title) || "分类";
    },
    getMerchantShareName() {
      return this.$getMerchantShareName
        ? this.$getMerchantShareName(this.user)
        : this.normalizeText(this.user.name) || "商户";
    },
    buildCategoryShareTitle() {
      return this.$buildTypedShareTitle
        ? this.$buildTypedShareTitle({
            typeText: "分类",
            targetName: this.getCategoryShareName(),
            merchantName: this.getMerchantShareName(),
          })
        : `${this.getMerchantShareName()}的${this.getCategoryShareName()}`;
    },
    updateShareMeta() {
      this.shareTitle = this.buildCategoryShareTitle();
      this.shareUrl = this.buildShareUrl();
    },
    applyCategorySwitchOptions(info = {}) {
      if (info.show_connect !== undefined && info.show_connect !== null && info.show_connect !== "") {
        this.category.show_connect = Number(info.show_connect) === 0 ? 0 : 1;
      }
      if (info.other_share !== undefined && info.other_share !== null && info.other_share !== "") {
        this.category.other_share = Number(info.other_share) === 1 ? 1 : 0;
      }
      if (info.set_top !== undefined && info.set_top !== null && info.set_top !== "") {
        this.category.set_top = Number(info.set_top) === 1 ? 1 : 0;
      }
      if (info.share_version !== undefined && info.share_version !== null && info.share_version !== "") {
        this.category.share_version = Number(info.share_version) || 0;
      }
      this.syncShareMenu();
    },
    syncShareMenu() {
      if (typeof wx === "undefined") return;
      try {
        if (this.canShareCategory) {
          wx.showShareMenu && wx.showShareMenu({ withShareTicket: true });
        } else {
          wx.hideShareMenu && wx.hideShareMenu();
        }
      } catch (e) {}
    },
    loadOwnerInfo() {
      if (this.uid) {
        this.shareOwnerId = this.uid;
        this.loadUserInfo(this.uid);
        return;
      }
      const cachedUser = uni.getStorageSync("userInfo") || {};
      const ownerId = cachedUser.id || cachedUser.uid || "";
      if (ownerId) {
        this.loadUserInfo(ownerId);
        return;
      }
      this.loadCurrentUserInfo();
    },
    async loadCurrentUserInfo() {
      try {
        if (this.uid || !this.$go) return;
        await getUserInfo();
        const cachedUser = uni.getStorageSync("userInfo") || {};
        const cachedOwnerId =
          this.normalizeShareParam(cachedUser.id) ||
          this.normalizeShareParam(cachedUser.uid) ||
          this.normalizeShareParam(cachedUser.user_id);
        if (cachedOwnerId) {
          this.shareOwnerId = cachedOwnerId;
          this.applyUserInfo(cachedUser);
          this.shareUrl = this.buildShareUrl();
          return;
        }
        const res = await this.$go("user/home/info", {}, "get", {
          show_err: false,
          loading: false,
        });
        if (res && res.code === 0 && res.data) {
          this.applyUserInfo(res.data);
          const ownerId =
            this.normalizeShareParam(res.data.id) ||
            this.normalizeShareParam(res.data.uid) ||
            this.normalizeShareParam(res.data.user_id);
          if (ownerId) {
            this.shareOwnerId = ownerId;
            this.shareUrl = this.buildShareUrl();
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    applyOwnerFromCategory(info = {}) {
      if (this.uid || this.shareOwnerId) return;
      const ownerId =
        this.normalizeShareParam(info.uid) ||
        this.normalizeShareParam(info.user_id) ||
        this.normalizeShareParam(info.owner_uid) ||
        this.normalizeShareParam(info.target_user_id);
      if (!ownerId) return;
      this.shareOwnerId = ownerId;
      this.shareUrl = this.buildShareUrl();
      this.loadUserInfo(ownerId);
    },
    normalizeColumns(value) {
      return Number(value) === 2 ? 1 : 2;
    },
    extractCategoryName(info = {}) {
      return (
        this.normalizeText(info.folder_name) ||
        this.normalizeText(info.category_name) ||
        this.normalizeText(info.class_name) ||
        this.normalizeText(info.title) ||
        this.normalizeText(info.name) ||
        ""
      );
    },
    buildShareUrl() {
      if (!this.categoryId) {
        return "/pagesOther/classDetail/classDetail";
      }
      const ownerId =
        this.getShareOwnerId();
      const shareVersion = Number(this.category.share_version || 0);
      const shareVersionQuery = shareVersion ? `&share_v=${encodeURIComponent(shareVersion)}` : "";
      const path = this.$buildPublicSharePath
        ? `${this.$buildPublicSharePath("category", this.categoryId, ownerId)}${shareVersionQuery}`
        : `/pagesOther/classDetail/classDetail?id=${this.categoryId}${ownerId ? `&uid=${ownerId}` : ""}${shareVersionQuery}`;
      return `${path}${path.indexOf("?") === -1 ? "?" : "&"}source=share`;
    },
    getShareOwnerId() {
      return (
        this.shareOwnerId ||
        this.uid ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "")
      );
    },
    parseSceneOptions(options = {}) {
      if (!options.scene || !this.$parseShareScene) return {};
      return this.$parseShareScene(options.scene);
    },
    normalizeShareParam(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    resolveOwnerUid(options = {}) {
      return (
        this.normalizeShareParam(options.uid) ||
        this.normalizeShareParam(options.target_user_id) ||
        this.normalizeShareParam(options.share_uid) ||
        this.normalizeShareParam(options.owner_uid) ||
        this.normalizeShareParam(options.user_id)
      );
    },
    async loadUserInfo(targetUserId = this.uid) {
      try {
        if (!targetUserId || !this.$go) return;
        const data = {
          target_user_id: targetUserId,
        };
        const res = await this.$go("user/home/info", data, "get", {
          show_err: false,
        });
        if (res && res.data) {
          this.shareOwnerId = targetUserId;
          this.applyUserInfo(res.data);
          this.updateShareMeta();
          if (!this.uid) {
            uni.setStorageSync("enterpriseInfo", res.data);
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    async loadPublicCategoryInfo() {
      if (!this.uid || !this.categoryId || !this.$go) return false;
      try {
        const params = {
          target_user_id: this.uid,
          fid: this.categoryId,
          include_current: 1,
          share_v: this.routeShareVersion,
          direct_share: this.directShareAccess ? 1 : 0,
        };
        this.debugShare("loadPublicCategoryInfo-request", {
          url: "user/home/categories",
          method: "get",
          params,
        });
        const res = await this.$go(
          "user/home/categories",
          params,
          "get",
          { show_err: false }
        );
        this.debugShare("loadPublicCategoryInfo-response", {
          code: res && res.code,
          msg: res && (res.msg || res.message),
          hasData: !!(res && res.data),
          rawChildrenCount: this.getChildrenFromResponse(res && res.data).length,
          rawProductsCount: this.getProductsFromResponse(res && res.data).length,
          hasFolderInfo: !!(res && res.data && res.data.folder_info),
          folderInfoId: res && res.data && res.data.folder_info && res.data.folder_info.id,
        });
        if (!res || res.code !== 0) {
          this.setPageError(this.getResponseErrorMessage(res));
          return false;
        }
        const data = res && res.data ? res.data : {};
        this.applyUserInfo(data.user_info || data.user || {});
        if (data.user_info && (data.user_info.id || data.user_info.uid)) {
          this.shareOwnerId = data.user_info.id || data.user_info.uid;
        }
        const current =
          data.folder_info ||
          data.current ||
          this.findCategoryInTree(this.getChildrenFromResponse(data), this.categoryId) ||
          this.findCategoryInTree(Array.isArray(data.categories) ? data.categories : [], this.categoryId);
        if (!current) {
          this.setPageError("分类不存在或暂不可访问");
          return false;
        }
        this.applyCategoryInfo(current);
        const children = this.getChildrenFromResponse(data);
        this.applyChildrenList(children);
        const products = this.getProductsFromResponse(data);
        this.products = products.map((item) => this.normalizeProductItem(item));
        if (!this.hasContent && this.directShareAccess) {
          await this.loadCategoryContent({ manageLoading: false });
        }
        if (children.length || products.length) {
          this.clearPageError();
        }
        this.debugShare("loadPublicCategoryInfo-applied", {
          childrenFromResponse: children.length,
          productsFromResponse: products.length,
          childrenCount: this.children.length,
          productsCount: this.products.length,
          hasContent: this.hasContent,
          pageError: this.pageError,
        });
        return true;
      } catch (e) {
        console.error("[classDetail-share-debug]", "loadPublicCategoryInfo-error", e);
        this.setPageError(this.getExceptionMessage(e));
        return false;
      }
    },
    handleRefreshData(marker) {
      this.markCategoryRefreshConsumed(marker);
      this.createdPreview = false;
      this.loadInitialData();
    },
    consumeCategoryRefreshMarker() {
      if (this.uid || this.createdPreview) return;
      const marker = consumeRefreshMarker(
        "category",
        "categoryListNeedsRefreshDetailConsumed",
        this.lastCategoryRefreshAt,
      );
      if (!marker) return;
      this.markCategoryRefreshConsumed(marker);
      this.handleRefreshData();
    },
    markCategoryRefreshConsumed(marker) {
      if (!marker) return;
      this.lastCategoryRefreshAt = marker;
      markRefreshMarkerConsumed("categoryListNeedsRefreshDetailConsumed", marker);
    },
    applyCategoryInfo(info) {
      if (!info) return;
      this.category.title = this.extractCategoryName(info) || this.category.title;
      this.category.desc =
        info.folder_desc ||
        info.category_desc ||
        info.class_desc ||
        info.desc ||
        this.category.desc;
      if (info.private_type !== undefined && info.private_type !== null) {
        this.category.private_type = this.normalizePrivateType(info.private_type);
      }
      this.applyCategorySwitchOptions(info);
      this.applyCategoryLevel(info);
      const layoutValue =
        info.layout_type !== undefined &&
        info.layout_type !== null &&
        info.layout_type !== ""
          ? info.layout_type
          : info.pic_layout;
      if (layoutValue !== undefined && layoutValue !== null && layoutValue !== "") {
        this.columns = this.normalizeColumns(layoutValue);
      }
      if (info.is_collect !== undefined) {
        this.isFavorited = !!info.is_collect;
      }
      this.updateShareMeta();
    },
    applyCategoryLevel(info = {}) {
      const pid = Number(info.pid || 0);
      const rawLevel =
        info.folder_level !== undefined && info.folder_level !== null
          ? info.folder_level
          : info.level;
      const level =
        rawLevel !== undefined && rawLevel !== null && rawLevel !== ""
          ? Number(rawLevel)
          : pid > 0
            ? 2
            : 1;
      this.category.pid = pid;
      this.category.level = level || 1;
    },
    normalizePrivateType(value) {
      const type = Number(value);
      return type === 2 || type === 4 ? type : 1;
    },
    findCategoryInTree(list, id) {
      if (!Array.isArray(list)) return null;
      for (const item of list) {
        if (String(item.id) === String(id)) {
          return item;
        }
        const found = this.findCategoryInTree(item.children || [], id);
        if (found) return found;
      }
      return null;
    },
    async loadCategoryContent({ manageLoading = true } = {}) {
      if (manageLoading) {
        this.loading = true;
        this.clearPageError();
      }
      try {
        if (this.uid) {
          const productsLoaded = await this.loadProducts();
          await this.loadChildren({ suppressPublicError: true });
          if (productsLoaded) {
            this.clearPageError();
          }
          return productsLoaded;
        }
        const results = await Promise.all([this.loadChildren(), this.loadProducts()]);
        return results.every(Boolean);
      } finally {
        if (manageLoading) {
          this.loading = false;
        }
      }
    },
    async loadChildren({ suppressPublicError = false } = {}) {
      try {
        if (!this.$go || !this.categoryId) {
          this.children = [];
          return false;
        }
        let params = {
          fid: this.categoryId,
          folder_type: 1,
          timestamp: Date.now(),
        };
        if (this.uid) {
          params = {
            target_user_id: this.uid,
            fid: this.categoryId,
            include_current: 1,
            direct_share: this.directShareAccess ? 1 : 0,
          };
        }
        const url = this.uid ? "user/home/categories" : "album/lists/folder";
        const methods = this.uid ? "get" : "post";
        this.debugShare("loadChildren-request", { url, method: methods, params });
        const res = await this.$go(url, params, methods, {
          show_err: !this.uid,
        });
        this.debugShare("loadChildren-response", {
          code: res && res.code,
          msg: res && (res.msg || res.message),
          rawChildrenCount: this.getChildrenFromResponse(res && res.data).length,
          suppressPublicError,
        });
        if (!res || res.code !== 0) {
          this.children = [];
          if (this.uid && !suppressPublicError) {
            this.setPageError(this.getResponseErrorMessage(res));
          }
          return false;
        }

        // 尝试补全用户信息：uid 只表示访客态，不要把自己的用户 id 写进 uid。
        if (!this.uid && res.data && (res.data.user_id || res.data.uid)) {
          this.shareOwnerId = res.data.user_id || res.data.uid;
          this.shareUrl = this.buildShareUrl();
          this.loadUserInfo(this.shareOwnerId);
        }

        this.applyUserInfo(res.data.user_info || res.data.user || {});

        if (res.data && res.data.user_info) {
          this.applyUserInfo(res.data.user_info);
        }

        if (res.data && res.data.folder_info) {
          this.applyOwnerFromCategory(res.data.folder_info);
          this.applyCategoryInfo(res.data.folder_info);
        }

        this.applyChildrenList(this.getChildrenFromResponse(res.data));
        this.debugShare("loadChildren-applied", { childrenCount: this.children.length });
        return true;
      } catch (e) {
        console.error("[classDetail-share-debug]", "loadChildren-error", e);
        if (this.uid && !suppressPublicError) {
          this.setPageError(this.getExceptionMessage(e));
        } else {
          if (!this.uid) {
            uni.showToast({ title: "加载分类失败", icon: "none" });
          }
        }
        return false;
      }
    },
    async loadProducts() {
      try {
        if (!this.$go || !this.categoryId) {
          this.products = [];
          return false;
        }
        let params = {
          fid: this.categoryId,
          folder_type: 2,
          timestamp: Date.now(),
        };
        if (this.uid) {
          params = {
            target_user_id: this.uid,
            cate_id: this.categoryId,
            category_id: this.categoryId,
            fid: this.categoryId,
            direct_share: this.directShareAccess ? 1 : 0,
            timestamp: Date.now(),
          };
        }
        const url = this.uid ? "user/home/products" : "album/lists/folder";
        const methods = this.uid ? "get" : "post";
        this.debugShare("loadProducts-request", { url, method: methods, params });
        const res = await this.$go(url, params, methods, {
          show_err: !this.uid,
        });
        this.debugShare("loadProducts-response", {
          code: res && res.code,
          msg: res && (res.msg || res.message),
          rawProductsCount: this.getProductsFromResponse(res && res.data).length,
        });
        if (!res || res.code !== 0) {
          this.products = [];
          if (this.uid && !this.hasChildren) {
            this.setPageError(this.getResponseErrorMessage(res));
          }
          return false;
        }
        const list = this.getProductsFromResponse(res.data);
        this.products = list.map((item) => this.normalizeProductItem(item));
        this.debugShare("loadProducts-applied", { productsCount: this.products.length });
        return true;
      } catch (e) {
        console.error("[classDetail-share-debug]", "loadProducts-error", e);
        if (this.uid && !this.hasChildren) {
          this.setPageError(this.getExceptionMessage(e));
        } else {
          uni.showToast({ title: "加载产品失败", icon: "none" });
        }
        return false;
      }
    },
    getChildrenFromResponse(data) {
      if (!data) return [];
      if (Array.isArray(data)) return data;
      if (data.lists) {
        return Array.isArray(data.lists)
          ? data.lists
          : Array.isArray(data.lists.data)
            ? data.lists.data
            : [];
      }
      if (Array.isArray(data.children)) return data.children;
      if (Array.isArray(data.categories)) return data.categories;
      return [];
    },
    applyChildrenList(list = []) {
      if (!Array.isArray(list) || !list.length) {
        this.children = [];
        return;
      }
      this.children = list.map((item) => {
        const pid = Number(item.pid || this.categoryId || 0);
        const level = Number(item.level || item.folder_level || 2);
        const countField =
          item.child_count ||
          item.children_count ||
          item.son_count ||
          item.product_count ||
          0;
        return {
          ...item,
          pid,
          level,
          nameField: this.getDisplayCategoryName(item),
          imageField:
            imageUrlFor(item, "thumb") || item.new_thumb || item.icon || "/static/icon/folder-open@2x.png",
          countField,
        };
      });
    },
    getProductsFromResponse(data) {
      if (!data) return [];
      if (Array.isArray(data)) return data;
      if (data.lists) {
        return Array.isArray(data.lists)
          ? data.lists
          : Array.isArray(data.lists.data)
            ? data.lists.data
            : [];
      }
      if (Array.isArray(data.products)) return data.products;
      if (data.products && Array.isArray(data.products.data)) return data.products.data;
      if (data.products && Array.isArray(data.products.lists)) return data.products.lists;
      if (data.data && Array.isArray(data.data.products)) return data.data.products;
      if (data.data && Array.isArray(data.data.lists)) return data.data.lists;
      return [];
    },
    normalizeArray(value) {
      if (Array.isArray(value)) return value;
      if (typeof value === "string" && value) {
        return value.split(",").filter(Boolean);
      }
      return [];
    },
    normalizeProductItem(item = {}) {
      const pictureCount =
        Number(item.son_count || item.pic_count || item.picture_count || 0) ||
        this.normalizeArray(item.detail_pic_ids_arr || item.detail_pic_ids).length +
          this.normalizeArray(item.pic_ids_arr || item.pic_ids).length;
      const thumbUrl =
        imageUrlFor(item, "thumb") ||
        item.new_thumb ||
        item.imageField ||
        item.picture_url ||
        item.imgurl ||
        item.cover_url ||
        item.coverUrl ||
        "/static/image/pic.png";
      const shareImage =
        imageUrlFor(item, "origin") ||
        imageUrlFor(item, "preview") ||
        thumbUrl;
      return {
        ...item,
        imageField: thumbUrl,
        share_image: shareImage,
        new_thumb: thumbUrl,
        folder_count: pictureCount,
      };
    },
    getDisplayCategoryName(item) {
      const name = item && this.extractCategoryName(item);
      const normalized = name ? String(name).trim() : "";
      if (!normalized) {
        return "未命名子分类";
      }
      return normalized;
    },
    getCategoryKey(category, index) {
      return category && category.id ? category.id : `category-${index}`;
    },
    getChildCount(category) {
      const count = category
        ? category.child_count ||
          category.children_count ||
          category.count ||
          category.son_count
        : 0;
      return Number(count) || 0;
    },
    handleCategoryClick(data) {
      const uidQuery = this.uid ? `&uid=${this.uid}` : "";
      const sourceQuery = this.uid && this.directShareAccess ? "&source=share" : "";
      uni.navigateTo({
        url: `/pagesOther/classDetail/classDetail?id=${data.id}${uidQuery}${sourceQuery}`,
        success: (res) => {
          res.eventChannel.emit("acceptDataFromOpenerPage", { data });
        },
      });
    },
    handleProductClick(data) {
      const productId = this.normalizeShareParam(
        getObjectId(data, ["id", "product_id", "folder_id", "fid"]),
      );
      if (!data || !productId) {
        showInvalidRecordToast("产品数据异常，请刷新后重试");
        return;
      }
      if (this.uid) {
        const shareSourceQuery = this.categoryId
          ? `&source=share&category_id=${encodeURIComponent(this.categoryId)}`
          : "&source=share";
        uni.navigateTo({
          url: this.$buildPublicSharePath
            ? `${this.$buildPublicSharePath("product", productId, this.uid)}${shareSourceQuery}`
            : `/pagesOther/productDetail/productDetail?id=${productId}&uid=${this.uid}${shareSourceQuery}`,
        });
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/productDetailsSelf/productDetailsSelf?id=${productId}`,
      });
    },
    handleGridItemClick(data, index, event) {
      const current = resolveClickedListItem(data, index, event, this.mixedContent);
      if (!current) {
        showInvalidRecordToast();
        return;
      }
      if (current.item_type === "product") {
        this.handleProductClick(current);
        return;
      }
      this.handleCategoryClick(current);
    },
    handleProductGridClick(data, index, event) {
      const current = resolveClickedListItem(data, index, event, this.productContent);
      if (!current) {
        showInvalidRecordToast();
        return;
      }
      this.handleProductClick(current);
    },
    createChildCategory() {
      if (!this.canManageChildCategory) {
        uni.showToast({ title: "子分类下不能继续新建子分类", icon: "none" });
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/addClass/addClass?parent_id=${this.categoryId}`,
      });
    },
    openChildSort() {
      if (!this.canManageChildCategory) {
        uni.showToast({ title: "子分类下没有下级分类", icon: "none" });
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/classSort/classSort?fid=${this.categoryId}&fromPage=classDetail`,
      });
    },
    buildCurrentCategoryQuery(extra = {}) {
      const query = {
        category_id: this.categoryId,
        category_name: this.displayCategoryTitle,
        ...extra,
      };
      return Object.keys(query)
        .filter((key) => query[key] !== undefined && query[key] !== null && query[key] !== "")
        .map((key) => `${key}=${encodeURIComponent(query[key])}`)
        .join("&");
    },
    createProductInCategory() {
      if (!this.canManageCategoryContent) return;
      const query = this.buildCurrentCategoryQuery({ fromPage: "classDetail" });
      uni.navigateTo({
        url: `/pagesOther/addProduct/addProduct?${query}`,
      });
    },
    selectExistingProducts() {
      if (!this.canManageCategoryContent) return;
      const query = this.buildCurrentCategoryQuery({
        fromPage: "classDetail",
        albumId: this.categoryId,
      });
      uni.navigateTo({
        url: `/pagesOther/productSelect/productSelect?${query}`,
      });
    },
    openCategorySettings() {
      if (this.uid || !this.categoryId) return;
      uni.setStorageSync("folderInfo", {
        id: this.categoryId,
        folder_type: 1,
        folder_name: this.displayCategoryTitle,
        folder_desc: this.category.desc || "",
        pid: this.category.pid || 0,
        private_type: this.category.private_type || 1,
        layout_type: this.columns === 1 ? 2 : 1,
        pic_layout: this.columns === 1 ? 2 : 1,
        uid: this.shareOwnerId || (uni.getStorageSync("userInfo") || {}).id || "",
        show_connect: this.category.show_connect,
        set_top: this.category.set_top,
        other_share: this.category.other_share,
        show_upload_date: 0,
        show_search: 0,
        upload_field: [],
        editer_create: 1,
        editer_delete: 1,
        editer_delete_pic: 1,
      });
      uni.navigateTo({
        url: "/pagesOther/setPage/setPage",
      });
    },
    contactOwner() {
      // 跳转或打开聊天/拨号
      this.personalVisible = true;
    },
    // 更新收藏状态
    async handleFavorite() {
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
      const isFavorited = !this.isFavorited;
      const newStatus = await this.$toggleFavorite({
        type: "category", // 'homepage' | 'product' | 'category'
        id: this.categoryId, // 目标ID
        isFavorite: isFavorited, // 当前是否已收藏
      });
      if (newStatus) {
        this.isFavorited = isFavorited;
      }
    },
    async openShare() {
      if (!this.canShareCategory) {
        uni.showToast({ title: "该分类不允许分享", icon: "none" });
        return;
      }
      if (!this.uid && !this.$checkLoginStatus()) {
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
      if (!this.getShareOwnerId()) {
        await this.loadCurrentUserInfo();
      }
      if (!this.getShareOwnerId()) {
        uni.showToast({ title: "分享信息缺失，请稍后重试", icon: "none" });
        return;
      }
      // 准备分享数据（可从后端获取小程序码或分享链接）
      this.updateShareMeta();
      this.shareVisible = true;
    },
    onShareAction(event) {
      // 组件内部已经执行分享/复制/生成海报等操作，父页面可接收做额外处理
      console.log("share action", event);
      // 例如：记录日志或上报
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  background: #f5f5f5;
  min-height: 100vh;
  padding: 16rpx;
  box-sizing: border-box;
}

.page-scoll {
  padding-bottom: 180rpx;
  box-sizing: border-box;
}

.category-panel {
  padding: 12rpx 0;
  margin-top: 28rpx;
}

.category-title-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 10rpx 6rpx;
}

.category-icon {
  width: 48rpx;
  height: 48rpx;
}

.title-wrap {
  flex: 1;
}

.category-title {
  font-weight: 400;
  font-size: 36rpx;
  color: #333333;
  margin-bottom: 6rpx;
  display: block;
}

.category-desc {
  font-size: 20rpx;
  color: #888;
  display: block;
}

.child-actions {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin: 8rpx 0 18rpx;
  padding: 0 4rpx;
}

.child-action-btn {
  flex: 1;
  height: 80rpx;
  border-radius: 80rpx;
  background: #ffffff;
  border: 1rpx solid #ececec;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  box-sizing: border-box;
}

.child-action-btn.primary {
  background: #ffd800;
  border-color: #ffd800;
}

.child-action-icon {
  width: 36rpx;
  height: 36rpx;
}

.child-action-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #333333;
}

.content {
  margin-top: 8rpx;
  padding-bottom: 180rpx;
}

.empty {
  text-align: center;
  color: #999;
  margin-top: 20rpx;
}

.empty-sub {
  text-align: center;
  color: #999;
  margin-top: 10rpx;
}

.section-block {
  margin-bottom: 34rpx;
}

.product-section {
  margin-top: 10rpx;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 58rpx;
  margin-bottom: 16rpx;
}

.section-title-wrap {
  display: flex;
  align-items: center;
  min-width: 0;
}

.section-emoji {
  width: 38rpx;
  height: 38rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 10rpx;
  font-size: 30rpx;
  line-height: 38rpx;
}

.section-title {
  font-size: 34rpx;
  font-weight: 700;
  line-height: 44rpx;
  color: #252b33;
}

.section-count {
  min-width: 36rpx;
  height: 32rpx;
  margin-left: 12rpx;
  padding: 0 10rpx;
  border-radius: 32rpx;
  background: #eef1f5;
  color: #8a929d;
  font-size: 22rpx;
  font-weight: 600;
  line-height: 32rpx;
  text-align: center;
  box-sizing: border-box;
}

.manage-category-grid {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -12rpx;
}

.manage-folder-cell {
  width: 33.3333%;
  padding: 0 8rpx 18rpx;
  box-sizing: border-box;
}

.manage-folder-tile {
  position: relative;
  height: 164rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 16rpx 8rpx 12rpx;
  background: #ffffff;
  border: 1rpx solid rgba(226, 229, 234, 0.9);
  border-radius: 16rpx;
  box-shadow: 0 8rpx 20rpx rgba(28, 35, 45, 0.035);
  box-sizing: border-box;
}

.manage-folder-icon {
  position: relative;
  width: 66rpx;
  height: 52rpx;
  flex: 0 0 52rpx;
}

.manage-folder-tab {
  position: absolute;
  left: 0;
  top: 0;
  width: 34rpx;
  height: 15rpx;
  border-radius: 10rpx 10rpx 4rpx 4rpx;
  background: #ffd45a;
}

.manage-folder-body {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 40rpx;
  border-radius: 11rpx;
  background: linear-gradient(180deg, #ffc93d 0%, #f4a500 100%);
  box-shadow: 0 7rpx 14rpx rgba(236, 156, 0, 0.14);
}

.manage-folder-title {
  display: block;
  width: 100%;
  margin-top: 13rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: center;
  font-size: 25rpx;
  font-weight: 600;
  line-height: 30rpx;
  color: #252b33;
}

.manage-folder-meta {
  display: block;
  width: 100%;
  margin-top: 4rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: center;
  font-size: 20rpx;
  line-height: 24rpx;
  color: #8a929d;
}

.state-card {
  margin: 120rpx 36rpx 0;
  padding: 56rpx 40rpx;
  background: #ffffff;
  border-radius: 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 10rpx 32rpx rgba(25, 31, 39, 0.05);
}

.state-icon {
  width: 72rpx;
  height: 72rpx;
  margin-bottom: 24rpx;
}

.state-title {
  font-size: 32rpx;
  font-weight: 600;
  color: #333333;
}

.state-desc {
  margin-top: 14rpx;
  font-size: 26rpx;
  line-height: 38rpx;
  color: #777777;
}

.state-btn {
  margin-top: 32rpx;
  padding: 18rpx 42rpx;
  border-radius: 999rpx;
  background: #ffd800;
  color: #333333;
  font-size: 26rpx;
  font-weight: 600;
}

.bottom-bar {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  bottom: calc(env(safe-area-inset-bottom) + 24rpx);
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
  box-shadow: 0rpx 8rpx 40rpx 0rpx rgba(0, 0, 0, 0.1);
  border-radius: 80rpx 80rpx 80rpx 80rpx;
  padding: 16rpx;
  z-index: 100;
  max-width: 480rpx;
}

.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 160rpx;
}

.action-icon {
  width: 48rpx;
  height: 48rpx;
  margin-bottom: 8rpx;
}

.action-text {
  font-weight: 400;
  font-size: 24rpx;
  color: #333333;
}

.class-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  min-height: 520rpx;
  padding: 44rpx 12rpx 40rpx;
  box-sizing: border-box;

  .empty-visual {
    position: relative;
    width: 320rpx;
    height: 250rpx;
    margin-bottom: 20rpx;
  }

  .visual-card {
    position: absolute;
    border-radius: 24rpx;
    box-sizing: border-box;
  }

  .visual-card-back {
    left: 42rpx;
    top: 10rpx;
    width: 210rpx;
    height: 150rpx;
    background: #eeeeee;
    border: 2rpx solid #e2e2e2;
    transform: rotate(-8deg);
  }

  .visual-card-front {
    left: 50%;
    bottom: 12rpx;
    width: 238rpx;
    height: 178rpx;
    padding: 22rpx;
    background: #f8f8f8;
    border: 2rpx solid #222222;
    box-shadow: 0 18rpx 40rpx rgba(34, 34, 34, 0.12);
    transform: translateX(-50%);
  }

  .visual-image {
    height: 74rpx;
    border-radius: 18rpx;
    background: linear-gradient(135deg, #ffd800 0%, #fff3a6 48%, #f1f1f1 100%);
  }

  .visual-lines {
    margin-top: 22rpx;
  }

  .visual-line {
    height: 12rpx;
    border-radius: 12rpx;
    background: #e0e0e0;
  }

  .visual-line-long {
    width: 152rpx;
  }

  .visual-line-short {
    width: 94rpx;
    margin-top: 14rpx;
  }

  .visual-plus {
    position: absolute;
    right: 30rpx;
    top: 26rpx;
    width: 58rpx;
    height: 58rpx;
    border-radius: 50%;
    background: #ffd800;
    color: #222222;
    font-size: 46rpx;
    line-height: 56rpx;
    text-align: center;
    font-weight: 600;
    box-shadow: 0 10rpx 24rpx rgba(255, 216, 0, 0.35);
  }

  .empty-title {
    color: #222222;
    font-size: 32rpx;
    font-weight: 600;
    line-height: 44rpx;
  }

  .empty-text {
    max-width: 480rpx;
    margin-top: 14rpx;
    color: #7a7a7a;
    font-size: 26rpx;
    line-height: 38rpx;
    text-align: center;
  }

  .actions {
    display: flex;
    gap: 16rpx;
    margin-top: 40rpx;
  }

  .btn {
    width: 268rpx;
    height: 96rpx;
    border-radius: 96rpx;
    background: #222222;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
  }

  .btn-icon {
    width: 40rpx;
    height: 40rpx;
  }

  .btn-text {
    font-size: 30rpx;
    font-weight: 600;
    color: #ffd800;
  }
}
</style>
