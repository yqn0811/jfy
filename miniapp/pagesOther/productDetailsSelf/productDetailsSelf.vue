<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 用户信息卡 -->
      <user-card
        :avatar="user.avatar"
        :name="user.name"
        :subtitle="user.subtitle"
        @contact="contactOwner"
      />

      <view class="content">
        <view class="title">{{ product.title || "未命名系列" }}</view>
        <view class="desc" v-if="product.desc">{{ product.desc }}</view>

        <!-- 无产品时的占位与操作 -->
        <view v-if="!hasProduct" class="empty">
          <image
            src="/static/icon/empty.png"
            mode="widthFix"
            class="empty-img"
          ></image>
        </view>

        <!-- 有产品时展示图片网格 -->
        <view class="image-list-wrap" v-if="product.images.length">
          <view class="section-title">花色图</view>
          <ImageGrid
            :list="product.images"
            imageField="imgurl"
            nameField="pic_name"
            :columns="columns"
            :showBadge="false"
            aspectRatio="4:3"
            @click="handleImageClick"
          >
          </ImageGrid>

          <!-- 上传图片按钮 -->
          <!-- <view class="upload-card" @tap="uploadImage">
          <image
            class="upload-icon"
            src="/static/icon/upload-top-icon.png"
            mode="scaleToFill"
          />
          <text class="upload-text">上传图片</text>
        </view> -->
        </view>

        <view class="detail-list-wrap" v-if="product.detailImages.length">
          <view class="section-title">详情图</view>
          <view
            v-for="item in product.detailImages"
            :key="item.id"
            class="detail-image"
            @tap="handleImageClick(item)"
          >
            <image
              class="detail-img"
              :src="item.imgurl || '/static/image/pic.png'"
              mode="widthFix"
            ></image>
          </view>
        </view>
      </view>

      <!-- 底部固定栏 -->
      <view class="bottom-bar">
        <view class="left-btn">
          <view class="share-inner" @tap="onShare">
            <image
              class="icon-box"
              src="/static/icon/24＊24@2x (1).png"
              mode="scaleToFill"
            />
            <text class="share-text">分享</text>
          </view>

          <view class="settings-btn" @tap="onSettings">
            <image
              class="icon-box"
              src="/static/icon/Frame.png"
              mode="scaleToFill"
            />
            <text class="share-text">设置</text>
          </view>

        </view>
      </view>
    </view>

    <PersonalDetails
      :use-popup="true"
      :visible="personalVisible"
      @update:visible="(val) => (personalVisible = val)"
    />
    <!-- 分享弹窗（复用组件） -->
    <share-popup
      :uid="shareOwnerId || ''"
      :visible="shareVisible"
      :title="product.title"
      typeText="产品"
      type="product"
      :hid="pid"
      :url="shareUrl"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
      @update:visible="(val) => (shareVisible = val)"
      @action="onShareAction"
    />
  </view>
</template>

<script>
import UserCard from "@/components/UserCard"; // 根据放置路径调整
import ImageGrid from "@/components/ImageGrid"; // 若放 components 下，路径可能为 '@/components/ImageGrid.vue'
import PersonalDetails from "@/components/PersonalDetails";
import SharePopup from "@/components/SharePopup";
import {
  consumeRefreshMarker,
  markRefreshMarkerConsumed,
} from "@/common/helper/refresh.js";
import { imageUrlFor } from "@/common/helper/imageUrls.js";

