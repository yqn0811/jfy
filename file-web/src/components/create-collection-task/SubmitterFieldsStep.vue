
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { toast } from 'vue-sonner'
import type { TaskFieldConfigData } from '@/data/CollectionTaskData'

interface Props {
  fields: TaskFieldConfigData[]
}

const emit = defineEmits<{
  (e: 'update:fields', value: TaskFieldConfigData[]): void
}>()

const props = defineProps<Props>()

const fieldTypeOptions = [
  { value: 'text', label: '文本' },
  { value: 'phone', label: '手机号' },
  { value: 'email', label: '邮箱' },
  { value: 'department', label: '部门' },
  { value: 'studentId', label: '学号' },
  { value: 'projectName', label: '项目名' },
]

const addField = () => {
  const newField: TaskFieldConfigData = {
    id: `field-${Date.now()}`,
    taskId: null,
    fieldKey: `field_${props.fields.length + 1}`,
    fieldLabel: '',
    fieldType: 'text',
    required: true,
    placeholder: '',
    order: props.fields.length + 1,
  }
  emit('update:fields', [...props.fields, newField])
}

const removeField = (index: number) => {
  emit('update:fields', props.fields.filter((_, i) => i !== index))
}

const updateField = (index: number, key: keyof TaskFieldConfigData, value: any) => {
  const updated = [...props.fields]
  updated[index] = { ...updated[index], [key]: value }
  emit('update:fields', updated)
}

const moveFieldUp = (index: number) => {
  if (index > 0) {
    const updated = [...props.fields]
    ;[updated[index], updated[index - 1]] = [updated[index - 1], updated[index]]
    updated.forEach((f, i) => (f.order = i + 1))
    emit('update:fields', updated)
  }
}

const moveFieldDown = (index: number) => {
  if (index < props.fields.length - 1) {
    const updated = [...props.fields]
    ;[updated[index], updated[index + 1]] = [updated[index + 1], updated[index]]
    updated.forEach((f, i) => (f.order = i + 1))
    emit('update:fields', updated)
  }
}
</script>

<template>
  <div class="mx-auto max-w-[820px] space-y-6">
    <div>
      <h3 class="text-section-title mb-4">提交人字段配置</h3>
      <p class="text-caption mb-6">
        定义提交人需要填写的信息字段。提交人在上传页面将按此顺序填写这些字段。
      </p>
    </div>

    <!-- Fields List -->
    <div class="space-y-3">
      <div
        v-for="(field, index) in props.fields"
        :key="field.id"
        class="surface-base card-padding space-y-4"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-muted-foreground">
            字段 {{ index + 1 }}
          </span>
          <div class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === 0"
              @click="moveFieldUp(index)"
            >
              <SafeIcon name="ChevronUp" :size="16" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === props.fields.length - 1"
              @click="moveFieldDown(index)"
            >
              <SafeIcon name="ChevronDown" :size="16" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-destructive hover:text-destructive"
              @click="removeField(index)"
            >
              <SafeIcon name="Trash2" :size="16" />
            </Button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Field Label -->
          <div class="space-y-2">
            <Label class="text-label">字段标签 *</Label>
            <Input
              :value="field.fieldLabel"
              @input="(e) => updateField(index, 'fieldLabel', (e.target as HTMLInputElement).value)"
              placeholder="例如：姓名"
              class="h-9"
            />
          </div>

          <!-- Field Type -->
          <div class="space-y-2">
            <Label class="text-label">字段类型 *</Label>
            <Select :model-value="field.fieldType" @update:model-value="(val) => updateField(index, 'fieldType', val)">
              <SelectTrigger class="h-9">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opt in fieldTypeOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Placeholder -->
          <div class="space-y-2 md:col-span-2">
            <Label class="text-label">提示文本</Label>
            <Input
              :value="field.placeholder"
              @input="(e) => updateField(index, 'placeholder', (e.target as HTMLInputElement).value)"
              placeholder="例如：请输入您的真实姓名"
              class="h-9"
            />
          </div>

          <!-- Required Checkbox -->
          <div class="flex items-center gap-2 md:col-span-2">
            <Checkbox
              :checked="field.required"
              @update:checked="(val) => updateField(index, 'required', val)"
              :id="`required-${field.id}`"
            />
            <Label :for="`required-${field.id}`" class="text-label cursor-pointer">
              必填项
            </Label>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="props.fields.length === 0" class="empty-state py-8">
        <SafeIcon name="FileQuestion" :size="48" class="text-muted-foreground/40 mb-2" />
        <p class="text-caption">还没有添加字段，点击下方按钮添加</p>
      </div>
    </div>

    <!-- Add Field Button -->
    <Button
      variant="outline"
      class="w-full"
      @click="addField"
    >
      <SafeIcon name="Plus" :size="18" class="mr-2" />
      添加字段
    </Button>

    <p class="text-xs text-muted-foreground">
      提示：至少需要添加一个字段。常见字段包括姓名、手机号、部门等。
    </p>
  </div>
</template>
