<script setup lang="ts">
import AppHeader from '@/components/common/AppHeader.vue'
import { currentRouteState } from '@/navigation'
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <AppHeader :current-path="currentRouteState.path" />

    <main class="page-body min-h-[calc(100vh-var(--header-height))]">
      <div class="app-shell">
        <slot />
      </div>
    </main>
  </div>
</template>
