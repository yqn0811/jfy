import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { useForwardPropsEmits, DialogRoot, DialogClose, DialogPortal, DialogOverlay, DialogContent as DialogContent$1, useForwardProps, DialogDescription as DialogDescription$1, DialogTitle as DialogTitle$1, DialogTrigger } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs } from "vue/server-renderer";
import { _ as _export_sfc } from "./BaseLayout.d5ww63VJ.js";
import { reactiveOmit } from "@vueuse/core";
import { X } from "lucide-vue-next";
import { c as cn } from "./index.CH7kJXp0.js";
const DEFAULT_API_BASE = "https://api.jfyuntu.com/api";
class ApiError extends Error {
  code;
  data;
  constructor(message, code = -1, data = null) {
    super(message);
    this.name = "ApiError";
    this.code = code;
    this.data = data;
  }
}
const TOKEN_KEY = "jfyuntu_pc_token";
const USER_KEY = "jfyuntu_pc_user";
const UPLOAD_TOKEN_KEY = "jfyuntu_web_upload_token";
const UPLOAD_TOKEN_CODE_KEY = "jfyuntu_web_upload_code";
const getRuntimeApiBase = () => {
  if (typeof window === "undefined") return DEFAULT_API_BASE;
  const injected = window.__JFYUNTU_API_BASE__;
  return injected || void 0 || DEFAULT_API_BASE;
};
const joinUrl = (base, path) => {
  if (/^https?:\/\//i.test(path)) return path;
  return `${base.replace(/\/$/, "")}/${path.replace(/^\//, "")}`;
};
const normalizeToken = (token = "") => token.replace(/^Bearer\s+/i, "").trim();
const normalizeCurrentUser = (raw = {}) => {
  const user = raw?.user_info || raw?.user || raw?.info || raw?.profile || raw || {};
  const id = String(user.id || user.uid || user.user_id || user.userid || user.userId || user.userID || "");
  return {
    ...user,
    id,
    uid: String(user.uid || id || "")
  };
};
const getCurrentUserId = (raw = {}) => {
  const user = normalizeCurrentUser(raw);
  return String(user.id || user.uid || "");
};
const getUrlHomeTarget = () => {
  if (typeof window === "undefined") return { targetUserId: "", shareCode: "" };
  const params = new URLSearchParams(window.location.search);
  return {
    targetUserId: "",
    shareCode: params.get("code") || params.get("share_code") || ""
  };
};
const buildHomeTargetParams = (target = {}) => {
  const value = typeof target === "string" ? { targetUserId: target, shareCode: "" } : { targetUserId: target.targetUserId || "", shareCode: target.shareCode || target.code || "" };
  return value.shareCode ? { code: value.shareCode } : { target_user_id: value.targetUserId };
};
const isMockEnabled = () => {
  return false;
};
const requestMockApi = async (path, options = {}) => {
  {
    throw new ApiError("Mock 未启用");
  }
};
const uploadWithMockApi = async (path, formData) => {
  {
    throw new ApiError("Mock 未启用");
  }
};
const removeAuthCallbackParams = () => {
  if (typeof window === "undefined") return;
  const url = new URL(window.location.href);
  let changed = false;
  ["token", "access_token", "authorization", "login", "error"].forEach((key) => {
    if (url.searchParams.has(key)) {
      url.searchParams.delete(key);
      changed = true;
    }
  });
  if (changed) {
    const next = `${url.pathname}${url.search}${url.hash}`;
    window.history.replaceState({}, "", next || window.location.pathname);
  }
};
const authStore = {
  getToken() {
    if (typeof localStorage === "undefined") return "";
    return normalizeToken(localStorage.getItem(TOKEN_KEY) || localStorage.getItem("token") || "");
  },
  setToken(token) {
    if (typeof localStorage === "undefined") return;
    const normalized = normalizeToken(token);
    if (normalized) {
      localStorage.setItem(TOKEN_KEY, normalized);
      localStorage.setItem("token", normalized);
    }
  },
  clearToken() {
    if (typeof localStorage === "undefined") return;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem("token");
    localStorage.removeItem(USER_KEY);
    localStorage.removeItem("userInfo");
  },
  getUser() {
    if (typeof localStorage === "undefined") return null;
    const raw = localStorage.getItem(USER_KEY) || localStorage.getItem("userInfo");
    if (!raw) return null;
    try {
      return normalizeCurrentUser(JSON.parse(raw));
    } catch {
      return null;
    }
  },
  setUser(user) {
    if (typeof localStorage === "undefined") return;
    const normalized = normalizeCurrentUser(user);
    localStorage.setItem(USER_KEY, JSON.stringify(normalized));
    localStorage.setItem("userInfo", JSON.stringify(normalized));
  },
  isLoggedIn() {
    return !!this.getToken();
  },
  consumeCallbackToken() {
    if (typeof window === "undefined") return "";
    const params = new URLSearchParams(window.location.search);
    const token = normalizeToken(
      params.get("token") || params.get("access_token") || params.get("authorization") || ""
    );
    if (token) {
      this.setToken(token);
      removeAuthCallbackParams();
      return token;
    }
    const error = params.get("error");
    if (error) removeAuthCallbackParams();
    return "";
  }
};
const uploadTokenStore = {
  get(code = "") {
    if (typeof sessionStorage === "undefined") return "";
    const savedCode = sessionStorage.getItem(UPLOAD_TOKEN_CODE_KEY) || "";
    if (code && savedCode && savedCode !== code) return "";
    return normalizeToken(sessionStorage.getItem(UPLOAD_TOKEN_KEY) || "");
  },
  set(token, code = "") {
    if (typeof sessionStorage === "undefined") return;
    sessionStorage.setItem(UPLOAD_TOKEN_KEY, normalizeToken(token));
    if (code) sessionStorage.setItem(UPLOAD_TOKEN_CODE_KEY, code);
  },
  clear() {
    if (typeof sessionStorage === "undefined") return;
    sessionStorage.removeItem(UPLOAD_TOKEN_KEY);
    sessionStorage.removeItem(UPLOAD_TOKEN_CODE_KEY);
  }
};
const buildQuery = (params) => {
  const search = new URLSearchParams();
  Object.entries(params || {}).forEach(([key, value]) => {
    if (value === void 0 || value === null || value === "") return;
    search.set(key, String(value));
  });
  return search.toString();
};
async function apiRequest(path, options = {}) {
  if (isMockEnabled()) {
    return requestMockApi(path, options);
  }
  const method = options.method || "GET";
  const query = buildQuery(options.params);
  const url = `${joinUrl(getRuntimeApiBase(), path)}${query ? `?${query}` : ""}`;
  const token = normalizeToken(options.token || (options.auth === false ? "" : authStore.getToken()));
  const headers = {
    "X-Requested-With": "XMLHttpRequest"
  };
  if (token) headers.Authorization = `Bearer ${token}`;
  const init = { method, headers };
  if (method !== "GET") {
    headers["Content-Type"] = "application/json";
    init.body = JSON.stringify(options.body || {});
  }
  const response = await fetch(url, init);
  const payload = await response.json().catch(() => null);
  if (!payload) {
    throw new ApiError("接口响应异常", response.status);
  }
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || "请求失败", Number(payload.code), payload.data);
  }
  return payload.data;
}
async function apiUpload(path, formData, token = authStore.getToken()) {
  if (isMockEnabled()) {
    return uploadWithMockApi();
  }
  const headers = {
    "X-Requested-With": "XMLHttpRequest"
  };
  const normalized = normalizeToken(token);
  if (normalized) headers.Authorization = `Bearer ${normalized}`;
  const response = await fetch(joinUrl(getRuntimeApiBase(), path), {
    method: "POST",
    headers,
    body: formData
  });
  const payload = await response.json().catch(() => null);
  if (!payload) throw new ApiError("上传响应异常", response.status);
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || "上传失败", Number(payload.code), payload.data);
  }
  return payload.data;
}
const pcApi = {
  getLoginOauthConfig: (redirect = "") => apiRequest("user/login/oauth_config", { params: { redirect, timestamp: Date.now() }, auth: false }),
  getLoginQrcode: () => apiRequest("user/login/qrcode", { auth: false }),
  checkLoginStatus: (scene) => apiRequest("user/login/status", { params: { scene, timestamp: Date.now() }, auth: false }),
  getCurrentUser: async () => normalizeCurrentUser(await apiRequest("user/show_info")),
  updatePcSettings: (body) => apiRequest("user/update_pc_settings", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getHomeInfo: (target = "") => apiRequest("user/home/info", { params: { ...buildHomeTargetParams(target), timestamp: Date.now() } }),
  getHomeCategories: (target = "", fid = "", includeCurrent = 0) => apiRequest("user/home/categories", {
    params: { ...buildHomeTargetParams(target), fid, include_current: includeCurrent, timestamp: Date.now() }
  }),
  getHomeProducts: (target = "", cateId = "") => apiRequest("user/home/products", {
    params: { ...buildHomeTargetParams(target), cate_id: cateId, timestamp: Date.now() }
  }),
  getHomeProductDetail: (target = "", productId) => apiRequest("user/home/products/detail", {
    params: { ...buildHomeTargetParams(target), product_id: productId, timestamp: Date.now() }
  }),
  getHomeShareLink: (target, path = "") => apiRequest("user/home/share_link", {
    params: { ...buildHomeTargetParams(target), path, timestamp: Date.now() },
    auth: false
  }),
  getHomeMiniCode: (target, type = "home", id = "", path = "") => apiRequest("user/home/minicode", {
    params: { ...buildHomeTargetParams(target), type, id, path, timestamp: Date.now() },
    auth: false
  }),
  getManagementCategories: (params) => apiRequest("album/lists/folder", { method: "POST", body: { folder_type: 1, limit: 100, timestamp: Date.now(), ...params } }),
  getManagementProducts: (params) => apiRequest("album/lists/folder", { method: "POST", body: { folder_type: 2, limit: 50, timestamp: Date.now(), ...params } }),
  getProductEditDetail: (fid) => apiRequest("album/products/detail", { method: "POST", body: { fid, timestamp: Date.now() } }),
  createProductOrCategory: (body) => apiRequest("album/create/folder", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  editProductOrCategory: (body) => apiRequest("album/edit/folder", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  updateProductStatus: (body) => apiRequest("album/product/update_status", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  deleteProductOrFolder: (fid, delType = 1) => apiRequest("album/delete/folder", { method: "POST", body: { fid, del_type: delType, timestamp: Date.now() } }),
  uploadProductImage: (fid, file, type) => {
    const form = new FormData();
    form.append("pid", fid);
    form.append("files", file, file.name);
    form.append("filename", file.name);
    form.append("file_name", file.name);
    form.append("original_name", file.name);
    form.append("name", file.name);
    form.append("file_type", type === "detailChart" ? "2" : "1");
    return apiUpload("album/upload/folder", form);
  },
  getBatchUploadLink: (fid) => apiRequest("album/batch_link", { params: { fid, timestamp: Date.now() } }),
  resetBatchUploadLink: (fid) => apiRequest("album/reset_batch_link", { method: "POST", body: { fid, timestamp: Date.now() } }),
  saveBatchUploadPassword: (body) => apiRequest("album/batch_upload_password", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getWebUploadInfo: (code) => apiRequest("web/upload", { params: { code, timestamp: Date.now() }, auth: false }),
  getWebUploadToken: (code, password = "") => apiRequest("web/token/upload", { method: "POST", body: { code, password }, auth: false }),
  uploadWebProductImage: (fid, file, type, token) => {
    const form = new FormData();
    form.append("fid", fid);
    form.append("files", file, file.name);
    form.append("filename", file.name);
    form.append("file_name", file.name);
    form.append("original_name", file.name);
    form.append("name", file.name);
    form.append("file_type", type === "detailChart" ? "2" : "1");
    return apiUpload("web/folder/pic/upload", form, token);
  },
  toggleFavorite: (type, id, add) => apiRequest(add ? "user/add/collect" : "user/cancel/collect", {
    method: "POST",
    body: { type, id, timestamp: Date.now() }
  }),
  addVisit: (type, id) => apiRequest("user/add/visit", { method: "POST", body: { type, id, timestamp: Date.now() } }),
  getFavorites: (type = "all", key = "", page = 1) => apiRequest("user/collect/records", { params: { type, key, page, timestamp: Date.now() } }),
  getVisits: (type = "all", key = "", page = 1) => apiRequest("user/visit/records", { params: { type, key, page, timestamp: Date.now() } }),
  deleteVisit: (visitIds) => apiRequest("user/del/visit", { method: "POST", body: { visit_ids: visitIds, timestamp: Date.now() } }),
  getSubscriptionPlans: () => apiRequest("web_payment/subscription/plans", { auth: false }),
  createMembershipOrder: (body) => apiRequest("web_payment/membership/order/create", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getPaymentOrderStatus: (orderNo) => apiRequest("web_payment/order/status", { params: { order_no: orderNo, timestamp: Date.now() } }),
  getPaymentOrders: (params = {}) => apiRequest("web_payment/orders", { params: { page: 1, page_size: 20, timestamp: Date.now(), ...params } }),
  getAiResources: (params = {}) => apiRequest("album/ai/resources", { params: { page: 1, page_size: 30, timestamp: Date.now(), ...params } }),
  importAiResource: (resourceId, role = "cover", productId = "") => apiRequest("album/ai/import_resource", {
    method: "POST",
    body: { resource_id: resourceId, role, product_id: productId, timestamp: Date.now() }
  }),
  getRecycleList: (params = {}) => apiRequest("user/recycle/list", { params: { page: 1, limit: 20, timestamp: Date.now(), ...params } }),
  restoreRecycleItem: (id) => apiRequest("user/restore/product", { method: "POST", body: { product_ids: id, timestamp: Date.now() } }),
  deleteRecycleItem: (id) => apiRequest("user/destroy/product", { method: "POST", body: { product_ids: id, timestamp: Date.now() } })
};
const _sfc_main$8 = defineComponent({ __name: "Dialog", props: { open: { type: Boolean }, defaultOpen: { type: Boolean }, modal: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DialogRoot() {
    return DialogRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/Dialog.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const Dialog = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "DialogClose", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DialogClose() {
    return DialogClose;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogClose, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogClose.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
_export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "DialogContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get X() {
    return X;
  }, get DialogClose() {
    return DialogClose;
  }, get DialogContent() {
    return DialogContent$1;
  }, get DialogOverlay() {
    return DialogOverlay;
  }, get DialogPortal() {
    return DialogPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, null, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.DialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3(ssrRenderComponent($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.X, { class: "w-4 h-4" }, null, _parent4, _scopeId3)), _push4(`<span class="sr-only"${_scopeId3}>Close</span>`);
        else return [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }), createVNode($setup.DialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogContent.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const DialogContent = _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "DialogDescription", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DialogDescription() {
    return DialogDescription$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogDescription, mergeProps($setup.forwardedProps, { class: $setup.cn("text-sm text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogDescription.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const DialogDescription = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "DialogFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
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
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogFooter.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const DialogFooter = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "DialogHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col gap-y-1.5 text-center sm:text-left", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogHeader.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const DialogHeader = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "DialogScrollContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get X() {
    return X;
  }, get DialogClose() {
    return DialogClose;
  }, get DialogContent() {
    return DialogContent$1;
  }, get DialogOverlay() {
    return DialogOverlay;
  }, get DialogPortal() {
    return DialogPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogOverlay, { class: "fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
        const originalEvent = event.detail.originalEvent, target = originalEvent.target;
        (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
      } }), { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push4, _parent4, _scopeId3), _push4(ssrRenderComponent($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(ssrRenderComponent($setup.X, { class: "w-4 h-4" }, null, _parent5, _scopeId4)), _push5(`<span class="sr-only"${_scopeId4}>Close</span>`);
          else return [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })];
      }), _: 3 }, _parent3, _scopeId2));
      else return [createVNode($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
        const originalEvent = event.detail.originalEvent, target = originalEvent.target;
        (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
      } }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class", "onPointerDownOutside"])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogOverlay, { class: "fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, { default: withCtx(() => [createVNode($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
      const originalEvent = event.detail.originalEvent, target = originalEvent.target;
      (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
    } }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class", "onPointerDownOutside"])]), _: 3 })];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogScrollContent.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const DialogScrollContent = _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "DialogTitle", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DialogTitle() {
    return DialogTitle$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogTitle, mergeProps($setup.forwardedProps, { class: $setup.cn("text-lg font-semibold leading-none tracking-tight", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogTitle.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const DialogTitle = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "DialogTrigger", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DialogTrigger() {
    return DialogTrigger;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogTrigger, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogTrigger.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
_export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  DialogTitle as D,
  DialogHeader as a,
  DialogContent as b,
  Dialog as c,
  authStore as d,
  DialogFooter as e,
  getCurrentUserId as f,
  getUrlHomeTarget as g,
  DialogDescription as h,
  DialogScrollContent as i,
  isMockEnabled as j,
  pcApi as p,
  uploadTokenStore as u
};
