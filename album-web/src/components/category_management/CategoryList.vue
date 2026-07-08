
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import Pagination from '@/components/common/Pagination.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import SearchBar from '@/components/common/SearchBar.vue'
import CategoryEditDialog from '@/components/category_management/CategoryEditDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import type { CategoryData } from '@/data/CategoryData'
import { pcApi } from '@/lib/api'
import { mapCategory, unwrapList } from '@/lib/jfyuntu-mappers'
import { cn } from '@/lib/utils'

const isClient = ref(true)
const categories = ref<CategoryData[]>([])
const isLoading = ref(false)
const keyword = ref('')
const visibilityFilter = ref<'all' | 'public' | 'private' | 'shared'>('all')
const sortBy = ref<'name' | 'productCount' | 'updatedAt'>('updatedAt')
const sortDirection = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const pageSize = 20

const editDialogOpen = ref(false)
const editingCategory = ref<CategoryData | undefined>(undefined)
const deleteConfirmOpen = ref(false)
const deleteTargetId = ref<string | undefined>(undefined)

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })

  const params = new URLSearchParams(window.location.search)
  const keywordParam = params.get('keyword')
  if (keywordParam) {
    keyword.value = keywordParam
  }
  loadCategories()
})

const loadCategories = async () => {
  isLoading.value = true
  try {
    const raw = await pcApi.getManagementCategories({ page: 1, limit: 500 })
    categories.value = unwrapList(raw).map(item => mapCategory(item))
  } catch (error: any) {
    toast.error(error?.message || '分类加载失败')
  } finally {
    isLoading.value = false
  }
}

const filteredCategories = computed(() => {
  let result = categories.value

  if (keyword.value.trim()) {
    const kw = keyword.value.toLowerCase()
    result = result.filter(cat =>
      cat.name.toLowerCase().includes(kw) ||
      cat.intro.toLowerCase().includes(kw)
    )
  }

  if (visibilityFilter.value !== 'all') {
    result = result.filter(cat => cat.visibility === visibilityFilter.value)
  }

  const sortKey = sortBy.value
  result = [...result].sort((a, b) => {
    const aVal = (a as any)[sortKey]
    const bVal = (b as any)[sortKey]
    if (aVal === bVal) return 0
    const direction = sortDirection.value === 'desc' ? -1 : 1
    return aVal > bVal ? direction : -direction
  })

  return result
})

const paginatedCategories = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  const end = start + pageSize
  return filteredCategories.value.slice(start, end)
})

const totalItems = computed(() => filteredCategories.value.length)

const handleCreateCategory = () => {
  editingCategory.value = undefined
  editDialogOpen.value = true
}

const handleEditCategory = (category: CategoryData) => {
  editingCategory.value = category
  editDialogOpen.value = true
}

const handleDeleteCategory = (categoryId: string) => {
  deleteTargetId.value = categoryId
  deleteConfirmOpen.value = true
}

const confirmDelete = async () => {
  if (!deleteTargetId.value) return
  try {
    await pcApi.deleteProductOrFolder(deleteTargetId.value)
    toast.success('分类已删除')
    await loadCategories()
    if (paginatedCategories.value.length === 0 && currentPage.value > 1) currentPage.value--
  } catch (error: any) {
    toast.error(error?.message || '删除失败')
  }
  deleteTargetId.value = undefined
}

const buildCategoryPayload = (data: CategoryData) => ({
  fid: data.id,
  folder_type: 1,
  folder_name: data.name,
  folder_desc: data.intro,
  private_type: data.visibility === 'private' ? 2 : data.visibility === 'shared' ? 4 : 1,
  layout_type: data.layout === 'list' ? 2 : 1,
  pic_layout: data.layout === 'list' ? 2 : 1,
})

const handleSaveCategory = async (data: CategoryData) => {
  try {
    if (editingCategory.value?.id) {
      await pcApi.editProductOrCategory(buildCategoryPayload(data))
      toast.success('分类已更新')
    } else {
      await pcApi.createProductOrCategory({
        ...buildCategoryPayload(data),
        fid: 0,
      })
      toast.success('分类已创建')
      currentPage.value = 1
    }
    await loadCategories()
  } catch (error: any) {
    toast.error(error?.message || '保存失败')
    return
  }
  editDialogOpen.value = false
  editingCategory.value = undefined
}

const handleCloseEditDialog = () => {
  editDialogOpen.value = false
  editingCategory.value = undefined
}

const handleKeywordChange = (value: string) => {
  keyword.value = value
  currentPage.value = 1
}

const handleVisibilityChange = (value: string) => {
  visibilityFilter.value = value as any
  currentPage.value = 1
}

const handleSortChange = (value: string) => {
  sortBy.value = value as any
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}
</script>

