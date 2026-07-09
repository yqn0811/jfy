
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import FileUploadZone from '@/components/common/FileUploadZone.vue'
import FileListItem from '@/components/common/FileListItem.vue'
import SafeIcon from '@/components/common/SafeIcon.vue'
import type { TaskMaterialItemData } from '@/data/CollectionTaskData'

interface Props {
  materials: TaskMaterialItemData[]
  uploadedFiles: Record<string, File[]>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'files-selected', materialId: string, files: File[]): void
}>()

const uploadingMaterials = ref<Record<string, boolean>>({})

const handleFilesSelected = (materialId: string, files: FileList) => {
  const fileArray = Array.from(files)
  uploadingMaterials.value[materialId] = true
  
  setTimeout(() => {
    emit('files-selected', materialId, fileArray)
    uploadingMaterials.value[materialId] = false
  }, 800)
}

const handleRemoveFile = (materialId: string, index: number) => {
  const currentFiles = props.uploadedFiles[materialId] || []
  const updated = currentFiles.filter((_, i) => i !== index)
  emit('files-selected', materialId, updated)
}

const formatFileTypes = (types: string[]) => {
  return types.map(t => t.toUpperCase()).join(', ')
}
</script>

<template>
  <Card class="surface-raised">
    <CardHeader>
      <CardTitle class="text-lg">上传材料</CardTitle>
    </CardHeader>
    <CardContent class="space-y-6">
      <div v-for="material in materials" :key="material.id" class="space-y-3">
        <!-- 材料标题 -->
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-sm font-medium">
              {{ material.materialName }}
              <span v-if="material.required" class="text-destructive ml-1">*</span>
            </h4>
            <p class="text-xs text-muted-foreground mt-1">
              格式: {{ formatFileTypes(material.fileTypes) }} | 最大: {{ material.maxSizeMb }}MB
            </p>
          </div>
          <div v-if="(uploadedFiles[material.id] || []).length > 0" class="flex items-center gap-2 text-xs">
            <SafeIcon name="CheckCircle2" :size="16" class="text-[hsl(var(--success))]" />
            <span class="text-[hsl(var(--success))] font-medium">已上传</span>
          </div>
        </div>

        <!-- 上传区域 -->
        <FileUploadZone
          :accept="material.fileTypes.map(t => `.${t}`).join(',')"
          :max-size="material.maxSizeMb"
          :disabled="uploadingMaterials[material.id]"
          @files-selected="(files) => handleFilesSelected(material.id, files)"
        >
          <div class="text-item-title">点击或拖拽文件上传</div>
          <p class="text-caption">{{ material.materialName }}</p>
        </FileUploadZone>

        <!-- 已上传文件列表 -->
        <div v-if="(uploadedFiles[material.id] || []).length > 0" class="space-y-2">
          <FileListItem
            v-for="(file, index) in uploadedFiles[material.id]"
            :key="`${material.id}-${index}`"
            :file-name="file.name"
            :file-size="file.size"
            status="success"
          >
            <template #actions>
              <Button
                variant="ghost"
                size="sm"
                class="h-6 w-6 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                @click="handleRemoveFile(material.id, index)"
              >
                <SafeIcon name="X" :size="14" />
              </Button>
            </template>
          </FileListItem>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
