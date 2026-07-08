
<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import SafeIcon from '@/components/common/SafeIcon.vue';
import { cn } from '@/lib/utils';

interface PaginationProps {
  current: number;
  total: number;
  pageSize: number;
}

const props = withDefaults(defineProps<PaginationProps>(), {
  current: 1,
  total: 0,
  pageSize: 20
});

const emit = defineEmits<{
  (e: 'update:current', value: number): void;
  (e: 'change', value: number): void;
}>();

const totalPages = computed(() => {
  const pages = Math.ceil((props.total || 0) / (props.pageSize || 20));
  return pages > 0 ? pages : 1;
});

const rangeStart = computed(() => (props.current - 1) * props.pageSize + 1);
const rangeEnd = computed(() => Math.min(props.current * props.pageSize, props.total));

const pages = computed(() => {
  const current = props.current;
  const total = totalPages.value;
  const delta = 2; // Number of pages to show around current
  const items: (number | string)[] = [];

  if (total <= 7) {
    for (let i = 1; i <= total; i++) items.push(i);
  } else {
    items.push(1);
    if (current > delta + 2) items.push('ellipsis-start');
    
    const start = Math.max(2, current - delta);
    const end = Math.min(total - 1, current + delta);
    
    for (let i = start; i <= end; i++) items.push(i);
    
    if (current < total - delta - 1) items.push('ellipsis-end');
    items.push(total);
  }
  return items;
});

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value || page === props.current) return;
  emit('update:current', page);
  emit('change', page);
};
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-4 py-4 w-full">
    <!-- 数据摘要 -->
    <div class="text-sm text-muted-foreground whitespace-nowrap shrink-0">
      显示 {{ total > 0 ? rangeStart : 0 }} - {{ rangeEnd }} 条，共 {{ total }} 条记录
    </div>

    <!-- 分页控制区 -->
    <div class="flex-1 min-w-0 flex items-center justify-end gap-1.5">
      <Button
        variant="outline"
        size="icon"
        class="h-9 w-9"
        :disabled="current === 1"
        @click="goToPage(current - 1)"
      >
        <SafeIcon name="ChevronLeft" :size="16" />
      </Button>

      <div class="flex items-center gap-1">
        <template v-for="(page, index) in pages" :key="index">
          <template v-if="typeof page === 'number'">
            <Button
              variant="outline"
              size="sm"
              :class="cn(
                'h-9 min-w-[36px] px-2',
                current === page ? 'bg-primary text-primary-foreground border-primary hover:bg-primary-hover active:bg-primary' : 'hover:bg-muted'
              )"
              @click="goToPage(page)"
            >
              {{ page }}
            </Button>
          </template>
          <template v-else>
            <div class="flex h-9 w-9 items-center justify-center text-muted-foreground select-none">
              <SafeIcon name="Ellipsis" :size="14" />
            </div>
          </template>
        </template>
      </div>

      <Button
        variant="outline"
        size="icon"
        class="h-9 w-9"
        :disabled="current === totalPages"
        @click="goToPage(current + 1)"
      >
        <SafeIcon name="ChevronRight" :size="16" />
      </Button>
    </div>
  </div>
</template>
