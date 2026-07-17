<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import SafeIcon from '@/components/common/SafeIcon.vue'
import { CollectionTaskService } from '@/data/CollectionTaskService'
import { TemplateService } from '@/data/TemplateService'
import type {
  CollectionTaskData,
  TaskFieldConfigData,
  TaskMaterialItemData,
  TaskRuleConfigData,
} from '@/data/CollectionTaskData'
import { FileTransferApi } from '@/data/FileTransferApi'
import { getApiErrorMessage } from '@/lib/apiClient'
import { navigateTo } from '@/navigation'

type RulePayload = Omit<TaskRuleConfigData, 'id' | 'taskId' | 'draftId'>
type SettingsTab = 'basic' | 'info' | 'files'

const isClient = ref(false)
const isSaving = ref(false)
const isCreating = ref(false)
const isSettingsOpen = ref(false)
const activeSettingsTab = ref<SettingsTab>('basic')
const lastSaveTime = ref(0)

const formData = reactive({
  name: '',
  description: '',
  dueAt: '',
  submitTargetDescription: '',
  fields: [] as TaskFieldConfigData[],
  materials: [] as TaskMaterialItemData[],
  ruleConfig: {
    namingRule: '姓名_材料名',
    allowResubmission: true,
    enableAICheck: true,
    anonymousSubmit: true,
    allowPreview: true,
    reminderBeforeDueHours: 24,
  } as RulePayload,
})

const settingsTabs: Array<{ value: SettingsTab; label: string }> = [
  { value: 'basic', label: '基础' },
  { value: 'info', label: '收集信息' },
  { value: 'files', label: '文件' },
]

const fieldTypeOptions: Array<{ value: TaskFieldConfigData['fieldType']; label: string; placeholder: string }> = [
  { value: 'text', label: '文本', placeholder: '请输入内容' },
  { value: 'phone', label: '手机号', placeholder: '请输入手机号' },
  { value: 'email', label: '邮箱', placeholder: '请输入邮箱' },
  { value: 'department', label: '部门', placeholder: '请输入部门' },
  { value: 'studentId', label: '学号', placeholder: '请输入学号' },
  { value: 'projectName', label: '项目名', placeholder: '请输入项目名' },
]

