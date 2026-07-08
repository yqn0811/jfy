<template>
    <u-popup :show="visible" mode="bottom" :round="16" :safe-area-inset-bottom="false" @close="close">
        <view class="root">
            <!-- 头部 -->
            <view class="head">
                <text class="title">新建产品</text>
            </view>

            <!-- 新建产品 -->
            <view class="group">
                <view class="row" @tap="onCreate">
                    <view class="left">
                        <image class="icon" src="/static/icon/plus-rec@2x.png" mode="scaleToFill" />
                        <text class="row-text">新建产品</text>
                    </view>
                </view>
                <text class="group-title">设置产品列表</text>
                <!-- 产品排序 -->
                <view class="row item" @tap="onOrder">
                    <view class="left">
                        <view class="left-icon-text">
                            <image class="icon" src="/static/icon/Frame@2x(22).png" mode="scaleToFill" />
                            <text class="row-text">产品排序</text>
                        </view>
                        <text class="hint">调整产品在主页的展示顺序</text>
                    </view>
                </view>

                <!-- 切换为单列展示 -->
                <view class="row item">
                    <view class="left" @tap="toggleSingle">
                        <!-- 根据 single 状态显示不同图标 -->
                        <view class="left-icon-text">
                            <image class="icon"
                                :src="single ? '/static/icon/Frame@2x(21).png' : '/static/icon/Frame@2x(23).png'"
                                mode="scaleToFill" />
                            <text class="row-text">{{ single ? '切换为双列展示' : '切换为单列展示' }}</text>
                        </view>
                        <view class="hint">调整产品在主页的展示样式</view>
                    </view>
                </view>
            </view>

            <!-- 取消 -->
            <view class="cancel-wrap">
                <view class="cancel-btn" @tap="close">取消</view>
            </view>
        </view>
    </u-popup>
</template>

<script>
export default {
    name: 'SettingPopup',
    props: {
        visible: { type: Boolean, default: false },
        albumId: { type: [String, Number], default: '' } // 可传当前合集 id 用于接口
    },
    emits: ['update:visible', 'create', 'order', 'change'],
    data() {
        return {
            single: false, // 当前是否为单列展示
            loading: false
        }
    },
    watch: {
        visible: {
            immediate: true,
            handler(val) {
                if (val) this.loadSettings()
            }
        }
    },
    methods: {
        close() {
            this.$emit('update:visible', false)
        },
        // 新建产品：通知父或直接跳转（父组件可监听 create）
        onCreate() {
            this.$emit('create')
            this.close()
        },
        // 跳转到排序页或触发父组件排序逻辑
        onOrder() {
            // 如果你有单独的排序页面请在父组件处理跳转；这里先 emit
            this.$emit('order')
            this.close()
        },
        // 切换单列展示（界面立即切换并调用后端保存）
        async toggleSingle() {
            const nextSingle = !this.single
            const prevSingle = this.single
            this.single = nextSingle
            this.$emit('change', { single: nextSingle })
            const saved = await this.saveSettings(nextSingle)
            if (!saved) {
                this.single = prevSingle
                this.$emit('change', { single: prevSingle })
            }
        },
        onSwitchChange(e) {
            // u-switch 的 change 会传事件对象或新值，兼容处理
            const val = (typeof e === 'object' && e && typeof e.value !== 'undefined') ? e.value : e
            this.single = !!val
            this.saveSettings(this.single)
            this.$emit('change', { single: this.single })
        },
        // 从后端加载当前设置（若无后端则忽略）
        async loadSettings() {
            if (!this.albumId) return
            this.loading = true
            try {
                if (this.$go) {
                    const params = { fid: this.albumId, folder_type: 2, timestamp: Date.now() }
                    const signed = this.$base ? { ...params, sign: this.$base.getASCII(params) } : params
                    const res = await this.$go('album/lists/folder', signed, 'post', { show_err: false })
                    if (res && res.data && res.data.folder_info) {
                        this.single = Number(res.data.folder_info.layout_type || 1) === 2
                    }
                }
            } catch (e) {
                console.error('loadSettings', e)
            } finally {
                this.loading = false
            }
        },
        // 保存设置到后端（若无后端则 emit）
        async saveSettings(nextSingle = this.single) {
            if (!this.albumId) {
                // 没有 albumId 时仅 emit，父组件处理保存
                this.$emit('change', { single: nextSingle })
                return true
            }
            this.loading = true
            try {
                const payload = { fid: this.albumId, layout_type: nextSingle ? 2 : 1, timestamp: Date.now() }
                const params = this.$base ? { ...payload, sign: this.$base.getASCII(payload) } : payload
                if (this.$go) {
                    const res = await this.$go('album/edit/folder', params, 'post', { show_err: true })
                    if (!res || res.code !== 0) {
                        throw new Error((res && res.msg) || '保存失败')
                    }
                    uni.showToast({ title: '设置已保存', icon: 'none' })
                } else {
                    // 没有后端时通知父组件
                    this.$emit('change', { single: nextSingle })
                    uni.showToast({ title: '设置已修改（本地）', icon: 'none' })
                }
                return true
            } catch (e) {
                console.error('saveSettings', e)
                uni.showToast({ title: '保存失败', icon: 'none' })
                return false
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped lang="scss">
.root {
    padding-bottom: 20rpx;
}

.head {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 20rpx 20rpx 12rpx;
}

.title {
    font-size: 32rpx;
    color: #222;
    font-weight: 600;
}

/* 内容区 */
.group {
    padding: 12rpx 18rpx 8rpx;
    background: #fff;
    border-radius: 12rpx;
    margin: 0 12rpx;
}

.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 0;
    margin-bottom: 16rpx;

    &.item .left {
        justify-content: space-between;
    }
}

.left-icon-text {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.left {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 16rpx;
}


.icon {
    width: 48rpx;
    height: 48rpx;
}

.icon.small {
    width: 46rpx;
    height: 46rpx;
    font-size: 22rpx;
    border-radius: 10rpx;
}

.row-text {
    font-weight: 400;
    font-size: 32rpx;
    color: #333333;
}

.hint {
    font-weight: 400;
    font-size: 24rpx;
    color: #999999;
}

/* 分割线 */
.divider {
    height: 1rpx;
    background: #f3f3f3;
    margin: 10rpx 0;
}

/* 分组标题 */
.group-title {
    font-weight: bold;
    font-size: 28rpx;
    color: #333333;
    padding: 8rpx 0 12rpx 6rpx;
    margin-top: 32rpx;
}

/* switch 右对齐 */
.switch-wrap {
    display: flex;
    align-items: center;
}

/* 取消按钮 */
.cancel-wrap {
    padding: 18rpx;
}

.cancel-btn {
    height: 96rpx;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 96rpx 96rpx 96rpx 96rpx;
    border: 1rpx solid #999999;
    font-size: 28rpx;
    color: #333;
}
</style>
