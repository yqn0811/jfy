
<script setup lang="ts">
import { computed } from 'vue'
import type { WorkspaceOverview } from '@/data/WorkspaceService'
import StatCard from '@/components/common/StatCard.vue'
import { Progress } from '@/components/ui/progress'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'

interface Props {
  workspaceOverview: WorkspaceOverview
}

const props = defineProps<Props>()

const storagePercentage = computed(() => Math.round(props.workspaceOverview.storageUsageRate * 100))

const storageVariant = computed(() => {
  const rate = props.workspaceOverview.storageUsageRate
  if (rate > 0.9) return 'danger'
  if (rate > 0.7) return 'warning'
  return 'default'
})
</script>

<template>
  <aside class="w-full xl:w-80 flex flex-col gap-4 xl:gap-6 xl:py-6 xl:pr-6 xl:border-l border-border">
    <div class="grid grid-cols-1 sm:grid-cols-3 xl:grid-cols-1 gap-4 xl:gap-6">
    <!-- Today Pending -->
    <StatCard
      label="今日待处理"
      :value="workspaceOverview.todayPendingCount"
      icon="Clock"
      variant="default"
    />

    <!-- Expiring Soon -->
    <StatCard
      label="即将过期"
      :value="workspaceOverview.expiringSoonCount"
      icon="AlertCircle"
      variant="warning"
    />

    <!-- Need Resubmit -->
    <StatCard
      label="需补交"
      :value="workspaceOverview.needResubmitCount"
      icon="RefreshCw"
      variant="warning"
    />
    </div>

    <!-- Storage Usage -->
    <div class="surface-base card-padding">
      <div class="flex items-center justify-between mb-3">
        <span class="text-caption font-medium">存储用量</span>
        <span class="text-item-title font-bold">
          {{ storagePercentage }}%
        </span>
      </div>

      <Progress :model-value="storagePercentage" class="mb-3 h-2" />

      <div class="text-caption text-muted-foreground space-y-1">
        <div class="flex justify-between">
          <span>已用</span>
          <span class="font-medium">{{ workspaceOverview.storageUsedGb.toFixed(1) }}GB</span>
        </div>
        <div class="flex justify-between">
          <span>总容量</span>
          <span class="font-medium">{{ workspaceOverview.storageLimitGb.toFixed(1) }}GB</span>
        </div>
      </div>

      <div
        v-if="storagePercentage > 90"
        class="mt-4 p-3 rounded-lg bg-destructive/10 border border-destructive/20 flex items-start gap-2"
      >
        <SafeIcon name="AlertTriangle" :size="16" class="text-destructive shrink-0 mt-0.5" />
        <p class="text-xs text-destructive leading-snug">存储空间即将满满，请及时清理或升级</p>
      </div>
    </div>
  </aside>
</template>
