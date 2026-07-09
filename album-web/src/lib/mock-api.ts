import { categoryDataList } from '@/data/CategoryService'
import { productDataList } from '@/data/ProductService'
import { productImageDataList } from '@/data/ProductImageService'
import { planPackageDataList } from '@/data/PlanPackageService'
import { orderDataList } from '@/data/OrderService'
import { resourceLibraryDataList } from '@/data/ResourceLibraryService'
import { trashDataList } from '@/data/TrashService'

type Method = 'GET' | 'POST' | 'PUT' | 'DELETE'

interface MockRequestOptions {
  method?: Method
  params?: Record<string, any>
  body?: Record<string, any>
  token?: string
  auth?: boolean
}

interface MockProductImage {
  id: string
  productId: string
  type: 'colorChart' | 'detailChart'
  name: string
  url: string
  thumbnailUrl: string
  sizeLabel: string
  sizeBytes: number
  sortOrder: number
  isOriginalLarge: boolean
  createdAt: string
}

interface MockCategory {
  id: string
  homeId: string
  parentId?: string
  name: string
  intro: string
  coverUrl: string
  productCount: number
  childCount: number
  visibility: 'public' | 'private' | 'shared'
  layout: 'grid' | 'list'
  isTop: boolean
  updatedAt: string
  createdAt: string
}

interface MockProduct {
  id: string
  homeId: string
  categoryId?: string
  ownerUserId: string
  name: string
  intro: string
  coverUrl: string
  visibility: 'public' | 'private' | 'shared'
  hideDetailImage: boolean
  isHot: boolean
  sortOrder: number
  colorChartCount: number
  detailChartCount: number
  updatedAt: string
  createdAt: string
}

interface MockState {
  user: Record<string, any>
  categories: MockCategory[]
  products: MockProduct[]
  images: MockProductImage[]
  batchLinks: Record<string, any>
  favorites: any[]
  visits: any[]
  trash: any[]
  orders: any[]
  resources: any[]
  plans: any[]
}

const STORAGE_KEY = 'jfyuntu_pc_mock_state_v1'
const MOCK_TOKEN = 'mock-local-token'
const ownerUid = 'user_001'
const fallbackImage = 'https://api.jfyuntu.com/image/static/footer/jfyuntu.png'

