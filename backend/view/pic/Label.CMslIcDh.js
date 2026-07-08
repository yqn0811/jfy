import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot } from "vue";
import { reactiveOmit } from "@vueuse/core";
import { Label as Label$1 } from "reka-ui";
import { c as cn } from "./index.DRLhNP3M.js";
import { ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
const _sfc_main = defineComponent({ __name: "Label", props: { for: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get Label() {
    return Label$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Label, mergeProps($setup.delegatedProps, { class: $setup.cn("text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/label/Label.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Label = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  Label as L
};
