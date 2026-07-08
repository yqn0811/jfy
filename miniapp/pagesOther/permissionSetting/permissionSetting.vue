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
          <view class="title">权限设置</view>
          <view class="right"></view>
        </view>
      </view>
    </view>

    <!-- 内容区域 -->
    <view class="content" :style="{ paddingTop: totalHeight + 'px' }">
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/home@2x(2).png"
            mode="aspectFit"
          ></image>
          <text class="section-title">主页访问权限</text>
        </view>

        <view class="setting-item">
          <view class="item-main">
            <view class="item-title">公开主页</view>
            <view class="item-desc">
              关闭后，访客无法访问你的主页、分类和产品分享内容。
            </view>
          </view>
          <view class="item-action">
            <u-switch
              v-model="settings.showHome"
              @change="handleSwitchChange('showHome')"
              active-color="#ffd700"
              size="24"
            ></u-switch>
          </view>
        </view>
      </view>

      <!-- 访客浏览权限 -->
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/user.png"
            mode="aspectFit"
          ></image>
          <text class="section-title">访客浏览权限</text>
        </view>

        <!-- 访问无需填写昵称 -->
        <view class="setting-item">
          <view class="item-main">
            <view class="item-title">访问无需填写昵称</view>
            <view class="item-desc">
              一旦开启，访客无需输入昵称/姓名即可访问你的作品，这也意味着你将无法获取访客的能够信息。
            </view>
          </view>
          <view class="item-action">
            <u-switch
              v-model="settings.noNicknameRequired"
              @change="handleSwitchChange('noNicknameRequired')"
              active-color="#ffd700"
              size="24"
            ></u-switch>
          </view>
        </view>

        <!-- 访问无需授权手机号 -->
        <view class="setting-item">
          <view class="item-main">
            <view class="item-title">访问无需授权手机号</view>
            <view class="item-desc">
              一旦开启，访客无需授权手机号即可查看你的资料等敏感操作，这也意味着你将无法获取访客的手机号。
            </view>
          </view>
          <view class="item-action">
            <u-switch
              v-model="settings.noPhoneRequired"
              @change="handleSwitchChange('noPhoneRequired')"
              active-color="#ffd700"
              size="24"
            ></u-switch>
          </view>
        </view>
      </view>

      <!-- 访客保存权限 -->
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/download.png"
            mode="aspectFit"
          ></image>
          <text class="section-title">访客保存权限</text>
        </view>

        <!-- 访客可以保存图片到相册 -->
        <view class="setting-item">
          <view class="item-main">
            <view class="item-title">访客可以保存图片到相册</view>
            <view class="item-desc"
              >一旦开启，将允许访客保存作品图片到相册</view
            >
          </view>
          <view class="item-action">
            <u-switch
              v-model="settings.allowSaveImage"
              @change="handleSwitchChange('allowSaveImage')"
              active-color="#ffd700"
              size="24"
            ></u-switch>
          </view>
        </view>
      </view>

      <!-- 水印设置 -->
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/Frame@2x(18).png"
            mode="aspectFit"
          ></image>
          <text class="section-title">水印设置</text>
        </view>
        <view class="section-desc">自定义图片、文字水印，防止盗用</view>
        <!-- 自定义水印 -->
        <view class="setting-item editable compact">
          <view class="item-label">文字水印</view>
          <view class="item-editor">
            <input
              class="setting-input"
              type="text"
              v-model="settings.home_watermark_text"
              placeholder-class="jf-input-placeholder"
              :placeholder="placeholderFor('homeWatermarkText', '我们的云相册')"
              placeholder-style="color:#bbb"
              confirm-type="done"
              @tap="focusField('homeWatermarkText')"
              @focus="focusField('homeWatermarkText')"
              @blur="blurField('homeWatermarkText')"
              @confirm="saveSettings('home_watermark_text')"
            />
          </view>
          <view class="item-action" @click.stop="saveSettings('home_watermark_text')">
            <text class="action-text">保存</text>
          </view>
        </view>
      </view>

      <!-- 服务功能名称设置 -->
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/设置@2x.png"
            mode="aspectFit"
          ></image>
          <text class="section-title">服务功能名称设置</text>
        </view>
        <view class="section-desc">
          服务功能默认名称为"服务"，你可根据所在行业自定义设置其他名称
        </view>

        <!-- 服务 -->
        <view class="setting-item editable compact">
          <view class="item-label">服务名称</view>
          <view class="item-editor">
            <input
              class="setting-input"
              type="text"
              placeholder-class="jf-input-placeholder"
              :placeholder="placeholderFor('homeServiceName', '服务')"
              placeholder-style="color:#bbb"
              confirm-type="done"
              @tap="focusField('homeServiceName')"
              @focus="focusField('homeServiceName')"
              @blur="blurField('homeServiceName')"
              @confirm="saveSettings('home_service_name')"
              v-model="settings.home_service_name"
            />
          </view>
          <view class="item-action" @click.stop="saveSettings('home_service_name')">
            <text class="action-text">保存</text>
          </view>
        </view>
      </view>

      <!-- 分享设置 -->
      <view class="section">
        <view class="section-header">
          <image
            class="section-icon"
            src="/static/icon/分享@2x.png"
            mode="aspectFit"
          ></image>
          <text class="section-title">分享设置</text>
        </view>
        <view class="section-desc">
          自定义主页"小程序分享卡片"的图片和文案
        </view>

        <!-- 自定义主页分享 -->
        <view class="setting-item clickable" @tap="goToShareSetting">
          <view class="item-main">
            <view class="item-title">{{ shareForm.title || "自定义主页分享" }}</view>
          </view>
          <view class="item-action">
            <text class="action-text">设置</text>
            <image
              class="arrow-icon"
              src="/static/icon/Chevron Right@2x(1).png"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>
    </view>

    <!-- 分享设置弹窗 -->
    <u-popup
      :show="sharePopupVisible"
      mode="center"
      closeable
      :round="20"
      @close="closeSharePopup"
    >
      <view class="share-popup">
        <view class="popup-title">自定义主页分享</view>

        <!-- 分享标题 -->
        <view class="popup-form-item">
          <view class="form-label">分享标题</view>
          <input
            class="form-input"
            v-model="shareForm.title"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('shareTitle', '请输入分享标题')"
            maxlength="30"
            @tap="focusField('shareTitle')"
            @focus="focusField('shareTitle')"
            @blur="blurField('shareTitle')"
          />
          <view class="char-count">{{ shareForm.desc.length }}/30</view>
        </view>
        <view class="popup-form-item">
          <view class="form-label">分享副标题</view>
          <input
            class="form-input"
            v-model="shareForm.desc"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('shareDesc', '请输入分享副标题')"
            maxlength="30"
            @tap="focusField('shareDesc')"
            @focus="focusField('shareDesc')"
            @blur="blurField('shareDesc')"
          />
          <view class="char-count">{{ shareForm.title.length }}/30</view>
        </view>

        <!-- 分享图片 -->
        <view class="popup-form-item">
          <view class="form-label">分享图片</view>
          <view class="form-tip">建议尺寸：500x400</view>

          <view class="share-image-box">
            <!-- 已上传的图片 -->
            <view v-if="shareForm.imageUrl" class="share-image-preview">
              <image
                class="preview-img"
                :src="shareForm.imageUrl"
                mode="aspectFill"
                @click="previewShareImage"
              ></image>
              <view class="remove-btn" @click="removeShareImage">
                <image
                  class="remove-icon"
                  src="/static/icon/close.png"
                  mode="aspectFit"
                ></image>
              </view>
              <view v-if="uploadingShareImage" class="upload-mask">
                <text>上传中...</text>
              </view>
            </view>

            <!-- 上传按钮 -->
            <view v-else class="share-upload-btn" @click="chooseShareImage">
              <image
                class="upload-icon"
                src="/static/icon/add.png"
                mode="aspectFit"
              ></image>
              <text class="upload-text">上传图片</text>
            </view>
          </view>
        </view>

        <!-- 底部按钮 -->
        <view class="popup-actions">
          <view class="popup-btn cancel-btn" @click="closeSharePopup">
            取消
          </view>
          <view
            class="popup-btn confirm-btn"
            :class="{ disabled: !canSaveShare }"
            @click="saveShareSettings"
          >
            确定
          </view>
        </view>
      </view>
    </u-popup>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      navigationBarHeight: 44,
      totalHeight: 0,

      // 权限设置
      settings: {
        showHome: true, // 是否公开主页
        noNicknameRequired: false, // 访问无需填写昵称
        noPhoneRequired: false, // 访问无需授权手机号
        allowSaveImage: false, // 访客可以保存图片到相册
        home_service_name: "",
        home_watermark_text: "",
      },

      // 原始设置（用于对比是否有变化）
      originalSettings: {},

      // 分享设置弹窗
      sharePopupVisible: false,
      shareForm: {
        title: "",
        image: "",
        desc: "",
        imageUrl: "", // 上传后的URL
      },
      uploadingShareImage: false,
    };
  },

  computed: {
    canSaveShare() {
      return (
        this.shareForm.title.trim() &&
        !this.uploadingShareImage
      );
    },
  },

  onLoad() {
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;

    this.loadSettings();
  },

  methods: {
    // 返回
    back() {
      uni.navigateBack();
    },

    // 加载权限设置
    loadSettings() {
      const user = uni.getStorageSync("userInfo");
      const querys = {
        target_user_id: user.id,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("user/home/info", data, "get", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          if (res && res.data) {
            this.settings = {
              showHome:
                res.data.is_show_home === undefined ||
                res.data.is_show_home === null
                  ? true
                  : Number(res.data.is_show_home) === 1,
              noNicknameRequired: !!res.data.visit_no_need_nickname,
              noPhoneRequired: !!res.data.visit_no_need_mobile,
              allowSaveImage: !!res.data.visit_allow_save_pic,
              home_service_name: res.data.home_service_name || "",
              home_watermark_text: res.data.home_watermark_text || "",
            };
            this.shareForm = {
              title: res.data.home_share_title || "",
              imageUrl: res.data.home_share_image || "",
              desc: res.data.home_share_desc || "",
              image: res.data.home_share_image || "",
            };
            // 保存原始设置
            this.originalSettings = { ...this.settings };
          }
        })
        .catch((err) => {
          console.error("加载权限设置失败:", err);
        });
    },

    // 开关切换
    handleSwitchChange(key) {
      // 延迟保存，给用户切换动画的时间
      setTimeout(() => {
        this.saveSettings(key);
      }, 300);
    },

    // 保存权限设置
    saveSettings(changedKey,isclose) {
      const querys = {
        openid: uni.getStorageSync("openid"),
        is_show_home: this.settings.showHome ? 1 : 0,
        visit_no_need_nickname: this.settings.noNicknameRequired ? 1 : 0,
        visit_no_need_mobile: this.settings.noPhoneRequired ? 1 : 0,
        visit_allow_save_pic: this.settings.allowSaveImage ? 1 : 0,
        home_watermark_text: this.settings.home_watermark_text,
        home_service_name: this.settings.home_service_name,
        home_share_title: this.shareForm.title,
        home_share_image: this.shareForm.imageUrl,
        home_share_desc: this.shareForm.desc,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("user/update_info", data, "post", {
        show_err: true,
        loading: false,
      })
        .then((res) => {
          uni.showToast({
            title: "设置成功",
            icon: "success",
            duration: 1500,
          });
          // 更新原始设置
          this.originalSettings = { ...this.settings };
          this.sharePopupVisible = false
        })
        .catch((err) => {
          console.error("保存权限设置失败:", err);
          if (changedKey) {
            // 保存失败，恢复原始值
            this.settings[changedKey] = this.originalSettings[changedKey];
          }

        });
    },

    // 跳转到水印设置
    goToWatermarkSetting() {
      uni.navigateTo({
        url: "/pagesOther/watermarkSetting/watermarkSetting",
      });
    },

    // 跳转到服务名称设置
    goToServiceNameSetting() {
      uni.navigateTo({
        url: "/pagesOther/serviceNameSetting/serviceNameSetting",
      });
    },

    // 跳转到分享设置
    goToShareSetting() {
      this.sharePopupVisible = true;
    },

    // 关闭分享设置弹窗
    closeSharePopup() {
      this.sharePopupVisible = false;
    },

    // 选择分享图片
    chooseShareImage() {
      uni.chooseImage({
        count: 1,
        sizeType: ["compressed"],
        sourceType: ["album", "camera"],
        success: (res) => {
          const tempFilePath = res.tempFilePaths[0];
          this.shareForm.image = tempFilePath;
          this.uploadShareImage(tempFilePath);
        },
        fail: (err) => {
          console.error("选择图片失败:", err);
        },
      });
    },

    // 上传分享图片
    uploadShareImage(filePath) {
      this.uploadingShareImage = true;

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
            console.log(data);
            if (data.code === 0) {
              this.shareForm.imageUrl = data.data.url;
              uni.showToast({
                title: "上传成功",
                icon: "success",
              });
            } else {
              uni.showToast({
                title: data.msg || "上传失败",
                icon: "none",
              });
              this.shareForm.image = "";
            }
          } catch (e) {
            console.error("解析上传结果失败:", e);
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
            this.shareForm.image = "";
          }
        },
        fail: (err) => {
          console.error("上传失败:", err);
          uni.showToast({
            title: "上传失败",
            icon: "none",
          });
          this.shareForm.image = "";
        },
        complete: () => {
          this.uploadingShareImage = false;
        },
      });
    },

    // 移除分享图片
    removeShareImage() {
      this.shareForm.image = "";
      this.shareForm.imageUrl = "";
    },

    // 预览分享图片
    previewShareImage() {
      uni.previewImage({
        urls: [this.shareForm.image],
        current: this.shareForm.image,
      });
    },

    // 保存分享设置
    saveShareSettings() {
      if (!this.canSaveShare) {
        return;
      }
      this.saveSettings();
      //  this.sharePopupVisible = false;
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
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
  padding-bottom: 60rpx;
}

