<template>
	<view class="vip-page">
		<!-- 头部导航栏 -->
		<view class="header" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left">
						<view class="back-button" @click="goBack">
							<image class="backIcon" src="../../static/icon/back2.png" mode=""></image>
						</view>
						<view class="info-box">
							<view class="title">开通会员</view>
						</view>
					</view>
				</view>
			</view>
			<view class="vip-icon">
				<image class="vipIcon" src="../../static/icon/slices/生成会员图标 1@2x.png" mode=""></image>
			</view>
		</view>

		<!-- 内容区域 -->
		<view class="content" :style="{ paddingTop: totalHeight + 'px' }">
			<!-- 会员等级切换 -->
			<view class="vip-level-container">
				<scroll-view scroll-x="true" class="vip-level-scroll">
					<view class="vip-level-wrapper">
						<view class="vip-level-item" v-for="(level, index) in vipList" :key="index"
							@click="selectVipLevel(level,index)">
							<view class="level-text" :class="{ 'active': currentLevel === index }">
								{{ level.grade_name }}
							</view>
						</view>
					</view>
				</scroll-view>
			</view>
			
			<view class="vip-content">
				<!-- 价格区域 -->
				<view class="price-container">
					<view class="price-item highlight single">
						<view class="discount-tag" v-if="levelInfo.show_annual_del_str">{{levelInfo.show_annual_del_str}}</view>
						<view class="duration">资源包</view>
						<view class="current-price">¥{{levelInfo.annual_fee}}</view>
						<view class="original-price">¥{{levelInfo.market_annual_fee}}</view>
					</view>
				</view>
					<view class="benefits-section">
						<view class="section-header">
							<text class="section-title">资源包权益</text>
						</view>
						<view class="benefits-content">
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278987.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">资源库{{levelInfo.cloud_size_str}}</view>
									<view class="level-des">AI 生图资源库扩容</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278989.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">网页/小程序互通</view>
									<view class="level-des">同账号素材自动同步</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278989.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">按文件大小计量</view>
									<view class="level-des">适合商品素材沉淀</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278994.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">上传视频</view>
									<view class="level-des">会员相册可上传视频</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278995.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">无广告</view>
									<view class="level-des">你的相册无广告</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278996.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">原图</view>
									<view class="level-des">支持查看和下载原图</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278997.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">文字备注</view>
									<view class="level-des">可以给图片添加备注</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278998.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">搜索</view>
									<view class="level-des">根据备注搜索图片</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171278999.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">回收站图片到期时间</view>
									<view class="level-des">30天</view>
								</view>
							</view>
							<view class="vip-tab" >
								<image src="/static/icon/slices/Frame 1171279000.png" class="vipIcon" mode=""></image>
								<view class="level-info">
									<view class="level-label">收集照片</view>
									<view class="level-des">分享到群收集制定照片</view>
								</view>
							</view>
						</view>
						<!-- <view class="vip-tip">
							更多会员权限，敬请期待
						</view> -->
					</view>
				<view class="scorll">
					<view class="review-section">
						<view class="section-header">
							<text class="section-title">会员口碑 ({{evaluateList.length}})</text>
							<view class="view-all" @click="toBuzz">查看全部 <image src="/static/icon/right.png" class="rightIcon" mode=""></image> </view>
						</view>
					
						<view class="category-tags">
							<text class="category-tag" v-for="(item,index) in cateList" :key="index">{{item.name}}({{item.count}})</text>
						</view>
					
						<view class="review-list">
							<view class="review-item" v-for="(item,index) in evaluateList" :key="index">
								<view class="reviewer-info">
									<image :src="item.user_info_data.avatar" mode="aspectFill" class="avatar"></image>
									<view class="info-text">
										<text class="reviewer-name">{{item.user_info_data.nickname}}</text>
										<text class="usage-time">已使用{{item.user_info_data.join_days}}天</text>
										<text class="album-type">{{item.purpose}}</text>
										<view class="review-content">{{item.evaluate_content}}</view>
									</view>
								</view>
							</view>
						</view>
					</view>
					
				
				</view>
			</view>
			

			<view class="bottom-upgrade" @click="toBuy">
				<view class="upgrade-text">购买{{levelInfo.grade_name}} {{payFee}}元</view>
			</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState
	} from 'vuex';

	export default {
		data() {
			return {
				statusBarHeight: 0,
				navigationBarHeight: 44,
				totalHeight: 0,
				currentLevel: 0,
				arrowPosition: 0,
				itemWidth: 140 ,
				vipList:[],
				cateList:[],
				activeName:'',
				evaluateList:[],
				page:1,
				levelInfo:{},
				activeVip:1,
				payFee:0,
				baseInfo:{},
				userInfo:{}
			};
		},
		onLoad() {
			const systemInfo = this.$base.getSystemInfoCompat();
			this.statusBarHeight = systemInfo.statusBarHeight;
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
			
			this.baseInfo = uni.getStorageSync('baseInfo')
			this.userInfo = uni.getStorageSync('userInfo') || {}

			this.$nextTick(() => {
				this.getVipList()
				this.getcateList()
				this.getUserInfo()
			});
		},
		methods: {
			toBuy(){
				if (!this.levelInfo || !this.levelInfo.grade_level) {
					uni.showToast({
						title: '请选择资源包',
						icon: 'none'
					});
					return;
				}
				const querys = {
					timestamp: new Date().getTime(),
					grade:this.levelInfo.grade_level,
					buy_time:1,
					pay_price:this.payFee,
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				
				// 显示加载提示
				uni.showLoading({
					title: '正在创建订单...',
					mask: true
				});
				
				this.$go('grade/order/create', data, 'post', {
					show_err: true
				}).then(res => {
					uni.hideLoading();
					
					const payData = res.data.data
					if (!payData || !payData.pay_info) {
						uni.showToast({
							title: '支付参数错误',
							icon: 'none'
						});
						return;
					}
					
					// 拉起微信支付
					uni.requestPayment({
						provider: 'wxpay',
						timeStamp: payData.pay_info.timeStamp,
						nonceStr: payData.pay_info.nonceStr,
						package: payData.pay_info.package,
						signType: payData.pay_info.signType || 'MD5',
						paySign: payData.pay_info.paySign,
						success: (payRes) => {
							uni.showToast({
								title: '支付成功',
								icon: 'success',
								duration: 2000
							});
							
							// 支付成功后的处理
							setTimeout(() => {
								uni.navigateBack();
							}, 2000);
						},
						fail: (err) => {
							console.log('支付失败', err);
							
							if (err.errMsg === 'requestPayment:fail cancel') {
								// 用户取消支付
								uni.showToast({
									title: '已取消支付',
									icon: 'none',
									duration: 2000
								});
							} else {
								// 支付失败
								uni.showToast({
									title: '支付失败，请重试',
									icon: 'none',
									duration: 2000
								});
							}
						}
					});
				}).catch(err => {
					uni.hideLoading();
					console.error('创建订单失败', err);
					uni.showToast({
						title: '订单创建失败',
						icon: 'none'
					});
				});
			},
			getUserInfo() {
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
					uni.setStorageSync('userInfo',res.data)
				}).catch(err => {
					console.error('获取用户信息失败:', err);
				});
			},
			
			selectVip(e,fee){
				this.activeVip = e
				this.payFee = fee
			},
			
			getList(){
				const querys = {
					timestamp: new Date().getTime(),
					name:this.activeName,
					page:this.page
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('submit/evaluate', data, 'get', {
					show_err: true
				}).then(res => {
					this.evaluateList = this.evaluateList.concat(res.data.data)
				})
			},
			
			getcateList(){
				const querys = {
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('cate/evaluate', data, 'get', {
					show_err: true
				}).then(res => {
					this.cateList = res.data
					this.getList()
				})
			},
			
			toBuzz(){
				uni.navigateTo({
					url:'/pagesOther/vipBuzz/vipBuzz'
				})
			},
			
			getVipList(){
				const querys = {
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('grade/lists', data, 'get', {
					show_err: true
				}).then(res => {
					this.vipList = res.data
					this.levelInfo = res.data[0]
					this.payFee = res.data[0].annual_fee
				})
			},
			
			goBack() {
				uni.navigateBack();
			},

			selectVipLevel(level,index) {
				this.levelInfo = level
				this.currentLevel = index;
				this.payFee = level.annual_fee
			},
		}
		};
</script>

<style lang="scss" scoped>
	/* 页面容器 */
	.vip-page {
		min-height: 100vh;
		background-color: #333333;
		overflow: hidden;
		background-repeat: no-repeat;
		background-position: center center;
	}

	/* 头部导航栏 */
	.header {
		width: 100%;
		background-size: 100%;
		box-sizing: border-box;
		position: fixed;
		top: 0;
		left: 0;
		background-color: #333333;
		z-index: 99;

		.custom-nav-bar {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			color: #fff;
			z-index: 1;
			// background-color: #382E21;
			position: absolute;
				top: 0;
				left: 0;

			.nav-bar-content {
				padding: 0 10px;
				// width: 520rpx;
				width: 100%;
				display: flex;
				align-items: center;
				justify-content: space-between;
				position: relative;

				.edit-btn {
					color: #57BE6B;
					font-size: 24rpx;
					border: 2rpx solid #29AD68;
					padding: 4rpx 6rpx;
					border-radius: 6rpx;
				}

				.left {
					display: flex;
					align-items: center;
					width: 100%;
				}

				.back-button {
					margin-right: 20rpx;

					.backIcon {
						width: 30rpx;
						height: 30rpx;
					}
				}

				.info-box {
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					.title {
						font-weight: bold;
						font-size: 28rpx;
						color: #ffffff;
					}
				}


			}
		}

	}


	.content {
		overflow: hidden;
	}

	.vip-level-container {
		background-color: #333;
		padding: 30rpx 0 10rpx;
		position: relative;
		margin-top: 50rpx;

		.vip-level-scroll {
			width: 100%;
			white-space: nowrap;
			padding: 0 30rpx;

			.vip-level-wrapper {
				display: inline-flex;

				.vip-level-item {
					margin-right: 40rpx;
					padding: 0 10rpx;

					.level-text {
							font-size: 32rpx;
							color: #666;
							padding-bottom: 10rpx;
							position: relative;
							
							&.active {
								color: #ffffff;
								font-weight: bold;
								
								&::after {
									content: '';
									position: absolute;
									bottom: 0;
									left: 50%;
									transform: translateX(-50%);
									width: 40rpx;
									height: 4rpx;
									background-color: #ffffff;
									border-radius: 2rpx;
								}
							}
						}
				}
			}
		}


		.level-arrow::before {
			content: '';
			position: absolute;
			top: 2rpx;
			left: -24rpx;
			border-left: 24rpx solid transparent;
			border-right: 24rpx solid transparent;
			border-bottom: 24rpx solid #f5f5f5;
			z-index: -1;
		}

		.vip-level-container {
			background-color: #333;
			padding: 30rpx 0 10rpx;
			position: relative;
			margin-bottom: 20rpx;
			// border-bottom: 1rpx solid #ddd;
			height: 120rpx;
		}


	}
	
	.vip-content{
		background-color: #333;
		border-top-right-radius: 20rpx;
		border-top-left-radius: 20rpx;
		overflow: hidden;
	}

	.price-container {
		display: flex;
		padding: 30rpx;
		background-color: #333;
		margin-top: 20rpx;

		.price-item {
			flex: 1;
			text-align: center;
			padding: 60rpx 20rpx;
			padding-top: 60rpx;
			background-color: #474747;
			border-radius: 10rpx;
			margin: 0 10rpx;
			position: relative;
			overflow: hidden;

			&.highlight {
				background-color: #5C5C5C;
				position: relative;
				overflow: visible !important;
				border-radius: 14rpx;
				border: 3rpx solid transparent;
				border-image: linear-gradient(135deg, #FFE329, #FFA229, #FFE329) 1;
				border-image-slice: 1;
			}

			.discount-tag {
				position: absolute;
				top: 0;
				left: 0;
				background-color: #333333;
				color: #FFE329;
				font-size: 24rpx;
				padding: 5rpx 15rpx;
				border-radius: 0 0 15rpx 0;
			}

			.duration {
				font-size: 26rpx;
				color: #fff;
			}

			.current-price {
				font-size: 48rpx;
				font-weight: bold;
				color: #FFE329;
				margin-bottom: 5rpx;
			}

			.original-price {
				font-size: 24rpx;
				color: #999;
				text-decoration: line-through;
			}
		}
	}
	
	.scorll{
		height: 1200rpx;
		padding-bottom: 200rpx;
		box-sizing: border-box;
		overflow-y: scroll;
		overscroll-behavior: contain;
	}

	.review-section {
		background-color: #333;
		margin-top: 20rpx;
		padding: 30rpx;

		.section-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20rpx;

			.section-title {
				font-size: 24rpx;
				color: #fff;
			}

			.view-all {
				font-size: 24rpx;
				color: #999;
				display: flex;
				align-items: center;
				.rightIcon{
					width: 30rpx;
					height: 30rpx;
				}
			}
		}

		.category-tags {
			display: flex;
			flex-wrap: wrap;
			margin-bottom: 30rpx;

			.category-tag {
				font-size: 22rpx;
				color: #b7b7b7;
				background-color: #3D3D3D;
				padding: 10rpx 10rpx;
				border-radius: 20rpx;
				margin-right: 10rpx;
				margin-bottom:10rpx;
			}
		}

		.review-list {
			.review-item {
				padding-bottom: 30rpx;
				margin-bottom: 30rpx;
				border-bottom: 2rpx solid #f0f0f0;

				&:last-child {
					border-bottom: none;
					margin-bottom: 0;
					padding-bottom: 0;
				}

				.reviewer-info {
					display: flex;
					align-items: center;
					margin-bottom: 20rpx;

					.avatar {
						width: 60rpx;
						height: 60rpx;
						border-radius: 50%;
						margin-right: 20rpx;
					}

					.info-text {
						flex: 1;
						.reviewer-name {
							font-size: 22rpx;
							color: #fff;
						}

						.usage-time,
						.album-type {
							font-size: 22rpx;
							color: #999;
							margin-left: 10rpx;
						}
					}
				}

				.review-content {
					font-size: 22rpx;
					color: #fff;
					line-height: 1.6;
					margin-top: 10rpx;
				}

				.expand-text {
					font-size: 28rpx;
					color: #e64340;
					margin-top: 10rpx;
					display: block;
				}
			}
		}
	}

	/* 会员权益 */
	.benefits-section {
		background-color: #333;
		margin-top: 20rpx;
		padding: 30rpx;

		.section-header {
			margin-bottom: 20rpx;

			.section-title {
				font-size: 32rpx;
				font-weight: bold;
				color: #fff;
			}
		}
		.benefits-content{
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
			.vip-tab{
				width: 340rpx;
				height: 100rpx;
				background-color: #3D3D3D;
				border-radius: 20rpx;
				margin-bottom: 40rpx;
				display: flex;
				align-items: center;
				.vipIcon{
					width: 50rpx;
					height: 50rpx;
					margin-right: 20rpx;
					margin-left: 20rpx;
				}
				.level-info{
					.level-label{
						color: #FFE329;
						font-size: 26rpx;
					}
					.level-des{
						color: #999999;
						font-size: 22rpx;
					}
				}
			}
			
		}
		.vip-tip{
			text-align: center;
			color: #B0B0AF;
			font-size: 22rpx;
			margin-top: 30rpx;
		}
	}

	/* 底部升级按钮 */
	.bottom-upgrade {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #333333;
		padding: 30rpx;
		.upgrade-text{
			text-align: center;
			font-size: 30rpx;
			font-weight: bold;
			height: 100rpx;
			border-radius: 50rpx;
			background-color: #FFE329;
			color: #000;
			line-height: 100rpx;
		}
		.upgrade-des{
			text-align: center;
			font-size: 22rpx;
			color: #626160;
			margin-top: 10rpx;
		}
		
	}
	
	/* iOS提示弹窗 */
	.ios-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.6);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 9999;
		
		.modal-content {
			width: 600rpx;
			background-color: #fff;
			border-radius: 20rpx;
			padding: 40rpx;
			display: flex;
			flex-direction: column;
			align-items: center;
			
			.modal-image {
				width: 400rpx;
				height: 400rpx;
				border-radius: 10rpx;
				margin-bottom: 30rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				
				image {
					width: 100%;
					height: 100%;
				}
			}
			
			.modal-text {
				font-size: 28rpx;
				color: #333;
				text-align: center;
				line-height: 1.6;
				margin-bottom: 40rpx;
			}
			
			.modal-button {
				width: 100%;
				height: 80rpx;
				background-color: #FFE4A3;
				color: #000;
				font-size: 30rpx;
				border-radius: 40rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				border: none;
				line-height: 80rpx;
				padding: 0;
			}

			.contact-button::after {
				border: none;
			}

			.modal-cancel {
				margin-top: 24rpx;
				font-size: 28rpx;
				color: #999;
			}
		}
	}
	.vip-icon{
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		position: absolute;
		top: 50rpx;
		left: 0;
		image{
			width: 30%;
			height: 120%;
			opacity: 0.4;
		}
	}
</style>
