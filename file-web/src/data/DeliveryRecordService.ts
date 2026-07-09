import type { DeliveryRecordData, DeliveryRecordVO } from './DeliveryRecordData'

export const deliveryRecordDataList: DeliveryRecordData[] = [
  {
    id: 'record-001',
    name: 'HR 入职资料收集 - 7 月批次',
    type: 'collected',
    status: 'collecting',
    fileCount: 15,
    storageSizeMb: 3400,
    createdAt: '2026-06-28T02:20:00Z',
    expiresAt: '2026-07-09T12:00:00Z',
    lastActorName: '管理员',
    isArchived: false
  },
  {
    id: 'record-002',
    name: '摄影交付链接 - 客户陈列馆',
    type: 'sent',
    status: 'approved',
    fileCount: 8,
    storageSizeMb: 1840,
    createdAt: '2026-07-07T10:20:00Z',
    expiresAt: '2026-07-18T18:00:00Z',
    lastActorName: '陈女士',
    isArchived: false
  },
  {
    id: 'record-003',
    name: '暑期作业材料收集',
    type: 'collected',
    status: 'need_resubmission',
    fileCount: 12,
    storageSizeMb: 680,
    createdAt: '2026-06-20T03:00:00Z',
    expiresAt: '2026-07-08T22:00:00Z',
    lastActorName: '张老师',
    isArchived: false
  },
  {
    id: 'record-004',
    name: '6 月报销凭证归集',
    type: 'archived',
    status: 'archived',
    fileCount: 26,
    storageSizeMb: 5210,
    createdAt: '2026-06-15T01:40:00Z',
    expiresAt: '2026-07-10T09:00:00Z',
    lastActorName: '财务专员',
    isArchived: true
  },
  {
    id: 'record-005',
    name: '供应商资质收集',
    type: 'received',
    status: 'pending_review',
    fileCount: 9,
    storageSizeMb: 1120,
    createdAt: '2026-06-25T08:00:00Z',
    expiresAt: '2026-07-09T16:00:00Z',
    lastActorName: '采购经理',
    isArchived: false
  }
]

export class DeliveryRecordService {
  static getAll(): DeliveryRecordData[] {
    return deliveryRecordDataList
  }

  static getById(id: string): DeliveryRecordData | undefined {
    return deliveryRecordDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'type' | 'status' | 'isArchived' | 'dateRange', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): DeliveryRecordData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return deliveryRecordDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.name.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = key === 'isArchived' ? String(item.isArchived) : (item as any)[key]
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

  static getRecordVOList(): DeliveryRecordVO[] {
    return deliveryRecordDataList.map((item) => ({
      id: item.id,
      name: item.name,
      type: item.type,
      status: item.status,
      fileCount: item.fileCount,
      storageSizeMb: item.storageSizeMb,
      createdAt: item.createdAt,
      expiresAt: item.expiresAt,
      lastActorName: item.lastActorName
    }))
  }

  static loadPersisted(): DeliveryRecordData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('deliveryRecordDataList')
    return raw ? (JSON.parse(raw) as DeliveryRecordData[]) : null
  }

  static savePersisted(items: DeliveryRecordData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('deliveryRecordDataList', JSON.stringify(items))
  }
}
