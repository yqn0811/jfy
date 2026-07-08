<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import type { ProductData } from '@/data/ProductData'

interface Props {
  products: ProductData[]
  categoryNameMap?: Record<string, string>
  sortKey?: string
  sortDirection?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<Props>(), {
  categoryNameMap: () => ({}),
  sortKey: '',
  sortDirection: 'asc',
})

const emit = defineEmits<{
  (e: 'sort', key: string): void
  (e: 'edit', productId: string): void
  (e: 'delete', product: ProductData): void
  (e: 'batch-upload', productId: string): void
  (e: 'share', product: ProductData): void
}>()

const isEmpty = computed(() => props.products.length === 0)

</script>

<template>
  <div v-if="isEmpty" class="py-16">
    <EmptyState
      icon="Package"
      title="暂无产品"
      description="当前筛选条件下没有产品"
    />
  </div>

  <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
    <article
      v-for="product in products"
      :key="product.id"
      class="product-card group flex min-w-0 flex-col"
    >
      <div class="relative aspect-[4/3] overflow-hidden bg-muted">
        <img
          v-if="product.coverUrl"
          :src="product.coverUrl"
          :alt="product.name"
          class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
        />
        <div v-else class="flex h-full w-full items-center justify-center bg-muted">
          <SafeIcon name="Image" :size="36" class="text-muted-foreground/60" />
        </div>

        <div class="absolute left-3 top-3 flex h-5 w-5 items-center justify-center rounded border border-border bg-background/90 shadow-sm">
          <SafeIcon name="Check" :size="13" class="opacity-0" />
        </div>
      </div>

      <div class="flex flex-1 flex-col gap-3 p-4">
        <div class="min-w-0">
          <h3 class="truncate text-base font-semibold text-foreground">
            {{ product.name || '未命名产品' }}
          </h3>
          <p class="mt-1 line-clamp-1 text-sm text-muted-foreground">
            {{ product.intro || '暂无描述' }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground">
          <span>花色图：{{ product.colorChartCount || 0 }} 张</span>
          <span class="text-right">详情图：{{ product.detailChartCount || 0 }} 张</span>
        </div>

        <div class="mt-auto grid grid-cols-4 gap-2 border-t border-border pt-3">
          <Button
            variant="ghost"
            size="sm"
            class="h-9 min-w-0 gap-2 px-2 text-xs"
            @click="emit('edit', product.id)"
          >
            <SafeIcon name="Edit2" :size="14" />
            <span>编辑</span>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            class="h-9 min-w-0 gap-2 px-2 text-xs"
            @click="emit('batch-upload', product.id)"
          >
            <SafeIcon name="Upload" :size="14" />
            <span>上传</span>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            class="h-9 min-w-0 gap-2 px-2 text-xs"
            @click="emit('share', product)"
          >
            <SafeIcon name="Share2" :size="14" />
            <span>分享</span>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            class="h-9 min-w-0 gap-2 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
            @click="emit('delete', product)"
          >
            <SafeIcon name="Trash2" :size="14" />
            <span>删除</span>
          </Button>
        </div>
      </div>
    </article>
  </div>
</template>
