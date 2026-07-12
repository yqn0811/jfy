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
          :value="form.folder_name"
          @tap="focusField('categoryName')"
          @focus="focusField('categoryName')"
          @blur="handleNameBlur"
          @confirm="onNameInput"
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
          :value="form.folder_desc"
          @tap="focusField('categoryDesc')"
          @focus="focusField('categoryDesc')"
          @blur="handleIntroBlur"
          @input="onIntroInput"
        ></textarea>
        <view class="count-row">
          <text class="count">{{ introLength }}/150</text>
        </view>
      </view>

      <view class="form-item">
        <text class="label">可见范围</text>
        <view class="option-list">
          <view
            v-for="item in visibilityOptions"
            :key="item.value"
            class="option-item"
            :class="{ active: Number(form.private_type) === item.value }"
            @tap="selectVisibility(item.value)"
          >
            <view class="option-main">
              <text class="option-title">{{ item.label }}</text>
              <text class="option-desc">{{ item.desc }}</text>
            </view>
          </view>
        </view>
      </view>

      <view class="form-item">
        <text class="label">展示方式</text>
        <view class="segmented">
          <view
            class="segmented-item"
            :class="{ active: Number(form.layout_type) === 1 }"
            @tap="selectLayout(1)"
          >
            双列
          </view>
          <view
            class="segmented-item"
            :class="{ active: Number(form.layout_type) === 2 }"
            @tap="selectLayout(2)"
          >
            单列
          </view>
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
import { notifyRefresh } from "@/common/helper/refresh.js";

