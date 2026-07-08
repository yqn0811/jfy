import { defineComponent, computed, useSSRContext, mergeProps, withCtx, createTextVNode, toDisplayString } from "vue";
import { B as Badge } from "./index.-aTAU5WA.js";
import { c as cn } from "./index.DRLhNP3M.js";
import { ssrRenderComponent, ssrInterpolate } from "vue/server-renderer";
/* empty css                           */
import { _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
const _sfc_main = defineComponent({ __name: "StatusBadge", props: { status: {}, text: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, statusConfig = { public: { label: "公开", class: "status-badge-public" }, private: { label: "私密", class: "status-badge-private" }, shared: { label: "分享可见", class: "status-badge-shared" } }, displayLabel = computed(() => props.text || (statusConfig[props.status]?.label ?? "未知")), badgeClass = computed(() => statusConfig[props.status]?.class ?? ""), __returned__ = { props, statusConfig, displayLabel, badgeClass, get Badge() {
    return Badge;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Badge, mergeProps({ variant: "outline", class: $setup.cn("px-2 py-0.5 font-normal text-xs transition-none border-solid", $setup.badgeClass) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`${ssrInterpolate($setup.displayLabel)}`);
    else return [createTextVNode(toDisplayString($setup.displayLabel), 1)];
  }), _: 1 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/StatusBadge.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StatusBadge = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-29250861"]]);
export {
  StatusBadge as S
};
