import { defineComponent, ref, watch, onBeforeUnmount, useSSRContext, nextTick, mergeProps, withCtx, createTextVNode, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString } from "vue";
import { i as isMockEnabled, p as pcApi, f as DialogDescription, D as DialogTitle, a as DialogHeader, b as DialogContent, c as Dialog } from "./DialogTrigger.2nV2h0RV.js";
import { B as Button } from "./index.CZvBHhPq.js";
import { S as SafeIcon } from "./SafeIcon.Qdz7K7DD.js";
import { toast } from "vue-sonner";
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.CpC1g6Ns.js";
const _sfc_main = defineComponent({ __name: "LoginDialog", props: { open: { type: Boolean } }, emits: ["update:open", "login-success"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, status = ref("loading"), loginError = ref(""), authUrl = ref(""), containerId = `wx-login-${Math.random().toString(36).slice(2)}`;
  let scriptPollTimer = null;
  const stopWaitingScript = () => {
    scriptPollTimer && (window.clearInterval(scriptPollTimer), scriptPollTimer = null);
  }, getRedirectUrl = () => typeof window > "u" ? "" : window.location.href, renderWxLogin = (config) => {
    const container = document.getElementById(containerId);
    return !container || (container.innerHTML = "", !window.WxLogin) ? false : (new window.WxLogin({ self_redirect: false, id: containerId, appid: config.appid, scope: config.scope || "snsapi_login", redirect_uri: encodeURIComponent(config.redirect_uri), state: config.state, style: "", href: "" }), status.value = "ready", true);
  }, openAuthUrl = () => {
    authUrl.value && (window.location.href = authUrl.value);
  }, enterLocalMock = async () => {
    try {
      const data = await pcApi.checkLoginStatus("mock_scene"), token = data?.token || data?.access_token || data?.authorization;
      if (!token) throw new Error("本地登录失败");
      if (localStorage.setItem("jfyuntu_pc_token", token), localStorage.setItem("token", token), data?.user || data?.user_info) {
        const user = JSON.stringify(data.user || data.user_info);
        localStorage.setItem("jfyuntu_pc_user", user), localStorage.setItem("userInfo", user);
      }
      emit("update:open", false), emit("login-success");
    } catch (error) {
      toast.error(error?.message || "本地登录失败");
    }
  }, loadOauthLogin = async () => {
    if (stopWaitingScript(), loginError.value = "", authUrl.value = "", status.value = "loading", isMockEnabled()) {
      status.value = "local";
      return;
    }
    try {
      const data = await pcApi.getLoginOauthConfig(getRedirectUrl());
      if (authUrl.value = data?.auth_url || "", !data?.appid || !data?.redirect_uri || !data?.state) throw new Error("微信登录配置缺失");
      if (await nextTick(), renderWxLogin(data)) return;
      let retryTimes = 0;
      scriptPollTimer = window.setInterval(() => {
        retryTimes += 1, (renderWxLogin(data) || retryTimes >= 20) && (stopWaitingScript(), retryTimes >= 20 && !window.WxLogin && (status.value = authUrl.value ? "ready" : "error", authUrl.value || (loginError.value = "微信登录组件加载失败，请刷新页面重试")));
      }, 250);
    } catch (error) {
      status.value = "error", loginError.value = error?.message || "微信登录初始化失败，请稍后重试", toast.error(loginError.value);
    }
  };
  watch(() => props.open, (open) => {
    open ? loadOauthLogin() : stopWaitingScript();
  }, { immediate: true }), onBeforeUnmount(stopWaitingScript);
  const __returned__ = { props, emit, status, loginError, authUrl, containerId, get scriptPollTimer() {
    return scriptPollTimer;
  }, set scriptPollTimer(v) {
    scriptPollTimer = v;
  }, stopWaitingScript, getRedirectUrl, renderWxLogin, openAuthUrl, enterLocalMock, loadOauthLogin, get Dialog() {
    return Dialog;
  }, get DialogContent() {
    return DialogContent;
  }, get DialogHeader() {
    return DialogHeader;
  }, get DialogTitle() {
    return DialogTitle;
  }, get DialogDescription() {
    return DialogDescription;
  }, get Button() {
    return Button;
  }, SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Dialog, mergeProps({ open: $props.open, "onUpdate:open": ($event) => $setup.emit("update:open", $event) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogContent, { class: "sm:max-w-[430px] gap-6" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, { class: "text-xl" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("微信扫码登录");
          else return [createTextVNode("微信扫码登录")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.DialogDescription, { class: "text-sm" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 使用微信扫码后自动进入 ");
          else return [createTextVNode(" 使用微信扫码后自动进入 ")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 使用微信扫码后自动进入 ")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="flex flex-col items-center justify-center gap-4 py-2"${_scopeId2}><div class="relative h-[310px] w-[310px] overflow-hidden rounded-lg border border-border bg-white"${_scopeId2}><div${ssrRenderAttr("id", $setup.containerId)} class="h-full w-full"${_scopeId2}></div>`), $setup.status === "loading" ? (_push3(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-white text-muted-foreground"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 30, class: "mb-2 animate-spin" }, null, _parent3, _scopeId2)), _push3(`<p class="text-sm"${_scopeId2}>正在加载微信登录</p></div>`)) : $setup.status === "local" ? (_push3(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "MonitorCog", size: 34, class: "mb-3 text-primary" }, null, _parent3, _scopeId2)), _push3(`<p class="text-sm font-medium text-foreground"${_scopeId2}>本地开发环境</p><p class="mt-1 text-xs text-muted-foreground"${_scopeId2}>微信回调不支持 localhost，可使用本地会话预览页面</p>`), _push3(ssrRenderComponent($setup.Button, { class: "mt-4", size: "sm", onClick: $setup.enterLocalMock }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(" 进入本地预览 ");
        else return [createTextVNode(" 进入本地预览 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3("</div>")) : $setup.status === "error" ? (_push3(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center"${_scopeId2}>`), _push3(ssrRenderComponent($setup.SafeIcon, { name: "CircleAlert", size: 34, class: "mb-3 text-destructive" }, null, _parent3, _scopeId2)), _push3(`<p class="text-sm font-medium text-foreground"${_scopeId2}>${ssrInterpolate($setup.loginError || "微信登录加载失败")}</p>`), _push3(ssrRenderComponent($setup.Button, { class: "mt-4", size: "sm", variant: "outline", onClick: $setup.loadOauthLogin }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 重新加载 ");
        else return [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 重新加载 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3("</div>")) : _push3("<!---->"), _push3("</div>"), $setup.authUrl && $setup.status !== "local" ? _push3(ssrRenderComponent($setup.Button, { variant: "outline", class: "w-[310px]", onClick: $setup.openAuthUrl }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "ExternalLink", size: 14, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 打开微信登录页面 ");
        else return [createVNode($setup.SafeIcon, { name: "ExternalLink", size: 14, class: "mr-2" }), createTextVNode(" 打开微信登录页面 ")];
      }), _: 1 }, _parent3, _scopeId2)) : _push3("<!---->"), _push3("</div>");
      else return [createVNode($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx(() => [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 使用微信扫码后自动进入 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "flex flex-col items-center justify-center gap-4 py-2" }, [createVNode("div", { class: "relative h-[310px] w-[310px] overflow-hidden rounded-lg border border-border bg-white" }, [createVNode("div", { id: $setup.containerId, class: "h-full w-full" }), $setup.status === "loading" ? (openBlock(), createBlock("div", { key: 0, class: "absolute inset-0 flex flex-col items-center justify-center bg-white text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 30, class: "mb-2 animate-spin" }), createVNode("p", { class: "text-sm" }, "正在加载微信登录")])) : $setup.status === "local" ? (openBlock(), createBlock("div", { key: 1, class: "absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center" }, [createVNode($setup.SafeIcon, { name: "MonitorCog", size: 34, class: "mb-3 text-primary" }), createVNode("p", { class: "text-sm font-medium text-foreground" }, "本地开发环境"), createVNode("p", { class: "mt-1 text-xs text-muted-foreground" }, "微信回调不支持 localhost，可使用本地会话预览页面"), createVNode($setup.Button, { class: "mt-4", size: "sm", onClick: $setup.enterLocalMock }, { default: withCtx(() => [createTextVNode(" 进入本地预览 ")]), _: 1 })])) : $setup.status === "error" ? (openBlock(), createBlock("div", { key: 2, class: "absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center" }, [createVNode($setup.SafeIcon, { name: "CircleAlert", size: 34, class: "mb-3 text-destructive" }), createVNode("p", { class: "text-sm font-medium text-foreground" }, toDisplayString($setup.loginError || "微信登录加载失败"), 1), createVNode($setup.Button, { class: "mt-4", size: "sm", variant: "outline", onClick: $setup.loadOauthLogin }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 重新加载 ")]), _: 1 })])) : createCommentVNode("", true)]), $setup.authUrl && $setup.status !== "local" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", class: "w-[310px]", onClick: $setup.openAuthUrl }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "ExternalLink", size: 14, class: "mr-2" }), createTextVNode(" 打开微信登录页面 ")]), _: 1 })) : createCommentVNode("", true)])];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogContent, { class: "sm:max-w-[430px] gap-6" }, { default: withCtx(() => [createVNode($setup.DialogHeader, { class: "items-center text-center" }, { default: withCtx(() => [createVNode($setup.DialogTitle, { class: "text-xl" }, { default: withCtx(() => [createTextVNode("微信扫码登录")]), _: 1 }), createVNode($setup.DialogDescription, { class: "text-sm" }, { default: withCtx(() => [createTextVNode(" 使用微信扫码后自动进入 ")]), _: 1 })]), _: 1 }), createVNode("div", { class: "flex flex-col items-center justify-center gap-4 py-2" }, [createVNode("div", { class: "relative h-[310px] w-[310px] overflow-hidden rounded-lg border border-border bg-white" }, [createVNode("div", { id: $setup.containerId, class: "h-full w-full" }), $setup.status === "loading" ? (openBlock(), createBlock("div", { key: 0, class: "absolute inset-0 flex flex-col items-center justify-center bg-white text-muted-foreground" }, [createVNode($setup.SafeIcon, { name: "Loader2", size: 30, class: "mb-2 animate-spin" }), createVNode("p", { class: "text-sm" }, "正在加载微信登录")])) : $setup.status === "local" ? (openBlock(), createBlock("div", { key: 1, class: "absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center" }, [createVNode($setup.SafeIcon, { name: "MonitorCog", size: 34, class: "mb-3 text-primary" }), createVNode("p", { class: "text-sm font-medium text-foreground" }, "本地开发环境"), createVNode("p", { class: "mt-1 text-xs text-muted-foreground" }, "微信回调不支持 localhost，可使用本地会话预览页面"), createVNode($setup.Button, { class: "mt-4", size: "sm", onClick: $setup.enterLocalMock }, { default: withCtx(() => [createTextVNode(" 进入本地预览 ")]), _: 1 })])) : $setup.status === "error" ? (openBlock(), createBlock("div", { key: 2, class: "absolute inset-0 flex flex-col items-center justify-center bg-white p-6 text-center" }, [createVNode($setup.SafeIcon, { name: "CircleAlert", size: 34, class: "mb-3 text-destructive" }), createVNode("p", { class: "text-sm font-medium text-foreground" }, toDisplayString($setup.loginError || "微信登录加载失败"), 1), createVNode($setup.Button, { class: "mt-4", size: "sm", variant: "outline", onClick: $setup.loadOauthLogin }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "RefreshCw", size: 14, class: "mr-2" }), createTextVNode(" 重新加载 ")]), _: 1 })])) : createCommentVNode("", true)]), $setup.authUrl && $setup.status !== "local" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", class: "w-[310px]", onClick: $setup.openAuthUrl }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "ExternalLink", size: 14, class: "mr-2" }), createTextVNode(" 打开微信登录页面 ")]), _: 1 })) : createCommentVNode("", true)])]), _: 1 })];
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
