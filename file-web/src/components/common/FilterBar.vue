
<script setup lang="ts">
import { cn } from '@/lib/utils'

/**
 * FilterBar Component
 * 
 * A structured container for list filtering and action controls.
 * Adheres to the layout requirements for dashboard toolbars:
 * - Left side: Filters (Selects, Date pickers, etc.)
 * - Right side: Batch actions (Export, Delete, etc.)
 */
</script>

<template>
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
    <!-- Left: Filter Inputs -->
    <div 
      class="flex w-full flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3"
      aria-label="筛选区域"
    >
      <slot />
    </div>

    <!-- Right: Actions -->
    <div 
      class="flex w-full flex-wrap items-center gap-2 shrink-0 lg:w-auto lg:justify-end"
      aria-label="操作按钮"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
/* Ensure slotted filter controls have a consistent base height if they are standard inputs */
:deep(.w-select-trigger), 
:deep(.w-input) {
  @apply h-9;
}

/* Enforce the explicit width rule from layout guidelines via parent context if needed, 
   but specific widths should be applied to individual components passed to the slot. */
</style>
