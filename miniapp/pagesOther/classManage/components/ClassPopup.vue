<template>
    <u-popup :show="visible" mode="bottom" :round="16" :safe-area-inset-bottom="false" @close="close">
        <view class="root">
            <view class="head">
                <text class="title">编辑分类</text>
            </view>
           
            <view class="group">
                <text class="group-title">设置分类</text>
                <view class="row" @tap="classSort">
                    <view class="left">
                        <image class="icon" src="/static/icon/Frame@2x(24).png" mode="scaleToFill" />
                        <text class="row-text">分类排序</text>
                    </view>
                    <text class="right-note">调整分类在主页的展示顺序</text>
                </view>
            </view>

            <view class="cancel-wrap">
                <view class="cancel-btn" @tap="close">取消</view>
            </view>
        </view>
    </u-popup>
</template>

<script>
export default {
    name: 'ClassSettingPopup',
    props: {
        visible: { type: Boolean, default: false },
        category: { type: Object, default: null } // 传入当前分类对象
    },
    emits: ['update:visible', 'add-product', 'remove-product', 'edit-info', 'order', 'toggle-single', 'toggle-share', 'set-private', 'delete-category'],
    data() {
        return {
            single: false,
            onlyShare: false,
            isPrivate: false
        }
    },
    watch: {
        visible(val) {
            if (val) this.loadState()
        }
    },
    computed: {
        categoryCount() {
            return (this.category && (this.category.count || this.category.pic_count)) || 0
        }
    },
    methods: {
        close() {
            this.$emit('update:visible', false)
        },
        loadState() {
            // 从 category 读取当前状态
            if (!this.category) return
            this.single = !!this.category.single_display
            this.onlyShare = !!this.category.only_share
            this.isPrivate = !!this.category.is_private
        },
        classSort() {
            this.$emit('class-sort', this.category)
            this.close()
        },
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
    padding: 20rpx;
}

.title {
    font-size: 32rpx;
    color: #222;
    font-weight: 600;
}

.close {
    position: absolute;
    right: 18rpx;
    top: 18rpx;
    font-size: 28rpx;
    color: #999;
}

.group {
    padding: 12rpx 18rpx;
    background: #fff;
    border-radius: 12rpx;
    margin: 0 12rpx;
}

.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 0;

    &.item .left {
        width: 100%;
    }
}

.left {
    display: flex;
    align-items: center;
    gap: 16rpx;

    .row-text-hint {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
}

.icon {
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-small {
    width: 48rpx;
    height: 48rpx;
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
    display: block;
}

.right-note {
    font-weight: 400;
    font-size: 24rpx;
    color: #999999;
}

.divider {
    height: 1rpx;
    background: #f3f3f3;
    margin: 10rpx 0;
}

.group-title {
    font-weight: bold;
    font-size: 28rpx;
    color: #333333;
    padding: 8rpx 0 12rpx 6rpx;
}

.switch-wrap {
    display: flex;
    align-items: center;
}

.delete-text {
    color: #ff4d4f;
}

.cancel-wrap {
    padding: 18rpx;
}

.cancel-btn {
    height: 96rpx;
    background: #fff;
    border-radius: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1rpx solid #f0f0f0;
    font-weight: 500;
    font-size: 32rpx;
    color: #333333;
}
</style>
