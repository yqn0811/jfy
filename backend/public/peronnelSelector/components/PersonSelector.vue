<template>
  <div
    :class="[
      'person-selector',
      datatype == 'multipart' ? 'person-selector-multipart' : '',
    ]"
  >
    <div class="person-selector-body">
      <template v-if="datatype == 'single'">
        <el-input
          placeholder="关键词搜索"
          v-model="model.key"
          class="person-input"
          @keyup.enter.native="search()"
          :clearable="true"
          @clear="search()"
        >
          <i slot="prefix" class="el-input__icon el-icon-search"></i
        ></el-input>
        <div class="person-list">
          <div
            class="person-list_item"
            v-for="(list, index) in datas"
            :key="index"
            @click="selectItem(list)"
          >
            <el-avatar class="avatar" :src="list.avatar"></el-avatar>
            <div class="content">
              <div class="name">
                <span class="tag-name">昵称</span>{{ list.nickname }}
              </div>
              <div class="phone">
                <span class="tag-phone">手机</span>{{ list.phone }}
              </div>
            </div>
            <div class="right">
              <div
                :class="['radio', list.checked ? 'radio-checked' : '']"
              ></div>
            </div>
          </div>
        </div>
        <person-pagination
          :pagination="pagination"
          @change-pagination="changePagination"
        ></person-pagination>
      </template>
      <template v-if="datatype == 'multipart'">
        <div class="display-flex">
          <div class="user-list">
            <el-input
              @keyup.enter.native="search()"
              :clearable="true"
              @clear="search()"
              placeholder="关键词搜索"
              v-model="model.key"
              class="person-input"
            >
              <i slot="prefix" class="el-input__icon el-icon-search"></i
            ></el-input>
            <div class="person-list">
              <div
                class="person-list_item"
                v-for="(list, index) in datas"
                :key="index"
                @click="selectItem(list)"
              >
                <el-avatar class="avatar" :src="list.avatar"></el-avatar>
                <div class="content">
                  <div class="name">
                    <span class="tag-name">昵称</span>{{ list.nickname }}
                  </div>
                  <div class="phone">
                    <span class="tag-phone">手机</span>{{ list.phone }}
                  </div>
                </div>
                <div class="right">
                  <div
                    :class="['check', list.checked ? 'check-checked' : '']"
                  ></div>
                </div>
              </div>
            </div>
            <person-pagination
              :pagination="pagination"
              @change-pagination="changePagination"
            ></person-pagination>
          </div>
          <div class="right-part">
            <div class="title">已选人员：</div>
            <div class="person-list">
              <div
                class="person-list_item right-part_item"
                v-for="(list, index) in selectDatats"
                :key="index"
              >
                <el-avatar class="avatar" :src="list.avatar"></el-avatar>
                <div class="content">
                  <div class="name">
                    <span class="tag-name">昵称</span>{{ list.nickname }}
                  </div>
                  <div class="phone">
                    <span class="tag-phone">手机</span>{{ list.phone }}
                  </div>
                </div>
                <div class="right">
                  <span
                    class="iconfont icon-del"
                    @click="delSelect(list)"
                  ></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
    <div
      :class="[
        'person-selector-footer',
        datatype == 'multipart' ? 'person-selector-footer-multipart' : '',
      ]"
    >
      <el-button class="link-btn link-btn-cancel" @click="cancel"
        >取消</el-button
      >
      <el-button class="link-btn link-btn-primary" @click="submit"
        >确定</el-button
      >
    </div>
  </div>