<template>
  <div class="flex flex-col h-full gap-6">
    <!-- 页面标题 -->
    <div class="flex items-center justify-between">
      <h1 class="text-page-title">分类管理</h1>
      <Button
        variant="default"
        size="lg"
        class="gap-2"
        @click="handleCreateCategory"
      >
        <SafeIcon name="Plus" :size="18" />
        新建分类
      </Button>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar gap-3">
      <SearchBar
        :model-value="keyword"
        placeholder="搜索分类名称或简介..."
        @update:model-value="handleKeywordChange"
      />

      <Select :model-value="visibilityFilter" @update:model-value="handleVisibilityChange">
        <SelectTrigger class="w-40 h-10">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">全部可见性</SelectItem>
          <SelectItem value="public">公开</SelectItem>
          <SelectItem value="private">私密</SelectItem>
          <SelectItem value="shared">分享可见</SelectItem>
        </SelectContent>
      </Select>

      <Select :model-value="sortBy" @update:model-value="handleSortChange">
        <SelectTrigger class="w-40 h-10">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="name">按名称排序</SelectItem>
          <SelectItem value="productCount">按产品数排序</SelectItem>
          <SelectItem value="updatedAt">按更新时间排序</SelectItem>
        </SelectContent>
      </Select>

      <Button
        variant="outline"
        size="icon"
        class="h-10 w-10"
        @click="toggleSortDirection"
        :title="`当前排序: ${sortDirection === 'asc' ? '升序' : '降序'}`"
      >
        <SafeIcon
          :name="sortDirection === 'asc' ? 'ArrowUp' : 'ArrowDown'"
          :size="16"
        />
      </Button>
    </div>

    <!-- 列表区域 -->
    <div v-if="!isClient || isLoading" class="flex-1 overflow-y-auto min-h-0 surface-raised">
      <div class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow class="border-b border-border hover:bg-transparent">
              <TableHead class="w-32 whitespace-nowrap font-semibold">分类名称</TableHead>
              <TableHead class="w-48 whitespace-nowrap font-semibold">简介</TableHead>
              <TableHead class="w-24 whitespace-nowrap font-semibold text-center">产品数</TableHead>
              <TableHead class="w-24 whitespace-nowrap font-semibold text-center">子分类</TableHead>
              <TableHead class="w-32 whitespace-nowrap font-semibold">可见性</TableHead>
              <TableHead class="w-40 whitespace-nowrap font-semibold">更新时间</TableHead>
              <TableHead class="w-20 whitespace-nowrap font-semibold text-center">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="paginatedCategories.length > 0">
              <TableRow
                v-for="category in paginatedCategories"
                :key="category.id"
                class="table-row-hover border-b border-border/50"
              >
                <TableCell class="font-medium truncate">
                  {{ category.name || '未命名分类' }}
                </TableCell>
                <TableCell class="text-muted-foreground text-sm truncate max-w-xs">
                  {{ category.intro || '暂无简介' }}
                </TableCell>
                <TableCell class="text-center text-sm">
                  {{ category.productCount }}
                </TableCell>
                <TableCell class="text-center text-sm">
                  {{ category.childCount }}
                </TableCell>
                <TableCell>
                  <StatusBadge :status="category.visibility" />
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">
                  {{ category.updatedAt }}
                </TableCell>
                <TableCell class="text-center">
                  <div class="flex items-center justify-center gap-1">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="handleEditCategory(category)"
                      title="编辑分类"
                    >
                      <SafeIcon name="Edit2" :size="16" class="text-muted-foreground hover:text-primary" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="handleDeleteCategory(category.id)"
                      title="删除分类"
                    >
                      <SafeIcon name="Trash2" :size="16" class="text-muted-foreground hover:text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </template>
            <TableRow v-else>
              <TableCell colspan="7" class="h-32">
                <EmptyState
                  icon="FolderOpen"
                  title="暂无分类"
                  description="还没有创建任何分类，点击'新建分类'开始添加"
                />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- 分页 -->
    <div v-if="!isClient && totalItems > 0" class="border-t border-border pt-4">
      <Pagination
        :current="currentPage"
        :total="totalItems"
        :page-size="pageSize"
        @update:current="(page) => (currentPage = page)"
      />
    </div>

    <!-- 编辑对话框 -->
    <CategoryEditDialog
      v-if="isClient"
      :open="editDialogOpen"
      :category="editingCategory"
      @update:open="handleCloseEditDialog"
      @save="handleSaveCategory"
    />

    <!-- 删除确认对话框 -->
    <ConfirmDialog
      v-if="isClient"
      :open="deleteConfirmOpen"
      title="删除分类"
      description="确定要删除该分类吗？删除后将无法恢复，但分类下的产品不会被删除。"
      confirm-text="确认删除"
      cancel-text="取消"
      variant="destructive"
      @update:open="(val) => (deleteConfirmOpen = val)"
      @confirm="confirmDelete"
    />
  </div>
</template>
