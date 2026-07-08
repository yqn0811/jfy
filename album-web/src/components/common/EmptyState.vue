
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue';
import { cn } from '@/lib/utils';

interface Props {
  /**
   * 图标名称 (PascalCase)，来自 Lucide 图标库
   */
  icon: string;
  /**
   * 主要标题文字
   */
  title: string;
  /**
   * 辅助说明描述文字
   */
  description?: string;
  /**
   * 附加样式的类名
   */
  class?: string;
}

const props = defineProps<Props>();
</script>

<template>
  <div :class="cn('empty-state', props.class)">
    <!-- 媒体部分 -->
    <div class="flex items-center justify-center w-20 h-20 rounded-full bg-muted/50 mb-2">
      <SafeIcon 
        :name="props.icon" 
        :size="40" 
        :stroke-width="1.5" 
        class="text-muted-foreground/60"
      />
    </div>

    <!-- 文字部分 -->
    <div class="space-y-2 max-w-sm px-4">
      <h3 class="text-section-title text-foreground">
        {{ props.title }}
      </h3>
      <p v-if="props.description" class="text-caption">
        {{ props.description }}
      </p>
    </div>

    <!-- 操作插槽 -->
    <div v-if="$slots.default" class="pt-4 flex flex-wrap justify-center gap-3">
      <slot />
    </div>
  </div>
</template>

<style scoped>
/* 样式已在 global.css 中的 .empty-state 类中定义 */
</style>
