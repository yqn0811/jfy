import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { reactiveOmit } from "@vueuse/core";
import { useForwardPropsEmits, SelectRoot, SelectViewport, SelectPortal, SelectContent as SelectContent$1, SelectGroup, useForwardProps, SelectItemText, SelectItemIndicator, SelectItem as SelectItem$1, SelectLabel, SelectScrollDownButton as SelectScrollDownButton$1, SelectScrollUpButton as SelectScrollUpButton$1, SelectSeparator, SelectTrigger as SelectTrigger$1, SelectIcon, SelectValue as SelectValue$1 } from "reka-ui";
import { c as cn } from "./index.D4Z4a3fh.js";
import { ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.8ztUq1M8.js";
import { Check, ChevronDown, ChevronUp } from "lucide-vue-next";
const _sfc_main$a = defineComponent({ __name: "Select", props: { open: { type: Boolean }, defaultOpen: { type: Boolean }, defaultValue: {}, modelValue: {}, by: { type: [String, Function] }, dir: {}, multiple: { type: Boolean }, autocomplete: {}, disabled: { type: Boolean }, name: {}, required: { type: Boolean } }, emits: ["update:modelValue", "update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get SelectRoot() {
    return SelectRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$a(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/Select.vue"), _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const Select = _export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$a]]);
const _sfc_main$9 = defineComponent({ inheritAttrs: false, __name: "SelectContent", props: { forceMount: { type: Boolean }, position: { default: "popper" }, bodyLock: { type: Boolean }, side: {}, sideOffset: {}, sideFlip: { type: Boolean }, align: {}, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["closeAutoFocus", "escapeKeyDown", "pointerDownOutside"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get SelectContent() {
    return SelectContent$1;
  }, get SelectPortal() {
    return SelectPortal;
  }, get SelectViewport() {
    return SelectViewport;
  }, get cn() {
    return cn;
  }, get SelectScrollDownButton() {
    return SelectScrollDownButton;
  }, get SelectScrollUpButton() {
    return SelectScrollUpButton;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SelectContent, mergeProps({ ...$setup.forwarded, ..._ctx.$attrs }, { class: $setup.cn("relative z-50 max-h-96 min-w-32 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $props.position === "popper" && "data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.SelectScrollUpButton, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.SelectViewport, { class: $setup.cn("p-1", $props.position === "popper" && "h-[--reka-select-trigger-height] w-full min-w-[--reka-select-trigger-width]") }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push4, _parent4, _scopeId3);
        else return [renderSlot(_ctx.$slots, "default")];
      }), _: 3 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.SelectScrollDownButton, null, null, _parent3, _scopeId2));
      else return [createVNode($setup.SelectScrollUpButton), createVNode($setup.SelectViewport, { class: $setup.cn("p-1", $props.position === "popper" && "h-[--reka-select-trigger-height] w-full min-w-[--reka-select-trigger-width]") }, { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 8, ["class"]), createVNode($setup.SelectScrollDownButton)];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.SelectContent, mergeProps({ ...$setup.forwarded, ..._ctx.$attrs }, { class: $setup.cn("relative z-50 max-h-96 min-w-32 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $props.position === "popper" && "data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1", $setup.props.class) }), { default: withCtx(() => [createVNode($setup.SelectScrollUpButton), createVNode($setup.SelectViewport, { class: $setup.cn("p-1", $props.position === "popper" && "h-[--reka-select-trigger-height] w-full min-w-[--reka-select-trigger-width]") }, { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 8, ["class"]), createVNode($setup.SelectScrollDownButton)]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectContent.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const SelectContent = _export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "SelectGroup", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get SelectGroup() {
    return SelectGroup;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectGroup, mergeProps({ class: $setup.cn("p-1 w-full", $setup.props.class) }, $setup.delegatedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectGroup.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
_export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "SelectItem", props: { value: {}, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get Check() {
    return Check;
  }, get SelectItem() {
    return SelectItem$1;
  }, get SelectItemIndicator() {
    return SelectItemIndicator;
  }, get SelectItemText() {
    return SelectItemText;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectItem, mergeProps($setup.forwardedProps, { class: $setup.cn("relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.SelectItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Check, { class: "h-4 w-4" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Check, { class: "h-4 w-4" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), _push2(ssrRenderComponent($setup.SelectItemText, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.SelectItemIndicator, null, { default: withCtx(() => [createVNode($setup.Check, { class: "h-4 w-4" })]), _: 1 })]), createVNode($setup.SelectItemText, null, { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 })];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectItem.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const SelectItem = _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "SelectItemText", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get SelectItemText() {
    return SelectItemText;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectItemText, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectItemText.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
_export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "SelectLabel", props: { for: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get SelectLabel() {
    return SelectLabel;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectLabel, mergeProps({ class: $setup.cn("py-1.5 pl-8 pr-2 text-sm font-semibold", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectLabel.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
_export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "SelectScrollDownButton", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get ChevronDown() {
    return ChevronDown;
  }, get SelectScrollDownButton() {
    return SelectScrollDownButton$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectScrollDownButton, mergeProps($setup.forwardedProps, { class: $setup.cn("flex cursor-default items-center justify-center py-1", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, () => {
      _push2(ssrRenderComponent($setup.ChevronDown, { class: "h-4 w-4" }, null, _parent2, _scopeId));
    }, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default", {}, () => [createVNode($setup.ChevronDown, { class: "h-4 w-4" })])];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectScrollDownButton.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const SelectScrollDownButton = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "SelectScrollUpButton", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get ChevronUp() {
    return ChevronUp;
  }, get SelectScrollUpButton() {
    return SelectScrollUpButton$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectScrollUpButton, mergeProps($setup.forwardedProps, { class: $setup.cn("flex cursor-default items-center justify-center py-1", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, () => {
      _push2(ssrRenderComponent($setup.ChevronUp, { class: "h-4 w-4" }, null, _parent2, _scopeId));
    }, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default", {}, () => [createVNode($setup.ChevronUp, { class: "h-4 w-4" })])];
  }), _: 3 }, _parent));
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectScrollUpButton.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const SelectScrollUpButton = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "SelectSeparator", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get SelectSeparator() {
    return SelectSeparator;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectSeparator, mergeProps($setup.delegatedProps, { class: $setup.cn("-mx-1 my-1 h-px bg-muted", $setup.props.class) }, _attrs), null, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectSeparator.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
_export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "SelectTrigger", props: { disabled: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get ChevronDown() {
    return ChevronDown;
  }, get SelectIcon() {
    return SelectIcon;
  }, get SelectTrigger() {
    return SelectTrigger$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background data-[placeholder]:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate text-start", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2(ssrRenderComponent($setup.SelectIcon, { "as-child": "" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.ChevronDown, { class: "w-4 h-4 opacity-50 shrink-0" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.ChevronDown, { class: "w-4 h-4 opacity-50 shrink-0" })];
    }), _: 1 }, _parent2, _scopeId));
    else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.SelectIcon, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.ChevronDown, { class: "w-4 h-4 opacity-50 shrink-0" })]), _: 1 })];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectTrigger.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const SelectTrigger = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "SelectValue", props: { placeholder: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get SelectValue() {
    return SelectValue$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.SelectValue, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/select/SelectValue.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const SelectValue = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  SelectValue as S,
  SelectTrigger as a,
  SelectItem as b,
  SelectContent as c,
  Select as d
};
