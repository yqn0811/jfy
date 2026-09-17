<template>
	<view class="page" :style="{ paddingTop: totalHeight + 20 + 'px' }">
		<!-- 全屏遮罩层 -->
		<view class="mask" v-if="showMask" @click="hideMask">
			<view class="select-box">
				<view class="select-item" @click="openUploadBox(2)">
					<image src="@/static/icon/slices/Frame 1000004377@2x.png" class="upload-icon" alt="" />
					<view class="upload-text">新建相册</view>
				</view>
				<view class="select-item" @click="openUploadBox(1)" v-if="this.level < 4">
					<image src="@/static/icon/slices/Frame 1000004894@2x.png" class="upload-icon" alt="" />
					<view class="upload-text">新建文件夹</view>
				</view>
			</view>
		</view>
		<!-- 顶部导航栏 -->
		<view class="header" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left">
						<view class="back-button" @click="back" v-if="hasPrePage">
							<img class="backIcon" src="@/static/icon/back.png" />
						</view>
						<view class="back-button" @click="toHome" v-if="!hasPrePage">
							<img class="backIcon" src="@/static/icon/toHome.png" />
						</view>
						<view class="info-box">
							<view class="title">{{ folder_name }}</view>
							<view class="nums">{{ visit_times }}次访问</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view class="search-box">
			<view class="search-left">
				<image src="@/static/icon/slices/搜索@2x.png" class="search-icon" alt="" />
				<input type="text" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('fileSearch', '输入照片名称')" v-model="searchText" class="search-input" @tap="focusField('fileSearch')" @focus="focusField('fileSearch')" @blur="blurField('fileSearch')" />
			</view>
			<view class="search-btn" @click="searchList">搜索</view>
		</view>

		<view class="tabs">
			<view class="tab" :class="{ 'activeTab': tabIndex == 0 }" @click="changeTab(0)">全部 {{ total_num }}</view>
			<view class="tab" :class="{ 'activeTab': tabIndex == 2 }" @click="changeTab(2)">共享相册 {{ total_album }}</view>
			<view class="tab" :class="{ 'activeTab': tabIndex == 1 }" @click="changeTab(1)">文件夹 {{ total_folder }}</view>
		</view>

		<!-- 文件/相册列表 -->
		<view class="content" v-if="albumList.length > 0 && passwordShow == false">
			<view class="album-list">
				<view class="album-item" v-for="(item, index) in albumList" :key="index" @click="toDetail(item)">
					<view class="album-cover">
						<image v-if="item.folder_type == 1" src="../../static/icon/slices/x@2x.png" mode="aspectFill">
						</image>
						<view class="aspectFill-bg" v-else-if="item.new_thumb">
							<image :src="item.new_thumb + '?x-oss-process=video/snapshot,t_0,f_jpg,w_180,h_360'"
								mode="aspectFill"></image>
						</view>

						<image v-else src="../../static/image/pic.png" mode="aspectFill"></image>
					</view>
					<view class="album-info">
						<view class="album-name">{{ item.folder_name }}</view>
						<view class="album-count">{{ item.son_count }}{{ item.folder_type == 1 ? '个文件夹' : '张照片' }}
						</view>
					</view>
				</view>
			</view>
		</view>

		<image class="add-pic" src="@/static/icon/slices/Frame 1000004375@2x.png" @click="showUploadMenu" />

		<!-- 底部操作栏 -->
		<view class="bottom-box" v-if="is_follow == -1 && option_flag">
			<view class="left-box">
				<!-- 设置按钮 -->
				<view class="tab" v-if="state == 0" @click="toSet">
					<img src="@/static/icon/setting.png" alt="" />
					<text>设置</text>
				</view>
				<button open-type="share"
					v-if="userInfo.grade_level > 0 && folderInfo.private_type != 2 && (folderInfo.other_share == 0 || folderInfo.other_share == 1 && folderInfo.uid == userInfo.id)"
					@click="setShareType('invite')">
					<view class="tab" v-if="state === 0">
						<img src="@/static/icon/invent.png" alt="" />
						<text>邀请</text>
					</view>
				</button>
			</view>
			<view class="right-box">
				<!-- 分享按钮 -->
				<button open-type="share" class="share-btn"
					v-if="state == 0 && folderInfo.private_type != 2 && (folderInfo.other_share == 0 || folderInfo.other_share == 1 && folderInfo.uid == userInfo.id)"
					@click="setShareType('share')">
					<image src="@/static/icon/slices/share@2x.png" class="share-icon" alt="" />
					分享
				</button>
				<!-- 新建按钮 -->
				<view class="update-btn" v-if="state == 0" @click="showUploadMenu">
					<image src="@/static/icon/slices/24＊24@2x(7).png" class="add-icon" alt="" />
					新建
				</view>
			</view>
		</view>

		<view class="pop-concact" @click="toConcact" v-if="folderInfo.show_connect == 1">
			<view class="headUrl">
				<image :src="userInfo.avatar || '/static/image/headurl.jpg'" mode="aspectFill"></image>
			</view>
			<view class="concact-info">
				<view class="nickname">
					{{ userInfo.nickname }}
				</view>
				<view class="concact-us">
					联系商家
				</view>
			</view>
		</view>

		<!-- 密码输入弹窗 -->
		<u-popup :show="passwordShow" mode="center" :round="10" :safe-area-inset-bottom="false">
			<view class="popBox">
				<view class="pop-title">输入文件密码</view>
				<view class="input-content input-wrapper">
					<input type="text" v-model="password" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('filePassword', '请输入密码')" @tap="focusField('filePassword')" @focus="focusField('filePassword')" @blur="blurField('filePassword')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="back">取消</view>
					<view class="submit" @click="submitPassword">确定</view>
				</view>
			</view>
		</u-popup>

		<!-- 新建文件夹/相册弹窗 -->
		<u-popup :show="show" mode="center" :round="10" :safe-area-inset-bottom="false">
			<view class="popBox">
				<view class="pop-title">
					<image v-if="folder_type == 2" src="/static/icon/slices/Frame 1000004895@2x.png" class="title-icon"
						mode="aspectFit"></image>
					<image v-else src="/static/icon/slices/Frame 1000004394@2x.png" class="title-icon" mode="aspectFit">
					</image>
					<view class="title-text">新建{{ folder_type == 2 ? '相册' : '文件夹' }}</view>

				</view>

				<view class="input-content">
					<input type="text" v-model="folder_name_input" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('fileFolderName', '请输入名称')" @tap="focusField('fileFolderName')" @focus="focusField('fileFolderName')" @blur="blurField('fileFolderName')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="cancelPop">取消</view>
					<view class="submit" @click="createAlbumApi">确定</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import {
	getMiniCode
} from '@/common/request/api.js';
import { notifyFolderRefresh } from '@/common/helper/refresh.js';
export default {
	data() {
		return {
			// 导航栏相关
			statusBarHeight: '',
			totalHeight: '',
			navigationBarHeight: 44,

			// 页面状态
			state: 0, // 0: 正常模式, 1: 编辑模式
			isChecked: false,

			// 文件夹信息
			folder_name: '',
			fid: '',
			visit_times: 0,

			// 列表数据
			page: 1,
			albumList: [],

			// 弹窗相关
			show: false,
			passwordShow: false,
			folder_type: 1, // 1: 文件夹, 2: 相册
			folder_name_input: '',

			// 访问控制
			is_follow: -1,
			need_pwd: 0,
			user_pwd: '',
			password: '',

			// 分享相关
			share_str: '',
			link_share_str: '',
			level: 0,
			
			// 其他
			folderInfo: {},
			getCode: 0,
			share_uid: '',
			shareType: '',
			option_flag: '',
			userInfo: {},
			hasPrePage: false,
			is_from_visit: false,
			baseInfo: {},
			searchText: '',
			tabIndex: 0,
			showMask: false,
			total_album: 0,
			total_folder: 0,
			total_num: 0,
		};
	},

	onLoad(options) {
		if(options.level == 4){
			this.level = options.level
		}
		const pages = getCurrentPages();
		this.hasPrePage = pages.length > 1;
		this.baseInfo = uni.getStorageSync('baseInfo')
		getMiniCode().then(res => {
			// 初始化页面参数
			this.folder_name = decodeURIComponent(options.folder_name)
			this.fid = options.id;
			this.visit_times = options.visit_times;
			this.link_share_str = options.share_str;
			this.share_uid = options.share_uid
			this.is_from_visit = options.is_from_visit || false;

			// 设置导航栏高度
			const systemInfo = this.$base.getSystemInfoCompat();
			this.statusBarHeight = systemInfo.statusBarHeight;
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight;

			// 加载数据
			this.getCode = 1
			this.albumList = [];
			if (options.is_follow == 0) {
				this.is_follow = 0
				this.getExample();
				this.getUserInfo()
			} else {
				this.is_follow = -1;
				this.getList();
				this.addVisit();
				this.getUserInfo()
			}
		})
	},

	onShow() {
		// 重置并刷新列表
		if (this.getCode == 1) {
			this.page = 1;
			this.albumList = [];

			if (this.is_follow == -1) {
				this.getList();
			} else {
				this.getExample();
			}
		}

	},

	onReachBottom() {
		// TODO: 实现上拉加载更多
	},


	onShareAppMessage() {
		let path = ''
		let title = ''
		const encodedFolderName = encodeURIComponent(this.folder_name)
		if (this.shareType == 'invite') {
			title = '邀请你一起加入我的相册！'
			path = `/pagesOther/filePage/filePage?id=${this.fid}&share_str=${this.share_str}&folder_name=${encodedFolderName}&share_uid=${this.folderInfo.uid}`
		}
		if (this.shareType == 'share') {
			title = '分享了相册给你,来看看吧',
				path = `/pagesOther/filePage/filePage?id=${this.fid}&share_str=${this.share_str}&folder_name=${encodedFolderName}`
		}
		console.log(path, 'path')
		return {
			title: title,
			path: path,
			imageUrl: this.baseInfo.share_thumb
		}
	},


	methods: {
		openUploadBox(type) {
			this.folder_type = type
			this.show = true
		},

		changeTab(index) {
			this.tabIndex = index
			this.page = 1;
			this.albumList = [];
			this.getList()
		},

		searchList() {
			this.page = 1;
			this.albumList = [];
			this.getList()
		},

		isMp4(url) {
			if (!url) return false;
			// 基于后缀简单判断，兼容带查询参数的情况
			const lower = url.toLowerCase(); return lower.includes('.mp4');
		},

		toHome() {
			uni.reLaunch({
				url: '/pages/index/index'
			})
		},

		buildRequestData(querys) {
			return {
				...querys,
				sign: this.$base.getASCII(querys)
			}
		},

		getUserInfo() {
			const querys = {
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)

			this.$go('user/show_info', data, 'get', {
				show_err: true
			})
				.then(res => {
					this.userInfo = res.data
				})
		},

		setShareType(type) {
			this.shareType = type
		},
		/**
		 * 提交密码验证
		 */
		submitPassword() {
			if (this.password == this.folderInfo.show_pwd) {
				this.passwordShow = false;
				const querys = {
					fid: this.fid,
					timestamp: new Date().getTime()
				}
				const data = this.buildRequestData(querys)

				this.$go('album/user/visit/pwd', data, 'post', {
					show_err: true
				})
			} else {
				uni.showToast({
					title: '密码错误',
					icon: 'none'
				})
			}
		},

		/**
		 * 获取示例数据
		 */
		getExample() {
			const querys = {
				fid: this.fid,
				is_follow: 0,
				timestamp: new Date().getTime(),
			};
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			};

			this.$go('common/example/folder', data, 'get', {
				show_err: true
			}).then(res => {
				this.albumList = this.albumList.concat(res.data.lists.data);
			});
		},

		/**
		 * 添加访问记录
		 */
		addVisit() {
			const querys = {
				fid: this.fid,
				timestamp: new Date().getTime(),
			};
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			};

			this.$go('album/visit/folder', data, 'post', {
				show_err: true
			}).then(res => {
				// 访问记录添加成功
			});
		},

		/**
		 * 跳转到详情页
		 */
		toDetail(item) {
			if (item.folder_type == 2) {
				// 相册类型
				uni.navigateTo({
					url: `/pagesOther/imgBook/imgBook?id=${item.id}&folder_name=${item.folder_name}&folder_type=2&visit_times=${item.visit_times}&is_follow=${this.is_follow}`
				});
			}

			if (item.folder_type == 1) {
				// 文件夹类型
				// if (item.level == 5) {
				// 	// 最后一级，跳转到相册
				// 	uni.navigateTo({
				// 		url: `/pagesOther/imgBook/imgBook?id=${item.id}&folder_name=${item.folder_name}&folder_type=2&visit_times=${item.visit_times}&is_follow=${this.is_follow}`
				// 	});
				// } else {
				// 	// 跳转到下一级文件夹
				// 	uni.navigateTo({
				// 		url: `/pagesOther/filePage/filePage?id=${item.id}&folder_name=${item.folder_name}&folder_type=1&visit_times=${item.visit_times}&is_follow=${this.is_follow}&level=${item.level}`
				// 	});
				// }
				uni.navigateTo({
					url: `/pagesOther/filePage/filePage?id=${item.id}&folder_name=${item.folder_name}&folder_type=1&visit_times=${item.visit_times}&is_follow=${this.is_follow}&level=${item.level}`
				});
			}
		},

		/**
		 * 取消弹窗
		 */
		cancelPop() {
			this.show = false;
		},

		/**
		 * 获取文件夹列表
		 */
		getList() {
			const querys = {
				fid: this.fid,
				page: this.page,
				share_uid: this.share_uid || '',
				size: 10,
				timestamp: new Date().getTime(),
				key: this.searchText || '',
				folder_type: this.tabIndex,
			};

			if (this.link_share_str) {
				querys.link_share_str = this.link_share_str;
			}

			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			};

			// 根据is_from_visit参数动态选择接口
			const api = this.is_from_visit ? 'user/visit/pics' : 'album/lists/folder';

			this.$go(api, data, 'post', {
				show_err: true
			}).then(res => {
				// 保存文件夹信息
				uni.setStorageSync('folderInfo', res.data.folder_info);
				this.folderInfo = res.data.folder_info
				// 设置密码相关信息
				this.option_flag = res.data.option_flag
				this.need_pwd = res.data.need_pwd;
				this.user_pwd = res.data.user_pwd;
				this.share_str = res.data.share_str;
				this.total_album = res.data.total_album;
				this.total_folder = res.data.total_folder;
				this.total_num = res.data.total_num;

				// 如果需要密码，显示密码输入框
				if (this.need_pwd == 1) {
					this.passwordShow = true;
				}

				// 控制分享按钮显示
				if ((res.data.folder_info.other_share == 1 && this.folderInfo.uid != this.userInfo.id) || this.folderInfo.private_type == 2) {
					wx.hideShareMenu(); // 隐藏分享按钮
				} else {
					wx.showShareMenu(); // 显示分享按钮
				}

				// 更新列表数据
				if (this.page == 1) {
					this.albumList = res.data.lists.data;
				} else {
					this.albumList = this.albumList.concat(res.data.lists.data);
				}
			});
		},

		/**
		 * 返回上一页
		 */
		back() {
			uni.navigateBack();
		},

		/**
		 * 跳转到设置页
		 */
		toSet() {
			uni.navigateTo({
				url: '/pagesOther/setPage/setPage'
			});
		},

		/**
		 * 切换选中状态
		 */
		handleRadioClick() {
			this.isChecked = !this.isChecked;
		},

		/**
		 * 切换编辑状态
		 */
		changeState() {
			this.state = this.state == 0 ? 1 : 0;
		},

		/**
		 * 显示新建菜单
		 */
		showUploadMenu() {
			if (this.folderInfo.uid != this.userInfo.id && this.folderInfo.editer_create == 0) {
				uni.showToast({
					title: '您没有权限新建相册',
					icon: 'none'
				})
			} else {
				this.showMask = true; // 显示遮罩层
				
			}
		},

		/**
		 * 隐藏遮罩层
		 */
		hideMask() {
			this.showMask = false;
		},

		/**
		 * 创建文件夹/相册
		 */
		createAlbumApi() {
			const querys = {
				folder_type: this.folder_type,
				folder_name: this.folder_name_input,
				fid: this.fid,
				timestamp: new Date().getTime(),
			};
			const data = {
				...querys,
				sign: this.$base.getASCII(querys)
			};

			this.$go('album/create/folder', data, 'post', {
				show_err: true
			}).then(res => {
				uni.showToast({
					title: res.msg,
					icon: 'none'
				});
				this.folder_name_input = '';
				this.show = false;
				notifyFolderRefresh(this.folder_type);
				this.getList();
			});
		}
	}
};
</script>

