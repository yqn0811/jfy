import { defineComponent, ref, onMounted, watch, useSSRContext, mergeProps, withCtx, createTextVNode, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString } from "vue";
import { p as pcApi, e as DialogFooter, f as DialogDescription, D as DialogTitle, a as DialogHeader, b as DialogContent, c as Dialog, d as authStore } from "./DialogTrigger.puU1MCQr.js";
import { B as Button } from "./index.CiCxTEA9.js";
import { S as SafeIcon, _ as _export_sfc } from "./SafeIcon.D7kIP4uZ.js";
import { toast } from "vue-sonner";
import { ssrRenderComponent, ssrRenderAttr, ssrRenderClass, ssrInterpolate } from "vue/server-renderer";
const _sfc_main = defineComponent({ __name: "LoginDialog", props: { open: { type: Boolean } }, emits: ["update:open", "login-success"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, isClient = ref(true), loginStatus = ref("loading"), qrCodeUrl = ref(""), scene = ref(""), loginError = ref("");
  let pollTimer = null;
  const stopPolling = () => {
    pollTimer && (window.clearInterval(pollTimer), pollTimer = null);
  }, resolveQrPayload = (data) => {
    qrCodeUrl.value = data?.qrcode || data?.qrcode_url || data?.data_url || data?.url || data?.image || data?.image_url || "", scene.value = data?.scene || data?.ticket || data?.key || data?.uuid || "";
  }, completeLogin = async (token, user = null) => {
    authStore.setToken(token), user && authStore.setUser(user);
    try {
      const profile = await pcApi.getCurrentUser();
      authStore.setUser(profile);
    } catch {
    }
    loginStatus.value = "success", toast.success("登录成功"), stopPolling(), setTimeout(() => {
      emit("update:open", false), emit("login-success"), loginStatus.value = "scanning";
    }, 800);
  }, startPolling = () => {
    stopPolling(), scene.value && (pollTimer = window.setInterval(async () => {
      try {
        const data = await pcApi.checkLoginStatus(scene.value), status = String(data?.status || data?.login_status || "").toLowerCase(), token = data?.token || data?.access_token || data?.authorization;
        if (token) {
          await completeLogin(token, data?.user || data?.user_info || null);
          return;
        }
        (status === "expired" || Number(data?.expired || 0) === 1) && (loginStatus.value = "expired", stopPolling());
      } catch (error) {
        const message = error?.message || "";
        (message.includes("过期") || message.includes("失效")) && (loginStatus.value = "expired", stopPolling());
      }
    }, 1800));
  }, loadQrcode = async () => {
    loginStatus.value = "loading", loginError.value = "", stopPolling();
    try {
      const data = await pcApi.getLoginQrcode();
      if (resolveQrPayload(data), !qrCodeUrl.value) throw new Error("二维码生成失败");
      loginStatus.value = "scanning", startPolling();
    } catch (error) {
      loginStatus.value = "expired";
      const message = error?.message || "";
      loginError.value = message.includes("48001") || message.includes("api unauthorized") ? "当前微信登录二维码暂不可用，请联系管理员开通公众号二维码权限。" : message || "二维码生成失败，请稍后重试。", toast.error(loginError.value);
    }
  };
  onMounted(() => {
    isClient.value = false, setTimeout(() => {
      isClient.value = true;
    }, 0);
  }), watch(() => props.open, (open) => {
    open ? loadQrcode() : stopPolling();
  }, { immediate: true });
  const __returned__ = { props, emit, isClient, loginStatus, qrCodeUrl, scene, loginError, get pollTimer() {
    return pollTimer;
  }, set pollTimer(v) {
    pollTimer = v;
  }, stopPolling, resolveQrPayload, completeLogin, startPolling, loadQrcode, handleRefresh: () => {
    loadQrcode();
  }, handleUpdateOpen: (value) => {
    emit("update:open", value);
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
  }, SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Dialog, mergeProps({ open: $props.open, "onUpdate:open": $setup.handleUpdateOpen }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogContent, { class: "sm:max-w-[400px] gap-6" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, { class: "text-xl" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("微信扫码安全登录");
          else return [createTextVNode("微信扫码安全登录")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.DialogDescription, { class: "text-sm" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 扫码关注公众号，实时接收订单及产品更新通知 ");
          else return [createTextVNode(" 扫码关注公众号，实时接收订单及产品更新通知 ")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码安全登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 扫码关注公众号，实时接收订单及产品更新通知 ")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="flex flex-col items-center justify-center space-y-4 py-4"${_scopeId2}><div class="relative w-48 h-48 border border-border rounded-lg bg-white p-2 flex items-center justify-center overflow-hidden"${_scopeId2}>`), $setup.loginStatus !== "success" && $setup.qrCodeUrl ? _push3(`<img${ssrRenderAttr("src", $setup.qrCodeUrl)} alt="WeChat Login QR Code" class="${ssrRenderClass([$setup.loginStatus === "expired" && "blur-[2px] opacity-20", "w-full h-full object-contain"])}"${_scopeId2}>`) : $setup.loginStatus === "loading" ? (_push3(`<div class="flex flex-col items-center text-muted-foreground"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 30, class: "animate-spin mb-2" }, null, _parent3, _scopeId2)), _push3(`<p class="text-sm"${_scopeId2}>二维码生成中</p></div>`)) : _push3("<!---->"), $setup.loginStatus === "success" ? (_push3(`<div class="flex flex-col items-center animate-in fade-in zoom-in duration-300"${_scopeId2}><div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-3"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "Check", size: 32, class: "text-primary" }, null, _parent3, _scopeId2)), _push3(`</div><p class="text-primary font-medium"${_scopeId2}>登录成功</p></div>`)) : _push3("<!---->"), $setup.loginStatus === "expired" ? (_push3(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-background/60 backdrop-blur-[1px]"${_scopeId2}><p class="text-sm font-medium mb-3"${_scopeId2}>${ssrInterpolate($setup.loginError || "二维码已失效")}</p>`), _push3(ssrRenderComponent($setup.Button, { size: "sm", variant: "primary", onClick: $setup.handleRefresh }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 点击刷新 ");
        else return [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 点击刷新 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3("</div>")) : _push3("<!---->"), _push3(`</div><div class="flex flex-col items-center space-y-2"${_scopeId2}>`), $setup.loginStatus === "scanning" ? (_push3(`<p class="text-sm flex items-center text-muted-foreground"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 14, class: "mr-2 animate-spin" }, null, _parent3, _scopeId2)), _push3(" 正在等待扫码... </p>")) : _push3("<!---->"), _push3("</div></div>"), _push3(ssrRenderComponent($setup.DialogFooter, { class: "sm:justify-center flex-col items-center gap-2 border-t pt-4" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(`<div class="flex items-center text-xs text-muted-foreground"${_scopeId3}>`), _push4(ssrRenderComponent($setup.SafeIcon, { name: "ShieldCheck", size: 12, class: "mr-1 text-primary" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>家纺云技术提供安全加密保护</span></div>`);
        else return [createVNode("div", { class: "flex items-center text-xs text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "ShieldCheck", size: 12, class: "mr-1 text-primary" }), createVNode("span", null, "家纺云技术提供安全加密保护")])];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx(() => [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码安全登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 扫码关注公众号，实时接收订单及产品更新通知 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "flex flex-col items-center justify-center space-y-4 py-4" }, [createVNode("div", { class: "relative w-48 h-48 border border-border rounded-lg bg-white p-2 flex items-center justify-center overflow-hidden" }, [$setup.loginStatus !== "success" && $setup.qrCodeUrl ? (openBlock(), createBlock("img", { key: 0, src: $setup.qrCodeUrl, alt: "WeChat Login QR Code", class: ["w-full h-full object-contain", $setup.loginStatus === "expired" && "blur-[2px] opacity-20"] }, null, 10, ["src"])) : $setup.loginStatus === "loading" ? (openBlock(), createBlock("div", { key: 1, class: "flex flex-col items-center text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 30, class: "animate-spin mb-2" }), createVNode("p", { class: "text-sm" }, "二维码生成中")])) : createCommentVNode("", true), $setup.loginStatus === "success" ? (openBlock(), createBlock("div", { key: 2, class: "flex flex-col items-center animate-in fade-in zoom-in duration-300" }, [createVNode("div", { class: "w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-3" }, [createVNode($setup.SafeIcon, { name: "Check", size: 32, class: "text-primary" })]), createVNode("p", { class: "text-primary font-medium" }, "登录成功")])) : createCommentVNode("", true), $setup.loginStatus === "expired" ? (openBlock(), createBlock("div", { key: 3, class: "absolute inset-0 flex flex-col items-center justify-center bg-background/60 backdrop-blur-[1px]" }, [createVNode("p", { class: "text-sm font-medium mb-3" }, toDisplayString($setup.loginError || "二维码已失效"), 1), createVNode($setup.Button, { size: "sm", variant: "primary", onClick: $setup.handleRefresh }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 点击刷新 ")]), _: 1 })])) : createCommentVNode("", true)]), createVNode("div", { class: "flex flex-col items-center space-y-2" }, [$setup.loginStatus === "scanning" ? (openBlock(), createBlock("p", { key: 0, class: "text-sm flex items-center text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 14, class: "mr-2 animate-spin" }), createTextVNode(" 正在等待扫码... ")])) : createCommentVNode("", true)])]), createVNode($setup.DialogFooter, { class: "sm:justify-center flex-col items-center gap-2 border-t pt-4" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center text-xs text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "ShieldCheck", size: 12, class: "mr-1 text-primary" }), createVNode("span", null, "家纺云技术提供安全加密保护")])]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogContent, { class: "sm:max-w-[400px] gap-6" }, { default: withCtx(() => [createVNode($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx(() => [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码安全登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 扫码关注公众号，实时接收订单及产品更新通知 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "flex flex-col items-center justify-center space-y-4 py-4" }, [createVNode("div", { class: "relative w-48 h-48 border border-border rounded-lg bg-white p-2 flex items-center justify-center overflow-hidden" }, [$setup.loginStatus !== "success" && $setup.qrCodeUrl ? (openBlock(), createBlock("img", { key: 0, src: $setup.qrCodeUrl, alt: "WeChat Login QR Code", class: ["w-full h-full object-contain", $setup.loginStatus === "expired" && "blur-[2px] opacity-20"] }, null, 10, ["src"])) : $setup.loginStatus === "loading" ? (openBlock(), createBlock("div", { key: 1, class: "flex flex-col items-center text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 30, class: "animate-spin mb-2" }), createVNode("p", { class: "text-sm" }, "二维码生成中")])) : createCommentVNode("", true), $setup.loginStatus === "success" ? (openBlock(), createBlock("div", { key: 2, class: "flex flex-col items-center animate-in fade-in zoom-in duration-300" }, [createVNode("div", { class: "w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-3" }, [createVNode($setup.SafeIcon, { name: "Check", size: 32, class: "text-primary" })]), createVNode("p", { class: "text-primary font-medium" }, "登录成功")])) : createCommentVNode("", true), $setup.loginStatus === "expired" ? (openBlock(), createBlock("div", { key: 3, class: "absolute inset-0 flex flex-col items-center justify-center bg-background/60 backdrop-blur-[1px]" }, [createVNode("p", { class: "text-sm font-medium mb-3" }, toDisplayString($setup.loginError || "二维码已失效"), 1), createVNode($setup.Button, { size: "sm", variant: "primary", onClick: $setup.handleRefresh }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 点击刷新 ")]), _: 1 })])) : createCommentVNode("", true)]), createVNode("div", { class: "flex flex-col items-center space-y-2" }, [$setup.loginStatus === "scanning" ? (openBlock(), createBlock("p", { key: 0, class: "text-sm flex items-center text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 14, class: "mr-2 animate-spin" }), createTextVNode(" 正在等待扫码... ")])) : createCommentVNode("", true)])]), createVNode($setup.DialogFooter, { class: "sm:justify-center flex-col items-center gap-2 border-t pt-4" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center text-xs text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "ShieldCheck", size: 12, class: "mr-1 text-primary" }), createVNode("span", null, "家纺云技术提供安全加密保护")])]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/LoginDialog.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const LoginDialog = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  LoginDialog as L
};
