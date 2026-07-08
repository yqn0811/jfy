import { defineComponent, computed, useSSRContext, mergeProps, withCtx, createVNode, createTextVNode, toDisplayString } from "vue";
import { c as cn, B as Button } from "./index.DRLhNP3M.js";
import { S as SafeIcon, _ as _export_sfc } from "./SafeIcon.IqZVWxMk.js";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList } from "vue/server-renderer";
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
  Pagination as P
};
