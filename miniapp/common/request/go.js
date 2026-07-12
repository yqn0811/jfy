import config from "../config";

import { showLoading } from "../helper/base.js";
import { buildAuthHeader, getAuthToken, normalizeAuthToken } from "../helper/auth.js";

const HEADER = {
  "content-type": "application/json",
  "X-Requested-With": "XMLHttpRequest",
};

const AUTH_ERROR_CODES = [4001, 4100, 403];
let isRedirectingToLogin = false;

const OPTIONAL_AUTH_PATHS = [
  "user/home/info",
  "user/home/categories",
  "user/home/products",
  "user/home/products/detail",
  "user/home/products/details",
  "user/home/picture/detail",
  "user/home/minicode",
  "user/home/minicode_image",
  "user/home/share_link",
  "user/home/share_poster",
];

const getSafeErrorMessage = (message) => {
  const text = message === null || message === undefined ? "" : String(message).trim();
  const lowerText = text.toLowerCase();

  if (
    lowerText === "need login" ||
    lowerText === "need token" ||
    lowerText === "missing token" ||
    lowerText.includes("wrong number of segments") ||
    lowerText.includes("not enough segments") ||
    (lowerText.includes("token") && lowerText.includes("segments"))
  ) {
    return "登录状态异常，请重新登录后再试";
  }

  return text || "请求失败，请稍后重试";
};

const isAuthError = (responseData = {}) => {
  const code = Number(responseData.code);
  const message = getSafeErrorMessage(responseData.msg || responseData.message);
  return (
    AUTH_ERROR_CODES.includes(code) ||
    message === "登录状态异常，请重新登录后再试" ||
    message.includes("请先授权登录") ||
    message.includes("用户不存在")
  );
};

const getCurrentLoginUid = () => {
  const pages = typeof getCurrentPages === "function" ? getCurrentPages() : [];
  const currentPage = pages && pages[pages.length - 1];
  const options = (currentPage && currentPage.options) || {};
  const uid = options.uid || options.target_user_id || "";
  if (!uid || uid === "undefined" || uid === "null") return "";
  return String(uid);
};

const buildCurrentPagePath = () => {
  const pages = typeof getCurrentPages === "function" ? getCurrentPages() : [];
  const currentPage = pages && pages[pages.length - 1];
  if (!currentPage || !currentPage.route) return "";
  const options = currentPage.options || {};
  const query = Object.keys(options)
    .filter((key) => options[key] !== undefined && options[key] !== null && options[key] !== "")
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(options[key])}`)
    .join("&");
  return `/${currentPage.route}${query ? `?${query}` : ""}`;
};

const saveLoginRedirect = () => {
  const redirectUrl = buildCurrentPagePath();
  if (redirectUrl) {
    uni.setStorageSync("share_login_redirect", redirectUrl);
  }
};

const getCurrentInviteCode = () => {
  const pages = typeof getCurrentPages === "function" ? getCurrentPages() : [];
  const currentPage = pages && pages[pages.length - 1];
  const options = (currentPage && currentPage.options) || {};
  const inviteCode = options.invite_code || uni.getStorageSync("pending_invite_code") || "";
  if (!inviteCode || inviteCode === "undefined" || inviteCode === "null") return "";
  return String(inviteCode);
};

const redirectToLogin = () => {
  const pages = typeof getCurrentPages === "function" ? getCurrentPages() : [];
  const currentPage = pages && pages[pages.length - 1];
  const route = currentPage && currentPage.route;
  if (route === "pages/login/login" || isRedirectingToLogin) return;

  isRedirectingToLogin = true;
  saveLoginRedirect();
  uni.removeStorageSync("token");
  uni.removeStorageSync("user");
  uni.removeStorageSync("userInfo");

  const uid = getCurrentLoginUid();
  const inviteCode = getCurrentInviteCode();
  const params = [];
  if (uid) params.push(`uid=${encodeURIComponent(uid)}`);
  if (inviteCode) params.push(`invite_code=${encodeURIComponent(inviteCode)}`);
  const loginUrl = `/pages/login/login${params.length ? `?${params.join("&")}` : ""}`;
  uni.navigateTo({
    url: loginUrl,
    fail: () => {
      uni.redirectTo({ url: loginUrl });
    },
    complete: () => {
      setTimeout(() => {
        isRedirectingToLogin = false;
      }, 800);
    },
  });
};

const isOptionalAuthRequest = (path = "") => {
  const text = String(path || "").replace(/^https?:\/\/[^/]+\/api\//i, "").replace(/^\//, "");
  return OPTIONAL_AUTH_PATHS.some((item) => text === item || text.indexOf(`${item}?`) === 0);
};

const go = (
  path,
  data = {},
  type = "get",
  {
    loading = true, //是否要loading
    loading_tip = "", //loading文字
    full_url = false, //是否是完整路径，否会自动组装
    show_err = true, //如果有错误信息返回 是否弹出
    is_carrying_token = false, //是否需要携带token请求
  } = {}
) => {
  let _loading = true;
  if (loading && type !== "post") {
    _loading = config.openLoading;
  } else {
    _loading = loading;
  }
  if (_loading) {
    showLoading(loading_tip);
  }
  return new Promise((resolve, reject) => {
    const rawToken = uni.getStorageSync("token");
    const token = getAuthToken();
    const header = { ...HEADER, ...buildAuthHeader(token) };
    if (token) {
      const segments = token.split(".");
      if (segments.length !== 3 || segments.some((item) => !item)) {
        delete header["authorization-token"];
        uni.removeStorageSync("token");
      }
    } else if (normalizeAuthToken(rawToken)) {
      uni.removeStorageSync("token");
    }
    let url = full_url ? path : `${config.host}/${path}`;
    uni.request({
      url,
      data,
      header,
      method: type ? type.toUpperCase() : "GET",
      success: (res) => {
        if (_loading) uni.hideLoading();
        const responseData = res.data || {};
        if (isAuthError(responseData) && !isOptionalAuthRequest(path)) {
          redirectToLogin();
          return resolve(responseData);
        }
        if (responseData.code == 0) {
          return resolve(responseData);
        } else {
          if (show_err) {
            uni.showModal({
              title: "提示",
              content: getSafeErrorMessage(responseData.msg || responseData.message),
              showCancel: false,
            });
          }
          return resolve(responseData);
        }
      },
      fail: (err) => {
        if (_loading) uni.hideLoading();
        return reject(err);
      },
    });
  });
};

export default go;
