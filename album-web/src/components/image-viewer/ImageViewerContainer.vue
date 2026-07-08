
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import ImageViewerHeader from './ImageViewerHeader.vue'
import ImageViewerContent from './ImageViewerContent.vue'
import ImageViewerFooter from './ImageViewerFooter.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore } from '@/lib/api'

interface ImageData {
  url: string
  name: string
  type: 'colorChart' | 'detailChart'
  isOriginalLarge: boolean
}

const isClient = ref(true)
const imageUrls = ref<string[]>([])
const currentIndex = ref(0)
const productId = ref('')
const isAuthenticated = ref(false)
const isLoadingOriginal = ref(false)
const showLoginDialog = ref(false)
const imageLoadError = ref(false)

const currentImage = computed(() => {
  if (imageUrls.value.length === 0) return null
  return {
    url: imageUrls.value[currentIndex.value],
    index: currentIndex.value + 1,
    total: imageUrls.value.length,
    type: 'colorChart'
  }
})

const handlePrevious = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    updateUrlParams()
  }
}

const handleNext = () => {
  if (currentIndex.value < imageUrls.value.length - 1) {
    currentIndex.value++
    updateUrlParams()
  }
}

const updateUrlParams = () => {
  const params = new URLSearchParams(window.location.search)
  params.set('currentIndex', currentIndex.value.toString())
  window.history.replaceState(null, '', `?${params.toString()}`)
}

const handleViewOriginal = () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }

  if (currentImage.value?.isOriginalLarge) {
    isLoadingOriginal.value = true
    setTimeout(() => {
      isLoadingOriginal.value = false
      toast.success('原图已加载')
    }, 1500)
  }
}

const handleDownload = () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }

  const url = currentImage.value?.url
  if (!url) return
  const link = document.createElement('a')
  link.href = url
  link.download = url.split('/').pop() || 'image.jpg'
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleClose = () => {
  window.history.back()
}

const handleRetry = () => {
  imageLoadError.value = false
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'ArrowLeft') {
    event.preventDefault()
    handlePrevious()
  } else if (event.key === 'ArrowRight') {
    event.preventDefault()
    handleNext()
  } else if (event.key === 'Escape') {
    event.preventDefault()
    handleClose()
  }
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })

  // 从URL读取参数
  const params = new URLSearchParams(window.location.search)
  const rawUrls = params.get('imageUrls')
  let urls: string[] = params.getAll('imageUrls')
  if (rawUrls) {
    try {
      const parsed = JSON.parse(rawUrls)
      if (Array.isArray(parsed)) urls = parsed.filter(Boolean)
    } catch {
      urls = rawUrls.split(',').map(item => item.trim()).filter(Boolean)
    }
  }
  const index = parseInt(params.get('currentIndex') || '0', 10)
  const pId = params.get('productId') || ''

  imageUrls.value = urls
  currentIndex.value = Math.min(Math.max(index, 0), imageUrls.value.length - 1)
  productId.value = pId
  isAuthenticated.value = authStore.isLoggedIn()
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
  }

  window.addEventListener('keydown', handleKeyDown)
  return () => {
    window.removeEventListener('keydown', handleKeyDown)
  }
})
</script>

<template>
  <div v-if="isClient" class="fixed inset-0 bg-black/95 z-50 flex flex-col">
    <!-- Header -->
    <ImageViewerHeader
      v-if="isAuthenticated"
      :image-name="currentImage?.url.split('/').pop() || '图片预览'"
      :is-loading-original="isLoadingOriginal"
      @view-original="handleViewOriginal"
      @download="handleDownload"
      @close="handleClose"
    />

    <!-- Content -->
    <div v-if="isAuthenticated" class="flex-1 overflow-hidden flex items-center justify-center relative">
      <ImageViewerContent
        v-if="!imageLoadError && currentImage"
        :image-url="currentImage.url"
        :image-index="currentImage.index"
        :total-images="currentImage.total"
        :can-go-prev="currentIndex > 0"
        :can-go-next="currentIndex < imageUrls.length - 1"
        @previous="handlePrevious"
        @next="handleNext"
        @load-error="imageLoadError = true"
      />

      <!-- Error State -->
      <div v-else-if="imageLoadError" class="flex flex-col items-center justify-center gap-4">
        <div class="text-white text-center">
          <p class="text-lg font-medium mb-2">图片加载失败</p>
          <p class="text-sm text-white/60">请检查网络连接后重试</p>
        </div>
        <button
          @click="handleRetry"
          class="px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
        >
          重新加载
        </button>
      </div>
    </div>

    <!-- Footer -->
    <ImageViewerFooter
      v-if="isAuthenticated"
      :current-index="currentIndex + 1"
      :total-images="imageUrls.length"
      :image-type="currentImage?.type || 'colorChart'"
    />

    <!-- Login Dialog -->
    <LoginDialog
      :open="showLoginDialog"
      @update:open="showLoginDialog = $event"
      @login-success="isAuthenticated = true"
    />
  </div>
</template>

<style scoped>
/* 全屏查看器样式 */
:deep(body) {
  overflow: hidden;
}
</style>
