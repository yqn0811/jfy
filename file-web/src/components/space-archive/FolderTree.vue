
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { SpaceFolderData } from '@/data/SpaceData'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import FolderTreeItem from '@/components/space-archive/FolderTreeItem.vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  folders: SpaceFolderData[]
  activeFolder: string | null
}>()

const emit = defineEmits<{
  (e: 'folder-select', folderId: string | null): void
}>()

const expandedFolders = ref<Set<string>>(new Set())

const folderTree = computed(() => {
  const tree: Map<string | null, SpaceFolderData[]> = new Map()
  
  props.folders.forEach(folder => {
    const parentId = folder.parentId || null
    if (!tree.has(parentId)) {
      tree.set(parentId, [])
    }
    tree.get(parentId)!.push(folder)
  })

  return tree
})

const toggleFolder = (folderId: string) => {
  if (expandedFolders.value.has(folderId)) {
    expandedFolders.value.delete(folderId)
  } else {
    expandedFolders.value.add(folderId)
  }
}

const selectFolder = (folderId: string | null) => {
  emit('folder-select', folderId)
}

const hasChildren = (folderId: string) => {
  return (folderTree.value.get(folderId) || []).length > 0
}

const isExpanded = (folderId: string) => {
  return expandedFolders.value.has(folderId)
}

const renderFolderItem = (folder: SpaceFolderData, level: number = 0) => {
  const children = folderTree.value.get(folder.id) || []
  const isActive = props.activeFolder === folder.id
  const expanded = isExpanded(folder.id)
  const hasChild = children.length > 0

  return {
    folder,
    level,
    children,
    isActive,
    expanded,
    hasChild
  }
}
</script>

<template>
  <div class="surface-raised card-padding">
    <h3 class="text-item-title mb-4 text-foreground">文件夹</h3>
    
    <div class="space-y-1">
      <!-- 全部归档 -->
      <Button
        variant="ghost"
        class="w-full justify-start px-3 h-9 text-sm"
        :class="{ 'bg-primary/10 text-primary': activeFolder === null }"
        @click="selectFolder(null)"
      >
        <SafeIcon name="FolderOpen" :size="16" class="mr-2 shrink-0" />
        <span class="truncate">全部归档</span>
      </Button>

      <!-- 文件夹树 -->
      <div v-if="folders.length > 0" class="space-y-0.5">
        <template v-for="rootFolder in (folderTree.get(null) || [])" :key="rootFolder.id">
          <FolderTreeItem
            :folder="rootFolder"
            :active-folder="activeFolder"
            :expanded-folders="expandedFolders"
            :folder-tree="folderTree"
            @folder-select="selectFolder"
            @toggle-folder="toggleFolder"
          />
        </template>
      </div>
    </div>
  </div>
</template>

