
<script setup lang="ts">
import { computed } from 'vue';
import SafeIcon from '@/components/common/SafeIcon.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Breadcrumb {
  label: string;
  href?: string;
}

interface Props {
  title: string;
  status?: 'draft' | 'collecting' | 'pending_review' | 'need_resubmit' | 'approved' | 'archived' | 'expired';
  breadcrumbs?: Breadcrumb[];
}

const props = defineProps<Props>();

const hasBreadcrumbs = computed(() => (props.breadcrumbs || []).length > 0);

const handleBack = () => {
  if (hasBreadcrumbs.value && props.breadcrumbs![0].href) {
    window.location.href = props.breadcrumbs![0].href;
  } else {
    window.history.back();
  }
};
</script>

<template>
  <div class="w-full flex flex-col gap-4 mb-6">
    <!-- Breadcrumbs -->
    <nav v-if="hasBreadcrumbs" class="flex items-center" aria-label="Breadcrumb">
      <ol class="flex items-center space-x-2">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
          <template v-if="index > 0">
            <span class="breadcrumb-separator mx-2 text-muted-foreground/50">/</span>
          </template>
          
          <a
            v-if="crumb.href"
            :href="crumb.href"
            class="breadcrumb-item inline-flex items-center"
          >
            {{ crumb.label }}
          </a>
          <span v-else class="text-sm font-medium text-foreground">
            {{ crumb.label }}
          </span>
        </li>
      </ol>
    </nav>

    <!-- Main Header Content -->
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-center gap-4 min-w-0">
        <Button
          variant="outline"
          size="icon"
          class="shrink-0 h-9 w-9 rounded-md"
          @click="handleBack"
        >
          <SafeIcon name="ChevronLeft" :size="20" />
        </Button>
        
        <div class="flex items-center gap-3 min-w-0">
          <h1 class="text-page-title truncate">
            {{ title }}
          </h1>
          <StatusBadge v-if="status" :status="status" />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
