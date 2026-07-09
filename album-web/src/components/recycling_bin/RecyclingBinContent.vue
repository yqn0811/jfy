
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { TrashData } from '@/data/TrashData'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'
import { formatBytes, parseSpaceToBytes } from '@/lib/account'
import { unwrapList, pickImage } from '@/lib/jfyuntu-mappers'

const isClient = ref(true)
const isLoading = ref(false)
const allTrashItems = ref<TrashData[]>([])
const activeTab = ref<'all' | 'product' | 'category'>('all')
const searchKeyword = ref('')
const confirmDialogOpen = ref(false)
const confirmAction = ref<'restore' | 'delete'>('delete')
const selectedItem = ref<TrashData | null>(null)
const selectedIds = ref<Set<string>>(new Set())
const profile = ref<any>({})

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const typeParam = params.get('type')
    if (typeParam && ['product', 'category'].includes(typeParam)) {
      activeTab.value = typeParam as 'product' | 'category'
    }
    isClient.value = true
    Promise.all([loadTrash(), loadProfile()])
  })
})

const mapTrashItem = (item: any): TrashData => ({
  id: String(item.id || item.fid || ''),
  itemType: Number(item.folder_type || item.itemType || 2) === 1 ? 'category' : 'product',
  sourceId: String(item.id || item.fid || ''),
  name: item.folder_name || item.pic_name || item.name || '未命名项目',
  coverUrl: pickImage(item.imgurl, item.new_thumb, item.coverUrl),
  deletedAt: item.delete_time || item.delete_time_str || item.create_time_str || item.update_time || '',
  canRestore: true,
})

const loadTrash = async () => {
  isLoading.value = true
  try {
    const raw = await pcApi.getRecycleList({ key: searchKeyword.value.trim(), limit: 100 })
    allTrashItems.value = unwrapList(raw).map(mapTrashItem)
    selectedIds.value = new Set([...selectedIds.value].filter(id => allTrashItems.value.some(item => item.sourceId === id || item.id === id)))
  } catch (error: any) {
    toast.error(error?.message || '回收站加载失败')
  } finally {
    isLoading.value = false
  }
}

const loadProfile = async () => {
  try {
    profile.value = await pcApi.getCurrentUser()
  } catch {
    profile.value = {}
  }
}

const filteredItems = computed(() => {
  let result = allTrashItems.value

  if (activeTab.value !== 'all') {
    result = result.filter(item => item.itemType === activeTab.value)
  }

  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.trim().toLowerCase()
    result = result.filter(item => item.name.toLowerCase().includes(keyword))
  }

  return result
})

const filteredIds = computed(() => filteredItems.value.map(item => item.sourceId || item.id).filter(Boolean))
const selectedCount = computed(() => selectedIds.value.size)
const selectedListIds = computed(() => [...selectedIds.value])
const isAllSelected = computed(() => filteredIds.value.length > 0 && filteredIds.value.every(id => selectedIds.value.has(id)))
const isIndeterminate = computed(() => selectedCount.value > 0 && !isAllSelected.value)

const totalSpaceBytes = computed(() => parseSpaceToBytes(profile.value?.all_space))
const normalSpaceBytes = computed(() => Number(profile.value?.normal_space_bytes ?? profile.value?.normal_space ?? profile.value?.use_space ?? 0))
const trashSpaceBytes = computed(() => Number(profile.value?.trash_space_bytes ?? profile.value?.recycle_space_bytes ?? 0))
const usedSpaceBytes = computed(() => Number(profile.value?.use_space || normalSpaceBytes.value + trashSpaceBytes.value))
const normalPercent = computed(() => totalSpaceBytes.value > 0 ? Math.min((normalSpaceBytes.value / totalSpaceBytes.value) * 100, 100) : 0)
const trashPercent = computed(() => totalSpaceBytes.value > 0 ? Math.min((trashSpaceBytes.value / totalSpaceBytes.value) * 100, Math.max(0, 100 - normalPercent.value)) : 0)
const remainingPercent = computed(() => Math.max(0, 100 - normalPercent.value - trashPercent.value))

const setSelected = (id: string, checked: boolean) => {
  const next = new Set(selectedIds.value)
  if (checked) next.add(id)
  else next.delete(id)
  selectedIds.value = next
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = new Set([...selectedIds.value].filter(id => !filteredIds.value.includes(id)))
    return
  }
  selectedIds.value = new Set([...selectedIds.value, ...filteredIds.value])
}

const handleRestore = (item: TrashData) => {
  selectedItem.value = item
  selectedIds.value = new Set()
  confirmAction.value = 'restore'
  confirmDialogOpen.value = true
}

const handleDelete = (item: TrashData) => {
  selectedItem.value = item
  selectedIds.value = new Set()
  confirmAction.value = 'delete'
  confirmDialogOpen.value = true
}

