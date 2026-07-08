
<script setup lang="ts">
interface Props {
  currentIndex: number
  totalImages: number
  imageType: 'colorChart' | 'detailChart'
}

defineProps<Props>()
</script>

<template>
  <div class="h-12 border-t border-white/10 bg-black/50 backdrop-blur-sm flex items-center justify-center shrink-0">
    <p class="text-white text-sm font-medium">
      {{ currentIndex }} / {{ totalImages }}
    </p>
  </div>
</template>
