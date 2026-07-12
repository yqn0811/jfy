<template>
	<view v-if="show" class="auth-popup-mask" >
		<view class="auth-popup-container" @click.stop>
			<view class="auth-header">
				登录后体验更多功能
			</view>
			<view class="auth-content">
				<image class="default-avatar" :src="avatarUrl" mode="aspectFill"></image>
				<button plain class="getInfoBtn" open-type="chooseAvatar" @chooseavatar="chooseAvatar">点击获取登录头像</button>
				<button v-if="phone_code == ''" plain class="getPhoneBtn" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber">点击授权手机号</button>
				<view class="getPhoneBtn" v-else>手机号已授权</view>
				<input class="nickname-input" type="nickname" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('authNickname', '点击获取昵称')" v-model="nickname" @tap="focusField('authNickname')" @focus="focusField('authNickname')" @blur="blurField('authNickname')" />
				<view class="submit-button" @click="submit">提交</view>
			</view>
		</view>
	</view>
</template>

<script>
	import Upload from '@/common/request/upload.js';
	import config from '@/common/config.js';
	export default {
		props: {
			show: {
				type: Boolean,
				default: false,
			}
		},
		data() {
			return {
				avatarUrl: "/static/image/headurl.jpg", // 默认头像
				userImg:'',
				nickname: "",
				phone_code:''
			};
		},
		methods: {
			closePopup() {
				this.$emit("update:show", false);
			},
				chooseAvatar(e) {
					const uploader = new Upload();
					uploader.upload(e.detail.avatarUrl).then((res) => {
						const url = res && res.data && (res.data.full_url || res.data.url);
						if (!url) {
							uni.showToast({
								title: '上传响应异常,请重试',
								icon: 'none'
							});
							return;
						}
						this.avatarUrl = url;
						this.userImg = url;
					}).catch(() => {});
				},
				getPhoneNumber(e) {
					if (e.detail.errMsg === "getPhoneNumber:ok") {
						this.phone_code = e.detail.code
					} else {
					uni.showToast({
						title: "获取手机号失败",
						icon: "none"
					});
				}
			},
			submit() {
				if (!this.nickname) {
					uni.showToast({
						title: "请输入昵称",
						icon: "none"
					});
					return;
				}
				if (!this.userImg) {
					uni.showToast({
						title: "请上传头像",
						icon: "none"
					});
					return;
				}
				if (!this.phone_code) {
					uni.showToast({
						title: "请授权手机号",
						icon: "none"
					});
					return;
				}
				this.$go('login_member/save',{
					phone_code: this.phone_code,
					nickname: this.nickname,
					logo:this.avatarUrl,
					},'post',{
						show_err: false
					}).then(res => {
						this.closePopup();
						let userInfo = {
							nickname:res.data.nickname,
						phone:res.data.phone,
						openid:res.data.openid,
							logo:res.data.logo,
						}
						uni.setStorageSync('userInfo', userInfo)
						this.$emit('handleAuthSubmit')
					})
				}
		}
	};
</script>

<style scoped>
	.auth-popup-mask {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.5);
		display: flex;
		justify-content: center;
		align-items: flex-end;
		z-index: 999;
	}

	.auth-popup-container {
		width: 100%;
		height: 700rpx;
		background-color: #ffffff;
		border-radius: 12px 12px 0 0;
		overflow: hidden;
		display: flex;
		flex-direction: column;
		animation: slideUp 0.3s ease-out;
		padding: 0 40rpx;
		box-sizing: border-box;
	}

	.auth-header {
		height: 100rpx;
		line-height: 100rpx;
		font-size: 30rpx;
		font-weight: bold;
		text-align: center;
	}

	.auth-content {
		text-align: center;
	}

	.default-avatar {
		width: 100rpx;
		height: 100rpx;
		border-radius: 50%;
		display: block;
		margin: 20rpx auto;
	}

	.getInfoBtn,
	.getPhoneBtn,
	.nickname-input,
	.submit-button {
		border: none;
		background-color: #f5f5f5;
		margin-top: 20rpx;
		font-size: 30rpx;
		width: 500rpx;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		border-radius: 10rpx;
		display: block;
		margin-left: auto;
		margin-right: auto;
	}

	.getPhoneBtn {
		color: #fe6146;
	}

	.submit-button {
		background-color: #fe6146;
		color: #ffffff;
	}

	@keyframes slideUp {
		from {
			transform: translateY(100%);
		}

		to {
			transform: translateY(0);
		}
	}
</style>
