<template>
	<view class="page">
		<!-- 头部导航栏，与imgBook.vue保持一致 -->
		<view class="header" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left">
						<view class="back-button" @click="goBack">
							<img class="backIcon" src="@/static/icon/back.png" />
						</view>
						<view class="info-box">
							<view class="title">分类设置</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 设置内容区域 -->
		<view class="content" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="setting-list">
				<view class="setting-item-box">
					<view class="setting-item">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(14).png" mode=""></image>
							联系商家按钮
						</view>
						<view class="switch-container">
							<switch class="setting-switch" :checked="folderInfo.show_connect == 1" @change="toggleContactMerchant($event, 'show_connect')" />
						</view>
					</view>
				</view>

				<view class="setting-item-box">
					<view class="setting-item clickable" @click="editAlbumName">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/24＊24@2x(12).png" mode=""></image>
							修改分类名称
						</view>
						<view class="arrow-container">
							{{ folderInfo.folder_name }}
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>

					<view class="setting-item">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(13).png" mode=""></image>
							置顶该分类
						</view>
						<view class="switch-container">
							<switch class="setting-switch" :checked="folderInfo.set_top == 1" @change="togglePinned" />
						</view>
					</view>
				</view>

				<view class="setting-item-box">
					<view class="setting-item" v-if="folderInfo.uid == userInfo.id">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(12).png" mode=""></image>
							不允许他人分享该分类
						</view>
						<view class="switch-container">
							<switch class="setting-switch" :checked="folderInfo.other_share == 1" @change="toggleDisableSharing" />
						</view>
					</view>

					<view class="setting-item clickable" @click="editAlbumShow" v-if="folderInfo.folder_type == 2">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(11).png" mode=""></image>
							分类布局
						</view>
						<view class="arrow-container">
							{{ folderInfo.pic_layout == 1 ? '小图' : '中图' }}
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>

					<u-action-sheet :actions="actionList" cancelText='取消' :closeOnClickOverlay="true" :title="title" :show="addShow" @select="selectTab" @close='addShow = false'>
					</u-action-sheet>
					<u-action-sheet :actions="privateList" cancelText='取消' :closeOnClickOverlay="true" :title="title" :show="privateShow" @select="selectType" @close='privateShow = false'>
					</u-action-sheet>

					<view class="setting-item" v-if="folderInfo.folder_type == 2">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(11).png" mode=""></image>
							分类显示日期
						</view>
						<view class="switch-container">
							<switch class="setting-switch" :checked="folderInfo.show_upload_date == 1" @change="toggleTime" />
						</view>
					</view>

					<view class="setting-item clickable" v-if="folderInfo.folder_type == 2">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(11).png" mode=""></image>
							根据备注搜索产品
						</view>
						<view class="switch-container">
							<switch class="setting-switch" :checked="folderInfo.show_search == 1" @change="searchByRemark" />
						</view>
					</view>
				</view>

				<view class="setting-item-box">
					<view class="setting-item clickable" @click="editAlbumPrivate" v-if="folderInfo.uid == userInfo.id">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(9).png" mode=""></image>
							分类隐私设置
						</view>
						<view class="arrow-container">
							<text v-if="folderInfo.private_type == 1">公开</text>
							<text v-if="folderInfo.private_type == 2">私密</text>
							<text v-if="folderInfo.private_type == 3">加密</text>
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>

					<view class="setting-item clickable" @click="passwordShow = true" v-if="folderInfo.uid == userInfo.id && folderInfo.private_type == 3">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(9).png" mode=""></image>
							修改分类密码
						</view>
						<view class="arrow-container">
							{{ folderInfo.show_pwd }}
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>

					<view class="setting-item clickable" @click="resertShare" v-if="folderInfo.uid == userInfo.id">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/Frame@2x(9).png" mode=""></image>
							重置分享链接
						</view>
						<view class="arrow-container">
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>
				</view>

				<view class="setting-item-box">
					<view class="setting-item clickable" @click="openDel">
						<view class="setting-label" style="display: flex; align-items: center;">
							<image src="@/static/icon/slices/trash@2x(2).png" mode=""></image>
							删除该分类
						</view>
						<view class="arrow-container">
							<image src="../../static/icon/slices/icon_返回(black)@2x.png" mode=""></image>
						</view>
					</view>
				</view>
			</view>
		</view>

		<u-popup :show="inputShow" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">
					修改名称
				</view>
				<view class="input-content input-wrapper">
					<input type="text" v-model="folder_name_input" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('setPageFolderName', '请输入名称')" @tap="focusField('setPageFolderName')" @focus="focusField('setPageFolderName')" @blur="blurField('setPageFolderName')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="inputShow = false">取消</view>
					<view class="submit" @click="createAlbum">创建</view>
				</view>
			</view>
		</u-popup>

		<u-popup :show="passwordShow" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">
					为这个分类设置密码(四位数字)
				</view>
				<view class="input-content input-wrapper">
					<input type="text" v-model="folder_password" maxlength="4" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('setPagePassword', '请输入密码')" @tap="focusField('setPagePassword')" @focus="focusField('setPagePassword')" @blur="blurField('setPagePassword')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="passwordShow = false">取消</view>
					<view class="submit" @click="submitPassword">创建</view>
				</view>
			</view>
		</u-popup>

		<u-popup :show="delShow" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">提示</view>
				<view class="input-content action-item" style="justify-content: center;" @click="delPic(1)">
					仅删除分类
				</view>
				<view class="input-content action-item danger" style="justify-content: center;" @click="delPic(2)">
					删除分类及内容
				</view>
				<view class="input-content" style="justify-content: center;" @click="delShow = false">
					取消
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
export default {
	data() {
		return {
			statusBarHeight: '',
			totalHeight: '',
			navigationBarHeight: 44,
			// 设置项的状态
			contactMerchant: false,
			pinned: false,
			disableSharing: false,
			folderInfo: {},
			inputShow: false,
			delShow: false,
			folder_name_input: '',
			folder_password: '',
			actionList: [
				{ name: '小图' },
				{ name: '中图' }
			],
			privateList: [
				{ name: '公开' },
				{ name: '私密' },
				{ name: '加密' },
			],
			addShow: false,
			privateShow: false,
			passwordShow: false,
			userInfo: {}
		};
	},
	onLoad() {
		// 获取系统信息，设置头部高度
		const systemInfo = this.$base.getSystemInfoCompat()
		this.statusBarHeight = systemInfo.statusBarHeight
		this.totalHeight = this.statusBarHeight + this.navigationBarHeight
		this.userInfo = uni.getStorageSync('userInfo')
		this.folderInfo = uni.getStorageSync('folderInfo')
	},
	methods: {
		resertShare() {
			uni.showModal({
				title: '请再次确认',
				content: '重置后，将无法使用之前的邀请链接或二维码加入编辑分类。已加入的用户不受影响',
				showCancel: true,
				success: function (res) {
					if (res.confirm) {
					} else if (res.cancel) {
					}
				}
			})
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

		// 返回上一页
		goBack() {
			uni.navigateBack();
		},

		openDel() {
			if (this.folderInfo.uid != this.userInfo.id && this.folderInfo.editer_delete == 0) {
				uni.showToast({
					title: '您没有权限删除该分类',
					icon: 'none'
				})
			} else {
				this.delShow = true
			}
		},

		selectType(e) {
			if (e.name == '公开') {
				this.folderInfo.private_type = 1
				this.submitForm()
			}
			if (e.name == '私密') {
				this.folderInfo.private_type = 2
				this.submitForm()
			}
			if (e.name == '加密') {
				this.passwordShow = true
			}
			this.privateShow = false
		},

		submitPassword() {
			const params = this.buildApiParams({
				fid: this.folderInfo.id,
				pwd: this.folder_password
			});

			this.$go('album/pwd/folder', params, 'post', {
				show_err: true
			})
				.then(res => {
					uni.showToast({
						title: res.msg,
						icon: 'none'
					});
					this.passwordShow = false
					this.folderInfo.show_pwd = this.folder_password
					this.folderInfo.private_type = 3
					this.submitForm()
				})
				.catch(err => { });

		},

		selectTab(e) {
			this.folderInfo.pic_layout = e.name === '小图' ? 1 : 2
		},

		// 隐藏分类
		hideAlbum() {
			uni.showToast({
				title: '隐藏分类',
				icon: 'none'
			});
		},

		// 切换可见性
		visibilityToggle() {
			uni.showToast({
				title: '切换可见性',
				icon: 'none'
			});
		},

		// 切换联系商家按钮
		toggleContactMerchant(e, state) {
			this.folderInfo[state] = e.detail.value ? 1 : 0
			this.submitForm()
		},

		createAlbum() {
			this.folderInfo.folder_name = this.folder_name_input
			this.submitForm()
			this.inputShow = false
		},

		normalizeUploadField(value) {
			if (Array.isArray(value)) {
				return value.length ? JSON.stringify(value) : ''
			}
			if (typeof value === 'string') {
				const trimmed = value.trim()
				return trimmed === '[]' ? '' : trimmed
			}
			return ''
		},

		submitForm() {
			const uploadField = this.normalizeUploadField(this.folderInfo.upload_field)
			const data = {
				show_connect: this.folderInfo.show_connect,
				folder_name: this.folderInfo.folder_name,
				set_top: this.folderInfo.set_top,
				other_share: this.folderInfo.other_share,
				show_upload_date: this.folderInfo.show_upload_date,
				show_search: this.folderInfo.show_search,
				upload_field: uploadField,
				editer_create: this.folderInfo.editer_create,
				editer_delete: this.folderInfo.editer_delete,
				editer_delete_pic: this.folderInfo.editer_delete_pic,
				private_type: this.folderInfo.private_type,
				pic_layout: this.folderInfo.pic_layout,
				sort_type: '',
				fid: this.folderInfo.id

			}
			this.folderInfo.upload_field = uploadField

			const params = this.buildApiParams({
				...data,
				fid: this.folderInfo.id
			});


			this.$go('album/update/folder', params, 'post', {
				show_err: true
			})
				.then(res => {
					uni.showToast({
						title: res.msg || '设置成功',
						icon: 'none'
					});
				})
				.catch(err => { });
		},

		// 查看示例分类
		viewExampleAlbum() {
			uni.navigateTo({
				url: '/pagesOther/imgBook/imgBook'
			});
		},

		// 修改分类名称
		editAlbumName() {
			this.inputShow = true
			this.folder_name_input = this.folderInfo.folder_name
		},

		// 切换置顶
		togglePinned(e) {
			this.folderInfo.set_top = e.detail.value ? 1 : 0
			this.submitForm()
		},

		// 根据备注搜索产品
		searchByRemark(e) {
			let userInfo = uni.getStorageSync('userInfo')
			if (userInfo.grade_level == 0) {
				uni.showToast({
					title: '请先升级成为会员',
					icon: 'none'
				})
			} else {
				this.folderInfo.show_search = e.detail.value ? 1 : 0
				this.submitForm()
			}

		},

		delPic(state) {


			const params = this.buildApiParams({
				del_type: state,
				fid: this.folderInfo.id
			});

			this.$go('album/delete/folder', params, 'post', {
				show_err: true
			})
				.then(res => {
					uni.showToast({
						title: res.msg || '删除成功',
						icon: 'none'
					});
					setTimeout(() => {
						uni.navigateBack({ delta: 2 })
					}, 1000)

				})
				.catch(err => { });
		},

		toggleEditerCreate(e) {
			this.folderInfo.editer_create = e.detail.value ? 1 : 0
			this.submitForm()
		},

		toggleEditerDel(e) {
			this.folderInfo.editer_delete = e.detail.value ? 1 : 0
			this.submitForm()
		},

		toggleEditerDelPic(e) {
			this.folderInfo.editer_delete_pic = e.detail.value ? 1 : 0
			this.submitForm()
		},

		toggleTime(e) {
			this.folderInfo.show_upload_date = e.detail.value ? 1 : 0
			this.submitForm()
		},

		editAlbumPrivate() {
			this.privateShow = true
		},

		editAlbumShow() {
			this.addShow = true
		},

		// 切换禁止分享
		toggleDisableSharing(e) {
			this.folderInfo.other_share = e.detail.value ? 1 : 0
			this.submitForm()
		},

		// 显示设置
		showDisplaySettings() {
			uni.showToast({
				title: '分类显示设置',
				icon: 'none'
			});
		},

		// 显示编辑设置
		showEditSettings() {
			uni.showToast({
				title: '分类编辑设置',
				icon: 'none'
			});
		},

		// 显示安全设置
		showSecuritySettings() {
			uni.navigateTo({
				url: '/pagesOther/addSetInfo/addSetInfo'
			})
		},

		// 删除分类
		deleteAlbum() {
			uni.showModal({
				title: '删除分类',
				content: '确定要删除该分类吗？删除后将无法恢复。',
				confirmText: '删除',
				confirmColor: '#EB3536',
				success: (res) => {
					if (res.confirm) {
						uni.showToast({
							title: '分类删除成功',
							icon: 'success'
						});
						// 延迟返回上一页
						setTimeout(() => {
							uni.navigateBack();
						}, 1500);
					}
				}
			});
		},
	}
};
</script>

<style lang="scss" scoped>
.popBox {
	width: 600rpx;
	border-radius: 20rpx;
	overflow: hidden;

	.pop-title {
		width: 100%;
		height: 100rpx;
		border-bottom: 2rpx solid #f6f6f6;
		text-align: center;
		line-height: 100rpx;
	}

	.input-content {
		width: 100%;
		height: 80rpx;
		font-size: 26rpx;
		display: flex;
		align-items: center;
		border-bottom: 2rpx solid #f6f6f6;
		background-color: #fff;

		&.input-wrapper {
			width: 540rpx;
			margin: 20rpx auto 0;
			background-color: #f8f3f3;
			border-radius: 10rpx;
			padding-left: 20rpx;
			box-sizing: border-box;
			border-bottom: none;

			input {
				font-size: 30rpx;
			}
		}

		&.action-item {
			color: #759eeb;

			&.danger {
				color: #e02d23;
			}
		}
	}

	.btn-box {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100rpx;
		border-top: 2rpx solid #f6f6f6;
		margin-top: 20rpx;

		.cancel,
		.submit {
			width: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.cancel {
			border-right: 2rpx solid #f6f6f6;
		}

		.submit {
			color: #7590e8;
		}
	}
}

/* 页面容器 */
.page {
	min-height: 100vh;
	box-sizing: border-box;
	min-height: 100vh;
	padding-bottom: 200rpx;
	background-color: #fff;
}

/* 头部样式，与imgBook.vue保持一致 */
.header {
	width: 100%;
	background-size: 100%;
	box-sizing: border-box;
	position: fixed;
	top: 0;
	left: 0;
	z-index: 99;

	.custom-nav-bar {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		color: #000;
		// border-bottom: 2rpx solid #eee;
		background-color: #FFFFFF;

		.nav-bar-content {
			padding: 0 10px;
			display: flex;
			align-items: center;
			justify-content: space-between;

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
					font-size: 28rpx;
					color: #000000;
				}

				.nums {
					color: #AAAAAA;
					font-size: 22rpx;
				}
			}

			.right-icon-group {
				display: flex;
				align-items: center;

				.icon-item {
					margin-left: 30rpx;
					font-size: 32rpx;
					color: #000;
				}

				.more-icon {
					font-size: 36rpx;
					line-height: 44px;
				}
			}
		}
	}
}

