<template>
    <view class="page">
        <view class="container">
            <view class="hero-panel">
                <view class="hero-title-row">
                    <view class="accent"></view>
                    <text class="hero-title">大批量照片上传</text>
                </view>
                <text class="hero-desc">复制产品专属链接到手机或电脑浏览器打开，可一次选择多张照片上传。</text>
                <view class="feature-grid">
                    <view class="feature-item">
                        <text class="feature-num">200</text>
                        <text class="feature-label">单次最多</text>
                    </view>
                    <view class="feature-item">
                        <text class="feature-num">手机</text>
                        <text class="feature-label">浏览器</text>
                    </view>
                    <view class="feature-item">
                        <text class="feature-num">电脑</text>
                        <text class="feature-label">浏览器</text>
                    </view>
                </view>
                <text class="hero-tip">小程序内适合少量添加，图片较多时建议使用浏览器上传。</text>
            </view>

            <view class="section-card">
                <view class="section-head">
                    <text class="section-title">上传链接</text>
                    <text class="section-tag">产品专属</text>
                </view>
                <view class="link-box" @tap="copyLink">
                    <text user-select="true" class="link-text">{{ uploadUrl || '链接生成中...' }}</text>
                </view>
                <text class="section-note">好友只能通过该链接上传到当前产品。</text>
            </view>

            <view class="section-card">
                <view class="password-head">
                    <text class="section-title">访问密码</text>
                    <view class="password-switch-wrap">
                        <text class="password-state">{{ passwordEnabled ? '已开启' : '已关闭' }}</text>
                        <switch
                            class="password-switch"
                            :checked="passwordEnabled"
                            color="#222222"
                            @change="togglePasswordEnabled"
                        />
                    </view>
                </view>
                <view v-if="passwordEnabled" class="password-row">
                    <input
                        class="password-input"
                        type="text"
                        maxlength="4"
                        v-model="uploadPassword"
                        placeholder="请输入访问密码"
                        placeholder-class="password-placeholder"
                    />
                    <view class="refresh-btn" @tap="generatePassword">
                        <text class="refresh-btn-text">刷新</text>
                    </view>
                </view>
                <view v-else class="password-disabled-row">
                    <text class="password-disabled-text">已关闭</text>
                </view>
                <view v-if="passwordEnabled" class="expire-block">
                    <view class="expire-head">
                        <text class="expire-title">密码有效期</text>
                        <text class="expire-current">{{ expireText }}</text>
                    </view>
                    <view class="expire-options">
                        <view
                            v-for="item in expireOptions"
                            :key="item.value"
                            :class="['expire-chip', expireMode === item.value ? 'active' : '']"
                            @tap="selectExpire(item)"
                        >
                            <text class="expire-chip-text">{{ item.label }}</text>
                        </view>
                    </view>
                </view>
                <text class="section-note">{{ passwordEnabled ? '开启后好友打开链接需要输入访问密码。' : '关闭后好友打开链接无需输入密码。' }}</text>
                <view class="password-save" @tap="saveUploadPassword">
                    <text class="password-save-text">{{ passwordSaving ? '保存中...' : '保存设置' }}</text>
                </view>
            </view>

            <view class="action-area">
                <view class="btn-primary" @tap="copyLinkAndPassword">
                    <text class="btn-primary-text">复制链接和密码</text>
                </view>
                <view class="btn-secondary" @tap="copyLink">
                    <text class="btn-secondary-text">只复制链接</text>
                </view>
                <view class="reset-line" @tap="resetLink">
                    <text class="reset-text">重置该产品上传链接</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
