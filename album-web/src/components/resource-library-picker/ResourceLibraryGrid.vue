
<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import SafeIcon from '@/components/common/SafeIcon.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { cn } from '@/lib/utils'
import type { ResourceImageVO } from '@/data/ResourceLibraryService'

interface Props {
  resources: ResourceImageVO[]
  selectedIds: Set<string>
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
})

const emit = defineEmits<{
  (e: 'toggle-select', id: string): void
}>()

const isSelected = (id: string) => props.selectedIds.has(id)
</script>

<template>
  <div>
    <!-- Empty State -->
    <div v-if="resources.length === 0" class="py-12">
      <EmptyState
        icon="ImageOff"
        title="暂无资源"
        description="资源库中没有符合条件的图片"
      />
    </div>

    <!-- Resource Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      <div
        v-for="resource in resources"
        :key="resource.id"
        class="group relative cursor-pointer"
        @click="!disabled && emit('toggle-select', resource.id)"
      >
        <!-- Image Container -->
        <div
          :class="cn(
            'relative w-full aspect-square rounded-lg border-2 overflow-hidden transition-all duration-200',
            isSelected(resource.id)
              ? 'border-primary bg-primary/5 ring-2 ring-primary/30'
              : 'border-border bg-muted/30 group-hover:border-primary/50 group-hover:bg-muted/50'
          )"
        >
          <!-- Image -->
          <img
            :src="resource.thumbnailUrl"
            :alt="resource.name"
            class="w-full h-full object-cover"
          />

          <!-- Overlay -->
          <div
            :class="cn(
              'absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center',
              isSelected(resource.id) && 'bg-black/20'
            )"
          >
            <!-- Checkbox -->
            <Checkbox
              :checked="isSelected(resource.id)"
              :disabled="disabled"
              class="pointer-events-none"
              @click.stop
            />
          </div>

          <!-- Used Badge -->
          <div
            v-if="resource.usedByProductId"
            class="absolute top-2 right-2 bg-accent/90 text-accent-foreground px-2 py-1 rounded text-[10px] font-medium flex items-center gap-1"
          >
            <SafeIcon name="Check" :size="12" />
            已使用
          </div>
        </div>

        <!-- Info -->
        <div class="mt-2 space-y-1 min-w-0">
          <p class="text-xs font-medium text-foreground truncate">
            {{ resource.name }}
          </p>
          <p class="text-[10px] text-muted-foreground">
            {{ resource.sizeLabel }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
