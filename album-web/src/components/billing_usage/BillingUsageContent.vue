
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'
import SafeIcon from '@/components/common/SafeIcon.vue'
import StorageCard from '@/components/billing_usage/StorageCard.vue'
import TrafficCard from '@/components/billing_usage/TrafficCard.vue'
import PlanCard from '@/components/billing_usage/PlanCard.vue'
import OrderHistoryTable from '@/components/billing_usage/OrderHistoryTable.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'
import { unwrapList } from '@/lib/jfyuntu-mappers'
import type { PlanPackageData } from '@/data/PlanPackageData'
import type { OrderData } from '@/data/OrderData'

const storageUsage = ref({
  planName: '',
  totalCapacityMb: 50,
  usedCapacityMb: 0,
  monthlyTrafficGb: 0,
  usedTrafficGb: 0,
  concurrentRights: 0,
  status: 'normal' as 'normal' | 'warning' | 'insufficient',
})
const plans = ref<PlanPackageData[]>([])
const orders = ref<OrderData[]>([])
const isLoading = ref(false)

onMounted(() => {
  loadBilling()
})

const parseSpaceToMb = (value: any) => {
  const text = String(value || '').trim().toUpperCase()
  const number = parseFloat(text) || 0
  if (text.includes('T')) return number * 1024 * 1024
  if (text.includes('G')) return number * 1024
  return number
}

const mapPlan = (item: any): PlanPackageData => ({
  id: String(item.id || item.plan_id || item.grade || ''),
  name: item.name || item.title || item.grade_name || '资源包',
  capacityMb: Number(item.capacity_mb || item.space_size || item.capacity || 0) || parseSpaceToMb(item.space_text || item.space),
  price: item.price ? `¥${item.price}` : item.price_text || item.amount_text || '¥0',
  concurrentRights: Number(item.concurrent_rights || item.concurrent || item.concurrent_limit || 0),
  trafficGb: Number(item.traffic_gb || item.flow_gb || item.traffic || 0),
  durationLabel: item.duration_label || item.buy_time_text || item.period || '长期',
  isRecommended: Number(item.is_recommend || item.recommended || 0) === 1,
  createdAt: item.create_time || '',
})

const mapOrder = (item: any): OrderData => ({
  id: String(item.id || item.order_id || item.order_no || ''),
  packageId: String(item.plan_id || item.membership_plan_id || ''),
  orderNo: item.order_no || item.order_id || '',
  amount: String(item.amount || item.pay_price || item.price || '0.00'),
  status: Number(item.status) === 1 || item.status === 'success' || item.pay_status === 'paid' ? 'success' : Number(item.status) === -1 || item.status === 'failed' ? 'failed' : 'pending',
  createdAt: item.create_time || item.created_at || '',
  updatedAt: item.pay_time || item.update_time || item.updated_at || '',
})

const loadBilling = async () => {
  isLoading.value = true
  try {
    const [profile, planRaw, orderRaw] = await Promise.all([
      pcApi.getCurrentUser(),
      pcApi.getSubscriptionPlans().catch(() => []),
      pcApi.getPaymentOrders().catch(() => ({ data: [] })),
    ])
    const totalMb = parseSpaceToMb(profile?.all_space)
    const usedMb = Number(profile?.use_space || 0) / 1024 / 1024
    const percent = Number(profile?.space_used || 0)
    storageUsage.value = {
      planName: profile?.grade_name || '',
      totalCapacityMb: totalMb || 50,
      usedCapacityMb: usedMb,
      monthlyTrafficGb: Number(profile?.traffic_gb || 0),
      usedTrafficGb: Number(profile?.used_traffic_gb || 0),
      concurrentRights: Number(profile?.concurrent_rights || 0),
      status: percent >= 95 ? 'insufficient' : percent >= 80 ? 'warning' : 'normal',
    }
    plans.value = unwrapList(planRaw).map(mapPlan)
    orders.value = unwrapList(orderRaw).map(mapOrder)
  } catch (error: any) {
    toast.error(error?.message || '套餐信息加载失败')
  } finally {
    isLoading.value = false
  }
}

const usagePercent = computed(() => {
  if (!storageUsage.value) return 0
  const total = Number(storageUsage.value.totalCapacityMb || 0)
  if (total <= 0) return 0
  return Math.min(100, Math.max(0, Math.round((Number(storageUsage.value.usedCapacityMb || 0) / total) * 100)))
})

const trafficPercent = computed(() => {
  if (!storageUsage.value) return 0
  const total = Number(storageUsage.value.monthlyTrafficGb || 0)
  if (total <= 0) return 0
  return Math.min(100, Math.max(0, Math.round((Number(storageUsage.value.usedTrafficGb || 0) / total) * 100)))
})

const isCapacityWarning = computed(() => usagePercent.value > 80)

