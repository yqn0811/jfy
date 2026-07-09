
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import type { CollectionTaskData } from '@/data/CollectionTaskData'
import type { SubmissionData } from '@/data/SubmissionData'
import { CollectionTaskService } from '@/data/CollectionTaskService'
import { SubmissionService } from '@/data/SubmissionService'
import { toast } from 'vue-sonner'
import { navigateTo } from '@/navigation'
import TaskDetailsHeader from '@/components/task-details/TaskDetailsHeader.vue'
import StatCardsRow from '@/components/task-details/StatCardsRow.vue'
import SubmissionTable from '@/components/task-details/SubmissionTable.vue'
import SubmissionDetailDrawer from '@/components/task-details/SubmissionDetailDrawer.vue'

interface Props {
  task?: CollectionTaskData
  submissions?: SubmissionData[]
  taskId: string
}

const props = withDefaults(defineProps<Props>(), {
  task: undefined,
  submissions: undefined
})

const isClient = ref(true)
const currentTask = ref<CollectionTaskData | undefined>(props.task)
const submissionList = ref<SubmissionData[]>(props.submissions || [])
const selectedSubmissionId = ref<string | null>(null)
const searchKeyword = ref('')
const statusFilter = ref<string>('')
const showMissingOnly = ref(false)

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })
})

const filteredSubmissions = computed(() => {
  let result = submissionList.value

  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(item => item.submitterName.toLowerCase().includes(keyword))
  }

  if (statusFilter.value) {
    result = result.filter(item => item.status === statusFilter.value)
  }

  if (showMissingOnly.value) {
    result = result.filter(item => item.hasMissing)
  }

  return result
})

const stats = computed(() => {
  const total = submissionList.value.length
  const submitted = submissionList.value.filter(s => s.status !== 'draft').length
  const needResubmit = submissionList.value.filter(s => s.status === 'need_resubmission').length
  const approved = submissionList.value.filter(s => s.status === 'approved').length

  return { total, submitted, needResubmit, approved }
})

const handleBatchRemind = () => {
  const targetCount = filteredSubmissions.value.filter(s => 
    s.status === 'pending_review' || s.status === 'need_resubmission'
  ).length

  if (targetCount === 0) {
    toast.info('没有需要提醒的提交记录')
    return
  }

  toast.success(`已向 ${targetCount} 人发送提醒`)
}

const handleBatchDownload = () => {
  if (filteredSubmissions.value.length === 0) {
    toast.info('没有可下载的提交记录')
    return
  }

  toast.loading('正在打包文件...')
  setTimeout(() => {
    toast.success(`已打包 ${filteredSubmissions.value.length} 份提交材料`)
  }, 1500)
}

const handleExportList = () => {
  if (filteredSubmissions.value.length === 0) {
    toast.info('没有可导出的记录')
    return
  }

  toast.success(`已导出 ${filteredSubmissions.value.length} 条记录为 Excel`)
}

const handleArchiveTask = () => {
  if (!currentTask.value) return
  
  CollectionTaskService.archiveTask(currentTask.value.id)
  currentTask.value.status = 'archived'
  
  toast.success('任务已归档')
  setTimeout(() => {
    navigateTo('/space-archive')
  }, 800)
}

const handleCopyLink = () => {
  const link = `${window.location.origin}/submission-upload?taskId=${props.taskId}`
  navigator.clipboard.writeText(link).then(() => {
    toast.success('收集链接已复制')
  }).catch(() => {
    toast.error('复制失败，请重试')
  })
}

const handlePreviewSubmission = () => {
  window.open(`/submission-upload?taskId=${props.taskId}`, '_blank')
}

const handleShowQRCode = () => {
  toast.info('二维码已显示（模拟）')
}

watch(
  () => props.task,
  (nextTask) => {
    currentTask.value = nextTask
  },
  { immediate: true }
)

watch(
  () => props.submissions,
  (nextSubmissions) => {
    submissionList.value = nextSubmissions || []
  },
  { immediate: true }
)
</script>

<template>
  <div v-if="isClient" class="flex flex-col gap-6">
    <!-- Header -->
    <TaskDetailsHeader
      v-if="currentTask"
      :task="currentTask"
      @copy-link="handleCopyLink"
      @show-qrcode="handleShowQRCode"
      @preview-submission="handlePreviewSubmission"
      @archive-task="handleArchiveTask"
    />

    <!-- Stats Cards -->
    <StatCardsRow :stats="stats" />

    <!-- Submissions Table -->
    <SubmissionTable
      :submissions="filteredSubmissions"
      :search-keyword="searchKeyword"
      :status-filter="statusFilter"
      :show-missing-only="showMissingOnly"
      :selected-submission-id="selectedSubmissionId"
      @update:search-keyword="searchKeyword = $event"
      @update:status-filter="statusFilter = $event"
      @update:show-missing-only="showMissingOnly = $event"
      @select-submission="selectedSubmissionId = $event"
      @batch-remind="handleBatchRemind"
      @batch-download="handleBatchDownload"
      @export-list="handleExportList"
    />

    <!-- Detail Drawer -->
    <SubmissionDetailDrawer
      v-if="selectedSubmissionId"
      :submission-id="selectedSubmissionId"
      :open="!!selectedSubmissionId"
      @close="selectedSubmissionId = null"
      @submission-updated="() => {
        submissionList = SubmissionService.query({ filter: { collectionTaskId: props.taskId } })
      }"
    />
  </div>
</template>
