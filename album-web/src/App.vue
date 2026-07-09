<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'
import { currentRoute } from '@/router'
</script>

<template>
  <component :is="currentRoute.layout" v-if="currentRoute.layout">
    <component :is="currentRoute.component" v-bind="currentRoute.props" />
  </component>
  <component :is="currentRoute.component" v-else v-bind="currentRoute.props" />
  <Toaster />
</template>
