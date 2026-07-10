<template>
  <view class="container">
    <view class="container-scoll">
      <!-- 表单内容 -->
      <view class="form-box">
        <!-- 产品分类 -->
        <view class="form-item">
          <text class="label">产品分类</text>
          <view class="select-box" @click="showCategoryPicker">
            <text
              class="select-text"
              :class="{ placeholder: selectedCategoryNames.length === 0 }"
            >
              {{
                selectedCategoryNames.length > 0
                  ? selectedCategoryNames.join("、")
                  : "请选择产品分类"
              }}
            </text>
            <image
              class="arrow-icon"
              src="/static/icon/Chevron Right@2x(1).png"
              mode="scaleToFill"
            />
          </view>
        </view>

        <!-- 产品名称 -->
        <view class="form-item">
          <text class="label"
            >产品名称 <text class="optional">（选填）</text></text
          >
          <input
            class="input"
            v-model="productName"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('productName', '请输入产品名称')"
            maxlength="16"
            @tap="focusField('productName')"
            @focus="focusField('productName')"
            @blur="blurField('productName')"
          />
          <text class="char-count">{{ productName.length }}/16</text>
        </view>

        <!-- 产品简介 -->
        <view class="form-item">
          <text class="label"
            >产品简介 <text class="optional">（选填）</text></text
          >
          <textarea
            class="textarea"
            v-model="productIntro"
            placeholder-class="jf-textarea-placeholder"
            :placeholder="placeholderFor('productIntro', '介绍一下你的产品')"
            maxlength="150"
            @tap="focusField('productIntro')"
            @focus="focusField('productIntro')"
            @blur="blurField('productIntro')"
          ></textarea>
          <text class="char-count">{{ productIntro.length }}/150</text>
        </view>

        <!-- 上传花色图（封面） - 改为与详情图相同的样式 -->
        <view class="form-item">
          <text class="label">上传花色图</text>
          <view class="detail-uploads">
            <view
              class="upload-cell"
              v-for="(img, idx) in coverImages"
              :key="idx"
            >
              <image
                :src="img.src"
                mode="aspectFill"
                class="upload-preview"
                @click="previewImage(img.src)"
              >
              </image>
              <view class="remove-x" @click.stop="removeCover(idx)">×</view>
              <view v-if="img.uploading" class="upload-mask small">上传中</view>
            </view>

            <view
              class="upload-cell"
              v-if="canAddCoverImage"
              @click="openUploadPicker('cover')"
            >
              <view class="upload-plus">+</view>
            </view>
          </view>
        </view>

        <!-- 上传详情图（多张） -->
        <view class="form-item">
          <view class="label-row">
            <text class="label">上传详情图</text>
            <text class="hide-detail-toggle" @tap="hideDetailPictures = !hideDetailPictures">
              {{ hideDetailPictures ? "展示详情页模块" : "关闭详情页模块" }}
            </text>
          </view>
          <view class="detail-uploads">
            <view
              class="upload-cell"
              v-for="(img, idx) in detailImages"
              :key="idx"
            >
              <image
                :src="img.src"
                mode="aspectFill"
                class="upload-preview"
                @click="previewImage(img.src)"
              >
              </image>
              <view class="remove-x" @click.stop="removeDetail(idx)">×</view>
              <view v-if="img.uploading" class="upload-mask small">上传中</view>
            </view>

            <view
              class="upload-cell"
              v-if="canAddDetailImage"
              @click="openUploadPicker('detail')"
            >
              <view class="upload-plus">+</view>
            </view>
          </view>
        </view>

        <!-- 大批量上传入口 -->
        <view v-if="pid" class="notice-box">
          <view class="notice-title">大批量上传</view>
          <view class="notice-desc">
            图片很多时，进入产品专属的网页上传页面，可复制链接发给同事或在电脑浏览器打开。
          </view>
          <view class="url-row" @click="openBatchUpload">
            <image class="notice-icon" src="/static/icon/upload-cloud.png" mode="aspectFit" />
            <view class="url-copy">
              <text class="url-text">网页上传链接和密码设置</text>
              <text class="url-subtext">{{ pid ? "已保存产品，可直接进入" : "提交产品信息后可使用" }}</text>
            </view>
          </view>
          <view class="copy-btn" @click="openBatchUpload">
            {{ pid ? "进入大批量上传" : "先提交产品信息后使用" }}
          </view>
        </view>
      </view>

      <!-- 底部上传按钮（保留但现在图片已在选择时上传） -->
      <view class="submit-wrap">
        <view class="submit-btn" @click="submit">提交产品信息</view>
      </view>
    </view>

    <UploadPicker
      :visible="uploadPickerVisible"
      @update:visible="(val) => (uploadPickerVisible = val)"
      @select="onUploadPick"
    />

    <!-- 分类选择器 - 多选 -->
    <CategoryMultiSelect
      :show="categoryPickerShow"
      :categories="categoryList"
      :defaultValue="selectedCategoryIds"
      @confirm="handleCategoryConfirm"
      @close="categoryPickerShow = false"
    />
  </view>
