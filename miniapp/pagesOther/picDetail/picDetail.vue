<template>
  <view class="pic-detail">
    <!-- 顶部导航栏 -->
    <view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view
        class="navbar-content"
        :style="{ height: navigationBarHeight + 'px' }"
      >
        <!-- 左侧返回按钮 -->
        <view class="navbar-left" @tap="goBack">
          <image
            class="back-icon"
            src="/static/icon/back2.png"
            mode="aspectFit"
          ></image>
        </view>

        <!-- 中间标题区域 -->
        <view class="navbar-center">
          <view class="title-text">{{
            imageInfo.pic_beizhu || "图片详情"
          }}</view>
          <view class="subtitle-text" v-if="imageInfo.nickname">
            <text class="merchant-name">{{ imageInfo.nickname }}</text>
            <text class="upload-time">{{ imageInfo.upload_time }}上传</text>
          </view>
        </view>

        <!-- 右侧操作按钮 -->
        <view class="navbar-right">
          <image
            class="more-icon"
            src="/static/icon/more.png"
            mode="aspectFit"
            @tap="showMoreActions"
          ></image>
          <image
            class="collect-icon"
            src="/static/icon/collect.png"
            mode="aspectFit"
            @tap="handleCollect"
          ></image>
        </view>
      </view>
    </view>

    <!-- 媒体预览区域 -->
    <view
      class="media-container"
      :style="{ paddingTop: statusBarHeight + navigationBarHeight + 'px' }"
      @tap="toggleActions"
    >
      <swiper
        :indicator-dots="false"
        :autoplay="false"
        :current="currentIndex"
        style="width: 100%; height: 100%"
        @change="onSwiperChange"
      >
        <swiper-item
          v-for="(item, index) in picList"
          :key="index"
          style="width: 100%; height: 100%; background-color: #333"
        >
          <!-- 视频项 -->
          <view v-if="item.file_type == 2" class="video-container">
            <video
              :id="`video-player-${index}`"
              :key="item.picture_url"
              :src="item.picture_url"
              :poster="item.poster || '/static/icon/play.png'"
              class="video-player"
              :controls="false"
              :show-center-play-btn="true"
              :enable-progress-gesture="false"
              :show-play-btn="false"
              :show-fullscreen-btn="false"
              :show-progress="false"
              object-fit="contain"
              @play="onVideoPlay"
              @pause="onVideoPause"
              @touchstart="handleVideoTouch($event)"
              @touchmove="handleVideoMove($event)"
              @touchend="handleVideoEnd($event)"
            ></video>
          </view>
          <!-- 图片项 -->
          <view v-else class="image-container">
            <image
              :src="item.picture_url"
              mode="aspectFit"
              class="preview-image"
            />
          </view>
        </swiper-item>
      </swiper>
    </view>

    <!-- 视频底部操作栏 -->
    <view
      v-if="currentItem && currentItem.file_type == 2"
      class="bottom-actions"
      :class="{ hide: !showActions }"
      @tap.stop
    >
      <view class="action-item" @tap="handleDelete">
        <view class="icon-wrapper">
          <image src="/static/icon/del.png" mode=""></image>
        </view>
        <text class="action-text">删除</text>
      </view>

      <view class="action-item" @tap="handleDownload">
        <view class="icon-wrapper">
          <image src="/static/icon/download.png" mode=""></image>
        </view>
        <text class="action-text">保存</text>
      </view>

      <view class="action-item" @tap="handlePlay">
        <view class="icon-wrapper">
          <image src="/static/icon/play.png" v-if="!isPlaying" mode=""></image>
          <image src="/static/icon/stop.png" v-else mode=""></image>
        </view>
        <text class="action-text">{{ isPlaying ? "暂停" : "播放" }}</text>
      </view>

      <view class="action-item" @tap="handleEdit">
        <view class="icon-wrapper">
          <image src="/static/icon/remark.png" mode=""></image>
        </view>
        <text class="action-text">重命名</text>
      </view>
    </view>

    <!-- 图片底部操作栏 -->
    <view v-else class="toolbar" :class="{ hide: !showActions }">
      <view
        v-for="(tool, index) in toolList"
        :key="index"
        class="tool-item"
        @tap.stop="handleToolClick(tool.action)"
      >
        <view class="tool-icon">
          <image :src="tool.icon" mode="aspectFit"></image>
        </view>
        <text class="tool-text">{{ tool.text }}</text>
      </view>
    </view>

    <!-- 删除确认弹窗 -->
    <u-popup :show="deletePopup.show" mode="center" :round="10">
      <view class="popBox">
        <view class="pop-title">提示</view>
        <view class="input-content white-bg">
          删除此照片还是把它从相册中移除
        </view>
        <view
          class="input-content white-bg primary-color"
          @click="confirmDelete(2)"
        >
          从相册中移除
        </view>
        <view
          class="input-content white-bg danger-color"
          @click="confirmDelete(1)"
        >
          删除照片
        </view>
        <view class="input-content white-bg" @click="deletePopup.show = false">
          取消
        </view>
      </view>
    </u-popup>

    <!-- 相册选择弹窗 -->
    <u-popup
      :show="albumPopup.show"
      :round="10"
      mode="bottom"
      @close="closeAlbumPopup"
    >
      <view class="myAlbum">
        <view class="pop-title">
          <image class="backIcon" src="/static/icon/back.png" mode=""></image>
          我的相册
          <view class="add-btn" @click="showCreatePopup">新建</view>
        </view>
        <view class="album-box">
          <view class="album" v-for="(item, index) in albumList" :key="index">
            <view class="album-img" @click="selectAlbum(item, index)">
              <image
                :src="item.new_thumb || '/static/image/pic.png'"
                mode="aspectFill"
              ></image>
              <view class="select-box" v-if="item.isChecked">
                <image src="/static/icon/checked.png" mode=""></image>
              </view>
            </view>
            <view class="album-name">{{ item.folder_name }}</view>
          </view>
        </view>
        <view class="submit-btn" @click="confirmAddToAlbum">确定</view>
      </view>
    </u-popup>

    <!-- 创建相册/文件夹弹窗 -->
    <u-popup :show="createPopup.show" mode="center" :round="10">
      <view class="popBox">
        <view class="pop-title">
          输入{{ createPopup.type === 2 ? "相册" : "文件夹" }}名称
        </view>
        <view class="input-content input-field">
          <input
            type="text"
            v-model="createPopup.name"
            maxlength="7"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('picCreateName', '请输入名称')"
            @tap="focusField('picCreateName')"
            @focus="focusField('picCreateName')"
            @blur="blurField('picCreateName')"
          />
        </view>
        <view class="btn-box">
          <view class="cancel" @click="createPopup.show = false">取消</view>
          <view class="submit" @click="confirmCreateAlbum">创建</view>
        </view>
      </view>
    </u-popup>

    <!-- 新建类型选择 -->
    <u-action-sheet
      :actions="actionList"
      cancelText="取消"
      :closeOnClickOverlay="true"
      :show="actionPopup.show"
      @select="selectActionType"
      @close="actionPopup.show = false"
    ></u-action-sheet>

    <!-- 备注弹窗 -->
    <u-popup
      :show="remarkPop.show"
      :round="10"
      mode="bottom"
      @close="closeRemarkPopup"
    >
      <view class="myAlbum" style="height: 500rpx">
        <view class="pop-title">
          修改照片备注
          <view class="add-btn" @click="submitRemark">保存</view>
        </view>
        <view class="input-remark">
          <textarea
            v-model="remark"
            style="width: 100%; height: 100%"
            name=""
            id=""
            cols="30"
            rows="10"
          ></textarea>
        </view>
      </view>
    </u-popup>
  </view>
