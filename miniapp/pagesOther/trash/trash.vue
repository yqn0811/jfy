<template>
  <view class="page">
    <view>
      <view class="search-box">
        <view class="search-input-wrapper">
          <image
            src="@/static/icon/slices/搜索@2x.png"
            class="search-icon"
            mode="widthFix"
          ></image>
          <input
            type="text"
            placeholder-class="jf-input-placeholder"
            :placeholder="placeholderFor('trashSearch', '输入照片名称')"
            class="search-input"
            v-model="searchText"
            @tap="focusField('trashSearch')"
            @focus="focusField('trashSearch')"
            @blur="blurField('trashSearch')"
            @input="handleSearch"
          />
          <view class="search-btn" @click="handleSearch">搜索</view>
        </view>
      </view>
      <view class="trash-list" v-if="albumList.length > 0">
        <view
          class="trash-item"
          v-for="(item, index) in albumList"
          :key="getTrashItemKey(item, index)"
          :data-index="index"
          @click="handleItemClick(item, index, $event)"
        >
          <view class="img-container">
            <image :src="getItemImage(item)" lazy-load mode="aspectFill"></image>
          </view>
          <view class="info-container">
            <view class="file-name">{{
              item.folder_name || "IMG_" + item.create_time
            }}</view>
            <view class="file-info">{{
              formatDate(item.create_time || "2021/05/27 13:57")
            }}</view>
          </view>
          <view class="action-container">
            <view
              class="checkbox"
              :class="{ checked: item.isChecked }"
              :data-index="index"
              @click.stop="handleCheckboxClick(item, index, $event)"
            >
              <image
                v-if="item.isChecked"
                src="../../static/icon/Frame 1000006316@2x.png"
                mode=""
              ></image>
            </view>
          </view>
        </view>
      </view>
      <view class="empty-state" v-else>
        <image src="@/static/icon/empty.png" mode="aspectFit"></image>
        <text class="empty-text">回收站暂无内容</text>
      </view>
    </view>

    <view class="bottom-box" v-if="showBottomBox">
      <view class="right-box" @click="comeback">
        <image
          class="del-icon"
          src="../../static/icon/slices/Frame@2x(3).png"
          mode="aspectFit"
        ></image>
        恢复
      </view>
      <view class="left-box" @click="delPic">
        <image
          class="del-icon"
          src="../../static/icon/slices/trash@2x(1).png"
          mode="aspectFit"
        ></image>
        删除
      </view>
    </view>
  </view>
</template>

<script>
import { getSystemInfoCompat } from "@/common/helper/base.js";
import { buildListItemKey } from "@/common/helper/listKey.js";
import {
  getObjectId,
  resolveClickedListItem,
  showInvalidRecordToast,
} from "@/common/helper/clickItem.js";

