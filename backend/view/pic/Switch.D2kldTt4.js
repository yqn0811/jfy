import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { reactiveOmit } from "@vueuse/core";
import { useForwardPropsEmits, SwitchThumb, SwitchRoot } from "reka-ui";
import { c as cn } from "./index.DRLhNP3M.js";
import { ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
const _sfc_main = defineComponent({ __name: "Switch", props: { defaultValue: { type: Boolean }, modelValue: { type: [Boolean, null] }, disabled: { type: Boolean }, id: {}, value: {}, asChild: { type: Boolean }, as: {}, name: {}, required: { type: Boolean }, class: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get SwitchRoot() {
    return SwitchRoot;
  }, get SwitchThumb() {
    return SwitchThumb;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SwitchRoot, mergeProps($setup.forwarded, { class: $setup.cn("peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SwitchThumb, { class: $setup.cn("pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-5") }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "thumb", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "thumb")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.SwitchThumb, { class: $setup.cn("pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-5") }, { default: withCtx(() => [renderSlot(_ctx.$slots, "thumb")]), _: 3 }, 8, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/switch/Switch.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Switch = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  Switch as S
};
