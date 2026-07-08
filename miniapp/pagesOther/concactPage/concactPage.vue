<template>
	<view class="contact-page">
		<!-- 商家信息区域 -->
		<view class="merchant-info">
			<view class="avatar-wrapper">
				<image class="avatar" :src="userInfo.avatar || '/static/image/headurl.jpg'" mode="aspectFill"></image>
			</view>
			<text class="merchant-name">{{userInfo.nickname}}</text>
		</view>

		<!-- 二维码图片区域 -->
		<view class="qrcode-section">
			<image class="qrcode-image" :src="userInfo.wx_ewm || '/static/image/pic.png'" show-menu-by-longpress mode="aspectFit"></image>
		</view>

		<!-- 提示文字 -->
		<view class="tip-text">
			<text>长按识别二维码，添加商家微信</text>
		</view>

		<!-- 联系说明 -->
		<view class="contact-desc">
			<text>联系商家，咨询/下单</text>
		</view>

		
	</view>
</template>

<script>
export default {
	data() {
		return {
			userInfo:{}
		}
	},
	onLoad() {
		this.getUserInfo()
	},
	methods: {
		buildRequestData(querys) {
			return {
				...querys,
				sign: this.$base.getASCII(querys)
			}
		},
		getUserInfo(){
			const querys = {
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)
			
			this.$go('user/show_info', data, 'get', { show_err: true })
				.then(res => {
					this.userInfo = res.data
				})
		},
		// 修改信息按钮点击事件
		handleEditInfo() {
			uni.showToast({
				title: '跳转到信息编辑页面',
				icon: 'none'
			});
			// 可以在这里跳转到信息编辑页面
			// uni.navigateTo({
			//   url: '/pages/editInfo/editInfo'
			// });
		}
	}
}
</script>

<style lang="scss">
.contact-page {
	min-height: 100vh;
	background-color: #f5f5f5;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0 30rpx;
	box-sizing: border-box;

	// 商家信息区域
	.merchant-info {
		display: flex;
		align-items: center;
		margin-top: 60rpx;
		margin-bottom: 40rpx;

		.avatar-wrapper {
			width: 100rpx;
			height: 100rpx;
			border-radius: 50%;
			overflow: hidden;
			background-color: #fff;
			box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);

			.avatar {
				width: 100%;
				height: 100%;
			}
		}

		.merchant-name {
			font-size: 36rpx;
			font-weight: 500;
			color: #333;
			margin-left: 20rpx;
		}
	}

	// 二维码图片区域
	.qrcode-section {
		width: 100%;
		height: 700rpx;
		border-radius: 16rpx;
		overflow: hidden;
		box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
		margin-bottom: 40rpx;

		.qrcode-image {
			width: 100%;
			height: 100%;
		}
	}

	// 提示文字
	.tip-text {
		font-size: 28rpx;
		color: #666;
		text-align: center;
		margin-bottom: 80rpx;

		text {
			line-height: 1.6;
		}
	}

	// 联系说明
	.contact-desc {
		font-size: 28rpx;
		color: #999;
		text-align: center;
		margin-bottom: 60rpx;

		text {
			line-height: 1.6;
		}
	}

	// 底部按钮
	.bottom-btn {
		width: 100%;
		padding: 0 30rpx;
		position: fixed;
		bottom: 40rpx;
		left: 0;
		box-sizing: border-box;

		.edit-btn {
			width: 100%;
			height: 90rpx;
			background: linear-gradient(135deg, #07c160 0%, #06ae56 100%);
			border-radius: 45rpx;
			border: none;
			color: #fff;
			font-size: 32rpx;
			font-weight: 500;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 8rpx 20rpx rgba(7, 193, 96, 0.3);

			&::after {
				border: none;
			}

			&:active {
				opacity: 0.9;
			}
		}
	}
}
</style>
