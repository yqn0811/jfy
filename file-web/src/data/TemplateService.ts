import type { TemplateData } from './TemplateData'

export const templateDataList: TemplateData[] = []

export class TemplateService {
  static getAll(): TemplateData[] {
    return []
  }

  static getById(_id: string): TemplateData | undefined {
    return undefined
  }

  static query(_params: {
    keyword?: string
    filter?: Partial<Record<'industry' | 'status' | 'isOfficial', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TemplateData[] {
    return []
  }

  static loadPersisted(): TemplateData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('templateDataList')
    }
    return null
  }

  static savePersisted(_items: TemplateData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('templateDataList')
    }
  }
}