</template>

<script>
export default {
  data() {
    return {
      // 状态栏高度
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,

      // 图片信息
      imageInfo: {
        picture_url: "",
        picture_url_original: "",
        user: "沫",
        uploadTime: "2025年10月11日 09:34上传",
        id: "",
        pic_id: "",
        pic_beizhu: "",
      },

      // 当前显示的图片URL
      currentImageUrl: "",
      // 当前显示的项目
      currentItem: null,
      // 当前索引
      currentIndex: 0,
      // 视频相关
      showActions: true, // 控制操作栏显示隐藏
      isPlaying: false, // 视频播放状态
      videoContext: null,
      // 触摸状态变量
      touchStartX: 0, // 触摸起始X坐标
      touchStartY: 0, // 触摸起始Y坐标
      isClick: false, // 是否为点击操作（非滑动）

      // 工具栏配置
      toolList: [
        { icon: "/static/icon/del.png", text: "删除", action: "handleDelete" },
        {
          icon: "/static/icon/down-yuantu.png",
          text: "下载原图",
          action: "handleDownload",
        },
        {
          icon: "/static/icon/remark-icon.png",
          text: "重命名",
          action: "handleEdit",
        },
        {
          icon: "/static/icon/share-pic.png",
          text: "分享",
          action: "handleShare",
        },
      ],

      // 删除弹窗
      deletePopup: {
        show: false,
      },

      // 相册选择弹窗
      albumPopup: {
        show: false,
      },
      remarkPop: {
        show: false,
      },
      remark: "",

      // 创建相册弹窗
      createPopup: {
        show: false,
        name: "",
        type: 2, // 1: 文件夹, 2: 相册
      },

      // 操作类型选择弹窗
      actionPopup: {
        show: false,
      },

      // 操作类型列表
      actionList: [{ name: "新建相册" }, { name: "新建文件夹" }],

      // 相册列表
      albumList: [],

      // 选中的文件夹ID路径
      selectedFolderIds: [],
      pic_type: 1,
      option_flag: "",
      picList: [],
      source: "",
      uid: "",
      picId: "",
      fromPage: "",
    };
  },

  onLoad(options) {
    // 获取系统信息
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;

    if (options.pic_type) {
      this.pic_type = options.pic_type;
    }
    this.fromPage = options.fromPage;
    this.picId = options.pic_id;
    if (
      (options.uid && options.uid != "undefined" && options.uid != null) ||
      options.fromPage === "styleResult"
    ) {
      this.uid = options.uid;

      console.log(options);
      this.toolList = [
        {
          icon: "/static/icon/down-yuantu.png",
          text: "下载原图",
          action: "handleViewOriginal",
        },
        {
          icon: "/static/icon/share-pic.png",
          text: "分享",
          action: "handleShare",
        },
      ];
    }
    this.picList = uni.getStorageSync("picList");
    this.option_flag = options.option_flag;
    this.source = options.source || "";
    console.log(this.option_flag, "this.option_flag");

    // 设置当前项，根据传递的pic_id查找
    if (this.picList && this.picList.length > 0) {
      let found = false;
      // 优先根据pic_id查找对应的图片
      console.log(options.pic_id, "options.pic_id");
      if (options.pic_id) {
        // 遍历数组找到对应的图片项
        for (let i = 0; i < this.picList.length; i++) {
          const item = this.picList[i];
          // 直接在item上查找pic_id
          console.log(item.pic_id, "item.pic_id");
          if (item.pic_id == options.pic_id) {
            this.currentItem = item;
            this.currentIndex = i;
            console.log("找到图片项:", this.currentItem, "索引:", i);
            found = true;
            break;
          }
        }
      }

      // 如果没有找到或没有提供pic_id，则使用第一个图片
      if (!found) {
        this.currentItem = this.picList[0];
        this.currentIndex = 0;
        console.log("未找到指定图片项，使用第一张图片:", this.currentItem);
      }
    }

    this.loadImageInfo();
    this.loadAlbumList();

    // 初始化视频上下文
    this.$nextTick(() => {
      this.initVideoContext();
    });
  },
  async onShareAppMessage() {
    console.log("触发分享");

    // 先尝试从缓存读取分享数据
    let shareData = uni.getStorageSync("shareDataForPage");

    // 如果缓存中没有，则重新获取
    if (!shareData) {
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");

      try {
        const shareConfig = await this.$getShareConfig({
          type: "link",
          userId: this.uid ? this.uid : userInfo.id,
          title: `分享图片`,
          path: "/pages/index/index",
        });

        if (shareConfig && shareConfig.code === 0) {
          shareData = {
            title: `分享图片`,
            path: shareConfig.share_link,
            imageUrl: enterpriseInfo.home_share_image || this.currentImageUrl,
          };
        } else {
          // 如果获取失败，使用默认数据
          shareData = {
            title: `分享图片`,
            path: "/pages/index/index",
            imageUrl: this.currentImageUrl,
          };
        }
      } catch (error) {
        console.error("获取分享配置失败:", error);
        shareData = {
          title: `分享图片`,
          path: "/pages/index/index",
          imageUrl: this.currentImageUrl,
        };
      }
    }

    // 清除缓存
    uni.removeStorageSync("shareDataForPage");

    return {
      title: shareData.title || "分享图片",
      path: shareData.path || "/pages/index/index",
      imageUrl: shareData.imageUrl || this.currentImageUrl,
    };
  },

  methods: {
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 显示更多操作
    showMoreActions() {
      // 可以在这里添加更多操作的弹窗
      console.log("显示更多操作");
    },

    // 收藏/取消收藏
    async handleCollect() {
      // 使用公共收藏方法
      const itemId =
        (this.currentItem && this.currentItem.id) || this.imageInfo.id;
      const isCollect =
        (this.currentItem && this.currentItem.is_collect) || false;

      const newStatus = await this.$toggleFavorite({
        type: "product", // 根据实际情况调整类型
        id: itemId,
        isFavorite: isCollect,
        request: this.$go,
        getASCII: this.$base.getASCII,
      });

      // 更新收藏状态
      if (this.currentItem) {
        this.currentItem.is_collect = newStatus;
      }
    },

    // ==================== 视频相关方法 ====================

    // 初始化视频上下文
    initVideoContext() {
      if (this.currentItem && this.currentItem.file_type == 2) {
        try {
          this.videoContext = uni.createVideoContext(
            `video-player-${this.currentIndex}`,
            this,
          );
          console.log("视频上下文初始化成功:", this.videoContext);
        } catch (error) {
          console.error("视频上下文初始化失败:", error);
        }
      } else {
        this.videoContext = null;
      }
    },

    // 切换操作栏显示/隐藏
    toggleActions() {
      this.showActions = !this.showActions;
    },

    // 播放视频
    playVideo() {
      console.log("尝试播放视频，当前索引:", this.currentIndex);
      if (this.videoContext) {
        this.videoContext.play();
        this.isPlaying = true;
      } else {
        // 如果视频上下文不存在，重新初始化
        console.log("视频上下文不存在，重新初始化");
        this.$nextTick(() => {
          this.initVideoContext();
          if (this.videoContext) {
            this.videoContext.play();
            this.isPlaying = true;
          }
        });
      }
    },

    // 暂停视频
    pauseVideo() {
      console.log("尝试暂停视频");
      if (this.videoContext) {
        this.videoContext.pause();
        this.isPlaying = false;
      } else {
        console.log("视频上下文不存在，状态设置为暂停");
        this.isPlaying = false;
      }
    },

    // 视频开始播放
    onVideoPlay() {
      this.isPlaying = true;
    },

    // 视频暂停
    onVideoPause() {
      this.isPlaying = false;
    },

    // 播放/暂停切换
    handlePlay() {
      console.log("播放/暂停切换，当前状态:", this.isPlaying);
      // 先重新初始化确保视频上下文正确
      this.initVideoContext();
      if (this.isPlaying) {
        this.pauseVideo();
      } else {
        this.playVideo();
      }
    },

    // 处理视频触摸开始事件
    handleVideoTouch(e) {
      // 记录触摸起始位置，用于判断是点击还是滑动
      this.touchStartX = e.touches[0].clientX;
      this.touchStartY = e.touches[0].clientY;
      this.isClick = true;

      // 如果视频正在播放，阻止事件冒泡和默认行为
      if (this.isPlaying) {
        e.stopPropagation();
        e.preventDefault();
      }
    },

    // 处理视频触摸移动事件
    handleVideoMove(e) {
      // 计算移动距离
      let moveX = Math.abs(e.touches[0].clientX - this.touchStartX);
      let moveY = Math.abs(e.touches[0].clientY - this.touchStartY);

      // 如果移动距离超过10px，判断为滑动而非点击
      if (moveX > 10 || moveY > 10) {
        this.isClick = false;
      }

      // 如果视频正在播放，阻止事件冒泡和默认行为，禁止滑动
      if (this.isPlaying) {
        e.stopPropagation();
        e.preventDefault();
      }
      // 视频未播放时，不阻止事件，允许滑动
    },

    // 处理视频触摸结束事件
    handleVideoEnd(e) {
      // 如果是点击操作且视频未播放，才执行播放
      if (this.isClick && !this.isPlaying) {
        this.handlePlay();
      }

      // 如果视频正在播放，阻止事件冒泡和默认行为
      if (this.isPlaying) {
        e.stopPropagation();
        e.preventDefault();
      }
    },

    // 切换swiper时更新当前项目
    onSwiperChange(e) {
      const current = e.detail.current;
      this.currentIndex = current;
      this.currentItem = this.picList[current];
      // 同步更新 imageInfo 和相关字段，确保 pic_id 始终为当前图片
      this.imageInfo = { ...this.imageInfo, ...this.currentItem };
      this.currentImageUrl = this.imageInfo.picture_url;
      this.remark = this.imageInfo.pic_beizhu;

      // 重置视频状态
      if (this.isPlaying) {
        this.isPlaying = false;
      }

      // 重新初始化视频上下文
      this.$nextTick(() => {
        this.initVideoContext();
      });
    },

    // ==================== 工具栏点击处理 ====================

    // 统一处理工具栏点击
    handleToolClick(action) {
      if (this[action] && typeof this[action] === "function") {
        this[action]();
      }
    },
    async handleShare() {
      // 先获取分享配置
      const enterpriseInfo = uni.getStorageSync("enterpriseInfo");
      const userInfo = uni.getStorageSync("userInfo");

      try {
        const shareConfig = await this.$getShareConfig({
          type: "link",
          userId: this.uid ? this.uid : userInfo.id,
          title: `分享图片`,
          path:
            "/pagesOther/picDetail/picDetail?pic_id=" +
            this.picId +
            "&uid=" +
            this.uid +
            "&fromPage=styleResult",
        });

        if (shareConfig && shareConfig.code === 0) {
          // 设置分享数据到全局，供 onShareAppMessage 使用
          uni.setStorageSync("shareDataForPage", {
            title: `分享图片`,
            path: shareConfig.share_link,
            imageUrl: enterpriseInfo.home_share_image || this.currentImageUrl,
          });

          // 调用微信分享菜单
          if (typeof wx !== "undefined" && wx.showShareMenu) {
            try {
              await wx.showShareMenu({ withShareTicket: true });
              // 提示用户点击右上角分享
              uni.showToast({
                title: "请点击右上角分享",
                icon: "none",
                duration: 2000,
              });
            } catch (e) {
              console.error("显示分享菜单失败:", e);
            }
          } else {
            uni.showToast({
              title: "请点击右上角分享",
              icon: "none",
              duration: 2000,
            });
          }
        } else {
          uni.showToast({
            title: shareConfig?.msg || "主页未公开",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("获取分享配置失败:", error);
        uni.showToast({
          title: "分享失败",
          icon: "none",
        });
      }
    },
    // ==================== 数据加载 ====================

    // 加载图片信息
    loadImageInfo() {
      const picInfo = uni.getStorageSync("picInfo");
      if (picInfo) {
        this.imageInfo = { ...this.imageInfo, ...picInfo };
        this.currentImageUrl = this.imageInfo.picture_url;
        this.remark = this.imageInfo.pic_beizhu;
        // 如果没有设置currentItem，则使用imageInfo
        if (!this.currentItem) {
          this.currentItem = { ...this.imageInfo };
        }
      }
    },

    // 加载相册列表
    loadAlbumList(fid = 0) {
      const params = this.buildApiParams({ fid });

      this.$go("album/show/folder", params, "post", { show_err: true })
        .then((res) => {
          this.albumList = res.data.map((item) => ({
            ...item,
            isChecked: false,
          }));
        })
        .catch((err) => {
          console.error("加载相册列表失败:", err);
        });
    },

    // ==================== 工具栏操作 ====================

    // 删除
    handleDelete() {
      if (this.option_flag == "false") {
        uni.showToast({
          title: "你没有删除该媒体的权限",
          icon: "none",
        });
      } else {
        this.deletePopup.show = true;
      }
    },

    // 确认删除
    confirmDelete(deleteType) {
      const currentItem = this.currentItem || this.imageInfo;
      if (this.source === "mineImg") {
        // 来自我的图片页，调用我的图片删除接口
        const params = this.buildApiParams({
          pic_ids: currentItem.pic_id || currentItem.id,
          del_type: deleteType,
          fid: -1,
        });
        this.$go("user/delete/pic", params, "post", { show_err: true })
          .then((res) => {
            this.deletePopup.show = false;
            uni.showToast({
              title: deleteType === 1 ? "删除成功" : "移除成功",
              icon: "success",
            });
            setTimeout(() => {
              uni.navigateBack();
            }, 1000);
          })
          .catch((err) => {
            console.error("删除失败:", err);
          });
      } else if (this.source === "mineLove") {
        // 来自我的收藏页，调用收藏删除接口
        const params = this.buildApiParams({
          collect_ids: currentItem.id,
          del_type: deleteType,
          fid: -1,
        });
        this.$go("user/collect", params, "post", { show_err: true })
          .then((res) => {
            this.deletePopup.show = false;
            uni.showToast({
              title: deleteType === 1 ? "删除成功" : "移除成功",
              icon: "success",
            });
            setTimeout(() => {
              uni.navigateBack();
            }, 1000);
          })
          .catch((err) => {
            console.error("删除失败:", err);
          });
      } else {
        // 默认走相册删除接口
        const params = this.buildApiParams({
          pic_id: currentItem.id || currentItem.pic_id,
          del_type: deleteType,
        });
        this.$go("album/delete/pic", params, "post", { show_err: true })
          .then((res) => {
            this.deletePopup.show = false;
            uni.showToast({
              title: deleteType === 1 ? "删除成功" : "移除成功",
              icon: "success",
            });
            setTimeout(() => {
              uni.navigateBack();
            }, 1000);
          })
          .catch((err) => {
            console.error("删除失败:", err);
          });
      }
    },

    // 保存媒体到相册
    handleDownload() {
      const currentItem = this.currentItem || this.imageInfo;
      const fileUrl =
        currentItem.picture_url_original || currentItem.picture_url;
      const isVideo = currentItem.is_video;

      if (!fileUrl) {
        uni.showToast({
          title: "媒体地址无效",
          icon: "none",
        });
        return;
      }

      let userInfo = uni.getStorageSync("userInfo");
      if (userInfo.grade_level == 0) {
        uni.showToast({
          title: "请先升级成为会员",
          icon: "none",
        });
        return;
      }

      uni.showLoading({ title: "保存中..." });

      uni.downloadFile({
        url: fileUrl,
        success: (res) => {
          if (res.statusCode === 200) {
            if (isVideo) {
              this.saveVideoToPhotosAlbum(res.tempFilePath);
            } else {
              this.saveImageToPhotosAlbum(res.tempFilePath);
            }
          } else {
            this.showDownloadError("下载失败");
          }
        },
        fail: () => {
          this.showDownloadError("下载失败");
        },
      });
    },

    // 保存图片到系统相册
    saveImageToPhotosAlbum(filePath) {
      uni.saveImageToPhotosAlbum({
        filePath,
        success: () => {
          uni.hideLoading();
          uni.showToast({
            title: "保存成功",
            icon: "success",
          });
        },
        fail: (err) => {
          uni.hideLoading();
          if (err.errMsg.includes("auth")) {
            this.showAuthModal();
          } else {
            uni.showToast({
              title: "保存失败",
              icon: "none",
            });
          }
        },
      });
    },

    // 保存视频到系统相册
    saveVideoToPhotosAlbum(filePath) {
      uni.saveVideoToPhotosAlbum({
        filePath,
        success: () => {
          uni.hideLoading();
          uni.showToast({
            title: "保存成功",
            icon: "success",
          });
        },
        fail: () => {
          uni.hideLoading();
          uni.showToast({
            title: "保存失败",
            icon: "none",
          });
        },
      });
    },

    // 显示授权弹窗
    showAuthModal() {
      uni.showModal({
        title: "提示",
        content: "需要您授权保存图片到相册",
        success: (res) => {
          if (res.confirm) {
            uni.openSetting();
          }
        },
      });
    },

    // 显示下载错误
    showDownloadError(message) {
      uni.hideLoading();
      uni.showToast({
        title: message,
        icon: "none",
      });
    },

    // 查看原图
    handleViewOriginal() {
      // 只有图片才可以查看原图
      if (this.currentItem && this.currentItem.is_video) {
        return;
      }

      let userInfo = uni.getStorageSync("userInfo");
      if (userInfo.grade_level == 0) {
        uni.showToast({
          title: "请先升级成为会员",
          icon: "none",
        });
      } else {
        if (this.imageInfo.picture_url_original) {
          this.currentImageUrl = this.imageInfo.picture_url_original;
          this.handleDownload();
        }
      }
    },

    // 添加到相册
    handleAddToAlbum() {
      if (this.option_flag == "false") {
        uni.showToast({
          title: "你没有添加该图片的权限",
          icon: "none",
        });
      } else {
        this.selectedFolderIds = [];
        this.albumPopup.show = true;
        this.loadAlbumList();
      }
    },

    // 编辑备注
    handleEdit() {
      if (this.option_flag == "false") {
        uni.showToast({
          title: "你没有备注该图片的权限",
          icon: "none",
        });
      } else {
        let userInfo = uni.getStorageSync("userInfo");
        if (userInfo.grade_level == 1) {
          uni.showToast({
            title: "请先升级成为会员",
            icon: "none",
          });
        } else {
          this.remarkPop.show = true;
        }
      }
    },

    submitRemark() {
      const params = {
        pic_id: this.picId,
        pic_name: this.remark,
      };
      this.$go("album/pics/rename", params, "post", { show_err: true })
        .then((res) => {
          uni.showToast({
            title: "重命名成功",
            icon: "success",
          });
          // 更新当前项与列表中的备注，保持 UI 与数据一致
          this.imageInfo.pic_beizhu = this.remark;
          if (this.currentItem) {
            this.currentItem.pic_beizhu = this.remark;
          }
          if (Array.isArray(this.picList) && this.picList[this.currentIndex]) {
            this.picList[this.currentIndex].pic_beizhu = this.remark;
          }
          this.closeRemarkPopup();
        })
        .catch((err) => {
          console.error("添加失败:", err);
        });
    },

    // ==================== 相册操作 ====================

    // 选择相册
    selectAlbum(album, index) {
      // 如果是相册类型,标记选中
      if (album.folder_type === 2) {
        this.selectedFolderIds.push(album.id);
        this.updateAlbumCheckedState(index);
      }
      // 如果是文件夹,进入下一级
      else if (album.folder_type === 1) {
        this.selectedFolderIds.push(album.id);
        this.loadAlbumList(album.id);
      }
    },

    // 更新相册选中状态
    updateAlbumCheckedState(selectedIndex) {
      this.albumList.forEach((item, idx) => {
        item.isChecked = idx === selectedIndex;
      });
    },

    // 确认添加到相册
    confirmAddToAlbum() {
      if (this.selectedFolderIds.length === 0) {
        uni.showToast({
          title: "请选择相册",
          icon: "none",
        });
        return;
      }

      const currentItem = this.currentItem || this.imageInfo;
      const params = this.buildApiParams({
        fid: this.selectedFolderIds[this.selectedFolderIds.length - 1],
        pic_ids: currentItem.pic_id || currentItem.id,
      });

      this.$go("album/move/pics", params, "post", { show_err: true })
        .then((res) => {
          uni.showToast({
            title: "添加成功",
            icon: "success",
          });
          this.closeAlbumPopup();
        })
        .catch((err) => {
          console.error("添加到相册失败:", err);
        });
    },

    // 关闭相册弹窗
    closeAlbumPopup() {
      this.albumPopup.show = false;
      this.selectedFolderIds = [];
      this.albumList.forEach((item) => (item.isChecked = false));
    },

    closeRemarkPopup() {
      uni.$emit("refreshProductlData");
      this.remarkPop.show = false;
      this.remark = "";
    },

    // ==================== 创建相册/文件夹 ====================

    // 显示创建弹窗
    showCreatePopup() {
      this.actionPopup.show = true;
    },

    // 选择创建类型
    selectActionType(action) {
      this.createPopup.type = action.name === "新建相册" ? 2 : 1;
      this.createPopup.name = "";
      this.createPopup.show = true;
    },

    // 确认创建相册/文件夹
    confirmCreateAlbum() {
      if (!this.createPopup.name.trim()) {
        uni.showToast({
          title: "请输入名称",
          icon: "none",
        });
        return;
      }

      const params = this.buildApiParams({
        folder_type: this.createPopup.type,
        folder_name: this.createPopup.name,
      });

      this.$go("album/create/folder", params, "post", { show_err: true })
        .then((res) => {
          uni.showToast({
            title: res.msg || "创建成功",
            icon: "none",
          });
          this.createPopup.show = false;
          this.createPopup.name = "";
          this.loadAlbumList();
        })
        .catch((err) => {
          console.error("创建失败:", err);
        });
    },

    // ==================== 工具方法 ====================

    // 构建API请求参数
    buildApiParams(params) {
      const querys = {
        ...params,
        timestamp: new Date().getTime(),
      };

      return {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
    },
  },
};
</script>

<style lang="scss" scoped>
// ==================== 页面容器 ====================
.pic-detail {
  width: 100%;
  height: 100vh;
  background-color: #333;
  position: relative;
  overflow: hidden;
}

// ==================== 自定义导航栏 ====================
.custom-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0) 100%
  );
  z-index: 1000;

  .navbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30rpx;

    .navbar-left {
      width: 80rpx;
      display: flex;
      align-items: center;

      .back-icon {
        width: 40rpx;
        height: 40rpx;
      }
    }

    .navbar-center {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .title-text {
        font-size: 32rpx;
        font-weight: 600;
        color: #ffffff;
        line-height: 1.2;
        max-width: 400rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .subtitle-text {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 4rpx;
        display: flex;
        align-items: center;

        .merchant-name {
          color: #ffd700;
          margin-right: 16rpx;
        }

        .upload-time {
          color: rgba(255, 255, 255, 0.6);
        }
      }
    }

    .navbar-right {
      width: 120rpx;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 20rpx;

      .more-icon,
      .collect-icon {
        width: 40rpx;
        height: 40rpx;
      }
    }
  }
}

// ==================== 媒体预览区域 ====================
.media-container {
  width: 100%;
  height: 100vh;
  position: relative;
}

// ==================== 图片预览区域 ====================
.image-container {
  width: 100%;
  height: 80%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #333;

  .preview-image {
    width: 100%;
    height: 100%;
  }
}

// ==================== 视频预览区域 ====================
.video-container {
  width: 100%;
  height: 100%;
  background-color: #000;

  .video-player {
    width: 100%;
    height: 100%;
  }
}

// ==================== 底部信息栏 ====================
.info-bar {
  position: fixed;
  bottom: 120rpx;
  left: 0;
  right: 0;
  padding: 20rpx 40rpx;
  background: linear-gradient(
    0deg,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0) 100%
  );
  z-index: 99;
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;

  &.hide {
    display: none;
    pointer-events: none;
  }

  .upload-info {
    display: flex;
    align-items: center;
    color: #fff;
    font-size: 24rpx;

    .upload-user {
      margin-right: 20rpx;
      font-weight: 500;
    }

    .upload-time {
      opacity: 0.8;
    }
  }
}

