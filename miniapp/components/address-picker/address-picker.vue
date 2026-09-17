<template>
  <view class="">
    <u-picker
      :show="show"
      ref="uPicker"
      :title="title"
      :showToolbar="showToolbar"
      :itemHeight="itemHeight"
      :cancelText="cancelText"
      :cancelColor="cancelColor"
      :confirmText="confirmText"
      :confirmColor="confirmColor"
      :loading="loading"
      :visibleItemCount="visibleItemCount"
      :defaultIndex="indexPop"
      :columns="columns"
      :closeOnClickOverlay="closeOnClickOverlay"
      @confirm="confirm"
      @close="close"
      @cancel="cancel"
      @change="changeHandler"
    >
    </u-picker>
  </view>
</template>
<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: () => false,
    },
    title: {
      type: String,
      default: () => "",
    },
    showToolbar: {
      type: Boolean,
      default: () => true,
    },
    itemHeight: {
      type: [String, Number],
      default: () => 44,
    },
    cancelText: {
      type: String,
      default: () => "取消",
    },
    closeOnClickOverlay: {
      type: Boolean,
      default: () => false,
    },
    cancelColor: {
      type: String,
      default: () => "#909193",
    },
    confirmText: {
      type: String,
      default: () => "确认",
    },
    confirmColor: {
      type: String,
      default: () => "#3c9cff",
    },
    visibleItemCount: {
      type: [String, Number],
      default: () => 5,
    },
    loading: {
      type: Boolean,
      default: () => false,
    },
    type: {
      type: Number,
      default: () => 3,
    },
    indexs: {
      type: Array,
      default: () => [0, 0],
    },
    areaId: {
      type: Array,
      default: () => [110000, 110101],
    },
    addressData: {
      type: Array,
      default: () => ["北京市", "东城区"],
    },
    addressList: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      columns: [],
      province: [],
      city: [],
      area: [],
      indexPop: [],
    };
  },
  mounted() {
    console.log(this.addressList);
    this.formatData();
  },
  watch: {
    addressData: "formatData",
    indexs: "formatData",
    areaId: "formatData",
  },
  methods: {
    changeHandler(e) {
      const {
        columnIndex,
        value,
        values, // values为当前变化列的数组内容
        indexs,
        picker = this.$refs.uPicker,
      } = e;
      switch (this.type) {
        case 1:
          break;
        case 2:
          if (columnIndex === 0) {
            picker.setColumnValues(
              1,
              this.city[indexs[0]].map((v) => v.name)
            );
          }
          break;
        default:
          if (columnIndex === 0) {
            picker.setColumnValues(
              1,
              this.city[indexs[0]].map((v) => v.name)
            );
            picker.setColumnValues(
              2,
              this.area[indexs[0]][0].map((v) => v.name)
            );
          }
          if (columnIndex === 1) {
            picker.setColumnValues(
              2,
              this.area[indexs[0]][indexs[1]].map((v) => v.name)
            );
          }
      }
    },
    formatData() {
      this.indexPop = this.indexs;
      this.province = this.addressList.map((t) => {
        return {
          name: t.label,
          areaId: t.value,
        };
      });
      this.city = this.addressList.map((t) =>
        (t.children || []).map((v) => {
          return {
            name: v.label,
            areaId: v.value,
          };
        })
      );
      this.area = this.addressList.map((t) =>
        (t.children || []).map((v) =>
          (v.children || []).map((i) => {
            return {
              name: i.label,
              areaId: i.value,
            };
          })
        )
      );
      switch (this.type) {
        case 1:
          this.columns = [this.province.map((res) => res.name)];
          this.type1();
          break;
        case 2:
          this.columns = [
            this.province.map((res) => res.name),
            this.city[0] && this.city[0].length > 0
              ? this.city[0].map((res) => res.name)
              : [],
          ];
          this.type2();
          break;
        default:
          //默认显示数据
          this.columns = [
            this.province.map((res) => res.name),
            this.city[0] && this.city[0].length > 0
              ? this.city[0].map((res) => res.name)
              : [],
            this.area[0] && this.area[0][0] && this.area[0][0].length > 0
              ? this.area[0][0].map((res) => res.name)
              : [],
          ];
          this.type3();
      }
    },
    type1() {
      //数据回显
      if (this.addressData.length > 0) {
        //省索引
        let pIdx = this.province.findIndex(
          (v) => v.name == this.addressData[0]
        );
        this.indexPop = [pIdx];
      } else if (this.areaId.length > 0) {
        //省索引
        let pIdx = this.province.findIndex((v) => v.areaId === this.areaId[0]);
        this.indexPop = [pIdx];
      }
    },
    type2() {
      //数据回显
      if (this.addressData.length > 0) {
        //省索引
        let pIdx = this.province.findIndex(
          (v) => v.name == this.addressData[0]
        );
        //根据省索引设置默认市数据
        this.columns[1] = this.city[pIdx].map((res) => res.name);
        //市索引
        let cIdx = this.city[pIdx].findIndex(
          (v) => v.name == this.addressData[1]
        );
        this.indexPop = [pIdx, cIdx];
      } else if (this.indexPop.length > 0) {
        this.columns[1] = this.city[this.indexPop[0]].map((res) => res.name);
      } else if (this.areaId.length > 0) {
        //省索引
        let pIdx = this.province.findIndex((v) => v.areaId === this.areaId[0]);
        //根据省索引设置默认市数据
        this.columns[1] = this.city[pIdx].map((res) => res.name);
        //市索引
        let cIdx = this.city[pIdx].findIndex((v) => v.areaId == this.areaId[1]);
        this.indexPop = [pIdx, cIdx];
      }
    },
    type3() {
      //数据回显
      if (this.addressData.length > 0) {
        //省索引
        let pIdx = this.province.findIndex(
          (v) => v.name == this.addressData[0]
        );
        //根据省索引设置默认市数据
        this.columns[1] = this.city[pIdx].map((res) => res.name);
        //市索引
        let cIdx = this.city[pIdx].findIndex(
          (v) => v.name == this.addressData[1]
        );
        //根据市索引设置默认区数据
        this.columns[2] = this.area[pIdx][cIdx].map((res) => res.name);
        //区索引
        let aIdx = this.area[pIdx][cIdx].findIndex(
          (v) => v.name == this.addressData[2]
        );
        this.indexPop = [pIdx, cIdx, aIdx];
      } else if (this.indexPop.length > 0) {
        this.columns[1] = this.city[this.indexPop[0]].map((res) => res.name);
        this.columns[2] = this.area[this.indexPop[0]][this.indexPop[1]].map(
          (res) => res.name
        );
      } else if (this.areaId.length > 0) {
        //省索引
        let pIdx = this.province.findIndex((v) => v.areaId === this.areaId[0]);
        //根据省索引设置默认市数据
        this.columns[1] = this.city[pIdx].map((res) => res.name);
        //市索引
        let cIdx = this.city[pIdx].findIndex((v) => v.areaId == this.areaId[1]);
        //根据市索引设置默认区数据
        this.columns[2] = this.area[pIdx][cIdx].map((res) => res.name);
        //区索引
        let aIdx = this.area[pIdx][cIdx].findIndex(
          (v) => v.areaId == this.areaId[2]
        );
        this.indexPop = [pIdx, cIdx, aIdx];
      }
    },
    confirm(e) {
      let provinceId, cityId, districtId;
      switch (this.type) {
        case 1:
          provinceId = this.addressList[e.indexs[0]]?.value;
          e.areaId = [provinceId];
          e.indexs = e.indexs.slice(0, 1);
          break;
        case 2:
          provinceId = this.addressList[e.indexs[0]]?.value;
          cityId = this.addressList[e.indexs[0]]?.children?.[e.indexs[1]]?.value;
          e.areaId = [provinceId, cityId].filter(Boolean);
          e.indexs = e.indexs.slice(0, 2);
          break;
        default:
          provinceId = this.addressList[e.indexs[0]]?.value;
          const province = this.addressList[e.indexs[0]];
          const city = province?.children?.[e.indexs[1]];
          cityId = city?.value;
          
          // 判断是否有第三级区县数据
          if (city?.children && city.children.length > 0 && e.indexs[2] !== undefined) {
            districtId = city.children[e.indexs[2]]?.value;
            e.areaId = [provinceId, cityId, districtId].filter(Boolean);
          } else {
            // 没有第三级，只返回省市
            e.areaId = [provinceId, cityId].filter(Boolean);
            e.indexs = e.indexs.slice(0, 2);
          }
      }
      this.$emit("confirm", e);
    },
    close() {
      this.$emit("close");
    },
    cancel() {
      this.$emit("cancel");
    },
  },
};
</script>
<style></style>
