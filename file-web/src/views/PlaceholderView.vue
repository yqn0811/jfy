<script setup lang="ts">
import { Package } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { navigateTo } from '@/navigation'
</script>

<template>
  <div class="page-body flex min-h-screen items-center justify-center">
    <div class="page-container flex justify-center">
      <div class="max-w-md text-center">
      <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary">
        <Package class="h-10 w-10" />
      </div>
      <h1 class="mb-4 text-page-title">页面建设中</h1>
      <p class="mb-8 text-body text-muted-foreground">
        该功能正在紧张开发中，我们会尽快上线。感谢您的耐心等待！
      </p>
      <Button @click="navigateTo('/workbench')">
        返回工作台
      </Button>
    </div>
    </div>
  </div>
</template>
