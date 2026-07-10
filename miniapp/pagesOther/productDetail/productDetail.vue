<template>
  <view class="page">
    <!-- 头部：复用用户卡组件 -->
    <user-card
      :avatar="user.avatar"
      :name="user.name"
      :subtitle="user.subtitle"
      @contact="contactOwner"
    />

    <!-- 分类标题与简介 -->
    <view class="category-panel">
      <view class="category-title-row">
        <image
          src="/static/icon/Frame 1171279070@2x.png"
          class="category-icon"
          mode="widthFix"
        />
        <view class="title-wrap">
          <text class="category-title">{{
            category.title || "未知产品名称"
          }}</text>
        </view>
      </view>
      <view class="category-desc" v-if="category.desc">
        <text>{{ category.desc }}</text>
      </view>
    </view>

    <!-- 图片网格 -->
    <view class="content">
      <view class="content-title">花色图</view>
      <ImageGrid
        :list="images"
        :showBadge="false"
        :columns="category.columns"
        aspectRatio="4:3"
        @click="handleImageClick"
      >
      </ImageGrid>
      <view v-if="images.length === 0" class="empty-sub">
        <image
          class="empty-icon"
          src="/static/icon/empty.png"
          mode="scaleToFill"
        />
      </view>
    </view>
    <!-- 详情图 -->
    <view class="content-detail" v-if="shouldShowDetailSection">
      <view class="content-title">详情图</view>
      <block v-for="(item, idx) in imagesDetails" :key="item.id">
        <view class="detail-image" @tap="handleImageClick(item)">
          <image class="item-image" :src="item.imageField || '/static/image/pic.png'" mode="widthFix" />
        </view>
      </block>
      <view v-if="imagesDetails.length === 0" class="empty-sub">
        <image
          class="empty-icon"
          src="/static/icon/empty.png"
          mode="scaleToFill"
        />
      </view>
    </view>

    <!-- 底部固定操作栏 -->
    <view class="bottom-bar">
      <view class="action-btn" @tap="onSelectProducts" v-if="uid && isSelectionListEnabled">
        <image
          src="/static/icon/checkbox-circle@2x.png"
          class="action-icon"
          mode="widthFix"
        />
        <text class="action-text">选款</text>
      </view>

      <view class="action-btn" @tap="toggleFavorite" v-if="uid">
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
    <personal-details
      :use-popup="true"
      :uid="getShareOwnerId() || ''"
      :visible="personalVisible"
      @update:visible="(val) => (personalVisible = val)"
    />
    <!-- 分享弹窗（复用组件） -->
    <share-popup
      :uid="getShareOwnerId() || ''"
      :visible="shareVisible"
      :title="category.title"
      typeText="产品"
      type="product"
      :hid="productId"
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
import { setPictureNavigationContext } from "@/common/helper/pictureNavigation.js";

