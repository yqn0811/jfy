<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'
import { pcApi } from '@/lib/api'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'
import type { ProductData } from '@/data/ProductData'

interface Props {
  open: boolean
  product: ProductData | null
  images: ProductImageData[]
  factoryUid: string
  existingSelection?: any
}

const props = withDefaults(defineProps<Props>(), {
  existingSelection: null,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved', selection: any): void
}>()

const selectedPicIds = ref<Set<string>>(new Set())
const isSaving = ref(false)
const brokenImageIds = ref<Set<string>>(new Set())

const visibleImages = computed(() => props.images.filter(image => image.type === 'colorChart'))
const selectedCount = computed(() => selectedPicIds.value.size)
const totalCount = computed(() => visibleImages.value.length)
const selectionId = computed(() =>
  String(props.existingSelection?.id || props.existingSelection?.info?.id || '')
)
const isEditMode = computed(() => !!selectionId.value)
const actionText = computed(() => `${isEditMode.value ? '更新选款单' : '加入选款单'}（${selectedCount.value}/${totalCount.value}）`)

const getExistingPicIds = () => {
  const sources = [
    props.existingSelection?.list,
    props.existingSelection?.selected_preview,
    props.existingSelection?.items,
  ]
  for (const source of sources) {
    if (Array.isArray(source) && source.length) {
      return source.map((item: any) => String(item.pic_id || item.id || '')).filter(Boolean)
    }
  }
  return []
}

watch(
  () => props.open,
  (open) => {
    if (!open) return
    selectedPicIds.value = new Set(getExistingPicIds())
    brokenImageIds.value = new Set()
  },
  { immediate: true }
)

watch(
  () => props.existingSelection,
  () => {
    if (props.open) selectedPicIds.value = new Set(getExistingPicIds())
  }
)

const setOpen = (value: boolean) => {
  emit('update:open', value)
}

const isSelected = (image: ProductImageData) => selectedPicIds.value.has(String(image.id))

const toggleImage = (image: ProductImageData) => {
  const id = String(image.id)
  const next = new Set(selectedPicIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selectedPicIds.value = next
}

const markBroken = (image: ProductImageData) => {
  brokenImageIds.value = new Set([...brokenImageIds.value, String(image.id)])
}

const syncSelectionImages = async () => {
  const before = new Set(getExistingPicIds())
  const after = selectedPicIds.value
  const toAdd = [...after].filter(id => !before.has(id))
  const toRemove = [...before].filter(id => !after.has(id))

  if (toAdd.length > 0) {
    await pcApi.addSelectionImages(selectionId.value, toAdd)
  }
  if (toRemove.length > 0) {
    await pcApi.removeSelectionImages(selectionId.value, toRemove)
  }
  return pcApi.getSelectionDetail(selectionId.value)
}

const handleSave = async () => {
  if (!props.product?.id) {
    toast.error('缺少产品信息')
    return
  }
  if (!props.factoryUid) {
    toast.error('缺少商家信息')
    return
  }
  if (selectedCount.value === 0) {
    toast.error('请至少选择一张花色图')
    return
  }

  isSaving.value = true
  try {
    let data = null
    if (isEditMode.value) {
      data = await syncSelectionImages()
    } else {
      const created = await pcApi.createSelection({
        product_id: props.product.id,
        pic_ids: [...selectedPicIds.value],
        factory_uid: props.factoryUid,
      })
      const createdId = String(created?.id || created?.info?.id || '')
      data = createdId ? await pcApi.getSelectionDetail(createdId) : created
      if (createdId && data) {
        data.id = createdId
      }
    }
    toast.success(isEditMode.value ? '选款单已更新' : '选款单已发送给商家')
    emit('saved', data)
    setOpen(false)
  } catch (error: any) {
    toast.error(error?.message || '选款单保存失败')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Dialog :open="props.open" @update:open="setOpen">
    <DialogScrollContent class="max-h-[88vh] max-w-[1080px] overflow-hidden p-0">
      <div class="flex max-h-[88vh] min-h-[620px] flex-col">
        <DialogHeader class="border-b border-border px-6 py-5">
          <DialogTitle>{{ isEditMode ? '编辑选款单' : '选择花色图' }}</DialogTitle>
          <p class="mt-1 truncate text-sm text-muted-foreground">{{ props.product?.name || '未命名产品' }}</p>
        </DialogHeader>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
          <div v-if="visibleImages.length === 0" class="flex h-72 flex-col items-center justify-center text-muted-foreground">
            <SafeIcon name="ImageOff" :size="34" class="mb-3" />
            暂无花色图
          </div>
          <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
            <button
              v-for="image in visibleImages"
              :key="image.id"
              type="button"
              :class="cn(
                'group overflow-hidden rounded-lg border bg-card text-left transition-colors',
                isSelected(image) ? 'border-primary ring-2 ring-primary/25' : 'border-border hover:border-primary/50'
              )"
              @click="toggleImage(image)"
            >
              <div class="relative aspect-square bg-muted">
                <img
                  v-if="productImageUrl(image, 'thumb') && !brokenImageIds.has(String(image.id))"
                  :src="productImageUrl(image, 'thumb')"
                  :alt="image.name"
                  class="h-full w-full object-cover transition group-hover:scale-[1.02]"
                  @error="markBroken(image)"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-muted text-muted-foreground">
                  <SafeIcon name="Image" :size="24" />
                </div>
                <span
                  :class="cn(
                    'absolute left-2 top-2 flex h-6 w-6 items-center justify-center rounded border bg-white shadow-sm',
                    isSelected(image) ? 'border-primary bg-primary text-primary-foreground' : 'border-border'
                  )"
                >
                  <SafeIcon v-if="isSelected(image)" name="Check" :size="14" />
                </span>
              </div>
              <div class="p-2">
                <p class="truncate text-xs font-medium">{{ image.name || '未命名花色' }}</p>
              </div>
            </button>
          </div>
        </div>

        <DialogFooter class="border-t border-border px-6 py-4">
          <Button variant="outline" @click="setOpen(false)">取消</Button>
          <Button class="gap-2" :disabled="isSaving || selectedCount === 0" @click="handleSave">
            <SafeIcon :name="isSaving ? 'Loader2' : 'CheckSquare'" :size="16" :class="isSaving ? 'animate-spin' : ''" />
            {{ actionText }}
          </Button>
        </DialogFooter>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>