const handleBatchRestore = () => {
  if (selectedCount.value === 0) {
    toast.error('请先选择要恢复的项目')
    return
  }
  selectedItem.value = null
  confirmAction.value = 'restore'
  confirmDialogOpen.value = true
}

const handleBatchDelete = () => {
  if (selectedCount.value === 0) {
    toast.error('请先选择要删除的项目')
    return
  }
  selectedItem.value = null
  confirmAction.value = 'delete'
  confirmDialogOpen.value = true
}

const handleConfirm = async () => {
  const ids = selectedItem.value ? [selectedItem.value.sourceId || selectedItem.value.id] : selectedListIds.value
  if (ids.length === 0) return

  try {
    const idText = ids.join(',')
    if (confirmAction.value === 'restore') {
      await pcApi.restoreRecycleItem(idText)
      toast.success(ids.length > 1 ? `已恢复 ${ids.length} 个项目` : '已恢复该项目')
    } else if (confirmAction.value === 'delete') {
      await pcApi.deleteRecycleItem(idText)
      toast.success(ids.length > 1 ? `已彻底删除 ${ids.length} 个项目` : '已彻底删除该项目')
    }
    selectedIds.value = new Set()
    await Promise.all([loadTrash(), loadProfile()])
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }

  selectedItem.value = null
  confirmDialogOpen.value = false
}

const handleCancel = () => {
  selectedItem.value = null
  confirmDialogOpen.value = false
}

const confirmDialogDescription = computed(() => {
  if (!selectedItem.value && selectedCount.value > 0) {
    return confirmAction.value === 'restore'
      ? `确定要恢复选中的 ${selectedCount.value} 个项目吗？恢复后将重新显示在相应的列表中。`
      : `确定要彻底删除选中的 ${selectedCount.value} 个项目吗？此操作不可撤销。`
  }
  const name = selectedItem.value?.name || ''
  return confirmAction.value === 'restore'
    ? `确定要恢复 "${name}" 吗？恢复后将重新显示在相应的列表中。`
    : `确定要彻底删除 "${name}" 吗？此操作不可撤销。`
})

const handleTabChange = (value: string) => {
  activeTab.value = value as 'all' | 'product' | 'category'
}

const handleSearch = (event: Event) => {
  const target = event.target as HTMLInputElement
  searchKeyword.value = target.value
  loadTrash()
}

const handleBackToWorkbench = () => {
  window.location.href = './management-workbench'
}

const handleBillingUsage = () => {
  window.location.href = './billing-usage'
}
</script>

