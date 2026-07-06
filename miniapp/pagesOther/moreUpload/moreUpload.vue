<template>
	<view class="container">
		<!-- 大批量上传照片部分 -->
		<view class="main">
			<view class="section">
				<view class="title">
					<view class="title-tag"></view>
					大批量上传照片
				</view>
				<view class="desc">复制链接，粘贴到浏览器中，打开即可上传照片</view>
				<view class="tips">
					<text class="tip-item">· 一次最多上传 200 张照片</text>
					<text class="tip-item">· 支持手机浏览器</text>
					<text class="tip-item">· 支持电脑浏览器</text>
				</view>
				<view class="notice">
					注：微信官方暂不支持大批量上传照片，因此请使用浏览器进行上传
				</view>
			</view>
			<!-- 邀请好友部分 -->
			<view class="section invite-section">
				<view class="title">
					<view class="title-tag"></view>
					邀请好友一起上传
				</view>
				<!-- <view class="subtitle">邀请好友一起上传</view> -->
				<view class="desc">复制链接，把链接发给好友，即可一起上传照片</view>
				<view class="tips">
					<text class="tip-item">· 每个相册都有自己的专属链接</text>
					<text class="tip-item">· 好友仅能在你分享的相册里上传照片</text>
				</view>
			</view>
			<!-- 链接展示区域 -->
			<view class="link-box">
				<text class="link-text">{{ uploadUrl }}</text>
			</view>
			<view class="section invite-section">
				<view class="subtitle">设置批量上传密码</view>
				<view class="line-tab">
					<view class="top-line">
						<view class="label">批量上传密码</view>
						<input type="text" v-model="userInfo.upload_pwd" @tap="focusField('uploadPwd')" @focus="focusField('uploadPwd')" @blur="handleUploadPwdBlur" class="input-right" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('uploadPwd', '点击添加')"
							maxlength="4" />
					</view>
						<view class="des">限4位字母或数字；留空则好友打开链接无需密码。</view>

				</view>
			</view>

			<!-- 复制按钮 -->
			<view class="copy-btn" @click="copyLink">
				复制该相册链接
			</view>
			<view class="copy-tip" @click="copyLinkAll">复制该相册链接和密码</view>
		</view>





	</view>
</template>

<script>
export default {
		data() {
			return {
				shareLink: 'https://pic.jfyuntu.com/assets/page/product-list.html',
				uploadd_code: '',
				user_pwd: '',
				userInfo: {}
		}
	},
	computed: {
		uploadUrl() {
			return `${this.shareLink}?uploadd_code=${this.uploadd_code || ''}`
		},
		currentUploadPwd() {
			return (this.userInfo && this.userInfo.upload_pwd) || this.user_pwd || ''
		}
	},
	onLoad(options) {
		console.log(options)
		this.uploadd_code = options.uploadd_code
		this.user_pwd = options.user_pwd
		this.getInfo()
	},
	methods: {
		handleUploadPwdBlur() {
			this.blurField('uploadPwd')
			this.submitInfo()
		},
		submitInfo() {
			// 验证昵称
				// 验证批量上传密码(如果填写了)
				if (this.userInfo.upload_pwd && !/^[A-Za-z0-9]{4}$/.test(this.userInfo.upload_pwd)) {
					uni.showToast({
						title: '批量上传密码需为4位字母或数字',
						icon: 'none'
					});
					return;
				}
				
				const querys = {
					nickname: this.userInfo.nickname,
					avatar: this.userInfo.avatar,
					wx_ewm: this.ermImg,
					user_desc: this.userInfo.user_desc || '',
					upload_pwd: this.userInfo.upload_pwd || '',
					upload_pwd_expire_time: this.userInfo.upload_pwd ? (Number(this.userInfo.upload_pwd_expire_time || 0)) : 0,
					openid:uni.getStorageSync('openid'),
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				
				this.$go('user/update_info', data, 'post', {
					show_err: true
				}).then(res => {
					this.user_pwd = this.userInfo.upload_pwd || ''
					uni.showToast({
						title: '更新成功',
						icon: 'success'
					});
				}).catch(err => {
					console.error('更新失败:', err);
				});
		},
		
		getInfo() {
			const querys = {
				timestamp: new Date().getTime(),
			}
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			}
			this.$go('user/show_info', data, 'get', {
				show_err: true
			}).then(res => {
				this.userInfo = res.data;
			}).catch(err => {
				console.error('获取用户信息失败:', err);
			});
		},

		copyLink() {
			uni.setClipboardData({
				data: this.uploadUrl,
				success: () => {
					uni.showToast({
						title: '复制成功',
						icon: 'success'
					})
				},
				fail: () => {
					uni.showToast({
						title: '复制失败',
						icon: 'none'
					})
				}
			})
		},
		copyLinkAll() {
			const password = this.currentUploadPwd
			const data = password
				? `${this.uploadUrl}\n上传密码：${password}`
				: this.uploadUrl
			uni.setClipboardData({
				data,
				success: () => {
					uni.showToast({
						title: password ? '已复制链接和密码' : '未设置密码，已复制链接',
						icon: 'none'
					})
				},
				fail: () => {
					uni.showToast({
						title: '复制失败',
						icon: 'none'
					})
				}
			})
		}
	}
}
</script>