<style lang="scss" scoped>
/* 重置按钮样式 */
button {
	background: none;
	border: none;
	box-shadow: none;
	padding: 0;
	margin: 0;

	&::after {
		border: 0;
	}
}

.pop-concact {
	position: fixed;
	right: 0;
	bottom: 200rpx;
	width: 260rpx;
	height: 80rpx;
	background-color: #F5F5F5;
	border-top-left-radius: 30rpx;
	border-bottom-left-radius: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-sizing: border-box;

	.headUrl {
		width: 60rpx;
		height: 60rpx;
		border-radius: 50%;
		margin-right: 20rpx;
		overflow: hidden;

		image {
			width: 100%;
			height: 100%;
		}
	}

	.concact-info {
		.nickname {
			font-size: 26rpx;
		}

		.concact-us {
			font-size: 24rpx;
			color: #29AD68;
		}
	}
}

/* 页面容器 */
.page {
	background-color: #fff;
	min-height: 100vh;
	padding-bottom: 200rpx;
	box-sizing: border-box;
}

/* 顶部导航栏 */
.header {
	width: 100%;
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

		.nav-bar-content {
			padding: 0 10px;
			width: 520rpx;
			display: flex;
			align-items: center;
			justify-content: space-between;

			.left {
				display: flex;
				align-items: center;

				.back-button {
					margin-right: 20rpx;

					.backIcon {
						width: 50rpx;
						height: 50rpx;
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

			.edit-btn {
				color: #57BE6B;
				font-size: 24rpx;
				border: 2rpx solid #29AD68;
				padding: 4rpx 6rpx;
				border-radius: 6rpx;
			}
		}
	}
}

.search-box {
	width: 686rpx;
	height: 72rpx;
	border-radius: 48rpx 48rpx 48rpx 48rpx;
	background: rgba(0, 0, 0, 0.05);
	margin: 0 auto;
	display: flex;
	align-items: center;
	justify-content: space-between;

	.search-left {
		display: flex;
		align-items: center;
		flex: 1;

		.search-icon {
			width: 32rpx;
			height: 32rpx;
			margin-left: 16rpx;
		}

		.search-input {
			font-size: 30rpx;
			color: #000000;
			margin-left: 8rpx;
			flex: 1;
		}
	}

	.search-btn {
		width: 128rpx;
		height: 64rpx;
		background: #FFE329;
		border-radius: 48rpx 48rpx 48rpx 48rpx;
		font-size: 32rpx;
		color: #333333;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 4rpx;
	}
}

.tabs {
	display: flex;
	align-items: center;
	margin-top: 32rpx;
	padding-left: 32rpx;
	box-sizing: border-box;

	.tab {
		font-size: 24rpx;
		color: #999999;
		margin-right: 32rpx;
	}

	.activeTab {
		background: #333333;
		font-size: 24rpx;
		color: #FFFFFF;
		padding: 12rpx 16rpx;
		box-sizing: border-box;
		border-radius: 16rpx 16rpx 16rpx 16rpx;
		margin-right: 32rpx;
	}
}

/* 弹窗样式 */
.popBox {
	width: 600rpx;
	background-color: #FFFFFF;
	border-radius: 20rpx;

	.pop-title {
		width: 100%;
		// height: 100rpx;
		// display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		line-height: 100rpx;
		font-size: 32rpx;
		font-weight: 500;
		color: #333;
		position: relative;
	}

	.title-icon {
		width: 80rpx;
		height: 80rpx;
		margin-right: 10rpx;
		vertical-align: middle;
	}

	.pop-subtitle {
		width: 100%;
		height: 60rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		font-size: 28rpx;
		color: #666;
		margin-bottom: 20rpx;
	}

	.input-content {
		width: 540rpx;
		height: 80rpx;
		margin: 0 auto;
		/* background-color: #f8f3f3 ; */
		border-radius: 10rpx;
		display: flex;
		align-items: center;
		padding-left: 20rpx;
		box-sizing: border-box;
		border-bottom: 1rpx solid #ccc;
		border-radius: 0;

		input {
			font-size: 30rpx;
			flex: 1;
		}
	}

	.btn-box {
		display: flex;
		align-items: center;
		justify-content: space-around;
		width: 100%;
		margin-top: 30rpx;
		padding: 0 40rpx 30rpx 40rpx;
		box-sizing: border-box;
	}

	.btn-box .cancel {
		width: 220rpx;
		height: 80rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 32rpx;
		color: #666;
		background-color: #F5F5F5;
		border-radius: 40rpx;
	}

	.btn-box .submit {
		width: 220rpx;
		height: 80rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #FFFFFF;
		font-size: 32rpx;
		font-weight: 600;
		background-color: #FFD700;
		border-radius: 40rpx;
	}
}

/* 内容区域 */
.content {
	.album-list {
		padding: 30rpx;
		display: flex;
		flex-wrap: wrap;
		// gap: 30rpx;
	}

	.album-item {
		width: calc(33.33% - 55rpx);
		margin-right: 55rpx;
		margin-bottom: 55rpx;
		box-sizing: border-box;
	}

	.album-item:nth-child(3n) {
		margin-right: 0;
	}

	.album-cover {
		width: 100%;
		height: 140rpx;
		border-radius: 16rpx;
		overflow: hidden;
		// background-color: #F5F5F5;

		image {
			width: 100%;
			height: 100%;
		}

		.aspectFill-bg {
			background-image: url('../../static/icon/slices/x@2x(1).png');
			background-size: cover;
			background-position: center;
			width: 100%;
			height: 100%;

			image {
				width: 77%;
				height: 68%;
				margin-top: 13%;
				margin-left: 11%;
			}
		}
	}

	.album-info {
		margin-top: 20rpx;
	}

	.album-name {
		font-size: 28rpx;
		font-weight: 600;
		color: #333333;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		margin-bottom: 8rpx;
	}

	.album-count {
		font-size: 22rpx;
		color: #999999;
		display: flex;
		align-items: center;

		image {
			width: 24rpx;
			height: 24rpx;
			margin-left: 10rpx;
		}
	}
}

.add-pic {
	position: fixed;
	bottom: 340rpx;
	right: 30rpx;
	width: 128rpx;
	height: 128rpx;
	border-radius: 50%;
	background-color: #FFD800;
	display: flex;
	align-items: center;
	justify-content: center;
}

/* 底部操作栏 */
.bottom-box {
	width: 100%;
	height: 140rpx;
	position: fixed;
	bottom: 0;
	left: 0;
	background-color: #fff;
	display: flex;
	align-items: start;
	padding-bottom: 40rpx;
	box-sizing: border-box;
	justify-content: space-between;

	.left-box {
		display: flex;
		align-items: center;
		line-height: 30rpx;

		.tab {
			text-align: center;
			margin-left: 50rpx;
			line-height: 30rpx;

			img {
				width: 50rpx;
				height: 50rpx;
				display: block;
			}

			text {
				font-size: 24rpx;
			}
		}
	}

	.right-box {
		display: flex;
		align-items: center;

		.share-btn {
			width: 191rpx;
			height: 96rpx;
			border: 1rpx solid #999999;
			border-radius: 96rpx 96rpx 96rpx 96rpx;
			font-size: 32rpx;
			color: #333333;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 20rpx;

			.share-icon {
				width: 48rpx;
				height: 48rpx;
				margin-right: 10rpx;
			}
		}

		.update-btn {
			width: 191rpx;
			height: 96rpx;
			background: #FFD800;
			border-radius: 96rpx 96rpx 96rpx 96rpx;
			font-size: 32rpx;
			color: #333333;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 30rpx;

			.add-icon {
				width: 48rpx;
				height: 48rpx;
				margin-right: 10rpx;
			}
		}
	}
}

/* 遮罩层样式 */
.mask {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.5);
	z-index: 999;

	.select-box {
		position: absolute;
		bottom: 140rpx;
		right: 30rpx;
		width: 686rpx;
		height: 296rpx;
		background-color: #fff;
		border-radius: 40rpx 40rpx 40rpx 40rpx;
		display: flex;
		align-items: center;
		justify-content: space-around;

		.select-item {
			width: 300rpx;
			height: 232rpx;
			background: #F2F2F2;
			border-radius: 24rpx 24rpx 24rpx 24rpx;
			padding-top: 46rpx;
			box-sizing: border-box;

			.upload-icon {
				width: 96rpx;
				height: 84rpx;
				margin: 0 auto;
				display: block;
			}

			.upload-text {
				font-weight: bold;
				font-size: 32rpx;
				color: #333333;
				text-align: center;
				margin-top: 18rpx;
			}
		}
	}
}
</style>
