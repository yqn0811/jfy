
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import StorageCard from '@/components/billing_usage/StorageCard.vue'
import TrafficCard from '@/components/billing_usage/TrafficCard.vue'
import PlanCard from '@/components/billing_usage/PlanCard.vue'
import OrderHistoryTable from '@/components/billing_usage/OrderHistoryTable.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'
import { unwrapList } from '@/lib/jfyuntu-mappers'
import { navigateTo } from '@/navigation'
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
const isCreatingOrder = ref(false)
const paymentDialogOpen = ref(false)
const activePayment = ref<any>(null)
let paymentTimer: number | null = null

onMounted(() => {
  loadBilling()
})

onBeforeUnmount(() => {
  stopPaymentPolling()
})

const parseSpaceToMb = (value: any) => {
  const text = String(value || '').trim().toUpperCase()
  const number = parseFloat(text) || 0
  if (text.includes('T')) return number * 1024 * 1024
  if (text.includes('G')) return number * 1024
  return number
}

const bytesToMb = (value: any) => {
  const bytes = Number(value || 0)
  return bytes > 0 ? bytes / 1024 / 1024 : 0
}

const formatMoney = (item: any, field: string) => {
  const value = item[field]
  if (value === undefined || value === null || value === '') return ''
  const text = String(value)
  if (text.includes('¥')) return text
  const number = Number(value)
  if (!Number.isFinite(number)) return text
  return `¥${(number / 100).toFixed(number % 100 === 0 ? 0 : 2)}`
}

const normalizePrice = (item: any) => {
  if (item.price_text || item.amount_text) return item.price_text || item.amount_text
  if (item.price && String(item.price).includes('¥')) return item.price
  if (item.current_price_cents !== undefined) return `${formatMoney(item, 'current_price_cents')}${item.display_unit || ''}`
  if (item.price) return `¥${item.price}${item.display_unit || ''}`
  return '¥0'
}

const normalizeCapacityMb = (item: any) => {
  const benefits = item.benefits_json || item.benefits || {}
  return (
    Number(item.capacity_mb || item.space_size || item.capacity || 0) ||
    bytesToMb(benefits.resource_storage_capacity_bytes || item.resource_storage_capacity_bytes) ||
    Number(benefits.resource_storage_capacity_gb || item.resource_storage_capacity_gb || 0) * 1024 ||
    parseSpaceToMb(item.space_text || item.space || item.name)
  )
}

const normalizeFeatures = (item: any) => {
  const benefits = item.benefits_json || item.benefits || {}
  const features = item.promo_features || benefits.features || item.features || []
  return Array.isArray(features) ? features.map((feature: any) => String(feature)).filter(Boolean) : []
}

const mapPlan = (item: any): PlanPackageData => ({
  id: String(item.id || item.plan_id || item.grade || ''),
  name: item.name || item.title || item.grade_name || '资源包',
  capacityMb: normalizeCapacityMb(item),
  price: normalizePrice(item),
  originalPrice: formatMoney(item, 'original_price_cents'),
  concurrentRights: Number(item.concurrent_rights || item.concurrent || item.concurrent_limit || 0),
  trafficGb: Number(item.traffic_gb || item.flow_gb || item.traffic || 0),
  durationLabel: item.duration_label || item.buy_time_text || item.period || item.plan_subtitle || '长期有效',
  features: normalizeFeatures(item),
  isRecommended: Number(item.is_recommend || item.recommended || item.is_popular || 0) === 1,
  createdAt: item.create_time || item.created_at || '',
})

