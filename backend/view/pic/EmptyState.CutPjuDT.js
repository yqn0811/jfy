import { defineComponent, useSSRContext, mergeProps } from "vue";
import { S as SafeIcon, _ as _export_sfc } from "./SafeIcon.8ztUq1M8.js";
import { c as cn } from "./index.D4Z4a3fh.js";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderSlot } from "vue/server-renderer";
/* empty css                                   */
const _sfc_main = defineComponent({ __name: "EmptyState", props: { icon: {}, title: {}, description: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, SafeIcon, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("empty-state", $setup.props.class) }, _attrs))} data-v-6629f6f7><div class="flex items-center justify-center w-20 h-20 rounded-full bg-muted/50 mb-2" data-v-6629f6f7>`), _push(ssrRenderComponent($setup.SafeIcon, { name: $setup.props.icon, size: 40, "stroke-width": 1.5, class: "text-muted-foreground/60" }, null, _parent)), _push(`</div><div class="space-y-2 max-w-sm px-4" data-v-6629f6f7><h3 class="text-section-title text-foreground" data-v-6629f6f7>${ssrInterpolate($setup.props.title)}</h3>`), $setup.props.description ? _push(`<p class="text-caption" data-v-6629f6f7>${ssrInterpolate($setup.props.description)}</p>`) : _push("<!---->"), _push("</div>"), _ctx.$slots.default ? (_push('<div class="pt-4 flex flex-wrap justify-center gap-3" data-v-6629f6f7>'), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>")) : _push("<!---->"), _push("</div>");
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/EmptyState.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const EmptyState = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-6629f6f7"]]);
export {
  EmptyState as E
};
