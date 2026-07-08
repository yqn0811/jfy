
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import SafeIcon from '@/components/common/SafeIcon.vue'
import Pagination from '@/components/common/Pagination.vue'
import ProductTable from '@/components/product_management/ProductTable.vue'
import ProductCreateDialog from '@/components/product_management/ProductCreateDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import ProductShareDialog from '@/components/product_detail/ShareDialog.vue'
import type { ProductData } from '@/data/ProductData'
import type { CategoryData } from '@/data/CategoryData'
import { authStore, pcApi } from '@/lib/api'
import { mapCategory, mapProduct, unwrapList } from '@/lib/jfyuntu-mappers'
import { cn } from '@/lib/utils'

const isClient = ref(true)

const allProducts = ref<ProductData[]>([])
const allCategories = ref<CategoryData[]>([])
const isLoading = ref(false)

const keyword = ref('')
const selectedCategoryId = ref('all')
const selectedStatus = ref('all')
const sortKey = ref('')
const sortDirection = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const pageSize = ref(20)

const deleteConfirmOpen = ref(false)
const productToDelete = ref<ProductData | null>(null)
const shareDialogOpen = ref(false)
const productToShare = ref<ProductData | null>(null)
const currentUserId = ref('')
const createDialogOpen = ref(false)
const isCreatingProduct = ref(false)

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const urlKeyword = params.get('keyword')
    const urlCategoryId = params.get('categoryId')
    const urlStatus = params.get('status')

    if (urlKeyword) keyword.value = urlKeyword
    if (urlCategoryId) selectedCategoryId.value = urlCategoryId
    if (urlStatus) selectedStatus.value = urlStatus

    isClient.value = true
    const user = authStore.getUser<any>() || {}
    currentUserId.value = String(user?.id || user?.uid || '')
    loadData()
  })
})

const filteredProducts = computed(() => {
  let result = allProducts.value
  if (selectedCategoryId.value !== 'all') result = result.filter(item => item.categoryId === selectedCategoryId.value)
  if (selectedStatus.value !== 'all') result = result.filter(item => item.visibility === selectedStatus.value)
  if (keyword.value.trim()) {
    const kw = keyword.value.trim().toLowerCase()
    result = result.filter(item => item.name.toLowerCase().includes(kw) || item.intro.toLowerCase().includes(kw))
  }
  if (sortKey.value) {
    result = [...result].sort((a: any, b: any) => {
      const av = a[sortKey.value]
      const bv = b[sortKey.value]
      if (av === bv) return 0
      return av > bv ? (sortDirection.value === 'desc' ? -1 : 1) : (sortDirection.value === 'desc' ? 1 : -1)
    })
  }
  return result
})

const loadData = async () => {
  isLoading.value = true
  try {
    const [productsRaw, categoriesRaw] = await Promise.all([
      pcApi.getManagementProducts({ page: 1, limit: 500 }),
      pcApi.getManagementCategories({ page: 1, limit: 500 }),
    ])
    if (!currentUserId.value) {
      const user = authStore.getUser<any>() || {}
      currentUserId.value = String(user?.id || user?.uid || '')
    }
    allProducts.value = unwrapList(productsRaw).map(item => mapProduct(item))
    allCategories.value = unwrapList(categoriesRaw).map(item => mapCategory(item))
  } catch (error: any) {
    toast.error(error?.message || '产品列表加载失败')
  } finally {
    isLoading.value = false
  }
}

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredProducts.value.slice(start, end)
})

const totalItems = computed(() => filteredProducts.value.length)

const handleSearch = () => {
  currentPage.value = 1
  const params = new URLSearchParams()
  if (keyword.value) params.set('keyword', keyword.value)
  if (selectedCategoryId.value !== 'all') params.set('categoryId', selectedCategoryId.value)
  if (selectedStatus.value !== 'all') params.set('status', selectedStatus.value)

  const queryString = params.toString()
  const newUrl = queryString ? `./product-management.html?${queryString}` : './product-management.html'
  window.history.replaceState({}, '', newUrl)
}

