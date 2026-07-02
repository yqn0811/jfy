import config from "../config";
import go from "./go";
import Cache from "../helper/cache.js";
import * as base from "@/common/helper/base.js";

const cache = new Cache();

/**
 *
 */
export const getMiniCode = () => {
  return new Promise((resolve, reject) => {
    let openid = uni.getStorageSync("openid");
    if (openid) {
      return resolve(true);
    } else {
      uni.login({
        success: (res) => {
          if (res.code) {
            go(
              "user/openid",
              {
                code: res.code,
              },
              "get",
              {
                loading: false,
                loading_tip: "",
                full_url: false,
                show_err: false,
              }
            ).then((res) => {
              const { data, code } = res;

              if (code == 0) {
                uni.setStorageSync("openid", data.openid);
              }
              return resolve(true);
            });
          }
        },
        fail: (err) => {
          // return reject(err);
          return resolve(true);
        },
      });
    }
  });
};

/**
 * 需要token的接口需要调用
 * 用户登录信息
 * code：手机号
 * openid：缓存中的openid
 */
export const login = (code) => {
  return new Promise((resolve) => {
    let openid = cache.get("openid");
    go(
      "user/phone",
      {
        code: code,
        openid: openid,
      },
      "post",
      {
        loading: false,
        loading_tip: "",
        full_url: false,
        show_err: true,
      }
    ).then(
      (res) => {
        if (res.code == 0) {
          const data = res.data;
          uni.setStorageSync("user", data.user);
          uni.setStorageSync("token", data.token);
          getUserInfo();
          uni.showToast({
            title: "手机号授权成功！",
          });
          return resolve(true);
        }
        return resolve(false);
      },
      (err) => {
        return resolve(false);
      }
    );
  });
};
const getUserInfo = () => {
  const querys = {
    timestamp: new Date().getTime(),
  };
  const data = {
    ...querys,
    sign: base.getASCII(querys),
  };
  go("user/show_info", data, "get", {
    show_err: true,
  })
    .then((res) => {
      // 合并默认数据和实际数据，确保所有字段都有值
      const userInfo = {
        ...res.data,
      };
      uni.setStorageSync("userInfo", userInfo);
    })
    .catch((err) => {
      console.error("获取用户信息失败:", err);
    });
};