export default {
  data() {
    return {
      statusBarHeight: "",
      totalHeight: "",
      navigationBarHeight: 44,
      page: 1,
      albumList: [],
      last_page: 1,
      pics: [],
      searchText: "",
    };
  },
  computed: {
    showBottomBox() {
      return this.pics.length > 0;
    },
  },
  onLoad() {
    const systemInfo = getSystemInfoCompat();
    this.statusBarHeight = systemInfo.statusBarHeight;
    this.totalHeight = this.statusBarHeight + this.navigationBarHeight;

    this.getList()
  },
  onShow() {},
  onReachBottom() {
    if (this.page < this.last_page) {
      this.page++;
      this.getList();
    }
  },
  methods: {
    getTrashItemKey(item, index) {
      return buildListItemKey(item, index, "trash");
    },
    parseDate(dateStr) {
      if (!dateStr) return null;
      if (dateStr instanceof Date) return dateStr;
      if (typeof dateStr === "number") return new Date(dateStr);

      const text = String(dateStr).trim();
      const normalizedText = text.includes(" ")
        ? text.replace(/-/g, "/")
        : text;
      const date = new Date(normalizedText);

      return Number.isNaN(date.getTime()) ? null : date;
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      const date = this.parseDate(dateStr);
      if (!date) return String(dateStr);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      return `${year}/${month}/${day} ${hours}:${minutes}`;
    },
    formatSize(size) {
      if (!size) return "0 B";
      if (size < 1024) return `${size} B`;
      if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
      return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    },
    getItemImage(item) {
      return (
        item.imgurl ||
        item.new_thumb ||
        item.image ||
        item.picture_url ||
        "/static/image/pic.png"
      );
    },
    getTrashId(item) {
      return getObjectId(item, ["id", "product_id", "folder_id"]);
    },
    getSelectedProductIds() {
      return this.pics
        .map((item) => this.getTrashId(item))
        .filter((id) => id !== undefined && id !== null && id !== "");
    },
    handleItemClick(item, index, event) {
      // 点击项目时切换选中状态
      this.handleCheckboxClick(item, index, event);
    },
    handleCheckboxClick(item, index, event) {
      const current = resolveClickedListItem(item, index, event, this.albumList);
      const itemId = this.getTrashId(current);
      if (!current || !itemId) {
        showInvalidRecordToast();
        return;
      }
      current.isChecked = !current.isChecked;
      if (current.isChecked) {
        if (!this.pics.some((p) => String(this.getTrashId(p)) === String(itemId))) {
          this.pics.push(current);
        }
      } else {
        const selectedIndex = this.pics.findIndex(
          (p) => String(this.getTrashId(p)) === String(itemId)
        );
        if (selectedIndex > -1) {
          this.pics.splice(selectedIndex, 1);
        }
      }
    },
    delPic() {
      uni.showModal({
        title: "提示",
        content: "确定要删除选中的产品吗",
        success: (res) => {
          if (res.confirm) {
            this.del();
          } else if (res.cancel) {
          }
        },
      });
    },
    comeback() {
      uni.showModal({
        title: "提示",
        content: "确定要恢复选中的产品吗",
        success: (res) => {
          if (res.confirm) {
            this.reset();
          } else if (res.cancel) {
          }
        },
      });
    },
    reset() {
      const pics = this.getSelectedProductIds();
      if (!pics.length) {
        showInvalidRecordToast("请选择有效产品");
        return;
      }
      const querys = {
        product_ids: pics.join(","),
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
      this.$go("user/restore/product", data, "post", {
        show_err: true,
      }).then((res) => {
        uni.showToast({
          title: "恢复成功",
          icon: "none",
        });
        this.albumList = [];
        this.pics = [];
        this.page = 1;
        this.getList();
      });
    },
    del() {
      const pics = this.getSelectedProductIds();
      if (!pics.length) {
        showInvalidRecordToast("请选择有效产品");
        return;
      }
      const querys = {
        product_ids: pics.join(","),
        timestamp: new Date().getTime(),
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
      this.$go("user/destroy/product", data, "post", {
        show_err: true,
      }).then((res) => {
        uni.showToast({
          title: "删除成功",
          icon: "none",
        });
        this.albumList = [];
        this.pics = [];
        this.page = 1;
        this.getList();
      });
    },
    back() {
      uni.navigateBack();
    },
    handleSearch() {
      console.log("搜索值:", this.searchText);

      // 搜索时重置页码和数据
      this.page = 1;
      this.albumList = [];
      this.getList();
    },

    getList() {
      const querys = {
        timestamp: new Date().getTime(),
        key: this.searchText, // 将搜索值赋值给key属性
      };
      const data = {
        ...querys,
        sign: this.$base.getASCII(querys),
      };
      this.$go("user/recycle/list", data, "get", {
        show_err: true,
      }).then((res) => {
        // 确保每个项目都有isChecked属性
        const newData = res.data.data.map((item) => ({
          ...item,
          imgurl:
            item.imgurl ||
            item.new_thumb ||
            item.image ||
            item.picture_url ||
            "",
          isChecked: false,
        }));

        this.albumList = this.albumList.concat(newData);
        this.last_page = res.data.last_page;
      });
    },
  },
};
</script>

<style lang="scss" scoped>
/* 搜索框样式 */
.search-box {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20rpx 30rpx;
  background-color: #ffffff;

  image {
    width: 30rpx;
    height: 30rpx;
  }

  .search-input-wrapper {
    flex: 1;
    height: 72rpx;
    background-color: #f5f5f5;
    border-radius: 30rpx;
    display: flex;
    align-items: center;
    padding: 0 20rpx 0 20rpx;
    position: relative;

    .search-icon {
      font-size: 28rpx;
      color: #999;
      margin-right: 10rpx;
    }

    .search-input {
      flex: 1;
      font-size: 28rpx;
      color: #333;
      background-color: transparent;
    }
  }

  .search-btn {
    padding: 0 30rpx;
    height: 60rpx;
    background-color: #ffd700;
    color: #333;
    font-size: 24rpx;
    font-weight: 500;
    border-radius: 30rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 20rpx;
    position: relative;
    left: 20rpx;
  }
}

.page {
  background-color: #fff;
  min-height: 100vh;
  box-sizing: border-box;
  padding-bottom: 200rpx;
  box-sizing: border-box;
}

.header {
  width: 100%;
  background-size: 100%;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;
  background-color: #ffffff;
  z-index: 1000;

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
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: flex-start;

      .left {
        display: flex;
        align-items: center;
      }

      .backIcon {
        width: 30rpx;
        height: 30rpx;
        margin-right: 20rpx;
      }

      .title {
        font-weight: bold;
        font-size: 28rpx;
        color: #000000;
      }

      .nums {
        color: #aaaaaa;
        font-size: 22rpx;
      }
    }
  }
}

