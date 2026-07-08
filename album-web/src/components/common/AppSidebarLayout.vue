
<script setup lang="ts">
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import AppSidebar from '@/components/common/AppSidebar.vue';

interface Props {
  currentPath?: string;
}

defineProps<Props>();
</script>

<template>
  <SidebarProvider class="h-full overflow-hidden">
    <!-- Pass currentPath to AppSidebar to ensure correct active state on hydration -->
    <AppSidebar :current-path="currentPath" />
    <SidebarInset class="flex flex-col overflow-hidden bg-background">
      <div class="flex-1 overflow-y-auto min-h-0">
        <slot />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
