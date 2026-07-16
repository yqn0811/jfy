
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { Button } from '@/components/ui/button'

interface Props {
  imageUrl: string
  imageIndex: number
  totalImages: number
  canGoPrev: boolean
  canGoNext: boolean
  isLoadingOriginal?: boolean
  scale?: number
  rotation?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'previous'): void
  (e: 'next'): void
  (e: 'load-error'): void
  (e: 'zoom-in'): void
  (e: 'zoom-out'): void
  (e: 'rotate'): void
  (e: 'reset-transform'): void
}>()

const imageRef = ref<HTMLImageElement | null>(null)
const isImageLoading = ref(true)
const imageTransformStyle = computed(() => ({
  transform: `scale(${props.scale ?? 1}) rotate(${props.rotation ?? 0}deg)`,
}))

const handleImageLoaded = () => {
  isImageLoading.value = false
}

const handleImageError = () => {
  emit('load-error')
}

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
      class="max-w-full max-h-full object-contain select-none transition-[opacity,transform] duration-200"
      :class="isImageLoading ? 'opacity-0' : isLoadingOriginal ? 'opacity-45' : 'opacity-100'"
      :style="imageTransformStyle"
      draggable="false"
      @load="handleImageLoaded"
      @error="handleImageError"
      @contextmenu.prevent
    />

    <!-- Loading Spinner -->
    <div v-if="isImageLoading" class="absolute inset-0 flex items-center justify-center">
      <SafeIcon name="Loader2" :size="48" class="text-white/40 animate-spin" />
    </div>

    <div v-if="isLoadingOriginal && !isImageLoading" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-black/60 text-sm text-white backdrop-blur-sm">
      <SafeIcon name="Loader2" :size="32" class="text-white/80 animate-spin" />
      <span>正在加载原图...</span>
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

    <div
      v-if="!isImageLoading"
      class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-full bg-black/55 p-1.5 text-white shadow-lg backdrop-blur-sm"
    >
      <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9 rounded-full text-white hover:bg-white/10"
        :disabled="isLoadingOriginal || (scale ?? 1) <= 0.5"
        @click="emit('zoom-out')"
      >
        <SafeIcon name="ZoomOut" :size="18" />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9 rounded-full text-white hover:bg-white/10"
        :disabled="isLoadingOriginal || (scale ?? 1) >= 3"
        @click="emit('zoom-in')"
      >
        <SafeIcon name="ZoomIn" :size="18" />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9 rounded-full text-white hover:bg-white/10"
        :disabled="isLoadingOriginal"
        @click="emit('rotate')"
      >
        <SafeIcon name="RotateCw" :size="18" />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9 rounded-full text-white hover:bg-white/10"
        :disabled="isLoadingOriginal"
        @click="emit('reset-transform')"
      >
        <SafeIcon name="RefreshCcw" :size="18" />
      </Button>
    </div>
  </div>
</template>
