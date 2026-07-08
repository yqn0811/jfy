
<script setup lang="ts">
import { ref } from 'vue'
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
import { Label } from '@/components/ui/label'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'

interface Props {
  open: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'created'): void
}>()

const formData = ref({
  name: '',
  intro: '',
  coverUrl: '',
})

const isLoading = ref(false)

const handleSubmit = async () => {
  if (!formData.value.name.trim()) {
    toast.error('请输入分类名称')
    return
  }

  isLoading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 500))
    toast.success('分类创建成功')
    emit('created')
    resetForm()
  } catch (error) {
    toast.error('创建失败，请重试')
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  formData.value = {
    name: '',
    intro: '',
    coverUrl: '',
  }
}

const handleOpenChange = (value: boolean) => {
  if (!value) {
    resetForm()
  }
  emit('update:open', value)
}
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="max-w-[500px]">
      <DialogHeader>
        <DialogTitle>新建分类</DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <Label for="category-name">分类名称 *</Label>
          <Input
            id="category-name"
            v-model="formData.name"
            placeholder="输入分类名称"
            class="h-10"
          />
        </div>

        <div class="space-y-2">
          <Label for="category-intro">分类简介</Label>
          <Textarea
            id="category-intro"
            v-model="formData.intro"
            placeholder="输入分类简介（可选）"
            class="min-h-[100px] resize-none"
          />
        </div>

        <div class="space-y-2">
          <Label>分类封面</Label>
          <div class="border-2 border-dashed border-border rounded-lg p-6 text-center cursor-pointer hover:bg-muted/50 transition-colors">
            <SafeIcon name="Image" :size="32" class="mx-auto text-muted-foreground/50 mb-2" />
            <p class="text-sm text-muted-foreground">点击上传或拖拽图片</p>
          </div>
        </div>
      </div>

      <DialogFooter class="flex gap-3">
        <Button
          variant="outline"
          @click="handleOpenChange(false)"
          :disabled="isLoading"
        >
          取消
        </Button>
        <Button
          @click="handleSubmit"
          :disabled="isLoading"
        >
          <SafeIcon v-if="isLoading" name="Loader2" :size="16" class="mr-2 animate-spin" />
          {{ isLoading ? '创建中...' : '创建分类' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
