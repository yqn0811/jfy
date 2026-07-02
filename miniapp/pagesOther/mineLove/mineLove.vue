<template>
	<view class="page">
		<view class="header" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left">
						<view class="back-button" @click="back">
							<img class="backIcon" src="@/static/icon/back.png" />
						</view>
						<view class="info-box">
							<view class="title">我的收藏</view>
						</view>
					</view>
					<view class="edit-btn" @click="changeState">{{state == 0 ? '批量编辑' : '取消'}}</view>
				</view>
			</view>
		</view>
		<view :style="{ paddingTop: totalHeight + 'px' }">
			<view >
				<view class="tip-box" v-if="state == 0">
					小贴士:长按任意照片，即可进入批量编辑模式
					<img class="closeIcon" src="@/static/icon/close.png" />
				</view>
				<view class="img-content" v-for="(item,index) in picList" :key="index">
					<view class="time-box">
						<view class="img-time">{{item.collect_date}}</view>
						<view class="select-box" @click="handleRadioClickAll(item)" v-if="state == 1">
							<image src="@/static/icon/check.png" v-if='!item.isChecked' />
							<image src="@/static/icon/checked.png" v-if='item.isChecked' /> 全选
						</view>
					</view>
					<view class="img-box" v-for="(pic,idx) in item.pictures" @longpress="state = 1">
						<image class="pic" @click="toPicDetail(pic)" v-if="pic.file_type == 2 && pic.picture_url" :src="pic.picture_url + '?x-oss-process=video/snapshot,t_0,f_jpg,w_800,h_600'" mode="aspectFill"></image>
						<image class="pic" @click="toPicDetail(pic)" v-if="pic.file_type == 1 && pic.picture_url" :src="pic.picture_url"  mode="aspectFill"></image>
						<template v-if="state == 1">
							<view class="top" v-if="pic.set_top == 0" @click="setTop(pic)">置顶</view>
							<view class="top" v-if="pic.set_top == 1" @click="setTop(pic)">取消置顶</view>
							<view class="select-box" @click.stop="handleRadioClick(pic)">
								<image src="../../static/icon/check.png" mode="" v-if='!pic.isChecked'></image>
								<image src="../../static/icon/checked.png" mode="" v-if='pic.isChecked'></image>
							</view>
						</template>
						<image src="../../static/icon/shipin.png" mode="" v-if='pic.file_type == 2' class="videoIcon">
						</image>
					</view>
				</view>
			</view>
		</view>

		<view class="bottom-box">
			<view class="left-box">
			
			</view>
			<view class="right-box">
				<view class="del-btn" v-if="state == 1" @click="openDel">删除</view>
				<view class="add-btn" v-if="state == 1" @click="openAdd">添加到相册</view>
				<view class="update-btn" v-if="state == 0" @click="showUploadMenu">上传</view>
			</view>
		</view>

		<u-popup :show="show" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">
					提示
				</view>
				<view class="input-content" style="background-color: #FFFFFF;">
					删除此照片还是把它从相册中移除
				</view>
				<view class="input-content"  style="color:#759EEB ;background-color: #FFFFFF;" @click="delPic(2)">
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
						<image class="backIcon" src="/static/icon/back.png" mode=""></image> 我的相册 <view class="add-btn" @click="addAlbum">新建</view>
					</view>
					<view class="album-box">
						<view class="album" v-for="(item,index) in albumList" :key="index" >
							<view class="album-img" @click="selectAlbum(item,index)">
								<image v-if="item.new_thumb" :src="item.new_thumb" mode="aspectFill"></image>
								<image v-else src="/static/image/pic.png" mode="aspectFill"></image>
								<view class="select-box" v-if="item.isChecked">
									<image src="../../static/icon/checked.png" mode="" ></image>
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
					<input type="text" v-model="folder_name_input" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('mineLoveFolderName', '请输入名称')" @tap="focusField('mineLoveFolderName')" @focus="focusField('mineLoveFolderName')" @blur="blurField('mineLoveFolderName')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="inputShow = false">取消</view>
					<view class="submit" @click="createAlbum">创建</view>
				</view>
			</view>
		</u-popup>
		<u-action-sheet :actions="list" cancelText='取消' :closeOnClickOverlay="true" :title="title" :show="addShow" @select="selectTab" @close='addShow = false'></u-action-sheet>
	</view>
</template>