</template>

<script>
import UploadPicker from "./components/UploadPicker.vue"; // 根据项目路径调整
import CategoryMultiSelect from "./components/CategoryMultiSelect.vue";
import { notifyRefresh } from "@/common/helper/refresh.js";
import {
  buildUploadNameFormData,
  getSelectedUploadFileName,
  normalizeSelectedUploadFile,
  prepareNamedUploadFile,
} from "@/common/helper/uploadName.js";
export default {
  components: {
    UploadPicker,
    CategoryMultiSelect,
  },
  data() {
    return {
      productName: "",
      productIntro: "",
      // coverImage / detailImages 结构改为对象，选择后立即上传：
      // { src: 本地临时地址或已上传的线上地址, uploadedUrl: 后端返回的真实访问地址, uploading: boolean }
      uploadPickerVisible: false,
      uploadTarget: "", // 'cover' 或 'detail'
      detailImages: [], // 数组元素为对象同上
      detailImageIds: [],
      maxDetailImages: 100,
      coverImages: [], // 花色图数组，元素格式 { src, uploadedUrl, uploading }
      coverImageIds: [],
      maxCoverImages: 100, // 根据需要调整上限
      hideDetailPictures: false,
      copiedUploadUrl: false,
      // 上传配置（需要按实际后端替换）
      uploadEndpoint: "/api/common/upload",
      pid: "",
      fromPage: "",

      // 分类相关
      categoryList: [], // 分类列表
      selectedCategoryIds: [], // 选中的分类ID数组
      selectedCategoryNames: [], // 选中的分类名称数组
      categoryPickerShow: false, // 分类选择器显示状态
    };
  },
    async onLoad(options) {
    this.pid = options.id;
    this.fromPage = options.fromPage;

    // 加载分类列表
    await this.getCategoryList();

    if (options.id) {
      // 查询产品详情
     await this.getProductDetail();
    } else if (options.images) {
      this.importInitialImages(options.images);
    }
  },
  onShow() {
    const picked = uni.getStorageSync("pickedAiResources");
    if (!picked) return;
    uni.removeStorageSync("pickedAiResources");
    try {
      const payload = typeof picked === "string" ? JSON.parse(picked) : picked;
      if (!payload || payload.page !== "addProduct") return;
      this.appendAiResourceImages(payload.target || this.uploadTarget || "cover", payload.items || []);
    } catch (e) {
      console.error("读取我的资源库选择失败", e);
    }
  },
  computed: {
    canAddCoverImage() {
      return this.coverImages.length < this.maxCoverImages;
    },
    canAddDetailImage() {
      return this.detailImages.length < this.maxDetailImages;
    },
  },
  methods: {
    openUploadPicker(target) {
      this.uploadTarget = target; // 'cover' 或 'detail'
      this.uploadPickerVisible = true;
    },
    importInitialImages(imagesParam) {
      let paths = [];
      try {
        paths = JSON.parse(decodeURIComponent(imagesParam));
      } catch (e) {
        console.error("解析初始图片失败", e);
      }
      if (!Array.isArray(paths) || paths.length === 0) {
        return;
      }
      paths.forEach((tempPath) => {
        const idx =
          this.coverImages.push({
            src: tempPath,
            uploadedUrl: "",
            uploading: true,
          }) - 1;
        this.uploadSingleFile(tempPath, 1, "", 0)
          .then((res) => {
            if (this.coverImages[idx]) {
              this.$set(this.coverImages, idx, {
                src: res.url,
                uploadedUrl: res.url,
                uploading: false,
              });
            }
            this.coverImageIds.push(res.id);
          })
          .catch((err) => {
            console.error("初始图片上传失败", err);
            if (this.coverImages[idx]) {
              this.$set(this.coverImages, idx, {
                src: tempPath,
                uploadedUrl: "",
                uploading: false,
              });
            }
            uni.showToast({ title: "部分图片上传失败", icon: "none" });
          });
      });
    },
    // 获取分类列表
    async getCategoryList() {
      try {
        const payload = {
          folder_type: 1, // 1-分类
          timestamp: new Date().getTime(),
        };

        const res = await this.$go("album/lists/folder", payload, "post", {
          show_err: true,
        });

        if (
          res &&
          res.data &&
          res.data.lists &&
          Array.isArray(res.data.lists.data)
        ) {
          this.categoryList = this.flattenCategoryTree(res.data.lists.data);
        }
      } catch (err) {
        console.error("获取分类列表失败:", err);
        uni.showToast({
          title: "获取分类列表失败",
          icon: "none",
        });
      }
    },

    // 显示分类选择器
    showCategoryPicker() {
      if (this.categoryList.length === 0) {
        uni.showToast({
          title: "暂无分类，请先创建分类",
          icon: "none",
        });
        return;
      }
      this.categoryPickerShow = true;
    },

    // 确认选择分类（多选）
    handleCategoryConfirm(data) {
      this.selectedCategoryIds = data.ids;
      this.selectedCategoryNames = data.categories.map(
        (item) => item.folder_name,
      );
      this.categoryPickerShow = false;
    },

    async getProductDetail() {
      const payload = {
        fid: this.pid,
      };
      const res = await this.$go("album/products/detail", payload, "post", {
        show_err: true,
      });
      if (res.code === 0) {
        console.log(res);
        this.productName = res.data.folder_name;
        this.productIntro = res.data.folder_desc;
        this.hideDetailPictures =
          Number(res.data.hide_detail_pictures || 0) === 1;

        // 回显分类信息（支持多个分类）
        if (res.data.category_ids) {
          // 假设后端返回的是数组或逗号分隔的字符串
          console.log(this.categoryList);

          const categoryIds = Array.isArray(res.data.category_ids)
            ? res.data.category_ids
            : res.data.category_ids
                .toString()
                .split(",")
                .map((id) => parseInt(id));

          this.selectedCategoryIds = categoryIds;

          // 从分类列表中找到对应的分类名称
          this.selectedCategoryNames = this.categoryList
            .filter((item) => categoryIds.includes(item.id))
            .map((item) => item.folder_name);
          console.log(this.selectedCategoryNames);
        }

        this.coverImages = res.data.pic_list.map((res) => {
          return {
            src: res.imgurl,
            uploadedUrl: res.imgurl,
            uploading: false,
          };
        });
        this.coverImageIds = res.data.pic_list.map((res) => res.id);
        this.detailImages = res.data.detail_pic_list.map((res) => {
          return {
            src: res.imgurl,
            uploadedUrl: res.imgurl,
            uploading: false,
          };
        });
        this.detailImageIds = res.data.detail_pic_list.map((res) => res.id);
      }
    },
    // 由 UploadPicker 发出选择事件（selectType: 'image'|'video'|'from_chat'|'batch'）
    async onUploadPick(selectType) {
      // 根据选择处理
      if (selectType === "image") {
        // 图片：调用已有的图片选择上传方法
        if (this.uploadTarget === "cover") {
          // 复用之前的多图花色方法： chooseCovers()
          this.chooseCovers();
        } else {
          this.chooseDetails();
        }
      } else if (selectType === "video") {
        this.chooseVideo(this.uploadTarget);
      } else if (selectType === "from_chat") {
        this.chooseFromChat(this.uploadTarget);
      } else if (selectType === "ai_resource") {
        this.openAiResourcePicker(this.uploadTarget);
      } else if (selectType === "batch") {
        // 大批量上传：复制链接提示或打开 webview
        if (!this.pid) {
          uni.showToast({ title: "请先保存产品后再使用大批量上传", icon: "none" });
          return;
        }
        uni.navigateTo({
          url: "/pagesOther/batchUpload/batchUpload?fid=" + this.pid,
        });
      }
    },

    flattenCategoryTree(list, level = 0) {
      if (!Array.isArray(list)) return [];
      return list.reduce((result, item) => {
        result.push({
          ...item,
          level,
          display_name: `${level ? "　".repeat(level) : ""}${item.folder_name}`,
        });
        if (Array.isArray(item.children) && item.children.length) {
          result.push(...this.flattenCategoryTree(item.children, level + 1));
        }
        return result;
      }, []);
    },
    openAiResourcePicker(target) {
      const remain =
        target === "detail"
          ? this.maxDetailImages - this.detailImages.length
          : this.maxCoverImages - this.coverImages.length;
      if (remain <= 0) {
        uni.showToast({ title: "已达到图片上限", icon: "none" });
        return;
      }
      uni.navigateTo({
        url:
          "/pagesOther/aiResourcePicker/aiResourcePicker?target=" +
          encodeURIComponent(target || "cover") +
          "&limit=" +
          Math.min(remain, 60),
      });
    },
    appendAiResourceImages(target, items) {
      if (!Array.isArray(items) || items.length === 0) return;
      const imageList = target === "detail" ? this.detailImages : this.coverImages;
      const idList = target === "detail" ? this.detailImageIds : this.coverImageIds;
      const max = target === "detail" ? this.maxDetailImages : this.maxCoverImages;
      const exists = new Set(idList.map((id) => String(id)));
      items.forEach((item) => {
        if (!item || !item.id || exists.has(String(item.id)) || imageList.length >= max) {
          return;
        }
        imageList.push({
          src: item.url || item.file_url,
          uploadedUrl: item.file_url || item.url,
          uploading: false,
          source: "ai_resource",
          resourceId: item.resource_id,
        });
        idList.push(item.id);
        exists.add(String(item.id));
      });
    },
    chooseVideo(target) {
      uni.chooseVideo({
        sourceType: ["album", "camera"],
        maxDuration: 60,
        success: (res) => {
          const selectedFile = normalizeSelectedUploadFile(
            { ...res, path: res.tempFilePath },
            2,
          );
          const tempPath = selectedFile.path;
          // 将视频当作图片处理上传：复用 uploadSingleFile，随后把返回地址存入对应数组
          if (target === "cover") {
            const idx =
              this.coverImages.push({
                src: tempPath,
                uploadedUrl: "",
                uploading: true,
              }) - 1;
            this.uploadSingleFile(tempPath, 2, selectedFile.name, selectedFile.size)
              .then((res) => {
                this.$set(this.coverImages, idx, {
                  src: res.url,
                  uploadedUrl: res.url,
                  uploading: false,
                });
                this.coverImageIds.push(res.id);
              })
              .catch(() => {
                this.$set(this.coverImages, idx, {
                  src: tempPath,
                  uploadedUrl: "",
                  uploading: false,
                });
                uni.showToast({ title: "视频上传失败", icon: "none" });
              });
          } else {
            const idx =
              this.detailImages.push({
                src: tempPath,
                uploadedUrl: "",
                uploading: true,
              }) - 1;
            this.uploadSingleFile(tempPath, 2, selectedFile.name, selectedFile.size)
              .then((res) => {
                this.$set(this.detailImages, idx, {
                  src: res.url,
                  uploadedUrl: res.url,
                  uploading: false,
                });
                this.detailImageIds.push(res.id);
              })
              .catch(() => {
                this.$set(this.detailImages, idx, {
                  src: tempPath,
                  uploadedUrl: "",
                  uploading: false,
                });
                uni.showToast({ title: "视频上传失败", icon: "none" });
              });
          }
        },
      });
    },

    chooseFromChat(target) {
      // 在小程序端可使用 wx.chooseMessageFile（uni 下需引用 wx）
      if (typeof wx !== "undefined" && wx.chooseMessageFile) {
        wx.chooseMessageFile({
          count: 1,
          type: "image",
          success: (res) => {
            const selectedFile = normalizeSelectedUploadFile(
              (res.tempFiles && res.tempFiles[0]) || {},
              1,
            );
            const tempPath = selectedFile.path;
            // 立即上传并保存结果（同上）
            if (target === "cover") {
              const idx =
                this.coverImages.push({
                  src: tempPath,
                  uploadedUrl: "",
                  uploading: true,
                }) - 1;
              this.uploadSingleFile(tempPath, 1, selectedFile.name, selectedFile.size)
                .then((res) => {
                  this.$set(this.coverImages, idx, {
                    src: res.url,
                    uploadedUrl: res.url,
                    uploading: false,
                  });
                  this.coverImageIds.push(res.id);
                })
                .catch(() => {
                  this.$set(this.coverImages, idx, {
                    src: tempPath,
                    uploadedUrl: "",
                    uploading: false,
                  });
                  uni.showToast({ title: "上传失败", icon: "none" });
                });
            } else {
              const idx =
                this.detailImages.push({
                  src: tempPath,
                  uploadedUrl: "",
                  uploading: true,
                }) - 1;
              this.uploadSingleFile(tempPath, 1, selectedFile.name, selectedFile.size)
                .then((res) => {
                  this.$set(this.detailImages, idx, {
                    src: res.url,
                    uploadedUrl: res.url,
                    uploading: false,
                  });
                  this.detailImageIds.push(res.id);
                })
                .catch(() => {
                  this.$set(this.detailImages, idx, {
                    src: tempPath,
                    uploadedUrl: "",
                    uploading: false,
                  });
                  uni.showToast({ title: "上传失败", icon: "none" });
                });
            }
          },
          fail: () => {
            uni.showToast({ title: "选择失败", icon: "none" });
          },
        });
      } else {
        uni.showToast({ title: "该平台不支持从聊天中选取", icon: "none" });
      }
    },
    // 选择并上传多张花色图（选择后每张立即上传）
    chooseCovers() {
      const remain = this.maxCoverImages - this.coverImages.length;
      uni.chooseImage({
        count: remain,
        success: (res) => {
          const files =
            res.tempFiles && res.tempFiles.length
              ? res.tempFiles
              : (res.tempFilePaths || []).map((path) => ({ path }));
          files.forEach((file) => {
            const selectedFile = normalizeSelectedUploadFile(file, 1);
            const tempPath = selectedFile.path;
            const idx =
              this.coverImages.push({
                src: tempPath,
                uploadedUrl: "",
                uploading: true,
              }) - 1;
            this.uploadSingleFile(tempPath, 1, selectedFile.name, selectedFile.size)
              .then((res) => {
                console.log(res);
                if (this.coverImages[idx]) {
                  this.$set(this.coverImages, idx, {
                    src: res.url,
                    uploadedUrl: res.url,
                    uploading: false,
                  });
                }
                this.coverImageIds.push(res.id);
              })
              .catch((err) => {
                console.error("花色图上传失败", err);
                if (this.coverImages[idx]) {
                  this.$set(this.coverImages, idx, {
                    src: tempPath,
                    uploadedUrl: "",
                    uploading: false,
                  });
                }
                uni.showToast({ title: "部分图片上传失败", icon: "none" });
              });
          });
        },
        fail: () => {
          uni.showToast({ title: "选择图片失败", icon: "none" });
        },
      });
    },
    // 删除花色图
    removeCover(idx) {
      this.coverImages.splice(idx, 1);
      this.coverImageIds.splice(idx, 1);
    },

    // 选择并上传多张详情图（选择后每张立即上传）
    chooseDetails() {
      const remain = this.maxDetailImages - this.detailImages.length;
      uni.chooseImage({
        count: remain,
        success: (res) => {
          const files =
            res.tempFiles && res.tempFiles.length
              ? res.tempFiles
              : (res.tempFilePaths || []).map((path) => ({ path }));
          // 先在界面上添加占位项并显示上传中
          files.forEach((file) => {
            const selectedFile = normalizeSelectedUploadFile(file, 1);
            const tempPath = selectedFile.path;
            const idx =
              this.detailImages.push({
                src: tempPath,
                uploadedUrl: "",
                uploading: true,
              }) - 1;
            // 立即上传每张图片
            this.uploadSingleFile(tempPath, 1, selectedFile.name, selectedFile.size)
              .then((res) => {
                if (this.detailImages[idx]) {
                  this.$set(this.detailImages, idx, {
                    src: res.url,
                    uploadedUrl: res.url,
                    uploading: false,
                  });
                }
                this.detailImageIds.push(res.id);
              })
              .catch((err) => {
                console.error("详情图上传失败", err);
                // 上传失败将该项标为未上传（保留本地路径并提示）
                if (this.detailImages[idx]) {
                  this.$set(this.detailImages, idx, {
                    src: tempPath,
                    uploadedUrl: "",
                    uploading: false,
                  });
                }
                uni.showToast({ title: "部分图片上传失败", icon: "none" });
              });
          });
        },
        fail: () => {
          uni.showToast({ title: "选择图片失败", icon: "none" });
        },
      });
    },
    // 预览图片（合并花色图和详情图）
    previewImage(src) {
      const urls = [];
      this.coverImages.forEach((i) => urls.push(i.src));
      this.detailImages.forEach((i) => urls.push(i.src));
      uni.previewImage({
        current: src,
        urls: urls.length > 0 ? urls : [src],
      });
    },
    // 删除详情某一张
    removeDetail(idx) {
      this.detailImages.splice(idx, 1);
      this.detailImageIds.splice(idx, 1);
    },
    // 复制上传链接到剪贴板
    copyLink() {
      this.openBatchUpload();
    },
    openBatchUpload() {
      if (!this.pid) {
        uni.showToast({ title: "请先提交产品信息后再使用大批量上传", icon: "none" });
        return;
      }
      uni.navigateTo({
        url: "/pagesOther/batchUpload/batchUpload?fid=" + this.pid,
      });
    },
    // 提交：现在图片已在选择时上传，提交只负责发送文本和已上传的图片地址
    async submit() {
      if (this.productName == "") {
        uni.showToast({ title: "请输入产品名称", icon: "none" });
        return;
      }
      // 简单校验
      if (!this.coverImageIds.length && !this.detailImageIds.length) {
        uni.showToast({ title: "请上传至少一张图片", icon: "none" });
        return;
      }
      // 检查是否还有图片在上传中
      const uploadingCount =
        this.coverImages.filter((i) => i.uploading).length +
        this.detailImages.filter((i) => i.uploading).length;
      if (uploadingCount > 0) {
        uni.showToast({ title: "图片正在上传，请稍候", icon: "none" });
        return;
      }

      uni.showLoading({ title: "提交中..." });
      try {
        const payload = {
          folder_type: 2,
          folder_name: this.productName,
          folder_desc: this.productIntro,
          pic_ids: this.coverImageIds,
          detail_pic_ids: this.detailImageIds,
          hide_detail_pictures: this.hideDetailPictures ? 1 : 0,
          new_thumb:
            (this.coverImages[0] &&
              (this.coverImages[0].uploadedUrl || this.coverImages[0].src)) ||
            "",
          category_ids: this.selectedCategoryIds, // 多个分类ID数组
          timestamp: new Date().getTime(),
        };
        let url = "album/create/folder";
        if (this.pid) {
          payload.fid = this.pid;
          url = "album/edit/folder";
        }

        if (this.$go) {
          const params = this.$base
            ? { ...payload, sign: this.$base.getASCII(payload) }
            : payload;
          await this.$go(url, params, "post", { show_err: true });
          if (!this.pid) {
            this.finishIntegralTask("upload_product");
          }
          uni.showToast({ title: "提交成功", icon: "none" });
          this.notifyProductChanged();
          setTimeout(() => {
            uni.navigateBack();
          }, 1000);
        } else {
          console.warn("请将 payload 发送到后端：", payload);
          uni.showToast({
            title: "已构建请求，请替换为真实接口",
            icon: "none",
          });
        }
      } catch (err) {
        console.error(err);
        uni.showToast({ title: "提交失败", icon: "none" });
      } finally {
        uni.hideLoading();
      }
    },
    normalizeUploadResult(rawData) {
      let data = rawData || {};
      if (typeof data === "string") {
        data = JSON.parse(data || "{}");
      }

      if (data.code !== undefined && Number(data.code) !== 0) {
        throw new Error(data.msg || data.message || "上传失败");
      }

      const payload = Array.isArray(data.data)
        ? data.data[0] || {}
        : data.data || data;
      const url =
        payload.url ||
        payload.imgurl ||
        payload.src ||
        payload.fileUrl ||
        payload.picture_url ||
        data.url ||
        data.fileUrl ||
        "";
      const id =
        payload.id ||
        payload.pid ||
        payload.pic_id ||
        data.id ||
        data.pid ||
        "";

      if (!url) {
        throw new Error("上传返回缺少图片地址");
      }
      if (!id) {
        throw new Error("上传返回缺少图片ID");
      }

      return { id, url };
    },
    notifyProductChanged() {
      notifyRefresh(["product", "home"]);
    },
    finishIntegralTask(taskKey) {
      if (!this.$go || !taskKey) {
        return;
      }
      const querys = {
        task_key: taskKey,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };
      this.$go("integral/task/finish", data, "post", {
        show_err: false,
        loading: false,
      }).catch((err) => {
        console.log("积分任务完成失败:", err);
      });
    },
    buildUploadFileName(filePath, fileType = 1, originalName = "") {
      return getSelectedUploadFileName({ name: originalName }, filePath, fileType);
    },
    prepareUploadFilePath(filePath, uploadName) {
      return prepareNamedUploadFile(filePath, uploadName);
    },
    getLocalFileSize(filePath) {
      return new Promise((resolve) => {
        if (!filePath) {
          resolve(0);
          return;
        }
        const getter =
          (typeof uni !== "undefined" && uni.getFileInfo) ||
          (typeof wx !== "undefined" && wx.getFileInfo);
        if (!getter) {
          resolve(0);
          return;
        }
        getter({
          filePath,
          success: (res) => resolve(Number(res.size || 0)),
          fail: () => resolve(0),
        });
      });
    },
    compressImageWhenSizeMissing(filePath, fileType = 1) {
      return new Promise((resolve) => {
        if (Number(fileType) !== 1 || !uni.compressImage) {
          resolve(filePath);
          return;
        }
        uni.compressImage({
          src: filePath,
          quality: 92,
          success: (res) => resolve(res.tempFilePath || filePath),
          fail: () => resolve(filePath),
        });
      });
    },
    async prepareUploadSource(filePath, fileType = 1, fileSize = 0) {
      let uploadPath = filePath;
      let size = Number(fileSize || 0);
      if (size <= 0) {
        size = await this.getLocalFileSize(uploadPath);
      }
      if (size <= 0) {
        uploadPath = await this.compressImageWhenSizeMissing(uploadPath, fileType);
        size = await this.getLocalFileSize(uploadPath);
      }
      return {
        path: uploadPath,
        size,
      };
    },
    // 上传单文件并返回线上访问地址
    uploadSingleFile(filePath, fileType = 1, originalName = "", fileSize = 0) {
      const that = this;
      return new Promise((resolve, reject) => {
        that.prepareUploadSource(filePath, fileType, fileSize).then((sourceFile) => {
        const uploadName = that.buildUploadFileName(sourceFile.path, fileType, originalName);
        that.prepareUploadFilePath(sourceFile.path, uploadName).then((uploadPath) => {
          uni.uploadFile({
            url: that.$config.domain + that.uploadEndpoint,
            filePath: uploadPath,
            name: "file",
            header: {
              "content-type": "multipart/form-data", // 默认值
              "authorization-token": `Bearer ${uni.getStorageSync("token")}`,
            },
            formData: {
              file_type: fileType,
              file_size: Number(sourceFile.size || 0),
              size: Number(sourceFile.size || 0),
              ...buildUploadNameFormData(uploadName),
            },
            success: (uploadRes) => {
              try {
                resolve(that.normalizeUploadResult(uploadRes.data));
              } catch (e) {
                reject(e);
              }
            },
            fail: (e) => {
              reject(e);
            },
          });
        });
        }).catch(reject);
      });
    },
  },
};
</script>

