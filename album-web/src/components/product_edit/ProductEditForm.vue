
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
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
  categoryId: string
  visibility: 'public' | 'private' | 'shared'
  hideDetailImage: boolean
  colorChartImages: ProductImageData[]
  detailChartImages: ProductImageData[]
}

const isClient = ref(true)
const isLoading = ref(false)
const isSaving = ref(false)
const productId = ref<string | null>(null)
const categories = ref<CategoryData[]>([])

const initialFormState: FormState = {
  id: '',
  name: '',
  intro: '',
  categoryId: 'none',
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
    formState.value.categoryId !== initialFormState.categoryId ||
    formState.value.hideDetailImage !== initialFormState.hideDetailImage ||
    formState.value.colorChartImages.length !== initialFormState.colorChartImages.length ||
    formState.value.detailChartImages.length !== initialFormState.detailChartImages.length
  )
})

const isFormValid = computed(() => {
  return formState.value.name.trim().length > 0
})

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })

  const params = new URLSearchParams(window.location.search)
  const id = params.get('productId')

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
      categoryId: product.categoryId || 'none',
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
  window.location.href = './product-management.html'
}

const buildSavePayload = () => ({
  fid: formState.value.id,
  folder_name: formState.value.name,
  folder_desc: formState.value.intro,
  category_ids: formState.value.categoryId === 'none' ? [] : [formState.value.categoryId],
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
        fid: formState.value.categoryId === 'none' ? 0 : formState.value.categoryId,
        folder_type: 2,
        allow_draft: 1,
      })
    }

    toast.success('设置已保存')
    window.location.href = './product-management.html'
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
        fid: formState.value.categoryId === 'none' ? 0 : formState.value.categoryId,
        folder_type: 2,
        allow_draft: 1,
      })
      if (created?.id) formState.value.id = String(created.id)
    }

    toast.success('设置已保存')
    window.location.href = `./product-detail.html?productId=${formState.value.id}`
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

const ensureProductDraft = async () => {
  if (productId.value || formState.value.id) {
    return productId.value || formState.value.id
  }
  const created = await pcApi.createProductOrCategory({
    fid: formState.value.categoryId === 'none' ? 0 : formState.value.categoryId,
    folder_type: 2,
    folder_name: formState.value.name || '未命名产品',
    folder_desc: formState.value.intro || '',
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

const handleResourceLibrarySelect = async (type: ProductImageType) => {
  const targetType = type === 'colorChart' ? 'colorChart' : 'detailChart'
  try {
    const currentProductId = await ensureProductDraft()
    const params = new URLSearchParams({
      targetType,
      productId: currentProductId,
      returnTo: `./product-edit.html?productId=${encodeURIComponent(currentProductId)}`,
    })
    window.location.href = `./resource-library-picker.html?${params.toString()}`
  } catch (error: any) {
    toast.error(error?.message || '请先保存产品后再选择资源库图片')
  }
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
    <div class="flex items-center justify-between border-b border-border pb-4 mb-6">
      <div>
        <h1 class="text-page-title">{{ isEditMode ? '编辑产品' : '新建产品' }}</h1>
        <p class="text-caption mt-1">
          {{ isEditMode ? '更新产品信息和图片' : '创建新的产品并上传图片' }}
        </p>
      </div>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto min-h-0 pr-4">
      <div class="space-y-6 pb-6">
        <!-- Basic Information Section -->
        <div class="form-section">
          <h2 class="form-section-title">基础信息</h2>
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="product-name" class="text-label">产品名称 *</Label>
              <Input
                id="product-name"
                v-model="formState.name"
                placeholder="输入产品名称，如：云感纯棉四件套"
                class="h-10"
              />
            </div>

            <div class="space-y-2">
              <Label for="product-intro" class="text-label">产品简介</Label>
              <Textarea
                id="product-intro"
                v-model="formState.intro"
                placeholder="输入产品简介，描述产品特点和用途"
                class="min-h-[100px] resize-none"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="product-category" class="text-label">所属分类</Label>
                <Select v-model="formState.categoryId">
                  <SelectTrigger id="product-category" class="h-10">
                    <SelectValue placeholder="选择分类" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">未分类</SelectItem>
                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label for="product-visibility" class="text-label">可见性</Label>
                <Select v-model="formState.visibility">
                  <SelectTrigger id="product-visibility" class="h-10">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="public">公开</SelectItem>
                    <SelectItem value="private">私密</SelectItem>
                    <SelectItem value="shared">分享可见</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-muted/30 rounded-md border border-border">
              <div class="flex items-center gap-2">
                <SafeIcon name="Eye" :size="18" class="text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">隐藏详情图</p>
                  <p class="text-xs text-muted-foreground">访客将无法查看和下载详情图</p>
                </div>
              </div>
              <Switch v-model="formState.hideDetailImage" />
            </div>
          </div>
        </div>

        <!-- Color Chart Images Section -->
        <div class="form-section">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-border">
            <h2 class="form-section-title">花色图</h2>
            <span class="text-xs text-muted-foreground">
              {{ formState.colorChartImages.length }} 张
            </span>
          </div>

          <ImageUploadZone
            type="colorChart"
            :images="formState.colorChartImages"
            :upload-handler="handleUploadImage"
            @add-images="handleAddImages"
            @remove-image="handleRemoveImage"
            @select-from-library="handleResourceLibrarySelect"
          />

          <ImageSortable
            v-if="formState.colorChartImages.length > 0"
            :images="formState.colorChartImages"
            type="colorChart"
            class="mt-4"
            @remove="handleRemoveImage"
          />
        </div>

        <!-- Detail Chart Images Section -->
        <div class="form-section">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-border">
            <h2 class="form-section-title">详情图</h2>
            <span class="text-xs text-muted-foreground">
              {{ formState.detailChartImages.length }} 张
            </span>
          </div>

          <ImageUploadZone
            type="detailChart"
            :images="formState.detailChartImages"
            :upload-handler="handleUploadImage"
            @add-images="handleAddImages"
            @remove-image="handleRemoveImage"
            @select-from-library="handleResourceLibrarySelect"
          />

          <ImageSortable
            v-if="formState.detailChartImages.length > 0"
            :images="formState.detailChartImages"
            type="detailChart"
            class="mt-4"
            @remove="handleRemoveImage"
          />
        </div>
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
  </div>
</template>
