<template>
	<view class="">
		<view class="video-detail-page" @tap="toggleActions">
			<!-- 视频播放区域 -->
			<view class="video-container">
				<video id="video-player" :key="videoInfo.picture_url" :src="videoInfo.picture_url"
					:poster="videoInfo.poster" class="video-player" :controls="false" :show-center-play-btn="true"
					:enable-progress-gesture="false" :show-play-btn="false" :show-fullscreen-btn="false"
					:show-progress="false" object-fit="contain" @play="onVideoPlay" @pause="onVideoPause">
				</video>
			</view>

			<!-- 视频信息 -->
			<view class="video-meta" :class="{ 'hide': !showActions }">
				<text class="upload-time">{{ videoInfo.uploadTime }}</text>
			</view>

			<!-- 底部操作栏 -->
			<view class="bottom-actions" :class="{ 'hide': !showActions }" @tap.stop>
				<view class="action-item" @tap="handleDelete">
					<view class="icon-wrapper">
						<image src="/static/icon/del.png" mode=""></image>
					</view>
				</view>

				<view class="action-item" @tap="handleDownload">
					<view class="icon-wrapper">
						<image src="/static/icon/download.png" mode=""></image>
					</view>
				</view>

				<view class="action-item" @tap="handlePlay">
					<view class="icon-wrapper play-btn">
						<image src="/static/icon/play.png" v-if="!isPlaying" mode=""></image>
						<image src="/static/icon/stop.png" v-else mode=""></image>
					</view>
				</view>

				<view class="action-item" @tap="handleShare">
					<view class="icon-wrapper">
						<image src="/static/icon/add.png" mode=""></image>
					</view>
				</view>
			</view>
		</view>
		<u-popup :show="show" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">
					提示
				</view>
				<view class="input-content" style="background-color: #FFFFFF;font-size: 32rpx;">
					删除此照片还是把它从相册中移除
				</view>
				<view class="input-content" style="color:#759EEB ;background-color: #FFFFFF;" @click="delPic(2)">
					从相册中移除
				</view>
				<view class="input-content" style="color:#E02D23 ;background-color: #FFFFFF;" @click="delPic(1)">
					删除照片
				</view>
				<view class="input-content" style="background-color: #FFFFFF;" @click="show = false">
					取消
				</view>
			</view>
		</u-popup>
		<u-popup :show="openShow" :round="10" mode="bottom" @close="openShow = false">
			<view>
				<view class="myAlbum">
					<view class="pop-title">
						<image class="backIcon" src="/static/icon/back.png" mode=""></image> 我的相册 <view class="add-btn"
							@click="addAlbum">新建</view>
					</view>
					<view class="album-box">
							<view class="album" v-for="(item,index) in albumList" :key="getAlbumKey(item, index)">
								<view class="album-img" :data-index="index" @click="selectAlbum(item,index,$event)">
									<image v-if="item.new_thumb" :src="item.new_thumb" lazy-load mode="aspectFill"></image>
								<image v-else src="/static/image/pic.png" mode="aspectFill"></image>
								<view class="select-box" v-if="item.isChecked">
									<image src="../../static/icon/checked.png" mode=""></image>
								</view>
							</view>
							<view class="album-name">
								{{item.folder_name}}
							</view>
						</view>
					</view>
					<view class="submit-btn" @click="addToAlbum">
						确定
					</view>
				</view>
			</view>
		</u-popup>
		<u-popup :show="inputShow" mode="center" :round="10">
			<view class="popBox">
				<view class="pop-title">
					输入{{folder_type == 2 ? '相册' : '文件夹'}}名称
				</view>
				<view class="input-content" style="width: 80%;">
					<input type="text" v-model="folder_name_input" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('videoFolderName', '请输入名称')" @tap="focusField('videoFolderName')" @focus="focusField('videoFolderName')" @blur="blurField('videoFolderName')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="inputShow = false">取消</view>
					<view class="submit" @click="createAlbum">创建</view>
				</view>
			</view>
		</u-popup>
		<u-action-sheet :actions="list" cancelText='取消' :closeOnClickOverlay="true" :title="title" :show="addShow"
			@select="selectTab" @close='addShow = false'></u-action-sheet>
	</view>
