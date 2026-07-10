<template>
	<view class="page">
		<!-- 自定义导航栏 -->
		<view class="header" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="custom-nav-bar" :style="{ height: totalHeight + 'px' }">
				<view :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="nav-bar-content" :style="{ height: navigationBarHeight + 'px' }">
					<view class="left" v-if="state === 0">
						<view class="back-button" @click="back" v-if="hasPrePage">
							<img class="backIcon" src="@/static/icon/back.png" />
						</view>
						<view class="back-button" @click="toHome" v-if="!hasPrePage">
							<img class="backIcon" src="@/static/icon/toHome.png" />
						</view>
						<view class="info-box">
							<view class="title">{{ folder_name }}</view>
						</view>
					</view>
					<view class="left" v-if="state === 1" @click="state = 0">
						<view class="back-button">
							<img class="backIcon" src="@/static/icon/slices/quxiao.png" />
						</view>
						<view class="info-box">
							<view class="title">取消</view>
						</view>
					</view>
					<!-- <view class="edit-btn" @click="changeState">
						{{ state === 0 ? '批量编辑' : '取消' }}
					</view> -->
				</view>
			</view>
		</view>

		<!-- 内容区域 -->
		<view :style="{ paddingTop: totalHeight + 'px' }">
			<!-- 空状态 -->
			<view class="empty-box" v-if="picList.length === 0">
				<view class="tip">这是一个相册</view>
				<view class="tip">你可以在相册里上传照片和视频</view>
				<image class="emptyIcon" src="/static/icon/empty.png" mode=""></image>
			</view>

			<!-- 图片列表 -->
			<view v-if="picList.length > 0 && passwordShow == false">
				<!-- <view class="tip-box" v-if="state === 0">
					小贴士:长按任意照片,即可进入批量编辑模式
					<img class="closeIcon" src="@/static/icon/close.png" />
				</view> -->
				<!-- 长按提示 -->
				<view class="search-box">
					<view class="search-left">
						<image src="@/static/icon/slices/搜索@2x.png" class="search-icon" alt="" />
						<input type="text" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('imgBookSearch', '输入照片名称')" v-model="searchText" class="search-input" @tap="focusField('imgBookSearch')" @focus="focusField('imgBookSearch')" @blur="blurField('imgBookSearch')" />
					</view>
					<view class="search-btn" @click="searchList">搜索</view>
				</view>


				<!-- 图片内容 -->
				<template v-if="folderInfo.show_upload_date == 1">
					<view class="img-content" v-for="(item, index) in picList" :key="index">
						<view class="time-box">
							<view class="img-time">{{ item.collect_date }}</view>
							<view class="select-box" @click="handleRadioClickAll(item)" v-if="state === 1">
								<image
									:src="item.isChecked ? '@/static/icon/checked.png' : '@/static/icon/check.png'" />
								全选
							</view>
						</view>

						<view class="img-box" :class="folderInfo.pic_layout == 1 ? 'layout-three' : 'layout-two'"
							v-for="(pic, idx) in item.pictures" :key="idx" @longpress="state = 1">
							<!-- 视频缩略图 -->
							<image v-if="pic.file_type === 2" class="pic" @click="toVideoDetail(pic)"
								:src="pic.picture_url + '?x-oss-process=video/snapshot,t_0,f_jpg,w_180,h_360'"
								mode="aspectFill">
							</image>

							<!-- 图片 -->
							<image v-if="pic.file_type === 1" class="pic" @click="toPicDetail(pic)"
								:src="pic.picture_url" mode="aspectFill">
							</image>

							<!-- 编辑模式操作按钮 -->
							<template v-if="state === 1">
								<view class="top" @click="setTop(pic)">
									{{ pic.set_top === 0 ? '置顶' : '取消置顶' }}
								</view>
								<view class="select-box" @click.stop="handleRadioClick(pic)">
									<image
										:src="pic.isChecked ? '../../static/icon/checked.png' : '../../static/icon/check.png'"
										mode=""></image>
								</view>
							</template>

							<!-- 视频标识 -->
							<image v-if="pic.file_type === 2" src="../../static/icon/shipin.png" mode=""
								class="videoIcon">
							</image>
						</view>
					</view>
				</template>

				<template v-if="folderInfo.show_upload_date == 0">
					<view class="img-content">
						<view class="time-box">
						</view>
						<view class="img-box" :class="folderInfo.pic_layout == 1 ? 'layout-three' : 'layout-two'"
							v-for="(pic, idx) in picList" :key="idx" @longpress="state = 1">
							<!-- 视频缩略图 -->
							<image v-if="pic.file_type === 2" class="pic" @click="toVideoDetail(pic)"
								:src="pic.picture_url + '?x-oss-process=video/snapshot,t_0,f_jpg,w_180,h_360'"
								mode="aspectFill">
							</image>

							<!-- 图片 -->
							<image v-if="pic.file_type === 1" class="pic" @click="toPicDetail(pic)"
								:src="pic.picture_url" mode="aspectFill">
							</image>

							<!-- 编辑模式操作按钮 -->
							<template v-if="state === 1">
								<view class="top" @click="setTop(pic)">
									{{ pic.set_top === 0 ? '置顶' : '取消置顶' }}
								</view>
								<view class="select-box" @click.stop="handleRadioClick(pic)">
									<image
										:src="pic.isChecked ? '../../static/icon/checked.png' : '../../static/icon/check.png'"
										mode=""></image>
								</view>
							</template>

							<!-- 视频标识 -->
							<image v-if="pic.file_type === 2" src="../../static/icon/shipin.png" mode=""
								class="videoIcon">
							</image>
						</view>
					</view>
				</template>
			</view>
		</view>

		<!-- 底部操作栏 -->
		<view class="bottom-box">
			<view class="left-box">
				<view class="tab" v-if="state === 0 && option_flag" @click="toSet">
					<img src="@/static/icon/setting.png" alt="" />
					<text>设置</text>
				</view>
				<button open-type="share"
					v-if="userInfo.grade_level > 0 && folderInfo.private_type != 2 && option_flag && (folderInfo.other_share == 0 || folderInfo.other_share == 1 && folderInfo.uid == userInfo.id)"
					@click="setShareType('invite')">
					<view class="tab" v-if="state === 0 && folderInfo.private_type != 2">
						<img src="@/static/icon/invent.png" alt="" />
						<text>邀请</text>
					</view>
				</button>
				<view class="tab" v-if="state === 0 && option_flag" @click="state = 1">
					<img src="@/static/icon/slices/24＊24@2x(12).png" alt="" />
					<text>批量编辑</text>
				</view>
			</view>
			<view class="right-box">
				<view class="del-btn" v-if="state === 1 && option_flag" @click="openDel">
					<image src="@/static/icon/slices/trash@2x(1).png" class="add-icon" alt="" />
					删除
				</view>
				<view class="add-btn" v-if="state === 1 && option_flag" @click="openAdd">
					<image src="@/static/icon/slices/Frame@2x(7).png" class="add-icon" />
					添加到
				</view>
				<button open-type="share" class="share-btn"
					v-if="state == 0 && folderInfo.private_type != 2 && (folderInfo.other_share == 0 || folderInfo.other_share == 1 && folderInfo.uid == userInfo.id)"
					@click="setShareType('share')">
					<image src="@/static/icon/slices/share@2x.png" class="share-icon" alt="" />
					分享
				</button>
				<view class="update-btn" v-if="state === 0 && option_flag" @click="showUploadMenu">
					<image src="@/static/icon/slices/24＊24@2x(11).png" class="add-icon" alt="" />
					上传
				</view>
			</view>
		</view>

		<!-- 删除确认弹窗 -->
		<u-popup :show="show" mode="center" :safe-area-inset-bottom="false" :round="10">
			<view class="popBox">
				<view class="pop-title">提示</view>
				<view class="input-content">
					删除此照片还是把它从相册中移除
				</view>
				<view class="input-content action-item" @click="delPic(2)">
					从相册中移除
				</view>
				<view class="input-content action-item danger" @click="delPic(1)">
					删除照片
				</view>
				<view class="input-content action-item" @click="show = false">
					取消
				</view>
			</view>
		</u-popup>



		<u-popup :show="infoShow" :round="10" mode="bottom" @close="infoShow = false">
			<view class="myAlbum">
				<view class="pop-title">
					相册创建者要求上传照片前先完善信息
					<image src="../../static/icon/close2.png" class="close2" mode="" @close="infoShow = false"></image>
				</view>
				<view class="info-input" v-for="(item, index) in infoList" :key="index">
					<view class="label">
						{{ item.label }}
					</view>
					<view class="input-value">
						<input v-model="item.value" type="text" name="" id="">
					</view>
				</view>
				<view class="submit-btn" @click="submitInfo">确定</view>
			</view>
		</u-popup>

		<!-- 创建相册/文件夹弹窗 -->
		<u-popup :show="inputShow" mode="center" :round="10">
			<view class="popBox">
				<view class="pop-title">
					输入{{ folder_type === 2 ? '相册' : '文件夹' }}名称
				</view>
				<view class="input-content input-wrapper" style="justify-content: flex-start;">
					<input type="text" v-model="folder_name_input" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('imgBookFolderName', '请输入名称')" @tap="focusField('imgBookFolderName')" @focus="focusField('imgBookFolderName')" @blur="blurField('imgBookFolderName')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="inputShow = false">取消</view>
					<view class="submit" @click="createAlbum">创建</view>
				</view>
			</view>
		</u-popup>

		<u-popup :show="passwordShow" :safe-area-inset-bottom="false" mode="center" :round="10">
			<view class="popBox">
				<view class="pop-title">
					输入相册密码
				</view>
				<view class="input-content input-wrapper" style="justify-content: flex-start;">
					<input type="text" v-model="password" maxlength="7" placeholder-class="jf-input-placeholder" :placeholder="placeholderFor('imgBookPassword', '请输入密码')" @tap="focusField('imgBookPassword')" @focus="focusField('imgBookPassword')" @blur="blurField('imgBookPassword')" />
				</view>
				<view class="btn-box">
					<view class="cancel" @click="back">取消</view>
					<view class="submit" @click="submitPassword">确定</view>
				</view>
			</view>
		</u-popup>

		<!-- 底部操作菜单 -->
		<u-action-sheet :actions="actionList" cancelText='取消' :closeOnClickOverlay="true" :title="title" :show="addShow"
			@select="selectTab" @close='addShow = false'>
		</u-action-sheet>

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
	</view>
