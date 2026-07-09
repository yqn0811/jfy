<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
import { currentRoute } from '@/router'
</script>

<template>
  <component :is="currentRoute.component" />
  <Toaster />
</template>
