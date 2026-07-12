
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { cn } from '@/lib/utils'

interface FormData {
  name: string
  description: string
  dueAt: string
  submitTargetDescription: string
  fields: any[]
  materials: any[]
  ruleConfig: any
}

interface Props {
  formData: FormData
}

const props = defineProps<Props>()
const showPreview = ref(false)

const formatDate = (dateStr: string) => {
  if (!dateStr) return '未设置'
  const date = new Date(dateStr)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getTimeRemaining = (dueAt: string) => {
  if (!dueAt) return '未设置'
  const now = new Date()
  const due = new Date(dueAt)
  const diff = due.getTime() - now.getTime()

  if (diff < 0) return '已过期'
  if (diff < 24 * 60 * 60 * 1000) {
    const hours = Math.floor(diff / (60 * 60 * 1000))
    return `${hours} 小时后截止`
  }
  const days = Math.floor(diff / (24 * 60 * 60 * 1000))
  return `${days} 天后截止`
}
</script>

<template>
  <div class="mx-auto max-w-[820px] space-y-6">
    <div>
      <h3 class="text-section-title mb-4">预览与生成</h3>
      <p class="text-caption mb-6">
        检查任务配置无误后，点击"创建任务"生成收集链接。
      </p>
    </div>

    <!-- Configuration Summary -->
    <div class="space-y-4">
      <!-- Task Info Card -->
      <div class="surface-base card-padding">
        <h4 class="text-item-title mb-3">任务信息</h4>
        <div class="space-y-3">
          <div class="flex items-start justify-between">
            <span class="text-caption">任务名称</span>
            <span class="text-sm font-medium text-right max-w-xs">{{ props.formData.name || '未设置' }}</span>
          </div>
          <div class="flex items-start justify-between">
            <span class="text-caption">截止时间</span>
            <span class="text-sm font-medium text-right">
              {{ formatDate(props.formData.dueAt) }}
            </span>
          </div>
          <div class="flex items-start justify-between">
            <span class="text-caption">距离截止</span>
            <span :class="cn('text-sm font-medium text-right', getTimeRemaining(props.formData.dueAt) === '已过期' && 'text-destructive')">
              {{ getTimeRemaining(props.formData.dueAt) }}
            </span>
          </div>
          <div v-if="props.formData.description" class="pt-2 border-t border-border">
            <p class="text-caption mb-1">任务说明</p>
            <p class="text-sm text-foreground">{{ props.formData.description }}</p>
          </div>
        </div>
      </div>

      <!-- Fields Summary -->
      <div class="surface-base card-padding">
        <h4 class="text-item-title mb-3">
          提交人字段 ({{ props.formData.fields.length }} 个)
        </h4>
        <div class="space-y-2">
          <div
            v-for="field in props.formData.fields"
            :key="field.id"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-foreground">
              {{ field.fieldLabel }}
              <span v-if="field.required" class="text-destructive ml-1">*</span>
            </span>
            <span class="text-caption">{{ field.fieldType }}</span>
          </div>
        </div>
      </div>

      <!-- Materials Summary -->
      <div class="surface-base card-padding">
        <h4 class="text-item-title mb-3">
          必传材料 ({{ props.formData.materials.length }} 项)
        </h4>
        <div class="space-y-2">
          <div
            v-for="material in props.formData.materials"
            :key="material.id"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-foreground">
              {{ material.materialName }}
              <span v-if="material.required" class="text-destructive ml-1">*</span>
            </span>
            <span class="text-caption">
              最大 {{ material.maxSizeMb }}MB
            </span>
          </div>
        </div>
      </div>

      <!-- Rules Summary -->
      <div class="surface-base card-padding">
        <h4 class="text-item-title mb-3">提交规则</h4>
        <div class="space-y-2 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-caption">命名规则</span>
            <span class="text-foreground">{{ props.formData.ruleConfig.namingRule }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-caption">允许补交</span>
            <span class="text-foreground">
              {{ props.formData.ruleConfig.allowResubmission ? '是' : '否' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-caption">AI 缺失检查</span>
            <span class="text-foreground">
              {{ props.formData.ruleConfig.enableAICheck ? '启用' : '禁用' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-caption">免登录提交</span>
            <span class="text-foreground">
              {{ props.formData.ruleConfig.anonymousSubmit ? '是' : '否' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Button -->
    <Button
      variant="outline"
      class="w-full"
      @click="showPreview = true"
    >
      <SafeIcon name="Eye" :size="18" class="mr-2" />
      预览提交人上传页
    </Button>

    <!-- Preview Dialog -->
    <Dialog v-model:open="showPreview">
      <DialogContent class="max-w-2xl max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>提交人上传页预览</DialogTitle>
          <DialogDescription>
            这是提交人将看到的页面样式
          </DialogDescription>
        </DialogHeader>

        <div class="flex-1 overflow-y-auto min-h-0 py-4">
          <!-- Preview Content -->
          <div class="space-y-6 px-4">
            <!-- Task Header -->
            <div class="space-y-2">
              <h2 class="text-xl font-bold">{{ props.formData.name }}</h2>
              <p class="text-sm text-muted-foreground">
                {{ props.formData.submitTargetDescription }}
              </p>
            </div>

            <!-- Task Info -->
            <div class="bg-muted/30 rounded-lg p-4 space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">截止时间</span>
                <span class="font-medium">{{ formatDate(props.formData.dueAt) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">距离截止</span>
                <span class="font-medium">{{ getTimeRemaining(props.formData.dueAt) }}</span>
              </div>
            </div>

            <!-- Description -->
            <div v-if="props.formData.description" class="space-y-2">
              <h4 class="text-sm font-medium">任务说明</h4>
              <p class="text-sm text-foreground">{{ props.formData.description }}</p>
            </div>

            <!-- Form Fields Preview -->
            <div class="space-y-4">
              <h4 class="text-sm font-medium">请填写以下信息</h4>
              <div
                v-for="field in props.formData.fields"
                :key="field.id"
                class="space-y-2"
              >
                <label class="text-sm font-medium">
                  {{ field.fieldLabel }}
                  <span v-if="field.required" class="text-destructive">*</span>
                </label>
                <div class="h-9 bg-muted/50 rounded-md border border-border" />
              </div>
            </div>

            <!-- Materials Preview -->
            <div class="space-y-4">
              <h4 class="text-sm font-medium">请上传以下材料</h4>
              <div
                v-for="material in props.formData.materials"
                :key="material.id"
                class="space-y-2"
              >
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium">
                    {{ material.materialName }}
                    <span v-if="material.required" class="text-destructive">*</span>
                  </label>
                  <span class="text-xs text-muted-foreground">
                    最大 {{ material.maxSizeMb }}MB
                  </span>
                </div>
                <div class="h-20 bg-muted/30 rounded-lg border-2 border-dashed border-border flex items-center justify-center">
                  <span class="text-sm text-muted-foreground">点击或拖拽文件</span>
                </div>
              </div>
            </div>

            <!-- Submit Button Preview -->
            <div class="pt-4">
              <div class="h-10 bg-primary rounded-md" />
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