const mapOrder = (item: any): OrderData => ({
  id: String(item.id || item.order_id || item.order_no || ''),
  packageId: String(item.plan_id || item.membership_plan_id || ''),
  orderNo: item.order_no || item.order_id || '',
  amount: String(item.amount || item.pay_price || item.price || '0.00'),
  status: item.status === 'paid' || Number(item.status) === 1 || item.status === 'success' || item.pay_status === 'paid' ? 'success' : Number(item.status) === -1 || item.status === 'failed' ? 'failed' : 'pending',
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

const paymentQrImage = computed(() => {
  const order = activePayment.value || {}
  return order.qr_image || order.qr_code_data || order.qrcode || order.qrcode_url || ''
})

const paymentUrl = computed(() => {
  const order = activePayment.value || {}
  return order.payment_url || order.code_url || ''
})

const paymentAmount = computed(() => {
  const order = activePayment.value || {}
  if (order.amount) return String(order.amount)
  const cents = Number(order.amount_cents || 0)
  return cents > 0 ? (cents / 100).toFixed(2) : '0.00'
})

const paymentOrderNo = computed(() => {
  const order = activePayment.value || {}
  return order.order_no || order.order_id || ''
})

const stopPaymentPolling = () => {
  if (paymentTimer) {
    window.clearInterval(paymentTimer)
    paymentTimer = null
  }
}

const closePaymentDialog = () => {
  paymentDialogOpen.value = false
  stopPaymentPolling()
}

const pollPaymentStatus = (orderNo: string) => {
  stopPaymentPolling()
  if (!orderNo) return
  const check = async () => {
    try {
      const data = await pcApi.getPaymentOrderStatus(orderNo)
      const status = String(data?.status || data?.pay_status || '').toLowerCase()
      if (status === 'paid' || status === 'success') {
        toast.success('支付成功，权益已刷新')
        stopPaymentPolling()
        closePaymentDialog()
        await loadBilling()
      } else if (status === 'closed' || status === 'expired' || status === 'cancelled' || status === 'failed') {
        toast.error('订单已失效，请重新下单')
        stopPaymentPolling()
        await loadBilling()
      }
    } catch {
      // 轮询失败保留弹窗，下一轮继续恢复。
    }
  }
  paymentTimer = window.setInterval(check, 3000)
  check()
}

const handleUpgrade = async (planId: string) => {
  const plan = plans.value.find(p => p.id === planId)
  if (!plan) return

  isCreatingOrder.value = true
  try {
    const order = await pcApi.createMembershipOrder({ membership_plan_id: plan.id, plan_id: plan.id })
    activePayment.value = { ...order, plan_name: plan.name }
    paymentDialogOpen.value = true
    toast.success('订单已创建，请使用微信扫码支付')
    pollPaymentStatus(order?.order_no || order?.order_id || '')
  } catch (error: any) {
    toast.error(error?.message || '下单失败')
  } finally {
    isCreatingOrder.value = false
  }
}

const handleBackToWorkbench = () => {
  navigateTo('./management-workbench')
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
          <span class="inline-flex items-center gap-2 whitespace-nowrap">
            <SafeIcon name="Package" :size="16" class="shrink-0" />
            <span>可用套餐</span>
          </span>
        </TabsTrigger>
        <TabsTrigger value="orders" class="rounded-md">
          <span class="inline-flex items-center gap-2 whitespace-nowrap">
            <SafeIcon name="History" :size="16" class="shrink-0" />
            <span>订单历史</span>
          </span>
        </TabsTrigger>
      </TabsList>

      <!-- 套餐列表 Tab -->
      <TabsContent value="plans" class="mt-6 space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <PlanCard 
            v-for="plan in plans"
            :key="plan.id"
            :plan="plan"
            :is-current="storageUsage?.planName === plan.name"
            :is-loading="isCreatingOrder"
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

    <Dialog :open="paymentDialogOpen" @update:open="(val) => val ? (paymentDialogOpen = val) : closePaymentDialog()">
      <DialogContent class="sm:max-w-[520px]">
        <DialogHeader>
          <DialogTitle>微信扫码支付</DialogTitle>
          <DialogDescription>订单创建成功，请使用微信扫描二维码完成支付</DialogDescription>
        </DialogHeader>

        <div class="grid gap-5 py-2 sm:grid-cols-[220px_minmax(0,1fr)] sm:items-center">
          <div class="mx-auto flex h-56 w-56 items-center justify-center rounded-lg border border-border bg-white p-3">
            <img
              v-if="paymentQrImage"
              :src="paymentQrImage"
              alt="微信支付二维码"
              class="h-full w-full object-contain"
            />
            <div v-else class="flex flex-col items-center text-muted-foreground">
              <SafeIcon name="QrCode" :size="52" class="mb-2" />
              <span class="text-sm">二维码生成中</span>
            </div>
          </div>

          <div class="min-w-0 text-center sm:text-left">
            <p class="text-sm text-muted-foreground">购买套餐</p>
            <h3 class="mt-1 text-lg font-semibold text-foreground">{{ activePayment?.plan_name || '资源包' }}</h3>
            <p class="mt-4 text-3xl font-bold text-primary">¥{{ paymentAmount }}</p>
            <p class="mt-3 break-all text-xs text-muted-foreground">订单号：{{ paymentOrderNo || '-' }}</p>
            <p class="mt-4 inline-flex items-center gap-2 rounded-md bg-primary/10 px-3 py-2 text-sm text-primary">
              <SafeIcon name="Loader2" :size="14" class="animate-spin" />
              等待支付结果
            </p>
          </div>
        </div>

        <DialogFooter class="sm:justify-between">
          <Button variant="outline" @click="closePaymentDialog">稍后支付</Button>
          <Button v-if="paymentUrl" as-child>
            <a :href="paymentUrl" target="_blank" rel="noopener noreferrer">
              <SafeIcon name="ExternalLink" :size="14" class="mr-2" />
              打开支付链接
            </a>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
