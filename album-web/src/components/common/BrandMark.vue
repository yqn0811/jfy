<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  size?: number;
}

const props = withDefaults(defineProps<Props>(), {
  size: 32,
});

const markStyle = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  borderRadius: `${Math.max(6, Math.round(props.size * 0.17))}px`,
}));

const innerStyle = computed(() => ({
  width: `${Math.round(props.size * 0.58)}px`,
  height: `${Math.round(props.size * 0.37)}px`,
  borderRadius: `${Math.max(4, Math.round(props.size * 0.09))}px`,
}));
</script>

<template>
  <span class="brand-mark" :style="markStyle" aria-hidden="true">
    <span class="brand-mark-inner" :style="innerStyle" />
  </span>
</template>

<style scoped>
.brand-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(180deg, #ffbc00 0%, #ffd333 100%);
  box-shadow: 0 6px 16px rgba(236, 160, 0, 0.24);
}

.brand-mark-inner {
  display: block;
  background: rgba(255, 255, 255, 0.34);
}
</style>
