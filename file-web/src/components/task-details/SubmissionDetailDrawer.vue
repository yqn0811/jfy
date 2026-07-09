
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { SubmissionService } from '@/data/SubmissionService'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetFooter,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from '@/components/ui/alert-dialog'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import StatusBadge from '@/components/common/StatusBadge.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  submissionId: string
  open: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submission-updated'): void
}>()

const submission = ref(SubmissionService.getDetailVOById(props.submissionId))
const showRejectDialog = ref(false)
const rejectReason = ref('')

onMounted(() => {
  submission.value = SubmissionService.getDetailVOById(props.submissionId)
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', { 
    year: 'numeric',
    month: '2-digit', 
    day: '2-digit', 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const handleApprove = () => {
  if (!submission.value) return
  
  toast.success('已标记为通过')
  emit('submission-updated')
  emit('close')
}

const handleRejectClick = () => {
  showRejectDialog.value = true
}

const handleConfirmReject = () => {
  if (!rejectReason.value.trim()) {
    toast.error('请填写退回原因')
    return
  }

  toast.success('已发送退回提醒')
  showRejectDialog.value = false
  rejectReason.value = ''
  emit('submission-updated')
  emit('close')
}

const handleDownloadFile = (fileName: string) => {
  toast.success(`已下载 ${fileName}`)
}

const handlePreviewFile = (fileName: string) => {
  window.open(`#preview-${fileName}`, '_blank')
  toast.info(`正在预览 ${fileName}`)
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('close')">
    <SheetContent side="right" class="w-full sm:w-[500px] flex flex-col max-h-[80vh]">
      <SheetHeader class="shrink-0">
        <SheetTitle class="flex items-center justify-between">
          <span>提交详情</span>
          <StatusBadge v-if="submission" :status="submission.status" />
        </SheetTitle>
        <SheetDescription v-if="submission">
          {{ submission.submitterName }} · {{ submission.submitterPhone }}
        </SheetDescription>
      </SheetHeader>

      <!-- Scrollable Content -->
      <div class="flex-1 overflow-y-auto min-h-0 py-4 space-y-6">
        <!-- 提交人信息 -->
        <div v-if="submission" class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">提交人信息</h4>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-muted-foreground">姓名</span>
              <p class="font-medium">{{ submission.submitterName }}</p>
            </div>
            <div>
              <span class="text-muted-foreground">手机号</span>
              <p class="font-medium">{{ submission.submitterPhone }}</p>
            </div>
            <div class="col-span-2">
              <span class="text-muted-foreground">部门</span>
              <p class="font-medium">{{ submission.submitterDepartment }}</p>
            </div>
          </div>
        </div>

        <!-- 缺失检查结果 -->
        <div v-if="submission?.missingCheck" class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">材料检查</h4>
          <div :class="[
            'p-3 rounded-lg border',
            submission.missingCheck.state === 'passing' ? 'bg-[hsl(var(--success))/0.05] border-[hsl(var(--success))/0.2]' : 'bg-warning/5 border-warning/20'
          ]">
            <div class="flex items-start gap-2">
              <SafeIcon 
                :name="submission.missingCheck.state === 'passing' ? 'CheckCircle2' : 'AlertCircle'" 
                :size="16"
                :class="submission.missingCheck.state === 'passing' ? 'text-[hsl(var(--success))]' : 'text-warning'"
              />
              <div class="flex-1">
                <p class="text-sm font-medium">{{ submission.missingCheck.summary }}</p>
                <p v-if="submission.missingCheck.missingNames.length > 0" class="text-xs text-muted-foreground mt-1">
                  缺失: {{ submission.missingCheck.missingNames.join('、') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- 上传文件列表 -->
        <div v-if="submission?.files && submission.files.length > 0" class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">上传文件 ({{ submission.files.length }})</h4>
          <div class="space-y-2">
            <div
              v-for="file in submission.files"
              :key="file.id"
              class="flex items-center justify-between p-3 rounded-lg border border-border/50 hover:bg-muted/30 transition-colors"
            >
              <div class="flex items-center gap-2 min-w-0">
                <SafeIcon name="File" :size="16" class="text-muted-foreground shrink-0" />
                <div class="min-w-0">
                  <p class="text-sm font-medium truncate">{{ file.fileName }}</p>
                  <p class="text-xs text-muted-foreground">{{ (file.fileSizeMb).toFixed(1) }}MB</p>
                </div>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 w-8 p-0"
                  @click="handlePreviewFile(file.fileName)"
                >
                  <SafeIcon name="Eye" :size="16" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-8 w-8 p-0"
                  @click="handleDownloadFile(file.fileName)"
                >
                  <SafeIcon name="Download" :size="16" />
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- 审核记录 -->
        <div v-if="submission?.reviewLogs && submission.reviewLogs.length > 0" class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">审核记录</h4>
          <div class="space-y-2">
            <div
              v-for="log in submission.reviewLogs"
              :key="log.id"
              class="p-3 rounded-lg border border-border/50 bg-muted/20"
            >
              <div class="flex items-start justify-between gap-2 mb-1">
                <span class="text-sm font-medium">{{ log.reviewerName }}</span>
                <span class="text-xs text-muted-foreground">{{ formatDate(log.createdAt) }}</span>
              </div>
              <p class="text-xs text-muted-foreground mb-1">
                {{ log.action === 'approve' ? '标记通过' : log.action === 'reject' ? '退回补交' : '评论' }}
              </p>
              <p class="text-sm text-foreground">{{ log.remark }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <SheetFooter class="shrink-0 flex-row gap-2 pt-4 border-t border-border/50">
        <Button
          v-if="submission?.status !== 'approved'"
          variant="outline"
          class="flex-1"
          @click="handleRejectClick"
        >
          退回补交
        </Button>
        <Button
          v-if="submission?.status !== 'approved'"
          class="flex-1"
          @click="handleApprove"
        >
          标记通过
        </Button>
        <Button
          v-else
          variant="outline"
          class="flex-1"
          @click="emit('close')"
        >
          关闭
        </Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>

  <!-- Reject Dialog -->
  <AlertDialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
    <AlertDialogContent class="max-w-md">
      <AlertDialogHeader>
        <AlertDialogTitle>退回补交</AlertDialogTitle>
        <AlertDialogDescription>
          请填写退回原因，提交人将收到提醒。
        </AlertDialogDescription>
      </AlertDialogHeader>

      <div class="space-y-3 py-4">
        <Label for="reason" class="text-sm font-medium">退回原因</Label>
        <Textarea
          id="reason"
          v-model="rejectReason"
          placeholder="例如：缺少身份证反面，请补交..."
          class="min-h-24 resize-none"
        />
      </div>

      <AlertDialogFooter>
        <AlertDialogCancel>取消</AlertDialogCancel>
        <AlertDialogAction @click="handleConfirmReject" class="bg-destructive hover:bg-destructive/90">
          确认退回
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