export default {
  data() {
    return {
      form: {
        folder_name: "",
        folder_desc: "",
        private_type: 1,
        layout_type: 1,
      },
      visibilityOptions: [
        {
          label: "公开",
          value: 1,
          desc: "会在主页和分类列表中展示",
        },
        {
          label: "私密",
          value: 2,
          desc: "仅自己可见，访客无法访问",
        },
        {
          label: "仅分享可见",
          value: 4,
          desc: "主页不展示，通过分享链接访问",
        },
      ],
      name: "",
      intro: "",
      nameLength: 0,
      introLength: 0,
      editCategoryId: 0,
      parentCategoryId: 0,
      fromPage:'',
    };
  },
  onLoad(options = {}) {
    if (options && options.fid) {
      this.editCategoryId = Number(options.fid || 0);
      this.getFolderDetail();
    }
    if (options && options.parent_id) {
      this.parentCategoryId = Number(options.parent_id || 0);
    }
    this.fromPage = options.fromPage || "";
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
          this.setCategoryName(data.folder_name);
          this.setCategoryDesc(data.folder_desc);
          this.form.private_type = Number(data.private_type || 1);
          this.form.layout_type = Number(data.layout_type || 1) === 2 ? 2 : 1;
        }
      }
    },
    getInputValue(e) {
      if (!e || !e.detail || e.detail.value === undefined || e.detail.value === null) {
        return "";
      }
      return String(e.detail.value);
    },
    setCategoryName(value) {
      const text = value === undefined || value === null ? "" : String(value);
      this.form.folder_name = text;
      this.nameLength = text.length;
      return text;
    },
    setCategoryDesc(value) {
      const text = value === undefined || value === null ? "" : String(value);
      this.form.folder_desc = text;
      this.introLength = text.length;
      return text;
    },
    onNameInput(e) {
      return this.setCategoryName(this.getInputValue(e));
    },
    handleNameBlur(e) {
      this.onNameInput(e);
      this.blurField("categoryName");
    },
    onIntroInput(e) {
      return this.setCategoryDesc(this.getInputValue(e));
    },
    handleIntroBlur(e) {
      this.onIntroInput(e);
      this.blurField("categoryDesc");
    },
    selectVisibility(value) {
      this.form.private_type = value;
    },
    selectLayout(value) {
      this.form.layout_type = value;
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
      const folderName = this.setCategoryName(this.form.folder_name).trim();
      const folderDesc = this.setCategoryDesc(this.form.folder_desc);
      // 必填校验：分类名称不能为空
      if (!folderName) {
        uni.showToast({ title: "分类名称为必填项", icon: "none" });
        return;
      }
      // 本页面按截图为“选填”，因此不强制校验名称，但可以限制长度为 1-10 可选
      if (folderName.length > 10) {
        uni.showToast({ title: "分类名称不能超过10个字符", icon: "none" });
        return;
      }
      if (folderDesc.length > 150) {
        uni.showToast({ title: "分类描述不能超过150个字符", icon: "none" });
        return;
      }

      uni.showLoading({ title: "保存中..." });
      try {
        const payload = {
          folder_type: 1,
          folder_name: folderName,
          folder_desc: folderDesc,
          private_type: this.form.private_type,
          layout_type: this.form.layout_type,
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
          const res = await this.$go(url, params, "post", {
            show_err: true,
          });
          uni.showToast({ title: "保存成功", icon: "none" });
          this.notifyCategoryChanged({
            includeCategory: !!this.editCategoryId || !!this.parentCategoryId,
          });
          setTimeout(() => {
            if (this.editCategoryId) {
              uni.navigateBack();
              return;
            }
            if (this.parentCategoryId) {
              uni.navigateBack();
              return;
            }
            this.goToCategoryPreview(res && res.data);
          }, 800);
        } else {
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
    notifyCategoryChanged({ includeCategory = true } = {}) {
      notifyRefresh(includeCategory ? ["category", "home"] : ["home"]);
    },
    resolveCreatedCategoryId(data) {
      const source = this.resolveCreatedCategoryData(data);
      return source && (source.id || source.fid || source.folder_id || source.category_id);
    },
    resolveCreatedCategoryName(data) {
      const source = this.resolveCreatedCategoryData(data);
      return source && (source.folder_name || source.name || this.form.folder_name);
    },
    resolveCreatedCategoryData(data) {
      if (!data) return null;
      const source = data.data || data.folder_info || data.category || data.info || data;
      return (
        source.folder_info ||
        source.category ||
        source.info ||
        source.data ||
        source
      );
    },
    goToCategoryPreview(data) {
      const categoryId = this.resolveCreatedCategoryId(data);
      if (!categoryId) {
        uni.navigateBack();
        return;
      }
      const query = [
        `id=${encodeURIComponent(categoryId)}`,
        "created_preview=1",
      ];
      const categoryName = this.resolveCreatedCategoryName(data);
      if (categoryName) {
        query.push(`name=${encodeURIComponent(categoryName)}`);
      }
      if (this.form.folder_desc) {
        query.push(`desc=${encodeURIComponent(this.form.folder_desc)}`);
      }
      if (this.form.private_type) {
        query.push(`private_type=${encodeURIComponent(this.form.private_type)}`);
      }
      if (this.form.layout_type) {
        query.push(`layout_type=${encodeURIComponent(this.form.layout_type)}`);
      }
      uni.redirectTo({
        url: `/pagesOther/categoryPreview/categoryPreview?${query.join("&")}`,
      });
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

.option-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.option-item {
  padding: 22rpx 24rpx;
  border-radius: 16rpx;
  background: #f2f2f2;
  border: 2rpx solid transparent;
  box-sizing: border-box;
}

.option-item.active {
  background: #fff9d8;
  border-color: #ffd800;
}

.option-main {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.option-title {
  font-size: 28rpx;
  color: #333;
  font-weight: 600;
}

.option-desc {
  font-size: 24rpx;
  color: #888;
  line-height: 1.45;
}

.segmented {
  display: flex;
  height: 80rpx;
  padding: 6rpx;
  background: #f2f2f2;
  border-radius: 16rpx;
  box-sizing: border-box;
}

.segmented-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12rpx;
  font-size: 28rpx;
  color: #666;
}

.segmented-item.active {
  background: #ffd800;
  color: #333;
  font-weight: 600;
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