export default {
  components: {
    UserCard,
    ImageGrid,
    SharePopup,
    PersonalDetails,
  },
  data() {
    return {
      personalVisible: false,
      user: {
        avatar: "",
        name: "",
        subtitle: "",
      },
      columns: 2,
      product: {
        id: null,
        title: "",
        desc: "",
        images: [],
        detailImages: [],
      },
      uploadEndpoint: "https://your-upload-endpoint.example.com/upload",
      pid: "",
      uid: "",
      shareOwnerId: "",
      fromPage: "",
      shareVisible: false,
      shareTitle: "",
      shareUrl: "/pagesOther/productDetail/productDetail",
      shareMiniQr: "",
      shareMiniPath: "",
      lastProductRefreshAt: "",
    };
  },
  computed: {
    hasProduct() {
      return !!(this.product.images.length || this.product.detailImages.length);
    },
  },
  onLoad(options) {
    // 尝试从 options 获取 product id，或从 storage / 后端加载
    const pid = options && options.id;
    this.fromPage = options.fromPage;
    this.pid = pid;
    this.shareOwnerId = this.getCurrentUserId();
    this.shareUrl = this.buildShareUrl();
    if (pid) {
      this.loadProduct(pid);
      this.$addVisit({ id: pid, type: "product" });
    }
    this.loadUserInfo();
    // 监听设置更新事件
    uni.$on("refreshProductDetailsSelfData", this.handleRefreshData);
  },
  onShow() {
    this.consumeProductRefreshMarker();
  },
  onUnload() {
    uni.$off("refreshProductDetailsSelfData", this.handleRefreshData);
  },
  onShareAppMessage() {
    return {
      title: this.product.title || "产品分享",
      path: this.buildShareUrl(),
      imageUrl: this.product.images[0]?.imgurl || "",
    };
  },
  methods: {
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
        imgurl: imageUrl,
        imageField: imageUrl,
        picture_url: previewUrl || imageUrl,
        picture_url_original: originUrl || previewUrl || imageUrl,
        image_urls: item.image_urls || item.imageUrls || item.urls || {},
        imageUrls: item.imageUrls || item.image_urls || item.urls || {},
        pic_name: item.pic_name || item.name || "",
        file_type: item.file_type || 1,
        poster: item.poster || fallbackPoster || imageUrl,
      };
    },
    buildShareUrl() {
      if (!this.pid) {
        return "/pagesOther/productDetail/productDetail";
      }
      return this.$buildPublicSharePath
        ? this.$buildPublicSharePath("product", this.pid, this.shareOwnerId)
        : `/pagesOther/productDetail/productDetail?id=${this.pid}${this.shareOwnerId ? `&uid=${this.shareOwnerId}` : ""}`;
    },
    getCurrentUserId() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      const user = uni.getStorageSync("user") || {};
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
      return (
        userInfo.id ||
        user.id ||
        enterpriseInfo.user_id ||
        enterpriseInfo.uid ||
        ""
      );
    },
    loadUserInfo() {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");
      const info = enterpriseInfo || userInfo || {};

      this.user = {
        avatar: info.company_logo || info.avatar || "",
        name: info.company_name || info.nickname || "商户名称",
        subtitle: info.company_desc || "",
      };
    },
    handleRefreshData(marker) {
      this.markProductRefreshConsumed(marker);
      if (this.fromPage === "manage") {
        uni.$emit("refreshProductManageData");
      }
      this.loadProduct(this.pid);
    },
    consumeProductRefreshMarker() {
      const marker = consumeRefreshMarker(
        "product",
        "productListNeedsRefreshSelfDetailConsumed",
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
        "productListNeedsRefreshSelfDetailConsumed",
        marker,
      );
    },

    // 加载产品详情（从后端）
    async loadProduct(pid) {
      try {
        if (this.$go) {
          const res = await this.$go(
            "album/products/detail",
            { fid: pid },
            "post",
            {
              show_err: true,
            },
          );
          if (res && res.data) {
            const picList = [];
            const productPics = Array.isArray(res.data.pic_list)
              ? res.data.pic_list
              : [];
            const detailPics = Array.isArray(res.data.detail_pic_list)
              ? res.data.detail_pic_list
              : [];
            const productItems = productPics.map((item) =>
              this.normalizeProductPicture(item),
            );
            const fallbackPoster = productItems[0]?.imgurl || "";
            const detailItems = detailPics.map((item) =>
              this.normalizeProductPicture(item, fallbackPoster),
            );
            productItems.forEach((item) => {
              picList.push({
                pic_id: item.id,
                picture_url: item.picture_url || item.imgurl,
                picture_url_original: item.picture_url_original,
                image_urls: item.image_urls,
                imageUrls: item.imageUrls,
                pic_name: item.pic_name,
                poster: fallbackPoster || item.imgurl,
              });
            });
            detailItems.forEach((item) => {
              picList.push({
                pic_id: item.id,
                picture_url: item.picture_url || item.imgurl,
                picture_url_original: item.picture_url_original,
                image_urls: item.image_urls,
                imageUrls: item.imageUrls,
                pic_name: item.pic_name,
                poster: "",
              });
            });
            uni.setStorageSync("picList", picList);
            this.columns = this.normalizePicColumns(res.data.pic_layout);
            this.product = {
              id: res.data.id,
              title: res.data.folder_name || "",
              desc: res.data.folder_desc || "",
              images: productItems,
              detailImages: detailItems,
            };
            console.log(this.product);
          }
        } else {
          // 开发fallback：模拟数据
          this.product = {
            id: 1,
            title: "13707系列",
            desc: "新款全棉纽扣工艺款系列",
            images: [],
            detailImages: [],
          };
        }
      } catch (e) {
        console.error(e);
      }
    },

    // 上传单张图片（选择后立即上传并追加到 product.images）
    uploadImage() {
      if (!this.hasProduct) {
        uni.showToast({ title: "请先创建或选择产品", icon: "none" });
        return;
      }
      uni.chooseImage({
        count: 1,
        success: (res) => {
          const temp = res.tempFilePaths[0];
          // 在界面做个加载提示
          uni.showLoading({ title: "上传中..." });
          this.uploadSingleFile(temp)
            .then((url) => {
              // 调后端接口把图片关联到 product（示例）
              if (this.$go) {
                this.$go(
                  "product/upload_image",
                  { id: this.product.id, image: url },
                  "post",
                  { show_err: true },
                )
                  .then(() => {
                    this.product.images.unshift(url);
                    uni.hideLoading();
                    uni.showToast({ title: "上传成功", icon: "none" });
                  })
                  .catch((err) => {
                    uni.hideLoading();
                    uni.showToast({ title: "关联图片失败", icon: "none" });
                    console.error(err);
                  });
              } else {
                // 回退：本地追加
                this.product.images.unshift(url);
                uni.hideLoading();
                uni.showToast({ title: "上传成功（示例）", icon: "none" });
              }
            })
            .catch((err) => {
              uni.hideLoading();
              uni.showToast({ title: "上传失败", icon: "none" });
              console.error(err);
            });
        },
      });
    },

    uploadSingleFile(filePath) {
      return new Promise((resolve, reject) => {
        if (/^https?:\/\//.test(filePath)) {
          resolve(filePath);
          return;
        }
        uni.uploadFile({
          url: this.uploadEndpoint,
          filePath,
          name: "file",
          success: (uploadRes) => {
            try {
              const data = JSON.parse(uploadRes.data);
              const url =
                data.url || (data.data && data.data.url) || data.fileUrl || "";
              if (url) resolve(url);
              else reject(new Error("返回格式不正确"));
            } catch (e) {
              reject(e);
            }
          },
          fail: (e) => reject(e),
        });
      });
    },

    contactOwner() {
      // 你可以在这里实现跳转到聊天/拨号等逻辑
      this.personalVisible = true;
    },
    handleImageClick(data) {
      if (!data || !data.id) {
        return;
      }
      const item = this.normalizeProductPicture(data);
      uni.setStorageSync("picInfo", {
        ...item,
        pic_id: item.id,
        picture_url: item.picture_url || item.imgurl,
        picture_url_original: item.picture_url_original || item.imgurl,
        image_urls: item.image_urls,
        imageUrls: item.imageUrls,
      });
      uni.navigateTo({
        url: "/pagesOther/picDetail/picDetail?pic_id=" + item.id,
      });
    },

    onShare() {
      this.shareUrl = this.buildShareUrl();
      this.shareVisible = true;
    },
    onShareAction(event) {
      console.log("share action", event);
    },
    onSettings() {
      uni.navigateTo({ url: "/pagesOther/setting/setting?id=" + this.pid });
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  background: #f5f5f5;
  padding: 24rpx;
  box-sizing: border-box;
}

.content {
  margin-top: 40rpx;
}

.title {
  font-weight: bold;
  font-size: 36rpx;
  color: #333333;
  margin-bottom: 12rpx;
}

.desc {
  font-weight: 400;
  font-size: 28rpx;
  color: #333333;
  line-height: 42rpx;
  margin-bottom: 20rpx;
}

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

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

.section-title {
  font-weight: 700;
  font-size: 32rpx;
  color: #333333;
  margin: 20rpx 0 16rpx;
}

.detail-list-wrap {
  padding-bottom: 200rpx;
}

.detail-image {
  width: 100%;
  margin-bottom: 20rpx;
  overflow: hidden;
  border-radius: 8rpx;
  background: #ffffff;
}

.detail-img {
  width: 100%;
  display: block;
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
  justify-content: space-between;
  padding: 16rpx 32rpx;
  gap: 24rpx;
  padding-bottom: calc(env(safe-area-inset-bottom) + 10rpx);
}

/* 黄色胶囊（包含图标框和文字） */
.share-inner {
  flex: 1;
  min-width: 0;
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
  min-width: 220rpx;
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
</style>
