import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot$1 } from "./astro/server.DafmnnCm.js";
import { _ as _export_sfc, $ as $$BaseLayout } from "./BaseLayout.d5ww63VJ.js";
import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode, computed, createTextVNode, toDisplayString, unref, ref, onMounted, createBlock, openBlock, Fragment, renderList, createCommentVNode, reactive, watch } from "vue";
import { c as cn, b as buttonVariants, B as Button } from "./index.CH7kJXp0.js";
import { u as unwrapList, d as mapPcRecord, I as Input, e as buildPcTargetUrl } from "./Input.A1B5IPr9.js";
import { cva } from "class-variance-authority";
import { AvatarRoot, AvatarFallback as AvatarFallback$1, AvatarImage as AvatarImage$1, useForwardPropsEmits, DropdownMenuRoot, DropdownMenuItemIndicator, DropdownMenuCheckboxItem, DropdownMenuPortal, DropdownMenuContent as DropdownMenuContent$1, DropdownMenuGroup, useForwardProps, DropdownMenuItem as DropdownMenuItem$1, DropdownMenuLabel as DropdownMenuLabel$1, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator as DropdownMenuSeparator$1, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger, DropdownMenuTrigger as DropdownMenuTrigger$1, TabsRoot, TabsContent, TabsList as TabsList$1, TabsTrigger as TabsTrigger$1, AlertDialogRoot, AlertDialogAction as AlertDialogAction$1, AlertDialogCancel as AlertDialogCancel$1, AlertDialogPortal, AlertDialogOverlay, AlertDialogContent as AlertDialogContent$1, AlertDialogDescription as AlertDialogDescription$1, AlertDialogTitle as AlertDialogTitle$1, AlertDialogTrigger } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs, ssrRenderStyle, ssrRenderClass, ssrInterpolate, ssrRenderList, ssrRenderAttr } from "vue/server-renderer";
import { d as authStore, p as pcApi, h as DialogDescription, D as DialogTitle, a as DialogHeader, i as DialogScrollContent, c as Dialog } from "./DialogTrigger.BdMU2fxD.js";
import { reactiveOmit } from "@vueuse/core";
import { Check, Circle, ChevronRight } from "lucide-vue-next";
import { S as SafeIcon } from "./SafeIcon.CU-R1NL6.js";
/* empty css                                   */
import { L as LoginDialog } from "./LoginDialog.BK5VqkJs.js";
import { toast } from "vue-sonner";
const _sfc_main$K = defineComponent({ __name: "Avatar", props: { class: {}, size: { default: "sm" }, shape: { default: "circle" } }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$K(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarRoot, mergeProps({ class: $setup.cn($setup.avatarVariant({ size: $props.size, shape: $props.shape }), $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$K = _sfc_main$K.setup;
_sfc_main$K.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/Avatar.vue"), _sfc_setup$K ? _sfc_setup$K(props, ctx) : void 0;
};
const Avatar = _export_sfc(_sfc_main$K, [["ssrRender", _sfc_ssrRender$K]]);
const _sfc_main$J = defineComponent({ __name: "AvatarFallback", props: { delayMs: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarFallback() {
    return AvatarFallback$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$J(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarFallback, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$J = _sfc_main$J.setup;
_sfc_main$J.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarFallback.vue"), _sfc_setup$J ? _sfc_setup$J(props, ctx) : void 0;
};
const AvatarFallback = _export_sfc(_sfc_main$J, [["ssrRender", _sfc_ssrRender$J]]);
const _sfc_main$I = defineComponent({ __name: "AvatarImage", props: { src: {}, referrerPolicy: {}, crossOrigin: {}, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AvatarImage() {
    return AvatarImage$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$I(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AvatarImage, mergeProps($setup.props, { class: "h-full w-full object-cover" }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$I = _sfc_main$I.setup;
_sfc_main$I.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/avatar/AvatarImage.vue"), _sfc_setup$I ? _sfc_setup$I(props, ctx) : void 0;
};
const AvatarImage = _export_sfc(_sfc_main$I, [["ssrRender", _sfc_ssrRender$I]]);
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
const _sfc_main$H = defineComponent({ __name: "DropdownMenu", props: { defaultOpen: { type: Boolean }, open: { type: Boolean }, dir: {}, modal: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRoot() {
    return DropdownMenuRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$H(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$H = _sfc_main$H.setup;
_sfc_main$H.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenu.vue"), _sfc_setup$H ? _sfc_setup$H(props, ctx) : void 0;
};
const DropdownMenu = _export_sfc(_sfc_main$H, [["ssrRender", _sfc_ssrRender$H]]);
const _sfc_main$G = defineComponent({ __name: "DropdownMenuCheckboxItem", props: { modelValue: { type: [Boolean, String] }, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select", "update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$G(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuCheckboxItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Check, { class: "w-4 h-4" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Check, { class: "w-4 h-4" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Check, { class: "w-4 h-4" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$G = _sfc_main$G.setup;
_sfc_main$G.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue"), _sfc_setup$G ? _sfc_setup$G(props, ctx) : void 0;
};
_export_sfc(_sfc_main$G, [["ssrRender", _sfc_ssrRender$G]]);
const _sfc_main$F = defineComponent({ __name: "DropdownMenuContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, side: {}, sideOffset: { default: 4 }, sideFlip: { type: Boolean }, align: {}, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$F(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$F = _sfc_main$F.setup;
_sfc_main$F.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuContent.vue"), _sfc_setup$F ? _sfc_setup$F(props, ctx) : void 0;
};
const DropdownMenuContent = _export_sfc(_sfc_main$F, [["ssrRender", _sfc_ssrRender$F]]);
const _sfc_main$E = defineComponent({ __name: "DropdownMenuGroup", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DropdownMenuGroup() {
    return DropdownMenuGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$E(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuGroup, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$E = _sfc_main$E.setup;
_sfc_main$E.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuGroup.vue"), _sfc_setup$E ? _sfc_setup$E(props, ctx) : void 0;
};
_export_sfc(_sfc_main$E, [["ssrRender", _sfc_ssrRender$E]]);
const _sfc_main$D = defineComponent({ __name: "DropdownMenuItem", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuItem() {
    return DropdownMenuItem$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$D(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuItem, mergeProps($setup.forwardedProps, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm gap-2 px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$D = _sfc_main$D.setup;
_sfc_main$D.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuItem.vue"), _sfc_setup$D ? _sfc_setup$D(props, ctx) : void 0;
};
const DropdownMenuItem = _export_sfc(_sfc_main$D, [["ssrRender", _sfc_ssrRender$D]]);
const _sfc_main$C = defineComponent({ __name: "DropdownMenuLabel", props: { asChild: { type: Boolean }, as: {}, class: {}, inset: { type: Boolean } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DropdownMenuLabel() {
    return DropdownMenuLabel$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$C(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuLabel, mergeProps($setup.forwardedProps, { class: $setup.cn("px-2 py-1.5 text-sm font-semibold", $props.inset && "pl-8", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$C = _sfc_main$C.setup;
_sfc_main$C.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuLabel.vue"), _sfc_setup$C ? _sfc_setup$C(props, ctx) : void 0;
};
const DropdownMenuLabel = _export_sfc(_sfc_main$C, [["ssrRender", _sfc_ssrRender$C]]);
const _sfc_main$B = defineComponent({ __name: "DropdownMenuRadioGroup", props: { modelValue: {}, asChild: { type: Boolean }, as: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuRadioGroup() {
    return DropdownMenuRadioGroup;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$B(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioGroup, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$B = _sfc_main$B.setup;
_sfc_main$B.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioGroup.vue"), _sfc_setup$B ? _sfc_setup$B(props, ctx) : void 0;
};
_export_sfc(_sfc_main$B, [["ssrRender", _sfc_ssrRender$B]]);
const _sfc_main$A = defineComponent({ __name: "DropdownMenuRadioItem", props: { value: {}, disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["select"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$A(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuRadioItem, mergeProps($setup.forwarded, { class: $setup.cn("relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"${_scopeId}>`), _push2(ssrRenderComponent($setup.DropdownMenuItemIndicator, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.Circle, { class: "h-2 w-2 fill-current" }, null, _parent3, _scopeId2));
      else return [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })];
    }), _: 1 }, _parent2, _scopeId)), _push2("</span>"), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [createVNode("span", { class: "absolute left-2 flex h-3.5 w-3.5 items-center justify-center" }, [createVNode($setup.DropdownMenuItemIndicator, null, { default: withCtx(() => [createVNode($setup.Circle, { class: "h-2 w-2 fill-current" })]), _: 1 })]), renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$A = _sfc_main$A.setup;
_sfc_main$A.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuRadioItem.vue"), _sfc_setup$A ? _sfc_setup$A(props, ctx) : void 0;
};
_export_sfc(_sfc_main$A, [["ssrRender", _sfc_ssrRender$A]]);
const _sfc_main$z = defineComponent({ __name: "DropdownMenuSeparator", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get DropdownMenuSeparator() {
    return DropdownMenuSeparator$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$z(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSeparator, mergeProps($setup.delegatedProps, { class: $setup.cn("-mx-1 my-1 h-px bg-muted", $setup.props.class) }, _attrs), null, _parent));
}
const _sfc_setup$z = _sfc_main$z.setup;
_sfc_main$z.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSeparator.vue"), _sfc_setup$z ? _sfc_setup$z(props, ctx) : void 0;
};
const DropdownMenuSeparator = _export_sfc(_sfc_main$z, [["ssrRender", _sfc_ssrRender$z]]);
const _sfc_main$y = defineComponent({ __name: "DropdownMenuShortcut", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$y(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<span${ssrRenderAttrs(mergeProps({ class: $setup.cn("ml-auto text-xs tracking-widest opacity-60", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</span>");
}
const _sfc_setup$y = _sfc_main$y.setup;
_sfc_main$y.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuShortcut.vue"), _sfc_setup$y ? _sfc_setup$y(props, ctx) : void 0;
};
_export_sfc(_sfc_main$y, [["ssrRender", _sfc_ssrRender$y]]);
const _sfc_main$x = defineComponent({ __name: "DropdownMenuSub", props: { defaultOpen: { type: Boolean }, open: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DropdownMenuSub() {
    return DropdownMenuSub;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$x(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSub, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$x = _sfc_main$x.setup;
_sfc_main$x.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSub.vue"), _sfc_setup$x ? _sfc_setup$x(props, ctx) : void 0;
};
_export_sfc(_sfc_main$x, [["ssrRender", _sfc_ssrRender$x]]);
const _sfc_main$w = defineComponent({ __name: "DropdownMenuSubContent", props: { forceMount: { type: Boolean }, loop: { type: Boolean }, sideOffset: {}, sideFlip: { type: Boolean }, alignOffset: {}, alignFlip: { type: Boolean }, avoidCollisions: { type: Boolean }, collisionBoundary: {}, collisionPadding: {}, arrowPadding: {}, sticky: {}, hideWhenDetached: { type: Boolean }, positionStrategy: {}, updatePositionStrategy: {}, disableUpdateOnLayoutShift: { type: Boolean }, prioritizePosition: { type: Boolean }, reference: {}, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "entryFocus", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get DropdownMenuSubContent() {
    return DropdownMenuSubContent;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$w(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubContent, mergeProps($setup.forwarded, { class: $setup.cn("z-50 min-w-32 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$w = _sfc_main$w.setup;
_sfc_main$w.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubContent.vue"), _sfc_setup$w ? _sfc_setup$w(props, ctx) : void 0;
};
_export_sfc(_sfc_main$w, [["ssrRender", _sfc_ssrRender$w]]);
const _sfc_main$v = defineComponent({ __name: "DropdownMenuSubTrigger", props: { disabled: { type: Boolean }, textValue: {}, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$v(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuSubTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none focus:bg-accent data-[state=open]:bg-accent", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2(ssrRenderComponent($setup.ChevronRight, { class: "ml-auto h-4 w-4" }, null, _parent2, _scopeId));
    else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.ChevronRight, { class: "ml-auto h-4 w-4" })];
  }), _: 3 }, _parent));
}
const _sfc_setup$v = _sfc_main$v.setup;
_sfc_main$v.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuSubTrigger.vue"), _sfc_setup$v ? _sfc_setup$v(props, ctx) : void 0;
};
_export_sfc(_sfc_main$v, [["ssrRender", _sfc_ssrRender$v]]);
const _sfc_main$u = defineComponent({ __name: "DropdownMenuTrigger", props: { disabled: { type: Boolean }, asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, forwardedProps = useForwardProps(props), __returned__ = { props, forwardedProps, get DropdownMenuTrigger() {
    return DropdownMenuTrigger$1;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$u(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DropdownMenuTrigger, mergeProps({ class: "outline-none" }, $setup.forwardedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$u = _sfc_main$u.setup;
_sfc_main$u.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dropdown-menu/DropdownMenuTrigger.vue"), _sfc_setup$u ? _sfc_setup$u(props, ctx) : void 0;
};
const DropdownMenuTrigger = _export_sfc(_sfc_main$u, [["ssrRender", _sfc_ssrRender$u]]);
const _sfc_main$t = defineComponent({ __name: "BrandMark", props: { size: { default: 32 } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, markStyle = computed(() => ({ width: `${props.size}px`, height: `${props.size}px`, borderRadius: `${Math.max(6, Math.round(props.size * 0.17))}px` })), innerStyle = computed(() => ({ width: `${Math.round(props.size * 0.58)}px`, height: `${Math.round(props.size * 0.37)}px`, borderRadius: `${Math.max(4, Math.round(props.size * 0.09))}px` })), __returned__ = { props, markStyle, innerStyle };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$t(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<span${ssrRenderAttrs(mergeProps({ class: "brand-mark", style: $setup.markStyle, "aria-hidden": "true" }, _attrs))} data-v-db442163><span class="brand-mark-inner" style="${ssrRenderStyle($setup.innerStyle)}" data-v-db442163></span></span>`);
}
const _sfc_setup$t = _sfc_main$t.setup;
_sfc_main$t.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/BrandMark.vue"), _sfc_setup$t ? _sfc_setup$t(props, ctx) : void 0;
};
const BrandMark = _export_sfc(_sfc_main$t, [["ssrRender", _sfc_ssrRender$t], ["__scopeId", "data-v-db442163"]]);
const _sfc_main$s = defineComponent({ __name: "Tabs", props: { defaultValue: {}, orientation: {}, dir: {}, activationMode: {}, modelValue: {}, unmountOnHide: { type: Boolean }, asChild: { type: Boolean }, as: {} }, emits: ["update:modelValue"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get TabsRoot() {
    return TabsRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$s(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$s = _sfc_main$s.setup;
_sfc_main$s.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/Tabs.vue"), _sfc_setup$s ? _sfc_setup$s(props, ctx) : void 0;
};
const Tabs = _export_sfc(_sfc_main$s, [["ssrRender", _sfc_ssrRender$s]]);
const _sfc_main$r = defineComponent({ __name: "TabsContent", props: { value: {}, forceMount: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get TabsContent() {
    return TabsContent;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$r(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsContent, mergeProps({ class: $setup.cn("mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2", $setup.props.class) }, $setup.delegatedProps, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$r = _sfc_main$r.setup;
_sfc_main$r.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsContent.vue"), _sfc_setup$r ? _sfc_setup$r(props, ctx) : void 0;
};
_export_sfc(_sfc_main$r, [["ssrRender", _sfc_ssrRender$r]]);
const _sfc_main$q = defineComponent({ __name: "TabsList", props: { loop: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get TabsList() {
    return TabsList$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$q(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsList, mergeProps($setup.delegatedProps, { class: $setup.cn("inline-flex items-center justify-center rounded-md bg-muted p-1 text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$q = _sfc_main$q.setup;
_sfc_main$q.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsList.vue"), _sfc_setup$q ? _sfc_setup$q(props, ctx) : void 0;
};
const TabsList = _export_sfc(_sfc_main$q, [["ssrRender", _sfc_ssrRender$q]]);
const _sfc_main$p = defineComponent({ __name: "TabsTrigger", props: { value: {}, disabled: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get TabsTrigger() {
    return TabsTrigger$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$p(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TabsTrigger, mergeProps($setup.forwardedProps, { class: $setup.cn("inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(`<span class="truncate"${_scopeId}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId), _push2("</span>");
    else return [createVNode("span", { class: "truncate" }, [renderSlot(_ctx.$slots, "default")])];
  }), _: 3 }, _parent));
}
const _sfc_setup$p = _sfc_main$p.setup;
_sfc_main$p.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/tabs/TabsTrigger.vue"), _sfc_setup$p ? _sfc_setup$p(props, ctx) : void 0;
};
const TabsTrigger = _export_sfc(_sfc_main$p, [["ssrRender", _sfc_ssrRender$p]]);
const _sfc_main$o = defineComponent({ __name: "Table", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$o(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative w-full overflow-auto" }, _attrs))}><table class="${ssrRenderClass($setup.cn("w-full caption-bottom text-sm", $setup.props.class))}">`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</table></div>");
}
const _sfc_setup$o = _sfc_main$o.setup;
_sfc_main$o.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/Table.vue"), _sfc_setup$o ? _sfc_setup$o(props, ctx) : void 0;
};
const Table = _export_sfc(_sfc_main$o, [["ssrRender", _sfc_ssrRender$o]]);
const _sfc_main$n = defineComponent({ __name: "TableBody", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$n(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tbody${ssrRenderAttrs(mergeProps({ class: $setup.cn("[&_tr:last-child]:border-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tbody>");
}
const _sfc_setup$n = _sfc_main$n.setup;
_sfc_main$n.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableBody.vue"), _sfc_setup$n ? _sfc_setup$n(props, ctx) : void 0;
};
const TableBody = _export_sfc(_sfc_main$n, [["ssrRender", _sfc_ssrRender$n]]);
const _sfc_main$m = defineComponent({ __name: "TableCaption", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$m(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<caption${ssrRenderAttrs(mergeProps({ class: $setup.cn("mt-4 text-sm text-muted-foreground", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</caption>");
}
const _sfc_setup$m = _sfc_main$m.setup;
_sfc_main$m.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableCaption.vue"), _sfc_setup$m ? _sfc_setup$m(props, ctx) : void 0;
};
_export_sfc(_sfc_main$m, [["ssrRender", _sfc_ssrRender$m]]);
const _sfc_main$l = defineComponent({ __name: "TableCell", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$l(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<td${ssrRenderAttrs(mergeProps({ class: $setup.cn("p-4 align-middle [&:has([role=checkbox])]:pr-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</td>");
}
const _sfc_setup$l = _sfc_main$l.setup;
_sfc_main$l.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableCell.vue"), _sfc_setup$l ? _sfc_setup$l(props, ctx) : void 0;
};
const TableCell = _export_sfc(_sfc_main$l, [["ssrRender", _sfc_ssrRender$l]]);
const _sfc_main$k = defineComponent({ __name: "TableRow", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$k(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tr${ssrRenderAttrs(mergeProps({ class: $setup.cn("border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tr>");
}
const _sfc_setup$k = _sfc_main$k.setup;
_sfc_main$k.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableRow.vue"), _sfc_setup$k ? _sfc_setup$k(props, ctx) : void 0;
};
const TableRow = _export_sfc(_sfc_main$k, [["ssrRender", _sfc_ssrRender$k]]);
const _sfc_main$j = defineComponent({ __name: "TableEmpty", props: { class: {}, colspan: { default: 1 } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get cn() {
    return cn;
  }, TableCell, TableRow };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$j(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TableRow, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TableCell, mergeProps({ class: $setup.cn("p-4 whitespace-nowrap align-middle text-sm text-foreground", $setup.props.class) }, $setup.delegatedProps), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex items-center justify-center py-10"${_scopeId2}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3("</div>");
      else return [createVNode("div", { class: "flex items-center justify-center py-10" }, [renderSlot(_ctx.$slots, "default")])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.TableCell, mergeProps({ class: $setup.cn("p-4 whitespace-nowrap align-middle text-sm text-foreground", $setup.props.class) }, $setup.delegatedProps), { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-center py-10" }, [renderSlot(_ctx.$slots, "default")])]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$j = _sfc_main$j.setup;
_sfc_main$j.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableEmpty.vue"), _sfc_setup$j ? _sfc_setup$j(props, ctx) : void 0;
};
_export_sfc(_sfc_main$j, [["ssrRender", _sfc_ssrRender$j]]);
const _sfc_main$i = defineComponent({ __name: "TableFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$i(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tfoot${ssrRenderAttrs(mergeProps({ class: $setup.cn("border-t bg-muted/50 font-medium [&>tr]:last:border-b-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tfoot>");
}
const _sfc_setup$i = _sfc_main$i.setup;
_sfc_main$i.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableFooter.vue"), _sfc_setup$i ? _sfc_setup$i(props, ctx) : void 0;
};
_export_sfc(_sfc_main$i, [["ssrRender", _sfc_ssrRender$i]]);
const _sfc_main$h = defineComponent({ __name: "TableHead", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$h(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<th${ssrRenderAttrs(mergeProps({ class: $setup.cn("h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</th>");
}
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableHead.vue"), _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
const TableHead = _export_sfc(_sfc_main$h, [["ssrRender", _sfc_ssrRender$h]]);
const _sfc_main$g = defineComponent({ __name: "TableHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$g(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<thead${ssrRenderAttrs(mergeProps({ class: $setup.cn("[&_tr]:border-b", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</thead>");
}
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableHeader.vue"), _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const TableHeader = _export_sfc(_sfc_main$g, [["ssrRender", _sfc_ssrRender$g]]);
const _sfc_main$f = defineComponent({ __name: "EmptyState", props: { icon: {}, title: {}, description: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, SafeIcon, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$f(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("empty-state", $setup.props.class) }, _attrs))} data-v-6629f6f7><div class="flex items-center justify-center w-20 h-20 rounded-full bg-muted/50 mb-2" data-v-6629f6f7>`), _push(ssrRenderComponent($setup.SafeIcon, { name: $setup.props.icon, size: 40, "stroke-width": 1.5, class: "text-muted-foreground/60" }, null, _parent)), _push(`</div><div class="space-y-2 max-w-sm px-4" data-v-6629f6f7><h3 class="text-section-title text-foreground" data-v-6629f6f7>${ssrInterpolate($setup.props.title)}</h3>`), $setup.props.description ? _push(`<p class="text-caption" data-v-6629f6f7>${ssrInterpolate($setup.props.description)}</p>`) : _push("<!---->"), _push("</div>"), _ctx.$slots.default ? (_push('<div class="pt-4 flex flex-wrap justify-center gap-3" data-v-6629f6f7>'), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>")) : _push("<!---->"), _push("</div>");
}
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/EmptyState.vue"), _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const EmptyState = _export_sfc(_sfc_main$f, [["ssrRender", _sfc_ssrRender$f], ["__scopeId", "data-v-6629f6f7"]]);
const _sfc_main$e = defineComponent({ __name: "AlertDialog", props: { open: { type: Boolean }, defaultOpen: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get AlertDialogRoot() {
    return AlertDialogRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$e(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialog.vue"), _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const AlertDialog = _export_sfc(_sfc_main$e, [["ssrRender", _sfc_ssrRender$e]]);
const _sfc_main$d = defineComponent({ __name: "AlertDialogAction", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogAction() {
    return AlertDialogAction$1;
  }, get cn() {
    return cn;
  }, get buttonVariants() {
    return buttonVariants;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$d(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogAction, mergeProps($setup.delegatedProps, { class: $setup.cn($setup.buttonVariants(), $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogAction.vue"), _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
const AlertDialogAction = _export_sfc(_sfc_main$d, [["ssrRender", _sfc_ssrRender$d]]);
const _sfc_main$c = defineComponent({ __name: "AlertDialogCancel", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogCancel() {
    return AlertDialogCancel$1;
  }, get cn() {
    return cn;
  }, get buttonVariants() {
    return buttonVariants;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$c(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogCancel, mergeProps($setup.delegatedProps, { class: $setup.cn($setup.buttonVariants({ variant: "outline" }), "mt-2 sm:mt-0", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogCancel.vue"), _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const AlertDialogCancel = _export_sfc(_sfc_main$c, [["ssrRender", _sfc_ssrRender$c]]);
const _sfc_main$b = defineComponent({ __name: "AlertDialogContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get AlertDialogContent() {
    return AlertDialogContent$1;
  }, get AlertDialogOverlay() {
    return AlertDialogOverlay;
  }, get AlertDialogPortal() {
    return AlertDialogPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$b(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.AlertDialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, null, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.AlertDialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.AlertDialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }), createVNode($setup.AlertDialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogContent.vue"), _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const AlertDialogContent = _export_sfc(_sfc_main$b, [["ssrRender", _sfc_ssrRender$b]]);
const _sfc_main$a = defineComponent({ __name: "AlertDialogDescription", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogDescription() {
    return AlertDialogDescription$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$a(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogDescription, mergeProps($setup.delegatedProps, { class: $setup.cn("text-sm text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogDescription.vue"), _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const AlertDialogDescription = _export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$a]]);
const _sfc_main$9 = defineComponent({ __name: "AlertDialogFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-x-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogFooter.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const AlertDialogFooter = _export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "AlertDialogHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col gap-y-2 text-center sm:text-left", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogHeader.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const AlertDialogHeader = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "AlertDialogTitle", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogTitle() {
    return AlertDialogTitle$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogTitle, mergeProps($setup.delegatedProps, { class: $setup.cn("text-lg font-semibold", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogTitle.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const AlertDialogTitle = _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "AlertDialogTrigger", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AlertDialogTrigger() {
    return AlertDialogTrigger;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogTrigger, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogTrigger.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
_export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "ConfirmDialog", props: { open: { type: Boolean }, title: {}, description: {}, confirmText: { default: "确定" }, cancelText: { default: "取消" }, variant: { default: "default" } }, emits: ["update:open", "confirm", "cancel"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, __returned__ = { props, emit, handleConfirm: () => {
    emit("confirm"), emit("update:open", false);
  }, handleCancel: () => {
    emit("cancel"), emit("update:open", false);
  }, get AlertDialog() {
    return AlertDialog;
  }, get AlertDialogAction() {
    return AlertDialogAction;
  }, get AlertDialogCancel() {
    return AlertDialogCancel;
  }, get AlertDialogContent() {
    return AlertDialogContent;
  }, get AlertDialogDescription() {
    return AlertDialogDescription;
  }, get AlertDialogFooter() {
    return AlertDialogFooter;
  }, get AlertDialogHeader() {
    return AlertDialogHeader;
  }, get AlertDialogTitle() {
    return AlertDialogTitle;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialog, mergeProps({ open: $props.open, "onUpdate:open": (val) => $setup.emit("update:open", val) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.AlertDialogContent, { class: "max-w-[400px]" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.AlertDialogHeader, null, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.AlertDialogTitle, { class: "text-xl font-semibold" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(`${ssrInterpolate($props.title)}`);
          else return [createTextVNode(toDisplayString($props.title), 1)];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.AlertDialogDescription, { class: "pt-2 text-muted-foreground leading-relaxed" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(`${ssrInterpolate($props.description)}`);
          else return [createTextVNode(toDisplayString($props.description), 1)];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.AlertDialogTitle, { class: "text-xl font-semibold" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.title), 1)]), _: 1 }), createVNode($setup.AlertDialogDescription, { class: "pt-2 text-muted-foreground leading-relaxed" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.description), 1)]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.AlertDialogFooter, { class: "mt-6" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.AlertDialogCancel, { onClick: $setup.handleCancel, class: "border-border hover:bg-muted text-foreground" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(`${ssrInterpolate($props.cancelText)}`);
          else return [createTextVNode(toDisplayString($props.cancelText), 1)];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.AlertDialogAction, { onClick: $setup.handleConfirm, class: ("cn" in _ctx ? _ctx.cn : unref(cn))("px-6", $props.variant === "destructive" ? "bg-destructive text-destructive-foreground hover:bg-destructive/90" : "bg-primary text-primary-foreground hover:bg-primary/90") }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(`${ssrInterpolate($props.confirmText)}`);
          else return [createTextVNode(toDisplayString($props.confirmText), 1)];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.AlertDialogCancel, { onClick: $setup.handleCancel, class: "border-border hover:bg-muted text-foreground" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.cancelText), 1)]), _: 1 }), createVNode($setup.AlertDialogAction, { onClick: $setup.handleConfirm, class: ("cn" in _ctx ? _ctx.cn : unref(cn))("px-6", $props.variant === "destructive" ? "bg-destructive text-destructive-foreground hover:bg-destructive/90" : "bg-primary text-primary-foreground hover:bg-primary/90") }, { default: withCtx(() => [createTextVNode(toDisplayString($props.confirmText), 1)]), _: 1 }, 8, ["class"])];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.AlertDialogHeader, null, { default: withCtx(() => [createVNode($setup.AlertDialogTitle, { class: "text-xl font-semibold" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.title), 1)]), _: 1 }), createVNode($setup.AlertDialogDescription, { class: "pt-2 text-muted-foreground leading-relaxed" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.description), 1)]), _: 1 })]), _: 1 }), createVNode($setup.AlertDialogFooter, { class: "mt-6" }, { default: withCtx(() => [createVNode($setup.AlertDialogCancel, { onClick: $setup.handleCancel, class: "border-border hover:bg-muted text-foreground" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.cancelText), 1)]), _: 1 }), createVNode($setup.AlertDialogAction, { onClick: $setup.handleConfirm, class: ("cn" in _ctx ? _ctx.cn : unref(cn))("px-6", $props.variant === "destructive" ? "bg-destructive text-destructive-foreground hover:bg-destructive/90" : "bg-primary text-primary-foreground hover:bg-primary/90") }, { default: withCtx(() => [createTextVNode(toDisplayString($props.confirmText), 1)]), _: 1 }, 8, ["class"])]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.AlertDialogContent, { class: "max-w-[400px]" }, { default: withCtx(() => [createVNode($setup.AlertDialogHeader, null, { default: withCtx(() => [createVNode($setup.AlertDialogTitle, { class: "text-xl font-semibold" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.title), 1)]), _: 1 }), createVNode($setup.AlertDialogDescription, { class: "pt-2 text-muted-foreground leading-relaxed" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.description), 1)]), _: 1 })]), _: 1 }), createVNode($setup.AlertDialogFooter, { class: "mt-6" }, { default: withCtx(() => [createVNode($setup.AlertDialogCancel, { onClick: $setup.handleCancel, class: "border-border hover:bg-muted text-foreground" }, { default: withCtx(() => [createTextVNode(toDisplayString($props.cancelText), 1)]), _: 1 }), createVNode($setup.AlertDialogAction, { onClick: $setup.handleConfirm, class: ("cn" in _ctx ? _ctx.cn : unref(cn))("px-6", $props.variant === "destructive" ? "bg-destructive text-destructive-foreground hover:bg-destructive/90" : "bg-primary text-primary-foreground hover:bg-primary/90") }, { default: withCtx(() => [createTextVNode(toDisplayString($props.confirmText), 1)]), _: 1 }, 8, ["class"])]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/ConfirmDialog.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const ConfirmDialog = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "Pagination", props: { current: { default: 1 }, total: { default: 0 }, pageSize: { default: 20 } }, emits: ["update:current", "change"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emit = __emit, totalPages = computed(() => {
    const pages2 = Math.ceil((props.total || 0) / (props.pageSize || 20));
    return pages2 > 0 ? pages2 : 1;
  }), rangeStart = computed(() => (props.current - 1) * props.pageSize + 1), rangeEnd = computed(() => Math.min(props.current * props.pageSize, props.total)), pages = computed(() => {
    const current = props.current, total = totalPages.value, delta = 2, items = [];
    if (total <= 7) for (let i = 1; i <= total; i++) items.push(i);
    else {
      items.push(1), current > delta + 2 && items.push("ellipsis-start");
      const start = Math.max(2, current - delta), end = Math.min(total - 1, current + delta);
      for (let i = start; i <= end; i++) items.push(i);
      current < total - delta - 1 && items.push("ellipsis-end"), items.push(total);
    }
    return items;
  }), __returned__ = { props, emit, totalPages, rangeStart, rangeEnd, pages, goToPage: (page) => {
    page < 1 || page > totalPages.value || page === props.current || (emit("update:current", page), emit("change", page));
  }, get Button() {
    return Button;
  }, SafeIcon, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex flex-wrap items-center justify-between gap-4 py-4 w-full" }, _attrs))}><div class="text-sm text-muted-foreground whitespace-nowrap shrink-0"> 显示 ${ssrInterpolate($props.total > 0 ? $setup.rangeStart : 0)} - ${ssrInterpolate($setup.rangeEnd)} 条，共 ${ssrInterpolate($props.total)} 条记录 </div><div class="flex-1 min-w-0 flex items-center justify-end gap-1.5">`), _push(ssrRenderComponent($setup.Button, { variant: "outline", size: "icon", class: "h-9 w-9", disabled: $props.current === 1, onClick: ($event) => $setup.goToPage($props.current - 1) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "ChevronLeft", size: 16 }, null, _parent2, _scopeId));
    else return [createVNode($setup.SafeIcon, { name: "ChevronLeft", size: 16 })];
  }), _: 1 }, _parent)), _push('<div class="flex items-center gap-1"><!--[-->'), ssrRenderList($setup.pages, (page, index) => {
    _push("<!--[-->"), typeof page == "number" ? _push(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", class: $setup.cn("h-9 min-w-[36px] px-2", $props.current === page ? "bg-primary text-primary-foreground border-primary hover:bg-primary-hover active:bg-primary" : "hover:bg-muted"), onClick: ($event) => $setup.goToPage(page) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
      if (_push2) _push2(`${ssrInterpolate(page)}`);
      else return [createTextVNode(toDisplayString(page), 1)];
    }), _: 2 }, _parent)) : (_push('<div class="flex h-9 w-9 items-center justify-center text-muted-foreground select-none">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Ellipsis", size: 14 }, null, _parent)), _push("</div>")), _push("<!--]-->");
  }), _push("<!--]--></div>"), _push(ssrRenderComponent($setup.Button, { variant: "outline", size: "icon", class: "h-9 w-9", disabled: $props.current === $setup.totalPages, onClick: ($event) => $setup.goToPage($props.current + 1) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "ChevronRight", size: 16 }, null, _parent2, _scopeId));
    else return [createVNode($setup.SafeIcon, { name: "ChevronRight", size: 16 })];
  }), _: 1 }, _parent)), _push("</div></div>");
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/Pagination.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const Pagination = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const pageSize$1 = 20, _sfc_main$3 = defineComponent({ __name: "FavoritesList", props: { embedded: { type: Boolean, default: false } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, isClient = ref(true), isLoading = ref(false), activeTab = ref("all"), searchKeyword = ref(""), currentPage = ref(1), confirmDialogOpen = ref(false), selectedFavorite = ref(null), showLoginDialog = ref(false), isLoggedIn = ref(false), favorites = ref([]), serverTotal = ref(0), tabOptions = [{ value: "all", label: "全部" }, { value: "homepage", label: "主页" }, { value: "category", label: "分类" }, { value: "product", label: "产品" }];
  onMounted(async () => {
    if (isClient.value = false, requestAnimationFrame(() => {
      authStore.consumeCallbackToken();
      const tabParam = new URLSearchParams(window.location.search).get("tab");
      tabParam && ["all", "homepage", "category", "product"].includes(tabParam) && (activeTab.value = tabParam), isLoggedIn.value = authStore.isLoggedIn(), isClient.value = true;
    }), authStore.consumeCallbackToken(), isLoggedIn.value = authStore.isLoggedIn(), !isLoggedIn.value) {
      showLoginDialog.value = true;
      return;
    }
    await loadFavorites();
  });
  const loadFavorites = async () => {
    if (!authStore.isLoggedIn()) {
      favorites.value = [], serverTotal.value = 0, isLoggedIn.value = false, showLoginDialog.value = true;
      return;
    }
    isLoggedIn.value = true, isLoading.value = true;
    try {
      const raw = await pcApi.getFavorites(activeTab.value, searchKeyword.value.trim(), currentPage.value);
      favorites.value = unwrapList(raw).map((item) => mapPcRecord(item)), serverTotal.value = Number(raw?.total || favorites.value.length);
    } catch (error) {
      (error?.code === 401 || error?.code === 41e4 || /登录|token|授权/i.test(error?.message || "")) && (authStore.clearToken(), isLoggedIn.value = false, showLoginDialog.value = true), toast.error(error?.message || "收藏加载失败");
    } finally {
      isLoading.value = false;
    }
  }, totalItems = computed(() => serverTotal.value || favorites.value.length), paginatedFavorites = computed(() => favorites.value), __returned__ = { props, isClient, isLoading, activeTab, searchKeyword, currentPage, pageSize: pageSize$1, confirmDialogOpen, selectedFavorite, showLoginDialog, isLoggedIn, favorites, serverTotal, tabOptions, loadFavorites, totalItems, paginatedFavorites, getFavoriteTitle: (fav) => fav.title || "未命名", getFavoriteSubtitle: (fav) => fav.subtitle || "", getFavoriteCover: (fav) => fav.coverUrl || "", formatDate: (dateStr) => new Date(dateStr).toLocaleDateString("zh-CN", { year: "numeric", month: "2-digit", day: "2-digit" }), handleTabChange: (tab) => {
    activeTab.value = tab, currentPage.value = 1, loadFavorites();
  }, handleSearch: () => {
    currentPage.value = 1, loadFavorites();
  }, handleView: (fav) => {
    window.location.href = buildPcTargetUrl(fav.targetType, fav.targetId, fav.targetUserId, fav.targetShareCode);
  }, handleDownload: (fav) => {
    fav.targetType === "product" && toast.success("下载已开始");
  }, openRemoveConfirm: (favorite) => {
    selectedFavorite.value = favorite, confirmDialogOpen.value = true;
  }, handleRemoveFavorite: async () => {
    if (selectedFavorite.value) {
      try {
        await pcApi.toggleFavorite(selectedFavorite.value.targetType === "home" ? "homepage" : selectedFavorite.value.targetType, selectedFavorite.value.targetId, false), toast.success("已取消收藏"), await loadFavorites();
      } catch (error) {
        toast.error(error?.message || "取消收藏失败");
      }
      selectedFavorite.value = null, confirmDialogOpen.value = false;
    }
  }, handleGoToBrowse: () => {
    if (props.embedded) {
      window.location.href = "./share-home.html";
      return;
    }
    window.location.href = "./share-home.html";
  }, handlePageChange: (page) => {
    currentPage.value = page, loadFavorites();
  }, handleLoginSuccess: async () => {
    showLoginDialog.value = false, isLoggedIn.value = true, await loadFavorites();
  }, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, get Tabs() {
    return Tabs;
  }, get TabsList() {
    return TabsList;
  }, get TabsTrigger() {
    return TabsTrigger;
  }, get Table() {
    return Table;
  }, get TableBody() {
    return TableBody;
  }, get TableCell() {
    return TableCell;
  }, get TableHead() {
    return TableHead;
  }, get TableHeader() {
    return TableHeader;
  }, get TableRow() {
    return TableRow;
  }, SafeIcon, EmptyState, ConfirmDialog, Pagination, LoginDialog };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.props.embedded ? "flex min-h-0 flex-col" : "page-body flex flex-col min-h-screen" }, _attrs))} data-v-e4e54e6f>`), $setup.props.embedded ? _push("<!---->") : _push('<div class="mb-6" data-v-e4e54e6f><h1 class="text-page-title mb-2" data-v-e4e54e6f>我的收藏</h1><p class="text-caption" data-v-e4e54e6f>你收藏的主页、分类和产品会展示在这里</p></div>'), _push(`<div class="${ssrRenderClass($setup.props.embedded ? "mb-4" : "surface-base card-padding mb-6")}" data-v-e4e54e6f><div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between" data-v-e4e54e6f>`), _push(ssrRenderComponent($setup.Tabs, { "model-value": $setup.activeTab, "onUpdate:modelValue": (value) => $setup.handleTabChange(value), class: "lg:w-auto" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TabsList, { class: "grid w-full grid-cols-4 bg-muted/50 lg:w-[420px]" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3("<!--[-->"), ssrRenderList($setup.tabOptions, (tab) => {
        _push3(ssrRenderComponent($setup.TabsTrigger, { key: tab.value, value: tab.value }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
          if (_push4) _push4(`${ssrInterpolate(tab.label)}`);
          else return [createTextVNode(toDisplayString(tab.label), 1)];
        }), _: 2 }, _parent3, _scopeId2));
      }), _push3("<!--]-->");
      else return [(openBlock(), createBlock(Fragment, null, renderList($setup.tabOptions, (tab) => createVNode($setup.TabsTrigger, { key: tab.value, value: tab.value }, { default: withCtx(() => [createTextVNode(toDisplayString(tab.label), 1)]), _: 2 }, 1032, ["value"])), 64))];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.TabsList, { class: "grid w-full grid-cols-4 bg-muted/50 lg:w-[420px]" }, { default: withCtx(() => [(openBlock(), createBlock(Fragment, null, renderList($setup.tabOptions, (tab) => createVNode($setup.TabsTrigger, { key: tab.value, value: tab.value }, { default: withCtx(() => [createTextVNode(toDisplayString(tab.label), 1)]), _: 2 }, 1032, ["value"])), 64))]), _: 1 })];
  }), _: 1 }, _parent)), _push('<div class="flex min-w-0 flex-1 gap-2 lg:max-w-md" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.Input, { modelValue: $setup.searchKeyword, "onUpdate:modelValue": ($event) => $setup.searchKeyword = $event, placeholder: "搜索收藏内容", class: "flex-1 h-10 bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary", onKeyup: $setup.handleSearch }, null, _parent)), _push(ssrRenderComponent($setup.Button, { variant: "default", size: "sm", class: "h-10 px-6", onClick: $setup.handleSearch }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.SafeIcon, { name: "Search", size: 16, class: "mr-2" }, null, _parent2, _scopeId)), _push2(" 搜索 ");
    else return [createVNode($setup.SafeIcon, { name: "Search", size: 16, class: "mr-2" }), createTextVNode(" 搜索 ")];
  }), _: 1 }, _parent)), _push("</div></div></div>"), $setup.isClient ? _push("<!---->") : _push('<div class="flex-1" data-v-e4e54e6f></div>'), $setup.isClient ? (_push('<div class="flex-1 flex flex-col min-h-0" data-v-e4e54e6f>'), $setup.isLoggedIn ? !$setup.isLoading && $setup.totalItems === 0 ? (_push('<div class="flex-1 flex items-center justify-center" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.EmptyState, { icon: "Heart", title: "暂无收藏", description: "你收藏的主页、分类和产品会展示在这里" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.Button, { variant: "default", onClick: $setup.handleGoToBrowse }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }, null, _parent3, _scopeId2)), _push3(" 去浏览 ");
      else return [createVNode($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }), createTextVNode(" 去浏览 ")];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.Button, { variant: "default", onClick: $setup.handleGoToBrowse }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }), createTextVNode(" 去浏览 ")]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div>")) : $setup.isLoading ? (_push('<div class="flex-1 flex items-center justify-center text-muted-foreground" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 24, class: "mr-2 animate-spin" }, null, _parent)), _push(" 加载中... </div>")) : (_push('<!--[--><div class="surface-base overflow-hidden flex flex-col flex-1" data-v-e4e54e6f><div class="overflow-x-auto" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.Table, null, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TableHeader, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("封面");
          else return [createTextVNode("封面")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "min-w-0 max-w-xs whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("标题");
          else return [createTextVNode("标题")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("副标题");
          else return [createTextVNode("副标题")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("类型");
          else return [createTextVNode("类型")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("收藏时间");
          else return [createTextVNode("收藏时间")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-40 whitespace-nowrap text-right" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("操作");
          else return [createTextVNode("操作")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("封面")]), _: 1 }), createVNode($setup.TableHead, { class: "min-w-0 max-w-xs whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("副标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("类型")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("收藏时间")]), _: 1 }), createVNode($setup.TableHead, { class: "w-40 whitespace-nowrap text-right" }, { default: withCtx(() => [createTextVNode("操作")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx(() => [createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("封面")]), _: 1 }), createVNode($setup.TableHead, { class: "min-w-0 max-w-xs whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("副标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("类型")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("收藏时间")]), _: 1 }), createVNode($setup.TableHead, { class: "w-40 whitespace-nowrap text-right" }, { default: withCtx(() => [createTextVNode("操作")]), _: 1 })]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.TableBody, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3("<!--[-->"), ssrRenderList($setup.paginatedFavorites, (fav) => {
        _push3(ssrRenderComponent($setup.TableRow, { key: fav.id, class: "border-b border-border hover:bg-muted/50 transition-colors" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
          if (_push4) _push4(ssrRenderComponent($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<div class="w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0" data-v-e4e54e6f${_scopeId4}><img${ssrRenderAttr("src", $setup.getFavoriteCover(fav))}${ssrRenderAttr("alt", $setup.getFavoriteTitle(fav))} class="w-full h-full object-cover" data-v-e4e54e6f${_scopeId4}></div>`);
            else return [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0" }, [createVNode("img", { src: $setup.getFavoriteCover(fav), alt: $setup.getFavoriteTitle(fav), class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])])];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "min-w-0 max-w-xs py-3 truncate text-item-title" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`${ssrInterpolate($setup.getFavoriteTitle(fav))}`);
            else return [createTextVNode(toDisplayString($setup.getFavoriteTitle(fav)), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "w-32 py-3 text-caption truncate" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`${ssrInterpolate($setup.getFavoriteSubtitle(fav))}`);
            else return [createTextVNode(toDisplayString($setup.getFavoriteSubtitle(fav)), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<span class="inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground" data-v-e4e54e6f${_scopeId4}>${ssrInterpolate(fav.targetType === "home" ? "主页" : fav.targetType === "category" ? "分类" : "产品")}</span>`);
            else return [createVNode("span", { class: "inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground" }, toDisplayString(fav.targetType === "home" ? "主页" : fav.targetType === "category" ? "分类" : "产品"), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "w-32 py-3 text-caption" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`${ssrInterpolate($setup.formatDate(fav.createdAt))}`);
            else return [createTextVNode(toDisplayString($setup.formatDate(fav.createdAt)), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "w-40 py-3 text-right" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<div class="flex items-center justify-end gap-2" data-v-e4e54e6f${_scopeId4}>`), _push5(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(fav) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }, null, _parent6, _scopeId5)), _push6(" 查看 ");
              else return [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")];
            }), _: 2 }, _parent5, _scopeId4)), fav.targetType === "product" ? _push5(ssrRenderComponent($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleDownload(fav) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }, null, _parent6, _scopeId5)), _push6(" 下载 ");
              else return [createVNode($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }), createTextVNode(" 下载 ")];
            }), _: 2 }, _parent5, _scopeId4)) : _push5("<!---->"), _push5(ssrRenderComponent($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.openRemoveConfirm(fav) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }, null, _parent6, _scopeId5)), _push6(" 取消收藏 ");
              else return [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 取消收藏 ")];
            }), _: 2 }, _parent5, _scopeId4)), _push5("</div>");
            else return [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), fav.targetType === "product" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleDownload(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }), createTextVNode(" 下载 ")]), _: 1 }, 8, ["onClick"])) : createCommentVNode("", true), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.openRemoveConfirm(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 取消收藏 ")]), _: 1 }, 8, ["onClick"])])];
          }), _: 2 }, _parent4, _scopeId3));
          else return [createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0" }, [createVNode("img", { src: $setup.getFavoriteCover(fav), alt: $setup.getFavoriteTitle(fav), class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "min-w-0 max-w-xs py-3 truncate text-item-title" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteTitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption truncate" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteSubtitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("span", { class: "inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground" }, toDisplayString(fav.targetType === "home" ? "主页" : fav.targetType === "category" ? "分类" : "产品"), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.formatDate(fav.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-40 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), fav.targetType === "product" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleDownload(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }), createTextVNode(" 下载 ")]), _: 1 }, 8, ["onClick"])) : createCommentVNode("", true), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.openRemoveConfirm(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 取消收藏 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)];
        }), _: 2 }, _parent3, _scopeId2));
      }), _push3("<!--]-->");
      else return [(openBlock(true), createBlock(Fragment, null, renderList($setup.paginatedFavorites, (fav) => (openBlock(), createBlock($setup.TableRow, { key: fav.id, class: "border-b border-border hover:bg-muted/50 transition-colors" }, { default: withCtx(() => [createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0" }, [createVNode("img", { src: $setup.getFavoriteCover(fav), alt: $setup.getFavoriteTitle(fav), class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "min-w-0 max-w-xs py-3 truncate text-item-title" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteTitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption truncate" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteSubtitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("span", { class: "inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground" }, toDisplayString(fav.targetType === "home" ? "主页" : fav.targetType === "category" ? "分类" : "产品"), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.formatDate(fav.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-40 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), fav.targetType === "product" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleDownload(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }), createTextVNode(" 下载 ")]), _: 1 }, 8, ["onClick"])) : createCommentVNode("", true), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.openRemoveConfirm(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 取消收藏 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)]), _: 2 }, 1024))), 128))];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.TableHeader, null, { default: withCtx(() => [createVNode($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx(() => [createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("封面")]), _: 1 }), createVNode($setup.TableHead, { class: "min-w-0 max-w-xs whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("副标题")]), _: 1 }), createVNode($setup.TableHead, { class: "w-20 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("类型")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 whitespace-nowrap" }, { default: withCtx(() => [createTextVNode("收藏时间")]), _: 1 }), createVNode($setup.TableHead, { class: "w-40 whitespace-nowrap text-right" }, { default: withCtx(() => [createTextVNode("操作")]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.TableBody, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList($setup.paginatedFavorites, (fav) => (openBlock(), createBlock($setup.TableRow, { key: fav.id, class: "border-b border-border hover:bg-muted/50 transition-colors" }, { default: withCtx(() => [createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0" }, [createVNode("img", { src: $setup.getFavoriteCover(fav), alt: $setup.getFavoriteTitle(fav), class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "min-w-0 max-w-xs py-3 truncate text-item-title" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteTitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption truncate" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.getFavoriteSubtitle(fav)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-20 py-3" }, { default: withCtx(() => [createVNode("span", { class: "inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground" }, toDisplayString(fav.targetType === "home" ? "主页" : fav.targetType === "category" ? "分类" : "产品"), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-32 py-3 text-caption" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.formatDate(fav.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "w-40 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), fav.targetType === "product" ? (openBlock(), createBlock($setup.Button, { key: 0, variant: "outline", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleDownload(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Download", size: 14, class: "mr-1" }), createTextVNode(" 下载 ")]), _: 1 }, 8, ["onClick"])) : createCommentVNode("", true), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.openRemoveConfirm(fav) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 取消收藏 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)]), _: 2 }, 1024))), 128))]), _: 1 })];
  }), _: 1 }, _parent)), _push('</div></div><div class="mt-6" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.Pagination, { current: $setup.currentPage, total: $setup.totalItems, "page-size": $setup.pageSize, "onUpdate:current": $setup.handlePageChange }, null, _parent)), _push("</div><!--]-->")) : (_push('<div class="flex-1 flex items-center justify-center" data-v-e4e54e6f>'), _push(ssrRenderComponent($setup.EmptyState, { icon: "ShieldCheck", title: "请先登录", description: "登录后可以查看你在小程序和网页收藏的主页、分类和产品" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.Button, { variant: "default", onClick: ($event) => $setup.showLoginDialog = true }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.SafeIcon, { name: "QrCode", size: 16, class: "mr-2" }, null, _parent3, _scopeId2)), _push3(" 微信扫码登录 ");
      else return [createVNode($setup.SafeIcon, { name: "QrCode", size: 16, class: "mr-2" }), createTextVNode(" 微信扫码登录 ")];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.Button, { variant: "default", onClick: ($event) => $setup.showLoginDialog = true }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "QrCode", size: 16, class: "mr-2" }), createTextVNode(" 微信扫码登录 ")]), _: 1 }, 8, ["onClick"])];
  }), _: 1 }, _parent)), _push("</div>")), _push("</div>")) : _push("<!---->"), _push(ssrRenderComponent($setup.ConfirmDialog, { open: $setup.confirmDialogOpen, title: "取消收藏", description: "确定要取消收藏该内容吗？", "confirm-text": "确认取消", "cancel-text": "取消", variant: "default", "onUpdate:open": (val) => $setup.confirmDialogOpen = val, onConfirm: $setup.handleRemoveFavorite }, null, _parent)), _push(ssrRenderComponent($setup.LoginDialog, { open: $setup.showLoginDialog, "onUpdate:open": ($event) => $setup.showLoginDialog = $event, onLoginSuccess: $setup.handleLoginSuccess }, null, _parent)), _push("</div>");
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/favorites/FavoritesList.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const FavoritesList = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3], ["__scopeId", "data-v-e4e54e6f"]]);
const _sfc_main$2 = defineComponent({ __name: "UserAvatar", props: { src: { default: "" }, name: {}, size: { default: "md" }, class: { default: "" } }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.Avatar, mergeProps({ class: $setup.cn($setup.sizeClasses[$setup.props.size], "shrink-0 select-none bg-muted", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) $setup.props.src ? _push2(ssrRenderComponent($setup.AvatarImage, { src: $setup.props.src, alt: $setup.props.name, class: "aspect-square h-full w-full object-cover" }, null, _parent2, _scopeId)) : _push2("<!---->"), _push2(ssrRenderComponent($setup.AvatarFallback, { class: "flex h-full w-full items-center justify-center rounded-full bg-primary/10 font-medium text-primary uppercase" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`${ssrInterpolate($setup.initials)}`);
      else return [createTextVNode(toDisplayString($setup.initials), 1)];
    }), _: 1 }, _parent2, _scopeId));
    else return [$setup.props.src ? (openBlock(), createBlock($setup.AvatarImage, { key: 0, src: $setup.props.src, alt: $setup.props.name, class: "aspect-square h-full w-full object-cover" }, null, 8, ["src", "alt"])) : createCommentVNode("", true), createVNode($setup.AvatarFallback, { class: "flex h-full w-full items-center justify-center rounded-full bg-primary/10 font-medium text-primary uppercase" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.initials), 1)]), _: 1 })];
  }), _: 1 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/UserAvatar.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
_export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const pageSize = 20, _sfc_main$1 = defineComponent({ __name: "BrowsingHistoryContent", props: { embedded: { type: Boolean, default: false } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, isClient = ref(true), activeTab = ref("all"), keyword = ref(""), currentPage = ref(1), isLoading = ref(false), serverTotal = ref(0), browsingRecords = ref([]), favorites = ref([]), confirmDialog = reactive({ open: false, recordId: "" }), filteredRecords = computed(() => browsingRecords.value), totalItems = computed(() => isLoading.value ? Math.max(serverTotal.value, filteredRecords.value.length) : serverTotal.value || filteredRecords.value.length), paginatedRecords = computed(() => filteredRecords.value), showInitialLoading = computed(() => isLoading.value && browsingRecords.value.length === 0), showEmptyState = computed(() => !isLoading.value && totalItems.value === 0), loadRecords = async () => {
    isLoading.value = true;
    try {
      const raw = await pcApi.getVisits(activeTab.value, keyword.value.trim(), currentPage.value);
      browsingRecords.value = unwrapList(raw).map((item) => mapPcRecord(item)), serverTotal.value = Number(raw?.total || browsingRecords.value.length);
    } catch (error) {
      toast.error(error?.message || "足迹加载失败");
    } finally {
      isLoading.value = false;
    }
  }, handleTabChange = async (tab) => {
    if (activeTab.value = tab, currentPage.value !== 1) {
      currentPage.value = 1;
      return;
    }
    await loadRecords();
  }, handleSearch = () => {
    if (currentPage.value !== 1) {
      currentPage.value = 1;
      return;
    }
    currentPage.value = 1, loadRecords();
  }, handleKeywordInput = (event) => {
    const target = event.target;
    keyword.value = target.value, currentPage.value = 1;
  }, handleView = (record) => {
    window.location.href = buildPcTargetUrl(record.targetType, record.targetId, record.targetUserId, record.targetShareCode);
  }, handleCollect = async (record) => {
    const isFavorited = favorites.value.includes(record.id);
    try {
      await pcApi.toggleFavorite(record.targetType === "home" ? "homepage" : record.targetType, record.targetId, !isFavorited), isFavorited ? (favorites.value = favorites.value.filter((id) => id !== record.id), toast.success("已取消收藏")) : (favorites.value.push(record.id), toast.success("收藏成功"));
    } catch (error) {
      toast.error(error?.message || "操作失败");
    }
  }, handleDeleteClick = (record) => {
    confirmDialog.open = true, confirmDialog.recordId = record.id;
  }, handleConfirmDelete = async () => {
    try {
      await pcApi.deleteVisit(confirmDialog.recordId), toast.success("已删除浏览记录"), await loadRecords();
    } catch (error) {
      toast.error(error?.message || "删除失败");
    }
  }, handleGoToBrowse = () => {
    window.location.href = "./share-home.html";
  }, formatDate = (dateStr) => {
    const date = new Date(dateStr), diffMs = (/* @__PURE__ */ new Date()).getTime() - date.getTime(), diffMins = Math.floor(diffMs / 6e4), diffHours = Math.floor(diffMs / 36e5), diffDays = Math.floor(diffMs / 864e5);
    return diffMins < 1 ? "刚刚" : diffMins < 60 ? `${diffMins}分钟前` : diffHours < 24 ? `${diffHours}小时前` : diffDays < 7 ? `${diffDays}天前` : date.toLocaleDateString("zh-CN");
  };
  onMounted(async () => {
    isClient.value = false, requestAnimationFrame(() => {
      const params = new URLSearchParams(window.location.search), tabParam = params.get("tab"), keywordParam = params.get("keyword");
      tabParam && ["all", "homepage", "category", "product"].includes(tabParam) && (activeTab.value = tabParam), keywordParam && (keyword.value = keywordParam), isClient.value = true;
    }), await loadRecords();
  }), watch(currentPage, () => {
    props.embedded || window.scrollTo({ top: 0, behavior: "smooth" }), loadRecords();
  });
  const __returned__ = { props, isClient, activeTab, keyword, currentPage, pageSize, isLoading, serverTotal, browsingRecords, favorites, confirmDialog, filteredRecords, totalItems, paginatedRecords, showInitialLoading, showEmptyState, loadRecords, handleTabChange, handleSearch, handleKeywordInput, handleView, handleCollect, handleDeleteClick, handleConfirmDelete, handleGoToBrowse, formatDate, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, get Tabs() {
    return Tabs;
  }, get TabsList() {
    return TabsList;
  }, get TabsTrigger() {
    return TabsTrigger;
  }, get Table() {
    return Table;
  }, get TableBody() {
    return TableBody;
  }, get TableCell() {
    return TableCell;
  }, get TableHead() {
    return TableHead;
  }, get TableHeader() {
    return TableHeader;
  }, get TableRow() {
    return TableRow;
  }, SafeIcon, EmptyState, ConfirmDialog, Pagination, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.props.embedded ? "space-y-4" : "page-body space-y-6" }, _attrs))} data-v-fdeaf03f>`), $setup.props.embedded ? _push("<!---->") : _push('<div class="flex flex-col gap-2" data-v-fdeaf03f><h1 class="text-page-title" data-v-fdeaf03f>浏览足迹</h1><p class="text-caption" data-v-fdeaf03f>你浏览过的主页、分类和产品会展示在这里</p></div>'), _push(`<div class="${ssrRenderClass($setup.props.embedded ? "" : "surface-base card-padding")}" data-v-fdeaf03f><div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between" data-v-fdeaf03f>`), _push(ssrRenderComponent($setup.Tabs, { value: $setup.activeTab, "onUpdate:modelValue": $setup.handleTabChange, class: "lg:w-auto" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TabsList, { class: "grid w-full grid-cols-4 h-auto bg-muted p-1 lg:w-[420px]" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.TabsTrigger, { value: "all", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(" 全部 ");
        else return [createTextVNode(" 全部 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.TabsTrigger, { value: "homepage", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(" 主页 ");
        else return [createTextVNode(" 主页 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.TabsTrigger, { value: "category", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(" 分类 ");
        else return [createTextVNode(" 分类 ")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.TabsTrigger, { value: "product", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(" 产品 ");
        else return [createTextVNode(" 产品 ")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.TabsTrigger, { value: "all", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 全部 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "homepage", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 主页 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "category", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 分类 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "product", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 产品 ")]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.TabsList, { class: "grid w-full grid-cols-4 h-auto bg-muted p-1 lg:w-[420px]" }, { default: withCtx(() => [createVNode($setup.TabsTrigger, { value: "all", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 全部 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "homepage", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 主页 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "category", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 分类 ")]), _: 1 }), createVNode($setup.TabsTrigger, { value: "product", class: "data-[state=active]:bg-card data-[state=active]:text-primary" }, { default: withCtx(() => [createTextVNode(" 产品 ")]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent)), _push('<div class="relative min-w-0 flex-1 lg:max-w-md" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Search", size: 18, class: "absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" }, null, _parent)), _push(ssrRenderComponent($setup.Input, { type: "text", placeholder: "搜索浏览记录...", value: $setup.keyword, class: "pl-10 h-10 bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary", onInput: $setup.handleKeywordInput, onKeyup: $setup.handleSearch }, null, _parent)), _push("</div></div></div>"), $setup.showInitialLoading ? (_push('<div class="py-12 text-center text-muted-foreground" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 24, class: "mx-auto mb-2 animate-spin" }, null, _parent)), _push(" 加载中... </div>")) : $setup.showEmptyState ? (_push('<div class="py-12" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.EmptyState, { icon: "History", title: "暂无足迹", description: "你浏览过的主页、分类和产品会展示在这里" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.Button, { onClick: $setup.handleGoToBrowse, class: "mt-4" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }, null, _parent3, _scopeId2)), _push3(" 去浏览 ");
      else return [createVNode($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }), createTextVNode(" 去浏览 ")];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.Button, { onClick: $setup.handleGoToBrowse, class: "mt-4" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "ArrowRight", size: 16, class: "mr-2" }), createTextVNode(" 去浏览 ")]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div>")) : (_push('<div class="surface-base relative overflow-hidden flex flex-col" data-v-fdeaf03f>'), $setup.isLoading ? (_push('<div class="absolute inset-0 z-10 flex items-center justify-center bg-background/65 backdrop-blur-[1px]" data-v-fdeaf03f><div class="flex items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm text-muted-foreground shadow-sm" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Loader2", size: 16, class: "animate-spin" }, null, _parent)), _push(" 加载中... </div></div>")) : _push("<!---->"), _push('<div class="overflow-x-auto" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.Table, { class: "w-full" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TableHeader, { class: "bg-muted/50 sticky top-0" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.TableHead, { class: "w-20 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 封面 ");
          else return [createTextVNode(" 封面 ")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "flex-1 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 标题 ");
          else return [createTextVNode(" 标题 ")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-24 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 类型 ");
          else return [createTextVNode(" 类型 ")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-32 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 浏览时间 ");
          else return [createTextVNode(" 浏览时间 ")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableHead, { class: "w-48 h-12 px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(" 操作 ");
          else return [createTextVNode(" 操作 ")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.TableHead, { class: "w-20 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 封面 ")]), _: 1 }), createVNode($setup.TableHead, { class: "flex-1 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 标题 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-24 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 类型 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 浏览时间 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-48 h-12 px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 操作 ")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx(() => [createVNode($setup.TableHead, { class: "w-20 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 封面 ")]), _: 1 }), createVNode($setup.TableHead, { class: "flex-1 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 标题 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-24 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 类型 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 浏览时间 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-48 h-12 px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 操作 ")]), _: 1 })]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.TableBody, null, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3("<!--[-->"), ssrRenderList($setup.paginatedRecords, (record) => {
        _push3(ssrRenderComponent($setup.TableRow, { key: record.id, class: "border-b border-border hover:bg-muted/30 transition-colors" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
          if (_push4) _push4(ssrRenderComponent($setup.TableCell, { class: "px-4 py-3" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<div class="w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0" data-v-fdeaf03f${_scopeId4}>`), record.coverUrl ? _push5(`<img${ssrRenderAttr("src", record.coverUrl)}${ssrRenderAttr("alt", record.title)} class="w-full h-full object-cover" data-v-fdeaf03f${_scopeId4}>`) : _push5(ssrRenderComponent($setup.SafeIcon, { name: "Image", size: 24, class: "text-muted-foreground" }, null, _parent5, _scopeId4)), _push5("</div>");
            else return [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0" }, [record.coverUrl ? (openBlock(), createBlock("img", { key: 0, src: record.coverUrl, alt: record.title, class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])) : (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Image", size: 24, class: "text-muted-foreground" }))])];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "px-4 py-3 min-w-0" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<div class="flex flex-col gap-1" data-v-fdeaf03f${_scopeId4}><p class="text-item-title font-medium truncate" data-v-fdeaf03f${_scopeId4}>${ssrInterpolate(record.title)}</p><p class="text-caption truncate" data-v-fdeaf03f${_scopeId4}>${ssrInterpolate(record.subtitle)}</p></div>`);
            else return [createVNode("div", { class: "flex flex-col gap-1" }, [createVNode("p", { class: "text-item-title font-medium truncate" }, toDisplayString(record.title), 1), createVNode("p", { class: "text-caption truncate" }, toDisplayString(record.subtitle), 1)])];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "px-4 py-3 whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<span class="${ssrRenderClass($setup.cn("inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium", record.targetType === "home" && "bg-blue-100 text-blue-800", record.targetType === "category" && "bg-purple-100 text-purple-800", record.targetType === "product" && "bg-green-100 text-green-800"))}" data-v-fdeaf03f${_scopeId4}>${ssrInterpolate(record.targetType === "home" ? "主页" : record.targetType === "category" ? "分类" : "产品")}</span>`);
            else return [createVNode("span", { class: $setup.cn("inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium", record.targetType === "home" && "bg-blue-100 text-blue-800", record.targetType === "category" && "bg-purple-100 text-purple-800", record.targetType === "product" && "bg-green-100 text-green-800") }, toDisplayString(record.targetType === "home" ? "主页" : record.targetType === "category" ? "分类" : "产品"), 3)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "px-4 py-3 text-caption whitespace-nowrap" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`${ssrInterpolate(record.timeText || $setup.formatDate(record.createdAt))}`);
            else return [createTextVNode(toDisplayString(record.timeText || $setup.formatDate(record.createdAt)), 1)];
          }), _: 2 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.TableCell, { class: "px-4 py-3 text-right" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
            if (_push5) _push5(`<div class="flex items-center justify-end gap-2" data-v-fdeaf03f${_scopeId4}>`), _push5(ssrRenderComponent($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(record) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }, null, _parent6, _scopeId5)), _push6(" 查看 ");
              else return [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")];
            }), _: 2 }, _parent5, _scopeId4)), _push5(ssrRenderComponent($setup.Button, { variant: "ghost", size: "sm", class: ["h-8 px-3 text-xs", $setup.favorites.includes(record.id) && "text-accent"], onClick: ($event) => $setup.handleCollect(record) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, _parent6, _scopeId5)), _push6(` ${ssrInterpolate($setup.favorites.includes(record.id) ? "已收藏" : "收藏")}`);
              else return [createVNode($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, 8, ["name", "fill"]), createTextVNode(" " + toDisplayString($setup.favorites.includes(record.id) ? "已收藏" : "收藏"), 1)];
            }), _: 2 }, _parent5, _scopeId4)), _push5(ssrRenderComponent($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.handleDeleteClick(record) }, { default: withCtx((_5, _push6, _parent6, _scopeId5) => {
              if (_push6) _push6(ssrRenderComponent($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }, null, _parent6, _scopeId5)), _push6(" 删除记录 ");
              else return [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 删除记录 ")];
            }), _: 2 }, _parent5, _scopeId4)), _push5("</div>");
            else return [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: ["h-8 px-3 text-xs", $setup.favorites.includes(record.id) && "text-accent"], onClick: ($event) => $setup.handleCollect(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, 8, ["name", "fill"]), createTextVNode(" " + toDisplayString($setup.favorites.includes(record.id) ? "已收藏" : "收藏"), 1)]), _: 2 }, 1032, ["class", "onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.handleDeleteClick(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 删除记录 ")]), _: 1 }, 8, ["onClick"])])];
          }), _: 2 }, _parent4, _scopeId3));
          else return [createVNode($setup.TableCell, { class: "px-4 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0" }, [record.coverUrl ? (openBlock(), createBlock("img", { key: 0, src: record.coverUrl, alt: record.title, class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])) : (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Image", size: 24, class: "text-muted-foreground" }))])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 min-w-0" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col gap-1" }, [createVNode("p", { class: "text-item-title font-medium truncate" }, toDisplayString(record.title), 1), createVNode("p", { class: "text-caption truncate" }, toDisplayString(record.subtitle), 1)])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 whitespace-nowrap" }, { default: withCtx(() => [createVNode("span", { class: $setup.cn("inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium", record.targetType === "home" && "bg-blue-100 text-blue-800", record.targetType === "category" && "bg-purple-100 text-purple-800", record.targetType === "product" && "bg-green-100 text-green-800") }, toDisplayString(record.targetType === "home" ? "主页" : record.targetType === "category" ? "分类" : "产品"), 3)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-caption whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(toDisplayString(record.timeText || $setup.formatDate(record.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: ["h-8 px-3 text-xs", $setup.favorites.includes(record.id) && "text-accent"], onClick: ($event) => $setup.handleCollect(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, 8, ["name", "fill"]), createTextVNode(" " + toDisplayString($setup.favorites.includes(record.id) ? "已收藏" : "收藏"), 1)]), _: 2 }, 1032, ["class", "onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.handleDeleteClick(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 删除记录 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)];
        }), _: 2 }, _parent3, _scopeId2));
      }), _push3("<!--]-->");
      else return [(openBlock(true), createBlock(Fragment, null, renderList($setup.paginatedRecords, (record) => (openBlock(), createBlock($setup.TableRow, { key: record.id, class: "border-b border-border hover:bg-muted/30 transition-colors" }, { default: withCtx(() => [createVNode($setup.TableCell, { class: "px-4 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0" }, [record.coverUrl ? (openBlock(), createBlock("img", { key: 0, src: record.coverUrl, alt: record.title, class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])) : (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Image", size: 24, class: "text-muted-foreground" }))])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 min-w-0" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col gap-1" }, [createVNode("p", { class: "text-item-title font-medium truncate" }, toDisplayString(record.title), 1), createVNode("p", { class: "text-caption truncate" }, toDisplayString(record.subtitle), 1)])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 whitespace-nowrap" }, { default: withCtx(() => [createVNode("span", { class: $setup.cn("inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium", record.targetType === "home" && "bg-blue-100 text-blue-800", record.targetType === "category" && "bg-purple-100 text-purple-800", record.targetType === "product" && "bg-green-100 text-green-800") }, toDisplayString(record.targetType === "home" ? "主页" : record.targetType === "category" ? "分类" : "产品"), 3)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-caption whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(toDisplayString(record.timeText || $setup.formatDate(record.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: ["h-8 px-3 text-xs", $setup.favorites.includes(record.id) && "text-accent"], onClick: ($event) => $setup.handleCollect(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, 8, ["name", "fill"]), createTextVNode(" " + toDisplayString($setup.favorites.includes(record.id) ? "已收藏" : "收藏"), 1)]), _: 2 }, 1032, ["class", "onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.handleDeleteClick(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 删除记录 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)]), _: 2 }, 1024))), 128))];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.TableHeader, { class: "bg-muted/50 sticky top-0" }, { default: withCtx(() => [createVNode($setup.TableRow, { class: "border-b border-border hover:bg-transparent" }, { default: withCtx(() => [createVNode($setup.TableHead, { class: "w-20 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 封面 ")]), _: 1 }), createVNode($setup.TableHead, { class: "flex-1 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 标题 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-24 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 类型 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-32 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(" 浏览时间 ")]), _: 1 }), createVNode($setup.TableHead, { class: "w-48 h-12 px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider" }, { default: withCtx(() => [createTextVNode(" 操作 ")]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.TableBody, null, { default: withCtx(() => [(openBlock(true), createBlock(Fragment, null, renderList($setup.paginatedRecords, (record) => (openBlock(), createBlock($setup.TableRow, { key: record.id, class: "border-b border-border hover:bg-muted/30 transition-colors" }, { default: withCtx(() => [createVNode($setup.TableCell, { class: "px-4 py-3" }, { default: withCtx(() => [createVNode("div", { class: "w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0" }, [record.coverUrl ? (openBlock(), createBlock("img", { key: 0, src: record.coverUrl, alt: record.title, class: "w-full h-full object-cover" }, null, 8, ["src", "alt"])) : (openBlock(), createBlock($setup.SafeIcon, { key: 1, name: "Image", size: 24, class: "text-muted-foreground" }))])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 min-w-0" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col gap-1" }, [createVNode("p", { class: "text-item-title font-medium truncate" }, toDisplayString(record.title), 1), createVNode("p", { class: "text-caption truncate" }, toDisplayString(record.subtitle), 1)])]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 whitespace-nowrap" }, { default: withCtx(() => [createVNode("span", { class: $setup.cn("inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium", record.targetType === "home" && "bg-blue-100 text-blue-800", record.targetType === "category" && "bg-purple-100 text-purple-800", record.targetType === "product" && "bg-green-100 text-green-800") }, toDisplayString(record.targetType === "home" ? "主页" : record.targetType === "category" ? "分类" : "产品"), 3)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-caption whitespace-nowrap" }, { default: withCtx(() => [createTextVNode(toDisplayString(record.timeText || $setup.formatDate(record.createdAt)), 1)]), _: 2 }, 1024), createVNode($setup.TableCell, { class: "px-4 py-3 text-right" }, { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-end gap-2" }, [createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs", onClick: ($event) => $setup.handleView(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Eye", size: 14, class: "mr-1" }), createTextVNode(" 查看 ")]), _: 1 }, 8, ["onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: ["h-8 px-3 text-xs", $setup.favorites.includes(record.id) && "text-accent"], onClick: ($event) => $setup.handleCollect(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: ($setup.favorites.includes(record.id), "Heart"), size: 14, class: "mr-1", fill: $setup.favorites.includes(record.id) ? "currentColor" : "none" }, null, 8, ["name", "fill"]), createTextVNode(" " + toDisplayString($setup.favorites.includes(record.id) ? "已收藏" : "收藏"), 1)]), _: 2 }, 1032, ["class", "onClick"]), createVNode($setup.Button, { variant: "ghost", size: "sm", class: "h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10", onClick: ($event) => $setup.handleDeleteClick(record) }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "Trash2", size: 14, class: "mr-1" }), createTextVNode(" 删除记录 ")]), _: 1 }, 8, ["onClick"])])]), _: 2 }, 1024)]), _: 2 }, 1024))), 128))]), _: 1 })];
  }), _: 1 }, _parent)), _push('</div><div class="border-t border-border px-4 py-4 bg-muted/20" data-v-fdeaf03f>'), _push(ssrRenderComponent($setup.Pagination, { current: $setup.currentPage, total: $setup.totalItems, "page-size": $setup.pageSize, "onUpdate:current": ($event) => $setup.currentPage = $event }, null, _parent)), _push("</div></div>")), _push(ssrRenderComponent($setup.ConfirmDialog, { open: $setup.confirmDialog.open, title: "删除足迹", description: "删除后将不再展示这条浏览记录，确定删除吗？", "confirm-text": "确认删除", "cancel-text": "取消", variant: "destructive", "onUpdate:open": ($event) => $setup.confirmDialog.open = $event, onConfirm: $setup.handleConfirmDelete }, null, _parent)), _push("</div>");
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/browsing_history/BrowsingHistoryContent.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const BrowsingHistoryContent = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1], ["__scopeId", "data-v-fdeaf03f"]]);
const _sfc_main = defineComponent({ __name: "CommonHeader", props: { isAuthenticated: { type: Boolean, default: false }, userName: { default: "访客用户" }, userAvatar: { default: "" }, currentPath: { default: "./share-home.html" } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, searchQuery = ref(""), showLoginDialog = ref(false), quickPanel = ref(""), userInfo = ref({}), isHydrated = ref(false), loggedIn = computed(() => isHydrated.value && (props.isAuthenticated || authStore.isLoggedIn())), displayName = computed(() => userInfo.value?.company_name || userInfo.value?.nickname || props.userName), displayAvatar = computed(() => userInfo.value?.avatar || userInfo.value?.company_logo || props.userAvatar), navItems = [{ name: "我的收藏", href: "./favorites.html", icon: "Heart", panel: "favorites" }, { name: "浏览足迹", href: "./browsing-history.html", icon: "History", panel: "history" }], handleSearch = () => {
    searchQuery.value.trim() && (window.location.href = `./share-home.html?keyword=${encodeURIComponent(searchQuery.value.trim())}`);
  }, handleNavigate = (href) => {
    window.location.href = href;
  }, handleNavClick = (item) => {
    quickPanel.value = item.panel;
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
  const __returned__ = { props, searchQuery, showLoginDialog, quickPanel, userInfo, isHydrated, loggedIn, displayName, displayAvatar, navItems, handleSearch, handleNavigate, handleNavClick, handleMerchantLogin, handleLogout, handleLoginSuccess, isActive, get Button() {
    return Button;
  }, get Input() {
    return Input;
  }, get Avatar() {
    return Avatar;
  }, get AvatarImage() {
    return AvatarImage;
  }, get AvatarFallback() {
    return AvatarFallback;
  }, get Dialog() {
    return Dialog;
  }, get DialogScrollContent() {
    return DialogScrollContent;
  }, get DialogHeader() {
    return DialogHeader;
  }, get DialogTitle() {
    return DialogTitle;
  }, get DialogDescription() {
    return DialogDescription;
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
  }, SafeIcon, BrandMark, LoginDialog, FavoritesList, BrowsingHistoryContent, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<header${ssrRenderAttrs(mergeProps({ class: "sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60" }, _attrs))}><div class="h-[var(--header-height)] page-container px-8 flex items-center justify-between gap-8"><div class="flex items-center gap-2 cursor-pointer shrink-0">`), _push(ssrRenderComponent($setup.BrandMark, { size: 32 }, null, _parent)), _push('<span class="text-xl font-bold tracking-tight text-foreground">家纺云相册</span></div><div class="flex-1 max-w-xl relative">'), _push(ssrRenderComponent($setup.SafeIcon, { name: "Search", class: "absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground", size: 18 }, null, _parent)), _push(ssrRenderComponent($setup.Input, { modelValue: $setup.searchQuery, "onUpdate:modelValue": ($event) => $setup.searchQuery = $event, placeholder: "搜索产品名称...", class: "pl-10 h-10 w-full bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary", onKeyup: $setup.handleSearch }, null, _parent)), _push('</div><div class="flex items-center gap-2"><nav class="flex items-center gap-1 mr-4"><!--[-->'), ssrRenderList($setup.navItems, (item) => {
    _push(ssrRenderComponent($setup.Button, { key: item.href, variant: "ghost", class: $setup.cn("flex items-center gap-2 px-4 h-10 text-muted-foreground hover:text-primary hover:bg-primary/5 transition-all", ($setup.isActive(item.href) || $setup.quickPanel === item.panel) && "text-primary bg-primary/10"), onClick: ($event) => $setup.handleNavClick(item) }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
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
        if (_push4) _push4(`<div class="flex flex-col space-y-1"${_scopeId3}><p class="text-sm font-medium leading-none"${_scopeId3}>${ssrInterpolate($setup.displayName)}</p><p class="text-xs leading-none text-muted-foreground"${_scopeId3}>欢迎使用家纺云相册</p></div>`);
        else return [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云相册")])];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuSeparator, null, null, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>管理工作台</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")];
      }), _: 1 }, _parent3, _scopeId2)), _push3(ssrRenderComponent($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }, null, _parent4, _scopeId3)), _push4(`<span${_scopeId3}>退出登录</span>`);
        else return [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云相册")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DropdownMenuTrigger, { "as-child": "" }, { default: withCtx(() => [createVNode($setup.Button, { variant: "ghost", class: "relative h-10 w-10 rounded-full p-0" }, { default: withCtx(() => [createVNode($setup.Avatar, { class: "h-10 w-10 border border-border" }, { default: withCtx(() => [createVNode($setup.AvatarImage, { src: $setup.displayAvatar, alt: $setup.displayName }, null, 8, ["src", "alt"]), createVNode($setup.AvatarFallback, { class: "bg-primary/10 text-primary" }, { default: withCtx(() => [createTextVNode(toDisplayString($setup.displayName.substring(0, 1)), 1)]), _: 1 })]), _: 1 })]), _: 1 })]), _: 1 }), createVNode($setup.DropdownMenuContent, { class: "w-56", align: "end" }, { default: withCtx(() => [createVNode($setup.DropdownMenuLabel, { class: "font-normal" }, { default: withCtx(() => [createVNode("div", { class: "flex flex-col space-y-1" }, [createVNode("p", { class: "text-sm font-medium leading-none" }, toDisplayString($setup.displayName), 1), createVNode("p", { class: "text-xs leading-none text-muted-foreground" }, "欢迎使用家纺云相册")])]), _: 1 }), createVNode($setup.DropdownMenuSeparator), createVNode($setup.DropdownMenuItem, { onClick: ($event) => $setup.handleNavigate("./management-workbench.html"), class: "cursor-pointer" }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LayoutDashboard", class: "mr-2 h-4 w-4" }), createVNode("span", null, "管理工作台")]), _: 1 }, 8, ["onClick"]), createVNode($setup.DropdownMenuItem, { class: "cursor-pointer text-destructive focus:text-destructive", onClick: $setup.handleLogout }, { default: withCtx(() => [createVNode($setup.SafeIcon, { name: "LogOut", class: "mr-2 h-4 w-4" }), createVNode("span", null, "退出登录")]), _: 1 })]), _: 1 })];
  }), _: 1 }, _parent)), _push("</div>")) : (_push("<div>"), _push(ssrRenderComponent($setup.Button, { variant: "default", size: "sm", class: "h-9 px-6 rounded-full", onClick: $setup.handleMerchantLogin }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(" 商家登录 ");
    else return [createTextVNode(" 商家登录 ")];
  }), _: 1 }, _parent)), _push("</div>")), _push("</div></div>"), _push(ssrRenderComponent($setup.LoginDialog, { open: $setup.showLoginDialog, "onUpdate:open": ($event) => $setup.showLoginDialog = $event, onLoginSuccess: $setup.handleLoginSuccess }, null, _parent)), _push(ssrRenderComponent($setup.Dialog, { open: $setup.quickPanel === "favorites", "onUpdate:open": (val) => $setup.quickPanel = val ? "favorites" : "" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogScrollContent, { class: "max-h-[88vh] max-w-[1120px] overflow-hidden p-0" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex max-h-[88vh] min-h-[620px] flex-col"${_scopeId2}>`), _push3(ssrRenderComponent($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("我的收藏");
          else return [createTextVNode("我的收藏")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.DialogDescription, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("查看你收藏的主页、分类和产品");
          else return [createTextVNode("查看你收藏的主页、分类和产品")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("我的收藏")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你收藏的主页、分类和产品")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="min-h-0 flex-1 overflow-y-auto px-6 py-5"${_scopeId2}>`), $setup.quickPanel === "favorites" ? _push3(ssrRenderComponent($setup.FavoritesList, { embedded: "" }, null, _parent3, _scopeId2)) : _push3("<!---->"), _push3("</div></div>");
      else return [createVNode("div", { class: "flex max-h-[88vh] min-h-[620px] flex-col" }, [createVNode($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("我的收藏")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你收藏的主页、分类和产品")]), _: 1 })]), _: 1 }), createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-6 py-5" }, [$setup.quickPanel === "favorites" ? (openBlock(), createBlock($setup.FavoritesList, { key: 0, embedded: "" })) : createCommentVNode("", true)])])];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogScrollContent, { class: "max-h-[88vh] max-w-[1120px] overflow-hidden p-0" }, { default: withCtx(() => [createVNode("div", { class: "flex max-h-[88vh] min-h-[620px] flex-col" }, [createVNode($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("我的收藏")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你收藏的主页、分类和产品")]), _: 1 })]), _: 1 }), createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-6 py-5" }, [$setup.quickPanel === "favorites" ? (openBlock(), createBlock($setup.FavoritesList, { key: 0, embedded: "" })) : createCommentVNode("", true)])])]), _: 1 })];
  }), _: 1 }, _parent)), _push(ssrRenderComponent($setup.Dialog, { open: $setup.quickPanel === "history", "onUpdate:open": (val) => $setup.quickPanel = val ? "history" : "" }, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogScrollContent, { class: "max-h-[88vh] max-w-[1120px] overflow-hidden p-0" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex max-h-[88vh] min-h-[620px] flex-col"${_scopeId2}>`), _push3(ssrRenderComponent($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.DialogTitle, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("浏览足迹");
          else return [createTextVNode("浏览足迹")];
        }), _: 1 }, _parent4, _scopeId3)), _push4(ssrRenderComponent($setup.DialogDescription, null, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5("查看你浏览过的主页、分类和产品");
          else return [createTextVNode("查看你浏览过的主页、分类和产品")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("浏览足迹")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你浏览过的主页、分类和产品")]), _: 1 })];
      }), _: 1 }, _parent3, _scopeId2)), _push3(`<div class="min-h-0 flex-1 overflow-y-auto px-6 py-5"${_scopeId2}>`), $setup.quickPanel === "history" ? _push3(ssrRenderComponent($setup.BrowsingHistoryContent, { embedded: "" }, null, _parent3, _scopeId2)) : _push3("<!---->"), _push3("</div></div>");
      else return [createVNode("div", { class: "flex max-h-[88vh] min-h-[620px] flex-col" }, [createVNode($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("浏览足迹")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你浏览过的主页、分类和产品")]), _: 1 })]), _: 1 }), createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-6 py-5" }, [$setup.quickPanel === "history" ? (openBlock(), createBlock($setup.BrowsingHistoryContent, { key: 0, embedded: "" })) : createCommentVNode("", true)])])];
    }), _: 1 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogScrollContent, { class: "max-h-[88vh] max-w-[1120px] overflow-hidden p-0" }, { default: withCtx(() => [createVNode("div", { class: "flex max-h-[88vh] min-h-[620px] flex-col" }, [createVNode($setup.DialogHeader, { class: "border-b border-border px-6 py-5" }, { default: withCtx(() => [createVNode($setup.DialogTitle, null, { default: withCtx(() => [createTextVNode("浏览足迹")]), _: 1 }), createVNode($setup.DialogDescription, null, { default: withCtx(() => [createTextVNode("查看你浏览过的主页、分类和产品")]), _: 1 })]), _: 1 }), createVNode("div", { class: "min-h-0 flex-1 overflow-y-auto px-6 py-5" }, [$setup.quickPanel === "history" ? (openBlock(), createBlock($setup.BrowsingHistoryContent, { key: 0, embedded: "" })) : createCommentVNode("", true)])])]), _: 1 })];
  }), _: 1 }, _parent)), _push("</header>");
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
          <div>© 2026 家纺云相册. 版权所有</div>
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
  BrowsingHistoryContent as B,
  EmptyState as E,
  FavoritesList as F,
  TabsTrigger as T,
  TabsList as a,
  Tabs as b
};
