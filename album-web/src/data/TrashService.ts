import { TrashData } from './TrashData'

export const trashDataList: TrashData[] = [
  {
    id: 'trash_001',
    itemType: 'product',
    sourceId: 'prod_006',
    name: '天鹅绒遮光帘',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/e317cfe4-0a9d-424b-bcc5-5e0010011cfd.png',
    deletedAt: '2026-07-07 08:00:00',
    canRestore: true
  },
  {
    id: 'trash_002',
    itemType: 'category',
    sourceId: 'cat_005',
    name: '高遮光窗帘',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/2314bd45-2374-4188-b2ef-7e55768f1ea1.png',
    deletedAt: '2026-07-06 16:30:00',
    canRestore: true
  },
  {
    id: 'trash_003',
    itemType: 'image',
    sourceId: 'img_003',
    name: '云感纯棉四件套-面料细节',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/9879a98f-f200-4145-b718-3a37a21cc8e7.png',
    deletedAt: '2026-07-07 09:10:00',
    canRestore: false
  }
]

export class TrashService {
  static getAll(): TrashData[] {
    return trashDataList
  }

  static getById(id: string): TrashData | undefined {
    return trashDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TrashData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = trashDataList.filter(item => {
      const matchKeyword = !keyword || [item.name].some(val => val.toLowerCase().includes(keyword))
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

  static loadPersisted(): TrashData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('trashDataList')
    return raw ? JSON.parse(raw) as TrashData[] : null
  }

  static savePersisted(items: TrashData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('trashDataList', JSON.stringify(items))
  }
}