// ==================== 底部操作栏 ====================
.toolbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 90rpx;
  background-color: #333;
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 20rpx 0;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  // border-top: 1px solid #f0f0f0;
  z-index: 99;
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;

  &.hide {
    display: none;
    pointer-events: none;
  }

  .tool-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    cursor: pointer;

    .tool-icon {
      width: 40rpx;
      height: 40rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8rpx;
      transition: transform 0.2s ease;

      image {
        width: 100%;
        height: 100%;
      }
    }

    .tool-text {
      font-size: 22rpx;
      color: #666;
    }

    &:active .tool-icon {
      transform: scale(0.9);
    }
  }
}

// ==================== 视频底部操作栏 ====================
.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 90rpx;
  background-color: #333;
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 20rpx 0;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  // border-top: 1px solid #f0f0f0;
  z-index: 99;
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;

  &.hide {
    display: none;
    pointer-events: none;
  }

  .action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    cursor: pointer;

    .icon-wrapper {
      width: 40rpx;
      height: 40rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8rpx;
      transition: transform 0.2s ease;

      image {
        width: 100%;
        height: 100%;
      }
    }

    .action-text {
      font-size: 22rpx;
      color: #666;
    }

    &:active .icon-wrapper {
      transform: scale(0.9);
    }
  }
}

