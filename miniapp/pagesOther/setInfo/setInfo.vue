<template>
  <view class="page">
    <view class="page-scoll">
      <!-- 公司信息 -->
      <view class="section">
        <view class="section-title">公司信息</view>
        <!-- 公司名称 -->
        <view class="form-item">
          <view class="label">公司名称</view>
          <input
            class="input"
            v-model="companyInfo.name"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('companyName', '请输入公司名称')"
            maxlength="50"
            @tap="focusField('companyName')"
            @focus="focusField('companyName')"
            @blur="blurField('companyName')"
          />
        </view>

        <!-- 上传logo -->
        <view class="form-item white">
          <view class="label">上传logo</view>
          <view class="upload-area" @click="uploadLogo">
            <image
              v-if="companyInfo.logo"
              :src="companyInfo.logo"
              class="logo-image"
              mode="aspectFit"
            />
            <view v-else class="upload-placeholder">
              <image
                src="/static/icon/upload-cloud.png"
                class="upload-icon"
                mode="scaleToFill"
              />
              <text class="upload-text">上传logo</text>
            </view>
          </view>
        </view>

        <!-- 公司简介 -->
        <view class="form-item white">
          <view class="label">公司简介</view>
          <u--textarea
            class="textarea"
            v-model="companyInfo.description"
            placeholder-class="jf-textarea-placeholder"
            :placeholder="placeholderFor('companyDescription', '请输入公司相关主页头部简介')"
            count
            maxlength="100"
            @tap="focusField('companyDescription')"
            @focus="focusField('companyDescription')"
            @blur="blurField('companyDescription')"
          ></u--textarea>
        </view>
      </view>

      <!-- 联系方式 -->
      <view class="section">
        <view class="section-title">联系方式</view>

        <!-- 手机号 -->
        <view class="form-item">
          <view class="label">手机号</view>
          <input
            class="input"
            v-model="companyInfo.phone"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('companyPhone', '请输入手机号')"
            type="number"
            maxlength="11"
            @tap="focusField('companyPhone')"
            @focus="focusField('companyPhone')"
            @blur="blurField('companyPhone')"
          />
        </view>

        <!-- 微信号 -->
        <view class="form-item">
          <view class="label">微信号</view>
          <input
            class="input"
            v-model="companyInfo.wechat"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('companyWechat', '请输入微信号')"
            maxlength="20"
            @tap="focusField('companyWechat')"
            @focus="focusField('companyWechat')"
            @blur="blurField('companyWechat')"
          />
        </view>

        <!-- 行业选择 -->
        <view class="form-item clickable" @click="showIndustryPicker">
          <view class="label">行业</view>
          <view class="input-area">
            <text
              class="input-text"
              :class="{ placeholder: !selectedIndustryText }"
            >
              {{ selectedIndustryText || "请选择行业" }}
            </text>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="scaleToFill"
            />
          </view>
        </view>
      </view>

      <!-- 地址 -->
      <view class="section">
        <view class="section-title">地址</view>

        <!-- 选择地址 -->
        <view class="form-item clickable" @click="showAddressPicker">
          <view class="label">选择地址</view>
          <view class="input-area">
            <text
              class="input-text"
              :class="{ placeholder: !selectedAddressText }"
            >
              {{ selectedAddressText || "请选择地址" }}
            </text>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="scaleToFill"
            />
          </view>
        </view>

        <!-- 设置导航地址 -->
        <view class="form-item clickable" @click="chooseMapLocation">
          <view class="label">导航地址</view>
          <view class="input-area">
            <text
              class="input-text"
              :class="{ placeholder: !selectedLocationText }"
            >
              {{ selectedLocationText || "请选择地图位置" }}
            </text>
            <image
              src="/static/icon/Chevron Right@2x(1).png"
              class="arrow-icon"
              mode="scaleToFill"
            />
          </view>
        </view>
        <!-- 详细地址 -->
        <view class="form-item">
          <view class="label">详细地址</view>
          <input
            class="input"
            v-model="companyInfo.detailAddress"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('detailAddress', '请填写详细地址')"
            maxlength="100"
            @tap="focusField('detailAddress')"
            @focus="focusField('detailAddress')"
            @blur="blurField('detailAddress')"
          />
        </view>
      </view>

      <!-- 是否在主页显示 -->
      <view class="section">
        <view class="form-item switch-item">
          <view class="label">是否在主页显示</view>
          <view class="switch-wrap">
            <switch
              style="width: 120rpx"
              :checked="companyInfo.showOnHomepage"
              @change="onSwitchChange"
              color="#333333"
            />
            <text class="switch-status">{{
              companyInfo.showOnHomepage ? "是" : "否"
            }}</text>
          </view>
        </view>
      </view>

      <!-- 完成按钮 -->
      <view class="submit-btn" @click="submitInfo"> 完成 </view>
    </view>

    <!-- 地址选择器 -->
    <address-picker
      v-if="areaList.length"
      :show="addressShow"
      closeOnClickOverlay
      @confirm="confirmAddress"
      @cancel="addressShow = false"
      @close="addressShow = false"
      :address-data="addressData"
      :indexs="indexs"
      :areaId="areaId"
      :address-list="areaList"
      :type="type"
    ></address-picker>

    <!-- 行业选择器 -->
    <u-action-sheet
      :actions="industryActions"
      :show="industryPickerShow"
      @select="selectIndustry"
      @close="industryPickerShow = false"
      cancelText="取消"
      :closeOnClickOverlay="true"
    ></u-action-sheet>
  </view>
