
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
import SearchBar from '@/components/common/SearchBar.vue'
import CategoryEditDialog from '@/components/category_management/CategoryEditDialog.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import type { CategoryData, CategoryVisibility } from '@/data/CategoryData'
import { pcApi } from '@/lib/api'
import { mapCategory, unwrapList } from '@/lib/jfyuntu-mappers'
import { cn } from '@/lib/utils'

interface CategoryTreeRow {
  category: CategoryData
  level: number
  hasChildren: boolean
}

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
const expandedIds = ref<Set<string>>(new Set())
const editingRows = ref<Record<string, { name: string; visibility: CategoryVisibility }>>({})
const savingRowIds = ref<Set<string>>(new Set())

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
    categories.value = normalizeCategoryTree(unwrapList(raw).map(item => mapCategory(item)))
    resetRowDrafts(categories.value)
    expandRowsWithChildren(categories.value)
  } catch (error: any) {
    toast.error(error?.message || '分类加载失败')
  } finally {
    isLoading.value = false
  }
}

const cloneCategoryNode = (category: CategoryData, parentId?: string): CategoryData => ({
  ...category,
  parentId: category.parentId || parentId,
  children: (category.children || []).map(child => cloneCategoryNode(child, category.id)),
})

const normalizeCategoryTree = (items: CategoryData[]) => {
  const map = new Map<string, CategoryData>()

  const collect = (category: CategoryData, parentId?: string) => {
    const node = cloneCategoryNode(category, parentId)
    const existed = map.get(node.id)
    if (existed) {
      map.set(node.id, {
        ...existed,
        ...node,
        parentId: node.parentId || existed.parentId,
        children: [...(existed.children || []), ...(node.children || [])],
      })
    } else {
      map.set(node.id, node)
    }
    ;(node.children || []).forEach(child => collect(child, node.id))
  }

  items.forEach(item => collect(item))
  const roots: CategoryData[] = []
  const seenChildIds = new Set<string>()

  map.forEach(node => {
    node.children = []
  })

  map.forEach(node => {
    const parentId = node.parentId || ''
    const parent = parentId ? map.get(parentId) : undefined
    if (parent && parent.id !== node.id) {
      if (!parent.children?.some(child => child.id === node.id)) {
        parent.children = [...(parent.children || []), node]
      }
      seenChildIds.add(node.id)
    }
  })

  map.forEach(node => {
    node.childCount = Math.max(Number(node.childCount || 0), node.children?.length || 0)
    if (!seenChildIds.has(node.id)) roots.push(node)
  })

  return roots
}

const matchesFilter = (category: CategoryData) => {
  const kw = keyword.value.trim().toLowerCase()
  const matchesKeyword = !kw || category.name.toLowerCase().includes(kw) || category.intro.toLowerCase().includes(kw)
  const matchesVisibility = visibilityFilter.value === 'all' || category.visibility === visibilityFilter.value
  return matchesKeyword && matchesVisibility
}

const sortCategories = (items: CategoryData[]) => {
  const sortKey = sortBy.value
  return [...items].sort((a, b) => {
    const aVal = (a as any)[sortKey]
    const bVal = (b as any)[sortKey]
    if (aVal === bVal) return 0
    const direction = sortDirection.value === 'desc' ? -1 : 1
    return aVal > bVal ? direction : -direction
  })
}

const filterTree = (items: CategoryData[]): CategoryData[] => {
  return sortCategories(items).reduce<CategoryData[]>((acc, category) => {
    const children = filterTree(category.children || [])
    if (matchesFilter(category) || children.length > 0) {
      acc.push({ ...category, children })
    }
    return acc
  }, [])
}

const filteredCategoryTree = computed(() => filterTree(categories.value))

const visibleRows = computed(() => {
  const rows: CategoryTreeRow[] = []
  const walk = (items: CategoryData[], level = 0) => {
    items.forEach(category => {
      const children = category.children || []
      const hasChildren = children.length > 0
      rows.push({ category, level, hasChildren })
      if (hasChildren && expandedIds.value.has(category.id)) {
        walk(children, level + 1)
      }
    })
  }
  walk(filteredCategoryTree.value)
  return rows
})

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  const end = start + pageSize
  return visibleRows.value.slice(start, end)
})

