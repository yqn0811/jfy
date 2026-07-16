<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { preloadImageUrl, resolveProductImageDownloadUrl, resolveProductImageOriginalViewUrl, revokeImageObjectUrl } from '@/lib/download'

interface Props {
  open: boolean
  images: ProductImageData[]
  currentIndex: number
  canDownload?: boolean
  canViewOriginal?: boolean
  isLoggedIn?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canDownload: false,
  canViewOriginal: undefined,
  isLoggedIn: true,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'update:currentIndex', value: number): void
  (e: 'login-required'): void
}>()

const currentImage = computed(() => props.images[props.currentIndex] || null)
const loadedOriginalImageUrls = ref<Record<string, string>>({})
const loadingOriginalImageIds = ref<Record<string, boolean>>({})
const imageScale = ref(1)
const imageRotation = ref(0)
const currentPreviewUrl = computed(() => {
  const image = currentImage.value
  if (!image) return ''
  return loadedOriginalImageUrls.value[image.id] || productImageUrl(image, 'preview')
})
const imageTransformStyle = computed(() => ({
  transform: `scale(${imageScale.value}) rotate(${imageRotation.value}deg)`,
}))
const canViewOriginalImage = computed(() => props.canViewOriginal ?? props.canDownload)
const isCurrentOriginalLoading = computed(() => {
  const image = currentImage.value
  return !!image && !!loadingOriginalImageIds.value[image.id]
})
const isCurrentOriginalLoaded = computed(() => {
  const image = currentImage.value
  return !!image && !!loadedOriginalImageUrls.value[image.id]
})
const canPrevious = computed(() => props.currentIndex > 0)
const canNext = computed(() => props.currentIndex < props.images.length - 1)

const imageTypeLabel = computed(() => currentImage.value?.type === 'detailChart' ? '详情图' : '花色图')

const goPrevious = () => {
  if (isCurrentOriginalLoading.value) return
  if (canPrevious.value) {
    resetImageTransform()
    emit('update:currentIndex', props.currentIndex - 1)
  }
}

const goNext = () => {
  if (isCurrentOriginalLoading.value) return
  if (canNext.value) {
    resetImageTransform()
    emit('update:currentIndex', props.currentIndex + 1)
  }
}

const zoomIn = () => {
  imageScale.value = Math.min(3, Number((imageScale.value + 0.25).toFixed(2)))
}

const zoomOut = () => {
  imageScale.value = Math.max(0.5, Number((imageScale.value - 0.25).toFixed(2)))
}

const rotateImage = () => {
  imageRotation.value = (imageRotation.value + 90) % 360
}

const resetImageTransform = () => {
  imageScale.value = 1
  imageRotation.value = 0
}

const setOriginalImageLoading = (imageId: string, loading: boolean) => {
  loadingOriginalImageIds.value = {
    ...loadingOriginalImageIds.value,
    [imageId]: loading,
  }
  if (!loading) {
    const next = { ...loadingOriginalImageIds.value }
    delete next[imageId]
    loadingOriginalImageIds.value = next
  }
}

const clearOriginalImageCache = () => {
  Object.values(loadedOriginalImageUrls.value).forEach(revokeImageObjectUrl)
  loadedOriginalImageUrls.value = {}
  loadingOriginalImageIds.value = {}
}

