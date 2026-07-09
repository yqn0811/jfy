
<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import type { TaskRuleConfigData } from '@/data/CollectionTaskData'

interface Props {
  ruleConfig: Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'>
}

const emit = defineEmits<{
  (e: 'update:ruleConfig', value: Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'>): void
}>()

const props = defineProps<Props>()

const updateConfig = (key: keyof Props['ruleConfig'], value: any) => {
  emit('update:ruleConfig', {
    ...props.ruleConfig,
    [key]: value,
  })
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h3 class="text-section-title mb-4">提交规则配置</h3>
      <p class="text-caption mb-6">
        配置文件命名规则、是否允许补交、AI 检查等高级选项。
      </p>
    </div>

    <!-- Naming Rule -->
    <div class="space-y-2">
      <Label for="naming-rule" class="text-label">
        文件命名规则 <span class="text-destructive">*</span>
      </Label>
      <Input
        id="naming-rule"
        :value="props.ruleConfig.namingRule"
        @input="(e) => updateConfig('namingRule', (e.target as HTMLInputElement).value)"
        placeholder="例如：姓名_部门_材料名"
        class="h-10"
      />
      <p class="text-xs text-muted-foreground">
        提交人上传文件时，系统将根据此规则提示文件命名方式
      </p>
    </div>

    <!-- Toggle Options -->
    <div class="space-y-4">
      <!-- Allow Resubmission -->
      <div class="flex items-center justify-between p-4 rounded-lg bg-muted/30">
        <div class="space-y-1">
          <Label class="text-label">允许补交</Label>
          <p class="text-xs text-muted-foreground">
            提交人可在被退回后重新提交材料
          </p>
        </div>
        <Switch
          :checked="props.ruleConfig.allowResubmission"
          @update:checked="(val) => updateConfig('allowResubmission', val)"
        />
      </div>

      <!-- Enable AI Check -->
      <div class="flex items-center justify-between p-4 rounded-lg bg-muted/30">
        <div class="space-y-1">
          <Label class="text-label">启用 AI 缺失检查</Label>
          <p class="text-xs text-muted-foreground">
            系统自动检查是否有遗漏的必填材料
          </p>
        </div>
        <Switch
          :checked="props.ruleConfig.enableAICheck"
          @update:checked="(val) => updateConfig('enableAICheck', val)"
        />
      </div>

      <!-- Anonymous Submit -->
      <div class="flex items-center justify-between p-4 rounded-lg bg-muted/30">
        <div class="space-y-1">
          <Label class="text-label">免登录提交</Label>
          <p class="text-xs text-muted-foreground">
            提交人无需注册账号即可提交材料
          </p>
        </div>
        <Switch
          :checked="props.ruleConfig.anonymousSubmit"
          @update:checked="(val) => updateConfig('anonymousSubmit', val)"
        />
      </div>

      <!-- Allow Preview -->
      <div class="flex items-center justify-between p-4 rounded-lg bg-muted/30">
        <div class="space-y-1">
          <Label class="text-label">允许在线预览</Label>
          <p class="text-xs text-muted-foreground">
            提交人可在上传页面预览已上传的文件
          </p>
        </div>
        <Switch
          :checked="props.ruleConfig.allowPreview"
          @update:checked="(val) => updateConfig('allowPreview', val)"
        />
      </div>

      <!-- Reminder Hours -->
      <div class="space-y-2">
        <Label for="reminder-hours" class="text-label">
          截止前提醒时间 (小时)
        </Label>
        <Input
          id="reminder-hours"
          type="number"
          :value="props.ruleConfig.reminderBeforeDueHours"
          @input="(e) => updateConfig('reminderBeforeDueHours', parseInt((e.target as HTMLInputElement).value) || 24)"
          min="1"
          max="168"
          class="h-10"
        />
        <p class="text-xs text-muted-foreground">
          在截止时间前此时长内，系统将向未提交的提交人发送提醒
        </p>
      </div>
    </div>
  </div>
</template>
