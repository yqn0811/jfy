
<script setup lang="ts">
import { computed } from 'vue'
import type { SpaceFolderData } from '@/data/SpaceData'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  folder: SpaceFolderData
  activeFolder: string | null
  expandedFolders: Set<string>
  folderTree: Map<string | null, SpaceFolderData[]>
}>()

const emit = defineEmits<{
  (e: 'folder-select', folderId: string): void
  (e: 'toggle-folder', folderId: string): void
}>()

const children = computed(() => props.folderTree.get(props.folder.id) || [])
const isExpanded = computed(() => props.expandedFolders.has(props.folder.id))
const isActive = computed(() => props.activeFolder === props.folder.id)
const hasChildren = computed(() => children.value.length > 0)

const handleToggle = (e: Event) => {
  e.stopPropagation()
  emit('toggle-folder', props.folder.id)
}

const handleSelect = () => {
  emit('folder-select', props.folder.id)
}
</script>

<template>
  <div>
    <div class="flex items-center gap-1">
      <!-- 展开/折叠按钮 -->
      <button
        v-if="hasChildren"
        class="w-6 h-6 flex items-center justify-center hover:bg-muted rounded transition-colors"
        @click="handleToggle"
      >
        <SafeIcon
          name="ChevronRight"
          :size="16"
          :class="cn('text-muted-foreground transition-transform', isExpanded && 'rotate-90')"
        />
      </button>
      <div v-else class="w-6" />

      <!-- 文件夹按钮 -->
      <Button
        variant="ghost"
        class="flex-1 justify-start px-2 h-8 text-sm"
        :class="{ 'bg-primary/10 text-primary': isActive }"
        @click="handleSelect"
      >
        <SafeIcon name="Folder" :size="14" class="mr-2 shrink-0" />
        <span class="truncate text-xs">{{ folder.name }}</span>
      </Button>
    </div>

    <!-- 子文件夹 -->
    <div v-if="hasChildren && isExpanded" class="ml-2 space-y-0.5 mt-1">
      <FolderTreeItem
        v-for="child in children"
        :key="child.id"
        :folder="child"
        :active-folder="activeFolder"
        :expanded-folders="expandedFolders"
        :folder-tree="folderTree"
        @folder-select="(id) => emit('folder-select', id)"
        @toggle-folder="(id) => emit('toggle-folder', id)"
      />
    </div>
  </div>
</template>
