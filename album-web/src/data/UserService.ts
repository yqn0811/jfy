import { UserData } from './UserData'

export const userDataList: UserData[] = [
  {
    id: 'user_001',
    username: 'yunzhi',
    nickname: '云织主理人',
    avatarUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/98e5967d-09b8-43ee-9951-248ffa1aec4b.png',
    role: 'owner',
    isLoggedIn: true,
    createdAt: '2026-01-10 09:00:00',
    updatedAt: '2026-07-07 12:00:00'
  },
  {
    id: 'user_002',
    username: 'visitor_a',
    nickname: '王女士',
    avatarUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/afb1e574-bdc3-4570-8e99-93d2764079b8.png',
    role: 'visitor',
    isLoggedIn: false,
    createdAt: '2026-03-12 15:30:00',
    updatedAt: '2026-07-04 09:00:00'
  }
]

export class UserService {
  static getAll(): UserData[] {
    return userDataList
  }

  static getById(id: string): UserData | undefined {
    return userDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): UserData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = userDataList.filter(item => {
      const matchKeyword = !keyword || [item.username, item.nickname].some(val => val.toLowerCase().includes(keyword))
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

  static loadPersisted(): UserData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('userDataList')
    return raw ? JSON.parse(raw) as UserData[] : null
  }

  static savePersisted(items: UserData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('userDataList', JSON.stringify(items))
  }
}