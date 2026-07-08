import { StorageUsageData } from './StorageUsageData'

export const storageUsageDataList: StorageUsageData[] = [
  {
    id: 'storage_001',
    planName: '免费版',
    totalCapacityMb: 50,
    usedCapacityMb: 38,
    monthlyTrafficGb: 20,
    usedTrafficGb: 8.6,
    concurrentRights: 2,
    expiresAt: '2026-12-31 23:59:59',
    status: 'warning',
    updatedAt: '2026-07-07 12:10:00'
  }
]

export class StorageUsageService {
  static getAll(): StorageUsageData[] {
    return storageUsageDataList
  }

  static getById(id: string): StorageUsageData | undefined {
    return storageUsageDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): StorageUsageData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = storageUsageDataList.filter(item => {
      const matchKeyword = !keyword || [item.planName, item.status].some(val => val.toLowerCase().includes(keyword))
      const matchFilter = Object.entries(filter).every(([key, val]) => {
        if (val === undefined) return true
        const itemVal = (item as any)[key]
        return Array.isArray(val) ? val.includes(itemVal) : itemVal === val
      })
      return matchKeyword && matchFilter
    })
    const sortKey = params.sortKey
    if (!sortKey) return list
    return [...list].sort((a, b) => {
      const av = (a as any)[sortKey]
      const bv = (b as any)[sortKey]
      if (av === bv) return 0
      const direction = params.sortDirection === 'desc' ? -1 : 1
      return av > bv ? direction : -direction
    })
  }

  static loadPersisted(): StorageUsageData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('storageUsageDataList')
    return raw ? JSON.parse(raw) as StorageUsageData[] : null
  }

  static savePersisted(items: StorageUsageData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('storageUsageDataList', JSON.stringify(items))
  }
}