
<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'

interface Props {
  modelValue: 'all' | 'subcategory' | 'product'
  subCategoryCount: number
  productCount: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: 'all' | 'subcategory' | 'product'): void
}>()
</script>

<template>
  <Tabs 
    :model-value="modelValue"
    @update:model-value="(val) => emit('update:modelValue', val as any)"
    class="w-full"
  >
    <TabsList class="grid w-full max-w-xs grid-cols-3 bg-muted">
      <TabsTrigger value="all">全部</TabsTrigger>
      <TabsTrigger value="subcategory">
        子分类 ({{ subCategoryCount }})
      </TabsTrigger>
      <TabsTrigger value="product">
        产品 ({{ productCount }})
      </TabsTrigger>
    </TabsList>
  </Tabs>
</template>
