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
  selectedIds?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  categoryNameMap: () => ({}),
  sortKey: '',
  sortDirection: 'asc',
  selectedIds: () => [],
})

const emit = defineEmits<{
  (e: 'sort', key: string): void
  (e: 'edit', productId: string): void
  (e: 'delete', product: ProductData): void
  (e: 'batch-upload', productId: string): void
  (e: 'share', product: ProductData): void
  (e: 'toggle-select', productId: string): void
}>()

const isEmpty = computed(() => props.products.length === 0)
const selectedSet = computed(() => new Set(props.selectedIds))

</script>

<template>
  <div v-if="isEmpty" class="py-16">
    <EmptyState
      icon="Package"
      title="暂无产品"
      description="当前筛选条件下没有产品"
    />
  </div>

  <div v-else class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    <article
      v-for="product in products"
      :key="product.id"
      :class="[
        'group min-w-0 overflow-hidden rounded-xl border bg-card shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md',
        selectedSet.has(product.id) ? 'border-primary ring-2 ring-primary/30' : 'border-border'
      ]"
    >
      <div class="relative aspect-[5/4] overflow-hidden bg-muted">
        <img
          v-if="product.coverUrl"
          :src="product.coverUrl"
          :alt="product.name"
          class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
        />
        <div v-else class="flex h-full w-full items-center justify-center bg-muted">
          <SafeIcon name="Image" :size="36" class="text-muted-foreground/60" />
        </div>

        <button
          type="button"
          :aria-label="`选择${product.name || '未命名产品'}`"
          :class="[
            'absolute left-4 top-4 z-20 flex h-8 w-8 items-center justify-center rounded-md border-2 shadow-sm transition-colors',
            selectedSet.has(product.id)
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-white bg-white/95 hover:border-primary'
          ]"
          @click.stop="emit('toggle-select', product.id)"
        >
          <SafeIcon v-if="selectedSet.has(product.id)" name="Check" :size="18" />
        </button>

        <div
          :class="[
            'absolute right-4 top-4 z-20 rounded-full px-3 py-1 text-sm font-semibold shadow-sm',
            product.visibility === 'public'
              ? 'bg-emerald-50 text-emerald-600'
              : product.visibility === 'shared'
                ? 'bg-amber-50 text-amber-700'
                : 'bg-slate-50 text-slate-600'
          ]"
        >
          {{ product.visibility === 'public' ? '公开' : product.visibility === 'shared' ? '分享可见' : '私密' }}
        </div>

        <div class="absolute bottom-5 right-5 z-20 flex items-center gap-3 opacity-100">
          <Button
            variant="secondary"
            size="icon"
            class="h-12 w-12 rounded-full bg-black/35 text-white shadow-sm backdrop-blur hover:bg-black/50 hover:text-white"
            title="批量上传"
            @click="emit('batch-upload', product.id)"
          >
            <SafeIcon name="Upload" :size="20" />
          </Button>
          <Button
            variant="secondary"
            size="icon"
            class="h-12 w-12 rounded-full bg-black/35 text-white shadow-sm backdrop-blur hover:bg-black/50 hover:text-white"
            title="分享"
            @click="emit('share', product)"
          >
            <SafeIcon name="Share2" :size="20" />
          </Button>
          <Button
            variant="secondary"
            size="icon"
            class="h-12 w-12 rounded-full bg-black/35 text-white shadow-sm backdrop-blur hover:bg-black/50 hover:text-white"
            title="编辑"
            @click="emit('edit', product.id)"
          >
            <SafeIcon name="Edit2" :size="20" />
          </Button>
          <Button
            variant="secondary"
            size="icon"
            class="h-12 w-12 rounded-full bg-black/35 text-white shadow-sm backdrop-blur hover:bg-destructive hover:text-white"
            title="删除"
            @click="emit('delete', product)"
          >
            <SafeIcon name="Trash2" :size="20" />
          </Button>
        </div>
      </div>

      <div class="space-y-3 bg-card px-5 py-5">
        <div class="min-w-0 space-y-2">
          <h3 class="truncate text-xl font-semibold text-foreground">
            {{ product.name || '未命名产品' }}
          </h3>
          <p class="truncate text-base font-medium text-muted-foreground">
            {{ product.intro || '暂无描述' }}
          </p>
        </div>

        <div class="flex items-center justify-between text-base font-semibold text-muted-foreground">
          <span>花色图: {{ product.colorChartCount || 0 }}张</span>
          <span>详情图: {{ product.detailChartCount || 0 }}张</span>
        </div>
      </div>
    </article>
  </div>
</template>
