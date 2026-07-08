import { CategoryData } from './CategoryData'

export const categoryDataList: CategoryData[] = [
  {
    id: 'cat_001',
    homeId: 'home_001',
    name: '床品套件',
    intro: '四件套、六件套与礼品装组合。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/161e494c-9552-46b5-8b32-9432cb9e9413.png',
    productCount: 6,
    childCount: 2,
    visibility: 'public',
    layout: 'grid',
    isTop: true,
    updatedAt: '2026-07-02 15:20:00',
    createdAt: '2026-02-12 11:10:00'
  },
  {
    id: 'cat_002',
    homeId: 'home_001',
    parentId: 'cat_001',
    name: '纯棉四件套',
    intro: '适合春夏季节的清爽纯棉面料。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/1886523a-b6b6-4321-a5fa-d2569626552f.png',
    productCount: 4,
    childCount: 0,
    visibility: 'shared',
    layout: 'grid',
    isTop: false,
    updatedAt: '2026-06-21 09:40:00',
    createdAt: '2026-03-05 14:00:00'
  },
  {
    id: 'cat_003',
    homeId: 'home_001',
    parentId: 'cat_001',
    name: '磨毛冬被',
    intro: '秋冬保暖被类产品。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/0c1c2ed8-16c5-4a31-9ca8-b822d7efd994.png',
    productCount: 5,
    childCount: 0,
    visibility: 'private',
    layout: 'list',
    isTop: false,
    updatedAt: '2026-06-18 18:30:00',
    createdAt: '2026-04-11 10:05:00'
  },
  {
    id: 'cat_004',
    homeId: 'home_001',
    name: '窗帘布艺',
    intro: '遮光、绒感与工程布艺方案。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/b238bf46-1e00-4209-b107-0974e9713538.png',
    productCount: 3,
    childCount: 1,
    visibility: 'public',
    layout: 'grid',
    isTop: true,
    updatedAt: '2026-07-04 12:00:00',
    createdAt: '2026-05-02 09:10:00'
  },
  {
    id: 'cat_005',
    homeId: 'home_001',
    parentId: 'cat_004',
    name: '高遮光窗帘',
    intro: '酒店与家装常用遮光方案。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/654831e4-1e98-4d75-9b96-bfa80bc9eb1b.png',
    productCount: 2,
    childCount: 0,
    visibility: 'shared',
    layout: 'list',
    isTop: false,
    updatedAt: '2026-06-30 08:20:00',
    createdAt: '2026-06-01 13:00:00'
  }
]

export interface CategoryVO extends CategoryData {
  parentName?: string
  children?: CategoryVO[]
}

export class CategoryService {
  static getAll(): CategoryData[] {
    return categoryDataList
  }

  static getById(id: string): CategoryData | undefined {
    return categoryDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): CategoryData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = categoryDataList.filter(item => {
      const matchKeyword = !keyword || [item.name, item.intro].some(val => val.toLowerCase().includes(keyword))
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

  static getTreeVO(): CategoryVO[] {
    const map = new Map<string, CategoryVO>()
    categoryDataList.forEach(item => map.set(item.id, { ...item, children: [] }))
    const roots: CategoryVO[] = []
    map.forEach(node => {
      if (node.parentId) {
        const parent = map.get(node.parentId)
        if (parent) parent.children = [...(parent.children ?? []), node]
      } else {
        roots.push(node)
      }
    })
    return roots
  }

  static getByIdVO(id: string): CategoryVO | undefined {
    const item = this.getById(id)
    if (!item) return undefined
    const parentName = item.parentId ? this.getById(item.parentId)?.name : undefined
    return { ...item, parentName }
  }

  static loadPersisted(): CategoryData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('categoryDataList')
    return raw ? JSON.parse(raw) as CategoryData[] : null
  }

  static savePersisted(items: CategoryData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('categoryDataList', JSON.stringify(items))
  }
}