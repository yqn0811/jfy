<template>
  <view class="con">
    <template v-if="viewWidth">
      <movable-area
        class="area"
        :style="{ height: areaHeight }"
        @mouseenter="mouseenter"
        @mouseleave="mouseleave"
      >
        <movable-view
          v-for="(item, index) in imageList"
          :key="item.id"
          class="view"
          direction="all"
          :y="item.y"
          :x="item.x"
          :damping="40"
          :disabled="item.disable"
          @change="onChange($event, item)"
          @touchstart="touchstart(item)"
          @mousedown="touchstart(item)"
          @touchend="touchend(item)"
          @mouseup="touchend(item)"
          :style="{
            width: viewWidth + 'px',
            height: viewHeight + 'px',
            'z-index': item.zIndex,
            opacity: item.opacity,
          }"
        >
          <view
            class="area-con"
            :style="{
              width: viewWidth + 'px',
              height: viewHeight + 'px',
              borderRadius: borderRadius + 'rpx',
              transform: 'scale(' + item.scale + ')',
            }"
          >
            <view v-if="item.num" class="amount-num"> {{ item.num || 0 }}张 </view>
            <image class="pre-image" :src="item.src" mode="aspectFill"></image>
          </view>
        </movable-view>
      </movable-area>
    </template>
  </view>
</template>

