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
						<!-- <view class="info-box">
							<view class="title">所有照片</view>
						</view> -->
					</view>
					<!-- 搜索框 -->
					<view class="search-box">
						<view class="search-input-wrapper">
							<image src="@/static/icon/slices/搜索@2x.png" class="search-icon" mode="widthFix"></image>
							<input type="text" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('mineImgSearch', '输入照片名称')" class="search-input" v-model="searchText" @tap="focusField('mineImgSearch')" @focus="focusField('mineImgSearch')" @blur="blurField('mineImgSearch')"
								@input="handleSearch" />
							<view class="search-btn" @click="search">搜索</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view :style="{ paddingTop: totalHeight + 'px' }">
			<view>
				<!-- <view class="tip-box" v-if="state == 0">
					小贴士:长按任意照片，即可进入批量编辑模式
					<img class="closeIcon" src="@/static/icon/close.png" />
				</view> -->
				<!--  -->
				<view>
					<view class="ming-title">所有照片</view>
					<view class="ming-tip">照片已使用空间 40G</view>
				</view>
					<view class="img-content" v-for="(item, index) in picList" :key="getGroupKey(item, index)">
					<view class="time-box">
						<view class="img-time">{{ item.collect_date }}</view>
						<!-- <view class="select-box select-box1" @click="handleRadioClickAll(item)" v-if="state == 1">
							<image src="@/static/icon/check.png" v-if='!item.isChecked' />
							<image src="@/static/icon/checked.png" v-if='item.isChecked' /> 全选
						</view> -->
					</view>
						<view class="img-box" v-for="(pic, idx) in item.pictures" :key="getPictureKey(pic, idx)" v-if="pic && (pic.file_type || pic.picture_url)" @longpress="state = 1">
							<image class="pic" :data-group-index="index" :data-pic-index="idx" @click="toPicDetail(pic, index, idx, $event)" v-if="pic.file_type == 2 && pic.picture_url"
								:src="pic.picture_url + '?x-oss-process=video/snapshot,t_0,f_jpg,w_800,h_600'" lazy-load mode="aspectFill">
							</image>
							<image class="pic" :data-group-index="index" :data-pic-index="idx" @click="toPicDetail(pic, index, idx, $event)" v-if="pic.file_type == 1 && pic.picture_url"
								:src="pic.picture_url" lazy-load mode="aspectFill"></image>
						<template v-if="state == 1">
							<view class="top" v-if="pic.set_top == 0" :data-group-index="index" :data-pic-index="idx" @click="setTop(pic, index, idx, $event)">置顶</view>
							<view class="top" v-if="pic.set_top == 1" :data-group-index="index" :data-pic-index="idx" @click="setTop(pic, index, idx, $event)">取消置顶</view>
							<view class="select-box" :data-group-index="index" :data-pic-index="idx" @click.stop="handleRadioClick(pic, index, idx, $event)">
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
			<view class="left-box" @click="changeState">
				<view v-if="state == 0">
					<image src="@/static/icon/slices/24＊24@2x(12).png" mode=""></image>批量编辑
				</view>
				<view v-if="state == 1">取消</view>
			</view>
			<view class="right-box" @click="handleRightBoxClick">
				<view v-if="state == 1">
					<image src="@/static/icon/slices/trash@2x.png" mode=""></image>删除
				</view>
				<view v-if="state == 0">
					<image src="@/static/icon/slices/24＊24@2x(11).png" mode=""></image>上传
				</view>
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
							<view class="album" v-for="(item, index) in albumList" :key="getAlbumKey(item, index)">
								<view class="album-img" @click="selectAlbum(item, index)">
									<image v-if="item.new_thumb" :src="item.new_thumb" lazy-load mode="aspectFill"></image>
								<image v-else src="/static/image/pic.png" mode="aspectFill"></image>
								<view class="select-box" v-if="item.isChecked">
									<image src="../../static/icon/checked.png" mode=""></image>
								</view>
							</view>
							<view class="album-name">
								{{ item.folder_name }}
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
					输入{{ folder_type == 2 ? '相册' : '文件夹' }}名称
				</view>
				<view class="input-content" style="width: 80%;">
					<input type="text" v-model="folder_name_input" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('mineImgFolderName', '请输入名称')" @tap="focusField('mineImgFolderName')" @focus="focusField('mineImgFolderName')" @blur="blurField('mineImgFolderName')" />
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
import config from '@/common/config'
import Upload from '@/common/request/upload.js';
import { notifyFolderRefresh } from '@/common/helper/refresh.js';
import {
	buildUploadNameFormData,
	normalizeSelectedUploadFile,
	prepareNamedUploadFile,
} from '@/common/helper/uploadName.js';
import {
	buildPictureListForNavigation,
	setPictureNavigationContext,
} from '@/common/helper/pictureNavigation.js';
import { buildListItemKey } from '@/common/helper/listKey.js';
import { getObjectId, resolveClickedListItem, showInvalidRecordToast } from '@/common/helper/clickItem.js';
const uploader = new Upload();
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
			addShow: false,
			inputShow: false,
			folder_type: 1,
			pics: [],
			list: [{
				name: '新建相册',
			},
			{
				name: '新建文件夹',
			},
			],
			folder_name_input: '',
			albumList: [],
			fidList: [],
			option_flag: true,
			showSearch: false,
			searchText: ''
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
		getGroupKey(item, index) {
			return buildListItemKey(item, index, 'pic-group', ['collect_date', 'id']);
		},
		getPictureKey(item, index) {
			return buildListItemKey(item, index, 'pic');
		},
		getAlbumKey(item, index) {
			return buildListItemKey(item, index, 'album');
		},
		getPictureId(item) {
			return getObjectId(item, ['pic_id', 'id']);
		},
		getCollectId(item) {
			return getObjectId(item, ['id', 'collect_id']);
		},
		resolvePictureItem(pic, groupIndex, picIndex, event) {
			if (pic && typeof pic === 'object' && !pic.currentTarget) {
				return pic;
			}
			const dataset = event && event.currentTarget && event.currentTarget.dataset
				? event.currentTarget.dataset
				: {};
			const resolvedGroupIndex = groupIndex !== null && groupIndex !== undefined
				? Number(groupIndex)
				: Number(dataset.groupIndex);
			const resolvedPicIndex = picIndex !== null && picIndex !== undefined
				? Number(picIndex)
				: Number(dataset.picIndex);
			if (Number.isInteger(resolvedGroupIndex) && this.picList[resolvedGroupIndex]) {
				const pictures = this.picList[resolvedGroupIndex].pictures || [];
				return pictures[resolvedPicIndex] || null;
			}
			return null;
		},
		// 处理右侧按钮区域点击事件
		handleRightBoxClick() {
			if (this.state == 1) {
				this.openDel();
			} else {
				this.showUploadMenu();
			}
		},
		toPicDetail(pic, groupIndex, picIndex, event) {
			const current = this.resolvePictureItem(pic, groupIndex, picIndex, event);
			const picId = this.getPictureId(current);
			if (!current || !picId) {
				showInvalidRecordToast();
				return;
			}
			const pictureContext = setPictureNavigationContext(current, this.picList)
			uni.navigateTo({
				url: `/pagesOther/picDetail/picDetail?option_flag=${this.option_flag}&pic_id=${pictureContext.current.pic_id || picId}&source=mineImg&pic_type=3`
			})
		},
		toVideoDetail(pic) {
			uni.setStorageSync('videoInfo', pic)
			uni.navigateTo({
				url: `/pagesOther/videoDetail/videoDetail`
			})
		},

		addToAlbum() {
			let pics = this.pics.map(item => this.getPictureId(item)).filter(Boolean)
			if (!pics.length) {
				showInvalidRecordToast('请选择有效图片')
				return
			}
			const querys = {
				fid: this.fidList[this.fidList.length - 1],
				pic_ids: pics.join(','),
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
					title: '添加成功',
					icon: 'none'
				})
				this.pics = []
				this.state = 0
				this.openShow = false
			})
		},

		selectAlbum(item, index, event) {
			const current = resolveClickedListItem(item, index, event, this.albumList);
			const folderId = getObjectId(current, ['id', 'folder_id', 'fid']);
			if (!current || !folderId) {
				showInvalidRecordToast();
				return;
			}
			if (current.folder_type == 2) {
				this.fidList.push(folderId)
				this.albumList.forEach((item, idx) => {
					if (index == idx) {
						this.albumList[idx].isChecked = true
					} else {
						this.albumList[idx].isChecked = false
					}
				})
			}
			if (current.folder_type == 1) {
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

		getAllPics() {
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
				uni.setStorageSync('picList', buildPictureListForNavigation(res.data.pictures || []))
			})
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

		createAlbum() {
			const querys = {
				folder_type: this.folder_type,
				folder_name: this.folder_name_input,
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
					title: res.msg,
					icon: 'none'
				})
				this.inputShow = false
				notifyFolderRefresh(this.folder_type)
				this.getAlbumList()
			})
		},

		selectTab(e) {
			if (e.name == '新建相册') {
				this.folder_type = 2
			} else {
				this.folder_type = 1
			}
			this.inputShow = true
		},

		addAlbum() {
			this.addShow = true
		},

		//打开添加
		openAdd() {
			if (this.pics.length == 0) {
				uni.showToast({
					title: '请选择要添加的图片',
					icon: 'none'
				})
			} else {
				this.openShow = true
			}
		},

		//删除
		delPic(e) {
			let pics = this.pics.map(item => this.getCollectId(item)).filter(Boolean)
			if (!pics.length) {
				showInvalidRecordToast('请选择有效图片')
				return
			}
			const querys = {
				collect_ids: pics.join(','),
				del_type: e,
				fid: -1,
				timestamp: new Date().getTime(),
			}
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			}
			this.$go('user/all_pics', data, 'post', {
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
		setTop(item, groupIndex, picIndex, event) {
			const current = this.resolvePictureItem(item, groupIndex, picIndex, event);
			const picId = this.getPictureId(current);
			if (!current || !picId) {
				showInvalidRecordToast();
				return;
			}
			const querys = {
				pic_id: picId,
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
				key: this.searchText,
			}
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			}
			this.$go('user/all_pics', data, 'get', {
				show_err: true
			}).then(res => {
				// 直接使用API返回的数据
				this.picList = res.data || [];
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
			if (!item || !Array.isArray(item.pictures)) {
				showInvalidRecordToast()
				return
			}
			if (item.isChecked === false) {
				item.pictures.filter(ele => this.getPictureId(ele)).forEach(ele => {
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

		handleRadioClick(pic, groupIndex, picIndex, event) {
			const current = this.resolvePictureItem(pic, groupIndex, picIndex, event)
			const picId = this.getPictureId(current)
			if (!current || !picId) {
				showInvalidRecordToast()
				return
			}
			if (current.isChecked) {
				current.isChecked = false;
				const selectedIndex = this.pics.findIndex(item => String(this.getPictureId(item)) === String(picId))
				if (selectedIndex !== -1) {
					this.pics.splice(selectedIndex, 1)
				}
			} else {
				current.isChecked = true;
				if (!this.pics.some(item => String(this.getPictureId(item)) === String(picId))) {
					this.pics.push(current)
				}
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

		// 搜索相关方法
		clearSearch() {
			this.searchText = '';
		},

		search() {
			this.getDetail()
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
					}
				},
				fail: (err) => {
				}
			});
		},
		uploadSelectedFile(file, params) {
			const selectedFile = normalizeSelectedUploadFile(file, params.file_type || 1)
			if (!selectedFile.path) {
				return Promise.reject(new Error('文件路径为空'))
			}
			return prepareNamedUploadFile(selectedFile.path, selectedFile.name).then((uploadPath) => {
				return uploader.upload(uploadPath, {
					endpoint: '/api/common/upload',
					formData: {
						...params,
						...buildUploadNameFormData(selectedFile.name)
					},
					showErrorToast: false
				})
			})
		},
		// 上传图片
		uploadImage() {
			let that = this
			uni.chooseImage({
				count: 9,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const tempFiles = res.tempFiles && res.tempFiles.length
						? res.tempFiles
						: (res.tempFilePaths || []).map(path => ({ path }));

					uni.showLoading({
						title: '上传中...',
						mask: true
					});

					let uploadCount = 0;
					const totalCount = tempFiles.length;
					const uploadedUrls = [];
					const failedCount = 0;
					let querys = {
						timestamp: new Date().getTime(),
						file_type: 1,
						collect_flag: 1
					}
					let params = {
							...querys,
							sign: this.$base.getASCII(querys)
						}
						tempFiles.forEach((file, index) => {
						this.uploadSelectedFile(file, params)
							.then((uploadRes) => {
								uploadCount++;
								if (uploadCount === totalCount) {
									uni.hideLoading()
									that.getDetail()
								}
							})
							.catch((err) => {
								console.error('上传失败:', err)
								uploadCount++;
								if (uploadCount === totalCount) {
									uni.hideLoading()
									that.getDetail()
								}
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
						collect_flag: 1
					}
					let params = {
						...querys,
						sign: this.$base.getASCII(querys)
					}

					this.uploadSelectedFile({ ...res, path: tempFilePath }, params)
						.then((uploadRes) => {
							uni.hideLoading();
							that.getDetail()
						})
						.catch((err) => {
							console.error('上传失败:', err)
							uni.hideLoading();
							uni.showToast({
								title: '上传失败',
								icon: 'none'
							})
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
						collect_flag: 1
					}
					let params = {
						...querys,
						sign: this.$base.getASCII(querys)
					}

					tempFiles.forEach((file, index) => {
						this.uploadSelectedFile(file, params)
							.then((uploadRes) => {
								uploadCount++;
								if (uploadCount === totalCount) {
									uni.hideLoading()
									that.getDetail()
								}
							})
							.catch((err) => {
								console.error('上传失败:', err)
								uploadCount++;
								if (uploadCount === totalCount) {
									uni.hideLoading()
									that.getDetail()
								}
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

/* 搜索相关样式 */
.search-icon {
	// padding: 20rpx;
}

.searchImg {
	width: 40rpx;
	height: 40rpx;
}

.closeImg {
	width: 30rpx;
	height: 30rpx;
}

.search-box {
	padding: 20rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background-color: #fff;
	// border-bottom: 1px solid #f0f0f0;
}

.search-input {
	display: flex;
	align-items: center;
	flex: 1;
	background-color: #f5f5f5;
	border-radius: 40rpx;
	padding: 20rpx 30rpx;
	margin-right: 20rpx;
	font-size: 22rpx;
}

.search-input input {
	flex: 1;
	margin: 0 20rpx;
	font-size: 28rpx;
}

.search-btn {
	color: #07c160;
	font-size: 28rpx;
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
		margin-bottom: 30rpx;
		padding-left: 30rpx;
		// 使用flex布局确保图片正确排列
		img {
			display: block;
		}

		.time-box {
			height: 80rpx;
			display: flex;
			align-items: center;
			justify-content: flex-start;
			// padding: 0 30rpx;
			// background-color: #f5f5f5;

			.img-time {
				font-size: 36rpx;
				color: #333333;
				font-weight: bold;
				margin-top: 40rpx;
				margin-bottom: 30rpx;
			}

			.select-box {
				font-size: 22rpx;
				display: flex;
				align-items: center;

				image {
					width: 30rpx;
					height: 30rpx;
					margin-right: 10rpx;
				}
			}

		}

		/* 图片网格容器 */
		.img-box {
			width: calc(25% - 30.5rpx);
    padding-top: calc(25% - 30.5rpx);
			/* 保持宽高比为1:1 */
			display: inline-block;
			position: relative;
			margin-right: 30rpx;
			margin-bottom: 30rpx;
			// 确保图片不会换行
			vertical-align: top;
		}

		/* 修复每行第四个图片右侧无边距的选择器 */
		// .img-box:nth-child(4n) {
		// 	margin-right: 0;
		// }

	.img-box .pic {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		border-radius: 10rpx;
	}

	.top {
		height: 40rpx;
		border-radius: 20rpx;
		background-color: rgba(255, 255, 255, 0.9);
		position: absolute;
		right: 20rpx;
		top: 10rpx;
		font-size: 22rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0 10rpx;
		z-index: 10;
	}

	.videoIcon {
		width: 30rpx;
		height: 25rpx;
		position: absolute;
		left: 10rpx;
		bottom: 10rpx;
		z-index: 10;
	}

	.select-box {
		position: absolute;
		bottom: 5rpx;
		right: 5rpx;
		z-index: 10;

		image {
			width: 30rpx;
			height: 30rpx;
			margin-right: 10rpx;
		}
	}
	.select-box1 {
					bottom: 125rpx;
					right: 25rpx;
				}
}

.bottom-box {
	width: 100%;
	height: 120rpx;
	position: fixed;
	bottom: 0;
	left: 0;
	background-color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 99;

	// border-top: 1px solid #f0f0f0;
	image {
		width: 30rpx;
		height: 30rpx;
		margin-right: 20rpx;
	}

	.left-box {
		display: flex;
		align-items: center;
		// padding-left: 30rpx;
		width: 38%;
		height: 80rpx;
		border-radius: 40rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1rpx solid #999999;
		margin-right: 50rpx;

		.batch-edit-btn {
			color: #333333;
			font-size: 28rpx;
		}

		.cancel-btn {
			color: #333333;
			font-size: 28rpx;
		}
	}

	.right-box {
		display: flex;
		align-items: center;
		// padding-right: 30rpx;
		width: 38%;
		height: 80rpx;
		border-radius: 40rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: #FFD800;
		border: 1rpx solid #FFD800;

		.del-btn {
			padding: 15rpx 40rpx;
			background-color: #FFF6F6;
			color: #EB3536;
			font-size: 28rpx;
			border-radius: 40rpx;
			margin-right: 20rpx;
		}

		.upload-btn {
			padding: 15rpx 40rpx;
			background-color: #ffc600;
			color: #FFFFFF;
			font-size: 28rpx;
			border-radius: 40rpx;
		}
	}
}

/* 搜索框样式 */
.search-box {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 15rpx 30rpx;
	background-color: #FFFFFF;

	image {
		width: 30rpx;
		height: 30rpx;
	}

	.search-input-wrapper {
		flex: 1;
		height: 60rpx;
		background-color: #F5F5F5;
		border-radius: 30rpx;
		display: flex;
		align-items: center;
		padding: 0 20rpx 0 20rpx;
		position: relative;

		.search-icon {
			// margin-right: 10rpx;
		}

		.search-input {
			flex: 1;
			font-size: 28rpx;
			color: #333;
			background-color: transparent;
		}

		.search-btn {
			padding: 0 30rpx;
			height: 60rpx;
			background-color: #FFD700;
			color: #333;
			font-size: 24rpx;
			font-weight: 500;
			border-radius: 30rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0;
			position: absolute;
			right: 0;
			top: 0;
			bottom: 0;
		}
	}
}

.ming-title {
	font-size: 36rpx;
	color: #333333;
	font-weight: bold;
	margin: 30rpx;
	margin-bottom: 20rpx;
}

.ming-tip {
	font-size: 28rpx;
	color: #333333;
	font-weight: 500;
	margin-left: 30rpx;
	margin-bottom: 30rpx;
}
</style>
