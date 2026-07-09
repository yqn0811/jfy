
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { TaskService } from '@/data/TaskService'
import { WorkspaceService } from '@/data/WorkspaceService'
import type { TaskData } from '@/data/TaskData'
import ActionCard from '@/components/workbench/ActionCard.vue'
import RecentTasksTable from '@/components/workbench/RecentTasksTable.vue'
import TodoSidebar from '@/components/workbench/TodoSidebar.vue'
import { Button } from '@/components/ui/button'
import { navigateTo } from '@/navigation'

const isClient = ref(true)

const tasks = ref<TaskData[]>(TaskService.getAll())
const workspaceOverview = ref(WorkspaceService.getOverview())

const searchKeyword = ref('')
const statusFilter = ref<string>('')

const filteredTasks = computed(() => {
  let result = tasks.value

  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter((task) => task.name.toLowerCase().includes(keyword))
  }

  if (statusFilter.value) {
    result = result.filter((task) => task.status === statusFilter.value)
  }

  return result.sort((a, b) => new Date(b.lastUpdatedAt).getTime() - new Date(a.lastUpdatedAt).getTime())
})

const hasNoTasks = computed(() => tasks.value.length === 0)

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })
})

const handleTaskDeleted = (taskId: string) => {
  tasks.value = tasks.value.filter((t) => t.id !== taskId)
}

const handleTaskArchived = (taskId: string) => {
  const task = tasks.value.find((t) => t.id === taskId)
  if (task) {
    task.status = 'archived'
  }
}
</script>

<template>
  <main class="page-body min-h-screen">
    <div class="page-container flex flex-col xl:flex-row gap-6">
      <!-- Main Content -->
      <div class="flex-1 min-w-0">
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
          <div>
            <h1 class="text-page-title mb-1">工作台</h1>
            <p class="text-caption">{{ workspaceOverview.teamName }}</p>
          </div>
        </div>

        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
          <ActionCard
            title="发送文件"
            description="快速生成分享链接，支持密码、有效期和下载限制"
            icon="Send"
            @click="navigateTo('/quick-send')"
          />
          <ActionCard
            title="创建收集任务"
            description="多人提交、自动整理、验收状态一目了然"
            icon="Download"
            @click="navigateTo('/create-collection-task')"
          />
        </div>
      </div>

      <!-- Recent Tasks Section -->
      <div class="mb-8">
        <h2 class="text-section-title mb-4">最近任务</h2>

        <!-- Empty State -->
        <div v-if="hasNoTasks && (isClient || !isClient)" class="surface-base card-padding text-center py-16">
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center">
              <svg
                class="w-8 h-8 text-muted-foreground"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <div>
              <h3 class="text-item-title mb-1">还没有文件任务</h3>
              <p class="text-caption mb-4">先创建一个收集任务或发送文件</p>
            </div>
            <div class="flex gap-2">
              <Button
                variant="outline"
                @click="navigateTo('/quick-send')"
              >
                发送文件
              </Button>
              <Button @click="navigateTo('/create-collection-task')">
                创建收集任务
              </Button>
            </div>
          </div>
        </div>

        <!-- Task Table -->
        <RecentTasksTable
          v-else
          :tasks="filteredTasks"
          :search-keyword="searchKeyword"
          :status-filter="statusFilter"
          @update:search-keyword="(v) => (searchKeyword = v)"
          @update:status-filter="(v) => (statusFilter = v)"
          @task-deleted="handleTaskDeleted"
          @task-archived="handleTaskArchived"
        />
      </div>
      </div>

      <!-- Right Sidebar -->
      <TodoSidebar :workspace-overview="workspaceOverview" />
    </div>
  </main>
</template>

<style scoped>
/* Ensure main content area scrolls independently */
main {
  @apply overflow-hidden;
}
</style>
