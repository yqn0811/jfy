
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import SafeIcon from '@/components/common/SafeIcon.vue'
import SubmissionForm from '@/components/submission_upload_page/SubmissionForm.vue'
import MaterialUploadSection from '@/components/submission_upload_page/MaterialUploadSection.vue'
import ResubmissionNoticeAlert from '@/components/submission_upload_page/ResubmissionNoticeAlert.vue'
import MissingCheckAlert from '@/components/submission_upload_page/MissingCheckAlert.vue'
import type { PublicSubmissionTaskVO } from '@/data/PublicSubmissionData'
import type { TaskFieldConfigData, TaskMaterialItemData } from '@/data/CollectionTaskData'
import { PublicSubmissionService } from '@/data/PublicSubmissionService'
import { getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

interface Props {
  taskVOData: PublicSubmissionTaskVO
  fieldConfigs: TaskFieldConfigData[]
  materialConfigs: TaskMaterialItemData[]
  isLoading?: boolean
  loadError?: string
  sourceSubmissionId?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'task-verified', task: PublicSubmissionTaskVO): void
}>()

const isClient = ref(true)
const isSubmitting = ref(false)
const isTaskExpired = ref(false)
const isAccessCodeRequired = ref(props.taskVOData.accessCodeRequired)
const accessCodeInput = ref('')
const isAccessCodeValid = ref(!props.taskVOData.accessCodeRequired || Boolean(props.taskVOData.accessCodeVerified))

const formData = ref<Record<string, string>>({})
const uploadedFiles = ref<Record<string, File[]>>({})
const resubmissionNotice = ref<any>(null)
const missingCheckResult = ref<any>(null)
const isResubmission = computed(() => Boolean(props.sourceSubmissionId))

const dueDate = computed(() => {
  const date = new Date(props.taskVOData.dueAt)
  return date.toLocaleString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
})

const isExpired = computed(() => {
  return props.taskVOData.status !== 'active' || new Date(props.taskVOData.dueAt) < new Date()
})

const allRequiredFieldsFilled = computed(() => {
  return props.fieldConfigs.every(field => {
    if (field.required) {
      return formData.value[field.fieldKey]?.trim().length > 0
    }
    return true
  })
})

const allRequiredMaterialsUploaded = computed(() => {
  return props.materialConfigs.every(material => {
    if (material.required) {
      return (uploadedFiles.value[material.id] || []).length > 0
    }
    return true
  })
})

const canSubmit = computed(() => {
  return !isSubmitting.value && !isExpired.value && isAccessCodeValid.value && allRequiredFieldsFilled.value && allRequiredMaterialsUploaded.value
})

const handleAccessCodeSubmit = () => {
  void verifyAccessCode()
}

const verifyAccessCode = async () => {
  if (!accessCodeInput.value.trim()) {
    toast.error('请输入访问密码')
    return
  }
  try {
    const task = await PublicSubmissionService.verifyAccessCode(props.taskVOData.taskId, accessCodeInput.value.trim())
    isAccessCodeValid.value = true
    emit('task-verified', task)
    toast.success('密码验证通过')
  } catch (error) {
    toast.error(getApiErrorMessage(error, '密码错误，请重试'))
  }
}

const handleFormUpdate = (key: string, value: string) => {
  formData.value[key] = value
}

const handleFilesSelected = (materialId: string, files: File[]) => {
  uploadedFiles.value[materialId] = files
}

const handleSubmit = async () => {
  if (!canSubmit.value) {
    toast.error('请完成所有必填项')
    return
  }

  isSubmitting.value = true
  try {
    const receipt = await PublicSubmissionService.submitPublic({
      taskId: props.taskVOData.taskId,
      accessCode: accessCodeInput.value.trim(),
      sourceSubmissionId: props.sourceSubmissionId || '',
      submitterFields: formData.value,
      filesByMaterialId: uploadedFiles.value,
    })
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(`submission-draft-${props.taskVOData.taskId}`)
    }
    toast.success('提交成功')
    const sourceQuery = receipt.sourceSubmissionId ? `&sourceSubmissionId=${encodeURIComponent(receipt.sourceSubmissionId)}` : ''
    navigateTo(`/submission-success?submissionId=${encodeURIComponent(receipt.submissionId)}&taskId=${encodeURIComponent(receipt.taskId || props.taskVOData.taskId)}${sourceQuery}`)
  } catch (error) {
    toast.error(getApiErrorMessage(error, '提交出错，请重试'))
  } finally {
    isSubmitting.value = false
  }
}

const saveDraft = () => {
  const draft = {
    taskId: props.taskVOData.taskId,
    formData: formData.value,
    uploadedFiles: Object.keys(uploadedFiles.value).reduce((acc, key) => {
      acc[key] = uploadedFiles.value[key].map(f => ({ name: f.name, size: f.size }))
      return acc
    }, {} as Record<string, any>),
    savedAt: new Date().toISOString()
  }
  localStorage.setItem(`submission-draft-${props.taskVOData.taskId}`, JSON.stringify(draft))
  toast.success('草稿已保存')
}

const loadDraft = () => {
  const draft = localStorage.getItem(`submission-draft-${props.taskVOData.taskId}`)
  if (draft) {
    const data = JSON.parse(draft)
    formData.value = data.formData || {}
  }
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
    
    const params = new URLSearchParams(window.location.search)
    const accessCode = params.get('accessCode')
    if (accessCode) {
      accessCodeInput.value = accessCode
    }
    
    loadDraft()
    isTaskExpired.value = isExpired.value
  })
})