const handleUpgrade = async (planId: string) => {
  const plan = plans.value.find(p => p.id === planId)
  if (!plan) return

  try {
    const order = await pcApi.createMembershipOrder({ membership_plan_id: plan.id, plan_id: plan.id })
    toast.success(order?.pay_url ? '订单已创建，请完成支付' : '订单已创建')
    if (order?.pay_url) window.open(order.pay_url, '_blank')
    await loadBilling()
  } catch (error: any) {
    toast.error(error?.message || '下单失败')
  }
}

const handleBackToWorkbench = () => {
  window.location.href = './management-workbench.html'
}
</script>

<template>
  <div class="page-body space-y-6">
    <!-- 页面标题 -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-page-title">容量与套餐</h1>
        <p class="text-caption mt-2">管理您的云存储空间、流量配额和订阅套餐</p>
      </div>
      <Button variant="outline" @click="handleBackToWorkbench">
        <SafeIcon name="ArrowLeft" :size="16" class="mr-2" />
        返回工作台
      </Button>
    </div>

    <!-- 容量警告条 -->
    <Alert v-if="isCapacityWarning" class="capacity-warning border-warning/30 bg-warning/5">
      <SafeIcon name="AlertTriangle" :size="16" class="mr-3 text-warning" />
      <div class="flex-1">
        <AlertTitle class="text-warning font-semibold">存储空间即将满满</AlertTitle>
        <AlertDescription class="text-warning/80 text-sm mt-1">
          您已使用 {{ usagePercent }}% 的存储空间，建议立即升级以获得更多容量
        </AlertDescription>
      </div>
      <Button 
        size="sm" 
        class="ml-4 bg-warning text-warning-foreground hover:bg-warning/90"
        @click="() => {}"
      >
        立即升级
      </Button>
    </Alert>

    <!-- 统计卡片区 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <StorageCard 
        v-if="storageUsage"
        :used="storageUsage.usedCapacityMb"
        :total="storageUsage.totalCapacityMb"
        :percent="usagePercent"
      />
      <TrafficCard 
        v-if="storageUsage"
        :used="storageUsage.usedTrafficGb"
        :total="storageUsage.monthlyTrafficGb"
        :percent="trafficPercent"
      />
    </div>

    <!-- 套餐与订单 Tab -->
    <Tabs default-value="plans" class="w-full">
      <TabsList class="grid w-full max-w-xs grid-cols-2 bg-muted/50 p-1 rounded-lg">
        <TabsTrigger value="plans" class="rounded-md">
          <SafeIcon name="Package" :size="16" class="mr-2" />
          可用套餐
        </TabsTrigger>
        <TabsTrigger value="orders" class="rounded-md">
          <SafeIcon name="History" :size="16" class="mr-2" />
          订单历史
        </TabsTrigger>
      </TabsList>

      <!-- 套餐列表 Tab -->
      <TabsContent value="plans" class="mt-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <PlanCard 
            v-for="plan in plans"
            :key="plan.id"
            :plan="plan"
            :is-current="storageUsage?.planName === plan.name"
            @upgrade="handleUpgrade(plan.id)"
          />
        </div>
      </TabsContent>

      <!-- 订单历史 Tab -->
      <TabsContent value="orders" class="mt-6">
        <Card class="surface-base">
          <CardHeader class="border-b border-border">
            <CardTitle class="text-lg">订单历史</CardTitle>
            <CardDescription>查看您的所有升级订单和支付记录</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <OrderHistoryTable :orders="orders" />
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <!-- 底部说明 -->
    <Card class="surface-raised border-border/50 bg-muted/30">
      <CardContent class="pt-6">
        <div class="space-y-3 text-sm text-muted-foreground">
          <div class="flex gap-3">
            <SafeIcon name="Info" :size="16" class="mt-0.5 shrink-0 text-primary" />
            <div>
              <p class="font-medium text-foreground mb-1">关于存储空间</p>
              <p>存储空间用于保存您上传的所有图片、视频等媒体文件。超出限额后将无法继续上传新文件。</p>
            </div>
          </div>
          <div class="flex gap-3">
            <SafeIcon name="Info" :size="16" class="mt-0.5 shrink-0 text-primary" />
            <div>
              <p class="font-medium text-foreground mb-1">关于月度流量</p>
              <p>流量指访客浏览、预览和下载您的产品时产生的数据传输。每月自动重置，不可累计。</p>
            </div>
          </div>
          <div class="flex gap-3">
            <SafeIcon name="Info" :size="16" class="mt-0.5 shrink-0 text-primary" />
            <div>
              <p class="font-medium text-foreground mb-1">关于并发权益</p>
              <p>并发数指同时在线访问您主页的用户数上限。超出限制后新用户将无法访问。</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
