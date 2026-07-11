<script>
import { getMiniCode } from "@/common/request/api.js";
export default {
  globalData: {
    bottomLift: 0,
    navBarTop: 0, //顶部导航栏距离
    navBarHeight: 0, //顶部导航栏高度，
    navBarWidth: 0,
    screenHeight: 0,
  },
  onLaunch: function () {
    uni.getSystemInfo({
      success: (res = {}) => {
        const screenHeight = res.screenHeight || res.windowHeight || 0;
        const safeArea = res.safeArea || {};
        const safeBottom =
          typeof safeArea.bottom === "number" ? safeArea.bottom : screenHeight;
        const menuButtonInfo = this.getMenuButtonInfo(res);

        this.globalData.bottomLift = Math.max(0, screenHeight - safeBottom);
        this.globalData.navBarHeight = menuButtonInfo.height;
        this.globalData.navBarTop = menuButtonInfo.top;
        this.globalData.navBarWidth = menuButtonInfo.width;
        this.globalData.screenHeight = screenHeight;
      },
      fail(err) {},
    });
    if (typeof uni.loadFontFace === "function") {
      uni.loadFontFace({
        family: "DOUYU",
        global: true,
        source:
          'url("https://manage.4funinnovate.com/assets/front/douyuzhuiguangti.ttf")',
        success() {},
        fail(err) {
          console.log(err);
        },
      });
    }
    this.checkUpdate();
    //版本更新
    // const updateManager = uni.getUpdateManager();
    // updateManager.onCheckForUpdate(function(res) {
    // 	// 请求完新版本信息的回调
    // 	if (res.hasUpdate) {
    // 		updateManager.onUpdateReady(function(res) {
    // 			uni.showModal({
    // 				title: '更新提示',
    // 				content: '新版本已经准备好，是否重启应用？',
    // 				success(res) {
    // 					if (res.confirm) {
    // 						// 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
    // 						updateManager.applyUpdate();
    // 					}
    // 				}
    // 			});

    // 		});
    // 		updateManager.onUpdateFailed(function() {
    // 			uni.showModal({
    // 				title: '已经有新版本了哟~',
    // 				content: '新版本已经上线啦~，请您删除当前小程序，重新搜索打开哟~'
    // 			})
    // 		})
    // 	}

    // });
  },
  onShow: function () {
  },
  onHide: function () {
  },
  methods: {
    getMenuButtonInfo(systemInfo = {}) {
      const fallback = {
        height: 32,
        top: systemInfo.statusBarHeight || 0,
        width: 0,
      };
      if (typeof uni.getMenuButtonBoundingClientRect !== "function") {
        return fallback;
      }
      try {
        const info = uni.getMenuButtonBoundingClientRect();
        return info && info.height ? info : fallback;
      } catch (e) {
        return fallback;
      }
    },
    checkUpdate() {
      if (
        typeof wx !== "undefined" &&
        wx.canIUse &&
        wx.canIUse("getUpdateManager") &&
        wx.getUpdateManager
      ) {
        const updateManager = wx.getUpdateManager();

        // 监听检查更新结果
        updateManager.onCheckForUpdate((res) => {
          if (res.hasUpdate) {
          }
        });

        // 监听下载完成事件
        updateManager.onUpdateReady(() => {
          wx.showModal({
            title: "更新提示",
            content: "新版本已准备好，是否重启应用？",
            success: (res) => {
              if (res.confirm) {
                // 强制重启应用
                updateManager.applyUpdate();
              }
            },
          });
        });

        // 监听下载失败事件
        updateManager.onUpdateFailed(() => {
          wx.showModal({
            title: "更新失败",
            content: "新版本下载失败，请检查网络后重试",
            showCancel: false,
          });
        });
      } else if (typeof wx !== "undefined" && wx.showModal) {
        wx.showModal({
          title: "提示",
          content: "当前微信版本过低，请升级到最新版本后重试",
          showCancel: false,
        });
      }
    },
  },
};
</script>

<style lang="scss">
@import "@/uni_modules/uview-ui/index.scss";

page {
  background-color: #f8f8f8;
}

.jf-input-placeholder {
  color: #999999;
  font-size: inherit;
  line-height: inherit;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.jf-textarea-placeholder {
  color: #999999;
  font-size: inherit;
  line-height: inherit;
}

.page {
  width: 100%;
  max-width: 100%;
  min-height: 100vh;
  position: relative;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: scroll;
  display: flex;
  flex-direction: column;
  .page-scoll {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
}
</style>
