
<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import SafeIcon from '@/components/common/SafeIcon.vue'
import FallbackImage from '@/components/common/FallbackImage.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import Pagination from '@/components/common/Pagination.vue'
import { toast } from 'vue-sonner'
import { cn } from '@/lib/utils'
import { pcApi } from '@/lib/api'
import { buildPcTargetUrl, mapPcRecord, unwrapList, type PcRecordItem } from '@/lib/jfyuntu-mappers'
import { navigateToInternal } from '@/navigation'

type TabType = 'all' | 'homepage' | 'category' | 'product'

interface Props {
  embedded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  embedded: false,
})

const emit = defineEmits<{
  (e: 'navigate'): void
}>()

const isClient = ref(true)
const activeTab = ref<TabType>('all')
const keyword = ref('')
const currentPage = ref(1)
const pageSize = 20
const isLoading = ref(false)
const serverTotal = ref(0)

const browsingRecords = ref<PcRecordItem[]>([])
const favorites = ref<string[]>([])

const confirmDialog = reactive({
  open: false,
  recordId: '',
})

const filteredRecords = computed(() => {
  return browsingRecords.value
})

const totalItems = computed(() => isLoading.value ? Math.max(serverTotal.value, filteredRecords.value.length) : (serverTotal.value || filteredRecords.value.length))

const paginatedRecords = computed(() => {
  return filteredRecords.value
})

const showInitialLoading = computed(() => isLoading.value && browsingRecords.value.length === 0)
const showEmptyState = computed(() => !isLoading.value && totalItems.value === 0)

const loadRecords = async () => {
  isLoading.value = true
  try {
    const raw = await pcApi.getVisits(activeTab.value, keyword.value.trim(), currentPage.value)
    browsingRecords.value = unwrapList(raw).map(item => mapPcRecord(item))
    serverTotal.value = Number(raw?.total || browsingRecords.value.length)
  } catch (error: any) {
    toast.error(error?.message || '足迹加载失败')
  } finally {
    isLoading.value = false
  }
}

const handleTabChange = async (tab: TabType) => {
  activeTab.value = tab
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }
  await loadRecords()
}

const handleSearch = () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }
  currentPage.value = 1
  loadRecords()
}

const handleKeywordInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  keyword.value = target.value
  currentPage.value = 1
}

const handleView = (record: PcRecordItem) => {
  emit('navigate')
  navigateToInternal(buildPcTargetUrl(record.targetType, record.targetId, record.targetUserId, record.targetShareCode))
}

const handleCollect = async (record: PcRecordItem) => {
  const isFavorited = favorites.value.includes(record.id)
  try {
    await pcApi.toggleFavorite(
      record.targetType === 'home' ? 'homepage' : record.targetType,
      record.targetId,
      !isFavorited
    )
    if (isFavorited) {
      favorites.value = favorites.value.filter(id => id !== record.id)
      toast.success('已取消收藏')
    } else {
      favorites.value.push(record.id)
      toast.success('收藏成功')
    }
  } catch (error: any) {
    toast.error(error?.message || '操作失败')
  }
}

const handleDeleteClick = (record: PcRecordItem) => {
  confirmDialog.open = true
  confirmDialog.recordId = record.id
}

const handleConfirmDelete = async () => {
  try {
    await pcApi.deleteVisit(confirmDialog.recordId)
    toast.success('已删除浏览记录')
    await loadRecords()
  } catch (error: any) {
    toast.error(error?.message || '删除失败')
  }
}

const handleGoToBrowse = () => {
  emit('navigate')
  navigateToInternal('./share-home')
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return '刚刚'
  if (diffMins < 60) return `${diffMins}分钟前`
  if (diffHours < 24) return `${diffHours}小时前`
  if (diffDays < 7) return `${diffDays}天前`

  return date.toLocaleDateString('zh-CN')
}

onMounted(async () => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const tabParam = params.get('tab') as TabType | null
    const keywordParam = params.get('keyword')

    if (tabParam && ['all', 'homepage', 'category', 'product'].includes(tabParam)) {
      activeTab.value = tabParam
    }
    if (keywordParam) {
      keyword.value = keywordParam
    }

    isClient.value = true
  })
  await loadRecords()
})

watch(currentPage, () => {
  if (!props.embedded) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
  loadRecords()
})
</script>

