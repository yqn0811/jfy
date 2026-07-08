import { defineComponent, computed, useSSRContext, mergeProps, withCtx, createTextVNode, toDisplayString, createBlock, createCommentVNode, createVNode, openBlock } from "vue";
import { A as AvatarFallback, a as AvatarImage, b as Avatar } from "./index.DawGBfjz.js";
import { c as cn } from "./index.DRLhNP3M.js";
import { ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
const _sfc_main = defineComponent({ __name: "UserAvatar", props: { src: { default: "" }, name: {}, size: { default: "md" }, class: { default: "" } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, initials = computed(() => {
    if (!props.name) return "?";
    const trimmed = props.name.trim();
    return /^[\u4e00-\u9fa5]+$/.test(trimmed) ? trimmed.slice(-1) : trimmed.split(/\s+/).map((word) => word[0]).join("").toUpperCase().slice(0, 2);
  }), __returned__ = { props, initials, sizeClasses: { sm: "h-8 w-8 text-xs", md: "h-10 w-10 text-sm", lg: "h-16 w-16 text-xl" }, get Avatar() {
    return Avatar;
  }, get AvatarImage() {
    return AvatarImage;
  }, get AvatarFallback() {
    return AvatarFallback;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Avatar, mergeProps({ class: $setup.cn($setup.sizeClasses[$setup.props.size], "shrink-0 select-none bg-muted", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) $setup.props.src ? _push2(ssrRenderComponent($setup.AvatarImage, { src: $setup.props.src, alt: $setup.props.name, class: "aspect-square h-full w-full object-cover" }, null, _parent2, _scopeId)) : _push2("<!---->"), _push2(ssrRenderComponent($setup.AvatarFallback, { class: "flex h-full w-full items-center justify-center rounded-full bg-primary/10 font-medium text-primary uppercase" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`${ssrInterpolate($setup.initials)}`);
      else return [createTextVNode(toDisplayString($setup.initials), 1)];
    }), _: 1 }, _parent2, _scopeId));
    else return [$setup.props.src ? (openBlock(), createBlock($setup.AvatarImage, { key: 0, src: $setup.props.src, alt: $setup.props.name, class: "aspect-square h-full w-full object-cover" }, null, 8, ["src", "alt"])) : createCommentVNode("", true), createVNode($setup.AvatarFallback, { class: "flex h-full w-full items-center justify-center rounded-full bg-primary/10 font-medium text-primary uppercase" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.initials), 1)]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/UserAvatar.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
_export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
