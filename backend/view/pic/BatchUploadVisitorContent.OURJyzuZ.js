import { defineComponent, ref, computed, useSSRContext, mergeProps, withCtx, createTextVNode, onMounted, createVNode, createBlock, createCommentVNode, openBlock, withKeys } from "vue";
import { toast } from "vue-sonner";
import { p as pcApi, u as uploadTokenStore, D as DialogTitle, a as DialogHeader, b as DialogContent, c as Dialog } from "./DialogTrigger.DL_BFsqe.js";
import { m as mapProduct } from "./jfyuntu-mappers.lUaEb_hK.js";
import { S as SafeIcon } from "./SafeIcon.J62d3GBT.js";
import { B as Button } from "./index.gwJhr38l.js";
import { I as Input } from "./Input.CP_AwBXf.js";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderComponent, ssrRenderList, ssrRenderAttr, ssrRenderStyle } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.cTiqBCPS.js";
const _sfc_main$2 = defineComponent({ __name: "UploadZone", props: { title: {}, description: {}, type: {}, progress: { default: 0 }, disabled: { type: Boolean, default: false }, maxConcurrent: { default: 1 }, uploadHandler: {} }, emits: ["upload-complete", "uploading"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, isDragging = ref(false), fileInput = ref(), uploadItems = ref([]), uploadedFiles = computed(() => uploadItems.value.map((item) => item.finalUrl || item.previewUrl)), safeMaxConcurrent = computed(() => {
    const value = Number(props.maxConcurrent || 1);
    return !Number.isFinite(value) || value <= 0 ? 1 : Math.min(Math.floor(value), 8);
  }), handleDragOver = (e) => {
    e.preventDefault(), props.disabled || (isDragging.value = true);
  }, handleDragLeave = () => {
    isDragging.value = false;
  }, handleDrop = (e) => {
    if (e.preventDefault(), isDragging.value = false, props.disabled) return;
    const files = e.dataTransfer?.files;
    files && handleFiles(files);
  }, handleFileSelect = (e) => {
    const target = e.target;
    target.files && handleFiles(target.files);
  }, handleFiles = async (files) => {
    const imageFiles = Array.from(files).filter((file) => file.type.startsWith("image/"));
    if (imageFiles.length === 0) {
      toast.error("请选择有效的图片文件");
      return;
    }
    const items = imageFiles.map((file) => ({ previewUrl: URL.createObjectURL(file), finalUrl: "", status: "uploading" }));
    uploadItems.value = items, emit("uploading", true);
    try {
      let nextIndex = 0, failedCount = 0;
      const worker = async () => {
        for (; nextIndex < imageFiles.length; ) {
          const index = nextIndex++, file = imageFiles[index], item = items[index];
          let finalUrl = item.previewUrl;
          try {
            props.uploadHandler ? finalUrl = await props.uploadHandler(file, props.type) : await new Promise((resolve) => setTimeout(resolve, 300)), item.finalUrl = finalUrl || item.previewUrl, item.status = "done";
          } catch {
            failedCount += 1, item.status = "error";
          }
          uploadItems.value = [...items];
        }
      };
      if (await Promise.all(Array.from({ length: Math.min(safeMaxConcurrent.value, imageFiles.length) }, () => worker())), failedCount > 0) {
        toast.error(`${failedCount} 张图片上传失败，请重试`);
        return;
      }
      const finalUrls = items.map((item) => item.finalUrl || item.previewUrl);
      emit("upload-complete", finalUrls), toast.success(`已上传 ${imageFiles.length} 张图片`);
    } catch (error) {
      uploadItems.value = items.map((item) => item.status === "done" ? item : { ...item, status: "error" }), toast.error(error?.message || "上传失败，请重试");
    } finally {
      emit("uploading", false), fileInput.value && (fileInput.value.value = "");
    }
  }, __returned__ = { props, emit, isDragging, fileInput, uploadItems, uploadedFiles, safeMaxConcurrent, handleDragOver, handleDragLeave, handleDrop, handleFileSelect, handleFiles, triggerFileInput: () => {
    props.disabled || fileInput.value?.click();
  }, getItemStatus: (index) => {
    const status = uploadItems.value[index]?.status;
    return status === "uploading" ? "上传中" : status === "error" ? "上传失败" : "已上传";
  }, getItemIcon: (index) => {
    const status = uploadItems.value[index]?.status;
    return status === "uploading" ? "Loader2" : status === "error" ? "AlertCircle" : "Check";
  }, getItemIconClass: (index) => {
    const status = uploadItems.value[index]?.status;
    return status === "uploading" ? "text-muted-foreground animate-spin" : status === "error" ? "text-destructive" : "text-primary";
  }, get Button() {
    return Button;
  }, SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "surface-base card-padding space-y-4" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="text-section-title">${ssrInterpolate($props.title)}</h3><span class="text-xs font-medium px-2 py-1 bg-primary/10 text-primary rounded">${ssrInterpolate($setup.uploadedFiles.length)} 张 </span></div>`), $props.progress < 100 ? (_push(`<div class="${ssrRenderClass([[$setup.isDragging && "border-primary bg-primary/5", $props.disabled && "opacity-60 pointer-events-none"], "upload-zone"])}"><input type="file" multiple accept="image/*" class="hidden"${ssrIncludeBooleanAttr($props.disabled) ? " disabled" : ""}><div class="flex flex-col items-center gap-3"><div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">`), _push(ssrRenderComponent($setup.SafeIcon, { name: "Upload", size: 24, class: "text-primary" }, null, _parent)), _push(`</div><div class="text-center"><p class="font-medium text-foreground">${ssrInterpolate($props.description)}</p><p class="text-xs text-muted-foreground mt-1">或点击选择文件</p></div>`), _push(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", disabled: $props.disabled, onClick: $setup.triggerFileInput }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(" 选择文件 ");
    else return [createTextVNode(" 选择文件 ")];
  }), _: 1 }, _parent)), _push("</div></div>")) : _push("<!---->"), $setup.uploadedFiles.length > 0 ? (_push('<div class="space-y-2"><!--[-->'), ssrRenderList($setup.uploadedFiles, (file, index) => {
    _push(`<div class="flex items-center gap-3 p-2 bg-muted/50 rounded border border-border"><img${ssrRenderAttr("src", file)}${ssrRenderAttr("alt", `Uploaded ${index + 1}`)} class="w-10 h-10 object-cover rounded"><div class="flex-1 min-w-0"><p class="text-sm font-medium truncate">图片 ${ssrInterpolate(index + 1)}</p><p class="text-xs text-muted-foreground">${ssrInterpolate($setup.getItemStatus(index))}</p></div>`), _push(ssrRenderComponent($setup.SafeIcon, { name: $setup.getItemIcon(index), size: 18, class: ["shrink-0", $setup.getItemIconClass(index)] }, null, _parent)), _push("</div>");
  }), _push("<!--]--></div>")) : _push("<!---->"), $props.progress > 0 && $props.progress < 100 ? _push(`<div class="space-y-2"><div class="flex justify-between text-xs"><span class="text-muted-foreground">上传中...</span><span class="font-medium">${ssrInterpolate($props.progress)}%</span></div><div class="w-full h-2 bg-muted rounded-full overflow-hidden"><div class="h-full bg-primary transition-all duration-300" style="${ssrRenderStyle({ width: `${$props.progress}%` })}"></div></div></div>`) : _push("<!---->"), _push("</div>");
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/batch_upload/UploadZone.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const UploadZone = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "UploadProgress", props: { progress: {}, isComplete: { type: Boolean, default: false } }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "surface-base card-padding space-y-4" }, _attrs))}><div class="flex items-center justify-between"><h3 class="text-section-title">上传进度</h3><span class="text-2xl font-bold text-primary">${ssrInterpolate($props.progress)}%</span></div><div class="w-full h-3 bg-muted rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-primary to-accent transition-all duration-500" style="${ssrRenderStyle({ width: `${$props.progress}%` })}"></div></div>`), $props.isComplete ? (_push('<div class="flex items-center gap-2 p-3 bg-primary/10 rounded-lg border border-primary/20">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "CheckCircle2", size: 20, class: "text-primary shrink-0" }, null, _parent)), _push('<div><p class="text-sm font-medium text-primary">上传完成</p><p class="text-xs text-primary/70">所有图片已成功上传</p></div></div>')) : _push("<!---->"), _push("</div>");
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/batch_upload/UploadProgress.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const UploadProgress = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "BatchUploadVisitorContent", setup(__props, { expose: __expose }) {
  __expose();
  const isClient = ref(true), productId = ref(""), uploadCode = ref(""), uploadToken = ref(""), product = ref(null), folderInfo = ref(null), ownerId = ref(""), ownerName = ref(""), ownerAvatar = ref(""), remainingSize = ref(""), uploadLimit = ref(""), concurrencyLimit = ref(1), passwordVerified = ref(false), passwordDialogOpen = ref(false), passwordValue = ref(""), passwordLoading = ref(false), accessClosed = ref(false), accessError = ref(""), uploadProgress = ref({ colorChart: 0, detailChart: 0 }), uploadedFiles = ref({ colorChart: [], detailChart: [] }), uploadingState = ref({ colorChart: false, detailChart: false }), totalProgress = computed(() => {
    const total = uploadProgress.value.colorChart + uploadProgress.value.detailChart;
    return Math.round(total / 2);
  }), isUploadComplete = computed(() => uploadProgress.value.colorChart === 100 && uploadProgress.value.detailChart === 100), initUpload = async () => {
    try {
      const info = await pcApi.getWebUploadInfo(uploadCode.value);
      if (folderInfo.value = info, accessClosed.value = Number(info?.upload_enabled ?? 1) !== 1, productId.value = String(info?.id || ""), ownerId.value = String(info?.owner_id || info?.uid || ""), ownerName.value = info?.owner_name || info?.company_name || info?.nickname || "", ownerAvatar.value = info?.owner_avatar || info?.company_logo || info?.avatar || "", remainingSize.value = info?.remaining_size !== void 0 ? `${info.remaining_size} MB` : "", uploadLimit.value = info?.upload_limit !== void 0 ? `${info.upload_limit} MB/张` : "", concurrencyLimit.value = Math.max(1, Math.min(Number(info?.concurrency_limit || 1), 8)), product.value = mapProduct({ id: info?.id, uid: info?.owner_id || info?.uid, folder_name: info?.folder_name, folder_desc: info?.folder_desc, new_thumb: info?.new_thumb, folder_type: 2 }), accessClosed.value) {
        accessError.value = "此产品上传入口已关闭，请联系分享者开启";
        return;
      }
      const cachedToken = uploadTokenStore.get(uploadCode.value);
      if (cachedToken) {
        uploadToken.value = cachedToken, passwordVerified.value = true, passwordDialogOpen.value = false;
        return;
      }
      Number(info?.has_password || 0) === 1 ? passwordDialogOpen.value = true : await requestUploadToken("");
    } catch (error) {
      accessClosed.value = true, accessError.value = error?.message || "链接无效或已失效";
    }
  };
  onMounted(() => {
    isClient.value = false, requestAnimationFrame(() => {
      const params = new URLSearchParams(window.location.search);
      uploadCode.value = params.get("uploadd_code") || params.get("code") || "", uploadCode.value ? initUpload() : (accessClosed.value = true, accessError.value = "缺少上传码，请检查链接是否完整"), isClient.value = true;
    });
  });
  const handlePasswordVerified = async () => {
    passwordVerified.value = true, toast.success("密码验证成功");
  }, requestUploadToken = async (password) => {
    passwordLoading.value = true;
    try {
      const data = await pcApi.getWebUploadToken(uploadCode.value, password);
      uploadToken.value = data?.token || "", uploadTokenStore.set(uploadToken.value, uploadCode.value), passwordVerified.value = true, passwordDialogOpen.value = false, password && toast.success("密码验证成功");
    } catch (error) {
      passwordVerified.value = false, toast.error(error?.message || "密码验证失败");
    } finally {
      passwordLoading.value = false;
    }
  }, __returned__ = { isClient, productId, uploadCode, uploadToken, product, folderInfo, ownerId, ownerName, ownerAvatar, remainingSize, uploadLimit, concurrencyLimit, passwordVerified, passwordDialogOpen, passwordValue, passwordLoading, accessClosed, accessError, uploadProgress, uploadedFiles, uploadingState, totalProgress, isUploadComplete, initUpload, handlePasswordVerified, requestUploadToken, submitPassword: () => {
    if (!passwordValue.value.trim()) {
      toast.error("请输入访问密码");
      return;
    }
    requestUploadToken(passwordValue.value.trim());
  }, handleUploadComplete: (type, files) => {
    uploadedFiles.value[type] = files, uploadProgress.value[type] = 100, toast.success(`${type === "colorChart" ? "花色图" : "详情图"}上传成功`), isUploadComplete.value && toast.success("所有图片上传完成");
  }, uploadFile: async (file, type) => {
    const token = uploadToken.value || uploadTokenStore.get(uploadCode.value);
    if (!token || !productId.value) throw new Error("上传凭证未就绪");
    const result = await pcApi.uploadWebProductImage(productId.value, file, type, token), item = (Array.isArray(result?.data) ? result.data : Array.isArray(result) ? result : [])?.[0] || {};
    return item.url || item.picture_url || item.imgurl || item.file_url || URL.createObjectURL(file);
  }, handleViewProduct: () => {
    if (productId.value) {
      const params = new URLSearchParams({ productId: productId.value });
      ownerId.value && params.set("uid", ownerId.value), window.location.href = `./product-detail.html?${params.toString()}`;
    } else window.location.href = "./share-home.html";
  }, setUploading: (type, value) => {
    uploadingState.value[type] = value;
  }, SafeIcon, get Button() {
    return Button;
  }, get Dialog() {
    return Dialog;
  }, get DialogContent() {
    return DialogContent;
  }, get DialogHeader() {
    return DialogHeader;
  }, get DialogTitle() {
    return DialogTitle;
  }, get Input() {
    return Input;
  }, UploadZone, UploadProgress };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full space-y-6" }, _attrs))}><div class="text-center space-y-2"><h1 class="text-3xl font-bold text-foreground">批量上传图片</h1><p class="text-muted-foreground">${ssrInterpolate($setup.product?.name || $setup.folderInfo?.folder_name || "未命名产品")}</p></div>`), $setup.accessClosed ? (_push('<div class="surface-base card-padding text-center space-y-3">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Lock", size: 36, class: "mx-auto text-muted-foreground" }, null, _parent)), _push(`<h3 class="text-section-title">无法访问上传入口</h3><p class="text-sm text-muted-foreground">${ssrInterpolate($setup.accessError || "此产品上传入口已关闭")}</p></div>`)) : _push("<!---->"), !$setup.accessClosed && $setup.passwordVerified ? (_push('<div class="space-y-6 animate-in fade-in duration-300"><div class="surface-base card-padding flex flex-col gap-4 md:flex-row md:items-center md:justify-between"><div class="flex items-center gap-3 min-w-0">'), $setup.ownerAvatar ? _push(`<img${ssrRenderAttr("src", $setup.ownerAvatar)}${ssrRenderAttr("alt", $setup.ownerName)} class="w-12 h-12 rounded-lg object-cover border border-border bg-muted">`) : _push("<!---->"), _push(`<div class="min-w-0"><p class="text-sm font-medium truncate">${ssrInterpolate($setup.ownerName || "分享者")}</p><p class="text-xs text-muted-foreground truncate">产品：${ssrInterpolate($setup.product?.name || $setup.folderInfo?.folder_name || "未命名产品")}</p></div></div><div class="grid grid-cols-2 gap-3 text-sm md:min-w-64"><div class="rounded-md border border-border bg-muted/30 px-3 py-2"><p class="text-[11px] text-muted-foreground">剩余容量</p><p class="font-medium">${ssrInterpolate($setup.remainingSize || "-")}</p></div><div class="rounded-md border border-border bg-muted/30 px-3 py-2"><p class="text-[11px] text-muted-foreground">单图限制</p><p class="font-medium">${ssrInterpolate($setup.uploadLimit || "-")}</p></div></div></div>`), _push(ssrRenderComponent($setup.UploadZone, { title: "花色图", description: "拖拽或点击选择花色图片", type: "colorChart", progress: $setup.uploadProgress.colorChart, disabled: $setup.uploadingState.colorChart, "max-concurrent": $setup.concurrencyLimit, "upload-handler": $setup.uploadFile, onUploadComplete: (files) => $setup.handleUploadComplete("colorChart", files), onUploading: (val) => $setup.setUploading("colorChart", val) }, null, _parent)), _push(ssrRenderComponent($setup.UploadZone, { title: "详情图", description: "拖拽或点击选择详情图片", type: "detailChart", progress: $setup.uploadProgress.detailChart, disabled: $setup.uploadingState.detailChart, "max-concurrent": $setup.concurrencyLimit, "upload-handler": $setup.uploadFile, onUploadComplete: (files) => $setup.handleUploadComplete("detailChart", files), onUploading: (val) => $setup.setUploading("detailChart", val) }, null, _parent)), _push(ssrRenderComponent($setup.UploadProgress, { progress: $setup.totalProgress, "is-complete": $setup.isUploadComplete }, null, _parent)), $setup.isUploadComplete ? (_push('<div class="flex gap-3 justify-center pt-4">'), _push(ssrRenderComponent($setup.Button, { variant: "default", size: "lg", class: "px-8", onClick: $setup.handleViewProduct }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "Eye", size: 18, class: "mr-2" }, null, _parent2, _scopeId)), _push2(" 查看产品 ");
    else return [createVNode($setup.SafeIcon, { name: "Eye", size: 18, class: "mr-2" }), createTextVNode(" 查看产品 ")];
  }), _: 1 }, _parent)), _push("</div>")) : _push("<!---->"), _push("</div>")) : _push("<!---->"), _push(ssrRenderComponent($setup.Dialog, { open: $setup.passwordDialogOpen, "onUpdate:open": ($event) => $setup.passwordDialogOpen = $event }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogContent, { class: "sm:max-w-[420px]" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogHeader, null, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("请输入访问密码");
          else return [createTextVNode("请输入访问密码")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("请输入访问密码")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="space-y-4"${_scopeId2}>`), _push3(ssrRenderComponent($setup.Input, { modelValue: $setup.passwordValue, "onUpdate:modelValue": ($event) => $setup.passwordValue = $event, type: "password", maxlength: "12", placeholder: "请输入分享者提供的密码", onKeyup: $setup.submitPassword }, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.Button, { class: "w-full", disabled: $setup.passwordLoading || !$setup.passwordValue.trim(), onClick: $setup.submitPassword }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) $setup.passwordLoading ? _push4(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 16, class: "mr-2 animate-spin" }, null, _parent4, _scopeId3)) : _push4("<!---->"), _push4(" 确认进入 ");
        else return [$setup.passwordLoading ? (openBlock(), createBlock($setup.SafeIcon, { key: 0, name: "Loader2", size: 16, class: "mr-2 animate-spin" })) : createCommentVNode("", true), createTextVNode(" 确认进入 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3("</div>");
      else return [createVNode($setup.DialogHeader, null, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("请输入访问密码")]), _: 1 })]), _: 1 }), createVNode("div", { class: "space-y-4" }, [createVNode($setup.Input, { modelValue: $setup.passwordValue, "onUpdate:modelValue": ($event) => $setup.passwordValue = $event, type: "password", maxlength: "12", placeholder: "请输入分享者提供的密码", onKeyup: withKeys($setup.submitPassword, ["enter"]) }, null, 8, ["modelValue", "onUpdate:modelValue"]), createVNode($setup.Button, { class: "w-full", disabled: $setup.passwordLoading || !$setup.passwordValue.trim(), onClick: $setup.submitPassword }, { default: withCtx(() => [$setup.passwordLoading ? (openBlock(), createBlock($setup.SafeIcon, { key: 0, name: "Loader2", size: 16, class: "mr-2 animate-spin" })) : createCommentVNode("", true), createTextVNode(" 确认进入 ")]), _: 1 }, 8, ["disabled"])])];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogContent, { class: "sm:max-w-[420px]" }, { default: withCtx(() => [createVNode($setup.DialogHeader, null, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("请输入访问密码")]), _: 1 })]), _: 1 }), createVNode("div", { class: "space-y-4" }, [createVNode($setup.Input, { modelValue: $setup.passwordValue, "onUpdate:modelValue": ($event) => $setup.passwordValue = $event, type: "password", maxlength: "12", placeholder: "请输入分享者提供的密码", onKeyup: withKeys($setup.submitPassword, ["enter"]) }, null, 8, ["modelValue", "onUpdate:modelValue"]), createVNode($setup.Button, { class: "w-full", disabled: $setup.passwordLoading || !$setup.passwordValue.trim(), onClick: $setup.submitPassword }, { default: withCtx(() => [$setup.passwordLoading ? (openBlock(), createBlock($setup.SafeIcon, { key: 0, name: "Loader2", size: 16, class: "mr-2 animate-spin" })) : createCommentVNode("", true), createTextVNode(" 确认进入 ")]), _: 1 }, 8, ["disabled"])])]), _: 1 })];
  }), _: 1 }, _parent)), !$setup.accessClosed && !$setup.product ? (_push('<div class="text-center space-y-4 py-8"><div class="w-16 h-16 bg-destructive/10 rounded-full flex items-center justify-center mx-auto">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "AlertCircle", size: 32, class: "text-destructive" }, null, _parent)), _push('</div><div><h3 class="text-lg font-semibold text-foreground mb-1">产品不存在</h3><p class="text-sm text-muted-foreground">请检查链接是否正确</p></div>'), _push(ssrRenderComponent($setup.Button, { variant: "outline", onClick: () => _ctx.window.location.href = "./share-home.html" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(" 返回主页 ");
    else return [createTextVNode(" 返回主页 ")];
  }), _: 1 }, _parent)), _push("</div>")) : _push("<!---->"), _push("</div>");
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/batch_upload/BatchUploadVisitorContent.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const BatchUploadVisitorContent = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  BatchUploadVisitorContent as B
};