// ==================== 弹窗通用样式 ====================
.popBox {
  width: 600rpx;
  background-color: #fff;
  border-radius: 10rpx;
  overflow: hidden;

  .pop-title {
    width: 100%;
    height: 100rpx;
    border-bottom: 2rpx solid #f6f6f6;
    text-align: center;
    line-height: 100rpx;
    font-size: 30rpx;
  }

  .input-content {
    width: 540rpx;
    height: 80rpx;
    margin: 20rpx auto 0;
    border-radius: 10rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;

    &.white-bg {
      background-color: #ffffff;
    }

    &.input-field {
      background-color: #f8f3f3;
      padding: 0 20rpx;
      box-sizing: border-box;

      input {
        width: 100%;
        font-size: 28rpx;
      }
    }

    &.primary-color {
      color: #759eeb;
    }

    &.danger-color {
      color: #e02d23;
    }
  }

  .btn-box {
    display: flex;
    align-items: center;
    width: 100%;
    height: 100rpx;
    border-top: 2rpx solid #f6f6f6;
    margin-top: 20rpx;

    .cancel,
    .submit {
      width: 50%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28rpx;
    }

    .cancel {
      border-right: 2rpx solid #f6f6f6;
      color: #666;
    }

    .submit {
      color: #7590e8;
    }
  }
}

