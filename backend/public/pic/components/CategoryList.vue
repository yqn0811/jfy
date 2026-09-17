<template>
  <div style="height:100%">
    <el-input
      v-model="form.keyword"
      placeholder="搜索分类"
      clearable
      @keyup.enter.native="searchTagName()"
      @clear="searchTagName()"
      class="mt-20 library-no-border__input"
    >
      <i slot="prefix" class="iconfont icon-search"></i>
    </el-input>
    <div class="library-list_left__body">
      <el-tree
        :current-node-key="currentNode.id"
        draggable
        @node-drop="handleDrop"
        :allow-drop="allowDrop"
        :allow-drag="allowDrag"
        class="library-list_left__tree"
        :data="leftGroups"
        node-key="id"
        :expand-on-click-node="true"
        :default-expanded-keys="defaultExpandKeys"
        @node-click="nodeClick"
        ref="libraryTree"
        v-loading="treeLoading"
      >
        <span slot-scope="{ node, data }" class="library-list_left__tree__box">
          <span>
            <i class="iconfont icon-wenjian1"></i>
            <span
              class="library-list_left__tree__label text-hide"
              :title="node.label"
              >{{ node.label }}</span
            >
          </span>
          <div @click.stop>
            <el-dropdown
              placement="bottom"
              v-if="data.id != -1"
              trigger="click"
            >
              <span class="iconfont icon-gengduo hover-show cl-normal-color"></span>
              <el-dropdown-menu slot="dropdown" class="library-dropdown">
                <el-dropdown-item
                  v-if="data.level == 1"
                  @click.native="setTop(node, data)"
                  >置顶</el-dropdown-item
                >
                <el-dropdown-item @click.native="addTag('edit', data)"
                  >重命名</el-dropdown-item
                >
                <el-dropdown-item
                  v-if="treeaddchild && data.level != 3"
                  @click.native="addTag('child', data)"
                  >添加子分类</el-dropdown-item
                >
                <el-dropdown-item @click.native="delTag(data)"
                  >删除</el-dropdown-item
                >
              </el-dropdown-menu>
            </el-dropdown>
          </div>
        </span>
      </el-tree>
    </div>
    <div class="library-list_left__bottom edit_ope" @click="addTag('parent')">
      <i class="iconfont icon-iconjia fz-14"></i>添加分类
    </div>

    <el-dialog
      :visible="tagVisible"
      :title="
        (form.tag_type == 'edit' ? '编辑' : '添加') +
        (form.tag_type == 'child' ? '子分类' : '分类')
      "
      @close="closeDiglog('tagVisible')"
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
        <el-form-item label="分类名称" prop="new_tag_name">
          <el-input
            v-model="form.new_tag_name"
            placeholder="请输入分类名称"
            class="library-input__medium"
          ></el-input>
        </el-form-item>
        <el-form-item>
          <el-button
            class="library-cancel-btn pd-10-28"
            @click="closeDiglog('tagVisible')"
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
  </div>
</template>
<script>
module.exports = {
  props: {
    lefts: {
      //格式可能需要处理
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
  },
  data() {
    return {
      leftGroups: [],
      defaultExpandKeys: [],
      currentKey: [-1],
      treeLoading: false,
      tagVisible: false,
      form: {
        url: "",
        keyword: "",
        tag_id: "",
        old_tag_name: "全部",
        new_tag_name: "",
        tag_type: "parent",
        new_tag_id: [],
      },
      delInfo:{
        id:""
      },
      currentNode: {
        id: "",
      },
      rules: {
        new_tag_name: {
          required: true,
          message: "请填写分类名称",
          trigger: "blur",
        },
        new_tag_id: {
          required: true,
          message: "请选择新分类",
          trigger: "change",
        },
      },
    };
  },
  methods: {
    searchTagName() {
      this.$emit("search-tag", this.form);
      this.$emit("searchTag", this.form);
    },
    closeDiglog(type) {
      this[type] = false;
    },
    nodeClick(item) {
      this.currentNode = item;
      this.form.old_tag_name = item.label;
      // this.selectedImg = [];
      // this.model.all_selected = false;
      this.$emit("change-item", item);
      this.$emit("changeItem", item);
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
        this.$nextTick(function () {
          this.$refs.libraryTree.setCurrentKey(this.currentNode.id);
        });
        if (this.form.tag_type == "move") {
          this.form.pic_ids = this.selectedImg.map((item) => {
            return item.id;
          });
        }
        this.$emit("add-tag", this.form);
        this.$emit("addTag", this.form);
        this.closeDiglog("tagVisible");
        this.closeDiglog("moveVisible");
      });
    },
    delTag(item){
      this.delInfo = item
      this.$emit("del-tag", {id:item.id});
      this.$emit("delTag", {id:item.id});
    },
    /**
     * 拖拽排序
     */
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
      // console.log("----------拖拽排序完成的新分类-------------");
      // console.log(paramData);

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
          this.$nextTick(() => {
            this.$refs.libraryTree.setCurrentKey(this.currentNode.id);
          });
          this.leftGroups = filterNoChildData(arr);
          this.caslenderOptions = filterNoChildData(arr);
        } else {
          this.leftGroups = [];
        }
      },
      immediate: true,
    },
  },
};
</script>