
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
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
const archiveItems = ref<ArchiveItemVO[]>(SpaceService.queryArchives({}))

const activeFolder = ref<string | null>(null)
const selectedArchive = ref<ArchiveItemVO | null>(null)
const isDrawerOpen = ref(false)
const searchKeyword = ref('')
const statusFilter = ref<string>('')

const breadcrumbs = [
  { label: '工作台', href: './workbench.html' },
  { label: '交付记录', href: './delivery-records.html' },
  { label: '空间与归档' }
]

const filteredArchives = computed(() => {
  return SpaceService.queryArchives({
    keyword: searchKeyword.value,
    filter: {
      ...(activeFolder.value && { folderId: activeFolder.value }),
      ...(statusFilter.value && { status: statusFilter.value })
    }
  })
})

const activeFolderName = computed(() => {
  if (!activeFolder.value) return '全部归档'
  return folders.value.find(f => f.id === activeFolder.value)?.name || '全部归档'
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
  toast.success(`已开始下载 "${archive.name}"`)
}

const handleMove = (archive: ArchiveItemVO) => {
  toast.success(`已移动 "${archive.name}" 到新位置`)
}

const handleDelete = (archive: ArchiveItemVO) => {
  toast.success(`已删除 "${archive.name}"`)
}

const handlePermissionChange = (archive: ArchiveItemVO) => {
  toast.success(`已更新 "${archive.name}" 的权限设置`)
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
  <div class="page-body min-h-screen flex flex-col gap-6">
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
</template>
