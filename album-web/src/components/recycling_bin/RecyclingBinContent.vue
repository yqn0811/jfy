
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { TrashData } from '@/data/TrashData'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import { toast } from 'vue-sonner'
import { cn } from '@/lib/utils'
import { pcApi } from '@/lib/api'
import { unwrapList, pickImage } from '@/lib/jfyuntu-mappers'

const isClient = ref(true)
const isLoading = ref(false)
const allTrashItems = ref<TrashData[]>([])
const activeTab = ref<'all' | 'product' | 'category' | 'image'>('all')
const searchKeyword = ref('')
const confirmDialogOpen = ref(false)
const confirmAction = ref<'restore' | 'delete'>('delete')
const selectedItem = ref<TrashData | null>(null)

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const typeParam = params.get('type')
    if (typeParam && ['product', 'category', 'image'].includes(typeParam)) {
      activeTab.value = typeParam as 'product' | 'category' | 'image'
    }
    isClient.value = true
    loadTrash()
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
  } catch (error: any) {
    toast.error(error?.message || '回收站加载失败')
  } finally {
    isLoading.value = false
  }
}

const typeLabels: Record<string, string> = {
  product: '产品',
  category: '分类',
  image: '图片',
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

const handleRestore = (item: TrashData) => {
  selectedItem.value = item
  confirmAction.value = 'restore'
  confirmDialogOpen.value = true
}

const handleDelete = (item: TrashData) => {
  selectedItem.value = item
  confirmAction.value = 'delete'
  confirmDialogOpen.value = true
}

const handleConfirm = async () => {
  if (!selectedItem.value) return

  try {
    if (confirmAction.value === 'restore') {
      await pcApi.restoreRecycleItem(selectedItem.value.sourceId || selectedItem.value.id)
      toast.success('已恢复该项目')
    } else if (confirmAction.value === 'delete') {
      await pcApi.deleteRecycleItem(selectedItem.value.sourceId || selectedItem.value.id)
      toast.success('已彻底删除该项目')
    }
    await loadTrash()
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
  const name = selectedItem.value?.name || ''
  return confirmAction.value === 'restore'
    ? `确定要恢复 "${name}" 吗？恢复后将重新显示在相应的列表中。`
    : `确定要彻底删除 "${name}" 吗？此操作不可撤销。`
})

const handleTabChange = (value: string) => {
  activeTab.value = value as 'all' | 'product' | 'category' | 'image'
}

const handleSearch = (event: Event) => {
  const target = event.target as HTMLInputElement
  searchKeyword.value = target.value
  loadTrash()
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
          @click="() => window.location.href = './management-workbench.html'"
        >
          <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
          返回工作台
        </Button>
      </div>
      <p class="text-caption">已删除的产品、分类和图片会在这里保存，您可以选择恢复或彻底删除。</p>
    </div>

    <!-- 筛选和搜索区域 -->
    <div class="page-body border-b border-border space-y-4">
      <Tabs :value="activeTab" @update:model-value="handleTabChange" class="w-full">
        <TabsList class="grid w-full grid-cols-4 bg-muted/50">
          <TabsTrigger value="all">全部</TabsTrigger>
          <TabsTrigger value="product">产品</TabsTrigger>
          <TabsTrigger value="category">分类</TabsTrigger>
          <TabsTrigger value="image">图片</TabsTrigger>
        </TabsList>
      </Tabs>

      <div class="flex items-center gap-3">
        <div class="flex-1 relative">
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
              >
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