<script>
	import config from '@/common/config'
	export default {
		data() {
			return {
				statusBarHeight: '',
				totalHeight: '',
				navigationBarHeight: 44,
				state: 0,
				isChecked: false,
				fid: '',
				folder_name: '',
				page: 1,
				visit_times: 0,
				picList: [],
				show: false,
				openShow: false,
				addShow:false,
				inputShow:false,
				folder_type:1,
				pics: [],
				list: [{
						name: '新建相册',
					},
					{
						name: '新建文件夹',
					},
				],
				folder_name_input:'',
				albumList:[],
				fidList:[],
				option_flag:true
			};
		},
		onLoad(options) {
			const systemInfo = this.$base.getSystemInfoCompat()
			this.statusBarHeight = systemInfo.statusBarHeight
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight

			this.fid = options.id
			this.folder_name = options.folder_name
			this.visit_times = options.visit_times
			this.getAlbumList()
		},


		onShow() {
			this.getDetail()
			this.getAllPics()
		},
		onReachBottom() {

		},
		onShareAppMessage() {
			return {
				title: '分享了相册给你，来看看吧',
				path: `/pagesOther/imgBook/imgBook?id=${this.fid}&folder_name=${this.folder_name}`,
			}
		},
		methods: {
			toPicDetail(pic){
				uni.setStorageSync('picInfo',pic)
				uni.navigateTo({
					url: `/pagesOther/picDetail/picDetail?option_flag=${this.option_flag}&pic_id=${pic.id}&source=mineImg&pic_type=3`
                })
            },
			toVideoDetail(pic){
				uni.setStorageSync('videoInfo',pic)
				uni.navigateTo({
					url:`/pagesOther/videoDetail/videoDetail`
				})
			},
			
			addToAlbum(){
				let pics = []
				this.pics.forEach(item=>{
					pics.push(item.pic_id)
				})
				const querys = {
					fid: this.fidList[this.fidList.length - 1],
					pic_ids:pics.join(','),
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
					this.pics = []
					this.state = 0
					this.openShow = false
				})
			},
			
			selectAlbum(item,index){
				if(item.folder_type == 2){
					this.fidList.push(item.id)
					this.albumList.forEach((item,idx)=>{
						if(index == idx){
							this.albumList[idx].isChecked = true
						}else{
							this.albumList[idx].isChecked = false
						}
					})
				}
				if(item.folder_type == 1){
					this.fidList.push(item.id)
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
			
			getAllPics(){
				const querys = {
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('user/pic/collect', data, 'get', {
					show_err: true
				}).then(res => {
					uni.setStorageSync('picList',res.data.pictures)
				})
			},
			
			getAlbumList(){
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
					this.getAlbumList()
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
			
			//打开添加
			openAdd() {
				if(this.pics.length == 0){
					uni.showToast({
						title: '请选择要添加的图片',
						icon: 'none'
					})
				}else{
					this.openShow = true
				}
			},

			//删除
			delPic(e) {
				let pics = []
				this.pics.forEach(item=>{
					pics.push(item.id)
				})
				const querys = {
					collect_ids: pics.join(','),
					del_type: e,
					fid:-1,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('user/collect', data, 'post', {
					show_err: true
				}).then(res => {
					this.show = false
					this.state = 0
					this.getDetail()
				})
			},

			//打开删除
			openDel() {
				if (this.pics.length == 0) {
					uni.showToast({
						title: '请选择要删除的图片',
						icon: 'none'
					})
				} else {
					this.show = true
				}
			},

			//置顶
			setTop(item) {
				const querys = {
					pic_id: item.id,
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('album/set_top/pic', data, 'post', {
					show_err: true
				}).then(res => {
					this.getDetail()
				})
			},

			

			getDetail() {
				const querys = {
					timestamp: new Date().getTime(),
				}
				const data = {
					...querys,
					sign: this.$base.getASCII(querys)
				}
				this.$go('user/collect', data, 'get', {
					show_err: true
				}).then(res => {
					this.picList = res.data
				})
			},

			back() {
				uni.navigateBack()
			},

			toSet() {
				uni.navigateTo({
					url: '/pagesOther/setPage/setPage'
				})
			},

			handleRadioClickAll(item) {
				if (item.isChecked === false) {
					item.pictures.forEach(ele => {
						this.pics.push(ele);
						ele.isChecked = true;
					});
					item.isChecked = true;
				} else {
					item.isChecked = false;
					item.pictures.forEach(ele => {
						ele.isChecked = false;
						const index = this.pics.indexOf(ele);
						if (index !== -1) {
							this.pics.splice(index, 1);
						}
					});
				}

			},

			handleRadioClick(pic) {
				if (pic.isChecked) {
					pic.isChecked = false;
					this.pics.forEach((item, index) => {
						if (item.pic_id == pic.pic_id) {
							this.pics.splice(index, 1)
						}
					})
				} else {
					pic.isChecked = true;
					this.pics.push(pic)
				}
			},
			changeState() {
				this.pics = []
				if (this.state == 0) {
					this.state = 1
				} else {
					this.state = 0
				}
			},
			showUploadMenu() {
				uni.showActionSheet({
					itemList: ['上传图片', '上传视频', '从微信聊天中选取'],
					itemColor: '#000000',
					success: (res) => {
						const {
							tapIndex
						} = res;

						switch (tapIndex) {
							case 0:
								this.uploadImage();
								break;
							case 1:
								this.uploadVideo();
								break;
							case 2:
								this.selectFromWechatChat();
								break;
							case 3:
								this.batchUploadPhotos();
								break;
						}
					},
					fail: (err) => {
						console.log('取消选择', err);
					}
				});
			},
			// 上传图片
			uploadImage() {
				let that = this
				uni.chooseImage({
					count: 9,
					sizeType: ['compressed'],
					sourceType: ['album', 'camera'],
					success: (res) => {
						const tempFilePaths = res.tempFilePaths;

						uni.showLoading({
							title: '上传中...',
							mask: true
						});

						let uploadCount = 0;
						const totalCount = tempFilePaths.length;
						const uploadedUrls = [];
						const failedCount = 0;
						let querys = {
							timestamp: new Date().getTime(),
							file_type: 1,
							collect_flag:1
						}
						let params = {
							...querys,
							sign: this.$base.getASCII(querys)
						}
						console.log(params)

						tempFilePaths.forEach((filePath, index) => {
							uni.uploadFile({
								url: config.domain + '/api/common/upload',
								filePath: filePath,
								name: 'file',
								header: {
									'content-type': 'multipart/form-data', // 默认值
									'authorization-token': `Bearer ${uni.getStorageSync('token')}`
								},
								formData: params,
								success: (uploadRes) => {
									uploadCount++;
									if (uploadCount === totalCount) {
										uni.hideLoading()
										that.getDetail()
									}

								},
							});
						});
					},
					fail: (err) => {
						console.error('选择图片失败:', err);
						uni.showToast({
							title: '选择图片失败',
							icon: 'none'
						});
					}
				});
			},

			// 上传视频
			uploadVideo() {
				let that = this
				uni.chooseVideo({
					sourceType: ['album', 'camera'],
					maxDuration: 60, // 最长60秒
					camera: 'back', // 后置摄像头
					success: (res) => {
						const tempFilePath = res.tempFilePath;

						uni.showLoading({
							title: '上传中...',
							mask: true
						});
						let querys = {
							timestamp: new Date().getTime(),
							file_type: 2,
							collect_flag:1
						}
						let params = {
							...querys,
							sign: this.$base.getASCII(querys)
						}

						uni.uploadFile({
							url: config.domain + '/api/common/upload',
							filePath: tempFilePath,
							name: 'file',
							header: {
								'content-type': 'multipart/form-data', // 默认值
								'authorization-token': `Bearer ${uni.getStorageSync('token')}`
							},
							formData: params,
							success: (uploadRes) => {
								uni.hideLoading();
								that.getDetail()
							},
						});
					},
				});
			},

			// 从微信聊天中选取
			selectFromWechatChat() {
				let that = this
				uni.chooseMessageFile({
					count: 9,
					type: 'image',
					success: (res) => {
						const tempFiles = res.tempFiles;
						console.log('从微信聊天中选取的文件:', tempFiles);

						if (tempFiles.length === 0) {
							uni.showToast({
								title: '未选择文件',
								icon: 'none'
							});
							return;
						}

						uni.showLoading({
							title: '上传中...',
							mask: true
						});

						let uploadCount = 0;
						const totalCount = tempFiles.length;
						const uploadedUrls = [];
						let querys = {
							timestamp: new Date().getTime(),
							file_type: 1,
							collect_flag:1
						}
						let params = {
							...querys,
							sign: this.$base.getASCII(querys)
						}

						tempFiles.forEach((file, index) => {
							uni.uploadFile({
								url: config.domain + '/api/common/upload',
								filePath: file.path,
								name: 'file',
								header: {
									'content-type': 'multipart/form-data', // 默认值
									'authorization-token': `Bearer ${uni.getStorageSync('token')}`
								},
								formData: params,
								success: (uploadRes) => {
									uploadCount++;
									if (uploadCount === totalCount) {
										uni.hideLoading()
										that.getDetail()
									}
								},
							});
						});
					},
				});
			},

			// 大批量上传照片
			batchUploadPhotos() {

			},
		}
	};
</script>

<style lang="scss" scoped>
	.small-radio {
		transform: scale(0.7);
	}

	button {
		background: none;
		border: none;
		box-shadow: none;
		padding: 0;
		margin: 0;
	}

	button::after {
		border: 0;
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
			.backIcon{
				width: 30rpx;
				height: 30rpx;
				margin-right: 20rpx;
			}
		}
		.album-box{
			width: 100%;
			height: 600rpx;
			overflow-y: auto;
			.album{
				display: inline-block;
				margin-bottom: 30rpx;
				.album-img{
					width: 200rpx;
					height: 200rpx;
					border-radius: 10rpx;
					margin-right: 20rpx;
					position: relative;
					image{
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
				.album-name{
					font-size: 26rpx;
					margin-top: 10rpx;
					display: flex;
					align-items: center;
					image{
						width: 30rpx;
						height: 30rpx;
					}
				}
			}
		}
		.submit-btn{
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
			width: 100%;
			height: 80rpx;
			margin: 0 auto;
			border-radius: 10rpx;
			display: flex;
			align-items: center;
			padding-left: 20rpx;
			box-sizing: border-box;
			font-size: 26rpx;
			justify-content: center;
			border-bottom: 2rpx solid #F6F6F6;
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
					width: 50rpx;
					height: 30rpx;

					.backIcon {
						width: 30rpx;
						height: 30rpx;
					}
				}

				.info-box {
					.title {
						font-weight: bold;
						font-size: 28rpx;
						color: #000000;
					}

					.nums {
						color: #AAAAAA;
						font-size: 22rpx;
					}
				}


			}
		}

	}

	.empty-box {
		margin-top: 200rpx;

		.tip {
			font-size: 26rpx;
			text-align: center;
			color: #999999;
		}

		.emptyIcon {
			width: 300rpx;
			height: 300rpx;
			display: block;
			margin: 0 auto;
			margin-top: 20rpx;
		}
	}

	.tip-box {
		background-color: #EEF8F0;
		color: #29AD68;
		padding: 20rpx 20rpx;
		box-sizing: border-box;
		font-size: 22rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;

		.closeIcon {
			width: 20rpx;
			height: 20rpx;
		}
	}

	.img-content {
		.time-box {
			display: flex;
			align-items: center;
			justify-content: space-between;

			.img-time {
				font-size: 20rpx;
				margin-left: 20rpx;
				margin-top: 20rpx;
				margin-bottom: 20rpx;
			}

			.select-box {
				font-size: 22rpx;
				margin-right: 20rpx;
				display: flex;
				align-items: center;

				image {
					width: 30rpx;
					height: 30rpx;
					margin-right: 10rpx;
				}
			}

		}


		.img-box {
			width: 370rpx;
			height: 370rpx;
			display: inline-block;
			position: relative;

			.pic {
				width: 100%;
				height: 100%;
			}

			.top {
				height: 40rpx;
				border-radius: 20rpx;
				background-color: #fff;
				position: absolute;
				right: 20rpx;
				top: 10rpx;
				font-size: 22rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 0 10rpx;
			}

			.videoIcon {
				width: 80rpx;
				height: 60rpx;
				position: absolute;
				left: 10rpx;
				bottom: 10rpx;
			}

			.select-box {
				position: absolute;
				bottom: 10rpx;
				right: 20rpx;

				image {
					width: 30rpx;
					height: 30rpx;
					margin-right: 10rpx;
				}
			}
		}

		.img-box:nth-child(odd) {
			margin-left: 10rpx;
		}
	}

	.bottom-box {
		width: 100%;
		height: 140rpx;
		position: fixed;
		bottom: 0;
		left: 0;
		background-color: #fff;
		display: flex;
		align-items: center;
		justify-content: space-between;

		.left-box {
			display: flex;
			align-items: center;
			line-height: 20rpx;

			.tab {
				text-align: center;
				margin-left: 30rpx;

				img {
					width: 40rpx;
					height: 40rpx;
					display: block;
				}

				text {
					font-size: 18rpx;
				}
			}
		}

		.right-box {
			display: flex;
			align-items: center;

			.del-btn {
				width: 100rpx;
				height: 50rpx;
				background-color: #FFF6F6;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #EB3536;
				font-size: 24rpx;
				margin-right: 20rpx;
				border-radius: 10rpx;
			}

			.share-btn {
				width: 100rpx;
				height: 50rpx;
				border: 2rpx solid #2CAE6A;
				text-align: center;
				font-size: 24rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 10rpx;
				color: #57C4B8;
				margin-right: 20rpx;
			}

			.add-btn {
				width: 150rpx;
				height: 50rpx;
				background-color: #57BE6B;
				color: #FFFFFF;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 24rpx;
				border-radius: 10rpx;
				margin-right: 20rpx;
			}

			.update-btn {
				width: 100rpx;
				height: 50rpx;
				background-color: #2CAE6A;
				text-align: center;
				font-size: 24rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 10rpx;
				color: #FFFFFF;
				margin-right: 20rpx;
			}
		}
	}
</style>