const totalItems = computed(() => visibleRows.value.length)

const flattenCategories = (items: CategoryData[]) => {
  const rows: CategoryData[] = []
  const walk = (nodes: CategoryData[]) => {
    nodes.forEach(node => {
      rows.push(node)
      if (node.children?.length) walk(node.children)
    })
  }
  walk(items)
  return rows
}

const expandRowsWithChildren = (items: CategoryData[]) => {
  const next = new Set(expandedIds.value)
  flattenCategories(items).forEach(category => {
    if (category.children?.length) next.add(category.id)
  })
  expandedIds.value = next
}

const toggleExpanded = (categoryId: string) => {
  const next = new Set(expandedIds.value)
  if (next.has(categoryId)) next.delete(categoryId)
  else next.add(categoryId)
  expandedIds.value = next
}

const rowDraft = (category: CategoryData) => {
  if (!editingRows.value[category.id]) {
    editingRows.value[category.id] = {
      name: category.name || '',
      visibility: category.visibility,
    }
  }
  return editingRows.value[category.id]
}

const isRowDirty = (category: CategoryData) => {
  const draft = rowDraft(category)
  return draft.name.trim() !== (category.name || '') || draft.visibility !== category.visibility
}

const resetRow = (category: CategoryData) => {
  editingRows.value[category.id] = {
    name: category.name || '',
    visibility: category.visibility,
  }
}

const resetRowDrafts = (items: CategoryData[]) => {
  const next: Record<string, { name: string; visibility: CategoryVisibility }> = {}
  flattenCategories(items).forEach(category => {
    next[category.id] = {
      name: category.name || '',
      visibility: category.visibility,
    }
  })
  editingRows.value = next
}

const isRowSaving = (categoryId: string) => savingRowIds.value.has(categoryId)

const updateRowVisibility = (category: CategoryData, value: string) => {
  rowDraft(category).visibility = value as CategoryVisibility
}

const handleSaveRow = async (category: CategoryData) => {
  const draft = rowDraft(category)
  const nextName = draft.name.trim()
  if (!nextName) {
    toast.error('分类名称不能为空')
    return
  }
  const nextSaving = new Set(savingRowIds.value)
  nextSaving.add(category.id)
  savingRowIds.value = nextSaving
  try {
    await pcApi.editProductOrCategory(buildCategoryPayload({
      ...category,
      name: nextName,
      visibility: draft.visibility,
    }))
    toast.success('分类已更新')
    await loadCategories()
  } catch (error: any) {
    toast.error(error?.message || '保存失败')
  } finally {
    const doneSaving = new Set(savingRowIds.value)
    doneSaving.delete(category.id)
    savingRowIds.value = doneSaving
  }
}

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
    if (paginatedRows.value.length === 0 && currentPage.value > 1) currentPage.value--
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
  pid: data.parentId || 0,
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

watch([keyword, visibilityFilter], () => {
  if (keyword.value.trim() || visibilityFilter.value !== 'all') {
    expandRowsWithChildren(filteredCategoryTree.value)
  }
})
</script>

