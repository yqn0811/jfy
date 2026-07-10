
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import ShareDialog from '@/components/share_home/ShareDialog.vue'
import ContactDialog from '@/components/share_home/ContactDialog.vue'
import ProductShareDialog from '@/components/product_detail/ShareDialog.vue'
import DownloadDialog from '@/components/product_detail/DownloadDialog.vue'
import SelectionPickerDialog from '@/components/selection/SelectionPickerDialog.vue'
import { authStore, getCurrentUserId, getUrlHomeTarget, pcApi } from '@/lib/api'
import { isVipMember } from '@/lib/account'
import { downloadImagesAsZip, downloadUrl, resolveProductImageDownloadUrl } from '@/lib/download'
import { mapCategory, mapProduct, mapProductImagesFromDetail, normalizeHomePayload, unwrapList } from '@/lib/jfyuntu-mappers'
import type { HomeProfileData } from '@/data/HomeProfileData'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductData } from '@/data/ProductData'
import { productImageUrl, type ProductImageData } from '@/data/ProductImageData'

const isClient = ref(true)
const isLoading = ref(false)
const homeProfile = ref<HomeProfileData | null>(null)
const categories = ref<CategoryData[]>([])
const allProducts = ref<ProductData[]>([])
const categoryProducts = ref<ProductData[]>([])

const searchKeyword = ref('')
const selectedCategoryId = ref<string | null>(null)
const isLoggedIn = ref(false)
const currentUserId = ref('')
const currentUser = ref<any>({})
const targetUserId = ref('')
const shareCode = ref('')

const showLoginDialog = ref(false)
const showShareDialog = ref(false)
const showContactDialog = ref(false)
const selectedProductId = ref<string | null>(null)
const selectedProduct = ref<ProductData | null>(null)
const selectedProductImages = ref<ProductImageData[]>([])
const isProductDetailLoading = ref(false)
const isProductFavorited = ref(false)
const showProductShareDialog = ref(false)
const showDownloadDialog = ref(false)
const showSelectionDialog = ref(false)
const currentSelection = ref<any>(null)
const isSelectionLoading = ref(false)
const previewImages = ref<ProductImageData[]>([])
const previewImageIndex = ref(0)
const showImagePreviewDialog = ref(false)

const isHomeFavorited = ref(false)

const isProductDetailMode = computed(() => !!selectedProductId.value)
const selectedColorImages = computed(() => selectedProductImages.value.filter(item => item.type === 'colorChart'))
const selectedDetailImages = computed(() => selectedProductImages.value.filter(item => item.type === 'detailChart'))
const shouldShowSelectedDetailImages = computed(() => !!selectedProduct.value && (!selectedProduct.value.hideDetailImage || isOwnerViewingOwnHome.value))
const visibleSelectedDetailImages = computed(() => shouldShowSelectedDetailImages.value ? selectedDetailImages.value : [])
const downloadableImages = computed(() => {
  if (!selectedProduct.value) return []
  if (selectedProduct.value.hideDetailImage && !isOwnerViewingOwnHome.value) return selectedColorImages.value
  return selectedProductImages.value
})
const isOwnerViewingOwnHome = computed(() => !!currentUserId.value && !!homeProfile.value?.ownerUserId && currentUserId.value === homeProfile.value.ownerUserId)
const isCurrentUserVip = computed(() => isVipMember(currentUser.value))
const canUseOriginalImage = computed(() => isCurrentUserVip.value)
const canDownloadProductImages = computed(() => (!!homeProfile.value?.allowSavePic || isOwnerViewingOwnHome.value) && canUseOriginalImage.value)
const previewImage = computed(() => previewImages.value[previewImageIndex.value] || null)

const getHomeTargetRef = () => ({
  targetUserId: targetUserId.value,
  shareCode: shareCode.value,
})

const buildHomeSearchParams = () => {
  const params = new URLSearchParams()
  if (shareCode.value) params.set('code', shareCode.value)
  else if (targetUserId.value) params.set('uid', targetUserId.value)
  return params
}

