
<script setup lang="ts">
import { computed } from 'vue'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  used: number
  total: number
  percent: number
}

defineProps<Props>()

const formatSize = (mb: number): string => {
  if (mb >= 1024) {
    return (mb / 1024).toFixed(1) + ' GB'
  }
  return mb.toFixed(1) + ' MB'
}
</script>

<template>
  <Card class="surface-raised">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <CardTitle class="text-base font-semibold flex items-center gap-2">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
            <SafeIcon name="HardDrive" :size="20" class="text-primary" />
          </div>
          存储空间
        </CardTitle>
        <span class="text-sm font-bold text-primary">{{ percent }}%</span>
      </div>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-2">
        <Progress :model-value="percent" class="h-2" />
        <div class="flex justify-between text-xs text-muted-foreground">
          <span>已使用 {{ formatSize(used) }}</span>
          <span>总容量 {{ formatSize(total) }}</span>
        </div>
      </div>
      <div class="pt-2 border-t border-border">
        <p class="text-xs text-muted-foreground">
          <span v-if="percent < 80" class="text-success">✓ 容量充足</span>
          <span v-else-if="percent < 95" class="text-warning">⚠ 容量紧张</span>
          <span v-else class="text-destructive">✕ 容量不足</span>
        </p>
      </div>
    </CardContent>
  </Card>
</template>
