<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import ShareDialog from '@/components/share_home/ShareDialog.vue'
import ContactDialog from '@/components/share_home/ContactDialog.vue'
import ProductDetailPanel from '@/components/share_home/ProductDetailPanel.vue'
import { authStore, getCurrentUserId, getUrlHomeTarget, pcApi } from '@/lib/api'
import { mapCategory, mapProduct, normalizeHomePayload, unwrapList } from '@/lib/jfyuntu-mappers'
import type { HomeProfileData } from '@/data/HomeProfileData'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductData } from '@/data/ProductData'

const isClient = ref(true)
const isLoading = ref(false)
const homeProfile = ref<HomeProfileData | null>(null)
const categories = ref<CategoryData[]>([])
const allProducts = ref<ProductData[]>([])
const categoryProducts = ref<ProductData[]>([])

const searchKeyword = ref('')
const selectedCategoryId = ref<string | null>('all')
const isLoggedIn = ref(false)
const currentUserId = ref('')
const targetUserId = ref('')
const shareCode = ref('')

const showLoginDialog = ref(false)
const showShareDialog = ref(false)
const showContactDialog = ref(false)
const selectedProductId = ref<string | null>(null)
const isHomeFavorited = ref(false)

const isProductDetailMode = computed(() => !!selectedProductId.value)

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

const updateUrlState = (productId = '') => {
  if (typeof window === 'undefined') return
  const params = buildHomeSearchParams()
  if (selectedCategoryId.value && selectedCategoryId.value !== 'all') {
    params.set('categoryId', selectedCategoryId.value)
  }
  if (searchKeyword.value.trim()) {
    params.set('keyword', searchKeyword.value.trim())
  }
  if (productId) {
    params.set('productId', productId)
  }
  window.history.replaceState(null, '', `./share-home.html${params.toString() ? `?${params.toString()}` : ''}`)
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
    } catch {
      user = authStore.getUser<any>() || {}
    }
  }
  currentUserId.value = getCurrentUserId(user)
  return user
}

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

  const keyword = searchKeyword.value.trim().toLowerCase()
  if (keyword) {
    result = result.filter(product => product.name.toLowerCase().includes(keyword))
  }

  return result
})

const categoryOptions = computed(() => {
  return [
    { id: 'all', name: '全部' },
    ...categories.value.filter(category => !category.parentId),
  ]
})

const loadCategoryProducts = async (categoryId: string) => {
  const target = getHomeTargetRef()
  if ((!target.targetUserId && !target.shareCode) || categoryId === 'all') {
    categoryProducts.value = []
    return
  }

  try {
    const raw = await pcApi.getHomeProducts(target, categoryId)
    categoryProducts.value = unwrapList(raw).map(item => mapProduct(item, homeProfile.value?.id || targetUserId.value))
  } catch (error: any) {
    categoryProducts.value = getLocalCategoryProducts(categoryId)
    toast.error(error?.message || '分类产品加载失败')
  }
}

const loadHomeData = async () => {
  authStore.consumeCallbackToken()
  isLoggedIn.value = authStore.isLoggedIn()

  let user: any = {}
  if (isLoggedIn.value) {
    user = await loadCurrentUser()
  }

  if (!targetUserId.value && !shareCode.value && isLoggedIn.value) {
    targetUserId.value = getCurrentUserId(user)
  }

  if (!targetUserId.value && !shareCode.value) {
    showLoginDialog.value = true
    return
  }

  isLoading.value = true
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

    const ownerId =
      homeProfile.value.ownerUserId && homeProfile.value.ownerUserId !== 'home'
        ? homeProfile.value.ownerUserId
        : targetUserId.value
    if (ownerId) {
      homeProfile.value.ownerUserId = ownerId
      homeProfile.value.id = ownerId
      targetUserId.value = ownerId
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
    searchKeyword.value = params.get('keyword') || ''
    selectedCategoryId.value = params.get('categoryId') || params.get('cate_id') || 'all'
    selectedProductId.value = params.get('productId') || params.get('product_id') || null
    isLoggedIn.value = authStore.isLoggedIn()
    isClient.value = true
    loadHomeData()
  })
})

const handleSearch = () => {
  selectedProductId.value = null
  updateUrlState()
}

