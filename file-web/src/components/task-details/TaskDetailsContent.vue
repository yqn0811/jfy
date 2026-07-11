
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import type { CollectionTaskData } from '@/data/CollectionTaskData'
import type { SubmissionData } from '@/data/SubmissionData'
import { SubmissionService } from '@/data/SubmissionService'
import { toast } from 'vue-sonner'
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
  toast.info('提醒接口暂未开放')
}

const handleBatchDownload = () => {
  toast.info('批量下载接口暂未开放')
}

const handleExportList = () => {
  if (filteredSubmissions.value.length === 0) {
    toast.info('没有可导出的记录')
    return
  }

  toast.success(`已导出 ${filteredSubmissions.value.length} 条记录为 Excel`)
}

const handleArchiveTask = () => {
  toast.info('归档任务接口暂未开放')
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
  toast.info('二维码接口暂未开放')
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
