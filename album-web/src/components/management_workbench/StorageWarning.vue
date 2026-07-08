
<script setup lang="ts">
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  used: number
  total: number
  status: 'warning' | 'insufficient'
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'click'): void
}>()

const percentage = computed(() => {
  return Math.round((props.used / props.total) * 100)
})
</script>

<template>
  <div class="capacity-warning cursor-pointer hover:bg-warning/15 transition-colors" @click="emit('click')">
    <div class="flex-1">
      <div class="flex items-center justify-between mb-2">
        <span class="font-medium">{{ status === 'insufficient' ? '存储空间不足' : '存储空间即将满满' }}</span>
        <span class="text-xs">{{ used }}MB / {{ total }}MB ({{ percentage }}%)</span>
      </div>
      <div class="w-full h-1.5 bg-warning/20 rounded-full overflow-hidden">
        <div
          class="h-full bg-warning transition-all"
          :style="{ width: `${Math.min(percentage, 100)}%` }"
        />
      </div>
      <p class="text-xs mt-2 opacity-80">{{ status === 'insufficient' ? '请立即升级套餐以继续使用' : '建议升级套餐以获得更多空间' }}</p>
    </div>
    <SafeIcon name="ChevronRight" :size="18" class="ml-4 shrink-0" />
  </div>
</template>
