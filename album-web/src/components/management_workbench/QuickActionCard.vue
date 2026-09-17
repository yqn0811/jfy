
<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  title: string
  description: string
  icon: string
  href: string
}

defineProps<Props>()

const handleClick = (href: string) => {
  window.location.href = href
}
</script>

<template>
  <Card
    class="product-card cursor-pointer hover:shadow-card transition-shadow"
    @click="handleClick(href)"
  >
    <CardHeader class="pb-3">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <CardTitle class="text-base">{{ title }}</CardTitle>
          <CardDescription class="text-xs mt-1">{{ description }}</CardDescription>
        </div>
        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
          <SafeIcon :name="icon" :size="20" class="text-primary" />
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <div class="flex items-center text-xs text-primary font-medium">
        进入
        <SafeIcon name="ArrowRight" :size="14" class="ml-1" />
      </div>
    </CardContent>
  </Card>
</template>