const nowText = () => new Date().toLocaleString('zh-CN', { hour12: false }).replace(/\//g, '-')
const randomCode = (length = 10) => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'
  return Array.from({ length }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

export const isLocalMockEnabled = () => {
  if (typeof window === 'undefined') return false
  const mode = import.meta.env.PUBLIC_JFYUNTU_MOCK
  if (mode === '0' || mode === 'false') return false
  if (mode === '1' || mode === 'true') return true
  return ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname)
}

const clone = <T>(value: T): T => JSON.parse(JSON.stringify(value))

const baseUser = {
  id: ownerUid,
  uid: ownerUid,
  username: 'yunzhi',
  nickname: '云织主理人',
  avatar: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/98e5967d-09b8-43ee-9951-248ffa1aec4b.png',
  company_name: '云织家纺',
  company_logo: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/404cfc00-741d-4356-a857-5af6f1fe0cb8.png',
  company_desc:
    '专注家纺面料与成品展示，提供稳定的产品资料维护、客户分享、协作上传与下载管理能力。',
  industry_name: '家纺面料',
  contact_mobile: '021-5678-9001',
  contact_wechat: 'yunzhi-home',
  home_share_code: 'HF3K8P2Q6R1A',
  share_code: 'HF3K8P2Q6R1A',
  invite_code: 'YUNZHI88',
  address_province: '浙江省',
  address_city: '杭州市',
  address_district: '余杭区',
  address_detail: '文一西路 998 号云纺大厦 8F',
  grade_name: '专业版 10G',
  all_space: '10G',
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
  home_watermark_text: '© 云织家纺 版权所有',
  home_share_title: '云织家纺产品主页',
  home_share_desc: '查看最新家纺产品、分类、花色图和详情图。',
  is_show_home: 1,
  create_time: '2026-01-10 09:00:00',
  update_time: '2026-07-08 09:30:00',
}

const normalizeCategoryCount = (categories: MockCategory[], products: MockProduct[]) => {
  return categories.map(category => ({
    ...category,
    productCount: products.filter(product => product.categoryId === category.id).length,
    childCount: categories.filter(item => item.parentId === category.id).length,
  }))
}

const withImageCounts = (products: MockProduct[], images: MockProductImage[]) => {
  return products.map(product => {
    const productImages = images.filter(image => image.productId === product.id)
    const firstImage = productImages.find(image => image.type === 'colorChart') || productImages[0]
    return {
      ...product,
      coverUrl: product.coverUrl || firstImage?.thumbnailUrl || firstImage?.url || fallbackImage,
      colorChartCount: productImages.filter(image => image.type === 'colorChart').length,
      detailChartCount: productImages.filter(image => image.type === 'detailChart').length,
    }
  })
}

const makeInitialState = (): MockState => {
  const images = clone(productImageDataList) as MockProductImage[]
  const products = withImageCounts(clone(productDataList) as MockProduct[], images)
  const categories = normalizeCategoryCount(clone(categoryDataList) as MockCategory[], products)
  return {
    user: {
      ...baseUser,
      product_count: products.length,
      category_count: categories.length,
    },
    categories,
    products,
    images,
    batchLinks: {
      prod_001: {
        fid: 'prod_001',
        code: 'JivHTmmJUC',
        upload_pwd: 'A7K3',
        upload_enabled: 1,
        upload_pwd_expire_time: 0,
      },
    },
    favorites: [
      makeRecord('fav_001', 'homepage', ownerUid, baseUser.company_name, '商户主页', baseUser.company_logo, ownerUid),
      makeRecord('fav_002', 'category', 'cat_002', '纯棉四件套', '分类', categories[1]?.coverUrl, ownerUid),
      makeRecord('fav_003', 'product', 'prod_001', '云感纯棉四件套', '产品', products[0]?.coverUrl, ownerUid),
    ],
    visits: [
      makeRecord('visit_001', 'homepage', ownerUid, baseUser.company_name, '商户主页', baseUser.company_logo, ownerUid),
      makeRecord('visit_002', 'category', 'cat_001', '床品套件', '分类', categories[0]?.coverUrl, ownerUid),
      makeRecord('visit_003', 'product', 'prod_004', '极简遮光窗帘', '产品', products[3]?.coverUrl, ownerUid),
    ],
    trash: clone(trashDataList),
    orders: clone(orderDataList),
    resources: clone(resourceLibraryDataList),
    plans: [
      {
        id: 'plan_free',
        name: '免费版 50MB',
        capacityMb: 50,
        price: '¥0',
        concurrentRights: 1,
        trafficGb: 2,
        durationLabel: '长期',
        isRecommended: false,
        createdAt: '2026-07-01 10:00:00',
      },
      {
        id: 'plan_5g',
        name: '基础版 5G',
        capacityMb: 5 * 1024,
        price: '¥29 / 年',
        concurrentRights: 2,
        trafficGb: 100,
        durationLabel: '1 年',
        isRecommended: false,
        createdAt: '2026-07-01 10:05:00',
      },
      {
        id: 'plan_10g',
        name: '专业版 10G',
        capacityMb: 10 * 1024,
        price: '¥59 / 年',
        concurrentRights: 5,
        trafficGb: 500,
        durationLabel: '1 年',
        isRecommended: true,
        createdAt: '2026-07-01 10:10:00',
      },
      {
        id: 'plan_200g',
        name: '旗舰版 200G',
        capacityMb: 200 * 1024,
        price: '¥1099 / 年',
        concurrentRights: 20,
        trafficGb: 5000,
        durationLabel: '1 年',
        isRecommended: false,
        createdAt: '2026-07-01 10:15:00',
      },
      ...clone(planPackageDataList),
    ],
  }
}

function makeRecord(id: string, type: string, targetId: string, title: string, subtitle: string, image = '', targetUid = ownerUid) {
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
    time: Math.floor(Date.now() / 1000),
  }
}

const getState = (): MockState => {
  if (typeof localStorage === 'undefined') return makeInitialState()
  const raw = localStorage.getItem(STORAGE_KEY)
  if (raw) {
    try {
      return JSON.parse(raw) as MockState
    } catch {
      localStorage.removeItem(STORAGE_KEY)
    }
  }
  const state = makeInitialState()
  saveState(state)
  return state
}

