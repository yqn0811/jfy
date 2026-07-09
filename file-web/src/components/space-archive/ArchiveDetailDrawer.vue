
<script setup lang="ts">
import type { ArchiveItemVO } from '@/data/SpaceService'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
  SheetFooter
} from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import SafeIcon from '@/components/common/SafeIcon.vue'

const props = defineProps<{
  archive: ArchiveItemVO
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'download', archive: ArchiveItemVO): void
  (e: 'move', archive: ArchiveItemVO): void
  (e: 'delete', archive: ArchiveItemVO): void
  (e: 'permission-change', archive: ArchiveItemVO): void
}>()

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatSize = (mb: number) => {
  if (mb >= 1024) {
    return (mb / 1024).toFixed(1) + ' GB'
  }
  return mb.toFixed(1) + ' MB'
}
</script>

<template>
  <Sheet :open="isOpen" @update:open="(open) => !open && emit('close')">
    <SheetContent class="w-full sm:w-96 flex flex-col max-h-[80vh]">
      <SheetHeader class="shrink-0">
        <SheetTitle class="flex items-center gap-2">
          <SafeIcon name="Archive" :size="20" />
          归档详情
        </SheetTitle>
        <SheetDescription>
          查看和管理归档项的详细信息
        </SheetDescription>
      </SheetHeader>

      <!-- 内容区域 -->
      <div class="flex-1 overflow-y-auto min-h-0 py-4 space-y-4">
        <!-- 基本信息 -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">基本信息</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">名称</span>
              <span class="font-medium truncate max-w-[200px]">{{ archive.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">来源任务</span>
              <span class="font-medium">{{ archive.sourceTaskId }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">所在文件夹</span>
              <span class="font-medium">{{ archive.folderName }}</span>
            </div>
          </div>
        </div>

        <Separator />

        <!-- 存储信息 -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">存储信息</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">文件数量</span>
              <span class="font-medium">{{ archive.fileCount }} 个</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">存储大小</span>
              <span class="font-medium">{{ formatSize(archive.storageSizeMb) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">归档时间</span>
              <span class="font-medium text-xs">{{ formatDate(archive.archivedAt) }}</span>
            </div>
          </div>
        </div>

        <Separator />

        <!-- 权限信息 -->
        <div class="space-y-3">
          <h4 class="text-sm font-semibold text-foreground">权限设置</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">访问权限</span>
              <span class="font-medium">{{ archive.permissionName }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">状态</span>
              <span class="font-medium capitalize">
                {{ archive.status === 'ready' ? '就绪' : archive.status === 'locked' ? '锁定' : '已移动' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- 操作按钮 -->
      <SheetFooter class="shrink-0 flex-row gap-2 pt-4 border-t border-border/50">
        <Button
          variant="outline"
          size="sm"
          class="flex-1"
          @click="() => emit('permission-change', archive)"
        >
          <SafeIcon name="Lock" :size="16" class="mr-2" />
          权限
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="flex-1"
          @click="() => emit('move', archive)"
        >
          <SafeIcon name="Move" :size="16" class="mr-2" />
          移动
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="flex-1"
          @click="() => emit('download', archive)"
        >
          <SafeIcon name="Download" :size="16" class="mr-2" />
          下载
        </Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>
