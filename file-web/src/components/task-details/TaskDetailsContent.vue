
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import type { CollectionTaskData, TaskRuleConfigData } from '@/data/CollectionTaskData'
import type { SubmissionData } from '@/data/SubmissionData'
import { FileTransferApi } from '@/data/FileTransferApi'
import { SubmissionService } from '@/data/SubmissionService'
import { toast } from 'vue-sonner'
import { getApiErrorMessage } from '@/lib/apiClient'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import TaskDetailsHeader from '@/components/task-details/TaskDetailsHeader.vue'
import StatCardsRow from '@/components/task-details/StatCardsRow.vue'
import SubmissionTable from '@/components/task-details/SubmissionTable.vue'
import SubmissionDetailDrawer from '@/components/task-details/SubmissionDetailDrawer.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  task?: CollectionTaskData
  submissions?: SubmissionData[]
  taskId: string
  ruleConfig?: TaskRuleConfigData
  isSubmissionsLoading?: boolean
  submissionsError?: string
}

const props = withDefaults(defineProps<Props>(), {
  task: undefined,
  submissions: undefined,
  ruleConfig: undefined,
  isSubmissionsLoading: false,
  submissionsError: ''
})

const emit = defineEmits<{
  (e: 'refresh-submissions'): void
}>()

const isClient = ref(true)
const currentTask = ref<CollectionTaskData | undefined>(props.task)
const submissionList = ref<SubmissionData[]>(props.submissions || [])
const selectedSubmissionId = ref<string | null>(null)
const searchKeyword = ref('')
const statusFilter = ref<string>('')
const showMissingOnly = ref(false)
const isArchiving = ref(false)
const isBatchDownloading = ref(false)
const isQrcodeDialogOpen = ref(false)
const isQrcodeLoading = ref(false)
const qrcodeImage = ref('')
const qrcodeUrl = ref('')
const isRemindDialogOpen = ref(false)
const isReminding = ref(false)
const pendingRemindIds = ref<string[]>([])
const remindRemark = ref('')

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

  if (statusFilter.value && statusFilter.value !== 'all') {
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

const collectLink = computed(() => {
  if (typeof window === 'undefined') return `/submission-upload?taskId=${props.taskId}`
  return `${window.location.origin}/submission-upload?taskId=${props.taskId}`
})

const selectedRemindCount = computed(() => pendingRemindIds.value.length)

const handleBatchRemind = (submissionIds: string[]) => {
  if (!submissionIds.length) {
    toast.info('请先勾选需要提醒的提交记录')
    return
  }
  if (!props.taskId || !currentTask.value) {
    toast.info('当前任务不可催办')
    return
  }
  pendingRemindIds.value = submissionIds
  remindRemark.value = `请查看收集任务「${currentTask.value?.name || '材料收集'}」并按要求补交材料。`
  isRemindDialogOpen.value = true
}

const handleConfirmRemind = async () => {
  if (!pendingRemindIds.value.length) return

  try {
    isReminding.value = true
    const result = await SubmissionService.remindRemote({
      taskId: props.taskId,
      submissionIds: pendingRemindIds.value,
      remark: remindRemark.value.trim(),
    })
    toast.success(`已记录 ${result.remindedCount} 条催办`)
    isRemindDialogOpen.value = false
    pendingRemindIds.value = []
    emit('refresh-submissions')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '催办记录失败，请重试'))
  } finally {
    isReminding.value = false
  }
}

const handleBatchDownload = async () => {
  if (!props.taskId || !currentTask.value) {
    toast.info('当前没有可下载的提交文件')
    return
  }

  try {
    isBatchDownloading.value = true
    await FileTransferApi.downloadCollectionTaskSubmissions(props.taskId, `${currentTask.value?.name || '提交文件'}.zip`)
    toast.success('已开始下载提交文件')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '批量下载失败，请重试'))
  } finally {
    isBatchDownloading.value = false
  }
}

const handleExportList = () => {
  if (filteredSubmissions.value.length === 0) {
    toast.info('没有可导出的记录')
    return
  }

  const headers = ['提交人', '手机号', '部门', '状态', '是否缺失', '文件数', '提交时间']
  const rows = filteredSubmissions.value.map((item) => [
    item.submitterName,
    item.submitterPhone,
    item.submitterDepartment,
    getStatusText(item.status),
    item.hasMissing ? '是' : '否',
    String(item.fileCount),
    formatDateTime(item.submittedAt),
  ])
  downloadCsv(`${currentTask.value?.name || '提交清单'}-提交清单.csv`, [headers, ...rows])
  toast.success(`已导出 ${filteredSubmissions.value.length} 条记录`)
}

const handleArchiveTask = async () => {
  if (!props.taskId || !currentTask.value) {
    toast.info('当前任务不可归档')
    return
  }

  try {
    isArchiving.value = true
    const detail = await FileTransferApi.archiveCollectionTask(props.taskId)
    currentTask.value = detail.task
    toast.success('任务已归档')
    emit('refresh-submissions')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '归档失败，请重试'))
  } finally {
    isArchiving.value = false
  }
}

const handleCopyLink = () => {
  navigator.clipboard.writeText(collectLink.value).then(() => {
    toast.success('收集链接已复制')
  }).catch(() => {
    toast.error('复制失败，请重试')
  })
}

const handlePreviewSubmission = () => {
  window.open(`/submission-upload?taskId=${props.taskId}`, '_blank')
}