</template>
<script>
module.exports = {
  props: {
    datatype: {
      type: String,
      default: "single",
    },
    appletid:{
      type:[String,Number]
    },
    gettype:{
      type:String
    }
  },
  components: {
    PersonPagination: httpVueLoader("/peronnelSelector/components/PersonPagination.vue"),
  },
  data() {
    return {
      goodsLoading: false,
      model: {
        key: "",
      },
      pagination: {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 1,
      },
      datas: [],
      selectDatats: [], //多选选中数据
      singleSelectedDatas: {}, //单选选中数据
    };
  },
  created() {
    this.getList();
  },
  methods: {
    search() {
      this.pagination.current_page = 1;
      this.getList();
    },
    async getList() {
      let res = await userselectiondata({
        appletid: this.appletid,
        page: this.pagination.current_page,
        size: this.pagination.per_page,
        key: this.model.key,
        get_type: this.gettype,
      });
      if (res.code == 1) {
        if(this.gettype == 'group_leader'){
          res.data.data.forEach(item => {
            item.nickname = item.ext_nickname
            item.phone = item.ext_phone
          })
        }
         this.datas = res.data.data;
        this.getReserveSelection();
        if (this.datatype == "multipart") {
          this.singleSelectedDatas = {};
        } else {
          this.selectDatats = [];
        }
        this.pagination = {
          current_page: res.data.current_page,
          last_page: res.data.last_page,
          total: res.data.total,
          per_page: this.pagination.per_page,
        };
      }
    },
    cancel() {
      this.$emit("cancel");
    },
    submit() {
       let data = this.datatype == 'single'?this.singleSelectedDatas:this.selectDatats

      if(this.datatype == 'single' && !data.hasOwnProperty('nickname')){
        this.$message.error('请选择！')
        return false
      }
      if(this.datatype == 'multipart' && data.length == 0){
        this.$message.error('请选择！')
        return false
      }
      this.$emit("submit", data);
    },
    /**
     * 选中数据回显
     */
    getReserveSelection() {
      if (this.datatype == "single") {  //单选
        let item1 = this.datas.find(
          (item) => item.id == this.singleSelectedDatas.id
        );
        if (item1) {
          this.$set(item1, "checked", true);
        }
      } else {
        this.selectDatats.forEach((data) => {
          let item = this.datas.find((item) => item.id == data.id);
          if (item) {
            this.$set(item, "checked", true);
          }
        });
      }
    },
    selectItem(item) {
      if (this.datatype == "single") {
        //单选
        this.singleSelectedDatas = {};
        let select_item = this.datas.find((item) => item.checked);
        this.$set(item, "checked", !item.checked);
        if (item.checked) {
          //选中就赋值
          this.singleSelectedDatas = item;
        }
        if (select_item) {
          this.$set(select_item, "checked", false);
        }
      } else {
        //多选
        this.$set(item, "checked", !item.checked);
        if (!item.checked) {
          //取消勾选时删除选中数据对应内容
          let index = this.selectDatats.findIndex((data) => data.id == item.id);
          this.selectDatats.splice(index, 1);
        }
        this.getSelectList();
      }
    },
    changePagination(e) {
      this.goodsLoading = true;
      this.pagination = e;
      this.getList();
    },
    delSelect(list) {
      let index = this.selectDatats.findIndex((data) => data.id == list.id);
      this.selectDatats.splice(index, 1);
      let item = this.datas.find((data) => data.id == list.id);
      item.checked = false;
    },
    /**
     * 获取选中的数组
     */
    getSelectList() {
      this.datas.forEach((data) => {
        let findIndex = this.selectDatats.findIndex(
          (item) => item.id == data.id
        );
        if (data.checked && findIndex == -1) {
          this.selectDatats.push(data);
        }
      });
    },
  },
  watch: {
    selectDatats: {
      handler(arr) {
        arr.forEach((item) => {
          let data_item = this.datas.find((data) => data.id == item.id);
          if (data_item) {
            data_item.checked = item.checked;
          }
        });
      },
      immediate: true,
    },
  },
};
</script>
<style>
.person-selector {
  width: 740px;
  height: 664px;
  background: #ffffff;
  border-radius: 5px;
  padding: 20px;
  box-sizing: border-box;
}
.person-selector-multipart {
  padding: 0;
}
.person-input {
  width: 340px;
}
.person-input .el-input__inner {
  width: 340px;
  line-height: 34px;
  height: 34px;
  background: #ffffff;
  border-radius: 3px;
  border: 1px solid #dee1e7;
}
.person-input .el-input__icon{
  line-height: 36px;
}
.person-list {
  height: 484px;
  overflow-y: auto;
  overflow-x: hidden;
  padding-bottom: 20px;
  margin-top: 10px;
}

.user-list .person-list {
  height: 465px;
}
.person-list_item {
  padding: 10px 0 10px 10px;
  width: 100%;
  display: flex;
  border-radius: 3px;
  align-items: center;
  cursor: pointer;
}
.person-list_item .avatar {
  width: 40px;
  height: 40px;
  background: #fff;
}
.person-list_item .content {
  width: calc(100% - 80px);
  padding-left: 10px;
  box-sizing: border-box;
}
.person-list_item .content .name {
  font-size: 13px;
  color: #242d4e;
}
.content .tag-name {
  display: inline-block;
  width: 25px;
  height: 15px;
  background: var(--active-bg);
  border-radius: 2px;
  text-align: center;
  line-height: 15px;
  font-size: 10px;
  color: #fff;
  margin-right: 6px;
}
.person-list_item .content .phone {
  font-size: 12px;
  color: #5a6275;
}
.content .tag-phone {
  display: inline-block;
  width: 25px;
  height: 15px;
  background: #ffc107;
  border-radius: 2px;
  text-align: center;
  line-height: 15px;
  font-size: 10px;
  color: #242d4e;
  margin-right: 6px;
}
.radio {
  width: 20px;
  height: 20px;
  line-height: 20px;
  border: 1px solid #c7cddb;
  border-radius: 10px;
  text-align: center;
}
.radio-checked {
  background: var(--active-bg);
  color: #fff;
  border: 1px solid var(--border-active-color);
}
.radio-checked::after {
  content: "\e6bf";
  font-family: "iconfont";
}

.check {
  width: 17px;
  height: 17px;
  line-height: 17px;
  text-align: center;
  border-radius: 3px;
  border: 1px solid #c7cddb;
}

.check-checked {
  background: var(--active-bg);
  color: #fff;
  border: 1px solid var(--border-active-color);
}
.check-checked::after {
  content: "\e6bf";
  font-family: "iconfont";
}

.person-list_item:nth-child(2n + 1) {
  background: #f5f6f8;
}
.person-selector-footer {
  text-align: center;
  padding: 15px 20px;
}

/**多选样式**/
.user-list {
  height: 599px;
  width: 60%;
  border-right: 1px solid var(--border-color);
  padding: 20px;
  box-sizing: border-box;
}

.person-selector-footer-multipart {
  border-top: 1px solid var(--border-color);
}

.user-list .person-pagination {
  padding: 10px 0;
  text-align: right;
}

.right-part {
  padding: 20px 0 20px 20px;
  width: 40%;
  box-sizing: border-box;
}
.right-part .title {
  font-size: 13px;
  color: #242d4e;
}

.right-part_item {
  background: transparent !important;
  margin-top: 14px;
}

.person-selector-footer-multipart {
  text-align: right;
}
</style>
