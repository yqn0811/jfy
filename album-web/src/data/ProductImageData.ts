import type { ProductImageType } from './ProductData'

export type ProductImageUrlUsage = 'thumb' | 'preview' | 'edit' | 'origin' | 'download'

export interface ProductImageUrls {
  thumb?: string
  preview?: string
  edit?: string
  origin?: string
  download?: string
}

export interface ProductImageData {
  id: string
  clientId?: string
  productId: string
  type: ProductImageType
  name: string
  imageUrls?: ProductImageUrls
  /**
   * Legacy canonical URL. Prefer productImageUrl(image, usage) in new code.
   */
  url: string
  /**
   * Legacy thumbnail URL. Prefer productImageUrl(image, 'thumb') in new code.
   */
  thumbnailUrl: string
  sizeLabel: string
  sizeBytes: number
  sortOrder: number
  isOriginalLarge: boolean
  createdAt: string
  uploadStatus?: 'uploading' | 'done' | 'error' | 'reused'
  uploadProgress?: number
  uploadError?: string
  fileHash?: string
  resourceId?: string | number
  albumPicId?: string | number
  source?: 'upload' | 'saved' | 'ai_resource'
  pendingDiscard?: boolean
}

const firstUrl = (...values: Array<string | undefined | null>) => {
  for (const value of values) {
    const trimmed = typeof value === 'string' ? value.trim() : ''
    if (trimmed) return trimmed
  }
  return ''
}

const isControlledDownloadUrl = (value: string) => /\/api\/user\/download\/original(?:\?|$)/.test(value)

const firstDisplayUrl = (...values: Array<string | undefined | null>) => {
  for (const value of values) {
    const trimmed = typeof value === 'string' ? value.trim() : ''
    if (trimmed && !isControlledDownloadUrl(trimmed)) return trimmed
  }
  return ''
}

export const buildProductImageUrls = (
  urls: ProductImageUrls = {},
  legacy: Pick<Partial<ProductImageData>, 'url' | 'thumbnailUrl'> = {}
): ProductImageUrls => {
  const origin = firstDisplayUrl(urls.origin, legacy.url)
  const preview = firstDisplayUrl(urls.preview, urls.edit, legacy.url, origin, legacy.thumbnailUrl, urls.thumb)
  const edit = firstDisplayUrl(urls.edit, urls.preview, origin, legacy.url, legacy.thumbnailUrl, urls.thumb)
  const thumb = firstDisplayUrl(urls.thumb, legacy.thumbnailUrl, urls.preview, urls.edit, origin, legacy.url)
  const download = firstUrl(urls.download, origin, legacy.url, edit, preview, thumb)

  return {
    thumb,
    preview,
    edit,
    origin,
    download,
  }
}

export const productImageUrl = (
  image: Pick<Partial<ProductImageData>, 'imageUrls' | 'url' | 'thumbnailUrl'> | null | undefined,
  usage: ProductImageUrlUsage = 'preview'
) => {
  if (!image) return ''
  const urls = buildProductImageUrls(image.imageUrls || {}, {
    url: image.url,
    thumbnailUrl: image.thumbnailUrl,
  })

  switch (usage) {
    case 'thumb':
      return firstDisplayUrl(urls.thumb, urls.preview, urls.edit, urls.origin, urls.download)
    case 'edit':
      return firstDisplayUrl(urls.edit, urls.preview, urls.origin, urls.download, urls.thumb)
    case 'origin':
      return firstDisplayUrl(urls.origin, urls.download, urls.edit, urls.preview, urls.thumb)
    case 'download':
      return firstUrl(urls.download, urls.origin, urls.edit, urls.preview, urls.thumb)
    case 'preview':
    default:
      return firstDisplayUrl(urls.preview, urls.edit, urls.origin, urls.download, urls.thumb)
  }
}
