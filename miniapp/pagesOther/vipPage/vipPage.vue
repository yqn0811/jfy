<template>
	<view v-if="canShowUpgrade" class="vip-page">
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
							<view class="title">资源与流量包</view>
						</view>
					</view>
				</view>
			</view>
			<view class="vip-icon">
				<image class="vipIcon" src="/pagesOther/static/icon/slices/生成会员图标 1@2x.png" mode=""></image>
			</view>
		</view>

		<!-- 内容区域 -->
		<view class="content" :style="{ paddingTop: totalHeight + 'px' }">
			<view class="resource-summary">
				<view class="summary-main">
					<view class="package-switch">
						<view
							class="package-switch-item"
							:class="{ active: activePackageType === 'resource_storage' }"
							@click="switchPackageType('resource_storage')"
						>
							资源包
						</view>
						<view
							class="package-switch-item"
							:class="{ active: activePackageType === 'traffic_monthly' }"
							@click="switchPackageType('traffic_monthly')"
						>
							流量包
						</view>
					</view>
					<view class="summary-subtitle">{{ packageSubtitle }}</view>
				</view>
				<view class="current-space" v-if="currentMetricText">
					{{ currentMetricLabel }}：<text>{{ currentMetricText }}</text>
				</view>
			</view>

			<scroll-view v-if="displayedVipList.length" scroll-x="true" class="package-scroll" :scroll-into-view="'package-' + currentLevel">
				<view class="package-wrapper">
						<view
							class="resource-card"
							v-for="(level, index) in displayedVipList"
							:key="index"
							:id="'package-' + index"
							:class="{ active: currentLevel === index }"
							@click="selectVipLevel(level, index)"
						>
						<view class="card-title-row">
							<view class="storage-icon"></view>
							<text class="card-title">{{ level.grade_name }}</text>
						</view>
						<view class="plan-tag" :class="'tag-' + (index % 5)">{{ getPlanTag(level) }}</view>
						<view class="price-line">
							<text class="price-symbol">¥</text>
							<text class="price-value">{{ level.annual_fee }}</text>
							<text class="price-unit">{{ getPlanPriceUnit(level) }}</text>
						</view>
						<view class="market-price" v-if="level.market_annual_fee">原价 ¥{{ level.market_annual_fee }}</view>
						<view class="feature-list">
							<view class="feature-item" v-for="(feature, featureIndex) in getPlanFeatures(level)" :key="featureIndex">
								<text class="feature-check">✓</text>
								<text class="feature-text">{{ feature }}</text>
							</view>
						</view>
					</view>
				</view>
			</scroll-view>
			<view v-else class="package-empty">
				<view class="empty-title">暂无{{ activePackageLabel }}</view>
				<view class="empty-desc">请稍后刷新，或前往电脑端查看最新套餐</view>
			</view>

			<view class="benefits-section">
				<view class="section-header">
					<text class="section-title">{{ currentBenefitTitle }}</text>
				</view>
				<view class="benefits-content">
					<view class="vip-tab" v-for="(benefit, index) in resourceBenefits" :key="index">
						<view class="benefit-icon"></view>
						<view class="level-info">
							<view class="level-label">{{ benefit.title }}</view>
							<view class="level-des">{{ benefit.desc }}</view>
						</view>
					</view>
				</view>
			</view>

			<view class="bottom-upgrade" :class="{ disabled: !hasSelectedPackage }" @click="toBuy">
				<view class="upgrade-text">{{ upgradeButtonText }}</view>
			</view>
		</view>
		
		<!-- 电脑端升级引导 -->
		<view class="ios-modal" v-if="showIOSModal" @click="closeIOSModal">
			<view class="modal-content" @click.stop>
				<view class="modal-text">
					套餐购买请在电脑浏览器完成<br/>
					完成后回到小程序，权益会自动同步
				</view>
				<view class="modal-tip">{{ upgradeGuideDescription }}</view>
				<button class="modal-button" @click="copyUpgradeLink">复制电脑端地址</button>
				<view class="modal-cancel" @click="closeIOSModal">取消</view>
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
				vipList:[],
				levelInfo:{},
				payFee:0,
				isIOS: false, // 是否是iOS系统
				showIOSModal: false, // 是否显示电脑端升级引导
				baseInfo:{},
				userInfo:{},
				canShowUpgrade: false,
				activePackageType: 'resource_storage',
				upgradeUrl: 'https://pic.jfyuntu.com/assets/page/product-list.html'
			};
		},
		onLoad() {
			const systemInfo = this.$base.getSystemInfoCompat();
			this.statusBarHeight = systemInfo.statusBarHeight;
			this.totalHeight = this.statusBarHeight + this.navigationBarHeight;
			
			// 判断是否是iOS系统
			this.isIOS = systemInfo.platform === 'ios';
			
			this.baseInfo = uni.getStorageSync('baseInfo')
			this.userInfo = uni.getStorageSync('userInfo') || {}

			this.getMemberUpgradeConfig()
		},
		methods: {
			initPageData() {
				this.$nextTick(() => {
					this.getVipList()
					this.getUserInfo()
				});
			},
			getMemberUpgradeConfig() {
				this.$go('common/member_upgrade_config', {}, 'get', {
					loading: false,
					show_err: false
				}).then(res => {
					const data = (res && res.data) || {}
					this.canShowUpgrade = Number(data.show_upgrade) === 1
					this.upgradeUrl = data.upgrade_url || this.upgradeUrl
					if (this.canShowUpgrade) {
						this.initPageData()
					} else {
						uni.navigateBack({
							fail: () => {
								uni.switchTab({
									url: '/pages/usercenter/index'
								})
							}
						})
					}
				}).catch(() => {
					uni.navigateBack({
						fail: () => {
							uni.switchTab({
								url: '/pages/usercenter/index'
							})
						}
					})
				})
			},
			toBuy(){
				if (!this.canShowUpgrade) {
					return;
				}
				if (!this.levelInfo || !this.levelInfo.grade_level) {
					uni.showToast({
						title: '请选择套餐',
						icon: 'none'
					});
					return;
				}
				this.showIOSModal = true;
			},
			copyUpgradeLink() {
				uni.setClipboardData({
					data: this.vipH5Link,
					success: () => {
						uni.showToast({
							title: '地址已复制',
							icon: 'success'
						});
						this.showIOSModal = false;
					},
					fail: () => {
						uni.showToast({
							title: '复制失败，请稍后重试',
							icon: 'none'
						});
					}
				});
			},
			// 关闭电脑端升级引导
			closeIOSModal() {
				this.showIOSModal = false;
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
					this.vipList = Array.isArray(res.data) ? res.data : []
					this.syncSelectedPackage()
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
			switchPackageType(type) {
				if (this.activePackageType === type) {
					return;
				}
				this.activePackageType = type;
				this.syncSelectedPackage();
			},
			syncSelectedPackage() {
				const list = this.displayedVipList;
				this.currentLevel = 0;
				this.levelInfo = list[0] || {};
				this.payFee = this.levelInfo.annual_fee || 0;
			},
			getPackageType(level = {}) {
				return this.isTrafficPackage(level) ? 'traffic_monthly' : 'resource_storage';
			},
			getPlanTag(level) {
				if (this.isTrafficPackage(level)) {
					return level.duration_label || level.show_month_del_str || level.show_annual_del_str || '月度流量包';
				}
				return level.show_annual_del_str || level.show_month_del_str || '资源扩容';
			},
			getPlanFeatures(level) {
				const benefits = level && level.benefits_json;
				const features = benefits && Array.isArray(benefits.features) ? benefits.features : [];
				if (features.length) {
					return features.slice(0, 3);
				}
				if (this.isTrafficPackage(level)) {
					return [
						`月度流量 ${level.traffic_size_str || '按套餐配置'}`,
						'适合访客预览和下载',
						'每月按套餐周期生效'
					];
				}
				return [
					`资源库存储空间 ${level.cloud_size_str || ''}`.trim(),
					'适合商品素材沉淀',
					'按实际上传文件大小计算容量'
				];
			},
			isTrafficPackage(level = {}) {
				const type = String(level.package_type || level.plan_category || '').toLowerCase();
				const name = String(level.grade_name || '').toLowerCase();
				return type.indexOf('traffic') !== -1 || name.indexOf('流量') !== -1;
			},
			getPlanPriceUnit(level = {}) {
				if (level.display_unit) return level.display_unit;
				return this.isTrafficPackage(level) ? '/月' : '/年';
			},
			pickPositiveNumber(values) {
				for (let i = 0; i < values.length; i += 1) {
					const value = Number(values[i]);
					if (Number.isFinite(value) && value > 0) {
						return value;
					}
				}
				return 0;
			},
			formatBytes(bytes) {
				const value = Number(bytes) || 0;
				if (value <= 0) return '0B';
				const units = ['B', 'KB', 'MB', 'GB', 'TB'];
				let size = value;
				let unitIndex = 0;
				while (size >= 1024 && unitIndex < units.length - 1) {
					size /= 1024;
					unitIndex += 1;
				}
				const precision = size >= 10 || unitIndex === 0 ? 0 : 1;
				return `${size.toFixed(precision)}${units[unitIndex]}`;
			},
		},
		computed: {
			displayedVipList() {
				return this.vipList.filter(level => this.getPackageType(level) === this.activePackageType);
			},
			activePackageLabel() {
				return this.activePackageType === 'traffic_monthly' ? '流量包' : '资源包';
			},
			packageSubtitle() {
				if (this.activePackageType === 'traffic_monthly') {
					return '流量包补充月度访问、预览和下载流量';
				}
				return '资源包计入容量权益，用于商品素材沉淀';
			},
			currentMetricLabel() {
				return this.activePackageType === 'traffic_monthly' ? '本月流量' : '当前空间';
			},
			currentMetricText() {
				return this.activePackageType === 'traffic_monthly' ? this.currentTrafficText : this.currentSpaceText;
			},
			currentSpaceText() {
				return this.userInfo && this.userInfo.all_space ? this.userInfo.all_space : '';
			},
			currentTrafficText() {
				const userInfo = this.userInfo || {};
				const usedGb = this.pickPositiveNumber([
					userInfo.used_traffic_gb,
					userInfo.traffic_used_gb,
				]);
				const usedBytes = this.pickPositiveNumber([
					userInfo.used_traffic_bytes,
					userInfo.traffic_used_bytes,
					usedGb * 1024 * 1024 * 1024,
				]);
				const limitGb = this.pickPositiveNumber([
					userInfo.monthly_traffic_limit_gb,
					userInfo.traffic_limit_gb,
					userInfo.traffic_gb,
				]);
				const limitBytes = this.pickPositiveNumber([
					userInfo.monthly_traffic_limit_bytes,
					userInfo.traffic_limit_bytes,
					limitGb * 1024 * 1024 * 1024,
				]);
				if (!usedBytes && !limitBytes) {
					return '';
				}
				return `${this.formatBytes(usedBytes)} / ${this.formatBytes(limitBytes)}`;
			},
			hasSelectedPackage() {
				return Boolean(this.levelInfo && (
					this.levelInfo.grade_level !== undefined ||
					this.levelInfo.annual_plan_id ||
					this.levelInfo.month_plan_id
				));
			},
			selectedPackageName() {
				return this.levelInfo && this.levelInfo.grade_name ? this.levelInfo.grade_name : this.activePackageLabel;
			},
			upgradeButtonText() {
				return this.hasSelectedPackage ? `前往电脑端购买${this.selectedPackageName}` : `暂无${this.activePackageLabel}`;
			},
			currentBenefitTitle() {
				return this.isTrafficPackage(this.levelInfo) ? '流量包权益' : '资源包权益';
			},
			resourceBenefits() {
				if (this.isTrafficPackage(this.levelInfo)) {
					return [
						{ title: `月度流量${this.levelInfo.traffic_size_str || ''}`, desc: '用于访客浏览、预览和下载' },
						{ title: '电脑端购买', desc: '小程序内复制地址后前往电脑浏览器开通' },
						{ title: '自动同步', desc: '购买完成后回到小程序自动刷新权益' },
						{ title: '月度生效', desc: '按套餐周期补充月度流量' }
					];
				}
				const storageText = this.levelInfo.cloud_size_str || '';
				return [
					{ title: `资源库空间${storageText}`, desc: '按实际上传文件大小计算容量' },
					{ title: '在线编辑', desc: `支持${this.levelInfo.editor_number || 1}编辑` },
					{ title: '最大上传文件', desc: `${this.levelInfo.upload_size || 20}M` },
					{ title: '素材沉淀', desc: '适合商品图片长期管理' },
					{ title: '原图查看', desc: '支持查看和下载原图' },
					{ title: '文字备注', desc: '可以给图片添加备注' },
					{ title: '搜索', desc: '根据备注搜索图片' },
					{ title: '回收站', desc: '图片保留30天' }
				];
			},
			upgradeGuideDescription() {
				const gradeName = this.levelInfo?.grade_name || '套餐';
				const price = this.levelInfo?.annual_fee || '';
				return `${gradeName}${price ? ` ¥${price}${this.getPlanPriceUnit(this.levelInfo)}` : ''}，请复制地址后在电脑浏览器打开`;
			},
			vipH5Link() {
				const gradeLevel = this.levelInfo?.grade_level || 0;
				const planId = this.levelInfo?.annual_plan_id || this.levelInfo?.month_plan_id || gradeLevel;
				const separator = this.upgradeUrl.indexOf('?') === -1 ? '?' : '&';
				return `${this.upgradeUrl}${separator}grade=${gradeLevel}&plan_id=${planId}&package_type=${this.levelInfo?.package_type || ''}`;
			}
		}
	};
