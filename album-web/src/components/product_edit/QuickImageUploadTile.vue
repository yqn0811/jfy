<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { ProductImageType } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

interface Props {
  type: ProductImageType
  uploadHandler?: (file: File, type: ProductImageType, placeholder: ProductImageData) => Promise<ProductImageData>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'add-images', images: ProductImageData[], type: ProductImageType): void
  (e: 'update-image', clientId: string, image: ProductImageData, type: ProductImageType): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)

const handleChoose = () => {
  if (!isUploading.value) fileInput.value?.click()
}

const createPlaceholder = (file: File, index: number): ProductImageData => {
  const previewUrl = URL.createObjectURL(file)
  const clientId = `quick_upload_${Date.now()}_${index}_${Math.random().toString(36).slice(2)}`
  return {
    id: clientId,
    clientId,
    productId: '',
    type: props.type,
    name: file.name,
    url: previewUrl,
    thumbnailUrl: previewUrl,
    sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
    sizeBytes: file.size,
    sortOrder: 0,
    isOriginalLarge: file.size > 3 * 1024 * 1024,
    createdAt: new Date().toLocaleString('zh-CN'),
    uploadStatus: 'uploading',
    uploadProgress: 8,
  }
}

const handleFiles = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || []).filter(file => file.type.startsWith('image/'))
  if (!files.length) {
    toast.error('请选择有效的图片文件')
    target.value = ''
    return
  }

  isUploading.value = true
  try {
    const placeholders = files.map((file, index) => createPlaceholder(file, index))
    emit('add-images', placeholders, props.type)
    for (let index = 0; index < files.length; index += 1) {
      const file = files[index]
      const placeholder = placeholders[index]
      try {
        const uploaded = props.uploadHandler
          ? await props.uploadHandler(file, props.type, placeholder)
          : { ...placeholder, uploadStatus: 'done' as const, uploadProgress: 100 }
        emit('update-image', placeholder.clientId || placeholder.id, {
          ...uploaded,
          clientId: placeholder.clientId,
          uploadStatus: uploaded.uploadStatus || 'done',
          uploadProgress: 100,
        }, props.type)
      } catch (error: any) {
        emit('update-image', placeholder.clientId || placeholder.id, {
          ...placeholder,
          uploadStatus: 'error',
          uploadProgress: 0,
          uploadError: error?.message || '上传失败',
        }, props.type)
      }
    }
    toast.success(`已添加 ${files.length} 张图片`)
  } catch (error: any) {
    toast.error(error?.message || '上传失败，请稍后重试')
  } finally {
    isUploading.value = false
    target.value = ''
  }
}
</script>

<template>
  <button
    type="button"
    class="flex h-28 w-28 flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border bg-background text-muted-foreground transition-colors hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
    :disabled="isUploading"
    @click="handleChoose"
  >
    <input ref="fileInput" type="file" multiple accept="image/*" class="hidden" @change="handleFiles" />
    <SafeIcon :name="isUploading ? 'Loader2' : 'Plus'" :size="28" :class="isUploading ? 'animate-spin' : ''" />
    <span class="text-xs font-medium">{{ isUploading ? '上传中' : '本地上传' }}</span>
  </button>
</template>
