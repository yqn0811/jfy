export interface CollectionTaskData {
  id: string
  teamId: string
  templateId?: string | null
  name: string
  description: string
  status: 'draft' | 'collecting' | 'pending_review' | 'need_resubmission' | 'approved' | 'archived' | 'expired'
  dueAt: string
  submitTargetDescription: string
  submitterFieldIds: string[]
  materialItemIds: string[]
  ruleConfigId: string
  createdAt: string
  updatedAt: string
  archivedAt?: string | null
  ownerId: string
  accessCodeRequired?: boolean
}

export interface CollectionTaskDraftData {
  id: string
  templateId?: string | null
  name: string
  description: string
  dueAt: string
  submitTargetDescription: string
  submitterFieldIds: string[]
  materialItemIds: string[]
  ruleConfigId: string
  stepIndex: number
  savedAt: string
}

export interface TaskFieldConfigData {
  id: string
  taskId?: string | null
  draftId?: string | null
  fieldKey: string
  fieldLabel: string
  fieldType: 'text' | 'phone' | 'department' | 'studentId' | 'projectName' | 'email'
  required: boolean
  placeholder: string
  order: number
}

export interface TaskMaterialItemData {
  id: string
  taskId?: string | null
  draftId?: string | null
  materialName: string
  fileTypes: string[]
  required: boolean
  maxSizeMb: number
  order: number
}

export interface TaskRuleConfigData {
  id: string
  taskId?: string | null
  draftId?: string | null
  namingRule: string
  allowResubmission: boolean
  enableAICheck: boolean
  anonymousSubmit: boolean
  allowPreview: boolean
  reminderBeforeDueHours: number
}
