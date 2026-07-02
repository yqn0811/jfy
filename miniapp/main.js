import App from "./App";
// #ifndef VUE3
import Vue from "vue";
import * as base from "@/common/helper/base.js";
import go from "@/common/request/go.js";
import Cache from "@/common/helper/cache.js";
import config from "@/common/config";
import uView from "@/uni_modules/uview-ui";
import { toggleFavorite, addVisit } from "@/common/helper/favorite.js";
import { getShareConfig } from "@/common/helper/share.js";
import inputPlaceholder from "@/common/helper/inputPlaceholder.js";
import {
  silentLogin,
  checkLoginStatus,
  logout,
  refreshUserInfo,
} from "@/common/request/api.js";

Vue.prototype.$go = go;
Vue.prototype.$config = config;
Vue.prototype.$img = config.domain;
Vue.prototype.$cache = new Cache();
Vue.prototype.$addVisit = addVisit;
Vue.prototype.$base = base;
Vue.prototype.$toggleFavorite = toggleFavorite;
Vue.prototype.$getShareConfig = getShareConfig;
Vue.prototype.$silentLogin = silentLogin;
Vue.prototype.$logout = logout;
Vue.prototype.$checkLoginStatus = checkLoginStatus;
Vue.prototype.$refreshUserInfo = refreshUserInfo;
Vue.mixin(inputPlaceholder);
Vue.use(uView);

Vue.config.productionTip = false;

App.mpType = "app";
const app = new Vue({
  ...App,
});
app.$mount();
// #endif

// #ifdef VUE3
import { createSSRApp } from "vue";
export function createApp() {
  const app = createSSRApp(App);
  return {
    app,
  };
}
// #endif
