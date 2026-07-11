<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface Props {
  src?: string
  candidates?: string[]
  alt?: string
  class?: string
  imgClass?: string
  loading?: 'eager' | 'lazy'
  decoding?: 'sync' | 'async' | 'auto'
}

const props = withDefaults(defineProps<Props>(), {
  src: '',
  candidates: () => [],
  alt: '',
  class: '',
  imgClass: '',
  loading: 'lazy',
  decoding: 'async',
})

const failedUrls = ref<Set<string>>(new Set())

const sources = computed(() => {
  const result: string[] = []
  const seen = new Set<string>()
  ;[props.src, ...props.candidates].forEach((value) => {
    const url = String(value || '').trim()
    if (!url || seen.has(url)) return
    seen.add(url)
    result.push(url)
  })
  return result
})

const currentSrc = computed(() => sources.value.find(url => !failedUrls.value.has(url)) || '')

watch(sources, () => {
  failedUrls.value = new Set()
})

const handleError = () => {
  if (!currentSrc.value) return
  const next = new Set(failedUrls.value)
  next.add(currentSrc.value)
  failedUrls.value = next
}
</script>

<template>
  <img
    v-if="currentSrc"
    :src="currentSrc"
    :alt="alt"
    :class="[props.class, props.imgClass]"
    :loading="loading"
    :decoding="decoding"
    @error="handleError"
  />
  <slot v-else />
</template>
