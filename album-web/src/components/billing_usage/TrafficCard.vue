
<script setup lang="ts">
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  used: number
  total: number
  percent: number
}

defineProps<Props>()

const formatTraffic = (gb: number): string => {
  if (gb >= 0.1) return `${gb.toFixed(1)} GB`
  const mb = gb * 1024
  if (mb > 0) return `${mb.toFixed(1)} MB`
  return '0 MB'
}
</script>

<template>
  <Card class="surface-raised">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <CardTitle class="text-base font-semibold flex items-center gap-2">
          <div class="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center">
            <SafeIcon name="Zap" :size="20" class="text-accent" />
          </div>
          月度流量
        </CardTitle>
        <span class="text-sm font-bold text-accent">{{ percent }}%</span>
      </div>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-2">
        <Progress :model-value="percent" class="h-2" />
        <div class="flex justify-between text-xs text-muted-foreground">
          <span>已使用 {{ formatTraffic(used) }}</span>
          <span>月度限额 {{ total }} GB</span>
        </div>
      </div>
      <div class="pt-2 border-t border-border">
        <p class="text-xs text-muted-foreground">
          <span v-if="percent < 80" class="text-success">✓ 流量充足</span>
          <span v-else-if="percent < 95" class="text-warning">⚠ 流量紧张</span>
          <span v-else class="text-destructive">✕ 流量不足</span>
        </p>
      </div>
    </CardContent>
  </Card>
</template>
