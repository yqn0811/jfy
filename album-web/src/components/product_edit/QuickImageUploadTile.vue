<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { ProductImageType } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

interface Props {
  type: ProductImageType
  uploadHandler?: (file: File, type: ProductImageType) => Promise<ProductImageData>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'add-images', images: ProductImageData[], type: ProductImageType): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)

const handleChoose = () => {
  if (!isUploading.value) fileInput.value?.click()
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
    const images: ProductImageData[] = []
    for (const file of files) {
      if (!props.uploadHandler) continue
      images.push(await props.uploadHandler(file, props.type))
    }
    if (images.length) {
      emit('add-images', images, props.type)
      toast.success(`已添加 ${images.length} 张图片`)
    }
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
