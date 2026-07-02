<template>
  <view class="class-select-page">
    <!-- 主要内容区域 -->
    <view class="content">
      <!-- 已选择提示 -->
      <view class="selected-tip">
        <text class="tip-text">已选择 {{ selectedCount }} 个分类</text>
      </view>

      <!-- 分类列表 -->
      <view class="category-list">
        <!-- 已选择的分类 -->
        <view
          v-for="(item, index) in categoryList"
          :key="item.id"
          class="category-item selected"
          @click="toggleCategory(item)"
        >
          <view class="item-left">
            <image
              src="/static/icon/Frame 1171279070@2x.png"
              class="category-icon"
              mode="aspectFit"
            ></image>
            <view class="item-info">
              <text class="category-name"
                >{{ item.display_name || item.folder_name }}</text
              >
              <text v-if="item.folder_desc" class="category-tag">{{ item.folder_desc }}</text>
            </view>
          </view>
          <view class="checkbox checked">
            <image
            v-if="item.checked"
              src="/static/icon/Frame 1000006316@2x.png"
              mode="aspectFit"
            ></image>
            <image
            v-else
              src="/static/icon/Frame 1171279071@2x.png"
              mode="aspectFit"
            ></image>
          </view>
        </view>
      </view>

      <!-- 没有更多提示 -->
      <view class="no-more" v-if="categoryList.length > 0">
        <text class="no-more-text">没有更多了~</text>
      </view>

      <!-- 空状态 -->
      <view class="empty-state" v-if="categoryList.length === 0">
        <text class="empty-text">暂无分类</text>
      </view>
    </view>

    <!-- 底部完成按钮 -->
    <view class="footer-btn">
      <view class="complete-btn" @click="handleComplete">
        <text class="btn-text">完成</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      productId: "",
      categoryList: [],
      selectedIds: [],
    };
  },
  computed: {
    // 已选择的分类
    selectedCategories() {
      return this.categoryList.filter((item) =>
        this.selectedIds.includes(item.id)
      );
    },
    // 未选择的分类
    unselectedCategories() {
      return this.categoryList.filter(
        (item) => !this.selectedIds.includes(item.id)
      );
    },
    // 已选择数量
    selectedCount() {
      return this.selectedIds.length;
    },
  },
  onLoad(options) {
    // 获取状态栏高度
    const systemInfo = this.$base.getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight || 0;

    // 获取产品ID
    if (options.id) {
      this.productId = options.id;
    }

    // 获取已选择的分类ID
    if (options.selectedIds) {
      try {
        this.selectedIds = JSON.parse(options.selectedIds);
      } catch (e) {
        this.selectedIds = [];
      }
    }
    // 获取分类列表
    this.loadCategoryData();
  },
  methods: {
    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 获取产品下的分类
    getPidCategoryList(){
      return new Promise((resolve, reject) => {
        const querys = {
          product_id: this.productId,
        };
        const data = {
          ...querys,
          sign: this.$base ? this.$base.getASCII(querys) : "",
        };

        this.$go("album/product/categories", data, "post", {
          show_err: false,
          loading: true,
        })
          .then((res) => {
            if (res && res.data) {
              resolve(res.data);
            } else {
              resolve([]);
            }
          })
          .catch((err) => {
            console.log("获取产品分类失败:", err);
            reject(err);
          });
      });
    },

    // 获取全部分类列表
    getCategoryList() {
      return new Promise((resolve, reject) => {
        const querys = {
          folder_type:1
        };
        const data = {
          ...querys,
          sign: this.$base ? this.$base.getASCII(querys) : "",
        };

        this.$go("album/lists/folder", data, "post", {
          show_err: false,
          loading: true,
        })
          .then((res) => {
            if (res && res.data && res.data.lists) {
              resolve(this.flattenCategoryTree(res.data.lists.data));
            } else {
              resolve([]);
            }
          })
          .catch((err) => {
            console.log("获取全部分类失败:", err);
            resolve([]);
          });
      });
    },

    // 加载并对比分类数据
    async loadCategoryData() {
      try {
        // 并行请求两个接口
        const [productCategories, allCategories] = await Promise.all([
          this.getPidCategoryList(),
          this.getCategoryList()
        ]);
        this.selectedIds = productCategories.map(item => item.id)
        // 创建产品分类的 id 集合
        const productCategoryIds = new Set(productCategories.map(item => item.id));

        // 给全部分类中存在于产品分类的项设置 checked 为 true
        this.categoryList = allCategories.map(item => ({
          ...item,
          checked: productCategoryIds.has(item.id)
        }));

      } catch (err) {
        console.log("加载分类数据失败:", err);
      }
    },

    flattenCategoryTree(list, level = 0) {
      if (!Array.isArray(list)) return [];
      return list.reduce((result, item) => {
        result.push({
          ...item,
          level,
          display_name: `${level ? "　".repeat(level) : ""}${item.folder_name}`,
        });
        if (Array.isArray(item.children) && item.children.length) {
          result.push(...this.flattenCategoryTree(item.children, level + 1));
        }
        return result;
      }, []);
    },

    // 切换分类选择状态
    toggleCategory(item) {
      item.checked = !item.checked
      const index = this.selectedIds.indexOf(item.id);
      if (index > -1) {
        // 已选中，取消选中
        this.selectedIds.splice(index, 1);
      } else {
        // 未选中，添加选中
        this.selectedIds.push(item.id);
      }
    },

    // 完成选择
    handleComplete() {
      // 如果有产品ID，更新产品分类
      if (this.productId) {
        this.updateProductCategory();
      } else {
        // 否则返回选择结果
        uni.$emit("categorySelected", {
          selectedIds: this.selectedIds,
          selectedCategories: this.selectedCategories,
        });
        uni.navigateBack();
      }
    },

    // 更新产品分类
    updateProductCategory() {
      const querys = {
        product_id: this.productId,
        category_ids: this.selectedIds,
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base ? this.$base.getASCII(querys) : "",
      };

      this.$go("album/product/add_categories", data, "post", {
        show_err: true,
        loading: true,
      })
        .then((res) => {
          // 触发全局事件，通知首页刷新
          uni.$emit('refreshIndexData');
          uni.$emit('refreshProductManageData');
          uni.$emit('refreshProductDetailsSelfData');
          
          uni.showToast({
            title: "设置成功",
            icon: "success",
            duration: 1500,
            success: () => {
              setTimeout(() => {
                uni.navigateBack();
              }, 1500);
            },
          });
        })
        .catch((err) => {
          console.log("更新产品分类失败:", err);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.class-select-page {
  background: #f5f5f5;
}

// 顶部导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30rpx;
  padding-bottom: 20rpx;
  background: #f5f5f5;

  .nav-back {
    width: 60rpx;
    height: 60rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    image {
      width: 40rpx;
      height: 40rpx;
    }
  }

  .nav-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
  }

  .nav-placeholder {
    width: 60rpx;
  }
}

// 主要内容区域
.content {
  padding: 0 30rpx;
}

// 已选择提示
.selected-tip {
  padding: 30rpx 0;

  .tip-text {
    font-size: 32rpx;
    font-weight: bold;
    color: #333333;
  }
}

// 分类列表
.category-list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

// 分类项
.category-item {
  background: #ffffff;
  border-radius: 16rpx;
  padding: 32rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.3s;

  &.selected {
    background: #ffffff;
  }

  .item-left {
    display: flex;
    align-items: center;
    flex: 1;

    .category-icon {
      width: 48rpx;
      height: 48rpx;
      margin-right: 24rpx;
    }

    .item-info {
      display: flex;
      flex-direction: column;
      gap: 8rpx;

      .category-name {
        font-size: 32rpx;
        color: #333333;
      }

      .category-tag {
        font-size: 24rpx;
        color: #999999;
      }
    }
  }

  .checkbox {
    width: 48rpx;
    height: 48rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    &.checked {
      image {
        width: 48rpx;
        height: 48rpx;
      }
    }

    .checkbox-inner {
      width: 48rpx;
      height: 48rpx;
    }
  }
}

// 没有更多提示
.no-more {
  padding: 60rpx 0;
  text-align: center;

  .no-more-text {
    font-size: 24rpx;
    color: #cccccc;
  }
}

// 空状态
.empty-state {
  padding: 200rpx 0;
  text-align: center;

  .empty-text {
    font-size: 28rpx;
    color: #999999;
  }
}

// 底部完成按钮
.footer-btn {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 30rpx;
  background: #f5f5f5;
  z-index: 100;
  padding-bottom: 60rpx;

  .complete-btn {
    height: 96rpx;
    background: linear-gradient(90deg, #ffd700 0%, #ffc700 100%);
    border-radius: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8rpx 16rpx rgba(255, 215, 0, 0.3);
    transition: all 0.3s;

    &:active {
      transform: scale(0.98);
    }

    .btn-text {
      font-size: 32rpx;
      font-weight: 600;
      color: #333333;
    }
  }
}
</style>
