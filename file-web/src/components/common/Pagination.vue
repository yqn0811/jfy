
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import SafeIcon from '@/components/common/SafeIcon.vue';

interface PaginationProps {
  currentPage: number;
  totalPages: number;
  pageSize?: number;
  total?: number;
}

const props = withDefaults(defineProps<PaginationProps>(), {
  pageSize: 10,
  total: 0,
});

const emit = defineEmits<{
  (e: 'page-change', page: number): void;
}>();

const handlePrevious = () => {
  if (props.currentPage > 1) {
    emit('page-change', props.currentPage - 1);
  }
};

const handleNext = () => {
  if (props.currentPage < props.totalPages) {
    emit('page-change', props.currentPage + 1);
  }
};

const handlePageClick = (page: number) => {
  if (page !== props.currentPage) {
    emit('page-change', page);
  }
};

// 计算显示的页码范围
const visiblePages = computed(() => {
  const range: (number | string)[] = [];
  const delta = 2; // 当前页前后显示的页数

  for (let i = 1; i <= props.totalPages; i++) {
    if (
      i === 1 ||
      i === props.totalPages ||
      (i >= props.currentPage - delta && i <= props.currentPage + delta)
    ) {
      if (range.length > 0) {
        const last = range[range.length - 1];
        if (typeof last === 'number' && i - last === 2) {
          range.push(i - 1);
        } else if (typeof last === 'number' && i - last > 2) {
          range.push('...');
        }
      }
      range.push(i);
    }
  }
  return range;
});
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-4 py-4 w-full">
    <!-- 数据摘要 -->
    <div class="text-caption whitespace-nowrap shrink-0">
      <span v-if="total > 0">
        共 {{ total }} 条记录，第 {{ currentPage }} / {{ totalPages }} 页
      </span>
      <span v-else>暂无记录</span>
    </div>

    <!-- 分页控制 -->
    <div class="flex-1 min-w-0 flex justify-end">
      <nav class="flex items-center gap-1" aria-label="Pagination">
        <!-- 上一页 -->
        <Button
          variant="outline"
          size="icon"
          class="h-9 w-9"
          :disabled="currentPage <= 1"
          @click="handlePrevious"
        >
          <SafeIcon name="ChevronLeft" :size="16" />
          <span class="sr-only">上一页</span>
        </Button>

        <!-- 页码 -->
        <div class="hidden sm:flex items-center gap-1 mx-1">
          <template v-for="(page, index) in visiblePages" :key="index">
            <template v-if="typeof page === 'number'">
              <Button
                size="sm"
                class="min-w-[36px] h-9"
                :variant="page === currentPage ? 'default' : 'ghost'"
                @click="handlePageClick(page)"
              >
                {{ page }}
              </Button>
            </template>
            <template v-else>
              <span class="px-2 text-muted-foreground">
                {{ page }}
              </span>
            </template>
          </template>
        </div>

        <!-- 移动端页码简易显示 -->
        <div class="sm:hidden px-3 text-sm font-medium">
          {{ currentPage }} / {{ totalPages }}
        </div>

        <!-- 下一页 -->
        <Button
          variant="outline"
          size="icon"
          class="h-9 w-9"
          :disabled="currentPage >= totalPages"
          @click="handleNext"
        >
          <SafeIcon name="ChevronRight" :size="16" />
          <span class="sr-only">下一页</span>
        </Button>
      </nav>
    </div>
  </div>
</template>
