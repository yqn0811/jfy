
<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { CategoryVO } from '@/data/CategoryService'

interface Props {
  categories: CategoryVO[]
  targetUserId?: string
  shareCode?: string
}

const props = defineProps<Props>()

const handleNavigate = (categoryId: string) => {
  const params = new URLSearchParams({ categoryId })
  if (props.shareCode) params.set('code', props.shareCode)
  else if (props.targetUserId) params.set('uid', props.targetUserId)
  window.location.href = `./category?${params.toString()}`
}
</script>

<template>
  <div v-if="categories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <Card 
      v-for="category in categories"
      :key="category.id"
      class="product-card cursor-pointer"
      @click="handleNavigate(category.id)"
    >
      <CardContent class="p-0">
        <div class="aspect-square overflow-hidden bg-muted">
          <img 
            :src="category.coverUrl"
            :alt="category.name"
            class="w-full h-full object-cover"
          />
        </div>
        <div class="p-4 space-y-2">
          <h3 class="text-item-title font-medium truncate">
            {{ category.name }}
          </h3>
          <p class="text-caption text-muted-foreground">
            {{ category.productCount }} 个产品
          </p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
