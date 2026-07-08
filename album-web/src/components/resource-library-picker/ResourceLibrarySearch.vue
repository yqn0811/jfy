
<script setup lang="ts">
import { ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import SafeIcon from '@/components/common/SafeIcon.vue'

interface Props {
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
})

const emit = defineEmits<{
  (e: 'search', keyword: string): void
  (e: 'filter-change', status: 'all' | 'recent' | 'used' | 'unused'): void
}>()

const searchInput = ref('')
const filterValue = ref('all')

const handleSearch = () => {
  emit('search', searchInput.value)
}

const handleFilterChange = (value: string) => {
  filterValue.value = value
  emit('filter-change', value as 'all' | 'recent' | 'used' | 'unused')
}

const handleClear = () => {
  searchInput.value = ''
  emit('search', '')
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <!-- Search Input -->
    <div class="flex items-center gap-2">
      <div class="relative flex-1">
        <SafeIcon
          name="Search"
          :size="18"
          class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none"
        />
        <Input
          v-model="searchInput"
          type="text"
          placeholder="搜索图片名称..."
          class="pl-10 h-10 bg-muted/50 border-none focus-visible:ring-1 focus-visible:ring-primary"
          :disabled="disabled"
          @keyup.enter="handleSearch"
        />
      </div>
      <Button
        variant="outline"
        size="sm"
        class="h-10 px-4"
        :disabled="disabled || !searchInput.trim()"
        @click="handleClear"
      >
        清空
      </Button>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2">
      <span class="text-sm text-muted-foreground font-medium">筛选:</span>
      <Select :model-value="filterValue" @update:model-value="handleFilterChange" :disabled="disabled">
        <SelectTrigger class="w-40 h-9">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">全部</SelectItem>
          <SelectItem value="recent">最近上传</SelectItem>
          <SelectItem value="used">已使用</SelectItem>
          <SelectItem value="unused">未使用</SelectItem>
        </SelectContent>
      </Select>
    </div>
  </div>
</template>