watch(
  () => props.taskVOData,
  (task) => {
    isAccessCodeRequired.value = task.accessCodeRequired
    isAccessCodeValid.value = !task.accessCodeRequired || Boolean(task.accessCodeVerified)
    isTaskExpired.value = isExpired.value
  },
  { deep: true }
)
</script>

<template>
  <div v-if="isClient && !isLoading" class="space-y-6">
    <Alert v-if="loadError" class="border-destructive/30 bg-destructive/5">
      <SafeIcon name="AlertCircle" :size="18" class="text-destructive" />
      <AlertTitle>任务加载失败</AlertTitle>
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <template v-else>
      <!-- 任务说明卡片 -->
      <Card class="surface-raised">
        <CardHeader>
          <div class="flex items-start justify-between gap-4">
            <div>
              <CardTitle class="text-2xl">{{ taskVOData.taskName }}</CardTitle>
              <CardDescription class="mt-2">{{ taskVOData.organizationName }} 发起</CardDescription>
            </div>
            <div v-if="isTaskExpired" class="px-3 py-1 bg-destructive/10 text-destructive rounded-full text-xs font-medium whitespace-nowrap">
              已过期
            </div>
            <div v-else class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium whitespace-nowrap">
              进行中
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <p class="text-sm text-foreground leading-relaxed">{{ taskVOData.description }}</p>

          <div class="grid grid-cols-2 gap-4 pt-2 border-t border-border">
            <div>
              <p class="text-xs text-muted-foreground mb-1">截止时间</p>
              <p class="text-sm font-medium">{{ dueDate }}</p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground mb-1">提交方</p>
              <p class="text-sm font-medium">{{ taskVOData.organizationName }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- 任务已过期提示 -->
      <Alert v-if="isTaskExpired" class="border-destructive/30 bg-destructive/5">
        <SafeIcon name="AlertCircle" :size="18" class="text-destructive" />
        <AlertTitle>任务已过期</AlertTitle>
        <AlertDescription>
          此任务的截止时间已过，暂不接受新提交。如有疑问，请联系发起方。
        </AlertDescription>
      </Alert>

      <!-- 访问密码验证 -->
      <div v-if="isAccessCodeRequired && !isAccessCodeValid" class="surface-base card-padding">
        <h3 class="text-item-title mb-4">访问验证</h3>
        <p class="text-caption mb-4">此任务需要访问密码，请输入后继续</p>
        <div class="flex gap-2">
          <input
            v-model="accessCodeInput"
            type="password"
            placeholder="请输入访问密码"
            class="flex-1 px-3 py-2 border border-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
          />
          <Button @click="handleAccessCodeSubmit">验证</Button>
        </div>
      </div>

      <!-- 退回补交通知 -->
      <ResubmissionNoticeAlert v-if="resubmissionNotice" :notice="resubmissionNotice" />

      <Alert v-else-if="isResubmission" class="border-warning/30 bg-warning/5">
        <SafeIcon name="RotateCcw" :size="18" class="text-warning" />
        <AlertTitle>补交材料</AlertTitle>
        <AlertDescription>
          当前正在补交已退回的提交记录，请按发起方要求重新上传材料。
        </AlertDescription>
      </Alert>

      <!-- 缺失检查结果 -->
      <MissingCheckAlert v-if="missingCheckResult" :result="missingCheckResult" />

      <!-- 提交表单和上传区域（需要通过密码验证才显示） -->
      <template v-if="!isAccessCodeRequired || isAccessCodeValid">
        <!-- 提交人信息表单 -->
        <SubmissionForm
          :fields="fieldConfigs"
          :form-data="formData"
          @update:formData="handleFormUpdate"
        />

        <!-- 材料上传区域 -->
        <MaterialUploadSection
          :materials="materialConfigs"
          :uploaded-files="uploadedFiles"
          @files-selected="handleFilesSelected"
        />

        <!-- 底部操作栏 -->
        <div class="flex gap-3 pt-4 border-t border-border sticky bottom-0 bg-background/95 backdrop-blur-sm -mx-6 px-6 py-4">
          <Button
            variant="outline"
            @click="saveDraft"
            class="flex-1"
          >
            保存草稿
          </Button>
          <Button
            :disabled="!canSubmit"
            @click="handleSubmit"
            class="flex-1"
          >
            <SafeIcon v-if="!isSubmitting" name="Send" :size="16" class="mr-2" />
            <SafeIcon v-else name="Loader2" :size="16" class="mr-2 animate-spin" />
            {{ isSubmitting ? '提交中...' : isResubmission ? '提交补交材料' : '提交' }}
          </Button>
        </div>
      </template>
    </template>
  </div>

  <!-- SSG 初始状态 -->
  <div v-else class="space-y-6">
    <Card class="surface-raised animate-pulse">
      <CardHeader>
        <div class="h-8 bg-muted rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-muted rounded w-1/2"></div>
      </CardHeader>
      <CardContent class="space-y-3">
        <div class="h-4 bg-muted rounded"></div>
        <div class="h-4 bg-muted rounded w-5/6"></div>
      </CardContent>
    </Card>

    <div class="space-y-4">
      <div class="h-10 bg-muted rounded"></div>
      <div class="h-32 bg-muted rounded"></div>
    </div>
  </div>
</template>
