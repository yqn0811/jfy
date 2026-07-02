/**
 * 分享相关公共方法
 */
import go from "../request/go";
import * as base from "@/common/helper/base.js";
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
  const { type, userId, title, path, imageUrl, atype, hid } = options;

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
  if (type === "poster") {
    data.type = atype;
    data.id = hid;
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
};