// ==================== 设置区块 ====================
.section {
  margin-bottom: 20rpx;
  background-color: #fff;
  padding: 30rpx;

  .section-header {
    display: flex;
    align-items: center;
    margin-bottom: 20rpx;

    .section-icon {
      width: 40rpx;
      height: 40rpx;
      margin-right: 12rpx;
    }

    .section-title {
      font-size: 32rpx;
      font-weight: 600;
      color: #333;
    }
  }

  .section-desc {
    font-size: 24rpx;
    color: #999;
    line-height: 1.6;
    margin-bottom: 20rpx;
  }
}

// ==================== 设置项 ====================
.setting-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 30rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }

  &.clickable {
    align-items: center;
  }

  &.editable {
    align-items: center;
  }

  &.compact {
    flex-wrap: nowrap;
    gap: 16rpx;
  }

  .item-label {
    width: 126rpx;
    flex: 0 0 126rpx;
    font-size: 28rpx;
    color: #333;
    font-weight: 500;
    line-height: 72rpx;
    white-space: nowrap;
  }

  .item-editor {
    flex: 1;
    min-width: 0;

    .setting-input {
      width: 100%;
      height: 72rpx;
      line-height: 72rpx;
      box-sizing: border-box;
      padding: 0 24rpx;
      border-radius: 16rpx;
      background: #f7f7f7;
      color: #333;
      font-size: 28rpx;
    }
  }

  .item-main {
    flex: 1;
    margin-right: 30rpx;
    min-width: 0;

    .item-title {
      font-size: 28rpx;
      color: #333;
      font-weight: 500;
      margin-bottom: 12rpx;
    }

    .item-desc {
      font-size: 24rpx;
      color: #999;
      line-height: 1.6;
    }
  }

  .item-action {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-shrink: 0;
    min-width: 56rpx;

    .action-text {
      font-size: 26rpx;
      color: #999;
      margin-right: 0;
      white-space: nowrap;
    }

    .arrow-icon {
      width: 24rpx;
      height: 24rpx;
      margin-left: 12rpx;
    }
  }
}

