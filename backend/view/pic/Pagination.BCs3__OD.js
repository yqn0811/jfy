import { defineComponent, useSSRContext, mergeProps, withCtx, createVNode, renderSlot, computed, createTextVNode, toDisplayString } from "vue";
import { c as cn, B as Button } from "./index.C8wo6kix.js";
import { ssrRenderAttrs, ssrRenderClass, ssrRenderSlot, ssrRenderComponent, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.BHhPB8Is.js";
import { reactiveOmit } from "@vueuse/core";
import { S as SafeIcon } from "./SafeIcon.DpfPD-xe.js";
const _sfc_main$9 = defineComponent({ __name: "Table", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$9(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative w-full overflow-auto" }, _attrs))}><table class="${ssrRenderClass($setup.cn("w-full caption-bottom text-sm", $setup.props.class))}">`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</table></div>");
}
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/Table.vue"), _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const Table = _export_sfc(_sfc_main$9, [["ssrRender", _sfc_ssrRender$9]]);
const _sfc_main$8 = defineComponent({ __name: "TableBody", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tbody${ssrRenderAttrs(mergeProps({ class: $setup.cn("[&_tr:last-child]:border-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tbody>");
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableBody.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const TableBody = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "TableCaption", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<caption${ssrRenderAttrs(mergeProps({ class: $setup.cn("mt-4 text-sm text-muted-foreground", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</caption>");
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableCaption.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
_export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "TableCell", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<td${ssrRenderAttrs(mergeProps({ class: $setup.cn("p-4 align-middle [&:has([role=checkbox])]:pr-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</td>");
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableCell.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const TableCell = _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "TableRow", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tr${ssrRenderAttrs(mergeProps({ class: $setup.cn("border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tr>");
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableRow.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const TableRow = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "TableEmpty", props: { class: {}, colspan: { default: 1 } }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), __returned__ = { props, delegatedProps, get cn() {
    return cn;
  }, TableCell, TableRow };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.TableRow, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.TableCell, mergeProps({ class: $setup.cn("p-4 whitespace-nowrap align-middle text-sm text-foreground", $setup.props.class) }, $setup.delegatedProps), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(`<div class="flex items-center justify-center py-10"${_scopeId2}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3("</div>");
      else return [createVNode("div", { class: "flex items-center justify-center py-10" }, [renderSlot(_ctx.$slots, "default")])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.TableCell, mergeProps({ class: $setup.cn("p-4 whitespace-nowrap align-middle text-sm text-foreground", $setup.props.class) }, $setup.delegatedProps), { default: withCtx(() => [createVNode("div", { class: "flex items-center justify-center py-10" }, [renderSlot(_ctx.$slots, "default")])]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableEmpty.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
_export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "TableFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<tfoot${ssrRenderAttrs(mergeProps({ class: $setup.cn("border-t bg-muted/50 font-medium [&>tr]:last:border-b-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</tfoot>");
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableFooter.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
_export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "TableHead", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<th${ssrRenderAttrs(mergeProps({ class: $setup.cn("h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</th>");
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableHead.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const TableHead = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "TableHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<thead${ssrRenderAttrs(mergeProps({ class: $setup.cn("[&_tr]:border-b", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</thead>");
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/table/TableHeader.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TableHeader = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "Pagination", props: { current: { default: 1 }, total: { default: 0 }, pageSize: { default: 20 } }, emits: ["update:current", "change"], setup(__props, { expose: __expose, emit: __emit }) {
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
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
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
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/common/Pagination.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Pagination = _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  Pagination as P,
  TableRow as T,
  TableHeader as a,
  TableHead as b,
  TableCell as c,
  TableBody as d,
  Table as e
};
