<template>
  <div style="width: 100%; height: 100%">
    <div class="library-list__right__top">
      <div class="library-list__right__top__left">
        <el-upload
          :show-file-list="false"
          action
          :http-request="uploadFile"
          multiple
          accept="image/jpeg,image/gif,image/png"
          ref="dynamic"
          style="display: inline-block; margin-right: 10px"
          ><el-button type="primary" class="library-btn pd-10-20">
            本地上传
          </el-button></el-upload
        >
        <el-button class="library-cancel-btn pd-10-20" @click.native="extract"
          >网络提取</el-button
        >
        <span class="cl-color ml-6"
          >大小不要超过{{ size / 1024 / 1024 }}M</span
        >
      </div>
      <div class="library-list__right__top__right">
        <el-input
          class="library-input"
          placeholder="图片名称"
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
          @change="selectAllImg"
          class="library-checkbox"
          v-model="model.all_selected"
          v-if="type == 2"
          label="全选"
        ></el-checkbox>
        <!-- && currentNode.id != -1 -->
        <template v-if="selectedImg.length > 0">
          <span
            class="cl-active-color album_text pointer"
            :class="{ 'ml-20': type == 2 }"
            @click="move"
            >{{ selectedImg.length > 1 ? "批量" : "" }}移动</span
          >
          <span
            class="ml-20 cl-active-color album_text pointer"
            @click="delOperate({}, 'bulk')"
            v-if="selectedImg.length > 1"
            >批量删除</span
          >
        </template>
        <!-- <span class="ml-20 cl-active-color pointer" v-if="selectedImg.length == 1">
              置顶
            </span> -->
        <span
          class="ml-20 cl-active-color album_text pointer"
          v-if="selectedImg.length == 1"
          @click="showCropper"
          >裁剪</span
        >
        <!-- 重命名 -->
        <span
          class="ml-20 cl-active-color album_text pointer"
          v-if="selectedImg.length == 1"
          @click="imgRename"
          >重命名</span
        >
      </div>
      <div class="library-list__right__body__list">
        <template v-if="imgsList.length > 0">
          <div
            v-for="item in imgsList"
            :key="item.id"
            @click="selectImg(item)"
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
              <img :src="item.src" alt="" v-if="item.src" />
              <i class="iconfont icon-jiazaishibai" v-else></i>
              <el-checkbox
                v-model="item.checked"
                class="library-checkbox"
              ></el-checkbox>
              <div
                class="library-list__right__body__item__num"
                v-if="item.checked"
              >
                {{
                  selectedImg.indexOf(item) >= 0
                    ? selectedImg.indexOf(item) + 1
                    : 0
                }}
              </div>
              <div
                class="library-list__right__body__item__delete"
                @click.stop="delOperate(item, 'single')"
              >
                <i class="iconfont icon-del"></i>
              </div>
              <div
                class="library-list__right__body__item__enlarge"
                @click.stop="enlargeShow(item)"
              >
                <i class="iconfont icon-enlarge"></i>
              </div>
              <div
                class="library-list__right__body__item__size"
                v-if="item.width && item.height"
              >
                {{ item.width }}*{{ item.height }}px
              </div>
            </div>
            <div class="name text-hide" :title="item.name">
              {{ item.name }}
            </div>
          </div>
        </template>
        <template v-else>
          <div class="library-list__right__body__list__empty">
            <img src="/pic/src/images/empty.png" alt="" />
            <p>暂无图片，赶紧上传吧~</p>
          </div>
        </template>
      </div>
    </div>
    <library-pagination
      :pagination.sync="pagination"
      @change-pagination="changePage"
    ></library-pagination>
    <div class="library-list__right__bottom">
      <el-button class="library-cancel-btn pd-10-28" @click="closeImg"
        >取消</el-button
      >
      <el-button type="primary" class="library-btn pd-10-28" @click="submitImg"
        >确定</el-button
      >
    </div>
    <el-dialog
      title="裁剪图片"
      width="900px"
      top="5vh"
      :close-on-click-modal="false"
      custom-class="library-dialog library-cropper-box"
      :visible="cropperVisible"
      @close="closeDiglog('cropperVisible')"
    >
      <cropper-box
        ref="cropperbox"
        :cropperimg="cropperImg"
        :cropperinfo="cropperInfo"
        @save="save(arguments)"
      ></cropper-box>
    </el-dialog>

    <el-dialog
      :visible="extractVisible"
      title="提取网络图片"
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
        <el-form-item label="图片地址" prop="url">
          <el-input
            v-model="form.url"
            placeholder="请在此处粘贴图片地址"
            class="library-input__long"
            clearable
          ></el-input>

          <div class="library-alert__text">
            需要http://............大小不要超过{{
              size / 1024 / 1024
            }}M，支持图片类型.gif，.jpg，.png，.jpeg
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
      :visible="delImgVisible"
      title="提示"
      @close="closeDiglog('delImgVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>是否确认删除该图片！</div>
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
      :visible="moveVisible"
      :title="(selectedImg.length > 1 ? '批量' : '') + '移动图片'"
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
      :visible.sync="enlargeVisible"
      custom-class="enlarge-dialog"
      top="5vh"
    >
      <div class="enlarge-dialog__img">
        <span @click="enlargeVisible = false">
          <i class="iconfont icon-del"></i>
        </span>
        <img :src="enlargeImg" alt="" />
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
        :model="imgModel"
        class="library-form"
        label-width="116px"
        label-position="right"
        ref="imgModel"
        :rules="rules"
      >
        <el-form-item label="原本名称">
          <el-input
            disabled
            v-model="renameImg.name"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item label="修改名称" prop="rename_name">
          <el-input
            v-model="imgModel.rename_name"
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
      :visible="radioVisible"
      title="提示"
      @close="closeDiglog('radioVisible')"
      custom-class="library-dialog"
      width="400px"
      :close-on-click-modal="false"
    >
      <div>只能显示1张图片，默认使用第{{ selectedImg.length }}张</div>
      <div slot="footer" class="dialog-footer">
        <el-button class="library-cancel-btn pd-10-28" @click="radioReset"
          >取消</el-button
        >
        <el-button
          class="library-btn pd-10-28"
          type="primary"
          @click="submitImgRes"
          >确定</el-button
        >
      </div>
    </el-dialog>
  </div>
