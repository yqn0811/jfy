import { BrowsingRecordData } from './BrowsingRecordData'

export const browsingRecordDataList: BrowsingRecordData[] = [
  {
    id: 'hist_001',
    userId: 'user_002',
    targetType: 'home',
    targetId: 'home_001',
    viewedAt: '2026-07-05 18:00:00'
  },
  {
    id: 'hist_002',
    userId: 'user_002',
    targetType: 'category',
    targetId: 'cat_002',
    viewedAt: '2026-07-05 18:10:00'
  },
  {
    id: 'hist_003',
    userId: 'user_002',
    targetType: 'product',
    targetId: 'prod_001',
    viewedAt: '2026-07-05 18:20:00'
  }
]

export interface BrowsingRecordVO extends BrowsingRecordData {
  targetTitle?: string
  targetSubtitle?: string
  targetCoverUrl?: string
}

export class BrowsingRecordService {
  static getAll(): BrowsingRecordData[] {
    return browsingRecordDataList
  }

  static getById(id: string): BrowsingRecordData | undefined {
    return browsingRecordDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): BrowsingRecordData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = browsingRecordDataList.filter(item => {
      const matchKeyword = !keyword || [item.targetType, item.targetId].some(val => val.toLowerCase().includes(keyword))
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

  static getListVO(): BrowsingRecordVO[] {
    return browsingRecordDataList.map(item => ({ ...item }))
  }

  static loadPersisted(): BrowsingRecordData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('browsingRecordDataList')
    return raw ? JSON.parse(raw) as BrowsingRecordData[] : null
  }

  static savePersisted(items: BrowsingRecordData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('browsingRecordDataList', JSON.stringify(items))
  }
}