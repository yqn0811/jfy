<script setup lang="ts">
import StandardLayout from '@/layouts/StandardLayout.vue'
import SpaceArchiveContent from '@/components/space-archive/SpaceArchiveContent.vue'
import { currentRouteState } from '@/navigation'
</script>

<template>
  <StandardLayout>
    <SpaceArchiveContent :current-path="currentRouteState.path" />
  </StandardLayout>
</template>
