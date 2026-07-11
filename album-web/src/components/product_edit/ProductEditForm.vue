
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'
import { pcApi } from '@/lib/api'
import { mapCategory, mapProduct, mapProductImagesFromDetail, normalizeProductImageUrls, pickImage, unwrapList } from '@/lib/jfyuntu-mappers'
import type { ProductData, ProductImageType } from '@/data/ProductData'
import type { CategoryData } from '@/data/CategoryData'
import { buildProductImageUrls, type ProductImageData } from '@/data/ProductImageData'
import ImageUploadZone from './ImageUploadZone.vue'
import ImageSortable from './ImageSortable.vue'
import { navigateTo } from '@/navigation'
import QuickImageUploadTile from './QuickImageUploadTile.vue'

interface FormState {
  id: string
  name: string
  intro: string
  categoryIds: string[]
  visibility: 'public' | 'private' | 'shared'
  hideDetailImage: boolean
  colorChartImages: ProductImageData[]
  detailChartImages: ProductImageData[]
}

interface Props {
  productId?: string
  embedded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  productId: '',
  embedded: false,
})

const emit = defineEmits<{
  (e: 'cancel'): void
  (e: 'saved', productId: string): void
  (e: 'preview', productId: string): void
}>()

const isClient = ref(true)
const isLoading = ref(false)
const isSaving = ref(false)
const isResourceLoading = ref(false)
const resourceDialogOpen = ref(false)
const resourceTargetType = ref<ProductImageType>('colorChart')
const resourceKeyword = ref('')
const resourceList = ref<any[]>([])
const selectedResourceIds = ref<Set<string>>(new Set())
const brokenResourceIds = ref<Set<string>>(new Set())
const productId = ref<string | null>(null)
const categories = ref<CategoryData[]>([])

const initialFormState: FormState = {
  id: '',
  name: '',
  intro: '',
  categoryIds: [],
  visibility: 'public',
  hideDetailImage: false,
  colorChartImages: [],
  detailChartImages: [],
}

const formState = ref<FormState>({ ...initialFormState })

const isEditMode = computed(() => !!productId.value && productId.value !== '')

const hasChanges = computed(() => {
  return (
    formState.value.name !== initialFormState.name ||
    formState.value.intro !== initialFormState.intro ||
    formState.value.categoryIds.join(',') !== initialFormState.categoryIds.join(',') ||
    formState.value.visibility !== initialFormState.visibility ||
    formState.value.hideDetailImage !== initialFormState.hideDetailImage ||
    formState.value.colorChartImages.length !== initialFormState.colorChartImages.length ||
    formState.value.detailChartImages.length !== initialFormState.detailChartImages.length
  )
})

const isFormValid = computed(() => {
  return formState.value.name.trim().length > 0
})

const visibilityOptions = [
  { value: 'public' as const, label: '公开' },
  { value: 'private' as const, label: '私密' },
  { value: 'shared' as const, label: '分享可见' },
]

const isCategorySelected = (categoryId: string) => formState.value.categoryIds.includes(categoryId)

const imageListForType = (type: ProductImageType) =>
  type === 'colorChart' ? formState.value.colorChartImages : formState.value.detailChartImages

const setImageListForType = (type: ProductImageType, images: ProductImageData[]) => {
  if (type === 'colorChart') {
    formState.value.colorChartImages = images
  } else {
    formState.value.detailChartImages = images
  }
}

const isPersistedImage = (image: ProductImageData) =>
  !String(image.id || '').startsWith('upload_') &&
  !String(image.id || '').startsWith('quick_upload_') &&
  image.uploadStatus !== 'uploading' &&
  image.uploadStatus !== 'error'

const imageIdsForSave = (images: ProductImageData[]) =>
  images.filter(isPersistedImage).map(item => item.id)

const originalSavedImageIds = new Set<string>()
const pendingUploadedImageIds = new Set<string>()

const markSavedSnapshot = () => {
  originalSavedImageIds.clear()
  ;[...formState.value.colorChartImages, ...formState.value.detailChartImages]
    .filter(isPersistedImage)
    .forEach(image => originalSavedImageIds.add(String(image.id)))
}

const isNewUploadImage = (image: ProductImageData) => {
  const id = String(image.id || '')
  return Boolean(id) && image.source === 'upload' && pendingUploadedImageIds.has(id) && !originalSavedImageIds.has(id)
}