</template>

	<script>
		import { notifyFolderRefresh } from '@/common/helper/refresh.js';
		import { buildOriginalDownloadRequest } from '@/common/helper/imageUrls.js';
		import { buildListItemKey } from '@/common/helper/listKey.js';
		import {
			getObjectId,
			resolveClickedListItem,
			showInvalidRecordToast,
		} from '@/common/helper/clickItem.js';
		export default {
		data() {
			return {
				videoInfo: {
					picture_url: '',
					picture_url_original: '',
					uploadTime: '淡、2025年10月11日 10:09上传'
				},
				showActions: true, // 控制操作栏显示隐藏
				isPlaying: false, // 视频播放状态
				videoContext: null,
				show: false,
				openShow: false,
				addShow: false,
				inputShow: false,
				option_flag:'',
				list: [{
						name: '新建相册',
					},
					{
						name: '新建文件夹',
					},
				],
				albumList: [],
				fidList: [],
				folder_name_input: '',
				folder_type: ''
			}
		},

		onLoad(options) {
			this.option_flag = options.option_flag
			// 接收列表页传来的视频信息
			this.videoInfo = uni.getStorageSync('videoInfo')
			this.getAlbumList()
			this.visitInfo()
		},

		onReady() {
			// 创建视频上下文
			this.videoContext = uni.createVideoContext('video-player', this)
		},

			methods: {
				getAlbumKey(item, index) {
					return buildListItemKey(item, index, 'album');
				},
				getVideoPicId() {
					return getObjectId(this.videoInfo, ['pic_id', 'id']);
				},
				visitInfo(){
				const picId = this.getVideoPicId();
				if (!picId) return;
				const params = this.buildApiParams({ pic_id: picId });
				
				this.$go('user/add/visit', params, 'post', { show_err: true })
					.then(res => {
					})
					.catch(err => {
					});
			},
			buildApiParams(params) {
				const querys = {
					...params,
					timestamp: new Date().getTime()
				};
				
				return {
					...querys,
					sign: this.$base.getASCII(querys)
				};
			},
			
			addToAlbum(){
				const picId = this.getVideoPicId();
				if (!picId || !this.fidList.length) {
					showInvalidRecordToast(!picId ? '视频数据异常，请刷新后重试' : '请选择相册');
					return;
				}
				const querys = {
					fid: this.fidList[this.fidList.length - 1],
					pic_ids: picId,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('album/move/pics', data, 'post', {
					show_err: true
				}).then(res => {
					uni.showToast({
						title:'添加成功',
						icon:'none'
					})
					this.openShow = false
				})
			},
			selectTab(e){
				if(e.name == '新建相册'){
					this.folder_type = 2
				}else{
					this.folder_type = 1
				}
				this.inputShow = true
			},
			addAlbum(){
				this.addShow = true
			},
			createAlbum(){
				const querys = {
					folder_type:this.folder_type,
					folder_name:this.folder_name_input,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('album/create/folder', data, 'post', {
					show_err: true
				}).then(res => {
					uni.showToast({
						title:res.msg,
						icon:'none'
					})
					this.inputShow = false
					notifyFolderRefresh(this.folder_type)
					this.getAlbumList()
				})
			},
			selectAlbum(item,index,event){
				const current = resolveClickedListItem(item, index, event, this.albumList);
				const folderId = getObjectId(current, ['id', 'folder_id', 'fid']);
				if (!current || !folderId) {
					showInvalidRecordToast();
					return;
				}
				if(current.folder_type == 2){
					this.fidList.push(folderId)
					this.albumList.forEach((item,idx)=>{
						if(index == idx){
							this.albumList[idx].isChecked = true
						}else{
							this.albumList[idx].isChecked = false
						}
					})
				}
				if(current.folder_type == 1){
					this.fidList.push(folderId)
					const querys = {
						fid: this.fidList[this.fidList.length - 1],
						timestamp: new Date().getTime(),
					}
					const data = {
						...querys,
						sign: this.$base.getASCII(querys)
					}
					this.$go('album/show/folder', data, 'post', {
						show_err: true
					}).then(res => {
						this.albumList = res.data
					})
				}
				
			},
			getAlbumList() {
				const querys = {
					fid: 0,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('album/show/folder', data, 'post', {
					show_err: true
				}).then(res => {
					this.albumList = res.data
				})
			},
			// 切换操作栏显示/隐藏
			toggleActions() {
				this.showActions = !this.showActions
			},

			// 播放视频
			playVideo() {
				if (this.videoContext) {
					this.videoContext.play()
				}
			},

			// 暂停视频
			pauseVideo() {
				if (this.videoContext) {
					this.videoContext.pause()
				}
			},

			// 视频开始播放
			onVideoPlay() {
				this.isPlaying = true
			},

			// 视频暂停
			onVideoPause() {
				this.isPlaying = false
			},

			// 删除
			handleDelete() {
				if(this.option_flag == 'false'){
					uni.showToast({
						title:'你没有删除该视频的权限',
						icon:'none'
					})
				}else{
					this.show = true
				}
			},

			delPic(e) {
				const picId = this.getVideoPicId();
				if (!picId) {
					showInvalidRecordToast('视频数据异常，请刷新后重试');
					return;
				}
				const querys = {
					pic_id: picId,
					del_type: e,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('album/delete/pic', data, 'post', {
					show_err: true
				}).then(res => {
					this.show = false
					uni.navigateBack()
				})
			},

			// 下载原视频
			async handleDownload() {
				if (!this.canUseOriginalMedia()) {
					uni.showToast({
						title: '请先升级成为会员',
						icon: 'none'
					})
					return
				}
				const downloadRequest = buildOriginalDownloadRequest(this.videoInfo, {
					pic_id: this.videoInfo.pic_id || this.videoInfo.id,
					file_size: this.videoInfo.file_size || this.videoInfo.size,
				})
				if (!downloadRequest.url) {
					uni.showToast({
						title: '视频地址无效',
						icon: 'none'
					})
					return
				}
				uni.showLoading({
					title: '下载中...'
				})

				// 下载视频
				uni.downloadFile({
					url: downloadRequest.url,
					header: downloadRequest.header,
					success: (res) => {
						if (res.statusCode === 200) {
							uni.saveVideoToPhotosAlbum({
								filePath: res.tempFilePath,
								success: () => {
									uni.hideLoading()
									uni.showToast({
										title: '保存成功',
										icon: 'success'
									})
								},
								fail: () => {
									uni.hideLoading()
									uni.showToast({
										title: '保存失败',
										icon: 'none'
									})
								}
							})
						}
					},
					fail: (err) => {
						uni.hideLoading()
						uni.showToast({
							title: '下载失败',
							icon: 'none'
						})
					}
				})
			},
			canUseOriginalMedia() {
				const userInfo = uni.getStorageSync('userInfo') || {}
				const gradeLevel = Number(
					userInfo.grade_level ||
					userInfo.gradeLevel ||
					userInfo.vip_grade ||
					userInfo.vipGrade ||
					0
				)
				const rawEndTime =
					userInfo.end_time ||
					userInfo.endTime ||
					userInfo.vip_end_time ||
					userInfo.vipEndTime ||
					userInfo.expire_time ||
					userInfo.expireTime ||
					0
				let endTime = Number(rawEndTime || 0)
				if (!endTime && typeof rawEndTime === 'string' && rawEndTime) {
					const parsed = new Date(rawEndTime).getTime()
					endTime = Number.isNaN(parsed) ? 0 : Math.floor(parsed / 1000)
				}
				return gradeLevel > 0 && (!endTime || endTime > Math.floor(Date.now() / 1000))
			},

			// 播放/暂停切换
			handlePlay() {
				if (this.isPlaying) {
					this.pauseVideo()
				} else {
					this.playVideo()
				}
			},

			// 分享
			handleShare() {
				if(this.option_flag == 'false'){
					uni.showToast({
						title:'你没有添加该视频的权限',
						icon:'none'
					})
				}else{
						this.openShow = true
					}
				}
		},

		// 分享配置
		onShareAppMessage() {
			return {
				title: this.videoInfo.title,
				path: `/pages/video-detail/video-detail?videoId=${this.videoInfo.id}`,
				imageUrl: this.videoInfo.poster
			}
		}
	}
</script>

<style lang="scss" scoped>
	.myAlbum {
		height: 900rpx;
		padding: 30rpx;
		box-sizing: border-box;

		.pop-title {
			font-size: 30rpx;
			display: flex;
			align-items: center;
			margin-bottom: 40rpx;

			.add-btn {
				width: 100rpx;
				height: 60rpx;
				background-color: #E6F5E9;
				border-radius: 10rpx;
				color: #57BE6B;
				display: flex;
				align-items: center;
				font-size: 26rpx;
				margin-left: 20rpx;
				justify-content: center;
			}

			.backIcon {
				width: 30rpx;
				height: 30rpx;
				margin-right: 20rpx;
			}
		}

		.album-box {
			width: 100%;
			height: 600rpx;
			overflow-y: auto;

			.album {
				display: inline-block;
				margin-bottom: 30rpx;

				.album-img {
					width: 200rpx;
					height: 200rpx;
					border-radius: 10rpx;
					margin-right: 20rpx;
					position: relative;

					image {
						width: 100%;
						height: 100%;
					}

					.select-box {
						font-size: 22rpx;
						display: flex;
						align-items: center;
						position: absolute;
						right: 10rpx;
						bottom: 10rpx;

						image {
							width: 30rpx;
							height: 30rpx;
							margin-right: 10rpx;
						}
					}
				}

				.album-name {
					font-size: 26rpx;
					margin-top: 10rpx;
					display: flex;
					align-items: center;

					image {
						width: 30rpx;
						height: 30rpx;
					}
				}
			}
		}

		.submit-btn {
			width: 600rpx;
			height: 80rpx;
			background-color: #57BE6B;
			border-radius: 10rpx;
			color: #FFFFFF;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto;
		}
	}

	.popBox {
		width: 600rpx;

		.pop-title {
			width: 100%;
			height: 100rpx;
			border-bottom: 2rpx solid #F6F6F6;
			text-align: center;
			line-height: 100rpx;
		}

		.input-content {
			width: 540rpx;
			height: 80rpx;
			margin: 0 auto;
			margin-top: 20rpx;
			background-color: #f8f3f3;
			border-radius: 10rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			padding-left: 20rpx;
			box-sizing: border-box;

			input {
				font-size: 30rpx;
			}
		}

		.btn-box {
			display: flex;
			align-items: center;
			width: 100%;
			height: 100rpx;
			border-top: 2rpx solid #F6F6F6;
			margin-top: 20rpx;

			.cancel {
				width: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				border-right: 2rpx solid #F6F6F6;
			}

			.submit {
				width: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #7590E8;
			}
		}
	}

	.video-detail-page {
		width: 100%;
		height: 100vh;
		background-color: #000;
		display: flex;
		flex-direction: column;
	}

	.video-container {
		flex: 1;
		position: relative;
		width: 100%;
		background-color: #000;

		.video-player {
			width: 100%;
			height: 100%;
		}

		.video-info-overlay {
			position: absolute;
			left: 50%;
			top: 50%;
			transform: translate(-50%, -50%);
			display: flex;
			flex-direction: column;
			align-items: center;
			pointer-events: none;
			z-index: 10;
			transition: opacity 0.3s ease;

			&.hide {
				opacity: 0;
			}

			.video-title {
				font-size: 48rpx;
				font-weight: bold;
				color: #fff;
				text-shadow: 2rpx 2rpx 8rpx rgba(0, 0, 0, 0.5);
				letter-spacing: 4rpx;
				margin-bottom: 20rpx;
			}

			.video-subtitle {
				font-size: 32rpx;
				color: #fff;
				text-shadow: 2rpx 2rpx 8rpx rgba(0, 0, 0, 0.5);
				letter-spacing: 2rpx;
			}
		}

		.play-control {
			position: absolute;
			left: 50%;
			top: 50%;
			transform: translate(-50%, -50%);
			width: 120rpx;
			height: 120rpx;
			background: rgba(0, 0, 0, 0.5);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 15;

			image {
				width: 100%;
				height: 100%;
			}

		}
	}

	.video-meta {
		position: absolute;
		bottom: 160rpx;
		left: 30rpx;
		z-index: 20;
		transition: opacity 0.3s ease, transform 0.3s ease;

		&.hide {
			opacity: 0;
			transform: translateY(20rpx);
		}

		.upload-time {
			font-size: 24rpx;
			color: rgba(255, 255, 255, 0.8);
			text-shadow: 1rpx 1rpx 4rpx rgba(0, 0, 0, 0.5);
		}
	}

	.bottom-actions {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		height: 140rpx;
		background-color: #333;
		display: flex;
		align-items: center;
		justify-content: space-around;
		padding: 0 40rpx;
		padding-bottom: env(safe-area-inset-bottom);
		z-index: 100;
		transition: opacity 0.3s ease, transform 0.3s ease;

		&.hide {
			opacity: 0;
			transform: translateY(100%);
			pointer-events: none;
		}

		.action-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 8rpx;

			.icon-wrapper {
				width: 40rpx;
				height: 40rpx;
				display: flex;
				align-items: center;
				justify-content: center;

				image {
					width: 100%;
					height: 100%;
				}

			}

			.action-text {
				font-size: 22rpx;
				color: #fff;
			}

			&.action-play {
				.play-btn {
					width: 100rpx;
					height: 100rpx;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					border-radius: 50%;

					.iconfont {
						font-size: 56rpx;
					}
				}
			}
		}
	}
</style>
