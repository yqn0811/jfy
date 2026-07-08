import { ResourceLibraryData } from './ResourceLibraryData'

export const resourceLibraryDataList: ResourceLibraryData[] = [
  {
    id: 'res_001',
    name: '纯棉四件套-细节图1',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6cd493f4-1863-4dda-9aef-dafb56ef25e5.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/8e21961e-2fef-4984-886e-5bd5b0ffd5d2.png',
    sizeLabel: '1.6 MB',
    sizeBytes: 1677721,
    uploadedAt: '2026-06-15 10:00:00',
    status: 'recent',
    usedByProductId: 'prod_001'
  },
  {
    id: 'res_002',
    name: '窗帘布艺-场景图2',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/a35176b0-d829-4b44-b4bd-58483cfa8878.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/6f2df262-9057-4489-afd4-fa9b4b185098.png',
    sizeLabel: '2.1 MB',
    sizeBytes: 2202009,
    uploadedAt: '2026-06-20 13:20:00',
    status: 'used',
    usedByProductId: 'prod_004'
  },
  {
    id: 'res_003',
    name: '冬被样板-静物图',
    url: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/b70616c5-fa19-4e07-bb76-b06627e86a86.jpeg',
    thumbnailUrl: 'https://spark-builder.s3.cn-north-1.amazonaws.com.cn/image/2026/7/8/25950e63-7646-4461-b33c-017ce226a280.png',
    sizeLabel: '3.2 MB',
    sizeBytes: 3355443,
    uploadedAt: '2026-06-22 15:40:00',
    status: 'unused'
  }
]

export interface ResourceImageVO extends ResourceLibraryData {
  targetType?: string
}

export class ResourceLibraryService {
  static getAll(): ResourceLibraryData[] {
    return resourceLibraryDataList
  }

  static getById(id: string): ResourceLibraryData | undefined {
    return resourceLibraryDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ResourceLibraryData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = resourceLibraryDataList.filter(item => {
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

  static getPickerVO(targetType: string): ResourceImageVO[] {
    return resourceLibraryDataList.map(item => ({ ...item, targetType }))
  }

  static loadPersisted(): ResourceLibraryData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('resourceLibraryDataList')
    return raw ? JSON.parse(raw) as ResourceLibraryData[] : null
  }

  static savePersisted(items: ResourceLibraryData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('resourceLibraryDataList', JSON.stringify(items))
  }
}