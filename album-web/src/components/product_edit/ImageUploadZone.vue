
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import type { ProductImageData, ProductImageType } from '@/data/ProductData'

interface Props {
  type: ProductImageType
  images: ProductImageData[]
  compact?: boolean
  uploadHandler?: (file: File, type: ProductImageType) => Promise<ProductImageData>
}

const props = withDefaults(defineProps<Props>(), {
  compact: false,
})

const emit = defineEmits<{
  (e: 'add-images', images: ProductImageData[], type: ProductImageType): void
}>()

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = false

  const files = e.dataTransfer?.files
  if (files) {
    processFiles(files)
  }
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files) {
    processFiles(target.files)
  }
}

const processFiles = async (files: FileList) => {
  const imageFiles = Array.from(files).filter((file) =>
    file.type.startsWith('image/')
  )

  if (imageFiles.length === 0) {
    toast.error('请选择有效的图片文件')
    return
  }

  const newImages: ProductImageData[] = []
  for (let index = 0; index < imageFiles.length; index += 1) {
    const file = imageFiles[index]
    if (props.uploadHandler) {
      const uploaded = await props.uploadHandler(file, props.type)
      newImages.push(uploaded)
    } else {
      const previewUrl = URL.createObjectURL(file)
      newImages.push({
        id: `img_${Date.now()}_${index}`,
        productId: '',
        type: props.type,
        name: file.name,
        url: previewUrl,
        thumbnailUrl: previewUrl,
        sizeLabel: `${(file.size / 1024 / 1024).toFixed(1)} MB`,
        sizeBytes: file.size,
        sortOrder: props.images.length + index,
        isOriginalLarge: file.size > 3 * 1024 * 1024,
        createdAt: new Date().toLocaleString('zh-CN'),
      })
    }
  }

  emit('add-images', newImages, props.type)
  toast.success(`已添加 ${newImages.length} 张图片`)
  if (fileInput.value) fileInput.value.value = ''
}

const handleClickUpload = () => {
  fileInput.value?.click()
}

const typeLabel = computed(() => {
  return props.type === 'colorChart' ? '花色图' : '详情图'
})
</script>

<template>
  <div
    class="upload-zone"
    :class="[props.compact && 'is-compact', isDragging && 'border-primary bg-primary/5']"
    @dragover="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
  >
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*"
      class="hidden"
      @change="handleFileSelect"
    />

    <div :class="props.compact ? 'flex flex-col items-center gap-2' : 'flex flex-col items-center gap-3'">
      <div :class="props.compact ? 'h-10 w-10 bg-primary/10 rounded-full flex items-center justify-center' : 'w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center'">
        <SafeIcon
          :name="isDragging ? 'Check' : 'Upload'"
          :size="props.compact ? 20 : 24"
          :class="isDragging ? 'text-primary' : 'text-muted-foreground'"
        />
      </div>

      <div class="text-center">
        <p class="text-sm font-medium text-foreground">
          {{ isDragging ? '松开鼠标上传图片' : `拖拽或点击上传${typeLabel}` }}
        </p>
        <p :class="props.compact ? 'mt-0.5 text-xs text-muted-foreground' : 'text-xs text-muted-foreground mt-1'">
          支持 JPG、PNG、WebP 格式，单张不超过 50MB
        </p>
      </div>

      <div :class="props.compact ? 'flex gap-2 pt-0.5' : 'flex gap-2 pt-2'">
        <Button
          variant="outline"
          size="sm"
          @click="handleClickUpload"
          class="h-9"
        >
          <SafeIcon name="Plus" :size="16" class="mr-1" />
          本地上传
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.upload-zone.is-compact {
  padding: 18px 24px;
}
</style>
