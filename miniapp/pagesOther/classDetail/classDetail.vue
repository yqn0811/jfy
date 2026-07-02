<template>
  <view class="page">
    <view class="page-scoll">
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
              category.title || "分类名称"
            }}</text>
            <text class="category-desc" v-if="category.desc">{{
              category.desc
            }}</text>
          </view>
        </view>
      </view>

      <!-- 子分类网格 -->
      <view class="content" v-if="hasChildren">
        <ImageGrid
          :list="children"
          :columns="columns"
          badge-suffix="个"
          @click="handleCategoryClick"
        >
        </ImageGrid>

        <!-- 当有图片但少于一行时也显示“没有更多了~” -->
        <view
          v-if="children && children.length > 0 && children.length < minShowCount"
          class="empty-sub"
        >
          <text class="empty-text">没有更多了~</text>
        </view>
      </view>
      <!-- 无子分类时的占位与操作 -->
      <view v-if="!hasChildren && canManageChildCategory" class="class-empty">
        <image
          src="/static/icon/Frame@2x(25).png"
          mode="widthFix"
          class="empty-img"
        ></image>

        <view class="actions">
          <view class="btn btn-outline" @tap="createChildCategory">
            <image
              class="btn-icon"
              src="/static/icon/add-yellow-icon.png"
              mode="scaleToFill"
            />
            <text class="btn-text">新建子分类</text>
          </view>
          <view class="btn btn-outline" @tap="openChildSort">
            <image
              class="btn-icon"
              src="/static/icon/image-3@2x(2).png"
              mode="scaleToFill"
            />
            <text class="btn-text">子分类排序</text>
          </view>
        </view>
      </view>

      <!-- 底部固定操作栏 -->
      <view class="bottom-bar">
        <view class="action-btn" @tap="contactOwner">
          <image
            src="/static/icon/user.png"
            class="action-icon"
            mode="widthFix"
          />
          <text class="action-text">简介</text>
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
      :uid="uid || ''"
      :visible="personalVisible"
      @update:visible="(val) => (personalVisible = val)"
    />

    <!-- 分享弹窗（复用组件） -->
    <share-popup
      :visible="shareVisible"
      :title="shareTitle"
      :uid="uid || ''"
      typeText="分类"
      type="category"
      :hid="categoryId"
      :url="shareUrl"
      :mini-qr="shareMiniQr"
      :mini-path="shareMiniPath"
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
      categoryId: null,
      category: {
        title: "",
        desc: "",
        pid: 0,
        level: 1,
      },
      children: [],
      loading: false,
      isFavorited: false,
      shareVisible: false,
      shareTitle: "",
      shareUrl: "",
      shareMiniQr: "",
      shareMiniPath: "",
      minShowCount: 1, // 控制“没有更多了~”显示逻辑
      columns: 2,
      uid: "", // 用户分享的用户ID
    };
  },
  computed: {
    hasChildren() {
      return !!this.children.length;
    },
    canManageChildCategory() {
      return !this.uid && this.isTopLevelCategory;
    },
    isTopLevelCategory() {
      const pid = Number(this.category.pid || 0);
      const level = Number(this.category.level || 1);
      return pid === 0 && level <= 1;
    },
  },
  onLoad(options) {
    // 支持通过 options 传入分类 id
    if (options && options.id) this.categoryId = options.id;
    this.uid = options.uid || "";
    this.shareUrl = this.buildShareUrl();
    this.initUserFromCache();

    // 通过 eventChannel 接收上一页面传递的数据
    const eventChannel = this.getOpenerEventChannel();
    if (eventChannel) {
      eventChannel.on("acceptDataFromOpenerPage", (data) => {
        console.log("通过 eventChannel 接收到完整的分类数据:", data);
        if (data && data.data) {
          const classDetailData = data.data;
          this.isFavorited = classDetailData.is_collect ? true : false;
          // 使用完整数据预填充
          this.category.title =
            classDetailData.folder_name || classDetailData.title || "";
          this.category.desc =
            classDetailData.folder_desc || classDetailData.desc || "";
          this.applyCategoryLevel(classDetailData);
          this.columns = this.normalizeColumns(
            classDetailData.layout_type || classDetailData.pic_layout || 2
          );
        }
      });
    }

    this.loadOwnerInfo();
    this.loadChildren();
    const token = uni.getStorageSync("token");
    if (token) {
      this.$addVisit({ id: options.id, type: "category" });
    }
    // 监听分类更新事件
    uni.$on("refreshClassDetailData", this.handleRefreshData);
  },
  onUnload() {
    uni.$off("refreshClassDetailData", this.handleRefreshData);
  },
  onShareAppMessage() {
    // 尝试读取组件写入的临时分享数据
    let shareData = {};
    try {
      const d = uni.getStorageSync("shareDataForPage");
      if (d) shareData = d;
    } catch (e) {}

    return {
      title: shareData.title || this.category.title || "分类分享",
      path: shareData.path || this.buildShareUrl(),
      imageUrl: shareData.imageUrl || "", // 可选：海报/缩略图
    };
  },
  methods: {
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
      };
    },
    loadOwnerInfo() {
      if (this.uid) {
        this.loadUserInfo(this.uid);
        return;
      }
      const cachedUser = uni.getStorageSync("userInfo") || {};
      const ownerId = cachedUser.id || cachedUser.uid || "";
      if (ownerId) {
        this.loadUserInfo(ownerId);
      }
    },
    normalizeColumns(value) {
      return Number(value) === 2 ? 1 : 2;
    },
    buildShareUrl() {
      if (!this.categoryId) {
        return "/pagesOther/classDetail/classDetail";
      }
      let url = `/pagesOther/classDetail/classDetail?id=${this.categoryId}`;
      if (this.uid) {
        url += `&uid=${this.uid}`;
      }
      return url;
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
          this.applyUserInfo(res.data);
          if (!this.uid) {
            uni.setStorageSync("enterpriseInfo", res.data);
          }
        }
      } catch (e) {
        console.error(e);
      }
    },
    async loadPublicCategoryInfo() {
      if (!this.uid || !this.categoryId || !this.$go) return;
      try {
        const res = await this.$go(
          "user/home/categories",
          { target_user_id: this.uid },
          "get",
          { show_err: false }
        );
        const list = Array.isArray(res && res.data) ? res.data : [];
        const current = this.findCategoryInTree(list, this.categoryId);
        this.applyCategoryInfo(current);
      } catch (e) {
        console.error(e);
      }
    },
    handleRefreshData() {
      this.loadChildren();
    },
    applyCategoryInfo(info) {
      if (!info) return;
      this.category.title =
        info.folder_name || info.title || this.category.title;
      this.category.desc = info.folder_desc || info.desc || this.category.desc;
      this.applyCategoryLevel(info);
      if (
        info.layout_type !== undefined &&
        info.layout_type !== null &&
        info.layout_type !== ""
      ) {
        this.columns = this.normalizeColumns(info.layout_type);
      }
      if (info.is_collect !== undefined) {
        this.isFavorited = !!info.is_collect;
      }
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
    async loadChildren() {
      this.loading = true;
      try {
        if (this.$go && this.categoryId) {
          if (this.uid) {
            this.loadPublicCategoryInfo();
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
            };
          }
          const url = this.uid ? "user/home/categories" : "album/lists/folder";
          const methods = this.uid ? "get" : "post";
          const res = await this.$go(url, params, methods, { show_err: true });

          // 尝试补全用户信息：uid 只表示访客态，不要把自己的用户 id 写进 uid。
          if (!this.uid && res.data && (res.data.user_id || res.data.uid)) {
            this.loadUserInfo(res.data.user_id || res.data.uid);
          }

          this.applyUserInfo(res.data.user_info || res.data.user || {});

          if (!this.uid && res.data && res.data.folder_info) {
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
                  item.new_thumb || item.icon || "/static/icon/folder-open@2x.png",
                countField,
              };
            });
          } else {
            this.children = [];
          }
        }
      } catch (e) {
        console.error(e);
        uni.showToast({ title: "加载分类失败", icon: "none" });
      } finally {
        this.loading = false;
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
    getDisplayCategoryName(item) {
      const name = item && (item.folder_name || item.title || item.name);
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
      // 准备分享数据（可从后端获取小程序码或分享链接）
      this.shareTitle = this.category.title;
      this.shareUrl = this.buildShareUrl();
      // 假定后端提供小程序码地址或本地静态
      this.shareMiniQr = `/static/image/mini-qrcode.png`;
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
