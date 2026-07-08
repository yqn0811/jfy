<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'

interface Props {
  title: string
  description: string
  type: 'colorChart' | 'detailChart'
  progress?: number
  disabled?: boolean
  maxConcurrent?: number
  uploadHandler?: (file: File, type: 'colorChart' | 'detailChart') => Promise<string>
}

const props = withDefaults(defineProps<Props>(), {
  progress: 0,
  disabled: false,
  maxConcurrent: 1,
})

const emit = defineEmits<{
  (e: 'upload-complete', files: string[]): void
  (e: 'uploading', value: boolean): void
}>()

type UploadItem = {
  previewUrl: string
  finalUrl: string
  status: 'uploading' | 'done' | 'error'
}

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()
const uploadItems = ref<UploadItem[]>([])

const uploadedFiles = computed(() => uploadItems.value.map(item => item.finalUrl || item.previewUrl))

const safeMaxConcurrent = computed(() => {
  const value = Number(props.maxConcurrent || 1)
  if (!Number.isFinite(value) || value <= 0) return 1
  return Math.min(Math.floor(value), 8)
})

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  if (!props.disabled) isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = false
  if (props.disabled) return

  const files = e.dataTransfer?.files
  if (files) handleFiles(files)
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files) handleFiles(target.files)
}

const handleFiles = async (files: FileList) => {
  const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))

  if (imageFiles.length === 0) {
    toast.error('请选择有效的图片文件')
    return
  }

  const items = imageFiles.map(file => ({
    previewUrl: URL.createObjectURL(file),
    finalUrl: '',
    status: 'uploading' as const,
  }))
  uploadItems.value = items
  emit('uploading', true)

  try {
    let nextIndex = 0
    let failedCount = 0
    const worker = async () => {
      while (nextIndex < imageFiles.length) {
        const index = nextIndex++
        const file = imageFiles[index]
        const item = items[index]
        let finalUrl = item.previewUrl

        try {
          if (props.uploadHandler) {
            finalUrl = await props.uploadHandler(file, props.type)
          } else {
            await new Promise(resolve => setTimeout(resolve, 300))
          }

          item.finalUrl = finalUrl || item.previewUrl
          item.status = 'done'
        } catch {
          failedCount += 1
          item.status = 'error'
        }
        uploadItems.value = [...items]
      }
    }

    await Promise.all(
      Array.from(
        { length: Math.min(safeMaxConcurrent.value, imageFiles.length) },
        () => worker()
      )
    )

    if (failedCount > 0) {
      toast.error(`${failedCount} 张图片上传失败，请重试`)
      return
    }

    const finalUrls = items.map(item => item.finalUrl || item.previewUrl)
    emit('upload-complete', finalUrls)
    toast.success(`已上传 ${imageFiles.length} 张图片`)
  } catch (error: any) {
    uploadItems.value = items.map(item => item.status === 'done' ? item : { ...item, status: 'error' })
    toast.error(error?.message || '上传失败，请重试')
  } finally {
    emit('uploading', false)
    if (fileInput.value) fileInput.value.value = ''
  }
}

const triggerFileInput = () => {
  if (!props.disabled) fileInput.value?.click()
}

const getItemStatus = (index: number) => {
  const status = uploadItems.value[index]?.status
  if (status === 'uploading') return '上传中'
  if (status === 'error') return '上传失败'
  return '已上传'
}

const getItemIcon = (index: number) => {
  const status = uploadItems.value[index]?.status
  if (status === 'uploading') return 'Loader2'
  if (status === 'error') return 'AlertCircle'
  return 'Check'
}

const getItemIconClass = (index: number) => {
  const status = uploadItems.value[index]?.status
  if (status === 'uploading') return 'text-muted-foreground animate-spin'
  if (status === 'error') return 'text-destructive'
  return 'text-primary'
}
</script>

<template>
  <div class="surface-base card-padding space-y-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-section-title">{{ title }}</h3>
      <span class="text-xs font-medium px-2 py-1 bg-primary/10 text-primary rounded">
        {{ uploadedFiles.length }} 张
      </span>
    </div>

    <div
      v-if="progress < 100"
      class="upload-zone"
      :class="[
        isDragging && 'border-primary bg-primary/5',
        disabled && 'opacity-60 pointer-events-none',
      ]"
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
        :disabled="disabled"
        @change="handleFileSelect"
      />

      <div class="flex flex-col items-center gap-3">
        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
          <SafeIcon name="Upload" :size="24" class="text-primary" />
        </div>
        <div class="text-center">
          <p class="font-medium text-foreground">{{ description }}</p>
          <p class="text-xs text-muted-foreground mt-1">或点击选择文件</p>
        </div>
        <Button
          variant="outline"
          size="sm"
          :disabled="disabled"
          @click="triggerFileInput"
        >
          选择文件
        </Button>
      </div>
    </div>

    <div v-if="uploadedFiles.length > 0" class="space-y-2">
      <div
        v-for="(file, index) in uploadedFiles"
        :key="`${file}_${index}`"
        class="flex items-center gap-3 p-2 bg-muted/50 rounded border border-border"
      >
        <img
          :src="file"
          :alt="`Uploaded ${index + 1}`"
          class="w-10 h-10 object-cover rounded"
        />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">图片 {{ index + 1 }}</p>
          <p class="text-xs text-muted-foreground">
            {{ getItemStatus(index) }}
          </p>
        </div>
        <SafeIcon
          :name="getItemIcon(index)"
          :size="18"
          class="shrink-0"
          :class="getItemIconClass(index)"
        />
      </div>
    </div>

    <div v-if="progress > 0 && progress < 100" class="space-y-2">
      <div class="flex justify-between text-xs">
        <span class="text-muted-foreground">上传中...</span>
        <span class="font-medium">{{ progress }}%</span>
      </div>
      <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
        <div
          class="h-full bg-primary transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </div>
</template>
