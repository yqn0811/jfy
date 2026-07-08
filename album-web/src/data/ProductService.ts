import { ProductData } from './ProductData'

export const productDataList: ProductData[] = [
  {
    id: 'prod_001',
    homeId: 'home_001',
    categoryId: 'cat_002',
    ownerUserId: 'user_001',
    name: '云感纯棉四件套',
    intro: '适合春夏的轻盈纯棉床品，手感柔软，适合客厅样板与电商展示。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c5a31449-3383-4af3-b43d-092356bfce80.png',
    visibility: 'public',
    hideDetailImage: false,
    isHot: true,
    sortOrder: 1,
    colorChartCount: 5,
    detailChartCount: 4,
    updatedAt: '2026-07-05 09:10:00',
    createdAt: '2026-05-10 10:00:00'
  },
  {
    id: 'prod_002',
    homeId: 'home_001',
    categoryId: 'cat_002',
    ownerUserId: 'user_001',
    name: '沁凉提花四件套',
    intro: '提花纹理清晰，适合夏季清爽风格。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/8fff95bb-a74b-4e99-a3f3-654e1db40bb5.png',
    visibility: 'shared',
    hideDetailImage: true,
    isHot: false,
    sortOrder: 2,
    colorChartCount: 3,
    detailChartCount: 2,
    updatedAt: '2026-06-28 14:20:00',
    createdAt: '2026-05-18 09:30:00'
  },
  {
    id: 'prod_003',
    homeId: 'home_001',
    categoryId: 'cat_003',
    ownerUserId: 'user_001',
    name: '暖绒磨毛冬被',
    intro: '厚实保暖，适合冬季渠道与客户样板。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/17dd0b00-3aab-4526-9c94-c6bf1bea3460.png',
    visibility: 'private',
    hideDetailImage: false,
    isHot: false,
    sortOrder: 3,
    colorChartCount: 4,
    detailChartCount: 5,
    updatedAt: '2026-06-20 11:00:00',
    createdAt: '2026-05-22 16:45:00'
  },
  {
    id: 'prod_004',
    homeId: 'home_001',
    categoryId: 'cat_004',
    ownerUserId: 'user_001',
    name: '极简遮光窗帘',
    intro: '商务空间常用遮光方案，适合工程展示。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6f958559-43f1-4ca5-be22-db3aff1e07ca.png',
    visibility: 'public',
    hideDetailImage: false,
    isHot: true,
    sortOrder: 4,
    colorChartCount: 6,
    detailChartCount: 8,
    updatedAt: '2026-07-03 18:00:00',
    createdAt: '2026-05-28 08:20:00'
  },
  {
    id: 'prod_005',
    homeId: 'home_001',
    categoryId: 'cat_005',
    ownerUserId: 'user_001',
    name: '高遮光酒店帘',
    intro: '酒店项目专用遮光帘，注重褶皱与垂感。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/c66cde7e-b2a6-4735-a29d-33cb0b9db8df.png',
    visibility: 'shared',
    hideDetailImage: true,
    isHot: false,
    sortOrder: 5,
    colorChartCount: 2,
    detailChartCount: 1,
    updatedAt: '2026-07-06 13:10:00',
    createdAt: '2026-06-02 09:00:00'
  },
  {
    id: 'prod_006',
    homeId: 'home_001',
    categoryId: 'cat_004',
    ownerUserId: 'user_001',
    name: '天鹅绒遮光帘',
    intro: '柔软绒感与高遮光性能兼具，适合客厅和卧室。',
    coverUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/7d4e0c79-2aef-40ea-b6ad-d01f0eb42622.png',
    visibility: 'public',
    hideDetailImage: false,
    isHot: false,
    sortOrder: 6,
    colorChartCount: 4,
    detailChartCount: 3,
    updatedAt: '2026-07-01 16:10:00',
    createdAt: '2026-06-10 11:30:00'
  }
]

export interface ProductVO extends ProductData {
  categoryName?: string
  ownerName?: string
  ownerAvatarUrl?: string
}

export interface ProductEditVO extends ProductVO {
  categoryOptions: { label: string; value: string }[]
  availableColorChartCount: number
  availableDetailChartCount: number
}

export interface ProductOwnerVO {
  ownerName: string
  ownerAvatarUrl: string
}

export interface ProductHomeVO {
  homeId: string
  homeName: string
  homeLogoUrl: string
}

export class ProductService {
  static getAll(): ProductData[] {
    return productDataList
  }

  static getById(id: string): ProductData | undefined {
    return productDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ProductData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = productDataList.filter(item => {
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

  static getListVO(): ProductVO[] {
    return productDataList.map(item => ({ ...item }))
  }

  static getDetailVO(id: string): ProductVO | undefined {
    const item = this.getById(id)
    return item ? { ...item } : undefined
  }

  static getEditVO(id: string): ProductEditVO | undefined {
    const item = this.getById(id)
    if (!item) return undefined
    return {
      ...item,
      categoryOptions: [],
      availableColorChartCount: item.colorChartCount,
      availableDetailChartCount: item.detailChartCount
    }
  }

  static loadPersisted(): ProductData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('productDataList')
    return raw ? JSON.parse(raw) as ProductData[] : null
  }

  static savePersisted(items: ProductData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('productDataList', JSON.stringify(items))
  }
}