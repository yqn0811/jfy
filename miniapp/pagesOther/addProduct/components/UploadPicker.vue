<template>
	<u-popup :show="visible" mode="bottom" :round="24" bgColor="transparent" :safe-area-inset-bottom="false" @close="close">
		<view class="picker-root">
			<view class="sheet-head">
				<text class="title">上传</text>
				<image @click="close" class="close-icon" src="/static/icon/退出@2x.png" mode="scaleToFill" />
			</view>
			<view class="option-box">
				<view class="option" @click="select('image')">上传图片</view>
				<view class="option" @click="select('ai_resource')">从我的资源库选择</view>
				<view class="option" @click="select('video')">上传视频</view>
				<view class="option" @click="select('from_chat')">从微信聊天中选取</view>
				<view class="option" @click="select('batch')">大批量上传</view>
			</view>

		</view>
	</u-popup>
</template>

<script>
export default {
	name: 'UploadPicker',
	props: {
		visible: { type: Boolean, default: false }
	},
	emits: ['update:visible', 'select'],
	methods: {
		close() {
			this.$emit('update:visible', false)
		},
		select(type) {
			this.$emit('select', type)
			this.close()
		}
	}
}
</script>

<style scoped>
.picker-root {
	background: #fff;
	padding: 24rpx;
	margin: 20rpx;
	border-radius: 48rpx;
}

/* 头部 */
.sheet-head {
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	padding: 24rpx 20rpx;
	background: #fff;
}

.title {
	font-weight: 500;
	font-size: 36rpx;
	color: #333333;
	margin-bottom: 24rpx;
}

.close-icon {
	position: absolute;
	right: 18rpx;
	top: 8rpx;
	width: 48rpx;
	height: 48rpx;
}

.option-box {
	background: #F5F5F5;
	border-radius: 24rpx;
	overflow: hidden;
}

/* 每项 */
.option {
	padding: 40rpx 20rpx;
	border-top: 1rpx solid #f6f6f6;
	text-align: center;
	font-weight: 500;
	font-size: 32rpx;
	color: #333333;
	background: #F5F5F5;
	border-bottom: 1px solid rgba(0, 0, 0, 0.05);
	;
}

/* 去掉第一项上边框 */
.option:first-of-type {
	border-top: none;
}

/* 兼容 safe-area */
.u-popup__wrap {
	border-top-left-radius: 16rpx;
	border-top-right-radius: 16rpx;
}
</style>
