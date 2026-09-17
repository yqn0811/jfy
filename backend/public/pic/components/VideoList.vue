<template>
  <div style="width: 100%; height: 100%">
    <div class="library-list__right__top">
      <div class="library-list__right__top__left">
        <el-button
          type="primary"
          class="library-btn pd-10-20"
          @click.native="uploadShow(1)"
        >
          本地上传
        </el-button>

        <el-button
          class="library-cancel-btn pd-10-20"
          @click.native="uploadShow(2)"
          >链接提取</el-button
        >
        <span class="cl-color ml-6"
          >大小不要超过{{ size / 1024 / 1024 }}M</span
        >
      </div>
      <div class="library-list__right__top__right">
        <el-input
          class="library-input"
          placeholder="视频名称"
          v-model="model.pic_name"
          @keyup.enter.native="searchList()"
          @clear="searchList()"
          clearable
        >
          <i slot="prefix" class="iconfont icon-search"></i>
        </el-input>
        <el-date-picker
          @change="searchList()"
          class="library-input-date"
          value-format="yyyy-MM-dd"
          v-model="model.date"
          clearable
          type="daterange"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
        >
        </el-date-picker>
      </div>
    </div>
    <div class="library-list__right__body" v-loading="loading">
      <div style="height: 20px">
        <el-checkbox
          @change="selectAllVideo"
          class="library-checkbox"
          v-model="model.all_selected"
          v-if="type == 2"
          label="全选"
        ></el-checkbox>
        <template v-if="selectedVideo.length > 0">
          <span
            class="cl-active-color pointer"
            :class="{ 'ml-20': type == 2 }"
            @click="move"
            >{{ selectedVideo.length > 1 ? "批量" : "" }}移动</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedVideo.length > 1"
            @click="delOperate({}, 'bulk')"
            >批量删除</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedVideo.length == 1"
            @click="imgRename"
            >重命名</span
          >
        </template>
      </div>
      <div class="library-list__right__body__list">
        <template v-if="videoLists.length > 0">
          <div
            v-for="(item, index) in videoLists"
            :key="item.id"
            @click="selectVideo(item,index)"
            :class="[
              'library-list__right__body__item',
              { 'library-list__right__body__empty': !item.src },
            ]"
          >
            <div
              :class="[
                'library-list__right__body__item__content',
                {
                  'library-list__right__body__item__content__active':
                    item.checked,
                },
              ]"
            >
              <div v-if="item.url && item.status" class="video-block">
                <video
                  :src="item.url"
                  :poster="item.cover_url"
                  :ref="'videoRef' + index"
                  @error="errors($event, index)"
                  controls="true"
                ></video>
                <i
                  class="iconfont icon-24gf-playCircle pointer"
                  :class="{ 'fade-out': operateIndex >= 0 && operateIndex == index }"
                  @click.stop="playVideo(item, index)"
                ></i>
              </div>
              <img src="/pic/src/images/video-broke.png" alt="" v-else />
              <el-checkbox
                v-model="item.checked"
                class="library-checkbox"
              ></el-checkbox>
              <img src="/pic/src/images/online.png" class="from-online" v-if="item.source == 2">
              <div
                class="library-list__right__body__item__num"
                v-if="item.checked"
              >
                {{
                  selectedVideo.indexOf(item) >= 0
                    ? selectedVideo.indexOf(item) + 1
                    : 0
                }}
              </div>
              <div
                class="library-list__right__body__item__delete"
                @click.stop="delOperate(item, 'single')"
              >
                <i class="iconfont icon-del"></i>
              </div>
            </div>
            <div class="name text-hide" :title="item.title">
              {{ item.title }}
            </div>
          </div>
        </template>
        <template v-else>
          <div class="library-list__right__body__list__empty">
            <img src="/pic/src/images/video-empty.png" alt="" />
            <p>暂无视频，赶紧上传吧~</p>
          </div>
        </template>
      </div>
    </div>
    <library-pagination
      :pagination.sync="pagination"
      @change-pagination="changePage"
    ></library-pagination>
    <div class="library-list__right__bottom">
      <el-button class="library-cancel-btn pd-10-28" @click="closeVideo">取消</el-button>
      <el-button type="primary" class="library-btn pd-10-28"  @click="submitVideo">确定</el-button>
    </div>

    <el-dialog
      :visible="moveVisible"
      :title="(selectedVideo.length > 1 ? '批量' : '') + '移动视频'"
      @close="closeDiglog('moveVisible')"
      custom-class="library-dialog"
      :close-on-click-modal="false"
      width="520px"
    >
      <el-form
        :model="form"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="form"
        :rules="rules"
      >
        <el-form-item label="原分类">
          <el-input
            disabled
            v-model="form.old_tag_name"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="移动至" prop="new_tag_id">
          <el-cascader
            class="library-input__medium"
            :options="caslenderOptions"
            clearable
            :props="props"
            :show-all-levels="false"
            v-model="form.new_tag_id"
          ></el-cascader>
        </el-form-item>
        <el-form-item>
          <el-button
            class="library-cancel-btn pd-10-28"
            @click="closeDiglog('moveVisible')"
            >取消</el-button
          >
          <el-button
            class="library-btn pd-10-28"
            type="primary"
            @click="addTagName"
            >确定</el-button
          >
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog
      :visible="delImgVisible"
      title="提示"
      @close="closeDiglog('delImgVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>是否确认删除视频！</div>
      <div slot="footer" class="dialog-footer">
        <el-button
          class="library-cancel-btn pd-10-28"
          @click="closeDiglog('delImgVisible')"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="delImgSubmit"
          >确定</el-button
        >
      </div>
    </el-dialog>

    <el-dialog
      :visible="renameVisible"
      title="重命名"
      @close="closeRename"
      custom-class="library-dialog"
      :close-on-click-modal="false"
      width="520px"
    >
      <el-form
        :model="videoModel"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="videoModel"
        :rules="rules"
      >
        <el-form-item label="原本名称">
          <el-input
            disabled
            v-model="renameVideo.title"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="修改名称" prop="re_title">
          <el-input
            v-model="videoModel.re_title"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item>
          <el-button
            class="library-cancel-btn pd-10-28"
            @click="closeDiglog('renameVisible')"
            >取消</el-button
          >
          <el-button class="library-btn pd-10-28" type="primary" @click="rename"
            >确定</el-button
          >
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog
      :visible="uploadVisible"
      :title="uploadModel.source == 1 ? '本地上传' : '链接提取'"
      custom-class="library-dialog"
      :close-on-click-modal="false"
      @close="closeDiglog('uploadVisible')"
      width="520px"
    >
      <el-form
        :model="uploadModel"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="uploadRef"
        :rules="rules"
      >
        <el-form-item label="视频封面：">
          <el-upload
            action
            :http-request="uploadFile"
            accept="image/jpeg,image/png"
            class="thumbnail-box"
            :show-file-list="false"
          >
            <img
              class="avatar"
              :src="uploadModel.cover"
              v-if="uploadModel.cover"
            />
            <i v-else class="el-icon-plus avatar-uploader-icon"></i>
          </el-upload>
          <div class="library-alert__text">支持图片类型是jpg、jpeg、png</div>
        </el-form-item>
        <el-form-item
          label="视频："
          v-if="uploadModel.source === 1"
          prop="file"
        >
          <el-upload
            :show-file-list="false"
            :http-request="uploadVideo"
            class="thumbnail-box"
            action
            :multiple="false"
            accept="video/avi,video/wmv,video/mpeg,video/mp4,video/m4v,video/mov,video/asf,video/flv,video/f4v,video/rmvb,video/rm,video/3gp,video/vob"
          >
            <template v-if="uploadModel.file">
              <video class="avatar" :src="uploadModel.file" />
            </template>
            <template v-else>
              <i class="el-icon-plus avatar-uploader-icon"></i>
            </template>
          </el-upload>
          <div class="library-alert__text" style="line-height: 2">
            支持视频类型是avi、wmv、mpeg、mp4、m4v、mov、asf、flv、f4v、rmvb、rm、3gp、vob,大小不要超过{{
              size / 1024 / 1024
            }}M
          </div>
        </el-form-item>
        <el-form-item
          label="视频链接："
          v-if="uploadModel.source === 2"
          prop="link"
        >
          <el-input
            v-model="uploadModel.link"
            placeholder="请输入第三方视频链接"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="视频名称：">
          <el-input
            v-model="uploadModel.title"
            class="library-input__medium"
            placeholder="请输入视频名称"
          ></el-input>
        </el-form-item>
        <el-form-item>
          <el-button
            class="library-cancel-btn pd-10-28"
            @click="closeDiglog('uploadVisible')"
            >取消</el-button
          >
          <el-button
            class="library-btn pd-10-28"
            type="primary"
            @click="addVideo"
            >确定</el-button
          >
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog
      :visible="radioVisible"
      title="提示"
      @close="closeDiglog('radioVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>只能显示1个视频，默认使用第{{ selectedVideo.length }}个</div>
      <div slot="footer" class="dialog-footer">
        <el-button class="library-cancel-btn pd-10-28" @click="radioReset"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="submitVideoRes"
          >确定</el-button
        >
      </div>
    </el-dialog>
  </div>
