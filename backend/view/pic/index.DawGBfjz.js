import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { useForwardPropsEmits, DropdownMenuRoot, DropdownMenuItemIndicator, DropdownMenuCheckboxItem, DropdownMenuPortal, DropdownMenuContent as DropdownMenuContent$1, DropdownMenuGroup, useForwardProps, DropdownMenuItem as DropdownMenuItem$1, DropdownMenuLabel as DropdownMenuLabel$1, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator as DropdownMenuSeparator$1, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger, DropdownMenuTrigger as DropdownMenuTrigger$1, AvatarRoot, AvatarFallback as AvatarFallback$1, AvatarImage as AvatarImage$1 } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
import { reactiveOmit } from "@vueuse/core";
import { Check, Circle, ChevronRight } from "lucide-vue-next";
import { c as cn } from "./index.DRLhNP3M.js";
import { cva } from "class-variance-authority";
const _sfc_main$g = defineComponent({ __name: "DropdownMenu", props: { defaultOpen: { type: Boolean }, open: { type: Boolean }, dir: {}, modal: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRoot() {
    return DropdownMenuRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$g(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenu.vue"), _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const DropdownMenu = _export_sfc(_sfc_main$g, [["ssrRender", _sfc_ssrRender$g]]);
const _sfc_main$f = defineComponent({ __name: "DropdownMenuCheckboxItem", props: { modelValue: { type: [Boolean, String] }, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select", "update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get Check() {
    return Check;
  }, get DropdownMenuCheckboxItem() {
    return DropdownMenuCheckboxItem;
  }, get DropdownMenuItemIndicator() {
    return DropdownMenuItemIndicator;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$f(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuCheckboxItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Check, { class: "w-4 h-4" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Check, { class: "w-4 h-4" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Check, { class: "w-4 h-4" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue"), _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
_export_sfc(_sfc_main$f, [["ssrRender", _sfc_ssrRender$f]]);
const _sfc_main$e = defineComponent({ __name: "DropdownMenuContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, side: {}, sideOffset: { default: 4 }, sideFlip: { type: Boolean }, align: {}, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get DropdownMenuContent() {
    return DropdownMenuContent$1;
  }, get DropdownMenuPortal() {
    return DropdownMenuPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$e(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuContent.vue"), _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const DropdownMenuContent = _export_sfc(_sfc_main$e, [["ssrRender", _sfc_ssrRender$e]]);
const _sfc_main$d = defineComponent({ __name: "DropdownMenuGroup", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DropdownMenuGroup() {
    return DropdownMenuGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$d(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuGroup, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuGroup.vue"), _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
_export_sfc(_sfc_main$d, [["ssrRender", _sfc_ssrRender$d]]);
const _sfc_main$c = defineComponent({ __name: "DropdownMenuItem", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuItem() {
    return DropdownMenuItem$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$c(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuItem, mergeProps($setup.forwardedProps, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm gap-2 px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuItem.vue"), _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const DropdownMenuItem = _export_sfc(_sfc_main$c, [["ssrRender", _sfc_ssrRender$c]]);
const _sfc_main$b = defineComponent({ __name: "DropdownMenuLabel", props: { asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuLabel() {
    return DropdownMenuLabel$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$b(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuLabel, mergeProps($setup.forwardedProps, { class: $setup.cn("px-2 py-1.5 text-sm font-semibold", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuLabel.vue"), _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const DropdownMenuLabel = _export_sfc(_sfc_main$b, [["ssrRender", _sfc_ssrRender$b]]);
const _sfc_main$a = defineComponent({ __name: "DropdownMenuRadioGroup", props: { modelValue: {}, asChild: { type: Boolean }, as: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRadioGroup() {
    return DropdownMenuRadioGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$a(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioGroup, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioGroup.vue"), _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
_export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$a]]);
const _sfc_main$9 = defineComponent({ __name: "DropdownMenuRadioItem", props: { value: {}, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get Circle() {
    return Circle;
  }, get DropdownMenuItemIndicator() {
    return DropdownMenuItemIndicator;
  }, get DropdownMenuRadioItem() {
    return DropdownMenuRadioItem;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Circle, { class: "h-2 w-2 fill-current" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioItem.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
_export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "DropdownMenuSeparator", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get DropdownMenuSeparator() {
    return DropdownMenuSeparator$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSeparator, mergeProps($setup.delegatedProps, { class: $setup.cn("-mx-1 my-1 h-px bg-muted", $setup.props.class) }, _attrs), null, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSeparator.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const DropdownMenuSeparator = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "DropdownMenuShortcut", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<span${ssrRenderAttrs(mergeProps({ class: $setup.cn("ml-auto text-xs tracking-widest opacity-60", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</span>");
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuShortcut.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
_export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "DropdownMenuSub", props: { defaultOpen: { type: Boolean }, open: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuSub() {
    return DropdownMenuSub;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSub, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSub.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
_export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "DropdownMenuSubContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, sideOffset: {}, sideFlip: { type: Boolean }, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "entryFocus", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get DropdownMenuSubContent() {
    return DropdownMenuSubContent;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubContent.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
_export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "DropdownMenuSubTrigger", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get ChevronRight() {
    return ChevronRight;
  }, get DropdownMenuSubTrigger() {
    return DropdownMenuSubTrigger;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none focus:bg-accent data-[state=open]:bg-accent", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2(ssrRenderComponent($setup.ChevronRight, { class: "ml-auto h-4 w-4" }, null, _parent2, _scopeId));
    else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.ChevronRight, { class: "ml-auto h-4 w-4" })];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubTrigger.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
_export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "DropdownMenuTrigger", props: { disabled: { type: Boolean }, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, forwardedProps = useForwardProps(props), __returned__ = { props, forwardedProps, get DropdownMenuTrigger() {
    return DropdownMenuTrigger$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuTrigger, mergeProps({ class: "outline-none" }, $setup.forwardedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuTrigger.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const DropdownMenuTrigger = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "Avatar", props: { class: {}, size: { default: "sm" }, shape: { default: "circle" } }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarRoot() {
    return AvatarRoot;
  }, get cn() {
    return cn;
  }, get avatarVariant() {
    return avatarVariant;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarRoot, mergeProps({ class: $setup.cn($setup.avatarVariant({ size: $props.size, shape: $props.shape }), $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/Avatar.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const Avatar = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "AvatarFallback", props: { delayMs: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarFallback() {
    return AvatarFallback$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarFallback, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarFallback.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AvatarFallback = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "AvatarImage", props: { src: {}, referrerPolicy: {}, crossOrigin: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarImage() {
    return AvatarImage$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarImage, mergeProps($setup.props, { class: "h-full w-full object-cover" }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarImage.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AvatarImage = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
const avatarVariant = cva(
  "inline-flex items-center justify-center font-normal text-foreground select-none shrink-0 bg-secondary overflow-hidden",
  {
    variants: {
      size: {
        sm: "h-10 w-10 text-xs",
        base: "h-16 w-16 text-2xl",
        lg: "h-32 w-32 text-5xl"
      },
      shape: {
        circle: "rounded-full",
        square: "rounded-md"
      }
    }
  }
);
export {
  AvatarFallback as A,
  DropdownMenuTrigger as D,
  AvatarImage as a,
  Avatar as b,
  DropdownMenuItem as c,
  DropdownMenuContent as d,
  DropdownMenu as e,
  DropdownMenuSeparator as f,
  DropdownMenuLabel as g
};