export default {
    data() {
        return {
            uploadUrl: '',
            uploadPassword: '',
            passwordEnabled: false,
            uploadPasswordExpireTime: 0,
            expireMode: 'forever',
            expireOptions: [
                { label: '永久有效', value: 'forever', days: 0 },
                { label: '1天', value: '1d', days: 1 },
                { label: '7天', value: '7d', days: 7 },
                { label: '30天', value: '30d', days: 30 },
            ],
            loading: false,
            passwordSaving: false,
            fid: '',
        }
    },
    computed: {
        expireText() {
            return this.formatExpireText(this.uploadPasswordExpireTime)
        }
    },
    onLoad(options) {
        this.fid = options && options.fid ? options.fid : ''
        if (!this.fid) {
            uni.showToast({ title: '缺少产品信息，请先保存产品', icon: 'none' })
            return
        }
        this.getBatchLink()
    },
    methods: {
        // 获取/刷新批量上传链接（优先使用项目内请求封装 this.$go）
        async getBatchLink() {
            this.loading = true
            try {
                if (this.$go) {
                    // 请根据后端接口替换第一参数与返回字段
                    const res = await this.$go('album/batch_link', { fid: this.fid, timestamp: Date.now() }, 'get', { show_err: true })
                    // 假定后端返回 res.data.upload_url / res.data.password
                    if (res && res.data) {
                        this.uploadUrl = res.data.upload_url || res.data.url || ''
                        this.uploadPassword = res.data.password || res.data.pwd || ''
                        this.uploadPasswordExpireTime = Number(res.data.password_expire_time || res.data.upload_pwd_expire_time || 0)
                        this.syncPasswordState()
                    }
                } else if (typeof uni !== 'undefined') {
                    // 回退：生成一个临时示例链接（开发环境）
                    const code = Math.random().toString(36).slice(2, 10).toUpperCase()
                    this.uploadUrl = `https://pic.jfyuntu.com/assets/page/product-list.html?uploadd_code=${code}`
                    this.passwordEnabled = true
                    this.generatePassword()
                }
            } catch (e) {
                console.error(e)
                uni.showToast({ title: '获取上传链接失败', icon: 'none' })
            } finally {
                this.loading = false
            }
        },

        // 复制产品上传链接
        copyLink() {
            if (!this.uploadUrl) {
                uni.showToast({ title: '暂无上传链接', icon: 'none' })
                return
            }
            uni.setClipboardData({
                data: this.uploadUrl,
                success: () => {
                    uni.showToast({ title: '已复制链接', icon: 'none' })
                }
            })
        },

        // 复制链接 + 密码
        copyLinkAndPassword() {
            if (!this.uploadUrl) {
                uni.showToast({ title: '暂无上传链接', icon: 'none' })
                return
            }
            const password = this.passwordEnabled ? (this.uploadPassword || '') : ''
            const text = password ? `${this.uploadUrl}\n上传密码：${password}\n有效期：${this.expireText}` : this.uploadUrl
            uni.setClipboardData({
                data: text,
                success: () => {
                    uni.showToast({
                        title: password ? '已复制链接和密码' : '未设置密码，已复制链接',
                        icon: 'none'
                    })
                }
            })
        },

        async saveUploadPassword() {
            if (this.passwordSaving) return
            if (this.passwordEnabled && !(this.uploadPassword || '').trim()) {
                this.generatePassword()
            }
            const password = this.passwordEnabled ? (this.uploadPassword || '').trim() : ''
            if (password && !/^[A-Za-z0-9]{4}$/.test(password)) {
                uni.showToast({ title: '密码需为4位字母或数字', icon: 'none' })
                return
            }
            const openid = uni.getStorageSync('openid')
            if (!openid) {
                uni.showToast({ title: '请先登录后再设置密码', icon: 'none' })
                return
            }
            const querys = {
                upload_pwd: password,
                upload_pwd_expire_time: password ? this.uploadPasswordExpireTime : 0,
                openid,
                timestamp: new Date().getTime(),
            }
            const data = {
                ...querys,
                sign: this.$base ? this.$base.getASCII(querys) : ''
            }
            this.passwordSaving = true
            try {
                await this.$go('user/update_info', data, 'post', { show_err: true })
                this.uploadPassword = password
                this.passwordEnabled = !!password
                if (!password) {
                    this.uploadPasswordExpireTime = 0
                    this.expireMode = 'forever'
                }
                uni.showToast({ title: password ? '设置已保存' : '密码已关闭', icon: 'none' })
            } catch (e) {
                console.error(e)
                uni.showToast({ title: '保存失败', icon: 'none' })
            } finally {
                this.passwordSaving = false
            }
        },

        // 重置链接（提示确认后调用后端）
        resetLink() {
            uni.showModal({
                title: '重置上传链接',
                content: '重置后旧链接将失效，确定要重置该产品上传链接吗？',
                confirmText: '重置',
                success: async (res) => {
                    if (res.confirm) {
                        await this.doResetLink()
                    }
                }
            })
        },

        // 调用后端重置并刷新
        async doResetLink() {
            this.loading = true
            try {
                if (this.$go) {
                    // 请替换为实际后端接口
                    const res = await this.$go('album/reset_batch_link', { fid: this.fid, timestamp: Date.now() }, 'post', { show_err: true })
                    if (res && res.data) {
                        this.uploadUrl = res.data.upload_url || res.data.url || this.uploadUrl
                        this.uploadPassword = res.data.password || res.data.pwd || this.uploadPassword
                        this.uploadPasswordExpireTime = Number(res.data.password_expire_time || res.data.upload_pwd_expire_time || this.uploadPasswordExpireTime || 0)
                        this.syncPasswordState()
                        uni.showToast({ title: '已重置并更新链接', icon: 'none' })
                    } else {
                        // 如果后端没有返回新链接，则重新调用 getBatchLink
                        await this.getBatchLink()
                        uni.showToast({ title: '已重置', icon: 'none' })
                    }
                } else {
                    // 回退：本地生成新示例
                    const code = Math.random().toString(36).slice(2, 10).toUpperCase()
                    this.uploadUrl = `https://pic.jfyuntu.com/assets/page/product-list.html?uploadd_code=${code}`
                    this.passwordEnabled = true
                    this.generatePassword()
                    uni.showToast({ title: '已重置（示例）', icon: 'none' })
                }
            } catch (e) {
                console.error(e)
                uni.showToast({ title: '重置失败', icon: 'none' })
            } finally {
                this.loading = false
            }
        },

        generatePassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'
            let password = ''
            for (let i = 0; i < 4; i += 1) {
                password += chars.charAt(Math.floor(Math.random() * chars.length))
            }
            this.uploadPassword = password
            this.passwordEnabled = true
        },

        clearPassword() {
            this.passwordEnabled = false
            this.uploadPassword = ''
            this.uploadPasswordExpireTime = 0
            this.expireMode = 'forever'
        },

        togglePasswordEnabled(e) {
            const enabled = !!(e && e.detail && e.detail.value)
            this.passwordEnabled = enabled
            if (enabled) {
                if (!this.uploadPassword) {
                    this.generatePassword()
                }
                return
            }
            this.clearPassword()
        },

        selectExpire(item) {
            this.expireMode = item.value
            if (!item.days) {
                this.uploadPasswordExpireTime = 0
                return
            }
            this.uploadPasswordExpireTime = Math.floor(Date.now() / 1000) + item.days * 24 * 60 * 60
        },

        syncExpireMode() {
            if (!this.uploadPasswordExpireTime) {
                this.expireMode = 'forever'
                return
            }
            this.expireMode = ''
        },

        syncPasswordState() {
            this.passwordEnabled = !!(this.uploadPassword || '').trim()
            if (!this.passwordEnabled) {
                this.uploadPasswordExpireTime = 0
                this.expireMode = 'forever'
                return
            }
            this.syncExpireMode()
        },

        formatExpireText(expireTime) {
            const time = Number(expireTime || 0)
            if (!time) return '永久有效'
            const date = new Date(time * 1000)
            const pad = value => String(value).padStart(2, '0')
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
        }
    }
}
</script>

