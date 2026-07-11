<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 头部：复用用户卡组件 -->
      <user-card
        v-if="shouldShowOwnerCard"
        :avatar="user.avatar"
        :name="user.name"
        :subtitle="user.subtitle"
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

      <!-- 子分类与产品网格 -->
      <view class="content" v-if="!pageError && hasContent">
        <ImageGrid
          :list="mixedContent"
          :columns="columns"
          @click="handleGridItemClick"
        >
        </ImageGrid>

        <!-- 当有图片但少于一行时也显示“没有更多了~” -->
        <view
          v-if="shouldShowLessThanMinHint"
          class="empty-sub"
        >
          <text class="empty-text">没有更多了~</text>
        </view>
      </view>
      <!-- 无内容时的占位 -->
      <view v-if="!pageError && !hasContent && !loading" class="class-empty">
        <image
          v-if="canManageChildCategory"
          src="/pagesOther/static/icon/Frame@2x(25).png"
          mode="widthFix"
          class="empty-img"
        ></image>
        <text class="empty-text">{{ emptyMessage }}</text>
        <view v-if="canManageCategoryContent" class="actions">
          <view class="btn" @tap="createProductInCategory">
            <image
              class="btn-icon"
              src="/static/icon/add-yellow-icon.png"
              mode="scaleToFill"
            />
            <text class="btn-text">添加产品</text>
          </view>
          <view class="btn secondary" @tap="selectExistingProducts">
            <image
              class="btn-icon"
              src="/static/icon/image-3@2x(2).png"
              mode="scaleToFill"
            />
            <text class="btn-text">选入已有</text>
          </view>
        </view>
      </view>

      <!-- 底部固定操作栏 -->
      <view v-if="!pageError" class="bottom-bar">
        <view class="action-btn" @tap="contactOwner">
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

        <view class="action-btn" @tap="openShare">
          <image
            src="/static/icon/24＊24@2x(4).png"
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">分享</text>
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
      },
      categoryId: null,
      category: {
        title: "",
        desc: "",
        pid: 0,
        level: 1,
        private_type: 1,
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
      shareOwnerId: "",
      lastCategoryRefreshAt: "",
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
    totalContentCount() {
      return this.children.length + this.products.length;
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
    shareCoverUrl() {
      const product = this.products.find((item) => this.normalizeText(item.new_thumb));
      const child = this.children.find((item) => {
        const cover = this.normalizeText(item.imageField || item.new_thumb);
        return cover && cover.indexOf("/static/") !== 0;
      });
      return (
        this.normalizeText(product && product.new_thumb) ||
        this.normalizeText(child && (child.imageField || child.new_thumb)) ||
        ""
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
    this.shareOwnerId = this.uid;
    this.shareUrl = this.buildShareUrl();
    if (this.uid && !ensureSharedPageLogin("pagesOther/classDetail/classDetail", options, this.uid)) {
      return;
    }
    this.initUserFromCache();

    // 通过 eventChannel 接收上一页面传递的数据
    const eventChannel = this.getOpenerEventChannel();
    if (eventChannel) {
      eventChannel.on("acceptDataFromOpenerPage", (data) => {
        console.log("通过 eventChannel 接收到完整的分类数据:", data);
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
          this.applyCategoryLevel(classDetailData);
          this.columns = this.normalizeColumns(
            classDetailData.layout_type || classDetailData.pic_layout || 2
          );
          this.updateShareMeta();
        }
      });
    }

    this.loadOwnerInfo();
    this.loadInitialData();
    const token = uni.getStorageSync("token");
    if (token) {
      this.$addVisit({ id: options.id, type: "category" });
    }
    // 监听分类更新事件
    uni.$on("refreshClassDetailData", this.handleRefreshData);
  },
  onShow() {
    this.consumeCategoryRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshClassDetailData", this.handleRefreshData);
  },
  onShareAppMessage() {
    this.updateShareMeta();
    return {
      title: this.shareTitle || this.buildCategoryShareTitle(),
      path: this.shareUrl || this.buildShareUrl(),
      imageUrl: this.shareCoverUrl,
    };
  },
  methods: {
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
    setPageError(message) {
      this.pageError = this.normalizeText(message) || "该分类暂不可访问";
      this.children = [];
      this.products = [];
    },
    clearPageError() {
      this.pageError = "";
    },
    async loadInitialData() {
      this.loading = true;
      this.clearPageError();
      try {
        if (this.uid) {
          const loaded = await this.loadPublicCategoryInfo();
          if (!loaded) return;
        }
        await this.loadCategoryContent({ manageLoading: false });
      } finally {
        this.loading = false;
      }
    },
    retryLoad() {
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
      const avatar =
        info.company_logo ||
        info.avatar ||
        info.headimgurl ||
        info.head_url ||
        "";
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
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("category", this.categoryId, ownerId)
        : `/pagesOther/classDetail/classDetail?id=${this.categoryId}${ownerId ? `&uid=${ownerId}` : ""}`;
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
        const res = await this.$go(
          "user/home/categories",
          { target_user_id: this.uid, fid: this.categoryId, include_current: 1 },
          "get",
          { show_err: false }
        );
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
        return true;
      } catch (e) {
        console.error(e);
        this.setPageError(this.getExceptionMessage(e));
        return false;
      }
    },
    handleRefreshData(marker) {
      this.markCategoryRefreshConsumed(marker);
      this.loadInitialData();
    },
    consumeCategoryRefreshMarker() {
      if (this.uid) return;
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
        const results = await Promise.all([this.loadChildren(), this.loadProducts()]);
        return results.every(Boolean);
      } finally {
        if (manageLoading) {
          this.loading = false;
        }
      }
    },
    async loadChildren() {
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
          };
        }
        const url = this.uid ? "user/home/categories" : "album/lists/folder";
        const methods = this.uid ? "get" : "post";
        const res = await this.$go(url, params, methods, {
          show_err: !this.uid,
        });
        if (!res || res.code !== 0) {
          this.children = [];
          if (this.uid) {
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

        const list = this.getChildrenFromResponse(res.data);
        if (list.length) {
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
        } else {
          this.children = [];
        }
        return true;
      } catch (e) {
        console.error(e);
        if (this.uid) {
          this.setPageError(this.getExceptionMessage(e));
        } else {
          uni.showToast({ title: "加载分类失败", icon: "none" });
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
            timestamp: Date.now(),
          };
        }
        const url = this.uid ? "user/home/products" : "album/lists/folder";
        const methods = this.uid ? "get" : "post";
        const res = await this.$go(url, params, methods, {
          show_err: !this.uid,
        });
        if (!res || res.code !== 0) {
          this.products = [];
          if (this.uid) {
            this.setPageError(this.getResponseErrorMessage(res));
          }
          return false;
        }
        const list = this.getProductsFromResponse(res.data);
        this.products = list.map((item) => this.normalizeProductItem(item));
        return true;
      } catch (e) {
        console.error(e);
        if (this.uid) {
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
      return {
        ...item,
        new_thumb: imageUrlFor(item, "thumb") || item.new_thumb || item.imageField || "/static/image/pic.png",
        folder_count: pictureCount,
      };
    },
    getDisplayCategoryName(item) {
      const name = item && this.extractCategoryName(item);
      const normalized = name ? String(name).trim() : "";
      if (!normalized || /^-?\d+-?$/.test(normalized)) {
        return "未命名子分类";
      }
      return normalized;
    },
    handleCategoryClick(data) {
      const uidQuery = this.uid ? `&uid=${this.uid}` : "";
      uni.navigateTo({
        url: `/pagesOther/classDetail/classDetail?id=${data.id}${uidQuery}`,
        success: (res) => {
          res.eventChannel.emit("acceptDataFromOpenerPage", { data });
        },
      });
    },
    handleProductClick(data) {
      if (!data || !data.id) return;
      if (this.uid) {
        uni.navigateTo({
          url: this.$buildPublicSharePath
            ? this.$buildPublicSharePath("product", data.id, this.uid)
            : `/pagesOther/productDetail/productDetail?id=${data.id}&uid=${this.uid}`,
        });
        return;
      }
      uni.navigateTo({
        url: `/pagesOther/productDetailsSelf/productDetailsSelf?id=${data.id}`,
      });
    },
    handleGridItemClick(data) {
      if (!data) return;
      if (data.item_type === "product") {
        this.handleProductClick(data);
        return;
      }
      this.handleCategoryClick(data);
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
    openShare() {
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
  padding: 16rpx;
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
  /* leave space for bottom bar */
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
  justify-content: center;

  .empty-img {
    width: 545rpx;
    height: 545rpx;
    margin-bottom: 18rpx;
  }

  .actions {
    display: flex;
    gap: 16rpx;
    margin-top: 12rpx;
  }

  .btn {
    padding: 24rpx 68rpx;
    border-radius: 96rpx;
    background: #222;
    display: flex;
    align-items: center;
    gap: 8rpx;
  }

  .btn-icon {
    width: 48rpx;
    height: 48rpx;
  }

  .btn-text {
    font-weight: bold;
    font-size: 32rpx;
    color: #ffd000;
  }
  .image-list-wrap {
    padding-bottom: 200rpx;
  }
  .upload-card {
    margin-top: 20rpx;
    height: 160rpx;
    background: rgba(235, 235, 235, 1);
    padding: 0 24rpx;
    border-radius: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .upload-icon {
    width: 48rpx;
    height: 48rpx;
  }

  .upload-text {
    color: rgba(0, 0, 0, 1);
    font-size: 28rpx;
    margin-top: 10rpx;
  }

  /* 底部栏容器（兼容安全区） */
  .bottom-bar {
    position: fixed;
    background: #ffffff;
    left: 0;
    right: 0;
    bottom: 0;
    box-sizing: border-box;
    z-index: 50;
  }

  /* 左侧大按钮容器（居中宽度受限） */
  .left-btn {
    display: flex;
    align-items: center;
    padding: 16rpx 48rpx;
    gap: 60rpx;
    padding-bottom: calc(env(safe-area-inset-bottom) + 10rpx);
  }

  /* 黄色胶囊（包含图标框和文字） */
  .share-inner {
    width: 438rpx;
    background: #ffd800;
    height: 96rpx;
    border-radius: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
  }

  /* 左侧小图标的虚线方块 */
  .icon-box {
    width: 48rpx;
    height: 48rpx;
  }

  /* 图标与文字样式 */
  .icon {
    font-size: 30rpx;
    color: #222;
  }

  .share-text {
    font-weight: bold;
    font-size: 32rpx;
    color: #333333;
  }

  /* 右侧齿轮按钮 */
  .settings-btn {
    height: 88rpx;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
  }

  /* 齿轮图标 */
  .settings-icon {
    font-size: 28rpx;
    color: #333;
  }
}
</style>