const flattenCategories = (raw: any[], homeId: string, parentId = ''): CategoryData[] => {
  return raw.flatMap(item => {
    const mapped = mapCategory(parentId ? { ...item, pid: item.pid || parentId } : item, homeId)
    const children = unwrapList(item.children || item.child || item.son || item.sub_categories)
    return [mapped, ...flattenCategories(children, homeId, mapped.id)]
  })
}

const loadCurrentUser = async () => {
  let user = authStore.getUser<any>() || {}
  if (!getCurrentUserId(user) && authStore.isLoggedIn()) {
    try {
      user = await pcApi.getCurrentUser()
      authStore.setUser(user)
    } catch (error: any) {
      user = authStore.getUser<any>() || {}
      if (!getCurrentUserId(user)) {
        throw error
      }
    }
  }
  currentUserId.value = getCurrentUserId(user)
  currentUser.value = user || {}
  return user
}

const applyCachedCurrentUser = () => {
  const user = authStore.getUser<any>() || {}
  currentUserId.value = getCurrentUserId(user)
  currentUser.value = user || {}
  return user
}

const filteredProducts = computed(() => {
  let result = allProducts.value

  if (selectedCategoryId.value && selectedCategoryId.value !== 'all') {
    const merged = new Map<string, ProductData>()
    getLocalCategoryProducts(selectedCategoryId.value).forEach(product => {
      merged.set(product.id, product)
    })
    categoryProducts.value.forEach(product => {
      merged.set(product.id, product)
    })
    result = Array.from(merged.values())
  }


  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(keyword))
  }

  return result
})

const getCategoryDescendantIds = (categoryId: string) => {
  const result = new Set<string>([categoryId])
  let changed = true
  while (changed) {
    changed = false
    categories.value.forEach(category => {
      if (category.parentId && result.has(category.parentId) && !result.has(category.id)) {
        result.add(category.id)
        changed = true
      }
    })
  }
  return result
}

const getLocalCategoryProducts = (categoryId: string) => {
  const categoryIds = getCategoryDescendantIds(categoryId)
  return allProducts.value.filter(product => {
    const productCategoryIds = product.categoryIds?.length
      ? product.categoryIds
      : product.categoryId
        ? [product.categoryId]
        : []
    return productCategoryIds.some(id => categoryIds.has(id))
  })
}

const loadCategoryProducts = async (categoryId: string) => {
  const target = getHomeTargetRef()
  if ((!target.targetUserId && !target.shareCode) || categoryId === 'all') {
    categoryProducts.value = []
    return
  }

  isLoading.value = true
  try {
    const raw = await pcApi.getHomeProducts(target, categoryId)
    categoryProducts.value = unwrapList(raw).map(item => mapProduct(item, homeProfile.value?.id || targetUserId.value))
  } catch (error: any) {
    categoryProducts.value = getLocalCategoryProducts(categoryId)
    toast.error(error?.message || '分类产品加载失败')
  } finally {
    isLoading.value = false
  }
}

const loadProductDetail = async (productId: string) => {
  const target = getHomeTargetRef()
  isProductDetailLoading.value = true
  try {
    const raw = await pcApi.getHomeProductDetail(target, productId)
    const detailSource = raw?.folder_info || raw?.product || raw
    const detail = {
      ...detailSource,
      pictures: detailSource?.pictures ?? raw?.pictures,
      pic_list: detailSource?.pic_list ?? raw?.pic_list,
      pic_ids_arr: detailSource?.pic_ids_arr ?? raw?.pic_ids_arr,
      detail_pic_list: detailSource?.detail_pic_list ?? raw?.detail_pic_list,
      detail_pic_ids_arr: detailSource?.detail_pic_ids_arr ?? raw?.detail_pic_ids_arr,
    }
    selectedProduct.value = mapProduct(detail, homeProfile.value?.id || targetUserId.value)
    selectedProductImages.value = mapProductImagesFromDetail(detail, selectedProduct.value.id)
    currentSelection.value = null
    selectedProduct.value.colorChartCount = selectedColorImages.value.length
    selectedProduct.value.detailChartCount = shouldShowSelectedDetailImages.value ? selectedDetailImages.value.length : 0
    isProductFavorited.value = Number(detail?.is_collect || detail?.isCollect || 0) === 1
    if (isLoggedIn.value) {
      pcApi.addVisit('product', selectedProduct.value.id).catch(() => {})
      loadCurrentProductSelection(productId).catch(() => {})
    }
  } catch (error: any) {
    selectedProductId.value = null
    selectedProduct.value = null
    selectedProductImages.value = []
    toast.error(error?.message || '产品加载失败')
  } finally {
    isProductDetailLoading.value = false
  }
}

