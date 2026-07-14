<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import StandardLayout from '@/layouts/StandardLayout.vue'
import TaskDetailsContent from '@/components/task-details/TaskDetailsContent.vue'
import { CollectionTaskService, taskRuleConfigDataList } from '@/data/CollectionTaskService'
import { FileTransferApi } from '@/data/FileTransferApi'
import { SubmissionService } from '@/data/SubmissionService'
import { currentRouteState } from '@/navigation'
import type { CollectionTaskData } from '@/data/CollectionTaskData'
import type { SubmissionData } from '@/data/SubmissionData'
import { ApiError, authStore, getApiErrorMessage } from '@/lib/apiClient'

const taskId = computed(() => {
  const queryTaskId = currentRouteState.value.query.taskId
  return queryTaskId ? String(queryTaskId) : ''
})
const ownerActionsEnabled = computed(() => authStore.hasToken())

const getLocalRuleConfig = (ruleConfigId: string) => {
  return taskRuleConfigDataList.find((item) => item.id === ruleConfigId)
}

const getLocalTaskDetail = (id: string) => {
  const localTask = CollectionTaskService.getById(id)
  if (!localTask) return null
  return {
    task: localTask,
    ruleConfig: getLocalRuleConfig(localTask.ruleConfigId),
  }
}

const isAuthExpiredError = (error: unknown) => {
  return error instanceof ApiError && (error.code === 4001 || error.code === 4100)
}

const task = ref<CollectionTaskData | undefined>(undefined)
const ruleConfig = ref(getLocalRuleConfig(task.value?.ruleConfigId || ''))
const submissions = ref<SubmissionData[]>([])
const submissionsError = ref('')
const isSubmissionsLoading = ref(false)
let loadToken = 0

const loadTaskDetails = async (id: string) => {
  const currentLoadToken = ++loadToken
  task.value = undefined
  ruleConfig.value = undefined
  submissions.value = []
  submissionsError.value = ''
  if (!id) {
    submissionsError.value = '缺少任务 ID，请从工作台或收发记录进入任务详情。'
    return
  }

  const localDetail = getLocalTaskDetail(id)
  if (!authStore.hasToken() && localDetail) {
    task.value = localDetail.task
    ruleConfig.value = localDetail.ruleConfig
    submissionsError.value = '当前是未登录创建的收集任务，只能复制收集链接和预览提交页；登录后可查看提交记录。'
    return
  }

  try {
    isSubmissionsLoading.value = true
    const [detail, submissionResult] = await Promise.all([
      FileTransferApi.getCollectionTask(id),
      SubmissionService.listRemote({ taskId: id, limit: 100 }),
    ])
    if (currentLoadToken !== loadToken) return
    task.value = detail.task
    ruleConfig.value = detail.ruleConfig
    submissions.value = submissionResult.items
  } catch (error) {
    if (currentLoadToken !== loadToken) return
    if (localDetail && (isAuthExpiredError(error) || localDetail.task.ownerId === '0' || localDetail.task.ownerId === '')) {
      if (isAuthExpiredError(error)) {
        authStore.clearToken()
      }
      task.value = localDetail.task
      ruleConfig.value = localDetail.ruleConfig
      submissions.value = []
      submissionsError.value = '当前任务可继续使用收集链接；登录后可查看提交记录和批量下载。'
      return
    }
    task.value = undefined
    ruleConfig.value = undefined
    submissions.value = []
    submissionsError.value = getApiErrorMessage(error, '任务详情加载失败')
  } finally {
    if (currentLoadToken === loadToken) {
      isSubmissionsLoading.value = false
    }
  }
}

watch(
  taskId,
  (id) => {
    void loadTaskDetails(id)
  },
  { immediate: true }
)
</script>

<template>
  <StandardLayout>
    <div class="page-body">
      <div class="app-shell">
        <TaskDetailsContent
          :task="task"
          :rule-config="ruleConfig"
          :submissions="submissions"
          :task-id="taskId"
          :is-submissions-loading="isSubmissionsLoading"
          :submissions-error="submissionsError"
          :owner-actions-enabled="ownerActionsEnabled"
          @refresh-submissions="loadTaskDetails(taskId)"
        />
      </div>
    </div>
  </StandardLayout>
</template>
