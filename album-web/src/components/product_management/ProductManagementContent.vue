
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
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
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import ProductShareDialog from '@/components/product_detail/ShareDialog.vue'
import ProductEditForm from '@/components/product_edit/ProductEditForm.vue'
import BatchUploadSettingsContent from '@/components/batch_upload_settings/BatchUploadSettingsContent.vue'
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
const currentShareCode = ref('')
const createDialogOpen = ref(false)
const editDialogOpen = ref(false)
const editingProductId = ref('')
const batchUploadDialogOpen = ref(false)
const batchUploadProductId = ref('')
const selectedProductIds = ref<string[]>([])
const batchDeleteConfirmOpen = ref(false)
const batchCategoryDialogOpen = ref(false)
const batchCategoryIds = ref<string[]>([])
const isBatchOperating = ref(false)

const applyUserShareInfo = (user: any = {}) => {
  currentUserId.value = String(user?.id || user?.uid || currentUserId.value || '')
  currentShareCode.value = String(user?.share_code || user?.shareCode || user?.home_share_code || currentShareCode.value || '')
}

const ensureUserShareInfo = async () => {
  applyUserShareInfo(authStore.getUser<any>() || {})
  if (currentUserId.value && currentShareCode.value) return
  try {
    const profile = await pcApi.getCurrentUser()
    authStore.setUser(profile)
    applyUserShareInfo(profile)
  } catch {
    // 分享码缺失时仍保留 uid 兼容入口
  }
}

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
    ensureUserShareInfo()
    loadData()
  })
})

