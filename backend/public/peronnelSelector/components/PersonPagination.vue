<template>
  <div class="person-pagination">
    <div class="pagination-select">
      <div>每页显示</div>
      <el-select
        v-model="limit"
        placeholder=""
        @change="pageChange"
        class="pagination-select__input"
      >
        <el-option
          v-for="(item, index) in pageSizeList"
          :key="index"
          :label="item"
          :value="item"
        >
        </el-option>
      </el-select>
    </div>
    <span
      @click="goPage(1)"
      :class="[
        'library-pagination__box',
        { 'library-pagination__box__disabled': current_page == 1 },
      ]"
    >
      <i class="iconfont icon-start"></i>
    </span>
    <span
      :class="[
        'library-pagination__box',
        { 'library-pagination__box__disabled': current_page == 1 },
      ]"
      @click="handlePrev"
    >
      <i class="iconfont icon-xiasanjiaoxiangxiamianxing-copy"></i>
    </span>
    <el-input
      v-model="current_page"
      class="library-pagination-input"
      @blur="goPage(current_page)"
      @keyup.enter.native="goPage(current_page)"
    ></el-input>
    <span class="cl-252830 total-page">/ {{ pages }} 页</span>
    <span
      :class="[
        'library-pagination__box',
        { 'library-pagination__box__disabled': current_page == pages },
      ]"
      @click="handleNext"
    >
      <i class="iconfont icon-xiasanjiaoxiangxiamianxing-copy-copy"></i>
    </span>
    <span
      :class="[
        'library-pagination__box',
        { 'library-pagination__box__disabled': current_page == pages },
      ]"
      @click="goPage(pages)"
    >
      <i class="iconfont icon-start-copy"></i>
    </span>
  </div>
</template>
<script>
module.exports = {
  name: "PersonPagination",
  props: {
    pagination: {
      type: Object,
      default: function () {
        return {
          current_page: 1,
          per_page: 10,
          last_page: 1,
          total: 1,
        };
      },
    },
  },
  data() {
    return {
      limit: "", //每页显示x条
      pageSizeList: [10, 20, 30, 40], // 数量列表
      current_page: 1,
      pages: 1,
      pageSize: 1,
      willTo: 0,
    };
  },
  created() {
    this.initLimitNums();
  },
  methods: {
    initLimitNums() {
      //可插入任意每页显示页数
      this.limit = this.pageSize;
      if (!this.pageSizeList.includes(this.limit)) {
        this.pageSizeList.push(this.limit);
        this.pageSizeList.sort(function (a, b) {
          return a - b;
        });
      }
    },
    handlePrev() {
      if (this.current_page == 1) {
        return;
      }
      this.current_page = this.current_page - 1;

      this.turn(this.current_page);
    },
    handleNext() {
      if (this.current_page >= this.pages) {
        return;
      }
      this.current_page = this.current_page + 1;
      this.turn(this.current_page);
    },
    goPage(jumpPageNumber) {
      if (Number(jumpPageNumber) <= 0) {
        jumpPageNumber = 1;
      }
      if (Number(jumpPageNumber) >= this.pages) {
        jumpPageNumber = this.pages;
      }
      this.turn(Number(jumpPageNumber));
    },
    pageChange() {
      this.current_page = 1;
      this.turn(this.current_page);
    },
    /**
     * 切换
    //  */
    turn(pageNumber) {
      if (this.current_page != pageNumber) {
        this.current_page = pageNumber;
      }
      this.$emit("change-pagination", {
        current_page: this.current_page,
        per_page: this.limit,
        last_page: this.pages,
      });
    },
  },
  watch: {
    pagination: {
      handler(params) {
        this.current_page = Number(params.current_page);
        this.pageSize = Number(params.per_page);
      },
      immediate: true,
    },
    "pagination.last_page": {
      handler(value) {
        this.pages = Number(value);
      },
      immediate: true,
    },
  },
};
</script>
<style>
.person-pagination {
  display: flex;
  justify-content: end;
}
.pagination-select{
  width: 104px;
  height: 30px;
  line-height: 30px;
  padding-left: 20px;
  background: #FFFFFF;
  border-radius: 3px;
  display: flex;
  font-size: 13px;
  color: #242D4E;
  border: 1px solid #D9DDE6;
  padding-right: 0;
}
.pagination-select__input .el-input__inner{
  height: 28px;
  display: inline-block;
    width: 50px;
    border: 0;
    padding-right: 20px;
    padding-left: 0px;
    text-align: center;
}
.pagination-select__input .el-input__icon {
  width: 18px;
  line-height: 30px;
  height: 30px;
}
.pagination-select__input .el-icon-arrow-up:before {
  content: "\e688";
  color: #000000;
  font-family: "iconfont";
}
 .library-pagination__box {
  display: inline-block;
  width: 30px;
  height: 30px;
  text-align: center;
  line-height: 30px;
  background: #f0f1f4;
  border-radius: 3px 3px 3px 3px;
  opacity: 1;
  margin-left: 10px;
  cursor: pointer;
  color: #000;
}
 .library-pagination__box__disabled {
  color: #d1d4da;
}
 .library-pagination__box:hover {
  background: #e6ecf6;
}
 .library-pagination__box__disabled:hover {
  background: #f0f1f4;
  cursor: not-allowed;
}
 .library-pagination-input {
  width: 54px;
  margin-left: 7px;
}
 .library-pagination-input .el-input__inner {
  width: 54px;
  height: 30px;
  border-radius: 3px 3px 3px 3px;
  border: 1px solid #d9dde6;
  display: inline-block;
}
.cl-252830 {
  color: #252830;
}
.total-page{
  line-height: 30px;
  font-size: 13px;
  margin-left: 10px;
  display: inline-block;
}
</style>
