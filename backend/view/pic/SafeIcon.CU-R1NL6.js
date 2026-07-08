import { defineComponent, computed, useSSRContext, createVNode, resolveDynamicComponent, mergeProps } from "vue";
import * as LucideIcons from "lucide-vue-next";
import { Circle } from "lucide-vue-next";
import { ssrRenderVNode } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.d5ww63VJ.js";
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
  SafeIcon as S
};
