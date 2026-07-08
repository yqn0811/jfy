
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ProductImageGallery from './ProductImageGallery.vue'
import ShareDialog from './ShareDialog.vue'
import DownloadDialog from './DownloadDialog.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore, getCurrentUserId, getUrlHomeTarget, pcApi } from '@/lib/api'
import { mapProduct, mapProductImagesFromDetail } from '@/lib/jfyuntu-mappers'
import type { ProductData } from '@/data/ProductData'
import type { ProductImageData } from '@/data/ProductImageData'

const product = ref<ProductData | null>(null)
const productImages = ref<ProductImageData[]>([])
const isClient = ref(true)
const isLoading = ref(false)
const targetUserId = ref('')
const shareCode = ref('')
const productId = ref('')

// State management
const isDialogOpen = ref(true)
const isFavorited = ref(false)
const isLoggedIn = ref(false)
const isOwnerView = ref(false)
const showLoginDialog = ref(false)
const showShareDialog = ref(false)
const showDownloadDialog = ref(false)

// Computed properties
const colorChartImages = computed(() => 
  productImages.value.filter(img => img.type === 'colorChart')
)

const detailChartImages = computed(() => 
  productImages.value.filter(img => img.type === 'detailChart')
)

const hasDetailImages = computed(() => detailChartImages.value.length > 0)

const canDownload = computed(() => {
  if (!product.value) return false
  if (product.value.hideDetailImage && !isOwnerView.value) return false
  return true
})

const loadProduct = async () => {
  authStore.consumeCallbackToken()
  if (!authStore.isLoggedIn()) {
    isLoggedIn.value = false
    showLoginDialog.value = true
    return
  }
  isLoggedIn.value = true
  if (!productId.value) {
    toast.error('缺少产品信息')
    return
  }

  isLoading.value = true
  try {
    const raw = targetUserId.value || shareCode.value
      ? await pcApi.getHomeProductDetail({ targetUserId: targetUserId.value, shareCode: shareCode.value }, productId.value)
      : await pcApi.getProductEditDetail(productId.value)
    const detail = raw?.folder_info || raw?.product || raw
    product.value = mapProduct(detail, targetUserId.value)
    productImages.value = mapProductImagesFromDetail(detail, product.value.id)
    isFavorited.value = Number(detail?.is_collect || detail?.isCollect || 0) === 1
    let currentUser = authStore.getUser<any>() || {}
    if (!getCurrentUserId(currentUser)) {
      currentUser = await pcApi.getCurrentUser()
      authStore.setUser(currentUser)
    }
    const currentUid = getCurrentUserId(currentUser)
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
    authStore.consumeCallbackToken()
    const target = getUrlHomeTarget()
    productId.value = params.get('productId') || params.get('product_id') || ''
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
    toast.error('分享者未开放图片下载')
    return
  }
  
  showDownloadDialog.value = true
}

const handleContact = () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  
  toast.info('联系信息已复制到剪贴板')
}

const handleImageClick = (imageUrl: string, index: number, type: 'colorChart' | 'detailChart') => {
  const images = type === 'colorChart' ? colorChartImages.value : detailChartImages.value
  const imageUrls = images.map(img => img.url)
  
  const params = new URLSearchParams({
    imageUrls: JSON.stringify(imageUrls),
    currentIndex: index.toString(),
    productId: product.value?.id || ''
  })
  if (shareCode.value) params.set('code', shareCode.value)
  else if (targetUserId.value) params.set('uid', targetUserId.value)
  
  window.location.href = `./image-viewer.html?${params.toString()}`
}

const handleCloseDialog = (open = false) => {
  if (open) {
    isDialogOpen.value = true
    return
  }
  isDialogOpen.value = false
  if (typeof window !== 'undefined' && window.history.length > 1) {
    window.history.back()
  }
}

const handleLoginSuccess = () => {
  showLoginDialog.value = false
  isLoggedIn.value = true
  toast.success('登录成功')
  loadProduct()
}
</script>

