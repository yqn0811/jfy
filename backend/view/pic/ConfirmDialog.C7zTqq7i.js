import { b as buttonVariants, c as cn } from "./index.DMmv2-2r.js";
import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode, createTextVNode, toDisplayString, unref } from "vue";
import { useForwardPropsEmits, AlertDialogRoot, AlertDialogAction as AlertDialogAction$1, AlertDialogCancel as AlertDialogCancel$1, AlertDialogPortal, AlertDialogOverlay, AlertDialogContent as AlertDialogContent$1, AlertDialogDescription as AlertDialogDescription$1, AlertDialogTitle as AlertDialogTitle$1, AlertDialogTrigger } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs, ssrInterpolate } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.BgPnvqQg.js";
import { reactiveOmit } from "@vueuse/core";
const _sfc_main$9 = defineComponent({ __name: "AlertDialog", props: { open: { type: Boolean }, defaultOpen: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get AlertDialogRoot() {
    return AlertDialogRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialog.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const AlertDialog = _export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "AlertDialogAction", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogAction, mergeProps($setup.delegatedProps, { class: $setup.cn($setup.buttonVariants(), $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogAction.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const AlertDialogAction = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "AlertDialogCancel", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
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
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogCancel, mergeProps($setup.delegatedProps, { class: $setup.cn($setup.buttonVariants({ variant: "outline" }), "mt-2 sm:mt-0", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogCancel.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const AlertDialogCancel = _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "AlertDialogContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.AlertDialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, null, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.AlertDialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2);
      else return [renderSlot(_ctx.$slots, "default")];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.AlertDialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }), createVNode($setup.AlertDialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default")]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogContent.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const AlertDialogContent = _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "AlertDialogDescription", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogDescription() {
    return AlertDialogDescription$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogDescription, mergeProps($setup.delegatedProps, { class: $setup.cn("text-sm text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogDescription.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const AlertDialogDescription = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "AlertDialogFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-x-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogFooter.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AlertDialogFooter = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "AlertDialogHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col gap-y-2 text-center sm:text-left", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogHeader.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AlertDialogHeader = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "AlertDialogTitle", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get AlertDialogTitle() {
    return AlertDialogTitle$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogTitle, mergeProps($setup.delegatedProps, { class: $setup.cn("text-lg font-semibold", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogTitle.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AlertDialogTitle = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "AlertDialogTrigger", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get AlertDialogTrigger() {
    return AlertDialogTrigger;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.AlertDialogTrigger, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/alert-dialog/AlertDialogTrigger.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
_export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "ConfirmDialog", props: { open: { type: Boolean }, title: {}, description: {}, confirmText: { default: "确定" }, cancelText: { default: "取消" }, variant: { default: "default" } }, emits: ["update:open", "confirm", "cancel"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
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
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/ConfirmDialog.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ConfirmDialog = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  ConfirmDialog as C
};
