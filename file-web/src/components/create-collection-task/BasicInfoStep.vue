
<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'

interface Props {
  name: string
  description: string
  dueAt: string
  submitTargetDescription: string
}

const emit = defineEmits<{
  (e: 'update:name', value: string): void
  (e: 'update:description', value: string): void
  (e: 'update:dueAt', value: string): void
  (e: 'update:submitTargetDescription', value: string): void
}>()

const props = defineProps<Props>()
</script>

<template>
  <div class="mx-auto max-w-[820px] space-y-6">
    <div>
      <h3 class="text-section-title mb-4">基本信息</h3>
      <p class="text-caption mb-6">
        填写任务的基本信息，包括名称、说明、截止时间和提交对象描述。
      </p>
    </div>

    <!-- Task Name -->
    <div class="space-y-2">
      <Label for="task-name" class="text-label">
        任务名称 <span class="text-destructive">*</span>
      </Label>
      <Input
        id="task-name"
        :value="props.name"
        @input="(e) => emit('update:name', (e.target as HTMLInputElement).value)"
        placeholder="请输入任务名称"
        class="h-10"
      />
      <p class="text-xs text-muted-foreground">
        清晰的任务名称有助于提交人快速理解收集内容
      </p>
    </div>

    <!-- Description -->
    <div class="space-y-2">
      <Label for="task-desc" class="text-label">任务说明</Label>
      <Textarea
        id="task-desc"
        :value="props.description"
        @input="(e) => emit('update:description', (e.target as HTMLTextAreaElement).value)"
        placeholder="请输入提交说明和注意事项"
        class="min-h-24 resize-none"
      />
      <p class="text-xs text-muted-foreground">
        提交人将在上传页面看到此说明，建议简洁明了
      </p>
    </div>

    <!-- Due Date -->
    <div class="space-y-2">
      <Label for="due-date" class="text-label">
        截止时间 <span class="text-destructive">*</span>
      </Label>
      <Input
        id="due-date"
        type="datetime-local"
        :value="props.dueAt"
        @input="(e) => emit('update:dueAt', (e.target as HTMLInputElement).value)"
        class="h-10"
      />
      <p class="text-xs text-muted-foreground">
        超过截止时间后，任务将自动标记为已过期
      </p>
    </div>

    <!-- Submit Target Description -->
    <div class="space-y-2">
      <Label for="submit-target" class="text-label">提交对象说明</Label>
      <Textarea
        id="submit-target"
        :value="props.submitTargetDescription"
        @input="(e) => emit('update:submitTargetDescription', (e.target as HTMLTextAreaElement).value)"
        placeholder="请输入需要提交材料的对象说明"
        class="min-h-20 resize-none"
      />
      <p class="text-xs text-muted-foreground">
        说明谁需要提交材料，提交人将在上传页面看到此信息
      </p>
    </div>
  </div>
</template>
