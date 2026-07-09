
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ProductImageGallery from './ProductImageGallery.vue'
import ShareDialog from './ShareDialog.vue'
import DownloadDialog from './DownloadDialog.vue'
import ImagePreviewDialog from './ImagePreviewDialog.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore, getUrlHomeTarget, pcApi } from '@/lib/api'
import { isVipMember } from '@/lib/account'
import { mapProductDetail, mapProductImagesFromDetail } from '@/lib/jfyuntu-mappers'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

const product = ref<ProductData | null>(null)
const productImages = ref<ProductImageData[]>([])
const isClient = ref(true)
const isLoading = ref(false)
const targetUserId = ref('')
const productId = ref('')
const shareCode = ref('')

const isFavorited = ref(false)
const isLoggedIn = ref(false)
const isOwnerView = ref(false)
const currentUser = ref<any>({})
const showLoginDialog = ref(false)
const showShareDialog = ref(false)
const showDownloadDialog = ref(false)
const showImagePreview = ref(false)
const previewImages = ref<ProductImageData[]>([])
const previewIndex = ref(0)

// Computed properties
const colorChartImages = computed(() =>
  productImages.value.filter(img => img.type === 'colorChart')
)

const detailChartImages = computed(() =>
  productImages.value.filter(img => img.type === 'detailChart')
)

const canUseOriginalImage = computed(() => isOwnerView.value || isVipMember(currentUser.value))

const canDownload = computed(() => {
  if (!product.value) return false
  return (product.value.allowDownload === true || isOwnerView.value) && canUseOriginalImage.value
})

const downloadableImages = computed(() => {
  if (!product.value) return []
  if (product.value.hideDetailImage && !isOwnerView.value) return colorChartImages.value
  return productImages.value
})

const homeTarget = computed(() => ({
  targetUserId: targetUserId.value,
  shareCode: shareCode.value,
}))

const loadProduct = async () => {
  if (!productId.value) {
    toast.error('缺少产品信息')
    return
  }
  isLoggedIn.value = authStore.isLoggedIn()
  currentUser.value = authStore.getUser<any>() || {}

  isLoading.value = true
  try {
    const raw = targetUserId.value || shareCode.value
      ? await pcApi.getHomeProductDetail(homeTarget.value, productId.value)
      : await pcApi.getProductEditDetail(productId.value)
    const detail = raw?.folder_info || raw?.product || raw
    product.value = mapProductDetail(raw, targetUserId.value)
    productImages.value = mapProductImagesFromDetail(raw, product.value.id)
    isFavorited.value = Number(detail?.is_collect || detail?.isCollect || 0) === 1
    const currentUid = String(currentUser.value?.id || currentUser.value?.uid || '')
    isOwnerView.value = !!currentUid && currentUid === String(detail?.uid || product.value.ownerUserId)
    if (isLoggedIn.value) pcApi.addVisit('product', product.value.id).catch(() => {})
  } catch (error: any) {
    toast.error(error?.message || '产品加载失败')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  isClient.value = false

  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    productId.value = params.get('productId') || params.get('product_id') || ''
    const target = getUrlHomeTarget()
    targetUserId.value = target.targetUserId
    shareCode.value = target.shareCode
    isLoggedIn.value = authStore.isLoggedIn()
    isClient.value = true
    loadProduct()
  })
})

// Handlers
const handleFavorite = async () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }

  if (!product.value) return

  try {
    await pcApi.toggleFavorite('product', product.value.id, !isFavorited.value)
    isFavorited.value = !isFavorited.value
    toast.success(isFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const handleShare = () => {
  showShareDialog.value = true
}

const handleDownload = () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }

  if (!canDownload.value) {
    if (!canUseOriginalImage.value) {
      toast.warning('开通会员后可下载原图')
    } else {
      toast.error('分享者未开放图片下载')
    }
    return
  }

  if (downloadableImages.value.length === 0) {
    toast.error('暂无可下载图片')
    return
  }

  showDownloadDialog.value = true
}

const handleImageClick = (imageUrl: string, index: number, type: 'colorChart' | 'detailChart') => {
  previewImages.value = type === 'colorChart' ? colorChartImages.value : detailChartImages.value
  previewIndex.value = index
  showImagePreview.value = true
}

