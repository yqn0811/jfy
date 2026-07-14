
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
  ownerActionsEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  task: undefined,
  submissions: undefined,
  ruleConfig: undefined,
  isSubmissionsLoading: false,
  submissionsError: '',
  ownerActionsEnabled: true
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
const activeRecordScope = ref('collected')
const detailTab = ref<'files' | 'stats'>('files')
const viewMode = ref<'grid' | 'list'>('grid')

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

const collectedFileCount = computed(() => submissionList.value.reduce((total, item) => total + item.fileCount, 0))

const parseDate = (value: string) => {
  const normalized = value
    .replace(' ', 'T')
    .replace(/([+-]\d{2})$/, '$1:00')
    .replace(/([+-]\d{2})(\d{2})$/, '$1:$2')
  return new Date(normalized)
}

const dueSummary = computed(() => {
  const dueAt = currentTask.value?.dueAt
  if (!dueAt) return '未设置'
  const due = parseDate(dueAt)
  if (Number.isNaN(due.getTime())) return dueAt
  const diff = due.getTime() - Date.now()
  if (diff <= 0) return '已截止'
  const totalHours = Math.ceil(diff / 36e5)
  const days = Math.floor(totalHours / 24)
  const hours = totalHours % 24
  return days > 0 ? `${days} 天，${hours} 小时` : `${hours} 小时`
})

const recordScopes = [
  { value: 'sent', label: '我发送的', icon: 'Send' },
  { value: 'received', label: '发给我的', icon: 'Download' },
  { value: 'collected', label: '我收集的', icon: 'FileDown' },
  { value: 'requested', label: '向我收集的', icon: 'FolderOpen' },
]

const formatRuleValue = (value: string) => value || '不限制'

const handleUnavailableSetting = () => {
  toast.info('当前任务规则已生成，如需调整请重新创建收集任务')
}

const collectLink = computed(() => {
  if (typeof window === 'undefined') return `/submission-upload?taskId=${props.taskId}`
  return `${window.location.origin}/submission-upload?taskId=${props.taskId}`
})

const selectedRemindCount = computed(() => pendingRemindIds.value.length)

