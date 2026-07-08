/**
 * 分享相关公共方法
 */
import go from "../request/go";
import * as base from "@/common/helper/base.js";

export function normalizeShareValue(value = "") {
  if (value === null || value === undefined) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  return text;
}

export function parseShareScene(scene = "") {
  const result = {};
  if (!scene) return result;

  let text = "";
  try {
    text = decodeURIComponent(String(scene));
  } catch (e) {
    text = String(scene);
  }
  text.split("&").forEach((part) => {
    const [key, ...rest] = part.split("=");
    if (!key) return;
    const value = normalizeShareValue(rest.join("="));
    if (value) {
      result[key] = value;
    }
  });

  const typeMap = {
    c: "category",
    p: "product",
    s: "selection",
    h: "home",
  };
  if (!result.uid && result.u) result.uid = result.u;
  if (!result.id && result.i) result.id = result.i;
  if (!result.type && result.t) result.type = typeMap[result.t] || result.t;
  if (!result.invite_code && result.ic) result.invite_code = result.ic;

  return result;
}

export function getCurrentUserId() {
  const userInfo = uni.getStorageSync("userInfo") || {};
  const user = uni.getStorageSync("user") || {};
  const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
  return (
    normalizeShareValue(userInfo.id) ||
    normalizeShareValue(userInfo.uid) ||
    normalizeShareValue(user.id) ||
    normalizeShareValue(user.uid) ||
    normalizeShareValue(enterpriseInfo.id) ||
    normalizeShareValue(enterpriseInfo.uid) ||
    normalizeShareValue(enterpriseInfo.user_id) ||
    ""
  );
}

export function buildPublicSharePath(type = "home", id = "", uid = "") {
  const ownerUid = normalizeShareValue(uid) || getCurrentUserId();
  const targetId = normalizeShareValue(id);
  const query = [];

  if (targetId) {
    query.push(`id=${encodeURIComponent(targetId)}`);
  }
  if (ownerUid) {
    query.push(`uid=${encodeURIComponent(ownerUid)}`);
  }

  const suffix = query.length ? `?${query.join("&")}` : "";

  if (type === "product") {
    return `/pagesOther/productDetail/productDetail${suffix}`;
  }
  if (type === "category") {
    return `/pagesOther/classDetail/classDetail${suffix}`;
  }

  return `/pages/index/index${ownerUid ? `?uid=${ownerUid}` : ""}`;
}

export function getShareOwnerId(uid = "") {
  return normalizeShareValue(uid) || getCurrentUserId();
}

export function getMerchantShareName(info = {}, fallback = "商户") {
  const enterpriseInfo = uni.getStorageSync("enterpriseInfo") || {};
  const userInfo = uni.getStorageSync("userInfo") || {};
  return (
    normalizeShareValue(info.company_name) ||
    normalizeShareValue(info.shop_name) ||
    normalizeShareValue(info.merchant_name) ||
    normalizeShareValue(info.nickname) ||
    normalizeShareValue(info.nick_name) ||
    normalizeShareValue(info.name) ||
    normalizeShareValue(enterpriseInfo.company_name) ||
    normalizeShareValue(enterpriseInfo.shop_name) ||
    normalizeShareValue(enterpriseInfo.merchant_name) ||
    normalizeShareValue(enterpriseInfo.nickname) ||
    normalizeShareValue(userInfo.company_name) ||
    normalizeShareValue(userInfo.shop_name) ||
    normalizeShareValue(userInfo.merchant_name) ||
    normalizeShareValue(userInfo.nickname) ||
    fallback
  );
}

export function getCategoryShareName(info = {}, fallback = "分类") {
  return (
    normalizeShareValue(info.folder_name) ||
    normalizeShareValue(info.category_name) ||
    normalizeShareValue(info.class_name) ||
    normalizeShareValue(info.title) ||
    normalizeShareValue(info.name) ||
    fallback
  );
}

export function buildTypedShareTitle({
  typeText = "",
  targetName = "",
  merchantName = "",
  prefix = "",
} = {}) {
  const cleanTargetName = normalizeShareValue(targetName);
  const cleanMerchantName = normalizeShareValue(merchantName);
  const cleanTypeText = normalizeShareValue(typeText);
  const label = cleanTargetName || cleanTypeText || "内容";
  const owner = cleanMerchantName || getMerchantShareName();
  const title = owner ? `${owner}的${label}` : label;
  return prefix ? `${prefix}${title}` : title;
}

export function getDefaultShareConfig() {
  return {
    title: "分享",
    path: "/pages/index/index",
    imageUrl: "",
  };
}
/**
 * 获取分享配置
 * @param {Object} options 配置项
 * @param {String} options.type 分享类型：'custom' | 'poster' | 'link' | 'mini'
 * @param {String} options.title 自定义标题（可选）
 * @param {String} options.userId 当前用户ID
 * @param {String} options.path 自定义路径（可选）
 * @param {String} options.imageUrl 自定义图片（可选）
 * @returns {Promise<Object>} 返回分享配置 { title, path, imageUrl }
 */
export async function getShareConfig(options) {
  const { type, userId, title, path, imageUrl, atype, hid, coverUrl } = options;

  // 如果提供了完整的自定义配置，直接返回
  if (type === "custom" && title && path) {
    return {
      title: title,
      path: path,
      imageUrl: imageUrl || "",
    };
  }

  // 验证必要参数
  if (!type) {
    console.error("getShareConfig: type 参数必填");
    return getDefaultShareConfig();
  }
  const apiMap = {
    poster: "user/home/share_poster",
    link: "user/home/share_link",
    mini: "user/home/minicode",
    shortlink: "user/shortlink",
  };

  const apiPath = apiMap[type];
  if (!apiPath) {
    console.error("getShareConfig: 不支持的分享类型", type);
    return getDefaultShareConfig();
  }

  // 构建请求参数
  const querys = {
    path,
    target_user_id: userId,
    timestamp: new Date().getTime(),
  };
  let data = {
    ...querys,
  };
  // 短链接参数
  if (type === "shortlink") {
    data = {
      path,
      title,
      is_permanent: false,
    };
  }
  if (type === "poster" || type === "mini" || type === "link") {
    if (atype) data.type = atype;
    if (hid) data.id = hid;
  }
  if (type === "poster" && coverUrl) {
    data.cover_url = coverUrl;
  }
  const methods = ["shortlink"].includes(type) ? "post" : "get";
  try {
    const res = await go(apiPath, data, methods, {
      show_err: false,
      loading: false,
    });
    return res;
  } catch (error) {
    console.error("getShareConfig error:", error);
    // 出错时使用默认配置或传入的配置
    return {
      title: title || "分享",
      path: path || "/pages/index/index",
      imageUrl: imageUrl || "",
    };
  }
}

export default {
  getShareConfig,
  buildTypedShareTitle,
  getCategoryShareName,
  getMerchantShareName,
};