const handleBack = () => {
  if (typeof window !== 'undefined' && window.history.length > 1) {
    window.history.back()
  } else {
    window.location.href = targetUserId.value
      ? `./share-home.html?uid=${encodeURIComponent(targetUserId.value)}`
      : shareCode.value
        ? `./share-home.html?code=${encodeURIComponent(shareCode.value)}`
      : './share-home.html'
  }
}

const handleLoginSuccess = () => {
  isLoggedIn.value = true
  toast.success('登录成功')
  loadProduct()
}
</script>

<template>
  <div class="mx-auto max-w-screen-2xl space-y-8">
    <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <template v-else-if="product">
      <section class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-3">
          <Button variant="outline" size="sm" class="w-fit" @click="handleBack">
            <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
            返回
          </Button>
          <div>
            <h1 class="text-page-title">
              {{ product?.name || '未命名产品' }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
              {{ product?.intro || '暂无产品简介' }}
            </p>
          </div>
          <div v-if="product" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            <Badge variant="secondary">花色图 {{ colorChartImages.length }} 张</Badge>
            <Badge variant="secondary">详情图 {{ detailChartImages.length }} 张</Badge>
            <span v-if="product.updatedAt">更新时间 {{ product.updatedAt }}</span>
          </div>
        </div>

        <div v-if="product" class="flex flex-wrap gap-2 sm:justify-end">
          <Button
            :variant="isFavorited ? 'default' : 'outline'"
            size="sm"
            @click="handleFavorite"
          >
            <SafeIcon :name="isFavorited ? 'Heart' : 'Heart'" :size="16" class="mr-2" :fill="isFavorited ? 'currentColor' : 'none'" />
            {{ isFavorited ? '已收藏' : '收藏' }}
          </Button>
          <Button variant="outline" size="sm" @click="handleShare">
            <SafeIcon name="Share2" :size="16" class="mr-2" />
            分享
          </Button>
          <Button variant="outline" size="sm" @click="handleDownload">
            <SafeIcon name="Download" :size="16" class="mr-2" />
            {{ canDownload ? '下载' : '不可下载' }}
          </Button>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-section-title font-semibold">花色图</h2>
          <Badge variant="secondary" class="text-xs">{{ colorChartImages.length }} 张</Badge>
        </div>
        <ProductImageGallery
          v-if="colorChartImages.length > 0"
          :images="colorChartImages"
          @image-click="(index) => handleImageClick(colorChartImages[index].url, index, 'colorChart')"
        />
        <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
          <SafeIcon name="Image" :size="30" class="mx-auto mb-2 opacity-50" />
          暂无花色图
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-section-title font-semibold">详情图</h2>
          <Badge variant="secondary" class="text-xs">{{ detailChartImages.length }} 张</Badge>
        </div>
        <div v-if="product?.hideDetailImage && !isOwnerView" class="rounded-lg border border-border bg-muted/40 p-4">
          <p class="text-sm font-medium">分享者已隐藏详情图</p>
          <p class="mt-1 text-xs text-muted-foreground">当前产品的详情图仅分享者本人可见</p>
        </div>
        <ProductImageGallery
          v-else-if="detailChartImages.length > 0"
          :images="detailChartImages"
          @image-click="(index) => handleImageClick(detailChartImages[index].url, index, 'detailChart')"
        />
        <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
          <SafeIcon name="Image" :size="30" class="mx-auto mb-2 opacity-50" />
          暂无详情图
        </div>
      </section>
    </template>

    <!-- Dialogs -->
    <LoginDialog :open="showLoginDialog" @update:open="(v) => showLoginDialog = v" @login-success="handleLoginSuccess" />
    <ShareDialog
      v-if="product"
      :open="showShareDialog"
      :product-id="product?.id || ''"
      :target-user-id="targetUserId"
      :share-code="shareCode"
      @update:open="(v) => showShareDialog = v"
    />
    <DownloadDialog
      v-if="isLoggedIn"
      :open="showDownloadDialog"
      :product-id="product?.id || ''"
      :images="downloadableImages"
      :can-download="canDownload"
      @update:open="(v) => showDownloadDialog = v"
    />
    <ImagePreviewDialog
      :open="showImagePreview"
      :images="previewImages"
      :current-index="previewIndex"
      :can-download="canDownload"
      :is-logged-in="isLoggedIn"
      @update:open="(v) => showImagePreview = v"
      @update:current-index="(v) => previewIndex = v"
      @login-required="showLoginDialog = true"
    />
  </div>
</template>
