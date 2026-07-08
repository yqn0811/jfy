
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import Breadcrumb from '@/components/common/Breadcrumb.vue'
import CategoryInfo from '@/components/category_browser/CategoryInfo.vue'
import CategoryTabs from '@/components/category_browser/CategoryTabs.vue'
import SubCategoryGrid from '@/components/category_browser/SubCategoryGrid.vue'
import ProductGrid from '@/components/category_browser/ProductGrid.vue'
import AccessDeniedPlaceholder from '@/components/category_browser/AccessDeniedPlaceholder.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { authStore, pcApi } from '@/lib/api'
import { mapCategory, mapHomeProfile, mapProduct, unwrapList } from '@/lib/jfyuntu-mappers'
import type { CategoryVO } from '@/data/CategoryService'
import type { ProductData } from '@/data/ProductData'
import type { HomeProfileData } from '@/data/HomeProfileData'

const isClient = ref(true)
const isLoading = ref(false)
const categoryId = ref<string>('')
const parentId = ref<string>('')
const targetUserId = ref<string>('')

const currentCategory = ref<CategoryVO | undefined>(undefined)
const parentCategory = ref<CategoryVO | undefined>(undefined)
const subCategories = ref<CategoryVO[]>([])
const products = ref<ProductData[]>([])
const homeProfile = ref<HomeProfileData | null>(null)

const activeTab = ref<'all' | 'subcategory' | 'product'>('all')
const isAccessDenied = ref(false)
const showLoginDialog = ref(false)
const isLoggedIn = ref(false)

const breadcrumbItems = computed(() => {
  const items: Array<{ label: string; href?: string }> = [
    { label: '主页', href: `./share-home.html?uid=${encodeURIComponent(targetUserId.value)}` }
  ]
  
  if (parentCategory.value) {
    items.push({
      label: parentCategory.value.name,
      href: `./category.html?uid=${encodeURIComponent(targetUserId.value)}&categoryId=${parentCategory.value.id}`
    })
  }
  
  if (currentCategory.value) {
    items.push({
      label: currentCategory.value.name
    })
  }
  
  return items
})

const filteredContent = computed(() => {
  if (activeTab.value === 'subcategory') {
    return subCategories.value
  } else if (activeTab.value === 'product') {
    return products.value
  }
  return { subCategories: subCategories.value, products: products.value }
})

const hasContent = computed(() => {
  if (activeTab.value === 'all') {
    return subCategories.value.length > 0 || products.value.length > 0
  } else if (activeTab.value === 'subcategory') {
    return subCategories.value.length > 0
  }
  return products.value.length > 0
})

const loadCategoryData = async () => {
  if (!authStore.isLoggedIn()) {
    isLoggedIn.value = false
    showLoginDialog.value = true
    return
  }
  isLoggedIn.value = true
  if (!categoryId.value) {
    isAccessDenied.value = true
    return
  }
  if (!targetUserId.value) {
    isAccessDenied.value = true
    toast.error('缺少主页用户信息')
    return
  }

  isLoading.value = true
  try {
    const [homeRaw, categoriesRaw, productsRaw] = await Promise.all([
      pcApi.getHomeInfo(targetUserId.value),
      pcApi.getHomeCategories(targetUserId.value, categoryId.value, 1),
      pcApi.getHomeProducts(targetUserId.value, categoryId.value),
    ])

    homeProfile.value = mapHomeProfile(homeRaw)
    const folderInfo = categoriesRaw?.folder_info || categoriesRaw?.current || categoriesRaw?.info
    if (!folderInfo) {
      isAccessDenied.value = true
      return
    }

    const category = mapCategory(folderInfo, homeProfile.value.id) as CategoryVO
    currentCategory.value = category
    parentCategory.value = undefined

    if (category.parentId || parentId.value) {
      try {
        const parentRaw = await pcApi.getHomeCategories(targetUserId.value, category.parentId || parentId.value, 1)
        const parentInfo = parentRaw?.folder_info || parentRaw?.current || parentRaw?.info
        if (parentInfo) parentCategory.value = mapCategory(parentInfo, homeProfile.value.id) as CategoryVO
      } catch {
        parentCategory.value = undefined
      }
    }

    subCategories.value = unwrapList(categoriesRaw).map(item => ({
      ...mapCategory(item, homeProfile.value?.id || ''),
      parentName: category.name,
    })) as CategoryVO[]

    products.value = unwrapList(productsRaw).map(item => mapProduct(item, homeProfile.value?.id || ''))
    pcApi.addVisit('category', categoryId.value).catch(() => {})

    isAccessDenied.value = false
  } catch (error: any) {
    isAccessDenied.value = true
    toast.error(error?.message || '分类加载失败')
  } finally {
    isLoading.value = false
  }
}

const handleLoginSuccess = () => {
  showLoginDialog.value = false
  isLoggedIn.value = true
  loadCategoryData()
}

onMounted(() => {
  isClient.value = false
  
  const params = new URLSearchParams(window.location.search)
  targetUserId.value = params.get('uid') || params.get('target_user_id') || ''
  categoryId.value = params.get('categoryId') || params.get('cate_id') || ''
  parentId.value = params.get('parentId') || ''

  loadCategoryData()

  requestAnimationFrame(() => {
    isClient.value = true
  })
})
</script>

<template>
  <div v-if="!isClient" class="page-body">
    <div class="page-container space-y-6">
      <div class="h-8 bg-muted rounded animate-pulse" />
      <div class="h-48 bg-muted rounded animate-pulse" />
    </div>
  </div>

  <div v-else-if="!isLoggedIn" class="page-body" />

  <div v-else-if="isAccessDenied" class="page-body">
    <AccessDeniedPlaceholder />
  </div>

  <div v-else class="page-body">
    <div class="page-container space-y-6">
      <Breadcrumb :items="breadcrumbItems" />

      <div v-if="isLoading" class="py-12 text-center text-muted-foreground">加载中...</div>

      <CategoryInfo 
        v-if="currentCategory"
        :category="currentCategory"
        :home-profile="homeProfile"
        :target-user-id="targetUserId"
      />

      <CategoryTabs 
        v-model="activeTab"
        :sub-category-count="subCategories.length"
        :product-count="products.length"
      />

      <div v-if="!hasContent" class="py-12">
        <EmptyState
          icon="FolderOpen"
          title="暂无内容"
          description="该分类下暂未展示产品"
        />
      </div>

      <template v-else>
        <SubCategoryGrid 
          v-if="activeTab === 'all' || activeTab === 'subcategory'"
          :categories="activeTab === 'all' ? subCategories : (activeTab === 'subcategory' ? subCategories : [])"
          :target-user-id="targetUserId"
        />

        <ProductGrid 
          v-if="activeTab === 'all' || activeTab === 'product'"
          :products="activeTab === 'all' ? products : (activeTab === 'product' ? products : [])"
          :target-user-id="targetUserId"
        />
      </template>
    </div>
  </div>

  <LoginDialog
    :open="showLoginDialog && isClient"
    @update:open="showLoginDialog = $event"
    @login-success="handleLoginSuccess"
  />
</template>