const fileToSha256 = async (file: File) => {
  if (!crypto?.subtle) return ''
  const buffer = await file.arrayBuffer()
  const hash = await crypto.subtle.digest('SHA-256', buffer)
  return Array.from(new Uint8Array(hash)).map(byte => byte.toString(16).padStart(2, '0')).join('')
}

const toggleCategory = (categoryId: string) => {
  if (isCategorySelected(categoryId)) {
    formState.value.categoryIds = formState.value.categoryIds.filter(id => id !== categoryId)
    return
  }
  formState.value.categoryIds = [...formState.value.categoryIds, categoryId]
}

const setVisibility = (value: FormState['visibility']) => {
  formState.value.visibility = value
}

const mapResourceToImage = (item: any, type: ProductImageType, productIdValue: string): ProductImageData => {
  const imageUrls = normalizeProductImageUrls(item)
  const url = pickImage(imageUrls.origin, imageUrls.edit, imageUrls.preview, imageUrls.thumb, item.original_url, item.file_url, item.fileUrl, item.picture_url_original, item.url, item, imageUrls.download)
  const thumbnailUrl = pickImage(imageUrls.thumb, item.thumbnail_url, item.thumbnailUrl, item.preview_url, item.previewUrl, item.thumb, item.picture_url, item.imgurl, url)
  return {
    id: String(item.pid || item.pic_id || item.id || item.local_pic_id || item.picture_id || item.resource_id || `resource_${Date.now()}`),
    productId: productIdValue,
    type,
    name: item.name || item.pic_name || item.file_name || `${type === 'colorChart' ? '花色图' : '详情图'}`,
    imageUrls: buildProductImageUrls(imageUrls, { url, thumbnailUrl }),
    url,
    thumbnailUrl,
    sizeLabel: item.sizeLabel || item.size_label || (item.size ? `${(Number(item.size) / 1024 / 1024).toFixed(1)} MB` : ''),
    sizeBytes: Number(item.sizeBytes || item.size || 0),
    sortOrder: type === 'colorChart' ? formState.value.colorChartImages.length : formState.value.detailChartImages.length,
    isOriginalLarge: Number(item.sizeBytes || item.size || 0) > 3 * 1024 * 1024,
    createdAt: item.create_time || item.created_at || new Date().toLocaleString('zh-CN'),
    uploadStatus: 'reused',
    resourceId: item.resource_id || item.id,
    fileHash: item.file_hash || item.content_hash || item.source_hash || item?.metadata?.file_hash || '',
  }
}

const normalizeResourceList = (raw: any) => {
  return unwrapList(raw).map((item: any) => {
    const url = pickImage(item.file_url, item.fileUrl, item.preview_url, item.previewUrl, item.thumbnail_url, item.thumbnailUrl, item.picture_url_original, item.url, item)
    const thumbnailUrl = pickImage(item.thumbnail_url, item.thumbnailUrl, item.preview_url, item.previewUrl, item.thumb, item.picture_url, item.imgurl, url)
    return {
      ...item,
      id: String(item.id || item.resource_id || item.pic_id || ''),
      name: item.name || item.pic_name || item.file_name || '未命名图片',
      url,
      thumbnailUrl,
      sizeLabel: item.sizeLabel || item.size_label || (item.size ? `${(Number(item.size) / 1024 / 1024).toFixed(1)} MB` : ''),
    }
  })
}

const loadResources = async () => {
  isResourceLoading.value = true
  brokenResourceIds.value = new Set()
  try {
    const raw = await pcApi.getAiResources({
      page_size: 60,
      keyword: resourceKeyword.value.trim(),
    })
    resourceList.value = normalizeResourceList(raw)
  } catch (error: any) {
    toast.error(error?.message || '资源库加载失败')
  } finally {
    isResourceLoading.value = false
  }
}

const openResourcePicker = async (type: ProductImageType) => {
  resourceTargetType.value = type
  selectedResourceIds.value = new Set()
  brokenResourceIds.value = new Set()
  resourceDialogOpen.value = true
  await loadResources()
}

const markResourceBroken = (id: string) => {
  brokenResourceIds.value = new Set([...brokenResourceIds.value, id])
}

