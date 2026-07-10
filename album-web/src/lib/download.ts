import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { pcApi } from '@/lib/api'

const sanitizeFilename = (value: string) => {
  const cleaned = value
    .replace(/[\\/:*?"<>|]/g, '_')
    .replace(/\s+/g, ' ')
    .trim()
  return cleaned || 'image'
}

const extensionFromUrl = (url: string) => {
  try {
    const parsed = new URL(url, window.location.href)
    const match = parsed.pathname.match(/\.([a-z0-9]{2,5})$/i)
    return match ? match[1].toLowerCase() : 'jpg'
  } catch {
    const match = url.split('?')[0].match(/\.([a-z0-9]{2,5})$/i)
    return match ? match[1].toLowerCase() : 'jpg'
  }
}

const extensionFromMime = (mime: string) => {
  const normalized = mime.split(';')[0].trim().toLowerCase()
  const map: Record<string, string> = {
    'image/jpeg': 'jpg',
    'image/png': 'png',
    'image/gif': 'gif',
    'image/webp': 'webp',
  }
  return map[normalized] || ''
}

const filenameFromContentDisposition = (value: string | null) => {
  if (!value) return ''
  const utf8Match = value.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    try {
      return decodeURIComponent(utf8Match[1])
    } catch {
      return utf8Match[1]
    }
  }
  const quotedMatch = value.match(/filename="([^"]+)"/i)
  if (quotedMatch?.[1]) return quotedMatch[1]
  const plainMatch = value.match(/filename=([^;]+)/i)
  return plainMatch?.[1]?.trim() || ''
}

export const downloadUrl = (url: string, filename: string) => {
  const link = document.createElement('a')
  link.href = url
  link.download = sanitizeFilename(filename)
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const isControlledDownloadUrl = (url: string) => /\/api\/user\/download\/original(?:\?|$)/.test(url)

const resolveControlledDownloadPicId = (image: ProductImageData, url: string) => {
  try {
    const parsed = new URL(url, window.location.href)
    return parsed.searchParams.get('pic_id') || parsed.searchParams.get('id') || image.id
  } catch {
    return image.id
  }
}

const resolveControlledDownloadIds = (images: ProductImageData[]) => {
  const ids: string[] = []
  for (const image of images) {
    const downloadEntry = productImageUrl(image, 'download')
    if (!downloadEntry || !isControlledDownloadUrl(downloadEntry)) return []
    const picId = resolveControlledDownloadPicId(image, downloadEntry)
    if (!picId || /^.+_(colorChart|detailChart)_\d+$/i.test(picId)) return []
    ids.push(String(picId))
  }
  return ids
}

export const resolveProductImageDownloadUrl = async (image: ProductImageData) => {
  const downloadEntry = productImageUrl(image, 'download')
  if (!downloadEntry) return ''
  if (!isControlledDownloadUrl(downloadEntry)) return downloadEntry
  const data = await pcApi.getOriginalDownloadUrl(image.id)
  return String(data?.download_url || data?.downloadUrl || data?.url || '')
}

const fetchProductImageBlobForZip = async (image: ProductImageData) => {
  const downloadEntry = productImageUrl(image, 'download')
  if (!downloadEntry) {
    throw new Error(`图片下载失败: ${image.name || image.id}`)
  }

  if (isControlledDownloadUrl(downloadEntry)) {
    const response = await pcApi.getOriginalDownloadBlob(resolveControlledDownloadPicId(image, downloadEntry))
    const blob = await response.blob()
    const headerName = filenameFromContentDisposition(response.headers.get('content-disposition'))
    const extension = extensionFromMime(response.headers.get('content-type') || '') || extensionFromUrl(headerName || downloadEntry)
    return { blob, extension, filename: headerName }
  }

  const imageUrl = await resolveProductImageDownloadUrl(image)
  if (!imageUrl) {
    throw new Error(`图片下载失败: ${image.name || image.id}`)
  }
  const response = await fetch(imageUrl, { mode: 'cors' })
  if (!response.ok) {
    throw new Error(`图片下载失败: ${image.name || image.id}`)
  }
  const blob = await response.blob()
  const extension = extensionFromMime(response.headers.get('content-type') || '') || extensionFromUrl(imageUrl)
  return { blob, extension, filename: '' }
}

export const downloadImagesAsZip = async (
  images: ProductImageData[],
  filename = 'product-images.zip',
  onProgress?: (completed: number, total: number) => void,
  onImageFetched?: (image: ProductImageData, blob: Blob) => void | Promise<void>
) => {
  if (!images.length) return

  const controlledIds = resolveControlledDownloadIds(images)
  if (controlledIds.length === images.length) {
    const response = await pcApi.downloadOriginalZip(controlledIds, filename)
    const blob = await response.blob()
    onProgress?.(images.length, images.length)
    const headerName = filenameFromContentDisposition(response.headers.get('content-disposition'))
    const objectUrl = URL.createObjectURL(blob)
    try {
      downloadUrl(objectUrl, headerName || filename)
    } finally {
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000)
    }
    return
  }

  const { default: JSZip } = await import('jszip')
  const zip = new JSZip()
  const usedNames = new Map<string, number>()

  for (let index = 0; index < images.length; index += 1) {
    const image = images[index]
    const { blob, extension, filename: responseFilename } = await fetchProductImageBlobForZip(image)
    await onImageFetched?.(image, blob)
    const responseBaseName = responseFilename ? responseFilename.replace(/\.[a-z0-9]{2,5}$/i, '') : ''
    const baseName = sanitizeFilename(image.name || responseBaseName || `图片-${index + 1}`)
    const usedCount = usedNames.get(baseName) || 0
    usedNames.set(baseName, usedCount + 1)
    const finalExtension = extension || 'jpg'
    const finalName = usedCount > 0 ? `${baseName}-${usedCount + 1}.${finalExtension}` : `${baseName}.${finalExtension}`
    zip.file(finalName, blob)
    onProgress?.(index + 1, images.length)
  }

  const content = await zip.generateAsync({ type: 'blob' })
  const objectUrl = URL.createObjectURL(content)
  try {
    downloadUrl(objectUrl, filename)
  } finally {
    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000)
  }
}
