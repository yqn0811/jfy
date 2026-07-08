import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot$1 } from "./astro/server.DafmnnCm.js";
import { _ as _export_sfc, $ as $$BaseLayout } from "./BaseLayout.BHhPB8Is.js";
import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode, ref, computed, onMounted, toDisplayString, createTextVNode } from "vue";
import { c as cn, B as Button } from "./index.C8wo6kix.js";
import { I as Input } from "./Input.CgFxAcv5.js";
import { cva } from "class-variance-authority";
import { AvatarRoot, AvatarFallback as AvatarFallback$1, AvatarImage as AvatarImage$1, useForwardPropsEmits, DropdownMenuRoot, DropdownMenuItemIndicator, DropdownMenuCheckboxItem, DropdownMenuPortal, DropdownMenuContent as DropdownMenuContent$1, DropdownMenuGroup, useForwardProps, DropdownMenuItem as DropdownMenuItem$1, DropdownMenuLabel as DropdownMenuLabel$1, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator as DropdownMenuSeparator$1, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger, DropdownMenuTrigger as DropdownMenuTrigger$1 } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs, ssrRenderList, ssrInterpolate } from "vue/server-renderer";
import { reactiveOmit } from "@vueuse/core";
import { Check, Circle, ChevronRight } from "lucide-vue-next";
import { S as SafeIcon } from "./SafeIcon.DpfPD-xe.js";
import { L as LoginDialog } from "./LoginDialog.DURL3b3H.js";
import { d as authStore, p as pcApi } from "./DialogTrigger.C1GEKDer.js";
/* empty css                                   */
const _sfc_main$h = defineComponent({ __name: "Avatar", props: { class: {}, size: { default: "sm" }, shape: { default: "circle" } }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$h(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarRoot, mergeProps({ class: $setup.cn($setup.avatarVariant({ size: $props.size, shape: $props.shape }), $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/Avatar.vue"), _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
const Avatar = _export_sfc(_sfc_main$h, [["ssrRender", _sfc_ssrRender$h]]);
const _sfc_main$g = defineComponent({ __name: "AvatarFallback", props: { delayMs: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarFallback() {
    return AvatarFallback$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$g(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarFallback, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarFallback.vue"), _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const AvatarFallback = _export_sfc(_sfc_main$g, [["ssrRender", _sfc_ssrRender$g]]);
const _sfc_main$f = defineComponent({ __name: "AvatarImage", props: { src: {}, referrerPolicy: {}, crossOrigin: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarImage() {
    return AvatarImage$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$f(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarImage, mergeProps($setup.props, { class: "h-full w-full object-cover" }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarImage.vue"), _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const AvatarImage = _export_sfc(_sfc_main$f, [["ssrRender", _sfc_ssrRender$f]]);
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
const _sfc_main$e = defineComponent({ __name: "DropdownMenu", props: { defaultOpen: { type: Boolean }, open: { type: Boolean }, dir: {}, modal: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRoot() {
    return DropdownMenuRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$e(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenu.vue"), _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const DropdownMenu = _export_sfc(_sfc_main$e, [["ssrRender", _sfc_ssrRender$e]]);
const _sfc_main$d = defineComponent({ __name: "DropdownMenuCheckboxItem", props: { modelValue: { type: [Boolean, String] }, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select", "update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$d(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuCheckboxItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Check, { class: "w-4 h-4" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Check, { class: "w-4 h-4" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Check, { class: "w-4 h-4" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue"), _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
_export_sfc(_sfc_main$d, [["ssrRender", _sfc_ssrRender$d]]);
const _sfc_main$c = defineComponent({ __name: "DropdownMenuContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, side: {}, sideOffset: { default: 4 }, sideFlip: { type: Boolean }, align: {}, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$c(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuContent.vue"), _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const DropdownMenuContent = _export_sfc(_sfc_main$c, [["ssrRender", _sfc_ssrRender$c]]);
const _sfc_main$b = defineComponent({ __name: "DropdownMenuGroup", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DropdownMenuGroup() {
    return DropdownMenuGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$b(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuGroup, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuGroup.vue"), _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
_export_sfc(_sfc_main$b, [["ssrRender", _sfc_ssrRender$b]]);
const _sfc_main$a = defineComponent({ __name: "DropdownMenuItem", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuItem() {
    return DropdownMenuItem$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$a(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuItem, mergeProps($setup.forwardedProps, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm gap-2 px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuItem.vue"), _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const DropdownMenuItem = _export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$a]]);
const _sfc_main$9 = defineComponent({ __name: "DropdownMenuLabel", props: { asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuLabel() {
    return DropdownMenuLabel$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuLabel, mergeProps($setup.forwardedProps, { class: $setup.cn("px-2 py-1.5 text-sm font-semibold", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuLabel.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const DropdownMenuLabel = _export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "DropdownMenuRadioGroup", props: { modelValue: {}, asChild: { type: Boolean }, as: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRadioGroup() {
    return DropdownMenuRadioGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioGroup, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioGroup.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
_export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "DropdownMenuRadioItem", props: { value: {}, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Circle, { class: "h-2 w-2 fill-current" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioItem.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
_export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "DropdownMenuSeparator", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get DropdownMenuSeparator() {
    return DropdownMenuSeparator$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSeparator, mergeProps($setup.delegatedProps, { class: $setup.cn("-mx-1 my-1 h-px bg-muted", $setup.props.class) }, _attrs), null, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSeparator.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const DropdownMenuSeparator = _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "DropdownMenuShortcut", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<span${ssrRenderAttrs(mergeProps({ class: $setup.cn("ml-auto text-xs tracking-widest opacity-60", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</span>");
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuShortcut.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
_export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "DropdownMenuSub", props: { defaultOpen: { type: Boolean }, open: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuSub() {
    return DropdownMenuSub;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSub, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSub.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
_export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "DropdownMenuSubContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, sideOffset: {}, sideFlip: { type: Boolean }, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "entryFocus", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get DropdownMenuSubContent() {
    return DropdownMenuSubContent;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubContent.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
_export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "DropdownMenuSubTrigger", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none focus:bg-accent data-[state=open]:bg-accent", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2(ssrRenderComponent($setup.ChevronRight, { class: "ml-auto h-4 w-4" }, null, _parent2, _scopeId));
    else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.ChevronRight, { class: "ml-auto h-4 w-4" })];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubTrigger.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
_export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "DropdownMenuTrigger", props: { disabled: { type: Boolean }, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, forwardedProps = useForwardProps(props), __returned__ = { props, forwardedProps, get DropdownMenuTrigger() {
    return DropdownMenuTrigger$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuTrigger, mergeProps({ class: "outline-none" }, $setup.forwardedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuTrigger.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const DropdownMenuTrigger = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "CommonHeader", props: { isAuthenticated: { type: Boolean, default: false }, userName: { default: "访客用户" }, userAvatar: { default: "" }, currentPath: { default: "./share-home.html" } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, searchQuery = ref(""), showLoginDialog = ref(false), userInfo = ref({}), isHydrated = ref(false), loggedIn = computed(() => isHydrated.value && (props.isAuthenticated || authStore.isLoggedIn())), displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || props.userName), displayAvatar = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || props.userAvatar), navItems = [{ name: "我的收藏", href: "./favorites.html", icon: "Heart" }, { name: "浏览足迹", href: "./browsing-history.html", icon: "History" }], handleSearch = () => {
    searchQuery.value.trim() && (window.location.href = `./share-home.html?keyword=${encodeURIComponent(searchQuery.value.trim())}`);
  }, handleNavigate = (href) => {
    window.location.href = href;
  }, handleMerchantLogin = () => {
    if (loggedIn.value) {
      handleNavigate("./management-workbench.html");
      return;
    }
    showLoginDialog.value = true;
  }, handleLogout = () => {
    authStore.clearToken(), userInfo.value = {}, window.location.reload();
  }, handleLoginSuccess = async () => {
    userInfo.value = authStore.getUser() || {};
    try {
      const profile = await pcApi.getCurrentUser();
      userInfo.value = profile || {}, authStore.setUser(profile);
    } catch {
    }
    handleNavigate("./management-workbench.html");
  }, isActive = (href) => props.currentPath?.includes(href.replace("./", ""));
  onMounted(() => {
    authStore.consumeCallbackToken(), isHydrated.value = true, userInfo.value = authStore.getUser() || {}, authStore.isLoggedIn() && pcApi.getCurrentUser().then((profile) => {
      userInfo.value = profile || {}, authStore.setUser(profile);
    }).catch(() => {
    });
  });
  const __returned__ = { props, searchQuery, showLoginDialog, userInfo, isHydrated, loggedIn, displayName, displayAvatar, navItems, handleSearch, handleNavigate, handleMerchantLogin, handleLogout, handleLoginSuccess, isActive, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, get Avatar() {
    return Avatar;
  }, get AvatarImage() {
    return AvatarImage;
  }, get AvatarFallback() {
    return AvatarFallback;
  }, get DropdownMenu() {
    return DropdownMenu;
  }, get DropdownMenuContent() {
    return DropdownMenuContent;
  }, get DropdownMenuItem() {
    return DropdownMenuItem;
  }, get DropdownMenuLabel() {
    return DropdownMenuLabel;
  }, get DropdownMenuSeparator() {
    return DropdownMenuSeparator;
  }, get DropdownMenuTrigger() {
    return DropdownMenuTrigger;
  }, SafeIcon, LoginDialog, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<header${ssrRenderAttrs(mergeProps({ class: "sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60" }, _attrs))}><div class="h-[var(--header-height)] page-container px-8 flex items-center justify-between gap-8"><div class="flex items-center gap-2 cursor-pointer shrink-0"><div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">`), _push(ssrRenderComponent($setup.SafeIcon, { name: "Cloud", color: "white", size: 20 }, null, _parent)), _push('</div><span class="text-xl font-bold tracking-tight text-primary">家纺云分享</span></div><div class="flex-1 max-w-xl relative">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Search", class: "absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground", size: 18 }, null, _parent)), _push(ssrRenderComponent($setup.Input, { modelValue: $setup.searchQuery, "onUpdate:modelValue": ($event) => $setup.searchQuery = $event, placeholder: "搜索产品名称...", class: "pl-10 h-10 w-full bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary", onKeyup: $setup.handleSearch }, null, _parent)), _push('</div><div class="flex items-center gap-2"><nav class="flex items-center gap-1 mr-4"><!--[-->'), ssrRenderList($setup.navItems, (item) => {
    _push(ssrRenderComponent($setup.Button, { key: item.href, variant: "ghost", class: $setup.cn("flex items-center gap-2 px-4 h-10 text-muted-foreground hover:text-primary hover:bg-primary/5 transition-all", $setup.isActive(item.href) && "text-primary bg-primary/10"), onClick: ($event) => $setup.handleNavigate(item.href) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
      if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: item.icon, size: 20 }, null, _parent2, _scopeId)), _push2(`<span class="text-sm font-medium"${_scopeId}>${ssrInterpolate(item.name)}</span>`);
      else return [createVNode($setup.SafeIcon, { name: item.icon, size: 20 }, null, 8, ["name"]), createVNode("span", { class: "text-sm font-medium" }, toDisplayString(item.name), 1)];
    }), _: 2 }, _parent));
  }), _push("<!--]--></nav>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push('<div class="h-8 w-px bg-border mx-2"></div>'), $setup.loggedIn ? (_push("<div>"), _push(ssrRenderComponent($setup.DropdownMenu, null, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(ssrRenderComponent($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, _parent5, _scopeId4)), _push5(ssrRenderComponent($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
            if (_push6) _push6(`${ssrInterpolate($setup.displayName.substring(0, 1))}`);
            else return [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)];
          }), _: 1 }, _parent5, _scopeId4));
          else return [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(`<div class="flex flex-col space-y-1"${_scopeId3}><p class="text-sm font-medium leading-none"${_scopeId3}>${ssrInterpolate($setup.displayName)}</p><p class="text-xs leading-none text-muted-foreground"${_scopeId3}>欢迎使用家纺云</p></div>`);
        else return [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuSeparator, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>管理工作台</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>退出登录</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx(() => [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div>")) : (_push("<div>"), _push(ssrRenderComponent($setup.Button, { variant: "default", size: "sm", class: "h-9 px-6 rounded-full", onClick: $setup.handleMerchantLogin }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(" 商家登录 ");
    else return [createTextVNode(" 商家登录 ")];
  }), _: 1 }, _parent)), _push("</div>")), _push("</div></div>"), _push(ssrRenderComponent($setup.LoginDialog, { open: $setup.showLoginDialog, "onUpdate:open": ($event) => $setup.showLoginDialog = $event, onLoginSuccess: $setup.handleLoginSuccess }, null, _parent)), _push("</header>");
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/CommonHeader.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CommonHeader = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
const $$Astro = createAstro();
const $$StandardLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$StandardLayout;
  const { title, description } = Astro.props;
  return renderTemplate`${renderComponent($$result, "BaseLayout", $$BaseLayout, { title, description }, { default: ($$result2) => renderTemplate`
  ${maybeRenderHead()}<div class="flex flex-col h-screen overflow-hidden bg-background">
    <!-- CommonHeader owns the logo and top-level navigation -->
    ${renderComponent($$result2, "CommonHeader", CommonHeader, { "client:load": true, currentPath: Astro.url.pathname, "client:component-hydration": "load", "client:component-path": "@/components/common/CommonHeader.vue", "client:component-export": "default" })}
    
    <!-- Main Scrollable Area -->
    <main class="flex-1 overflow-y-auto min-h-0">
      <div class="page-container">
        ${renderSlot$1($$result2, $$slots.default)}
      </div>
      
      <!-- Footer (Optional, briefly included for professional look) -->
      <footer class="border-t border-border bg-card/50 py-8 px-8">
        <div class="page-container flex justify-between items-center text-caption">
          <div>© 2026 家纺云分享系统. 版权所有</div>
          <div class="flex gap-6">
            <a href="#" class="hover:text-primary">服务条款</a>
            <a href="#" class="hover:text-primary">隐私政策</a>
            <a href="#" class="hover:text-primary">联系客服</a>
          </div>
        </div>
      </footer>
    </main>
  </div>
` })}`;
}, "/Users/mac/Documents/trae_projects/sub2api/ai_jf/.codex_tmp/jfy-sync/album-web/src/layouts/StandardLayout.astro", void 0);
export {
  $$StandardLayout as $,
  AvatarFallback as A,
  AvatarImage as a,
  Avatar as b
};
