
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  label: string
  value: string | number
  icon: string
  color?: string
}

withDefaults(defineProps<Props>(), {
  color: 'text-primary',
})
</script>

<template>
  <div class="stat-card">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="stat-card-label">{{ label }}</p>
        <p class="stat-card-value">{{ value }}</p>
      </div>
      <div class="w-12 h-12 bg-muted rounded-lg flex items-center justify-center shrink-0">
        <SafeIcon :name="icon" :size="24" :class="color" />
      </div>
    </div>
  </div>
</template>