<style scoped lang="scss">
.page {
    min-height: 100vh;
    background: #f6f6f6;
    padding-bottom: calc(env(safe-area-inset-bottom) + 40rpx);
    box-sizing: border-box;
}

.container {
    padding: 28rpx 30rpx 40rpx;
    box-sizing: border-box;
}

.hero-panel {
    background: #ffffff;
    border-radius: 24rpx;
    padding: 30rpx;
    margin-bottom: 24rpx;
    box-sizing: border-box;
}

.hero-title-row {
    display: flex;
    align-items: center;
    margin-bottom: 16rpx;
}

.accent {
    width: 6rpx;
    height: 36rpx;
    background: #FFD800;
    border-radius: 2rpx;
    margin-right: 14rpx;
}

.hero-title {
    font-weight: 700;
    font-size: 36rpx;
    color: #1f1f1f;
    line-height: 1.25;
}

.hero-desc {
    display: block;
    font-size: 27rpx;
    color: #4f4f4f;
    line-height: 1.6;
}

.feature-grid {
    display: flex;
    gap: 14rpx;
    margin-top: 24rpx;
}

.feature-item {
    flex: 1;
    min-width: 0;
    background: #f7f7f7;
    border-radius: 16rpx;
    padding: 18rpx 10rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-sizing: border-box;
}

.feature-num {
    font-size: 30rpx;
    font-weight: 700;
    color: #222222;
    line-height: 1.2;
}

.feature-label {
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #7a7a7a;
    line-height: 1.2;
}

