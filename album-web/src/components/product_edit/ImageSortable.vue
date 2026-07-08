
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'
import type { ProductImageData, ProductImageType } from '@/data/ProductData'

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
const dragOverIndex = ref<number | null>(null)

const handleDragStart = (index: number) => {
  draggedIndex.value = index
}

const handleDragOver = (index: number, e: DragEvent) => {
  e.preventDefault()
  dragOverIndex.value = index
}

const handleDragLeave = () => {
  dragOverIndex.value = null
}

const handleDrop = (targetIndex: number) => {
  if (draggedIndex.value === null || draggedIndex.value === targetIndex) {
    draggedIndex.value = null
    dragOverIndex.value = null
    return
  }

  const newImages = [...props.images]
  const [draggedItem] = newImages.splice(draggedIndex.value, 1)
  newImages.splice(targetIndex, 0, draggedItem)
  emit('reorder', newImages.map((item, index) => ({ ...item, sortOrder: index })), props.type)

  draggedIndex.value = null
  dragOverIndex.value = null
}

const handleRemove = (id: string) => {
  emit('remove', id, props.type)
}

const typeLabel = computed(() => {
  return props.type === 'colorChart' ? '花色图' : '详情图'
})
</script>

<template>
  <div :class="props.class">
    <p class="text-sm font-medium text-muted-foreground mb-3">
      已上传 {{ images.length }} 张{{ typeLabel }}（可拖拽排序）
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
      <div
        v-for="(image, index) in images"
        :key="image.id"
        draggable
        class="group relative aspect-square rounded-lg border-2 border-dashed border-border overflow-hidden bg-muted/30 cursor-move transition-all hover:border-primary"
        :class="
          cn(
            dragOverIndex === index && 'border-primary bg-primary/10',
            draggedIndex === index && 'opacity-50'
          )
        "
        @dragstart="handleDragStart(index)"
        @dragover="handleDragOver(index, $event)"
        @dragleave="handleDragLeave"
        @drop="handleDrop(index)"
      >
        <!-- Image Preview -->
        <img
          :src="image.thumbnailUrl"
          :alt="image.name"
          class="w-full h-full object-cover"
        />

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

      </div>
    </div>
  </div>
</template>