<style lang="scss">
.line-tab {
	width: 96%;
	margin: 0 auto;
	border-bottom: 2rpx solid #F0F0F0;
	padding: 20rpx 10rpx;
	box-sizing: border-box;

	.top-line {
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-sizing: border-box;

		.uploadIcon {
			width: 100rpx;
			height: 100rpx;
			border-radius: 8rpx;
		}

		.label {
			font-size: 32rpx;
			color: #333333;
		}

		.headUrl {
			width: 80rpx;
			height: 80rpx;
			border-radius: 50%;
			border: 2rpx solid #F0F0F0;
		}

		.input-right {
			text-align: right;
			font-size: 26rpx;
			color: #808080;
		}
	}

	.des {
		font-size: 28rpx;
		color: #9D9D9D;
		margin-top: 30rpx;
	}
}

.container {
	padding: 40rpx 30rpx;
	background-color: #fff;
	min-height: 100vh;
	box-sizing: border-box;
}

.main {
	background-color: #fff;
	// padding: 50rpx 30rpx;
	box-sizing: border-box;
	border-radius: 20rpx;
}

.section {
	margin-bottom: 30rpx;
}

.title {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 24rpx;
}

.subtitle {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 24rpx;
}

.desc {
	font-size: 28rpx;
	color: #666;
	line-height: 44rpx;
	margin-bottom: 20rpx;
}

.tips {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
	background-color: #F2F2F2;
	padding: 20rpx;
	border-radius: 12rpx;
}

.tip-item {
	font-size: 26rpx;
	color: #666;
	line-height: 40rpx;
}

.notice {
	margin-top: 24rpx;
	font-size: 26rpx;
	color: #999;
	line-height: 40rpx;
}

.invite-section {
	margin-top: 60rpx;
}

.link-box {
	margin: 60rpx 0 40rpx;
	padding: 30rpx 24rpx;
	background-color: #f0f0f0;
	border-radius: 12rpx;
	word-break: break-all;
}

.link-text {
	font-size: 26rpx;
	color: #999;
	line-height: 40rpx;
}

.copy-btn {
	width: 100%;
	height: 88rpx;
	background-color: #FFD000;
	border-radius: 44rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 32rpx;
	color: #333333;
	font-weight: 500;
	margin-bottom: 20rpx;
}

.copy-tip {
		width: 100%;
	height: 88rpx;
	background-color: #F2F2F2;
	border-radius: 44rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 32rpx;
	color: #333333;
	font-weight: 500;
	margin-bottom: 20rpx;
}
.title-tag{
	height: 45rpx;
    width: 6rpx;
    background: #FFD000;
    float: left;
    margin-right: 20rpx;
}
</style>
