<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import type { CategoryData } from '@/data/CategoryData'
import type { ProductVisibility } from '@/data/ProductData'

interface Props {
  open: boolean
  categories: CategoryData[]
  saving?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  saving: false,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'create', data: {
    name: string
    intro: string
    categoryId: string
    visibility: ProductVisibility
    hideDetailImage: boolean
  }): void
}>()

const form = ref({
  name: '',
  intro: '',
  categoryId: 'none',
  visibility: 'public' as ProductVisibility,
  hideDetailImage: false,
})

const resetForm = () => {
  form.value = {
    name: '',
    intro: '',
    categoryId: 'none',
    visibility: 'public',
    hideDetailImage: false,
  }
}

watch(
  () => props.open,
  (open) => {
    if (open) resetForm()
  }
)

const handleOpenChange = (value: boolean) => {
  if (!value) resetForm()
  emit('update:open', value)
}

const handleCreate = () => {
  if (!form.value.name.trim()) {
    toast.error('请输入产品名称')
    return
  }

  emit('create', {
    name: form.value.name.trim(),
    intro: form.value.intro.trim(),
    categoryId: form.value.categoryId,
    visibility: form.value.visibility,
    hideDetailImage: form.value.hideDetailImage,
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="max-w-xl">
      <DialogHeader>
        <DialogTitle>新建产品</DialogTitle>
      </DialogHeader>

      <div class="space-y-5 py-2">
        <div class="space-y-2">
          <Label for="product-create-name" class="text-label">产品名称 *</Label>
          <Input
            id="product-create-name"
            v-model="form.name"
            class="h-10"
            placeholder="输入产品名称"
            @keyup.enter="handleCreate"
          />
        </div>

        <div class="space-y-2">
          <Label for="product-create-intro" class="text-label">产品简介</Label>
          <Textarea
            id="product-create-intro"
            v-model="form.intro"
            class="min-h-[88px] resize-none"
            placeholder="简单描述产品特点"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="product-create-category" class="text-label">所属分类</Label>
            <Select v-model="form.categoryId">
              <SelectTrigger id="product-create-category" class="h-10">
                <SelectValue placeholder="选择分类" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">未分类</SelectItem>
                <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="product-create-visibility" class="text-label">可见性</Label>
            <Select v-model="form.visibility">
              <SelectTrigger id="product-create-visibility" class="h-10">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="public">公开</SelectItem>
                <SelectItem value="private">私密</SelectItem>
                <SelectItem value="shared">分享可见</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="flex items-center justify-between rounded-md border border-border bg-muted/30 p-3">
          <div class="flex items-center gap-2">
            <SafeIcon name="EyeOff" :size="18" class="text-muted-foreground" />
            <div>
              <p class="text-sm font-medium">隐藏详情图</p>
              <p class="text-xs text-muted-foreground">开启后，分享访客看不到详情图</p>
            </div>
          </div>
          <Switch v-model="form.hideDetailImage" />
        </div>
      </div>

      <DialogFooter class="gap-3">
        <Button variant="outline" :disabled="saving" @click="handleOpenChange(false)">
          取消
        </Button>
        <Button :disabled="saving" class="gap-2" @click="handleCreate">
          <SafeIcon v-if="saving" name="Loader2" :size="16" class="animate-spin" />
          {{ saving ? '创建中...' : '创建产品' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
