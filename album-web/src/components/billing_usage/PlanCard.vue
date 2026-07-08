
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { PlanPackageData } from '@/data/PlanPackageData'

interface Props {
  plan: PlanPackageData
  isCurrent?: boolean
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCurrent: false,
  isLoading: false,
})

const emit = defineEmits<{
  (e: 'upgrade'): void
}>()

const formatCapacity = (mb: number): string => {
  return (mb / 1024).toFixed(0) + ' GB'
}
</script>

<template>
  <Card :class="['surface-raised relative overflow-hidden', props.isCurrent && 'ring-2 ring-primary']">
    <!-- 推荐标签 -->
    <div v-if="plan.isRecommended" class="absolute top-0 right-0">
      <Badge class="rounded-none rounded-bl-lg bg-accent text-accent-foreground border-0">
        <SafeIcon name="Star" :size="12" class="mr-1" />
        推荐
      </Badge>
    </div>

    <CardHeader class="pb-3">
      <div class="flex items-start justify-between">
        <div>
          <CardTitle class="text-lg">{{ plan.name }}</CardTitle>
          <CardDescription class="text-xs mt-1">{{ plan.durationLabel }}</CardDescription>
        </div>
        <div v-if="isCurrent" class="px-2 py-1 bg-primary/10 rounded text-xs font-semibold text-primary">
          当前套餐
        </div>
      </div>
    </CardHeader>

    <CardContent class="space-y-4">
      <!-- 价格 -->
      <div class="space-y-1">
        <p class="text-3xl font-bold text-primary">{{ plan.price }}</p>
        <p class="text-xs text-muted-foreground">包含以下权益</p>
      </div>

      <!-- 权益列表 -->
      <div class="space-y-2 py-3 border-y border-border">
        <div class="flex items-center gap-2 text-sm">
          <SafeIcon name="Check" :size="16" class="text-success shrink-0" />
          <span>存储空间：{{ formatCapacity(plan.capacityMb) }}</span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <SafeIcon name="Check" :size="16" class="text-success shrink-0" />
          <span>月度流量：{{ plan.trafficGb }} GB</span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <SafeIcon name="Check" :size="16" class="text-success shrink-0" />
          <span>并发权益：{{ plan.concurrentRights }} 人</span>
        </div>
      </div>

      <!-- 操作按钮 -->
      <Button 
        v-if="!isCurrent"
        class="w-full" 
        :disabled="props.isLoading"
        @click="emit('upgrade')"
      >
        <SafeIcon :name="props.isLoading ? 'Loader2' : 'ShoppingCart'" :size="16" :class="['mr-2', props.isLoading && 'animate-spin']" />
        {{ props.isLoading ? '正在创建订单' : '立即升级' }}
      </Button>
      <Button 
        v-else
        variant="outline" 
        class="w-full" 
        disabled
      >
        <SafeIcon name="Check" :size="16" class="mr-2" />
        已激活
      </Button>
    </CardContent>
  </Card>
</template>
