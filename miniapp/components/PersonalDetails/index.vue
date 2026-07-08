<template>
  <!-- 弹窗模式 -->
  <u-popup
    :show="visible"
    mode="bottom"
    :round="16"
    bgColor="transparent"
    :safe-area-inset-bottom="false"
    @close="close"
  >
    <view class="popup-inner">
      <!-- 重用页面内容 -->
      <view class="content">
        <!-- 头部卡片 -->
        <view class="card head-card">
          <view class="left">
            <image
              class="avatar"
              :src="user.company_logo || defaultAvatar"
              mode="aspectFill"
            />
            <view class="info">
              <view class="title-row">
                <view class="city-wrap">
                  <text class="company">{{
                    user.company_name || "司名称名称"
                  }}</text>
                  <text class="city" v-if="user.address_city">{{
                    user.address_city
                  }}</text>
                </view>
                <view v-if="!uid" class="edit-btn" @tap="editProfile"
                  >编辑资料</view
                >
              </view>
            </view>
          </view>
        </view>
        <view class="intro">
          <view class="intro-title-wrap">
            <view class="title-line"></view>
            <text class="intro-title">个人简介</text>
          </view>

          <view class="intro-text">{{ user.company_desc || "暂无介绍" }}</view>
        </view>

        <!-- 地图预览 -->
        <view class="map-wrap" @tap="openLocation">
          <!-- 小程序 map 组件（兼容 uni-app） -->
          <map
            v-if="hasLocation"
            :latitude="Number(user.latitude)"
            :longitude="Number(user.longitude)"
            :scale="14"
            :markers="mapMarkers"
            style="width: 100%; height: 240rpx"
            show-location
          ></map>
          <!-- 无坐标时展示图片 -->
          <image
            v-else
            class="map-placeholder"
            src="/static/image/map-placeholder.png"
            mode="aspectFill"
          >
          </image>
        </view>

        <!-- 详情项 -->
        <view class="list">
          <view class="row item">
            <view class="left">
              <image src="/static/icon/Frame 1171279094@2x.png" class="icon" />
              <view class="texts">
                <text class="label">详细地址</text>
              </view>
            </view>
            <view class="right">
              <text class="value address" user-select="true">{{
              getAddressDetail || "暂无地址"
            }}</text>
              <button class="btn" @tap="navigateToAddress">导航</button>
            </view>
          </view>

          <view class="row item">
            <view class="left">
              <image src="/static/icon/Frame 1171279095@2x.png" class="icon" />
              <view class="texts">
                <text class="label">联系电话</text>
              </view>
            </view>
            <view class="right">
              <text class="value">{{ user.contact_mobile || "暂无电话" }}</text>
              <button class="btn" @tap="callPhone">拨打</button>
            </view>
          </view>

          <view class="row item">
            <view class="left">
              <image src="/static/icon/Frame 1171279096@2x.png" class="icon" />
              <view class="texts">
                <text class="label">微信号</text>
              </view>
            </view>
            <view class="right">
              <text class="value">{{
                user.contact_wechat || "暂无微信号"
              }}</text>
              <button class="btn" @tap="copyText(user.contact_wechat)">
                复制
              </button>
            </view>
          </view>
        </view>

        <!-- 底部安全区留白 -->
        <view :style="{ height: bottomSafe + 'px' }"></view>
      </view>
    </view>
  </u-popup>
</template>

