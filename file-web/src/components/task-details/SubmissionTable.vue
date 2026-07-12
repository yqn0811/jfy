
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { SubmissionData } from '@/data/SubmissionData'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import SearchBar from '@/components/common/SearchBar.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  submissions: SubmissionData[]
  searchKeyword?: string
  statusFilter?: string
  showMissingOnly?: boolean
  selectedSubmissionId?: string | null
  isBatchDownloading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  searchKeyword: '',
  statusFilter: '',
  showMissingOnly: false,
  selectedSubmissionId: null,
  isBatchDownloading: false
})

const emit = defineEmits<{
  (e: 'update:search-keyword', value: string): void
  (e: 'update:status-filter', value: string): void
  (e: 'update:show-missing-only', value: boolean): void
  (e: 'select-submission', id: string): void
  (e: 'batch-remind', ids: string[]): void
  (e: 'batch-download'): void
  (e: 'export-list'): void
}>()

const selectedRows = ref(new Set<string>())

const allSelected = computed({
  get: () => props.submissions.length > 0 && selectedRows.value.size === props.submissions.length,
  set: (val) => {
    if (val) {
      selectedRows.value = new Set(props.submissions.map(s => s.id))
    } else {
      selectedRows.value = new Set()
    }
  }
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const getCompletionRate = (submission: SubmissionData) => {
  if (submission.fileCount === 0) return '0%'
  return `${Math.round((submission.fileCount / 3) * 100)}%`
}

const handleRowClick = (submissionId: string) => {
  emit('select-submission', submissionId)
}

const handleSelectRow = (submissionId: string, checked: boolean) => {
  const nextRows = new Set(selectedRows.value)
  if (checked) {
    nextRows.add(submissionId)
  } else {
    nextRows.delete(submissionId)
  }
  selectedRows.value = nextRows
}

const handleBatchRemind = () => {
  emit('batch-remind', Array.from(selectedRows.value))
}

watch(
  () => props.submissions.map((submission) => submission.id).join(','),
  () => {
    const visibleIds = new Set(props.submissions.map((submission) => submission.id))
    selectedRows.value = new Set(Array.from(selectedRows.value).filter((id) => visibleIds.has(id)))
  }
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- Filter Bar -->
    <FilterBar>
      <SearchBar
        :model-value="searchKeyword"
        placeholder="搜索提交人姓名..."
        @update:model-value="emit('update:search-keyword', $event)"
      />

      <Select :model-value="statusFilter" @update:model-value="(value) => emit('update:status-filter', String(value))">
        <SelectTrigger class="w-full sm:w-40 h-10">
          <SelectValue placeholder="筛选状态" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">全部状态</SelectItem>
          <SelectItem value="submitted">已提交</SelectItem>
          <SelectItem value="pending_review">待审核</SelectItem>
          <SelectItem value="need_resubmission">需补交</SelectItem>
          <SelectItem value="approved">已通过</SelectItem>
        </SelectContent>
      </Select>

      <div class="flex items-center gap-2 px-3 py-1.5 border border-border rounded-md bg-background hover:bg-muted/30 cursor-pointer transition-colors"
           @click="emit('update:show-missing-only', !showMissingOnly)">
        <Checkbox :checked="showMissingOnly" />
        <span class="text-sm font-medium">仅显示缺失</span>
      </div>

      <template #actions>
        <Button 
          variant="outline" 
          size="sm"
          class="flex-1 gap-2 sm:flex-none"
          @click="handleBatchRemind"
        >
          <SafeIcon name="Bell" :size="16" />
          <span class="hidden sm:inline">{{ selectedRows.size > 0 ? `提醒选中(${selectedRows.size})` : '提醒选中' }}</span>
        </Button>
        <Button 
          variant="outline" 
          size="sm"
          class="flex-1 gap-2 sm:flex-none"
          :disabled="isBatchDownloading"
          @click="emit('batch-download')"
        >
          <SafeIcon :name="isBatchDownloading ? 'Loader2' : 'Download'" :size="16" :class="isBatchDownloading ? 'animate-spin' : ''" />
          <span class="hidden sm:inline">{{ isBatchDownloading ? '下载中' : '批量下载' }}</span>
        </Button>
        <Button 
          variant="outline" 
          size="sm"
          class="flex-1 gap-2 sm:flex-none"
          @click="emit('export-list')"
        >
          <SafeIcon name="FileDown" :size="16" />
          <span class="hidden sm:inline">导出清单</span>
        </Button>
      </template>
    </FilterBar>

    <!-- Empty State -->
    <EmptyState
      v-if="submissions.length === 0"
      title="暂无提交记录"
      description="还没有人提交材料，请复制收集链接分享给提交人。"
      icon="FileQuestion"
    >
      <template #actions>
        <Button variant="default" size="sm">复制收集链接</Button>
      </template>
    </EmptyState>

    <!-- Table -->
    <div v-else class="grid gap-3 md:hidden">
      <article
        v-for="submission in submissions"
        :key="submission.id"
        class="surface-base p-4"
        @click="handleRowClick(submission.id)"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm font-semibold text-foreground">{{ submission.submitterName }}</h3>
            <p class="mt-1 text-caption">
              {{ submission.submitterPhone }} · {{ submission.submitterDepartment }}
            </p>
          </div>
          <StatusBadge :status="submission.status" size="sm" />
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
          <div>
            <p class="text-xs text-muted-foreground">完整度</p>
            <p :class="submission.hasMissing ? 'text-warning font-medium' : 'text-[hsl(var(--success))] font-medium'">
              {{ getCompletionRate(submission) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">文件数</p>
            <p class="font-medium">{{ submission.fileCount }}</p>
          </div>
          <div>
            <p class="text-xs text-muted-foreground">提交时间</p>
            <p class="font-medium">{{ formatDate(submission.submittedAt) }}</p>
          </div>
        </div>
      </article>
    </div>

    <div v-if="submissions.length > 0" class="surface-base hidden overflow-hidden md:block">
      <div class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow class="hover:bg-transparent border-b border-border/50">
              <TableHead class="w-12 text-center">
                <Checkbox :checked="allSelected" @update:checked="allSelected = $event" />
              </TableHead>
              <TableHead class="w-32 whitespace-nowrap">提交人</TableHead>
              <TableHead class="w-28 whitespace-nowrap">联系方式</TableHead>
              <TableHead class="w-24 whitespace-nowrap">部门</TableHead>
              <TableHead class="w-20 whitespace-nowrap text-center">完整度</TableHead>
              <TableHead class="w-28 whitespace-nowrap">提交时间</TableHead>
              <TableHead class="w-24 whitespace-nowrap text-center">审核状态</TableHead>
              <TableHead class="w-20 whitespace-nowrap text-center">文件数</TableHead>
              <TableHead class="w-16 text-center">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="submission in submissions"
              :key="submission.id"
              class="table-row-hover border-b border-border/30 last:border-b-0"
              @click="handleRowClick(submission.id)"
            >
              <TableCell class="text-center" @click.stop>
                <Checkbox
                  :checked="selectedRows.has(submission.id)"
                  @update:checked="handleSelectRow(submission.id, $event)"
                />
              </TableCell>
              <TableCell class="font-medium truncate">{{ submission.submitterName }}</TableCell>
              <TableCell class="text-sm text-muted-foreground truncate">{{ submission.submitterPhone }}</TableCell>
              <TableCell class="text-sm text-muted-foreground truncate">{{ submission.submitterDepartment }}</TableCell>
              <TableCell class="text-center">
                <span :class="submission.hasMissing ? 'text-warning font-medium' : 'text-[hsl(var(--success))] font-medium'">
                  {{ getCompletionRate(submission) }}
                </span>
              </TableCell>
              <TableCell class="text-sm text-muted-foreground whitespace-nowrap">
                {{ formatDate(submission.submittedAt) }}
              </TableCell>
              <TableCell class="text-center">
                <StatusBadge :status="submission.status" size="sm" />
              </TableCell>
              <TableCell class="text-center text-sm font-medium">{{ submission.fileCount }}</TableCell>
              <TableCell class="text-center" @click.stop>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 w-8 p-0"
                  @click="handleRowClick(submission.id)"
                >
                  <SafeIcon name="ChevronRight" :size="16" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </div>
</template>
