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
          placeholder="音频名称"
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
          @change="selectAllAudio"
          class="library-checkbox"
          v-model="model.all_selected"
          v-if="type == 2"
          label="全选"
        ></el-checkbox>
        <template v-if="selectedAudio.length > 0">
          <span
            class="cl-active-color pointer"
            :class="{ 'ml-20': type == 2 }"
            @click="move"
            >{{ selectedAudio.length > 1 ? "批量" : "" }}移动</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedAudio.length > 1"
            @click="delOperate({}, 'bulk')"
            >批量删除</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedAudio.length == 1"
            @click="imgRename"
            >重命名</span
          >
        </template>
      </div>
      <div class="library-list__right__body__list">
        <template v-if="audioLists.length > 0">
          <div
            :class="['library-list__right__body__file',
              {
                'library-list__right__body__item__active':
                  item.checked,
              }]"
            v-for="(item, index) in audioLists"
            :key="item.id"
            @click="selectAudio(item,index)"
          >
            <template v-if="item.url && item.status">
              <audio
                :src="item.url"
                :ref="'audioRef' + index"
                @error="errors($event, index)"
              ></audio>
              <img class="library-audio-img" :src="operateIndex >= 0 && operateIndex == index ? '/pic/src/images/audio-stop.png' : '/pic/src/images/audio-show.png'" alt="" @click.stop="playAudio(item, index)" />
            </template>
            <template v-else>
              <img class="library-audio-img" src="/pic/src/images/audio-no.png" alt="" />
            </template>
            <div class="file-name text-hide">{{ item.title }}</div>
            <el-checkbox
              v-model="item.checked"
              v-if="item.checked"
              class="library-checkbox library-list__right__body__checkbox mr-10"
            ></el-checkbox>
            <div
              class="library-list__right__body__item__delete"
              @click.stop="delOperate(item, 'single')"
            >
              <i class="iconfont icon-del"></i>
            </div>
            <img src="/pic/src/images/online.png" class="from-online" v-if="item.source == 2">
            <div
              class="library-list__right__body__item__num"
              v-if="item.checked"
            >
              {{
                selectedAudio.indexOf(item) >= 0
                  ? selectedAudio.indexOf(item) + 1
                  : 0
              }}
            </div>
          </div>
        </template>
        <template v-else>
          <div class="library-list__right__body__list__empty">
            <img src="/pic/src/images/video-empty.png" alt="" />
            <p>暂无音频，赶紧上传吧~</p>
          </div>
        </template>
      </div>
    </div>
    <library-pagination
      :pagination.sync="pagination"
      @change-pagination="changePage"
    ></library-pagination>
    <div class="library-list__right__bottom">
      <el-button class="library-cancel-btn pd-10-28" @click="closeAudio">取消</el-button>
      <el-button type="primary" class="library-btn pd-10-28" @click="submitAudio">确定</el-button>
    </div>

    <el-dialog
      :visible="moveVisible"
      :title="(selectedAudio.length > 1 ? '批量' : '') + '移动音频'"
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
      <div>是否确认删除音频！</div>
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
        :model="audioModel"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="audioModel"
        :rules="rules"
      >
        <el-form-item label="原本名称">
          <el-input
            disabled
            v-model="renameAudio.title"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="修改名称" prop="re_title">
          <el-input
            v-model="audioModel.re_title"
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
        <el-form-item
          label="音频："
          v-if="uploadModel.source === 1"
          prop="file"
        >
          <el-upload
            :show-file-list="false"
            :http-request="uploadAudio"
            class="thumbnail-box"
            action
            :multiple="false"
            accept="audio/cd,audio/wave,audio/aiff,audio/mpeg,audio/mp3,audio/mpeg-4,audio/midi,audio/wma,audio/RealAudio,audio/vqf,audio/ogg,audio/amr,audio/ape,audio/flac,audio/aac"
          >
            <template v-if="uploadModel.file">
              <img class="audio_show" src="/pic/src/images/audio-show.png" alt="" />
            </template>
            <template v-else>
              <i class="el-icon-plus avatar-uploader-icon"></i>
            </template>
          </el-upload>
          <div class="library-alert__text" style="line-height: 2">
            支持音频类型是cd、wave、aiff、mpeg、mp3、mpeg-4、midi、wma、RealAudio、vqf、ogg、amr、ape、flac、aac,大小不要超过{{
              size / 1024 / 1024
            }}M
          </div>
        </el-form-item>
        <el-form-item
          label="音频链接："
          v-if="uploadModel.source === 2"
          prop="link"
        >
          <el-input
            v-model="uploadModel.link"
            placeholder="请输入第三方音频链接"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="音频名称：">
          <el-input
            v-model="uploadModel.title"
            class="library-input__medium"
            placeholder="请输入音频名称"
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
            @click="addAudio"
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
      <div>只能显示1个音频，默认使用第{{ selectedAudio.length }}个</div>
      <div slot="footer" class="dialog-footer">
        <el-button class="library-cancel-btn pd-10-28" @click="radioReset"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="submitAudioRes"
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
    audios: {
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
      audioUrl: "", //音频地址
      leftGroups: [], // tree结构数据
      defaultExpandKeys: [],
      audioLists: [], //音频列表
      selectedAudio: [], //当前选中音频
      caslenderOptions: [],
      props: {
        checkStrictly: true,
      },
      uploadModel: {
        source: 1,
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
      delAudioInfo: {
        pic_ids: [],
      },
      renameAudio: {}, // 重命名的对象参数
      audioModel: {
        re_title: "",
      }, // 重命名新名称
      rules: {
        link: [{ required: true, message: "请粘贴音频地址", trigger: "blur" }],
        file: { required: true, message: "请上传音频", trigger: "change" },
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
    // 提交音频
    submitAudio() {
      if (this.selectedAudio.length <= 0) {
        this.$message.error("请选择音频！");
        return false;
      }
      if (this.type == 1 && this.selectedAudio.length != 1) {
        this.radioVisible = true;
      } else {
        this.submitAudioRes();
      }
    },
    // 单选模式 重置 清空选中列表
    radioReset() {
      this.selectedAudio.map((item) => {
        // 先把图片选中样式清除
        let index = this.audioLists.indexOf(item);
        this.audioLists[index].checked = false;
      });

      this.selectedAudio = []; // 再清空选中音频 顺序不可颠倒

      this.radioVisible = false;
    },
    // 提交音频 请求
    submitAudioRes() {
      if (this.type == 1) {
        this.radioVisible = false;
      }

      if (this.selectedAudio.length <= 0) {
        this.$message.error("请选择音频！");
        return false;
      }
      this.$emit('submitAudio',this.selectedAudio)
      this.$emit('submit-audio',this.selectedAudio)
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
      this.audioLists[index]["status"] = false;
    },
    playAudio(item, index) {
      if (!item.url) {
        this.$message({ message: "暂无音频资源，请查看其他音频！" });
        return false;
      }
      let audioRef = this.$refs[`audioRef${index}`][0];
      if (audioRef.paused) {
        audioRef.play();
        this.operateIndex = index;
      } else {
        audioRef.pause();
        this.operateIndex = -1;
      }
      const audios = document.getElementsByTagName("audio");
      [].forEach.call(audios, function (i) {
        if (i !== audioRef) {
          i.pause();
          i.currentTime = 0;
        }
      });
    },
    closeAudio(){
      this.$emit("close-audio");
      this.$emit("closeAudio");
    },
    selectAudio(item,index) {
      item.checked = !item.checked;
      let audioRef = this.$refs[`audioRef${index}`][0];
      if (audioRef) {
        audioRef.pause() //勾选时禁止播放音频
      }
      this.selectFilterArr(item, "single");
    },
    selectFilterArr(item, type) {
      if (type == "clear") {
        this.selectedAudio = [];
        return false;
      }
      console.log(item)
      let find_index = this.selectedAudio.findIndex((arr) => arr.id == item.id);
      if (find_index == -1) {
        this.selectedAudio.push(item);
      } else {
        if (type == "single") {
          this.selectedAudio.splice(find_index, 1);
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
    selectAllAudio(value) {
      this.audioLists.forEach((item) => {
        item.checked = value;
        this.selectFilterArr(item, value ? "multiple" : "clear");
      });
    },
    move() {
      this.form.new_tag_id = [];
      this.moveVisible = true;
      this.form.tag_type = "audio_move";
    },
    addTagName() {
      this.$refs["form"].validate((valid) => {
        if (!valid) {
          return false;
        }
        if (this.form.tag_id) {
          this.defaultExpandKeys.push(this.form.tag_id);
        }
        if (this.form.tag_type == "audio_move") {
          this.form.pic_ids = this.selectedAudio.map((item) => {
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
      this.delAudioInfo.pic_ids = [];
      if (type == "single") {
        this.delAudioInfo.pic_ids.push(info.id);
      } else {
        this.delAudioInfo.pic_ids = this.selectedAudio.map((item) => {
          return item.id;
        });
      }
      this.delImgVisible = true;
    },
    delImgSubmit() {
      this.$emit("del-audio", this.delAudioInfo);
      this.$emit("delAudio", this.delAudioInfo);
      this.closeDiglog("delImgVisible");
    },
    // 图片重命名
    imgRename() {
      this.renameAudio = this.selectedAudio[0];
      this.renameVisible = true;
    },
    // 重命名
    rename() {
      this.$refs["audioModel"].validate((valid) => {
        if (!valid) {
          return false;
        }
        let audioArr = this.renameAudio.url.split(".");
        let type = audioArr[audioArr.length - 1];
        let data = {
          id: this.renameAudio.id,
          re_title: this.audioModel.re_title + "." + type,
        };
        this.$emit("rename", data);
      });
    },
    // 关闭重命名弹框
    closeRename() {
      this.closeDiglog("renameVisible");
      this.audioModel.rename_name = "";
    },

    // 更新完成刷新
    refreshRename() {
      let audioArr = this.renameAudio.url.split(".");
      let type = audioArr[audioArr.length - 1];
      this.audioLists.map((item) => {
        if (item.checked) {
          item.title = this.audioModel.re_title + "." + type;
        }
      });
      this.closeRename();
    },
    uploadAudio(file) {
      let fileInfo = file.file;
      const isLt10M = fileInfo.size > this.size;
      if (
        [
          "audio/cd",
          "audio/wave",
          "audio/aiff",
          "audio/mpeg",
          "audio/mp3",
          "audio/mpeg-4",
          "audio/midi",
          "audio/wma",
          "audio/RealAudio",
          "audio/vqf",
          "audio/ogg",
          "audio/amr",
          "audio/ape",
          "audio/flac",
          "audio/aac"
        ].indexOf(fileInfo.type) === -1
      ) {
        this.$message.error("请上传正确的音频格式！");
        return false;
      }
      if (isLt10M) {
         let pic_limit = this.size / 1024 / 1024;
        this.$message.error(`上传音频大小不能超过${pic_limit}M！`);
        return false;
      }

      this.audioUrl = fileInfo;
      this.uploadModel.file = window.URL.createObjectURL(fileInfo);
    },
    /**
     * 上传音频
     */
    addAudio() {
      this.$refs["uploadRef"].validate((valid) => {
        if (!valid) {
          return false;
        }
        this.formData = new FormData();
        this.formData.append("form", 3);

        if (this.uploadModel.source === 1) {
          this.formData.append("file", this.audioUrl);
        } else if (this.uploadModel.source === 2) {
          this.formData.append("link", this.uploadModel.link);
        }
        this.formData.append("source", this.uploadModel.source);
        this.formData.append("title", this.uploadModel.title);
        this.$emit("upload-audio", this.formData);
        this.$emit("uploadAudio", this.formData);
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
    audios: {
      handler(arrs) {
        this.audioLists = [];
        arrs.map((item) => {
          this.audioLists.push(
            Object.assign({}, item, { checked: false, status: true })
          );
        });
      },
      immediate: true,
    },
    uploadVisible(visible) {
      if (!visible) {
        this.$refs["uploadRef"].clearValidate([]);
        this.audioUrl = ''
        this.uploadModel = {
          source: 1,
          file: "",
          title: "",
          link: "",
        };
      }
    },
  },
};
</script>

<style scoped>
.library-list__right__body__file {
  width: calc(50% - 10px);
  border: 1px solid var(--border-normal-color);
  border-radius: 4px;
  cursor: pointer;
  padding: 16px 15px 16px 17px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 400;
  color: var(--font-auxiliary-color);
  position: relative;
  display: flex;
  align-items: center;
  box-sizing: border-box;
}
.library-list__right__body__file:nth-child(2n) {
  margin-left: 20px;
}
.library-list__right__body__file.library-list__right__body__item__active {
  border-width: 2px;
  border-color: var(--border-active-color);
}
.library-list__right__body__file .file-name {
  width: 308px;
  padding-right: 30px;
  box-sizing: border-box;
}
.library-audio-img {
  width: 34px;
  height: 45px;
  margin-right: 10px;
}
</style>