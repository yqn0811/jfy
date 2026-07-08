
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'
import StatCard from '@/components/management_workbench/StatCard.vue'
import QuickActionCard from '@/components/management_workbench/QuickActionCard.vue'
import StorageWarning from '@/components/management_workbench/StorageWarning.vue'
import CategoryEditDialog from '@/components/management_workbench/CategoryEditDialog.vue'

const isClient = ref(true)
const isLoading = ref(false)
const showCategoryDialog = ref(false)
const profile = ref<any>({})

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })
  loadWorkbench()
})

const loadWorkbench = async () => {
  isLoading.value = true
  try {
    profile.value = await pcApi.getCurrentUser()
  } catch (error: any) {
    toast.error(error?.message || '工作台数据加载失败')
  } finally {
    isLoading.value = false
  }
}

const companyName = computed(() => profile.value?.company_name || profile.value?.nickname || '商户主页')
const usedMb = computed(() => Number(profile.value?.use_space || 0) / 1024 / 1024)
const totalSpaceText = computed(() => String(profile.value?.all_space || '0M'))
const storageStatus = computed(() => Number(profile.value?.space_used || 0) >= 95 ? 'insufficient' : Number(profile.value?.space_used || 0) >= 80 ? 'warning' : 'normal')

const stats = computed(() => [
  {
    label: '产品总数',
    value: profile.value?.product_count ?? 0,
    icon: 'Package',
    color: 'text-blue-600',
  },
  {
    label: '分类总数',
    value: profile.value?.category_count ?? 0,
    icon: 'FolderTree',
    color: 'text-green-600',
  },
  {
    label: '存储已用',
    value: `${usedMb.value.toFixed(1)}MB`,
    icon: 'Database',
    color: 'text-orange-600',
  },
  {
    label: '浏览量',
    value: profile.value?.view_count ?? 0,
    icon: 'TrendingUp',
    color: 'text-purple-600',
  },
])

const quickActions = [
  {
    title: '分类管理',
    description: '组织产品分类',
    icon: 'FolderTree',
    href: './category-management.html',
  },
  {
    title: '产品管理',
    description: '编辑产品信息',
    icon: 'Package',
    href: './product-management.html',
  },
  {
    title: '回收站',
    description: '恢复删除内容',
    icon: 'Trash2',
    href: './recycling-bin.html',
  },
]

const handleNewProduct = () => {
  window.location.href = './product-edit.html'
}

const handleNewCategory = () => {
  showCategoryDialog.value = true
}

const handleBatchUpload = () => {
  if (!profile.value?.product_count || profile.value.product_count === 0) {
    toast.error('请先创建产品')
    return
  }
  window.location.href = './product-management.html'
}

const handleCategoryCreated = () => {
  showCategoryDialog.value = false
  toast.success('分类创建成功')
  loadWorkbench()
}

const handleStorageClick = () => {
  window.location.href = './billing-usage.html'
}
</script>

<template>
  <div class="page-body space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-page-title">工作台概览</h1>
        <p class="text-caption mt-2">{{ companyName }} - 实时数据统计</p>
      </div>
      <div class="flex items-center gap-3">
        <Button variant="outline" @click="handleNewCategory">
          <SafeIcon name="FolderPlus" :size="18" class="mr-2" />
          新建分类
        </Button>
        <Button @click="handleNewProduct">
          <SafeIcon name="Plus" :size="18" class="mr-2" />
          新建产品
        </Button>
      </div>
    </div>

    <!-- Storage Warning (if needed) -->
    <StorageWarning
      v-if="storageStatus === 'warning' || storageStatus === 'insufficient'"
      :used="usedMb"
      :total="0"
      :status="storageStatus"
      @click="handleStorageClick"
    />

    <!-- KPI Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :color="stat.color"
      />
    </div>

    <!-- Quick Actions -->
    <div class="space-y-4">
      <h2 class="text-section-title">快速操作</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <QuickActionCard
          v-for="action in quickActions"
          :key="action.title"
          :title="action.title"
          :description="action.description"
          :icon="action.icon"
          :href="action.href"
        />
        <Card class="product-card cursor-pointer hover:shadow-card transition-shadow" @click="handleBatchUpload">
          <CardHeader class="pb-3">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <CardTitle class="text-base">批量上传</CardTitle>
                <CardDescription class="text-xs mt-1">协作上传产品图片</CardDescription>
              </div>
              <div class="w-10 h-10 bg-accent/10 rounded-lg flex items-center justify-center shrink-0">
                <SafeIcon name="Upload" :size="20" class="text-accent" />
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-xs text-muted-foreground">生成分享链接，邀请团队成员上传</p>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Recent Activity (Optional) -->
    <div class="space-y-4">
      <h2 class="text-section-title">最近更新</h2>
      <Card class="surface-base">
        <CardContent class="pt-6">
          <div class="flex flex-col items-center justify-center py-12 text-center">
            <SafeIcon name="Clock" :size="40" class="text-muted-foreground/30 mb-3" />
            <p class="text-muted-foreground text-sm">暂无最近更新记录</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Category Edit Dialog -->
    <CategoryEditDialog
      :open="showCategoryDialog"
      @update:open="showCategoryDialog = $event"
      @created="handleCategoryCreated"
    />
  </div>
</template>