const handleReset = () => {
  keyword.value = ''
  selectedCategoryId.value = 'all'
  selectedStatus.value = 'all'
  sortKey.value = ''
  sortDirection.value = 'asc'
  currentPage.value = 1
  window.history.replaceState({}, '', './product-management.html')
}

const handleCategoryChange = (value: string) => {
  selectedCategoryId.value = value
  currentPage.value = 1
  handleSearch()
}

const handleStatusChange = (value: string) => {
  selectedStatus.value = value
  currentPage.value = 1
  handleSearch()
}

const handleSort = (key: string) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDirection.value = 'asc'
  }
  currentPage.value = 1
}

const handleNewProduct = () => {
  createDialogOpen.value = true
}

const handleCreateProduct = async (data: {
  name: string
  intro: string
  categoryId: string
  visibility: ProductData['visibility']
  hideDetailImage: boolean
}) => {
  isCreatingProduct.value = true
  try {
    const categoryIds = data.categoryId === 'none' ? [] : [data.categoryId]
    await pcApi.createProductOrCategory({
      fid: categoryIds,
      folder_type: 2,
      folder_name: data.name,
      folder_desc: data.intro,
      category_ids: categoryIds,
      private_type: data.visibility === 'private' ? 2 : data.visibility === 'shared' ? 4 : 1,
      hide_detail_pictures: data.hideDetailImage ? 1 : 0,
      pic_ids: [],
      detail_pic_ids: [],
      new_thumb: '',
      allow_draft: 1,
    })
    toast.success('产品已创建')
    createDialogOpen.value = false
    currentPage.value = 1
    await loadData()
  } catch (error: any) {
    toast.error(error?.message || '创建失败')
  } finally {
    isCreatingProduct.value = false
  }
}

const handleEditProduct = (productId: string) => {
  window.location.href = `./product-edit.html?productId=${productId}`
}

const handleBatchUpload = (productId: string) => {
  window.location.href = `./batch-upload-settings.html?productId=${productId}`
}

const handleShareProduct = (product: ProductData) => {
  productToShare.value = product
  if (!currentUserId.value) {
    const user = authStore.getUser<any>() || {}
    currentUserId.value = String(user?.id || user?.uid || product.ownerUserId || '')
  }
  shareDialogOpen.value = true
}

const handleDeleteProduct = (product: ProductData) => {
  productToDelete.value = product
  deleteConfirmOpen.value = true
}

const confirmDelete = async () => {
  if (!productToDelete.value) return

  try {
    await pcApi.deleteProductOrFolder(productToDelete.value.id)
    toast.success('产品已删除')
    await loadData()
    currentPage.value = 1
  } catch (error: any) {
    toast.error(error?.message || '删除失败')
  }

  deleteConfirmOpen.value = false
  productToDelete.value = null
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  const tableElement = document.querySelector('[data-table-scroll]')
  if (tableElement) {
    tableElement.scrollTop = 0
  }
}

const categoryOptions = computed(() => [
  { label: '全部分类', value: 'all' },
  ...allCategories.value.map(cat => ({ label: cat.name, value: cat.id }))
])

const categoryNameMap = computed(() => {
  const map: Record<string, string> = {}
  allCategories.value.forEach(cat => {
    map[cat.id] = cat.name
  })
  return map
})

const statusOptions = [
  { label: '全部状态', value: 'all' },
  { label: '公开', value: 'public' },
  { label: '私密', value: 'private' },
  { label: '分享可见', value: 'shared' },
]
</script>