const loadCurrentProductSelection = async (productId: string) => {
  if (!isLoggedIn.value || isOwnerViewingOwnHome.value) return null
  isSelectionLoading.value = true
  try {
    const raw = await pcApi.getMySelections({ limit: 100 })
    const rows = unwrapList(raw)
    const matched = rows.find((item: any) => String(item.product?.id || item.product_id || item.product?.product_id || '') === String(productId))
    if (!matched?.id) {
      currentSelection.value = null
      return null
    }
    const detail = await pcApi.getSelectionDetail(String(matched.id))
    currentSelection.value = {
      ...matched,
      ...detail,
      id: matched.id,
      list: detail?.list || matched.selected_preview || [],
    }
    return currentSelection.value
  } finally {
    isSelectionLoading.value = false
  }
}

const categoryOptions = computed(() => {
  return [
    { id: 'all', name: '全部' },
    ...categories.value.filter(c => !c.parentId)
  ]
})

const getCategoryById = (categoryId = '') => categories.value.find(category => category.id === categoryId) || null

const categoryBreadcrumbs = computed(() => {
  const crumbs: Array<{ id: string; name: string }> = [{ id: 'all', name: '主页' }]
  const selectedId = selectedCategoryId.value
  if (!selectedId || selectedId === 'all') return crumbs

  const chain: CategoryData[] = []
  const seen = new Set<string>()
  let current = getCategoryById(selectedId)
  while (current && !seen.has(current.id)) {
    chain.unshift(current)
    seen.add(current.id)
    current = current.parentId ? getCategoryById(current.parentId) : null
  }
  return [...crumbs, ...chain.map(category => ({ id: category.id, name: category.name }))]
})

const childCategoryOptions = computed(() => {
  const selectedId = selectedCategoryId.value
  if (!selectedId || selectedId === 'all') {
    return categories.value.filter(category => !category.parentId)
  }
  return categories.value.filter(category => category.parentId === selectedId)
})

const currentCategoryName = computed(() => {
  if (!selectedCategoryId.value || selectedCategoryId.value === 'all') return '全部产品'
  return childCategoryOptions.value.length > 0 ? '下级分类' : '当前分类'
})

const loadHomeData = async () => {
  authStore.consumeCallbackToken()
  if (!authStore.isLoggedIn()) {
    isLoggedIn.value = false
    showLoginDialog.value = true
    return
  }
  isLoggedIn.value = true
  isLoading.value = true
  let user: any = {}
  const hasShareTarget = !!targetUserId.value || !!shareCode.value
  if (hasShareTarget) {
    user = applyCachedCurrentUser()
    loadCurrentUser().catch(() => {})
  } else {
    try {
      user = await loadCurrentUser()
    } catch (error: any) {
      authStore.clearToken()
      isLoggedIn.value = false
      isLoading.value = false
      showLoginDialog.value = true
      toast.error(error?.message || '登录已失效，请重新扫码')
      return
    }
  }
  if (!targetUserId.value && !shareCode.value) {
    targetUserId.value = getCurrentUserId(user)
  }
  if (!targetUserId.value && !shareCode.value) {
    isLoading.value = false
    toast.error('登录信息不完整，请重新扫码')
    showLoginDialog.value = true
    return
  }
  try {
    const target = getHomeTargetRef()
    const [homeRaw, categoriesRaw, productsRaw] = await Promise.all([
      pcApi.getHomeInfo(target),
      pcApi.getHomeCategories(target),
      pcApi.getHomeProducts(target),
    ])
    const normalized = normalizeHomePayload(homeRaw, categoriesRaw, productsRaw)
    homeProfile.value = normalized.home
    if (homeProfile.value.shareCode) {
      shareCode.value = homeProfile.value.shareCode
    }
    if (!homeProfile.value.ownerUserId || homeProfile.value.ownerUserId === 'home') {
      homeProfile.value.ownerUserId = targetUserId.value
      homeProfile.value.id = targetUserId.value
    } else {
      targetUserId.value = homeProfile.value.ownerUserId
    }
    categories.value = flattenCategories(unwrapList(categoriesRaw), normalized.home.id)
    allProducts.value = normalized.products
    if (selectedCategoryId.value && selectedCategoryId.value !== 'all') {
      await loadCategoryProducts(selectedCategoryId.value)
    } else {
      categoryProducts.value = []
    }
    isHomeFavorited.value = Number(homeRaw?.is_collect || homeRaw?.isCollect || 0) === 1
    if (isLoggedIn.value && homeProfile.value) {
      pcApi.addVisit('homepage', homeProfile.value.id).catch(() => {})
    }
  } catch (error: any) {
    toast.error(error?.message || '主页加载失败')
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
    targetUserId.value = target.targetUserId
    shareCode.value = target.shareCode
    const keyword = params.get('keyword')
    const categoryId = params.get('categoryId') || params.get('cate_id')
    const productId = params.get('productId') || params.get('product_id')
    if (keyword) searchKeyword.value = keyword
    selectedCategoryId.value = categoryId || 'all'
    isLoggedIn.value = authStore.isLoggedIn()
    isClient.value = true
    loadHomeData().then(() => {
      if (productId && isLoggedIn.value) {
        selectedProductId.value = productId
        loadProductDetail(productId)
      }
    })
  })
})

