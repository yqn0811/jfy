
<script setup lang="ts">
import { computed } from 'vue'
import type { SpaceOverview } from '@/data/SpaceService'
import { Progress } from '@/components/ui/progress'
import SafeIcon from '@/components/common/SafeIcon.vue'

const props = defineProps<{
  overview: SpaceOverview
}>()

const storagePercentage = computed(() => {
  return Math.round(props.overview.storageUsageRate * 100)
})

const remainingStorage = computed(() => {
  return (props.overview.storageLimitGb - props.overview.storageUsedGb).toFixed(1)
})

const storageStatusColor = computed(() => {
  const rate = props.overview.storageUsageRate
  if (rate >= 0.9) return 'text-destructive'
  if (rate >= 0.7) return 'text-warning'
  return 'text-[hsl(var(--success))]'
})
</script>

<template>
  <div class="surface-raised card-padding">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- 空间名称 -->
      <div class="flex flex-col gap-2">
        <span class="text-caption font-medium text-muted-foreground">空间名称</span>
        <h3 class="text-item-title text-foreground truncate">{{ overview.name }}</h3>
      </div>

      <!-- 存储用量 -->
      <div class="flex flex-col gap-3">
        <span class="text-caption font-medium text-muted-foreground">存储用量</span>
        <div class="space-y-1">
          <div class="flex items-baseline justify-between">
            <span :class="['text-lg font-semibold', storageStatusColor]">
              {{ storagePercentage }}%
            </span>
            <span class="text-xs text-muted-foreground">
              {{ overview.storageUsedGb.toFixed(1) }} / {{ overview.storageLimitGb }} GB
            </span>
          </div>
          <Progress :model-value="storagePercentage" class="h-2" />
        </div>
      </div>

      <!-- 剩余空间 -->
      <div class="flex flex-col gap-2">
        <span class="text-caption font-medium text-muted-foreground">剩余空间</span>
        <p class="text-lg font-semibold text-foreground">
          {{ remainingStorage }} GB
        </p>
      </div>

      <!-- 成员数 -->
      <div class="flex flex-col gap-2">
        <span class="text-caption font-medium text-muted-foreground">团队成员</span>
        <div class="flex items-center gap-2">
          <span class="text-lg font-semibold text-foreground">{{ overview.memberCount }}</span>
          <span class="text-xs text-muted-foreground">人</span>
        </div>
      </div>
    </div>

    <!-- 归档规则说明 -->
    <div class="mt-4 pt-4 border-t border-border/50">
      <div class="flex items-start gap-2">
        <SafeIcon name="Info" :size="16" class="text-muted-foreground mt-0.5 shrink-0" />
        <p class="text-xs text-muted-foreground leading-relaxed">
          {{ overview.archiveRule }}
        </p>
      </div>
    </div>
  </div>
</template>
