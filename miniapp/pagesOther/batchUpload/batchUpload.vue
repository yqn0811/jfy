<template>
    <view class="page">
        <view class="container">
            <!-- 大标题与说明 -->
            <view class="section">
                <view class="section-title">
                    <view class="accent"></view>
                    <text class="title-text">大批量照片上传</text>
                </view>
                <view class="section-body">
                    <text class="desc">复制链接，粘贴到浏览器中，打开即可上传照片</text>

                    <view class="card">
                        <text class="bullet">· 一次最多上传 200 张照片</text>
                        <text class="bullet">· 支持手机浏览器</text>
                        <text class="bullet">· 支持电脑浏览器</text>
                        <text class="tip">注: 微信官方暂不支持大批量上传照片，因此请使用浏览器进行上传</text>
                    </view>
                </view>
            </view>

            <!-- 邀请好友一起上传 / 链接展示 -->
            <view class="section">
                <view class="section-title">
                    <view class="accent"></view>
                    <text class="title-text">邀请好友一起上传</text>
                </view>

                <view class="section-body">
                    <text class="desc">复制链接，把链接发给好友，即可一起上传照片</text>

                    <view class="card">
                        <text class="bullet">· 每个产品都有自己的专属链接</text>
                        <text class="bullet">· 好友仅能在你分享的产品里上传照片</text>
                    </view>

                    <!-- 链接显示框 -->
                    <view class="link-box">
                        <text user-select="true" class="link-text">{{ uploadUrl }}</text>
                    </view>

                    <!-- 黄色按钮 -->
                    <view class="btn-yellow" @tap="copyLink">
                        <text class="btn-yellow-text">复制该产品链接</text>
                    </view>

                    <!-- 灰色次要按钮 -->
                    <view class="btn-ghost" @tap="copyLinkAndPassword">
                        <text class="btn-ghost-text">复制该产品链接和密码</text>
                    </view>

                    <!-- 重置链接 -->
                    <view class="reset-line" @tap="resetLink">
                        <text class="reset-text">重置该产品的上传链接</text>
                    </view>
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
            loading: false,
            fid: '',
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
                    }
                } else if (typeof uni !== 'undefined') {
                    // 回退：生成一个临时示例链接（开发环境）
                    const code = Math.random().toString(36).slice(2, 10).toUpperCase()
                    this.uploadUrl = `https://api.jfyuntu.com/web/pc_index.html?uploadd_code=${code}`
                    this.uploadPassword = Math.random().toString(36).slice(2, 8).toUpperCase()
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
            const text = this.uploadUrl + (this.uploadPassword ? ` 密码: ${this.uploadPassword}` : '')
            uni.setClipboardData({
                data: text,
                success: () => {
                    uni.showToast({ title: '已复制链接和密码', icon: 'none' })
                }
            })
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
                        uni.showToast({ title: '已重置并更新链接', icon: 'none' })
                    } else {
                        // 如果后端没有返回新链接，则重新调用 getBatchLink
                        await this.getBatchLink()
                        uni.showToast({ title: '已重置', icon: 'none' })
                    }
                } else {
                    // 回退：本地生成新示例
                    const code = Math.random().toString(36).slice(2, 10).toUpperCase()
                    this.uploadUrl = `https://api.jfyuntu.com/web/pc_index.html?uploadd_code=${code}`
                    this.uploadPassword = Math.random().toString(36).slice(2, 8).toUpperCase()
                    uni.showToast({ title: '已重置（示例）', icon: 'none' })
                }
            } catch (e) {
                console.error(e)
                uni.showToast({ title: '重置失败', icon: 'none' })
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped lang="scss">
.page {
    background: #F5F5F5;
    min-height: 100%;
    padding-bottom: 40rpx;
}

.container {
    padding: 24rpx;
}

/* 每个 section */
.section {
    margin-bottom: 30rpx;
}

/* 标题行，左侧黄色条 */
.section-title {
    display: flex;
    align-items: center;
    margin-bottom: 14rpx;
}

.accent {
    width: 6rpx;
    height: 36rpx;
    background: #FFD800;
    border-radius: 2rpx;
    margin-right: 14rpx;
}

.title-text {
    font-weight: bold;
    font-size: 32rpx;
    color: #000000;
}

/* 描述文字 */
.section-body .desc {
    font-weight: 400;
    font-size: 28rpx;
    color: #000000;
    margin-bottom: 24rpx;
    display: block;
}

/* 白色卡片 */
.card {
    display: flex;
    flex-direction: column;
    background: #F2F2F2;
    border-radius: 24rpx 24rpx 24rpx 24rpx;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 24rpx;
}


.bullet {
    display: block;
    font-weight: 400;
    font-size: 28rpx;
    color: #666666;
    margin-bottom: 6rpx;
}

.tip {
    margin-top: 8rpx;
    font-weight: 400;
    font-size: 28rpx;
    color: #666666;
}

/* 链接展示框 */
.link-box {
    background: #f5f5f5;
    border-radius: 12rpx;
    padding: 18rpx;
    margin: 14rpx 0 18rpx;
}

.link-text {
    font-weight: 400;
    font-size: 28rpx;
    color: #666666;
    word-break: break-all;
}

/* 黄色按钮 */
.btn-yellow {
    max-width: 512rpx;
    background: #FFD000;
    height: 84rpx;
    border-radius: 96rpx;
    align-items: center;
    justify-content: center;
    display: flex;
    margin: 0 auto;
    margin-bottom: 48rpx;
}

.btn-yellow-text {
    font-size: 30rpx;
    color: #222;
    font-weight: 600;
}

/* 灰色次要按钮 */
.btn-ghost {
    background: transparent;
    height: 60rpx;
    border-radius: 8rpx;
    align-items: center;
    justify-content: center;
    display: flex;
    margin-bottom: 20rpx;
}

.btn-ghost-text {
    font-weight: 400;
    font-size: 32rpx;
    color: #333333;
}

/* 重置链接文字 */
.reset-line {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    justify-content: center;
    /* 默认底部间距 */
    bottom: 20rpx;
    /* iPhone X 等安全区兼容：先声明 constant，再声明 env（逐条覆盖，env 为最新标准）*/
    bottom: calc(constant(safe-area-inset-bottom) + 20rpx);
    bottom: calc(env(safe-area-inset-bottom) + 20rpx);
    z-index: 10;
}

.reset-text {
    font-weight: 400;
    font-size: 32rpx;
    color: #FF3434;
}
</style>