const saveState = (state: MockState) => {
  state.products = withImageCounts(state.products, state.images)
  state.categories = normalizeCategoryCount(state.categories, state.products)
  state.user.product_count = state.products.length
  state.user.category_count = state.categories.length
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  }
}

const ok = <T>(data: T) => clone(data)

const listPayload = (rows: any[]) => ({
  data: rows,
  lists: rows,
  total: rows.length,
  per_page: rows.length,
  current_page: 1,
})

const visibilityToPrivateType = (visibility: string) => {
  if (visibility === 'private') return 2
  if (visibility === 'shared') return 4
  return 1
}

const privateTypeToVisibility = (value: any) => {
  if (Number(value) === 2) return 'private'
  if (Number(value) === 4) return 'shared'
  return 'public'
}

const toPhpImage = (image: MockProductImage) => ({
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
  create_time: image.createdAt,
})

const toPhpCategory = (category: MockCategory) => ({
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
  layout_type: category.layout === 'list' ? 2 : 1,
  pic_layout: category.layout === 'list' ? 2 : 1,
  set_top: category.isTop ? 1 : 0,
  update_time: category.updatedAt,
  create_time: category.createdAt,
})

const toPhpProduct = (product: MockProduct, state: MockState) => {
  const images = state.images.filter(image => image.productId === product.id)
  const colorImages = images.filter(image => image.type === 'colorChart').map(toPhpImage)
  const detailImages = images.filter(image => image.type === 'detailChart').map(toPhpImage)
  return {
    id: product.id,
    fid: product.id,
    product_id: product.id,
    uid: product.ownerUserId || ownerUid,
    owner_uid: product.ownerUserId || ownerUid,
    home_id: product.homeId || ownerUid,
    pid: product.categoryId || 0,
    category_id: product.categoryId || '',
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
    is_collect: state.favorites.some(item => item.type === 'product' && String(item.target_id) === product.id) ? 1 : 0,
  }
}

const getProduct = (state: MockState, id: string) => state.products.find(product => product.id === id)
const getCategory = (state: MockState, id: string) => state.categories.find(category => category.id === id)

const currentOrigin = () => {
  if (typeof window === 'undefined') return 'https://pic.jfyuntu.com'
  return window.location.origin
}

const buildBatchLink = (code: string) => `${currentOrigin()}/assets/page/product-list.html?uploadd_code=${code}`

const ensureBatchLink = (state: MockState, fid: string) => {
  if (!state.batchLinks[fid]) {
    state.batchLinks[fid] = {
      fid,
      code: randomCode(),
      upload_pwd: '',
      upload_enabled: 0,
      upload_pwd_expire_time: 0,
    }
  }
  return state.batchLinks[fid]
}

const batchPayload = (state: MockState, fid: string) => {
  const link = ensureBatchLink(state, fid)
  const product = getProduct(state, fid)
  return {
    id: fid,
    fid,
    code: link.code,
    upload_code: link.code,
    upload_url: buildBatchLink(link.code),
    url: buildBatchLink(link.code),
    password: link.upload_pwd || '',
    pwd: link.upload_pwd || '',
    upload_pwd: link.upload_pwd || '',
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
    folder_name: product?.name || '未命名产品',
    folder_desc: product?.intro || '',
    new_thumb: product?.coverUrl || fallbackImage,
  }
}

const uploadInfoByCode = (state: MockState, code: string) => {
  const pair = Object.entries(state.batchLinks).find(([, value]) => value.code === code)
  if (!pair) return null
  return batchPayload(state, pair[0])
}

const normalizeType = (type: string) => (type === 'homepage' ? 'home' : type)

const filterRecords = (rows: any[], type = 'all', keyword = '') => {
  const normalizedType = normalizeType(type)
  const kw = keyword.trim().toLowerCase()
  return rows.filter(item => {
    const typeMatch = normalizedType === 'all' || normalizeType(item.type || item.target_type) === normalizedType
    const keywordMatch = !kw || [item.title, item.subtitle, item.source].some(value => String(value || '').toLowerCase().includes(kw))
    return typeMatch && keywordMatch
  })
}