</template>
<script>
// vue项目引用方法
// import {CropperBox} from './CropperBox.vue'
// import {LibraryPagination} from './LibraryPagination.vue'
module.exports = {
  props: {
    type: {
      type: Number | String,
      default: 1,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    lefts: {
      //格式可能需要处理
      type: Array,
      default: function () {
        return [];
      },
    },
    size: {
      type: Number,
      default: 2097152,
    },
    imgs: {
      type: Array,
      default: function () {
        return [];
      },
    },
    //是否可用添加子分类
    treeaddchild: {
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
  },
  data() {
    let urlValidate = (rule, value, callback) => {
      let reg = /(http(s?):)*\.(?:jpg|gif|png|jpeg)$/;
      if (reg.exec(value)) {
        callback();
      } else {
        callback(new Error("地址结尾必须为.gif，.jpg，.png，.jpeg"));
      }
    };

    let imgNameValidate = (rule, value, callback) => {
      let reg = /\./g;
      if (!reg.exec(value)) {
        callback();
      } else {
        callback(new Error("名称不能包含非法字符."));
      }
    };
    return {
      cropperImg: "",
      cropperInfo: "",
      enlargeImg: "",
      cropperVisible: false,
      extractVisible: false,
      tagVisible: false,
      delVisible: false,
      moveVisible: false,
      delImgVisible: false,
      enlargeVisible: false,
      renameVisible: false, // 重命名弹框
      radioVisible: false, // 单选提示弹框
      renameImg: {}, // 重命名的对象参数
      imgModel: {
        rename_name: "",
      }, // 重命名新名称
      timerOut: null,
      activeIndex: -1,
      imgsList: [],
      selectedImg: [], //选中图片
      tagList: [],
      caslenderOptions: [],
      defaultExpandKeys: [],
      currentKey: [-1],
      leftGroups: [], // tree结构数据
      treeLoading: false, // tree结构加载
      files: [],
      delInfo: {},
      fileData: [],
      formData: "",
      delImgInfo: {
        pic_ids: [],
      },
      per_page: 18,
      currentNode: {
        id: "",
      },
      model: {
        pic_name: "",
        date: [],
        all_selected: false,
      },
      props: {
        checkStrictly: true,
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
        url: [
          { required: true, message: "请粘贴图片地址", trigger: "blur" },
          { validator: urlValidate, trigger: "blur" },
        ],
        new_tag_id: {
          required: true,
          message: "请选择新分类",
          trigger: "change",
        },
        rename_name: [
          { required: true, message: "请输入新名称", trigger: "blur" },
          { validator: imgNameValidate, trigger: "blur" },
        ],
      },
    };
  },
  components: {
    CropperBox: httpVueLoader("./CropperBox.vue"),
    LibraryPagination: httpVueLoader("./LibraryPagination.vue"),
  },
  methods: {
    cropperDiv() {
      this.cropperVisible = true;
    },
    closeCropper() {
      this.cropperVisible = false;
    },
    nodeClick(item) {
      this.currentNode = item;
      this.form.old_tag_name = item.label;
      this.selectedImg = [];
      this.model.all_selected = false;
      this.$emit("change-item", item);
      this.$emit("changeItem", item);
    },
    // 全选
    selectAllImg(value) {
      this.imgsList.forEach((item) => {
        item.checked = value;
        this.selectFilterArr(item, value ? "multiple" : "clear");
      });
    },
    selectImg(item) {
      // if (this.type == 1) {
      //   this.imgsList.forEach((arr) => {
      //     if (arr.id == item.id) {
      //       arr.checked = !arr.checked;
      //     } else {
      //       if (arr.checked) arr.checked = !arr.checked;
      //     }
      //   });
      //   this.selectedImg = [];
      //   this.selectedImg.push(item);
      //   return false;
      // }
      item.checked = !item.checked;
      this.selectFilterArr(item, "single");
    },
    selectFilterArr(item, type) {
      if (type == "clear") {
        this.selectedImg = [];
        return false;
      }
      let find_index = this.selectedImg.findIndex((arr) => arr.id == item.id);
      if (find_index == -1) {
        this.selectedImg.push(item);
      } else {
        if (type == "single") {
          this.selectedImg.splice(find_index, 1);
        }
      }
    },

    // 提交图片
    submitImg() {
      if (this.selectedImg.length <= 0) {
        this.$message.error("请选择图片！");
        return false;
      }
      if (this.type == 1 && this.selectedImg.length != 1) {
        this.radioVisible = true;
      } else {
        this.submitImgRes();
      }
    },

    // 单选模式 重置 清空选中列表
    radioReset() {
      this.selectedImg.map((item) => {
        // 先把图片选中样式清除
        let index = this.imgsList.indexOf(item);
        this.imgsList[index].checked = false;
      });

      this.selectedImg = []; // 再清空选中图片 顺序不可颠倒

      this.radioVisible = false;
    },

    // 提交图片 请求
    submitImgRes() {
      if (this.type == 1) {
        this.radioVisible = false;
      }

      if (this.selectedImg.length <= 0) {
        this.$message.error("请选择图片！");
        return false;
      }
      this.$emit("submit-image", this.selectedImg);
      this.$emit("submitImage", this.selectedImg);
    },
    closeImg() {
      this.$emit("close-image");
      this.$emit("closeImage");
    },
    /**
     * 提交分页修改
     */
    changePage(params) {
      // this.per_page = params.per_page;
      this.$emit("change-parent-pagination", params);
      this.$emit("changeParentPagination", params);
    },
    searchList() {
      let params = {
        page: 1,
      };
      params = Object.assign(params, this.model);
      this.$emit("get-list", params);
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
    /**
     * 添加编辑分类
     * type：一级分类||子分类
     * item: 添加子分类下当前分类信息
     */
    addTag(type, item = {}) {
      this.form.tag_type = type;
      this.form.new_tag_name = type == "child" ? "" : item.label;
      this.form.new_tag_id = [];
      this.form.tag_id = item.id;
      this.form.url = "";
      this.tagVisible = true;
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
        if (this.form.tag_type == "move") {
          this.form.pic_ids = this.selectedImg.map((item) => {
            return item.id;
          });
        }
        this.$emit("add-tag", this.form);
        this.$emit("addTag", this.form);
        this.closeDiglog("moveVisible");
      });
    },

    // 重命名
    rename() {
      this.$refs["imgModel"].validate((valid) => {
        if (!valid) {
          return false;
        }
        let imgArr = this.renameImg.name.split(".");
        let type = imgArr[imgArr.length - 1];
        let data = {
          pid: this.renameImg.id,
          new_name: this.imgModel.rename_name + "." + type,
        };
        this.$emit("rename", data);
      });
    },

    // 关闭重命名弹框
    closeRename() {
      this.closeDiglog("renameVisible");
      this.imgModel.rename_name = "";
    },

    // 更新完成刷新
    refreshRename() {
      let imgArr = this.renameImg.name.split(".");
      let type = imgArr[imgArr.length - 1];
      this.imgsList.map((item) => {
        if (item.checked) {
          item.name = this.imgModel.rename_name + "." + type;
        }
      });
      this.closeRename();
    },

    // 置顶
    setTop(node, item) {
      let arr = [...node.parent.data];
      let index = arr.indexOf(item);
      arr.splice(index, 1);
      arr.splice(1, 0, item);
      let ids = [];
      arr.map((item) => {
        ids.push(item.id);
      });
      this.$emit("handle-drop", ids, 1, arr);
      this.$emit("handleDrop", ids, 1, arr);
    },

    allowDrop(draggingNode, dropNode, type) {
      if (draggingNode.data.level === dropNode.data.level) {
        if (draggingNode.data.parentId === dropNode.data.parentId) {
          return type === "prev" || type === "next";
        } else {
          return false;
        }
      } else {
        // 不同级进行处理
        return false;
      }
    },

    move() {
      this.form.new_tag_id = [];
      this.moveVisible = true;
      this.form.tag_type = "move";
    },
    clearTagValidate() {
      this.$nextTick(() => {
        this.$refs["form"].resetFields([]);
      });
    },
    searchTagName() {
      this.$emit("search-tag", this.form);
      this.$emit("searchTag", this.form);
    },
    closeDiglog(type) {
      if (type == "cropperVisible") {
        this.$refs.cropperbox.clearCropperContent();
      }
      this[type] = false;
    },
    /**
     * 修改tag名称
     */
    changeTagName(name){
      this.form.old_tag_name = name
    },
    /**
     * 显示裁剪框
     */
    showCropper() {
      let selectedImg = this.selectedImg[0];
      this.cropperImg = selectedImg.src;
      if (!selectedImg.name) {
        let url = selectedImg.src || "";
        if (!url) {
          this.$messgae.error("该图片不存在，不可裁剪！");
          return false;
        }
        let index = url.lastIndexOf("/");
        selectedImg.name = url.substr(index + 1);
      }
      this.cropperInfo = selectedImg;
      this.cropperVisible = true;
    },
    /**
     * 保存裁剪图片
     * file:裁剪后的文件blob、
     * saveType:被裁剪图片上传方式 1、cover：覆盖原图 2、cropperimg:原裁剪图片信息3、save：另存
     */
    save(arg) {
      this.$emit("cropper-image", arg[0], arg[1], arg[2]);
      this.$emit("cropperImage", arg[0], arg[1], arg[2]);
      this.closeDiglog("cropperVisible");
    },
    /**
     * 上传本地图片先存数组，过几秒一起提交并清空数组
     */
    uploadFile(file) {
      this.fileData.push(file.file);
    },
    /**
     * @formData  array ['uploadfile'+index]
     */
    getSynamic() {
      this.formData = new FormData();
      this.fileData.forEach((item, index) => {
        this.formData.append(`uploadfile${index > 0 ? index : ""}`, item);
      });
      this.$emit("upload-image", this.formData);
      this.$emit("uploadImage", this.formData);
    },
    delOperate(info, type) {
      this.delImgInfo.pic_ids = [];
      if (type == "single") {
        this.delImgInfo.pic_ids.push(info.id);
      } else {
        this.delImgInfo.pic_ids = this.selectedImg.map((item) => {
          return item.id;
        });
      }
      this.delImgVisible = true;
    },
    delImgSubmit() {
      this.$emit("del-img", this.delImgInfo);
      this.$emit("delImg", this.delImgInfo);
      this.closeDiglog("delImgVisible");
    },
    enlargeShow(item) {
      console.log(item);
      this.enlargeImg = item.src;
      this.enlargeVisible = true;
    },

    handleDrop(draggingNode, dropNode, dropType, ev) {
      // console.log("tree drop: ", dropNode.label, dropType);
      var paramData = [];
      // 当拖拽类型不为inner,说明只是同级或者跨级排序，只需要寻找目标节点的父ID，获取其对象以及所有的子节点，并为子节点设置当前对象的ID为父ID即可
      // 当拖拽类型为inner，说明拖拽节点成为了目标节点的子节点，只需要获取目标节点对象即可
      var data = dropType != "inner" ? dropNode.parent.data : dropNode.data;
      var nodeData =
        dropNode.level == 1 && dropType != "inner" ? data : data.children;
      // 设置父ID,当level为1说明在第一级，pid为空
      nodeData.forEach((element) => {
        element.pid = dropNode.level == 1 ? "" : data.id;
      });
      nodeData.forEach((element, i) => {
        console.log(element);
        var dept = {
          deptId: element.id,
          parentDeptId: element.pid,
          order: i,
        };
        paramData.push(dept);
      });
      console.log("----------拖拽排序完成的新分类-------------");
      console.log(paramData);

      let ids = [];
      let arr = [];
      if (draggingNode.data.level != 1) {
        arr = dropNode.parent.data.children;
      } else {
        arr = this.leftGroups;
      }
      arr.map((item) => {
        ids.push(item.id);
      });

      this.$emit("handle-drop", ids, 0);
      this.$emit("handleDrop", ids, 0);
      //提交拖拽排序之后的分类至父组件
    },
    allowDrag(draggingNode) {
      return draggingNode.data.label.indexOf("全部") === -1;
    },

    // 图片重命名
    imgRename() {
      this.renameImg = this.selectedImg[0];
      this.renameVisible = true;
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
    imgs: {
      handler(arrs) {
        this.imgsList = [];
        arrs.map((item) => {
          this.imgsList.push(Object.assign({}, item, { checked: false }));
        });
      },
      immediate: true,
    },
    extractVisible(visible) {
      if (!visible) {
        this.$refs["form"].clearValidate([]);
      }
    },
    tagVisible(visible) {
      if (!visible) {
        this.$refs["form"].clearValidate([]);
      }
    },
    fileData: {
      handler(arrs) {
        if (arrs.length > 12) {
          this.$message.error("最多同时上传12张！");
          this.fileData = [];
          return false;
        }
        if (arrs.length > 0) {
          this.timerOut = setTimeout(() => {
            let messages = [];
            arrs.forEach((file) => {
              if (!/^image\/\w+$/.test(file.type)) {
                messages.push("请上传图片格式！");
                return false;
              }
              if (file.size > this.size) {
                let pic_limit = this.size / 1024 / 1024;
                messages.push(`上传图片 ${file.name} 不能超过${pic_limit}M；`);
                return false;
              }
            });
            if (messages.length > 0) {
              this.$message.error(messages.join(""));
              this.fileData = [];
              return false;
            }
            this.getSynamic();
          }, 600); //0.6秒之后将批量上传图片格式成数组上传
        }
      },
    },
    enlargeVisible(visible) {
      if (!visible) {
        this.enlargeImg = "";
      }
    },
  },
};
</script>
<style>
</style>