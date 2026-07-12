
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import ImageViewerHeader from './ImageViewerHeader.vue'
import ImageViewerContent from './ImageViewerContent.vue'
import ImageViewerFooter from './ImageViewerFooter.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore, getCurrentUserId, pcApi } from '@/lib/api'
import { downloadUrl } from '@/lib/download'
import { isVipMember } from '@/lib/account'

interface ImageData {
  id: string
  url: string
  originalUrl: string
  downloadUrl: string
  name: string
  type: 'colorChart' | 'detailChart'
  isOriginalLarge: boolean
  sizeBytes: number
}

const isClient = ref(true)
const images = ref<ImageData[]>([])
const currentIndex = ref(0)
const productId = ref('')
const isAuthenticated = ref(false)
const canUseOriginal = ref(false)
const isLoadingOriginal = ref(false)
const showLoginDialog = ref(false)
const imageLoadError = ref(false)

const currentImage = computed(() => {
  const image = images.value[currentIndex.value]
  if (!image) return null
  return {
    ...image,
    index: currentIndex.value + 1,
    total: images.value.length,
  }
})

const handlePrevious = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    updateUrlParams()
  }
}

const handleNext = () => {
  if (currentIndex.value < images.value.length - 1) {
    currentIndex.value++
    updateUrlParams()
  }
}

const updateUrlParams = () => {
  const params = new URLSearchParams(window.location.search)
  params.set('currentIndex', currentIndex.value.toString())
  window.history.replaceState(null, '', `?${params.toString()}`)
}

const resolveCurrentDownloadUrl = async () => {
  const image = currentImage.value
  if (!image) return ''
  const entry = image.downloadUrl || image.originalUrl || image.url
  if (!entry) return ''
  if (/\/api\/user\/download\/original(?:\?|$)/.test(entry) && image.id) {
    const data = await pcApi.getOriginalDownloadUrl(image.id)
    return String(data?.download_url || data?.downloadUrl || data?.url || '')
  }
  return entry
}

const handleViewOriginal = async () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }
  if (!canUseOriginal.value) {
    toast.warning('开通会员后可查看原图')
    return
  }

  const url = await resolveCurrentDownloadUrl()
  if (!url) return
  if (url !== currentImage.value?.url) {
    const next = [...images.value]
    next[currentIndex.value] = { ...next[currentIndex.value], url }
    images.value = next
  }
  isLoadingOriginal.value = false
}

const handleDownload = async () => {
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
    return
  }
  if (!canUseOriginal.value) {
    toast.warning('开通会员后可下载原图')
    return
  }

  const url = await resolveCurrentDownloadUrl()
  if (!url) return
  downloadUrl(url, currentImage.value?.name || url.split('/').pop() || 'image.jpg')
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

const parseJsonArrayParam = (params: URLSearchParams, key: string) => {
  const raw = params.get(key)
  if (!raw) return params.getAll(key).filter(Boolean)
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed)) return parsed
  } catch {
    return raw.split(',').map(item => item.trim()).filter(Boolean)
  }
  return []
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })

  // 从URL读取参数
  const params = new URLSearchParams(window.location.search)
  const urls = parseJsonArrayParam(params, 'imageUrls').map(item => String(item || '')).filter(Boolean)
  const ids = parseJsonArrayParam(params, 'imageIds')
  const names = parseJsonArrayParam(params, 'imageNames')
  const sizes = parseJsonArrayParam(params, 'imageSizes')
  const originalUrls = parseJsonArrayParam(params, 'imageOriginalUrls')
  const downloadUrls = parseJsonArrayParam(params, 'imageDownloadUrls')
  const types = parseJsonArrayParam(params, 'imageTypes')
  const index = parseInt(params.get('currentIndex') || '0', 10)
  const pId = params.get('productId') || ''

  images.value = urls.map((url, itemIndex) => ({
    id: String(ids[itemIndex] || ''),
    url,
    originalUrl: String(originalUrls[itemIndex] || url),
    downloadUrl: String(downloadUrls[itemIndex] || originalUrls[itemIndex] || url),
    name: String(names[itemIndex] || url.split('/').pop() || 'image.jpg'),
    type: types[itemIndex] === 'detailChart' ? 'detailChart' : 'colorChart',
    isOriginalLarge: Number(sizes[itemIndex] || 0) > 3 * 1024 * 1024,
    sizeBytes: Number(sizes[itemIndex] || 0),
  }))
  currentIndex.value = Math.min(Math.max(index, 0), Math.max(images.value.length - 1, 0))
  productId.value = pId
  isAuthenticated.value = authStore.isLoggedIn()
  if (!isAuthenticated.value) {
    showLoginDialog.value = true
  } else {
    loadCurrentUserPermission()
  }

  window.addEventListener('keydown', handleKeyDown)
  return () => {
    window.removeEventListener('keydown', handleKeyDown)
  }
})

const loadCurrentUserPermission = async () => {
  try {
    let user = authStore.getUser<any>() || {}
    if (!getCurrentUserId(user)) {
      user = await pcApi.getCurrentUser()
      authStore.setUser(user)
    }
    canUseOriginal.value = isVipMember(user)
  } catch {
    canUseOriginal.value = false
  }
}

const handleLoginSuccess = () => {
  isAuthenticated.value = true
  showLoginDialog.value = false
  loadCurrentUserPermission()
}
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
        :can-go-next="currentIndex < images.length - 1"
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
      :total-images="images.length"
      :image-type="currentImage?.type || 'colorChart'"
    />

    <!-- Login Dialog -->
    <LoginDialog
      :open="showLoginDialog"
      @update:open="showLoginDialog = $event"
      @login-success="handleLoginSuccess"
    />
  </div>
</template>

<style scoped>
/* 全屏查看器样式 */
:deep(body) {
  overflow: hidden;
}
</style>
