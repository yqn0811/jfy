
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import { pcApi } from '@/lib/api'
import { mapCategory, unwrapList } from '@/lib/jfyuntu-mappers'
import StatCard from '@/components/management_workbench/StatCard.vue'
import QuickActionCard from '@/components/management_workbench/QuickActionCard.vue'
import StorageWarning from '@/components/management_workbench/StorageWarning.vue'
import CategoryEditDialog from '@/components/category_management/CategoryEditDialog.vue'
import ProductCreateDialog from '@/components/product_management/ProductCreateDialog.vue'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductData } from '@/data/ProductData'

const isClient = ref(true)
const isLoading = ref(false)
const showCategoryDialog = ref(false)
const showProductDialog = ref(false)
const isSavingCategory = ref(false)
const isCreatingProduct = ref(false)
const profile = ref<any>({})
const categories = ref<CategoryData[]>([])

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    isClient.value = true
  })
  loadWorkbench()
  loadCategories()
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

const loadCategories = async () => {
  try {
    const raw = await pcApi.getManagementCategories({ page: 1, limit: 500 })
    categories.value = unwrapList(raw).map(item => mapCategory(item))
  } catch {
    // 工作台分类加载失败不影响概览展示
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
  showProductDialog.value = true
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

const buildCategoryPayload = (data: CategoryData) => ({
  fid: 0,
  folder_type: 1,
  folder_name: data.name,
  folder_desc: data.intro,
  private_type: data.visibility === 'private' ? 2 : data.visibility === 'shared' ? 4 : 1,
  layout_type: data.layout === 'list' ? 2 : 1,
  pic_layout: data.layout === 'list' ? 2 : 1,
})

const handleSaveCategory = async (data: CategoryData) => {
  isSavingCategory.value = true
  try {
    await pcApi.createProductOrCategory(buildCategoryPayload(data))
    toast.success('分类已创建')
    showCategoryDialog.value = false
    await Promise.all([loadWorkbench(), loadCategories()])
  } catch (error: any) {
    toast.error(error?.message || '创建失败')
  } finally {
    isSavingCategory.value = false
  }
}

const handleCreateProduct = async (data: {
  name: string
  intro: string
  categoryId: string
  visibility: ProductData['visibility']
  hideDetailImage: boolean
}) => {
  isCreatingProduct.value = true
  try {
    const categoryIds = data.categoryId === 'none' ? [] : [data.categoryId]
    await pcApi.createProductOrCategory({
      fid: categoryIds,
      folder_type: 2,
      folder_name: data.name,
      folder_desc: data.intro,
      category_ids: categoryIds,
      private_type: data.visibility === 'private' ? 2 : data.visibility === 'shared' ? 4 : 1,
      hide_detail_pictures: data.hideDetailImage ? 1 : 0,
      pic_ids: [],
      detail_pic_ids: [],
      new_thumb: '',
      allow_draft: 1,
    })
    toast.success('产品已创建')
    showProductDialog.value = false
    await loadWorkbench()
  } catch (error: any) {
    toast.error(error?.message || '创建失败')
  } finally {
    isCreatingProduct.value = false
  }
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
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
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

    <!-- Category Edit Dialog -->
    <CategoryEditDialog
      :open="showCategoryDialog"
      @update:open="showCategoryDialog = $event"
      @save="handleSaveCategory"
    />

    <ProductCreateDialog
      :open="showProductDialog"
      :categories="categories"
      :saving="isCreatingProduct"
      @update:open="showProductDialog = $event"
      @create="handleCreateProduct"
    />
  </div>
</template>
