<template>
  <div style="width: 100%; height: 100%">
    <div class="library-list__right__top">
      <div class="library-list__right__top__left">
        <el-upload
          :show-file-list="false"
          action
          :http-request="uploadFile"
          multiple
          accept=".txt,.PDF,.pdf,.docx,.doc,.xls,.xlsx,.zip,.rar,.exe,.gz"
          ref="dynamic"
          style="display: inline-block; margin-right: 10px"
          ><el-button type="primary" class="library-btn pd-10-20">
            本地上传
          </el-button></el-upload
        >
        <el-button class="library-cancel-btn pd-10-20" @click.native="extract"
          >链接提取</el-button
        >
      </div>
      <div class="library-list__right__top__right">
        <el-input
          class="library-input"
          placeholder="文档名称"
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
          class="library-checkbox"
          v-model="model.all_selected"
          v-if="type == 2"
          label="全选"
        ></el-checkbox>
        <template v-if="selectedFile.length > 0">
          <span
            class="cl-active-color pointer"
            :class="{ 'ml-20': type == 2 }"
            @click="move"
            >{{ selectedFile.length > 1 ? "批量" : "" }}移动</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedFile.length > 1"
            @click="delOperate({}, 'bulk')"
            >批量删除</span
          >
          <span
            class="ml-20 cl-active-color pointer"
            v-if="selectedFile.length == 1"
            @click="fileRename"
            >重命名</span
          >
        </template>
      </div>
      <div class="library-list__right__body__list">
        <template v-if="fileLists.length">
          <div
            :class="['library-list__right__body__file']"
            v-for="(item, index) in fileLists"
            :key="index"
            @click="selectFile(item)"
          >
            <el-checkbox
              v-model="item.checked"
              v-if="item.checked"
              class="library-checkbox library-list__right__body__checkbox mr-10"
            ></el-checkbox>
            <span
              :class="[
                'library-file-icon',
                filterFileClass(item.type)['class_name'],
              ]"
            >
              <i
                :class="['iconfont', , filterFileClass(item.type)['icon']]"
              ></i>
            </span>

            <span class="file-name text-hide">{{ item.title }}</span>
            <div
              class="library-list__right__body__item__delete"
              @click.stop="delOperate(item, 'single')"
            >
              <i class="iconfont icon-del"></i>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="library-list__right__body__list__empty">
            <img src="/pic/src/images/file-empty.png" alt="" />
            <p>暂无文件，赶紧上传吧~</p>
          </div>
        </template>
      </div>
    </div>
    <library-pagination
      :pagination.sync="pagination"
      @change-pagination="changePage"
    ></library-pagination>
    <div class="">
      <el-button class="library-cancel-btn pd-10-28" @click="closeFile"
        >取消</el-button
      >
      <el-button type="primary" class="library-btn pd-10-28" @click="submitFile">确定</el-button>
    </div>
    <el-dialog
      :visible="extractVisible"
      title="链接提取"
      :close-on-click-modal="false"
      @close="closeDiglog('extractVisible')"
      custom-class="library-dialog"
      width="900px"
    >
      <el-form
        :model="form"
        class="library-form"
        label-width="125px"
        label-position="right"
        ref="form"
        :rules="rules"
      >
        <el-form-item label="文件地址" prop="url">
          <el-input
            v-model="form.url"
            placeholder="请在此处粘贴文件地址"
            class="library-input__long"
            clearable
          ></el-input>

          <div class="library-alert__text">
            需要http://............大小不要超过{{
              size / 1024 / 1024
            }}M，支持文件类型.pdf,.docx,.doc,.xls,.xlsx,.pptx,.ppt
          </div>
        </el-form-item>
        <el-form-item>
          <el-button
            class="library-cancel-btn pd-10-28"
            @click="closeDiglog('extractVisible')"
            >取消</el-button
          >
          <el-button
            class="library-btn pd-10-28"
            type="primary"
            @click="extractImage"
            >提取</el-button
          >
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog
      :visible="moveVisible"
      :title="(selectedFile.length > 1 ? '批量' : '') + '移动文件'"
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
      :visible="renameVisible"
      title="重命名"
      @close="closeRename"
      custom-class="library-dialog"
      :close-on-click-modal="false"
      width="520px"
    >
      <el-form
        :model="fileModel"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="fileModel"
        :rules="rules"
      >
        <el-form-item label="原本名称">
          <el-input
            disabled
            v-model="renameFile.title"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="修改名称" prop="re_title">
          <el-input
            v-model="fileModel.re_title"
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
      :visible="delFileVisible"
      title="提示"
      @close="closeDiglog('delFileVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>
        是否确认删除{{ delFilesInfo.pic_ids.length == 1 ? "该" : "" }}文件！
      </div>
      <div slot="footer" class="dialog-footer">
        <el-button
          class="library-cancel-btn pd-10-28"
          @click="closeDiglog('delFileVisible')"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="delFileSubmit"
          >确定</el-button
        >
      </div>
    </el-dialog>

    <el-dialog
      :visible="radioVisible"
      title="提示"
      @close="closeDiglog('radioVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>只能显示1个文件，默认使用第{{ selectedFile.length }}个</div>
      <div slot="footer" class="dialog-footer">
        <el-button class="library-cancel-btn pd-10-28" @click="radioReset"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="submitFileRes"
          >确定</el-button
        >
      </div>
    </el-dialog>
  </div>
