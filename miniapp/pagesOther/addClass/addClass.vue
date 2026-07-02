<template>
  <view class="page">
    <view class="container">
      <!-- 分类名称 -->
      <view class="form-item">
        <text class="label">分类名称 <text class="required">*</text></text>
        <input
          class="input"
          placeholder-class="jf-input-placeholder"
          :placeholder="placeholderFor('categoryName', '一个好的分类名称，能更吸引人哦')"
          maxlength="10"
          :placeholder-style="'color:#cfcfcf'"
          v-model="form.folder_name"
          @tap="focusField('categoryName')"
          @focus="focusField('categoryName')"
          @blur="blurField('categoryName')"
          @input="onNameInput"
        />
        <view class="count-row">
          <text class="count">{{ nameLength }}/10</text>
        </view>
      </view>

      <!-- 分类描述 -->
      <view class="form-item">
        <text class="label">分类描述（选填）</text>
        <textarea
          class="textarea"
          placeholder-class="jf-textarea-placeholder"
          :placeholder="placeholderFor('categoryDesc', '简单介绍一下你的分类')"
          maxlength="150"
          :placeholder-style="'color:#cfcfcf'"
          v-model="form.folder_desc"
          @tap="focusField('categoryDesc')"
          @focus="focusField('categoryDesc')"
          @blur="blurField('categoryDesc')"
          @input="onIntroInput"
        ></textarea>
        <view class="count-row">
          <text class="count">{{ introLength }}/150</text>
        </view>
      </view>
    </view>

    <!-- 底部保存按钮（固定，兼容安全区） -->
    <view class="save-wrap">
      <view class="save-btn" @tap="save">
        <text class="save-text">保存</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      form: {
        folder_name: "",
        folder_desc: "",
      },
      name: "",
      intro: "",
      nameLength: 0,
      introLength: 0,
      editCategoryId: 0,
      parentCategoryId: 0,
      fromPage:''
    };
  },
  onLoad(options) {
    if (options && options.fid) {
      this.editCategoryId = Number(options.fid || 0);
      this.getFolderDetail();
    }
    if (options && options.parent_id) {
      this.parentCategoryId = Number(options.parent_id || 0);
    }
    this.fromPage = options.fromPage
    this.nameLength = this.form.folder_name.length;
    this.introLength = this.form.folder_desc.length;
  },
  methods: {
    async getFolderDetail() {
      if (this.$go) {
        const data = {
          fid: this.editCategoryId,
          folder_type: 1,
        };
        // 使用项目已有请求封装
        const res = await this.$go("album/lists/folder", data, "post", {
          show_err: true,
        });
        if (res.code === 0) {
          const data = res.data.folder_info;
          console.log(res.data);
          this.form.folder_name = data.folder_name;
          this.form.folder_desc = data.folder_desc;
          this.nameLength = this.form.folder_name.length;
          this.introLength = this.form.folder_desc.length;
        }
      }
    },
    onNameInput(e) {
      this.form.folder_name = e.detail.value || "";
      this.nameLength = this.form.folder_name.length;
    },
    onIntroInput(e) {
      this.form.folder_desc = e.detail.value || "";
      this.introLength = this.form.folder_desc.length;
    },

    // 构建带签名的请求参数（使用项目已有签名方法）
    buildApiParams(params) {
      const querys = {
        ...params,
        timestamp: new Date().getTime(),
      };
      return {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
    },

    // 保存分类
    async save() {
      // 必填校验：分类名称不能为空
      if (!this.form.folder_name || !this.form.folder_name.trim()) {
        uni.showToast({ title: "分类名称为必填项", icon: "none" });
        return;
      }
      // 本页面按截图为“选填”，因此不强制校验名称，但可以限制长度为 1-10 可选
      if (this.nameLength > 10) {
        uni.showToast({ title: "分类名称不能超过10个字符", icon: "none" });
        return;
      }
      if (this.introLength > 150) {
        uni.showToast({ title: "分类描述不能超过150个字符", icon: "none" });
        return;
      }

      uni.showLoading({ title: "保存中..." });
      try {
        const payload = {
          folder_type: 1,
          folder_name: this.form.folder_name || "",
          folder_desc: this.form.folder_desc || "",
        };
        if (this.parentCategoryId) {
          payload.fid = this.parentCategoryId;
        }
        if (this.$go) {
          const url = this.editCategoryId
            ? "album/edit/folder"
            : "album/create/folder";
          if (this.editCategoryId) {
            payload.fid = this.editCategoryId;
          }
          const params = this.buildApiParams(payload);
          // 使用项目已有请求封装
          await this.$go(url, params, "post", {
            show_err: true,
          });
          uni.showToast({ title: "保存成功", icon: "none" });
          setTimeout(() => {
            if (this.fromPage === "index") {
              uni.$emit("refreshIndexData");
            }
            if (this.editCategoryId) {
              uni.$emit("refreshClassManageData");
              uni.navigateBack();
              return;
            }
            uni.$emit("refreshClassManageData");
            uni.navigateBack();
          }, 800);
        } else {
          // 回退：打印参数，方便联调替换为真实请求
          console.warn("提交数据：", params);
          uni.showToast({
            title: "已构建请求，请替换为真实接口",
            icon: "none",
          });
        }
      } catch (err) {
        console.error(err);
        uni.showToast({ title: "保存失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },
  },
};
</script>

<style scoped lang="scss">
.page {
  background: #ffffff;
  min-height: 100vh;
}

.required {
  color: #ff3b30;
  font-size: 28rpx;
  margin-left: 6rpx;
}

.container {
  padding: 24rpx;
  padding-bottom: 140rpx;
  /* 留出底部保存按钮空间（含安全区） */
}

/* 表单项 */
.form-item {
  margin-bottom: 28rpx;
}

.label {
  font-size: 28rpx;
  color: #333333;
  margin-bottom: 12rpx;
  display: flex;
  align-items: center;
}

.optional {
  font-size: 22rpx;
  color: #999999;
}

/* 输入框样式（灰色圆角） */
.input {
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: #f2f2f2;
  border-radius: 16rpx;
  padding: 0 24rpx;
  font-size: 28rpx;
  box-sizing: border-box;
  border: none;
  color: #333;
  overflow: hidden;
  white-space: nowrap;
}

/* 文本域样式 */
.textarea {
  width: 100%;
  min-height: 200rpx;
  background: #f2f2f2;
  border-radius: 16rpx;
  padding: 24rpx;
  font-size: 28rpx;
  box-sizing: border-box;
  border: none;
  color: #333;
  resize: none;
}

/* 右上角字数统计 */
.count-row {
  width: 100%;
  display: flex;
  justify-content: flex-end;
  padding: 0 4rpx;
  box-sizing: border-box;
  margin-top: 8rpx;
}

.count {
  display: inline-block;
  min-width: 72rpx;
  text-align: right;
  font-size: 22rpx;
  color: #999;
  line-height: 30rpx;
  white-space: nowrap;
}

/* 底部保存按钮，固定并兼容安全区 */
.save-wrap {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 20rpx;
  display: flex;
  justify-content: center;
  padding-bottom: 0;
  /* 安全区兼容：先声明常规值，再使用 env */
  bottom: calc(constant(safe-area-inset-bottom) + 20rpx);
  bottom: calc(env(safe-area-inset-bottom) + 20rpx);
  z-index: 50;
}

.save-btn {
  width: 686rpx;
  height: 96rpx;
  background: #ffd800;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.08);
}

.save-text {
  font-size: 32rpx;
  color: #222;
  font-weight: 600;
}
</style>
