import type {
  CollectionTaskData,
  CollectionTaskDraftData,
  TaskFieldConfigData,
  TaskMaterialItemData,
  TaskRuleConfigData
} from './CollectionTaskData'

export interface CollectionTaskDraftVO {
  id: string
  name: string
  description: string
  dueAt: string
  submitTargetDescription: string
  fields: TaskFieldConfigData[]
  materials: TaskMaterialItemData[]
  ruleConfig: TaskRuleConfigData | undefined
  stepIndex: number
  savedAt: string
}

export const collectionTaskDataList: CollectionTaskData[] = [
  {
    id: 'task-001',
    teamId: 'team-001',
    templateId: 'template-hr-onboarding',
    name: 'HR 入职资料收集 - 7 月批次',
    description: '请在截止时间前补齐入职资料，便于统一归档与审核。',
    status: 'collecting',
    dueAt: '2026-07-09T12:00:00Z',
    submitTargetDescription: '新入职员工，请按部门通知提交。',
    submitterFieldIds: ['field-001', 'field-002', 'field-003'],
    materialItemIds: ['mat-001', 'mat-002', 'mat-003'],
    ruleConfigId: 'rule-001',
    createdAt: '2026-06-28T02:20:00Z',
    updatedAt: '2026-07-08T06:10:00Z',
    archivedAt: null,
    ownerId: 'user-001'
  },
  {
    id: 'task-003',
    teamId: 'team-002',
    templateId: 'template-edu-homework',
    name: '暑期作业材料收集',
    description: '请提交作业照片与说明文档，便于教师统一检查。',
    status: 'need_resubmission',
    dueAt: '2026-07-08T22:00:00Z',
    submitTargetDescription: '本班学生及家长。',
    submitterFieldIds: ['field-004', 'field-005'],
    materialItemIds: ['mat-004', 'mat-005'],
    ruleConfigId: 'rule-002',
    createdAt: '2026-06-20T03:00:00Z',
    updatedAt: '2026-07-08T08:25:00Z',
    archivedAt: null,
    ownerId: 'user-003'
  },
  {
    id: 'task-004',
    teamId: 'team-003',
    templateId: 'template-fin-expense',
    name: '6 月报销凭证归集',
    description: '统一收集报销附件并进行审核后归档。',
    status: 'pending_review',
    dueAt: '2026-07-10T09:00:00Z',
    submitTargetDescription: '项目成员及财务对接人。',
    submitterFieldIds: ['field-006', 'field-007', 'field-008'],
    materialItemIds: ['mat-006', 'mat-007', 'mat-008'],
    ruleConfigId: 'rule-003',
    createdAt: '2026-06-15T01:40:00Z',
    updatedAt: '2026-07-07T16:50:00Z',
    archivedAt: null,
    ownerId: 'user-004'
  },
  {
    id: 'task-008',
    teamId: 'team-002',
    templateId: 'template-supplier-docs',
    name: '供应商资质收集',
    description: '收集供应商营业执照、资质证书和联系信息。',
    status: 'collecting',
    dueAt: '2026-07-09T16:00:00Z',
    submitTargetDescription: '合作供应商联系人。',
    submitterFieldIds: ['field-009', 'field-010', 'field-011'],
    materialItemIds: ['mat-009', 'mat-010', 'mat-011'],
    ruleConfigId: 'rule-004',
    createdAt: '2026-06-25T08:00:00Z',
    updatedAt: '2026-07-08T07:20:00Z',
    archivedAt: null,
    ownerId: 'user-007'
  }
]

export const collectionTaskDraftDataList: CollectionTaskDraftData[] = [
  {
    id: 'draft-001',
    templateId: 'template-photo-delivery',
    name: '摄影交付任务草稿',
    description: '准备给客户发送的照片交付链接。',
    dueAt: '2026-07-15T18:00:00Z',
    submitTargetDescription: '客户项目负责人。',
    submitterFieldIds: ['field-012'],
    materialItemIds: ['mat-012', 'mat-013'],
    ruleConfigId: 'rule-005',
    stepIndex: 3,
    savedAt: '2026-07-08T04:10:00Z'
  }
]

