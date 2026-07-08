import { c as createComponent, b as createAstro, e as addAttribute, f as renderHead, d as renderSlot, r as renderComponent, a as renderTemplate } from "./astro/server.D8EH5g9Z.js";
import { defineComponent, useSSRContext, mergeProps, computed, createVNode, resolveDynamicComponent } from "vue";
import { Toaster as Toaster$1 } from "vue-sonner";
import { ssrRenderComponent, ssrRenderVNode } from "vue/server-renderer";
/* empty css                                */
import * as LucideIcons from "lucide-vue-next";
import { Circle } from "lucide-vue-next";
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$1 = defineComponent({ __name: "Sonner", props: { id: {}, invert: { type: Boolean }, theme: {}, position: {}, closeButtonPosition: {}, hotkey: {}, richColors: { type: Boolean }, expand: { type: Boolean }, duration: {}, gap: {}, visibleToasts: {}, closeButton: { type: Boolean }, toastOptions: {}, class: {}, style: {}, offset: {}, mobileOffset: {}, dir: {}, swipeDirections: {}, icons: {}, containerAriaLabel: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get Sonner() {
    return Toaster$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Sonner, mergeProps({ class: "toaster group" }, $setup.props, { "toast-options": { classes: { toast: "group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg", description: "group-[.toast]:text-muted-foreground", actionButton: "group-[.toast]:bg-primary group-[.toast]:text-primary-foreground", cancelButton: "group-[.toast]:bg-muted group-[.toast]:text-muted-foreground" } } }, _attrs), null, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/sonner/Sonner.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const Toaster = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const $$Astro = createAstro();
const $$BaseLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$BaseLayout;
  const { title = "Project", description = "Built with Astro" } = Astro.props;
  return renderTemplate`<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"${addAttribute(description, "content")}>
    <title>${title}</title>
  ${renderHead()}</head>
  <body>
    ${renderSlot($$result, $$slots.default)}
    ${renderComponent($$result, "Toaster", Toaster, { "client:load": true, "client:component-hydration": "load", "client:component-path": "@/components/ui/sonner", "client:component-export": "Toaster" })}
  </body></html>`;
}, "/Users/mac/Documents/trae_projects/sub2api/ai_jf/.codex_tmp/jfy-sync/album-web/src/layouts/BaseLayout.astro", void 0);
const _sfc_main = defineComponent({ __name: "SafeIcon", props: { name: {}, size: { default: 24 }, color: {}, strokeWidth: { default: 2 }, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, IconComponent = computed(() => {
    const icon = LucideIcons[props.name];
    return icon || (console.warn(`SafeIcon: icon "${props.name}" not found in lucide-vue-next, using fallback`), Circle);
  }), __returned__ = { props, IconComponent };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  ssrRenderVNode(_push, createVNode(resolveDynamicComponent($setup.IconComponent), mergeProps({ size: $props.size, color: $props.color, "stroke-width": $props.strokeWidth, class: $setup.props.class }, _attrs), null), _parent);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/SafeIcon.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const SafeIcon = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  $$BaseLayout as $,
  SafeIcon as S,
  _export_sfc as _
};
