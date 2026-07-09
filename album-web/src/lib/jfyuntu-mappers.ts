import type { HomeProfileData } from '@/data/HomeProfileData'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

const fallbackImage = 'https://api.jfyuntu.com/image/static/footer/jfyuntu.png'

export const toArray = (value: any): any[] => {
  if (!value) return []
  if (Array.isArray(value)) return value
  if (Array.isArray(value.data)) return value.data
  if (Array.isArray(value.list)) return value.list
  if (Array.isArray(value?.list?.data)) return value.list.data
  if (Array.isArray(value.lists)) return value.lists
  if (Array.isArray(value?.lists?.data)) return value.lists.data
  if (Array.isArray(value.plans)) return value.plans
  if (Array.isArray(value.resources)) return value.resources
  return []
}

export const unwrapList = (value: any): any[] => {
  if (Array.isArray(value)) return value
  if (Array.isArray(value?.data)) return value.data
  if (Array.isArray(value?.list)) return value.list
  if (Array.isArray(value?.list?.data)) return value.list.data
  if (Array.isArray(value?.lists?.data)) return value.lists.data
  if (Array.isArray(value?.lists)) return value.lists
  if (Array.isArray(value?.plans)) return value.plans
  if (Array.isArray(value?.categories)) return value.categories
  if (Array.isArray(value?.products)) return value.products
  if (Array.isArray(value?.resources)) return value.resources
  return []
}

export const pickImage = (...values: any[]) => {
  for (const value of values) {
    if (typeof value === 'string' && value.trim()) return value
    if (value?.url) return value.url
    if (value?.file_url) return value.file_url
    if (value?.fileUrl) return value.fileUrl
    if (value?.preview_url) return value.preview_url
    if (value?.previewUrl) return value.previewUrl
    if (value?.thumbnail_url) return value.thumbnail_url
    if (value?.thumbnailUrl) return value.thumbnailUrl
    if (value?.imgurl) return value.imgurl
    if (value?.picture_url) return value.picture_url
    if (value?.picture_url_original) return value.picture_url_original
    if (value?.src) return value.src
    if (value?.picture) {
      const nested = pickImage(value.picture)
      if (nested) return nested
    }
  }
  return ''
}

const countImages = (value: any) => {
  if (Array.isArray(value)) return value.length
  if (typeof value === 'string' && value.trim()) {
    return value.split(',').map(item => item.trim()).filter(Boolean).length
  }
  return 0
}

const splitStringList = (value: any) => {
  if (Array.isArray(value)) return value.map(item => String(item)).filter(Boolean)
  if (typeof value === 'string' && value.trim()) {
    return value.split(',').map(item => item.trim()).filter(Boolean)
  }
  return []
}