const handleBatchRemind = (submissionIds: string[]) => {
  if (!props.ownerActionsEnabled) {
    toast.info('登录后可查看提交记录并催办')
    return
  }
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
  if (!props.ownerActionsEnabled) {
    toast.info('登录后可批量下载提交文件')
    return
  }
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
  if (!props.ownerActionsEnabled) {
    toast.info('登录后可导出提交清单')
    return
  }
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
  if (!props.ownerActionsEnabled) {
    toast.info('登录后可归档任务')
    return
  }
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
  if (qrcodeImage.value || !props.taskId || !currentTask.value || !props.ownerActionsEnabled) {
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
  <div v-if="isClient" class="record-console">
    <template v-if="!currentTask">
      <div class="surface-base card-padding text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted/60">
          <SafeIcon name="FileQuestion" :size="28" class="text-muted-foreground" />
        </div>
        <h2 class="text-section-title mb-2">未找到任务</h2>
        <p class="text-caption">{{ submissionsError || '请从收发记录进入任务详情。' }}</p>
      </div>
    </template>

    <template v-else>
      <aside class="record-scope-nav">
        <button
          v-for="scope in recordScopes"
          :key="scope.value"
          :class="{ active: activeRecordScope === scope.value }"
          @click="activeRecordScope = scope.value"
        >
          <SafeIcon :name="scope.icon" :size="18" />
          <span>{{ scope.label }}</span>
        </button>
      </aside>

      <main class="record-workspace">
        <section class="record-task-summary">
          <div class="task-avatar">
            <SafeIcon name="FileDown" :size="26" />
          </div>
          <div class="task-title-block">
            <h1>{{ currentTask.name }}</h1>
            <p>共 {{ collectedFileCount }} 文件，总大小：0 B</p>
            <button v-if="currentTask.description" type="button" @click="toast.info(currentTask.description)">
              留言
            </button>
          </div>
          <div class="task-deadline">
            <SafeIcon name="Clock3" :size="18" />
            <span>截止时间：</span>
            <strong>{{ dueSummary }}</strong>
            <button type="button" @click="handleUnavailableSetting">修改</button>
          </div>
        </section>

        <Alert v-if="submissionsError" class="border-warning/30 bg-warning/5">
          <SafeIcon name="AlertTriangle" :size="18" class="text-warning" />
          <AlertDescription>{{ submissionsError }}</AlertDescription>
        </Alert>

        <section class="record-file-panel">
          <div class="record-tabs">
            <button :class="{ active: detailTab === 'files' }" type="button" @click="detailTab = 'files'">文件</button>
            <button :class="{ active: detailTab === 'stats' }" type="button" @click="detailTab = 'stats'">统计</button>
          </div>

          <div class="file-toolbar">
            <div class="folder-crumb">
              <SafeIcon name="Folder" :size="18" />
              <span>全部文件</span>
            </div>
            <div class="toolbar-actions">
              <Button variant="ghost" size="sm" @click="handleUnavailableSetting">
                <SafeIcon name="Plus" :size="16" class="mr-1" />
                新建文件夹
              </Button>
              <Button
                variant="ghost"
                size="icon"
                title="列表视图"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                <SafeIcon name="Files" :size="18" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                title="网格视图"
                :class="{ active: viewMode === 'grid' }"
                @click="viewMode = 'grid'"
              >
                <SafeIcon name="File" :size="18" />
              </Button>
            </div>
          </div>

          <div class="sort-row">
            <span>按名称排序（A-Z）</span>
            <SafeIcon name="ChevronDown" :size="16" />
          </div>

          <div v-if="isSubmissionsLoading" class="loading-row">
            <SafeIcon name="Loader2" :size="18" class="animate-spin" />
            <span>正在加载提交记录...</span>
          </div>

          <div v-else-if="detailTab === 'stats'" class="stats-grid">
            <div>
              <strong>{{ stats.total }}</strong>
              <span>提交人</span>
            </div>
            <div>
              <strong>{{ stats.submitted }}</strong>
              <span>已提交</span>
            </div>
            <div>
              <strong>{{ stats.needResubmit }}</strong>
              <span>需补交</span>
            </div>
            <div>
              <strong>{{ stats.approved }}</strong>
              <span>已通过</span>
            </div>
          </div>

          <template v-else>
            <div v-if="filteredSubmissions.length === 0" class="empty-file-state">
              <div class="empty-file-icon">
                <SafeIcon name="FileText" :size="62" />
              </div>
              <p>还未收集到文件</p>
            </div>

            <SubmissionTable
              v-else-if="props.ownerActionsEnabled"
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

            <div v-else class="anonymous-link-card">
              <h2>收集链接已生成</h2>
              <p>把链接发给提交人即可开始收文件。提交记录和批量下载需要登录后管理。</p>
              <div>{{ collectLink }}</div>
              <Button @click="handleCopyLink">
                <SafeIcon name="Copy" :size="16" class="mr-2" />
                复制收集链接
              </Button>
            </div>
          </template>
        </section>
      </main>

      <aside class="record-manage-panel">
        <div class="manage-header">
          <h2>管理</h2>
          <SafeIcon name="X" :size="20" />
        </div>

        <section class="manage-section compact-switch">
          <div class="section-title">
            <span></span>
            <strong>访问密码</strong>
          </div>
          <span class="readonly-switch" :class="{ 'is-on': Boolean(currentTask.accessCodeRequired) }" aria-hidden="true"></span>
        </section>

        <section class="manage-section">
          <div class="section-title">
            <span></span>
            <strong>向谁收集文件？</strong>
          </div>
          <div class="share-target-row">
            <span>QQ、微信好友</span>
            <span class="readonly-switch is-on" aria-hidden="true"></span>
          </div>
          <div class="share-icons">
            <span>Q</span>
            <span>微</span>
          </div>
        </section>

        <section class="manage-section">
          <div class="share-target-row">
            <span>生成公共链接</span>
            <span class="readonly-switch is-on" aria-hidden="true"></span>
          </div>
          <button type="button" class="manage-link" @click="handleCopyLink">{{ collectLink }}</button>
          <div class="manage-inline-actions">
            <button type="button" @click="handleCopyLink">
              <SafeIcon name="Copy" :size="16" />
              复制
            </button>
            <button type="button" @click="handleShowQRCode">
              <SafeIcon name="QrCode" :size="16" />
              二维码
            </button>
          </div>
        </section>

        <section class="manage-section">
          <div class="section-title with-action">
            <span></span>
            <strong>取件码</strong>
            <button type="button" @click="handleUnavailableSetting">重新生成</button>
          </div>
          <p class="muted-line">{{ currentTask.accessCodeRequired ? '已启用' : '未启用' }}</p>
          <div class="valid-row">
            <SafeIcon name="Clock3" :size="17" />
            <span>有效期：{{ dueSummary }}</span>
          </div>
        </section>

        <section class="manage-section">
          <div class="section-title with-action">
            <span></span>
            <strong>文件类型</strong>
            <button type="button" @click="handleUnavailableSetting">修改</button>
          </div>
          <p>{{ formatRuleValue('') }}</p>
        </section>

        <section class="manage-section">
          <div class="section-title with-action">
            <span></span>
            <strong>限制文件大小 / 数量</strong>
            <button type="button" @click="handleUnavailableSetting">修改</button>
          </div>
          <p>单文件最大：不限制</p>
          <p>总文件最大（每人）：不限制</p>
          <p>文件总数（每人）：不限制</p>
        </section>

        <section class="manage-section">
          <div class="section-title with-action">
            <span></span>
            <strong>重命名文件</strong>
            <button type="button" @click="handleUnavailableSetting">修改</button>
          </div>
          <p>{{ props.ruleConfig?.namingRule || '原文件名' }}</p>
        </section>

        <section class="manage-section">
          <div class="section-title with-action">
            <span></span>
            <strong>自动回复</strong>
            <button type="button" @click="handleUnavailableSetting">修改</button>
          </div>
          <p>无</p>
        </section>

        <section class="manage-section compact-switch">
          <div class="section-title">
            <span></span>
            <strong>允许上传文件夹</strong>
          </div>
          <span class="readonly-switch is-on" aria-hidden="true"></span>
        </section>

        <section class="manage-section compact-switch">
          <div class="section-title">
            <span></span>
            <strong>自动创建文件夹</strong>
          </div>
          <span class="readonly-switch" aria-hidden="true"></span>
        </section>

        <section class="manage-section danger-zone">
          <div class="section-title">
            <span></span>
            <strong>操作</strong>
          </div>
          <Button
            variant="outline"
            class="w-full justify-start"
            :disabled="isArchiving || currentTask.status === 'archived'"
            @click="handleArchiveTask"
          >
            <SafeIcon name="Trash2" :size="16" class="mr-2" />
            {{ isArchiving ? '销毁中...' : currentTask.status === 'archived' ? '已归档' : '销毁本次任务' }}
          </Button>
        </section>
      </aside>

      <SubmissionDetailDrawer
        v-if="selectedSubmissionId"
        :submission-id="selectedSubmissionId"
        :open="!!selectedSubmissionId"
        :allow-resubmission="props.ruleConfig?.allowResubmission ?? true"
        @close="selectedSubmissionId = null"
        @submission-updated="emit('refresh-submissions')"
      />
    </template>

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
              <p>{{ props.ownerActionsEnabled ? '二维码生成失败时，可直接复制收集链接。' : '未登录创建的任务可先直接复制收集链接。' }}</p>
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
  </div>
</template>

<style scoped>
.record-console {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr) 320px;
  min-height: calc(100vh - var(--header-height) - 48px);
  border: 1px solid hsl(var(--border));
  background: hsl(var(--card));
}

.record-scope-nav {
  border-right: 1px solid hsl(var(--border));
  background: hsl(var(--muted) / 0.35);
  padding: 22px 0;
}

.record-scope-nav button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  border-left: 4px solid transparent;
  padding: 14px 18px;
  color: hsl(var(--muted-foreground));
  text-align: left;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.record-scope-nav button.active {
  border-left-color: hsl(var(--primary));
  background: hsl(var(--primary) / 0.12);
  color: hsl(var(--primary));
  font-weight: 600;
}

.record-workspace {
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
}

.record-task-summary {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 16px;
  align-items: start;
  padding: 22px 30px;
  border-bottom: 1px solid hsl(var(--border));
}

.task-avatar {
  display: flex;
  width: 56px;
  height: 56px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #101622;
  color: hsl(var(--primary));
}

.task-title-block {
  min-width: 0;
}

.task-title-block h1 {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: hsl(var(--foreground));
  font-size: 20px;
  font-weight: 600;
}

.task-title-block p,
.task-title-block button {
  margin-top: 6px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.task-title-block button {
  color: hsl(var(--primary));
}

.task-deadline {
  display: flex;
  align-items: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  white-space: nowrap;
}

.task-deadline svg,
.valid-row svg {
  color: hsl(var(--success));
}

.task-deadline strong {
  color: hsl(var(--foreground));
}

.task-deadline button,
.section-title button,
.manage-inline-actions button {
  color: hsl(var(--primary));
}

.record-file-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 520px;
  padding: 20px 30px 26px;
}

.record-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 18px;
}

