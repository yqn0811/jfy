
<script setup lang="ts">
import { Input } from '@/components/ui/input'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  placeholder?: string
  modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '搜索任务或记录...',
  modelValue: ''
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'search', value: string): void
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    emit('search', props.modelValue)
  }
}
</script>

<template>
  <div class="relative w-full sm:max-w-sm flex items-center">
    <div class="absolute left-3 flex items-center pointer-events-none text-muted-foreground/60">
      <SafeIcon 
        name="Search" 
        :size="18" 
        :stroke-width="2" 
      />
    </div>
    <Input
      type="text"
      :placeholder="placeholder"
      :value="modelValue"
      @input="handleInput"
      @keydown="handleKeyDown"
      class="pl-10 h-10 bg-background border-border focus-visible:ring-primary/30 text-sm"
    />
  </div>
</template>

<style scoped>
/* Ensure input group height consistency with other tools in filter bars */
:deep(input) {
  @apply transition-shadow;
}
</style>