</script>

<style lang="scss" scoped>
	.vip-page {
		min-height: 100vh;
		background: #333333;
		color: #ffffff;
		overflow-x: hidden;
		padding-bottom: 190rpx;
		box-sizing: border-box;
	}

	.header {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 99;
		background: #333333;

		.custom-nav-bar {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			color: #ffffff;
			z-index: 1;

			.nav-bar-content {
				position: relative;
				width: 100%;
				display: flex;
				align-items: center;
				padding: 0 24rpx;
				box-sizing: border-box;
			}

			.left {
				display: flex;
				align-items: center;
				width: 100%;
			}

			.back-button {
				width: 56rpx;
				height: 56rpx;
				display: flex;
				align-items: center;
				justify-content: center;

				.backIcon {
					width: 30rpx;
					height: 30rpx;
					filter: invert(1);
				}
			}

			.info-box {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);

				.title {
					font-weight: 700;
					font-size: 32rpx;
					color: #ffffff;
				}
			}
		}
	}

	.vip-icon {
		display: none;
	}

	.content {
		padding-bottom: 150rpx;
		overflow: hidden;
	}

	.resource-summary {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 20rpx;
		padding: 40rpx 32rpx 24rpx;
		box-sizing: border-box;
	}

	.summary-main {
		min-width: 0;
		flex: 1;
	}

	.package-switch {
		display: inline-flex;
		padding: 6rpx;
		background: #262626;
		border: 1rpx solid #4d4d4d;
		border-radius: 12rpx;
		box-sizing: border-box;
	}

	.package-switch-item {
		min-width: 132rpx;
		height: 56rpx;
		line-height: 56rpx;
		text-align: center;
		font-size: 28rpx;
		font-weight: 800;
		color: #b8b8b8;
		border-radius: 8rpx;
		transition: background 0.2s, color 0.2s;

		&.active {
			background: #ffe329;
			color: #333333;
		}
	}

	.summary-subtitle {
		margin-top: 12rpx;
		font-size: 26rpx;
		color: #b8b8b8;
		line-height: 1.35;
	}

	.current-space {
		font-size: 24rpx;
		color: #b8b8b8;
		white-space: nowrap;

		text {
			color: #ffe329;
			font-weight: 800;
		}
	}

	.package-scroll {
		width: 100%;
		white-space: nowrap;
		padding-bottom: 10rpx;
	}

	.package-wrapper {
		display: inline-flex;
		padding: 0 28rpx 20rpx;
		box-sizing: border-box;
	}

	.package-empty {
		margin: 0 32rpx 20rpx;
		min-height: 280rpx;
		border: 2rpx dashed #555555;
		border-radius: 14rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		box-sizing: border-box;
		background: #3a3a3a;
	}

	.empty-title {
		font-size: 30rpx;
		font-weight: 800;
		color: #ffffff;
	}

	.empty-desc {
		margin-top: 12rpx;
		font-size: 24rpx;
		color: #b8b8b8;
	}

	.resource-card {
		width: 430rpx;
		min-height: 520rpx;
		margin-right: 24rpx;
		padding: 34rpx 32rpx;
		background: #414141;
		border: 2rpx solid #525252;
		border-radius: 14rpx;
		box-shadow: 0 10rpx 24rpx rgba(0, 0, 0, 0.16);
		box-sizing: border-box;
		white-space: normal;
		transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;

		&.active {
			background: #4a4536;
			border-color: #ffd800;
			box-shadow: 0 16rpx 38rpx rgba(255, 216, 0, 0.2);
			transform: translateY(-2rpx);
		}
	}

	.card-title-row {
		display: flex;
		align-items: center;
		min-height: 40rpx;
	}

	.storage-icon {
		width: 30rpx;
		height: 24rpx;
		margin-right: 14rpx;
		border: 4rpx solid #ffd800;
		border-top-width: 8rpx;
		border-radius: 6rpx;
		box-sizing: border-box;
		position: relative;
		flex-shrink: 0;

		&::after {
			content: '';
			position: absolute;
			left: 5rpx;
			right: 5rpx;
			bottom: 4rpx;
			height: 3rpx;
			background: #ffd800;
			border-radius: 4rpx;
		}
	}

	.card-title {
		font-size: 30rpx;
		line-height: 1.3;
		font-weight: 800;
		color: #ffffff;
	}

	.plan-tag {
		margin-top: 14rpx;
		font-size: 22rpx;
		line-height: 1.3;
		font-weight: 700;
		color: #ffe329;
	}

	.tag-2,
	.tag-3 {
		color: #ffe329;
	}

	.tag-4 {
		color: #ffe329;
	}

	.price-line {
		display: flex;
		align-items: baseline;
		margin-top: 38rpx;
		color: #ffe329;
	}

	.price-symbol {
		font-size: 48rpx;
		font-weight: 900;
	}

	.price-value {
		font-size: 58rpx;
		font-weight: 900;
		letter-spacing: 0;
	}

	.price-unit {
		margin-left: 6rpx;
		font-size: 26rpx;
		font-weight: 700;
		color: #f5d95a;
	}

	.market-price {
		margin-top: 8rpx;
		font-size: 24rpx;
		color: #9a9a9a;
		text-decoration: line-through;
	}

	.feature-list {
		margin-top: 34rpx;
	}

	.feature-item {
		display: flex;
		align-items: flex-start;
		margin-top: 20rpx;
		font-size: 25rpx;
		line-height: 1.5;
		color: #d0d0d0;
	}

	.feature-check {
		width: 34rpx;
		color: #ffd800;
		font-size: 26rpx;
		font-weight: 800;
		flex-shrink: 0;
	}

	.feature-text {
		flex: 1;
	}

	.benefits-section {
		padding: 28rpx 32rpx 40rpx;

		.section-header {
			margin-bottom: 22rpx;
		}

		.section-title {
			font-size: 34rpx;
			font-weight: 800;
			color: #ffffff;
		}
	}

	.benefits-content {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.vip-tab {
		width: 330rpx;
		min-height: 112rpx;
		margin-bottom: 22rpx;
		padding: 22rpx;
		background: #414141;
		border: 1rpx solid #4d4d4d;
		border-radius: 14rpx;
		display: flex;
		align-items: center;
		box-sizing: border-box;
	}

	.benefit-icon {
		width: 44rpx;
		height: 44rpx;
		margin-right: 18rpx;
		border-radius: 50%;
		background: #4f4730;
		position: relative;
		flex-shrink: 0;

		&::after {
			content: '';
			position: absolute;
			left: 13rpx;
			top: 12rpx;
			width: 18rpx;
			height: 10rpx;
			border-left: 4rpx solid #ffd800;
			border-bottom: 4rpx solid #ffd800;
			transform: rotate(-45deg);
		}
	}

	.level-info {
		min-width: 0;
	}

	.level-label {
		font-size: 26rpx;
		font-weight: 800;
		color: #ffe329;
		line-height: 1.35;
	}

	.level-des {
		margin-top: 4rpx;
		font-size: 22rpx;
		color: #b8b8b8;
		line-height: 1.35;
	}

	.bottom-upgrade {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 90;
		background: rgba(51, 51, 51, 0.96);
		padding: 24rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
		box-sizing: border-box;
		border-top: 1rpx solid #4d4d4d;

		&.disabled {
			.upgrade-text {
				background: #666666;
				color: #b8b8b8;
			}
		}

		.upgrade-text {
			height: 92rpx;
			line-height: 92rpx;
			border-radius: 46rpx;
			background: #ffe329;
			color: #333333;
			text-align: center;
			font-size: 30rpx;
			font-weight: 800;
		}
	}

	.ios-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(15, 23, 42, 0.58);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 9999;
		padding: 0 42rpx;
		box-sizing: border-box;
	}

	.modal-content {
		width: 100%;
		background-color: #fff;
		border-radius: 18rpx;
		padding: 42rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		box-sizing: border-box;
	}

	.modal-text {
		font-size: 30rpx;
		color: #333333;
		text-align: center;
		line-height: 1.6;
		margin-bottom: 18rpx;
		font-weight: 700;
	}

	.modal-tip {
		font-size: 24rpx;
		color: #999999;
		text-align: center;
		line-height: 1.6;
		margin-bottom: 34rpx;
	}

	.modal-button {
		width: 100%;
		height: 82rpx;
		background-color: #ffd800;
		color: #333333;
		font-size: 30rpx;
		border-radius: 12rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: none;
		line-height: 82rpx;
		padding: 0;
	}

	.modal-cancel {
		margin-top: 24rpx;
		font-size: 28rpx;
		color: #98a2b3;
	}
</style>
