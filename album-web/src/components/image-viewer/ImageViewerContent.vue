
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { Button } from '@/components/ui/button'

interface Props {
  imageUrl: string
  imageIndex: number
  totalImages: number
  canGoPrev: boolean
  canGoNext: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'previous'): void
  (e: 'next'): void
  (e: 'load-error'): void
}>()

const imageRef = ref<HTMLImageElement | null>(null)
const isImageLoading = ref(true)

onMounted(() => {
  if (imageRef.value) {
    imageRef.value.addEventListener('load', () => {
      isImageLoading.value = false
    })
    imageRef.value.addEventListener('error', () => {
      emit('load-error')
    })
  }
})

watch(
  () => props.imageUrl,
  () => {
    isImageLoading.value = true
  }
)
</script>

<template>
  <div class="relative w-full h-full flex items-center justify-center group">
    <!-- Image -->
    <img
      ref="imageRef"
      :src="imageUrl"
      :alt="`Image ${imageIndex} of ${totalImages}`"
      class="max-w-full max-h-full object-contain select-none"
      :class="isImageLoading && 'opacity-0'"
    />

    <!-- Loading Spinner -->
    <div v-if="isImageLoading" class="absolute inset-0 flex items-center justify-center">
      <SafeIcon name="Loader2" :size="48" class="text-white/40 animate-spin" />
    </div>

    <!-- Previous Button -->
    <Button
      v-if="canGoPrev"
      variant="ghost"
      size="icon"
      class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 h-12 w-12 opacity-0 group-hover:opacity-100 transition-opacity"
      @click="emit('previous')"
    >
      <SafeIcon name="ChevronLeft" :size="32" />
    </Button>

    <!-- Next Button -->
    <Button
      v-if="canGoNext"
      variant="ghost"
      size="icon"
      class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:bg-white/10 h-12 w-12 opacity-0 group-hover:opacity-100 transition-opacity"
      @click="emit('next')"
    >
      <SafeIcon name="ChevronRight" :size="32" />
    </Button>

    <!-- Image Type Badge -->
    <div class="absolute bottom-4 left-4 px-3 py-1.5 bg-black/50 backdrop-blur-sm rounded-full text-white text-xs font-medium">
      {{ imageIndex === 1 ? '花色图' : '详情图' }}
    </div>
  </div>
</template>
