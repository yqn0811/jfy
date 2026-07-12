
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { SpaceService } from '@/data/SpaceService'
import type { SpaceOverview, ArchiveItemVO } from '@/data/SpaceService'
import type { SpaceFolderData } from '@/data/SpaceData'
import DetailPageHeader from '@/components/common/DetailPageHeader.vue'
import SpaceOverviewCard from '@/components/space-archive/SpaceOverviewCard.vue'
import FolderTree from '@/components/space-archive/FolderTree.vue'
import ArchiveListTable from '@/components/space-archive/ArchiveListTable.vue'
import ArchiveDetailDrawer from '@/components/space-archive/ArchiveDetailDrawer.vue'
import { Button } from '@/components/ui/button'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'

const props = defineProps<{
  currentPath?: string
}>()

const isClient = ref(true)
const spaceOverview = ref<SpaceOverview>(SpaceService.getOverview())
const folders = ref<SpaceFolderData[]>(SpaceService.getFolders())
const activeFolder = ref<string | null>(null)
const selectedArchive = ref<ArchiveItemVO | null>(null)
const isDrawerOpen = ref(false)
const searchKeyword = ref('')
const statusFilter = ref<string>('')

const breadcrumbs = [
  { label: '工作台', href: '/workbench' },
  { label: '交付记录', href: '/delivery-records' },
  { label: '空间与归档' }
]

const filteredArchives = computed(() => {
  const keyword = searchKeyword.value.trim().toLowerCase()
  return SpaceService.queryArchives({
    keyword,
    filter: {
      ...(activeFolder.value && { folderId: activeFolder.value }),
      ...(statusFilter.value && statusFilter.value !== 'all' && { status: statusFilter.value })
    }
  })
})

const activeFolderName = computed(() => {
  if (!activeFolder.value) return folders.value.length ? '全部归档' : '归档列表'
  return folders.value.find(f => f.id === activeFolder.value)?.name || '归档列表'
})

const handleFolderSelect = (folderId: string | null) => {
  activeFolder.value = folderId
  selectedArchive.value = null
  isDrawerOpen.value = false
}

const handleArchiveSelect = (archive: ArchiveItemVO) => {
  selectedArchive.value = archive
  isDrawerOpen.value = true
}

const handleDownload = (archive: ArchiveItemVO) => {
  if (!archive.id) return
  toast.info('归档下载能力待接入真实数据')
}

const handleMove = (archive: ArchiveItemVO) => {
  if (!archive.id) return
  toast.info('归档移动能力待接入真实数据')
}

const handleDelete = (archive: ArchiveItemVO) => {
  if (!archive.id) return
  toast.info('归档删除能力待接入真实数据')
}

const handlePermissionChange = (archive: ArchiveItemVO) => {
  if (!archive.id) return
  toast.info('归档权限能力待接入真实数据')
}

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const folderId = params.get('folderId')
    if (folderId) {
      activeFolder.value = folderId
    }
    isClient.value = true
  })
})
</script>

<template>
  <div class="page-body min-h-screen">
    <div class="app-shell flex flex-col gap-6">
      <!-- 页面头部 -->
      <DetailPageHeader
        title="空间与归档"
        :breadcrumbs="breadcrumbs"
      >
        <template #actions>
          <Button variant="outline" size="sm">
            <SafeIcon name="Settings" :size="16" class="mr-2" />
            空间设置
          </Button>
        </template>
      </DetailPageHeader>

      <!-- 空间概览卡片 -->
      <SpaceOverviewCard :overview="spaceOverview" />

      <!-- 主体内容：文件夹树 + 归档列表 -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1">
        <!-- 左侧：文件夹树 -->
        <div class="lg:col-span-1">
          <FolderTree
            :folders="folders"
            :active-folder="activeFolder"
            @folder-select="handleFolderSelect"
          />
        </div>

        <!-- 右侧：归档列表 -->
        <div class="lg:col-span-3 flex flex-col gap-4">
          <!-- 列表标题和筛选 -->
          <div class="flex items-center justify-between">
            <h2 class="text-section-title">
              {{ activeFolderName }}
            </h2>
            <span class="text-caption text-muted-foreground">
              共 {{ filteredArchives.length }} 项
            </span>
          </div>

          <!-- 归档列表 -->
          <ArchiveListTable
            :items="filteredArchives"
            :search-keyword="searchKeyword"
            :status-filter="statusFilter"
            @search="(keyword) => (searchKeyword = keyword)"
            @status-filter="(status) => (statusFilter = status)"
            @archive-select="handleArchiveSelect"
            @download="handleDownload"
            @move="handleMove"
            @delete="handleDelete"
            @permission-change="handlePermissionChange"
          />
        </div>
      </div>

      <!-- 右侧详情抽屉 -->
      <ArchiveDetailDrawer
        v-if="selectedArchive && isDrawerOpen"
        :archive="selectedArchive"
        :is-open="isDrawerOpen"
        @close="isDrawerOpen = false"
        @download="handleDownload"
        @move="handleMove"
        @delete="handleDelete"
        @permission-change="handlePermissionChange"
      />
    </div>
  </div>
</template>
