<script setup lang="ts">
import StandardLayout from '@/layouts/StandardLayout.vue'
import CreateCollectionTaskForm from '@/components/create-collection-task/CreateCollectionTaskForm.vue'
</script>

<template>
  <StandardLayout>
    <div class="page-body">
      <div class="create-collection-shell">
        <CreateCollectionTaskForm />
      </div>
    </div>
  </StandardLayout>
</template>

<style scoped>
.create-collection-shell {
  width: min(100%, 1040px);
  margin: 0 auto;
}
</style>
