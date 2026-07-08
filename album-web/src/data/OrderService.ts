import { OrderData } from './OrderData'

export const orderDataList: OrderData[] = [
  {
    id: 'order_001',
    packageId: 'plan_001',
    orderNo: 'ORD20260707001',
    amount: '199.00',
    status: 'pending',
    createdAt: '2026-07-07 10:00:00',
    updatedAt: '2026-07-07 10:00:00'
  },
  {
    id: 'order_002',
    packageId: 'plan_002',
    orderNo: 'ORD20260706002',
    amount: '499.00',
    status: 'success',
    createdAt: '2026-07-06 14:10:00',
    updatedAt: '2026-07-06 14:20:00'
  }
]

export interface OrderRecordVO extends OrderData {
  packageName?: string
  packageCapacityMb?: number
}

export class OrderService {
  static getAll(): OrderData[] {
    return orderDataList
  }

  static getById(id: string): OrderData | undefined {
    return orderDataList.find(item => item.id === id)
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<string, string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): OrderData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const list = orderDataList.filter(item => {
      const matchKeyword = !keyword || [item.orderNo, item.amount, item.status].some(val => val.toLowerCase().includes(keyword))
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

  static loadPersisted(): OrderData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('orderDataList')
    return raw ? JSON.parse(raw) as OrderData[] : null
  }

  static savePersisted(items: OrderData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('orderDataList', JSON.stringify(items))
  }
}