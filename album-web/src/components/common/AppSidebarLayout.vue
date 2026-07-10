
<script setup lang="ts">
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
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
      <div class="border-b border-border bg-background px-4 py-3 md:hidden">
        <SidebarTrigger class="h-9 w-9" />
      </div>
      <div class="flex-1 overflow-y-auto min-h-0">
        <slot />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