const toggleResource = (id: string) => {
  const next = new Set(selectedResourceIds.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  selectedResourceIds.value = next
}

const handleImportResources = async () => {
  if (selectedResourceIds.value.size === 0) {
    toast.error('请至少选择一张图片')
    return
  }

  isResourceLoading.value = true
  try {
    const type = resourceTargetType.value
    const role = type === 'detailChart' ? 'detail' : 'cover'
    const selected = resourceList.value.filter(item => selectedResourceIds.value.has(item.id))
    const imported: ProductImageData[] = []

    for (const item of selected) {
      const data = await pcApi.importAiResource(item.id, role)
      const rows = unwrapList(data)
      const importedItem = rows[0] || (data && typeof data === 'object' ? data : null) || item
      imported.push({
        ...mapResourceToImage(importedItem, type, productId.value || formState.value.id || ''),
        source: 'ai_resource',
      })
    }

    handleAddImages(imported, type)
    resourceDialogOpen.value = false
    toast.success(`已导入 ${imported.length} 张图片`)
  } catch (error: any) {
    toast.error(error?.message || '导入失败')
  } finally {
    isResourceLoading.value = false
  }
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })

  const params = new URLSearchParams(window.location.search)
  const id = props.productId || params.get('productId')

  loadCategories()
  if (id) {
    productId.value = id
    loadProductData(id)
  }
})

