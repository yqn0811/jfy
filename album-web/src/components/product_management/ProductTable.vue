
<script setup lang="ts">
import { computed } from 'vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import SafeIcon from '@/components/common/SafeIcon.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import type { ProductData } from '@/data/ProductData'
import { cn } from '@/lib/utils'

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

const handleSort = (key: string) => {
  emit('sort', key)
}

const getSortIcon = (key: string) => {
  if (props.sortKey !== key) return 'ArrowUpDown'
  return props.sortDirection === 'asc' ? 'ArrowUp' : 'ArrowDown'
}

const SortableHeader = (key: string, label: string) => ({
  key,
  label,
  sortable: true,
})

const columns = [
  { key: 'name', label: '产品名称', width: 'w-40' },
  { key: 'categoryId', label: '所属分类', width: 'w-32' },
  { key: 'colorChartCount', label: '花色图', width: 'w-24' },
  { key: 'detailChartCount', label: '详情图', width: 'w-24' },
  { key: 'visibility', label: '可见性', width: 'w-28' },
  { key: 'updatedAt', label: '更新时间', width: 'w-40' },
  { key: 'actions', label: '操作', width: 'w-32' },
]
</script>

<template>
  <div class="overflow-x-auto">
    <Table class="w-full">
      <TableHeader class="bg-muted/30 sticky top-0 z-10">
        <TableRow class="border-b border-border hover:bg-transparent">
          <TableHead
            v-for="col in columns"
            :key="col.key"
            :class="cn(col.width, 'h-12 px-4 text-left font-semibold text-xs uppercase tracking-wider text-muted-foreground whitespace-nowrap')"
          >
            <div
              v-if="col.key !== 'actions'"
              class="flex items-center gap-2 cursor-pointer hover:text-foreground transition-colors"
              @click="handleSort(col.key)"
            >
              {{ col.label }}
              <SafeIcon
                :name="getSortIcon(col.key)"
                :size="14"
                :class="cn(
                  'transition-opacity',
                  sortKey === col.key ? 'opacity-100' : 'opacity-0 group-hover:opacity-50'
                )"
              />
            </div>
            <div v-else>{{ col.label }}</div>
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow
          v-for="product in products"
          :key="product.id"
          class="border-b border-border hover:bg-muted/20 transition-colors"
        >
          <!-- 产品名称 + 封面 -->
          <TableCell class="w-40 px-4 py-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded bg-muted flex items-center justify-center flex-shrink-0 overflow-hidden">
                <img
                  v-if="product.coverUrl"
                  :src="product.coverUrl"
                  :alt="product.name"
                  class="w-full h-full object-cover"
                />
                <SafeIcon v-else name="Image" :size="20" class="text-muted-foreground" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">
                  {{ product.name || '未命名产品' }}
                </p>
                <p class="text-xs text-muted-foreground">ID: {{ product.id }}</p>
              </div>
            </div>
          </TableCell>

          <!-- 所属分类 -->
          <TableCell class="w-32 px-4 py-3 text-sm">
            {{ product.categoryId ? (props.categoryNameMap[product.categoryId] || `分类 ${product.categoryId}`) : '-' }}
          </TableCell>

          <!-- 花色图数量 -->
          <TableCell class="w-24 px-4 py-3 text-sm text-center">
            {{ product.colorChartCount }} 张
          </TableCell>

          <!-- 详情图数量 -->
          <TableCell class="w-24 px-4 py-3 text-sm text-center">
            {{ product.detailChartCount }} 张
          </TableCell>

          <!-- 可见性 -->
          <TableCell class="w-28 px-4 py-3">
            <StatusBadge :status="product.visibility" />
          </TableCell>

          <!-- 更新时间 -->
          <TableCell class="w-40 px-4 py-3 text-sm text-muted-foreground">
            {{ product.updatedAt }}
          </TableCell>

          <!-- 操作 -->
          <TableCell class="w-32 px-4 py-3">
            <div class="flex items-center gap-1">
              <Button
                variant="ghost"
                size="sm"
                class="h-8 px-2 text-xs"
                @click="emit('edit', product.id)"
              >
                <SafeIcon name="Edit2" :size="14" class="mr-1" />
                编辑
              </Button>

              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                    <SafeIcon name="MoreVertical" :size="16" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                  <DropdownMenuItem
                    class="cursor-pointer"
                    @click="emit('batch-upload', product.id)"
                  >
                    <SafeIcon name="Upload" :size="14" class="mr-2" />
                    批量上传
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    class="cursor-pointer"
                    @click="emit('share', product)"
                  >
                    <SafeIcon name="Share2" :size="14" class="mr-2" />
                    分享产品
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    class="cursor-pointer text-destructive focus:text-destructive"
                    @click="emit('delete', product)"
                  >
                    <SafeIcon name="Trash2" :size="14" class="mr-2" />
                    删除
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
