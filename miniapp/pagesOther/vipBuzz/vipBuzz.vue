<template>
	<view class="page">
		<view class="header">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left">
						<view class="back-button" @click="back">
							<img class="backIcon" src="@/static/icon/back.png" />
						</view>
						<view class="info-box">
							<view class="title">会员口碑</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="review-section" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="category-tags">
				<text class="category-tag"  @click="selectTag(item)" :class="activeName == item.name ? 'actived' : ''"  v-for="(item,index) in cateList" :key="index">{{item.name}}({{item.count}})</text>
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
		<view class="btn" @click="toWrite">
			<image src="/static/icon/write.png" class="writeIcon" mode=""></image> 写口碑
			<view class="send">
				再送一个月会员
			</view>
		</view>
	</view>
</template>

<script>
	export default{
		data(){
			return{
				statusBarHeight: '',
				totalHeight: '',
				navigationBarHeight: 44,
				cateList:[],
				evaluateList:[],
				activeName:'',
				page:1,
				last_page:1
			}
		},
		onLoad(options) {
			const systemInfo = this.$base.getSystemInfoCompat()
			this.statusBarHeight = systemInfo.statusBarHeight
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight
			
			this.getcateList()
		},
		onReachBottom() {
			if(this.page < this.last_page){
				this.page ++
				this.getList()
			}
		},
		methods:{
			toWrite(){
				uni.navigateTo({
					url:'/pagesOther/writePage/writePage'
				})
			},
			back(){
				uni.navigateBack()
			},
			selectTag(item){
				this.activeName = item.name
				this.evaluateList = []
				this.page = 1
				this.getList()
			},
			getList(){
				const querys = {
					timestamp: new Date().getTime(),
					purpose:this.activeName,
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
					this.last_page = res.data.last_page
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
					this.activeName = res.data[0].name
					this.getList()
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.page {
		background-color: #fff;
		min-height: 100vh;
		box-sizing: border-box;
		padding-bottom: 200rpx;
		box-sizing: border-box;
	}
	
	.header {
		width: 100%;
		background-size: 100%;
		box-sizing: border-box;
		position: fixed;
		top: 0;
		left: 0;
		background-color: #FFFFFF;
		z-index: 99;
	
		.custom-nav-bar {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			color: #fff;
			background-color: #FFFFFF;
			z-index: 99;
			z-index: 1;
			border-bottom: 2rpx solid #eee;
	
			.nav-bar-content {
				padding: 0 10px;
				width: 520rpx;
				display: flex;
				align-items: center;
				justify-content: space-between;
	
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
				}
	
				.back-button {
					margin-right: 20rpx;
	
					.backIcon {
						width: 30rpx;
						height: 30rpx;
					}
				}
	
				.info-box {
					.title {
						font-weight: bold;
						font-size: 34rpx;
						color: #000000;
					}
				}
	
	
			}
		}
	
	}
	.review-section {
		background-color: #fff;
		margin-top: 20rpx;
		padding: 30rpx;
	
		.section-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20rpx;
	
			.section-title {
				font-size: 24rpx;
				color: #333;
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
				color: #666;
				background-color: #f5f5f5;
				padding: 10rpx 10rpx;
				border-radius: 20rpx;
				margin-right: 10rpx;
				margin-bottom:10rpx;
			}
			.actived{
				background-color: #05BF61;
				color: #FFFFFF;
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
							color: #333;
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
					color: #333;
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
	.btn{
		position: fixed;
		width: 100%;
		height: 140rpx;
		bottom: 0;
		left: 0;
		background-color: #06C15F;
		color: #FFFFFF;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 30rpx;
		.writeIcon{
			width: 30rpx;
			height: 30rpx;
			margin-right: 10rpx;
		}
		.send{
			position: absolute;
			top: 10rpx;
			left: 55%;
			background-color: #FA7142;
			color: #FFFFFF;
			padding: 4rpx 10rpx;
			box-sizing: border-box;
			border-top-right-radius: 10rpx;
			border-top-left-radius: 10rpx;
			border-bottom-right-radius: 10rpx;
			font-size: 26rpx;
		}
	}
	
</style>