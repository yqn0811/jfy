import { PlanPackageData } from './PlanPackageData'

export const planPackageDataList: PlanPackageData[] = [
  {
    id: 'resource_30g',
    name: '标准资源包',
    packageType: 'resource_storage',
    capacityMb: 30 * 1024,
    price: '¥299 / 年',
    concurrentRights: 8,
    trafficGb: 30,
    durationLabel: '1 年',
    isRecommended: true,
    createdAt: '2026-01-12 10:00:00'
  },
  {
    id: 'resource_100g',
    name: '专业资源包',
    packageType: 'resource_storage',
    capacityMb: 100 * 1024,
    price: '¥999 / 年',
    concurrentRights: 10,
    trafficGb: 100,
    durationLabel: '1 年',
    isRecommended: false,
    createdAt: '2026-01-12 10:05:00'
  }
]

export class PlanPackageService {
  static getAll(): PlanPackageData[] {
    return planPackageDataList
  }

  static getById(id: string): PlanPackageData | undefined {
    return planPackageDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): PlanPackageData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = planPackageDataList.filter(item => {
      const matchKeyword = !keyword || [item.name, item.price, item.durationLabel].some(val => val.toLowerCase().includes(keyword))
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

  static loadPersisted(): PlanPackageData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('planPackageDataList')
    return raw ? JSON.parse(raw) as PlanPackageData[] : null
  }

  static savePersisted(items: PlanPackageData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('planPackageDataList', JSON.stringify(items))
  }
}