export const taskFieldConfigDataList: TaskFieldConfigData[] = [
  {
    id: 'field-001',
    taskId: 'task-001',
    fieldKey: 'name',
    fieldLabel: '姓名',
    fieldType: 'text',
    required: true,
    placeholder: '请输入姓名',
    order: 1
  },
  {
    id: 'field-002',
    taskId: 'task-001',
    fieldKey: 'phone',
    fieldLabel: '手机号',
    fieldType: 'phone',
    required: true,
    placeholder: '请输入手机号',
    order: 2
  },
  {
    id: 'field-003',
    taskId: 'task-001',
    fieldKey: 'department',
    fieldLabel: '部门',
    fieldType: 'department',
    required: true,
    placeholder: '请输入部门',
    order: 3
  },
  {
    id: 'field-004',
    taskId: 'task-003',
    fieldKey: 'name',
    fieldLabel: '姓名',
    fieldType: 'text',
    required: true,
    placeholder: '请输入姓名',
    order: 1
  },
  {
    id: 'field-005',
    taskId: 'task-003',
    fieldKey: 'studentId',
    fieldLabel: '学号',
    fieldType: 'studentId',
    required: true,
    placeholder: '请输入学号',
    order: 2
  },
  {
    id: 'field-006',
    taskId: 'task-004',
    fieldKey: 'name',
    fieldLabel: '姓名',
    fieldType: 'text',
    required: true,
    placeholder: '请输入姓名',
    order: 1
  },
  {
    id: 'field-007',
    taskId: 'task-004',
    fieldKey: 'department',
    fieldLabel: '部门',
    fieldType: 'department',
    required: true,
    placeholder: '请输入部门',
    order: 2
  },
  {
    id: 'field-008',
    taskId: 'task-004',
    fieldKey: 'email',
    fieldLabel: '邮箱',
    fieldType: 'email',
    required: false,
    placeholder: '请输入邮箱',
    order: 3
  },
  {
    id: 'field-009',
    taskId: 'task-008',
    fieldKey: 'name',
    fieldLabel: '联系人姓名',
    fieldType: 'text',
    required: true,
    placeholder: '请输入联系人姓名',
    order: 1
  },
  {
    id: 'field-010',
    taskId: 'task-008',
    fieldKey: 'phone',
    fieldLabel: '联系电话',
    fieldType: 'phone',
    required: true,
    placeholder: '请输入联系电话',
    order: 2
  },
  {
    id: 'field-011',
    taskId: 'task-008',
    fieldKey: 'projectName',
    fieldLabel: '项目名',
    fieldType: 'projectName',
    required: false,
    placeholder: '请输入项目名',
    order: 3
  },
  {
    id: 'field-012',
    draftId: 'draft-001',
    fieldKey: 'name',
    fieldLabel: '客户名',
    fieldType: 'text',
    required: true,
    placeholder: '请输入客户名',
    order: 1
  }
]

export const taskMaterialItemDataList: TaskMaterialItemData[] = [
  {
    id: 'mat-001',
    taskId: 'task-001',
    materialName: '身份证正面',
    fileTypes: ['jpg', 'png', 'pdf'],
    required: true,
    maxSizeMb: 10,
    order: 1
  },
  {
    id: 'mat-002',
    taskId: 'task-001',
    materialName: '身份证反面',
    fileTypes: ['jpg', 'png', 'pdf'],
    required: true,
    maxSizeMb: 10,
    order: 2
  },
  {
    id: 'mat-003',
    taskId: 'task-001',
    materialName: '合同签署页',
    fileTypes: ['pdf'],
    required: true,
    maxSizeMb: 20,
    order: 3
  },
  {
    id: 'mat-004',
    taskId: 'task-003',
    materialName: '作业照片',
    fileTypes: ['jpg', 'png'],
    required: true,
    maxSizeMb: 15,
    order: 1
  },
  {
    id: 'mat-005',
    taskId: 'task-003',
    materialName: '说明文档',
    fileTypes: ['docx', 'pdf'],
    required: false,
    maxSizeMb: 20,
    order: 2
  },
  {
    id: 'mat-006',
    taskId: 'task-004',
    materialName: '报销单',
    fileTypes: ['pdf'],
    required: true,
    maxSizeMb: 10,
    order: 1
  },
  {
    id: 'mat-007',
    taskId: 'task-004',
    materialName: '发票',
    fileTypes: ['jpg', 'png', 'pdf'],
    required: true,
    maxSizeMb: 20,
    order: 2
  },
  {
    id: 'mat-008',
    taskId: 'task-004',
    materialName: '付款截图',
    fileTypes: ['jpg', 'png'],
    required: false,
    maxSizeMb: 10,
    order: 3
  },
  {
    id: 'mat-009',
    taskId: 'task-008',
    materialName: '营业执照',
    fileTypes: ['pdf', 'jpg', 'png'],
    required: true,
    maxSizeMb: 10,
    order: 1
  },
  {
    id: 'mat-010',
    taskId: 'task-008',
    materialName: '资质证书',
    fileTypes: ['pdf'],
    required: true,
    maxSizeMb: 15,
    order: 2
  },
  {
    id: 'mat-011',
    taskId: 'task-008',
    materialName: '联系人名片',
    fileTypes: ['jpg', 'png'],
    required: false,
    maxSizeMb: 5,
    order: 3
  },
  {
    id: 'mat-012',
    draftId: 'draft-001',
    materialName: '交付清单',
    fileTypes: ['xlsx'],
    required: true,
    maxSizeMb: 5,
    order: 1
  },
  {
    id: 'mat-013',
    draftId: 'draft-001',
    materialName: '精选成片',
    fileTypes: ['zip', 'rar'],
    required: true,
    maxSizeMb: 500,
    order: 2
  }
]

