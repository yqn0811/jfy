
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { CategoryData } from '@/data/CategoryData'
import { toast } from 'vue-sonner'

interface Props {
  open: boolean
  category?: CategoryData
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'save', data: CategoryData): void
}>()

const isEditing = computed(() => !!props.category?.id)
const isLoading = ref(false)

const form = ref({
  name: '',
  intro: '',
  visibility: 'public' as const,
  layout: 'grid' as const,
})

watch(
  () => props.open,
  (newVal) => {
    if (newVal && props.category) {
      form.value = {
        name: props.category.name,
        intro: props.category.intro,
        visibility: props.category.visibility,
        layout: props.category.layout,
      }
    } else if (newVal) {
      form.value = {
        name: '',
        intro: '',
        visibility: 'public',
        layout: 'grid',
      }
    }
  }
)

const handleSave = async () => {
  if (!form.value.name.trim()) {
    toast.error('请输入分类名称')
    return
  }

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 500))

    emit('save', {
      ...(props.category || {}),
      id: props.category?.id || '',
      homeId: props.category?.homeId || '',
      parentId: props.category?.parentId,
      name: form.value.name.trim(),
      intro: form.value.intro.trim(),
      coverUrl: props.category?.coverUrl || '',
      productCount: props.category?.productCount || 0,
      childCount: props.category?.childCount || 0,
      visibility: form.value.visibility,
      layout: form.value.layout,
      isTop: props.category?.isTop || false,
      children: props.category?.children || [],
      updatedAt: new Date().toLocaleString('zh-CN'),
      createdAt: props.category?.createdAt || new Date().toLocaleString('zh-CN'),
    } as CategoryData)
  } finally {
    isLoading.value = false
  }
}

const handleClose = () => {
  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="max-w-2xl max-h-[80vh] flex flex-col">
      <DialogHeader class="flex-shrink-0">
        <DialogTitle class="text-xl">
          {{ isEditing ? '编辑分类' : '新建分类' }}
        </DialogTitle>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto min-h-0 px-6 space-y-6 py-4">
        <!-- 分类名称 -->
        <div class="space-y-2">
          <Label for="name" class="text-label">分类名称 *</Label>
          <Input
            id="name"
            v-model="form.name"
            placeholder="例如：床品套件、窗帘布艺"
            class="h-10"
          />
        </div>

        <!-- 分类简介 -->
        <div class="space-y-2">
          <Label for="intro" class="text-label">分类简介</Label>
          <Textarea
            id="intro"
            v-model="form.intro"
            placeholder="简要描述该分类的内容和特点"
            class="min-h-24 resize-none"
          />
        </div>

        <!-- 可见性设置 -->
        <div class="space-y-2">
          <Label for="visibility" class="text-label">可见性</Label>
          <Select v-model="form.visibility">
            <SelectTrigger id="visibility" class="h-10">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="public">
                <div class="flex items-center gap-2">
                  <SafeIcon name="Globe" :size="16" />
                  <span>公开</span>
                </div>
              </SelectItem>
              <SelectItem value="private">
                <div class="flex items-center gap-2">
                  <SafeIcon name="Lock" :size="16" />
                  <span>私密</span>
                </div>
              </SelectItem>
              <SelectItem value="shared">
                <div class="flex items-center gap-2">
                  <SafeIcon name="Share2" :size="16" />
                  <span>分享可见</span>
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
          <p class="text-xs text-muted-foreground mt-1">
            公开：所有访客可见 | 私密：仅拥有者可见 | 分享可见：需要分享链接才能访问
          </p>
        </div>

        <!-- 布局方式 -->
        <div class="space-y-2">
          <Label for="layout" class="text-label">布局方式</Label>
          <Select v-model="form.layout">
            <SelectTrigger id="layout" class="h-10">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="grid">
                <div class="flex items-center gap-2">
                  <SafeIcon name="Grid3x3" :size="16" />
                  <span>网格布局</span>
                </div>
              </SelectItem>
              <SelectItem value="list">
                <div class="flex items-center gap-2">
                  <SafeIcon name="List" :size="16" />
                  <span>列表布局</span>
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter class="flex-shrink-0 border-t pt-4 px-6 pb-4 flex items-center justify-end gap-3">
        <Button variant="outline" @click="handleClose" :disabled="isLoading">
          取消
        </Button>
        <Button
          variant="default"
          @click="handleSave"
          :disabled="isLoading"
          class="gap-2"
        >
          <SafeIcon v-if="isLoading" name="Loader2" :size="16" class="animate-spin" />
          {{ isLoading ? '保存中...' : isEditing ? '更新分类' : '创建分类' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
