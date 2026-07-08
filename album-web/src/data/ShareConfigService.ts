import { ShareConfigData } from './ShareConfigData'

export const shareConfigDataList: ShareConfigData[] = [
  {
    id: 'share_001',
    homeId: 'home_001',
    shareToken: 'token_yunzhi_001',
    isPublic: true,
    shareTitle: '云织家纺产品主页',
    shareDescription: '展示家纺产品、分类与详情图。',
    shareCoverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/1b05b246-e2e8-4732-9507-4f6720d39448.png',
    isLinkValid: true,
    createdAt: '2026-01-10 09:10:00',
    updatedAt: '2026-07-01 09:00:00'
  }
]

export class ShareConfigService {
  static getAll(): ShareConfigData[] {
    return shareConfigDataList
  }

  static getById(id: string): ShareConfigData | undefined {
    return shareConfigDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ShareConfigData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = shareConfigDataList.filter(item => {
      const matchKeyword = !keyword || [item.shareTitle, item.shareDescription, item.shareToken].some(val => val.toLowerCase().includes(keyword))
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

  static loadPersisted(): ShareConfigData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('shareConfigDataList')
    return raw ? JSON.parse(raw) as ShareConfigData[] : null
  }

  static savePersisted(items: ShareConfigData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('shareConfigDataList', JSON.stringify(items))
  }
}