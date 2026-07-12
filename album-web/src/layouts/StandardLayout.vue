<script setup lang="ts">
import CommonHeader from '@/components/common/CommonHeader.vue'
import { currentRouteState } from '@/navigation'
</script>

<template>
  <div class="flex flex-col h-screen overflow-hidden bg-background">
    <CommonHeader :current-path="currentRouteState.path" />

    <main class="flex-1 overflow-y-auto min-h-0">
      <div class="page-container">
        <slot />
      </div>

      <footer class="border-t border-border bg-card/50 py-8 px-8">
        <div class="page-container flex flex-col gap-4 text-caption md:flex-row md:justify-between md:items-center">
          <div>© 2026 家纺云相册. 版权所有</div>
          <div class="flex gap-6">
            <a href="#" class="hover:text-primary">服务条款</a>
            <a href="#" class="hover:text-primary">隐私政策</a>
            <a href="#" class="hover:text-primary">联系客服</a>
          </div>
        </div>
      </footer>
    </main>
  </div>
</template>