const filteredProducts = computed(() => {
  let result = allProducts.value
  if (selectedCategoryId.value !== 'all') {
    result = result.filter(item => {
      const categoryIds = item.categoryIds?.length ? item.categoryIds : item.categoryId ? [item.categoryId] : []
      return categoryIds.includes(selectedCategoryId.value)
    })
  }
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
      await ensureUserShareInfo()
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

watch([filteredProducts, currentPage], () => {
  const visibleIds = new Set(filteredProducts.value.map(item => item.id))
  selectedProductIds.value = selectedProductIds.value.filter(id => visibleIds.has(id))
})

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

const handleEditProduct = (productId: string) => {
  editingProductId.value = productId
  editDialogOpen.value = true
}

const handleBatchUpload = (productId: string) => {
  batchUploadProductId.value = productId
  batchUploadDialogOpen.value = true
}

const handleShareProduct = async (product: ProductData) => {
  productToShare.value = product
  if (!currentUserId.value) {
    const user = authStore.getUser<any>() || {}
    currentUserId.value = String(user?.id || user?.uid || product.ownerUserId || '')
    currentShareCode.value = String(user?.share_code || user?.shareCode || user?.home_share_code || currentShareCode.value)
  }
  if (!currentShareCode.value) {
    await ensureUserShareInfo()
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

const selectedProducts = computed(() => {
  const selected = new Set(selectedProductIds.value)
  return allProducts.value.filter(item => selected.has(item.id))
})

const paginatedProductIds = computed(() => paginatedProducts.value.map(item => item.id))
const isPageAllSelected = computed(() => {
  return paginatedProductIds.value.length > 0 && paginatedProductIds.value.every(id => selectedProductIds.value.includes(id))
})

const toggleSelectProduct = (productId: string) => {
  if (selectedProductIds.value.includes(productId)) {
    selectedProductIds.value = selectedProductIds.value.filter(id => id !== productId)
    return
  }
  selectedProductIds.value = [...selectedProductIds.value, productId]
}

const toggleSelectPage = () => {
  if (isPageAllSelected.value) {
    const pageIds = new Set(paginatedProductIds.value)
    selectedProductIds.value = selectedProductIds.value.filter(id => !pageIds.has(id))
    return
  }
  selectedProductIds.value = Array.from(new Set([...selectedProductIds.value, ...paginatedProductIds.value]))
}

const openBatchDelete = () => {
  if (selectedProductIds.value.length === 0) {
    toast.error('请先选择产品')
    return
  }
  batchDeleteConfirmOpen.value = true
}

const confirmBatchDelete = async () => {
  if (selectedProductIds.value.length === 0) return
  isBatchOperating.value = true
  try {
    await Promise.all(selectedProductIds.value.map(id => pcApi.deleteProductOrFolder(id)))
    toast.success(`已删除 ${selectedProductIds.value.length} 个产品`)
    selectedProductIds.value = []
    batchDeleteConfirmOpen.value = false
    currentPage.value = 1
    await loadData()
  } catch (error: any) {
    toast.error(error?.message || '批量删除失败')
  } finally {
    isBatchOperating.value = false
  }
}

const toggleBatchCategory = (categoryId: string) => {
  if (batchCategoryIds.value.includes(categoryId)) {
    batchCategoryIds.value = batchCategoryIds.value.filter(id => id !== categoryId)
    return
  }
  batchCategoryIds.value = [...batchCategoryIds.value, categoryId]
}

const openBatchCategory = () => {
  if (selectedProductIds.value.length === 0) {
    toast.error('请先选择产品')
    return
  }
  batchCategoryIds.value = []
  batchCategoryDialogOpen.value = true
}

const confirmBatchCategory = async () => {
  if (selectedProductIds.value.length === 0) return
  if (batchCategoryIds.value.length === 0) {
    toast.error('请选择要添加的分类')
    return
  }

  isBatchOperating.value = true
  try {
    await Promise.all(selectedProducts.value.map(product => {
      const currentCategoryIds = product.categoryIds?.length ? product.categoryIds : product.categoryId ? [product.categoryId] : []
      const nextCategoryIds = Array.from(new Set([...currentCategoryIds, ...batchCategoryIds.value]))
      return pcApi.editProductOrCategory({
        fid: product.id,
        category_ids: nextCategoryIds,
      })
    }))
    toast.success(`已添加到分类（${selectedProductIds.value.length} 个产品）`)
    selectedProductIds.value = []
    batchCategoryDialogOpen.value = false
    await loadData()
  } catch (error: any) {
    toast.error(error?.message || '添加分类失败')
  } finally {
    isBatchOperating.value = false
  }
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  const tableElement = document.querySelector('[data-table-scroll]')
  if (tableElement) {
    tableElement.scrollTop = 0
  }
}

interface CategoryTreeNode extends CategoryData {
  level: number
  children: CategoryTreeNode[]
}

const categoryProductCountMap = computed(() => {
  const map: Record<string, number> = {}
  allProducts.value.forEach(product => {
    const categoryIds = product.categoryIds?.length ? product.categoryIds : product.categoryId ? [product.categoryId] : []
    categoryIds.forEach(id => {
      map[id] = (map[id] || 0) + 1
    })
  })
  return map
})

const categoryTree = computed<CategoryTreeNode[]>(() => {
  const nodeMap = new Map<string, CategoryTreeNode>()
  allCategories.value.forEach(category => {
    nodeMap.set(category.id, { ...category, level: 0, children: [] })
  })
  const roots: CategoryTreeNode[] = []
  nodeMap.forEach(node => {
    const parent = node.parentId ? nodeMap.get(node.parentId) : null
    if (parent) {
      node.level = parent.level + 1
      parent.children.push(node)
    } else {
      roots.push(node)
    }
  })
  return roots
})

const flatCategoryTree = computed(() => {
  const rows: CategoryTreeNode[] = []
  const walk = (nodes: CategoryTreeNode[], level = 0) => {
    nodes.forEach(node => {
      rows.push({ ...node, level })
      if (node.children.length) walk(node.children, level + 1)
    })
  }
  walk(categoryTree.value)
  return rows
})

const selectedCategoryName = computed(() => {
  if (selectedCategoryId.value === 'all') return '全部产品'
  return categoryNameMap.value[selectedCategoryId.value] || '当前分类'
})

const getCategoryCount = (categoryId: string) => categoryProductCountMap.value[categoryId] || 0

const handleOpenShareHome = async () => {
  if (!currentShareCode.value) {
    await ensureUserShareInfo()
  }
  const params = currentShareCode.value
    ? `?code=${encodeURIComponent(currentShareCode.value)}`
    : currentUserId.value
      ? `?uid=${encodeURIComponent(currentUserId.value)}`
      : ''
  window.open(`./share-home.html${params}`, '_blank')
}

const handleOpenRecycle = () => {
  window.location.href = './recycling-bin.html'
}

const handleOpenProductPreview = (productId: string) => {
  if (!productId) return
  window.open(`./product-detail.html?productId=${encodeURIComponent(productId)}`, '_blank')
}

const handleProductSaved = async () => {
  editDialogOpen.value = false
  editingProductId.value = ''
  await loadData()
}

const handleProductCreated = async () => {
  createDialogOpen.value = false
  currentPage.value = 1
  await loadData()
}

const handleBatchSaved = async () => {
  await loadData()
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
        <div>
          <h1 class="text-page-title">产品列表 <span class="text-base font-normal text-muted-foreground">({{ selectedCategoryName }})</span></h1>
          <p class="text-caption mt-1">按分类维护产品、图片、批量上传和分享设置</p>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="outline" class="gap-2" @click="handleOpenShareHome">
            <SafeIcon name="Share2" :size="18" />
            分享主页
          </Button>
          <Button size="lg" class="gap-2" @click="handleNewProduct">
            <SafeIcon name="Plus" :size="18" />
            新建产品
          </Button>
          <Button variant="outline" class="gap-2" @click="handleOpenRecycle">
            <SafeIcon name="Archive" :size="18" />
            回收站
          </Button>
        </div>
      </div>
    </div>

    <div class="flex min-h-0 flex-1">
      <!-- 分类树 -->
      <aside class="w-72 shrink-0 border-r border-border bg-card/50 p-4">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-foreground">分类</h2>
          <span class="text-xs text-muted-foreground">{{ allCategories.length }} 个</span>
        </div>
        <div class="space-y-1">
          <button
            type="button"
            :class="cn(
              'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors',
              selectedCategoryId === 'all' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'
            )"
            @click="handleCategoryChange('all')"
          >
            <span class="flex items-center gap-2">
              <SafeIcon name="Layers" :size="16" />
              全部产品
            </span>
            <span class="text-xs opacity-80">{{ allProducts.length }}</span>
          </button>

          <button
            v-for="category in flatCategoryTree"
            :key="category.id"
            type="button"
            :class="cn(
              'flex w-full items-center justify-between rounded-md py-2 pr-3 text-left text-sm transition-colors',
              selectedCategoryId === category.id ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'
            )"
            :style="{ paddingLeft: `${12 + category.level * 18}px` }"
            @click="handleCategoryChange(category.id)"
          >
            <span class="flex min-w-0 items-center gap-2">
              <SafeIcon :name="category.children.length ? 'FolderTree' : 'Folder'" :size="16" class="shrink-0" />
              <span class="truncate">{{ category.name }}</span>
            </span>
            <span class="text-xs opacity-80">{{ getCategoryCount(category.id) }}</span>
          </button>
        </div>
      </aside>

      <main class="flex min-w-0 flex-1 flex-col">
        <!-- 筛选栏 -->
        <div class="border-b border-border bg-background px-6 py-4">
          <div class="flex items-center justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-center gap-2">
              <div class="relative min-w-[280px] max-w-xl flex-1">
                <SafeIcon name="Search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <Input
                  v-model="keyword"
                  placeholder="搜索产品名称、描述..."
                  class="h-10 border-none bg-muted/50 pl-10 focus-visible:ring-1"
                  @keyup.enter="handleSearch"
                />
              </div>
              <Button variant="outline" class="h-10 px-4" @click="handleSearch">
                搜索
              </Button>
            </div>

            <div class="flex items-center gap-3">
              <Select :model-value="selectedStatus" @update:model-value="handleStatusChange">
                <SelectTrigger class="h-10 w-36 border-none bg-muted/50">
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

              <Button variant="ghost" class="h-10 px-3 text-muted-foreground hover:text-foreground" @click="handleReset">
                <SafeIcon name="RotateCcw" :size="16" class="mr-1" />
                重置
              </Button>
            </div>
          </div>
        </div>

        <!-- 产品卡片 -->
        <div class="min-h-0 flex-1 overflow-y-auto p-6" data-table-scroll>
          <div v-if="isLoading" class="py-16 text-center text-muted-foreground">
            <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
            加载中...
          </div>
          <template v-else>
            <div
              v-if="paginatedProducts.length > 0"
              :class="cn(
                'mb-5 flex items-center justify-between rounded-lg border px-4 py-3 shadow-sm transition-colors',
                selectedProductIds.length > 0 ? 'border-primary/30 bg-primary/5' : 'border-border bg-card'
              )"
            >
              <button
                type="button"
                class="inline-flex items-center gap-3 text-sm font-medium text-foreground"
                @click="toggleSelectPage"
              >
                <span
                  :class="cn(
                    'flex h-6 w-6 items-center justify-center rounded-md border-2 transition-colors',
                    isPageAllSelected ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background'
                  )"
                >
                  <SafeIcon v-if="isPageAllSelected" name="Check" :size="16" />
                </span>
                <span>全选当前页（{{ paginatedProducts.length }}项）</span>
                <span v-if="selectedProductIds.length" class="rounded-full bg-primary px-3 py-1 text-xs text-primary-foreground">
                  已选 {{ selectedProductIds.length }}
                </span>
              </button>

              <div class="flex items-center gap-2">
                <Button
                  variant="outline"
                  class="gap-2"
                  :disabled="selectedProductIds.length === 0 || isBatchOperating"
                  @click="openBatchDelete"
                >
                  <SafeIcon name="Trash2" :size="16" />
                  批量删除
                </Button>
                <Button
                  variant="outline"
                  class="gap-2"
                  :disabled="selectedProductIds.length === 0 || isBatchOperating"
                  @click="openBatchCategory"
                >
                  <SafeIcon name="FolderPlus" :size="16" />
                  添加到分类
                </Button>
              </div>
            </div>
            <ProductTable
              :products="paginatedProducts"
              :category-name-map="categoryNameMap"
              :sort-key="sortKey"
              :sort-direction="sortDirection"
              :selected-ids="selectedProductIds"
              @sort="handleSort"
              @edit="handleEditProduct"
              @delete="handleDeleteProduct"
              @batch-upload="handleBatchUpload"
              @share="handleShareProduct"
              @toggle-select="toggleSelectProduct"
            />
          </template>
        </div>

        <!-- 分页栏 -->
        <div v-if="totalItems > 0" class="border-t border-border px-6">
          <Pagination
            :current="currentPage"
            :total="totalItems"
            :page-size="pageSize"
            @change="handlePageChange"
          />
        </div>
      </main>
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

    <ConfirmDialog
      :open="batchDeleteConfirmOpen"
      title="批量删除产品"
      :description="`确定要删除选中的 ${selectedProductIds.length} 个产品吗？删除后可在回收站恢复。`"
      confirm-text="批量删除"
      cancel-text="取消"
      variant="destructive"
      @confirm="confirmBatchDelete"
      @update:open="(val) => (batchDeleteConfirmOpen = val)"
    />

    <ProductShareDialog
      :open="shareDialogOpen"
      :product-id="productToShare?.id || ''"
      :target-user-id="currentUserId || productToShare?.ownerUserId || ''"
      :share-code="currentShareCode"
      @update:open="(val) => (shareDialogOpen = val)"
    />

    <Dialog :open="editDialogOpen" @update:open="(val) => (editDialogOpen = val)">
      <DialogScrollContent class="max-h-[92vh] max-w-[1180px] overflow-hidden p-0">
        <div class="flex max-h-[92vh] min-h-[720px] flex-col">
          <DialogHeader class="border-b border-border px-6 py-5">
            <DialogTitle>编辑产品</DialogTitle>
            <DialogDescription>维护产品基础信息、花色图、详情图和详情图展示开关</DialogDescription>
          </DialogHeader>
          <div class="min-h-0 flex-1 px-6 py-5">
            <ProductEditForm
              v-if="editDialogOpen"
              :product-id="editingProductId"
              embedded
              @cancel="editDialogOpen = false"
              @saved="handleProductSaved"
              @preview="handleOpenProductPreview"
            />
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

    <Dialog :open="createDialogOpen" @update:open="(val) => (createDialogOpen = val)">
      <DialogScrollContent class="max-h-[92vh] max-w-[1180px] overflow-hidden p-0">
        <div class="flex max-h-[92vh] min-h-[720px] flex-col">
          <DialogHeader class="border-b border-border px-6 py-5">
            <DialogTitle>新建产品</DialogTitle>
            <DialogDescription>填写产品基础信息，并上传花色图和详情图</DialogDescription>
          </DialogHeader>
          <div class="min-h-0 flex-1 px-6 py-5">
            <ProductEditForm
              v-if="createDialogOpen"
              embedded
              @cancel="createDialogOpen = false"
              @saved="handleProductCreated"
              @preview="handleOpenProductPreview"
            />
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

    <Dialog :open="batchCategoryDialogOpen" @update:open="(val) => (batchCategoryDialogOpen = val)">
      <DialogScrollContent class="max-h-[82vh] max-w-[640px] overflow-hidden p-0">
        <div class="flex max-h-[82vh] min-h-[420px] flex-col">
          <DialogHeader class="border-b border-border px-6 py-5">
            <DialogTitle>添加到分类</DialogTitle>
            <DialogDescription>为已选的 {{ selectedProductIds.length }} 个产品追加分类，不会移除原有分类</DialogDescription>
          </DialogHeader>
          <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div v-if="allCategories.length" class="flex flex-wrap gap-2">
              <button
                v-for="cat in allCategories"
                :key="cat.id"
                type="button"
                :class="cn(
                  'inline-flex h-10 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                  batchCategoryIds.includes(cat.id)
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-border bg-card text-foreground hover:border-primary/40 hover:text-primary'
                )"
                @click="toggleBatchCategory(cat.id)"
              >
                {{ cat.name }}
                <SafeIcon
                  v-if="batchCategoryIds.includes(cat.id)"
                  name="Check"
                  :size="14"
                  class="ml-1.5"
                />
              </button>
            </div>
            <p v-else class="py-12 text-center text-sm text-muted-foreground">暂无分类</p>
          </div>
          <DialogFooter class="border-t border-border px-6 py-4">
            <Button variant="outline" :disabled="isBatchOperating" @click="batchCategoryDialogOpen = false">取消</Button>
            <Button :disabled="isBatchOperating || batchCategoryIds.length === 0" @click="confirmBatchCategory">
              <SafeIcon v-if="isBatchOperating" name="Loader2" :size="16" class="mr-2 animate-spin" />
              添加到分类
            </Button>
          </DialogFooter>
        </div>
      </DialogScrollContent>
    </Dialog>

    <Dialog :open="batchUploadDialogOpen" @update:open="(val) => (batchUploadDialogOpen = val)">
      <DialogScrollContent class="max-h-[92vh] max-w-[1160px] overflow-hidden p-0">
        <div class="flex max-h-[92vh] min-h-[620px] flex-col">
          <DialogHeader class="border-b border-border px-6 py-4">
            <DialogTitle>批量上传设置</DialogTitle>
            <DialogDescription>生成协作上传链接、二维码和访问密码</DialogDescription>
          </DialogHeader>
          <div class="min-h-0 flex-1 px-6 py-4">
            <BatchUploadSettingsContent
              v-if="batchUploadDialogOpen"
              :product-id="batchUploadProductId"
              embedded
              @cancel="batchUploadDialogOpen = false"
              @saved="handleBatchSaved"
              @view-product="handleOpenProductPreview"
            />
          </div>
        </div>
      </DialogScrollContent>
    </Dialog>

  </div>
</template>
