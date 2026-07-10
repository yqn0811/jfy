import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { mapProductImagesFromDetail, pickImage } from '@/lib/jfyuntu-mappers'

const normalizeText = (value: any) => {
  if (value === undefined || value === null) return ''
  const text = String(value).trim()
  return text && text !== 'null' && text !== 'undefined' ? text : ''
}

const isDisplayUrl = (value: any) => {
  const text = normalizeText(value)
  return /^(https?:)?\/\//i.test(text) || /^(data|blob):/i.test(text) || text.startsWith('/')
}

const imageId = (image: any = {}) => String(
  image.id ||
    image.pic_id ||
    image.picId ||
    image.picture_id ||
    image.pictureId ||
    image.picture?.id ||
    image.pic?.id ||
    ''
)

export const buildSelectionProductImageMap = (...sources: any[]) => {
  const map = new Map<string, ProductImageData>()
  sources.forEach(source => {
    if (!source) return
    const productId = String(source.id || source.fid || source.product_id || source.product?.id || source.product_summary?.id || '')
    mapProductImagesFromDetail(source, productId).forEach(image => {
      if (image.id && !map.has(String(image.id))) {
        map.set(String(image.id), image)
      }
    })
  })
  return map
}

export const normalizeSelectionImage = (
  value: any,
  index: number,
  fallbackMap: Map<string, ProductImageData> = new Map()
) => {
  const raw = typeof value === 'string'
    ? { src: value, imgurl: value, name: `花色${index + 1}`, pic_name: `花色${index + 1}` }
    : value || {}
  const id = imageId(raw)
  const fallback = id ? fallbackMap.get(id) : undefined
  const fallbackThumb = fallback ? productImageUrl(fallback, 'thumb') : ''
  const fallbackPreview = fallback ? productImageUrl(fallback, 'preview') : ''
  const fallbackOrigin = fallback ? productImageUrl(fallback, 'origin') : ''
  const rawUrl = pickImage(raw, raw.picture, raw.pic)
  const src = isDisplayUrl(rawUrl) ? rawUrl : fallbackThumb || fallbackPreview || rawUrl
  const name = raw.pic_name || raw.name || raw.title || fallback?.name || `花色${index + 1}`

  return {
    ...raw,
    id: id || fallback?.id || String(index),
    pic_id: raw.pic_id || raw.picId || id || fallback?.id || '',
    src,
    imgurl: src,
    url: isDisplayUrl(raw.url) ? raw.url : fallbackOrigin || src,
    thumbnailUrl: isDisplayUrl(raw.thumbnailUrl) ? raw.thumbnailUrl : fallbackThumb || src,
    picture_url: isDisplayUrl(raw.picture_url) ? raw.picture_url : fallbackPreview || src,
    preview_url: isDisplayUrl(raw.preview_url) ? raw.preview_url : fallbackPreview || src,
    thumbnail_url: isDisplayUrl(raw.thumbnail_url) ? raw.thumbnail_url : fallbackThumb || src,
    picture_url_original: isDisplayUrl(raw.picture_url_original) ? raw.picture_url_original : fallbackOrigin || src,
    file_url: isDisplayUrl(raw.file_url) ? raw.file_url : fallbackOrigin || src,
    imageUrls: raw.imageUrls || fallback?.imageUrls,
    name,
    pic_name: name,
  }
}

export const toSelectionImageList = (
  value: any,
  fallbackMap: Map<string, ProductImageData> = new Map()
) => {
  if (Array.isArray(value)) {
    return value.map((item, index) => normalizeSelectionImage(item, index, fallbackMap)).filter(item => pickImage(item))
  }
  if (typeof value === 'string' && value.trim()) {
    return value
      .split(',')
      .map((src, index) => normalizeSelectionImage(src.trim(), index, fallbackMap))
      .filter(item => pickImage(item))
  }
  return []
}

export const pickSelectionImageList = (
  fallbackMap: Map<string, ProductImageData> = new Map(),
  ...values: any[]
) => {
  for (const value of values) {
    const list = toSelectionImageList(value, fallbackMap)
    if (list.length) return list
  }
  return []
}
