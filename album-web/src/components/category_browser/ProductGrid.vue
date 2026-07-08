
<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { ProductData } from '@/data/ProductData'

interface Props {
  products: ProductData[]
  targetUserId?: string
  shareCode?: string
}

const props = defineProps<Props>()

const handleNavigate = (productId: string) => {
  const params = new URLSearchParams({ productId })
  if (props.shareCode) params.set('code', props.shareCode)
  else if (props.targetUserId) params.set('uid', props.targetUserId)
  window.location.href = `./product-detail.html?${params.toString()}`
}
</script>

<template>
  <div v-if="products.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <Card 
      v-for="product in products"
      :key="product.id"
      class="product-card cursor-pointer"
      @click="handleNavigate(product.id)"
    >
      <CardContent class="p-0">
        <div class="aspect-square overflow-hidden bg-muted">
          <img 
            :src="product.coverUrl"
            :alt="product.name"
            class="w-full h-full object-cover"
          />
        </div>
        <div class="p-4 space-y-2">
          <h3 class="text-item-title font-medium truncate">
            {{ product.name || '未命名产品' }}
          </h3>
          <div class="flex items-center justify-between text-caption text-muted-foreground">
            <span v-if="product.colorChartCount > 0">
              花色图 {{ product.colorChartCount }} 张
            </span>
            <span v-if="product.detailChartCount > 0">
              详情图 {{ product.detailChartCount }} 张
            </span>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
