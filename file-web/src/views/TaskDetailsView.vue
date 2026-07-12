<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import StandardLayout from '@/layouts/StandardLayout.vue'
import TaskDetailsContent from '@/components/task-details/TaskDetailsContent.vue'
import { taskRuleConfigDataList } from '@/data/CollectionTaskService'
import { FileTransferApi } from '@/data/FileTransferApi'
import { SubmissionService } from '@/data/SubmissionService'
import { currentRouteState } from '@/navigation'
import type { CollectionTaskData } from '@/data/CollectionTaskData'
import type { SubmissionData } from '@/data/SubmissionData'
import { getApiErrorMessage } from '@/lib/apiClient'

const taskId = computed(() => {
  const queryTaskId = currentRouteState.value.query.taskId
  return queryTaskId ? String(queryTaskId) : ''
})

const getLocalRuleConfig = (ruleConfigId: string) => {
  return taskRuleConfigDataList.find((item) => item.id === ruleConfigId)
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
    submissionsError.value = '缺少任务 ID，请从工作台或交付记录进入任务详情。'
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
          @refresh-submissions="loadTaskDetails(taskId)"
        />
      </div>
    </div>
  </StandardLayout>
</template>