</template>

<script>
import config from '@/common/config'
import {
	getMiniCode
} from '@/common/request/api.js';
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

export default {
	data() {
		return {
			// 导航栏相关
			statusBarHeight: 0,
			totalHeight: 0,
			navigationBarHeight: 44,

			// 状态管理
			state: 0, // 0: 正常模式, 1: 编辑模式

			// 相册信息
			fid: '',
			folder_name: '',
			folder_type: 1,
			visit_times: 0,
			page: 1,

			// 图片列表
			picList: [],
			pics: [], // 选中的图片

			// 弹窗状态
			show: false,
			openShow: false,
			addShow: false,
			inputShow: false,
			infoShow: false,
			passwordShow: false,



			// 操作菜单
			actionList: [{
				name: '新建相册'
			},
			{
				name: '新建文件夹'
			}
			],
			title: '',
			userInfo: {},
			folderInfo: {},
			infoList: [],
			key: '',
			user_pwd: '',
			option_flag: '',
			uploadd_code: '',
			share_str: '',
			link_share_str: '',
			need_pwd: '',
			getCode: 0,
			share_uid: '',
			shareType: '',
			hasPrePage: false
		}
	},

	onLoad(options) {
		const pages = getCurrentPages();
		this.hasPrePage = pages.length > 1;
		getMiniCode().then(res => {
			this.getCode = 1
			this.initPageData(options)
			this.initNavBar()
			this.getUserInfo()
			this.getDetail()
			this.getAllPic()
		})

	},

	onShow() {
		this.pics = []
		if (this.getCode == 1) {
			this.getDetail()
			this.getAllPic()
		}
	},


	onShareAppMessage() {
		let path = ''
		let title = ''
		const encodedFolderName = encodeURIComponent(this.folder_name)
		if (this.shareType == 'invite') {
			title = '邀请你一起加入我的相册！'
			path = `/pagesOther/imgBook/imgBook?id=${this.fid}&share_str=${this.share_str}&folder_name=${encodedFolderName}&share_uid=${this.folderInfo.uid}`
		}
		if (this.shareType == 'share') {
			title = '分享了相册给你,来看看吧',
				path = `/pagesOther/imgBook/imgBook?id=${this.fid}&share_str=${this.share_str}&folder_name=${encodedFolderName}`
		}
		console.log(path, 'path')
		return {
			title: title,
			path: path
		}
	},

	methods: {
		/** 获取所有图片 */
		getAllPic() {
			const querys = {
				fid: this.fid,
				key: '',
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)
			this.$go('album/all/pics', data, 'post', {
				show_err: true
			}).then(res => {
				uni.setStorageSync('picList', buildPictureListForNavigation(res.data.lists || []))
			})
		},

		toHome() {
			uni.reLaunch({
				url: '/pages/index/index'
			})
		},

		setShareType(type) {
			this.shareType = type
		},
		// ==================== 初始化方法 ====================

		/** 初始化导航栏 */
		initNavBar() {
			const systemInfo = this.$base.getSystemInfoCompat()
			this.statusBarHeight = systemInfo.statusBarHeight
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight
		},

		/** 初始化页面数据 */
		initPageData(options) {
			console.log(options, 'options')
			this.fid = options.id
			this.share_uid = options.share_uid
			this.folder_name = decodeURIComponent(options.folder_name)
			this.visit_times = options.visit_times
			this.link_share_str = options.share_str
			// 接收特殊参数，标识是否来自历史记录页面
			this.is_from_visit = options.is_from_visit || false
			this.addVisit()
		},

		// ==================== 导航方法 ====================

		submitPassword() {
			if (this.password == this.folderInfo.show_pwd) {
				this.passwordShow = false
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

		/** 返回上一页 */
		back() {
			uni.navigateBack()
		},

		toConcact() {
			uni.navigateTo({
				url: '/pagesOther/concactPage/concactPage'
			})
		},

		/** 跳转到设置页 */
		toSet() {
			uni.navigateTo({
				url: '/pagesOther/setPage/setPage'
			})
		},

		/** 跳转到图片详情 */
		toPicDetail(pic) {
			if (this.state == 0) {
				const pictureContext = setPictureNavigationContext(pic, this.picList)
				// 传递当前点击的图片ID
				uni.navigateTo({
					url: `/pagesOther/picDetail/picDetail?option_flag=${this.option_flag}&pic_id=${pictureContext.current.pic_id || pic.pic_id || pic.id}`
				})
			}
		},

		/** 跳转到视频详情 */
		toVideoDetail(pic) {
			if (this.state == 0) {
				uni.setStorageSync('videoInfo', pic)
				uni.navigateTo({
					url: `/pagesOther/videoDetail/videoDetail?option_flag=${this.option_flag}`
				})
			}
		},

		handleAvatarError() {
			this.userInfo.avatar = "/static/image/headurl.jpg";
		},

		// ==================== API 请求方法 ====================

		/** 构建请求参数 */
		buildRequestData(querys) {
			return {
				...querys,
				sign: this.$base.getASCII(querys)
			}
		},

		submitInfo() {
			this.infoShow = false
			this.showUploadMenu()
		},

		search() {
			this.picList = []
			this.page = 1
			this.getDetail()
		},

		/** 增加访问次数 */
		addVisit() {
			const querys = {
				fid: this.fid,
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)

			this.$go('album/visit/folder', data, 'post', {
				show_err: true
			})
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

		/** 获取相册详情 */
		getDetail() {
			const querys = {
				fid: this.fid,
				key: this.key,
				share_uid: this.share_uid || '',
				timestamp: new Date().getTime()
			}

			if (this.link_share_str) {
				querys.link_share_str = this.link_share_str
			}
			console.log(querys)
			const data = this.buildRequestData(querys)

			// 根据是否来自历史记录页面决定调用哪个接口
			const api = this.is_from_visit ? 'user/visit/pics' : 'album/lists/pics'
			this.$go(api, data, 'post', {
				show_err: true
			})
				.then(res => {
					this.infoList = []
					this.picList = res.data.lists
					this.folderInfo = res.data.folder
					this.need_pwd = res.data.need_pwd
					if (this.need_pwd == 1) {
						this.passwordShow = true
					}
					this.user_pwd = res.data.user_pwd
					this.option_flag = res.data.option_flag
					this.uploadd_code = res.data.uploadd_code
					this.share_str = res.data.share_str
					if ((this.folderInfo.other_share == 1 && this.folderInfo.uid != this.userInfo.id) || this.folderInfo.private_type == 2) {
						wx.hideShareMenu(); // 隐藏分享按钮
					} else {
						wx.showShareMenu(); // 显示分享按钮
					}
					if (this.folderInfo.upload_field) {
						this.folderInfo.upload_field.forEach(item => {
							this.infoList.push({
								label: item,
								value: ''
							})
						})
					}

					uni.setStorageSync('folderInfo', res.data.folder)
				})
		},



		/** 创建相册 */
		createAlbum() {
			const querys = {
				folder_type: this.folder_type,
				folder_name: this.folder_name_input,
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)

			this.$go('album/create/folder', data, 'post', {
				show_err: true
			})
				.then(res => {
					uni.showToast({
						title: res.msg,
						icon: 'none'
					})
					this.inputShow = false
					notifyFolderRefresh(this.folder_type)
					this.getAlbumList()
				})
		},

		/** 置顶/取消置顶 */
		setTop(item) {
			const querys = {
				pic_id: item.id,
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)

			this.$go('album/set_top/pic', data, 'post', {
				show_err: true
			})
				.then(() => {
					this.getDetail()
				})
		},

		/** 删除图片 */
		delPic(delType) {
			const pics = this.pics.map(item => item.id)
			const querys = {
				pic_id: pics.join(','),
				del_type: delType,
				timestamp: new Date().getTime()
			}
			const data = this.buildRequestData(querys)

			this.$go('album/delete/pic', data, 'post', {
				show_err: true
			})
				.then(() => {
					this.show = false
					this.state = 0
					this.getDetail()
				})
		},



		// ==================== 状态切换方法 ====================

		/** 切换编辑状态 */
		changeState() {
			this.pics = []
			this.state = this.state === 0 ? 1 : 0
		},

		/** 打开删除确认 */
		openDel() {
					if (this.folderInfo.uid != this.userInfo.id && Number(this.folderInfo.editer_delete_pic) !== 1) {
					uni.showToast({
						title: '您没有权限删除该相册图片',
						icon: 'none'
					})
			} else {
				if (this.pics.length === 0) {
					uni.showToast({
						title: '请选择要删除的图片',
						icon: 'none'
					})
					return
				}
				this.show = true
			}

		},

		/** 打开添加到相册 */
		openAdd() {
			if (this.pics.length === 0) {
				uni.showToast({
					title: '请选择要添加的图片',
					icon: 'none'
				})
				return
			}
			let arr = []
			this.pics.forEach(item => {
				arr.push(item.pic_id)
			})
			uni.setStorageSync('pic', this.pics[0])
			uni.navigateTo({
				url: '/pagesOther/addTo/addTo?pic_ids=' + arr.join(',')
			})
		},

		/** 打开新建相册菜单 */
		addAlbum() {
			if (this.folderInfo.uid != this.userInfo.id && this.folderInfo.editer_create == 0) {
				uni.showToast({
					title: '您没有权限新建相册',
					icon: 'none'
				})
			} else {
				this.addShow = true
			}
		},

		/** 选择菜单项 */
		selectTab(e) {
			this.folder_type = e.name === '新建相册' ? 2 : 1
			this.inputShow = true
		},

		// ==================== 选择方法 ====================

		/** 全选/取消全选 */
		handleRadioClickAll(item) {
			if (item.isChecked === false) {
				item.pictures.forEach(pic => {
					this.pics.push(pic)
					pic.isChecked = true
				})
				item.isChecked = true
			} else {
				item.isChecked = false
				item.pictures.forEach(pic => {
					pic.isChecked = false
					const index = this.pics.indexOf(pic)
					if (index !== -1) {
						this.pics.splice(index, 1)
					}
				})
			}
		},

		/** 单选/取消单选 */
		handleRadioClick(pic) {
			if (pic.isChecked) {
				pic.isChecked = false
				const index = this.pics.findIndex(item => item.pic_id === pic.pic_id)
				if (index !== -1) {
					this.pics.splice(index, 1)
				}
			} else {
				pic.isChecked = true
				this.pics.push(pic)
			}
		},



		// ==================== 上传方法 ====================

		/** 显示上传菜单 */
		showUploadMenu() {
			if (this.infoList.length > 0) {
				this.infoList.forEach((item => {
					if (item.value == '' && this.folderInfo.need_confirm == 1 && this.folderInfo
						.upload_field.length > 0) {
						this.infoShow = true
					} else {
						uni.showActionSheet({
							itemList: ['上传图片', '上传视频', '从微信聊天中选取', '大批量上传'],
							itemColor: '#000000',
							success: (res) => {
								const {
									tapIndex
								} = res
								switch (tapIndex) {
									case 0:
										this.uploadImage()
										break
									case 1:
										this.uploadVideo()
										break
									case 2:
										this.selectFromWechatChat()
										break
									case 3:
										uni.navigateTo({
											url: `/pagesOther/moreUpload/moreUpload?uploadd_code=${this.uploadd_code}&user_pwd=${this.user_pwd}`
										})
										break
								}
							}
						})
					}
				}))
			} else {
				uni.showActionSheet({
					itemList: ['上传图片', '上传视频', '从微信聊天中选取', '大批量上传'],
					itemColor: '#000000',
					success: (res) => {
						const {
							tapIndex
						} = res
						switch (tapIndex) {
							case 0:
								this.uploadImage()
								break
							case 1:
								this.uploadVideo()
								break
							case 2:
								this.selectFromWechatChat()
								break
							case 3:
								uni.navigateTo({
									url: `/pagesOther/moreUpload/moreUpload?uploadd_code=${this.uploadd_code}&user_pwd=${this.user_pwd}`
								})
								break
						}
					}
				})
			}


		},

		/** 上传文件公共方法 */
		async uploadFiles(files, fileType) {
			const selectedFiles = (files || [])
				.map(file => normalizeSelectedUploadFile(file, fileType))
				.filter(file => file.path)

			if (selectedFiles.length === 0) {
				uni.showToast({
					title: '未选择文件',
					icon: 'none'
				})
				return
			}

			uni.showLoading({
				title: '上传中...',
				mask: true
			})

			const querys = {
				timestamp: new Date().getTime(),
				pid: this.fid,
				file_type: fileType,
				upload_field: JSON.stringify(this.infoList)

			}
			const params = this.buildRequestData(querys)

			let uploadCount = 0
			const totalCount = selectedFiles.length

			for (const file of selectedFiles) {
				try {
					await this.uploadFile(file, params)
					uploadCount++
					if (uploadCount === totalCount) {
						uni.hideLoading()
						this.getDetail()
					}
				} catch (error) {
					console.error('上传失败:', error)
					uploadCount++
					if (uploadCount === totalCount) {
						uni.hideLoading()
						this.getDetail()
					}
				}
			}
		},

		/** 单个文件上传 */
		uploadFile(file, params) {
			console.log(params, '======')
			return new Promise((resolve, reject) => {
				prepareNamedUploadFile(file.path, file.name).then((uploadPath) => {
					uni.uploadFile({
						url: config.domain + '/api/album/upload/folder',
						filePath: uploadPath,
						name: 'file',
						header: {
							'content-type': 'multipart/form-data',
							'authorization-token': `Bearer ${uni.getStorageSync('token')}`
						},
						formData: {
							...params,
							...buildUploadNameFormData(file.name)
						},
						success: resolve,
						fail: reject
					})
				})
			})
		},

		/** 上传图片 */
		uploadImage() {
			uni.chooseImage({
				count: 9,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const files = res.tempFiles && res.tempFiles.length
						? res.tempFiles
						: (res.tempFilePaths || []).map(path => ({ path }))
					this.uploadFiles(files, 1)
				},
				fail: (err) => {
					console.error('选择图片失败:', err)
					uni.showToast({
						title: '选择图片失败',
						icon: 'none'
					})
				}
			})
		},

		/** 上传视频 */
		uploadVideo() {
			let userInfo = uni.getStorageSync('userInfo')
			if (userInfo.grade_level == 0) {
				uni.showToast({
					title: '请先升级成为会员',
					icon: 'none'
				})
			} else {
				uni.chooseVideo({
					sourceType: ['album', 'camera'],
					maxDuration: 60,
					camera: 'back',
					success: (res) => {
						// 根据会员等级设置文件大小限制（单位：字节）
						let maxSizeMap = {
							1: 200 * 1024 * 1024, // 200M
							2: 1 * 1024 * 1024 * 1024, // 1G
							3: 2 * 1024 * 1024 * 1024, // 2G
							4: 8 * 1024 * 1024 * 1024, // 8G
							5: 20 * 1024 * 1024 * 1024 // 20G
						}

						let maxSize = maxSizeMap[userInfo.grade_level]
						let fileSize = res.size // 视频文件大小（字节）

						// 检查文件大小是否超出限制
						if (fileSize > maxSize) {
							let maxSizeText = this.formatFileSize(maxSize)
							uni.showToast({
								title: `视频大小超出限制，当前会员最大支持${maxSizeText}`,
								icon: 'none',
								duration: 2500
							})
							return
						}

						// 文件大小符合要求，继续上传
						this.uploadFiles([{ ...res, path: res.tempFilePath }], 2)
					}
				})
			}
		},

		// 添加一个格式化文件大小的辅助方法
		formatFileSize(bytes) {
			if (bytes >= 1024 * 1024 * 1024) {
				return (bytes / (1024 * 1024 * 1024)).toFixed(0) + 'G'
			} else if (bytes >= 1024 * 1024) {
				return (bytes / (1024 * 1024)).toFixed(0) + 'M'
			} else {
				return (bytes / 1024).toFixed(0) + 'K'
			}
		},

		/** 从微信聊天中选取 */
		selectFromWechatChat() {
			uni.chooseMessageFile({
				count: 9,
				type: 'image',
				success: (res) => {
					if (res.tempFiles.length === 0) {
						uni.showToast({
							title: '未选择文件',
							icon: 'none'
						})
						return
					}
					this.uploadFiles(res.tempFiles, 1)
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
/* ==================== 全局样式 ==================== */
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

/* ==================== 页面容器 ==================== */
.page {
	background-color: #fff;
	min-height: 100vh;
	padding-bottom: 200rpx;
	box-sizing: border-box;
}

/* ==================== 导航栏 ==================== */
.header {
	width: 100%;
	position: fixed;
	top: 0;
	left: 0;
	background-color: #fff;
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

			.left {
				display: flex;
			}

			.back-button {
				margin-right: 20rpx;

				.backIcon {
					width: 50rpx;
					height: 50rpx;
				}
			}

			.info-box {
				.title {
					font-size: 36rpx;
					color: #000000;
				}
			}

			.edit-btn {
				color: #57be6b;
				font-size: 24rpx;
				border: 2rpx solid #29ad68;
				padding: 4rpx 6rpx;
				border-radius: 6rpx;
			}
		}
	}
}

/* ==================== 空状态 ==================== */
.empty-box {
	margin-top: 200rpx;

	.tip {
		font-size: 26rpx;
		text-align: center;
		color: #999;
	}

	.emptyIcon {
		width: 300rpx;
		height: 300rpx;
		display: block;
		margin: 20rpx auto 0;
	}
}

.search-box {
	width: 710rpx;
	height: 72rpx;
	border-radius: 48rpx 48rpx 48rpx 48rpx;
	background: rgba(0, 0, 0, 0.05);
	margin: 0 auto;
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 20rpx;
	margin-bottom: 20rpx;

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

/* ==================== 提示框 ==================== */
.tip-box {
	background-color: #eef8f0;
	color: #29ad68;
	padding: 20rpx;
	font-size: 22rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;

	.closeIcon {
		width: 20rpx;
		height: 20rpx;
	}
}

/* ==================== 图片内容 ==================== */
.img-content {
	.time-box {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin: 20rpx 0;

		.img-time {
			font-weight: bold;
			font-size: 30rpx;
			color: #000000;
			margin-left: 20rpx;
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
			}
		}
	}
}

.layout-three {
	width: 160rpx;
	height: 160rpx;
	margin-left: 15rpx;
	border-radius: 8rpx 8rpx 8rpx 8rpx;
	overflow: hidden;
}

.layout-three:nth-child(3n+1) {
	margin-right: 0rpx;
}

/* 一行2个布局 */
.layout-two {
	width: 365rpx;
	height: 365rpx;
	overflow: hidden;
	border-radius: 20rpx;
	margin-left: 10rpx;
}

/* ==================== 底部操作栏 ==================== */
.bottom-box {
	width: 100%;
	height: 160rpx;
	position: fixed;
	bottom: 0;
	left: 0;
	background-color: #fff;
	display: flex;
	align-items: start;
	justify-content: space-between;
	padding-bottom: 40rpx;
	padding-top: 20rpx;
	box-sizing: border-box;

	.left-box {
		display: flex;
		align-items: center;

		.tab {
			text-align: center;
			margin-left: 30rpx;
			line-height: 30rpx;

			img {
				width: 50rpx;
				height: 50rpx;
				display: block;
				margin: 0 auto;
			}

			text {
				color: #333333;
				font-size: 24rpx;
			}
		}
	}

	.right-box {
		display: flex;
		align-items: center;

		.del-btn {
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

			.add-icon {
				width: 48rpx;
				height: 48rpx;
				margin-right: 10rpx;
			}
		}

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

		.add-btn {
			width: 336rpx;
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

/* ==================== 弹窗样式 ==================== */
.popBox {
	width: 600rpx;

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
		justify-content: center;
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

/* ==================== 相册选择弹窗 ==================== */
.myAlbum {
	padding: 30rpx;
	box-sizing: border-box;

	.info-input {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 50rpx;

		.label {
			color: #AAAAAA;
			font-size: 30rpx;
		}

		.input-value {
			width: 80%;
			height: 60rpx;
			background-color: #F6F6F6;
			border-radius: 10rpx;
			padding-left: 20rpx;
			box-sizing: border-box;
			font-size: 30rpx;

			input {
				width: 100%;
				height: 100%;
			}
		}
	}

	.pop-title {
		font-size: 30rpx;
		display: flex;
		align-items: center;
		margin-bottom: 40rpx;
		position: relative;

		.backIcon {
			width: 30rpx;
			height: 30rpx;
			margin-right: 20rpx;
		}

		.close2 {
			width: 30rpx;
			height: 30rpx;
			position: absolute;
			right: 0;
		}

		.add-btn {
			width: 100rpx;
			height: 60rpx;
			background-color: #e6f5e9;
			border-radius: 10rpx;
			color: #57be6b;
			font-size: 26rpx;
			margin-left: 20rpx;
			display: flex;
			align-items: center;
			justify-content: center;
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
					position: absolute;
					right: 10rpx;
					bottom: 10rpx;

					image {
						width: 30rpx;
						height: 30rpx;
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
		background-color: #57be6b;
		border-radius: 10rpx;
		color: #fff;
		margin: 0 auto;
		display: flex;
		align-items: center;
		justify-content: center;
	}
}
</style>
