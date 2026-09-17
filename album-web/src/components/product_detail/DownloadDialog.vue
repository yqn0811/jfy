
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
import type { ProductImageData } from '@/data/ProductImageData'

interface Props {
  open: boolean
  productId: string
  images?: ProductImageData[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const selectedImages = ref<Set<string>>(new Set())

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

const handleDownload = () => {
  if (selectedImageList.value.length === 0) {
    toast.error('请选择至少一张图片')
    return
  }

  const selectedList = selectedImageList.value
  toast.success(`已开始下载 ${selectedList.length} 张图片`)

  selectedList
    .forEach((image, index) => {
      window.setTimeout(() => {
        const link = document.createElement('a')
        link.href = image.url
        link.download = image.name || `product-${props.productId}-${index + 1}.jpg`
        link.target = '_blank'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      }, index * 150)
    })

  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-[720px] flex flex-col max-h-[80vh]">
      <DialogHeader class="flex-shrink-0">
        <DialogTitle>下载图片</DialogTitle>
        <DialogDescription>
          选择要下载的图片
        </DialogDescription>
      </DialogHeader>

      <!-- Scrollable content -->
      <div class="flex-1 overflow-y-auto min-h-0 space-y-4 py-4">
        <!-- Select All -->
        <div class="px-6">
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
        <div v-if="colorChartImages.length > 0" class="px-6 space-y-2">
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
                :src="image.thumbnailUrl || image.url"
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
        <div v-if="detailChartImages.length > 0" class="px-6 space-y-2 border-t pt-4">
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
                :src="image.thumbnailUrl || image.url"
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
          :disabled="selectedImageList.length === 0"
          @click="handleDownload"
          class="flex items-center gap-2"
        >
          <SafeIcon name="Download" :size="16" />
          下载 ({{ selectedImageList.length }})
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
