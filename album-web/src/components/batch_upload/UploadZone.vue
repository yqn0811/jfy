<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'

interface Props {
  title: string
  description: string
  actionLabel?: string
  waitingLabel?: string
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

  const items: UploadItem[] = imageFiles.map(file => ({
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
  <div class="batch-upload-card">
    <div class="mb-6 flex items-start gap-4">
      <div class="batch-upload-step">{{ type === 'colorChart' ? 1 : 2 }}</div>
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <SafeIcon :name="type === 'colorChart' ? 'Palette' : 'FileImage'" :size="22" class="text-primary" />
          <h3 class="text-2xl font-semibold leading-tight text-foreground">{{ title }}</h3>
        </div>
        <p class="mt-3 text-sm font-medium text-muted-foreground">{{ description }}</p>
      </div>
      <span class="ml-auto shrink-0 rounded-md bg-primary/10 px-3 py-1.5 text-sm font-semibold text-primary">
        {{ uploadedFiles.length }} 张
      </span>
    </div>

    <div
      v-if="progress < 100"
      class="batch-drop-zone"
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
        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-primary/10">
          <SafeIcon name="CloudUpload" :size="34" class="text-muted-foreground" />
        </div>
        <div class="text-center">
          <p class="text-xl font-semibold text-foreground">点击或拖拽图片到这里</p>
          <p class="mt-3 text-base font-medium text-muted-foreground">支持 JPG、PNG、GIF，一次最多 200 张</p>
        </div>
      </div>
    </div>

    <div class="mt-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <p class="text-base font-medium text-muted-foreground">
        {{ uploadedFiles.length > 0 ? `已选择 ${uploadedFiles.length} 张图片` : (waitingLabel || '等待选择图片') }}
      </p>
      <div class="flex flex-wrap gap-3">
        <Button
          variant="outline"
          size="lg"
          class="h-11 min-w-[128px] gap-2"
          :disabled="disabled"
          @click="triggerFileInput"
        >
          <SafeIcon name="FolderOpen" :size="18" />
          选择图片
        </Button>
        <Button
          size="lg"
          class="h-11 min-w-[148px] gap-2"
          :disabled="disabled || uploadedFiles.length === 0"
          @click="triggerFileInput"
        >
          <SafeIcon name="Upload" :size="18" />
          {{ actionLabel || '上传图片' }}
        </Button>
      </div>
    </div>

    <div v-if="uploadedFiles.length > 0" class="mt-5 max-h-52 space-y-2 overflow-y-auto pr-1">
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

    <div v-if="progress > 0 && progress < 100" class="mt-5 space-y-2">
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

<style scoped>
.batch-upload-card {
  min-width: 0;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--card));
  padding: 2rem;
}

.batch-upload-step {
  display: flex;
  height: 2.25rem;
  width: 2.25rem;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  font-weight: 700;
}

.batch-drop-zone {
  display: flex;
  min-height: 360px;
  align-items: center;
  justify-content: center;
  border: 2px dashed hsl(var(--border));
  border-radius: var(--radius);
  background: hsl(var(--muted) / 0.2);
  padding: 2rem;
  text-align: center;
  transition: border-color 150ms ease, background-color 150ms ease;
  cursor: pointer;
}

.batch-drop-zone:hover {
  border-color: hsl(var(--primary) / 0.5);
  background: hsl(var(--muted) / 0.36);
}

@media (max-width: 900px) {
  .batch-upload-card {
    padding: 1.25rem;
  }

  .batch-drop-zone {
    min-height: 260px;
  }
}
</style>
