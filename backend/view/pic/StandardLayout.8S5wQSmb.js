import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot } from "./astro/server.D8EH5g9Z.js";
import { S as SafeIcon, _ as _export_sfc, $ as $$BaseLayout } from "./SafeIcon.D7kIP4uZ.js";
import { defineComponent, ref, computed, onMounted, useSSRContext, mergeProps, withCtx, createVNode, toDisplayString, createTextVNode } from "vue";
import { c as cn, B as Button } from "./index.CiCxTEA9.js";
import { I as Input } from "./Input.BPyhE5AH.js";
import { D as DropdownMenuTrigger, f as DropdownMenuSeparator, g as DropdownMenuLabel, c as DropdownMenuItem, d as DropdownMenuContent, e as DropdownMenu, A as AvatarFallback, a as AvatarImage, b as Avatar } from "./index.DlIl1dUz.js";
import { L as LoginDialog } from "./LoginDialog.DXQSdpN9.js";
import { d as authStore, p as pcApi } from "./DialogTrigger.puU1MCQr.js";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderSlot } from "vue/server-renderer";
/* empty css                                   */
const _sfc_main = defineComponent({ __name: "CommonHeader", props: { isAuthenticated: { type: Boolean, default: false }, userName: { default: "访客用户" }, userAvatar: { default: "" }, currentPath: { default: "./share-home.html" } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, searchQuery = ref(""), showLoginDialog = ref(false), userInfo = ref({}), loggedIn = computed(() => props.isAuthenticated || authStore.isLoggedIn()), displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || props.userName), displayAvatar = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || props.userAvatar), navItems = [{ name: "我的收藏", href: "./favorites.html", icon: "Heart" }, { name: "浏览足迹", href: "./browsing-history.html", icon: "History" }], handleSearch = () => {
    searchQuery.value.trim() && (window.location.href = `./share-home.html?keyword=${encodeURIComponent(searchQuery.value.trim())}`);
  }, handleNavigate = (href) => {
    window.location.href = href;
  }, handleMerchantLogin = () => {
    if (loggedIn.value) {
      handleNavigate("./management-workbench.html");
      return;
    }
    showLoginDialog.value = true;
  }, handleLogout = () => {
    authStore.clearToken(), userInfo.value = {}, window.location.reload();
  }, handleLoginSuccess = async () => {
    userInfo.value = authStore.getUser() || {};
    try {
      const profile = await pcApi.getCurrentUser();
      userInfo.value = profile || {}, authStore.setUser(profile);
    } catch {
    }
    handleNavigate("./management-workbench.html");
  }, isActive = (href) => props.currentPath?.includes(href.replace("./", ""));
  onMounted(() => {
    authStore.consumeCallbackToken(), userInfo.value = authStore.getUser() || {};
  });
  const __returned__ = { props, searchQuery, showLoginDialog, userInfo, loggedIn, displayName, displayAvatar, navItems, handleSearch, handleNavigate, handleMerchantLogin, handleLogout, handleLoginSuccess, isActive, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, get Avatar() {
    return Avatar;
  }, get AvatarImage() {
    return AvatarImage;
  }, get AvatarFallback() {
    return AvatarFallback;
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
  }, SafeIcon, LoginDialog, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<header${ssrRenderAttrs(mergeProps({ class: "sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60" }, _attrs))}><div class="h-[var(--header-height)] page-container px-8 flex items-center justify-between gap-8"><div class="flex items-center gap-2 cursor-pointer shrink-0"><div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">`), _push(ssrRenderComponent($setup.SafeIcon, { name: "Cloud", color: "white", size: 20 }, null, _parent)), _push('</div><span class="text-xl font-bold tracking-tight text-primary">家纺云分享</span></div><div class="flex-1 max-w-xl relative">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Search", class: "absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground", size: 18 }, null, _parent)), _push(ssrRenderComponent($setup.Input, { modelValue: $setup.searchQuery, "onUpdate:modelValue": ($event) => $setup.searchQuery = $event, placeholder: "搜索产品名称...", class: "pl-10 h-10 w-full bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary", onKeyup: $setup.handleSearch }, null, _parent)), _push('</div><div class="flex items-center gap-2"><nav class="flex items-center gap-1 mr-4"><!--[-->'), ssrRenderList($setup.navItems, (item) => {
    _push(ssrRenderComponent($setup.Button, { key: item.href, variant: "ghost", class: $setup.cn("flex items-center gap-2 px-4 h-10 text-muted-foreground hover:text-primary hover:bg-primary/5 transition-all", $setup.isActive(item.href) && "text-primary bg-primary/10"), onClick: ($event) => $setup.handleNavigate(item.href) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
      if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: item.icon, size: 20 }, null, _parent2, _scopeId)), _push2(`<span class="text-sm font-medium"${_scopeId}>${ssrInterpolate(item.name)}</span>`);
      else return [createVNode($setup.SafeIcon, { name: item.icon, size: 20 }, null, 8, ["name"]), createVNode("span", { class: "text-sm font-medium" }, toDisplayString(item.name), 1)];
    }), _: 2 }, _parent));
  }), _push("<!--]--></nav>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push('<div class="h-8 w-px bg-border mx-2"></div>'), $setup.loggedIn ? (_push("<div>"), _push(ssrRenderComponent($setup.DropdownMenu, null, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(ssrRenderComponent($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, _parent5, _scopeId4)), _push5(ssrRenderComponent($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
            if (_push6) _push6(`${ssrInterpolate($setup.displayName.substring(0, 1))}`);
            else return [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)];
          }), _: 1 }, _parent5, _scopeId4));
          else return [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(`<div class="flex flex-col space-y-1"${_scopeId3}><p class="text-sm font-medium leading-none"${_scopeId3}>${ssrInterpolate($setup.displayName)}</p><p class="text-xs leading-none text-muted-foreground"${_scopeId3}>欢迎使用家纺云</p></div>`);
        else return [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuSeparator, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>管理工作台</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>退出登录</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx(() => [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div>")) : (_push("<div>"), _push(ssrRenderComponent($setup.Button, { variant: "default", size: "sm", class: "h-9 px-6 rounded-full", onClick: $setup.handleMerchantLogin }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(" 商家登录 ");
    else return [createTextVNode(" 商家登录 ")];
  }), _: 1 }, _parent)), _push("</div>")), _push("</div></div>"), _push(ssrRenderComponent($setup.LoginDialog, { open: $setup.showLoginDialog, "onUpdate:open": ($event) => $setup.showLoginDialog = $event, onLoginSuccess: $setup.handleLoginSuccess }, null, _parent)), _push("</header>");
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/CommonHeader.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CommonHeader = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
const $$Astro = createAstro();
const $$StandardLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$StandardLayout;
  const { title, description } = Astro.props;
  return renderTemplate`${renderComponent($$result, "BaseLayout", $$BaseLayout, { title, description }, { default: ($$result2) => renderTemplate`
  ${maybeRenderHead()}<div class="flex flex-col h-screen overflow-hidden bg-background">
    <!-- CommonHeader owns the logo and top-level navigation -->
    ${renderComponent($$result2, "CommonHeader", CommonHeader, { "client:load": true, currentPath: Astro.url.pathname, "client:component-hydration": "load", "client:component-path": "@/components/common/CommonHeader.vue", "client:component-export": "default" })}
    
    <!-- Main Scrollable Area -->
    <main class="flex-1 overflow-y-auto min-h-0">
      <div class="page-container">
        ${renderSlot($$result2, $$slots.default)}
      </div>
      
      <!-- Footer (Optional, briefly included for professional look) -->
      <footer class="border-t border-border bg-card/50 py-8 px-8">
        <div class="page-container flex justify-between items-center text-caption">
          <div>© 2026 家纺云分享系统. 版权所有</div>
          <div class="flex gap-6">
            <a href="#" class="hover:text-primary">服务条款</a>
            <a href="#" class="hover:text-primary">隐私政策</a>
            <a href="#" class="hover:text-primary">联系客服</a>
          </div>
        </div>
      </footer>
    </main>
  </div>
` })}`;
}, "/Users/mac/Documents/trae_projects/sub2api/ai_jf/.codex_tmp/jfy-sync/album-web/src/layouts/StandardLayout.astro", void 0);
export {
  $$StandardLayout as $
};