export default {
  components: { UserCard, ImageGrid, SharePopup, PersonalDetails },
  data() {
    return {
      personalVisible: false,
      user: {
        avatar: "",
        name: "",
        subtitle: "",
      },
      productId: null,
      category: {
        title: "",
        desc: "",
        columns: 2,
      },
      images: [], // 图片 url 数组
      imagesDetails: [], // 图片 url 数组
      loading: false,
      isFavorited: false,
      shareVisible: false,
      shareTitle: "",
      shareUrl: "/pagesOther/productDetail/productDetail",
      shareMiniQr: "",
      shareMiniPath: "",
      minShowCount: 1, // 控制“没有更多了~”显示逻辑
      uid: "",
      shareOwnerId: "",
      lastProductRefreshAt: "",
      hideDetailPictures: false,
    };
  },
  onLoad(options) {
    const sceneOptions = this.parseSceneOptions(options);
    options = {
      ...(options || {}),
      ...sceneOptions,
    };
    this.uid = this.normalizeShareParam(options.uid);
    this.shareOwnerId = this.uid;
    console.log(options);
    if (this.uid && !ensureSharedPageLogin("pagesOther/productDetail/productDetail", options, this.uid)) {
      return;
    }
    // 支持通过 options 传入分类 id
    if (options && options.id) {
      this.productId = options.id;
      this.shareUrl = this.buildShareUrl();
      this.loadProductDetail();
      const token = uni.getStorageSync("token");
      if (token) {
        this.$addVisit({ id: options.id, type: "product" });
      }
    }
    if (this.uid) {
      this.loadUserInfo();
    }
    uni.$on("refreshProductlData", this.handleRefreshData);
  },
  onShow() {
    this.consumeProductRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshProductlData", this.handleRefreshData);
  },
  onShareAppMessage() {
    return {
      title: this.category.title || "产品分享",
      path: this.buildShareUrl(),
      imageUrl: this.shareCoverUrl,
    };
  },
  computed: {
    isSelectionListEnabled() {
      return Boolean(this.$config && this.$config.features && this.$config.features.selectionList);
    },
    shareCoverUrl() {
      return this.images[0]?.imageField || this.imagesDetails[0]?.imageField || "";
    },
    shouldShowDetailSection() {
      return !this.hideDetailPictures && this.imagesDetails.length > 0;
    },
  },
  methods: {
    parseSceneOptions(options = {}) {
      if (!options.scene || !this.$parseShareScene) return {};
      const scene = this.$parseShareScene(options.scene);
      if (scene.type === "product" && scene.id) {
        scene.id = scene.id;
      }
      return scene;
    },
    normalizePicColumns(value) {
      return Number(value) === 1 ? 1 : 2;
    },
    normalizeProductPicture(item = {}, fallbackPoster = "") {
      const thumbUrl = imageUrlFor(item, "thumb");
      const previewUrl = imageUrlFor(item, "preview");
      const originUrl = imageUrlFor(item, "origin");
      const imageUrl = thumbUrl || previewUrl || originUrl;
      return {
        id: item.id || item.pic_id || "",
        imageField: imageUrl,
        imgurl: imageUrl,
        picture_url: previewUrl || imageUrl,
        picture_url_original: originUrl || previewUrl || imageUrl,
        image_urls: item.image_urls || item.imageUrls || item.urls || {},
        imageUrls: item.imageUrls || item.image_urls || item.urls || {},
        nameField: item.pic_name || item.name || "",
        pic_name: item.pic_name || item.name || "",
        file_type: item.file_type || 1,
        poster: item.poster || fallbackPoster || imageUrl,
        file_size: Number(item.file_size || item.size_bytes || item.size || 0),
        size: Number(item.size || item.file_size || item.size_bytes || 0),
      };
    },
    buildShareUrl() {
      if (!this.productId) {
        return "/pagesOther/productDetail/productDetail";
      }
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("product", this.productId, this.getShareOwnerId())
        : `/pagesOther/productDetail/productDetail?id=${this.productId}${this.getShareOwnerId() ? `&uid=${this.getShareOwnerId()}` : ""}`;
    },
    normalizeShareParam(value) {
      if (value === null || value === undefined) return "";
      const text = String(value).trim();
      if (!text || text === "null" || text === "undefined") return "";
      return text;
    },
    getShareOwnerId() {
      return (
        this.shareOwnerId ||
        this.uid ||
        (this.$getCurrentUserId ? this.$getCurrentUserId() : "")
      );
    },
    async loadUserInfo(targetUserId = this.getShareOwnerId()) {
      try {
        if (!targetUserId) return;
        const data = {
          target_user_id: targetUserId,
        };
        const res = await this.$go("user/home/info", data, "get", {
          show_err: false,
        });
        if (res && res.data) {
          const info = res.data;
          this.user = {
            avatar: info.company_logo || info.avatar || "",
            name: info.company_name || info.nickname || "商户名称",
            subtitle: info.company_desc || "",
          };
        }
      } catch (e) {
        console.error(e);
      }
    },
    handleRefreshData(marker) {
      this.markProductRefreshConsumed(marker);
      this.loadProductDetail();
    },
    consumeProductRefreshMarker() {
      if (this.uid) return;
      const marker = consumeRefreshMarker(
        "product",
        "productListNeedsRefreshPublicDetailConsumed",
        this.lastProductRefreshAt,
      );
      if (!marker) return;
      this.markProductRefreshConsumed(marker);
      this.handleRefreshData();
    },
    markProductRefreshConsumed(marker) {
      if (!marker) return;
      this.lastProductRefreshAt = marker;
      markRefreshMarkerConsumed(
        "productListNeedsRefreshPublicDetailConsumed",
        marker,
      );
    },
    async loadProductDetail() {
      try {
        if (this.$go && this.productId) {
          let data = {
            fid: this.productId,
            timestamp: Date.now(),
          };
          if (this.uid) {
            data = {
              target_user_id: this.uid,
              product_id: this.productId,
              timestamp: Date.now(),
            };
          }
          const url = this.uid
            ? "user/home/products/detail"
            : "album/products/detail";
          const methods = this.uid ? "get" : "post";
          const res = await this.$go(url, data, methods, { show_err: true });
          const d = res && res.data ? res.data : {};
          this.isFavorited = d.is_collect ? true : false;
          this.hideDetailPictures = Number(d.hide_detail_pictures || d.hideDetailImage || 0) === 1;
          const coverPics = Array.isArray(d.pic_list) ? d.pic_list : [];
          const detailPics = Array.isArray(d.detail_pic_list)
            ? d.detail_pic_list
            : [];
          const coverItems = coverPics.map((item) =>
            this.normalizeProductPicture(item),
          );
          const fallbackPoster = coverItems[0] ? coverItems[0].imageField : "";
          const detailItems = detailPics.map((item) =>
            this.normalizeProductPicture(item, fallbackPoster),
          );
          const picList = [];
          this.images = coverItems.map((item) => {
            picList.push({
              pic_id: item.id,
              id: item.id,
              picture_url: item.picture_url || item.imageField,
              picture_url_original: item.picture_url_original,
            image_urls: item.image_urls,
            imageUrls: item.imageUrls,
            pic_name: item.pic_name,
            poster: fallbackPoster,
            product_id: this.productId,
            folder_id: this.productId,
            file_size: item.file_size || item.size || 0,
            size: item.size || item.file_size || 0,
          });
            return item;
          });
          this.imagesDetails = detailItems.map((item) => {
            picList.push({
              pic_id: item.id,
              id: item.id,
              picture_url: item.picture_url || item.imageField,
            picture_url_original: item.picture_url_original,
            image_urls: item.image_urls,
            imageUrls: item.imageUrls,
            pic_name: item.pic_name,
            poster: "",
            product_id: this.productId,
            folder_id: this.productId,
            file_size: item.file_size || item.size || 0,
            size: item.size || item.file_size || 0,
          });
            return item;
          });
          uni.setStorageSync("picList", picList);
          this.category.title = d.folder_name || "";
          this.category.desc = d.folder_desc || "";
          this.category.columns = this.normalizePicColumns(d.pic_layout);
          this.isFavorited = !!d.is_collect;

          // 尝试补全用户信息：如果接口返回了 user_id 且当前未指定 uid，则获取用户信息
          if (!this.shareOwnerId && (d.user_id || d.uid)) {
            this.shareOwnerId = d.user_id || d.uid;
            this.shareUrl = this.buildShareUrl();
            this.loadUserInfo(this.shareOwnerId);
          }
          // 如果接口直接返回了用户信息（user_info 对象或扁平字段）
          if (d.user_info || d.company_name || d.nickname) {
            const info = d.user_info || d;
            // 仅当 user 为空或需要更新时赋值
            if (!this.user.name) {
               this.user = {
                  avatar: info.company_logo || info.avatar || "",
                  name: info.company_name || info.nickname || "商户名称",
                  subtitle: info.company_desc || ""
               };
            }
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    handleImageClick(data) {
      const uidQuery = this.uid ? "&uid=" + this.uid + "&source=share" : "";
      const item = this.normalizeProductPicture(data);
      const pictureContext = setPictureNavigationContext(
        item,
        [...this.images, ...this.imagesDetails],
        {
          product_id: this.productId,
          folder_id: this.productId,
        },
      );
      uni.navigateTo({
        url:
          "/pagesOther/picDetail/picDetail?pic_id=" +
          (pictureContext.current.pic_id || item.id) +
          uidQuery,
      });
    },
    contactOwner() {
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
      // 跳转或打开聊天/拨号
      this.personalVisible = true;
    },
    onSelectProducts() {
      if (!this.isSelectionListEnabled) {
        return;
      }
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
      // 进入选择产品流程（例如跳转到 addTo 页面）
      uni.navigateTo({
        url: `/pagesOther/selectStyle/selectStyle?id=${this.productId}&uid=${this.uid}`,
      });
    },
    async toggleFavorite() {
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
      try {
        // 切换收藏状态并调用 API
        const isFavorited = !this.isFavorited;
        const newStatus = await this.$toggleFavorite({
          type: "product", // 'homepage' | 'product' | 'category'
          id: this.productId, // 目标ID
          isFavorite: isFavorited, // 当前是否已收藏
        });
        if (newStatus) {
          this.isFavorited = isFavorited;
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "操作失败", icon: "none" });
      }
    },
    openShare() {
      this.shareUrl = this.buildShareUrl();
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
  padding-bottom: 200rpx;
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
  font-family:
    PingFang SC,
    PingFang SC;
  font-weight: 400;
  font-size: 28rpx;
  color: #333333;
  line-height: 42rpx;
  text-align: left;
  font-style: normal;
  text-transform: none;
}

.content {
  margin-top: 8rpx;
  /* leave space for bottom bar */
}
.content-title {
  font-size: 32rpx;
  color: #333333;
  font-weight: bold;
  margin-bottom: 16rpx;
}
.content-detail {
  margin-top: 8rpx;
  padding-bottom: 120rpx;

  .detail-image {
    width: 100%;
    margin-bottom: 20rpx;
    /* 图片之间间距，可按需调整 */
    box-sizing: border-box;
  }

  .item-image {
    width: 100%;
    height: auto;
    /* 高度自适应图片本身比例 */
    display: block;
    /* 可选：圆角 */
    object-fit: cover;
    /* 在某些端有用，保持图片填充宽度 */
  }
}

.empty {
  text-align: center;
  color: #999;
  margin-top: 20rpx;
}

.empty-sub {
  width: 100%;
  display: flex;
  justify-content: center;
  text-align: center;
  color: #999;
  margin-top: 10rpx;
  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
  .empty-icon {
    widows: 360rpx;
  }
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
</style>
