<script setup lang="ts">
import StandardLayout from '@/layouts/StandardLayout.vue'
import DeliveryRecordList from '@/components/delivery_records/DeliveryRecordList.vue'
</script>

<template>
  <StandardLayout>
    <main class="page-body">
      <div class="app-shell">
        <DeliveryRecordList />
      </div>
    </main>
  </StandardLayout>
</template>
