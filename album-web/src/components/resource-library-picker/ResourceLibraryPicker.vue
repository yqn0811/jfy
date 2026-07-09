
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { toast } from 'vue-sonner'
import SafeIcon from '@/components/common/SafeIcon.vue'
import ResourceLibrarySearch from './ResourceLibrarySearch.vue'
import ResourceLibraryGrid from './ResourceLibraryGrid.vue'
import type { ResourceImageVO } from '@/data/ResourceLibraryService'
import { cn } from '@/lib/utils'
import { pcApi } from '@/lib/api'
import { pickImage, unwrapList } from '@/lib/jfyuntu-mappers'

const isClient = ref(true)
const isLoading = ref(false)
const targetType = ref<'colorChart' | 'detailChart'>('colorChart')
const productId = ref('')
const returnTo = ref('./product-edit')
const allResources = ref<ResourceImageVO[]>([])
const selectedIds = ref<Set<string>>(new Set())
const searchKeyword = ref('')
const filterStatus = ref<'all' | 'recent' | 'used' | 'unused'>('all')

onMounted(() => {
  isClient.value = false
  requestAnimationFrame(() => {
    const params = new URLSearchParams(window.location.search)
    const target = params.get('targetType') as 'colorChart' | 'detailChart' | null
    if (target && ['colorChart', 'detailChart'].includes(target)) targetType.value = target
    productId.value = params.get('productId') || params.get('product_id') || ''
    returnTo.value = params.get('returnTo') || (productId.value ? `./product-edit?productId=${productId.value}` : './product-edit')
    isClient.value = true
    loadResources()
  })
})

const mapResource = (item: any): ResourceImageVO => ({
  id: String(item.id || item.resource_id || item.pic_id || ''),
  name: item.name || item.pic_name || item.file_name || '未命名图片',
  url: pickImage(item.file_url, item.fileUrl, item.preview_url, item.previewUrl, item.picture_url_original, item.url, item),
  thumbnailUrl: pickImage(item.thumbnail_url, item.thumbnailUrl, item.preview_url, item.previewUrl, item.thumb, item.picture_url, item.imgurl, item.url, item),
  sizeLabel: item.sizeLabel || item.size_label || (item.size ? `${(Number(item.size) / 1024 / 1024).toFixed(1)} MB` : ''),
  sizeBytes: Number(item.sizeBytes || item.size || 0),
  uploadedAt: item.uploadedAt || item.create_time || item.created_at || '',
  status: item.usedByProductId || item.used_by_product_id ? 'used' : 'unused',
  usedByProductId: item.usedByProductId || item.used_by_product_id || item.product_id || '',
  targetType: targetType.value,
})

const loadResources = async () => {
  isLoading.value = true
  try {
    const raw = await pcApi.getAiResources({
      page_size: 100,
      keyword: searchKeyword.value.trim(),
    })
    allResources.value = unwrapList(raw).map(mapResource)
  } catch (error: any) {
    toast.error(error?.message || '资源库加载失败')
  } finally {
    isLoading.value = false
  }
}

const filteredResources = computed(() => {
  let result = allResources.value

  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.trim().toLowerCase()
    result = result.filter(item => item.name.toLowerCase().includes(keyword))
  }

  if (filterStatus.value === 'recent') {
    result = [...result].sort((a, b) => new Date(b.uploadedAt).getTime() - new Date(a.uploadedAt).getTime())
  } else if (filterStatus.value === 'used') {
    result = result.filter(item => item.usedByProductId)
  } else if (filterStatus.value === 'unused') {
    result = result.filter(item => !item.usedByProductId)
  }

  return result
})

const isAllSelected = computed(() => {
  return filteredResources.value.length > 0 && selectedIds.value.size === filteredResources.value.length
})

const toggleSelect = (id: string) => {
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id)
  } else {
    selectedIds.value.add(id)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value.clear()
  } else {
    filteredResources.value.forEach(item => selectedIds.value.add(item.id))
  }
}

const handleSearch = (keyword: string) => {
  searchKeyword.value = keyword
  loadResources()
}

const handleFilterChange = (status: 'all' | 'recent' | 'used' | 'unused') => {
  filterStatus.value = status
}

const handleConfirm = async () => {
  if (selectedIds.value.size === 0) {
    toast.error('请至少选择一张图片')
    return
  }
  try {
    const role = targetType.value === 'detailChart' ? 'detail' : 'cover'
    await Promise.all(Array.from(selectedIds.value).map(id => pcApi.importAiResource(id, role, productId.value)))
    toast.success(`已导入 ${selectedIds.value.size} 张图片`)
    window.location.href = returnTo.value
  } catch (error: any) {
    toast.error(error?.message || '导入失败')
  }
}

const handleCancel = () => {
  window.location.href = returnTo.value
}
</script>

<template>
  <Dialog :open="true">
    <DialogContent class="max-w-4xl max-h-[90vh] flex flex-col gap-0 p-0 overflow-hidden">
      <!-- Header -->
      <DialogHeader class="border-b border-border px-6 py-4 shrink-0">
        <DialogTitle class="text-xl font-semibold">
          选择{{ targetType === 'colorChart' ? '花色图' : '详情图' }}
        </DialogTitle>
      </DialogHeader>

      <!-- Search & Filter Bar -->
      <div class="border-b border-border px-6 py-4 shrink-0 space-y-4">
        <ResourceLibrarySearch
          :disabled="!isClient"
          @search="handleSearch"
          @filter-change="handleFilterChange"
        />
      </div>

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto min-h-0 px-6 py-4">
        <!-- Select All Checkbox -->
        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-border">
          <Checkbox
            :checked="isAllSelected"
            :disabled="!isClient || filteredResources.length === 0"
            @update:checked="toggleSelectAll"
          />
          <span class="text-sm text-muted-foreground">
            {{ isAllSelected ? '已全选' : '全选' }} ({{ selectedIds.size }}/{{ filteredResources.length }})
          </span>
        </div>

        <!-- Resource Grid -->
        <ResourceLibraryGrid
          :resources="filteredResources"
          :selected-ids="selectedIds"
          :disabled="!isClient || isLoading"
          @toggle-select="toggleSelect"
        />
      </div>

      <!-- Footer -->
      <DialogFooter class="border-t border-border px-6 py-4 shrink-0 flex items-center justify-end gap-3">
        <Button
          variant="outline"
          @click="handleCancel"
          :disabled="!isClient"
        >
          取消
        </Button>
        <Button
          variant="default"
          @click="handleConfirm"
          :disabled="!isClient || selectedIds.size === 0"
        >
          <SafeIcon name="Check" :size="16" class="mr-2" />
          确认导入 ({{ selectedIds.size }})
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
