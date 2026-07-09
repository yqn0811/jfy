
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  title: string
  description: string
  icon: string
}

defineProps<Props>()

defineEmits<{
  (e: 'click'): void
}>()
</script>

<template>
  <div class="action-card" @click="$emit('click')">
    <div class="action-card-icon-wrapper">
      <SafeIcon :name="icon" :size="24" :stroke-width="2" />
    </div>
    <h3 class="action-card-title">{{ title }}</h3>
    <p class="action-card-description">{{ description }}</p>
  </div>
</template>
