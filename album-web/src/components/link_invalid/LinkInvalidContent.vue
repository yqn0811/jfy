
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { navigateToInternal } from '@/navigation'

const handleReturnHome = () => {
  navigateToInternal('./share-home')
}
</script>

<template>
  <div class="w-full max-w-md mx-auto text-center space-y-6">
    <!-- Error Icon -->
    <div class="flex justify-center">
      <div class="w-24 h-24 bg-destructive/10 rounded-full flex items-center justify-center">
        <SafeIcon 
          name="AlertCircle" 
          :size="48" 
          class="text-destructive"
          :stroke-width="1.5"
        />
      </div>
    </div>

    <!-- Error Title -->
    <div class="space-y-2">
      <h1 class="text-3xl font-bold text-foreground">
        链接已失效
      </h1>
      <p class="text-base text-muted-foreground leading-relaxed">
        抱歉，您访问的分享链接已被重置或过期。请联系分享者获取最新链接。
      </p>
    </div>

    <!-- Error Details (Optional context) -->
    <div class="bg-muted/50 border border-border rounded-lg p-4 text-left">
      <p class="text-sm text-muted-foreground">
        <strong class="text-foreground">可能的原因：</strong>
      </p>
      <ul class="text-sm text-muted-foreground mt-2 space-y-1 list-disc list-inside">
        <li>分享链接已被分享者重置</li>
        <li>分享链接已过期</li>
        <li>分享内容已被删除</li>
      </ul>
    </div>

    <!-- Action Button -->
    <Button 
      size="lg" 
      class="w-full h-11 rounded-lg font-medium"
      @click="handleReturnHome"
    >
      <SafeIcon name="Home" :size="18" class="mr-2" />
      返回主页
    </Button>

    <!-- Secondary Help Text -->
    <p class="text-xs text-muted-foreground">
      如有疑问，请
      <a href="#" class="text-primary hover:underline transition-colors">
        联系客服
      </a>
      获取帮助
    </p>
  </div>
</template>

<style scoped>
/* Ensure proper spacing and alignment within the centered container */
:deep(.w-full) {
  @apply flex flex-col items-center justify-center;
}
</style>
