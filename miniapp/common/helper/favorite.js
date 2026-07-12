import go from "../request/go";
import * as base from "@/common/helper/base.js";
/**
 * 收藏/取消收藏
 * @param {Object} options 配置项
 * @param {String} options.type 类型：'homepage' | 'product' | 'category'
 * @param {Array} options.id 目标ID
 * @param {Boolean} options.isFavorite 是否收藏（true: 收藏, false: 取消收藏）
 * @returns {Promise<Boolean>} 返回是否成功
 */
export async function toggleFavorite(options) {
  const { type, id, isFavorite } = options;
  if (!type || !id) {
    uni.showToast({
      title: "参数错误",
      icon: "none",
    });
    return false;
  }

  const apiMap = {
    homepage: {
      add: "user/add/collect",
      remove: "user/cancel/collect",
    },
    product: {
      add: "user/add/collect",
      remove: "user/cancel/collect",
    },
    category: {
      add: "user/add/collect",
      remove: "user/cancel/collect",
    },
  };

  const api = apiMap[type];
  if (!api) {
    uni.showToast({
      title: "不支持的收藏类型",
      icon: "none",
    });
    return false;
  }

  const action = isFavorite ? "add" : "remove";
  const method = isFavorite ? "post" : "post";
  const apiPath = api[action];

  const querys = {
    id,
    type,
    timestamp: new Date().getTime(),
  };

  const data = {
    ...querys,
    sign: base.getASCII ? base.getASCII(querys) : "",
  };

  try {
    const res = await go(apiPath, data, method, {
      show_err: true,
      loading: true,
    });

    if (res && res.code === 0) {
      uni.showToast({
        title: isFavorite ? "收藏成功" : "取消收藏成功",
        icon: "success",
      });
      return true;
    } else {
      return false;
    }
  } catch (error) {
    console.error("batchToggleFavorite error:", error);
    return false;
  }
}

export async function addVisit(params) {
  try {
    const res = await go("user/add/visit", params, "post", {
      show_err: false,
      loading: false,
    });

    if (res && res.code === 0) {
      return true;
    } else {
      return false;
    }
  } catch (error) {
    console.error("batchToggleFavorite error:", error);
    return false;
  }
}

export default {
  toggleFavorite,
  addVisit,
};
