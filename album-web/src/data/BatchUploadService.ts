import { BatchUploadData } from './BatchUploadData'

export const batchUploadDataList: BatchUploadData[] = [
  {
    id: 'batch_001',
    productId: 'prod_001',
    uploadUrl: 'https://example.com/upload/prod_001',
    passwordEnabled: true,
    password: 'A7K3',
    expire: '7d',
    isClosed: false,
    createdAt: '2026-07-05 08:00:00',
    updatedAt: '2026-07-05 08:10:00'
  }
]

export interface BatchUploadVO extends BatchUploadData {
  productName?: string
  productCoverUrl?: string
  categoryName?: string
}

export class BatchUploadService {
  static getAll(): BatchUploadData[] {
    return batchUploadDataList
  }

  static getById(id: string): BatchUploadData | undefined {
    return batchUploadDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): BatchUploadData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = batchUploadDataList.filter(item => {
      const matchKeyword = !keyword || [item.uploadUrl, item.password ?? ''].some(val => val.toLowerCase().includes(keyword))
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

  static getDetailVO(id: string): BatchUploadVO | undefined {
    const item = this.getById(id)
    return item ? { ...item } : undefined
  }

  static loadPersisted(): BatchUploadData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('batchUploadDataList')
    return raw ? JSON.parse(raw) as BatchUploadData[] : null
  }

  static savePersisted(items: BatchUploadData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('batchUploadDataList', JSON.stringify(items))
  }
}