const handleShowQRCode = async () => {
  qrcodeUrl.value = collectLink.value
  isQrcodeDialogOpen.value = true
  if (qrcodeImage.value || !props.taskId || !currentTask.value) {
    return
  }

  try {
    isQrcodeLoading.value = true
    const result = await FileTransferApi.getCollectionTaskQrcode(props.taskId, collectLink.value)
    qrcodeImage.value = result.qrcode
    qrcodeUrl.value = result.url || collectLink.value
  } catch (error) {
    toast.error(getApiErrorMessage(error, '二维码生成失败，已保留收集链接'))
  } finally {
    isQrcodeLoading.value = false
  }
}

const handleCopyQrcodeLink = () => {
  navigator.clipboard.writeText(qrcodeUrl.value || collectLink.value).then(() => {
    toast.success('收集链接已复制')
  }).catch(() => {
    toast.error('复制失败，请重试')
  })
}

const handleDownloadQrcode = () => {
  if (!qrcodeImage.value) {
    toast.info('二维码尚未生成')
    return
  }
  const link = document.createElement('a')
  link.href = qrcodeImage.value
  link.download = `${currentTask.value?.name || '收集任务'}-二维码.png`.replace(/[\\/:*?"<>|\r\n]+/g, '_')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const getStatusText = (status: SubmissionData['status']) => {
  const map: Record<SubmissionData['status'], string> = {
    draft: '草稿',
    submitted: '已提交',
    pending_review: '待审核',
    need_resubmission: '需补交',
    approved: '已通过',
  }
  return map[status] || status
}

const formatDateTime = (value: string) => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const escapeCsvCell = (value: string) => {
  const text = String(value ?? '')
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

const downloadCsv = (filename: string, rows: string[][]) => {
  const content = '\uFEFF' + rows.map((row) => row.map(escapeCsvCell).join(',')).join('\r\n')
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename.replace(/[\\/:*?"<>|\r\n]+/g, '_')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
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
      :is-archiving="isArchiving"
      @copy-link="handleCopyLink"
      @show-qrcode="handleShowQRCode"
      @preview-submission="handlePreviewSubmission"
      @archive-task="handleArchiveTask"
    />

    <template v-else>
      <div class="surface-base card-padding text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted/60">
          <SafeIcon name="FileQuestion" :size="28" class="text-muted-foreground" />
        </div>
        <h2 class="text-section-title mb-2">未找到任务</h2>
        <p class="text-caption">{{ submissionsError || '请从工作台或交付记录进入任务详情。' }}</p>
      </div>
    </template>

    <template v-if="currentTask">
    <!-- Stats Cards -->
    <StatCardsRow :stats="stats" />

    <Alert v-if="submissionsError" class="border-warning/30 bg-warning/5">
      <SafeIcon name="AlertTriangle" :size="18" class="text-warning" />
      <AlertDescription>{{ submissionsError }}</AlertDescription>
    </Alert>

    <div v-if="isSubmissionsLoading" class="flex items-center gap-2 text-sm text-muted-foreground">
      <SafeIcon name="Loader2" :size="16" class="animate-spin" />
      <span>正在加载提交记录...</span>
    </div>

    <!-- Submissions Table -->
    <SubmissionTable
      :submissions="filteredSubmissions"
      :is-batch-downloading="isBatchDownloading"
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
      :allow-resubmission="props.ruleConfig?.allowResubmission ?? true"
      @close="selectedSubmissionId = null"
      @submission-updated="emit('refresh-submissions')"
    />

    <Dialog :open="isQrcodeDialogOpen" @update:open="isQrcodeDialogOpen = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>收集二维码</DialogTitle>
          <DialogDescription>提交人扫描后可打开材料提交页。</DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div class="flex aspect-square w-full items-center justify-center rounded-lg border border-border bg-muted/30 p-6">
            <SafeIcon v-if="isQrcodeLoading" name="Loader2" :size="40" class="animate-spin text-muted-foreground" />
            <img
              v-else-if="qrcodeImage"
              :src="qrcodeImage"
              alt="收集二维码"
              class="h-full w-full object-contain"
            />
            <div v-else class="text-center text-sm text-muted-foreground">
              <SafeIcon name="QrCode" :size="44" class="mx-auto mb-2 text-muted-foreground/50" />
              <p>二维码生成失败时，可直接复制收集链接。</p>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="collection-link">收集链接</Label>
            <Input id="collection-link" :model-value="qrcodeUrl || collectLink" readonly class="text-xs" />
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" @click="handleCopyQrcodeLink">
            <SafeIcon name="Copy" :size="16" class="mr-2" />
            复制链接
          </Button>
          <Button :disabled="!qrcodeImage" @click="handleDownloadQrcode">
            <SafeIcon name="Download" :size="16" class="mr-2" />
            下载二维码
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="isRemindDialogOpen" @update:open="isRemindDialogOpen = $event">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>记录催办</DialogTitle>
          <DialogDescription>
            将为选中的 {{ selectedRemindCount }} 条提交记录写入催办记录，请复制链接或通过现有沟通渠道通知提交人。
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3">
          <Label for="remind-remark">催办备注</Label>
          <Textarea
            id="remind-remark"
            v-model="remindRemark"
            class="min-h-24 resize-none"
            placeholder="填写给提交人的补交说明"
          />
          <div class="rounded-lg border border-border/60 bg-muted/30 p-3 text-xs text-muted-foreground">
            当前系统仅记录催办动作，不会自动发送短信或微信通知。
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button variant="outline" :disabled="isReminding" @click="isRemindDialogOpen = false">取消</Button>
          <Button :disabled="isReminding || selectedRemindCount === 0" @click="handleConfirmRemind">
            <SafeIcon :name="isReminding ? 'Loader2' : 'Bell'" :size="16" :class="isReminding ? 'mr-2 animate-spin' : 'mr-2'" />
            {{ isReminding ? '记录中...' : '确认记录' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
    </template>
  </div>
</template>
