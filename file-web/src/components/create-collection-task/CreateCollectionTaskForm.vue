
<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  AlertDialog,
  AlertDialogTrigger,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
} from '@/components/ui/alert-dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import StepIndicator from '@/components/create-collection-task/StepIndicator.vue'
import BasicInfoStep from '@/components/create-collection-task/BasicInfoStep.vue'
import SubmitterFieldsStep from '@/components/create-collection-task/SubmitterFieldsStep.vue'
import MaterialsStep from '@/components/create-collection-task/MaterialsStep.vue'
import RulesStep from '@/components/create-collection-task/RulesStep.vue'
import PreviewStep from '@/components/create-collection-task/PreviewStep.vue'
import { CollectionTaskService } from '@/data/CollectionTaskService'
import { TemplateService } from '@/data/TemplateService'
import type { CollectionTaskData, TaskFieldConfigData, TaskMaterialItemData, TaskRuleConfigData } from '@/data/CollectionTaskData'
import { FileTransferApi } from '@/data/FileTransferApi'
import { getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

const isClient = ref(true)
const currentStep = ref(1)
const totalSteps = 5
const isSaving = ref(false)
const isCreating = ref(false)
const showCancelDialog = ref(false)
const lastSaveTime = ref(0)
const LOCAL_FALLBACK_ENABLED =
  import.meta.env.DEV ||
  import.meta.env.PUBLIC_ENABLE_MOCK === '1' ||
  import.meta.env.PUBLIC_ENABLE_MOCK === 'true'

const formData = reactive({
  name: '',
  description: '',
  dueAt: '',
  submitTargetDescription: '',
  fields: [] as TaskFieldConfigData[],
  materials: [] as TaskMaterialItemData[],
  ruleConfig: {
    namingRule: '',
    allowResubmission: true,
    enableAICheck: true,
    anonymousSubmit: false,
    allowPreview: false,
    reminderBeforeDueHours: 24,
  } as Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'>,
})

const steps = [
  { number: 1, label: '基本信息', description: '任务名称、说明、截止时间' },
  { number: 2, label: '提交人字段', description: '配置提交人信息字段' },
  { number: 3, label: '必传材料', description: '配置需要收集的材料清单' },
  { number: 4, label: '提交规则', description: '配置提交规则和审核选项' },
  { number: 5, label: '预览与生成', description: '预览提交页并生成任务' },
]

const canProceedToNext = computed(() => {
  switch (currentStep.value) {
    case 1:
      return formData.name.trim() && formData.dueAt
    case 2:
      return formData.fields.length > 0
    case 3:
      return formData.materials.length > 0
    case 4:
      return formData.ruleConfig.namingRule.trim()
    case 5:
      return true
    default:
      return false
  }
})

const handleNextStep = () => {
  if (!canProceedToNext.value) {
    toast.error('请完成当前步骤的必填项')
    return
  }

  if (currentStep.value < totalSteps) {
    currentStep.value++
    autoSaveDraft()
  }
}

const handlePreviousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const autoSaveDraft = () => {
  const now = Date.now()
  if (now - lastSaveTime.value < 1000) return

  isSaving.value = true
  lastSaveTime.value = now

  try {
    const draft = {
      id: `draft-${Date.now()}`,
      templateId: null,
      name: formData.name,
      description: formData.description,
      dueAt: formData.dueAt,
      submitTargetDescription: formData.submitTargetDescription,
      submitterFieldIds: formData.fields.map((f) => f.id),
      materialItemIds: formData.materials.map((m) => m.id),
      ruleConfigId: `rule-${Date.now()}`,
      stepIndex: currentStep.value,
      savedAt: new Date().toISOString(),
    }

    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('collectionTaskDraft', JSON.stringify(draft))
      localStorage.setItem('collectionTaskDraftFields', JSON.stringify(formData.fields))
      localStorage.setItem('collectionTaskDraftMaterials', JSON.stringify(formData.materials))
      localStorage.setItem('collectionTaskDraftRuleConfig', JSON.stringify(formData.ruleConfig))
    }

    toast.success('已保存草稿', { duration: 2000 })
  } catch (error) {
    console.error('Save draft error:', error)
  } finally {
    isSaving.value = false
  }
}