const addOrRemoveFavorite = (state: MockState, type: string, id: string, add: boolean) => {
  const normalizedType = normalizeType(type)
  state.favorites = state.favorites.filter(item => !(normalizeType(item.type) === normalizedType && String(item.target_id) === String(id)))
  if (!add) return
  let title = '商户主页'
  let subtitle = '主页'
  let image = state.user.company_logo
  if (normalizedType === 'category') {
    const category = getCategory(state, id)
    title = category?.name || '分类'
    subtitle = '分类'
    image = category?.coverUrl || fallbackImage
  } else if (normalizedType === 'product') {
    const product = getProduct(state, id)
    title = product?.name || '产品'
    subtitle = '产品'
    image = product?.coverUrl || fallbackImage
  }
  state.favorites.unshift(makeRecord(`fav_${Date.now()}`, normalizedType === 'home' ? 'homepage' : normalizedType, id, title, subtitle, image, ownerUid))
}

const addVisit = (state: MockState, type: string, id: string) => {
  const normalizedType = normalizeType(type)
  let title = state.user.company_name
  let subtitle = '商户主页'
  let image = state.user.company_logo
  if (normalizedType === 'category') {
    const category = getCategory(state, id)
    title = category?.name || '分类'
    subtitle = '分类'
    image = category?.coverUrl || fallbackImage
  } else if (normalizedType === 'product') {
    const product = getProduct(state, id)
    title = product?.name || '产品'
    subtitle = '产品'
    image = product?.coverUrl || fallbackImage
  }
  state.visits = state.visits.filter(item => !(normalizeType(item.type) === normalizedType && String(item.target_id) === String(id)))
  state.visits.unshift(makeRecord(`visit_${Date.now()}`, normalizedType === 'home' ? 'homepage' : normalizedType, id, title, subtitle, image, ownerUid))
}

const createImageFromFile = (file: File, productId: string, type: 'colorChart' | 'detailChart') => {
  const url = typeof URL !== 'undefined' ? URL.createObjectURL(file) : fallbackImage
  return {
    id: `img_${Date.now()}_${Math.floor(Math.random() * 10000)}`,
    pid: `img_${Date.now()}_${Math.floor(Math.random() * 10000)}`,
    productId,
    type,
    name: file.name,
    url,
    thumbnailUrl: url,
    sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
    sizeBytes: file.size,
    sortOrder: Date.now(),
    isOriginalLarge: file.size > 3 * 1024 * 1024,
    createdAt: nowText(),
  }
}

const parseFormUpload = (formData: FormData) => {
  const file = (formData.get('files') || formData.get('file')) as File | null
  const fid = String(formData.get('fid') || formData.get('pid') || '')
  const fileType = String(formData.get('file_type') || '1')
  const type = fileType === '2' ? 'detailChart' : 'colorChart'
  return { file, fid, type: type as 'colorChart' | 'detailChart' }
}

const makeCategoryFromBody = (body: Record<string, any>, id: string): MockCategory => ({
  id,
  homeId: ownerUid,
  parentId: body.pid && Number(body.pid) !== 0 ? String(body.pid) : undefined,
  name: body.folder_name || '未命名分类',
  intro: body.folder_desc || '',
  coverUrl: body.new_thumb || fallbackImage,
  productCount: 0,
  childCount: 0,
  visibility: privateTypeToVisibility(body.private_type) as MockCategory['visibility'],
  layout: Number(body.layout_type || body.pic_layout) === 2 ? 'list' : 'grid',
  isTop: Number(body.set_top || 0) === 1,
  updatedAt: nowText(),
  createdAt: nowText(),
})

const makeProductFromBody = (body: Record<string, any>, id: string): MockProduct => ({
  id,
  homeId: ownerUid,
  categoryId: Array.isArray(body.category_ids) && body.category_ids[0] ? String(body.category_ids[0]) : body.fid && Number(body.fid) !== 0 ? String(body.fid) : '',
  ownerUserId: ownerUid,
  name: body.folder_name || '未命名产品',
  intro: body.folder_desc || '',
  coverUrl: body.new_thumb || fallbackImage,
  visibility: privateTypeToVisibility(body.private_type) as MockProduct['visibility'],
  hideDetailImage: Number(body.hide_detail_pictures || 0) === 1,
  isHot: false,
  sortOrder: Date.now(),
  colorChartCount: 0,
  detailChartCount: 0,
  updatedAt: nowText(),
  createdAt: nowText(),
})

