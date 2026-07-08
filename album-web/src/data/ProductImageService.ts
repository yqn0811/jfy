import { ProductImageData } from './ProductImageData'

export const productImageDataList: ProductImageData[] = [
  {
    id: 'img_001',
    productId: 'prod_001',
    type: 'colorChart',
    name: '云感纯棉四件套-主花型',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/e6db8dad-f2a8-43ea-8539-352e9f1705ea.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/167ae256-9670-44d1-a27a-3761cdf9d3b9.png',
    sizeLabel: '2.4 MB',
    sizeBytes: 2516582,
    sortOrder: 1,
    isOriginalLarge: false,
    createdAt: '2026-05-10 10:10:00'
  },
  {
    id: 'img_002',
    productId: 'prod_001',
    type: 'colorChart',
    name: '云感纯棉四件套-铺床场景',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/dac61102-43af-4e43-a6b6-1d20f433f36f.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/790003b9-7c56-4a7b-b586-ff43a9a1af1f.png',
    sizeLabel: '3.8 MB',
    sizeBytes: 3984588,
    sortOrder: 2,
    isOriginalLarge: true,
    createdAt: '2026-05-10 10:12:00'
  },
  {
    id: 'img_003',
    productId: 'prod_001',
    type: 'detailChart',
    name: '云感纯棉四件套-面料细节',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/47288cd4-8859-4a15-ad53-0c6515878e0c.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/3d17ff40-56e6-480e-9427-01387ff2afa1.png',
    sizeLabel: '1.9 MB',
    sizeBytes: 1992294,
    sortOrder: 1,
    isOriginalLarge: false,
    createdAt: '2026-05-10 10:20:00'
  }
]

export interface ProductImageVO extends ProductImageData {
  productName?: string
  productCoverUrl?: string
}

export class ProductImageService {
  static getAll(): ProductImageData[] {
    return productImageDataList
  }

  static getById(id: string): ProductImageData | undefined {
    return productImageDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ProductImageData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = productImageDataList.filter(item => {
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

  static loadPersisted(): ProductImageData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('productImageDataList')
    return raw ? JSON.parse(raw) as ProductImageData[] : null
  }

  static savePersisted(items: ProductImageData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('productImageDataList', JSON.stringify(items))
  }
}