</template>
<script>
module.exports = {
  props: {
    type: {
      type: [Number, String],
      default: 1,
    },
    lefts: {
      //格式可能需要处理
      type: Array,
      default: function () {
        return [];
      },
    },
    loading: {
      type: Boolean,
      default: false,
    },
    size: {
      type: Number,
      default: 2097152,
    },
    pagination: {
      type: Object,
      default: function () {
        return {
          current_page: 1,
          per_page: 18,
          last_page: 1,
          total: 1,
        };
      },
    },
    size: {
      type: Number,
      default: 2097152,
    },
    videos: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  data() {
    let fileNameValidate = (rule, value, callback) => {
      let reg = /\./g;
      if (!reg.exec(value)) {
        callback();
      } else {
        callback(new Error("名称不能包含非法字符."));
      }
    };
    return {
      treeLoading: false, // tree结构加载
      extractVisible: false,
      moveVisible: false,
      delImgVisible: false,
      renameVisible: false,
      uploadVisible: false,
      radioVisible:false,
      operateIndex: -1,
      imgUrl: "", //图片地址
      videoUrl: "", //视频地址
      leftGroups: [], // tree结构数据
      defaultExpandKeys: [],
      videoLists: [], //视频列表
      selectedVideo: [], //当前选中视频
      caslenderOptions: [],
      fileList: [],
      props: {
        checkStrictly: true,
      },
      uploadModel: {
        source: 1,
        cover: "",
        file: "",
        title: "",
        link: "",
      },
      form: {
        url: "",
        keyword: "",
        tag_id: "",
        old_tag_name: "全部",
        new_tag_name: "",
        tag_type: "parent",
        new_tag_id: [],
      },
      formData: "",
      model: {
        pic_name: "",
        date: "",
      },
      currentNode: {
        id: "",
      },
      delVideoInfo: {
        pic_ids: [],
      },
      renameVideo: {}, // 重命名的对象参数
      videoModel: {
        re_title: "",
      }, // 重命名新名称
      rules: {
        link: [{ required: true, message: "请粘贴视频地址", trigger: "blur" }],
        file: { required: true, message: "请上传视频", trigger: "change" },
        re_title:[ { required: true, message: "请输入新名称", trigger: "blur" }, { validator: fileNameValidate, trigger: "blur" },],
      },
    };
  },
  components: {
    LibraryPagination: httpVueLoader("./LibraryPagination.vue"),
  },
  methods: {
    uploadShow(type) {
      this.uploadModel.source = type;
      this.uploadVisible = true;
    },

    changeTagName(name) {
      this.form.old_tag_name = name;
    },
    extract() {
      this.form.url = "";
      this.extractVisible = true;
    },
    extractImage() {
      this.$refs["form"].validate((valid) => {
        if (!valid) {
          return false;
        }
        this.form.tag_type = "remote";
        this.$emit("add-tag", this.form);
        this.$emit("addTag", this.form);
        this.closeDiglog("extractVisible");
      });
    },
    closeDiglog(type) {
      this[type] = false;
    },
    // 提交视频
    submitVideo() {
      if (this.selectedVideo.length <= 0) {
        this.$message.error("请选择视频！");
        return false;
      }
      if (this.type == 1 && this.selectedVideo.length != 1) {
        this.radioVisible = true;
      } else {
        this.submitVideoRes();
      }
    },
    // 单选模式 重置 清空选中列表
    radioReset() {
      this.selectedVideo.map((item) => {
        // 先把图片选中样式清除
        let index = this.videoLists.indexOf(item);
        this.videoLists[index].checked = false;
      });

      this.selectedVideo = []; // 再清空选中视频 顺序不可颠倒

      this.radioVisible = false;
    },
    // 提交视频 请求
    submitVideoRes() {
      if (this.type == 1) {
        this.radioVisible = false;
      }

      if (this.selectedVideo.length <= 0) {
        this.$message.error("请选择视频！");
        return false;
      }
       this.$emit('submitVideo',this.selectedVideo)
      this.$emit('submit-video',this.selectedVideo)
    },
    searchList() {
      let params = {
        page: 1,
      };
      params = Object.assign(params, this.model);
      params = Object.assign(params, this.model);
      this.$emit("get-list", params);
    },
    errors(e, index) {
      this.videoLists[index]["status"] = false;
    },
    playVideo(item, index) {
      if (!item.url) {
        this.$message({ message: "暂无视频资源，请查看其他视频！" });
        return false;
      }
      let videoRef = this.$refs[`videoRef${index}`][0];
      if (videoRef.paused) {
        videoRef.play();
        this.operateIndex = index;
      } else {
        videoRef.pause();
        this.operateIndex = "";
      }
      const videos = document.getElementsByTagName("video");
      [].forEach.call(videos, function (i) {
        if (i !== videoRef) {
          i.pause();
          i.currentTime = 0;
        }
      });
    },
    closeVideo(){
      this.$emit("close-video");
      this.$emit("closeVideo");
    },
    selectVideo(item,index) {
      item.checked = !item.checked;
       let videoRef = this.$refs[`videoRef${index}`][0];
      if (videoRef) {
        videoRef.pause() //勾选时禁止播放视频
      }
      this.selectFilterArr(item, "single");
    },
    selectFilterArr(item, type) {
      if (type == "clear") {
        this.selectedVideo = [];
        return false;
      }
      console.log(item)
      let find_index = this.selectedVideo.findIndex((arr) => arr.id == item.id);
      if (find_index == -1) {
        this.selectedVideo.push(item);
      } else {
        if (type == "single") {
          this.selectedVideo.splice(find_index, 1);
        }
      }
    },
    /**
     * 提交分页修改
     */
    changePage(params) {
      // this.per_page = params.per_page;
      this.$emit("change-parent-pagination", params);
      this.$emit("changeParentPagination", params);
    },
    selectAllVideo(value) {
      this.videoLists.forEach((item) => {
        item.checked = value;
        this.selectFilterArr(item, value ? "multiple" : "clear");
      });
    },
    move() {
      this.form.new_tag_id = [];
      this.moveVisible = true;
      this.form.tag_type = "video_move";
    },
    addTagName() {
      this.$refs["form"].validate((valid) => {
        if (!valid) {
          return false;
        }
        if (this.form.tag_id) {
          this.defaultExpandKeys.push(this.form.tag_id);
        }
        if (this.form.tag_type == "video_move") {
          this.form.pic_ids = this.selectedVideo.map((item) => {
            return item.id;
          });
        }
        this.$emit("add-tag", this.form);
        this.$emit("addTag", this.form);
        this.closeDiglog("moveVisible");
      });
    },
    /**
     * 删除，批量删除
     */
    delOperate(info, type) {
      this.delVideoInfo.pic_ids = [];
      if (type == "single") {
        this.delVideoInfo.pic_ids.push(info.id);
      } else {
        this.delVideoInfo.pic_ids = this.selectedVideo.map((item) => {
          return item.id;
        });
      }
      this.delImgVisible = true;
    },
    delImgSubmit() {
      this.$emit("del-video", this.delVideoInfo);
      this.$emit("delVideo", this.delVideoInfo);
      this.closeDiglog("delImgVisible");
    },
    // 图片重命名
    imgRename() {
      this.renameVideo = this.selectedVideo[0];
      this.renameVisible = true;
    },
    // 重命名
    rename() {
      this.$refs["videoModel"].validate((valid) => {
        if (!valid) {
          return false;
        }
        let videoArr = this.renameVideo.url.split(".");
        let type = videoArr[videoArr.length - 1];
        let data = {
          id: this.renameVideo.id,
          re_title: this.videoModel.re_title + "." + type,
        };
        this.$emit("rename", data);
      });
    },
    // 关闭重命名弹框
    closeRename() {
      this.closeDiglog("renameVisible");
      this.videoModel.rename_name = "";
    },

    // 更新完成刷新
    refreshRename() {
      let videoArr = this.renameVideo.url.split(".");
      let type = videoArr[videoArr.length - 1];
      this.videoLists.map((item) => {
        if (item.checked) {
          item.title = this.videoModel.re_title + "." + type;
        }
      });
      this.closeRename();
    },
    uploadFile(file) {
      let fileInfo = file.file;
      if (!/^image\/\w+$/.test(fileInfo.type)) {
        this.$message.error("请上传图片格式！");
        return false;
      }
      if (fileInfo.size > this.size) {
        let pic_limit = this.size / 1024 / 1024;
        this.$message.error(
          `上传图片 ${fileInfo.name} 不能超过${pic_limit}M；`
        );
        return false;
      }

      this.imgUrl = fileInfo;
      this.uploadModel.cover = window.URL.createObjectURL(file.file);
    },
    uploadVideo(file) {

      let fileInfo = file.file;
      const isLt10M = fileInfo.size > this.size;
      if (
        [
          "video/avi",
          "video/wmv",
          "video/mpeg",
          "video/mp4",
          "video/m4v",
          "video/mov",
          "video/asf",
          "video/flv",
          "video/f4v",
          "video/rmvb",
          "video/rm",
          "video/3gp",
          "video/vob",
          "video/quicktime"
        ].indexOf(fileInfo.type) === -1
      ) {
        this.$message.error("请上传正确的视频格式！");
        return false;
      }
      if (isLt10M) {
         let pic_limit = this.size / 1024 / 1024;
        this.$message.error(`上传视频大小不能超过${pic_limit}M！`);
        return false;
      }

      this.videoUrl = fileInfo;
      this.uploadModel.file = window.URL.createObjectURL(fileInfo);
    },
    /**
     * 上传视频
     */
    addVideo() {
      this.$refs["uploadRef"].validate((valid) => {
        if (!valid) {
          return false;
        }
        this.formData = new FormData();
        this.formData.append("cover", this.imgUrl);
        this.formData.append("form", 1);

        if (this.uploadModel.source === 1) {
          this.formData.append("file", this.videoUrl);
        } else if (this.uploadModel.source === 2) {
          this.formData.append("link", this.uploadModel.link);
        }
        this.formData.append("source", this.uploadModel.source);
        this.formData.append("title", this.uploadModel.title);
        this.$emit("upload-video", this.formData);
        this.$emit("uploadVideo", this.formData);
      });
    },
  },
  watch: {
    lefts: {
      handler(arr) {
        if (arr.length > 0) {
          //currentNode 存在且不等于全部且当前激活项不等于shanchuxiang
          this.currentNode =
            this.currentNode.id != -1 &&
            this.currentNode.id &&
            this.currentNode.id != this.delInfo.id
              ? this.currentNode
              : arr[0];
          this.caslenderOptions = filterNoChildData(arr);
        } else {
          this.caslenderOptions = [];
        }
      },
      immediate: true,
    },
    videos: {
      handler(arrs) {
        this.videoLists = [];
        arrs.map((item) => {
          this.videoLists.push(
            Object.assign({}, item, { checked: false, status: true })
          );
        });
      },
      immediate: true,
    },
    uploadVisible(visible) {
      if (!visible) {
        this.$refs["uploadRef"].clearValidate([]);
        this.imgUrl = ''
        this.videoUrl = ''
        this.uploadModel = {
          source: 1,
          cover: "",
          file: "",
          title: "",
          link: "",
        };
      }
    },
  },
};
</script>
