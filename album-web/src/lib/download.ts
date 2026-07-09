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

export const resolveProductImageDownloadUrl = async (image: ProductImageData) => {
  const downloadEntry = productImageUrl(image, 'download')
  if (!downloadEntry) return ''
  if (!isControlledDownloadUrl(downloadEntry)) return downloadEntry
  const data = await pcApi.getOriginalDownloadUrl(image.id)
  return String(data?.download_url || data?.downloadUrl || data?.url || '')
}

export const downloadImagesAsZip = async (
  images: ProductImageData[],
  filename = 'product-images.zip',
  onProgress?: (completed: number, total: number) => void,
  onImageFetched?: (image: ProductImageData, blob: Blob) => void | Promise<void>
) => {
  if (!images.length) return

  const { default: JSZip } = await import('jszip')
  const zip = new JSZip()
  const usedNames = new Map<string, number>()

  for (let index = 0; index < images.length; index += 1) {
    const image = images[index]
    const imageUrl = await resolveProductImageDownloadUrl(image)
    if (!imageUrl) {
      throw new Error(`图片下载失败: ${image.name || index + 1}`)
    }
    const response = await fetch(imageUrl, { mode: 'cors' })
    if (!response.ok) {
      throw new Error(`图片下载失败: ${image.name || index + 1}`)
    }

    const blob = await response.blob()
    await onImageFetched?.(image, blob)
    const extension = extensionFromUrl(imageUrl)
    const baseName = sanitizeFilename(image.name || `图片-${index + 1}`)
    const usedCount = usedNames.get(baseName) || 0
    usedNames.set(baseName, usedCount + 1)
    const finalName = usedCount > 0 ? `${baseName}-${usedCount + 1}.${extension}` : `${baseName}.${extension}`
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