const handleCategoryChange = async (categoryId: string) => {
  selectedProductId.value = null
  selectedCategoryId.value = categoryId
  if (categoryId === 'all') {
    categoryProducts.value = []
  } else {
    await loadCategoryProducts(categoryId)
  }
  updateUrlState()
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
  updateUrlState(productId)
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const handleBackToList = () => {
  selectedProductId.value = null
  updateUrlState()
}

const handleLoginSuccess = () => {
  showLoginDialog.value = false
  isLoggedIn.value = true
  loadCurrentUser().then(() => loadHomeData())
  toast.success('登录成功')
}
</script>

<template>
  <div class="min-h-screen bg-background">
    <section v-if="isClient && homeProfile" class="page-body border-b border-border">
      <div class="page-container flex flex-col gap-6 lg:flex-row lg:items-start">
        <div class="flex shrink-0 justify-center lg:justify-start">
          <img
            :src="homeProfile.logoUrl"
            :alt="homeProfile.companyName"
            class="h-28 w-28 rounded-lg border border-border object-cover sm:h-32 sm:w-32"
          />
        </div>

        <div class="min-w-0 flex-1">
          <h1 class="text-page-title mb-2">{{ homeProfile.companyName || '商户主页' }}</h1>
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <Badge variant="outline" class="border-primary/30 bg-primary/10 text-primary">
              {{ homeProfile.industryTag }}
            </Badge>
            <span class="text-sm text-muted-foreground">
              产品 {{ homeProfile.productCount }} 个
            </span>
          </div>
          <p class="mb-4 line-clamp-2 text-body text-muted-foreground">
            {{ homeProfile.intro || '暂无简介' }}
          </p>
          <div class="space-y-1 text-sm text-muted-foreground">
            <p v-if="homeProfile.region">{{ homeProfile.region }}</p>
            <p v-if="homeProfile.address">{{ homeProfile.address }}</p>
          </div>
        </div>

        <div class="grid shrink-0 grid-cols-3 gap-2 lg:w-32 lg:grid-cols-1">
          <Button
            :variant="isHomeFavorited ? 'default' : 'outline'"
            size="sm"
            class="gap-2"
            :disabled="!isClient"
            @click="handleFavoriteHome"
          >
            <SafeIcon name="Heart" :size="16" :fill="isHomeFavorited ? 'currentColor' : 'none'" />
            {{ isHomeFavorited ? '已收藏' : '收藏' }}
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="gap-2"
            :disabled="!isClient"
            @click="handleContactMerchant"
          >
            <SafeIcon name="MessageCircle" :size="16" />
            {{ homeProfile.contactServiceName }}
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="gap-2"
            :disabled="!isClient"
            @click="handleShareHome"
          >
            <SafeIcon name="Share2" :size="16" />
            分享
          </Button>
        </div>
      </div>
    </section>

    <section
      v-if="isClient && homeProfile && categoryOptions.length > 0 && !isProductDetailMode"
      class="border-b border-border bg-background px-6 md:px-8"
    >
      <div class="page-container">
        <Tabs :model-value="selectedCategoryId || 'all'" @update:model-value="value => handleCategoryChange(String(value))" class="w-full">
          <TabsList class="h-auto w-full justify-start gap-1 overflow-x-auto rounded-none bg-transparent p-0">
            <TabsTrigger
              v-for="cat in categoryOptions"
              :key="cat.id"
              :value="cat.id"
              class="shrink-0 rounded-none border-b-2 border-transparent px-4 py-4 font-medium text-muted-foreground transition-colors data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-primary"
            >
              {{ cat.name }}
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>
    </section>

    <section v-if="isClient && (isLoading || homeProfile)" class="page-body">
      <div class="page-container">
        <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
          <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
          加载中...
        </div>

        <ProductDetailPanel
          v-else-if="isProductDetailMode && selectedProductId"
          :product-id="selectedProductId"
          :target-user-id="targetUserId"
          :share-code="shareCode"
          :is-logged-in="isLoggedIn"
          :current-user-id="currentUserId"
          @back="handleBackToList"
          @login-required="showLoginDialog = true"
        />

        <template v-else-if="homeProfile">
          <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-sm">
              <Input
                v-model="searchKeyword"
                placeholder="搜索产品"
                class="pr-10"
                @keyup.enter="handleSearch"
              />
              <Button
                variant="ghost"
                size="icon"
                class="absolute right-0 top-0 h-full"
                @click="handleSearch"
              >
                <SafeIcon name="Search" :size="16" />
              </Button>
            </div>
            <span class="text-sm text-muted-foreground">共 {{ filteredProducts.length }} 个产品</span>
          </div>

          <div v-if="filteredProducts.length === 0" class="py-16">
            <EmptyState
              icon="Package"
              title="暂无产品"
              description="该主页暂未展示产品"
            />
          </div>

          <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <button
              v-for="product in filteredProducts"
              :key="product.id"
              type="button"
              class="product-card group cursor-pointer overflow-hidden rounded-lg text-left"
              @click="handleProductClick(product.id)"
            >
              <div class="relative aspect-square w-full overflow-hidden bg-muted">
                <img
                  :src="product.coverUrl"
                  :alt="product.name"
                  loading="lazy"
                  class="h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10" />
              </div>

              <div class="card-padding space-y-2">
                <h3 class="truncate text-item-title font-medium">
                  {{ product.name || '未命名产品' }}
                </h3>
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                  <span>花色图 {{ product.colorChartCount }} 张</span>
                  <span>详情图 {{ product.detailChartCount }} 张</span>
                </div>
              </div>
            </button>
          </div>
        </template>
      </div>
    </section>

    <LoginDialog
      :open="showLoginDialog && isClient"
      @update:open="val => (showLoginDialog = val)"
      @login-success="handleLoginSuccess"
    />

    <ShareDialog
      v-if="isClient && homeProfile"
      :open="showShareDialog"
      :home-profile="homeProfile"
      @update:open="val => (showShareDialog = val)"
    />

    <ContactDialog
      v-if="isClient && homeProfile"
      :open="showContactDialog"
      :home-profile="homeProfile"
      @update:open="val => (showContactDialog = val)"
    />
  </div>
</template>

<style scoped>
.product-card {
  @apply transition-all duration-200;
}

.product-card:hover {
  @apply shadow-card;
  transform: translateY(-2px);
}
</style>