const handleCreateTask = async () => {
  if (!canProceedToNext.value) {
    toast.error('请完成所有必填项')
    return
  }

  isCreating.value = true

  try {
    const created = await FileTransferApi.createCollectionTask({
      templateId: null,
      name: formData.name,
      description: formData.description,
      dueAt: formData.dueAt,
      submitTargetDescription: formData.submitTargetDescription,
      fields: formData.fields,
      materials: formData.materials,
      ruleConfig: formData.ruleConfig,
    })

    persistCreatedTask(created.task)
    clearDraftStorage()

    toast.success('收集任务创建成功！')

    setTimeout(() => {
      navigateTo(`/task-details?taskId=${created.task.id}`)
    }, 500)
  } catch (error) {
    console.error('Create task error:', error)
    if (!LOCAL_FALLBACK_ENABLED) {
      toast.error(getApiErrorMessage(error, '创建任务失败，请重试'))
      return
    }

    const localTask = createLocalTask()
    persistCreatedTask(localTask)
    clearDraftStorage()
    toast.success('收集任务创建成功！')
    setTimeout(() => {
      navigateTo(`/task-details?taskId=${localTask.id}`)
    }, 500)
  } finally {
    isCreating.value = false
  }
}

const createLocalTask = () => ({
  id: `task-${Date.now()}`,
  teamId: 'team-001',
  templateId: null,
  name: formData.name,
  description: formData.description,
  status: 'collecting' as const,
  dueAt: formData.dueAt,
  submitTargetDescription: formData.submitTargetDescription,
  submitterFieldIds: formData.fields.map((f) => f.id),
  materialItemIds: formData.materials.map((m) => m.id),
  ruleConfigId: `rule-${Date.now()}`,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  archivedAt: null,
  ownerId: 'user-001',
})

const persistCreatedTask = (task: CollectionTaskData) => {
  const allTasks = CollectionTaskService.getAll()
  const existingIndex = allTasks.findIndex((item) => item.id === task.id)
  if (existingIndex >= 0) {
    allTasks[existingIndex] = task
  } else {
    allTasks.push(task)
  }
  CollectionTaskService.savePersisted(allTasks)
}

const clearDraftStorage = () => {
  if (typeof localStorage === 'undefined') return
  localStorage.removeItem('collectionTaskDraft')
  localStorage.removeItem('collectionTaskDraftFields')
  localStorage.removeItem('collectionTaskDraftMaterials')
  localStorage.removeItem('collectionTaskDraftRuleConfig')
}

const handleCancel = () => {
  showCancelDialog.value = true
}

const confirmCancel = () => {
  autoSaveDraft()
  navigateTo('/workbench')
}

const loadDraftFromStorage = () => {
  if (typeof localStorage === 'undefined') return

  try {
    const draftStr = localStorage.getItem('collectionTaskDraft')
    const fieldsStr = localStorage.getItem('collectionTaskDraftFields')
    const materialsStr = localStorage.getItem('collectionTaskDraftMaterials')
    const ruleConfigStr = localStorage.getItem('collectionTaskDraftRuleConfig')

    if (draftStr) {
      const draft = JSON.parse(draftStr)
      formData.name = draft.name || ''
      formData.description = draft.description || ''
      formData.dueAt = draft.dueAt || ''
      formData.submitTargetDescription = draft.submitTargetDescription || ''
      currentStep.value = draft.stepIndex || 1
    }

    if (fieldsStr) {
      formData.fields = JSON.parse(fieldsStr)
    }

    if (materialsStr) {
      formData.materials = JSON.parse(materialsStr)
    }

    if (ruleConfigStr) {
      formData.ruleConfig = JSON.parse(ruleConfigStr)
    }
  } catch (error) {
    console.error('Load draft error:', error)
  }
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true

    const params = new URLSearchParams(window.location.search)
    const templateId = params.get('templateId')

    if (templateId) {
      const template = TemplateService.getById(templateId)
      if (template) {
        formData.name = `${template.name} - ${new Date().toLocaleDateString()}`
        formData.description = template.description
      }
    }

    loadDraftFromStorage()
  })
})

