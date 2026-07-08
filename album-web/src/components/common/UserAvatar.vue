
<script setup lang="ts">
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { cn } from '@/lib/utils'

interface Props {
  src?: string
  name: string
  size?: 'sm' | 'md' | 'lg'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  src: '',
  size: 'md',
  class: '',
})

/**
 * Get the initials from the name
 * e.g., "Zhang San" -> "ZS", "李四" -> "李"
 */
const initials = computed(() => {
  if (!props.name) return '?'
  const trimmed = props.name.trim()
  if (/^[\u4e00-\u9fa5]+$/.test(trimmed)) {
    // Chinese characters: take the last character
    return trimmed.slice(-1)
  }
  // Latin characters: take first letter of each word
  return trimmed
    .split(/\s+/)
    .map((word) => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const sizeClasses = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-16 w-16 text-xl',
}
</script>

<template>
  <Avatar :class="cn(sizeClasses[props.size], 'shrink-0 select-none bg-muted', props.class)">
    <AvatarImage
      v-if="props.src"
      :src="props.src"
      :alt="props.name"
      class="aspect-square h-full w-full object-cover"
    />
    <AvatarFallback
      class="flex h-full w-full items-center justify-center rounded-full bg-primary/10 font-medium text-primary uppercase"
    >
      {{ initials }}
    </AvatarFallback>
  </Avatar>
</template>