const formatDateText = (value: any) => {
  if (!value) return ''
  if (typeof value === 'string' && /[年/-]/.test(value)) return value
  const numberValue = Number(value)
  if (!Number.isFinite(numberValue) || numberValue <= 0) return String(value)
  const timestamp = numberValue > 10_000_000_000 ? numberValue : numberValue * 1000
  const date = new Date(timestamp)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

export const mapHomeProfile = (raw: any): HomeProfileData => {
  const info = raw?.user_info || raw?.info || raw || {}
  const userId = String(
    info.id || info.uid || info.user_id || raw?.id || raw?.uid || raw?.user_id || raw?.target_user_id || ''
  )
  const companyName =
    info.company_name || info.shop_name || info.merchant_name || info.nickname || info.nick_name || '商户主页'
  const region = [info.address_province, info.address_city, info.address_district].filter(Boolean).join(' / ')
  return {
    id: userId || 'home',
    companyName,
    logoUrl: pickImage(info.company_logo, info.avatar, info.logo, raw?.avatar) || fallbackImage,
    industryTag: info.industry_name || info.industry || '家纺',
    intro: info.company_desc || info.user_desc || info.desc || '暂无简介',
    productCount: Number(info.product_count || raw?.product_count || raw?.total_album || 0),
    contactServiceName: info.home_service_name || '服务',
    contactPhone: info.contact_mobile || info.mobile || '',
    wechatId: info.contact_wechat || info.wechat || '',
    region,
    address: info.address_detail || info.address || '',
    isPublic: Number(info.is_show_home ?? 1) === 1,
    allowSavePic: Number(info.visit_allow_save_pic ?? info.allowSavePic ?? 1) === 1,
    shareTitle: info.home_share_title || `${companyName}的产品主页`,
    shareDescription: info.home_share_desc || info.company_desc || info.user_desc || '',
    shareCoverUrl: pickImage(info.home_share_image, info.company_logo, info.avatar),
    shareCode: String(
      info.share_code ||
        info.shareCode ||
        info.home_share_code ||
        info.homeShareCode ||
        raw?.share_code ||
        raw?.home_share_code ||
        ''
    ),
    ownerUserId: userId,
    createdAt: info.create_time || '',
    updatedAt: info.update_time || '',
  }
}

export const mapCategory = (raw: any, homeId = ''): CategoryData => {
  const children = toArray(raw.children || raw.child || raw.son || raw.sub_categories)
  return {
    id: String(raw.id || raw.fid || ''),
    homeId: String(homeId || raw.uid || raw.home_id || ''),
    parentId: raw.pid ? String(raw.pid) : undefined,
    name: raw.folder_name || raw.name || '未命名分类',
    intro: raw.folder_desc || raw.desc || '',
    coverUrl: pickImage(raw.new_thumb, raw.cover, raw.picture_url) || fallbackImage,
    productCount: Number(raw.product_count || raw.products_count || raw.total_album || raw.son_product_count || 0),
    childCount: Number(raw.child_count || raw.son_count || children.length || 0),
    visibility: Number(raw.private_type) === 2 ? 'private' : Number(raw.private_type) === 4 ? 'shared' : 'public',
    layout: Number(raw.layout_type || raw.pic_layout) === 2 ? 'list' : 'grid',
    isTop: Number(raw.set_top || 0) === 1,
    children: children.map(item => mapCategory(item, homeId || raw.uid || raw.home_id || '')),
    updatedAt: formatDateText(raw.update_time || raw.updated_at || ''),
    createdAt: formatDateText(raw.create_time || raw.created_at || ''),
  }
}

export const mapProduct = (raw: any, homeId = ''): ProductData => {
  const colorImages = raw.pic_ids_arr || raw.pic_list || raw.color_images || raw.pictures || []
  const detailImages = raw.detail_pic_ids_arr || raw.detail_pic_list || raw.detail_pictures || []
  const categoryIds = splitStringList(raw.category_ids || raw.categoryIds || raw.category_id || raw.pid)
  const categoryNames = splitStringList(raw.category_names || raw.categoryNames || raw.category_name || raw.categoryName)
  return {
    id: String(raw.id || raw.fid || raw.product_id || ''),
    homeId: String(homeId || raw.uid || raw.home_id || ''),
    categoryId: categoryIds[0] || '',
    categoryIds,
    categoryName: categoryNames[0] || '',
    categoryNames,
    ownerUserId: String(raw.uid || raw.owner_uid || raw.ownerUserId || ''),
    name: raw.folder_name || raw.name || '未命名产品',
    intro: raw.folder_desc || raw.desc || raw.intro || '',
    coverUrl: pickImage(raw.new_thumb, raw.picture_url, colorImages?.[0]) || fallbackImage,
    visibility: Number(raw.private_type) === 2 ? 'private' : Number(raw.private_type) === 4 ? 'shared' : 'public',
    hideDetailImage: Number(raw.hide_detail_pictures || raw.hideDetailImage || 0) === 1,
    isHot: Number(raw.is_hot || 0) === 1,
    sortOrder: Number(raw.sort || raw.sortOrder || 0),
    colorChartCount: Number(raw.color_chart_count || raw.pic_count || 0) || countImages(colorImages || raw.pic_ids),
    detailChartCount: Number(raw.detail_chart_count || raw.detail_pic_count || 0) || countImages(detailImages || raw.detail_pic_ids),
    updatedAt: formatDateText(raw.update_time || raw.updated_at || ''),
    createdAt: formatDateText(raw.create_time || raw.created_at || ''),
  }
}

const mapImageItem = (raw: any, productId: string, type: 'colorChart' | 'detailChart', index: number): ProductImageData => {
  const source = raw?.picture || raw || {}
  const url = pickImage(raw?.picture_url, raw?.imgurl, raw?.picture_url_original, raw?.url, raw?.src, source, raw)
  const id = String(raw?.pic_id || source?.id || raw?.id || `${productId}_${type}_${index}`)
  return {
    id,
    productId,
    type,
    name: raw?.pic_name || source?.pic_name || raw?.name || source?.name || raw?.file_name || `${type === 'colorChart' ? '花色图' : '详情图'} ${index + 1}`,
    url,
    thumbnailUrl: pickImage(raw?.thumbnailUrl, raw?.thumb, raw?.picture_url, raw?.url, source, raw) || url,
    sizeLabel: raw?.sizeLabel || raw?.size_label || source?.sizeLabel || source?.size_label || '',
    sizeBytes: Number(raw?.sizeBytes || raw?.size || source?.sizeBytes || source?.size || 0),
    sortOrder: Number(raw?.sort || raw?.sortOrder || index),
    isOriginalLarge: Number(raw?.size || raw?.sizeBytes || source?.size || source?.sizeBytes || 0) > 3 * 1024 * 1024,
    createdAt: raw?.create_time || raw?.createdAt || source?.create_time || source?.createdAt || '',
  }
}

export const mapProductImagesFromDetail = (raw: any, productId: string) => {
  const rawPictures = toArray(raw.pictures)
  const getFileType = (item: any) => Number(item?.file_type ?? item?.fileType ?? item?.picture?.file_type ?? item?.picture?.fileType ?? 1)
  const typedColor = rawPictures.filter(item => getFileType(item) === 1)
  const typedDetail = rawPictures.filter(item => getFileType(item) === 2)
  const color = toArray(raw.pic_ids_arr || raw.pic_list || raw.color_images || raw.color_pictures || raw.colorCharts || typedColor)
  const detail = toArray(raw.detail_pic_ids_arr || raw.detail_pic_list || raw.detail_pictures || raw.detail_images || raw.detailCharts || typedDetail)
  return [
    ...color.map((item, index) => mapImageItem(item, productId, 'colorChart', index)),
    ...detail.map((item, index) => mapImageItem(item, productId, 'detailChart', index)),
  ]
}

export type PcTargetType = 'home' | 'category' | 'product'

export interface PcRecordItem {
  id: string
  targetType: PcTargetType
  targetId: string
  targetUserId: string
  targetShareCode?: string
  title: string
  subtitle: string
  coverUrl: string
  time: number
  timeText: string
  createdAt: string
  raw: any
}

export const normalizeTargetType = (value: any): PcTargetType => {
  const type = String(value || '').toLowerCase()
  if (type === 'homepage' || type === 'home' || type === 'user' || type === 'shop' || type === 'merchant' || type === '主页') return 'home'
  if (type === 'category' || type === 'cate' || type === 'class' || type === 'folder' || type === '分类') return 'category'
  return 'product'
}

const normalizeTimestamp = (value: any) => {
  if (!value) return 0
  if (typeof value === 'number') return value > 10_000_000_000 ? Math.floor(value / 1000) : value
  if (/^\d+$/.test(String(value))) {
    const numberValue = Number(value)
    return numberValue > 10_000_000_000 ? Math.floor(numberValue / 1000) : numberValue
  }
  const parsed = Date.parse(String(value).replace(/-/g, '/'))
  return Number.isNaN(parsed) ? 0 : Math.floor(parsed / 1000)
}

export const formatTimeValue = (value: any) => {
  const timestamp = normalizeTimestamp(value)
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

export const mapPcRecord = (raw: any): PcRecordItem => {
  const targetType = normalizeTargetType(raw.type || raw.targetType || raw.target_type)
  const targetId = String(
    targetType === 'home'
      ? raw.target_id || raw.targetId || raw.target_uid || raw.target_user_id || raw.uid || raw.user_id || raw.id || ''
      : raw.target_id || raw.targetId || raw.fid || raw.product_id || raw.category_id || raw.id || ''
  )
  const targetUserId = String(
    raw.target_uid ||
      raw.targetUserId ||
      raw.target_user_id ||
      raw.owner_uid ||
      raw.uid ||
      (targetType === 'home' ? targetId : '')
  )
  const targetShareCode = String(
    raw.target_share_code || raw.targetShareCode || raw.home_share_code || raw.homeShareCode || raw.share_code || ''
  )
  const time = normalizeTimestamp(raw.time || raw.update_time || raw.create_time || raw.createdAt || raw.viewedAt)
  const title =
    raw.title ||
    raw.folder_name ||
    raw.company_name ||
    raw.shop_name ||
    raw.nickname ||
    raw.nick_name ||
    raw.name ||
    (targetType === 'home' ? '商户主页' : targetType === 'category' ? '分类' : '产品')
  const subtitle =
    raw.source ||
    raw.subtitle ||
    raw.type_name ||
    (targetType === 'home' ? '商户主页' : targetType === 'category' ? '分类' : '产品')

  return {
    id: String(raw.id || `${targetType}_${targetId}_${time || Date.now()}`),
    targetType,
    targetId,
    targetUserId,
    targetShareCode,
    title,
    subtitle,
    coverUrl: pickImage(raw.image, raw.new_thumb, raw.cover, raw.avatar, raw.company_logo, raw.logo, raw.picture_url) || fallbackImage,
    time,
    timeText: raw.time_str || formatTimeValue(time),
    createdAt: time ? new Date(time * 1000).toISOString() : '',
    raw,
  }
}

export const buildPcTargetUrl = (type: PcTargetType, id: string, targetUserId = '', targetShareCode = '') => {
  const params = new URLSearchParams()
  if (targetShareCode) params.set('code', targetShareCode)
  else if (targetUserId) params.set('uid', targetUserId)
  if (type === 'home') return `./share-home.html${params.toString() ? `?${params.toString()}` : ''}`
  if (type === 'category') {
    params.set('categoryId', id)
    return `./category.html?${params.toString()}`
  }
  params.set('productId', id)
  return `./share-home.html?${params.toString()}`
}

export const normalizeHomePayload = (homeRaw: any, categoriesRaw: any, productsRaw: any) => {
  const home = mapHomeProfile(homeRaw)
  const categories = unwrapList(categoriesRaw).map(item => mapCategory(item, home.id))
  const products = unwrapList(productsRaw).map(item => mapProduct(item, home.id))
  home.productCount = Number(home.productCount || products.length)
  return { home, categories, products }
}
