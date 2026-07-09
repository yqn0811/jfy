import type { TeamData } from './TeamData'

export const teamDataList: TeamData[] = [
  {
    id: 'team-001',
    name: '织序传输助手演示团队',
    code: 'ZX-OPS',
    status: 'active',
    plan: 'business',
    memberCount: 18,
    storageLimitGb: 500,
    storageUsedGb: 286.4,
    createdAt: '2025-09-12T08:30:00Z',
    updatedAt: '2026-07-06T09:15:00Z'
  },
  {
    id: 'team-002',
    name: '星河教育资料组',
    code: 'XH-EDU',
    status: 'active',
    plan: 'pro',
    memberCount: 9,
    storageLimitGb: 120,
    storageUsedGb: 58.2,
    createdAt: '2025-10-08T06:00:00Z',
    updatedAt: '2026-07-03T14:20:00Z'
  },
  {
    id: 'team-003',
    name: '清澜人事共享空间',
    code: 'QL-HR',
    status: 'active',
    plan: 'business',
    memberCount: 24,
    storageLimitGb: 800,
    storageUsedGb: 431.7,
    createdAt: '2025-07-18T10:10:00Z',
    updatedAt: '2026-07-07T11:05:00Z'
  },
  {
    id: 'team-004',
    name: '墨点财务交付组',
    code: 'MD-FIN',
    status: 'paused',
    plan: 'starter',
    memberCount: 6,
    storageLimitGb: 30,
    storageUsedGb: 27.9,
    createdAt: '2026-01-05T03:40:00Z',
    updatedAt: '2026-06-28T07:45:00Z'
  }
]

export class TeamService {
  static getAll(): TeamData[] {
    return teamDataList
  }

  static getById(id: string): TeamData | undefined {
    return teamDataList.find((item) => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'plan', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TeamData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return teamDataList
      .filter((item) => {
        const matchKeyword =
          !keyword ||
          [item.name, item.code].some((val) => val.toLowerCase().includes(keyword))

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

  static loadPersisted(): TeamData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('teamDataList')
    return raw ? (JSON.parse(raw) as TeamData[]) : null
  }

  static savePersisted(items: TeamData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('teamDataList', JSON.stringify(items))
  }
}
