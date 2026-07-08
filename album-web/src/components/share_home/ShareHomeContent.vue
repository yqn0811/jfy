
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
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
import ProductDetailDrawer from '@/components/share_home/ProductDetailDrawer.vue'
import { authStore, getCurrentUserId, pcApi } from '@/lib/api'
import { normalizeHomePayload } from '@/lib/jfyuntu-mappers'
import type { HomeProfileData } from '@/data/HomeProfileData'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductData } from '@/data/ProductData'

const isClient = ref(true)
const isLoading = ref(false)
const homeProfile = ref<HomeProfileData | null>(null)
const categories = ref<CategoryData[]>([])
const allProducts = ref<ProductData[]>([])

const searchKeyword = ref('')
const selectedCategoryId = ref<string | null>(null)
const isLoggedIn = ref(false)
const currentUserId = ref('')
const targetUserId = ref('')

const showLoginDialog = ref(false)
const showShareDialog = ref(false)
const showContactDialog = ref(false)
const selectedProductId = ref<string | null>(null)
const showProductDrawer = ref(false)

const isHomeFavorited = ref(false)

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
  return user
}

const filteredProducts = computed(() => {
  let result = allProducts.value

  if (selectedCategoryId.value && selectedCategoryId.value !== 'all') {
    result = result.filter(p => p.categoryId === selectedCategoryId.value)
  }

  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(keyword))
  }

  return result
})

const categoryOptions = computed(() => {
  return [
    { id: 'all', name: '全部' },
    ...categories.value.filter(c => !c.parentId)
  ]
})

const loadHomeData = async () => {
  authStore.consumeCallbackToken()
  if (!authStore.isLoggedIn()) {
    isLoggedIn.value = false
    showLoginDialog.value = true
    return
  }
  isLoggedIn.value = true
  let user: any = {}
  try {
    user = await loadCurrentUser()
  } catch (error: any) {
    authStore.clearToken()
    isLoggedIn.value = false
    showLoginDialog.value = true
    toast.error(error?.message || '登录已失效，请重新扫码')
    return
  }
  if (!targetUserId.value) {
    targetUserId.value = getCurrentUserId(user)
  }
  if (!targetUserId.value) {
    toast.error('登录信息不完整，请重新扫码')
    showLoginDialog.value = true
    return
  }
  isLoading.value = true
  try {
    const [homeRaw, categoriesRaw, productsRaw] = await Promise.all([
      pcApi.getHomeInfo(targetUserId.value),
      pcApi.getHomeCategories(targetUserId.value),
      pcApi.getHomeProducts(targetUserId.value),
    ])
    const normalized = normalizeHomePayload(homeRaw, categoriesRaw, productsRaw)
    homeProfile.value = normalized.home
    if (!homeProfile.value.ownerUserId || homeProfile.value.ownerUserId === 'home') {
      homeProfile.value.ownerUserId = targetUserId.value
      homeProfile.value.id = targetUserId.value
    }
    categories.value = normalized.categories
    allProducts.value = normalized.products
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
    targetUserId.value = params.get('uid') || params.get('target_user_id') || ''
    const keyword = params.get('keyword')
    const categoryId = params.get('categoryId') || params.get('cate_id')
    if (keyword) searchKeyword.value = keyword
    selectedCategoryId.value = categoryId || 'all'
    isLoggedIn.value = authStore.isLoggedIn()
    isClient.value = true
    loadHomeData()
  })
})

const handleSearch = () => {
  if (typeof window !== 'undefined' && searchKeyword.value.trim()) {
    const params = new URLSearchParams()
    if (targetUserId.value) params.set('uid', targetUserId.value)
    params.set('keyword', searchKeyword.value.trim())
    const url = `./share-home.html?${params.toString()}`
    window.history.replaceState(null, '', url)
  }
}

const handleCategoryChange = (categoryId: string) => {
  selectedCategoryId.value = categoryId
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams()
    if (targetUserId.value) params.set('uid', targetUserId.value)
    if (categoryId !== 'all') params.set('categoryId', categoryId)
    window.history.replaceState(null, '', `./share-home.html${params.toString() ? `?${params.toString()}` : ''}`)
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
  showProductDrawer.value = true
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

    <!-- 分类导航区 -->
    <section v-if="isClient && isLoggedIn && homeProfile && categoryOptions.length > 0" class="border-b border-border bg-background">
      <div class="page-container">
        <Tabs :model-value="selectedCategoryId || 'all'" @update:model-value="(value) => handleCategoryChange(String(value))" class="w-full">
          <TabsList class="w-full justify-start overflow-x-auto bg-transparent rounded-none h-auto p-0 gap-1">
            <TabsTrigger
              v-for="cat in categoryOptions"
              :key="cat.id"
              :value="cat.id"
              class="shrink-0 px-4 py-4 rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent text-muted-foreground data-[state=active]:text-primary font-medium transition-colors"
            >
              {{ cat.name }}
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>
    </section>

    <!-- 产品流区 -->
    <section v-if="isLoggedIn" class="page-body">
      <div class="page-container">
        <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
          <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
          加载中...
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

    <!-- 产品详情抽屉 -->
    <ProductDetailDrawer
      v-if="isClient && selectedProductId"
      :open="showProductDrawer"
      :product-id="selectedProductId"
      :is-logged-in="isLoggedIn"
      :current-user-id="currentUserId"
      @update:open="(val) => (showProductDrawer = val)"
      @login-required="showLoginDialog = true"
      @login-success="handleLoginSuccess"
    />
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