const handleDownload = async () => {
  if (!props.isLoggedIn) {
    emit('login-required')
    return
  }
  if (!props.canDownload) {
    toast.error('该用户未开放下载')
    return
  }
  if (!currentImage.value) {
    toast.error('图片地址无效')
    return
  }
  const downloadUrl = await resolveProductImageDownloadUrl(currentImage.value)
  if (!downloadUrl) {
    toast.error('图片地址无效')
    return
  }
  const link = document.createElement('a')
  link.href = downloadUrl
  link.download = currentImage.value.name || 'image.jpg'
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleViewOriginal = async () => {
  if (!props.isLoggedIn) {
    emit('login-required')
    return
  }
  if (!canViewOriginalImage.value) {
    toast.warning('开通会员后可查看原图')
    return
  }
  const image = currentImage.value
  if (!image) return
  if (loadedOriginalImageUrls.value[image.id] || loadingOriginalImageIds.value[image.id]) return

  let resolvedUrl = ''
  setOriginalImageLoading(image.id, true)
  try {
    resolvedUrl = await resolveProductImageOriginalViewUrl(image)
    if (!resolvedUrl) {
      toast.error('原图暂不可查看')
      return
    }
    await preloadImageUrl(resolvedUrl)
    loadedOriginalImageUrls.value = {
      ...loadedOriginalImageUrls.value,
      [image.id]: resolvedUrl,
    }
  } catch (error: any) {
    revokeImageObjectUrl(resolvedUrl)
    toast.error(error?.message || '原图暂不可查看')
  } finally {
    setOriginalImageLoading(image.id, false)
  }
}

onUnmounted(() => {
  clearOriginalImageCache()
})
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[92vw] h-[88vh] p-0 overflow-hidden border-0 bg-black text-white shadow-2xl">
      <div class="flex h-full flex-col">
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-white/10 bg-black/70 px-4">
          <div class="min-w-0">
            <p class="truncate text-sm font-medium">{{ currentImage?.name || '图片预览' }}</p>
            <p class="text-xs text-white/60">{{ imageTypeLabel }} {{ currentIndex + 1 }} / {{ images.length }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="ghost"
              size="sm"
              class="text-white hover:bg-white/10"
              @click="handleDownload"
            >
              <SafeIcon name="Download" :size="16" class="mr-1" />
              下载
            </Button>
            <Button
              variant="ghost"
              size="sm"
              class="text-white hover:bg-white/10"
              :disabled="isCurrentOriginalLoading || isCurrentOriginalLoaded"
              @click="handleViewOriginal"
            >
              <SafeIcon
                :name="isCurrentOriginalLoading ? 'Loader2' : isCurrentOriginalLoaded ? 'Check' : 'ExternalLink'"
                :size="16"
                :class="isCurrentOriginalLoading ? 'mr-1 animate-spin' : 'mr-1'"
              />
              {{ isCurrentOriginalLoading ? '加载中...' : isCurrentOriginalLoaded ? '已加载原图' : '原图' }}
            </Button>
          </div>
        </div>

        <div class="relative flex min-h-0 flex-1 items-center justify-center bg-black">
          <img
            v-if="currentImage"
            :src="currentPreviewUrl"
            :alt="currentImage.name"
            class="max-h-full max-w-full object-contain transition-[opacity,transform] duration-200"
            :class="isCurrentOriginalLoading ? 'opacity-45' : 'opacity-100'"
            :style="imageTransformStyle"
            draggable="false"
            @contextmenu.prevent
          />
          <div
            v-if="currentImage"
            class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1 rounded-full bg-black/55 p-1.5 text-white shadow-lg backdrop-blur-sm"
          >
            <Button
              variant="ghost"
              size="icon"
              class="h-9 w-9 rounded-full text-white hover:bg-white/10"
              :disabled="isCurrentOriginalLoading || imageScale <= 0.5"
              @click="zoomOut"
            >
              <SafeIcon name="ZoomOut" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-9 w-9 rounded-full text-white hover:bg-white/10"
              :disabled="isCurrentOriginalLoading || imageScale >= 3"
              @click="zoomIn"
            >
              <SafeIcon name="ZoomIn" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-9 w-9 rounded-full text-white hover:bg-white/10"
              :disabled="isCurrentOriginalLoading"
              @click="rotateImage"
            >
              <SafeIcon name="RotateCw" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-9 w-9 rounded-full text-white hover:bg-white/10"
              :disabled="isCurrentOriginalLoading"
              @click="resetImageTransform"
            >
              <SafeIcon name="RefreshCcw" :size="18" />
            </Button>
          </div>
          <div v-else class="text-sm text-white/60">暂无图片</div>
          <div
            v-if="isCurrentOriginalLoading"
            class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-black/60 text-sm text-white backdrop-blur-sm"
          >
            <SafeIcon name="Loader2" :size="30" class="animate-spin text-white/80" />
            <span>正在加载原图...</span>
          </div>

          <Button
            v-if="canPrevious"
            variant="ghost"
            size="icon"
            class="absolute left-4 top-1/2 h-11 w-11 -translate-y-1/2 rounded-full bg-black/50 text-white hover:bg-black/70"
            :disabled="isCurrentOriginalLoading"
            @click="goPrevious"
          >
            <SafeIcon name="ChevronLeft" :size="30" />
          </Button>
          <Button
            v-if="canNext"
            variant="ghost"
            size="icon"
            class="absolute right-4 top-1/2 h-11 w-11 -translate-y-1/2 rounded-full bg-black/50 text-white hover:bg-black/70"
            :disabled="isCurrentOriginalLoading"
            @click="goNext"
          >
            <SafeIcon name="ChevronRight" :size="30" />
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
