
<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * Props definition for StatusBadge
 * status: The visibility status of a product or category
 * text: Optional override for the status text
 */
interface StatusBadgeProps {
  status: 'public' | 'private' | 'shared';
  text?: string;
}

const props = defineProps<StatusBadgeProps>();

/**
 * Mapping status to display text and CSS classes defined in global.css
 */
const statusConfig = {
  public: {
    label: '公开',
    class: 'status-badge-public',
  },
  private: {
    label: '私密',
    class: 'status-badge-private',
  },
  shared: {
    label: '分享可见',
    class: 'status-badge-shared',
  },
};

const displayLabel = computed(() => props.text || (statusConfig[props.status]?.label ?? '未知'));
const badgeClass = computed(() => statusConfig[props.status]?.class ?? '');
</script>

<template>
  <Badge
    variant="outline"
    :class="cn(
      'px-2 py-0.5 font-normal text-xs transition-none border-solid',
      badgeClass
    )"
  >
    {{ displayLabel }}
  </Badge>
</template>

<style scoped>
/* 
  Ensures that the StatusBadge maintains consistent styling 
  even when used inside different parent containers.
  The actual colors and borders are driven by class tokens 
  defined in global.css (e.g., .status-badge-public).
*/
</style>
