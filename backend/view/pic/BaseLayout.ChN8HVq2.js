import { c as createComponent, b as createAstro, a as renderTemplate, r as renderComponent, d as renderSlot, e as renderHead, f as addAttribute } from "./astro/server.CNtEcxKA.js";
import { defineComponent, useSSRContext, mergeProps } from "vue";
import { Toaster as Toaster$1 } from "vue-sonner";
import { ssrRenderComponent } from "vue/server-renderer";
/* empty css                                */
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main = defineComponent({ __name: "Sonner", props: { id: {}, invert: { type: Boolean }, theme: {}, position: {}, closeButtonPosition: {}, hotkey: {}, richColors: { type: Boolean }, expand: { type: Boolean }, duration: {}, gap: {}, visibleToasts: {}, closeButton: { type: Boolean }, toastOptions: {}, class: {}, style: {}, offset: {}, mobileOffset: {}, dir: {}, swipeDirections: {}, icons: {}, containerAriaLabel: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Sonner() {
    return Toaster$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Sonner, mergeProps({ class: "toaster group" }, $setup.props, { "toast-options": { classes: { toast: "group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg", description: "group-[.toast]:text-muted-foreground", actionButton: "group-[.toast]:bg-primary group-[.toast]:text-primary-foreground", cancelButton: "group-[.toast]:bg-muted group-[.toast]:text-muted-foreground" } } }, _attrs), null, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sonner/Sonner.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Toaster = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
var __freeze = Object.freeze, __defProp = Object.defineProperty;
var __template = (cooked, raw) => __freeze(__defProp(cooked, "raw", { value: __freeze(cooked.slice()) }));
const $$Astro = createAstro();
var _a;
const $$BaseLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$BaseLayout;
  const { title = "家纺云相册", description = "家纺云产品相册与协作上传" } = Astro.props;
  return renderTemplate(_a || (_a = __template([`<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"`, `>
    <title>`, `</title>
    <script src="https://res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js"><\/script>
  `, `</head>
  <body>
    `, `
    `, `
  </body></html>`])), addAttribute(description, "content"), title, renderHead(), renderSlot($$result, $$slots.default), renderComponent($$result, "Toaster", Toaster, { "client:load": true, "client:component-hydration": "load", "client:component-path": "@/components/ui/sonner", "client:component-export": "Toaster" }));
}, "/private/tmp/jfyuntu-album-web-build-202607091340/src/layouts/BaseLayout.astro", void 0);
export {
  $$BaseLayout as $,
  _export_sfc as _
};
