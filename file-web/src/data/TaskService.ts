import type { TaskData } from './TaskData'

export interface TaskSummaryVO {
  id: string
  name: string
  type: TaskData['type']
  status: TaskData['status']
  submitProgress: number
  storageUsedGb: number
  storageLimitGb: number
  dueAt: string
  lastUpdatedAt: string
  overdueLevel: TaskData['overdueLevel']
}

export const taskDataList: TaskData[] = []

export class TaskService {
  static getAll(): TaskData[] {
    return taskDataList
  }

  static getById(_id: string): TaskData | undefined {
    return undefined
  }

  static query(_params: {
    keyword?: string
    filter?: Partial<Record<'status' | 'type' | 'overdueLevel' | 'teamId', string | string[]>>
    sortKey?: string
    sortDirection?: 'asc' | 'desc'
  }): TaskData[] {
    return []
  }

  static getSummaryList(): TaskSummaryVO[] {
    return []
  }

  static loadPersisted(): TaskData[] | null {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('taskDataList')
    }
    return null
  }

  static savePersisted(_items: TaskData[]): void {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('taskDataList')
    }
  }
}
