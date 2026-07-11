<template>
  <u-popup
    :show="show"
    mode="bottom"
    :round="20"
    @close="handleClose"
    :closeOnClickOverlay="true"
  >
    <view class="category-multi-select">
      <view class="header">
        <text class="title">选择产品分类</text>
        <text class="close-btn" @click="handleClose">×</text>
      </view>

      <view class="category-list">
        <view
          v-for="(item, index) in categories"
          :key="item.id"
          class="category-item"
          :data-index="index"
          @click="toggleCategory(item, index, $event)"
        >
          <text class="category-name">{{ item.display_name || item.folder_name }}</text>
          <view class="checkbox" :class="{ checked: isSelected(item.id) }">
            <image
              v-if="isSelected(item.id)"
              class="checkbox-icon"
              src="/static/icon/Frame 1000006316@2x.png"
              mode="scaleToFill"
            />
            <image
              v-else
              class="checkbox-icon"
              src="/static/icon/Frame 1171279071@2x.png"
              mode="scaleToFill"
            />
          </view>
        </view>
      </view>

      <view class="footer">
        <view class="btn-group">
          <view class="btn cancel-btn" @click="handleClose">取消</view>
          <view class="btn confirm-btn" @click="handleConfirm">确定</view>
        </view>
      </view>
    </view>
  </u-popup>
</template>

<script>
import {
  getObjectId,
  resolveClickedListItem,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";

export default {
  name: "CategoryMultiSelect",
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    categories: {
      type: Array,
      default: () => [],
    },
    // 默认选中的分类ID数组
    defaultValue: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      selectedIds: [],
    };
  },
  watch: {
    show(val) {
      if (val) {
        // 弹窗打开时，初始化选中状态
        this.selectedIds = [...this.defaultValue];
      }
    },
    defaultValue: {
      handler(val) {
        this.selectedIds = [...val];
      },
      immediate: true,
    },
  },
  methods: {
    // 判断是否选中
    isSelected(id) {
      return this.selectedIds.includes(id);
    },

    // 切换选中状态
    toggleCategory(item, index, event) {
      const current = resolveClickedListItem(item, index, event, this.categories);
      const categoryId = getObjectId(current, ["id", "category_id", "folder_id"]);
      if (!categoryId) {
        showInvalidRecordToast();
        return;
      }
      const selectedIndex = this.selectedIds.indexOf(categoryId);
      if (selectedIndex > -1) {
        // 已选中，取消选中
        this.selectedIds.splice(selectedIndex, 1);
      } else {
        // 未选中，添加选中
        this.selectedIds.push(categoryId);
      }
    },

    // 确认选择
    handleConfirm() {
      const selectedCategories = this.categories.filter((item) =>
        this.selectedIds.includes(item.id),
      );
      this.$emit("confirm", {
        ids: this.selectedIds,
        categories: selectedCategories,
      });
      this.$emit("update:show", false);
    },

    // 关闭弹窗
    handleClose() {
      this.$emit("close");
      this.$emit("update:show", false);
    },
  },
};
</script>

<style scoped lang="scss">
.category-multi-select {
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
  max-height: 80vh;
  display: flex;
  flex-direction: column;

  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx 32rpx 24rpx;
    border-bottom: 1rpx solid #f0f0f0;

    .title {
      font-size: 32rpx;
      font-weight: 600;
      color: #333;
    }

    .close-btn {
      font-size: 48rpx;
      color: #999;
      line-height: 1;
      padding: 0 8rpx;
    }
  }

  .category-list {
    flex: 1;
    overflow-y: auto;
    padding: 16rpx 0;
    max-height: 60vh;

    .category-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 28rpx 32rpx;
      border-bottom: 1rpx solid #f5f5f5;

      &:active {
        background: #f8f8f8;
      }

      .category-name {
        font-size: 28rpx;
        color: #333;
        flex: 1;
      }

      .checkbox {
        width: 48rpx;
        height: 48rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        .checkbox-icon {
          width: 48rpx;
          height: 48rpx;
        }
      }
    }
  }

  .footer {
    padding: 24rpx 32rpx;
    border-top: 1rpx solid #f0f0f0;
    background: #fff;

    .btn-group {
      display: flex;
      gap: 24rpx;

      .btn {
        flex: 1;
        height: 88rpx;
        border-radius: 44rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32rpx;
        font-weight: 500;

        &.cancel-btn {
          background: #f5f5f5;
          color: #666;
        }

        &.confirm-btn {
          background: #ffd800;
          color: #333;
        }
      }
    }
  }
}
</style>
