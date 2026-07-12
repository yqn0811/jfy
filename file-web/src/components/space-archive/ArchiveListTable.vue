
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { ArchiveItemVO } from '@/data/SpaceService'
import SearchBar from '@/components/common/SearchBar.vue'
import FilterBar from '@/components/common/FilterBar.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  items: ArchiveItemVO[]
  searchKeyword: string
  statusFilter: string
}>()

const emit = defineEmits<{
  (e: 'search', keyword: string): void
  (e: 'status-filter', status: string): void
  (e: 'archive-select', archive: ArchiveItemVO): void
  (e: 'download', archive: ArchiveItemVO): void
  (e: 'move', archive: ArchiveItemVO): void
  (e: 'delete', archive: ArchiveItemVO): void
  (e: 'permission-change', archive: ArchiveItemVO): void
}>()

const statusOptions = [
  { value: 'all', label: '全部状态' },
  { value: 'ready', label: '就绪' },
  { value: 'locked', label: '锁定' },
  { value: 'moved', label: '已移动' }
]

const getStatusLabel = (status: string) => {
  return statusOptions.find(opt => opt.value === status)?.label || status
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'ready':
      return 'text-[hsl(var(--success))]'
    case 'locked':
      return 'text-warning'
    case 'moved':
      return 'text-muted-foreground'
    default:
      return 'text-foreground'
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const formatSize = (mb: number) => {
  if (mb >= 1024) {
    return (mb / 1024).toFixed(1) + ' GB'
  }
  return mb.toFixed(1) + ' MB'
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- 筛选栏 -->
    <FilterBar>
      <SearchBar
        :model-value="searchKeyword"
        placeholder="搜索归档项..."
        @update:model-value="(keyword) => emit('search', keyword)"
      />

      <Select :model-value="statusFilter" @update:model-value="(status) => emit('status-filter', String(status))">
        <SelectTrigger class="w-40 h-9">
          <SelectValue placeholder="选择状态" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FilterBar>

    <!-- 列表 -->
    <div v-if="items.length === 0" class="surface-raised">
      <EmptyState
        icon="Archive"
        title="暂无归档项"
        description="该文件夹下还没有归档的任务。完成任务后将自动归档到此处。"
      />
    </div>

    <div v-else class="surface-raised overflow-hidden">
      <div class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow class="border-b border-border/50 hover:bg-transparent">
              <TableHead class="w-48 whitespace-nowrap">归档项名称</TableHead>
              <TableHead class="w-20 whitespace-nowrap">文件数</TableHead>
              <TableHead class="w-24 whitespace-nowrap">存储大小</TableHead>
              <TableHead class="w-28 whitespace-nowrap">归档时间</TableHead>
              <TableHead class="w-20 whitespace-nowrap">状态</TableHead>
              <TableHead class="w-20 whitespace-nowrap">权限</TableHead>
              <TableHead class="w-24 whitespace-nowrap text-right">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in items"
              :key="item.id"
              class="table-row-hover border-b border-border/30 last:border-b-0"
              @click="emit('archive-select', item)"
            >
              <TableCell class="truncate max-w-xs">
                <div class="flex items-center gap-2">
                  <SafeIcon name="Archive" :size="16" class="text-muted-foreground shrink-0" />
                  <span class="text-sm font-medium truncate">{{ item.name }}</span>
                </div>
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">{{ item.fileCount }}</TableCell>
              <TableCell class="text-sm text-muted-foreground">{{ formatSize(item.storageSizeMb) }}</TableCell>
              <TableCell class="text-sm text-muted-foreground">{{ formatDate(item.archivedAt) }}</TableCell>
              <TableCell>
                <span :class="cn('text-xs font-medium', getStatusColor(item.status))">
                  {{ item.status === 'ready' ? '就绪' : item.status === 'locked' ? '锁定' : '已移动' }}
                </span>
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">{{ item.permissionName }}</TableCell>
              <TableCell class="text-right">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                      <SafeIcon name="MoreHorizontal" :size="16" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem @click.stop="emit('download', item)">
                      <SafeIcon name="Download" :size="14" class="mr-2" />
                      下载
                    </DropdownMenuItem>
                    <DropdownMenuItem @click.stop="emit('move', item)">
                      <SafeIcon name="Move" :size="14" class="mr-2" />
                      移动
                    </DropdownMenuItem>
                    <DropdownMenuItem @click.stop="emit('permission-change', item)">
                      <SafeIcon name="Lock" :size="14" class="mr-2" />
                      权限设置
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      @click.stop="emit('delete', item)"
                      class="text-destructive focus:text-destructive"
                    >
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
    </div>
  </div>
</template>