/* 内容区域 */
.content {
	padding-bottom: 20rpx;
	background-color: #fff;
}

/* 设置列表 */
.setting-list {
	margin-top: 20rpx;

	.setting-item-box {
		width: 94%;
		border-radius: 20rpx;
		background-color: #F5F5F5;
		overflow: hidden;
		margin-left: 3%;
		margin-bottom: 30rpx;
	}
}

.tip-text {
	font-size: 26rpx;
	margin-top: 20rpx;
	margin-bottom: 20rpx;
	margin-left: 30rpx;
	color: #000;
	font-weight: bold;
}

/* 设置项 */
.setting-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 30rpx;
	border-bottom: 2rpx solid #f0f0f0;
	background-color: #F5F5F5;

	&.clickable {
		cursor: pointer;
		transition: background-color 0.2s;

		&:active {
			background-color: #f5f5f5;
		}
	}

	.setting-label {
		font-size: 28rpx;
		color: #000;

		image {
			width: 40rpx;
			height: 40rpx;
			margin-right: 10rpx;
		}

		.top-label {
			font-size: 28rpx;
			color: #000;
		}

		.bottom-des {
			font-size: 22rpx;
			color: #737373;
			margin-top: 6rpx;
		}
	}

	.setting-hint {
		position: absolute;
		top: 60rpx;
		left: 30rpx;
		font-size: 22rpx;
		color: #AAAAAA;
	}

	/* 开关容器 */
	.switch-container {
		transform: scale(0.8);
	}

	/* 箭头容器 */
	.arrow-container {
		color: #AAAAAA;
		font-size: 22rpx;
		display: flex;
		align-items: center;

		image {
			width: 30rpx;
			height: 30rpx;
			margin-left: 20rpx;
		}
	}

}

/* 特殊的设置项 - 删除 */
.delete-item {
	padding-top: 40rpx;
	padding-bottom: 40rpx;
}

.delete-label {
	font-size: 28rpx;
	color: #EB3536;
}

.del-btn {
	text-align: center;
	color: #E02D23;
	margin-top: 30rpx;
}

.warning-btn {
	text-align: center;
	color: #7B7B7B;
	margin-top: 30rpx;
	font-size: 22rpx;
}
</style>
