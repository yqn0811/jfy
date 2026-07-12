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

export const collectionTaskDataList: CollectionTaskData[] = []

export const collectionTaskDraftDataList: CollectionTaskDraftData[] = []

export const taskFieldConfigDataList: TaskFieldConfigData[] = []

export const taskMaterialItemDataList: TaskMaterialItemData[] = []

export const taskRuleConfigDataList: TaskRuleConfigData[] = []

export class CollectionTaskService {
  static getAll(): CollectionTaskData[] {
    const taskMap = new Map<string, CollectionTaskData>()
    collectionTaskDataList.forEach((item) => taskMap.set(item.id, item))
    this.loadPersisted()?.forEach((item) => taskMap.set(item.id, item))
    return Array.from(taskMap.values())
  }

  static getById(id: string): CollectionTaskData | undefined {
    return this.getAll().find((item) => item.id === id)
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

    return this.getAll()
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
    if (!raw) return null
    const items = JSON.parse(raw) as CollectionTaskData[]
    const filtered = items.filter((item) => !/^task-\d{3}$/.test(String(item.id)))
    if (filtered.length !== items.length) {
      localStorage.setItem('collectionTaskDataList', JSON.stringify(filtered))
    }
    return filtered
  }

  static savePersisted(items: CollectionTaskData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('collectionTaskDataList', JSON.stringify(items))
  }
}
