<script setup lang="ts">
import ManagementHeader from '@/components/common/ManagementHeader.vue'
import AppSidebarLayout from '@/components/common/AppSidebarLayout.vue'
import AuthGate from '@/components/common/AuthGate.vue'
import { currentRouteState } from '@/navigation'
</script>

<template>
  <div class="flex flex-col h-screen overflow-hidden">
    <ManagementHeader />
    <div class="flex-1 min-h-0">
      <AuthGate>
        <AppSidebarLayout :current-path="currentRouteState.path">
          <slot />
        </AppSidebarLayout>
      </AuthGate>
    </div>
  </div>
</template>
