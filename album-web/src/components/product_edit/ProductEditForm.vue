
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
import { mapCategory, mapProduct, mapProductImagesFromDetail, unwrapList } from '@/lib/jfyuntu-mappers'
import type { ProductData, ProductImageType } from '@/data/ProductData'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductImageData } from '@/data/ProductImageData'
import ImageUploadZone from './ImageUploadZone.vue'
import ImageSortable from './ImageSortable.vue'

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
  const url = item.url || item.picture_url_original || item.picture_url || item.imgurl || item.thumbnailUrl || item.thumb || ''
  const thumbnailUrl = item.thumbnailUrl || item.thumb || item.picture_url || item.imgurl || url
  return {
    id: String(item.pid || item.pic_id || item.resource_id || item.id || `resource_${Date.now()}`),
    productId: productIdValue,
    type,
    name: item.name || item.pic_name || item.file_name || `${type === 'colorChart' ? '花色图' : '详情图'}`,
    url,
    thumbnailUrl,
    sizeLabel: item.sizeLabel || item.size_label || (item.size ? `${(Number(item.size) / 1024 / 1024).toFixed(1)} MB` : ''),
    sizeBytes: Number(item.sizeBytes || item.size || 0),
    sortOrder: type === 'colorChart' ? formState.value.colorChartImages.length : formState.value.detailChartImages.length,
    isOriginalLarge: Number(item.sizeBytes || item.size || 0) > 3 * 1024 * 1024,
    createdAt: item.create_time || item.created_at || new Date().toLocaleString('zh-CN'),
  }
}

const normalizeResourceList = (raw: any) => {
  const list = Array.isArray(raw?.data)
    ? raw.data
    : Array.isArray(raw?.lists)
      ? raw.lists
      : Array.isArray(raw?.list)
        ? raw.list
        : Array.isArray(raw)
          ? raw
          : []
  return list.map((item: any) => {
    const url = item.url || item.picture_url_original || item.picture_url || item.imgurl || ''
    return {
      ...item,
      id: String(item.id || item.resource_id || item.pic_id || ''),
      name: item.name || item.pic_name || item.file_name || '未命名图片',
      url,
      thumbnailUrl: item.thumbnailUrl || item.thumb || item.picture_url || item.imgurl || url,
      sizeLabel: item.sizeLabel || item.size_label || (item.size ? `${(Number(item.size) / 1024 / 1024).toFixed(1)} MB` : ''),
    }
  })
}

