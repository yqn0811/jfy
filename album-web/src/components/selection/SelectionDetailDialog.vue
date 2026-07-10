<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { pcApi } from '@/lib/api'
import { pickImage } from '@/lib/jfyuntu-mappers'
import { buildSelectionProductImageMap, pickSelectionImageList } from '@/lib/selection-images'

interface Props {
  open: boolean
  selectionId?: string
  fallback?: any
}

const props = withDefaults(defineProps<Props>(), {
  selectionId: '',
  fallback: null,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const isLoading = ref(false)
const detail = ref<any>(null)
const brokenImages = ref<Set<string>>(new Set())

const info = computed(() => detail.value?.info || detail.value || props.fallback || {})
const product = computed(() =>
  detail.value?.product_summary ||
  detail.value?.product ||
  props.fallback?.product_summary ||
  props.fallback?.product ||
  props.fallback?.detail?.product_summary ||
  props.fallback?.detail?.product ||
  {}
)
const pictures = computed(() => {
  const productImageMap = buildSelectionProductImageMap(
    detail.value?.product,
    detail.value?.product_summary,
    props.fallback?.product,
    props.fallback?.product_summary,
    props.fallback?.detail?.product,
    props.fallback?.detail?.product_summary
  )
  return pickSelectionImageList(
    productImageMap,
    detail.value?.list ||
      detail.value?.selected_preview,
    detail.value?.grouped_pictures?.variant_pictures,
    props.fallback?.selected_preview,
    props.fallback?.list ||
      props.fallback?.detail?.list,
    props.fallback?.detail?.selected_preview,
    props.fallback?.detail?.grouped_pictures?.variant_pictures ||
      props.fallback?.cover_img,
    detail.value?.cover_img
  )
})
const title = computed(() => info.value?.title || info.value?.name || props.fallback?.title || props.fallback?.name || '选款单')

const getImageSrc = (image: any) => pickImage(image)
const getImageName = (image: any) => image?.pic_name || image?.name || '未命名花色'
const getImageKey = (image: any, index: number) => `${image?.id || image?.pic_id || index}:${getImageSrc(image)}`
const isBroken = (image: any, index: number) => brokenImages.value.has(getImageKey(image, index))
const markBroken = (image: any, index: number) => {
  brokenImages.value = new Set([...brokenImages.value, getImageKey(image, index)])
}

const loadDetail = async () => {
  if (!props.selectionId) return
  isLoading.value = true
  try {
    detail.value = await pcApi.getSelectionDetail(props.selectionId)
  } catch (error: any) {
    toast.error(error?.message || '选款单详情加载失败')
  } finally {
    isLoading.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    brokenImages.value = new Set()
    detail.value = props.fallback || null
    loadDetail()
  }
)
</script>

<template>
  <Dialog :open="props.open" @update:open="(value) => emit('update:open', value)">
    <DialogScrollContent class="max-h-[88vh] max-w-[1040px] overflow-hidden p-0">
      <div class="flex max-h-[88vh] min-h-[560px] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <DialogTitle>{{ title }}</DialogTitle>
          <p class="mt-1 truncate text-sm text-muted-foreground">{{ product?.name || product?.folder_name || '关联产品' }}</p>
        </DialogHeader>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
          <div v-if="isLoading" class="flex h-64 items-center justify-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="22" class="mr-2 animate-spin" />
            加载中...
          </div>
          <div v-else-if="pictures.length === 0" class="flex h-64 flex-col items-center justify-center text-muted-foreground">
            <SafeIcon name="ImageOff" :size="34" class="mb-3" />
            暂无选中花色
          </div>
          <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
            <div v-for="(image, index) in pictures" :key="getImageKey(image, index)" class="overflow-hidden rounded-lg border border-border bg-card">
              <div class="aspect-square bg-muted">
                <img
                  v-if="getImageSrc(image) && !isBroken(image, index)"
                  :src="getImageSrc(image)"
                  :alt="getImageName(image)"
                  class="h-full w-full object-cover"
                  @error="markBroken(image, index)"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-muted text-muted-foreground">
                  <SafeIcon name="Image" :size="24" />
                </div>
              </div>
              <p class="truncate p-2 text-xs font-medium">{{ getImageName(image) }}</p>
            </div>
          </div>
        </div>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>