const handleSearch = () => {
  if (typeof window !== 'undefined' && searchKeyword.value.trim()) {
    const params = buildHomeSearchParams()
    params.set('keyword', searchKeyword.value.trim())
    const url = `./share-home?${params.toString()}`
    window.history.replaceState(null, '', url)
  }
}

const handleCategoryChange = async (categoryId: string) => {
  selectedProductId.value = null
  selectedProduct.value = null
  selectedProductImages.value = []
  selectedCategoryId.value = categoryId
  if (categoryId === 'all') {
    categoryProducts.value = []
  } else {
    await loadCategoryProducts(categoryId)
  }
  if (typeof window !== 'undefined') {
    const params = buildHomeSearchParams()
    if (categoryId !== 'all') params.set('categoryId', categoryId)
    window.history.replaceState(null, '', `./share-home${params.toString() ? `?${params.toString()}` : ''}`)
  }
}

const handleFavoriteHome = async () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }

  if (!homeProfile.value) return

  try {
    await pcApi.toggleFavorite('homepage', homeProfile.value.id, !isHomeFavorited.value)
    isHomeFavorited.value = !isHomeFavorited.value
    toast.success(isHomeFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const handleContactMerchant = () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  showContactDialog.value = true
}

const handleShareHome = () => {
  showShareDialog.value = true
}

const handleProductClick = (productId: string) => {
  selectedProductId.value = productId
  selectedProduct.value = null
  selectedProductImages.value = []
  loadProductDetail(productId)
  if (typeof window !== 'undefined') {
    const params = buildHomeSearchParams()
    if (selectedCategoryId.value && selectedCategoryId.value !== 'all') params.set('categoryId', selectedCategoryId.value)
    params.set('productId', productId)
    window.history.replaceState(null, '', `./share-home?${params.toString()}`)
  }
}

const handleBackToList = () => {
  selectedProductId.value = null
  selectedProduct.value = null
  selectedProductImages.value = []
  currentSelection.value = null
  showDownloadDialog.value = false
  showImagePreviewDialog.value = false
  showSelectionDialog.value = false
  if (typeof window !== 'undefined') {
    const params = buildHomeSearchParams()
    if (selectedCategoryId.value && selectedCategoryId.value !== 'all') params.set('categoryId', selectedCategoryId.value)
    window.history.replaceState(null, '', `./share-home${params.toString() ? `?${params.toString()}` : ''}`)
  }
}

const handleFavoriteProduct = async () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  if (!selectedProduct.value) return
  try {
    await pcApi.toggleFavorite('product', selectedProduct.value.id, !isProductFavorited.value)
    isProductFavorited.value = !isProductFavorited.value
    toast.success(isProductFavorited.value ? '收藏成功' : '已取消收藏')
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const handleShareProduct = () => {
  if (!selectedProduct.value) return
  showProductShareDialog.value = true
}

const handleOpenSelectionDialog = async () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  if (!selectedProduct.value) return
  if (isOwnerViewingOwnHome.value) {
    toast.warning('自己的主页无需发送选款单')
    return
  }
  if (!currentSelection.value && !isSelectionLoading.value) {
    await loadCurrentProductSelection(selectedProduct.value.id).catch(() => {})
  }
  showSelectionDialog.value = true
}

const handleSelectionSaved = (selection: any) => {
  currentSelection.value = selection || currentSelection.value
}

const handleDownloadProduct = () => {
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  if (!homeProfile.value?.allowSavePic && !isOwnerViewingOwnHome.value) {
    toast.error('分享者未开放图片下载')
    return
  }
  if (!canUseOriginalImage.value) {
    toast.warning('开通会员后可下载原图')
    return
  }
  if (downloadableImages.value.length === 0) {
    toast.error('暂无可下载图片')
    return
  }
  showDownloadDialog.value = true
}

const downloadImages = async (images: ProductImageData[]) => {
  if (!homeProfile.value?.allowSavePic && !isOwnerViewingOwnHome.value) {
    toast.error('分享者未开放图片下载')
    return
  }
  if (!canUseOriginalImage.value) {
    toast.warning('开通会员后可下载原图')
    return
  }
  if (images.length === 0) {
    toast.error('暂无可下载图片')
    return
  }
  try {
    await downloadImagesAsZip(
      images,
      `product-${selectedProduct.value?.id || 'images'}.zip`
    )
    toast.success(`已打包 ${images.length} 张图片`)
  } catch (error: any) {
    toast.error(error?.message || '打包下载失败，请稍后重试')
  }
}

const handleViewImage = (index: number, type: 'colorChart' | 'detailChart') => {
  if (!selectedProduct.value) return
  const images = type === 'colorChart' ? selectedColorImages.value : selectedDetailImages.value
  if (!images.length) return
  previewImages.value = images
  previewImageIndex.value = index
  showImagePreviewDialog.value = true
}

const handlePreviewPrev = () => {
  if (!previewImages.value.length) return
  previewImageIndex.value = (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length
}

const handlePreviewNext = () => {
  if (!previewImages.value.length) return
  previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length
}

const handleOpenOriginalImage = async () => {
  if (!previewImage.value) return
  if (!canUseOriginalImage.value) {
    toast.warning('开通会员后可查看原图')
    return
  }
  try {
    const url = await resolveProductImageDownloadUrl(previewImage.value)
    if (url) window.open(url, '_blank')
  } catch (error: any) {
    toast.error(error?.message || '原图暂不可查看')
  }
}

const handleDownloadPreviewImage = async () => {
  if (!previewImage.value) return
  if (!homeProfile.value?.allowSavePic && !isOwnerViewingOwnHome.value) {
    toast.error('分享者未开放图片下载')
    return
  }
  if (!canUseOriginalImage.value) {
    toast.warning('开通会员后可下载原图')
    return
  }
  try {
    const url = await resolveProductImageDownloadUrl(previewImage.value)
    if (!url) {
      toast.error('图片地址无效')
      return
    }
    downloadUrl(url, previewImage.value.name || 'image')
  } catch (error: any) {
    toast.error(error?.message || '下载失败')
  }
}

const handleLoginSuccess = () => {
  showLoginDialog.value = false
  isLoggedIn.value = true
  loadCurrentUser().then(() => loadHomeData())
  if (homeProfile.value) {
    pcApi.addVisit('homepage', homeProfile.value.id).catch(() => {})
  }
  toast.success('登录成功')
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- 分类导航区 -->
    <section
      v-if="isClient && isLoggedIn && homeProfile && categoryOptions.length > 0 && !isProductDetailMode"
      class="sticky top-0 z-40 border-b border-border bg-background/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/85 md:px-8"
    >
      <div class="page-container space-y-3">
        <nav class="flex min-w-0 flex-wrap items-center gap-1 text-sm" aria-label="分类层级">
          <template v-for="(crumb, index) in categoryBreadcrumbs" :key="crumb.id">
            <button
              type="button"
              class="rounded px-1.5 py-1 font-medium text-muted-foreground transition-colors hover:bg-primary/5 hover:text-primary"
              :class="index === categoryBreadcrumbs.length - 1 ? 'text-foreground' : ''"
              @click="handleCategoryChange(crumb.id)"
            >
              {{ crumb.name }}
            </button>
            <SafeIcon
              v-if="index < categoryBreadcrumbs.length - 1"
              name="ChevronRight"
              :size="14"
              class="text-muted-foreground/70"
            />
          </template>
        </nav>

        <div class="flex min-w-0 items-center gap-2">
          <span class="shrink-0 text-xs font-medium text-muted-foreground">{{ currentCategoryName }}</span>
          <div v-if="childCategoryOptions.length > 0" class="flex min-w-0 flex-1 gap-2 overflow-x-auto pb-0.5">
            <Button
              v-for="cat in childCategoryOptions"
              :key="cat.id"
              type="button"
              variant="outline"
              size="sm"
              class="shrink-0 rounded-md border-border bg-card px-3 font-medium hover:border-primary hover:bg-primary/5 hover:text-primary"
              @click="handleCategoryChange(cat.id)"
            >
              {{ cat.name }}
            </Button>
          </div>
          <span v-else class="text-xs text-muted-foreground">当前分类暂无下级分类</span>
        </div>
      </div>
    </section>

    <!-- 商户信息区 -->
    <section v-if="isClient && homeProfile" class="page-body border-b border-border">
      <div class="page-container flex gap-8 items-start">
        <!-- 左侧：Logo + 基本信息 -->
        <div class="flex-shrink-0">
          <img
            :src="homeProfile.logoUrl"
            :alt="homeProfile.companyName"
            class="w-32 h-32 rounded-lg object-cover border border-border"
          />
        </div>

        <!-- 中间：公司信息 -->
        <div class="flex-1 min-w-0">
          <h1 class="text-page-title mb-2">{{ homeProfile.companyName || '商户主页' }}</h1>
          <div class="flex items-center gap-2 mb-4">
            <Badge variant="outline" class="bg-primary/10 text-primary border-primary/30">
              {{ homeProfile.industryTag }}
            </Badge>
            <span class="text-sm text-muted-foreground">
              产品 {{ homeProfile.productCount }} 个
            </span>
          </div>
          <p class="text-body text-muted-foreground mb-4 line-clamp-2">
            {{ homeProfile.intro || '暂无简介' }}
          </p>
          <div class="text-sm text-muted-foreground space-y-1">
            <p v-if="homeProfile.region">📍 {{ homeProfile.region }}</p>
            <p v-if="homeProfile.address">🏢 {{ homeProfile.address }}</p>
          </div>
        </div>

        <!-- 右侧：操作按钮 -->
        <div class="flex-shrink-0 flex flex-col gap-2">
          <Button
            :variant="isHomeFavorited ? 'default' : 'outline'"
            size="sm"
            @click="handleFavoriteHome"
            :disabled="!isClient"
            class="w-32"
          >
            <SafeIcon :name="isHomeFavorited ? 'Heart' : 'Heart'" :size="16" class="mr-2" :fill="isHomeFavorited ? 'currentColor' : 'none'" />
            {{ isHomeFavorited ? '已收藏' : '收藏主页' }}
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="handleContactMerchant"
            :disabled="!isClient"
            class="w-32"
          >
            <SafeIcon name="MessageCircle" :size="16" class="mr-2" />
            {{ homeProfile.contactServiceName }}
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="handleShareHome"
            :disabled="!isClient"
            class="w-32"
          >
            <SafeIcon name="Share2" :size="16" class="mr-2" />
            分享主页
          </Button>
        </div>
      </div>
    </section>

    <!-- 产品流区 / 产品详情区 -->
    <section v-if="isLoggedIn" class="page-body">
      <div class="page-container">
        <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
          <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
          加载中...
        </div>

        <div v-else-if="isProductDetailMode" class="space-y-6">
          <Button variant="outline" class="gap-2" @click="handleBackToList">
            <SafeIcon name="ArrowLeft" :size="16" />
            返回列表
          </Button>

          <div v-if="isProductDetailLoading" class="py-16 text-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
            加载产品详情...
          </div>

          <div v-else-if="selectedProduct" class="space-y-8">
            <div class="flex flex-col gap-4 border-b border-border pb-6 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0 space-y-3">
                <div class="flex items-center gap-3">
                  <h2 class="text-page-title">{{ selectedProduct.name || '未命名产品' }}</h2>
                  <Badge variant="outline">{{ selectedProduct.categoryName || selectedProduct.categoryNames?.[0] || '未分类' }}</Badge>
                </div>
                <p class="max-w-3xl text-body text-muted-foreground">{{ selectedProduct.intro || '暂无产品简介' }}</p>
                <div class="flex flex-wrap gap-3 text-sm text-muted-foreground">
                  <span>花色图 {{ selectedColorImages.length }} 张</span>
                  <span>详情图 {{ visibleSelectedDetailImages.length }} 张</span>
                  <span v-if="selectedProduct.updatedAt">更新 {{ selectedProduct.updatedAt }}</span>
                </div>
              </div>
              <div class="grid w-full grid-cols-4 gap-2 sm:w-auto sm:min-w-[460px]">
                <Button :variant="isProductFavorited ? 'default' : 'outline'" class="gap-2" @click="handleFavoriteProduct">
                  <SafeIcon name="Heart" :size="16" :fill="isProductFavorited ? 'currentColor' : 'none'" />
                  {{ isProductFavorited ? '已收藏' : '收藏' }}
                </Button>
                <Button variant="outline" class="gap-2" @click="handleShareProduct">
                  <SafeIcon name="Share2" :size="16" />
                  分享
                </Button>
                <Button variant="outline" class="gap-2" @click="handleOpenSelectionDialog">
                  <SafeIcon name="CheckSquare" :size="16" />
                  {{ currentSelection ? '编辑选款单' : '选款' }}
                </Button>
                <Button variant="outline" class="gap-2" @click="handleDownloadProduct">
                  <SafeIcon name="Download" :size="16" />
                  {{ canDownloadProductImages ? '下载 ZIP' : '下载' }}
                </Button>
              </div>
            </div>

            <section class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-section-title">花色图</h3>
                <Badge variant="secondary">{{ selectedColorImages.length }} 张</Badge>
              </div>
              <div v-if="selectedColorImages.length > 0" class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                <button
                  v-for="(image, index) in selectedColorImages"
                  :key="image.id"
                  type="button"
                  class="group relative aspect-square overflow-hidden rounded-lg border border-border bg-muted text-left transition hover:border-primary"
                  @click="handleViewImage(index, 'colorChart')"
                >
                  <img :src="productImageUrl(image, 'thumb')" :alt="image.name" class="h-full w-full object-cover transition group-hover:scale-[1.02]" />
                  <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2 pt-8 text-sm font-medium text-white opacity-100">
                    <span class="block truncate">{{ image.name || '未命名图片' }}</span>
                  </span>
                </button>
              </div>
              <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
                暂无花色图
              </div>
            </section>

            <section v-if="shouldShowSelectedDetailImages" class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-section-title">详情图</h3>
                <Badge variant="secondary">{{ visibleSelectedDetailImages.length }} 张</Badge>
              </div>
              <div v-if="visibleSelectedDetailImages.length > 0" class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                <button
                  v-for="(image, index) in visibleSelectedDetailImages"
                  :key="image.id"
                  type="button"
                  class="group aspect-square overflow-hidden rounded-lg border border-border bg-muted text-left transition hover:border-primary"
                  @click="handleViewImage(index, 'detailChart')"
                >
                  <img :src="productImageUrl(image, 'thumb')" :alt="image.name" class="h-full w-full object-cover transition group-hover:scale-[1.02]" />
                </button>
              </div>
              <div v-else class="rounded-lg border border-dashed border-border py-12 text-center text-muted-foreground">
                暂无详情图
              </div>
            </section>
          </div>
        </div>

        <div v-else-if="filteredProducts.length === 0" class="py-16">
          <EmptyState
            icon="Package"
            title="暂无产品"
            description="该主页暂未展示产品"
          />
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            class="product-card cursor-pointer overflow-hidden rounded-lg"
            @click="handleProductClick(product.id)"
          >
            <!-- 产品封面 -->
            <div class="relative w-full aspect-square bg-muted overflow-hidden">
              <img
                :src="product.coverUrl"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
            </div>

            <!-- 产品信息 -->
            <div class="card-padding space-y-2">
              <h3 class="text-item-title font-medium truncate">
                {{ product.name || '未命名产品' }}
              </h3>
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>🎨 花色图 {{ product.colorChartCount }} 张</span>
                <span>📋 详情图 {{ product.detailChartCount }} 张</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 登录弹窗 -->
    <LoginDialog
      :open="showLoginDialog && isClient"
      @update:open="(val) => (showLoginDialog = val)"
      @login-success="handleLoginSuccess"
    />

    <!-- 分享弹窗 -->
    <ShareDialog
      v-if="isClient && homeProfile"
      :open="showShareDialog"
      :home-profile="homeProfile"
      @update:open="(val) => (showShareDialog = val)"
    />

    <!-- 联系商户弹窗 -->
    <ContactDialog
      v-if="isClient && homeProfile"
      :open="showContactDialog"
      :home-profile="homeProfile"
      @update:open="(val) => (showContactDialog = val)"
    />

    <ProductShareDialog
      v-if="isClient && selectedProduct"
      :open="showProductShareDialog"
      :product-id="selectedProduct.id"
      :target-user-id="targetUserId"
      :share-code="shareCode"
      @update:open="showProductShareDialog = $event"
    />

    <DownloadDialog
      v-if="isClient && selectedProduct && canDownloadProductImages"
      :open="showDownloadDialog"
      :product-id="selectedProduct.id"
      :images="downloadableImages"
      @update:open="showDownloadDialog = $event"
    />

    <SelectionPickerDialog
      v-if="isClient && selectedProduct"
      :open="showSelectionDialog"
      :product="selectedProduct"
      :images="selectedProductImages"
      :factory-uid="homeProfile?.ownerUserId || targetUserId"
      :existing-selection="currentSelection"
      @update:open="showSelectionDialog = $event"
      @saved="handleSelectionSaved"
    />

    <Dialog :open="showImagePreviewDialog" @update:open="showImagePreviewDialog = $event">
      <DialogContent class="flex max-h-[92vh] max-w-[92vw] flex-col overflow-hidden p-0 sm:max-w-[960px]">
        <DialogHeader class="border-b border-border px-5 py-4">
          <DialogTitle class="truncate text-base">
            {{ previewImage?.name || '图片预览' }}
          </DialogTitle>
        </DialogHeader>

        <div class="relative flex min-h-0 flex-1 items-center justify-center bg-muted/40 p-4">
          <Button
            v-if="previewImages.length > 1"
            variant="outline"
            size="sm"
            class="absolute left-4 top-1/2 z-10 h-10 w-10 -translate-y-1/2 rounded-full p-0"
            @click="handlePreviewPrev"
          >
            <SafeIcon name="ChevronLeft" :size="18" />
          </Button>
          <img
            v-if="previewImage"
            :src="productImageUrl(previewImage, 'preview')"
            :alt="previewImage.name"
            class="max-h-[70vh] max-w-full rounded-md object-contain"
          />
          <Button
            v-if="previewImages.length > 1"
            variant="outline"
            size="sm"
            class="absolute right-4 top-1/2 z-10 h-10 w-10 -translate-y-1/2 rounded-full p-0"
            @click="handlePreviewNext"
          >
            <SafeIcon name="ChevronRight" :size="18" />
          </Button>
        </div>

        <DialogFooter class="border-t border-border px-5 py-4">
          <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm text-muted-foreground">
              {{ previewImages.length ? `${previewImageIndex + 1} / ${previewImages.length}` : '' }}
            </span>
            <div class="flex gap-2">
              <Button variant="outline" class="gap-2" @click="handleOpenOriginalImage">
                <SafeIcon name="ExternalLink" :size="16" />
                查看原图
              </Button>
              <Button
                variant="default"
                class="gap-2"
                @click="handleDownloadPreviewImage"
              >
                <SafeIcon name="Download" :size="16" />
                下载
              </Button>
            </div>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
/* 产品卡片悬停效果 */
.product-card {
  @apply transition-all duration-200;
}

.product-card:hover {
  @apply shadow-card;
  transform: translateY(-2px);
}
</style>
