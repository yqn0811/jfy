<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import StandardLayout from '@/layouts/StandardLayout.vue'
import TaskDetailsContent from '@/components/task-details/TaskDetailsContent.vue'
import { CollectionTaskService } from '@/data/CollectionTaskService'
import { FileTransferApi } from '@/data/FileTransferApi'
import { SubmissionService } from '@/data/SubmissionService'
import { currentRouteState } from '@/navigation'
import type { CollectionTaskData } from '@/data/CollectionTaskData'

const taskId = computed(() => {
  const queryTaskId = currentRouteState.value.query.taskId
  return String(queryTaskId || CollectionTaskService.getAll()[0]?.id || 'task-001')
})

const task = ref<CollectionTaskData | undefined>(CollectionTaskService.getById(taskId.value))
const submissions = computed(() => SubmissionService.query({ filter: { collectionTaskId: taskId.value } }))

watch(
  taskId,
  async (id) => {
    task.value = CollectionTaskService.getById(id)
    if (!id || id.startsWith('task-')) return

    try {
      const detail = await FileTransferApi.getCollectionTask(id)
      task.value = detail.task
    } catch {
      task.value = CollectionTaskService.getById(id)
    }
  },
  { immediate: true }
)
</script>

<template>
  <StandardLayout>
    <div class="page-body">
      <div class="page-container">
        <TaskDetailsContent :task="task" :submissions="submissions" :task-id="taskId" />
      </div>
    </div>
  </StandardLayout>
</template>