</template>
<script>
module.exports = {
  props: {
    size: {
      type: Number,
      default: 2097152,
    },
    type: {
      type: Number | String,
      default: 1,
    },
    lefts: {
      //格式可能需要处理
      type: Array,
      default: function () {
        return [];
      },
    },
    files: {
      type: Array,
      default: function () {
        return [];
      },
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
  },
  components: {
    LibraryPagination: httpVueLoader("./LibraryPagination.vue"),
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
      extractVisible: false,
      loading: false,
      moveVisible: false,
      renameVisible: false, // 重命名弹框
      delFileVisible: false,
      radioVisible:false,
      selectedFile: [],
      caslenderOptions: [],
      fileLists: [],
      fileData: [],
      delFilesInfo: {
        pic_ids: [],
      },
      props: {
        checkStrictly: true,
      },
      currentNode: {
        id: "",
      },
      renameFile: {}, // 重命名的对象参数
      fileModel: {
        re_title: "",
      },
      model: {
        pic_name: "",
        date: "",
          all_selected: false,
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
      rules: {
        url: [{ required: true, message: "请粘贴文件地址", trigger: "blur" }],
        new_tag_id: {
          required: true,
          message: "请选择新分类",
          trigger: "change",
        },
        re_title: [
          { required: true, message: "请输入新名称", trigger: "blur" },
          { validator: fileNameValidate, trigger: "blur" },
        ],
      },
    };
  },
  methods: {
    uploadFile(file) {
      this.fileData.push(file.file);
    },
    extract() {
      this.form.url = "";
      this.extractVisible = true;
    },
    changeTagName(name) {
      this.form.old_tag_name = name;
    },
    extractImage() {
      this.$refs["form"].validate((valid) => {
        if (!valid) {
          return false;
        }
        let params = {
          link: this.form.url,
          source: 2,
          form: 2,
          tag_type: "remote_file",
        };
        this.$emit("add-tag", params);
        this.$emit("addTag", params);
        this.closeDiglog("extractVisible");
      });
    },
    closeDiglog(type) {
      this[type] = false;
    },
    // 提交文件
    submitFile() {
      if (this.selectedFile.length <= 0) {
        this.$message.error("请选择文件！");
        return false;
      }
      if (this.type == 1 && this.selectedFile.length != 1) {
        this.radioVisible = true;
      } else {
        this.submitFileRes();
      }
    },
    // 单选模式 重置 清空选中列表
    radioReset() {
      this.selectedFile.map((item) => {
        // 先把文件选中样式清除
        let index = this.fileLists.indexOf(item);
        this.fileLists[index].checked = false;
      });

      this.selectedFile = []; // 再清空选中文件 顺序不可颠倒

      this.radioVisible = false;
    },
    // 提交文件 请求
    submitFileRes() {
      if (this.type == 1) {
        this.radioVisible = false;
      }

      if (this.selectedFile.length <= 0) {
        this.$message.error("请选择文件！");
        return false;
      }
       this.$emit('submitFile',this.selectedFile)
      this.$emit('submit-file',this.selectedFile)
    },
    closeFile() {
      this.$emit("close-file");
      this.$emit("closeFile");
    },
    move() {
      this.form.new_tag_id = [];
      this.moveVisible = true;
      this.form.tag_type = "file_move";
    },
    addTagName() {
      this.$refs["form"].validate((valid) => {
        if (!valid) {
          return false;
        }
        if (this.form.tag_id) {
          this.defaultExpandKeys.push(this.form.tag_id);
        }
        // this.$nextTick(function () {
        //   this.$refs.libraryTree.setCurrentKey(this.currentNode.id);
        // });
        if (this.form.tag_type == "file_move") {
          this.form.pic_ids = this.selectedFile.map((item) => {
            return item.id;
          });
        }
        this.$emit("add-tag", this.form);
        this.$emit("addTag", this.form);
        this.closeDiglog("moveVisible");
      });
    },
    filterFileClass(type) {
      let obj = {
        icon: "",
        class_name: "",
      };
      if (type == "doc" || type == "docx") {
        obj.icon = "icon-doc";
        obj.class_name = "library-file-icon-word";
      } else if (type == "ppt" || type == "pptx") {
        obj.icon = "icon-ppt";
        obj.class_name = "library-file-icon-ppt";
      } else if (type == "xls" || type == "xlsx") {
        obj.icon = "icon-xls";
        obj.class_name = "library-file-icon-excel";
      } else if (type == "pdf") {
        obj.icon = "icon-pdf";
        obj.class_name = "library-file-icon-pdf";
      } else if (type == "txt") {
        obj.icon = "icon-txt";
        obj.class_name = "library-file-icon-txt";
      } else {
        obj.class_name = "icon-file";
        obj.class_name = "library-file-icon-txt";
      }
      return obj;
    },
    selectFile(item) {
      item.checked = !item.checked;
      this.selectFilterArr(item, "single");
    },
    selectFilterArr(item, type) {
      if (type == "clear") {
        this.selectedFile = [];
        return false;
      }
      let find_index = this.selectedFile.findIndex((arr) => arr.id == item.id);
      if (find_index == -1) {
        this.selectedFile.push(item);
      } else {
        if (type == "single") {
          this.selectedFile.splice(find_index, 1);
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
    delOperate(info, type) {
      this.delFilesInfo.pic_ids = [];
      if (type == "single") {
        this.delFilesInfo.pic_ids.push(info.id);
      } else {
        this.delFilesInfo.pic_ids = this.selectedFile.map((item) => {
          return item.id;
        });
      }
      this.delFileVisible = true;
    },
    delFileSubmit() {
      this.$emit("del-file", this.delFilesInfo);
      this.$emit("delFile", this.delFilesInfo);
      this.closeDiglog("delFileVisible");
    },
    // 文件重命名
    fileRename() {
      this.renameFile = this.selectedFile[0];
      this.renameVisible = true;
    },
    // 重命名
    rename() {
      this.$refs["fileModel"].validate((valid) => {
        if (!valid) {
          return false;
        }
        let fileArr = this.renameFile.url.split(".");
        let type = fileArr[fileArr.length - 1];
        let data = {
          id: this.renameFile.id,
          re_title: this.fileModel.re_title + "." + type,
        };
        this.$emit("rename", data);
      });
    },
    // 关闭重命名弹框
    closeRename() {
      this.closeDiglog("renameVisible");
      this.fileModel.re_title = "";
    },

    // 更新完成刷新
    refreshRename() {
      let fileArr = this.renameFile.url.split(".");
      let type = fileArr[fileArr.length - 1];
      this.fileLists.map((item) => {
        if (item.checked) {
          item.title = this.fileModel.re_title + "." + type;
        }
      });
      this.closeRename();
    },
    dealSuffix(url) {
      let arr = url.split(".");
      return arr[arr.length - 1];
    },
    searchList() {
      let params = {
        page: 1,
      };
      params = Object.assign(params, this.model);
      params = Object.assign(params, this.model);
      this.$emit("get-list", params);
    },
    /**
     * @formData  array ['uploadfile'+index]
     */
    getSynamic() {
      this.formData = new FormData();
      this.fileData.forEach((item, index) => {
        this.formData.append(`file[]`, item);
      });
      this.formData.append("title", "");
      this.formData.append("form", 2);
      this.formData.append("source", 1);
      this.$emit("upload-file", this.formData);
      this.$emit("uploadFile", this.formData);
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
    files: {
      handler(arrs) {
        this.fileLists = [];
        if (arrs.length) {
          arrs.map((item) => {
            this.fileLists.push(
              Object.assign({}, item, {
                checked: false,
                type: item.url ? this.dealSuffix(item.url) : "",
              })
            );
          });
        }
      },
      immediate: true,
    },
    fileData: {
      handler(arrs) {
        console.log(arrs);
        if (arrs.length > 12) {
          this.$message.error("最多同时上传12个！");
          this.fileData = [];
          return false;
        }
        if (arrs.length > 0) {
          this.timerOut = setTimeout(() => {
            let messages = [];
            arrs.forEach((file) => {
              if (file.size / 1024 / 1024 > 50) {
                messages.push(`上传文件大小不能超过50M`);
                return false;
              }
            });
            if (messages.length > 0) {
              this.$message.error(messages.join(""));
              this.fileData = [];
              return false;
            }
            this.getSynamic();
          }, 600); //0.6秒之后将批量上传文件格式成数组上传
        }
      },
    },
  },
};
</script>
<style scoped>
.library-list__right__body__file {
  width: calc(50% - 45px);
  border: 1px solid var(--border-normal-color);
  border-radius: 4px;
  cursor: pointer;
  padding: 16px 15px 16px 17px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 400;
  color: var(--font-auxiliary-color);
  position: relative;
}

.library-list__right__body__file .file-name {
  display: inline-block;
  max-width: 280px;
}
.library-file-icon {
  display: inline-block;
  width: 42px;
  height: 42px;
  text-align: center;
  line-height: 42px;

  border-radius: 4px;
  color: var(--font-white-color);
  margin-right: 10px;
}
.library-file-icon .iconfont {
  font-size: 24px;
}
.library-file-icon-word {
  background: linear-gradient(-35deg, #1678f2, #609af4);
}
.library-file-icon-excel {
  background: linear-gradient(-35deg, #0db773, #20c885);
}
.library-file-icon-ppt {
  background: linear-gradient(-35deg, #e86335, #f38063);
}
.library-file-icon-txt {
  background: linear-gradient(-35deg, #787d9a, #8e99ba);
}
.library-file-icon-pdf {
  background: linear-gradient(-35deg, #e34744, #ec7270);
}
.library-list__right__body__file:nth-child(2n) {
  margin-left: 20px;
}
</style>