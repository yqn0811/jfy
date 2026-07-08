
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  imageName: string
  isLoadingOriginal: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'view-original'): void
  (e: 'download'): void
  (e: 'close'): void
}>()
</script>

<template>
  <div class="h-16 border-b border-white/10 bg-black/50 backdrop-blur-sm flex items-center justify-between px-6 shrink-0">
    <!-- Left: Image Name -->
    <div class="flex-1 min-w-0">
      <p class="text-white text-sm font-medium truncate">
        {{ imageName || '图片预览' }}
      </p>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center gap-3 ml-4">
      <!-- View Original Button -->
      <Button
        variant="ghost"
        size="sm"
        class="text-white hover:bg-white/10 h-9 px-4 text-xs font-medium"
        :disabled="isLoadingOriginal"
        @click="emit('view-original')"
      >
        <SafeIcon
          v-if="!isLoadingOriginal"
          name="Maximize2"
          :size="16"
          class="mr-2"
        />
        <SafeIcon
          v-else
          name="Loader2"
          :size="16"
          class="mr-2 animate-spin"
        />
        {{ isLoadingOriginal ? '正在加载原图...' : '查看原图' }}
      </Button>

      <!-- Download Button -->
      <Button
        variant="ghost"
        size="sm"
        class="text-white hover:bg-white/10 h-9 px-4 text-xs font-medium"
        @click="emit('download')"
      >
        <SafeIcon name="Download" :size="16" class="mr-2" />
        下载
      </Button>

      <!-- Close Button -->
      <Button
        variant="ghost"
        size="sm"
        class="text-white hover:bg-white/10 h-9 w-9 p-0"
        @click="emit('close')"
      >
        <SafeIcon name="X" :size="20" />
      </Button>
    </div>
  </div>
</template>
