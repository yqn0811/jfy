<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import SubmissionUploadPageContent from '@/components/submission_upload_page/SubmissionUploadPageContent.vue'
import { PublicSubmissionService } from '@/data/PublicSubmissionService'
import type { PublicSubmissionTaskVO } from '@/data/PublicSubmissionData'
import type { TaskFieldConfigData, TaskMaterialItemData } from '@/data/CollectionTaskData'
import { currentRouteState } from '@/navigation'
import { getApiErrorMessage } from '@/lib/apiClient'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'

const taskId = computed(() => String(currentRouteState.value.query.taskId || ''))
const sourceSubmissionId = computed(() => String(currentRouteState.value.query.sourceSubmissionId || currentRouteState.value.query.source_submission_id || ''))
const remoteTask = ref<PublicSubmissionTaskVO | null>(null)
const loadError = ref('')
const isLoading = ref(true)

const taskVOData = computed(() => remoteTask.value)
const fieldConfigs = computed(() => {
  const fields = taskVOData.value?.submitterFields || []
  if (fields.length > 0 && typeof fields[0] === 'object') {
    return fields as TaskFieldConfigData[]
  }
  return []
})
const materialConfigs = computed(() => {
  const materials = taskVOData.value?.materials || []
  if (materials.length > 0 && typeof materials[0] === 'object') {
    return materials as TaskMaterialItemData[]
  }
  return []
})

const loadTask = async () => {
  isLoading.value = true
  loadError.value = ''
  remoteTask.value = null
  if (!taskId.value) {
    loadError.value = '缺少任务 ID，请使用发起方提供的提交链接。'
    isLoading.value = false
    return
  }
  try {
    remoteTask.value = await PublicSubmissionService.getPublicTask(taskId.value, String(currentRouteState.value.query.accessCode || ''))
  } catch (error) {
    loadError.value = getApiErrorMessage(error, '任务加载失败')
    remoteTask.value = null
  } finally {
    isLoading.value = false
  }
}

onMounted(loadTask)

const handleTaskVerified = (task: PublicSubmissionTaskVO) => {
  remoteTask.value = task
}
</script>

<template>
  <PublicLayout>
    <SubmissionUploadPageContent
      v-if="taskVOData"
      :task-v-o-data="taskVOData"
      :field-configs="fieldConfigs"
      :material-configs="materialConfigs"
      :is-loading="isLoading"
      :load-error="loadError"
      :source-submission-id="sourceSubmissionId"
      @task-verified="handleTaskVerified"
    />
    <div v-else class="app-content-narrow py-16">
      <div class="surface-base card-padding text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted/60">
          <SafeIcon :name="isLoading ? 'Loader2' : 'FileQuestion'" :size="28" :class="isLoading ? 'animate-spin text-muted-foreground' : 'text-muted-foreground'" />
        </div>
        <h1 class="text-section-title mb-2">{{ isLoading ? '正在加载任务' : '未找到收集任务' }}</h1>
        <p class="text-caption mb-5">{{ isLoading ? '请稍候...' : loadError || '请确认提交链接是否正确。' }}</p>
        <Button v-if="!isLoading" variant="outline" @click="loadTask">
          <SafeIcon name="RefreshCw" :size="16" class="mr-2" />
          重新加载
        </Button>
      </div>
    </div>
  </PublicLayout>
</template>
