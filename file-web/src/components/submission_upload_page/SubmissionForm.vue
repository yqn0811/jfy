
<script setup lang="ts">
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { TaskFieldConfigData } from '@/data/CollectionTaskData'

interface Props {
  fields: TaskFieldConfigData[]
  formData: Record<string, string>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:formData', key: string, value: string): void
}>()

const handleInputChange = (fieldKey: string, value: string) => {
  emit('update:formData', fieldKey, value)
}

const getFieldLabel = (field: TaskFieldConfigData) => {
  const typeLabels: Record<string, string> = {
    text: '文本',
    phone: '手机号',
    department: '部门',
    studentId: '学号',
    projectName: '项目名',
    email: '邮箱'
  }
  return field.fieldLabel || typeLabels[field.fieldType] || '字段'
}

const getFieldType = (fieldType: string) => {
  if (fieldType === 'phone') return 'tel'
  if (fieldType === 'email') return 'email'
  return 'text'
}
</script>

<template>
  <Card class="surface-raised">
    <CardHeader>
      <CardTitle class="text-lg">提交人信息</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="field in fields" :key="field.id" class="flex flex-col gap-2">
          <Label :for="field.fieldKey" class="text-sm font-medium">
            {{ getFieldLabel(field) }}
            <span v-if="field.required" class="text-destructive ml-1">*</span>
          </Label>
          <Input
            :id="field.fieldKey"
            :type="getFieldType(field.fieldType)"
            :placeholder="field.placeholder"
            :value="formData[field.fieldKey] || ''"
            :required="field.required"
            @input="(e) => handleInputChange(field.fieldKey, (e.target as HTMLInputElement).value)"
            class="h-10"
          />
        </div>
      </div>
    </CardContent>
  </Card>
</template>
