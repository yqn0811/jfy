import { defineComponent, useSSRContext, mergeProps, withCtx, renderSlot, createVNode } from "vue";
import { useForwardPropsEmits, DialogRoot, DialogClose, DialogPortal, DialogOverlay, DialogContent as DialogContent$1, useForwardProps, DialogDescription as DialogDescription$1, DialogTitle as DialogTitle$1, DialogTrigger } from "reka-ui";
import { ssrRenderComponent, ssrRenderSlot, ssrRenderAttrs } from "vue/server-renderer";
import { _ as _export_sfc } from "./SafeIcon.D7kIP4uZ.js";
import { reactiveOmit } from "@vueuse/core";
import { X } from "lucide-vue-next";
import { c as cn } from "./index.CiCxTEA9.js";
const categoryDataList = [
  {
    id: "cat_001",
    homeId: "home_001",
    name: "床品套件",
    intro: "四件套、六件套与礼品装组合。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/161e494c-9552-46b5-8b32-9432cb9e9413.png",
    productCount: 6,
    childCount: 2,
    visibility: "public",
    layout: "grid",
    isTop: true,
    updatedAt: "2026-07-02 15:20:00",
    createdAt: "2026-02-12 11:10:00"
  },
  {
    id: "cat_002",
    homeId: "home_001",
    parentId: "cat_001",
    name: "纯棉四件套",
    intro: "适合春夏季节的清爽纯棉面料。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/1886523a-b6b6-4321-a5fa-d2569626552f.png",
    productCount: 4,
    childCount: 0,
    visibility: "shared",
    layout: "grid",
    isTop: false,
    updatedAt: "2026-06-21 09:40:00",
    createdAt: "2026-03-05 14:00:00"
  },
  {
    id: "cat_003",
    homeId: "home_001",
    parentId: "cat_001",
    name: "磨毛冬被",
    intro: "秋冬保暖被类产品。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/0c1c2ed8-16c5-4a31-9ca8-b822d7efd994.png",
    productCount: 5,
    childCount: 0,
    visibility: "private",
    layout: "list",
    isTop: false,
    updatedAt: "2026-06-18 18:30:00",
    createdAt: "2026-04-11 10:05:00"
  },
  {
    id: "cat_004",
    homeId: "home_001",
    name: "窗帘布艺",
    intro: "遮光、绒感与工程布艺方案。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/b238bf46-1e00-4209-b107-0974e9713538.png",
    productCount: 3,
    childCount: 1,
    visibility: "public",
    layout: "grid",
    isTop: true,
    updatedAt: "2026-07-04 12:00:00",
    createdAt: "2026-05-02 09:10:00"
  },
  {
    id: "cat_005",
    homeId: "home_001",
    parentId: "cat_004",
    name: "高遮光窗帘",
    intro: "酒店与家装常用遮光方案。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/654831e4-1e98-4d75-9b96-bfa80bc9eb1b.png",
    productCount: 2,
    childCount: 0,
    visibility: "shared",
    layout: "list",
    isTop: false,
    updatedAt: "2026-06-30 08:20:00",
    createdAt: "2026-06-01 13:00:00"
  }
];
const productDataList = [
  {
    id: "prod_001",
    homeId: "home_001",
    categoryId: "cat_002",
    ownerUserId: "user_001",
    name: "云感纯棉四件套",
    intro: "适合春夏的轻盈纯棉床品，手感柔软，适合客厅样板与电商展示。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c5a31449-3383-4af3-b43d-092356bfce80.png",
    visibility: "public",
    hideDetailImage: false,
    isHot: true,
    sortOrder: 1,
    colorChartCount: 5,
    detailChartCount: 4,
    updatedAt: "2026-07-05 09:10:00",
    createdAt: "2026-05-10 10:00:00"
  },
  {
    id: "prod_002",
    homeId: "home_001",
    categoryId: "cat_002",
    ownerUserId: "user_001",
    name: "沁凉提花四件套",
    intro: "提花纹理清晰，适合夏季清爽风格。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/8fff95bb-a74b-4e99-a3f3-654e1db40bb5.png",
    visibility: "shared",
    hideDetailImage: true,
    isHot: false,
    sortOrder: 2,
    colorChartCount: 3,
    detailChartCount: 2,
    updatedAt: "2026-06-28 14:20:00",
    createdAt: "2026-05-18 09:30:00"
  },
  {
    id: "prod_003",
    homeId: "home_001",
    categoryId: "cat_003",
    ownerUserId: "user_001",
    name: "暖绒磨毛冬被",
    intro: "厚实保暖，适合冬季渠道与客户样板。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/17dd0b00-3aab-4526-9c94-c6bf1bea3460.png",
    visibility: "private",
    hideDetailImage: false,
    isHot: false,
    sortOrder: 3,
    colorChartCount: 4,
    detailChartCount: 5,
    updatedAt: "2026-06-20 11:00:00",
    createdAt: "2026-05-22 16:45:00"
  },
  {
    id: "prod_004",
    homeId: "home_001",
    categoryId: "cat_004",
    ownerUserId: "user_001",
    name: "极简遮光窗帘",
    intro: "商务空间常用遮光方案，适合工程展示。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6f958559-43f1-4ca5-be22-db3aff1e07ca.png",
    visibility: "public",
    hideDetailImage: false,
    isHot: true,
    sortOrder: 4,
    colorChartCount: 6,
    detailChartCount: 8,
    updatedAt: "2026-07-03 18:00:00",
    createdAt: "2026-05-28 08:20:00"
  },
  {
    id: "prod_005",
    homeId: "home_001",
    categoryId: "cat_005",
    ownerUserId: "user_001",
    name: "高遮光酒店帘",
    intro: "酒店项目专用遮光帘，注重褶皱与垂感。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c66cde7e-b2a6-4735-a29d-33cb0b9db8df.png",
    visibility: "shared",
    hideDetailImage: true,
    isHot: false,
    sortOrder: 5,
    colorChartCount: 2,
    detailChartCount: 1,
    updatedAt: "2026-07-06 13:10:00",
    createdAt: "2026-06-02 09:00:00"
  },
  {
    id: "prod_006",
    homeId: "home_001",
    categoryId: "cat_004",
    ownerUserId: "user_001",
    name: "天鹅绒遮光帘",
    intro: "柔软绒感与高遮光性能兼具，适合客厅和卧室。",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/7d4e0c79-2aef-40ea-b6ad-d01f0eb42622.png",
    visibility: "public",
    hideDetailImage: false,
    isHot: false,
    sortOrder: 6,
    colorChartCount: 4,
    detailChartCount: 3,
    updatedAt: "2026-07-01 16:10:00",
    createdAt: "2026-06-10 11:30:00"
  }
];
const productImageDataList = [
  {
    id: "img_001",
    productId: "prod_001",
    type: "colorChart",
    name: "云感纯棉四件套-主花型",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/e6db8dad-f2a8-43ea-8539-352e9f1705ea.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/167ae256-9670-44d1-a27a-3761cdf9d3b9.png",
    sizeLabel: "2.4 MB",
    sizeBytes: 2516582,
    sortOrder: 1,
    isOriginalLarge: false,
    createdAt: "2026-05-10 10:10:00"
  },
  {
    id: "img_002",
    productId: "prod_001",
    type: "colorChart",
    name: "云感纯棉四件套-铺床场景",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/dac61102-43af-4e43-a6b6-1d20f433f36f.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/790003b9-7c56-4a7b-b586-ff43a9a1af1f.png",
    sizeLabel: "3.8 MB",
    sizeBytes: 3984588,
    sortOrder: 2,
    isOriginalLarge: true,
    createdAt: "2026-05-10 10:12:00"
  },
  {
    id: "img_003",
    productId: "prod_001",
    type: "detailChart",
    name: "云感纯棉四件套-面料细节",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/47288cd4-8859-4a15-ad53-0c6515878e0c.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/3d17ff40-56e6-480e-9427-01387ff2afa1.png",
    sizeLabel: "1.9 MB",
    sizeBytes: 1992294,
    sortOrder: 1,
    isOriginalLarge: false,
    createdAt: "2026-05-10 10:20:00"
  }
];
const planPackageDataList = [
  {
    id: "plan_001",
    name: "标准版 100GB",
    capacityMb: 102400,
    price: "¥199 / 年",
    concurrentRights: 5,
    trafficGb: 100,
    durationLabel: "1 年",
    isRecommended: true,
    createdAt: "2026-01-12 10:00:00"
  },
  {
    id: "plan_002",
    name: "专业版 300GB",
    capacityMb: 307200,
    price: "¥499 / 年",
    concurrentRights: 10,
    trafficGb: 300,
    durationLabel: "1 年",
    isRecommended: false,
    createdAt: "2026-01-12 10:05:00"
  }
];
const orderDataList = [
  {
    id: "order_001",
    packageId: "plan_001",
    orderNo: "ORD20260707001",
    amount: "199.00",
    status: "pending",
    createdAt: "2026-07-07 10:00:00",
    updatedAt: "2026-07-07 10:00:00"
  },
  {
    id: "order_002",
    packageId: "plan_002",
    orderNo: "ORD20260706002",
    amount: "499.00",
    status: "success",
    createdAt: "2026-07-06 14:10:00",
    updatedAt: "2026-07-06 14:20:00"
  }
];
const resourceLibraryDataList = [
  {
    id: "res_001",
    name: "纯棉四件套-细节图1",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6cd493f4-1863-4dda-9aef-dafb56ef25e5.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/8e21961e-2fef-4984-886e-5bd5b0ffd5d2.png",
    sizeLabel: "1.6 MB",
    sizeBytes: 1677721,
    uploadedAt: "2026-06-15 10:00:00",
    status: "recent",
    usedByProductId: "prod_001"
  },
  {
    id: "res_002",
    name: "窗帘布艺-场景图2",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/a35176b0-d829-4b44-b4bd-58483cfa8878.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6f2df262-9057-4489-afd4-fa9b4b185098.png",
    sizeLabel: "2.1 MB",
    sizeBytes: 2202009,
    uploadedAt: "2026-06-20 13:20:00",
    status: "used",
    usedByProductId: "prod_004"
  },
  {
    id: "res_003",
    name: "冬被样板-静物图",
    url: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/b70616c5-fa19-4e07-bb76-b06627e86a86.jpeg",
    thumbnailUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/25950e63-7646-4461-b33c-017ce226a280.png",
    sizeLabel: "3.2 MB",
    sizeBytes: 3355443,
    uploadedAt: "2026-06-22 15:40:00",
    status: "unused"
  }
];
const trashDataList = [
  {
    id: "trash_001",
    itemType: "product",
    sourceId: "prod_006",
    name: "天鹅绒遮光帘",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/e317cfe4-0a9d-424b-bcc5-5e0010011cfd.png",
    deletedAt: "2026-07-07 08:00:00",
    canRestore: true
  },
  {
    id: "trash_002",
    itemType: "category",
    sourceId: "cat_005",
    name: "高遮光窗帘",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/2314bd45-2374-4188-b2ef-7e55768f1ea1.png",
    deletedAt: "2026-07-06 16:30:00",
    canRestore: true
  },
  {
    id: "trash_003",
    itemType: "image",
    sourceId: "img_003",
    name: "云感纯棉四件套-面料细节",
    coverUrl: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/9879a98f-f200-4145-b718-3a37a21cc8e7.png",
    deletedAt: "2026-07-07 09:10:00",
    canRestore: false
  }
];
const STORAGE_KEY = "jfyuntu_pc_mock_state_v1";
const MOCK_TOKEN = "mock-local-token";
const ownerUid = "user_001";
const fallbackImage = "https://api.jfyuntu.com/image/static/footer/jfyuntu.png";
const nowText = () => (/* @__PURE__ */ new Date()).toLocaleString("zh-CN", { hour12: false }).replace(/\//g, "-");
const randomCode = (length = 10) => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
  return Array.from({ length }, () => chars[Math.floor(Math.random() * chars.length)]).join("");
};
const isLocalMockEnabled = () => {
  if (typeof window === "undefined") return false;
  return true;
};
const clone = (value) => JSON.parse(JSON.stringify(value));
const baseUser = {
  id: ownerUid,
  uid: ownerUid,
  username: "yunzhi",
  nickname: "云织主理人",
  avatar: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/98e5967d-09b8-43ee-9951-248ffa1aec4b.png",
  company_name: "云织家纺",
  company_logo: "https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/404cfc00-741d-4356-a857-5af6f1fe0cb8.png",
  company_desc: "专注家纺面料与成品展示，提供稳定的产品资料维护、客户分享、协作上传与下载管理能力。",
  industry_name: "家纺面料",
  contact_mobile: "021-5678-9001",
  contact_wechat: "yunzhi-home",
  address_province: "浙江省",
  address_city: "杭州市",
  address_district: "余杭区",
  address_detail: "文一西路 998 号云纺大厦 8F",
  grade_name: "专业版 10G",
  all_space: "10G",
  use_space: 3.2 * 1024 * 1024 * 1024,
  space_used: 32,
  traffic_gb: 500,
  monthly_traffic_gb: 500,
  used_traffic_gb: 86.5,
  concurrent_rights: 5,
  concurrency_limit: 5,
  product_count: productDataList.length,
  category_count: categoryDataList.length,
  view_count: 1286,
  home_watermark_text: "© 云织家纺 版权所有",
  home_share_title: "云织家纺产品主页",
  home_share_desc: "查看最新家纺产品、分类、花色图和详情图。",
  is_show_home: 1,
  create_time: "2026-01-10 09:00:00",
  update_time: "2026-07-08 09:30:00"
};
const normalizeCategoryCount = (categories, products) => {
  return categories.map((category) => ({
    ...category,
    productCount: products.filter((product) => product.categoryId === category.id).length,
    childCount: categories.filter((item) => item.parentId === category.id).length
  }));
};
const withImageCounts = (products, images) => {
  return products.map((product) => {
    const productImages = images.filter((image) => image.productId === product.id);
    const firstImage = productImages.find((image) => image.type === "colorChart") || productImages[0];
    return {
      ...product,
      coverUrl: product.coverUrl || firstImage?.thumbnailUrl || firstImage?.url || fallbackImage,
      colorChartCount: productImages.filter((image) => image.type === "colorChart").length,
      detailChartCount: productImages.filter((image) => image.type === "detailChart").length
    };
  });
};
const makeInitialState = () => {
  const images = clone(productImageDataList);
  const products = withImageCounts(clone(productDataList), images);
  const categories = normalizeCategoryCount(clone(categoryDataList), products);
  return {
    user: {
      ...baseUser,
      product_count: products.length,
      category_count: categories.length
    },
    categories,
    products,
    images,
    batchLinks: {
      prod_001: {
        fid: "prod_001",
        code: "JivHTmmJUC",
        upload_pwd: "A7K3",
        upload_enabled: 1,
        upload_pwd_expire_time: 0
      }
    },
    favorites: [
      makeRecord("fav_001", "homepage", ownerUid, baseUser.company_name, "商户主页", baseUser.company_logo, ownerUid),
      makeRecord("fav_002", "category", "cat_002", "纯棉四件套", "分类", categories[1]?.coverUrl, ownerUid),
      makeRecord("fav_003", "product", "prod_001", "云感纯棉四件套", "产品", products[0]?.coverUrl, ownerUid)
    ],
    visits: [
      makeRecord("visit_001", "homepage", ownerUid, baseUser.company_name, "商户主页", baseUser.company_logo, ownerUid),
      makeRecord("visit_002", "category", "cat_001", "床品套件", "分类", categories[0]?.coverUrl, ownerUid),
      makeRecord("visit_003", "product", "prod_004", "极简遮光窗帘", "产品", products[3]?.coverUrl, ownerUid)
    ],
    trash: clone(trashDataList),
    orders: clone(orderDataList),
    resources: clone(resourceLibraryDataList),
    plans: [
      {
        id: "plan_free",
        name: "免费版 50MB",
        capacityMb: 50,
        price: "¥0",
        concurrentRights: 1,
        trafficGb: 2,
        durationLabel: "长期",
        isRecommended: false,
        createdAt: "2026-07-01 10:00:00"
      },
      {
        id: "plan_5g",
        name: "基础版 5G",
        capacityMb: 5 * 1024,
        price: "¥29 / 年",
        concurrentRights: 2,
        trafficGb: 100,
        durationLabel: "1 年",
        isRecommended: false,
        createdAt: "2026-07-01 10:05:00"
      },
      {
        id: "plan_10g",
        name: "专业版 10G",
        capacityMb: 10 * 1024,
        price: "¥59 / 年",
        concurrentRights: 5,
        trafficGb: 500,
        durationLabel: "1 年",
        isRecommended: true,
        createdAt: "2026-07-01 10:10:00"
      },
      {
        id: "plan_200g",
        name: "旗舰版 200G",
        capacityMb: 200 * 1024,
        price: "¥1099 / 年",
        concurrentRights: 20,
        trafficGb: 5e3,
        durationLabel: "1 年",
        isRecommended: false,
        createdAt: "2026-07-01 10:15:00"
      },
      ...clone(planPackageDataList)
    ]
  };
};
function makeRecord(id, type, targetId, title, subtitle, image = "", targetUid = ownerUid) {
  return {
    id,
    type,
    target_type: type,
    target_id: targetId,
    target_uid: targetUid,
    title,
    subtitle,
    source: subtitle,
    image: image || fallbackImage,
    create_time: nowText(),
    time: Math.floor(Date.now() / 1e3)
  };
}
const getState = () => {
  if (typeof localStorage === "undefined") return makeInitialState();
  const raw = localStorage.getItem(STORAGE_KEY);
  if (raw) {
    try {
      return JSON.parse(raw);
    } catch {
      localStorage.removeItem(STORAGE_KEY);
    }
  }
  const state = makeInitialState();
  saveState(state);
  return state;
};
const saveState = (state) => {
  state.products = withImageCounts(state.products, state.images);
  state.categories = normalizeCategoryCount(state.categories, state.products);
  state.user.product_count = state.products.length;
  state.user.category_count = state.categories.length;
  if (typeof localStorage !== "undefined") {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  }
};
const ok = (data) => clone(data);
const listPayload = (rows) => ({
  data: rows,
  lists: rows,
  total: rows.length,
  per_page: rows.length,
  current_page: 1
});
const visibilityToPrivateType = (visibility) => {
  if (visibility === "private") return 2;
  if (visibility === "shared") return 4;
  return 1;
};
const privateTypeToVisibility = (value) => {
  if (Number(value) === 2) return "private";
  if (Number(value) === 4) return "shared";
  return "public";
};
const toPhpImage = (image) => ({
  id: image.id,
  pic_id: image.id,
  pid: image.id,
  pic_name: image.name,
  name: image.name,
  file_name: image.name,
  picture_url: image.thumbnailUrl || image.url,
  picture_url_original: image.url,
  imgurl: image.url,
  url: image.url,
  thumb: image.thumbnailUrl || image.url,
  size: image.sizeBytes,
  size_label: image.sizeLabel,
  sort: image.sortOrder,
  create_time: image.createdAt
});
const toPhpCategory = (category) => ({
  id: category.id,
  fid: category.id,
  uid: ownerUid,
  pid: category.parentId || 0,
  folder_type: 1,
  folder_name: category.name,
  name: category.name,
  folder_desc: category.intro,
  desc: category.intro,
  new_thumb: category.coverUrl,
  cover: category.coverUrl,
  product_count: category.productCount,
  son_product_count: category.productCount,
  child_count: category.childCount,
  son_count: category.childCount,
  private_type: visibilityToPrivateType(category.visibility),
  layout_type: category.layout === "list" ? 2 : 1,
  pic_layout: category.layout === "list" ? 2 : 1,
  set_top: category.isTop ? 1 : 0,
  update_time: category.updatedAt,
  create_time: category.createdAt
});
const toPhpProduct = (product, state) => {
  const images = state.images.filter((image) => image.productId === product.id);
  const colorImages = images.filter((image) => image.type === "colorChart").map(toPhpImage);
  const detailImages = images.filter((image) => image.type === "detailChart").map(toPhpImage);
  return {
    id: product.id,
    fid: product.id,
    product_id: product.id,
    uid: product.ownerUserId || ownerUid,
    owner_uid: product.ownerUserId || ownerUid,
    home_id: product.homeId || ownerUid,
    pid: product.categoryId || 0,
    category_id: product.categoryId || "",
    folder_type: 2,
    folder_name: product.name,
    name: product.name,
    folder_desc: product.intro,
    desc: product.intro,
    new_thumb: product.coverUrl,
    picture_url: product.coverUrl,
    private_type: visibilityToPrivateType(product.visibility),
    hide_detail_pictures: product.hideDetailImage ? 1 : 0,
    is_hot: product.isHot ? 1 : 0,
    sort: product.sortOrder,
    color_chart_count: colorImages.length,
    pic_count: colorImages.length,
    detail_chart_count: detailImages.length,
    detail_pic_count: detailImages.length,
    pic_ids_arr: colorImages,
    pic_list: colorImages,
    detail_pic_ids_arr: detailImages,
    detail_pic_list: detailImages,
    update_time: product.updatedAt,
    create_time: product.createdAt,
    is_collect: state.favorites.some((item) => item.type === "product" && String(item.target_id) === product.id) ? 1 : 0
  };
};
const getProduct = (state, id) => state.products.find((product) => product.id === id);
const getCategory = (state, id) => state.categories.find((category) => category.id === id);
const currentOrigin = () => {
  if (typeof window === "undefined") return "https://pic.jfyuntu.com";
  return window.location.origin;
};
const buildBatchLink = (code) => `${currentOrigin()}/assets/page/product-list.html?uploadd_code=${code}`;
const ensureBatchLink = (state, fid) => {
  if (!state.batchLinks[fid]) {
    state.batchLinks[fid] = {
      fid,
      code: randomCode(),
      upload_pwd: "",
      upload_enabled: 0,
      upload_pwd_expire_time: 0
    };
  }
  return state.batchLinks[fid];
};
const batchPayload = (state, fid) => {
  const link = ensureBatchLink(state, fid);
  const product = getProduct(state, fid);
  return {
    id: fid,
    fid,
    code: link.code,
    upload_code: link.code,
    upload_url: buildBatchLink(link.code),
    url: buildBatchLink(link.code),
    password: link.upload_pwd || "",
    pwd: link.upload_pwd || "",
    upload_pwd: link.upload_pwd || "",
    has_password: link.upload_pwd ? 1 : 0,
    upload_enabled: Number(link.upload_enabled || 0),
    access_enabled: Number(link.upload_enabled || 0),
    password_expire_time: Number(link.upload_pwd_expire_time || 0),
    upload_pwd_expire_time: Number(link.upload_pwd_expire_time || 0),
    owner_id: state.user.uid,
    owner_name: state.user.company_name,
    owner_avatar: state.user.company_logo || state.user.avatar,
    remaining_size: 6963,
    upload_limit: 50,
    concurrency_limit: Number(state.user.concurrency_limit || state.user.concurrent_rights || 1),
    folder_name: product?.name || "未命名产品",
    folder_desc: product?.intro || "",
    new_thumb: product?.coverUrl || fallbackImage
  };
};
const uploadInfoByCode = (state, code) => {
  const pair = Object.entries(state.batchLinks).find(([, value]) => value.code === code);
  if (!pair) return null;
  return batchPayload(state, pair[0]);
};
const normalizeType = (type) => type === "homepage" ? "home" : type;
const filterRecords = (rows, type = "all", keyword = "") => {
  const normalizedType = normalizeType(type);
  const kw = keyword.trim().toLowerCase();
  return rows.filter((item) => {
    const typeMatch = normalizedType === "all" || normalizeType(item.type || item.target_type) === normalizedType;
    const keywordMatch = !kw || [item.title, item.subtitle, item.source].some((value) => String(value || "").toLowerCase().includes(kw));
    return typeMatch && keywordMatch;
  });
};
const addOrRemoveFavorite = (state, type, id, add) => {
  const normalizedType = normalizeType(type);
  state.favorites = state.favorites.filter((item) => !(normalizeType(item.type) === normalizedType && String(item.target_id) === String(id)));
  if (!add) return;
  let title = "商户主页";
  let subtitle = "主页";
  let image = state.user.company_logo;
  if (normalizedType === "category") {
    const category = getCategory(state, id);
    title = category?.name || "分类";
    subtitle = "分类";
    image = category?.coverUrl || fallbackImage;
  } else if (normalizedType === "product") {
    const product = getProduct(state, id);
    title = product?.name || "产品";
    subtitle = "产品";
    image = product?.coverUrl || fallbackImage;
  }
  state.favorites.unshift(makeRecord(`fav_${Date.now()}`, normalizedType === "home" ? "homepage" : normalizedType, id, title, subtitle, image, ownerUid));
};
const addVisit = (state, type, id) => {
  const normalizedType = normalizeType(type);
  let title = state.user.company_name;
  let subtitle = "商户主页";
  let image = state.user.company_logo;
  if (normalizedType === "category") {
    const category = getCategory(state, id);
    title = category?.name || "分类";
    subtitle = "分类";
    image = category?.coverUrl || fallbackImage;
  } else if (normalizedType === "product") {
    const product = getProduct(state, id);
    title = product?.name || "产品";
    subtitle = "产品";
    image = product?.coverUrl || fallbackImage;
  }
  state.visits = state.visits.filter((item) => !(normalizeType(item.type) === normalizedType && String(item.target_id) === String(id)));
  state.visits.unshift(makeRecord(`visit_${Date.now()}`, normalizedType === "home" ? "homepage" : normalizedType, id, title, subtitle, image, ownerUid));
};
const createImageFromFile = (file, productId, type) => {
  const url = typeof URL !== "undefined" ? URL.createObjectURL(file) : fallbackImage;
  return {
    id: `img_${Date.now()}_${Math.floor(Math.random() * 1e4)}`,
    pid: `img_${Date.now()}_${Math.floor(Math.random() * 1e4)}`,
    productId,
    type,
    name: file.name,
    url,
    thumbnailUrl: url,
    sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
    sizeBytes: file.size,
    sortOrder: Date.now(),
    isOriginalLarge: file.size > 3 * 1024 * 1024,
    createdAt: nowText()
  };
};
const parseFormUpload = (formData) => {
  const file = formData.get("files") || formData.get("file");
  const fid = String(formData.get("fid") || formData.get("pid") || "");
  const fileType = String(formData.get("file_type") || "1");
  const type = fileType === "2" ? "detailChart" : "colorChart";
  return { file, fid, type };
};
const makeCategoryFromBody = (body, id) => ({
  id,
  homeId: ownerUid,
  parentId: body.pid && Number(body.pid) !== 0 ? String(body.pid) : void 0,
  name: body.folder_name || "未命名分类",
  intro: body.folder_desc || "",
  coverUrl: body.new_thumb || fallbackImage,
  productCount: 0,
  childCount: 0,
  visibility: privateTypeToVisibility(body.private_type),
  layout: Number(body.layout_type || body.pic_layout) === 2 ? "list" : "grid",
  isTop: Number(body.set_top || 0) === 1,
  updatedAt: nowText(),
  createdAt: nowText()
});
const makeProductFromBody = (body, id) => ({
  id,
  homeId: ownerUid,
  categoryId: Array.isArray(body.category_ids) && body.category_ids[0] ? String(body.category_ids[0]) : body.fid && Number(body.fid) !== 0 ? String(body.fid) : "",
  ownerUserId: ownerUid,
  name: body.folder_name || "未命名产品",
  intro: body.folder_desc || "",
  coverUrl: body.new_thumb || fallbackImage,
  visibility: privateTypeToVisibility(body.private_type),
  hideDetailImage: Number(body.hide_detail_pictures || 0) === 1,
  isHot: false,
  sortOrder: Date.now(),
  colorChartCount: 0,
  detailChartCount: 0,
  updatedAt: nowText(),
  createdAt: nowText()
});
const updateImagesForProduct = (state, productId, picIds, detailPicIds) => {
  const wanted = /* @__PURE__ */ new Set([...picIds, ...detailPicIds]);
  state.images = state.images.filter((image) => image.productId !== productId || wanted.has(image.id));
  state.images = state.images.map((image) => {
    if (picIds.includes(image.id)) return { ...image, productId, type: "colorChart" };
    if (detailPicIds.includes(image.id)) return { ...image, productId, type: "detailChart" };
    return image;
  });
};
const handleCreateOrEdit = (state, body, isEdit) => {
  const folderType = Number(body.folder_type || 2);
  if (folderType === 1) {
    const id2 = isEdit ? String(body.fid) : `cat_${Date.now()}`;
    const next2 = makeCategoryFromBody(body, id2);
    const index2 = state.categories.findIndex((item) => item.id === id2);
    if (index2 >= 0) state.categories[index2] = { ...state.categories[index2], ...next2, createdAt: state.categories[index2].createdAt };
    else state.categories.unshift(next2);
    saveState(state);
    return toPhpCategory(next2);
  }
  const id = isEdit ? String(body.fid) : `prod_${Date.now()}`;
  const next = makeProductFromBody(body, id);
  const index = state.products.findIndex((item) => item.id === id);
  if (index >= 0) state.products[index] = { ...state.products[index], ...next, createdAt: state.products[index].createdAt };
  else state.products.unshift(next);
  updateImagesForProduct(state, id, (body.pic_ids || []).map(String), (body.detail_pic_ids || []).map(String));
  saveState(state);
  return toPhpProduct(getProduct(state, id) || next, state);
};
async function mockApiRequest(path, options = {}) {
  await new Promise((resolve) => window.setTimeout(resolve, 120));
  const state = getState();
  const cleanPath = path.replace(/^\/+/, "").split("?")[0];
  const params = options.params || {};
  const body = options.body || {};
  switch (cleanPath) {
    case "user/login/qrcode":
      return ok({
        qrcode: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent("jfyuntu-mock-login")}`,
        scene: "mock_scene"
      });
    case "user/login/status":
      return ok({ status: "success", token: MOCK_TOKEN, user: state.user });
    case "user/show_info":
      return ok(state.user);
    case "user/update_pc_settings":
      Object.assign(state.user, body);
      saveState(state);
      return ok(state.user);
    case "user/home/info":
      return ok({
        user_info: state.user,
        product_count: state.products.length,
        total_album: state.products.length,
        is_collect: state.favorites.some((item) => normalizeType(item.type) === "home") ? 1 : 0
      });
    case "user/home/categories": {
      const fid = String(params.fid || params.category_id || "");
      const includeCurrent = Number(params.include_current || 0) === 1;
      const current = fid ? getCategory(state, fid) : null;
      const rows = state.categories.filter((category) => fid ? category.parentId === fid : !category.parentId).map(toPhpCategory);
      const payload = listPayload(rows);
      if (includeCurrent && current) payload.folder_info = toPhpCategory(current);
      return ok(payload);
    }
    case "user/home/products": {
      const cateId = String(params.cate_id || params.category_id || "");
      const rows = state.products.filter((product) => !cateId || product.categoryId === cateId).map((product) => toPhpProduct(product, state));
      return ok(listPayload(rows));
    }
    case "user/home/products/detail": {
      const product = getProduct(state, String(params.product_id || params.productId || body.fid || body.product_id));
      if (!product) throw new Error("产品不存在");
      return ok({ folder_info: toPhpProduct(product, state) });
    }
    case "user/home/share_link": {
      const uid = String(params.target_user_id || ownerUid);
      return ok({
        pc_link: `${currentOrigin()}/share-home.html?uid=${encodeURIComponent(uid)}`,
        web_link: `${currentOrigin()}/share-home.html?uid=${encodeURIComponent(uid)}`,
        mini_path: `/pages/index/index?uid=${encodeURIComponent(uid)}`
      });
    }
    case "user/home/minicode": {
      const uid = String(params.target_user_id || ownerUid);
      const type = String(params.type || "home");
      const id = String(params.id || "");
      const miniPath = type === "product" ? `/pages/product/detail?uid=${uid}&productId=${id}` : type === "category" ? `/pages/category/index?uid=${uid}&categoryId=${id}` : `/pages/index/index?uid=${uid}`;
      return ok({
        qrcode: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(miniPath)}`,
        qrcode_url: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(miniPath)}`,
        mini_path: miniPath
      });
    }
    case "album/lists/folder": {
      const folderType = Number(body.folder_type || params.folder_type || 2);
      const rows = folderType === 1 ? state.categories.map(toPhpCategory) : state.products.map((product) => toPhpProduct(product, state));
      return ok(listPayload(rows));
    }
    case "album/products/detail": {
      const product = getProduct(state, String(body.fid || params.fid || body.product_id));
      if (!product) throw new Error("产品不存在");
      return ok({ folder_info: toPhpProduct(product, state) });
    }
    case "album/create/folder":
      return ok(handleCreateOrEdit(state, body, false));
    case "album/edit/folder":
      return ok(handleCreateOrEdit(state, body, true));
    case "album/product/update_status":
      return ok({ success: true });
    case "album/delete/folder": {
      const id = String(body.fid || params.fid);
      const product = getProduct(state, id);
      const category = getCategory(state, id);
      if (product) {
        state.trash.unshift({
          id: `trash_${Date.now()}`,
          itemType: "product",
          sourceId: product.id,
          name: product.name,
          coverUrl: product.coverUrl,
          deletedAt: nowText(),
          canRestore: true
        });
        state.products = state.products.filter((item) => item.id !== id);
      }
      if (category) {
        state.trash.unshift({
          id: `trash_${Date.now()}`,
          itemType: "category",
          sourceId: category.id,
          name: category.name,
          coverUrl: category.coverUrl,
          deletedAt: nowText(),
          canRestore: true
        });
        state.categories = state.categories.filter((item) => item.id !== id);
      }
      saveState(state);
      return ok({ success: true });
    }
    case "album/batch_link":
      return ok(batchPayload(state, String(params.fid || body.fid)));
    case "album/reset_batch_link": {
      const fid = String(body.fid || params.fid);
      state.batchLinks[fid] = {
        ...ensureBatchLink(state, fid),
        code: randomCode()
      };
      saveState(state);
      return ok(batchPayload(state, fid));
    }
    case "album/batch_upload_password": {
      const fid = String(body.fid);
      state.batchLinks[fid] = {
        ...ensureBatchLink(state, fid),
        upload_enabled: Number(body.upload_enabled || 0),
        upload_pwd: body.upload_pwd || "",
        upload_pwd_expire_time: Number(body.upload_pwd_expire_time || 0)
      };
      saveState(state);
      return ok(batchPayload(state, fid));
    }
    case "web/upload": {
      const info = uploadInfoByCode(state, String(params.code || body.code));
      if (!info) throw new Error("链接无效或已失效");
      return ok(info);
    }
    case "web/token/upload": {
      const info = uploadInfoByCode(state, String(body.code || params.code));
      if (!info) throw new Error("链接无效或已失效");
      if (Number(info.upload_enabled || 0) !== 1) throw new Error("此产品上传入口已关闭");
      if (info.upload_pwd && String(body.password || "") !== String(info.upload_pwd)) throw new Error("访问密码错误");
      return ok({ token: `mock_upload_${info.fid}_${Date.now()}` });
    }
    case "user/add/collect":
      addOrRemoveFavorite(state, body.type, String(body.id), true);
      saveState(state);
      return ok({ success: true });
    case "user/cancel/collect":
      addOrRemoveFavorite(state, body.type, String(body.id), false);
      saveState(state);
      return ok({ success: true });
    case "user/add/visit":
      addVisit(state, body.type, String(body.id));
      saveState(state);
      return ok({ success: true });
    case "user/collect/records":
      return ok(listPayload(filterRecords(state.favorites, String(params.type || "all"), String(params.key || ""))));
    case "user/visit/records":
      return ok(listPayload(filterRecords(state.visits, String(params.type || "all"), String(params.key || ""))));
    case "user/del/visit": {
      const ids = String(body.visit_ids || "").split(",").filter(Boolean);
      state.visits = state.visits.filter((item) => !ids.includes(String(item.id)));
      saveState(state);
      return ok({ success: true });
    }
    case "web_payment/subscription/plans":
      return ok(listPayload(state.plans));
    case "web_payment/orders":
      return ok(listPayload(state.orders));
    case "web_payment/membership/order/create": {
      const planId = String(body.membership_plan_id || body.plan_id || "");
      const plan = state.plans.find((item) => String(item.id) === planId);
      const order = {
        id: `order_${Date.now()}`,
        packageId: planId,
        plan_id: planId,
        membership_plan_id: planId,
        orderNo: `MOCK${Date.now()}`,
        order_no: `MOCK${Date.now()}`,
        amount: String(plan?.price || "0").replace(/[^\d.]/g, "") || "0.00",
        status: "pending",
        create_time: nowText(),
        createdAt: nowText(),
        updatedAt: nowText(),
        pay_url: ""
      };
      state.orders.unshift(order);
      saveState(state);
      return ok(order);
    }
    case "album/ai/resources":
      return ok(listPayload(state.resources));
    case "album/ai/import_resource":
      return ok({ success: true });
    case "user/recycle/list":
      return ok(listPayload(state.trash.map((item) => ({
        id: item.sourceId || item.id,
        fid: item.sourceId || item.id,
        folder_type: item.itemType === "category" ? 1 : 2,
        folder_name: item.name,
        name: item.name,
        imgurl: item.coverUrl,
        new_thumb: item.coverUrl,
        delete_time: item.deletedAt
      }))));
    case "user/restore/product":
      state.trash = state.trash.filter((item) => String(item.sourceId || item.id) !== String(body.product_ids));
      saveState(state);
      return ok({ success: true });
    case "user/destroy/product":
      state.trash = state.trash.filter((item) => String(item.sourceId || item.id) !== String(body.product_ids));
      saveState(state);
      return ok({ success: true });
    default:
      return ok({ success: true, path: cleanPath, params, body });
  }
}
async function mockApiUpload(path, formData) {
  await new Promise((resolve) => window.setTimeout(resolve, 180));
  const state = getState();
  const cleanPath = path.replace(/^\/+/, "").split("?")[0];
  const { file, fid, type } = parseFormUpload(formData);
  if (!file || !fid) throw new Error("上传参数不完整");
  if (cleanPath === "album/upload/folder" || cleanPath === "web/folder/pic/upload") {
    const image = createImageFromFile(file, fid, type);
    state.images.push(image);
    saveState(state);
    return ok({ data: [toPhpImage(image)], lists: [toPhpImage(image)] });
  }
  return ok({ data: [] });
}
const DEFAULT_API_BASE = "https://api.jfyuntu.com/api";
class ApiError extends Error {
  code;
  data;
  constructor(message, code = -1, data = null) {
    super(message);
    this.name = "ApiError";
    this.code = code;
    this.data = data;
  }
}
const TOKEN_KEY = "jfyuntu_pc_token";
const USER_KEY = "jfyuntu_pc_user";
const UPLOAD_TOKEN_KEY = "jfyuntu_web_upload_token";
const UPLOAD_TOKEN_CODE_KEY = "jfyuntu_web_upload_code";
const getRuntimeApiBase = () => {
  if (typeof window === "undefined") return DEFAULT_API_BASE;
  const injected = window.__JFYUNTU_API_BASE__;
  return injected || void 0 || DEFAULT_API_BASE;
};
const joinUrl = (base, path) => {
  if (/^https?:\/\//i.test(path)) return path;
  return `${base.replace(/\/$/, "")}/${path.replace(/^\//, "")}`;
};
const normalizeToken = (token = "") => token.replace(/^Bearer\s+/i, "").trim();
const removeAuthCallbackParams = () => {
  if (typeof window === "undefined") return;
  const url = new URL(window.location.href);
  let changed = false;
  ["token", "access_token", "authorization", "login", "error"].forEach((key) => {
    if (url.searchParams.has(key)) {
      url.searchParams.delete(key);
      changed = true;
    }
  });
  if (changed) {
    const next = `${url.pathname}${url.search}${url.hash}`;
    window.history.replaceState({}, "", next || window.location.pathname);
  }
};
const authStore = {
  getToken() {
    if (typeof localStorage === "undefined") return "";
    return normalizeToken(localStorage.getItem(TOKEN_KEY) || localStorage.getItem("token") || "");
  },
  setToken(token) {
    if (typeof localStorage === "undefined") return;
    const normalized = normalizeToken(token);
    if (normalized) {
      localStorage.setItem(TOKEN_KEY, normalized);
      localStorage.setItem("token", normalized);
    }
  },
  clearToken() {
    if (typeof localStorage === "undefined") return;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem("token");
    localStorage.removeItem(USER_KEY);
    localStorage.removeItem("userInfo");
  },
  getUser() {
    if (typeof localStorage === "undefined") return null;
    const raw = localStorage.getItem(USER_KEY) || localStorage.getItem("userInfo");
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  },
  setUser(user) {
    if (typeof localStorage === "undefined") return;
    localStorage.setItem(USER_KEY, JSON.stringify(user || {}));
    localStorage.setItem("userInfo", JSON.stringify(user || {}));
  },
  isLoggedIn() {
    return !!this.getToken();
  },
  consumeCallbackToken() {
    if (typeof window === "undefined") return "";
    const params = new URLSearchParams(window.location.search);
    const token = normalizeToken(
      params.get("token") || params.get("access_token") || params.get("authorization") || ""
    );
    if (token) {
      this.setToken(token);
      removeAuthCallbackParams();
      return token;
    }
    const error = params.get("error");
    if (error) removeAuthCallbackParams();
    return "";
  }
};
const uploadTokenStore = {
  get(code = "") {
    if (typeof sessionStorage === "undefined") return "";
    const savedCode = sessionStorage.getItem(UPLOAD_TOKEN_CODE_KEY) || "";
    if (code && savedCode && savedCode !== code) return "";
    return normalizeToken(sessionStorage.getItem(UPLOAD_TOKEN_KEY) || "");
  },
  set(token, code = "") {
    if (typeof sessionStorage === "undefined") return;
    sessionStorage.setItem(UPLOAD_TOKEN_KEY, normalizeToken(token));
    if (code) sessionStorage.setItem(UPLOAD_TOKEN_CODE_KEY, code);
  },
  clear() {
    if (typeof sessionStorage === "undefined") return;
    sessionStorage.removeItem(UPLOAD_TOKEN_KEY);
    sessionStorage.removeItem(UPLOAD_TOKEN_CODE_KEY);
  }
};
const buildQuery = (params) => {
  const search = new URLSearchParams();
  Object.entries(params || {}).forEach(([key, value]) => {
    if (value === void 0 || value === null || value === "") return;
    search.set(key, String(value));
  });
  return search.toString();
};
async function apiRequest(path, options = {}) {
  if (isLocalMockEnabled()) {
    return mockApiRequest(path, options);
  }
  const method = options.method || "GET";
  const query = buildQuery(options.params);
  const url = `${joinUrl(getRuntimeApiBase(), path)}${query ? `?${query}` : ""}`;
  const token = normalizeToken(options.token || (options.auth === false ? "" : authStore.getToken()));
  const headers = {
    "X-Requested-With": "XMLHttpRequest"
  };
  if (token) headers["authorization-token"] = `Bearer ${token}`;
  const init = { method, headers };
  if (method !== "GET") {
    headers["Content-Type"] = "application/json";
    init.body = JSON.stringify(options.body || {});
  }
  const response = await fetch(url, init);
  const payload = await response.json().catch(() => null);
  if (!payload) {
    throw new ApiError("接口响应异常", response.status);
  }
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || "请求失败", Number(payload.code), payload.data);
  }
  return payload.data;
}
async function apiUpload(path, formData, token = authStore.getToken()) {
  if (isLocalMockEnabled()) {
    return mockApiUpload(path, formData);
  }
  const headers = {
    "X-Requested-With": "XMLHttpRequest"
  };
  const normalized = normalizeToken(token);
  if (normalized) headers["authorization-token"] = `Bearer ${normalized}`;
  const response = await fetch(joinUrl(getRuntimeApiBase(), path), {
    method: "POST",
    headers,
    body: formData
  });
  const payload = await response.json().catch(() => null);
  if (!payload) throw new ApiError("上传响应异常", response.status);
  if (Number(payload.code) !== 0) {
    throw new ApiError(payload.msg || payload.message || "上传失败", Number(payload.code), payload.data);
  }
  return payload.data;
}
const pcApi = {
  getLoginQrcode: () => apiRequest("user/login/qrcode", { auth: false }),
  checkLoginStatus: (scene) => apiRequest("user/login/status", { params: { scene, timestamp: Date.now() }, auth: false }),
  getCurrentUser: () => apiRequest("user/show_info"),
  updatePcSettings: (body) => apiRequest("user/update_pc_settings", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getHomeInfo: (targetUserId = "") => apiRequest("user/home/info", { params: { target_user_id: targetUserId, timestamp: Date.now() } }),
  getHomeCategories: (targetUserId = "", fid = "", includeCurrent = 0) => apiRequest("user/home/categories", {
    params: { target_user_id: targetUserId, fid, include_current: includeCurrent, timestamp: Date.now() }
  }),
  getHomeProducts: (targetUserId = "", cateId = "") => apiRequest("user/home/products", {
    params: { target_user_id: targetUserId, cate_id: cateId, timestamp: Date.now() }
  }),
  getHomeProductDetail: (targetUserId = "", productId) => apiRequest("user/home/products/detail", {
    params: { target_user_id: targetUserId, product_id: productId, timestamp: Date.now() }
  }),
  getHomeShareLink: (targetUserId, path = "") => apiRequest("user/home/share_link", {
    params: { target_user_id: targetUserId, path, timestamp: Date.now() },
    auth: false
  }),
  getHomeMiniCode: (targetUserId, type = "home", id = "", path = "") => apiRequest("user/home/minicode", {
    params: { target_user_id: targetUserId, type, id, path, timestamp: Date.now() },
    auth: false
  }),
  getManagementCategories: (params) => apiRequest("album/lists/folder", { method: "POST", body: { folder_type: 1, limit: 100, timestamp: Date.now(), ...params } }),
  getManagementProducts: (params) => apiRequest("album/lists/folder", { method: "POST", body: { folder_type: 2, limit: 50, timestamp: Date.now(), ...params } }),
  getProductEditDetail: (fid) => apiRequest("album/products/detail", { method: "POST", body: { fid, timestamp: Date.now() } }),
  createProductOrCategory: (body) => apiRequest("album/create/folder", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  editProductOrCategory: (body) => apiRequest("album/edit/folder", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  updateProductStatus: (body) => apiRequest("album/product/update_status", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  deleteProductOrFolder: (fid, delType = 1) => apiRequest("album/delete/folder", { method: "POST", body: { fid, del_type: delType, timestamp: Date.now() } }),
  uploadProductImage: (fid, file, type) => {
    const form = new FormData();
    form.append("pid", fid);
    form.append("files", file, file.name);
    form.append("filename", file.name);
    form.append("file_name", file.name);
    form.append("original_name", file.name);
    form.append("name", file.name);
    form.append("file_type", type === "detailChart" ? "2" : "1");
    return apiUpload("album/upload/folder", form);
  },
  getBatchUploadLink: (fid) => apiRequest("album/batch_link", { params: { fid, timestamp: Date.now() } }),
  resetBatchUploadLink: (fid) => apiRequest("album/reset_batch_link", { method: "POST", body: { fid, timestamp: Date.now() } }),
  saveBatchUploadPassword: (body) => apiRequest("album/batch_upload_password", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getWebUploadInfo: (code) => apiRequest("web/upload", { params: { code, timestamp: Date.now() }, auth: false }),
  getWebUploadToken: (code, password = "") => apiRequest("web/token/upload", { method: "POST", body: { code, password }, auth: false }),
  uploadWebProductImage: (fid, file, type, token) => {
    const form = new FormData();
    form.append("fid", fid);
    form.append("files", file, file.name);
    form.append("filename", file.name);
    form.append("file_name", file.name);
    form.append("original_name", file.name);
    form.append("name", file.name);
    form.append("file_type", type === "detailChart" ? "2" : "1");
    return apiUpload("web/folder/pic/upload", form, token);
  },
  toggleFavorite: (type, id, add) => apiRequest(add ? "user/add/collect" : "user/cancel/collect", {
    method: "POST",
    body: { type, id, timestamp: Date.now() }
  }),
  addVisit: (type, id) => apiRequest("user/add/visit", { method: "POST", body: { type, id, timestamp: Date.now() } }),
  getFavorites: (type = "all", key = "", page = 1) => apiRequest("user/collect/records", { params: { type, key, page, timestamp: Date.now() } }),
  getVisits: (type = "all", key = "", page = 1) => apiRequest("user/visit/records", { params: { type, key, page, timestamp: Date.now() } }),
  deleteVisit: (visitIds) => apiRequest("user/del/visit", { method: "POST", body: { visit_ids: visitIds, timestamp: Date.now() } }),
  getSubscriptionPlans: () => apiRequest("web_payment/subscription/plans", { auth: false }),
  createMembershipOrder: (body) => apiRequest("web_payment/membership/order/create", { method: "POST", body: { timestamp: Date.now(), ...body } }),
  getPaymentOrders: (params = {}) => apiRequest("web_payment/orders", { params: { page: 1, page_size: 20, timestamp: Date.now(), ...params } }),
  getAiResources: (params = {}) => apiRequest("album/ai/resources", { params: { page: 1, page_size: 30, timestamp: Date.now(), ...params } }),
  importAiResource: (resourceId, role = "cover", productId = "") => apiRequest("album/ai/import_resource", {
    method: "POST",
    body: { resource_id: resourceId, role, product_id: productId, timestamp: Date.now() }
  }),
  getRecycleList: (params = {}) => apiRequest("user/recycle/list", { params: { page: 1, limit: 20, timestamp: Date.now(), ...params } }),
  restoreRecycleItem: (id) => apiRequest("user/restore/product", { method: "POST", body: { product_ids: id, timestamp: Date.now() } }),
  deleteRecycleItem: (id) => apiRequest("user/destroy/product", { method: "POST", body: { product_ids: id, timestamp: Date.now() } })
};
const _sfc_main$8 = defineComponent({ __name: "Dialog", props: { open: { type: Boolean }, defaultOpen: { type: Boolean }, modal: { type: Boolean } }, emits: ["update:open"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, forwarded = useForwardPropsEmits(props, emits), __returned__ = { props, emits, forwarded, get DialogRoot() {
    return DialogRoot;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$8(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogRoot, mergeProps($setup.forwarded, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/Dialog.vue"), _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const Dialog = _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$8]]);
const _sfc_main$7 = defineComponent({ __name: "DialogClose", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DialogClose() {
    return DialogClose;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogClose, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogClose.vue"), _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
_export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$6 = defineComponent({ __name: "DialogContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get X() {
    return X;
  }, get DialogClose() {
    return DialogClose;
  }, get DialogContent() {
    return DialogContent$1;
  }, get DialogOverlay() {
    return DialogOverlay;
  }, get DialogPortal() {
    return DialogPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, null, _parent2, _scopeId)), _push2(ssrRenderComponent($setup.DialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push3, _parent3, _scopeId2), _push3(ssrRenderComponent($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) _push4(ssrRenderComponent($setup.X, { class: "w-4 h-4" }, null, _parent4, _scopeId3)), _push4(`<span class="sr-only"${_scopeId3}>Close</span>`);
        else return [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")];
      }), _: 1 }, _parent3, _scopeId2));
      else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogOverlay, { class: "fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }), createVNode($setup.DialogContent, mergeProps($setup.forwarded, { class: $setup.cn("fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg", $setup.props.class) }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class"])];
  }), _: 3 }, _parent));
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogContent.vue"), _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const DialogContent = _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$6]]);
const _sfc_main$5 = defineComponent({ __name: "DialogDescription", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DialogDescription() {
    return DialogDescription$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogDescription, mergeProps($setup.forwardedProps, { class: $setup.cn("text-sm text-muted-foreground", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogDescription.vue"), _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const DialogDescription = _export_sfc(_sfc_main$5, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$4 = defineComponent({ __name: "DialogFooter", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-x-2", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogFooter.vue"), _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const DialogFooter = _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$3 = defineComponent({ __name: "DialogHeader", props: { class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: $setup.cn("flex flex-col gap-y-1.5 text-center sm:text-left", $setup.props.class) }, _attrs))}>`), ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent), _push("</div>");
}
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogHeader.vue"), _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const DialogHeader = _export_sfc(_sfc_main$3, [["ssrRender", _sfc_ssrRender$3]]);
const _sfc_main$2 = defineComponent({ __name: "DialogScrollContent", props: { forceMount: { type: Boolean }, disableOutsidePointerEvents: { type: Boolean }, asChild: { type: Boolean }, as: {}, class: {} }, emits: ["escapeKeyDown", "pointerDownOutside", "focusOutside", "interactOutside", "openAutoFocus", "closeAutoFocus"], setup(__props, { expose: __expose, emit: __emit }) {
  __expose();
  const props = __props, emits = __emit, delegatedProps = reactiveOmit(props, "class"), forwarded = useForwardPropsEmits(delegatedProps, emits), __returned__ = { props, emits, delegatedProps, forwarded, get X() {
    return X;
  }, get DialogClose() {
    return DialogClose;
  }, get DialogContent() {
    return DialogContent$1;
  }, get DialogOverlay() {
    return DialogOverlay;
  }, get DialogPortal() {
    return DialogPortal;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogPortal, _attrs, { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) _push2(ssrRenderComponent($setup.DialogOverlay, { class: "fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, { default: withCtx((_2, _push3, _parent3, _scopeId2) => {
      if (_push3) _push3(ssrRenderComponent($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
        const originalEvent = event.detail.originalEvent, target = originalEvent.target;
        (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
      } }), { default: withCtx((_3, _push4, _parent4, _scopeId3) => {
        if (_push4) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push4, _parent4, _scopeId3), _push4(ssrRenderComponent($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx((_4, _push5, _parent5, _scopeId4) => {
          if (_push5) _push5(ssrRenderComponent($setup.X, { class: "w-4 h-4" }, null, _parent5, _scopeId4)), _push5(`<span class="sr-only"${_scopeId4}>Close</span>`);
          else return [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")];
        }), _: 1 }, _parent4, _scopeId3));
        else return [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })];
      }), _: 3 }, _parent3, _scopeId2));
      else return [createVNode($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
        const originalEvent = event.detail.originalEvent, target = originalEvent.target;
        (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
      } }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class", "onPointerDownOutside"])];
    }), _: 3 }, _parent2, _scopeId));
    else return [createVNode($setup.DialogOverlay, { class: "fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" }, { default: withCtx(() => [createVNode($setup.DialogContent, mergeProps({ class: $setup.cn("relative z-50 grid w-full max-w-lg my-8 gap-4 border border-border bg-background p-6 shadow-lg duration-200 sm:rounded-lg md:w-full", $setup.props.class) }, $setup.forwarded, { onPointerDownOutside: (event) => {
      const originalEvent = event.detail.originalEvent, target = originalEvent.target;
      (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) && event.preventDefault();
    } }), { default: withCtx(() => [renderSlot(_ctx.$slots, "default"), createVNode($setup.DialogClose, { class: "absolute top-3 right-3 p-0.5 transition-colors rounded-md hover:bg-secondary" }, { default: withCtx(() => [createVNode($setup.X, { class: "w-4 h-4" }), createVNode("span", { class: "sr-only" }, "Close")]), _: 1 })]), _: 3 }, 16, ["class", "onPointerDownOutside"])]), _: 3 })];
  }), _: 3 }, _parent));
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogScrollContent.vue"), _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
_export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$2]]);
const _sfc_main$1 = defineComponent({ __name: "DialogTitle", props: { asChild: { type: Boolean }, as: {}, class: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const props = __props, delegatedProps = reactiveOmit(props, "class"), forwardedProps = useForwardProps(delegatedProps), __returned__ = { props, delegatedProps, forwardedProps, get DialogTitle() {
    return DialogTitle$1;
  }, get cn() {
    return cn;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogTitle, mergeProps($setup.forwardedProps, { class: $setup.cn("text-lg font-semibold leading-none tracking-tight", $setup.props.class) }, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogTitle.vue"), _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const DialogTitle = _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main = defineComponent({ __name: "DialogTrigger", props: { asChild: { type: Boolean }, as: {} }, setup(__props, { expose: __expose }) {
  __expose();
  const __returned__ = { props: __props, get DialogTrigger() {
    return DialogTrigger;
  } };
  return Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true }), __returned__;
} });
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(ssrRenderComponent($setup.DialogTrigger, mergeProps($setup.props, _attrs), { default: withCtx((_, _push2, _parent2, _scopeId) => {
    if (_push2) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
    else return [renderSlot(_ctx.$slots, "default")];
  }), _: 3 }, _parent));
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  return (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("src/components/ui/dialog/DialogTrigger.vue"), _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
_export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);
export {
  DialogTitle as D,
  DialogHeader as a,
  DialogContent as b,
  Dialog as c,
  authStore as d,
  DialogFooter as e,
  DialogDescription as f,
  isLocalMockEnabled as i,
  pcApi as p,
  uploadTokenStore as u
};
