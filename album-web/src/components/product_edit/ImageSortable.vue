
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'
import type { ProductImageType } from '@/data/ProductData'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'

interface Props {
  images: ProductImageData[]
  type: ProductImageType
  class?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'remove', id: string, type: ProductImageType): void
  (e: 'reorder', images: ProductImageData[], type: ProductImageType): void
}>()

const draggedIndex = ref<number | null>(null)
const draggedImageId = ref<string | null>(null)
const dragOverIndex = ref<number | 'end' | null>(null)

const resetDragState = () => {
  draggedIndex.value = null
  draggedImageId.value = null
  dragOverIndex.value = null
}

const handleDragStart = (index: number, event: DragEvent) => {
  draggedIndex.value = index
  draggedImageId.value = props.images[index]?.id || null
  if (event.dataTransfer && draggedImageId.value) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.dropEffect = 'move'
    event.dataTransfer.setData('text/plain', draggedImageId.value)
  }
}

const handleDragOver = (index: number, e: DragEvent) => {
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
  dragOverIndex.value = index
}

const handleListDragOver = (event: DragEvent) => {
  event.preventDefault()
  if (event.dataTransfer) event.dataTransfer.dropEffect = 'move'
  dragOverIndex.value = 'end'
}

const handleDragLeave = () => {
  dragOverIndex.value = null
}

const moveImage = (targetIndex: number) => {
  const sourceIndex = draggedImageId.value
    ? props.images.findIndex(image => image.id === draggedImageId.value)
    : draggedIndex.value

  if (sourceIndex === null || sourceIndex < 0) {
    resetDragState()
    return
  }

  const newImages = [...props.images]
  const [draggedItem] = newImages.splice(sourceIndex, 1)
  const nextIndex = Math.max(0, Math.min(targetIndex, newImages.length))
  newImages.splice(nextIndex, 0, draggedItem)
  emit('reorder', newImages.map((item, index) => ({ ...item, sortOrder: index })), props.type)

  resetDragState()
}

const handleDrop = (targetIndex: number) => {
  if (draggedIndex.value === null) {
    resetDragState()
    return
  }

  if (draggedIndex.value === targetIndex) {
    resetDragState()
    return
  }

  moveImage(targetIndex)
}

const handleDropAtEnd = () => {
  if (draggedIndex.value === null) return
  moveImage(props.images.length)
}

const handleRemove = (id: string) => {
  emit('remove', id, props.type)
}

const typeLabel = computed(() => {
  return props.type === 'colorChart' ? '花色图' : '详情图'
})

const finishedCount = computed(() =>
  props.images.filter(image => image.uploadStatus !== 'uploading' && image.uploadStatus !== 'error').length
)

const isUploading = (image: ProductImageData) => image.uploadStatus === 'uploading'
const isFailed = (image: ProductImageData) => image.uploadStatus === 'error'
</script>

<template>
  <div :class="props.class">
    <p class="mb-2 text-sm font-medium text-muted-foreground">
      已上传 {{ finishedCount }} 张{{ typeLabel }}（可拖拽排序）
    </p>

    <div
      class="flex flex-wrap gap-3"
      @dragover.self.prevent="handleListDragOver"
      @drop.self.prevent="handleDropAtEnd"
    >
      <div
        v-for="(image, index) in images"
        :key="image.id"
        :draggable="!isUploading(image)"
        class="group relative h-28 w-28 overflow-hidden rounded-lg border-2 border-dashed border-border bg-muted/30 transition-all hover:border-primary"
        :class="
          cn(
            dragOverIndex === index && 'border-primary bg-primary/10',
            draggedIndex === index && 'opacity-50',
            isUploading(image) ? 'cursor-default animate-pulse border-primary/40 bg-primary/5' : 'cursor-move',
            isFailed(image) && 'border-destructive/60 bg-destructive/5'
          )
        "
        :aria-grabbed="draggedIndex === index"
        @dragstart="handleDragStart(index, $event)"
        @dragover="handleDragOver(index, $event)"
        @dragleave="handleDragLeave"
        @drop.stop.prevent="handleDrop(index)"
        @dragend="resetDragState"
      >
        <!-- Image Preview -->
        <img
          :src="productImageUrl(image, 'thumb')"
          :alt="image.name"
          draggable="false"
          class="w-full h-full object-cover"
        />

        <div
          v-if="isUploading(image)"
          class="absolute inset-0 flex flex-col items-center justify-center bg-white/70 text-primary backdrop-blur-[1px]"
        >
          <SafeIcon name="Loader2" :size="22" class="animate-spin" />
          <span class="mt-2 text-xs font-medium">上传中</span>
        </div>

        <div
          v-else-if="isFailed(image)"
          class="absolute inset-0 flex flex-col items-center justify-center bg-destructive/80 px-2 text-center text-white"
        >
          <SafeIcon name="CircleAlert" :size="22" />
          <span class="mt-2 line-clamp-2 text-xs">{{ image.uploadError || '上传失败' }}</span>
        </div>

        <!-- Overlay -->
        <div
          class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100"
        >
          <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 bg-destructive/90 hover:bg-destructive text-white rounded-full"
            @click.stop="handleRemove(image.id)"
          >
            <SafeIcon name="Trash2" :size="16" />
          </Button>
        </div>

        <!-- Badge -->
        <div class="absolute top-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded">
          {{ index + 1 }}
        </div>

        <div
          v-if="!isUploading(image)"
          class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded bg-black/50 text-white opacity-80"
        >
          <SafeIcon name="GripVertical" :size="14" />
        </div>

      </div>

      <div
        v-if="draggedIndex !== null"
        :class="
          cn(
            'flex h-28 w-28 items-center justify-center rounded-lg border-2 border-dashed text-xs text-muted-foreground transition-colors',
            dragOverIndex === 'end' ? 'border-primary bg-primary/10 text-primary' : 'border-border bg-muted/20'
          )
        "
        @dragover.stop.prevent="handleListDragOver"
        @drop.stop.prevent="handleDropAtEnd"
      >
        放到末尾
      </div>

      <slot name="after" />
    </div>
  </div>
</template>