export const taskRuleConfigDataList: TaskRuleConfigData[] = [
  {
    id: 'rule-001',
    taskId: 'task-001',
    namingRule: '姓名_部门_材料名',
    allowResubmission: true,
    enableAICheck: true,
    anonymousSubmit: false,
    allowPreview: false,
    reminderBeforeDueHours: 24
  },
  {
    id: 'rule-002',
    taskId: 'task-003',
    namingRule: '学号_姓名_作业名',
    allowResubmission: true,
    enableAICheck: true,
    anonymousSubmit: false,
    allowPreview: true,
    reminderBeforeDueHours: 12
  },
  {
    id: 'rule-003',
    taskId: 'task-004',
    namingRule: '姓名_报销月份_材料名',
    allowResubmission: false,
    enableAICheck: true,
    anonymousSubmit: false,
    allowPreview: false,
    reminderBeforeDueHours: 48
  },
  {
    id: 'rule-004',
    taskId: 'task-008',
    namingRule: '联系人_项目名_材料名',
    allowResubmission: true,
    enableAICheck: false,
    anonymousSubmit: true,
    allowPreview: true,
    reminderBeforeDueHours: 24
  },
  {
    id: 'rule-005',
    draftId: 'draft-001',
    namingRule: '客户名_交付内容_序号',
    allowResubmission: true,
    enableAICheck: true,
    anonymousSubmit: false,
    allowPreview: true,
    reminderBeforeDueHours: 24
  }
]

export class CollectionTaskService {
  static getAll(): CollectionTaskData[] {
    return collectionTaskDataList
  }

  static getById(id: string): CollectionTaskData | undefined {
    return collectionTaskDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'templateId' | 'archived' | 'overdueLevel' | 'ownerId', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): CollectionTaskData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return collectionTaskDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.name.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal =
            key === 'archived' ? String(Boolean(item.archivedAt)) : (item as any)[key]
          return Array.isArray(val) ? val.includes(String(itemVal)) : String(itemVal) === String(val)
        })
        return matchKeyword && matchFilter
      })
      .sort((a, b) => {
        if (!sortKey) return 0
        const aVal = (a as any)[sortKey]
        const bVal = (b as any)[sortKey]
        if (aVal === bVal) return 0
        const result = aVal > bVal ? 1 : -1
        return sortDirection === 'asc' ? result : -result
      })
  }

  static getDraftById(id: string): CollectionTaskDraftData | undefined {
    return collectionTaskDraftDataList.find((item) => item.id === id)
  }

  static getDraftVOById(id: string): CollectionTaskDraftVO | undefined {
    const draft = this.getDraftById(id)
    if (!draft) return undefined
    return {
      id: draft.id,
      name: draft.name,
      description: draft.description,
      dueAt: draft.dueAt,
      submitTargetDescription: draft.submitTargetDescription,
      fields: taskFieldConfigDataList.filter((item) => item.draftId === draft.id),
      materials: taskMaterialItemDataList.filter((item) => item.draftId === draft.id),
      ruleConfig: taskRuleConfigDataList.find((item) => item.draftId === draft.id),
      stepIndex: draft.stepIndex,
      savedAt: draft.savedAt
    }
  }

  static createFromDraft(draftId: string): CollectionTaskData | undefined {
    const draft = this.getDraftById(draftId)
    if (!draft) return undefined
    return {
      id: `task-created-${draft.id}`,
      teamId: 'team-001',
      templateId: draft.templateId ?? null,
      name: draft.name,
      description: draft.description,
      status: 'collecting',
      dueAt: draft.dueAt,
      submitTargetDescription: draft.submitTargetDescription,
      submitterFieldIds: draft.submitterFieldIds,
      materialItemIds: draft.materialItemIds,
      ruleConfigId: draft.ruleConfigId,
      createdAt: draft.savedAt,
      updatedAt: draft.savedAt,
      archivedAt: null,
      ownerId: 'user-001'
    }
  }

  static archiveTask(taskId: string): boolean {
    const task = collectionTaskDataList.find((item) => item.id === taskId)
    if (!task) return false
    task.status = 'archived'
    task.archivedAt = '2026-07-08T09:00:00Z'
    task.updatedAt = '2026-07-08T09:00:00Z'
    return true
  }

  static loadPersisted(): CollectionTaskData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('collectionTaskDataList')
    return raw ? (JSON.parse(raw) as CollectionTaskData[]) : null
  }

  static savePersisted(items: CollectionTaskData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('collectionTaskDataList', JSON.stringify(items))
  }
}