.trash-list {
  padding: 20rpx;
}

.trash-item {
  display: flex;
  align-items: center;
  padding: 20rpx;
  // border-bottom: 2rpx solid #f5f5f5;
}

.img-container {
  width: 100rpx;
  height: 100rpx;
  overflow: hidden;
  margin-right: 20rpx;
  flex-shrink: 0;
  border-radius: 12rpx;
  background-color: #f5f5f5;

  image {
    width: 100%;
    height: 100%;
    display: block;
  }
}

.info-container {
  flex: 1;
  min-width: 0;
}

.file-name {
  font-size: 28rpx;
  color: #333;
  margin-bottom: 10rpx;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-info {
  font-size: 24rpx;
  color: #999;
}

.action-container {
  margin-left: 20rpx;
}

.checkbox {
  width: 36rpx;
  height: 36rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2rpx solid #ddd;
  border-radius: 50%;

  &.checked {
    // border-color: #57BE6B;
    // background-color: #57BE6B;
    border: none;
  }

  image {
    width: 36rpx;
    height: 36rpx;
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 0;

  image {
    width: 200rpx;
    height: 200rpx;
    margin-bottom: 30rpx;
    opacity: 0.5;
  }

  .empty-text {
    font-size: 28rpx;
    color: #999;
  }
}

.bottom-box {
  width: 100%;
  height: 140rpx;
  position: fixed;
  bottom: 0;
  left: 0;
  background-color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-bottom: 40rpx;

  // border-top: 2rpx solid #eee;
  .left-box {
    width: 250rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10rpx;
    margin-left: 30rpx;
    font-size: 28rpx;
    color: #333333;
    background-color: #f2f2f2;
  }

  .right-box {
    width: 250rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10rpx;
    margin-right: 30rpx;
    font-size: 28rpx;
    color: #333333;
    background-color: #f2f2f2;
  }

  .del-icon {
    width: 35rpx;
    height: 35rpx;
    margin-right: 10rpx;
  }
}
</style>