const loadResources = async () => {
  isResourceLoading.value = true
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
  resourceDialogOpen.value = true
  await loadResources()
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
    const fid = await ensureProductDraft()
    const type = resourceTargetType.value
    const role = type === 'detailChart' ? 'detail' : 'cover'
    const selected = resourceList.value.filter(item => selectedResourceIds.value.has(item.id))
    const imported: ProductImageData[] = []

    for (const item of selected) {
      const data = await pcApi.importAiResource(item.id, role, fid)
      const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
      imported.push(mapResourceToImage(rows[0] || item, type, fid))
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
  if (props.embedded) {
    emit('cancel')
    return
  }
  window.location.href = './product-management.html'
}

const buildSavePayload = () => ({
  fid: formState.value.id,
  folder_name: formState.value.name,
  folder_desc: formState.value.intro,
  category_ids: formState.value.categoryIds,
  private_type: formState.value.visibility === 'private' ? 2 : formState.value.visibility === 'shared' ? 4 : 1,
  hide_detail_pictures: formState.value.hideDetailImage ? 1 : 0,
  pic_ids: formState.value.colorChartImages.map(item => item.id),
  detail_pic_ids: formState.value.detailChartImages.map(item => item.id),
  new_thumb: formState.value.colorChartImages[0]?.url || '',
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
      await pcApi.createProductOrCategory({
        ...buildSavePayload(),
        fid: formState.value.categoryIds[0] || 0,
        folder_type: 2,
        allow_draft: 1,
      })
    }

    toast.success('设置已保存')
    if (props.embedded) {
      emit('saved', formState.value.id || productId.value || '')
    } else {
      window.location.href = './product-management.html'
    }
  } catch (error) {
    console.error('Failed to save product:', error)
    toast.error('保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

const handleSaveAndPreview = async () => {
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
      if (created?.id) formState.value.id = String(created.id)
    }

    toast.success('设置已保存')
    if (props.embedded) {
      emit('preview', formState.value.id)
      emit('saved', formState.value.id)
    } else {
      window.location.href = `./product-detail.html?productId=${formState.value.id}`
    }
  } catch (error) {
    console.error('Failed to save product:', error)
    toast.error('保存失败，请稍后重试')
  } finally {
    isSaving.value = false
  }
}

const handleAddImages = (images: ProductImageData[], type: ProductImageType) => {
  if (type === 'colorChart') {
    formState.value.colorChartImages.push(...images)
  } else {
    formState.value.detailChartImages.push(...images)
  }
}

const handleRemoveImage = (id: string, type: ProductImageType) => {
  if (type === 'colorChart') {
    formState.value.colorChartImages = formState.value.colorChartImages.filter(
      (img) => img.id !== id
    )
  } else {
    formState.value.detailChartImages = formState.value.detailChartImages.filter(
      (img) => img.id !== id
    )
  }
  toast.success('已删除')
}

const handleReorderImages = (images: ProductImageData[], type: ProductImageType) => {
  if (type === 'colorChart') {
    formState.value.colorChartImages = images
  } else {
    formState.value.detailChartImages = images
  }
}

const ensureProductDraft = async () => {
  if (productId.value || formState.value.id) {
    return productId.value || formState.value.id
  }
  const created = await pcApi.createProductOrCategory({
    fid: formState.value.categoryIds[0] || 0,
    folder_type: 2,
    folder_name: formState.value.name || '未命名产品',
    folder_desc: formState.value.intro || '',
    category_ids: formState.value.categoryIds,
    pic_ids: [],
    detail_pic_ids: [],
    hide_detail_pictures: formState.value.hideDetailImage ? 1 : 0,
    allow_draft: 1,
  })
  const newId = String(created?.id || created?.fid || created?.folder_id || '')
  if (!newId) throw new Error('产品创建失败，请稍后重试')
  productId.value = newId
  formState.value.id = newId
  return newId
}

const handleUploadImage = async (file: File, type: ProductImageType): Promise<ProductImageData> => {
  const fid = await ensureProductDraft()
  const data = await pcApi.uploadProductImage(fid, file, type)
  const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
  const item = rows[0] || {}
  const url = item.url || URL.createObjectURL(file)
  return {
    id: String(item.pid || item.id || `img_${Date.now()}`),
    productId: fid,
    type,
    name: file.name,
    url,
    thumbnailUrl: url,
    sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
    sizeBytes: file.size,
    sortOrder: type === 'colorChart' ? formState.value.colorChartImages.length : formState.value.detailChartImages.length,
    isOriginalLarge: file.size > 3 * 1024 * 1024,
    createdAt: new Date().toLocaleString('zh-CN'),
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
      <div class="grid min-h-full gap-8 pb-6 lg:grid-cols-[minmax(320px,0.9fr)_minmax(560px,1.1fr)]">
        <!-- Basic Information Section -->
        <section class="space-y-6 lg:border-r lg:border-border lg:pr-8">
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
              class="min-h-[136px] resize-none rounded-lg bg-background text-base"
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
        <section class="space-y-8">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold">花色图</h2>
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
              class="min-h-[166px]"
              @add-images="handleAddImages"
              @remove-image="handleRemoveImage"
            />

            <ImageSortable
              v-if="formState.colorChartImages.length > 0"
              :images="formState.colorChartImages"
              type="colorChart"
              class="mt-2"
              @remove="handleRemoveImage"
              @reorder="handleReorderImages"
            />
          </div>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold">详情图</h2>
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
              class="min-h-[166px]"
              @add-images="handleAddImages"
              @remove-image="handleRemoveImage"
            />

            <ImageSortable
              v-if="formState.detailChartImages.length > 0"
              :images="formState.detailChartImages"
              type="detailChart"
              class="mt-2"
              @remove="handleRemoveImage"
              @reorder="handleReorderImages"
            />
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
        variant="outline"
        @click="handleSaveAndPreview"
        :disabled="!isFormValid || isSaving"
        class="px-6"
      >
        <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
        <span v-else>保存并预览</span>
      </Button>
      <Button
        @click="handleSave"
        :disabled="!isFormValid || isSaving"
        class="px-6"
      >
        <SafeIcon v-if="isSaving" name="Loader2" :size="16" class="mr-2 animate-spin" />
        <span v-else>保存</span>
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
                    :src="resource.thumbnailUrl || resource.url"
                    :alt="resource.name"
                    class="h-full w-full object-cover"
                  />
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
