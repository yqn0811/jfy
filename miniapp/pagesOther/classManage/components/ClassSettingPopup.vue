<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :round="16"
    :safe-area-inset-bottom="false"
    @close="close"
  >
    <view class="root">
      <view class="head">
        <text class="title">编辑分类</text>
      </view>

      <view class="group">
        <view class="row" @tap="emitAddChildCategory">
          <view class="left">
            <image
              class="icon"
              src="/static/icon/plus-rec@2x.png"
              mode="scaleToFill"
            />
            <text class="row-text">新建子分类</text>
          </view>
          <text class="right-note">已包含{{ childCount }}个子分类</text>
        </view>
        <text class="group-title">设置分类</text>

        <view class="row item" @tap="emitEditInfo">
          <view class="left">
            <image src="/static/icon/24＊24@2x(9).png" class="icon-small" />
            <view class="row-text-hint">
              <text class="row-text">分类信息</text>
              <text class="hint">编辑分类名称和描述</text>
            </view>
          </view>
        </view>

        <view class="row item" @tap="emitOrder">
          <view class="left">
            <image src="/static/icon/Frame@2x(22).png" class="icon-small" />
            <view class="row-text-hint">
              <text class="row-text">子分类排序</text>
              <text class="hint">调整子分类展示顺序</text>
            </view>
          </view>
        </view>
        <view class="row item">
          <view class="left" @tap="toggleSingle">
            <image
              :src="
                single === 1
                  ? '/static/icon/Frame@2x(21).png'
                  : '/static/icon/Frame@2x(23).png'
              "
              class="icon-small"
            />
            <view class="row-text-hint">
              <text class="row-text">{{
                single === 1 ? "切换为单列展示" : "切换为双列展示"
              }}</text>
            </view>
          </view>
        </view>
        <view class="group-title-row">
          <view class="group-title">可见范围</view>
          <text class="status-pill">{{ visibilityLabel }}</text>
        </view>
        <view
          class="row item visibility-row"
          :class="{ active: privateType === 1 }"
          @tap="setVisibility(1)"
        >
          <view class="left">
            <image src="/static/icon/Frame@2x(30).png" class="icon-small" />
            <view>
              <text class="row-text">设为公开</text>
              <text class="hint">会在主页和分类列表中展示</text>
            </view>
          </view>
          <text class="hint">{{ privateType === 1 ? "当前" : "" }}</text>
        </view>
        <view class="row item">
          <view class="left" @tap="setVisibility(4)">
            <image src="/static/icon/Frame@2x(30).png" class="icon-small" />
            <view>
              <text class="row-text">仅单独分享可见</text>
              <text class="hint">分享后该分类仅通过链接可见</text>
            </view>
          </view>
          <view class="switch-wrap">
            <u-switch
              v-model="onlyShare"
              :disabled="saving"
              @change="onSwitchOnlyShare"
              active-color="#333"
            />
          </view>
        </view>

        <view class="row" @tap="onSwitchPrivate">
          <view class="left">
            <image src="/static/icon/lock-2.png" class="icon-small" />
            <view>
              <text class="row-text">{{
                privateType === 2 ? "取消私密" : "设为私密"
              }}</text>
              <text class="hint">设为后仅自己可见</text>
            </view>
          </view>
          <text class="hint">{{ privateType === 2 ? "当前" : "" }}</text>
        </view>
        <view class="row" @tap="confirmDelete">
          <view class="left">
            <image src="/static/icon/trash@2x(2).png" class="icon-small" />
            <view>
              <text class="row-text delete-text">删除分类</text>
            </view>
          </view>
          <text class="hint">有子分类时不能删除</text>
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
  name: "ClassSettingPopup",
  props: {
    visible: { type: Boolean, default: false },
    category: { type: Object, default: null }, // 传入当前分类对象
  },
  emits: [
    "update:visible",
    "add-child-category",
    "edit-info",
    "order",
    "toggle-single",
    "toggle-share",
    "set-private",
    "delete-category",
  ],
  data() {
    return {
      single: 1,
      onlyShare: false,
      privateType: 1,
      saving: false,
    };
  },
  watch: {
    visible(val) {
      if (val) this.loadState();
    },
    category: {
      handler() {
        if (this.visible) this.loadState();
      },
      deep: true,
    },
  },
  computed: {
    childCount() {
      if (!this.category) return 0;
      return Number(
        this.category.child_count ||
          this.category.children_count ||
          this.category.son_count ||
          0,
      );
    },
    visibilityLabel() {
      if (this.privateType === 2) return "私密";
      if (this.privateType === 4) return "仅分享可见";
      return "公开";
    },
  },
  methods: {
    close() {
      this.$emit("update:visible", false);
    },
    loadState() {
      // 从 category 读取当前状态
      if (!this.category) return;
      this.single = this.normalizeLayoutType(this.category.layout_type);
      this.privateType = this.normalizePrivateType(this.category.private_type);
      this.onlyShare = this.privateType === 4;
    },
    emitAddChildCategory() {
      this.$emit("add-child-category", this.category);
      this.close();
    },
    emitEditInfo() {
      this.$emit("edit-info", this.category);
      this.close();
    },
    emitOrder() {
      this.$emit("order", this.category);
      this.close();
    },
    toggleSingle() {
      this.saveSingle();
    },
    normalizeLayoutType(value) {
      return Number(value) === 2 ? 2 : 1;
    },
    normalizePrivateType(value) {
      const type = Number(value);
      return type === 2 || type === 4 ? type : 1;
    },
    async saveSingle() {
      if (this.$go) {
        const nextSingle = this.single === 1 ? 2 : 1;
        const data = {
          fid: this.category.id,
          layout_type: nextSingle,
          folder_type: 1,
          timestamp: Date.now(),
        };
        const params = this.$base
          ? { ...data, sign: this.$base.getASCII(data) }
          : data;
        // 使用项目已有请求封装
        const res = await this.$go("album/edit/folder", params, "post", {
          show_err: true,
        });
        if (res.code === 0) {
          uni.showToast({
            title: "修改成功",
            icon: "success",
            mask: true,
          });
          this.single = nextSingle;
          uni.$emit("refreshClassManageData");
        }
      }
    },
    async onSwitchPrivate() {
      if (this.privateType === 2) {
        await this.setVisibility(1);
        return;
      }
      uni.showModal({
        title: "是否设置为私密",
        content: "",
        success: async (res) => {
          if (res.confirm) {
            await this.setVisibility(2);
          } else if (res.cancel) {
            console.log("用户点击取消");
          }
        },
      });
    },
    async onSwitchOnlyShare(e) {
      const checked =
        typeof e === "object" && e && e.value !== undefined ? e.value : e;
      await this.setVisibility(checked ? 4 : 1);
    },
    async setVisibility(privateType) {
      if (!this.category || !this.category.id || this.saving) return;
      const nextType = this.normalizePrivateType(privateType);
      const prevType = this.privateType;
      if (prevType === nextType) return;

      this.privateType = nextType;
      this.onlyShare = nextType === 4;
      const saved = await this.albumEditFolder(nextType);
      if (!saved) {
        this.privateType = prevType;
        this.onlyShare = prevType === 4;
      }
    },
    async albumEditFolder(private_type) {
      if (this.$go) {
        this.saving = true;
        const data = {
          fid: this.category.id,
          private_type,
          folder_type: 1,
          timestamp: Date.now(),
        };
        const params = this.$base
          ? { ...data, sign: this.$base.getASCII(data) }
          : data;
        // 使用项目已有请求封装
        try {
          const res = await this.$go("album/edit/folder", params, "post", {
            show_err: true,
          });
          if (res && res.code === 0) {
            if (this.$set) {
              this.$set(this.category, "private_type", private_type);
            } else {
              this.category.private_type = private_type;
            }
            uni.showToast({
              title: "修改成功",
              icon: "success",
              mask: true,
            });
            uni.$emit("refreshClassManageData");
            return true;
          }
        } catch (e) {
          console.error(e);
          uni.showToast({ title: "保存失败", icon: "none" });
        } finally {
          this.saving = false;
        }
      }
      return false;
    },
    confirmDelete() {
      uni.showModal({
        title: "是否删除分类",
        content: "",
        success: async (res) => {
          if (res.confirm) {
            if (this.$go) {
              const data = {
                category_id: this.category.id,
              };
              // 使用项目已有请求封装
              const res = await this.$go("album/delete/category", data, "post", {
                show_err: true,
              });
              if (res.code === 0) {
                uni.showToast({
                  title: "删除成功",
                  icon: "success",
                  mask: true,
                });
                uni.$emit("refreshClassManageData");
              }
            }
          } else if (res.cancel) {
            console.log("用户点击取消");
          }
        },
      });
      this.close();
    },
  },
};
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

.group-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-right: 4rpx;
}

.status-pill {
  padding: 6rpx 18rpx;
  border-radius: 999rpx;
  background: #f4f5f7;
  font-size: 22rpx;
  color: #666666;
}

.visibility-row.active .row-text {
  font-weight: 600;
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