<template>
  <div class="flex flex-col h-full min-h-0 bg-background">
    <!-- 页面标题 -->
    <div class="page-body border-b border-border">
      <div class="flex items-center justify-between">
        <h1 class="text-page-title">产品管理</h1>
        <Button
          size="lg"
          class="gap-2"
          @click="handleNewProduct"
        >
          <SafeIcon name="Plus" :size="18" />
          新建产品
        </Button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar mx-0 rounded-none border-b border-t-0 gap-3">
      <div class="flex-1 min-w-0 flex flex-wrap items-center gap-3">
        <!-- 搜索框 -->
        <div class="flex items-center gap-2 flex-1 min-w-[200px] max-w-sm">
          <Input
            v-model="keyword"
            placeholder="搜索产品名称..."
            class="h-9 bg-muted/50 border-none focus-visible:ring-1"
            @keyup.enter="handleSearch"
          />
          <Button
            variant="outline"
            size="sm"
            class="h-9 px-4"
            @click="handleSearch"
          >
            <SafeIcon name="Search" :size="16" />
          </Button>
        </div>

        <!-- 分类筛选 -->
        <Select :model-value="selectedCategoryId" @update:model-value="handleCategoryChange">
          <SelectTrigger class="w-40 h-9 bg-muted/50 border-none">
            <SelectValue placeholder="选择分类" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="opt in categoryOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <!-- 状态筛选 -->
        <Select :model-value="selectedStatus" @update:model-value="handleStatusChange">
          <SelectTrigger class="w-40 h-9 bg-muted/50 border-none">
            <SelectValue placeholder="选择状态" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="opt in statusOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <!-- 重置按钮 -->
        <Button
          variant="ghost"
          size="sm"
          class="h-9 px-3 text-muted-foreground hover:text-foreground"
          @click="handleReset"
        >
          <SafeIcon name="RotateCcw" :size="16" class="mr-1" />
          重置
        </Button>
      </div>
    </div>

    <!-- 产品表格 -->
    <div class="flex-1 min-h-0 overflow-y-auto" data-table-scroll>
      <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
        <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
        加载中...
      </div>
      <ProductTable
        v-else
        :products="paginatedProducts"
        :category-name-map="categoryNameMap"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        @sort="handleSort"
        @edit="handleEditProduct"
        @delete="handleDeleteProduct"
        @batch-upload="handleBatchUpload"
        @share="handleShareProduct"
      />

      <!-- 空状态 -->
      <div v-if="!isLoading && totalItems === 0" class="empty-state">
        <SafeIcon name="Package" :size="48" class="text-muted-foreground/40" />
        <h3 class="text-section-title text-foreground">暂无产品</h3>
        <p class="text-caption max-w-sm">
          {{ keyword || selectedCategoryId !== 'all' || selectedStatus !== 'all'
            ? '未找到匹配的产品，请调整筛选条件'
            : '还没有创建产品，点击"新建产品"开始创建' }}
        </p>
        <Button
          v-if="!keyword && selectedCategoryId === 'all' && selectedStatus === 'all'"
          size="sm"
          class="mt-4"
          @click="handleNewProduct"
        >
          <SafeIcon name="Plus" :size="16" class="mr-2" />
          新建产品
        </Button>
      </div>
    </div>

    <!-- 分页栏 -->
    <div v-if="totalItems > 0" class="border-t border-border page-body">
      <Pagination
        :current="currentPage"
        :total="totalItems"
        :page-size="pageSize"
        @change="handlePageChange"
      />
    </div>

    <!-- 删除确认弹窗 -->
    <ConfirmDialog
      :open="deleteConfirmOpen"
      title="删除产品"
      :description="`确定要删除产品「${productToDelete?.name || '未命名产品'}」吗？删除后可在回收站恢复。`"
      confirm-text="确认删除"
      cancel-text="取消"
      variant="destructive"
      @confirm="confirmDelete"
      @update:open="(val) => (deleteConfirmOpen = val)"
    />

    <ProductShareDialog
      :open="shareDialogOpen"
      :product-id="productToShare?.id || ''"
      :target-user-id="currentUserId || productToShare?.ownerUserId || ''"
      @update:open="(val) => (shareDialogOpen = val)"
    />

    <ProductCreateDialog
      :open="createDialogOpen"
      :categories="allCategories"
      :saving="isCreatingProduct"
      @update:open="(val) => (createDialogOpen = val)"
      @create="handleCreateProduct"
    />
  </div>
</template>