<script>
export default {
  emits: ["input", "update:modelValue"],
  props: {
    // 排序图片
    value: {
      type: Array,
      default: function () {
        return [];
      },
    },
    // 排序图片
    modelValue: {
      type: Array,
      default: function () {
        return [];
      },
    },
    // 从 list 元素对象中读取的键名
    keyName: {
      type: String,
      default: null,
    },
    // 选择图片数量限制
    number: {
      type: Number,
      default: 6,
    },
    // 图片父容器宽度（实际显示的图片宽度为 imageWidth / 1.1 ），单位 rpx
    // imageWidth > 0 则 cols 无效
    imageWidth: {
      type: Number,
      default: 0,
    },
    // 图片列数
    cols: {
      type: Number,
      default: 4,
    },
    // 图片圆角，单位 rpx
    borderRadius: {
      type: Number,
      default: 8,
    },
    // 图片周围空白填充，单位 rpx
    padding: {
      type: Number,
      default: 10,
    },
    // 行间距，单位 rpx
    rowGap: {
      type: Number,
      default: 20,
    },
    // 列间距，单位 rpx
    colGap: {
      type: Number,
      default: 20,
    },
    // 拖动图片时放大倍数 [0, ∞)
    scale: {
      type: Number,
      default: 1.1,
    },
    // 拖动图片时不透明度
    opacity: {
      type: Number,
      default: 0.7,
    },
    // 自定义添加
    addImage: {
      type: Function,
      default: null,
    },
    // 删除确认
    delImage: {
      type: Function,
      default: null,
    },
  },
  data() {
    return {
      imageList: [],
      width: 0,
      add: {
        x: 0,
        y: 0,
      },
      colsValue: 0,
      viewWidth: 0,
      viewHeight: 0, // 添加固定高度
      rowGapPx: 0, // 行间距的 px 值
      colGapPx: 0, // 列间距的 px 值
      tempItem: null,
      timer: null,
      changeStatus: true,
      preStatus: true,
      first: true,
    };
  },
  computed: {
    areaHeight() {
      let height = "";
      if (this.imageList.length < this.number) {
        height =
          (
            Math.ceil((this.imageList.length + 1) / this.colsValue) *
            (this.viewHeight + this.rowGapPx)
          ).toFixed() + "px";
      } else {
        height =
          (
            Math.ceil(this.imageList.length / this.colsValue) * (this.viewHeight + this.rowGapPx)
          ).toFixed() + "px";
      }
      console.log("areaHeight", height);
      return height;
    },
    childWidth() {
      return this.viewWidth - this.rpx2px(this.padding) * 2 + "px";
    },
  },
  watch: {
    value: {
      handler(n) {
        if (!this.first && this.changeStatus) {
          console.log("watch", n);
          let flag = false;
          for (let i = 0; i < n.length; i++) {
            if (flag) {
              this.addProperties(n[i]);
              continue;
            }
            if (
              this.imageList.length === i ||
              this.imageList[i].src !== this.getSrc(n[i])
            ) {
              flag = true;
              this.imageList.splice(i);
              this.addProperties(n[i]);
            }
          }
        }
      },
      deep: true,
    },
    modelValue: {
      handler(n) {
        if (!this.first && this.changeStatus) {
          console.log("watch", n);
          let flag = false;
          for (let i = 0; i < n.length; i++) {
            if (flag) {
              this.addProperties(this.getSrc(n[i]));
              continue;
            }
            if (
              this.imageList.length === i ||
              this.imageList[i].src !== this.getSrc(n[i])
            ) {
              flag = true;
              this.imageList.splice(i);
              this.addProperties(this.getSrc(n[i]));
            }
          }
        }
      },
      deep: true,
    },
  },
  created() {
    this.width = this.$base.getSystemInfoCompat().windowWidth;
  },
  mounted() {
    const query = uni.createSelectorQuery().in(this);
    query.select(".con").boundingClientRect((data) => {
      this.colsValue = this.cols;
      this.rowGapPx = this.rpx2px(this.rowGap); // 转换行间距为 px
      this.colGapPx = this.rpx2px(this.colGap); // 转换列间距为 px
      
      // 计算实际可用宽度：容器宽度 - (列数-1)*列间距，然后除以列数
      const totalColGap = (this.cols - 1) * this.colGapPx;
      this.viewWidth = (data.width - totalColGap) / this.cols;
      this.viewHeight = this.rpx2px(224); // 固定高度 224rpx
      
      if (this.imageWidth > 0) {
        this.viewWidth = this.rpx2px(this.imageWidth);
        const totalWidth = this.viewWidth + this.colGapPx;
        this.colsValue = Math.floor((data.width + this.colGapPx) / totalWidth);
      }
      let list = this.value;
      // #ifdef VUE3
      list = this.modelValue;
      // #endif
      for (let item of list) {
        this.addProperties(item);
      }
      this.first = false;
    });
    query.exec();
  },
  methods: {
    getSrc(item) {
      if (this.keyName !== null) {
        return item[this.keyName];
      }
      return item;
    },
    onChange(e, item) {
      if (!item) return;
      item.oldX = e.detail.x;
      item.oldY = e.detail.y;
      if (e.detail.source === "touch") {
        if (item.moveEnd) {
          item.offset = Math.sqrt(
            Math.pow(item.oldX - item.absX * (this.viewWidth + this.colGapPx), 2) +
              Math.pow(item.oldY - item.absY * (this.viewHeight + this.rowGapPx), 2)
          );
        }
        let x = Math.floor((e.detail.x + (this.viewWidth + this.colGapPx) / 2) / (this.viewWidth + this.colGapPx));
        if (x >= this.colsValue) return;
        let y = Math.floor((e.detail.y + (this.viewHeight + this.rowGapPx) / 2) / (this.viewHeight + this.rowGapPx));
        let index = this.colsValue * y + x;
        if (item.index != index && index < this.imageList.length) {
          this.changeStatus = false;
          for (let obj of this.imageList) {
            if (
              item.index > index &&
              obj.index >= index &&
              obj.index < item.index
            ) {
              this.change(obj, 1);
            } else if (
              item.index < index &&
              obj.index <= index &&
              obj.index > item.index
            ) {
              this.change(obj, -1);
            } else if (obj.id != item.id) {
              obj.offset = 0;
              obj.x = obj.oldX;
              obj.y = obj.oldY;
              setTimeout(() => {
                this.$nextTick(() => {
                  obj.x = obj.absX * (this.viewWidth + this.colGapPx);
                  obj.y = obj.absY * (this.viewHeight + this.rowGapPx);
                });
              }, 0);
            }
          }
          item.index = index;
          item.absX = x;
          item.absY = y;
          if (!item.moveEnd) {
            setTimeout(() => {
              this.$nextTick(() => {
                item.x = item.absX * (this.viewWidth + this.colGapPx);
                item.y = item.absY * (this.viewHeight + this.rowGapPx);
              });
            }, 0);
          }
          // console.log('bbb', JSON.parse(JSON.stringify(item)));
          this.sortList();
        }
      }
    },
    change(obj, i) {
      obj.index += i;
      obj.offset = 0;
      obj.x = obj.oldX;
      obj.y = obj.oldY;
      obj.absX = obj.index % this.colsValue;
      obj.absY = Math.floor(obj.index / this.colsValue);
      setTimeout(() => {
        this.$nextTick(() => {
          obj.x = obj.absX * (this.viewWidth + this.colGapPx);
          obj.y = obj.absY * (this.viewHeight + this.rowGapPx);
        });
      }, 0);
    },
    touchstart(item) {
      this.imageList.forEach((v) => {
        v.zIndex = v.index + 9;
      });
      item.zIndex = 99;
      item.moveEnd = true;
      this.tempItem = item;
      this.timer = setTimeout(() => {
        item.scale = this.scale;
        item.opacity = this.opacity;
        clearTimeout(this.timer);
        this.timer = null;
      }, 200);
    },
    touchend(item) {
      this.previewImage(item);
      item.scale = 1;
      item.opacity = 1;
      item.x = item.oldX;
      item.y = item.oldY;
      item.offset = 0;
      item.moveEnd = false;
      setTimeout(() => {
        this.$nextTick(() => {
          item.x = item.absX * (this.viewWidth + this.colGapPx);
          item.y = item.absY * (this.viewHeight + this.rowGapPx);
          this.tempItem = null;
          this.changeStatus = true;
        });
        // console.log('ccc', JSON.parse(JSON.stringify(item)));
      }, 0);
      // console.log('ddd', JSON.parse(JSON.stringify(item)));
    },
    previewImage(item) {
      if (
        this.timer &&
        this.preStatus &&
        this.changeStatus &&
        item.offset < 28.28
      ) {
        clearTimeout(this.timer);
        this.timer = null;
        const list = this.value || this.modelValue;
        let srcList = list.map((v) => this.getSrc(v));
        console.log(list, srcList);
        uni.previewImage({
          urls: srcList,
          current: item.src,
          success: () => {
            this.preStatus = false;
            setTimeout(() => {
              this.preStatus = true;
            }, 600);
          },
          fail: (e) => {
            console.log(e);
          },
        });
      } else if (this.timer) {
        clearTimeout(this.timer);
        this.timer = null;
      }
    },
    mouseenter() {
      //#ifdef H5
      this.imageList.forEach((v) => {
        v.disable = false;
      });
      //#endif
    },
    mouseleave() {
      //#ifdef H5
      if (this.tempItem) {
        this.imageList.forEach((v) => {
          v.disable = true;
          v.zIndex = v.index + 9;
          v.offset = 0;
          v.moveEnd = false;
          if (v.id == this.tempItem.id) {
            if (this.timer) {
              clearTimeout(this.timer);
              this.timer = null;
            }
            v.scale = 1;
            v.opacity = 1;
            v.x = v.oldX;
            v.y = v.oldY;
            this.$nextTick(() => {
              v.x = v.absX * (this.viewWidth + this.colGapPx);
              v.y = v.absY * (this.viewHeight + this.rowGapPx);
              this.tempItem = null;
            });
          }
        });
        this.changeStatus = true;
      }
      //#endif
    },
    addImages() {
      if (typeof this.addImage === "function") {
        this.addImage.bind(this.$parent)();
      } else {
        let checkNumber = this.number - this.imageList.length;
        uni.chooseImage({
          count: checkNumber,
          sourceType: ["album", "camera"],
          success: (res) => {
            let count =
              checkNumber <= res.tempFilePaths.length
                ? checkNumber
                : res.tempFilePaths.length;
            for (let i = 0; i < count; i++) {
              this.addProperties(res.tempFilePaths[i]);
            }
            this.sortList();
          },
        });
      }
    },
    delImages(item, index) {
      if (typeof this.delImage === "function") {
        this.delImage.bind(this.$parent)(() => {
          this.delImageHandle(item, index);
        });
      } else {
        this.delImageHandle(item, index);
      }
    },
    delImageHandle(item, index) {
      this.imageList.splice(index, 1);
      for (let obj of this.imageList) {
        if (obj.index > item.index) {
          obj.index -= 1;
          obj.x = obj.oldX;
          obj.y = obj.oldY;
          obj.absX = obj.index % this.colsValue;
          obj.absY = Math.floor(obj.index / this.colsValue);
          this.$nextTick(() => {
            obj.x = obj.absX * (this.viewWidth + this.colGapPx);
            obj.y = obj.absY * (this.viewHeight + this.rowGapPx);
          });
        }
      }
      this.add.x =
        (this.imageList.length % this.colsValue) * (this.viewWidth + this.colGapPx) + "px";
      this.add.y =
        Math.floor(this.imageList.length / this.colsValue) * (this.viewHeight + this.rowGapPx) +
        "px";
      this.sortList();
    },
    delImageMp(item, index) {
      //#ifdef MP
      this.delImages(item, index);
      //#endif
    },
    sortList() {
      console.log("sortList");
      const result = [];
      let source = this.value;
      // #ifdef VUE3
      source = this.modelValue;
      // #endif

      // 按 index 排序当前显示项
      let list = this.imageList.slice();
      list.sort((a, b) => a.index - b.index);

      for (let s of list) {
        // 1) 如果 addProperties 已保存原始对象，则直接使用，保证所有原字段保留
        if (s.original && typeof s.original === "object") {
          result.push(s.original);
          continue;
        }

        // 2) 在 source 里查找匹配项（兼容传入对象或字符串）
        let item = null;
        if (Array.isArray(source) && source.length > 0) {
          item = source.find((d) => {
            try {
              return this.getSrc(d) == s.src;
            } catch (e) {
              return false;
            }
          });
        }

        if (item) {
          result.push(item);
          continue;
        }

        // 3) 未匹配到，按 keyName 或返回 src
        if (this.keyName !== null) {
          result.push({ [this.keyName]: s.src });
        } else {
          result.push(s.src);
        }
      }

      this.$emit("input", result);
      this.$emit("update:modelValue", result);
    },
    addProperties(item) {
      // item 可能是原始对象或字符串（src）
      const original = item && typeof item === "object" ? item : null;
      const src = this.getSrc(item);

      let absX = this.imageList.length % this.colsValue;
      let absY = Math.floor(this.imageList.length / this.colsValue);
      let x = absX * (this.viewWidth + this.colGapPx);
      let y = absY * (this.viewHeight + this.rowGapPx);
      this.imageList.push({
        src: src,
        original: original, // 保留原始数据对象（若存在）
        x,
        y,
        oldX: x,
        oldY: y,
        absX,
        absY,
        scale: 1,
        zIndex: 9,
        opacity: 1,
        index: this.imageList.length,
        id: this.guid(16),
        disable: false,
        offset: 0,
        moveEnd: false,
      });
      this.add.x =
        (this.imageList.length % this.colsValue) * (this.viewWidth + this.colGapPx) + "px";
      this.add.y =
        Math.floor(this.imageList.length / this.colsValue) * (this.viewHeight + this.rowGapPx) +
        "px";
    },
    nothing() {},
    rpx2px(v) {
      return (this.width * v) / 750;
    },
    guid(len = 32) {
      const chars =
        "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz".split(
          ""
        );
      const uuid = [];
      const radix = chars.length;
      for (let i = 0; i < len; i++)
        uuid[i] = chars[0 | (Math.random() * radix)];
      uuid.shift();
      return `u${uuid.join("")}`;
    },
  },
};
</script>

<style lang="scss" scoped>
.con {
  // padding: 30rpx;

  .area {
    width: 100%;

    .view {
      display: flex;
      justify-content: center;
      align-items: center;

      .area-con {
        position: relative;
        overflow: hidden;
        background: #eeeeee;
        .amount-num {
          position: absolute;
          left: 10rpx;
          top: 10rpx;
          padding: 6rpx 16rpx;
          z-index: 10;
          font-weight: 400;
          font-size: 24rpx;
          color: #ffffff;
          background: rgba(0, 0, 0, 0.4);
          border-radius: 80rpx 80rpx 80rpx 80rpx;
        }

        .pre-image {
          width: 100%;
          height: 100%;
        }

        .del-con {
          position: absolute;
          top: 0rpx;
          right: 0rpx;
          padding: 0 0 20rpx 20rpx;

          .del-wrap {
            width: 36rpx;
            height: 36rpx;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 0 0 0 10rpx;
            display: flex;
            justify-content: center;
            align-items: center;

            .del-image {
              width: 20rpx;
              height: 20rpx;
            }
          }
        }
      }
    }

    .add {
      position: absolute;
      display: flex;
      justify-content: center;
      align-items: center;

      .add-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #eeeeee;
      }
    }
  }
}
</style>
