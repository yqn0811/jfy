import type { DeliveryRecordData, DeliveryRecordVO } from './DeliveryRecordData'

export const deliveryRecordDataList: DeliveryRecordData[] = []

export class DeliveryRecordService {
  static getAll(): DeliveryRecordData[] {
    return deliveryRecordDataList
  }

  static getById(_id: string): DeliveryRecordData | undefined {
    return undefined
  }

  static query(_params: {
    keyword?: string
    filter?: Partial<Record<'type' | 'status' | 'isArchived' | 'dateRange', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): DeliveryRecordData[] {
    return []
  }

  static getRecordVOList(): DeliveryRecordVO[] {
    return []
  }

  static loadPersisted(): DeliveryRecordData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('deliveryRecordDataList')
    }
    return null
  }

  static savePersisted(_items: DeliveryRecordData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('deliveryRecordDataList')
    }
  }
}
