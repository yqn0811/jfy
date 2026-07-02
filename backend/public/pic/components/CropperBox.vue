<template>
  <div>
    <el-form :inline="true" label-width="0">
      <el-form-item>
        <el-radio-group v-model="type" class="libaray-radio">
          <el-radio :label="1">自由截取</el-radio>
          <el-radio :label="2">固定比例</el-radio>
        </el-radio-group>
        <el-input
          placeholder="宽"
          class="library-input-short mr-10"
          v-model="model.autoCropWidth"
        ></el-input
        >:<el-input
          placeholder="高"
          class="library-input-short"
          v-model="model.autoCropHeight"
        ></el-input>
      </el-form-item>
    </el-form>
    <vue-cropper
      ref="cropper"
      class="library-cropper"
      :img="option.img"
      :output-size="option.size"
      :output-type="option.outputType"
      :info="true"
      :full="option.full"
      :fixed="option.fixed"
      :fixed-number="option.fixedNumber"
      :can-move="option.canMove"
      :can-move-box="option.canMoveBox"
      :fixed-box="option.fixedBox"
      :original="option.original"
      :auto-crop="option.autoCrop"
      :center-box="option.centerBox"
      :high="option.high"
      mode="contain"
      :max-img-size="option.max"
    ></vue-cropper>
    <div class="library-cropper-bottom">
      <div>
        <el-button class="library-cancel-btn" @click="refreshCrop"
          ><i class="iconfont icon-refresh"></i>刷新</el-button
        >
        <el-button class="library-cancel-btn" @click="changeScale(1)"
          ><i class="iconfont icon-enlarge"></i>放大</el-button
        >
        <el-button class="library-cancel-btn" @click="changeScale(-1)"
          ><i class="iconfont icon-narrow"></i>缩小</el-button
        >
        <el-button class="library-cancel-btn" @click="rotateLeft"
          ><i class="iconfont icon-rotateleft"></i>左旋转</el-button
        >
        <el-button class="library-cancel-btn" @click="rotateRight"
          ><i class="iconfont icon-rotateright"></i>右旋转</el-button
        >
      </div>
      <div class="text-right">
        <!-- <el-button class="library-cancel-btn pd-10-28" @click="saveImage('2')"
          >覆盖原图</el-button
        > -->
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="saveImage('1')"
          >另存</el-button
        >
      </div>
    </div>
  </div>
</template>
<script>
// import "vue-cropper/dist/index.css";
// import { VueCropper } from "vue-cropper";
/**
 * 看源码参数信息
 * https://gitcode.net/mirrors/xyxiao001/vue-cropper/-/blob/master/src/vue-cropper.vue
 */
module.exports = {
  props: {
    cropperimg: {
      default: "",
    },
    cropperinfo: {
      default: function () {
        return {};
      },
    },
  },
  // components: {
  //   VueCropper,
  // },
  data() {
    return {
      timeout: null,
      type: 1,
      model: {
        autoCropWidth: "",
        autoCropHeight: "",
      },
      option: {
        img: "",
        autoCropWidth: 0,
        autoCropHeight: 0,
        outputSize: 0.8,
        full: true, //false按原比例裁切图片，不失真
        outputType: "png", //裁剪生成图片的格式（jpeg || png || webp）
        canMove: true, //上传图片是否可以移动
        fixedBox: false, //固定截图框大小，不允许改变
        original: false, //上传图片按照原始比例渲染
        canMoveBox: true, //截图框能否拖动
        autoCrop: true, //是否默认生成截图框
        centerBox: false, //截图框是否被限制在图片里面
        fixed: false, //是否开启截图框宽高固定比例
        high: true,
        max: 99999,
        fixedNumber: [1, 1], //截图框的宽高比例
      },
    };
  },
  methods: {
    getImg() {
      let _this = this;
      this.setAvatarBase64(_this.cropperimg, (base64) => {
        _this.option.img = base64;
      });
    },
    setAvatarBase64(src, callback) {
      let _this = this;
      let image = new Image();
      image.src = src + "?v=" + Math.random();
      image.crossOrigin = "*";
      image.onload = function () {
        let base64 = _this.transBase64FromImage(image);
        callback && callback(base64);
      };
    },
    transBase64FromImage(image) {
      let canvas = document.createElement("canvas");
      canvas.width = image.width;
      canvas.height = image.height;
      let ctx = canvas.getContext("2d");
      ctx.drawImage(image, 0, 0, image.width, image.height);
      return canvas.toDataURL("image/png");
    },
    refreshCrop() {
      this.$refs.cropper.refresh();
    },
    changeScale(num) {
      num = num || 1;
      this.$refs.cropper.changeScale(num);
    },
    rotateLeft() {
      this.$refs.cropper.rotateLeft();
    },
    rotateRight() {
      this.$refs.cropper.rotateRight();
    },
    saveImage(type) {
      this.$refs.cropper.getCropBlob((data) => {
        this.$emit("save", data, this.cropperinfo, type);
      });
    },
    changeCropper() {
      this.option.fixedNumber = [];
      this.option.fixedNumber.push(this.model.autoCropWidth);
      this.option.fixedNumber.push(this.model.autoCropHeight);
      this.$refs.cropper.refresh();
    },
    clearCropperContent() {
      this.type = 1;
      this.option = {
        img: "",
        autoCropWidth: 0,
        autoCropHeight: 0,
        outputSize: 0.8,
        full: true, //false按原比例裁切图片，不失真
        outputType: "png", //裁剪生成图片的格式（jpeg || png || webp）
        canMove: true, //上传图片是否可以移动
        fixedBox: false, //固定截图框大小，不允许改变
        original: false, //上传图片按照原始比例渲染
        canMoveBox: true, //截图框能否拖动
        autoCrop: true, //是否默认生成截图框
        centerBox: false, //截图框是否被限制在图片里面
        fixed: false, //是否开启截图框宽高固定比例
        high: true,
        max: 99999,
        fixedNumber: [1, 1], //截图框的宽高比例
      };
    },
  },
  watch: {
    /**
     * 实时更新裁剪框图片
     */
    cropperimg: {
      handler(newInfo, orInfo) {
        this.option.img = ''
        this.getImg();
      },
      immediate: true,
    },
    type: {
      handler(type) {
        this.option.fixed = type == 2 ? true : false;
        if (type == 1) {
          this.option.fixedNumber = [];
          this.model.autoCropWidth = "";
          this.model.autoCropHeight = "";
          this.$refs.cropper.refresh();
        } else {
          if (this.model.autoCropWidth && this.model.autoCropHeight) {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
              this.changeCropper();
            }, 600);
          }
        }
      },
    },
    model: {
      handler(model) {
        if (model.autoCropWidth && model.autoCropHeight && this.type == 2) {
          clearTimeout(this.timeout);
          this.timeout = setTimeout(() => {
            this.changeCropper();
          }, 600);
        }
      },
      deep: true,
      immediate: true,
    },
  },
};
</script>
<style>
.library-cropper.vue-cropper {
  height: 430px;
}
.library-input-short {
  width: 56px;
  margin-left: 10px;
}
.library-input-short .el-input__inner {
  width: 56px;
  height: 34px;
  line-height: 34px;
  border-radius: 3px;
  border: 1px solid #dee1e7;
}
.library-cropper-bottom {
  display: flex;
  margin: 20px 0;
  width: 100%;
}
.library-cropper-bottom div:first-child {
  width: 70%;
}
.library-cropper-bottom div:last-child {
  width: 30%;
}
</style>