watch(
  () => [formData.name, formData.description, formData.dueAt, formData.submitTargetDescription],
  () => {
    autoSaveDraft()
  },
  { deep: true }
)
</script>

<template>
  <div class="flex flex-col gap-6 lg:gap-8 max-w-4xl mx-auto" v-if="isClient">
    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-page-title mb-2">创建收集任务</h1>
        <p class="text-caption">通过简单的步骤配置任务，快速启动文件收集流程</p>
      </div>
      <Button
        variant="ghost"
        size="icon"
        class="text-muted-foreground hover:text-foreground"
        @click="handleCancel"
      >
        <SafeIcon name="X" :size="24" />
      </Button>
    </div>

    <!-- Step Indicator -->
    <StepIndicator :current-step="currentStep" :steps="steps" />

    <!-- Form Content -->
    <div class="surface-base card-padding min-h-[400px]">
      <!-- Step 1: Basic Info -->
      <BasicInfoStep
        v-if="currentStep === 1"
        v-model:name="formData.name"
        v-model:description="formData.description"
        v-model:dueAt="formData.dueAt"
        v-model:submitTargetDescription="formData.submitTargetDescription"
      />

      <!-- Step 2: Submitter Fields -->
      <SubmitterFieldsStep
        v-if="currentStep === 2"
        v-model:fields="formData.fields"
      />

      <!-- Step 3: Materials -->
      <MaterialsStep
        v-if="currentStep === 3"
        v-model:materials="formData.materials"
      />

      <!-- Step 4: Rules -->
      <RulesStep
        v-if="currentStep === 4"
        v-model:ruleConfig="formData.ruleConfig"
      />

      <!-- Step 5: Preview -->
      <PreviewStep
        v-if="currentStep === 5"
        :form-data="formData"
      />
    </div>

    <!-- Navigation Buttons -->
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <Button
          variant="outline"
          class="w-full sm:w-auto"
          @click="handlePreviousStep"
          :disabled="currentStep === 1"
        >
          <SafeIcon name="ChevronLeft" :size="18" class="mr-2" />
          上一步
        </Button>
      </div>

      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
        <AlertDialog v-model:open="showCancelDialog">
          <AlertDialogTrigger as-child>
            <Button variant="outline" class="w-full sm:w-auto">取消</Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>确定要放弃创建吗？</AlertDialogTitle>
              <AlertDialogDescription>
                已填内容将保存为草稿，您可以稍后继续编辑。
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>继续编辑</AlertDialogCancel>
              <AlertDialogAction @click="confirmCancel" class="bg-destructive hover:bg-destructive/90">
                放弃
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>

        <Button
          v-if="currentStep < totalSteps"
          class="w-full sm:w-auto"
          @click="handleNextStep"
          :disabled="!canProceedToNext"
        >
          下一步
          <SafeIcon name="ChevronRight" :size="18" class="ml-2" />
        </Button>

        <Button
          v-if="currentStep === totalSteps"
          class="w-full sm:w-auto bg-[hsl(var(--success))] hover:bg-[hsl(var(--success))] text-white"
          @click="handleCreateTask"
          :disabled="!canProceedToNext || isCreating"
        >
          <SafeIcon
            v-if="isCreating"
            name="Loader2"
            :size="18"
            class="mr-2 animate-spin"
          />
          <span v-else class="mr-2">✓</span>
          创建任务
        </Button>
      </div>
    </div>
  </div>
</template>