<template>
  <div>
    <Dialog v-if="isLoggedIn" :open="isDialogOpen" @update:open="handleCloseDialog">
      <DialogScrollContent class="max-h-[90vh] max-w-[760px] overflow-hidden p-0">
        <div class="flex max-h-[90vh] flex-col">
        <!-- Header with close button -->
        <DialogHeader class="flex-shrink-0 border-b border-border px-6 py-5 text-left">
          <div class="flex items-center justify-between">
            <div>
              <DialogTitle class="text-2xl font-bold">产品详情</DialogTitle>
              <p class="text-lg font-semibold text-foreground mt-2">
                {{ product?.name || '未命名产品' }}
              </p>
            </div>
          </div>
        </DialogHeader>

        <!-- Scrollable content area -->
        <div class="flex-1 overflow-y-auto min-h-0 py-4">
          <div v-if="isLoading" class="py-12 text-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
            加载中...
          </div>
          <div class="space-y-6 px-6">
            <!-- Product Information Section -->
            <section class="space-y-3">
              <h3 class="text-section-title font-semibold">产品信息</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">产品名称</span>
                  <span class="font-medium">{{ product?.name || '未命名产品' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">产品简介</span>
                  <span class="font-medium text-right max-w-xs">
                    {{ product?.intro || '暂无产品简介' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">更新时间</span>
                  <span class="font-medium">{{ product?.updatedAt }}</span>
                </div>
              </div>
            </section>

            <!-- Color Chart Images Section -->
            <section class="space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="text-section-title font-semibold">
                  花色图
                  <span class="text-muted-foreground text-sm font-normal ml-2">{{ colorChartImages.length }} 张</span>
                </h3>
              </div>
              <ProductImageGallery
                v-if="colorChartImages.length > 0"
                :images="colorChartImages"
                @image-click="(index) => handleImageClick(colorChartImages[index].url, index, 'colorChart')"
              />
              <div v-else class="text-center py-8 text-muted-foreground">
                <SafeIcon name="Image" :size="32" class="mx-auto mb-2 opacity-50" />
                <p>暂无花色图</p>
              </div>
            </section>

            <!-- Detail Chart Images Section -->
            <section class="space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="text-section-title font-semibold">
                  详情图
                  <span class="text-muted-foreground text-sm font-normal ml-2">{{ detailChartImages.length }} 张</span>
                </h3>
              </div>
              <div v-if="product?.hideDetailImage && !isOwnerView" class="p-4 bg-muted/50 rounded-lg border border-border">
                <p class="text-sm text-muted-foreground">分享者已隐藏详情图</p>
                <p class="text-xs text-muted-foreground mt-1">当前产品的详情图仅分享者本人可见</p>
              </div>
              <ProductImageGallery
                v-else-if="detailChartImages.length > 0"
                :images="detailChartImages"
                @image-click="(index) => handleImageClick(detailChartImages[index].url, index, 'detailChart')"
              />
              <div v-else class="text-center py-8 text-muted-foreground">
                <SafeIcon name="Image" :size="32" class="mx-auto mb-2 opacity-50" />
                <p>暂无详情图</p>
              </div>
            </section>
          </div>
        </div>
        <DialogFooter class="border-t border-border px-6 py-4 sm:justify-stretch">
          <div class="grid w-full grid-cols-4 gap-3">
            <Button
              :variant="isFavorited ? 'default' : 'outline'"
              class="h-11 gap-2"
              @click="handleFavorite"
            >
              <SafeIcon :name="isFavorited ? 'Heart' : 'Heart'" :size="16" :fill="isFavorited ? 'currentColor' : 'none'" />
              {{ isFavorited ? '已收藏' : '收藏' }}
            </Button>
            <Button variant="outline" class="h-11 gap-2" @click="handleShare">
              <SafeIcon name="Share2" :size="16" />
              分享
            </Button>
            <Button variant="outline" class="h-11 gap-2" @click="handleDownload">
              <SafeIcon name="Download" :size="16" />
              下载
            </Button>
            <Button variant="outline" class="h-11 gap-2" @click="handleContact">
              <SafeIcon name="MessageCircle" :size="16" />
              联系商户
            </Button>
          </div>
        </DialogFooter>
        </div>
      </DialogScrollContent>
    </Dialog>

    <!-- Dialogs -->
    <LoginDialog :open="showLoginDialog" @update:open="(v) => showLoginDialog = v" @login-success="handleLoginSuccess" />
    <ShareDialog
      v-if="isLoggedIn"
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
      :images="productImages"
      @update:open="(v) => showDownloadDialog = v"
    />
  </div>
</template>
