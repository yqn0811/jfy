
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import Pagination from '@/components/common/Pagination.vue'
import LoginDialog from '@/components/common/LoginDialog.vue'
import { toast } from 'vue-sonner'
import { authStore, pcApi } from '@/lib/api'
import { buildPcTargetUrl, mapPcRecord, unwrapList, type PcRecordItem } from '@/lib/jfyuntu-mappers'

type FavoriteType = 'all' | 'homepage' | 'category' | 'product'

interface Props {
  embedded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  embedded: false,
})

const isClient = ref(true)
const isLoading = ref(false)
const activeTab = ref<FavoriteType>('all')
const searchKeyword = ref('')
const currentPage = ref(1)
const pageSize = 20
const confirmDialogOpen = ref(false)
const selectedFavorite = ref<PcRecordItem | null>(null)
const showLoginDialog = ref(false)
const isLoggedIn = ref(false)

const favorites = ref<PcRecordItem[]>([])
const serverTotal = ref(0)

const tabOptions = [
  { value: 'all' as FavoriteType, label: '全部' },
  { value: 'homepage' as FavoriteType, label: '主页' },
  { value: 'category' as FavoriteType, label: '分类' },
  { value: 'product' as FavoriteType, label: '产品' },
]

onMounted(async () => {
  isClient.value = false
  requestAnimationFrame(() => {
    authStore.consumeCallbackToken()
    const params = new URLSearchParams(window.location.search)
    const tabParam = params.get('tab') as FavoriteType | null
    if (tabParam && ['all', 'homepage', 'category', 'product'].includes(tabParam)) {
      activeTab.value = tabParam
    }
    isLoggedIn.value = authStore.isLoggedIn()
    isClient.value = true
  })
  authStore.consumeCallbackToken()
  isLoggedIn.value = authStore.isLoggedIn()
  if (!isLoggedIn.value) {
    showLoginDialog.value = true
    return
  }
  await loadFavorites()
})

const loadFavorites = async () => {
  if (!authStore.isLoggedIn()) {
    favorites.value = []
    serverTotal.value = 0
    isLoggedIn.value = false
    showLoginDialog.value = true
    return
  }
  isLoggedIn.value = true
  isLoading.value = true
  try {
    const raw = await pcApi.getFavorites(activeTab.value, searchKeyword.value.trim(), currentPage.value)
    favorites.value = unwrapList(raw).map(item => mapPcRecord(item))
    serverTotal.value = Number(raw?.total || favorites.value.length)
  } catch (error: any) {
    if (error?.code === 401 || error?.code === 410000 || /登录|token|授权/i.test(error?.message || '')) {
      authStore.clearToken()
      isLoggedIn.value = false
      showLoginDialog.value = true
    }
    toast.error(error?.message || '收藏加载失败')
  } finally {
    isLoading.value = false
  }
}

const totalItems = computed(() => serverTotal.value || favorites.value.length)

const paginatedFavorites = computed(() => {
  return favorites.value
})

const getFavoriteTitle = (fav: any): string => {
  return fav.title || '未命名'
}

const getFavoriteSubtitle = (fav: any): string => {
  return fav.subtitle || ''
}

const getFavoriteCover = (fav: any): string => {
  return fav.coverUrl || ''
}

const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const handleTabChange = (tab: FavoriteType) => {
  activeTab.value = tab
  currentPage.value = 1
  loadFavorites()
}

const handleSearch = () => {
  currentPage.value = 1
  loadFavorites()
}

const handleView = (fav: any) => {
  window.location.href = buildPcTargetUrl(fav.targetType, fav.targetId, fav.targetUserId)
}

const handleDownload = (fav: any) => {
  if (fav.targetType === 'product') {
    toast.success('下载已开始')
  }
}

const openRemoveConfirm = (favorite: PcRecordItem) => {
  selectedFavorite.value = favorite
  confirmDialogOpen.value = true
}

const handleRemoveFavorite = async () => {
  if (!selectedFavorite.value) return

  try {
    await pcApi.toggleFavorite(
      selectedFavorite.value.targetType === 'home' ? 'homepage' : selectedFavorite.value.targetType,
      selectedFavorite.value.targetId,
      false
    )
    toast.success('已取消收藏')
    await loadFavorites()
  } catch (error: any) {
    toast.error(error?.message || '取消收藏失败')
  }

  selectedFavorite.value = null
  confirmDialogOpen.value = false
}

const handleGoToBrowse = () => {
  if (props.embedded) {
    window.location.href = './share-home.html'
    return
  }
  window.location.href = './share-home.html'
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  loadFavorites()
}

const handleLoginSuccess = async () => {
  showLoginDialog.value = false
  isLoggedIn.value = true
  await loadFavorites()
}
</script>

