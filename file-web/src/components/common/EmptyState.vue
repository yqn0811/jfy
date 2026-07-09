
<script setup lang="ts">
/**
 * EmptyState 空状态组件
 * 用于在页面无数据、无搜索结果或权限受限时，提供友好的视觉反馈和后续操作引导。
 */
import SafeIcon from '@/components/common/SafeIcon.vue';

interface Props {
  /** 图标名称，需符合 PascalCase 格式，默认使用 FileQuestion */
  icon?: string;
  /** 核心标题文本 */
  title: string;
  /** 补充描述文本，可用于说明原因或提供操作建议 */
  description?: string;
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'FileQuestion',
});
</script>

<template>
  <div class="empty-state">
    <!-- 图标区域 -->
    <div class="mb-4 text-muted-foreground/40">
      <SafeIcon 
        :name="icon" 
        :size="64" 
        :stroke-width="1.5" 
      />
    </div>

    <!-- 文本区域 -->
    <div class="max-w-md mx-auto space-y-2">
      <h3 class="text-xl font-semibold text-foreground tracking-tight">
        {{ title }}
      </h3>
      <p v-if="description" class="text-sm text-muted-foreground leading-relaxed">
        {{ description }}
      </p>
    </div>

    <!-- 操作按钮插槽 -->
    <div v-if="$slots.actions" class="mt-6 flex flex-wrap items-center justify-center gap-3">
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
/* 继承 global.css 中的 .empty-state 样式 */
.empty-state {
  min-height: 320px;
  width: 100%;
}
</style>
