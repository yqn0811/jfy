
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { DeliveryRecordService } from '@/data/DeliveryRecordService'
import type { DeliveryRecordVO } from '@/data/DeliveryRecordData'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import SearchBar from '@/components/common/SearchBar.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import Pagination from '@/components/common/Pagination.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { navigateTo } from '@/navigation'
import { cn } from '@/lib/utils'

const isClient = ref(true)

const records = ref<DeliveryRecordVO[]>(DeliveryRecordService.getRecordVOList())

const searchKeyword = ref('')
const selectedType = ref<string>('all')
const selectedStatus = ref<string>('all')
const currentPage = ref(1)
const pageSize = 10

const typeOptions = [
  { value: 'all', label: '全部类型' },
  { value: 'sent', label: '已发送' },
  { value: 'received', label: '已接收' },
  { value: 'collected', label: '已收集' },
  { value: 'archived', label: '已归档' }
]

const statusOptions = [
  { value: 'all', label: '全部状态' },
  { value: 'draft', label: '草稿' },
  { value: 'collecting', label: '进行中' },
  { value: 'pending_review', label: '待审核' },
  { value: 'need_resubmission', label: '待重传' },
  { value: 'approved', label: '已通过' },
  { value: 'archived', label: '已归档' },
  { value: 'expired', label: '已过期' }
]

const filteredRecords = computed(() => {
  const filter: Record<string, string | string[]> = {}

  if (selectedType.value !== 'all') {
    filter.type = selectedType.value
  }

  if (selectedStatus.value !== 'all') {
    filter.status = selectedStatus.value
  }

  return DeliveryRecordService.query({
    keyword: searchKeyword.value,
    filter,
    sortKey: 'createdAt',
    sortDirection: 'desc'
  }).map((item) => ({
    id: item.id,
    name: item.name,
    type: item.type,
    status: item.status,
    fileCount: item.fileCount,
    storageSizeMb: item.storageSizeMb,
    createdAt: item.createdAt,
    expiresAt: item.expiresAt,
    lastActorName: item.lastActorName
  }))
})

const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  const end = start + pageSize
  return filteredRecords.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredRecords.value.length / pageSize)
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStorageSize = (mb: number) => {
  if (mb >= 1024) {
    return (mb / 1024).toFixed(2) + ' GB'
  }
  return mb.toFixed(2) + ' MB'
}

const handleRecordClick = (recordId: string) => {
  navigateTo(`/task-details?taskId=${recordId}`)
}

const handleExport = () => {
  toast.success('导出成功，文件已下载')
}

const handleBatchDownload = () => {
  toast.success('下载已启动，请稍候')
}

const handlePageChange = (page: number) => {
  currentPage.value = page
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const typeParam = params.get('type')
    const statusParam = params.get('status')

    if (typeParam && typeOptions.some((opt) => opt.value === typeParam)) {
      selectedType.value = typeParam
    }

    if (statusParam && statusOptions.some((opt) => opt.value === statusParam)) {
      selectedStatus.value = statusParam
    }

    isClient.value = true
  })
})
</script>

<template>
  <div v-if="isClient" class="w-full flex flex-col gap-6">
    <!-- 页面标题 -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <h1 class="text-page-title">交付记录</h1>
      <p class="text-caption sm:text-right">查看和管理所有已发送和已收集的任务记录</p>
    </div>

    <!-- 筛选栏 -->
    <FilterBar>
      <SearchBar
        v-model="searchKeyword"
        placeholder="搜索记录名称..."
      />

      <Select v-model="selectedType">
        <SelectTrigger class="w-full sm:w-40 h-9">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>

      <Select v-model="selectedStatus">
        <SelectTrigger class="w-full sm:w-40 h-9">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>

      <template #actions>
        <Button variant="outline" size="sm" class="flex-1 sm:flex-none" @click="handleBatchDownload">
          <SafeIcon name="Download" :size="16" class="mr-2" />
          批量下载
        </Button>
        <Button variant="outline" size="sm" class="flex-1 sm:flex-none" @click="handleExport">
          <SafeIcon name="FileDown" :size="16" class="mr-2" />
          导出清单
        </Button>
      </template>
    </FilterBar>

    <!-- 内容区域 -->
    <div v-if="filteredRecords.length === 0" class="surface-base card-padding">
      <EmptyState
        icon="FileQuestion"
        title="暂无交付记录"
        description="还没有任何发送或收集的记录。返回工作台创建新任务。"
      >
        <template #actions>
          <Button @click="navigateTo('/workbench')">
            <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
            返回工作台
          </Button>
        </template>
      </EmptyState>
    </div>

    <div v-else class="surface-base overflow-hidden flex flex-col">
      <!-- 表格 -->
      <div class="overflow-x-auto flex-1">
        <Table>
          <TableHeader>
            <TableRow class="bg-muted/30 hover:bg-muted/30">
              <TableHead class="w-48 whitespace-nowrap">记录名称</TableHead>
              <TableHead class="w-24 whitespace-nowrap">类型</TableHead>
              <TableHead class="w-24 whitespace-nowrap">状态</TableHead>
              <TableHead class="w-20 whitespace-nowrap text-right">文件数</TableHead>
              <TableHead class="w-24 whitespace-nowrap text-right">存储大小</TableHead>
              <TableHead class="w-32 whitespace-nowrap">创建时间</TableHead>
              <TableHead class="w-32 whitespace-nowrap">过期时间</TableHead>
              <TableHead class="w-24 whitespace-nowrap">最后操作人</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="record in paginatedRecords"
              :key="record.id"
              class="table-row-hover cursor-pointer"
              @click="handleRecordClick(record.id)"
            >
              <TableCell class="font-medium truncate max-w-xs" :title="record.name">
                {{ record.name }}
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">
                <span v-if="record.type === 'sent'" class="inline-flex items-center gap-1">
                  <SafeIcon name="Send" :size="14" />
                  已发送
                </span>
                <span v-else-if="record.type === 'received'" class="inline-flex items-center gap-1">
                  <SafeIcon name="Download" :size="14" />
                  已接收
                </span>
                <span v-else-if="record.type === 'collected'" class="inline-flex items-center gap-1">
                  <SafeIcon name="CheckCircle2" :size="14" />
                  已收集
                </span>
                <span v-else-if="record.type === 'archived'" class="inline-flex items-center gap-1">
                  <SafeIcon name="Archive" :size="14" />
                  已归档
                </span>
              </TableCell>
              <TableCell>
                <StatusBadge :status="record.status" size="sm" />
              </TableCell>
              <TableCell class="text-right text-sm">{{ record.fileCount }}</TableCell>
              <TableCell class="text-right text-sm">{{ formatStorageSize(record.storageSizeMb) }}</TableCell>
              <TableCell class="text-sm text-muted-foreground">
                {{ formatDate(record.createdAt) }}
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">
                {{ formatDate(record.expiresAt) }}
              </TableCell>
              <TableCell class="text-sm">{{ record.lastActorName }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- 分页 -->
      <div class="border-t border-border px-6 py-4">
        <Pagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="filteredRecords.length"
          :page-size="pageSize"
          @page-change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 确保表格行在悬停时有清晰的视觉反馈 */
:deep(.table-row-hover) {
  @apply transition-colors duration-150;
}

:deep(.table-row-hover:hover) {
  @apply bg-muted/40;
}
</style>
