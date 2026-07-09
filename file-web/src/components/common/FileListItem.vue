
<script setup lang="ts">
import { computed } from 'vue';
import { Progress } from '@/components/ui/progress';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { cn } from '@/lib/utils';

interface Props {
  fileName: string;
  fileSize: number;
  status: 'pending' | 'uploading' | 'success' | 'error';
  progress?: number;
  errorMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  progress: 0,
});

/**
 * 格式化文件大小
 */
const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const statusIcon = computed(() => {
  switch (props.status) {
    case 'pending':
      return { name: 'Clock', class: 'text-muted-foreground' };
    case 'uploading':
      return { name: 'Loader2', class: 'text-primary animate-spin' };
    case 'success':
      return { name: 'CheckCircle2', class: 'text-[hsl(var(--success))]' };
    case 'error':
      return { name: 'XCircle', class: 'text-destructive' };
    default:
      return { name: 'File', class: 'text-muted-foreground' };
  }
});

const fileIcon = computed(() => {
  const ext = props.fileName.split('.').pop()?.toLowerCase();
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext || '')) return 'Image';
  if (['pdf'].includes(ext || '')) return 'FileText';
  if (['zip', 'rar', '7z'].includes(ext || '')) return 'Archive';
  if (['mp4', 'mov', 'avi'].includes(ext || '')) return 'Video';
  return 'File';
});
</script>

<template>
  <div class="surface-base mb-3 overflow-hidden">
    <div class="flex items-center gap-4 p-4">
      <!-- 文件类型图标 -->
      <div class="w-10 h-10 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
        <SafeIcon :name="fileIcon" :size="20" class="text-muted-foreground" />
      </div>

      <!-- 文件信息 -->
      <div class="flex-1 min-w-0 flex flex-col gap-0.5">
        <div class="flex items-center justify-between gap-2">
          <span class="text-item-title truncate" :title="fileName">
            {{ fileName }}
          </span>
          <div class="flex items-center gap-3 shrink-0">
            <!-- 状态图标 -->
            <SafeIcon 
              :name="statusIcon.name" 
              :size="18" 
              :class="statusIcon.class" 
            />
            <!-- 操作插槽 -->
            <div class="flex items-center gap-1">
              <slot name="actions" />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between text-caption">
          <span>{{ formatFileSize(fileSize) }}</span>
          
          <span v-if="status === 'uploading'" class="text-primary font-medium">
            {{ progress }}%
          </span>
          <span v-else-if="status === 'error'" class="text-destructive truncate max-w-[200px]">
            {{ errorMessage || '上传失败' }}
          </span>
          <span v-else-if="status === 'success'" class="text-[hsl(var(--success))] font-medium">
            已完成
          </span>
          <span v-else-if="status === 'pending'">
            等待上传
          </span>
        </div>
      </div>
    </div>

    <!-- 上传进度条 -->
    <div v-if="status === 'uploading'" class="px-4 pb-3">
      <Progress :model-value="progress" class="h-1" />
    </div>
  </div>
</template>

<style scoped>
.surface-base {
  @apply transition-all duration-200;
}

.surface-base:hover {
  @apply border-primary/20 bg-accent/30;
}
</style>