<template>
  <div class="flex flex-col h-full bg-background">
    <!-- 页面头部 -->
    <div class="page-body border-b border-border">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-page-title">回收站</h1>
        <Button 
          variant="outline" 
          size="sm"
          @click="handleBackToWorkbench"
        >
          <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
          返回工作台
        </Button>
      </div>
      <p class="text-caption">已删除的产品、分类和图片会在这里保存，您可以选择恢复或彻底删除。</p>
      <div class="mt-5 rounded-lg border border-border bg-card p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-foreground">存储容量</p>
            <p class="text-xs text-muted-foreground">
              已用 {{ formatBytes(usedSpaceBytes) }} / 总额度 {{ profile?.all_space || formatBytes(totalSpaceBytes) }}
            </p>
          </div>
          <Button variant="outline" size="sm" class="gap-2" @click="handleBillingUsage">
            <SafeIcon name="Database" :size="14" />
            容量套餐
          </Button>
        </div>
        <div class="flex h-3 overflow-hidden rounded-full bg-muted">
          <div class="bg-primary" :style="{ width: `${normalPercent}%` }" />
          <div class="bg-amber-500" :style="{ width: `${trashPercent}%` }" />
          <div class="bg-muted" :style="{ width: `${remainingPercent}%` }" />
        </div>
        <div class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
          <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-primary" />正常资源 {{ formatBytes(normalSpaceBytes) }}</span>
          <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-amber-500" />回收站 {{ formatBytes(trashSpaceBytes) }}</span>
          <span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full bg-muted-foreground/30" />剩余额度 {{ formatBytes(Math.max(totalSpaceBytes - usedSpaceBytes, 0)) }}</span>
        </div>
      </div>
    </div>

    <!-- 筛选和搜索区域 -->
    <div class="page-body border-b border-border">
      <div class="flex items-center justify-between gap-4">
      <Tabs :value="activeTab" @update:model-value="handleTabChange" class="shrink-0">
        <TabsList class="grid w-[320px] grid-cols-3 bg-muted/50">
          <TabsTrigger value="all">全部</TabsTrigger>
          <TabsTrigger value="product">产品</TabsTrigger>
          <TabsTrigger value="category">分类</TabsTrigger>
        </TabsList>
      </Tabs>

      <div class="flex min-w-0 flex-1 items-center gap-3">
        <div class="relative min-w-0 flex-1">
          <SafeIcon 
            name="Search" 
            class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" 
            :size="16" 
          />
          <Input
            type="text"
            placeholder="搜索回收站内容..."
            class="pl-10 h-10 bg-muted/50 border-none focus-visible:ring-1"
            :value="searchKeyword"
            @input="handleSearch"
          />
        </div>
      </div>
      </div>
    </div>

    <div v-if="selectedCount > 0" class="border-b border-border bg-primary/5 px-6 py-3">
      <div class="flex items-center justify-between gap-4">
        <p class="text-sm font-medium text-primary">已选择 {{ selectedCount }} 个项目</p>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" class="gap-2" @click="handleBatchRestore">
            <SafeIcon name="RotateCcw" :size="14" />
            批量恢复
          </Button>
          <Button variant="outline" size="sm" class="gap-2 border-destructive/30 text-destructive hover:bg-destructive/5 hover:text-destructive" @click="handleBatchDelete">
            <SafeIcon name="Trash2" :size="14" />
            批量删除
          </Button>
        </div>
      </div>
    </div>

    <!-- 列表区域 -->
    <div class="flex-1 overflow-y-auto min-h-0">
      <div v-if="!isClient || isLoading || filteredItems.length === 0" class="flex items-center justify-center h-full">
        <EmptyState
          icon="Trash2"
          :title="isLoading ? '加载中' : '回收站为空'"
          :description="isLoading ? '正在加载回收站内容' : '已删除的项目会在这里显示，您可以选择恢复或彻底删除。'"
        />
      </div>

      <div v-else class="page-body">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="border-b border-border hover:bg-transparent">
                <TableHead class="w-12 text-center">
                  <button
                    type="button"
                    class="inline-flex rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    :aria-label="isAllSelected ? '取消全选' : '全选'"
                    @click="toggleSelectAll"
                  >
                    <Checkbox :model-value="isAllSelected || isIndeterminate" class="pointer-events-none" />
                  </button>
                </TableHead>
                <TableHead class="w-16 text-center">类型</TableHead>
                <TableHead class="w-32">名称</TableHead>
                <TableHead class="w-24">删除时间</TableHead>
                <TableHead class="w-32 text-right">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow 
                v-for="item in filteredItems" 
                :key="item.id"
                class="border-b border-border hover:bg-muted/30 transition-colors"
                :class="selectedIds.has(item.sourceId || item.id) ? 'bg-primary/5' : ''"
              >
                <TableCell class="text-center">
                  <button
                    type="button"
                    class="inline-flex rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    :aria-label="selectedIds.has(item.sourceId || item.id) ? '取消选择' : '选择'"
                    @click="setSelected(item.sourceId || item.id, !selectedIds.has(item.sourceId || item.id))"
                  >
                    <Checkbox :model-value="selectedIds.has(item.sourceId || item.id)" class="pointer-events-none" />
                  </button>
                </TableCell>
                <TableCell class="text-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-muted/50">
                    <SafeIcon 
                      :name="item.itemType === 'product' ? 'Package' : item.itemType === 'category' ? 'FolderTree' : 'Image'"
                      :size="16"
                      class="text-muted-foreground"
                    />
                  </span>
                </TableCell>
                <TableCell class="font-medium text-foreground truncate max-w-xs">
                  {{ item.name || '未命名项目' }}
                </TableCell>
                <TableCell class="text-sm text-muted-foreground whitespace-nowrap">
                  {{ item.deletedAt }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <Button
                      v-if="item.canRestore"
                      variant="outline"
                      size="sm"
                      class="h-8 px-3 text-xs"
                      @click="handleRestore(item)"
                    >
                      <SafeIcon name="RotateCcw" :size="14" class="mr-1" />
                      恢复
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      class="h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/5 border-destructive/30"
                      @click="handleDelete(item)"
                    >
                      <SafeIcon name="Trash2" :size="14" class="mr-1" />
                      删除
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>
    </div>

    <!-- 确认对话框 -->
    <ConfirmDialog
      :open="confirmDialogOpen"
      :title="confirmAction === 'restore' ? '恢复项目' : '彻底删除'"
      :description="confirmDialogDescription"
      :confirm-text="confirmAction === 'restore' ? '恢复' : '删除'"
      cancel-text="取消"
      :variant="confirmAction === 'delete' ? 'destructive' : 'default'"
      @update:open="(val) => confirmDialogOpen = val"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </div>
</template>

<style scoped>
/* 确保表格行的高度和间距一致 */
:deep(.table tbody tr) {
  @apply h-14;
}

:deep(.table td) {
  @apply py-3 px-4;
}

:deep(.table th) {
  @apply py-3 px-4 font-semibold text-xs uppercase tracking-wider text-muted-foreground;
}
</style>
