
<script setup lang="ts">
import { ref, computed } from 'vue'
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
const downloadFormat = ref<'original' | 'compressed'>('original')

const productImages = computed(() => {
  return props.images || []
})

const colorChartImages = computed(() => {
  return productImages.value.filter(img => img.type === 'colorChart')
})

const detailChartImages = computed(() => {
  return productImages.value.filter(img => img.type === 'detailChart')
})

const selectAll = computed({
  get: () => selectedImages.value.size === productImages.value.length && productImages.value.length > 0,
  set: (value) => {
    if (value) {
      selectedImages.value = new Set(productImages.value.map(img => img.id))
    } else {
      selectedImages.value.clear()
    }
  }
})

const toggleImage = (imageId: string) => {
  if (selectedImages.value.has(imageId)) {
    selectedImages.value.delete(imageId)
  } else {
    selectedImages.value.add(imageId)
  }
}

const handleDownload = () => {
  if (selectedImages.value.size === 0) {
    toast.error('请选择至少一张图片')
    return
  }

  const selectedCount = selectedImages.value.size
  const format = downloadFormat.value === 'original' ? '原图' : '压缩图'
  
  toast.success(`已开始下载 ${selectedCount} 张${format}`)

  productImages.value
    .filter(image => selectedImages.value.has(image.id))
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
    <DialogContent class="max-w-[600px] flex flex-col max-h-[80vh]">
      <DialogHeader class="flex-shrink-0">
        <DialogTitle>下载图片</DialogTitle>
        <DialogDescription>
          选择要下载的图片和格式
        </DialogDescription>
      </DialogHeader>

      <!-- Scrollable content -->
      <div class="flex-1 overflow-y-auto min-h-0 space-y-4 py-4">
        <!-- Download Format Selection -->
        <div class="space-y-2 px-6">
          <label class="text-sm font-medium">下载格式</label>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <input
                type="radio"
                id="format-original"
                v-model="downloadFormat"
                value="original"
                class="w-4 h-4"
              />
              <Label for="format-original" class="font-normal cursor-pointer">
                原图 (高清无损)
              </Label>
            </div>
            <div class="flex items-center gap-2">
              <input
                type="radio"
                id="format-compressed"
                v-model="downloadFormat"
                value="compressed"
                class="w-4 h-4"
              />
              <Label for="format-compressed" class="font-normal cursor-pointer">
                压缩图 (更小的文件)
              </Label>
            </div>
          </div>
        </div>

        <!-- Select All -->
        <div class="px-6 border-t pt-4">
          <div class="flex items-center gap-2">
            <Checkbox
              :checked="selectAll"
              @update:checked="selectAll = $event"
            />
            <Label class="font-medium cursor-pointer">
              全选 ({{ productImages.length }} 张)
            </Label>
          </div>
        </div>

        <!-- Color Chart Images -->
        <div v-if="colorChartImages.length > 0" class="px-6 space-y-2">
          <h4 class="text-sm font-semibold text-muted-foreground">花色图 ({{ colorChartImages.length }} 张)</h4>
          <div class="space-y-2">
            <div
              v-for="image in colorChartImages"
              :key="image.id"
              class="flex items-center gap-3 p-2 rounded hover:bg-muted/50 cursor-pointer"
              @click="toggleImage(image.id)"
            >
              <Checkbox
                :checked="selectedImages.has(image.id)"
                @update:checked="toggleImage(image.id)"
              />
              <img
                :src="image.thumbnailUrl"
                :alt="image.name"
                class="w-12 h-12 object-cover rounded border border-border"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ image.name }}</p>
                <p class="text-xs text-muted-foreground">{{ image.sizeLabel }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Detail Chart Images -->
        <div v-if="detailChartImages.length > 0" class="px-6 space-y-2 border-t pt-4">
          <h4 class="text-sm font-semibold text-muted-foreground">详情图 ({{ detailChartImages.length }} 张)</h4>
          <div class="space-y-2">
            <div
              v-for="image in detailChartImages"
              :key="image.id"
              class="flex items-center gap-3 p-2 rounded hover:bg-muted/50 cursor-pointer"
              @click="toggleImage(image.id)"
            >
              <Checkbox
                :checked="selectedImages.has(image.id)"
                @update:checked="toggleImage(image.id)"
              />
              <img
                :src="image.thumbnailUrl"
                :alt="image.name"
                class="w-12 h-12 object-cover rounded border border-border"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ image.name }}</p>
                <p class="text-xs text-muted-foreground">{{ image.sizeLabel }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <DialogFooter class="flex-shrink-0 border-t pt-4 px-6">
        <Button variant="outline" @click="emit('update:open', false)">
          取消
        </Button>
        <Button @click="handleDownload" class="flex items-center gap-2">
          <SafeIcon name="Download" :size="16" />
          下载 ({{ selectedImages.size }})
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
