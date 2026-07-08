import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot$1 } from "./astro/server.D8EH5g9Z.js";
import { _ as _export_sfc, S as SafeIcon, $ as $$BaseLayout } from "./SafeIcon.IqZVWxMk.js";
import { defineComponent, useSSRContext, mergeProps, withCtx, createVNode, ref, computed, onMounted, createTextVNode, toDisplayString, renderSlot, resolveDynamicComponent, createBlock, openBlock, Fragment, renderList } from "vue";
import { c as cn, B as Button } from "./index.DRLhNP3M.js";
import { reactiveOmit, useMediaQuery, useVModel, useEventListener, defaultDocument } from "@vueuse/core";
import { ProgressRoot, ProgressIndicator, createContext, Primitive, useForwardPropsEmits, TooltipRoot, TooltipPortal, TooltipContent as TooltipContent$1, TooltipProvider, TooltipTrigger as TooltipTrigger$1, Separator as Separator$1 } from "reka-ui";
import { ssrRenderComponent, ssrRenderAttrs, ssrInterpolate, ssrRenderSlot, ssrRenderAttr, ssrRenderVNode, ssrRenderList } from "vue/server-renderer";
import { A as AvatarFallback, a as AvatarImage, b as Avatar, D as DropdownMenuTrigger, f as DropdownMenuSeparator, g as DropdownMenuLabel, c as DropdownMenuItem, d as DropdownMenuContent, e as DropdownMenu } from "./index.DawGBfjz.js";
import { d as authStore, i as isLocalMockEnabled, p as pcApi } from "./DialogTrigger.BEhXrbNy.js";
import { cva } from "class-variance-authority";
import { d as SheetContent, e as Sheet } from "./index.By0E1SZv.js";
import { I as Input } from "./Input.Tp1AvTv4.js";
import { PanelLeft } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { L as LoginDialog } from "./LoginDialog.DYl3MOAW.js";
const _sfc_main$y = defineComponent({ __name: "Progress", props: { modelValue: { default: 0 }, max: {}, getValueLabel: {}, getValueText: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get ProgressIndicator() {
    return ProgressIndicator;
  }, get ProgressRoot() {
    return ProgressRoot;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$x(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.ProgressRoot, mergeProps($setup.delegatedProps, { class: $setup.cn("relative h-4 w-full overflow-hidden rounded-full bg-secondary", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.ProgressIndicator, { class: "h-full w-full flex-1 bg-primary transition-all", style: `transform: translateX(-${100 - ($setup.props.modelValue ?? 0)}%);` }, null, _parent2, _scopeId));
    else return [createVNode($setup.ProgressIndicator, { class: "h-full w-full flex-1 bg-primary transition-all", style: `transform: translateX(-${100 - ($setup.props.modelValue ?? 0)}%);` }, null, 8, ["style"])];
  }), _: 1 }, _parent));
}
const _sfc_setup$y = _sfc_main$y.setup;
_sfc_main$y.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/progress/Progress.vue"), _sfc_setup$y ? _sfc_setup$y(props, ctx) : void 0;
};
const Progress = _export_sfc(_sfc_main$y, [["ssrRender", _sfc_ssrRender$x]]);
const _sfc_main$x = defineComponent({ __name: "ManagementHeader", props: { usedStorage: { default: "1.2 GB" }, totalStorage: { default: "5.0 GB" }, remainingStorage: { default: "3.8 GB" } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, userInfo = ref({}), storagePercent = computed(() => {
    const value = Number(userInfo.value?.space_used ?? 0);
    return Number.isFinite(value) ? Math.min(Math.max(value, 0), 100) : 0;
  }), displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || "家纺云用户"), avatarUrl = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || ""), totalStorageText = computed(() => userInfo.value?.all_space || props.totalStorage), usedStorageText = computed(() => {
    const bytes = Number(userInfo.value?.use_space || 0);
    if (!bytes) return props.usedStorage;
    const mb = bytes / 1024 / 1024;
    return mb >= 1024 ? `${(mb / 1024).toFixed(2)} GB` : `${mb.toFixed(2)} MB`;
  });
  onMounted(async () => {
    if (!(!authStore.isLoggedIn() && !isLocalMockEnabled())) try {
      const profile = await pcApi.getCurrentUser();
      userInfo.value = profile || {}, authStore.setUser(profile);
    } catch {
    }
  });
  const __returned__ = { props, userInfo, storagePercent, displayName, avatarUrl, totalStorageText, usedStorageText, handlePreview: () => {
    const uid = userInfo.value?.id || userInfo.value?.uid || "";
    window.location.href = `./share-home.html${uid ? `?uid=${uid}` : ""}`;
  }, handleUpgrade: () => {
    window.location.href = "./billing-usage.html";
  }, handleLogout: () => {
    authStore.clearToken(), userInfo.value = {}, window.location.href = "./share-home.html";
  }, goToWorkbench: () => {
    window.location.href = "./management-workbench.html";
  }, get Button() {
    return Button;
  }, get Progress() {
    return Progress;
  }, get DropdownMenu() {
    return DropdownMenu;
  }, get DropdownMenuContent() {
    return DropdownMenuContent;
  }, get DropdownMenuItem() {
    return DropdownMenuItem;
  }, get DropdownMenuLabel() {
    return DropdownMenuLabel;
  }, get DropdownMenuSeparator() {
    return DropdownMenuSeparator;
  }, get DropdownMenuTrigger() {
    return DropdownMenuTrigger;
  }, get Avatar() {
    return Avatar;
  }, get AvatarImage() {
    return AvatarImage;
  }, get AvatarFallback() {
    return AvatarFallback;
  }, SafeIcon };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$w(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<header${ssrRenderAttrs(mergeProps({ class: "h-[var(--header-height)] border-b border-border bg-card px-6 flex items-center justify-between shrink-0 z-10" }, _attrs))}><div class="flex items-center gap-8"><div class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"><div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">`), _push(ssrRenderComponent($setup.SafeIcon, { name: "LayoutDashboard", size: 20, class: "text-primary-foreground" }, null, _parent)), _push(`</div><h1 class="text-lg font-bold tracking-tight">产品工作台</h1></div><div class="hidden lg:flex items-center gap-4 border-l border-border pl-8"><div class="flex flex-col gap-1 w-48"><div class="flex justify-between text-[10px] text-muted-foreground uppercase font-bold"><span>存储空间</span><span>${ssrInterpolate($setup.usedStorageText)} / ${ssrInterpolate($setup.totalStorageText)}</span></div>`), _push(ssrRenderComponent($setup.Progress, { "model-value": $setup.storagePercent, class: "h-1.5" }, null, _parent)), _push("</div>"), _push(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", class: "h-8 text-xs", onClick: $setup.handleUpgrade }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "TrendingUp", size: 14, class: "mr-1" }, null, _parent2, _scopeId)), _push2(" 升级容量 ");
    else return [createVNode($setup.SafeIcon, { name: "TrendingUp", size: 14, class: "mr-1" }), createTextVNode(" 升级容量 ")];
  }), _: 1 }, _parent)), _push('</div></div><div class="flex items-center gap-3">'), _push(ssrRenderComponent($setup.Button, { variant: "ghost", size: "sm", onClick: $setup.handlePreview }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "Eye", size: 18, class: "mr-2" }, null, _parent2, _scopeId)), _push2(" 预览主页 ");
    else return [createVNode($setup.SafeIcon, { name: "Eye", size: 18, class: "mr-2" }), createTextVNode(" 预览主页 ")];
  }), _: 1 }, _parent)), _push(ssrRenderComponent($setup.DropdownMenu, null, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Button, { variant: "ghost", class: "relative h-9 w-9 rounded-full" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.Avatar, { class: "h-9 w-9" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(ssrRenderComponent($setup.AvatarImage, { src: $setup.avatarUrl, alt: "User" }, null, _parent5, _scopeId4)), _push5(ssrRenderComponent($setup.AvatarFallback, null, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
            if (_push6) _push6(`${ssrInterpolate($setup.displayName.substring(0, 1))}`);
            else return [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)];
          }), _: 1 }, _parent5, _scopeId4));
          else return [createVNode($setup.AvatarImage, { src: $setup.avatarUrl, alt: "User" }, null, 8, ["src"]), createVNode($setup.AvatarFallback, null, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.Avatar, { class: "h-9 w-9" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.avatarUrl, alt: "User" }, null, 8, ["src"]), createVNode($setup.AvatarFallback, null, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.Button, { variant: "ghost", class: "relative h-9 w-9 rounded-full" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-9 w-9" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.avatarUrl, alt: "User" }, null, 8, ["src"]), createVNode($setup.AvatarFallback, null, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(`<div class="flex flex-col space-y-1"${_scopeId3}><p class="text-sm font-medium leading-none"${_scopeId3}>${ssrInterpolate($setup.displayName)}</p><p class="text-xs leading-none text-muted-foreground"${_scopeId3}>家纺云产品工作台</p></div>`);
        else return [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "家纺云产品工作台")])];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuSeparator, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { onClick: $setup.goToWorkbench }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LayoutDashboard", size: 16, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 管理工作台 ");
        else return [createVNode($setup.SafeIcon, { name: "LayoutDashboard", size: 16, class: "mr-2" }), createTextVNode(" 管理工作台 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { onClick: $setup.handleUpgrade }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "CreditCard", size: 16, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 版本与账单 ");
        else return [createVNode($setup.SafeIcon, { name: "CreditCard", size: 16, class: "mr-2" }), createTextVNode(" 版本与账单 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuSeparator, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { class: "text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LogOut", size: 16, class: "mr-2" }, null, _parent4, _scopeId3)), _push4(" 退出登录 ");
        else return [createVNode($setup.SafeIcon, { name: "LogOut", size: 16, class: "mr-2" }), createTextVNode(" 退出登录 ")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "家纺云产品工作台")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: $setup.goToWorkbench }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", size: 16, class: "mr-2" }), createTextVNode(" 管理工作台 ")]), _: 1 }), createVNode($setup.DropdownMenuItem, { onClick: $setup.handleUpgrade }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "CreditCard", size: 16, class: "mr-2" }), createTextVNode(" 版本与账单 ")]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { class: "text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", size: 16, class: "mr-2" }), createTextVNode(" 退出登录 ")]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.Button, { variant: "ghost", class: "relative h-9 w-9 rounded-full" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-9 w-9" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.avatarUrl, alt: "User" }, null, 8, ["src"]), createVNode($setup.AvatarFallback, null, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx(() => [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "家纺云产品工作台")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: $setup.goToWorkbench }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", size: 16, class: "mr-2" }), createTextVNode(" 管理工作台 ")]), _: 1 }), createVNode($setup.DropdownMenuItem, { onClick: $setup.handleUpgrade }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "CreditCard", size: 16, class: "mr-2" }), createTextVNode(" 版本与账单 ")]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { class: "text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", size: 16, class: "mr-2" }), createTextVNode(" 退出登录 ")]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div></header>");
}
const _sfc_setup$x = _sfc_main$x.setup;
_sfc_main$x.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/ManagementHeader.vue"), _sfc_setup$x ? _sfc_setup$x(props, ctx) : void 0;
};
const ManagementHeader = _export_sfc(_sfc_main$x, [["ssrRender", _sfc_ssrRender$w]]);
const SIDEBAR_COOKIE_NAME = "sidebar_state";
const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
const SIDEBAR_WIDTH = "16rem";
const SIDEBAR_WIDTH_MOBILE = "18rem";
const SIDEBAR_WIDTH_ICON = "3rem";
const SIDEBAR_KEYBOARD_SHORTCUT = "b";
const [useSidebar, provideSidebarContext] = createContext("Sidebar");
const _sfc_main$w = defineComponent({ inheritAttrs: false, __name: "Sidebar", props: { side: { default: "left" }, variant: { default: "sidebar" }, collapsible: { default: "offcanvas" }, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, { isMobile, state, openMobile, setOpenMobile } = useSidebar(), __returned__ = { props, isMobile, state, openMobile, setOpenMobile, get cn() {
    return cn;
  }, get Sheet() {
    return Sheet;
  }, get SheetContent() {
    return SheetContent;
  }, get SIDEBAR_WIDTH_MOBILE() {
    return SIDEBAR_WIDTH_MOBILE;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$v(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  $props.collapsible === "none" ? (_push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex h-full w-[--sidebar-width] flex-col bg-sidebar text-sidebar-foreground", $setup.props.class) }, _ctx.$attrs, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>")) : (_push(`<div${ssrRenderAttrs(_attrs)}>`), _push(ssrRenderComponent($setup.Sheet, mergeProps({ open: $setup.openMobile, class: "md:hidden" }, _ctx.$attrs, { "onUpdate:open": $setup.setOpenMobile }), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SheetContent, { "data-sidebar": "sidebar", "data-mobile": "true", side: $props.side, class: "w-[--sidebar-width] bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden", style: { "--sidebar-width": $setup.SIDEBAR_WIDTH_MOBILE } }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex h-full w-full flex-col"${_scopeId2}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3("</div>");
      else return [createVNode("div", { class: "flex h-full w-full flex-col" }, [renderSlot(_ctx.$slots, "default")])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.SheetContent, { "data-sidebar": "sidebar", "data-mobile": "true", side: $props.side, class: "w-[--sidebar-width] bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden", style: { "--sidebar-width": $setup.SIDEBAR_WIDTH_MOBILE } }, { default: withCtx(() => [createVNode("div", { class: "flex h-full w-full flex-col" }, [renderSlot(_ctx.$slots, "default")])]), _: 3 }, 8, ["side", "style"])];
  }), _: 3 }, _parent)), _push(`<div class="group peer hidden md:block"${ssrRenderAttr("data-state", $setup.state)}${ssrRenderAttr("data-collapsible", $setup.state === "collapsed" ? $props.collapsible : "")}${ssrRenderAttr("data-variant", $props.variant)}${ssrRenderAttr("data-side", $props.side)}><div${ssrRenderAttrs(mergeProps({ class: $setup.cn("duration-200 sticky top-0 h-full w-[--sidebar-width] overflow-hidden transition-[width] ease-linear md:flex flex-col", "group-data-[collapsible=offcanvas]:w-0", $props.variant === "floating" || $props.variant === "inset" ? "p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)_+_theme(spacing.4)_+_2px)]" : "group-data-[collapsible=icon]:w-[--sidebar-width-icon] group-data-[side=left]:border-r group-data-[side=right]:border-l", $setup.props.class) }, _ctx.$attrs))}><div data-sidebar="sidebar" class="flex h-full w-full flex-col text-sidebar-foreground bg-sidebar group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:border-sidebar-border group-data-[variant=floating]:shadow">`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div></div></div></div>"));
}
const _sfc_setup$w = _sfc_main$w.setup;
_sfc_main$w.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/Sidebar.vue"), _sfc_setup$w ? _sfc_setup$w(props, ctx) : void 0;
};
const Sidebar = _export_sfc(_sfc_main$w, [["ssrRender", _sfc_ssrRender$v]]);
const _sfc_main$v = defineComponent({ __name: "SidebarContent", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$u(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "content", class: $setup.cn("flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$v = _sfc_main$v.setup;
_sfc_main$v.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarContent.vue"), _sfc_setup$v ? _sfc_setup$v(props, ctx) : void 0;
};
const SidebarContent = _export_sfc(_sfc_main$v, [["ssrRender", _sfc_ssrRender$u]]);
const _sfc_main$u = defineComponent({ __name: "SidebarFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$t(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "footer", class: $setup.cn("flex flex-col gap-2 p-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$u = _sfc_main$u.setup;
_sfc_main$u.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarFooter.vue"), _sfc_setup$u ? _sfc_setup$u(props, ctx) : void 0;
};
_export_sfc(_sfc_main$u, [["ssrRender", _sfc_ssrRender$t]]);
const _sfc_main$t = defineComponent({ __name: "SidebarGroup", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$s(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "group", class: $setup.cn("relative flex w-full min-w-0 flex-col p-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$t = _sfc_main$t.setup;
_sfc_main$t.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarGroup.vue"), _sfc_setup$t ? _sfc_setup$t(props, ctx) : void 0;
};
const SidebarGroup = _export_sfc(_sfc_main$t, [["ssrRender", _sfc_ssrRender$s]]);
const _sfc_main$s = defineComponent({ __name: "SidebarGroupAction", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Primitive() {
    return Primitive;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$r(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Primitive, mergeProps({ "data-sidebar": "group-action", as: $props.as, "as-child": $props.asChild, class: $setup.cn("absolute right-3 top-3.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-none ring-sidebar-ring transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0", "after:absolute after:-inset-2 after:md:hidden", "group-data-[collapsible=icon]:hidden", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$s = _sfc_main$s.setup;
_sfc_main$s.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarGroupAction.vue"), _sfc_setup$s ? _sfc_setup$s(props, ctx) : void 0;
};
_export_sfc(_sfc_main$s, [["ssrRender", _sfc_ssrRender$r]]);
const _sfc_main$r = defineComponent({ __name: "SidebarGroupContent", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$q(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "group-content", class: $setup.cn("w-full text-sm", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$r = _sfc_main$r.setup;
_sfc_main$r.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarGroupContent.vue"), _sfc_setup$r ? _sfc_setup$r(props, ctx) : void 0;
};
const SidebarGroupContent = _export_sfc(_sfc_main$r, [["ssrRender", _sfc_ssrRender$q]]);
const _sfc_main$q = defineComponent({ __name: "SidebarGroupLabel", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Primitive() {
    return Primitive;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$p(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Primitive, mergeProps({ "data-sidebar": "group-label", as: $props.as, "as-child": $props.asChild, class: $setup.cn("duration-200 flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium text-sidebar-foreground/70 outline-none ring-sidebar-ring transition-[margin,opacity] ease-linear focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0", "group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$q = _sfc_main$q.setup;
_sfc_main$q.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarGroupLabel.vue"), _sfc_setup$q ? _sfc_setup$q(props, ctx) : void 0;
};
const SidebarGroupLabel = _export_sfc(_sfc_main$q, [["ssrRender", _sfc_ssrRender$p]]);
const _sfc_main$p = defineComponent({ __name: "SidebarHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$o(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "header", class: $setup.cn("flex flex-col gap-2 p-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$p = _sfc_main$p.setup;
_sfc_main$p.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarHeader.vue"), _sfc_setup$p ? _sfc_setup$p(props, ctx) : void 0;
};
_export_sfc(_sfc_main$p, [["ssrRender", _sfc_ssrRender$o]]);
const _sfc_main$o = defineComponent({ __name: "SidebarInput", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  }, get Input() {
    return Input;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$n(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Input, mergeProps({ "data-sidebar": "input", class: $setup.cn("h-8 w-full bg-background shadow-none focus-visible:ring-2 focus-visible:ring-sidebar-ring", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$o = _sfc_main$o.setup;
_sfc_main$o.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarInput.vue"), _sfc_setup$o ? _sfc_setup$o(props, ctx) : void 0;
};
_export_sfc(_sfc_main$o, [["ssrRender", _sfc_ssrRender$n]]);
const _sfc_main$n = defineComponent({ __name: "SidebarInset", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$m(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<main${ssrRenderAttrs(mergeProps({ class: $setup.cn("relative flex min-h-0 flex-1 flex-col bg-background", "md:peer-data-[variant=inset]:m-2 md:peer-data-[state=collapsed]:peer-data-[variant=inset]:ml-2 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</main>");
}
const _sfc_setup$n = _sfc_main$n.setup;
_sfc_main$n.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarInset.vue"), _sfc_setup$n ? _sfc_setup$n(props, ctx) : void 0;
};
const SidebarInset = _export_sfc(_sfc_main$n, [["ssrRender", _sfc_ssrRender$m]]);
const _sfc_main$m = defineComponent({ __name: "SidebarMenu", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$l(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<ul${ssrRenderAttrs(mergeProps({ "data-sidebar": "menu", class: $setup.cn("flex w-full min-w-0 flex-col gap-1", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</ul>");
}
const _sfc_setup$m = _sfc_main$m.setup;
_sfc_main$m.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenu.vue"), _sfc_setup$m ? _sfc_setup$m(props, ctx) : void 0;
};
const SidebarMenu = _export_sfc(_sfc_main$m, [["ssrRender", _sfc_ssrRender$l]]);
const _sfc_main$l = defineComponent({ __name: "SidebarMenuAction", props: { asChild: { type: Boolean }, as: { default: "button" }, showOnHover: { type: Boolean }, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Primitive() {
    return Primitive;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$k(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Primitive, mergeProps({ "data-sidebar": "menu-action", class: $setup.cn("absolute right-1 top-1.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-none ring-sidebar-ring transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 peer-hover/menu-button:text-sidebar-accent-foreground [&>svg]:size-4 [&>svg]:shrink-0", "after:absolute after:-inset-2 after:md:hidden", "peer-data-[size=sm]/menu-button:top-1", "peer-data-[size=default]/menu-button:top-1.5", "peer-data-[size=lg]/menu-button:top-2.5", "group-data-[collapsible=icon]:hidden", $props.showOnHover && "group-focus-within/menu-item:opacity-100 group-hover/menu-item:opacity-100 data-[state=open]:opacity-100 peer-data-[active=true]/menu-button:text-sidebar-accent-foreground md:opacity-0", $setup.props.class), as: $props.as, "as-child": $props.asChild }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$l = _sfc_main$l.setup;
_sfc_main$l.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuAction.vue"), _sfc_setup$l ? _sfc_setup$l(props, ctx) : void 0;
};
_export_sfc(_sfc_main$l, [["ssrRender", _sfc_ssrRender$k]]);
const _sfc_main$k = defineComponent({ __name: "SidebarMenuBadge", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$j(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "menu-badge", class: $setup.cn("absolute right-1 flex h-5 min-w-5 items-center justify-center rounded-md px-1 text-xs font-medium tabular-nums text-sidebar-foreground select-none pointer-events-none", "peer-hover/menu-button:text-sidebar-accent-foreground peer-data-[active=true]/menu-button:text-sidebar-accent-foreground", "peer-data-[size=sm]/menu-button:top-1", "peer-data-[size=default]/menu-button:top-1.5", "peer-data-[size=lg]/menu-button:top-2.5", "group-data-[collapsible=icon]:hidden", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$k = _sfc_main$k.setup;
_sfc_main$k.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuBadge.vue"), _sfc_setup$k ? _sfc_setup$k(props, ctx) : void 0;
};
_export_sfc(_sfc_main$k, [["ssrRender", _sfc_ssrRender$j]]);
const _sfc_main$j = defineComponent({ __name: "Tooltip", props: { defaultOpen: { type: Boolean }, open: { type: Boolean }, delayDuration: {}, disableHoverableContent: { type: Boolean }, disableClosingTrigger: { type: Boolean }, disabled: { type: Boolean }, ignoreNonKeyboardFocus: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get TooltipRoot() {
    return TooltipRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$i(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TooltipRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$j = _sfc_main$j.setup;
_sfc_main$j.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tooltip/Tooltip.vue"), _sfc_setup$j ? _sfc_setup$j(props, ctx) : void 0;
};
const Tooltip = _export_sfc(_sfc_main$j, [["ssrRender", _sfc_ssrRender$i]]);
const _sfc_main$i = defineComponent({ inheritAttrs: false, __name: "TooltipContent", props: { forceMount: { type: Boolean }, ariaLabel: {}, asChild: { type: Boolean }, as: {}, side: {}, sideOffset: { default: 4 }, align: {}, alignOffset: {}, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get TooltipContent() {
    return TooltipContent$1;
  }, get TooltipPortal() {
    return TooltipPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$h(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TooltipPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TooltipContent, mergeProps({ ...$setup.forwarded, ..._ctx.$attrs }, { class: $setup.cn("z-50 overflow-hidden rounded-md border bg-popover px-3 py-1.5 text-sm text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.TooltipContent, mergeProps({ ...$setup.forwarded, ..._ctx.$attrs }, { class: $setup.cn("z-50 overflow-hidden rounded-md border bg-popover px-3 py-1.5 text-sm text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$i = _sfc_main$i.setup;
_sfc_main$i.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tooltip/TooltipContent.vue"), _sfc_setup$i ? _sfc_setup$i(props, ctx) : void 0;
};
const TooltipContent = _export_sfc(_sfc_main$i, [["ssrRender", _sfc_ssrRender$h]]);
const _sfc_main$h = defineComponent({ __name: "TooltipProvider", props: { delayDuration: {}, skipDelayDuration: {}, disableHoverableContent: { type: Boolean }, disableClosingTrigger: { type: Boolean }, disabled: { type: Boolean }, ignoreNonKeyboardFocus: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get TooltipProvider() {
    return TooltipProvider;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$g(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TooltipProvider, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tooltip/TooltipProvider.vue"), _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
_export_sfc(_sfc_main$h, [["ssrRender", _sfc_ssrRender$g]]);
const _sfc_main$g = defineComponent({ __name: "TooltipTrigger", props: { reference: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get TooltipTrigger() {
    return TooltipTrigger$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$f(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TooltipTrigger, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tooltip/TooltipTrigger.vue"), _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const TooltipTrigger = _export_sfc(_sfc_main$g, [["ssrRender", _sfc_ssrRender$f]]);
const _sfc_main$f = defineComponent({ __name: "SidebarMenuButtonChild", props: { variant: { default: "default" }, size: { default: "default" }, isActive: { type: Boolean }, class: {}, asChild: { type: Boolean }, as: { default: "button" } }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Primitive() {
    return Primitive;
  }, get cn() {
    return cn;
  }, get sidebarMenuButtonVariants() {
    return sidebarMenuButtonVariants;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$e(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Primitive, mergeProps({ "data-sidebar": "menu-button", "data-size": $props.size, "data-active": $props.isActive, class: $setup.cn($setup.sidebarMenuButtonVariants({ variant: $props.variant, size: $props.size }), $setup.props.class), as: $props.as, "as-child": $props.asChild }, _ctx.$attrs, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuButtonChild.vue"), _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const SidebarMenuButtonChild = _export_sfc(_sfc_main$f, [["ssrRender", _sfc_ssrRender$e]]);
const _sfc_main$e = defineComponent({ inheritAttrs: false, __name: "SidebarMenuButton", props: { variant: { default: "default" }, size: { default: "default" }, isActive: { type: Boolean }, class: {}, asChild: { type: Boolean }, as: { default: "button" }, tooltip: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, { isMobile, state } = useSidebar(), delegatedProps = reactiveOmit(props, "tooltip"), __returned__ = { props, isMobile, state, delegatedProps, get Tooltip() {
    return Tooltip;
  }, get TooltipContent() {
    return TooltipContent;
  }, get TooltipTrigger() {
    return TooltipTrigger;
  }, SidebarMenuButtonChild };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$d(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  $props.tooltip ? _push(ssrRenderComponent($setup.Tooltip, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TooltipTrigger, { "as-child": "" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.SidebarMenuButtonChild, { ...$setup.delegatedProps, ..._ctx.$attrs }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push4, _parent4, _scopeId3);
        else return [renderSlot(_ctx.$slots, "default")];
      }), _: 3 }, _parent3, _scopeId2));
      else return [createVNode($setup.SidebarMenuButtonChild, { ...$setup.delegatedProps, ..._ctx.$attrs }, { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16)];
    }), _: 3 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.TooltipContent, { side: "right", align: "center", hidden: $setup.state !== "collapsed" || $setup.isMobile }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) typeof $props.tooltip == "string" ? _push3(`<!--[-->${ssrInterpolate($props.tooltip)}<!--]-->`) : ssrRenderVNode(_push3, createVNode(resolveDynamicComponent($props.tooltip), null, null), _parent3, _scopeId2);
      else return [typeof $props.tooltip == "string" ? (openBlock(), createBlock(Fragment, { key: 0 }, [createTextVNode(toDisplayString($props.tooltip), 1)], 64)) : (openBlock(), createBlock(resolveDynamicComponent($props.tooltip), { key: 1 }))];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.TooltipTrigger, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.SidebarMenuButtonChild, { ...$setup.delegatedProps, ..._ctx.$attrs }, { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16)]), _: 3 }), createVNode($setup.TooltipContent, { side: "right", align: "center", hidden: $setup.state !== "collapsed" || $setup.isMobile }, { default: withCtx(() => [typeof $props.tooltip == "string" ? (openBlock(), createBlock(Fragment, { key: 0 }, [createTextVNode(toDisplayString($props.tooltip), 1)], 64)) : (openBlock(), createBlock(resolveDynamicComponent($props.tooltip), { key: 1 }))]), _: 1 }, 8, ["hidden"])];
  }), _: 3 }, _parent)) : _push(ssrRenderComponent($setup.SidebarMenuButtonChild, mergeProps({ ...$setup.delegatedProps, ..._ctx.$attrs }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuButton.vue"), _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const SidebarMenuButton = _export_sfc(_sfc_main$e, [["ssrRender", _sfc_ssrRender$d]]);
const _sfc_main$d = defineComponent({ __name: "SidebarMenuItem", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$c(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<li${ssrRenderAttrs(mergeProps({ "data-sidebar": "menu-item", class: $setup.cn("group/menu-item relative", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</li>");
}
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuItem.vue"), _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
const SidebarMenuItem = _export_sfc(_sfc_main$d, [["ssrRender", _sfc_ssrRender$c]]);
const _sfc_main$c = defineComponent({ __name: "Skeleton", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$b(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("animate-pulse rounded-md bg-muted", $setup.props.class) }, _attrs))}></div>`);
}
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/skeleton/Skeleton.vue"), _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const Skeleton = _export_sfc(_sfc_main$c, [["ssrRender", _sfc_ssrRender$b]]);
const _sfc_main$b = defineComponent({ __name: "SidebarMenuSkeleton", props: { showIcon: { type: Boolean }, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, width = computed(() => `${Math.floor(Math.random() * 40) + 50}%`), __returned__ = { props, width, get cn() {
    return cn;
  }, get Skeleton() {
    return Skeleton;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$a(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ "data-sidebar": "menu-skeleton", class: $setup.cn("rounded-md h-8 flex gap-2 px-2 items-center", $setup.props.class) }, _attrs))}>`), $props.showIcon ? _push(ssrRenderComponent($setup.Skeleton, { class: "size-4 rounded-md", "data-sidebar": "menu-skeleton-icon" }, null, _parent)) : _push("<!---->"), _push(ssrRenderComponent($setup.Skeleton, { class: "h-4 flex-1 max-w-[--skeleton-width]", "data-sidebar": "menu-skeleton-text", style: { "--skeleton-width": $setup.width } }, null, _parent)), _push("</div>");
}
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuSkeleton.vue"), _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
_export_sfc(_sfc_main$b, [["ssrRender", _sfc_ssrRender$a]]);
const _sfc_main$a = defineComponent({ __name: "SidebarMenuSub", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<ul${ssrRenderAttrs(mergeProps({ "data-sidebar": "menu-badge", class: $setup.cn("mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l border-sidebar-border px-2.5 py-0.5", "group-data-[collapsible=icon]:hidden", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</ul>");
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuSub.vue"), _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
_export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$9 = defineComponent({ __name: "SidebarMenuSubButton", props: { asChild: { type: Boolean }, as: { default: "a" }, size: { default: "md" }, isActive: { type: Boolean }, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Primitive() {
    return Primitive;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Primitive, mergeProps({ "data-sidebar": "menu-sub-button", as: $props.as, "as-child": $props.asChild, "data-size": $props.size, "data-active": $props.isActive, class: $setup.cn("flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 text-sidebar-foreground outline-none ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-sidebar-accent-foreground", "data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground", $props.size === "sm" && "text-xs", $props.size === "md" && "text-sm", "group-data-[collapsible=icon]:hidden", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuSubButton.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
_export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$8 = {};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarMenuSubItem.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const _sfc_main$7 = defineComponent({ __name: "SidebarProvider", props: { defaultOpen: { type: Boolean, default: !defaultDocument?.cookie.includes(`${SIDEBAR_COOKIE_NAME}=false`) }, open: { type: Boolean, default: void 0 }, class: {} }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, isMobile = useMediaQuery("(max-width: 768px)", { ssrWidth: 1024 }), openMobile = ref(false), open = useVModel(props, "open", emits, { defaultValue: props.defaultOpen ?? false, passive: props.open === void 0 });
  function setOpen(value) {
    open.value = value, document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
  }
  function setOpenMobile(value) {
    openMobile.value = value;
  }
  function toggleSidebar() {
    return isMobile.value ? setOpenMobile(!openMobile.value) : setOpen(!open.value);
  }
  useEventListener("keydown", (event) => {
    event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey) && (event.preventDefault(), toggleSidebar());
  });
  const state = computed(() => open.value ? "expanded" : "collapsed");
  provideSidebarContext({ state, open, setOpen, isMobile, openMobile, setOpenMobile, toggleSidebar });
  const __returned__ = { props, emits, isMobile, openMobile, open, setOpen, setOpenMobile, toggleSidebar, state, get TooltipProvider() {
    return TooltipProvider;
  }, get cn() {
    return cn;
  }, get SIDEBAR_WIDTH() {
    return SIDEBAR_WIDTH;
  }, get SIDEBAR_WIDTH_ICON() {
    return SIDEBAR_WIDTH_ICON;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TooltipProvider, mergeProps({ "delay-duration": 0 }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<div${ssrRenderAttrs(mergeProps({ style: { "--sidebar-width": $setup.SIDEBAR_WIDTH, "--sidebar-width-icon": $setup.SIDEBAR_WIDTH_ICON }, class: $setup.cn("group/sidebar-wrapper flex min-h-full w-full has-[[data-variant=inset]]:bg-sidebar", $setup.props.class) }, _ctx.$attrs))}${_scopeId}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2("</div>");
    else return [createVNode("div", mergeProps({ style: { "--sidebar-width": $setup.SIDEBAR_WIDTH, "--sidebar-width-icon": $setup.SIDEBAR_WIDTH_ICON }, class: $setup.cn("group/sidebar-wrapper flex min-h-full w-full has-[[data-variant=inset]]:bg-sidebar", $setup.props.class) }, _ctx.$attrs), [renderSlot(_ctx.$slots, "default")], 16)];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarProvider.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const SidebarProvider = _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "SidebarRail", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, { toggleSidebar } = useSidebar(), __returned__ = { props, toggleSidebar, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<button${ssrRenderAttrs(mergeProps({ "data-sidebar": "rail", "aria-label": "Toggle Sidebar", tabindex: -1, title: "Toggle Sidebar", class: $setup.cn("absolute inset-y-0 z-20 hidden w-4 -translate-x-1/2 transition-all ease-linear after:absolute after:inset-y-0 after:left-1/2 after:w-[2px] hover:after:bg-sidebar-border group-data-[side=left]:-right-4 group-data-[side=right]:left-0 sm:flex", "[[data-side=left]_&]:cursor-w-resize [[data-side=right]_&]:cursor-e-resize", "[[data-side=left][data-state=collapsed]_&]:cursor-e-resize [[data-side=right][data-state=collapsed]_&]:cursor-w-resize", "group-data-[collapsible=offcanvas]:translate-x-0 group-data-[collapsible=offcanvas]:after:left-full group-data-[collapsible=offcanvas]:hover:bg-sidebar", "[[data-side=left][data-collapsible=offcanvas]_&]:-right-2", "[[data-side=right][data-collapsible=offcanvas]_&]:-left-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</button>");
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarRail.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
_export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "Separator", props: { orientation: { default: "horizontal" }, decorative: { type: Boolean, default: true }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get Separator() {
    return Separator$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Separator, mergeProps($setup.delegatedProps, { class: $setup.cn("shrink-0 bg-border", $setup.props.orientation === "horizontal" ? "h-px w-full" : "w-px h-full", $setup.props.class) }, _attrs), null, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/separator/Separator.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const Separator = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "SidebarSeparator", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  }, get Separator() {
    return Separator;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Separator, mergeProps({ "data-sidebar": "separator", class: $setup.cn("mx-2 w-auto bg-sidebar-border", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarSeparator.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
_export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "SidebarTrigger", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, { toggleSidebar } = useSidebar(), __returned__ = { props, toggleSidebar, get PanelLeft() {
    return PanelLeft;
  }, get cn() {
    return cn;
  }, get Button() {
    return Button;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Button, mergeProps({ "data-sidebar": "trigger", variant: "ghost", size: "icon", class: $setup.cn("h-7 w-7", $setup.props.class), onClick: $setup.toggleSidebar }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.PanelLeft, null, null, _parent2, _scopeId)), _push2(`<span class="sr-only"${_scopeId}>Toggle Sidebar</span>`);
    else return [createVNode($setup.PanelLeft), createVNode("span", { class: "sr-only" }, "Toggle Sidebar")];
  }), _: 1 }, _parent));
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sidebar/SidebarTrigger.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
_export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const sidebarMenuButtonVariants = cva(
  "peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-none ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 group-has-[[data-sidebar=menu-action]]/menu-item:pr-8 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground data-[state=open]:hover:bg-sidebar-accent data-[state=open]:hover:text-sidebar-accent-foreground group-data-[collapsible=icon]:!size-8 group-data-[collapsible=icon]:!p-2 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0",
  {
    variants: {
      variant: {
        default: "hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
        outline: "bg-background shadow-[0_0_0_1px_hsl(var(--sidebar-border))] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground hover:shadow-[0_0_0_1px_hsl(var(--sidebar-accent))]"
      },
      size: {
        default: "h-8 text-sm",
        sm: "h-7 text-xs",
        lg: "h-12 text-sm group-data-[collapsible=icon]:!p-0"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);
const _sfc_main$2 = defineComponent({ __name: "AppSidebar", props: { currentPath: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, __returned__ = { props, menuGroups: [{ label: "核心业务", items: [{ title: "工作台概览", icon: "BarChart4", url: "./management-workbench.html" }, { title: "产品管理", icon: "Package", url: "./product-management.html" }, { title: "分类管理", icon: "FolderTree", url: "./category-management.html" }, { title: "资源库", icon: "Image", url: "./resource-library-picker.html?targetType=colorChart" }] }, { label: "个人中心", items: [{ title: "我的收藏", icon: "Heart", url: "./favorites.html" }, { title: "浏览足迹", icon: "History", url: "./browsing-history.html" }] }, { label: "系统设置", items: [{ title: "水印设置", icon: "Stamp", url: "./watermark-settings.html" }, { title: "容量套餐", icon: "Database", url: "./billing-usage.html" }, { title: "回收站", icon: "Trash2", url: "./recycling-bin.html" }] }], isActive: (url) => {
    if (!props.currentPath) return false;
    const normalizedNav = url.replace(/^\.\//, "").replace(/\.html$/, ""), normalizedPath = props.currentPath.replace(/^\//, "").replace(/\.html$/, "");
    return normalizedPath === normalizedNav || normalizedNav !== "" && normalizedPath.startsWith(normalizedNav);
  }, handleNavigate: (url) => {
    window.location.href = url;
  }, get Sidebar() {
    return Sidebar;
  }, get SidebarContent() {
    return SidebarContent;
  }, get SidebarGroup() {
    return SidebarGroup;
  }, get SidebarGroupContent() {
    return SidebarGroupContent;
  }, get SidebarGroupLabel() {
    return SidebarGroupLabel;
  }, get SidebarMenu() {
    return SidebarMenu;
  }, get SidebarMenuItem() {
    return SidebarMenuItem;
  }, get SidebarMenuButton() {
    return SidebarMenuButton;
  }, SafeIcon, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Sidebar, mergeProps({ variant: "inset", class: "h-full border-r border-border bg-card" }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SidebarContent, { class: "py-4" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3("<!--[-->"), ssrRenderList($setup.menuGroups, (group) => {
        _push3(ssrRenderComponent($setup.SidebarGroup, { key: group.label }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
          if (_push4) _push4(ssrRenderComponent($setup.SidebarGroupLabel, { class: "px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`${ssrInterpolate(group.label)}`);
            else return [createTextVNode(toDisplayString(group.label), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.SidebarGroupContent, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(ssrRenderComponent($setup.SidebarMenu, null, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6("<!--[-->"), ssrRenderList(group.items, (item) => {
                _push6(ssrRenderComponent($setup.SidebarMenuItem, { key: item.title }, { default: withCtx((_6, _push7, _parent7, _scopeId6) => {
                  if (_push7) _push7(ssrRenderComponent($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx((_7, _push8, _parent8, _scopeId7) => {
                    if (_push8) _push8(ssrRenderComponent($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, _parent8, _scopeId7)), _push8(`<span${_scopeId7}>${ssrInterpolate(item.title)}</span>`);
                    else return [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)];
                  }), _: 2 }, _parent7, _scopeId6));
                  else return [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])];
                }), _: 2 }, _parent6, _scopeId5));
              }), _push6("<!--]-->");
              else return [(openBlock(true), createBlock(Fragment, null, renderList(group.items, (item) => (openBlock(), createBlock($setup.SidebarMenuItem, { key: item.title }, { default: withCtx(() => [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])]), _: 2 }, 1024))), 128))];
            }), _: 2 }, _parent5, _scopeId4));
            else return [createVNode($setup.SidebarMenu, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group.items, (item) => (openBlock(), createBlock($setup.SidebarMenuItem, { key: item.title }, { default: withCtx(() => [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])]), _: 2 }, 1024))), 128))]), _: 2 }, 1024)];
          }), _: 2 }, _parent4, _scopeId3));
          else return [createVNode($setup.SidebarGroupLabel, { class: "px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2" }, { default: withCtx(() => [createTextVNode(toDisplayString(group.label), 1)]), _: 2 }, 1024), createVNode($setup.SidebarGroupContent, null, { default: withCtx(() => [createVNode($setup.SidebarMenu, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group.items, (item) => (openBlock(), createBlock($setup.SidebarMenuItem, { key: item.title }, { default: withCtx(() => [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])]), _: 2 }, 1024))), 128))]), _: 2 }, 1024)]), _: 2 }, 1024)];
        }), _: 2 }, _parent3, _scopeId2));
      }), _push3("<!--]-->");
      else return [(openBlock(), createBlock(Fragment, null, renderList($setup.menuGroups, (group) => createVNode($setup.SidebarGroup, { key: group.label }, { default: withCtx(() => [createVNode($setup.SidebarGroupLabel, { class: "px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2" }, { default: withCtx(() => [createTextVNode(toDisplayString(group.label), 1)]), _: 2 }, 1024), createVNode($setup.SidebarGroupContent, null, { default: withCtx(() => [createVNode($setup.SidebarMenu, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group.items, (item) => (openBlock(), createBlock($setup.SidebarMenuItem, { key: item.title }, { default: withCtx(() => [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])]), _: 2 }, 1024))), 128))]), _: 2 }, 1024)]), _: 2 }, 1024)]), _: 2 }, 1024)), 64))];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.SidebarContent, { class: "py-4" }, { default: withCtx(() => [(openBlock(), createBlock(Fragment, null, renderList($setup.menuGroups, (group) => createVNode($setup.SidebarGroup, { key: group.label }, { default: withCtx(() => [createVNode($setup.SidebarGroupLabel, { class: "px-4 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/70 mb-2" }, { default: withCtx(() => [createTextVNode(toDisplayString(group.label), 1)]), _: 2 }, 1024), createVNode($setup.SidebarGroupContent, null, { default: withCtx(() => [createVNode($setup.SidebarMenu, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList(group.items, (item) => (openBlock(), createBlock($setup.SidebarMenuItem, { key: item.title }, { default: withCtx(() => [createVNode($setup.SidebarMenuButton, { class: $setup.cn("w-full flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors rounded-none border-l-4 border-transparent", $setup.isActive(item.url) ? "bg-secondary text-primary border-primary" : "text-muted-foreground hover:bg-muted hover:text-foreground"), onClick: ($event) => $setup.handleNavigate(item.url) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: item.icon, size: 18, class: $setup.isActive(item.url) ? "text-primary" : "text-muted-foreground" }, null, 8, ["name", "class"]), createVNode("span", null, toDisplayString(item.title), 1)]), _: 2 }, 1032, ["class", "onClick"])]), _: 2 }, 1024))), 128))]), _: 2 }, 1024)]), _: 2 }, 1024)]), _: 2 }, 1024)), 64))]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/AppSidebar.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AppSidebar = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "AppSidebarLayout", props: { currentPath: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { get SidebarProvider() {
    return SidebarProvider;
  }, get SidebarInset() {
    return SidebarInset;
  }, AppSidebar };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SidebarProvider, mergeProps({ class: "h-full overflow-hidden" }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.AppSidebar, { "current-path": $props.currentPath }, null, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.SidebarInset, { class: "flex flex-col overflow-hidden bg-background" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex-1 overflow-y-auto min-h-0"${_scopeId2}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3("</div>");
      else return [createVNode("div", { class: "flex-1 overflow-y-auto min-h-0" }, [renderSlot(_ctx.$slots, "default")])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.AppSidebar, { "current-path": $props.currentPath }, null, 8, ["current-path"]), createVNode($setup.SidebarInset, { class: "flex flex-col overflow-hidden bg-background" }, { default: withCtx(() => [createVNode("div", { class: "flex-1 overflow-y-auto min-h-0" }, [renderSlot(_ctx.$slots, "default")])]), _: 3 })];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/AppSidebarLayout.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AppSidebarLayout = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "AuthGate", setup(__props, { expose: __expose }) {
  __expose();
  const isReady = ref(false), isAuthenticated = ref(false), showLoginDialog = ref(false), verifyLogin = async () => {
    if (authStore.consumeCallbackToken(), isAuthenticated.value = authStore.isLoggedIn(), !isAuthenticated.value) {
      showLoginDialog.value = true, isReady.value = true;
      return;
    }
    try {
      const profile = await pcApi.getCurrentUser();
      authStore.setUser(profile), isAuthenticated.value = true;
    } catch (error) {
      authStore.clearToken(), isAuthenticated.value = false, showLoginDialog.value = true, toast.error(error?.message || "登录已失效，请重新扫码");
    } finally {
      isReady.value = true;
    }
  }, handleLoginSuccess = async () => {
    isReady.value = false, await verifyLogin(), showLoginDialog.value = false;
  };
  onMounted(verifyLogin);
  const __returned__ = { isReady, isAuthenticated, showLoginDialog, verifyLogin, handleLoginSuccess, SafeIcon, LoginDialog };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push("<!--[-->"), $setup.isReady ? $setup.isAuthenticated ? ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent) : (_push('<div class="h-full flex items-center justify-center bg-background"><div class="surface-raised card-padding w-full max-w-sm text-center space-y-4"><div class="w-12 h-12 mx-auto rounded-lg bg-primary/10 flex items-center justify-center">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "ShieldCheck", size: 24, class: "text-primary" }, null, _parent)), _push('</div><div><h2 class="text-section-title">请先登录</h2><p class="text-caption mt-1">扫码后即可进入产品工作台</p></div></div></div>')) : (_push('<div class="h-full flex items-center justify-center text-muted-foreground"><div class="flex items-center gap-2 text-sm">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 18, class: "animate-spin" }, null, _parent)), _push(" 正在校验登录状态... </div></div>")), _push(ssrRenderComponent($setup.LoginDialog, { open: $setup.showLoginDialog, "onUpdate:open": ($event) => $setup.showLoginDialog = $event, onLoginSuccess: $setup.handleLoginSuccess }, null, _parent)), _push("<!--]-->");
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/AuthGate.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AuthGate = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
const $$Astro = createAstro();
const $$ManagementLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$ManagementLayout;
  const { title, description } = Astro.props, currentPath = Astro.url.pathname;
  return renderTemplate`${renderComponent($$result, "BaseLayout", $$BaseLayout, { title, description }, { default: ($$result2) => renderTemplate`
  ${maybeRenderHead()}<div class="flex flex-col h-screen overflow-hidden">
    <!-- Header is client:load to handle user menu and navigation -->
    ${renderComponent($$result2, "ManagementHeader", ManagementHeader, { "client:load": true, "client:component-hydration": "load", "client:component-path": "@/components/common/ManagementHeader.vue", "client:component-export": "default" })}

    <div class="flex-1 min-h-0">
      ${renderComponent($$result2, "AuthGate", AuthGate, { "client:load": true, "client:component-hydration": "load", "client:component-path": "@/components/common/AuthGate.vue", "client:component-export": "default" }, { default: ($$result3) => renderTemplate`
        ${renderComponent($$result3, "AppSidebarLayout", AppSidebarLayout, { "client:load": true, currentPath, "client:component-hydration": "load", "client:component-path": "@/components/common/AppSidebarLayout.vue", "client:component-export": "default" }, { default: ($$result4) => renderTemplate`
          ${renderSlot$1($$result4, $$slots.default)}
        ` })}
      ` })}
    </div>
  </div>
` })}`;
}, "/Users/mac/Downloads/code/src/layouts/ManagementLayout.astro", void 0);
export {
  $$ManagementLayout as $,
  Progress as P
};