// ==================== 相册选择弹窗 ====================
.myAlbum {
  height: 900rpx;
  padding: 30rpx;
  box-sizing: border-box;
  background-color: #fff;

  .pop-title {
    font-size: 30rpx;
    font-weight: 500;
    display: flex;
    align-items: center;
    margin-bottom: 40rpx;

    .backIcon {
      width: 30rpx;
      height: 30rpx;
      margin-right: 20rpx;
    }

    .add-btn {
      width: 100rpx;
      height: 60rpx;
      background-color: #e6f5e9;
      border-radius: 10rpx;
      color: #57be6b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26rpx;
      margin-left: auto;
    }
  }

  .input-remark {
    width: 710rpx;
    height: 400rpx;
    border-radius: 20rpx;
    background-color: #f8f8f8;
    margin: 0 auto;
    margin-top: 20rpx;
    padding: 20rpx 30rpx;
    font-size: 26rpx;
    box-sizing: border-box;
  }

  .album-box {
    width: 100%;
    height: 600rpx;
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;

    .album {
      width: 200rpx;
      margin-right: 20rpx;
      margin-bottom: 30rpx;

      .album-img {
        width: 200rpx;
        height: 200rpx;
        border-radius: 10rpx;
        position: relative;
        overflow: hidden;

        image {
          width: 100%;
          height: 100%;
        }

        .select-box {
          position: absolute;
          right: 10rpx;
          bottom: 10rpx;
          width: 30rpx;
          height: 30rpx;

          image {
            width: 100%;
            height: 100%;
          }
        }
      }

      .album-name {
        font-size: 26rpx;
        margin-top: 10rpx;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }
  }

  .submit-btn {
    width: 600rpx;
    height: 80rpx;
    background-color: #57be6b;
    border-radius: 10rpx;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20rpx auto 0;
    font-size: 30rpx;
  }
}
</style>
