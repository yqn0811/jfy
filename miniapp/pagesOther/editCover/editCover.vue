<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <view class="header" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
        <view :style="{ height: statusBarHeight + 'px' }"></view>
        <view
          class="nav-bar-content"
          :style="{ height: navigationBarHeight + 'px' }"
        >
          <view class="left" @click="back">
            <image
              class="back-icon"
              src="/static/icon/back.png"
              mode="aspectFit"
            ></image>
          </view>
          <view class="title">编辑封面</view>
          <view class="right"></view>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <!-- 当前封面预览 -->
      <view class="current-cover-section">
        <view class="section-title">当前封面</view>
        <view class="current-cover-box">
          <image
            v-if="currentCover"
            class="current-cover-img"
            :src="currentCover"
            mode="aspectFill"
            @click="previewImage(currentCover)"
          ></image>
          <view v-else class="no-cover">
            <image
              class="empty-icon"
              src="/static/icon/empty.png"
              mode="aspectFit"
            ></image>
            <text class="empty-text">暂无封面</text>
          </view>
        </view>
      </view>

      <!-- 选择新封面 -->
      <view class="upload-section">
        <view class="section-title">选择新封面</view>
        <view class="upload-tip"
          >支持上传 JPG、PNG 格式图片，建议使用 4:3 或接近比例图片</view
        >

        <view class="upload-area">
          <!-- 已选择的新封面 -->
          <view v-if="newCover" class="new-cover-box">
            <image
              class="new-cover-img"
              :src="newCover"
              mode="aspectFill"
              @click="previewImage(newCover)"
            ></image>
            <view class="remove-btn" @click="removeNewCover">
              <image
                class="remove-icon"
                src="/static/icon/close.png"
                mode="aspectFit"
              ></image>
            </view>
            <view v-if="uploading" class="upload-mask">
              <text>上传中...</text>
            </view>
          </view>

          <!-- 上传按钮 -->
          <view v-else class="upload-btn" @click="chooseImage">
            <image
              class="upload-icon"
              src="/static/icon/upload-top-icon.png"
              mode="aspectFit"
            ></image>
            <text class="upload-text">点击上传</text>
          </view>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="bottom-actions">
        <view class="action-btn cancel-btn" @click="back">取消</view>
        <view
          class="action-btn save-btn"
          :class="{ disabled: !canSave }"
          @click="saveCover"
        >
          保存
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { buildAuthHeader } from "@/common/helper/auth.js";