</template>

<script>
import config from "@/common/config";
import AddressPicker from "@/components/address-picker/address-picker.vue";
export default {
  components: {
    AddressPicker,
  },
  data() {
    return {
      companyInfo: {
        name: "", // 公司名称
        logo: "", // 公司logo
        description: "", // 公司简介
        phone: "", // 手机号
        wechat: "", // 微信号
        province: "", // 省份
        city: "", // 城市
        district: "", // 区县
        detailAddress: "", // 详细地址
        latitude: "", // 纬度
        longitude: "", // 经度
        showOnHomepage: true, // 是否在主页显示
        gender: "男",
        industry_info: "", // 行业信息
      },
      // 行业选项
      industryOptions: [
        { label: "微供", value: 1 },
        { label: "网供", value: 2 },
        { label: "摄影", value: 3 },
      ],
      areaList: [],
      address: "",
      addressShow: false,
      indexs: [0, 0],
      areaId: [110000, 110101],
      addressData: ["北京市", "东城区"],
      type: 3, //1-省，2-省市，3-省市区
      selectedAddressText: "", // 显示的地址文本
      selectedLocationText: "", // 显示的地图位置文本
      fromPage: "",
      industryPickerShow: false, // 行业选择器显示状态
      selectedIndustryText: "", // 显示的行业文本
    };
  },
  computed: {
    // 转换行业选项为 u-action-sheet 需要的格式
    industryActions() {
      return this.industryOptions.map((item) => ({
        name: item.label,
        value: item.value,
      }));
    },
  },
  onLoad(options) {
    this.fromPage = options.fromPage;
    this.getAddressData();
    this.getCompanyInfo();
  },

  methods: {
    // 获取地址数据
    getAddressData() {
      this.$go("region/tree", {}, "get", {
        show_err: true,
      })
        .then((res) => {
          if (res.data && Array.isArray(res.data)) {
            this.areaList = res.data;
          }
        })
        .catch((err) => {
          console.error("获取地址数据失败:", err);
          // 如果接口失败，使用模拟数据
        });
    },
    confirmAddress(val) {
      console.log(val);

      // val.value 数组: [0]=省份, [1]=城市, [2]=区县
      const [province, city, district] = val.value;

      // 只设置非 undefined 的值
      if (province !== undefined) {
        this.companyInfo.province = province;
      }
      if (city !== undefined) {
        this.companyInfo.city = city;
      }
      if (district !== undefined) {
        this.companyInfo.district = district;
      }

      // 显示文本：过滤掉 undefined 后拼接
      this.selectedAddressText = val.value
        .filter((item) => item !== undefined)
        .join("");
      this.addressShow = false;
    },

    // 显示地址选择器
    showAddressPicker() {
      this.addressShow = true;
    },

    // 选择地图位置
    chooseMapLocation() {
      console.log("打开地图选择位置");

      // 构建搜索关键词：优先使用已选择的省市区
      let keyword = "";
      if (this.companyInfo.province) {
        keyword += this.companyInfo.province;
      }
      if (this.companyInfo.city) {
        keyword += this.companyInfo.city;
      }
      if (this.companyInfo.district) {
        keyword += this.companyInfo.district;
      }

      // 如果没有选择省市区，使用详细地址
      if (!keyword && this.companyInfo.detailAddress) {
        keyword = this.companyInfo.detailAddress;
      }

      console.log("搜索关键词:", keyword);

      // 配置参数
      const chooseLocationParams = {
        success: (res) => {
          console.log("选择位置成功:", res);

          // 保存经纬度
          this.companyInfo.latitude = res.latitude;
          this.companyInfo.longitude = res.longitude;

          // 显示选择的位置信息
          this.selectedLocationText = res.name || res.address || "已选择位置";

          // 如果返回了详细地址，可以更新详细地址字段
          if (res.address) {
            this.companyInfo.detailAddress = res.address;
          }

          uni.showToast({
            title: "位置选择成功",
            icon: "success",
          });
        },
        fail: (err) => {
          console.error("选择位置失败:", err);

          if (err.errMsg && err.errMsg.includes("cancel")) {
            // 用户取消选择
            console.log("用户取消选择位置");
          } else if (err.errMsg && err.errMsg.includes("auth deny")) {
            // 用户拒绝授权
            uni.showModal({
              title: "提示",
              content: "需要位置权限才能选择地图位置，请在设置中开启",
              confirmText: "去设置",
              success: (modalRes) => {
                if (modalRes.confirm) {
                  uni.openSetting();
                }
              },
            });
          } else {
            uni.showToast({
              title: "选择位置失败",
              icon: "none",
            });
          }
        },
      };

      // 如果有关键词，添加到参数中（用于定位地图中心点）
      if (keyword) {
        // 注意：chooseLocation 不直接支持 keyword 参数
        // 但我们可以先尝试获取关键词的坐标，然后传入 latitude 和 longitude
        this.geocodeAddress(keyword, (location) => {
          if (location) {
            chooseLocationParams.latitude = location.lat;
            chooseLocationParams.longitude = location.lng;
          }
          uni.chooseLocation(chooseLocationParams);
        });
      } else {
        // 没有关键词，直接打开地图选择
        uni.chooseLocation(chooseLocationParams);
      }
    },

    // 地址解析：将地址转换为经纬度（可选功能）
    geocodeAddress(address, callback) {
      console.log("尝试解析地址:", address);

      // 这里可以调用腾讯地图或其他地图服务的地理编码API
      // 由于需要API key，这里提供一个简化版本
      // 如果不需要自动定位到省市区，可以直接调用 callback(null)

      // 简化处理：直接返回 null，让地图显示默认位置
      callback(null);

      // 如果需要使用腾讯地图API，可以取消下面的注释并配置key
      /*
      uni.request({
        url: 'https://apis.map.qq.com/ws/geocoder/v1/',
        data: {
          address: address,
          key: 'YOUR_TENCENT_MAP_KEY', // 需要替换为实际的腾讯地图key
          output: 'json'
        },
        success: (res) => {
          if (res.data.status === 0 && res.data.result && res.data.result.location) {
            callback(res.data.result.location);
          } else {
            callback(null);
          }
        },
        fail: () => {
          callback(null);
        }
      });
      */
    },

    // 获取公司信息
    getCompanyInfo() {
      const user = uni.getStorageSync("userInfo");

      const querys = {
        target_user_id: user.id,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      this.$go("user/home/info", data, "get", {
        show_err: true,
      })
        .then((res) => {
          if (res.data) {
            this.companyInfo = {
              name: res.data.company_name,
              logo: res.data.company_logo,
              description: res.data.company_desc,
              phone: res.data.contact_mobile,
              wechat: res.data.contact_wechat,
              province: res.data.address_province,
              city: res.data.address_city,
              district: res.data.address_district,
              detailAddress: res.data.address_detail,
              latitude: res.data.latitude || "",
              longitude: res.data.longitude || "",
              showOnHomepage: res.data.is_show_home === 1 ? true : false,
              industry_info: res.data.industry_info || "",
            };

            // 设置行业显示文本
            if (res.data.industry_info) {
              const industry = this.industryOptions.find(
                (item) => item.value === res.data.industry_info,
              );
              if (industry) {
                this.selectedIndustryText = industry.label;
              }
            }

            // 如果有地址信息，更新显示文本
            if (
              res.data.address_province ||
              res.data.address_city ||
              res.data.address_district
            ) {
              this.selectedAddressText =
                res.data.address_province +
                res.data.address_city +
                res.data.address_district;
              if (res.data.province) {
                this.addressData.push(res.data.address_province);
              }
              if (res.data.city) {
                this.addressData.push(res.data.address_city);
              }
              if (res.data.district) {
                this.addressData.push(res.data.address_district);
              }
            }

            // 如果有经纬度信息，显示已选择位置
            if (res.data.latitude && res.data.longitude) {
              this.selectedLocationText = "已设置导航位置";
            }
          }
        })
        .catch((err) => {
          console.error("获取公司信息失败:", err);
        });
    },

    // 上传logo
    uploadLogo() {
      uni.chooseImage({
        count: 1,
        sizeType: ["compressed"],
        sourceType: ["album", "camera"],
        success: (res) => {
          const tempFilePath = res.tempFilePaths[0];
          this.uploadFile(tempFilePath, "logo");
        },
        fail: (err) => {
          console.error("选择图片失败:", err);
          uni.showToast({
            title: "选择图片失败",
            icon: "none",
          });
        },
      });
    },

    // 上传文件到服务器
    uploadFile(filePath, type) {
      uni.showLoading({
        title: "上传中...",
        mask: true,
      });

      let querys = {
        timestamp: new Date().getTime(),
      };
      let params = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      uni.uploadFile({
        url: config.domain + "/api/common/upload",
        filePath: filePath,
        name: "file",
        header: {
          "content-type": "multipart/form-data",
          "authorization-token": `Bearer ${uni.getStorageSync("token")}`,
        },
        formData: params,
        success: (uploadRes) => {
          uni.hideLoading();
          try {
            let res = JSON.parse(uploadRes.data);
            if (res.code === 0 || res.code === 200) {
              if (type === "logo") {
                this.companyInfo.logo = res.data.url;
              }
              uni.showToast({
                title: "上传成功",
                icon: "success",
              });
            } else {
              uni.showToast({
                title: res.msg || "上传失败",
                icon: "none",
              });
            }
          } catch (error) {
            console.error("解析上传结果失败:", error);
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
          }
        },
        fail: (err) => {
          uni.hideLoading();
          console.error("上传失败:", err);
          uni.showToast({
            title: "上传失败,请重试",
            icon: "none",
          });
        },
      });
    },

    // 开关切换
    onSwitchChange(e) {
      this.companyInfo.showOnHomepage = e.detail.value;
    },

    // 显示行业选择器
    showIndustryPicker() {
      this.industryPickerShow = true;
    },

    // 选择行业
    selectIndustry(item) {
      this.companyInfo.industry_info = item.value;
      this.selectedIndustryText = item.name;
      this.industryPickerShow = false;
    },

    // 提交信息
    submitInfo() {
      const openid = uni.getStorageSync("openid");
      if (!openid) {
        uni.showToast({
          title: "用户未登录",
          icon: "none",
        });
        return;
      }
      // 验证必填项
      if (!this.companyInfo.name.trim()) {
        uni.showToast({
          title: "请输入公司名称",
          icon: "none",
        });
        return;
      }

      // 验证手机号格式
      if (
        this.companyInfo.phone &&
        !/^1[3-9]\d{9}$/.test(this.companyInfo.phone)
      ) {
        uni.showToast({
          title: "请输入正确的手机号",
          icon: "none",
        });
        return;
      }
      const formData = {
        company_name: this.companyInfo.name,
        company_logo: this.companyInfo.logo,
        company_desc: this.companyInfo.description,
        contact_mobile: this.companyInfo.phone,
        contact_wechat: this.companyInfo.wechat,
        address_province: this.companyInfo.province,
        address_city: this.companyInfo.city,
        address_district: this.companyInfo.district,
        address_detail: this.companyInfo.detailAddress,
        latitude: this.companyInfo.latitude,
        longitude: this.companyInfo.longitude,
        is_show_home: this.companyInfo.showOnHomepage ? 1 : 0,
        industry_info: this.companyInfo.industry_info,
      };
      const querys = {
        ...formData,
        openid: openid,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };

      uni.showLoading({
        title: "保存中...",
        mask: true,
      });

      this.$go("user/update_info", data, "post", {
        show_err: true,
      })
        .then((res) => {
          uni.hideLoading();
          uni.showToast({
            title: "保存成功",
            icon: "success",
          });
          setTimeout(() => {
            if (this.fromPage === "index") {
              uni.$emit("refreshIndexData");
            }
            uni.navigateBack();
          }, 1500);
        })
        .catch((err) => {
          uni.hideLoading();
          console.error("保存失败:", err);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.page {
  background-color: #fff;
  padding: 20rpx 0;
  box-sizing: border-box;
}

.section {
  background-color: #ffffff;
  border-radius: 16rpx;
  overflow: hidden;
}

.section-title {
  font-size: 32rpx;
  color: #999999;
  padding: 32rpx 32rpx 32rpx;
  font-weight: 400;
}

.form-item {
  display: flex;
  align-items: center;
  padding: 0 32rpx;
  margin-bottom: 24rpx;
  &.white {
    background: #fff;
    align-items: flex-start;
  }

  &:last-child {
    border-bottom: none;
  }

  &.clickable {
    cursor: pointer;
  }

  &.switch-item {
    justify-content: flex-start;
    gap: 16rpx;

    .label {
      width: auto;
      font-size: 32rpx;
      color: #333333;
      flex-shrink: 0;
    }
    .group-gender {
      display: flex;
      align-items: center;
      gap: 24rpx;
    }
    .switch-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      .switch-status {
        font-size: 32rpx;
        color: #333333;
        margin-left: 16rpx;
      }
    }
  }
  ::v-deep .u-textarea {
    background: #f5f5f5 !important;
    border-radius: 16rpx !important;
    padding: 20rpx 24rpx !important;
    box-sizing: border-box !important;
  }
  ::v-deep .u-textarea__count {
    background: #f5f5f5 !important;
    border-radius: 0 0 16rpx 16rpx !important;
    padding-right: 8rpx !important;
  }
}

.label {
  font-size: 32rpx;
  color: #333333;
  width: 140rpx;
  flex-shrink: 0;
}

.input {
  flex: 1;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 32rpx;
  color: #333333;
  background: #f5f5f5;
  padding: 0 24rpx;
  border-radius: 16rpx;
  box-sizing: border-box;
  overflow: hidden;
  white-space: nowrap;

  &::placeholder {
    color: #cccccc;
  }
}

.input-area {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f5f5f5;
  min-height: 88rpx;
  padding: 0 20rpx 0 24rpx;
  border-radius: 16rpx;
  box-sizing: border-box;
}

.input-text {
  flex: 1;
  font-size: 32rpx;
  color: #333333;
  line-height: 44rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  &.placeholder {
    color: #cccccc;
  }
}

.arrow-icon {
  width: 48rpx;
  height: 48rpx;
  margin-left: 16rpx;
}

.upload-area {
  width: 172rpx;
  height: 172rpx;
  border-radius: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  background-color: #f2f2f2;
}

.logo-image {
  width: 100%;
  height: 100%;
  border-radius: 14rpx;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.upload-icon {
  width: 48rpx;
  height: 48rpx;
  opacity: 0.6;
  margin-bottom: 12rpx;
}

.upload-text {
  font-size: 24rpx;
  color: #000;
}

.textarea {
  flex: 1;
  font-size: 32rpx;
  color: #333333;
  min-height: 120rpx;
  background: #f5f5f5;
  padding: 24rpx;
  border-radius: 16rpx;
  box-sizing: border-box;

  &::placeholder {
    color: #cccccc;
  }
}

.submit-btn {
  width: 100%;
  height: 96rpx;
  background-color: #ffd700;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32rpx;
  color: #333333;
  font-weight: 500;
  margin-top: 60rpx;
  margin-bottom: calc(constant(safe-area-inset-bottom));
  margin-bottom: calc(env(safe-area-inset-bottom));

  &:active {
    opacity: 0.8;
  }
}
</style>
