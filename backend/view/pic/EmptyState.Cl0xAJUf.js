import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { useForwardPropsEmits, TabsRoot, TabsContent, TabsList as TabsList$1, useForwardProps, TabsTrigger as TabsTrigger$1 } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs, ssrInterpolate } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.Dnq8fxXw.js";
import { reactiveOmit } from "@vueuse/core";
import { c as cn } from "./index.CHN9pADe.js";
import { S as SafeIcon } from "./SafeIcon.R9c496e_.js";
/* empty css                                   */
const _sfc_main$4 = defineComponent({ __name: "Tabs", props: { defaultValue: {}, orientation: {}, dir: {}, activationMode: {}, modelValue: {}, unmountOnHide: { type: Boolean }, asChild: { type: Boolean }, as: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get TabsRoot() {
    return TabsRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/Tabs.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const Tabs = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "TabsContent", props: { value: {}, forceMount: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get TabsContent() {
    return TabsContent;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsContent, mergeProps({ class: $setup.cn("mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2", $setup.props.class) }, $setup.delegatedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsContent.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
_export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "TabsList", props: { loop: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get TabsList() {
    return TabsList$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsList, mergeProps($setup.delegatedProps, { class: $setup.cn("inline-flex items-center justify-center rounded-md bg-muted p-1 text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsList.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const TabsList = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "TabsTrigger", props: { value: {}, disabled: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get TabsTrigger() {
    return TabsTrigger$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="truncate"${_scopeId}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2("</span>");
    else return [createVNode("span", { class: "truncate" }, [renderSlot(_ctx.$slots, "default")])];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsTrigger.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TabsTrigger = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
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
  EmptyState as E,
  TabsTrigger as T,
  TabsList as a,
  Tabs as b
};
