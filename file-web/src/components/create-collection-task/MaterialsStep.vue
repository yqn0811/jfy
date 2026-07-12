
<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { TaskMaterialItemData } from '@/data/CollectionTaskData'

interface Props {
  materials: TaskMaterialItemData[]
}

const emit = defineEmits<{
  (e: 'update:materials', value: TaskMaterialItemData[]): void
}>()

const props = defineProps<Props>()

const addMaterial = () => {
  const newMaterial: TaskMaterialItemData = {
    id: `mat-${Date.now()}`,
    taskId: null,
    materialName: '',
    fileTypes: [],
    required: true,
    maxSizeMb: 20,
    order: props.materials.length + 1,
  }
  emit('update:materials', [...props.materials, newMaterial])
}

const removeMaterial = (index: number) => {
  emit('update:materials', props.materials.filter((_, i) => i !== index))
}

const updateMaterial = (index: number, key: keyof TaskMaterialItemData, value: any) => {
  const updated = [...props.materials]
  updated[index] = { ...updated[index], [key]: value }
  emit('update:materials', updated)
}

const moveMaterialUp = (index: number) => {
  if (index > 0) {
    const updated = [...props.materials]
    ;[updated[index], updated[index - 1]] = [updated[index - 1], updated[index]]
    updated.forEach((m, i) => (m.order = i + 1))
    emit('update:materials', updated)
  }
}

const moveMaterialDown = (index: number) => {
  if (index < props.materials.length - 1) {
    const updated = [...props.materials]
    ;[updated[index], updated[index + 1]] = [updated[index + 1], updated[index]]
    updated.forEach((m, i) => (m.order = i + 1))
    emit('update:materials', updated)
  }
}

const parseFileTypes = (str: string): string[] => {
  return str
    .split(',')
    .map((t) => t.trim().toLowerCase())
    .filter((t) => t)
}

const formatFileTypes = (types: string[]): string => {
  return types.join(', ')
}
</script>

<template>
  <div class="mx-auto max-w-[820px] space-y-6">
    <div>
      <h3 class="text-section-title mb-4">必传材料配置</h3>
      <p class="text-caption mb-6">
        定义需要收集的材料清单。提交人将按此顺序上传相应的文件。
      </p>
    </div>

    <!-- Materials List -->
    <div class="space-y-3">
      <div
        v-for="(material, index) in props.materials"
        :key="material.id"
        class="surface-base card-padding space-y-4"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-muted-foreground">
            材料 {{ index + 1 }}
          </span>
          <div class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === 0"
              @click="moveMaterialUp(index)"
            >
              <SafeIcon name="ChevronUp" :size="16" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === props.materials.length - 1"
              @click="moveMaterialDown(index)"
            >
              <SafeIcon name="ChevronDown" :size="16" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-destructive hover:text-destructive"
              @click="removeMaterial(index)"
            >
              <SafeIcon name="Trash2" :size="16" />
            </Button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Material Name -->
          <div class="space-y-2">
            <Label class="text-label">材料名称 *</Label>
            <Input
              :value="material.materialName"
              @input="(e) => updateMaterial(index, 'materialName', (e.target as HTMLInputElement).value)"
              placeholder="例如：身份证正面"
              class="h-9"
            />
          </div>

          <!-- Max Size -->
          <div class="space-y-2">
            <Label class="text-label">单文件最大限制 (MB)</Label>
            <Input
              type="number"
              :value="material.maxSizeMb"
              @input="(e) => updateMaterial(index, 'maxSizeMb', parseInt((e.target as HTMLInputElement).value) || 20)"
              min="1"
              max="500"
              class="h-9"
            />
          </div>

          <!-- File Types -->
          <div class="space-y-2 md:col-span-2">
            <Label class="text-label">接收文件格式 (逗号分隔)</Label>
            <Input
              :value="formatFileTypes(material.fileTypes)"
              @input="(e) => updateMaterial(index, 'fileTypes', parseFileTypes((e.target as HTMLInputElement).value))"
              placeholder="例如：jpg, png, pdf"
              class="h-9"
            />
            <p class="text-xs text-muted-foreground">
              留空表示接收所有格式
            </p>
          </div>

          <!-- Required Checkbox -->
          <div class="flex items-center gap-2 md:col-span-2">
            <Checkbox
              :checked="material.required"
              @update:checked="(val) => updateMaterial(index, 'required', val)"
              :id="`required-mat-${material.id}`"
            />
            <Label :for="`required-mat-${material.id}`" class="text-label cursor-pointer">
              必填项
            </Label>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="props.materials.length === 0" class="empty-state py-8">
        <SafeIcon name="FileQuestion" :size="48" class="text-muted-foreground/40 mb-2" />
        <p class="text-caption">还没有添加材料，点击下方按钮添加</p>
      </div>
    </div>

    <!-- Add Material Button -->
    <Button
      variant="outline"
      class="w-full"
      @click="addMaterial"
    >
      <SafeIcon name="Plus" :size="18" class="mr-2" />
      添加材料
    </Button>

    <p class="text-xs text-muted-foreground">
      提示：至少需要添加一项材料。常见材料包括身份证、合同、发票等。
    </p>
  </div>
</template>
