import config from "../config";

import { showLoading } from "../helper/base.js";
import { getMiniCode } from "@/common/request/api.js";
import Cache from "../helper/cache.js";
const cache = new Cache();

const HEADER = {
  "content-type": "application/json",
  "X-Requested-With": "XMLHttpRequest",
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
    let token = uni.getStorageSync("token");
    let header = HEADER;
    Object.assign(header, {
      "authorization-token": `Bearer ${token}`,
    });
    let url = full_url ? path : `${config.host}/${path}`;
    uni.request({
      url,
      data,
      header,
      method: type ? type.toUpperCase() : "GET",
      success: (res) => {
        if (_loading) uni.hideLoading();
        if (res.data.code === 403) {
          //token过期
          uni.clearStorage();
          return reject(res);
        }
        if (res.data.code == 0) {
          return resolve(res.data);
        } else {
          if (show_err) {
            uni.showModal({
              title: "提示",
              content: res.data.msg,
              showCancel: false,
            });
          }
          return resolve(res.data);
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
