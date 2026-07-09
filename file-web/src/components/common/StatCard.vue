
<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import SafeIcon from '@/components/common/SafeIcon.vue';

interface StatCardProps {
  label: string;
  value: string | number;
  icon?: string;
  trend?: 'up' | 'down';
  trendValue?: string;
  variant?: 'default' | 'success' | 'warning' | 'danger';
}

const props = withDefaults(defineProps<StatCardProps>(), {
  variant: 'default',
});

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'success':
      return 'border-l-4 border-l-[hsl(var(--success))]';
    case 'warning':
      return 'border-l-4 border-l-[hsl(var(--warning))]';
    case 'danger':
      return 'border-l-4 border-l-[hsl(var(--destructive))]';
    default:
      return 'border-l-4 border-l-primary';
  }
});

const trendIcon = computed(() => {
  if (props.trend === 'up') return 'TrendingUp';
  if (props.trend === 'down') return 'TrendingDown';
  return null;
});

const trendColorClass = computed(() => {
  if (props.trend === 'up') return 'text-[hsl(var(--success))]';
  if (props.trend === 'down') return 'text-[hsl(var(--destructive))]';
  return 'text-muted-foreground';
});
</script>

<template>
  <div :class="cn('surface-base card-padding flex flex-col justify-between h-full transition-all hover:shadow-md', variantClasses)">
    <div class="flex items-start justify-between mb-2">
      <span class="text-caption font-medium">{{ label }}</span>
      <div v-if="icon" class="text-muted-foreground opacity-50">
        <SafeIcon :name="icon" :size="20" />
      </div>
    </div>

    <div class="flex items-baseline gap-2 mt-auto">
      <h3 class="text-2xl font-bold tracking-tight text-foreground">
        {{ value }}
      </h3>
      
      <div v-if="trendValue" :class="cn('flex items-center gap-0.5 text-xs font-medium', trendColorClass)">
        <SafeIcon v-if="trendIcon" :name="trendIcon" :size="14" />
        <span>{{ trendValue }}</span>
      </div>
    </div>
  </div>
</template>
