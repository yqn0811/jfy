
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ShareDialog from '@/components/product_detail/ShareDialog.vue'
import { getUrlHomeTarget, pcApi } from '@/lib/api'
import { mapProduct, mapProductImagesFromDetail } from '@/lib/jfyuntu-mappers'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

interface Props {
  open: boolean
  productId: string
  isLoggedIn: boolean
  currentUserId: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'login-required'): void
  (e: 'login-success'): void
}>()

const product = ref<ProductData | null>(null)
const productImages = ref<ProductImageData[]>([])
const isLoading = ref(false)
const isProductFavorited = ref(false)
const showShareDialog = ref(false)

const colorImages = computed(() => productImages.value.filter(item => item.type === 'colorChart'))
const detailImages = computed(() => productImages.value.filter(item => item.type === 'detailChart'))
const shareTarget = computed(() => getUrlHomeTarget())

const loadProduct = async () => {
  isLoading.value = true
  try {
    const target = getUrlHomeTarget()
    const raw = await pcApi.getHomeProductDetail(target, props.productId)
    const detail = raw?.folder_info || raw?.product || raw
    product.value = mapProduct(detail, target.targetUserId)
    productImages.value = mapProductImagesFromDetail(detail, product.value.id)
    isProductFavorited.value = Number(detail?.is_collect || detail?.isCollect || 0) === 1
    if (props.isLoggedIn) {
      pcApi.addVisit('product', product.value.id).catch(() => {})
    }
  } catch (error: any) {
    toast.error(error?.message || '产品加载失败')
  } finally {
    isLoading.value = false
  }
}

onMounted(loadProduct)

const handleFavoriteProduct = async () => {
  if (!props.isLoggedIn) {
    emit('login-required')
    return
  }

  if (!product.value) return

  try {
    await pcApi.toggleFavorite('product', product.value.id, !isProductFavorited.value)
    isProductFavorited.value = !isProductFavorited.value
    toast.success(isProductFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const handleShare = () => {
  if (!product.value) return
  showShareDialog.value = true
}

const handleDownload = () => {
  if (!props.isLoggedIn) {
    emit('login-required')
    return
  }
  toast.success('下载已开始')
}

const handleViewImage = (imageIndex: number, type: 'color' | 'detail') => {
  if (!product.value) return
  const urls = type === 'color' 
    ? colorImages.value.map(item => item.url)
    : detailImages.value.map(item => item.url)
  const params = new URLSearchParams({
    imageUrls: JSON.stringify(urls),
    currentIndex: String(imageIndex),
    productId: product.value.id,
  })
  const target = getUrlHomeTarget()
  if (target.shareCode) params.set('code', target.shareCode)
  else if (target.targetUserId) params.set('uid', target.targetUserId)
  window.location.href = `./image-viewer?${params.toString()}`
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogScrollContent class="max-h-[90vh] max-w-[720px] overflow-hidden p-0">
      <div class="flex max-h-[90vh] flex-col">
      <DialogHeader class="flex-shrink-0 border-b border-border px-6 py-5 text-left">
        <DialogTitle class="text-2xl">产品详情</DialogTitle>
        <DialogDescription v-if="product">
          {{ product.name || '未命名产品' }}
        </DialogDescription>
      </DialogHeader>

      <!-- 可滚动内容区 -->
      <div class="min-h-0 flex-1 overflow-y-auto py-5">
        <div v-if="isLoading" class="py-12 text-center text-muted-foreground">
          <SafeIcon name="Loader2" :size="22" class="mx-auto mb-2 animate-spin" />
          加载中...
        </div>

        <div v-else-if="product" class="space-y-8 px-8">
          <!-- 产品信息 -->
          <div class="space-y-3">
            <div>
              <h3 class="text-section-title mb-2">{{ product.name || '未命名产品' }}</h3>
              <p class="text-caption">{{ product.intro || '暂无产品简介' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div class="p-2 bg-muted/50 rounded">
                <p class="text-muted-foreground text-xs">所属分类</p>
                <p class="font-medium">分类名称</p>
              </div>
              <div class="p-2 bg-muted/50 rounded">
                <p class="text-muted-foreground text-xs">更新时间</p>
                <p class="font-medium text-xs">{{ product.updatedAt }}</p>
              </div>
            </div>
          </div>

          <!-- 花色图区 -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-item-title font-medium">花色图</h4>
              <Badge variant="secondary" class="text-xs">{{ colorImages.length }} 张</Badge>
            </div>
            <div v-if="colorImages.length > 0" class="grid grid-cols-3 gap-2">
              <div
                v-for="(image, index) in colorImages.slice(0, 6)"
                :key="image.id"
                class="aspect-square bg-muted rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                @click="handleViewImage(index, 'color')"
              >
                <img
                  :src="image.thumbnailUrl || image.url"
                  :alt="image.name"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>
            <div v-else class="text-center py-6 text-muted-foreground">
              暂无花色图
            </div>
          </div>

          <!-- 详情图区 -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-item-title font-medium">详情图</h4>
              <Badge variant="secondary" class="text-xs">{{ detailImages.length }} 张</Badge>
            </div>
            <div v-if="product.hideDetailImage" class="p-3 bg-warning/10 border border-warning/30 rounded-lg">
              <p class="text-xs text-warning font-medium">分享者已隐藏详情图</p>
              <p class="text-xs text-warning/70 mt-1">当前产品的详情图仅分享者本人可见</p>
            </div>
            <div v-else-if="detailImages.length > 0" class="grid grid-cols-3 gap-2">
              <div
                v-for="(image, index) in detailImages.slice(0, 6)"
                :key="image.id"
                class="aspect-square bg-muted rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                @click="handleViewImage(index, 'detail')"
              >
                <img
                  :src="image.thumbnailUrl || image.url"
                  :alt="image.name"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>
            <div v-else class="text-center py-6 text-muted-foreground">
              暂无详情图
            </div>
          </div>
        </div>
      </div>

      <!-- 操作栏 -->
      <DialogFooter class="flex-shrink-0 border-t border-border px-6 py-4 sm:justify-stretch">
        <div class="grid w-full grid-cols-3 gap-3">
        <Button
          :variant="isProductFavorited ? 'default' : 'outline'"
          size="sm"
          @click="handleFavoriteProduct"
          class="h-11"
        >
          <SafeIcon :name="isProductFavorited ? 'Heart' : 'Heart'" :size="16" class="mr-2" :fill="isProductFavorited ? 'currentColor' : 'none'" />
          {{ isProductFavorited ? '已收藏' : '收藏' }}
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="handleShare"
          class="h-11"
        >
          <SafeIcon name="Share2" :size="16" class="mr-2" />
          分享
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="handleDownload"
          class="h-11"
        >
          <SafeIcon name="Download" :size="16" class="mr-2" />
          下载
        </Button>
        </div>
      </DialogFooter>
      </div>
    </DialogScrollContent>
  </Dialog>

  <ShareDialog
    v-if="product"
    :open="showShareDialog"
    :product-id="product.id"
    :target-user-id="shareTarget.targetUserId"
    :share-code="shareTarget.shareCode"
    @update:open="showShareDialog = $event"
  />
</template>