<style scoped lang="scss">
.container {
  height: 100%;
  height: 100vh;
  background-color: #ffffff;
  box-sizing: border-box;
  overflow-y: auto;
  .container-scoll {
    width: 100%;
    padding-bottom: 160rpx;
  }

  .form-box {
    padding: 24rpx;
  }

  .form-item {
    margin-bottom: 16rpx;

    .label {
      font-weight: 400;
      font-size: 28rpx;
      color: #333333;
      display: block;
      margin-bottom: 12rpx;

      .optional {
        color: #999999;
        font-size: 24rpx;
      }
    }

    .input {
      width: 100%;
      height: 88rpx;
      line-height: 88rpx;
      background: #f2f2f2;
      border-radius: 16rpx;
      padding: 0 24rpx;
      font-size: 28rpx;
      color: #333333;
      box-sizing: border-box;
      border: none;
      overflow: hidden;
      white-space: nowrap;
    }

    .textarea {
      width: 100%;
      min-height: 180rpx;
      background: #f2f2f2;
      border-radius: 16rpx;
      padding: 20rpx;
      box-sizing: border-box;
      font-size: 28rpx;
      resize: none;
      border: none;
    }

    .char-count {
      display: block;
      text-align: right;
      font-size: 22rpx;
      color: #999999;
      margin-top: 8rpx;
    }

    .label-row {
      display: flex;
      align-items: center;
      gap: 24rpx;
      margin-bottom: 12rpx;

      .label {
        margin-bottom: 0;
      }
    }

    .hide-detail-toggle {
      font-size: 26rpx;
      color: #ff3b30;
      line-height: 1.4;
    }

    .select-box {
      width: 100%;
      height: 88rpx;
      background: #f2f2f2;
      border-radius: 16rpx;
      padding: 0 20rpx;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;

      .select-text {
        font-size: 28rpx;
        color: #333333;
        flex: 1;

        &.placeholder {
          color: #999999;
        }
      }

      .arrow-icon {
        width: 32rpx;
        height: 32rpx;
        margin-left: 16rpx;
      }
    }
  }

  .upload-row {
    display: flex;
    align-items: center;
  }

  .detail-uploads {
    display: flex;
    flex-wrap: wrap;
    gap: 20rpx;
  }

  .upload-cell {
    width: 150rpx;
    height: 150rpx;
    background: #f0f0f0;
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  .upload-plus {
    font-size: 56rpx;
    color: #666666;
  }

  .upload-preview {
    width: 100%;
    height: 100%;
  }

  .remove-x {
    position: absolute;
    right: 6rpx;
    top: 6rpx;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    width: 30rpx;
    height: 30rpx;
    border-radius: 15rpx;
    text-align: center;
    line-height: 30rpx;
    font-size: 24rpx;
  }

  .upload-actions {
    margin-left: 20rpx;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
  }

  .small-btn {
    background: #ffffff;
    border-radius: 8rpx;
    padding: 10rpx 20rpx;
    font-size: 24rpx;
    color: #333333;
  }

  .notice-box {
    margin-top: 20rpx;
    background: #fff9dd;
    border: 1rpx solid rgba(255, 208, 0, 0.38);
    border-radius: 20rpx;
    padding: 24rpx 24rpx 26rpx;
    display: flex;
    flex-direction: column;
    gap: 16rpx;

    .notice-title {
      font-size: 28rpx;
      font-weight: 700;
      color: #333333;
      line-height: 1.45;
    }

    .notice-desc {
      font-size: 24rpx;
      font-weight: 400;
      color: #777777;
      line-height: 1.65;
    }

    .url-row {
      min-height: 64rpx;
      padding: 18rpx 20rpx;
      background: rgba(255, 255, 255, 0.76);
      border-radius: 14rpx;
      display: flex;
      align-items: center;
      box-sizing: border-box;
    }

    .notice-icon {
      width: 42rpx;
      height: 42rpx;
      margin-right: 16rpx;
      flex-shrink: 0;
    }

    .url-copy {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 6rpx;
    }

    .url-text {
      font-size: 25rpx;
      color: #333333;
      font-weight: 700;
      line-height: 1.35;
    }

    .url-subtext {
      font-size: 22rpx;
      color: #777777;
      line-height: 1.35;
    }

    .copy-btn {
      text-align: center;
      background: #ffffff;
      border: 1rpx solid rgba(255, 208, 0, 0.72);
      font-weight: 700;
      font-size: 28rpx;
      color: #5f4a00;
      padding: 18rpx 24rpx;
      border-radius: 999rpx;
      line-height: 1.2;
      box-shadow: none;
      box-sizing: border-box;
    }

    .copy-btn.copied {
      background: #222222;
      color: #ffd000;
      border-color: #222222;
      box-shadow: none;
    }
  }

  /* 上传中遮罩 */
  .upload-mask {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26rpx;
  }

  .upload-mask.small {
    font-size: 22rpx;
  }

  .submit-wrap {
    position: fixed;
    bottom: 30rpx;
    left: 0;
    width: 100%;
    display: flex;
    justify-content: center;
  }

  .submit-btn {
    width: 686rpx;
    height: 96rpx;
    background: #ffd800;
    border-radius: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32rpx;
    color: #333333;
    font-weight: 600;
    box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.1);
  }
}
</style>