const updateImagesForProduct = (state: MockState, productId: string, picIds: string[], detailPicIds: string[]) => {
  const wanted = new Set([...picIds, ...detailPicIds])
  state.images = state.images.filter(image => image.productId !== productId || wanted.has(image.id))
  state.images = state.images.map(image => {
    if (picIds.includes(image.id)) return { ...image, productId, type: 'colorChart' }
    if (detailPicIds.includes(image.id)) return { ...image, productId, type: 'detailChart' }
    return image
  })
}

const handleCreateOrEdit = (state: MockState, body: Record<string, any>, isEdit: boolean) => {
  const folderType = Number(body.folder_type || 2)
  if (folderType === 1) {
    const id = isEdit ? String(body.fid) : `cat_${Date.now()}`
    const next = makeCategoryFromBody(body, id)
    const index = state.categories.findIndex(item => item.id === id)
    if (index >= 0) state.categories[index] = { ...state.categories[index], ...next, createdAt: state.categories[index].createdAt }
    else state.categories.unshift(next)
    saveState(state)
    return toPhpCategory(next)
  }

  const id = isEdit ? String(body.fid) : `prod_${Date.now()}`
  const next = makeProductFromBody(body, id)
  const index = state.products.findIndex(item => item.id === id)
  if (index >= 0) state.products[index] = { ...state.products[index], ...next, createdAt: state.products[index].createdAt }
  else state.products.unshift(next)
  updateImagesForProduct(state, id, (body.pic_ids || []).map(String), (body.detail_pic_ids || []).map(String))
  saveState(state)
  return toPhpProduct(getProduct(state, id) || next, state)
}

