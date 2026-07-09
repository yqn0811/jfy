import type { ReviewLogData } from './SubmissionData'
import { reviewLogDataList } from './SubmissionService'

export class ReviewService {
  static getBySubmissionId(submissionId: string): import('./SubmissionService').ReviewLogVO[] {
    return reviewLogDataList
      .filter((item) => item.submissionId === submissionId)
      .map((item) => ({
        id: item.id,
        reviewerName: item.reviewerName,
        action: item.action,
        result: item.result,
        remark: item.remark,
        createdAt: item.createdAt
      }))
  }

  static query(params: {
    keyword?: string
    filter?: Partial<Record<'action' | 'reviewerId' | 'result', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): ReviewLogData[] {
    const keyword = params.keyword?.trim().toLowerCase() ?? ''
    const filter = params.filter ?? {}
    const sortKey = params.sortKey
    const sortDirection = params.sortDirection ?? 'asc'

    return reviewLogDataList
      .filter((item) => {
        const matchKeyword = !keyword || item.remark.toLowerCase().includes(keyword)
        const matchFilter = Object.entries(filter).every(([key, val]) => {
          if (val === undefined) return true
          const itemVal = (item as any)[key]
          return Array.isArray(val) ? val.includes(String(itemVal)) : String(itemVal) === String(val)
        })
        return matchKeyword && matchFilter
      })
      .sort((a, b) => {
        if (!sortKey) return 0
        const aVal = (a as any)[sortKey]
        const bVal = (b as any)[sortKey]
        if (aVal === bVal) return 0
        const result = aVal > bVal ? 1 : -1
        return sortDirection === 'asc' ? result : -result
      })
  }

  static loadPersisted(): ReviewLogData[] | null {
    if (typeof localStorage === 'undefined') return null
    const raw = localStorage.getItem('reviewLogDataList')
    return raw ? (JSON.parse(raw) as ReviewLogData[]) : null
  }

  static savePersisted(items: ReviewLogData[]): void {
    if (typeof localStorage === 'undefined') return
    localStorage.setItem('reviewLogDataList', JSON.stringify(items))
  }
}
