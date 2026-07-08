
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  progress: number
  isComplete?: boolean
}

withDefaults(defineProps<Props>(), {
  isComplete: false
})
</script>

<template>
  <div class="surface-base card-padding space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-section-title">上传进度</h3>
      <span class="text-2xl font-bold text-primary">{{ progress }}%</span>
    </div>

    <div class="w-full h-3 bg-muted rounded-full overflow-hidden">
      <div
        class="h-full bg-gradient-to-r from-primary to-accent transition-all duration-500"
        :style="{ width: `${progress}%` }"
      />
    </div>

    <div v-if="isComplete" class="flex items-center gap-2 p-3 bg-primary/10 rounded-lg border border-primary/20">
      <SafeIcon name="CheckCircle2" :size="20" class="text-primary shrink-0" />
      <div>
        <p class="text-sm font-medium text-primary">上传完成</p>
        <p class="text-xs text-primary/70">所有图片已成功上传</p>
      </div>
    </div>
  </div>
</template>
