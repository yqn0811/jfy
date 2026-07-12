<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'
import { navigateTo } from '@/navigation'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center page-body">
    <div class="text-center space-y-6 max-w-md">
      <div class="w-24 h-24 mx-auto rounded-full bg-primary/10 flex items-center justify-center">
        <SafeIcon name="Hammer" :size="48" class="text-primary" />
      </div>
      <h1 class="text-page-title">页面建设中</h1>
      <p class="text-body text-muted-foreground">
        该功能正在紧锣密鼓地开发中，敬请期待。我们会尽快为您呈现更完善的产品体验。
      </p>
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-[--radius] bg-primary px-6 py-3 text-sm font-medium text-primary-foreground interactive hover:bg-[hsl(var(--primary-hover))]"
        @click="navigateTo('/share-home')"
      >
        返回主页
      </button>
    </div>
  </div>
</template>