.hero-tip {
    display: block;
    margin-top: 20rpx;
    padding: 16rpx 18rpx;
    background: #fff8d8;
    border-radius: 14rpx;
    font-size: 24rpx;
    color: #746000;
    line-height: 1.5;
}

.section-card {
    background: #ffffff;
    border-radius: 24rpx;
    padding: 28rpx;
    margin-bottom: 24rpx;
    box-sizing: border-box;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20rpx;
}

.password-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    margin-bottom: 20rpx;
}

.section-title {
    font-size: 30rpx;
    color: #1f1f1f;
    font-weight: 700;
    line-height: 1.3;
}

.section-tag,
.password-state {
    flex-shrink: 0;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: #fff4bd;
    color: #6b5600;
    font-size: 22rpx;
    line-height: 1.2;
}

.password-switch-wrap {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-shrink: 0;
}

.password-switch {
    transform: scale(0.74);
    transform-origin: right center;
}

.link-box {
    background: #f7f7f7;
    border-radius: 16rpx;
    padding: 22rpx;
    box-sizing: border-box;
}

.link-text {
    font-weight: 500;
    font-size: 26rpx;
    color: #4a4a4a;
    line-height: 1.45;
    word-break: break-all;
}

.section-note {
    display: block;
    margin-top: 16rpx;
    font-size: 24rpx;
    color: #8a8a8a;
    line-height: 1.45;
}

.password-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    min-height: 88rpx;
    padding: 0 14rpx 0 22rpx;
    background: #f7f7f7;
    border-radius: 16rpx;
    box-sizing: border-box;
}

.password-input {
    flex: 1;
    height: 64rpx;
    min-width: 0;
    text-align: left;
    font-size: 28rpx;
    color: #333333;
}

.password-placeholder {
    color: #999999;
}

.refresh-btn {
    flex-shrink: 0;
    min-width: 92rpx;
    height: 56rpx;
    border-radius: 28rpx;
    background: #222222;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 20rpx;
    box-sizing: border-box;
}

.refresh-btn-text {
    font-size: 24rpx;
    color: #ffffff;
    font-weight: 600;
}

.password-disabled-row {
    min-height: 88rpx;
    padding: 0 22rpx;
    background: #f7f7f7;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

.password-disabled-text {
    font-size: 28rpx;
    color: #8a8a8a;
    font-weight: 500;
}

.expire-block {
    margin-top: 22rpx;
    padding: 22rpx;
    background: #f7f7f7;
    border-radius: 16rpx;
    box-sizing: border-box;
}

.expire-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 18rpx;
}

.expire-title {
    flex-shrink: 0;
    font-size: 26rpx;
    color: #222222;
    font-weight: 600;
}

.expire-current {
    min-width: 0;
    text-align: right;
    font-size: 24rpx;
    color: #7a7a7a;
    line-height: 1.4;
}

.expire-options {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.expire-chip {
    min-width: 132rpx;
    height: 58rpx;
    border-radius: 64rpx;
    background: #ffffff;
    border: 2rpx solid #eeeeee;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 18rpx;
    box-sizing: border-box;
}

.expire-chip.active {
    background: #FFD000;
    border-color: #FFD000;
}

.expire-chip-text {
    font-size: 24rpx;
    color: #333333;
    font-weight: 600;
}

.password-tip {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    color: #888888;
}

.password-save {
    margin-top: 22rpx;
    height: 72rpx;
    border-radius: 64rpx;
    border: 2rpx solid #FFD000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.password-save-text {
    color: #222222;
    font-size: 28rpx;
    font-weight: 600;
}

.action-area {
    padding: 6rpx 18rpx 0;
    box-sizing: border-box;
}

.btn-primary {
    background: #FFD000;
    height: 92rpx;
    border-radius: 96rpx;
    align-items: center;
    justify-content: center;
    display: flex;
    margin-bottom: 18rpx;
}

.btn-primary-text {
    font-size: 30rpx;
    color: #222;
    font-weight: 600;
}

.btn-secondary {
    height: 88rpx;
    border-radius: 96rpx;
    border: 2rpx solid #e2e2e2;
    background: #ffffff;
    align-items: center;
    justify-content: center;
    display: flex;
    box-sizing: border-box;
}

.btn-secondary-text {
    font-weight: 600;
    font-size: 28rpx;
    color: #333333;
}

.reset-line {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 78rpx;
    margin-top: 10rpx;
}

.reset-text {
    font-weight: 500;
    font-size: 26rpx;
    color: #FF3434;
}
</style>
