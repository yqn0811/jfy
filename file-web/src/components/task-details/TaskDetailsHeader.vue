
<script setup lang="ts">
import { computed } from 'vue'
import type { CollectionTaskData } from '@/data/CollectionTaskData'
import { Button } from '@/components/ui/button'
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from '@/components/ui/dropdown-menu'
import DetailPageHeader from '@/components/common/DetailPageHeader.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  task: CollectionTaskData
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'copy-link'): void
  (e: 'show-qrcode'): void
  (e: 'preview-submission'): void
  (e: 'archive-task'): void
}>()

const breadcrumbs = [
  { label: '工作台', href: './workbench.html' },
  { label: props.task.name }
]

const isOverdue = computed(() => {
  const now = new Date()
  const due = new Date(props.task.dueAt)
  return now > due
})

const hoursUntilDue = computed(() => {
  const now = new Date()
  const due = new Date(props.task.dueAt)
  const hours = Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60))
  return hours
})

const handleArchiveClick = () => {
  if (confirm('确认要归档此任务吗？归档后将无法编辑。')) {
    emit('archive-task')
  }
}
</script>

<template>
  <div>
    <DetailPageHeader
      :title="task.name"
      :status="task.status"
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <!-- 截止时间提示 -->
          <div v-if="!isOverdue && hoursUntilDue < 24" class="flex items-center gap-2 px-3 py-1.5 bg-warning/10 rounded-md border border-warning/20">
            <SafeIcon name="AlertCircle" :size="16" class="text-warning" />
            <span class="text-xs font-medium text-warning">
              {{ hoursUntilDue }}小时后截止
            </span>
          </div>

          <div v-if="isOverdue" class="flex items-center gap-2 px-3 py-1.5 bg-destructive/10 rounded-md border border-destructive/20">
            <SafeIcon name="AlertTriangle" :size="16" class="text-destructive" />
            <span class="text-xs font-medium text-destructive">已过期</span>
          </div>

          <!-- 更多操作 -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="sm" class="gap-2">
                <span>更多</span>
                <SafeIcon name="ChevronDown" :size="16" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
              <DropdownMenuItem @click="emit('copy-link')" class="gap-2">
                <SafeIcon name="Copy" :size="16" />
                <span>复制收集链接</span>
              </DropdownMenuItem>
              <DropdownMenuItem @click="emit('show-qrcode')" class="gap-2">
                <SafeIcon name="QrCode" :size="16" />
                <span>查看二维码</span>
              </DropdownMenuItem>
              <DropdownMenuItem @click="emit('preview-submission')" class="gap-2">
                <SafeIcon name="Eye" :size="16" />
                <span>预览提交页</span>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                v-if="task.status !== 'archived'"
                @click="handleArchiveClick" 
                class="gap-2 text-destructive focus:text-destructive"
              >
                <SafeIcon name="Archive" :size="16" />
                <span>归档任务</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </template>
    </DetailPageHeader>

    <!-- 任务描述 -->
    <div v-if="task.description" class="mt-4 p-4 bg-accent/30 rounded-lg border border-accent">
      <p class="text-sm text-foreground">{{ task.description }}</p>
    </div>
  </div>
</template>