<script>
export default {
  name: "PersonalDetails",
  props: {
    // 是否以弹窗模式展示（父组件传 true）
    usePopup: { type: Boolean, default: false },
    // 弹窗显示控制（仅在 usePopup 为 true 时使用）
    visible: { type: Boolean, default: false },
    uid: { type: [String, Number], default: "" },
  },
  emits: ["update:visible"],
  data() {
    return {
      defaultAvatar: "/static/image/headurl.jpg",
      user: {
        avatar: "",
        name: "",
        city: "成都市",
        intro: "",
        address: "",
        latitude: 23.129163,
        longitude: 113.264435,
        phone: "17882324723",
        wechat: "asdad",
      },
      bottomSafe: 0,
      hasLoadedUser: false,
      isLoadingUser: false,
    };
  },
  computed: {
    getAddressDetail() {
      return (
        this.user.address_province +
        this.user.address_city +
        this.user.address_district +
        this.user.address_detail
      );
    },
    hasLocation() {
      return (
        this.user.latitude !== null &&
        this.user.longitude !== null &&
        this.user.latitude !== "" &&
        this.user.longitude !== ""
      );
    },
    mapMarkers() {
      if (!this.hasLocation) return [];
      // 请确保下面 iconPath 指向项目中存在的图标文件（用于小程序显示 marker）
      return [
        {
          id: 1,
          latitude: Number(this.user.latitude),
          longitude: Number(this.user.longitude),
          iconPath: "/static/icon/marker.png", // <- 确保该文件存在，或替换为你的 marker 图标
          width: 30,
          height: 30,
        },
      ];
    },
  },
  watch: {
    visible(value) {
      if (value) {
        this.loadUser();
      }
    },
    uid() {
      this.hasLoadedUser = false;
      if (this.visible) {
        this.loadUser();
      }
    },
  },
  mounted() {
    if (this.visible) {
      this.loadUser();
    }
    this.setSafeArea();
  },
  methods: {
    handleRefreshData() {
      this.hasLoadedUser = false;
      this.loadUser();
      this.setSafeArea();
    },
    close() {
      // 关闭弹窗（外部通过 .sync 或 update:visible 绑定）
      this.$emit("update:visible", false);
    },
    loadUser() {
      if (this.hasLoadedUser || this.isLoadingUser) return;
      const user = uni.getStorageSync("userInfo") || {};
      const uid = this.uid ? this.uid : user.id;
      if (!uid) return;
      const data = {
        target_user_id: uid,
      };
      this.isLoadingUser = true;
      this.$go("user/home/info", data, "get", {
        show_err: false,
      })
        .then((res) => {
          this.isLoadingUser = false;
          if (!res || res.code !== 0) return;
          // 合并默认数据和实际数据，确保所有字段都有值
          const userInfo = {
            ...res.data,
            getUserInfo: "/static/icon/Frame@2x(14).png",
          };
          this.user = userInfo;
          uni.setStorageSync("enterpriseInfo", userInfo);
          this.hasLoadedUser = true;
        })
        .catch((err) => {
          this.isLoadingUser = false;
          console.error("获取商户信息失败:", err);
        });
    },

    openLocation() {
      if (!this.hasLocation) {
        uni.showToast({ title: "暂无定位信息", icon: "none" });
        return;
      }
      console.log(1111111111);
      uni.getLocation({
        type: "gcj02", //返回可以用于uni.openLocation的经纬度
        success: function (res) {
          const latitude = res.latitude;
          const longitude = res.longitude;
          uni.openLocation({
            latitude: latitude,
            longitude: longitude,
            success: function () {
              console.log("success");
            },
          });
        },
      });
      console.log("打开地图", this.user.latitude, this.user.longitude);
    },

    // 导航到地址
    navigateToAddress() {
      console.log("=== 开始导航 ===");
      console.log("hasLocation:", this.hasLocation);
      console.log("latitude:", this.user.latitude);
      console.log("longitude:", this.user.longitude);
      console.log("address:", this.getAddressDetail);

      // 如果有经纬度，直接使用经纬度导航
      if (this.hasLocation) {
        const lat = Number(this.user.latitude);
        const lng = Number(this.user.longitude);

        console.log("转换后的坐标:", lat, lng);

        // 验证坐标有效性
        if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
          console.error("坐标无效:", lat, lng);
          uni.showToast({ title: "位置坐标无效", icon: "none" });
          return;
        }

        console.log("准备调用 uni.openLocation");
        uni.openLocation({
          latitude: lat,
          longitude: lng,
          name: this.user.company_name || "目的地",
          address: this.getAddressDetail || "",
          scale: 18,
          success: (res) => {
            console.log("打开地图成功:", res);
          },
          fail: (err) => {
            console.error("打开地图失败:", err);
            console.error("错误详情:", JSON.stringify(err));

            // 显示更详细的错误信息
            let errorMsg = "打开地图失败";
            if (err.errMsg) {
              errorMsg += ": " + err.errMsg;
            }

            uni.showModal({
              title: "提示",
              content: errorMsg + "\n\n是否复制地址到剪贴板？",
              success: (modalRes) => {
                if (modalRes.confirm) {
                  this.copyText(this.getAddressDetail);
                }
              },
            });
          },
        });
      } else if (this.getAddressDetail) {
        console.log("没有坐标，提示复制地址");
        // 如果没有经纬度但有地址，提示用户复制地址
        uni.showModal({
          title: "提示",
          content: "暂无位置坐标，是否复制地址到剪贴板？",
          success: (res) => {
            if (res.confirm) {
              this.copyText(this.getAddressDetail);
            }
          },
        });
      } else {
        console.log("没有地址信息");
        uni.showToast({ title: "暂无地址信息", icon: "none" });
      }
    },

    copyText(text) {
      if (!text) {
        uni.showToast({ title: "无内容可复制", icon: "none" });
        return;
      }
      uni.setClipboardData({
        data: String(text),
        success: () => uni.showToast({ title: "已复制", icon: "none" }),
        fail: () => uni.showToast({ title: "复制失败", icon: "none" }),
      });
    },
    callPhone() {
      if (!this.user.contact_mobile) {
        uni.showToast({ title: "暂无电话号码", icon: "none" });
        return;
      }
      uni.makePhoneCall({ phoneNumber: String(this.user.contact_mobile) });
    },
    editProfile() {
      uni.navigateTo({ url: "/pagesOther/setInfo/setInfo" });
      // 如果在弹窗内打开编辑，可关闭弹窗
      if (this.usePopup) this.close();
    },
    setSafeArea() {
      const sys = this.$base.getSystemInfoCompat();
      if (sys.safeArea) {
        this.bottomSafe = sys.safeArea.bottom
          ? sys.windowHeight - sys.safeArea.bottom
          : 32;
      } else {
        this.bottomSafe = 32;
      }
    },
  },
};
</script>

