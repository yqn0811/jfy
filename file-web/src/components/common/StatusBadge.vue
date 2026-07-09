
<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  status: 'draft' | 'collecting' | 'pending_review' | 'need_resubmit' | 'approved' | 'archived' | 'expired';
  size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md'
});

const statusConfig = {
  draft: { label: '草稿', className: 'status-badge-draft' },
  collecting: { label: '进行中', className: 'status-badge-collecting' },
  pending_review: { label: '待审核', className: 'status-badge-pending' },
  need_resubmit: { label: '待重传', className: 'status-badge-need-resubmit' },
  approved: { label: '已通过', className: 'status-badge-approved' },
  archived: { label: '已归档', className: 'status-badge-archived' },
  expired: { label: '已过期', className: 'status-badge-expired' }
} as const;

const config = computed(() => statusConfig[props.status] || { label: props.status, className: 'status-badge-draft' });

const sizeClasses = computed(() => {
  if (props.size === 'sm') return 'px-2 py-0.5 text-[10px]';
  return 'px-2.5 py-1 text-xs';
});
</script>

<template>
  <span :class="cn('status-badge-base shrink-0', config.className, sizeClasses)">
    {{ config.label }}
  </span>
</template>