const loadCategories = async () => {
  try {
    const raw = await pcApi.getManagementCategories({ page: 1, limit: 500 })
    categories.value = unwrapList(raw).map(item => mapCategory(item))
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

const loadProductData = async (id: string) => {
  isLoading.value = true
  try {
    const raw = await pcApi.getProductEditDetail(id)
    const rawProduct = raw?.folder_info || raw?.product || raw
    const product = mapProduct(rawProduct)
    const images = mapProductImagesFromDetail(rawProduct, product.id)
    const colorImages = images.filter(item => item.type === 'colorChart')
    const detailImages = images.filter(item => item.type === 'detailChart')

    formState.value = {
      id: product.id,
      name: product.name,
      intro: product.intro,
      categoryIds: product.categoryIds?.length ? product.categoryIds : product.categoryId ? [product.categoryId] : [],
      visibility: product.visibility,
      hideDetailImage: product.hideDetailImage,
      colorChartImages: colorImages,
      detailChartImages: detailImages,
    }

    Object.assign(initialFormState, formState.value)
    markSavedSnapshot()
  } catch (error) {
    console.error('Failed to load product:', error)
    toast.error('加载产品失败，请重试')
  } finally {
    isLoading.value = false
  }
}

const handleCancel = () => {
  if (hasChanges.value) {
    const confirmed = window.confirm('您有未保存的更改，确定要放弃吗？')
    if (!confirmed) return
  }
  const discardIds = [...pendingUploadedImageIds].filter(id => !originalSavedImageIds.has(id))
  pendingUploadedImageIds.clear()
  discardIds.forEach((id) => {
    pcApi.discardUploadedPicture(id).catch((error) => {
      console.warn('Failed to discard uploaded image:', error)
    })
  })
  if (props.embedded) {
    emit('cancel')
    return
  }
  navigateTo('./product-management')
}

const buildSavePayload = () => ({
  fid: formState.value.id,
  folder_name: formState.value.name,
  folder_desc: formState.value.intro,
  category_ids: formState.value.categoryIds,
  private_type: formState.value.visibility === 'private' ? 2 : formState.value.visibility === 'shared' ? 4 : 1,
  hide_detail_pictures: formState.value.hideDetailImage ? 1 : 0,
  pic_ids: imageIdsForSave(formState.value.colorChartImages),
  detail_pic_ids: imageIdsForSave(formState.value.detailChartImages),
  new_thumb: formState.value.colorChartImages.find(isPersistedImage)?.url || '',
})

const handleSave = async () => {
  if (!isFormValid.value) {
    toast.error('请填写产品名称')
    return
  }

  isSaving.value = true
  try {
    if (isEditMode.value) {
      await pcApi.editProductOrCategory(buildSavePayload())
    } else {
      const created = await pcApi.createProductOrCategory({
        ...buildSavePayload(),
        fid: formState.value.categoryIds[0] || 0,
        folder_type: 2,
        allow_draft: 1,
      })
      const newId = String(created?.id || created?.fid || created?.folder_id || created?.data?.id || '')
      if (newId) {
        productId.value = newId
        formState.value.id = newId
      }
    }

    toast.success('设置已保存')
    Object.assign(initialFormState, {
      ...formState.value,
      colorChartImages: formState.value.colorChartImages.map(image => ({ ...image, source: 'saved' as const, pendingDiscard: false })),
      detailChartImages: formState.value.detailChartImages.map(image => ({ ...image, source: 'saved' as const, pendingDiscard: false })),
    })
    formState.value.colorChartImages = initialFormState.colorChartImages
    formState.value.detailChartImages = initialFormState.detailChartImages
    pendingUploadedImageIds.clear()
    markSavedSnapshot()
    if (props.embedded) {
      emit('saved', formState.value.id || productId.value || '')
    } else {
      navigateTo('./product-management')
    }
  } catch (error) {
    console.error('Failed to save product:', error)
    toast.error('保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

const handleAddImages = (images: ProductImageData[], type: ProductImageType) => {
  const existingKeys = new Set(
    imageListForType(type)
      .map(item => item.fileHash ? `hash:${item.fileHash}` : `id:${item.id}`)
      .filter(Boolean)
  )
  const nextImages = images.filter((image) => {
    const key = image.fileHash ? `hash:${image.fileHash}` : `id:${image.id}`
    if (existingKeys.has(key)) return false
    existingKeys.add(key)
    return true
  })
  setImageListForType(type, [...imageListForType(type), ...nextImages])
}

const handleUpdateImage = (clientId: string, image: ProductImageData, type: ProductImageType) => {
  const list = imageListForType(type)
  const index = list.findIndex(item => item.clientId === clientId || item.id === clientId)
  if (index < 0) return
  const next = [...list]
  const duplicateIndex =
    image.fileHash && image.uploadStatus !== 'error'
      ? next.findIndex((item, itemIndex) => itemIndex !== index && item.fileHash === image.fileHash && isPersistedImage(item))
      : -1
  if (duplicateIndex >= 0) {
    next.splice(index, 1)
    setImageListForType(type, next.map((item, sortOrder) => ({ ...item, sortOrder })))
    toast.info('重复图片已使用已有资源')
    return
  }
  next[index] = { ...image, sortOrder: index }
  setImageListForType(type, next)
}

const handleRemoveImage = (id: string, type: ProductImageType) => {
  const removed = imageListForType(type).find((img) => img.id === id || img.clientId === id)
  setImageListForType(type, imageListForType(type).filter((img) => img.id !== id && img.clientId !== id))
  if (removed && isNewUploadImage(removed)) {
    pendingUploadedImageIds.delete(String(removed.id))
    pcApi.discardUploadedPicture(String(removed.id)).catch((error) => {
      console.warn('Failed to discard uploaded image:', error)
    })
  } else if (removed?.albumPicId) {
    pcApi.discardUploadedPicture({ album_pic_id: removed.albumPicId }).catch((error) => {
      console.warn('Failed to discard album image relation:', error)
    })
  }
  toast.success('已删除')
}

const handleReorderImages = (images: ProductImageData[], type: ProductImageType) => {
  setImageListForType(type, images)
}

const handleUploadImage = async (file: File, type: ProductImageType, placeholder: ProductImageData): Promise<ProductImageData> => {
  const fileHash = await fileToSha256(file).catch(() => '')
  if (fileHash) {
    const localDuplicate = imageListForType(type).find(image =>
      image.fileHash === fileHash && isPersistedImage(image)
    )
    if (localDuplicate) {
      return {
        ...localDuplicate,
        clientId: placeholder.clientId,
        sortOrder: placeholder.sortOrder,
        uploadStatus: 'reused',
        uploadProgress: 100,
      }
    }

    const duplicate = await pcApi.findAiResourceDuplicate({
      file_hash: fileHash,
      content_hash: fileHash,
      file_size: file.size || 0,
      name: file.name,
    }).catch(() => null)
    const duplicateResourceId = String(duplicate?.resource_id || duplicate?.id || duplicate?.resource?.id || '')
    if (duplicateResourceId) {
      const role = type === 'detailChart' ? 'detail' : 'cover'
      const imported = await pcApi.importAiResource(duplicateResourceId, role)
      const rows = unwrapList(imported)
      const importedItem = rows[0] || (imported && typeof imported === 'object' ? imported : null) || duplicate
      return {
        ...mapResourceToImage(importedItem, type, productId.value || formState.value.id || ''),
        clientId: placeholder.clientId,
        fileHash,
        uploadStatus: 'reused',
        uploadProgress: 100,
      }
    }
  }

  const item = await pcApi.uploadCommonImage(file, {
    file_type: 1,
    file_hash: fileHash,
    content_hash: fileHash,
  })
  const imageUrls = normalizeProductImageUrls(item)
  const fallbackUrl = URL.createObjectURL(file)
  const url = pickImage(imageUrls.origin, imageUrls.edit, imageUrls.preview, imageUrls.thumb, item.url, fallbackUrl, imageUrls.download)
  const thumbnailUrl = pickImage(imageUrls.thumb, imageUrls.preview, url)
  const id = String(item.pid || item.id || `img_${Date.now()}`)
  if (/^\d+$/.test(id)) {
    pendingUploadedImageIds.add(id)
  }
  return {
    id,
    productId: productId.value || formState.value.id || '',
    type,
    name: file.name,
    imageUrls: buildProductImageUrls(imageUrls, { url, thumbnailUrl }),
    url,
    thumbnailUrl,
    sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
    sizeBytes: file.size,
    sortOrder: type === 'colorChart' ? formState.value.colorChartImages.length : formState.value.detailChartImages.length,
    isOriginalLarge: file.size > 3 * 1024 * 1024,
    createdAt: new Date().toLocaleString('zh-CN'),
    clientId: placeholder.clientId,
    fileHash,
    uploadStatus: 'done',
    uploadProgress: 100,
    source: 'upload',
    pendingDiscard: true,
  }
}
</script>

<template>
  <div v-if="!isClient" class="flex items-center justify-center h-96">
    <div class="text-muted-foreground flex items-center gap-2">
      <SafeIcon name="Loader2" :size="20" class="animate-spin" />
      <span>加载中...</span>
    </div>
  </div>

  <div v-else class="flex flex-col h-full overflow-hidden">
    <!-- Header Section -->
    <div v-if="!props.embedded" class="flex items-center justify-between border-b border-border pb-4 mb-6">
      <div>
        <h1 class="text-page-title">{{ isEditMode ? '编辑产品' : '新建产品' }}</h1>
        <p class="text-caption mt-1">
          {{ isEditMode ? '更新产品信息和图片' : '创建新的产品并上传图片' }}
        </p>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="min-h-0 flex-1 overflow-y-auto px-1">
      <div class="grid min-h-full gap-6 pb-6 lg:grid-cols-[minmax(320px,0.9fr)_minmax(560px,1.1fr)]">
        <!-- Basic Information Section -->
        <section class="space-y-5 lg:border-r lg:border-border lg:pr-8">
          <div class="space-y-2">
            <Label for="product-name" class="text-label text-base">产品名称</Label>
            <Input
              id="product-name"
              v-model="formState.name"
              placeholder="输入产品名称"
              class="h-12 rounded-lg bg-background text-base"
            />
          </div>

          <div class="space-y-2">
            <Label for="product-intro" class="text-label text-base">产品简介</Label>
            <Textarea
              id="product-intro"
              v-model="formState.intro"
              placeholder="输入产品简介"
              class="min-h-[96px] resize-none rounded-lg bg-background text-base"
            />
          </div>

          <div class="space-y-2">
            <Label class="text-label text-base">所属分类（可多选）</Label>
            <div class="min-h-[84px] rounded-lg border border-input bg-background p-3">
              <div v-if="categories.length" class="flex flex-wrap gap-2">
                <button
                  v-for="cat in categories"
                  :key="cat.id"
                  type="button"
                  :class="cn(
                    'inline-flex h-9 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                    isCategorySelected(cat.id)
                      ? 'border-primary bg-primary/10 text-primary'
                      : 'border-border bg-card text-foreground hover:border-primary/40 hover:text-primary'
                  )"
                  @click="toggleCategory(cat.id)"
                >
                  {{ cat.name }}
                  <SafeIcon
                    v-if="isCategorySelected(cat.id)"
                    name="Check"
                    :size="14"
                    class="ml-1.5"
                  />
                </button>
              </div>
              <p v-else class="text-sm text-muted-foreground">暂无分类</p>
            </div>
          </div>

          <div class="space-y-3">
            <Label class="text-label text-base">产品状态</Label>
            <div class="flex flex-wrap gap-5">
              <button
                v-for="option in visibilityOptions"
                :key="option.value"
                type="button"
                class="inline-flex items-center gap-2 text-base text-foreground"
                @click="setVisibility(option.value)"
              >
                <span
                  :class="cn(
                    'flex h-5 w-5 items-center justify-center rounded-full border transition-colors',
                    formState.visibility === option.value ? 'border-primary' : 'border-muted-foreground/60'
                  )"
                >
                  <span
                    v-if="formState.visibility === option.value"
                    class="h-2.5 w-2.5 rounded-full bg-primary"
                  />
                </span>
                {{ option.label }}
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between gap-4 rounded-lg border border-border bg-muted/25 p-4">
            <div class="min-w-0">
              <p class="text-sm font-medium">隐藏详情图</p>
              <p class="mt-1 text-xs text-muted-foreground">开启后，分享出去的链接不展示详情图</p>
            </div>
            <Switch v-model="formState.hideDetailImage" />
          </div>
        </section>

        <!-- Image Sections -->
        <section class="space-y-6">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold">花色图</h2>
              <div class="flex items-center gap-4 text-sm font-medium text-primary">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 hover:text-primary/80"
                  @click="openResourcePicker('colorChart')"
                >
                  <SafeIcon name="Image" :size="18" />
                  我的资源库
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 hover:text-primary/80"
                  @click="toast.info('已上传图片可直接拖拽调整顺序')"
                >
                  <SafeIcon name="ArrowUpDown" :size="18" />
                  排序
                </button>
              </div>
            </div>

            <ImageUploadZone
              type="colorChart"
              :images="formState.colorChartImages"
              :upload-handler="handleUploadImage"
              compact
              class="min-h-[118px]"
              @add-images="handleAddImages"
              @update-image="handleUpdateImage"
            />

            <div class="mt-2">
              <ImageSortable
                v-if="formState.colorChartImages.length > 0"
                :images="formState.colorChartImages"
                type="colorChart"
                @remove="handleRemoveImage"
                @reorder="handleReorderImages"
              >
                <template #after>
                  <QuickImageUploadTile
                    type="colorChart"
                    :upload-handler="handleUploadImage"
                    @add-images="handleAddImages"
                    @update-image="handleUpdateImage"
                  />
                </template>
              </ImageSortable>

              <div v-else>
                <p class="mb-2 text-sm font-medium text-muted-foreground">
                  已上传 0 张花色图（可拖拽排序）
                </p>
                <QuickImageUploadTile
                  type="colorChart"
                  :upload-handler="handleUploadImage"
                  @add-images="handleAddImages"
                  @update-image="handleUpdateImage"
                />
              </div>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold">详情图</h2>
              <div class="flex items-center gap-4 text-sm font-medium text-primary">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 hover:text-primary/80"
                  @click="openResourcePicker('detailChart')"
                >
                  <SafeIcon name="Image" :size="18" />
                  我的资源库
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 hover:text-primary/80"
                  @click="toast.info('已上传图片可直接拖拽调整顺序')"
                >
                  <SafeIcon name="ArrowUpDown" :size="18" />
                  排序
                </button>
              </div>
            </div>

            <ImageUploadZone
              type="detailChart"
              :images="formState.detailChartImages"
              :upload-handler="handleUploadImage"
              compact
              class="min-h-[118px]"
              @add-images="handleAddImages"
              @update-image="handleUpdateImage"
            />

            <div class="mt-2">
              <ImageSortable
                v-if="formState.detailChartImages.length > 0"
                :images="formState.detailChartImages"
                type="detailChart"
                @remove="handleRemoveImage"
                @reorder="handleReorderImages"
              >
                <template #after>
                  <QuickImageUploadTile
                    type="detailChart"
                    :upload-handler="handleUploadImage"
                    @add-images="handleAddImages"
                    @update-image="handleUpdateImage"
                  />
                </template>
              </ImageSortable>

              <div v-else>
                <p class="mb-2 text-sm font-medium text-muted-foreground">
                  已上传 0 张详情图（可拖拽排序）
                </p>
                <QuickImageUploadTile
                  type="detailChart"
                  :upload-handler="handleUploadImage"
                  @add-images="handleAddImages"
                  @update-image="handleUpdateImage"
                />
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Fixed Action Bar -->
    <div class="floating-action-bar">
      <Button
        variant="outline"
        @click="handleCancel"
        :disabled="isSaving"
        class="px-6"
      >
        取消
      </Button>
      <Button
        @click="handleSave"
        :disabled="!isFormValid || isSaving"
        class="px-6"
      >
        <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
        <span v-else>保存产品</span>
      </Button>
    </div>

    <Dialog :open="resourceDialogOpen" @update:open="(val) => (resourceDialogOpen = val)">
      <DialogScrollContent class="max-h-[88vh] max-w-[960px] overflow-hidden p-0">
        <div class="flex max-h-[88vh] min-h-[620px] flex-col">
          <DialogHeader class="border-b border-border px-6 py-5">
            <DialogTitle>
              选择{{ resourceTargetType === 'colorChart' ? '花色图' : '详情图' }}
            </DialogTitle>
            <DialogDescription>从我的资源库中选择图片导入到当前产品</DialogDescription>
          </DialogHeader>

          <div class="border-b border-border px-6 py-4">
            <div class="flex items-center gap-2">
              <div class="relative flex-1">
                <SafeIcon name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <Input
                  v-model="resourceKeyword"
                  placeholder="搜索图片名称"
                  class="h-10 pl-9"
                  @keyup.enter="loadResources"
                />
              </div>
              <Button variant="outline" @click="loadResources" :disabled="isResourceLoading">
                搜索
              </Button>
            </div>
          </div>

          <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div v-if="isResourceLoading" class="flex h-64 items-center justify-center text-muted-foreground">
              <SafeIcon name="Loader2" :size="22" class="mr-2 animate-spin" />
              加载中...
            </div>
            <div v-else-if="resourceList.length === 0" class="flex h-64 flex-col items-center justify-center text-muted-foreground">
              <SafeIcon name="ImageOff" :size="36" class="mb-3" />
              暂无资源
            </div>
            <div v-else class="grid grid-cols-3 gap-4 lg:grid-cols-5">
              <button
                v-for="resource in resourceList"
                :key="resource.id"
                type="button"
                :class="cn(
                  'group overflow-hidden rounded-lg border bg-card text-left transition-colors',
                  selectedResourceIds.has(resource.id)
                    ? 'border-primary ring-2 ring-primary/20'
                    : 'border-border hover:border-primary/50'
                )"
                @click="toggleResource(resource.id)"
              >
                <div class="relative aspect-square bg-muted">
                  <img
                    v-if="(resource.thumbnailUrl || resource.url) && !brokenResourceIds.has(resource.id)"
                    :src="resource.thumbnailUrl || resource.url"
                    :alt="resource.name"
                    class="h-full w-full object-cover"
                    @error="markResourceBroken(resource.id)"
                  />
                  <div
                    v-else
                    class="flex h-full w-full flex-col items-center justify-center gap-2 bg-muted text-muted-foreground"
                  >
                    <SafeIcon name="ImageOff" :size="28" />
                    <span class="max-w-[80%] truncate text-[11px]">图片暂不可预览</span>
                  </div>
                  <div
                    :class="cn(
                      'absolute left-2 top-2 flex h-5 w-5 items-center justify-center rounded border bg-white shadow-sm',
                      selectedResourceIds.has(resource.id) ? 'border-primary bg-primary text-primary-foreground' : 'border-border'
                    )"
                  >
                    <SafeIcon v-if="selectedResourceIds.has(resource.id)" name="Check" :size="13" />
                  </div>
                </div>
                <div class="space-y-1 p-2">
                  <p class="truncate text-xs font-medium">{{ resource.name }}</p>
                  <p class="truncate text-[11px] text-muted-foreground">{{ resource.sizeLabel || '资源图片' }}</p>
                </div>
              </button>
            </div>
          </div>

          <DialogFooter class="border-t border-border px-6 py-4">
            <Button variant="outline" @click="resourceDialogOpen = false">取消</Button>
            <Button
              @click="handleImportResources"
              :disabled="isResourceLoading || selectedResourceIds.size === 0"
            >
              导入选中图片（{{ selectedResourceIds.size }}）
            </Button>
          </DialogFooter>
        </div>
      </DialogScrollContent>
    </Dialog>
  </div>
</template>