<style scoped lang="scss">
/* 原样保留样式（不重复粘贴过长样式，保持与之前一致） */
.page {
  background: #fff;
  padding: 24rpx;
  box-sizing: border-box;
}

.head-card {
  border-radius: 18rpx;
  padding: 24rpx;
  display: flex;
}

.left {
  display: flex;
  width: 100%;
}

.avatar {
  position: absolute;
  top: -88rpx;
  left: 24rpx;
  width: 176rpx;
  height: 176rpx;
  border-radius: 176rpx;
  margin-right: 20rpx;
  background: #f2f2f2;
}

.info {
  flex: 1;
  padding-left: 195rpx;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  margin-bottom: 12rpx;

  .city-wrap {
    display: flex;
    align-items: center;
    gap: 16rpx;
  }
}

.company {
  max-width: 200rpx;
  font-size: 30rpx;
  color: #222;
  font-weight: 600;
}

.city {
  font-size: 22rpx;
  color: #8a6eff;
  padding: 8rpx 16rpx;
  background: linear-gradient(90deg, #f7f5ff 0%, #e2ceff 100%);
  border-radius: 40rpx 40rpx 40rpx 40rpx;
}

.edit-btn {
  padding: 12rpx 16rpx;
  background: #fff;
  border-radius: 96rpx 96rpx 96rpx 96rpx;
  border: 1rpx solid rgba(51, 51, 51, 0.4);
  font-weight: 400;
  font-size: 24rpx;
  color: #333333;
}

.map-wrap {
  height: 240rpx;
  border-radius: 12rpx;
  overflow: hidden;
  margin-bottom: 18rpx;
  background: #f5f5f5;
  margin-top: 48rpx;
}

.map {
  width: 100%;
  height: 100%;
}

.map-placeholder {
  width: 100%;
  height: 100%;
}

.list {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}

.item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  padding: 18rpx;
  border-radius: 12rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.03);

  .left {
    width: auto;
    display: flex;
    align-items: center;
    gap: 14rpx;
  }
}

.left {
  display: flex;
  align-items: center;
  gap: 14rpx;
}

.icon {
  width: 48rpx;
  height: 48rpx;
}

.texts {
  display: flex;
  flex-direction: column;
}

.label {
  font-weight: 500;
  font-size: 28rpx;
  color: #333333;
}

.value {
  font-size: 22rpx;
  color: #888;
  margin-top: 6rpx;

  &.address {
    width: 316rpx;
  }
}

.right {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.btn {
  width: 80rpx;
  height: 48rpx;
  padding: 0;
  background: #fff;
  border: 1rpx solid #33333366;
  border-radius: 48rpx;
  font-size: 22rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  &::after {
    display: none;
  }
}

.popup-inner {
  min-height: 1180rpx;
  position: relative;
  padding: 12rpx 24rpx 24rpx;
  background: #fff;
  border-radius: 48rpx 48rpx 0rpx 0rpx;
}

/* 简介 */
.intro {
  margin-top: 6rpx;

  .intro-title-wrap {
    display: flex;
    align-items: center;
    gap: 16rpx;
  }

  .title-line {
    width: 8rpx;
    height: 32rpx;
    background: #333333;
    border-radius: 0rpx 4rpx 4rpx 0rpx;
  }

  .intro-title {
    font-weight: bold;
    font-size: 32rpx;
    color: #333333;
  }

  .intro-text {
    font-weight: 400;
    font-size: 28rpx;
    color: #666666;
    margin-top: 10rpx;
  }
}
</style>