export async function mockApiRequest<T = any>(path: string, options: MockRequestOptions = {}): Promise<T> {
  await new Promise(resolve => window.setTimeout(resolve, 120))
  const state = getState()
  const cleanPath = path.replace(/^\/+/, '').split('?')[0]
  const params = options.params || {}
  const body = options.body || {}

  switch (cleanPath) {
    case 'user/login/qrcode':
      return ok({
        qrcode: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent('jfyuntu-mock-login')}`,
        scene: 'mock_scene',
      }) as T

    case 'user/login/oauth_config': {
      const redirect = String(params.redirect || currentOrigin())
      return ok({
        appid: 'mock_appid',
        scope: 'snsapi_login',
        redirect_uri: `${currentOrigin()}/api/user/login/callback`,
        state: btoa(redirect),
        auth_url: redirect,
      }) as T
    }

    case 'user/login/status':
      return ok({ status: 'success', token: MOCK_TOKEN, user: state.user }) as T

    case 'user/show_info':
      return ok(state.user) as T

    case 'user/update_pc_settings':
      Object.assign(state.user, body)
      saveState(state)
      return ok(state.user) as T

    case 'user/home/info':
      return ok({
        user_info: state.user,
        product_count: state.products.length,
        total_album: state.products.length,
        is_collect: state.favorites.some(item => normalizeType(item.type) === 'home') ? 1 : 0,
      }) as T

    case 'user/home/categories': {
      const fid = String(params.fid || params.category_id || '')
      const includeCurrent = Number(params.include_current || 0) === 1
      const current = fid ? getCategory(state, fid) : null
      const rows = state.categories
        .filter(category => (fid ? category.parentId === fid : !category.parentId))
        .map(toPhpCategory)
      const payload: any = listPayload(rows)
      if (includeCurrent && current) payload.folder_info = toPhpCategory(current)
      return ok(payload) as T
    }

    case 'user/home/products': {
      const cateId = String(params.cate_id || params.category_id || '')
      const rows = state.products
        .filter(product => !cateId || product.categoryId === cateId)
        .map(product => toPhpProduct(product, state))
      return ok(listPayload(rows)) as T
    }

    case 'user/home/products/detail': {
      const product = getProduct(state, String(params.product_id || params.productId || body.fid || body.product_id))
      if (!product) throw new Error('产品不存在')
      return ok({ folder_info: toPhpProduct(product, state) }) as T
    }

    case 'user/home/share_link': {
      const uid = String(params.target_user_id || ownerUid)
      const code = String(params.code || params.share_code || state.user.home_share_code || state.user.share_code || `HFY${ownerUid}`)
      const mobileLink = `https://wxmpurl.cn/${encodeURIComponent(uid).slice(0, 8)}`
      return ok({
        share_link: mobileLink,
        link: mobileLink,
        url_link: mobileLink,
        pc_link: `${currentOrigin()}/share-home.html?code=${encodeURIComponent(code)}`,
        web_link: `${currentOrigin()}/share-home.html?code=${encodeURIComponent(code)}`,
        share_code: code,
        code,
        invite_code: state.user.invite_code,
        mini_path: `/pages/index/index?uid=${encodeURIComponent(uid)}`,
      }) as T
    }

    case 'user/home/minicode': {
      const uid = String(params.target_user_id || ownerUid)
      const type = String(params.type || 'home')
      const id = String(params.id || '')
      const miniPath =
        type === 'product'
          ? `/pages/product/detail?uid=${uid}&productId=${id}`
          : type === 'category'
            ? `/pages/category/index?uid=${uid}&categoryId=${id}`
            : `/pages/index/index?uid=${uid}`
      return ok({
        qrcode: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(miniPath)}`,
        qrcode_url: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(miniPath)}`,
        mini_path: miniPath,
      }) as T
    }

    case 'album/lists/folder': {
      const folderType = Number(body.folder_type || params.folder_type || 2)
      const rows = folderType === 1
        ? state.categories.map(toPhpCategory)
        : state.products.map(product => toPhpProduct(product, state))
      return ok(listPayload(rows)) as T
    }

    case 'album/products/detail': {
      const product = getProduct(state, String(body.fid || params.fid || body.product_id))
      if (!product) throw new Error('产品不存在')
      return ok({ folder_info: toPhpProduct(product, state) }) as T
    }

    case 'album/create/folder':
      return ok(handleCreateOrEdit(state, body, false)) as T

    case 'album/edit/folder':
      return ok(handleCreateOrEdit(state, body, true)) as T

    case 'album/product/update_status':
      return ok({ success: true }) as T

    case 'album/delete/folder': {
      const id = String(body.fid || params.fid)
      const product = getProduct(state, id)
      const category = getCategory(state, id)
      if (product) {
        state.trash.unshift({
          id: `trash_${Date.now()}`,
          itemType: 'product',
          sourceId: product.id,
          name: product.name,
          coverUrl: product.coverUrl,
          deletedAt: nowText(),
          canRestore: true,
        })
        state.products = state.products.filter(item => item.id !== id)
      }
      if (category) {
        state.trash.unshift({
          id: `trash_${Date.now()}`,
          itemType: 'category',
          sourceId: category.id,
          name: category.name,
          coverUrl: category.coverUrl,
          deletedAt: nowText(),
          canRestore: true,
        })
        state.categories = state.categories.filter(item => item.id !== id)
      }
      saveState(state)
      return ok({ success: true }) as T
    }

    case 'album/batch_link':
      return ok(batchPayload(state, String(params.fid || body.fid))) as T

    case 'album/reset_batch_link': {
      const fid = String(body.fid || params.fid)
      state.batchLinks[fid] = {
        ...ensureBatchLink(state, fid),
        code: randomCode(),
      }
      saveState(state)
      return ok(batchPayload(state, fid)) as T
    }

    case 'album/batch_upload_password': {
      const fid = String(body.fid)
      state.batchLinks[fid] = {
        ...ensureBatchLink(state, fid),
        upload_enabled: Number(body.upload_enabled || 0),
        upload_pwd: body.upload_pwd || '',
        upload_pwd_expire_time: Number(body.upload_pwd_expire_time || 0),
      }
      saveState(state)
      return ok(batchPayload(state, fid)) as T
    }

    case 'web/upload': {
      const info = uploadInfoByCode(state, String(params.code || body.code))
      if (!info) throw new Error('链接无效或已失效')
      return ok(info) as T
    }

    case 'web/token/upload': {
      const info = uploadInfoByCode(state, String(body.code || params.code))
      if (!info) throw new Error('链接无效或已失效')
      if (Number(info.upload_enabled || 0) !== 1) throw new Error('此产品协同编辑入口已关闭')
      if (info.upload_pwd && String(body.password || '') !== String(info.upload_pwd)) throw new Error('访问密码错误')
      return ok({ token: `mock_upload_${info.fid}_${Date.now()}` }) as T
    }

    case 'user/add/collect':
      addOrRemoveFavorite(state, body.type, String(body.id), true)
      saveState(state)
      return ok({ success: true }) as T

    case 'user/cancel/collect':
      addOrRemoveFavorite(state, body.type, String(body.id), false)
      saveState(state)
      return ok({ success: true }) as T

    case 'user/add/visit':
      addVisit(state, body.type, String(body.id))
      saveState(state)
      return ok({ success: true }) as T

    case 'user/collect/records':
      return ok(listPayload(filterRecords(state.favorites, String(params.type || 'all'), String(params.key || '')))) as T

    case 'user/visit/records':
      return ok(listPayload(filterRecords(state.visits, String(params.type || 'all'), String(params.key || '')))) as T

    case 'user/del/visit': {
      const ids = String(body.visit_ids || '').split(',').filter(Boolean)
      state.visits = state.visits.filter(item => !ids.includes(String(item.id)))
      saveState(state)
      return ok({ success: true }) as T
    }

    case 'web_payment/subscription/plans':
      return ok(listPayload(state.plans)) as T

    case 'web_payment/orders':
      return ok(listPayload(state.orders)) as T

    case 'web_payment/membership/order/create': {
      const planId = String(body.membership_plan_id || body.plan_id || '')
      const plan = state.plans.find(item => String(item.id) === planId)
      const orderNo = `MOCK${Date.now()}`
      const amount = String(plan?.price || '0').replace(/[^\d.]/g, '') || '0.00'
      const paymentUrl = `weixin://wxpay/bizpayurl?pr=${orderNo}`
      const order = {
        id: `order_${Date.now()}`,
        packageId: planId,
        plan_id: planId,
        membership_plan_id: planId,
        orderNo,
        order_no: orderNo,
        amount,
        amount_cents: Math.round(Number(amount) * 100),
        status: 'pending',
        create_time: nowText(),
        createdAt: nowText(),
        updatedAt: nowText(),
        payment_url: paymentUrl,
        code_url: paymentUrl,
        qr_image: `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(paymentUrl)}`,
      }
      state.orders.unshift(order)
      saveState(state)
      return ok(order) as T
    }

    case 'web_payment/order/status':
      return ok({ status: 'pending' }) as T

    case 'album/ai/resources':
      return ok(listPayload(state.resources)) as T

    case 'album/ai/import_resource':
      return ok({ success: true }) as T

    case 'user/recycle/list':
      return ok(listPayload(state.trash.map((item: any) => ({
        id: item.sourceId || item.id,
        fid: item.sourceId || item.id,
        folder_type: item.itemType === 'category' ? 1 : 2,
        folder_name: item.name,
        name: item.name,
        imgurl: item.coverUrl,
        new_thumb: item.coverUrl,
        delete_time: item.deletedAt,
      })))) as T

    case 'user/restore/product':
      state.trash = state.trash.filter((item: any) => String(item.sourceId || item.id) !== String(body.product_ids))
      saveState(state)
      return ok({ success: true }) as T

    case 'user/destroy/product':
      state.trash = state.trash.filter((item: any) => String(item.sourceId || item.id) !== String(body.product_ids))
      saveState(state)
      return ok({ success: true }) as T

    default:
      return ok({ success: true, path: cleanPath, params, body }) as T
  }
}

export async function mockApiUpload<T = any>(path: string, formData: FormData): Promise<T> {
  await new Promise(resolve => window.setTimeout(resolve, 180))
  const state = getState()
  const cleanPath = path.replace(/^\/+/, '').split('?')[0]
  const { file, fid, type } = parseFormUpload(formData)
  if (!file || !fid) throw new Error('上传参数不完整')

  if (cleanPath === 'album/upload/folder' || cleanPath === 'web/folder/pic/upload') {
    const image = createImageFromFile(file, fid, type)
    state.images.push(image)
    saveState(state)
    return ok({ data: [toPhpImage(image)], lists: [toPhpImage(image)] }) as T
  }

  return ok({ data: [] }) as T
}
