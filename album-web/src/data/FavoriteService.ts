import { FavoriteData } from './FavoriteData'

export const favoriteDataList: FavoriteData[] = [
  {
    id: 'fav_001',
    userId: 'user_002',
    targetType: 'home',
    targetId: 'home_001',
    createdAt: '2026-07-05 19:20:00'
  },
  {
    id: 'fav_002',
    userId: 'user_002',
    targetType: 'category',
    targetId: 'cat_002',
    createdAt: '2026-07-05 19:30:00'
  },
  {
    id: 'fav_003',
    userId: 'user_002',
    targetType: 'product',
    targetId: 'prod_001',
    createdAt: '2026-07-05 19:40:00'
  }
]

export interface FavoriteVO extends FavoriteData {
  targetTitle?: string
  targetSubtitle?: string
  targetCoverUrl?: string
}

export class FavoriteService {
  static getAll(): FavoriteData[] {
    return favoriteDataList
  }

  static getById(id: string): FavoriteData | undefined {
    return favoriteDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): FavoriteData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = favoriteDataList.filter(item => {
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

  static getListVO(): FavoriteVO[] {
    return favoriteDataList.map(item => ({ ...item }))
  }

  static loadPersisted(): FavoriteData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('favoriteDataList')
    return raw ? JSON.parse(raw) as FavoriteData[] : null
  }

  static savePersisted(items: FavoriteData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('favoriteDataList', JSON.stringify(items))
  }
}