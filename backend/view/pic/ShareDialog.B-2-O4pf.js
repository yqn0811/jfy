import { defineComponent, ref, onMounted, watch, useSSRContext, mergeProps, withCtx, createTextVNode, createVNode, createBlock, openBlock, toDisplayString } from "vue";
import { toast } from "vue-sonner";
import { p as pcApi, d as DialogFooter, e as DialogDescription, D as DialogTitle, a as DialogHeader, b as DialogContent, c as Dialog } from "./DialogTrigger.Bf13fktX.js";
import { B as Button } from "./index.D4Z4a3fh.js";
import { I as Input } from "./Input.CB2G6HV3.js";
import { S as SafeIcon, _ as _export_sfc } from "./SafeIcon.8ztUq1M8.js";
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate } from "vue/server-renderer";
const _sfc_main = defineComponent({ __name: "ShareDialog", props: { open: { type: Boolean }, productId: {}, targetUserId: {} }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, shareUrl = ref(""), miniCodeUrl = ref(""), miniPath = ref(""), isLoadingShare = ref(false), buildShareUrl = () => {
    const params = new URLSearchParams({ productId: props.productId });
    return props.targetUserId && params.set("uid", props.targetUserId), `${window.location.origin}/product-detail.html?${params.toString()}`;
  }, loadShareData = async () => {
    if (shareUrl.value = buildShareUrl(), miniCodeUrl.value = "", miniPath.value = "", !(!props.targetUserId || !props.productId)) {
      isLoadingShare.value = true;
      try {
        const codeData = await pcApi.getHomeMiniCode(props.targetUserId, "product", props.productId).catch(() => null);
        miniCodeUrl.value = codeData?.qrcode || codeData?.qrcode_url || "", miniPath.value = codeData?.mini_path || "";
      } finally {
        isLoadingShare.value = false;
      }
    }
  };
  onMounted(loadShareData), watch(() => props.open, (open) => {
    open && loadShareData();
  });
  const __returned__ = { props, emit, shareUrl, miniCodeUrl, miniPath, isLoadingShare, buildShareUrl, loadShareData, handleCopyLink: () => {
    navigator.clipboard.writeText(shareUrl.value), toast.success("分享链接已复制");
  }, handleShareWeChat: () => {
    toast.info("请使用微信扫描二维码分享");
  }, handleShareQQ: () => {
    toast.info("请使用QQ分享此链接");
  }, get Dialog() {
    return Dialog;
  }, get DialogContent() {
    return DialogContent;
  }, get DialogHeader() {
    return DialogHeader;
  }, get DialogTitle() {
    return DialogTitle;
  }, get DialogDescription() {
    return DialogDescription;
  }, get DialogFooter() {
    return DialogFooter;
  }, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Dialog, mergeProps({ open: $props.open, "onUpdate:open": ($event) => $setup.emit("update:open", $event) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogContent, { class: "max-w-[500px]" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogHeader, null, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("分享产品");
          else return [createTextVNode("分享产品")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.DialogDescription, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 选择分享方式，让更多人了解这款产品 ");
          else return [createTextVNode(" 选择分享方式，让更多人了解这款产品 ")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("分享产品")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode(" 选择分享方式，让更多人了解这款产品 ")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="space-y-4 py-4"${_scopeId2}><div class="space-y-2"${_scopeId2}><label class="text-sm font-medium"${_scopeId2}>分享链接</label><div class="flex gap-2"${_scopeId2}>`), _push3(ssrRenderComponent($setup.Input, { value: $setup.shareUrl, readonly: "", class: "flex-1 bg-muted/50 text-sm" }, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", class: "px-3", onClick: $setup.handleCopyLink }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "Copy", size: 16 }, null, _parent4, _scopeId3));
        else return [createVNode($setup.SafeIcon, { name: "Copy", size: 16 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`</div></div><div class="space-y-2"${_scopeId2}><label class="text-sm font-medium"${_scopeId2}>分享方式</label><div class="grid grid-cols-3 gap-2"${_scopeId2}>`), _push3(ssrRenderComponent($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareWeChat }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "MessageCircle", size: 24, class: "text-green-600" }, null, _parent4, _scopeId3)), _push4(`<span class="text-xs"${_scopeId3}>微信</span>`);
        else return [createVNode($setup.SafeIcon, { name: "MessageCircle", size: 24, class: "text-green-600" }), createVNode("span", { class: "text-xs" }, "微信")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareQQ }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "Send", size: 24, class: "text-blue-600" }, null, _parent4, _scopeId3)), _push4(`<span class="text-xs"${_scopeId3}>QQ</span>`);
        else return [createVNode($setup.SafeIcon, { name: "Send", size: 24, class: "text-blue-600" }), createVNode("span", { class: "text-xs" }, "QQ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleCopyLink }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "Link", size: 24, class: "text-primary" }, null, _parent4, _scopeId3)), _push4(`<span class="text-xs"${_scopeId3}>复制链接</span>`);
        else return [createVNode($setup.SafeIcon, { name: "Link", size: 24, class: "text-primary" }), createVNode("span", { class: "text-xs" }, "复制链接")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`</div></div><div class="space-y-2 pt-2 border-t"${_scopeId2}><label class="text-sm font-medium"${_scopeId2}>二维码分享</label><div class="flex justify-center p-4 bg-muted/30 rounded-lg"${_scopeId2}>`), $setup.miniCodeUrl ? _push3(`<img${ssrRenderAttr("src", $setup.miniCodeUrl)} alt="产品小程序码" class="w-40 h-40 border border-border rounded object-contain bg-white"${_scopeId2}>`) : $setup.isLoadingShare ? _push3(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 28, class: "animate-spin text-muted-foreground" }, null, _parent3, _scopeId2)) : _push3(ssrRenderComponent($setup.SafeIcon, { name: "QrCode", size: 36, class: "text-muted-foreground" }, null, _parent3, _scopeId2)), _push3(`</div><p class="text-xs text-muted-foreground text-center"${_scopeId2}>${ssrInterpolate($setup.miniPath || "扫描小程序码快速分享；网页链接可直接复制")}</p></div></div>`), _push3(ssrRenderComponent($setup.DialogFooter, null, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.Button, { variant: "outline", onClick: ($event) => $setup.emit("update:open", false) }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 关闭 ");
          else return [createTextVNode(" 关闭 ")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.Button, { variant: "outline", onClick: ($event) => $setup.emit("update:open", false) }, { default: withCtx(() => [createTextVNode(" 关闭 ")]), _: 1 }, 8, ["onClick"])];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DialogHeader, null, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("分享产品")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode(" 选择分享方式，让更多人了解这款产品 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "space-y-4 py-4" }, [createVNode("div", { class: "space-y-2" }, [createVNode("label", { class: "text-sm font-medium" }, "分享链接"), createVNode("div", { class: "flex gap-2" }, [createVNode($setup.Input, { value: $setup.shareUrl, readonly: "", class: "flex-1 bg-muted/50 text-sm" }, null, 8, ["value"]), createVNode($setup.Button, { variant: "outline", size: "sm", class: "px-3", onClick: $setup.handleCopyLink }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Copy", size: 16 })]), _: 1 })])]), createVNode("div", { class: "space-y-2" }, [createVNode("label", { class: "text-sm font-medium" }, "分享方式"), createVNode("div", { class: "grid grid-cols-3 gap-2" }, [createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareWeChat }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "MessageCircle", size: 24, class: "text-green-600" }), createVNode("span", { class: "text-xs" }, "微信")]), _: 1 }), createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareQQ }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Send", size: 24, class: "text-blue-600" }), createVNode("span", { class: "text-xs" }, "QQ")]), _: 1 }), createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleCopyLink }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Link", size: 24, class: "text-primary" }), createVNode("span", { class: "text-xs" }, "复制链接")]), _: 1 })])]), createVNode("div", { class: "space-y-2 pt-2 border-t" }, [createVNode("label", { class: "text-sm font-medium" }, "二维码分享"), createVNode("div", { class: "flex justify-center p-4 bg-muted/30 rounded-lg" }, [$setup.miniCodeUrl ? (openBlock(), createBlock("img", { key: 0, src: $setup.miniCodeUrl, alt: "产品小程序码", class: "w-40 h-40 border border-border rounded object-contain bg-white" }, null, 8, ["src"])) : $setup.isLoadingShare ? (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Loader2", size: 28, class: "animate-spin text-muted-foreground" })) : (openBlock(), createBlock($setup.SafeIcon, { key: 2, name: "QrCode", size: 36, class: "text-muted-foreground" }))]), createVNode("p", { class: "text-xs text-muted-foreground text-center" }, toDisplayString($setup.miniPath || "扫描小程序码快速分享；网页链接可直接复制"), 1)])]), createVNode($setup.DialogFooter, null, { default: withCtx(() => [createVNode($setup.Button, { variant: "outline", onClick: ($event) => $setup.emit("update:open", false) }, { default: withCtx(() => [createTextVNode(" 关闭 ")]), _: 1 }, 8, ["onClick"])]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogContent, { class: "max-w-[500px]" }, { default: withCtx(() => [createVNode($setup.DialogHeader, null, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("分享产品")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode(" 选择分享方式，让更多人了解这款产品 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "space-y-4 py-4" }, [createVNode("div", { class: "space-y-2" }, [createVNode("label", { class: "text-sm font-medium" }, "分享链接"), createVNode("div", { class: "flex gap-2" }, [createVNode($setup.Input, { value: $setup.shareUrl, readonly: "", class: "flex-1 bg-muted/50 text-sm" }, null, 8, ["value"]), createVNode($setup.Button, { variant: "outline", size: "sm", class: "px-3", onClick: $setup.handleCopyLink }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Copy", size: 16 })]), _: 1 })])]), createVNode("div", { class: "space-y-2" }, [createVNode("label", { class: "text-sm font-medium" }, "分享方式"), createVNode("div", { class: "grid grid-cols-3 gap-2" }, [createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareWeChat }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "MessageCircle", size: 24, class: "text-green-600" }), createVNode("span", { class: "text-xs" }, "微信")]), _: 1 }), createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleShareQQ }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Send", size: 24, class: "text-blue-600" }), createVNode("span", { class: "text-xs" }, "QQ")]), _: 1 }), createVNode($setup.Button, { variant: "outline", class: "flex flex-col items-center gap-2 h-auto py-4", onClick: $setup.handleCopyLink }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Link", size: 24, class: "text-primary" }), createVNode("span", { class: "text-xs" }, "复制链接")]), _: 1 })])]), createVNode("div", { class: "space-y-2 pt-2 border-t" }, [createVNode("label", { class: "text-sm font-medium" }, "二维码分享"), createVNode("div", { class: "flex justify-center p-4 bg-muted/30 rounded-lg" }, [$setup.miniCodeUrl ? (openBlock(), createBlock("img", { key: 0, src: $setup.miniCodeUrl, alt: "产品小程序码", class: "w-40 h-40 border border-border rounded object-contain bg-white" }, null, 8, ["src"])) : $setup.isLoadingShare ? (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Loader2", size: 28, class: "animate-spin text-muted-foreground" })) : (openBlock(), createBlock($setup.SafeIcon, { key: 2, name: "QrCode", size: 36, class: "text-muted-foreground" }))]), createVNode("p", { class: "text-xs text-muted-foreground text-center" }, toDisplayString($setup.miniPath || "扫描小程序码快速分享；网页链接可直接复制"), 1)])]), createVNode($setup.DialogFooter, null, { default: withCtx(() => [createVNode($setup.Button, { variant: "outline", onClick: ($event) => $setup.emit("update:open", false) }, { default: withCtx(() => [createTextVNode(" 关闭 ")]), _: 1 }, 8, ["onClick"])]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/product_detail/ShareDialog.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ShareDialog = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  ShareDialog as S
};