.record-tabs button {
  min-width: 96px;
  height: 36px;
  border: 1px solid hsl(var(--border));
  border-radius: 6px;
  color: hsl(var(--muted-foreground));
  background: hsl(var(--card));
}

.record-tabs button.active {
  border-color: hsl(var(--primary));
  color: hsl(var(--primary));
  box-shadow: 0 2px 8px hsl(var(--primary) / 0.16);
}

.file-toolbar,
.toolbar-actions,
.folder-crumb,
.sort-row,
.loading-row,
.valid-row,
.share-target-row,
.manage-inline-actions {
  display: flex;
  align-items: center;
}

.file-toolbar {
  justify-content: space-between;
  gap: 16px;
}

.folder-crumb {
  gap: 8px;
  color: hsl(var(--muted-foreground));
}

.toolbar-actions {
  gap: 6px;
}

.toolbar-actions .active {
  color: hsl(var(--primary));
  background: hsl(var(--primary) / 0.08);
}

.sort-row {
  justify-content: flex-end;
  gap: 8px;
  margin: 8px 0 20px;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.loading-row {
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.empty-file-state {
  flex: 1;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 18px;
  min-height: 380px;
  color: hsl(var(--muted-foreground));
}

.empty-file-icon {
  display: flex;
  width: 126px;
  height: 126px;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary) / 0.48);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.stats-grid div {
  display: grid;
  gap: 8px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  padding: 20px;
}

.stats-grid strong {
  font-size: 28px;
}

.stats-grid span,
.muted-line,
.manage-section p {
  color: hsl(var(--muted-foreground));
}

.anonymous-link-card {
  display: grid;
  gap: 14px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  padding: 18px;
}

.anonymous-link-card div,
.manage-link {
  overflow-wrap: anywhere;
  color: hsl(var(--primary));
}

.record-manage-panel {
  overflow-y: auto;
  max-height: calc(100vh - var(--header-height) - 48px);
  border-left: 1px solid hsl(var(--border));
  background: #fff;
  padding-bottom: 24px;
}

.manage-header {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid hsl(var(--border));
  background: #fff;
  padding: 16px 18px;
}

.manage-header h2 {
  font-size: 18px;
  font-weight: 700;
}

.manage-section {
  display: grid;
  gap: 12px;
  padding: 18px;
  border-bottom: 1px solid hsl(var(--border));
}

.manage-section.compact-switch {
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
}

.section-title {
  display: grid;
  grid-template-columns: 4px minmax(0, 1fr);
  gap: 12px;
  align-items: center;
}

.section-title span {
  width: 4px;
  height: 24px;
  background: hsl(var(--primary));
}

.section-title strong {
  font-size: 17px;
  font-weight: 600;
}

.section-title.with-action {
  grid-template-columns: 4px minmax(0, 1fr) auto;
}

.share-target-row {
  justify-content: space-between;
  gap: 12px;
}

.readonly-switch {
  position: relative;
  display: inline-flex;
  width: 42px;
  height: 24px;
  flex: 0 0 auto;
  border-radius: 999px;
  background: hsl(var(--muted));
}

.readonly-switch::after {
  content: "";
  position: absolute;
  top: 3px;
  left: 3px;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(18, 24, 33, 0.18);
  transition: transform 0.15s;
}

.readonly-switch.is-on {
  background: hsl(var(--primary));
}

.readonly-switch.is-on::after {
  transform: translateX(18px);
}

.share-icons {
  display: flex;
  gap: 12px;
}

.share-icons span {
  display: inline-flex;
  width: 44px;
  height: 44px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  color: #fff;
  font-weight: 700;
}

.share-icons span:first-child {
  background: #38aeea;
}

.share-icons span:last-child {
  background: #47c043;
}

.manage-link {
  text-align: left;
  font-size: 13px;
}

.manage-inline-actions {
  gap: 16px;
  color: hsl(var(--muted-foreground));
}

.manage-inline-actions button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.valid-row {
  gap: 8px;
  color: hsl(var(--muted-foreground));
}

.danger-zone button {
  color: hsl(var(--muted-foreground));
}

@media (max-width: 1180px) {
  .record-console {
    grid-template-columns: 180px minmax(0, 1fr);
  }

  .record-manage-panel {
    grid-column: 1 / -1;
    max-height: none;
    border-left: 0;
    border-top: 1px solid hsl(var(--border));
  }
}

@media (max-width: 768px) {
  .record-console {
    display: flex;
    flex-direction: column;
    min-height: auto;
  }

  .record-scope-nav {
    display: flex;
    overflow-x: auto;
    border-right: 0;
    border-bottom: 1px solid hsl(var(--border));
    padding: 0;
  }

  .record-scope-nav button {
    min-width: 132px;
    border-left: 0;
    border-bottom: 3px solid transparent;
  }

  .record-scope-nav button.active {
    border-bottom-color: hsl(var(--primary));
  }

  .record-task-summary {
    grid-template-columns: auto minmax(0, 1fr);
    padding: 18px;
  }

  .task-deadline {
    grid-column: 1 / -1;
    flex-wrap: wrap;
  }

  .record-file-panel {
    padding: 16px;
  }

  .file-toolbar,
  .sort-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