<template>
  <div :class="props.embedded ? 'space-y-4' : 'page-body space-y-6'">
    <!-- Page Header -->
    <div v-if="!props.embedded" class="flex flex-col gap-2">
      <h1 class="text-page-title">浏览足迹</h1>
      <p class="text-caption">你浏览过的主页、分类和产品会展示在这里</p>
    </div>

    <!-- Filter & Search Bar -->
    <div :class="props.embedded ? '' : 'surface-base card-padding'">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <!-- Tabs -->
      <Tabs :value="activeTab" @update:model-value="handleTabChange" class="lg:w-auto">
        <TabsList class="grid w-full grid-cols-4 h-auto bg-muted p-1 lg:w-[420px]">
          <TabsTrigger value="all" class="data-[state=active]:bg-card data-[state=active]:text-primary">
            全部
          </TabsTrigger>
          <TabsTrigger value="homepage" class="data-[state=active]:bg-card data-[state=active]:text-primary">
            主页
          </TabsTrigger>
          <TabsTrigger value="category" class="data-[state=active]:bg-card data-[state=active]:text-primary">
            分类
          </TabsTrigger>
          <TabsTrigger value="product" class="data-[state=active]:bg-card data-[state=active]:text-primary">
            产品
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Search Input -->
      <div class="relative min-w-0 flex-1 lg:max-w-md">
        <SafeIcon
          name="Search"
          :size="18"
          class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
        />
        <Input
          type="text"
          placeholder="搜索浏览记录..."
          :value="keyword"
          class="pl-10 h-10 bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary"
          @input="handleKeywordInput"
          @keyup.enter="handleSearch"
        />
      </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="showInitialLoading" class="py-12 text-center text-muted-foreground">
      <SafeIcon name="Loader2" :size="24" class="mx-auto mb-2 animate-spin" />
      加载中...
    </div>

    <div v-else-if="showEmptyState" class="py-12">
      <EmptyState
        icon="History"
        title="暂无足迹"
        description="你浏览过的主页、分类和产品会展示在这里"
      >
        <Button @click="handleGoToBrowse" class="mt-4">
          <SafeIcon name="ArrowRight" :size="16" class="mr-2" />
          去浏览
        </Button>
      </EmptyState>
    </div>

    <!-- Records Table -->
    <div v-else class="surface-base relative overflow-hidden flex flex-col">
      <div
        v-if="isLoading"
        class="absolute inset-0 z-10 flex items-center justify-center bg-background/65 backdrop-blur-[1px]"
      >
        <div class="flex items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm text-muted-foreground shadow-sm">
          <SafeIcon name="Loader2" :size="16" class="animate-spin" />
          加载中...
        </div>
      </div>
      <div class="overflow-x-auto">
        <Table class="w-full">
          <TableHeader class="bg-muted/50 sticky top-0">
            <TableRow class="border-b border-border hover:bg-transparent">
              <TableHead class="w-20 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                封面
              </TableHead>
              <TableHead class="flex-1 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">
                标题
              </TableHead>
              <TableHead class="w-24 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">
                类型
              </TableHead>
              <TableHead class="w-32 h-12 px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">
                浏览时间
              </TableHead>
              <TableHead class="w-48 h-12 px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                操作
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="record in paginatedRecords"
              :key="record.id"
              class="border-b border-border hover:bg-muted/30 transition-colors"
            >
              <!-- Cover Image -->
              <TableCell class="px-4 py-3">
                <div class="w-16 h-16 rounded-md overflow-hidden bg-muted flex items-center justify-center shrink-0">
                  <FallbackImage
                    :src="record.coverUrl"
                    :candidates="record.coverUrlCandidates"
                    :alt="record.title"
                    class="w-full h-full object-cover"
                  >
                    <SafeIcon name="Image" :size="24" class="text-muted-foreground" />
                  </FallbackImage>
                </div>
              </TableCell>

              <!-- Title & Subtitle -->
              <TableCell class="px-4 py-3 min-w-0">
                <div class="flex flex-col gap-1">
                  <p class="text-item-title font-medium truncate">{{ record.title }}</p>
                  <p class="text-caption truncate">{{ record.subtitle }}</p>
                </div>
              </TableCell>

              <!-- Type Badge -->
              <TableCell class="px-4 py-3 whitespace-nowrap">
                <span
                  :class="cn(
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    record.targetType === 'home' && 'bg-blue-100 text-blue-800',
                    record.targetType === 'category' && 'bg-purple-100 text-purple-800',
                    record.targetType === 'product' && 'bg-green-100 text-green-800'
                  )"
                >
                  {{ record.targetType === 'home' ? '主页' : record.targetType === 'category' ? '分类' : '产品' }}
                </span>
              </TableCell>

              <!-- Viewed Time -->
              <TableCell class="px-4 py-3 text-caption whitespace-nowrap">
                {{ record.timeText || formatDate(record.createdAt) }}
              </TableCell>

              <!-- Actions -->
              <TableCell class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-3 text-xs"
                    @click="handleView(record)"
                  >
                    <SafeIcon name="Eye" :size="14" class="mr-1" />
                    查看
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-3 text-xs"
                    :class="favorites.includes(record.id) && 'text-accent'"
                    @click="handleCollect(record)"
                  >
                    <SafeIcon
                      :name="favorites.includes(record.id) ? 'Heart' : 'Heart'"
                      :size="14"
                      class="mr-1"
                      :fill="favorites.includes(record.id) ? 'currentColor' : 'none'"
                    />
                    {{ favorites.includes(record.id) ? '已收藏' : '收藏' }}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                    @click="handleDeleteClick(record)"
                  >
                    <SafeIcon name="Trash2" :size="14" class="mr-1" />
                    删除记录
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="border-t border-border px-4 py-4 bg-muted/20">
        <Pagination
          :current="currentPage"
          :total="totalItems"
          :page-size="pageSize"
          @update:current="currentPage = $event"
        />
      </div>
    </div>

    <!-- Delete Confirm Dialog -->
    <ConfirmDialog
      :open="confirmDialog.open"
      title="删除足迹"
      description="删除后将不再展示这条浏览记录，确定删除吗？"
      confirm-text="确认删除"
      cancel-text="取消"
      variant="destructive"
      @update:open="confirmDialog.open = $event"
      @confirm="handleConfirmDelete"
    />
  </div>
</template>

<style scoped>
/* Ensure table cells have proper alignment and spacing */
:deep(.table) {
  @apply w-full border-collapse;
}

:deep(.table-row) {
  @apply border-b border-border;
}

:deep(.table-cell) {
  @apply px-4 py-3 align-middle;
}
</style>
