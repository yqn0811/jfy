
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import { downloadProductImages, shouldDownloadImagesAsZip } from '@/lib/download'

interface Props {
  open: boolean
  productId: string
  images?: ProductImageData[]
  canDownload?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canDownload: true,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const selectedImages = ref<Set<string>>(new Set())
const isDownloading = ref(false)
const downloadProgress = ref('')

const productImages = computed(() => {
  return props.images || []
})

const colorChartImages = computed(() => {
  return productImages.value.filter(img => img.type === 'colorChart')
})

const detailChartImages = computed(() => {
  return productImages.value.filter(img => img.type === 'detailChart')
})

const imageKey = (image: ProductImageData) => String(image.id)

const isImageSelected = (image: ProductImageData) => {
  return selectedImages.value.has(imageKey(image))
}

const selectedImageList = computed(() => {
  return productImages.value.filter(isImageSelected)
})

const selectedCount = computed(() => selectedImageList.value.length)

const useZipDownload = computed(() => shouldDownloadImagesAsZip(selectedCount.value))

const downloadButtonText = computed(() => {
  const count = selectedCount.value
  if (isDownloading.value) {
    return downloadProgress.value || (useZipDownload.value ? '打包中...' : '下载中...')
  }
  return useZipDownload.value ? `下载 ZIP (${count})` : `下载图片 (${count})`
})

const selectAll = computed({
  get: () => productImages.value.length > 0 && productImages.value.every(isImageSelected),
  set: (value) => {
    if (value) {
      selectedImages.value = new Set(productImages.value.map(imageKey))
    } else {
      selectedImages.value = new Set()
    }
  }
})

const setImageSelected = (image: ProductImageData, checked: boolean) => {
  const key = imageKey(image)
  const next = new Set(selectedImages.value)
  if (checked) {
    next.add(key)
  } else {
    next.delete(key)
  }
  selectedImages.value = next
}

const toggleImage = (image: ProductImageData) => {
  setImageSelected(image, !isImageSelected(image))
}

const toggleSelectAll = () => {
  selectAll.value = !selectAll.value
}

watch(() => props.open, (open) => {
  if (!open) return
  selectedImages.value = new Set()
})

watch(productImages, (images) => {
  const validImageKeys = new Set(images.map(imageKey))
  selectedImages.value = new Set([...selectedImages.value].filter(id => validImageKeys.has(id)))
})

const handleDownload = async () => {
  if (!props.canDownload) {
    toast.error('商户未开放保存权限')
    return
  }
  if (selectedImageList.value.length === 0) {
    toast.error('请选择至少一张图片')
    return
  }

  const selectedList = selectedImageList.value
  isDownloading.value = true
  downloadProgress.value = useZipDownload.value ? '正在打包图片...' : '正在下载图片...'
  try {
    const mode = await downloadProductImages(
      selectedList,
      `product-${props.productId || 'images'}.zip`,
      (completed, total) => {
        downloadProgress.value = useZipDownload.value
          ? `正在打包 ${completed}/${total}`
          : `正在下载 ${completed}/${total}`
      }
    )
    toast.success(mode === 'zip' ? `已打包 ${selectedList.length} 张图片` : `已下载 ${selectedList.length} 张图片`)
    emit('update:open', false)
  } catch (error: any) {
    toast.error(error?.message || '下载失败，请稍后重试')
  } finally {
    isDownloading.value = false
    downloadProgress.value = ''
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[720px] flex flex-col max-h-[80vh]">
      <DialogHeader class="flex-shrink-0">
        <DialogTitle>下载图片</DialogTitle>
        <DialogDescription>
          选择要下载的图片，5 张及以上会打包为 ZIP 文件
        </DialogDescription>
      </DialogHeader>

      <!-- Scrollable content -->
      <div class="flex-1 overflow-y-auto min-h-0 space-y-4 py-4">
        <div v-if="!canDownload" class="mx-6 rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
          商户未开放保存权限，当前产品图片不可下载
        </div>

        <div v-else-if="productImages.length === 0" class="mx-6 rounded-lg border border-dashed border-border py-10 text-center text-muted-foreground">
          <SafeIcon name="ImageOff" :size="28" class="mx-auto mb-2 opacity-60" />
          暂无可下载图片
        </div>

        <!-- Select All -->
        <div v-if="canDownload && productImages.length > 0" class="px-6">
          <div
            role="checkbox"
            tabindex="0"
            :aria-checked="selectAll"
            class="flex w-fit cursor-pointer items-center gap-2 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            @click="toggleSelectAll"
            @keydown.enter.prevent="toggleSelectAll"
            @keydown.space.prevent="toggleSelectAll"
          >
            <Checkbox
              :model-value="selectAll"
              class="pointer-events-none"
            />
            <Label class="font-medium cursor-pointer">
              全选 ({{ productImages.length }} 张)
            </Label>
          </div>
        </div>

        <!-- Color Chart Images -->
        <div v-if="canDownload && colorChartImages.length > 0" class="px-6 space-y-2">
          <h4 class="text-sm font-semibold text-muted-foreground">花色图 ({{ colorChartImages.length }} 张)</h4>
          <div class="grid grid-cols-5 gap-3">
            <div
              v-for="image in colorChartImages"
              :key="imageKey(image)"
              role="button"
              tabindex="0"
              class="relative aspect-square overflow-hidden rounded-md border border-border bg-muted transition hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              :class="isImageSelected(image) ? 'border-primary ring-2 ring-primary' : ''"
              @click="toggleImage(image)"
              @keydown.enter.prevent="toggleImage(image)"
              @keydown.space.prevent="toggleImage(image)"
            >
              <img
                :src="productImageUrl(image, 'thumb')"
                :alt="image.name || '图片'"
                loading="lazy"
                class="h-full w-full object-cover"
              />
              <span class="absolute left-2 top-2 rounded bg-background/90 p-0.5 shadow">
                <Checkbox
                  :model-value="isImageSelected(image)"
                  class="pointer-events-none"
                />
              </span>
            </div>
          </div>
        </div>

        <!-- Detail Chart Images -->
        <div v-if="canDownload && detailChartImages.length > 0" class="px-6 space-y-2 border-t pt-4">
          <h4 class="text-sm font-semibold text-muted-foreground">详情图 ({{ detailChartImages.length }} 张)</h4>
          <div class="grid grid-cols-5 gap-3">
            <div
              v-for="image in detailChartImages"
              :key="imageKey(image)"
              role="button"
              tabindex="0"
              class="relative aspect-square overflow-hidden rounded-md border border-border bg-muted transition hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              :class="isImageSelected(image) ? 'border-primary ring-2 ring-primary' : ''"
              @click="toggleImage(image)"
              @keydown.enter.prevent="toggleImage(image)"
              @keydown.space.prevent="toggleImage(image)"
            >
              <img
                :src="productImageUrl(image, 'thumb')"
                :alt="image.name || '图片'"
                loading="lazy"
                class="h-full w-full object-cover"
              />
              <span class="absolute left-2 top-2 rounded bg-background/90 p-0.5 shadow">
                <Checkbox
                  :model-value="isImageSelected(image)"
                  class="pointer-events-none"
                />
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <DialogFooter class="flex-shrink-0 border-t pt-4 px-6">
        <Button variant="outline" @click="emit('update:open', false)">
          取消
        </Button>
        <Button
          :disabled="!canDownload || productImages.length === 0 || selectedImageList.length === 0 || isDownloading"
          @click="handleDownload"
          class="flex items-center gap-2"
        >
          <SafeIcon :name="isDownloading ? 'Loader2' : 'Download'" :size="16" :class="isDownloading ? 'animate-spin' : ''" />
          {{ downloadButtonText }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
