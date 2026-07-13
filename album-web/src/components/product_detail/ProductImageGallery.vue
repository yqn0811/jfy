
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { cn } from '@/lib/utils'

interface Props {
  images: ProductImageData[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'image-click', index: number): void
}>()
</script>

<template>
  <div :class="cn('grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5', props.images.length === 1 && 'max-w-56')">
    <div
      v-for="(image, index) in images"
      :key="image.id"
      class="group relative aspect-square overflow-hidden rounded-lg border border-border bg-card cursor-pointer transition-all hover:border-primary hover:shadow-card"
      @click="emit('image-click', index)"
    >
      <!-- Image -->
      <div class="flex h-full w-full items-center justify-center bg-muted/40">
        <img
          :src="productImageUrl(image, 'preview')"
          :alt="image.name"
          loading="lazy"
          class="max-h-full max-w-full object-contain transition-transform duration-200 group-hover:scale-[1.02]"
        />
      </div>

      <!-- Overlay on hover -->
      <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
          <div class="w-10 h-10 bg-white/90 rounded-full flex items-center justify-center">
            <SafeIcon name="Eye" :size="20" class="text-foreground" />
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
