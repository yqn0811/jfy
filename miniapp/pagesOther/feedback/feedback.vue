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
          <view class="title">意见反馈</view>
          <view class="right"></view>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: contentTop + 'px' }">
      <!-- 提示文字 -->
      <view class="tip-text">
        感谢您的宝贵功能建议/问题反馈，有效建议/反馈可获得积分奖励
      </view>

      <!-- 反馈类型选择 -->
      <view class="type-tabs">
        <view
          class="tab-item"
          :class="{ active: feedbackType === 'problem' }"
          @click="changeFeedbackType('problem')"
        >
          问题反馈
        </view>
        <view
          class="tab-item"
          :class="{ active: feedbackType === 'suggestion' }"
          @click="changeFeedbackType('suggestion')"
        >
          功能建议
        </view>
      </view>

      <!-- 反馈内容 -->
      <view class="form-section">
        <view class="form-label">反馈内容</view>
        <textarea
          class="feedback-textarea"
          v-model="content"
          placeholder-class="jf-textarea-placeholder"
          :placeholder="placeholderFor('feedbackContent', '请填写5个字以上的功能建议或问题描述，以便我们为您提供更好的服务。')"
          maxlength="150"
          :auto-height="false"
          @tap="focusField('feedbackContent')"
          @focus="focusField('feedbackContent')"
          @blur="blurField('feedbackContent')"
        ></textarea>
        <view class="char-count">{{ content.length }}/150</view>
      </view>

      <!-- 图片上传 -->
      <view class="upload-section">
        <view class="upload-list">
          <!-- 已上传的图片 -->
          <view
            class="upload-item"
            v-for="(img, index) in imageList"
            :key="index"
          >
            <image
              class="upload-img"
              :src="img.url"
              mode="aspectFill"
              @click="previewImage(index)"
            ></image>
            <view class="remove-btn" @click="removeImage(index)">
              <image
                class="remove-icon"
                src="/static/icon/close.png"
                mode="aspectFit"
              ></image>
            </view>
          </view>

          <!-- 上传按钮 -->
          <view
            v-if="imageList.length < maxImages"
            class="upload-btn"
            @click="chooseImage"
          >
            <image
              class="add-icon"
              src="/static/icon/24＊24@2x(6).png"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>

      <!-- 联系方式 -->
      <view class="form-section">
        <view class="form-label">联系方式（选填）</view>
        <input
          class="contact-input"
          v-model="contact"
          placeholder-class="jf-input-placeholder"
          :placeholder="placeholderFor('feedbackContact', '输入手机号/微信号，方便与您联系')"
          maxlength="50"
          @tap="focusField('feedbackContact')"
          @focus="focusField('feedbackContact')"
          @blur="blurField('feedbackContact')"
        />
      </view>

      <!-- 提交按钮 -->
      <view class="submit-btn" @click="submitFeedback">提交反馈</view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,
      contentTop: 0,

      feedbackType: "problem", // problem-问题反馈 suggestion-功能建议
      content: "",
      imageList: [], // 上传的图片列表
      maxImages: 3, // 最多上传3张图片
      contact: "",
      uploading: false,
    };
  },

  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
    this.contentTop = this.totalHeight + 18;
  },

  methods: {
    // 返回
    back() {
      uni.navigateBack();
    },

    // 切换反馈类型
    changeFeedbackType(type) {
      this.feedbackType = type;
    },

    // 选择图片
    chooseImage() {
      const remainCount = this.maxImages - this.imageList.length;
      uni.chooseImage({
        count: remainCount,
        sizeType: ["compressed"],
        sourceType: ["album", "camera"],
        success: (res) => {
          const tempFilePaths = res.tempFilePaths;
          tempFilePaths.forEach((filePath) => {
            this.uploadImage(filePath);
          });
        },
        fail: (err) => {
          console.error("选择图片失败:", err);
        },
      });
    },

    // 上传图片
    uploadImage(filePath) {
      this.uploading = true;

      // 先添加到列表显示（本地路径）
      const tempImage = {
        url: filePath,
        uploading: true,
        uploadedUrl: "",
      };
      this.imageList.push(tempImage);
      const currentIndex = this.imageList.length - 1;

      uni.uploadFile({
        url: this.$config.domain + "/api/common/upload",
        filePath: filePath,
        name: "file",
        header: {
          "content-type": "multipart/form-data", // 默认值
          "authorization-token": `Bearer ${uni.getStorageSync("token")}`,
        },
        success: (uploadRes) => {
          try {
            const data = JSON.parse(uploadRes.data);
            if (data.code === 0 && data.data && data.data.url) {
              // 更新为服务器返回的URL
              this.imageList[currentIndex].uploadedUrl = data.data.url;
              this.imageList[currentIndex].uploading = false;
            } else {
              uni.showToast({
                title: data.msg || "上传失败",
                icon: "none",
              });
              // 上传失败，移除该图片
              this.imageList.splice(currentIndex, 1);
            }
          } catch (e) {
            console.error("解析上传结果失败:", e);
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
            this.imageList.splice(currentIndex, 1);
          }
        },
        fail: (err) => {
          console.error("上传失败:", err);
          uni.showToast({
            title: "上传失败",
            icon: "none",
          });
          this.imageList.splice(currentIndex, 1);
        },
        complete: () => {
          this.uploading = false;
        },
      });
    },

    // 移除图片
    removeImage(index) {
      this.imageList.splice(index, 1);
    },

    // 预览图片
    previewImage(index) {
      const urls = this.imageList.map((img) => img.url);
      uni.previewImage({
        urls: urls,
        current: urls[index],
      });
    },

    // 提交反馈
    submitFeedback() {
      // 验证反馈内容
      if (!this.content.trim()) {
        uni.showToast({
          title: "请填写反馈内容",
          icon: "none",
        });
        return;
      }

      if (this.content.trim().length < 5) {
        uni.showToast({
          title: "反馈内容至少5个字",
          icon: "none",
        });
        return;
      }

      // 检查是否有图片正在上传
      const hasUploading = this.imageList.some((img) => img.uploading);
      if (hasUploading) {
        uni.showToast({
          title: "图片上传中，请稍候",
          icon: "none",
        });
        return;
      }

      // 获取已上传的图片URL
      const uploadedImages = this.imageList
        .filter((img) => img.uploadedUrl)
        .map((img) => img.uploadedUrl);

      const querys = {
        type: this.feedbackType === "problem" ? 1 : 2, // 1-问题反馈 2-功能建议
        content: this.content.trim(),
        images: uploadedImages.join(","),
        contact: this.contact.trim(),
        timestamp: new Date().getTime(),
      };

      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      uni.showLoading({ title: "提交中..." });

      this.$go("user/feedback", data, "post", {
        show_err: true,
      })
        .then((res) => {
          uni.hideLoading();
          uni.showToast({
            title: "提交成功",
            icon: "success",
            duration: 2000,
          });

          // 清空表单
          setTimeout(() => {
            this.content = "";
            this.imageList = [];
            this.contact = "";
            uni.navigateBack();
          }, 2000);
        })
        .catch((err) => {
          uni.hideLoading();
          console.error("提交反馈失败:", err);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff;
}

// ==================== 导航栏 ====================
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: #fff;
  z-index: 999;
  border-bottom: 1rpx solid #eee;

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
  padding-top: 54rpx;
  padding-bottom: 180rpx;
}

// ==================== 提示文字 ====================
.tip-text {
  font-size: 28rpx;
  color: #000;
  line-height: 1.6;
  margin-bottom: 30rpx;
}

// ==================== 类型标签 ====================
.type-tabs {
  display: flex;
  gap: 20rpx;
  margin-bottom: 40rpx;

  .tab-item {
    padding: 16rpx 40rpx;
    background-color: #fff;
    border-radius: 16rpx;
    font-size: 28rpx;
    color: #666;
    font-weight: 500;

    &.active {
      background-color: #ffd700;
      color: #333;
      font-weight: 600;
    }
  }
}

// ==================== 表单区域 ====================
.form-section {
  margin-bottom: 30rpx;

  .form-label {
    font-size: 28rpx;
    color: #333;
    font-weight: 500;
    margin-bottom: 16rpx;
  }

  .feedback-textarea {
    width: 100%;
    min-height: 300rpx;
    background-color: #f2f2f2;
    border-radius: 12rpx;
    padding: 24rpx;
    font-size: 28rpx;
    color: #333;
    box-sizing: border-box;
    line-height: 1.6;
  }

  .char-count {
    text-align: right;
    font-size: 24rpx;
    color: #999;
    margin-top: 12rpx;
  }

  .contact-input {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background-color: #f2f2f2;
    border-radius: 12rpx;
    padding: 0 24rpx;
    font-size: 28rpx;
    color: #333;
    box-sizing: border-box;
    overflow: hidden;
    white-space: nowrap;
  }
}

// ==================== 图片上传 ====================
.upload-section {
  margin-bottom: 30rpx;

  .upload-list {
    display: flex;
    gap: 20rpx;
    flex-wrap: wrap;
  }

  .upload-item {
    width: 200rpx;
    height: 200rpx;
    position: relative;
    border-radius: 12rpx;
    overflow: hidden;

    .upload-img {
      width: 100%;
      height: 100%;
    }

    .remove-btn {
      position: absolute;
      top: 8rpx;
      right: 8rpx;
      width: 40rpx;
      height: 40rpx;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;

      .remove-icon {
        width: 20rpx;
        height: 20rpx;
      }
    }
  }

  .upload-btn {
    width: 160rpx;
    height: 160rpx;
    background-color: #f2f2f2;
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    .add-icon {
      width: 60rpx;
      height: 60rpx;
      opacity: 0.5;
    }
  }
}

// ==================== 提交按钮 ====================
.submit-btn {
  position: fixed;
  bottom: calc(env(safe-area-inset-bottom) + 30rpx);
  left: 30rpx;
  right: 30rpx;
  height: 88rpx;
  background-color: #ffd700;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  font-weight: 600;
  color: #333;
  z-index: 100;
}
</style>