export default {
  data() {
    return {
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,

      folderId: "", // 产品/分类ID
      folderType: 2, // 1-分类 2-产品
      currentCover: "", // 当前封面
      newCover: "", // 新选择的封面
      newCoverUrl: "", // 上传后的封面URL
      uploading: false,
    };
  },

  computed: {
    canSave() {
      return this.newCover && !this.uploading;
    },
  },

  onLoad(options) {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;

    if (options.id) {
      this.folderId = options.id;
      this.getFoldDetail();
    }
    if (options.folder_type) {
      this.folderType = options.folder_type;
    }
    if (options.cover) {
      this.currentCover = decodeURIComponent(options.cover);
    }
  },

  methods: {
    // 返回
    back() {
      uni.navigateBack();
    },
    async getFoldDetail() {
      const res = await this.$go(
        "album/products/detail",
        { fid: this.folderId },
        "post",
        {
          show_err: true,
        }
      );
      if (res.code === 0) {
        this.currentCover = res.data.new_thumb;
      }
    },

    // 选择图片
    chooseImage() {
      uni.chooseImage({
        count: 1,
        sizeType: ["compressed"],
        sourceType: ["album", "camera"],
        success: (res) => {
          const tempFilePath = res.tempFilePaths[0];
          this.newCover = tempFilePath;
          this.uploadImage(tempFilePath);
        },
        fail: (err) => {
          console.error("选择图片失败:", err);
        },
      });
    },

    // 上传图片
    uploadImage(filePath) {
      this.uploading = true;

      uni.uploadFile({
        url: this.$config.domain + "/api/common/upload",
        filePath: filePath,
        name: "file",
        header: {
          "content-type": "multipart/form-data", // 默认值
          ...buildAuthHeader(),
        },
        success: (uploadRes) => {
          try {
            const data = JSON.parse(uploadRes.data);
            if (data.code === 0) {
              this.newCoverUrl = data.url || (data.data && data.data.url) || "";
              if (!this.newCoverUrl) {
                uni.showToast({
                  title: "上传结果缺少图片地址",
                  icon: "none",
                });
                this.newCover = "";
                return;
              }
              uni.showToast({
                title: "上传成功",
                icon: "success",
              });
            } else {
              uni.showToast({
                title: data.msg || "上传失败",
                icon: "none",
              });
              this.newCover = "";
            }
          } catch (e) {
            console.error("解析上传结果失败:", e);
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
            this.newCover = "";
          }
        },
        fail: (err) => {
          console.error("上传失败:", err);
          uni.showToast({
            title: "上传失败",
            icon: "none",
          });
          this.newCover = "";
        },
        complete: () => {
          this.uploading = false;
        },
      });
    },

    // 移除新封面
    removeNewCover() {
      this.newCover = "";
      this.newCoverUrl = "";
    },

    // 预览图片
    previewImage(url) {
      uni.previewImage({
        urls: [url],
        current: url,
      });
    },

    // 保存封面
    saveCover() {
      if (!this.canSave) {
        return;
      }

      const querys = {
        fid: this.folderId,
        folder_type: this.folderType,
        new_thumb: this.newCoverUrl,
        timestamp: new Date().getTime(),
      };

      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("album/edit/folder", data, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          uni.showToast({
            title: "保存成功",
            icon: "success",
            duration: 1500,
          });

          setTimeout(() => {
            // 触发刷新事件
            uni.$emit("refreshProductDetailsSelfData");
            uni.$emit("refreshProductManageData");
            uni.$emit("refreshIndexData");
            uni.navigateBack();
          }, 1500);
        })
        .catch((err) => {
          console.error("保存封面失败:", err);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-image: linear-gradient(to bottom, #ffec5d, #ffffff 70%);
}

// ==================== 导航栏 ====================
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;

  .custom-nav-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;

    .nav-bar-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30rpx;

      .left {
        width: 80rpx;
        display: flex;
        align-items: center;

        .back-icon {
          width: 40rpx;
          height: 40rpx;
        }
      }

      .title {
        flex: 1;
        text-align: center;
        font-size: 32rpx;
        font-weight: 600;
        color: #333;
      }

      .right {
        width: 80rpx;
      }
    }
  }
}

// ==================== 内容区域 ====================
.content {
  padding: 30rpx;
  padding-bottom: 180rpx;
}

// ==================== 当前封面区域 ====================
.current-cover-section {
  margin-bottom: 40rpx;

  .section-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 20rpx;
  }

  .current-cover-box {
    width: 100%;
    height: 400rpx;
    background-color: #fff;
    border-radius: 16rpx;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;

    .current-cover-img {
      width: 100%;
      height: 100%;
    }

    .no-cover {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .empty-icon {
        width: 120rpx;
        height: 120rpx;
        margin-bottom: 20rpx;
        opacity: 0.3;
      }

      .empty-text {
        font-size: 28rpx;
        color: #999;
      }
    }
  }
}

// ==================== 上传区域 ====================
.upload-section {
  .section-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    margin-bottom: 20rpx;
  }

  .upload-tip {
    font-size: 24rpx;
    color: #999;
    margin-bottom: 20rpx;
    line-height: 1.6;
  }

  .upload-area {
    width: 100%;
  }

  .new-cover-box {
    padding: 30rpx;
    height: 400rpx;
    background-color: #fff;
    border-radius: 16rpx;
    overflow: hidden;
    position: relative;

    .new-cover-img {
      width: 100%;
      height: 100%;
    }

    .remove-btn {
      position: absolute;
      top: 20rpx;
      right: 20rpx;
      width: 60rpx;
      height: 60rpx;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;

      .remove-icon {
        width: 32rpx;
        height: 32rpx;
      }
    }

    .upload-mask {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;

      text {
        font-size: 28rpx;
        color: #fff;
      }
    }
  }

  .upload-btn {
    width: 100%;
    height: 400rpx;
    background-color: #fff;
    border-radius: 16rpx;
    border: 2rpx dashed #ddd;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    .upload-icon {
      width: 80rpx;
      height: 80rpx;
      margin-bottom: 20rpx;
      opacity: 0.5;
    }

    .upload-text {
      font-size: 28rpx;
      color: #999;
    }
  }
}

// ==================== 底部按钮 ====================
.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 30rpx;
  background-color: #fff;
  border-top: 1rpx solid #eee;
  display: flex;
  gap: 30rpx;
  z-index: 100;

  .action-btn {
    flex: 1;
    height: 88rpx;
    border-radius: 44rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32rpx;
    font-weight: 500;
  }

  .cancel-btn {
    background-color: #f5f5f5;
    color: #666;
  }

  .save-btn {
    background-color: #ffd700;
    color: #333;

    &.disabled {
      background-color: #f5f5f5;
      color: #ccc;
    }
  }
}
</style>
