import config from "../config";
import go from "./go";
import Cache from "../helper/cache.js";
import * as base from "@/common/helper/base.js";

const cache = new Cache();

// 登录状态标记
let isLoggingIn = false;
let loginPromise = null;

/**
 * 无感登录 - 完整流程
 * 1. 获取 openid
 * 2. 获取手机号授权
 * 3. 获取用户信息
 * @returns {Promise<Boolean>} 登录是否成功
 */
export const silentLogin = (uid) => {
  // 如果正在登录中，返回同一个 Promise
  if (isLoggingIn && loginPromise) {
    return loginPromise;
  }

  // 检查是否已登录
  const token = uni.getStorageSync("token");
  const userInfo = uni.getStorageSync("userInfo");
  if (token && userInfo) {
    return Promise.resolve(true);
  }

  isLoggingIn = true;

  loginPromise = new Promise(async (resolve) => {
    try {
      // 步骤1: 获取 openid
      const hasOpenid = await getMiniCode();
      if (!hasOpenid) {
        console.error("获取 openid 失败");
        isLoggingIn = false;
        loginPromise = null;
        return resolve(false);
      }

      // 步骤2: 检查是否已有 token
      const existToken = uni.getStorageSync("token");
      if (existToken) {
        // 已有 token，直接获取用户信息
        const userInfoSuccess = await getUserInfo();
        isLoggingIn = false;
        loginPromise = null;
        return resolve(userInfoSuccess);
      }

      // 步骤3: 需要手机号授权，跳转到登录页
      console.log("需要手机号授权，跳转登录页");
      isLoggingIn = false;
      loginPromise = null;

      // 跳转到登录页
      uni.navigateTo({
        url: "/pages/login/login?uid=" + uid,
      });

      return resolve(false);
    } catch (error) {
      console.error("无感登录失败:", error);
      isLoggingIn = false;
      loginPromise = null;
      return resolve(false);
    }
  });

  return loginPromise;
};

/**
 * 获取微信小程序 openid
 */
export const getMiniCode = () => {
  return new Promise((resolve, reject) => {
    let openid = uni.getStorageSync("openid");
    if (openid) {
      return resolve(true);
    }

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
          )
            .then((res) => {
              const { data, code } = res;

              if (code == 0) {
                uni.setStorageSync("openid", data.openid);
                cache.set("openid", data.openid);
                return resolve(true);
              } else {
                return resolve(false);
              }
            })
            .catch(() => {
              return resolve(false);
            });
        } else {
          return resolve(false);
        }
      },
      fail: (err) => {
        console.error("uni.login 失败:", err);
        return resolve(false);
      },
    });
  });
};

/**
 * 手机号授权登录
 * @param {String} code 手机号授权 code
 * @returns {Promise<Boolean>}
 */
export const login = (code) => {
  return new Promise((resolve, reject) => {
    let openid = cache.get("openid") || uni.getStorageSync("openid");

    if (!openid) {
      return reject(new Error("请先获取 openid"));
    }

    go(
      "user/phone",
      {
        code: code,
        openid: openid,
      },
      "post",
      {
        loading: true,
        loading_tip: "登录中...",
        full_url: false,
        show_err: true,
      }
    ).then(
      async (res) => {
        if (res.code == 0) {
          const data = res.data;
          uni.setStorageSync("user", data.user);
          uni.setStorageSync("token", data.token);

          // 获取用户信息
          const userInfoSuccess = await getUserInfo();

          if (userInfoSuccess) {
            return resolve(true);
          }

          return reject(new Error("获取用户信息失败"));
        } else {
          return reject(new Error(res.msg || "登录失败"));
        }
      },
      (err) => {
        console.error("登录失败:", err);
        const errorMsg =
          err?.data?.msg || err?.msg || err?.errMsg || "登录失败，请重试";
        return reject(new Error(errorMsg));
      }
    );
  });
};

/**
 * 获取用户信息
 * @returns {Promise<Boolean>}
 */
const getUserInfo = () => {
  return new Promise((resolve) => {
    const querys = {
      timestamp: new Date().getTime(),
    };
    const data = {
      ...querys,
      sign: base.getASCII(querys),
    };

    go("user/show_info", data, "get", {
      show_err: false,
      loading: false,
    })
      .then((res) => {
        if (res && res.data) {
          const userInfo = {
            ...res.data,
          };
          uni.setStorageSync("userInfo", userInfo);
          return resolve(true);
        } else {
          return resolve(false);
        }
      })
      .catch((err) => {
        console.error("获取用户信息失败:", err);
        return resolve(false);
      });
  });
};

/**
 * 检查登录状态
 * @returns {Boolean}
 */
export const checkLoginStatus = () => {
  const token = uni.getStorageSync("token");
  const userInfo = uni.getStorageSync("userInfo");
  return !!(token && userInfo);
};

/**
 * 退出登录
 */
export const logout = () => {
  uni.removeStorageSync("token");
  uni.removeStorageSync("user");
  uni.removeStorageSync("userInfo");

  uni.reLaunch({
    url: "/pages/login/login",
  });
};

/**
 * 刷新用户信息
 * @returns {Promise<Boolean>}
 */
export const refreshUserInfo = () => {
  return getUserInfo();
};
