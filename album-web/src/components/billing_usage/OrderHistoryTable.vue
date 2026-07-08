
<script setup lang="ts">
import { computed } from 'vue'
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { OrderData } from '@/data/OrderData'

interface Props {
  orders: OrderData[]
}

const props = defineProps<Props>()

const statusConfig = {
  pending: {
    label: '待支付',
    icon: 'Clock',
    class: 'bg-warning/10 text-warning border-warning/30'
  },
  success: {
    label: '已完成',
    icon: 'Check',
    class: 'bg-success/10 text-success border-success/30'
  },
  failed: {
    label: '已失败',
    icon: 'X',
    class: 'bg-destructive/10 text-destructive border-destructive/30'
  }
}

const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const emptyState = computed(() => props.orders.length === 0)
</script>

<template>
  <div class="overflow-x-auto">
    <Table v-if="!emptyState" class="w-full">
      <TableHeader class="bg-muted/30 border-b border-border">
        <TableRow class="hover:bg-transparent">
          <TableHead class="w-32 font-semibold">订单号</TableHead>
          <TableHead class="w-24 font-semibold">金额</TableHead>
          <TableHead class="w-32 font-semibold">创建时间</TableHead>
          <TableHead class="w-32 font-semibold">完成时间</TableHead>
          <TableHead class="w-20 font-semibold">状态</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow 
          v-for="order in props.orders" 
          :key="order.id"
          class="border-b border-border hover:bg-muted/20 transition-colors"
        >
          <TableCell class="font-mono text-sm">{{ order.orderNo }}</TableCell>
          <TableCell class="font-semibold">¥{{ order.amount }}</TableCell>
          <TableCell class="text-sm text-muted-foreground">{{ formatDate(order.createdAt) }}</TableCell>
          <TableCell class="text-sm text-muted-foreground">
            {{ order.status === 'pending' ? '-' : formatDate(order.updatedAt) }}
          </TableCell>
          <TableCell>
            <Badge 
              variant="outline"
              :class="['flex items-center gap-1 w-fit', statusConfig[order.status].class]"
            >
              <SafeIcon :name="statusConfig[order.status].icon" :size="12" />
              {{ statusConfig[order.status].label }}
            </Badge>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <!-- 空状态 -->
    <div v-else class="py-12 text-center">
      <SafeIcon name="ShoppingCart" :size="48" class="mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground">暂无订单记录</p>
      <p class="text-xs text-muted-foreground/60 mt-1">升级套餐后订单将显示在这里</p>
    </div>
  </div>
</template>
