<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { pcApi } from '@/lib/api'
import { unwrapList } from '@/lib/jfyuntu-mappers'

type SelectionMode = 'my' | 'customer'

const props = withDefaults(defineProps<{ mode?: SelectionMode }>(), {
  mode: 'my',
})

const isLoading = ref(false)
const selections = ref<any[]>([])
const confirmOpen = ref(false)
const selectionToDelete = ref<any>(null)
const brokenPreviewImages = ref<Set<string>>(new Set())

const pageTitle = computed(() => props.mode === 'customer' ? '客户选款' : '我的选款')
const pageDesc = computed(() => props.mode === 'customer' ? '客户发送给你的选款单会展示在这里' : '你发送给商家的选款单会展示在这里')
const emptyTitle = computed(() => props.mode === 'customer' ? '暂无客户选款' : '暂无选款单')
const emptyDesc = computed(() => props.mode === 'customer' ? '客户从你的主页选款并发送后，会在这里看到记录' : '从商家分享主页选择花色并发送后，会在这里看到记录')

const loadSelections = async () => {
  isLoading.value = true
  try {
    const raw = props.mode === 'customer'
      ? await pcApi.getCustomerSelections({ limit: 50 })
      : await pcApi.getMySelections({ limit: 50 })
    selections.value = unwrapList(raw)
  } catch (error: any) {
    toast.error(error?.message || '选款单加载失败')
  } finally {
    isLoading.value = false
  }
}

const getTitle = (item: any) => item.title || item.name || `选款单 #${item.id}`
const getProductName = (item: any) => item.product?.name || item.product?.folder_name || '关联产品'
const getPeerName = (item: any) => {
  const peer = props.mode === 'customer' ? item.customer : item.factory
  return peer?.company_name || peer?.nickname || '未知用户'
}
const getPreviewImages = (item: any) => {
  const preview = item.selected_preview || item.preview || []
  if (Array.isArray(preview) && preview.length) return preview
  const covers = item.cover_img || []
  return Array.isArray(covers) ? covers.map((src: any) => typeof src === 'string' ? { src, imgurl: src } : src) : []
}
const getPreviewImageSrc = (image: any) =>
  image?.src || image?.imgurl || image?.url || image?.thumbnail_url || image?.thumbnailUrl || ''
const getPreviewImageKey = (item: any, image: any, index: number) =>
  `${item.id || 'selection'}:${index}:${getPreviewImageSrc(image)}`
const isPreviewImageBroken = (item: any, image: any, index: number) =>
  brokenPreviewImages.value.has(getPreviewImageKey(item, image, index))
const markPreviewImageBroken = (item: any, image: any, index: number) => {
  brokenPreviewImages.value = new Set([...brokenPreviewImages.value, getPreviewImageKey(item, image, index)])
}

const formatTime = (value: any) => {
  if (!value) return ''
  if (typeof value === 'string' && /[年/-]/.test(value)) return value
  const numberValue = Number(value)
  const date = Number.isFinite(numberValue)
    ? new Date(numberValue > 10_000_000_000 ? numberValue : numberValue * 1000)
    : new Date(String(value).replace(/-/g, '/'))
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const handleViewProduct = (item: any) => {
  const productId = item.product?.id || item.product_id
  const targetUserId = props.mode === 'customer'
    ? ''
    : (item.factory?.id || item.factory_uid || '')
  if (!productId) {
    toast.error('关联产品不存在')
    return
  }
  const params = new URLSearchParams({ productId: String(productId) })
  if (targetUserId) params.set('uid', String(targetUserId))
  window.location.href = `./share-home?${params.toString()}`
}

const openDeleteConfirm = (item: any) => {
  selectionToDelete.value = item
  confirmOpen.value = true
}

const handleDelete = async () => {
  if (!selectionToDelete.value?.id) return
  try {
    await pcApi.deleteSelection(String(selectionToDelete.value.id))
    toast.success('选款单已删除')
    confirmOpen.value = false
    selectionToDelete.value = null
    await loadSelections()
  } catch (error: any) {
    toast.error(error?.message || '删除失败')
  }
}

onMounted(loadSelections)
</script>

<template>
  <div class="page-body space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-page-title">{{ pageTitle }}</h1>
        <p class="text-caption mt-2">{{ pageDesc }}</p>
      </div>
      <Button variant="outline" class="gap-2" @click="loadSelections">
        <SafeIcon name="RefreshCw" :size="16" />
        刷新
      </Button>
    </div>

    <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <div v-else-if="selections.length === 0" class="py-16">
      <EmptyState icon="ClipboardList" :title="emptyTitle" :description="emptyDesc" />
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-2">
      <Card v-for="item in selections" :key="item.id" class="overflow-hidden">
        <CardContent class="p-5">
          <div class="flex gap-4">
            <div class="grid h-24 w-24 shrink-0 grid-cols-2 gap-1 overflow-hidden rounded-lg bg-muted">
              <div
                v-for="(image, index) in getPreviewImages(item).slice(0, 4)"
                :key="index"
                class="h-full w-full overflow-hidden"
              >
                <img
                  v-if="getPreviewImageSrc(image) && !isPreviewImageBroken(item, image, index)"
                  :src="getPreviewImageSrc(image)"
                  alt=""
                  class="h-full w-full object-cover"
                  @error="markPreviewImageBroken(item, image, index)"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                  <SafeIcon name="Image" :size="18" class="text-muted-foreground" />
                </div>
              </div>
              <div v-if="getPreviewImages(item).length === 0" class="col-span-2 flex h-24 items-center justify-center">
                <SafeIcon name="Image" :size="24" class="text-muted-foreground" />
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h2 class="truncate text-base font-semibold">{{ getTitle(item) }}</h2>
                  <p class="mt-1 truncate text-sm text-muted-foreground">{{ getProductName(item) }}</p>
                </div>
                <span class="rounded bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                  {{ item.product_count || item.total_selected || 0 }} 款
                </span>
              </div>
              <div class="mt-3 grid gap-1 text-xs text-muted-foreground">
                <span>{{ props.mode === 'customer' ? '客户' : '商家' }}：{{ getPeerName(item) }}</span>
                <span>创建时间：{{ formatTime(item.display_time || item.create_time) || '-' }}</span>
              </div>
              <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" size="sm" class="gap-2" @click="handleViewProduct(item)">
                  <SafeIcon name="Eye" :size="14" />
                  查看产品
                </Button>
                <Button variant="outline" size="sm" class="gap-2 border-destructive/30 text-destructive hover:bg-destructive/5 hover:text-destructive" @click="openDeleteConfirm(item)">
                  <SafeIcon name="Trash2" :size="14" />
                  删除
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <ConfirmDialog
      :open="confirmOpen"
      title="删除选款单"
      description="确定要删除该选款单吗？"
      confirm-text="删除"
      cancel-text="取消"
      variant="destructive"
      @update:open="confirmOpen = $event"
      @confirm="handleDelete"
    />
  </div>
</template>
