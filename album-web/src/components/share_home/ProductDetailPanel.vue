
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ShareDialog from '@/components/product_detail/ShareDialog.vue'
import DownloadDialog from '@/components/product_detail/DownloadDialog.vue'
import ProductImageGallery from '@/components/product_detail/ProductImageGallery.vue'
import ImagePreviewDialog from '@/components/product_detail/ImagePreviewDialog.vue'
import { pcApi } from '@/lib/api'
import { mapProductDetail, mapProductImagesFromDetail } from '@/lib/jfyuntu-mappers'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

interface Props {
  productId: string
  targetUserId: string
  shareCode?: string
  isLoggedIn: boolean
  currentUserId: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'back'): void
  (e: 'login-required'): void
  (e: 'login-success'): void
}>()

const product = ref<ProductData | null>(null)
const productImages = ref<ProductImageData[]>([])
const isLoading = ref(false)
const isProductFavorited = ref(false)
const showShareDialog = ref(false)
const showDownloadDialog = ref(false)
const showImagePreview = ref(false)
const previewImages = ref<ProductImageData[]>([])
const previewIndex = ref(0)

const colorImages = computed(() => productImages.value.filter(item => item.type === 'colorChart'))
const detailImages = computed(() => productImages.value.filter(item => item.type === 'detailChart'))
const isOwnerView = computed(() => !!props.currentUserId && props.currentUserId === product.value?.ownerUserId)
const canDownload = computed(() => !!product.value && (product.value.allowDownload === true || isOwnerView.value))
const downloadableImages = computed(() => {
  if (!product.value) return []
  if (product.value.hideDetailImage && !isOwnerView.value) return colorImages.value
  return productImages.value
})
const homeTarget = computed(() => ({
  targetUserId: props.targetUserId,
  shareCode: props.shareCode || '',
}))

const loadProduct = async () => {
  if (!props.productId) return
  isLoading.value = true
  product.value = null
  productImages.value = []
  try {
    const raw = await pcApi.getHomeProductDetail(homeTarget.value, props.productId)
    const detail = raw?.folder_info || raw?.product || raw
    product.value = mapProductDetail(raw, props.targetUserId)
    productImages.value = mapProductImagesFromDetail(raw, product.value.id)
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
watch(() => props.productId, loadProduct)

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
  if (!downloadableImages.value.length) {
    toast.error('暂无可下载图片')
    return
  }
  if (!canDownload.value) {
    toast.error('商户未开放保存权限')
    return
  }
  showDownloadDialog.value = true
}

const handleViewImage = (imageIndex: number, type: 'colorChart' | 'detailChart') => {
  if (!product.value) return
  previewImages.value = type === 'colorChart' ? colorImages.value : detailImages.value
  previewIndex.value = imageIndex
  showImagePreview.value = true
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-border pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-3">
        <Button variant="outline" size="sm" class="w-fit" @click="emit('back')">
          <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
          返回列表
        </Button>
        <div>
          <h2 class="text-page-title">
            {{ product?.name || (isLoading ? '产品详情' : '未命名产品') }}
          </h2>
          <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
            {{ product?.intro || '暂无产品简介' }}
          </p>
        </div>
        <div v-if="product" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
          <Badge variant="secondary">花色图 {{ colorImages.length }} 张</Badge>
          <Badge variant="secondary">详情图 {{ detailImages.length }} 张</Badge>
          <span v-if="product.updatedAt">更新时间 {{ product.updatedAt }}</span>
        </div>
      </div>
      <div v-if="product" class="flex flex-wrap gap-2 sm:justify-end">
        <Button
          :variant="isProductFavorited ? 'default' : 'outline'"
          size="sm"
          @click="handleFavoriteProduct"
        >
          <SafeIcon :name="isProductFavorited ? 'Heart' : 'Heart'" :size="16" class="mr-2" :fill="isProductFavorited ? 'currentColor' : 'none'" />
          {{ isProductFavorited ? '已收藏' : '收藏' }}
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="handleShare"
        >
          <SafeIcon name="Share2" :size="16" class="mr-2" />
          分享
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="handleDownload"
        >
          <SafeIcon name="Download" :size="16" class="mr-2" />
          {{ canDownload ? '下载' : '不可下载' }}
        </Button>
      </div>
    </div>

    <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <div v-else-if="product" class="space-y-8">
      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-section-title font-semibold">花色图</h3>
          <Badge variant="secondary" class="text-xs">{{ colorImages.length }} 张</Badge>
        </div>
        <ProductImageGallery
          v-if="colorImages.length > 0"
          :images="colorImages"
          @image-click="(index) => handleViewImage(index, 'colorChart')"
        />
        <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
          <SafeIcon name="Image" :size="30" class="mx-auto mb-2 opacity-50" />
          暂无花色图
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-section-title font-semibold">详情图</h3>
          <Badge variant="secondary" class="text-xs">{{ detailImages.length }} 张</Badge>
        </div>
        <div v-if="product.hideDetailImage && !isOwnerView" class="rounded-lg border border-border bg-muted/40 p-4">
          <p class="text-sm font-medium">分享者已隐藏详情图</p>
          <p class="mt-1 text-xs text-muted-foreground">当前产品的详情图仅分享者本人可见</p>
        </div>
        <ProductImageGallery
          v-else-if="detailImages.length > 0"
          :images="detailImages"
          @image-click="(index) => handleViewImage(index, 'detailChart')"
        />
        <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
          <SafeIcon name="Image" :size="30" class="mx-auto mb-2 opacity-50" />
          暂无详情图
        </div>
      </section>
    </div>
  </div>

  <ShareDialog
    v-if="product"
    :open="showShareDialog"
    :product-id="product.id"
    :target-user-id="targetUserId"
    :share-code="shareCode"
    @update:open="showShareDialog = $event"
  />
  <DownloadDialog
    v-if="product"
    :open="showDownloadDialog"
    :product-id="product.id"
    :images="downloadableImages"
    :can-download="canDownload"
    @update:open="showDownloadDialog = $event"
  />
  <ImagePreviewDialog
    :open="showImagePreview"
    :images="previewImages"
    :current-index="previewIndex"
    :can-download="canDownload"
    :is-logged-in="isLoggedIn"
    @update:open="showImagePreview = $event"
    @update:current-index="previewIndex = $event"
    @login-required="emit('login-required')"
  />
</template>
