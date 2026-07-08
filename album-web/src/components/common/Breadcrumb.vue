
<script setup lang="ts">
import {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbSeparator,
  BreadcrumbPage,
} from '@/components/ui/breadcrumb';
import SafeIcon from '@/components/common/SafeIcon.vue';

interface BreadcrumbItemData {
  label: string;
  href?: string;
}

const props = defineProps<{
  items: BreadcrumbItemData[];
}>();

const listItems = computed(() => props.items ?? []);
</script>

<template>
  <Breadcrumb class="mb-4">
    <BreadcrumbList>
      <template v-for="(item, index) in listItems" :key="index">
        <BreadcrumbItem>
          <!-- Last item or item without href is rendered as current page -->
          <template v-if="index === listItems.length - 1 || !item.href">
            <BreadcrumbPage class="font-medium text-foreground">
              {{ item.label }}
            </BreadcrumbPage>
          </template>
          
          <!-- Middle items with href -->
          <template v-else>
            <BreadcrumbLink 
              :href="item.href" 
              class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-1"
            >
              <SafeIcon 
                v-if="index === 0" 
                name="Home" 
                :size="14" 
                class="inline-block"
              />
              {{ item.label }}
            </BreadcrumbLink>
          </template>
        </BreadcrumbItem>

        <!-- Separator -->
        <BreadcrumbSeparator v-if="index < listItems.length - 1">
          <SafeIcon name="ChevronRight" :size="14" />
        </BreadcrumbSeparator>
      </template>
    </BreadcrumbList>
  </Breadcrumb>
</template>

<style scoped>
/* Ensure consistent alignment with global.css breadcrumb-separator style if needed */
:deep(.breadcrumb-separator) {
  @apply mx-1 opacity-60;
}
</style>
