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
							<view class="title">写口碑</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="tip" :style="{ paddingTop: totalHeight + 'px' }">
			写使用口碑，让我们做的更好，同时领取一个月会员
		</view>
		<view class="way">
			<view class="title">
				相册用途
			</view>
			<view class="input-content">
				<input type="text" v-model="purpose" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('writePurpose', '最少两个字')" @tap="focusField('writePurpose')" @focus="focusField('writePurpose')" @blur="blurField('writePurpose')" />
			</view>
			<view class="tabs">
				<view class="tab" @click="selectTag(item)" v-for="(item,index) in cateList" :key="index">{{item}}</view>
			</view>
		</view>
		<view class="answer">
			<view class="title">
				你的评价
			</view>
			<view class="input-content">
				<textarea placeholder-class="jf-textarea-placeholder" :placeholder="placeholderFor('writeEvaluate', '优质的口碑,我们会送出30天的会员!!')" v-model="evaluate_content" @tap="focusField('writeEvaluate')" @focus="focusField('writeEvaluate')" @blur="blurField('writeEvaluate')" cols="30" rows="10"></textarea>
			</view>
		</view>
		<view class="bottom-box">
			<view class="btn" @click="submit">
				确认
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
				purpose:'',
				evaluate_content:''
			}
		},
		onLoad(options) {
			const systemInfo = this.$base.getSystemInfoCompat()
			this.statusBarHeight = systemInfo.statusBarHeight
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight
			
			this.getcateList()
		},
		methods:{
			back(){
				uni.navigateBack()
			},
			submit(){
				const querys = {
					purpose:this.purpose,
					evaluate_content:this.evaluate_content,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('submit/evaluate', data, 'post', {
					show_err: true
				}).then(res => {
				})
			},
			selectTag(item){
				this.purpose = item
			},
			getcateList(){
				const querys = {
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('scate/evaluate', data, 'get', {
					show_err: true
				}).then(res => {
					this.cateList = res.data
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.page {
		background-color: #EEF0F4;
		min-height: 100vh;
		box-sizing: border-box;
		padding-bottom: 300rpx;
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
	.tip{
		width: 100%;
		height: 100rpx;
		background-color: #FFF4E3;
		font-size: 26rpx;
		display: flex;
		align-items: center;
		color: #CBA04C;
		padding-left: 40rpx;
	}
	.way{
		width: 710rpx;
		background-color: #FFFFFF;
		margin: 0 auto;
		padding: 20rpx;
		box-sizing: border-box;
		border-radius: 20rpx;
		margin-top: 20rpx;
		padding-bottom: 40rpx;
		.title{
			font-size: 32rpx;
			font-weight: bold;
		}
		.input-content{
			height: 100rpx;
			width: 680rpx;
			background-color: #F8F8F8;
			border-radius: 20rpx;
			margin: 0 auto;
			display: flex;
			align-items: center;
			margin-top: 20rpx;
			padding:0 20rpx;
			box-sizing: border-box;
			input{
				font-size: 28rpx;
			}
		}
		.tabs{
			margin-top: 20rpx;
			display: flex;
			flex-wrap: wrap;
			width: 680rpx;
			margin: 0 auto;
			.tab{
				background-color: #FAFAFA;
				padding: 10rpx;
				box-sizing: border-box;
				border-radius: 20rpx;
				border: 2rpx solid #b1b1b1;
				font-size: 24rpx;
				margin-right: 20rpx;
				margin-top: 20rpx;
			}
		}
	}
	.answer{
		width: 710rpx;
		background-color: #FFFFFF;
		margin: 0 auto;
		padding: 20rpx;
		box-sizing: border-box;
		border-radius: 20rpx;
		margin-top: 20rpx;
		padding-bottom: 40rpx;
		.title{
			font-size: 32rpx;
			font-weight: bold;
		}
		.input-content{
			height: 500rpx;
			width: 680rpx;
			background-color: #F8F8F8;
			border-radius: 20rpx;
			margin: 0 auto;
			margin-top: 20rpx;
			padding:20rpx;
			box-sizing: border-box;
			textarea{
				font-size: 28rpx;
			}
		}
	}
	.bottom-box{
		width: 100%;
		height: 200rpx;
		background-color: #FFFFFF;
		position: fixed;
		bottom: 0;
		left: 0;
		z-index: 99;
		padding-top: 40rpx;
		box-sizing: border-box;
		.btn{
			width: 620rpx;
			height: 80rpx;
			margin: 0 auto;
			background-color:#58BE6C ;
			border-radius: 10rpx;
			color: #FFFFFF;
			display: flex;
			align-items: center;
			justify-content: center;
		}
	}
</style>