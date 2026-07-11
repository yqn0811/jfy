<script setup lang="ts">
import { computed } from 'vue'
import { toast } from 'vue-sonner'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { resolveProductImageDownloadUrl } from '@/lib/download'

interface Props {
  open: boolean
  images: ProductImageData[]
  currentIndex: number
  canDownload?: boolean
  isLoggedIn?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canDownload: false,
  isLoggedIn: true,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'update:currentIndex', value: number): void
  (e: 'login-required'): void
}>()

const currentImage = computed(() => props.images[props.currentIndex] || null)
const currentPreviewUrl = computed(() => productImageUrl(currentImage.value, 'preview'))
const canPrevious = computed(() => props.currentIndex > 0)
const canNext = computed(() => props.currentIndex < props.images.length - 1)

const imageTypeLabel = computed(() => currentImage.value?.type === 'detailChart' ? '详情图' : '花色图')

const goPrevious = () => {
  if (canPrevious.value) emit('update:currentIndex', props.currentIndex - 1)
}

const goNext = () => {
  if (canNext.value) emit('update:currentIndex', props.currentIndex + 1)
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

const handleViewOriginal = () => {
  if (!props.isLoggedIn) {
    emit('login-required')
    return
  }
  if (!props.canDownload) {
    toast.warning('开通会员后可查看原图')
    return
  }
  if (!currentImage.value) return
  resolveProductImageDownloadUrl(currentImage.value)
    .then((url) => {
      if (url) window.open(url, '_blank')
    })
    .catch((error: any) => {
      toast.error(error?.message || '原图暂不可查看')
    })
}
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
              @click="handleViewOriginal"
            >
              <SafeIcon name="ExternalLink" :size="16" class="mr-1" />
              原图
            </Button>
          </div>
        </div>

        <div class="relative flex min-h-0 flex-1 items-center justify-center bg-black">
          <img
            v-if="currentImage"
            :src="currentPreviewUrl"
            :alt="currentImage.name"
            class="max-h-full max-w-full object-contain"
          />
          <div v-else class="text-sm text-white/60">暂无图片</div>

          <Button
            v-if="canPrevious"
            variant="ghost"
            size="icon"
            class="absolute left-4 top-1/2 h-11 w-11 -translate-y-1/2 rounded-full bg-black/50 text-white hover:bg-black/70"
            @click="goPrevious"
          >
            <SafeIcon name="ChevronLeft" :size="30" />
          </Button>
          <Button
            v-if="canNext"
            variant="ghost"
            size="icon"
            class="absolute right-4 top-1/2 h-11 w-11 -translate-y-1/2 rounded-full bg-black/50 text-white hover:bg-black/70"
            @click="goNext"
          >
            <SafeIcon name="ChevronRight" :size="30" />
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
