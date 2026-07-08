
<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import SafeIcon from '@/components/common/SafeIcon.vue';

interface Props {
  placeholder?: string;
  modelValue?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '搜索您感兴趣的内容...',
  modelValue: '',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'search', value: string): void;
}>();

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
};

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    emit('search', props.modelValue);
  }
};
</script>

<template>
  <div class="relative w-full max-w-sm group">
    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
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
      class="pl-10 h-10 bg-muted/50 border-transparent focus:bg-card focus:border-primary transition-all duration-200"
      @input="handleInput"
      @keydown="handleKeyDown"
    />
  </div>
</template>

<style scoped>
/* 确保输入框在不同状态下的视觉一致性 */
:deep(.input) {
  @apply shadow-none;
}
</style>
