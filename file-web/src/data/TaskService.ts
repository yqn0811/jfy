import type { TaskData } from './TaskData'

export interface TaskSummaryVO {
  id: string
  name: string
  type: TaskData['type']
  status: TaskData['status']
  submitProgress: number
  storageUsedGb: number
  storageLimitGb: number
  dueAt: string
  lastUpdatedAt: string
  overdueLevel: TaskData['overdueLevel']
}

export const taskDataList: TaskData[] = [
  {
    id: 'task-001',
    teamId: 'team-001',
    templateId: 'template-hr-onboarding',
    name: 'HR 入职资料收集 - 7 月批次',
    type: 'collection',
    status: 'collecting',
    submitProgress: 0.78,
    storageUsedGb: 16.2,
    storageLimitGb: 40,
    dueAt: '2026-07-09T12:00:00Z',
    lastUpdatedAt: '2026-07-08T06:10:00Z',
    createdAt: '2026-06-28T02:20:00Z',
    ownerId: 'user-001',
    overdueLevel: 'warning',
    isArchived: false
  },
  {
    id: 'task-002',
    teamId: 'team-001',
    templateId: null,
    name: '摄影交付链接 - 客户陈列馆',
    type: 'send',
    status: 'approved',
    submitProgress: 1,
    storageUsedGb: 24.4,
    storageLimitGb: 50,
    dueAt: '2026-07-14T18:00:00Z',
    lastUpdatedAt: '2026-07-07T10:32:00Z',
    createdAt: '2026-07-01T08:00:00Z',
    ownerId: 'user-002',
    overdueLevel: 'normal',
    isArchived: false
  },
  {
    id: 'task-003',
    teamId: 'team-002',
    templateId: 'template-edu-homework',
    name: '暑期作业材料收集',
    type: 'collection',
    status: 'need_resubmission',
    submitProgress: 0.54,
    storageUsedGb: 8.5,
    storageLimitGb: 20,
    dueAt: '2026-07-08T22:00:00Z',
    lastUpdatedAt: '2026-07-08T08:25:00Z',
    createdAt: '2026-06-20T03:00:00Z',
    ownerId: 'user-003',
    overdueLevel: 'critical',
    isArchived: false
  },
  {
    id: 'task-004',
    teamId: 'team-003',
    templateId: 'template-fin-expense',
    name: '6 月报销凭证归集',
    type: 'collection',
    status: 'pending_review',
    submitProgress: 0.91,
    storageUsedGb: 33.6,
    storageLimitGb: 60,
    dueAt: '2026-07-10T09:00:00Z',
    lastUpdatedAt: '2026-07-07T16:50:00Z',
    createdAt: '2026-06-15T01:40:00Z',
    ownerId: 'user-004',
    overdueLevel: 'warning',
    isArchived: false
  },
  {
    id: 'task-005',
    teamId: 'team-001',
    templateId: null,
    name: '合同签署页补交通知',
    type: 'collection',
    status: 'expired',
    submitProgress: 0.32,
    storageUsedGb: 5.1,
    storageLimitGb: 15,
    dueAt: '2026-07-06T12:00:00Z',
    lastUpdatedAt: '2026-07-07T09:00:00Z',
    createdAt: '2026-06-22T04:15:00Z',
    ownerId: 'user-002',
    overdueLevel: 'critical',
    isArchived: false
  },
  {
    id: 'task-006',
    teamId: 'team-004',
    templateId: 'template-fin-audit',
    name: '财务审计归档任务',
    type: 'archive',
    status: 'archived',
    submitProgress: 1,
    storageUsedGb: 12.8,
    storageLimitGb: 30,
    dueAt: '2026-06-30T18:00:00Z',
    lastUpdatedAt: '2026-07-01T02:30:00Z',
    createdAt: '2026-06-10T05:00:00Z',
    ownerId: 'user-005',
    overdueLevel: 'normal',
    isArchived: true
  },
  {
    id: 'task-007',
    teamId: 'team-003',
    templateId: 'template-photo-delivery',
    name: '产品图交付给市场部',
    type: 'send',
    status: 'draft',
    submitProgress: 0,
    storageUsedGb: 2.3,
    storageLimitGb: 25,
    dueAt: '2026-07-12T14:00:00Z',
    lastUpdatedAt: '2026-07-08T03:10:00Z',
    createdAt: '2026-07-08T03:00:00Z',
    ownerId: 'user-006',
    overdueLevel: 'normal',
    isArchived: false
  },
  {
    id: 'task-008',
    teamId: 'team-002',
    templateId: 'template-supplier-docs',
    name: '供应商资质收集',
    type: 'collection',
    status: 'collecting',
    submitProgress: 0.67,
    storageUsedGb: 14.9,
    storageLimitGb: 35,
    dueAt: '2026-07-09T16:00:00Z',
    lastUpdatedAt: '2026-07-08T07:20:00Z',
    createdAt: '2026-06-25T08:00:00Z',
    ownerId: 'user-007',
    overdueLevel: 'warning',
    isArchived: false
  }
]

export class TaskService {
  static getAll(): TaskData[] {
    return taskDataList
  }

  static getById(id: string): TaskData | undefined {
    return taskDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'type' | 'overdueLevel' | 'teamId', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TaskData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return taskDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.name.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = (item as any)[key]
          return Array.isArray(val) ? val.includes(itemVal) : itemVal === val
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

  static getSummaryList(): TaskSummaryVO[] {
    return taskDataList.map((item) => ({
      id: item.id,
      name: item.name,
      type: item.type,
      status: item.status,
      submitProgress: item.submitProgress,
      storageUsedGb: item.storageUsedGb,
      storageLimitGb: item.storageLimitGb,
      dueAt: item.dueAt,
      lastUpdatedAt: item.lastUpdatedAt,
      overdueLevel: item.overdueLevel
    }))
  }

  static loadPersisted(): TaskData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('taskDataList')
    return raw ? (JSON.parse(raw) as TaskData[]) : null
  }

  static savePersisted(items: TaskData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('taskDataList', JSON.stringify(items))
  }
}