// ==================== 分享设置弹窗 ====================
.share-popup {
  min-width: 600rpx;
  padding: 40rpx 30rpx 30rpx;

  .popup-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333;
    text-align: center;
    margin-bottom: 40rpx;
  }

  .popup-form-item {
    margin-bottom: 30rpx;

    .form-label {
      font-size: 28rpx;
      color: #333;
      font-weight: 500;
      margin-bottom: 16rpx;
    }

    .form-tip {
      font-size: 24rpx;
      color: #999;
      margin-bottom: 16rpx;
    }

    .form-input {
      width: 100%;
      height: 80rpx;
      background-color: #f5f5f5;
      border-radius: 12rpx;
      padding: 0 24rpx;
      font-size: 28rpx;
      color: #333;
      box-sizing: border-box;
    }

    .char-count {
      font-size: 24rpx;
      color: #999;
      text-align: right;
      margin-top: 8rpx;
    }

    .share-image-box {
      width: 100%;
    }

    .share-image-preview {
      width: 100%;
      height: 300rpx;
      background-color: #f5f5f5;
      border-radius: 12rpx;
      overflow: hidden;
      position: relative;

      .preview-img {
        width: 100%;
        height: 100%;
      }

      .remove-btn {
        position: absolute;
        top: 16rpx;
        right: 16rpx;
        width: 48rpx;
        height: 48rpx;
        background-color: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;

        .remove-icon {
          width: 24rpx;
          height: 24rpx;
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

    .share-upload-btn {
      width: 100%;
      height: 300rpx;
      background-color: #f5f5f5;
      border-radius: 12rpx;
      border: 2rpx dashed #ddd;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .upload-icon {
        width: 60rpx;
        height: 60rpx;
        margin-bottom: 16rpx;
        opacity: 0.5;
      }

      .upload-text {
        font-size: 26rpx;
        color: #999;
      }
    }
  }

  .popup-actions {
    display: flex;
    gap: 20rpx;
    margin-top: 40rpx;

    .popup-btn {
      flex: 1;
      height: 80rpx;
      border-radius: 40rpx;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28rpx;
      font-weight: 500;
    }

    .cancel-btn {
      background-color: #f5f5f5;
      color: #666;
    }

    .confirm-btn {
      background-color: #ffd700;
      color: #333;

      &.disabled {
        background-color: #f5f5f5;
        color: #ccc;
      }
    }
  }
}
</style>
