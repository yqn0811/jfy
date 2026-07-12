
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/common/EmptyState.vue'
import { navigateToInternal } from '@/navigation'

const handleGoHome = () => {
  navigateToInternal('./share-home')
}
</script>

<template>
  <div class="page-container py-16">
    <EmptyState
      icon="Lock"
      title="暂时无法访问"
      description="该分类不存在或暂未公开"
      class="py-20"
    >
      <Button 
        variant="default"
        @click="handleGoHome"
      >
        返回主页
      </Button>
    </EmptyState>
  </div>
</template>