<template>
  <div :class="props.embedded ? 'flex min-h-0 flex-col' : 'page-body flex flex-col min-h-screen'">
    <!-- Page Header -->
    <div v-if="!props.embedded" class="mb-6">
      <h1 class="text-page-title mb-2">我的收藏</h1>
      <p class="text-caption">你收藏的主页、分类和产品会展示在这里</p>
    </div>

    <!-- Filter Bar -->
    <div :class="props.embedded ? 'mb-4' : 'surface-base card-padding mb-6'">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <!-- Tabs -->
        <Tabs :model-value="activeTab" @update:model-value="(value) => handleTabChange(value as FavoriteType)" class="lg:w-auto">
          <TabsList class="grid w-full grid-cols-4 bg-muted/50 lg:w-[420px]">
            <TabsTrigger v-for="tab in tabOptions" :key="tab.value" :value="tab.value">
              {{ tab.label }}
            </TabsTrigger>
          </TabsList>
        </Tabs>

        <!-- Search -->
        <div class="flex min-w-0 flex-1 gap-2 lg:max-w-md">
          <Input
            v-model="searchKeyword"
            placeholder="搜索收藏内容"
            class="flex-1 h-10 bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary"
            @keyup.enter="handleSearch"
          />
          <Button variant="default" size="sm" class="h-10 px-6" @click="handleSearch">
            <SafeIcon name="Search" :size="16" class="mr-2" />
            搜索
          </Button>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div v-if="!isClient" class="flex-1">
      <!-- SSG Placeholder - will be replaced on client hydration -->
    </div>

    <div v-if="isClient" class="flex-1 flex flex-col min-h-0">
      <div v-if="!isLoggedIn" class="flex-1 flex items-center justify-center">
        <EmptyState
          icon="ShieldCheck"
          title="请先登录"
          description="登录后可以查看你在小程序和网页收藏的主页、分类和产品"
        >
          <Button variant="default" @click="showLoginDialog = true">
            <SafeIcon name="QrCode" :size="16" class="mr-2" />
            微信扫码登录
          </Button>
        </EmptyState>
      </div>

      <!-- Empty State -->
      <div v-else-if="!isLoading && totalItems === 0" class="flex-1 flex items-center justify-center">
        <EmptyState
          icon="Heart"
          title="暂无收藏"
          description="你收藏的主页、分类和产品会展示在这里"
        >
          <Button variant="default" @click="handleGoToBrowse">
            <SafeIcon name="ArrowRight" :size="16" class="mr-2" />
            去浏览
          </Button>
        </EmptyState>
      </div>

      <div v-else-if="isLoading" class="flex-1 flex items-center justify-center text-muted-foreground">
        <SafeIcon name="Loader2" :size="24" class="mr-2 animate-spin" />
        加载中...
      </div>

      <!-- Favorites Table -->
      <template v-else>
        <div class="surface-base overflow-hidden flex flex-col flex-1">
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow class="border-b border-border hover:bg-transparent">
                  <TableHead class="w-20 whitespace-nowrap">封面</TableHead>
                  <TableHead class="min-w-0 max-w-xs whitespace-nowrap">标题</TableHead>
                  <TableHead class="w-32 whitespace-nowrap">副标题</TableHead>
                  <TableHead class="w-20 whitespace-nowrap">类型</TableHead>
                  <TableHead class="w-32 whitespace-nowrap">收藏时间</TableHead>
                  <TableHead class="w-40 whitespace-nowrap text-right">操作</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="fav in paginatedFavorites"
                  :key="fav.id"
                  class="border-b border-border hover:bg-muted/50 transition-colors"
                >
                  <!-- Cover -->
                  <TableCell class="w-20 py-3">
                    <div class="w-16 h-16 rounded-md overflow-hidden bg-muted/50 flex-shrink-0">
                      <img
                        :src="getFavoriteCover(fav)"
                        :alt="getFavoriteTitle(fav)"
                        class="w-full h-full object-cover"
                      />
                    </div>
                  </TableCell>

                  <!-- Title -->
                  <TableCell class="min-w-0 max-w-xs py-3 truncate text-item-title">
                    {{ getFavoriteTitle(fav) }}
                  </TableCell>

                  <!-- Subtitle -->
                  <TableCell class="w-32 py-3 text-caption truncate">
                    {{ getFavoriteSubtitle(fav) }}
                  </TableCell>

                  <!-- Type -->
                  <TableCell class="w-20 py-3">
                    <span class="inline-block px-2 py-1 text-xs font-medium rounded bg-muted text-muted-foreground">
                      {{ fav.targetType === 'home' ? '主页' : fav.targetType === 'category' ? '分类' : '产品' }}
                    </span>
                  </TableCell>

                  <!-- Date -->
                  <TableCell class="w-32 py-3 text-caption">
                    {{ formatDate(fav.createdAt) }}
                  </TableCell>

                  <!-- Actions -->
                  <TableCell class="w-40 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        class="h-8 px-3 text-xs"
                        @click="handleView(fav)"
                      >
                        <SafeIcon name="Eye" :size="14" class="mr-1" />
                        查看
                      </Button>

                      <Button
                        v-if="fav.targetType === 'product'"
                        variant="outline"
                        size="sm"
                        class="h-8 px-3 text-xs"
                        @click="handleDownload(fav)"
                      >
                        <SafeIcon name="Download" :size="14" class="mr-1" />
                        下载
                      </Button>

                      <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 px-3 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                        @click="openRemoveConfirm(fav)"
                      >
                        <SafeIcon name="Trash2" :size="14" class="mr-1" />
                        取消收藏
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
          <Pagination
            :current="currentPage"
            :total="totalItems"
            :page-size="pageSize"
            @update:current="handlePageChange"
          />
        </div>
      </template>
    </div>

    <!-- Confirm Remove Dialog -->
    <ConfirmDialog
      :open="confirmDialogOpen"
      title="取消收藏"
      description="确定要取消收藏该内容吗？"
      confirm-text="确认取消"
      cancel-text="取消"
      variant="default"
      @update:open="(val) => (confirmDialogOpen = val)"
      @confirm="handleRemoveFavorite"
    />

    <LoginDialog
      :open="showLoginDialog"
      @update:open="showLoginDialog = $event"
      @login-success="handleLoginSuccess"
    />
  </div>
</template>

<style scoped>
/* 确保表格在不同状态下的视觉一致性 */
:deep(.table) {
  @apply w-full;
}

:deep(.table-header) {
  @apply bg-muted/30;
}
</style>
