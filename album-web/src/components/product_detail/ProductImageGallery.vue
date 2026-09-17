
<script setup lang="ts">
import { computed } from 'vue'
import type { ProductImageData } from '@/data/ProductImageData'
import { cn } from '@/lib/utils'

interface Props {
  images: ProductImageData[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'image-click', index: number): void
}>()

const gridCols = computed(() => {
  const count = props.images.length
  if (count === 1) return 'grid-cols-1'
  if (count === 2) return 'grid-cols-2'
  if (count === 3) return 'grid-cols-3'
  return 'grid-cols-4'
})
</script>

<template>
  <div :class="cn('grid gap-3', gridCols)">
    <div
      v-for="(image, index) in images"
      :key="image.id"
      class="group relative aspect-square bg-muted rounded-lg overflow-hidden cursor-pointer border border-border hover:border-primary transition-all hover:shadow-card"
      @click="emit('image-click', index)"
    >
      <!-- Image -->
      <img
        :src="image.thumbnailUrl"
        :alt="image.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
      />

      <!-- Overlay on hover -->
      <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
          <div class="w-10 h-10 bg-white/90 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Image info tooltip -->
      <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <p class="text-white text-xs font-medium truncate">{{ image.name }}</p>
        <p class="text-white/70 text-[10px]">{{ image.sizeLabel }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transitions for gallery items */
</style>