const materialPresets = [
  { value: 'all', label: '不限格式', types: [] as string[] },
  { value: 'image', label: '图片', types: ['jpg', 'jpeg', 'png', 'webp'] },
  { value: 'document', label: '文档', types: ['doc', 'docx', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx'] },
  { value: 'archive', label: '压缩包', types: ['zip', 'rar', '7z'] },
  { value: 'media', label: '音视频', types: ['mp4', 'mov', 'mp3', 'wav'] },
]

const hasDraftContent = computed(() => {
  return Boolean(
    formData.name.trim() ||
      formData.description.trim() ||
      formData.submitTargetDescription.trim() ||
      formData.fields.length ||
      formData.materials.length
  )
})

const canCreateTask = computed(() => {
  return Boolean(
    formData.name.trim() &&
      formData.dueAt &&
      formData.fields.length > 0 &&
      formData.fields.every((field) => field.fieldLabel.trim()) &&
      formData.materials.length > 0 &&
      formData.materials.every((material) => material.materialName.trim() && material.maxSizeMb > 0) &&
      formData.ruleConfig.namingRule.trim()
  )
})

const dueDateLabel = computed(() => {
  if (!formData.dueAt) return '未设置截止时间'
  const date = new Date(formData.dueAt.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return formData.dueAt
  return date.toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
})

const enabledRuleCount = computed(() => {
  return [
    formData.ruleConfig.anonymousSubmit,
    formData.ruleConfig.allowPreview,
    formData.ruleConfig.allowResubmission,
    formData.ruleConfig.enableAICheck,
  ].filter(Boolean).length
})

const primaryMaterial = computed(() => formData.materials[0])

const setDefaultDueAt = () => {
  if (formData.dueAt) return
  const date = new Date()
  date.setDate(date.getDate() + 7)
  date.setHours(18, 0, 0, 0)
  formData.dueAt = toDatetimeLocalValue(date)
}

const toDatetimeLocalValue = (date: Date) => {
  const pad = (value: number) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const makeField = (
  label: string,
  fieldType: TaskFieldConfigData['fieldType'] = 'text',
  required = true
): TaskFieldConfigData => {
  const option = fieldTypeOptions.find((item) => item.value === fieldType)
  return {
    id: `field-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    taskId: null,
    draftId: null,
    fieldKey: `field_${formData.fields.length + 1}`,
    fieldLabel: label,
    fieldType,
    required,
    placeholder: option?.placeholder || '请输入内容',
    order: formData.fields.length + 1,
  }
}

const makeMaterial = (name: string, types: string[] = [], maxSizeMb = 100): TaskMaterialItemData => {
  return {
    id: `mat-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    taskId: null,
    draftId: null,
    materialName: name,
    fileTypes: types,
    required: true,
    maxSizeMb,
    order: formData.materials.length + 1,
  }
}

const ensureDefaults = () => {
  if (formData.fields.length === 0) {
    formData.fields = [makeField('姓名', 'text'), makeField('手机号', 'phone')]
  }
  if (formData.materials.length === 0) {
    formData.materials = [makeMaterial('上传文件')]
  }
  setDefaultDueAt()
}

const addField = (fieldType: TaskFieldConfigData['fieldType'] = 'text') => {
  const option = fieldTypeOptions.find((item) => item.value === fieldType)
  formData.fields.push(makeField(option?.label || '自定义字段', fieldType))
}

const removeField = (index: number) => {
  formData.fields.splice(index, 1)
  reorderFields()
}

const updateField = (index: number, key: keyof TaskFieldConfigData, value: any) => {
  formData.fields[index] = { ...formData.fields[index], [key]: value }
  if (key === 'fieldType') {
    const option = fieldTypeOptions.find((item) => item.value === value)
    formData.fields[index].placeholder = option?.placeholder || formData.fields[index].placeholder
  }
}

const reorderFields = () => {
  formData.fields.forEach((field, index) => {
    field.order = index + 1
    field.fieldKey = `field_${index + 1}`
  })
}

const addMaterial = () => {
  formData.materials.push(makeMaterial(`上传文件 ${formData.materials.length + 1}`))
}

const removeMaterial = (index: number) => {
  formData.materials.splice(index, 1)
  reorderMaterials()
}

const updateMaterial = (index: number, key: keyof TaskMaterialItemData, value: any) => {
  formData.materials[index] = { ...formData.materials[index], [key]: value }
}

const reorderMaterials = () => {
  formData.materials.forEach((material, index) => {
    material.order = index + 1
  })
}

const applyMaterialPreset = (index: number, presetValue: string) => {
  const preset = materialPresets.find((item) => item.value === presetValue)
  if (!preset) return
  updateMaterial(index, 'fileTypes', [...preset.types])
}

const getMaterialPresetValue = (material: TaskMaterialItemData) => {
  const types = [...material.fileTypes].sort().join(',')
  const preset = materialPresets.find((item) => [...item.types].sort().join(',') === types)
  return preset?.value || 'all'
}

const formatFileTypes = (types: string[]) => (types.length ? types.join(', ') : '不限格式')

const parseFileTypes = (value: string) => {
  return value
    .split(',')
    .map((item) => item.trim().replace(/^\./, '').toLowerCase())
    .filter(Boolean)
}

const validateBeforeCreate = () => {
  if (canCreateTask.value) return true
  if (!formData.name.trim()) toast.error('请填写收集主题')
  else if (!formData.dueAt) toast.error('请设置截止时间')
  else if (formData.fields.length === 0) toast.error('请至少配置一个收集信息字段')
  else if (formData.fields.some((field) => !field.fieldLabel.trim())) toast.error('请补全收集信息字段名称')
  else if (formData.materials.length === 0) toast.error('请至少配置一个文件材料')
  else if (formData.materials.some((material) => !material.materialName.trim())) toast.error('请补全文件名称')
  else toast.error('请补全创建任务所需信息')
  return false
}

const autoSaveDraft = () => {
  const now = Date.now()
  if (now - lastSaveTime.value < 800 || !hasDraftContent.value) return

  isSaving.value = true
  lastSaveTime.value = now

  try {
    const draft = {
      id: `draft-${Date.now()}`,
      templateId: null,
      name: formData.name,
      description: formData.description,
      dueAt: formData.dueAt,
      submitTargetDescription: formData.submitTargetDescription,
      submitterFieldIds: formData.fields.map((field) => field.id),
      materialItemIds: formData.materials.map((material) => material.id),
      ruleConfigId: `rule-${Date.now()}`,
      stepIndex: 1,
      savedAt: new Date().toISOString(),
    }

    localStorage.setItem('collectionTaskDraft', JSON.stringify(draft))
    localStorage.setItem('collectionTaskDraftFields', JSON.stringify(formData.fields))
    localStorage.setItem('collectionTaskDraftMaterials', JSON.stringify(formData.materials))
    localStorage.setItem('collectionTaskDraftRuleConfig', JSON.stringify(formData.ruleConfig))
  } catch (error) {
    console.error('Save draft error:', error)
  } finally {
    isSaving.value = false
  }
}

const loadDraftFromStorage = () => {
  try {
    const draftStr = localStorage.getItem('collectionTaskDraft')
    const fieldsStr = localStorage.getItem('collectionTaskDraftFields')
    const materialsStr = localStorage.getItem('collectionTaskDraftMaterials')
    const ruleConfigStr = localStorage.getItem('collectionTaskDraftRuleConfig')

    if (draftStr) {
      const draft = JSON.parse(draftStr)
      formData.name = draft.name || ''
      formData.description = draft.description || ''
      formData.dueAt = draft.dueAt || ''
      formData.submitTargetDescription = draft.submitTargetDescription || ''
    }

    if (fieldsStr) formData.fields = JSON.parse(fieldsStr)
    if (materialsStr) formData.materials = JSON.parse(materialsStr)
    if (ruleConfigStr) formData.ruleConfig = { ...formData.ruleConfig, ...JSON.parse(ruleConfigStr) }
  } catch (error) {
    console.error('Load draft error:', error)
  }
}

const persistCreatedTask = (task: CollectionTaskData) => {
  const allTasks = CollectionTaskService.getAll()
  const existingIndex = allTasks.findIndex((item) => item.id === task.id)
  if (existingIndex >= 0) allTasks[existingIndex] = task
  else allTasks.push(task)
  CollectionTaskService.savePersisted(allTasks)
}

const clearDraftStorage = () => {
  localStorage.removeItem('collectionTaskDraft')
  localStorage.removeItem('collectionTaskDraftFields')
  localStorage.removeItem('collectionTaskDraftMaterials')
  localStorage.removeItem('collectionTaskDraftRuleConfig')
}

const handleCreateTask = async () => {
  if (!validateBeforeCreate()) {
    isSettingsOpen.value = true
    return
  }

  isCreating.value = true

  try {
    const created = await FileTransferApi.createCollectionTask({
      templateId: null,
      name: formData.name.trim(),
      description: formData.description.trim(),
      dueAt: formData.dueAt,
      submitTargetDescription: formData.submitTargetDescription.trim(),
      fields: formData.fields,
      materials: formData.materials,
      ruleConfig: formData.ruleConfig,
    })

    persistCreatedTask(created.task)
    clearDraftStorage()
    toast.success('收集任务创建成功')

    setTimeout(() => {
      navigateTo(`/task-details?taskId=${created.task.id}`)
    }, 500)
  } catch (error) {
    console.error('Create task error:', error)
    toast.error(getApiErrorMessage(error, '创建任务失败，请重试'))
  } finally {
    isCreating.value = false
  }
}

const applyTemplateFromQuery = () => {
  const params = new URLSearchParams(window.location.search)
  const templateId = params.get('templateId')
  if (!templateId) return
  const template = TemplateService.getById(templateId)
  if (!template) return
  formData.name = `${template.name} - ${new Date().toLocaleDateString()}`
  formData.description = template.description
}

onMounted(() => {
  applyTemplateFromQuery()
  loadDraftFromStorage()
  ensureDefaults()
  isClient.value = true
})

watch(
  () => [
    formData.name,
    formData.description,
    formData.dueAt,
    formData.submitTargetDescription,
    JSON.stringify(formData.fields),
    JSON.stringify(formData.materials),
    JSON.stringify(formData.ruleConfig),
  ],
  () => autoSaveDraft(),
  { deep: true }
)
</script>

<template>
  <div v-if="isClient" class="collection-page">
    <div class="collection-background" aria-hidden="true"></div>
    <section class="collection-stage">
      <div class="collection-card">
        <div class="collection-card__header">
          <div>
            <p class="collection-eyebrow">收文件</p>
            <h1>创建收集任务</h1>
          </div>
          <div class="collection-save-state">
            <SafeIcon v-if="isSaving" name="Loader2" :size="14" class="animate-spin" />
            <SafeIcon v-else name="Check" :size="14" />
            <span>{{ isSaving ? '保存中' : '草稿已保存' }}</span>
          </div>
        </div>

        <div class="collection-form">
          <Input
            v-model="formData.name"
            class="line-input"
            placeholder="收集主题，例如：毕业材料收集"
            maxlength="80"
          />
          <Input
            v-model="formData.submitTargetDescription"
            class="line-input"
            placeholder="收集对象，例如：2026 届全体学生（可不填）"
            maxlength="120"
          />
          <Textarea
            v-model="formData.description"
            class="line-input line-textarea"
            placeholder="留言或说明，例如：请按要求上传材料（可不填）"
            maxlength="500"
          />
        </div>

        <div class="collection-summary">
          <div>
            <span>{{ formData.fields.length }}</span>
            <small>信息字段</small>
          </div>
          <div>
            <span>{{ formData.materials.length }}</span>
            <small>文件项</small>
          </div>
          <div>
            <span>{{ enabledRuleCount }}</span>
            <small>已启用规则</small>
          </div>
        </div>

        <div class="collection-actions">
          <Button
            variant="outline"
            class="settings-button"
            aria-label="打开收集设置"
            @click="isSettingsOpen = true"
          >
            <SafeIcon name="Settings" :size="20" />
          </Button>
          <Button
            class="create-button"
            :disabled="isCreating"
            @click="handleCreateTask"
          >
            <SafeIcon v-if="isCreating" name="Loader2" :size="18" class="mr-2 animate-spin" />
            <SafeIcon v-else name="Download" :size="18" class="mr-2" />
            创建收集
          </Button>
        </div>
      </div>

      <div class="collection-warning">
        <SafeIcon name="ShieldAlert" :size="18" />
        收集违法、违规等有害信息，会受到司法严惩。
      </div>

      <aside class="quick-settings">
        <Button variant="secondary" class="quick-pill" @click="isSettingsOpen = true">
          <SafeIcon name="KeyRound" :size="16" />
          设置
        </Button>
        <Button variant="secondary" size="icon" class="quick-icon" @click="activeSettingsTab = 'basic'; isSettingsOpen = true">
          <SafeIcon name="CalendarDays" :size="18" />
        </Button>
        <Button variant="secondary" size="icon" class="quick-icon" @click="activeSettingsTab = 'files'; isSettingsOpen = true">
          <SafeIcon name="FileCog" :size="18" />
        </Button>
      </aside>
    </section>

    <Dialog v-model:open="isSettingsOpen">
      <DialogContent class="settings-dialog max-w-[760px] p-0 sm:rounded-lg">
        <DialogHeader class="sr-only">
          <DialogTitle>收集设置</DialogTitle>
          <DialogDescription>配置基础规则、收集信息和文件限制。</DialogDescription>
        </DialogHeader>

        <div class="settings-shell">
          <div class="settings-tabs" role="tablist" aria-label="收集设置分类">
            <button
              v-for="tab in settingsTabs"
              :key="tab.value"
              type="button"
              :class="{ active: activeSettingsTab === tab.value }"
              @click="activeSettingsTab = tab.value"
            >
              {{ tab.label }}
            </button>
          </div>

          <div v-if="activeSettingsTab === 'basic'" class="settings-panel">
            <div class="setting-row">
              <div>
                <h3>截止时间</h3>
                <p>截止后提交页将按任务状态限制继续提交。</p>
              </div>
              <Input v-model="formData.dueAt" type="datetime-local" class="setting-control" />
            </div>

            <div class="setting-row">
              <div>
                <h3>免登录提交</h3>
                <p>开启后提交人不用注册账号也可以上传。</p>
              </div>
              <Switch
                v-model="formData.ruleConfig.anonymousSubmit"
              />
            </div>

            <div class="setting-row">
              <div>
                <h3>允许在线预览</h3>
                <p>提交人可在上传页查看已上传文件。</p>
              </div>
              <Switch
                v-model="formData.ruleConfig.allowPreview"
              />
            </div>

            <div class="setting-row">
              <div>
                <h3>允许补交</h3>
                <p>材料被退回后，可重新补充提交。</p>
              </div>
              <Switch
                v-model="formData.ruleConfig.allowResubmission"
              />
            </div>

            <div class="setting-row">
              <div>
                <h3>截止前提醒</h3>
                <p>用于任务详情里的提醒规则。</p>
              </div>
              <Input
                :model-value="formData.ruleConfig.reminderBeforeDueHours"
                type="number"
                min="1"
                max="168"
                class="setting-control compact"
                @update:model-value="(value) => formData.ruleConfig.reminderBeforeDueHours = Number(value) || 24"
              />
            </div>
          </div>

          <div v-else-if="activeSettingsTab === 'info'" class="settings-panel">
            <div class="setting-row align-start">
              <div>
                <h3>收集信息</h3>
                <p>上传人提交文件前需要填写的信息。</p>
              </div>
              <Button variant="outline" size="sm" @click="addField()">
                <SafeIcon name="Plus" :size="16" class="mr-1" />
                添加字段
              </Button>
            </div>

            <div class="item-list">
              <div v-for="(field, index) in formData.fields" :key="field.id" class="config-item">
                <div class="config-grid field-grid">
                  <Input
                    :model-value="field.fieldLabel"
                    placeholder="字段名称"
                    @update:model-value="(value) => updateField(index, 'fieldLabel', String(value))"
                  />
                  <Select
                    :model-value="field.fieldType"
                    @update:model-value="(value) => updateField(index, 'fieldType', value)"
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in fieldTypeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <Input
                    :model-value="field.placeholder"
                    placeholder="提示文本"
                    @update:model-value="(value) => updateField(index, 'placeholder', String(value))"
                  />
                </div>
                <div class="config-item__footer">
                  <Label class="switch-label">
                    <Switch v-model="field.required" />
                    必填
                  </Label>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="danger-icon"
                    :disabled="formData.fields.length <= 1"
                    @click="removeField(index)"
                  >
                    <SafeIcon name="Trash2" :size="16" />
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="settings-panel">
            <div class="setting-row align-start">
              <div>
                <h3>文件设置</h3>
                <p>配置需要收集的文件项、格式和大小限制。</p>
              </div>
              <Button variant="outline" size="sm" @click="addMaterial">
                <SafeIcon name="Plus" :size="16" class="mr-1" />
                添加文件项
              </Button>
            </div>

            <div class="setting-row">
              <div>
                <h3>文件命名规则</h3>
                <p>提交页会按该规则提示上传人命名。</p>
              </div>
              <Input
                v-model="formData.ruleConfig.namingRule"
                placeholder="例如：姓名_材料名"
                class="setting-control"
              />
            </div>

            <div class="setting-row">
              <div>
                <h3>启用缺失检查</h3>
                <p>系统按必填材料检查是否有遗漏。</p>
              </div>
              <Switch
                v-model="formData.ruleConfig.enableAICheck"
              />
            </div>

            <div class="item-list">
              <div v-for="(material, index) in formData.materials" :key="material.id" class="config-item">
                <div class="config-grid material-grid">
                  <Input
                    :model-value="material.materialName"
                    placeholder="文件项名称"
                    @update:model-value="(value) => updateMaterial(index, 'materialName', String(value))"
                  />
                  <Select
                    :model-value="getMaterialPresetValue(material)"
                    @update:model-value="(value) => applyMaterialPreset(index, String(value))"
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="preset in materialPresets" :key="preset.value" :value="preset.value">
                        {{ preset.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <Input
                    :model-value="material.maxSizeMb"
                    type="number"
                    min="1"
                    max="500"
                    @update:model-value="(value) => updateMaterial(index, 'maxSizeMb', Number(value) || 100)"
                  />
                </div>
                <Input
                  :model-value="formatFileTypes(material.fileTypes)"
                  placeholder="自定义格式，逗号分隔；留空表示不限"
                  @update:model-value="(value) => updateMaterial(index, 'fileTypes', parseFileTypes(String(value)))"
                />
                <div class="config-item__footer">
                  <Label class="switch-label">
                    <Switch v-model="material.required" />
                    必传文件
                  </Label>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="danger-icon"
                    :disabled="formData.materials.length <= 1"
                    @click="removeMaterial(index)"
                  >
                    <SafeIcon name="Trash2" :size="16" />
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div class="settings-footer">
            <div>
              <strong>{{ dueDateLabel }}</strong>
              <span>{{ primaryMaterial ? primaryMaterial.materialName : '未配置文件项' }}</span>
            </div>
            <Button @click="isSettingsOpen = false">完成</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
.collection-page {
  position: relative;
  min-height: calc(100vh - var(--header-height));
  overflow: hidden;
  background: #dbeafe;
}

.collection-background {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(7, 23, 48, 0.28), rgba(255, 255, 255, 0.08) 36%, rgba(44, 99, 160, 0.28)),
    url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=2400&q=80');
  background-position: center;
  background-size: cover;
  filter: saturate(0.9);
}

.collection-background::after {
  position: absolute;
  inset: 0;
  content: '';
  background: rgba(226, 238, 255, 0.44);
}

.collection-stage {
  position: relative;
  display: flex;
  min-height: calc(100vh - var(--header-height));
  align-items: center;
  justify-content: center;
  padding: 56px 24px 88px;
}

.collection-card {
  width: min(520px, calc(100vw - 40px));
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18px 52px rgba(31, 65, 112, 0.2);
  padding: 32px 36px 28px;
}

.collection-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}

.collection-eyebrow {
  margin: 0 0 4px;
  color: #4f84f8;
  font-size: 14px;
  font-weight: 600;
}

.collection-card h1 {
  margin: 0;
  color: #1f2937;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: 0;
}

.collection-save-state {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #64748b;
  font-size: 12px;
  white-space: nowrap;
}

.collection-form {
  display: grid;
  gap: 2px;
}

.line-input {
  height: 58px;
  border-width: 0 0 1px;
  border-color: #d6dce7;
  border-radius: 0;
  background: transparent;
  padding-inline: 0;
  color: #334155;
  font-size: 17px;
  box-shadow: none;
}

.line-input:focus-visible {
  border-color: #4f84f8;
  box-shadow: none;
  ring-offset-width: 0;
}

.line-textarea {
  min-height: 74px;
  resize: none;
  padding-top: 18px;
}

.collection-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin: 22px 0 20px;
}

.collection-summary div {
  border: 1px solid #e6ebf3;
  border-radius: 8px;
  padding: 10px;
  background: #f8fafc;
  text-align: center;
}

.collection-summary span,
.collection-summary small {
  display: block;
}

.collection-summary span {
  color: #2563eb;
  font-size: 18px;
  font-weight: 700;
}

.collection-summary small {
  margin-top: 2px;
  color: #64748b;
  font-size: 12px;
}

.collection-actions {
  display: grid;
  grid-template-columns: 104px 1fr;
  gap: 14px;
}

.settings-button,
.create-button {
  height: 48px;
  border-radius: 8px;
}

.settings-button {
  border-color: #9bbcff;
  color: #3b78ff;
}

.create-button {
  background: #3f83f8;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
}

.create-button:hover {
  background: #2f72ed;
}

.collection-warning {
  position: absolute;
  left: 50%;
  bottom: 42px;
  display: inline-flex;
  min-height: 50px;
  transform: translateX(-50%);
  align-items: center;
  gap: 10px;
  border-radius: 8px;
  background: #f59e0b;
  padding: 0 28px;
  color: white;
  font-size: 16px;
  font-weight: 600;
  box-shadow: 0 12px 28px rgba(180, 83, 9, 0.22);
  white-space: nowrap;
}

.quick-settings {
  position: absolute;
  top: 48px;
  right: 54px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.quick-pill,
.quick-icon {
  height: 44px;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.12);
}

.quick-pill {
  gap: 7px;
  padding-inline: 20px;
  color: #475569;
}

.quick-icon {
  width: 44px;
  color: #64748b;
}

.settings-dialog {
  overflow: hidden;
  border: 0;
  background: white;
}

.settings-shell {
  display: flex;
  max-height: min(76vh, 760px);
  min-height: 560px;
  flex-direction: column;
}

.settings-tabs {
  display: flex;
  align-items: center;
  gap: 28px;
  border-bottom: 3px solid #d9dce2;
  padding: 24px 42px 0;
}

.settings-tabs button {
  position: relative;
  height: 52px;
  border: 0;
  background: transparent;
  color: #334155;
  font-size: 20px;
  font-weight: 500;
  letter-spacing: 0;
}

.settings-tabs button.active {
  color: #3f83f8;
}

.settings-tabs button.active::after {
  position: absolute;
  right: 0;
  bottom: -3px;
  left: 0;
  height: 3px;
  content: '';
  background: #5b8cff;
}

.settings-panel {
  flex: 1;
  overflow-y: auto;
  padding: 30px 42px 24px;
}

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
  border-bottom: 1px solid #edf1f7;
  padding: 18px 0;
}

.setting-row.align-start {
  align-items: flex-start;
}

.setting-row h3 {
  position: relative;
  margin: 0;
  padding-left: 18px;
  color: #334155;
  font-size: 18px;
  font-weight: 600;
  letter-spacing: 0;
}

.setting-row h3::before {
  position: absolute;
  top: 3px;
  bottom: 3px;
  left: 0;
  width: 4px;
  content: '';
  background: #5b8cff;
}

.setting-row p {
  margin: 8px 0 0 18px;
  color: #8a94a6;
  font-size: 14px;
}

.setting-control {
  width: 240px;
  height: 44px;
  flex: 0 0 auto;
}

.setting-control.compact {
  width: 112px;
}

.item-list {
  display: grid;
  gap: 14px;
  margin-top: 16px;
}

.config-item {
  display: grid;
  gap: 12px;
  border: 1px solid #e6ebf3;
  border-radius: 8px;
  background: #f8fafc;
  padding: 14px;
}

.config-grid {
  display: grid;
  gap: 12px;
}

.field-grid {
  grid-template-columns: 1.15fr 136px 1.35fr;
}

.material-grid {
  grid-template-columns: 1fr 150px 110px;
}

.config-item__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.switch-label {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #475569;
  font-size: 14px;
}

.danger-icon {
  color: #ef4444;
}

.settings-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  border-top: 1px solid #e8edf5;
  background: #fbfdff;
  padding: 16px 42px;
}

.settings-footer div {
  display: grid;
  gap: 2px;
  color: #64748b;
  font-size: 13px;
}

.settings-footer strong {
  color: #334155;
  font-size: 14px;
}

@media (max-width: 900px) {
  .quick-settings {
    display: none;
  }

  .settings-shell {
    min-height: 520px;
  }

  .field-grid,
  .material-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .collection-stage {
    align-items: flex-start;
    padding: 32px 18px 104px;
  }

  .collection-card {
    width: 100%;
    padding: 24px 22px;
  }

  .collection-card__header,
  .setting-row,
  .settings-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .collection-actions,
  .collection-summary {
    grid-template-columns: 1fr;
  }

  .settings-tabs {
    gap: 18px;
    padding-inline: 24px;
  }

  .settings-tabs button {
    font-size: 17px;
  }

  .settings-panel,
  .settings-footer {
    padding-inline: 24px;
  }

  .setting-control {
    width: 100%;
  }

  .collection-warning {
    right: 18px;
    left: 18px;
    justify-content: center;
    transform: none;
    padding-inline: 14px;
    white-space: normal;
    text-align: center;
  }
}
</style>
