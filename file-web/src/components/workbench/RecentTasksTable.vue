
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { TaskData } from '@/data/TaskData'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import SafeIcon from '@/components/common/SafeIcon.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { Progress } from '@/components/ui/progress'
import { toast } from 'vue-sonner'
import { cn } from '@/lib/utils'

interface Props {
  tasks: TaskData[]
  searchKeyword?: string
  statusFilter?: string
}

const props = withDefaults(defineProps<Props>(), {
  searchKeyword: '',
  statusFilter: 'all',
})

const emit = defineEmits<{
  (e: 'update:search-keyword', value: string): void
  (e: 'update:status-filter', value: string): void
  (e: 'task-deleted', taskId: string): void
  (e: 'task-archived', taskId: string): void
}>()

const statusOptions = [
  { value: 'all', label: '全部状态' },
  { value: 'draft', label: '草稿' },
  { value: 'collecting', label: '进行中' },
  { value: 'pending_review', label: '待审核' },
  { value: 'need_resubmission', label: '待重传' },
  { value: 'approved', label: '已通过' },
  { value: 'archived', label: '已归档' },
  { value: 'expired', label: '已过期' },
]

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return '刚刚'
  if (diffMins < 60) return `${diffMins}分钟前`
  if (diffHours < 24) return `${diffHours}小时前`
  if (diffDays < 7) return `${diffDays}天前`

  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}

const formatStorage = (gb: number) => {
  if (gb < 1) return `${(gb * 1024).toFixed(0)}MB`
  return `${gb.toFixed(1)}GB`
}

const isOverdue = (dueAt: string) => {
  return new Date(dueAt) < new Date()
}

const hoursUntilDue = (dueAt: string) => {
  const now = new Date()
  const due = new Date(dueAt)
  return Math.floor((due.getTime() - now.getTime()) / 3600000)
}

const handleTaskClick = (taskId: string) => {
  window.location.href = `./task-details.html?taskId=${taskId}`
}

const handleCopyLink = (taskId: string) => {
  const link = `${window.location.origin}/task/${taskId}`
  navigator.clipboard.writeText(link)
  toast.success('链接已复制')
}

const handleReminder = (taskId: string) => {
  toast.success('已发送提醒')
}

const handleArchive = (taskId: string) => {
  emit('task-archived', taskId)
  toast.success('任务已归档')
}

const handleDelete = (taskId: string) => {
  emit('task-deleted', taskId)
  toast.success('任务已删除')
}
</script>

<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
      <div class="flex-1 min-w-0 max-w-sm">
        <Input
          type="text"
          placeholder="搜索任务名称..."
          :value="searchKeyword"
          @input="(e) => emit('update:search-keyword', (e.target as HTMLInputElement).value)"
          class="h-9"
        />
      </div>

      <Select :model-value="statusFilter" @update:model-value="(v) => emit('update:status-filter', v)">
        <SelectTrigger class="w-full sm:w-48 h-9">
          <SelectValue placeholder="筛选状态" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Table -->
    <div class="surface-base overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow class="border-b border-border hover:bg-transparent">
            <TableHead class="w-32 whitespace-nowrap">任务名称</TableHead>
            <TableHead class="w-24 whitespace-nowrap">类型</TableHead>
            <TableHead class="w-24 whitespace-nowrap">状态</TableHead>
            <TableHead class="w-28 whitespace-nowrap">提交进度</TableHead>
            <TableHead class="w-24 whitespace-nowrap">容量</TableHead>
            <TableHead class="w-28 whitespace-nowrap">截止时间</TableHead>
            <TableHead class="w-24 whitespace-nowrap">最后更新</TableHead>
            <TableHead class="w-16 whitespace-nowrap text-right">操作</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="task in tasks"
            :key="task.id"
            class="table-row-hover border-b border-border/50"
          >
            <!-- Task Name -->
            <TableCell class="w-32 truncate">
              <button
                class="text-primary hover:underline font-medium truncate max-w-full"
                @click="handleTaskClick(task.id)"
              >
                {{ task.name }}
              </button>
            </TableCell>

            <!-- Type -->
            <TableCell class="w-24 text-caption">
              {{ task.type === 'send' ? '发送' : task.type === 'collection' ? '收集' : '归档' }}
            </TableCell>

            <!-- Status -->
            <TableCell class="w-24">
              <StatusBadge :status="task.status" size="sm" />
            </TableCell>

            <!-- Progress -->
            <TableCell class="w-28">
              <div class="flex items-center gap-2">
                <Progress :model-value="task.submitProgress * 100" class="flex-1 h-1.5" />
                <span class="text-xs font-medium text-muted-foreground shrink-0">
                  {{ Math.round(task.submitProgress * 100) }}%
                </span>
              </div>
            </TableCell>

            <!-- Storage -->
            <TableCell class="w-24 text-caption">
              {{ formatStorage(task.storageUsedGb) }} / {{ formatStorage(task.storageLimitGb) }}
            </TableCell>

            <!-- Due Date -->
            <TableCell
              class="w-28 text-caption"
              :class="cn(
                isOverdue(task.dueAt) && 'text-destructive font-medium',
                !isOverdue(task.dueAt) && hoursUntilDue(task.dueAt) < 24 && 'text-warning font-medium'
              )"
            >
              {{ new Date(task.dueAt).toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' }) }}
            </TableCell>

            <!-- Last Updated -->
            <TableCell class="w-24 text-caption">
              {{ formatDate(task.lastUpdatedAt) }}
            </TableCell>

            <!-- Actions -->
            <TableCell class="w-16 text-right">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon" class="h-8 w-8">
                    <SafeIcon name="MoreHorizontal" :size="16" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem @click="handleCopyLink(task.id)">
                    <SafeIcon name="Copy" :size="14" class="mr-2" />
                    复制链接
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="handleReminder(task.id)">
                    <SafeIcon name="Bell" :size="14" class="mr-2" />
                    提醒
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="handleArchive(task.id)">
                    <SafeIcon name="Archive" :size="14" class="mr-2" />
                    归档
                  </DropdownMenuItem>
                  <DropdownMenuItem class="text-destructive" @click="handleDelete(task.id)">
                    <SafeIcon name="Trash2" :size="14" class="mr-2" />
                    删除
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Summary -->
    <div class="text-caption text-muted-foreground">
      共 {{ tasks.length }} 个任务
    </div>
  </div>
</template>