<template>
  <div class="flex h-full min-h-0 flex-col bg-background">
    <!-- 页面标题 -->
    <div class="page-body border-b border-border">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-page-title">分类管理</h1>
          <p class="text-caption mt-1">维护产品分类、层级、权限和排序设置</p>
        </div>
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
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-6 p-6">
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
      <div class="flex-1 overflow-y-auto min-h-0 surface-raised">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="border-b border-border hover:bg-transparent">
                <TableHead class="min-w-[360px] whitespace-nowrap font-semibold">分类名称</TableHead>
                <TableHead class="w-48 whitespace-nowrap font-semibold">简介</TableHead>
                <TableHead class="w-24 whitespace-nowrap font-semibold text-center">产品数</TableHead>
                <TableHead class="w-24 whitespace-nowrap font-semibold text-center">子分类</TableHead>
                <TableHead class="w-44 whitespace-nowrap font-semibold">权限状态</TableHead>
                <TableHead class="w-40 whitespace-nowrap font-semibold">更新时间</TableHead>
                <TableHead class="w-36 whitespace-nowrap font-semibold text-center">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="paginatedRows.length > 0">
                <TableRow
                  v-for="row in paginatedRows"
                  :key="row.category.id"
                  class="table-row-hover border-b border-border/50"
                >
                  <TableCell>
                    <div
                      class="flex min-w-0 items-center gap-2"
                      :style="{ paddingLeft: `${row.level * 24}px` }"
                    >
                      <Button
                        v-if="row.hasChildren"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        :title="expandedIds.has(row.category.id) ? '收起子分类' : '展开子分类'"
                        @click="toggleExpanded(row.category.id)"
                      >
                        <SafeIcon
                          :name="expandedIds.has(row.category.id) ? 'ChevronDown' : 'ChevronRight'"
                          :size="16"
                        />
                      </Button>
                      <span v-else class="h-7 w-7 shrink-0" />
                      <div class="flex min-w-0 flex-1 items-center gap-2">
                        <SafeIcon
                          :name="row.hasChildren ? 'FolderTree' : 'Folder'"
                          :size="16"
                          class="shrink-0 text-muted-foreground"
                        />
                        <Input
                          v-model="rowDraft(row.category).name"
                          class="h-9 min-w-[220px] max-w-[520px] flex-1 border-transparent bg-transparent px-2 font-medium shadow-none hover:border-input hover:bg-background focus-visible:border-input"
                          :disabled="isRowSaving(row.category.id)"
                          @keyup.enter="handleSaveRow(row.category)"
                        />
                      </div>
                    </div>
                  </TableCell>
                  <TableCell class="text-muted-foreground text-sm truncate max-w-xs">
                    {{ row.category.intro || '暂无简介' }}
                  </TableCell>
                  <TableCell class="text-center text-sm">
                    {{ row.category.productCount }}
                  </TableCell>
                  <TableCell class="text-center text-sm">
                    <button
                      v-if="row.hasChildren"
                      type="button"
                      class="rounded-md px-2 py-1 text-sm font-medium text-primary hover:bg-primary/10"
                      @click="toggleExpanded(row.category.id)"
                    >
                      {{ row.category.childCount }}
                    </button>
                    <span v-else>{{ row.category.childCount }}</span>
                  </TableCell>
                  <TableCell>
                    <Select
                      :model-value="rowDraft(row.category).visibility"
                      :disabled="isRowSaving(row.category.id)"
                      @update:model-value="(value) => updateRowVisibility(row.category, String(value))"
                    >
                      <SelectTrigger class="h-9 w-32">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="public">公开</SelectItem>
                        <SelectItem value="private">私密</SelectItem>
                        <SelectItem value="shared">分享可见</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ row.category.updatedAt }}
                  </TableCell>
                  <TableCell class="text-center">
                    <div class="flex items-center justify-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0"
                        :disabled="!isRowDirty(row.category) || isRowSaving(row.category.id)"
                        @click="handleSaveRow(row.category)"
                        title="保存行内修改"
                      >
                        <SafeIcon
                          :name="isRowSaving(row.category.id) ? 'Loader2' : 'Check'"
                          :size="16"
                          :class="cn(isRowSaving(row.category.id) ? 'animate-spin' : 'text-muted-foreground hover:text-primary')"
                        />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0"
                        :disabled="!isRowDirty(row.category) || isRowSaving(row.category.id)"
                        @click="resetRow(row.category)"
                        title="撤销行内修改"
                      >
                        <SafeIcon name="RotateCcw" :size="16" class="text-muted-foreground hover:text-primary" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0"
                        @click="handleEditCategory(row.category)"
                        title="编辑分类"
                      >
                        <SafeIcon name="Edit2" :size="16" class="text-muted-foreground hover:text-primary" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 w-8 p-0"
                        @click="handleDeleteCategory(row.category.id)"
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
      <div v-if="isClient && totalItems > 0" class="border-t border-border pt-4">
        <Pagination
          :current="currentPage"
          :total="totalItems"
          :page-size="pageSize"
          @update:current="(page) => (currentPage = page)"
        />
      </div>
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
