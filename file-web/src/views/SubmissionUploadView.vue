<script setup lang="ts">
import { computed } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import SubmissionUploadPageContent from '@/components/submission_upload_page/SubmissionUploadPageContent.vue'
import { PublicSubmissionService } from '@/data/PublicSubmissionService'
import {
  CollectionTaskService,
  taskFieldConfigDataList,
  taskMaterialItemDataList,
} from '@/data/CollectionTaskService'
import type { PublicSubmissionTaskVO } from '@/data/PublicSubmissionData'
import { currentRouteState } from '@/navigation'

const taskId = computed(() => String(currentRouteState.value.query.taskId || 'task-001'))
const taskVOData = computed<PublicSubmissionTaskVO>(() => {
  return PublicSubmissionService.getTaskVOById(taskId.value) || {
    id: 'public-task-001',
    taskId: taskId.value,
    taskName: '示例收集任务',
    organizationName: '织序传输助手',
    description: '请完成材料上传',
    dueAt: '2026-07-09T12:00:00Z',
    accessCodeRequired: false,
    submitterFields: [],
    materials: [],
    status: 'active',
  }
})

const taskData = computed(() => CollectionTaskService.getById(taskId.value))
const fieldConfigs = computed(() => {
  return taskData.value
    ? (taskData.value.submitterFieldIds || [])
        .map((id) => taskFieldConfigDataList.find((field) => field.id === id))
        .filter(Boolean)
    : []
})
const materialConfigs = computed(() => {
  return taskData.value
    ? (taskData.value.materialItemIds || [])
        .map((id) => taskMaterialItemDataList.find((material) => material.id === id))
        .filter(Boolean)
    : []
})
</script>

<template>
  <PublicLayout>
    <SubmissionUploadPageContent
      :task-v-o-data="taskVOData"
      :field-configs="fieldConfigs"
      :material-configs="materialConfigs"
    />
  </PublicLayout>
</template>
